/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager.Model');

require('Tinebase/js/widgets/container/GrantsGrid');
const {startTransaction: startBroadcastTransaction} = require("../../Tinebase/js/broadcastClient");

/**
 * @namespace   Tine.Filemanager.Model
 * @class       Tine.Filemanager.Model.Node
 * @extends     Tine.Tinebase.data.Record
 * Example record definition
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Filemanager.Model.NodeMixin = {
    /**
     * virtual nodes are part of the tree but don't exists / are editable
     *
     * NOTE: only "real" virtual node is node with path "otherUsers". all other nodes exist
     *
     * @returns {boolean}
     */
    isVirtual: function() {
        var _ = window.lodash,
            path = this.get('path'),
            parts = _.trim(path, '/').split('/');

        return _.indexOf(['/', '/personal', '/shared'], path) >= 0 || (parts.length == 2 && parts[0] == 'personal');
    },

    getSystemLink: function() {
        return [Tine.Tinebase.common.getUrl().replace(/\/$/, ''), '#',
            Tine.Tinebase.appMgr.get('Filemanager').getRoute(this.get('path'), this.get('type'))].join('/');
    },

    mixinConfig: {
        before: {
            create(o, meta) {
                // NOTE: custom fields of Tree_Nodes are inherited but mc can't show it
                const parentConfig = Tine.Tinebase.Model.Tree_Node.getModelConfiguration();
                _.difference(parentConfig.fieldKeys, meta.modelConfiguration.fieldKeys).forEach((fieldName) => {
                    const idx = parentConfig.fieldKeys.indexOf(fieldName);
                    meta.modelConfiguration.fieldKeys.splice(idx, 0, fieldName);
                    o.splice(idx, 0, {... Tine.Tinebase.Model.Tree_Node.getField(fieldName)});
                    meta.modelConfiguration.fields[fieldName] = {... parentConfig.fields[fieldName]};
                })
                // @TODO: filtermodel?
            }
        }
    },

    statics: {
        type(path) {
            path = String(path);
            const basename = path.split('/').pop(); // do not use basename() here -> recursion!
            return path.lastIndexOf('/') === path.length-1 || basename.lastIndexOf('.') < Math.max(1, basename.length - 5) ? 'folder' : 'file';
        },
        
        dirname(path) {
            const self = Tine.Filemanager.Model.Node;
            const sanitized = self.sanitize(path).replace(/\/$/, '');
            return sanitized.substr(0, sanitized.lastIndexOf('/') + 1);
        },
        
        basename(path, sep='/') {
            const self = Tine.Filemanager.Model.Node;
            const sanitized = self.sanitize(path).replace(/\/$/, '');
            return sanitized.substr(sanitized.lastIndexOf(sep) + 1);
        },
        
        extension(path) {
            const self = Tine.Filemanager.Model.Node;
            return self.type(path) === 'file' ? self.basename(path,'.') : null;
        },

        pathinfo(path) {
            const self = Tine.Filemanager.Model.Node;
            const basename = self.basename(path);
            const extension = self.extension(path);
            return {
                dirname: self.dirname(path),
                basename: basename,
                extension: extension,
                filename: extension ? basename.substring(0, basename.length - extension.length - 1) : null
            }
        },
        
        sanitize(path) {
            path = String(path);
            const self = Tine.Filemanager.Model.Node;
            let isFolder = path.lastIndexOf('/') === path.length -1;
            path = _.compact(path.split('/')).join('/');
            return '/' + path + (isFolder || self.type(path) === 'folder' ? '/' : '');
        },
        
        isNameValid: function (name) {
            const forbidden = /[\/\\\:*?"<>|]/;
            return !forbidden.test(name);
        },
        
        getExtension: function(filename) {
            const self = Tine.Filemanager.Model.Node;
            return self.extension(filename);
        },

        registerStyleProvider: function(provider) {
            const ns = Tine.Filemanager.Model.Node;
            ns._styleProviders = ns._styleProviders || [];
            ns._styleProviders.push(provider);
        },

        getStyles: function(node) {
            const ns = Tine.Filemanager.Model.Node;
            return _.uniq(_.compact(_.map(ns._styleProviders || [], (styleProvider) => {
                return styleProvider(node);
            })));
        },

        createFromFile: function(file) {
            return new Tine.Filemanager.Model.Node(Tine.Filemanager.Model.Node.getDefaultData({
                name: file.name ? file.name : file.fileName,  // safari and chrome use the non std. fileX props
                size: file.size || 0,
                contenttype: file.type ? file.type : file.fileType, // missing if safari and chrome 
            }));
        },

        getDownloadUrl: function(record, revision, disposition = 'attachment') {
            if (_.isString(record)) record = {path: record, revision: revision, type: 'file'};
            record = record.data ? record : Tine.Tinebase.data.Record.setFromJson(record, Tine.Filemanager.Model.Node);

            const app = Tine.Tinebase.appMgr.get(this.prototype.appName);
            const path = record.get('path');
            const type = record.get('type');
            const [,root,modelName, recordId ] = String(path).split('/');

            let httpRequest = {
                frontend: 'http',
                revision: revision ?? record.get('revision'),
                disposition: disposition
            };
            if (root === 'records') {
                httpRequest.method = 'Tinebase.downloadRecordAttachment';
                httpRequest.nodeId = record.get('id');
                httpRequest.recordId = recordId;
                httpRequest.modelName = modelName;
            } else {
                if (!!_.get(record, 'json.input')) {
                    httpRequest = {
                        method: 'Tinebase.downloadTempfile',
                        requestType: 'HTTP',
                        tmpfileId: record.data.id,
                        disposition: disposition
                    };
                } else {
                    if (!path) {
                        Ext.Msg.alert(i18n._('Errors'), app.i18n._('Failed to download this file!'));
                    }
                    httpRequest.path = path;
                    if (type === 'file') {
                        httpRequest.method = 'Filemanager.downloadFile';
                        httpRequest.id = record.get('id');
                    }
                    if (type === 'folder') {
                        httpRequest.method = 'Filemanager.downloadFolder';
                        httpRequest.recursive = true;
                        httpRequest.revision = null;
                    }
                }
            }
            return  Ext.urlEncode(httpRequest, Tine.Tinebase.tineInit.requestUrl + '?');
        },

        getDefaultData: function (defaults) {
            return _.assign({
                type: 'file',
                size: 0,
                creation_time: new Date(),
                created_by: Tine.Tinebase.registry.get('currentAccount'),
                revision: 0,
                revision_size: 0,
                isIndexed: false
            }, defaults);
        },
    }
};

// register grants for nodes
Tine.widgets.container.GrantsManager.register('Filemanager_Model_Node', function(container) {
    // TODO get default grants and remove export
    // var grants = Tine.widgets.container.GrantsManager.defaultGrants();
    //grants.push('download', 'publish');
    var grants = ['read', 'add', 'edit', 'delete', 'sync', 'download', 'publish'];

    return grants;
});

Ext.override(Tine.widgets.container.GrantsGrid, {
    downloadGrantTitle: 'Download', // i18n._('Download')
    downloadGrantDescription: 'Permission to download files', // i18n._('Permission to download files')
    publishGrantTitle: 'Publish', // i18n._('Publish')
    publishGrantDescription: 'Permission to create anonymous download links for files', // i18n._('Permission to create anonymous download links for files')
});

// NOTE: atm the activity records are stored as Tinebase_Model_Tree_Node records
Tine.widgets.grid.RendererManager.register('Tinebase', 'Tree_Node', 'revision', function(revision, metadata, record) {
    revision = parseInt(revision, 10);
    var revisionString = Tine.Tinebase.appMgr.get('Filemanager').i18n._('Revision') + " " + revision,
        availableRevisions = record.get('available_revisions');
    // NOTE we have to encode the path here because it might contain quotes or other bad chars
    if (Ext.isArray(availableRevisions) && availableRevisions.indexOf(String(revision)) >= 0) {
       /* if (revision.is_quarantined == '1') {
            return '<img src="images/icon-set/icon_virus.svg" >' + revisionString; @ToDo needs field revision_quarantine
        }*/
        const path =  atob(btoa(record.get('path')));
        return '<a href="#"; onclick="Tine.Filemanager.downloadNode(\'' + path + '\',' + revision
            + '); return false;">' + revisionString + '</a>';

    }else {
        return revisionString;
    }
});

Tine.Filemanager.nodeBackendMixin = {
    
    /**
     * searches all (lightweight) records matching filter
     *
     * @param   {Object} filter
     * @param   {Object} paging
     * @param   {Object} options
     * @return  {Number} Ext.Ajax transaction id
     * @success {Object} root:[records], totalcount: number
     */
    searchRecords: function(filter, paging, options) {
        const cb = options.success;
        options.success = async function (response) {
            const path = _.get(_.find(filter, {field: 'path'}), 'value');
            
            if (path && filter.length === 1) {
                const virtualNodes = await Tine.Tinebase.uploadManager.getProcessingNodesByPath(path);
                _.each(virtualNodes, (nodeData) => {
                    if (!_.find(_.map(response.records, 'data'), {name: nodeData.name})) {
                        response.records.push(new this.recordClass(nodeData));
                    }
                })
            }
    
            cb.apply(cb.scope, arguments);
        }
        return Tine.Tinebase.data.RecordProxy.prototype.searchRecords.apply(this, arguments);
    },
    
    
    /**
     * creating folder
     * 
     * @param name      folder name
     * @param options   additional options
     * @returns
     */
    createFolder: function(name, options) {
        return new Promise((fulfill, reject) => {
            options = options || {};
            _.wrap(_.escape, function(func, text) {
                return '<p>' + func(text) + '</p>';
            });
            
            options.success = (options.success || Ext.emptyFn).createSequence(fulfill);
            options.failure = (options.failure || Ext.emptyFn).createSequence(reject);

            const rc = Tine.Tinebase.data.RecordMgr.get('Tinebase', 'Tree_Node');
            const treeNodeRecord = new rc({});
            const commitBroadcastTransaction = startBroadcastTransaction(treeNodeRecord, 'create', 300000);

            var params = {
                    application : this.appName,
                    filename : name,
                    type : 'folder',
                    method : this.appName + ".createNode"  
            };
            
            options.params = params;
            
            options.beforeSuccess = function(response) {
                const folder = this.recordReader(response);
                this.postMessage('create', folder.data);
                commitBroadcastTransaction(folder);
                return [folder];
            };
            this.doXHTTPRequest(options);
        });
    },
    
    
    /**
     * is automatically called in generic GridPanel
     */
    saveRecord : function(record, request) {
        if(record.hasOwnProperty('fileRecord')) {
            return;
        } else {
            Tine.Tinebase.data.RecordProxy.prototype.saveRecord.call(this, record, request);
        }
    },
    
    /**
     * copy/move folder/files to a folder
     *
     * @param items files/folders to copy
     *
     * @param target
     * @param move
     * @param showConfirmDialog
     * @param params
     */
    copyNodes : function(items, target, move, showConfirmDialog, params) {
        
        var message = '',
            app = Tine.Tinebase.appMgr.get(this.appName);
        
        if(!params) {
        
            if(!target || !items || items.length < 1) {
                return false;
            }
            
            var sourceFilenames = new Array(),
                destinationFilenames = new Array(),
                withOwnGrants = [],
                forceOverwrite = false,
                treeIsTarget = false,
                targetPath = target;
            
            if(target.data) {
                targetPath = target.data.path;
            }
            else if (target.attributes) {
                targetPath = target.attributes.path;
                treeIsTarget = true;
            }
            else if (target.path) {
                targetPath = target.path;
            }

            for(var i=0; i<items.length; i++) {
                var item = items[i];
                var itemData = item.data;
                if(!itemData) {
                    itemData = item.attributes;
                }
                sourceFilenames.push(itemData.path);
                
                var itemName = itemData.name;
                if(typeof itemName == 'object') {
                    itemName = itemName.name;
                }

                destinationFilenames.push(Tine.Filemanager.Model.Node.sanitize(targetPath + (targetPath.match(/\/$/) ? itemName : '')));

                if (itemData.type === 'folder' && itemData.acl_node === itemData.id) {
                    withOwnGrants.push(itemData);
                }
            }
            
            var method = this.appName + ".copyNodes",
                message = app.i18n._('Copying data .. {0}');
            if(move) {
                method = this.appName + ".moveNodes";
                message = app.i18n._('Moving data .. {0}');
            }
            
            params = {
                    application: this.appName,
                    sourceFilenames: sourceFilenames,
                    destinationFilenames: destinationFilenames,
                    forceOverwrite: forceOverwrite,
                    method: method
            };
            
            if (move && withOwnGrants.length && showConfirmDialog) {
                Ext.MessageBox.show({
                    icon: Ext.MessageBox.WARNING,
                    buttons: Ext.MessageBox.OKCANCEL,
                    title: app.i18n._('Confirm Changing of Folder Permissions'),
                    msg: app.i18n._("You are about to move a folder that has its own permissions. These permissions will be lost, and the folder will inherit permissions from its new parent folder."),
                    fn: function(btn) {
                        if (btn === 'ok') {
                            Tine.Filemanager.nodeBackend.copyNodes(items, target, move, false, params);
                        }
                    }
                });
                return false;
            }
        } else {
            message = app.i18n._('Copying data .. {0}');
            if(params.method == this.appName + '.moveNodes') {
                message = app.i18n._('Moving data .. {0}');
            }
        }

        Ext.MessageBox.wait(i18n._('Please wait'), i18n._('Please wait'));

        const rc = Tine.Tinebase.data.RecordMgr.get('Tinebase', 'Tree_Node');
        const commitBroadcastTransactions = items.map((record) => {
            const treeNodeRecord = new rc(record.data);
            return _.partial(startBroadcastTransaction(treeNodeRecord, 'update', 300000), treeNodeRecord);
        });

        Ext.Ajax.request({
            params: params,
            timeout: 300000, // 5 minutes
            scope: this,
            success: function(result, request){
                Ext.MessageBox.hide();

                const recordsData = Ext.util.JSON.decode(result.responseText);
                const grid = app.getMainScreen().getCenterPanel();

                if (grid?.filterToolbar && recordsData.length === 1) {
                    const filters = grid.filterToolbar.getValue();
                    filters.forEach((filter) => {
                        if (filter.field === 'path') {
                            const path = Tine.Filemanager.Model.Node.dirname(recordsData[0].path);
                            filter.value = `${path}${recordsData[0].name}`;
                        }
                    })
                    grid.filterToolbar.setValue(filters);
                }

                _.each(recordsData, (recordData) => {
                    this.postMessage('update', recordData);
                });
                _.each(commitBroadcastTransactions, (t) => { t(); });

                // var nodeData = Ext.util.JSON.decode(result.responseText),
                //     treePanel = app.getMainScreen().getWestPanel().getContainerTreePanel(),
                //     grid = app.getMainScreen().getCenterPanel();
                //
                // // Tree refresh
                // if(treeIsTarget) {
                //
                //     for(var i=0; i<items.length; i++) {
                //
                //         var nodeToCopy = items[i];
                //
                //         if(nodeToCopy.data && nodeToCopy.data.type !== 'folder') {
                //             continue;
                //         }
                //
                //         if(move) {
                //             var copiedNode = treePanel.cloneTreeNode(nodeToCopy, target),
                //                 nodeToCopyId = nodeToCopy.id,
                //                 removeNode = treePanel.getNodeById(nodeToCopyId);
                //
                //             if(removeNode && removeNode.parentNode) {
                //                 removeNode.parentNode.removeChild(removeNode);
                //             }
                //
                //             target.appendChild(copiedNode);
                //             copiedNode.setId(nodeData[i].id);
                //         }
                //         else {
                //             var copiedNode = treePanel.cloneTreeNode(nodeToCopy, target);
                //             target.appendChild(copiedNode);
                //             copiedNode.setId(nodeData[i].id);
                //
                //         }
                //     }
                // }
                //
                // // Grid refresh
                // grid.getStore().reload();
            },
            failure: function(response, request) {
                var nodeData = Ext.util.JSON.decode(response.responseText),
                    request = Ext.util.JSON.decode(request.jsonData);

                Ext.MessageBox.hide();

                Tine.Filemanager.nodeBackend.handleRequestException(nodeData.data, request);
            }
        });
    },
    
    /**
     * upload files
     * 
     * @param {} params Request parameters
     * @param [] uploadKeyArray
     * @param Boolean addToGridStore
     */
    createNodes: function (params, uploadKeyArray, addToGridStore) {
        var app = Tine.Tinebase.appMgr.get(this.appName),
            grid = app.getMainScreen().getCenterPanel(),
            me = this,
            gridStore = grid.store;

        params.application = this.appName;
        params.method = this.appName + '.createNodes';
        params.uploadKeyArray = uploadKeyArray;
        params.addToGridStore = addToGridStore;

        const rc = Tine.Tinebase.data.RecordMgr.get('Tinebase', 'Tree_Node');
        const commitBroadcastTransactions = items.map((record) => {
            const treeNodeRecord = new rc(record.data);
            return _.partial(startBroadcastTransaction(treeNodeRecord, 'update', 300000), treeNodeRecord);
        });

        var onSuccess = (function (response, request) {

            var nodeData = Ext.util.JSON.decode(response.responseText);

            for (var i = 0; i < this.uploadKeyArray.length; i++) {
                var fileRecord = Tine.Tinebase.uploadManager.upload(this.uploadKeyArray[i]);
                var nodeRecord = Tine.Tinebase.data.Record.setFromJson(nodeData[i], Tine.Filemanager.Model.Node);
                
                if (addToGridStore) {

                    var existingRecordIdx = gridStore.find('name', fileRecord.get('name'));
                    if (existingRecordIdx > -1) {
                        gridStore.removeAt(existingRecordIdx);
                        gridStore.insert(existingRecordIdx, nodeRecord);
                    } else {
                        gridStore.add(nodeRecord);
                    }
                    
                }
                
                fileRecord = Tine.Filemanager.nodeBackend.updateNodeRecord(nodeData[i], fileRecord);
                nodeRecord.fileRecord = fileRecord;
            }
            _.each(commitBroadcastTransactions, (t) => { t(); });
        }).createDelegate({uploadKeyArray: uploadKeyArray, addToGridStore: addToGridStore});

        var onFailure = (function (response, request) {

            var nodeData = Ext.util.JSON.decode(response.responseText),
                request = Ext.util.JSON.decode(request.jsonData);

            nodeData.data.uploadKeyArray = this.uploadKeyArray;
            nodeData.data.addToGridStore = this.addToGridStore;
            Tine.Filemanager.nodeBackend.handleRequestException(nodeData.data, request);

        }).createDelegate({uploadKeyArray: uploadKeyArray, addToGridStore: addToGridStore});

        Ext.Ajax.request({
            params: params,
            timeout: 300000, // 5 minutes
            scope: this,
            success: onSuccess || Ext.emptyFn,
            failure: onFailure || Ext.emptyFn
        });
    },
    /**
     * exception handler for this proxy
     * 
     * @param {Tine.Exception} exception
     */
    handleRequestException: function(exception, request) {
        var _ = window.lodash,
            appNS = _.get(Tine, this.appName);
        appNS.handleRequestException(exception, request);
    },
    
    /**
     * updates given record with nodeData from from response
     */
    updateNodeRecord : function(nodeData, nodeRecord) {
        
        for(var field in nodeData) {
            nodeRecord.set(field, nodeData[field]);
        }
        
        return nodeRecord;
    },
    
    statics: {

    }
};

/**
 * @namespace   Tine.Filemanager.Model
 * @class       Tine.Filemanager.Model.DownloadLink
 * @extends     Tine.Tinebase.data.Record
 * Example record definition
 */
Tine.Filemanager.Model.DownloadLink = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.modlogFields.concat([
    { name: 'id' },
    { name: 'node_id' },
    { name: 'url' },
    { name: 'expiry_time', type: 'datetime' },
    { name: 'access_count' },
    { name: 'password' }
]), {
    appName: 'Filemanager',
    modelName: 'DownloadLink',
    idProperty: 'id',
    titleProperty: 'url',
    // ngettext('Download Link', 'Download Links', n); gettext('Download Link');
    recordName: 'Download Link',
    recordsName: 'Download Links'
});

/**
 * download link backend
 */
Tine.Filemanager.downloadLinkRecordBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Filemanager',
    modelName: 'DownloadLink',
    recordClass: Tine.Filemanager.Model.DownloadLink
});

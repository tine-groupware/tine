/*
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import upload from "./upload";

Ext.ns('Tine.Filemanager');

require('./nodeContextMenu');

/**
 * @namespace Tine.Filemanager
 * @class Tine.Filemanager.NodeTreePanel
 * @extends Tine.widgets.container.TreePanel
 *
 * @author Martin Jatho <m.jatho@metaways.de>
 */
Tine.Filemanager.NodeTreePanel = Ext.extend(Tine.widgets.container.TreePanel, {

    filterMode : 'filterToolbar',

    allowMultiSelection : false,

    ddGroup: 'fileDDGroup',
    enableDD: true,

    dataSafeEnabled: false,

    hasGrid: true,

    currentNodePath: null,

    initComponent: function() {
        this.recordClass= Tine.Filemanager.Model.Node;
        this.on('nodedragover', this.onNodeDragOver, this);

        if (! this.app) {
            this.app = Tine.Tinebase.appMgr.get(this.recordClass.getMeta('appName'));
        }

        if (this.readOnly) {
            this.enableDD = false;
        }

        // NOTE: fm tree is initially loaded from grid!
        // this.defaultContainerPath = Tine.Tinebase.container.getMyFileNodePath();

        this.dragConfig = {
            ddGroup: this.ddGroup,
            scroll: this.ddScroll,
            onBeforeDrag: this.onBeforeDrag.createDelegate(this)
        };

        this.dropConfig = {
            ddGroup: this.ddGroup,
            appendOnly: this.ddAppendOnly === true,
            onNodeOver: this.onNodeOver.createDelegate(this)
        };

        Tine.Filemanager.NodeTreePanel.superclass.initComponent.call(this);

        this.plugins = this.plugins || [];

        if (!this.readOnly && this.enableDD) {
            this.plugins.push({
                ptype : 'ux.browseplugin',
                enableFileDialog: false,
                multiple : true,
                handler : this.dropIntoTree
            });
        }
        this.postalSubscriptions = [];
        this.postalSubscriptions.push(postal.subscribe({
            channel: "recordchange",
            topic: [this.recordClass.getMeta('appName'), this.recordClass.getMeta('modelName'), '*'].join('.'),
            callback: this.onRecordChanges.createDelegate(this)
        }));

        this.dataSafeEnabled = !! Tine.Tinebase.areaLocks.getLocks(Tine.Tinebase.areaLocks.dataSafeAreaName).length;
        if (this.dataSafeEnabled) {
            _.each(Tine.Tinebase.areaLocks.getLocks(Tine.Tinebase.areaLocks.dataSafeAreaName), (areaLock) => {
                this.postalSubscriptions.push(postal.subscribe({
                    channel: "areaLocks",
                    topic: areaLock + '.*',
                    callback: this.applyDataSafeState.createDelegate(this)
                }));
            });
            
            this.dataSafeIsLocked = !!Tine.Tinebase.areaLocks.getLocks(Tine.Tinebase.areaLocks.dataSafeAreaName, true).length;
        }
    },

    onDestroy: function() {
        _.each(this.postalSubscriptions, (subscription) => {subscription.unsubscribe()});
        return this.supr().onDestroy.call(this);
    },
    
    onRecordChanges: function (data, e) {
        if (data.type === 'folder') {
            const path = data.path;
            const oldNode = this.getNodeById(data.id) ?? this.getNodeByPath(path);

            if (oldNode && e.topic.match(/\.delete/)) {
                try {
                    oldNode.cancelExpand();
                    oldNode.remove(true);
                } catch (e) {}
                return;
            }
        
            if (oldNode) {
                const selectedNode = this.getSelectionModel().getSelectedNode();
                const oldNodeData = oldNode?.attributes?.nodeRecord?.data;
                const pathChange = oldNodeData && oldNodeData.path !== path;
                const isNodeSelected = oldNode.isSelected();

                Ext.apply(oldNode.attributes, data);
                const updatedNode = oldNode;
                updatedNode.setText(Ext.util.Format.htmlEncode(data.name));
                // NOTE: qtip dosn't work, but implementing is not worth the effort...
                updatedNode.qtip = Tine.Tinebase.common.doubleEncode(data.name);
                updatedNode.attributes.nodeRecord = new this.recordClass(data);
            
                if (updatedNode.attributes?.status !== 'pending') {
                    Ext.fly(updatedNode.ui?.elNode)?.removeClass('x-type-data-pending');
                }
            
                if (pathChange) {
                    const sourceParentNode = updatedNode.parentNode;
                    const targetParentNode = this.getNodeByPath(Tine.Filemanager.Model.Node.dirname(path));
                    const promises = [];
                    if (sourceParentNode) {
                        promises.push(new Promise(resolve => {
                            sourceParentNode.reload(resolve);
                        }));
                    }
                    if (targetParentNode) {
                        promises.push(new Promise(resolve => {
                            targetParentNode.reload(resolve);
                        }));
                    }
                    Promise.all(promises)
                        .then(() => {
                            const pathToSelect = isNodeSelected || !selectedNode?.attributes?.path ? path : selectedNode.attributes.path;
                            this.selectPath(pathToSelect, data, {});
                        })
                }
            
                // in case of path change we need to reload the node (children) as well
                // as the path of all children changed as well
                if (updatedNode.hasChildNodes() && pathChange && !updatedNode.loading) {
                    if (!updatedNode.bufferedReload) {
                        updatedNode.bufferedReload = Function.createBuffered(updatedNode.reload, 100, updatedNode);
                    }
                    updatedNode.bufferedReload();
                }
            }
        }
    },
    
    appendTreeNode: function (parentPath, node) {
        // add / remount node
        const newNode = this.loader.createNode({...node.data});
        try {
            this.expandPath(parentPath, '', (success, parentNode) => {
                this.loader.expandChildNode(parentNode, newNode);
            });
        } catch (e) {}
    },
    
    applyDataSafeState: function(data, e) {
        if (e?.topic && e.topic.includes('ttl')) return;
        
        const wasLocked = this.dataSafeIsLocked;
        this.dataSafeIsLocked = !! Tine.Tinebase.areaLocks.getLocks(Tine.Tinebase.areaLocks.dataSafeAreaName, true).length;
        
        if (this.dataSafeIsLocked != wasLocked) {
            var rootNode = this.getRootNode(),
                selectedNode = this.getSelectionModel().getSelectedNode();

            this.getSelectionModel().suspendEvents();
            rootNode.collapse(true);
            // NOTE: the grid reload expands the tree as well!
            // not clear yet how to detect if a grid is on board as well
            // if (selectedNode) {
            //     me.selectPath(selectedNode.attributes.path, {}, me.getSelectionModel().resumeEvents.bind(me));
            // } else {
            //     rootNode.expand(false, true,  me.getSelectionModel().resumeEvents.bind(me));
            // }
        }
    },
    
    /**
     * An empty function by default, but provided so that you can perform a custom action before the initial
     * drag event begins and optionally cancel it.
     * @param {Object} data An object containing arbitrary data to be shared with drop targets
     * @param {Event} e The event object
     * @return {Boolean} isValid True if the drag event is valid, else false to cancel
     */
    onBeforeDrag : function(data, e) {
        var _ = window.lodash,
            requiredGrant = e.ctrlKey || e.altKey ? 'readGrant' : 'editGrant';

        data.nodes = [_.get(data, 'node.attributes.nodeRecord')];
        
        // @TODO: rethink: do I need delte on the record or parent?
        return !! _.get(data, 'node.attributes.nodeRecord.data.account_grants.' + requiredGrant);
    },

    onNodeOver : function(n, dd, e, data) {
        var action = e.ctrlKey || e.altKey ? 'copy' : 'move',
            cls = Ext.tree.TreeDropZone.prototype.onNodeOver.apply(this.dropZone, arguments);

        return cls != this.dropZone.dropNotAllowed ?
            'tinebase-dd-drop-ok-' + action :
            this.dropZone.dropNotAllowed;
    },

    /**
     * @param {Object} dragOverEvent
     *
     * tree - The TreePanel
     * target - The node being targeted for the drop
     * data - The drag data from the drag source
     * point - The point of the drop - append, above or below
     * source - The drag source
     * rawEvent - Raw mouse event
     * dropNode - Drop node(s) provided by the source.
     * cancel - Set this to true to signal drop not allowed.
     */
    onNodeDragOver: function(dragOverEvent) {
        const action = dragOverEvent.rawEvent.ctrlKey || dragOverEvent.rawEvent.altKey ? 'copy' : 'move';
        const targetNode = _.get(dragOverEvent, 'target.attributes.nodeRecord');
        const sourceNodes = dragOverEvent.data.nodes;

        dragOverEvent.cancel = this.readOnly
            || dragOverEvent.point !== 'append'
            || ! Tine.Filemanager.nodeActionsMgr.checkConstraints(action, targetNode, sourceNodes, {
                targetChildNodes: dragOverEvent.target.childNodes
            });
    },

    /**
     * files/folder got dropped on node
     *
     * @param {Object} dropEvent
     * @private
     */
    onBeforeNodeDrop: function(dropEvent) {
        var nodes = dropEvent.data.nodes,
            target = dropEvent.target;

        const success = Tine[this.appName].nodeBackend.copyNodes(nodes, target, !(dropEvent.rawEvent.ctrlKey  || dropEvent.rawEvent.altKey), true) !== false;

        dropEvent.dropStatus = success;
        return success;
    },

    /**
     * load everything from server
     * @returns {Object} root node definition
     */
    getRoot: function() {
        return {
            path: '/',
            cls: 'tinebase-tree-hide-collapsetool'
        };
    },

    /**
     * Tine.widgets.tree.FilterPlugin
     * returns a filter plugin to be used in a grid
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            this.filterPlugin = new Tine.Filemanager.PathFilterPlugin({
                treePanel: this,
                field: 'path',
                nodeAttributeField: 'path'
            });
        }

        return this.filterPlugin;
    },

    /**
     * returns params for async request
     *
     * @param {Ext.tree.TreeNode} node
     * @return {Object}
     */
    onBeforeLoad: function(node) {
        var path = node.attributes.path;
        var type = Tine.Tinebase.container.path2type(path);
        var owner = Tine.Tinebase.container.pathIsPersonalNode(path);
        var loginName = Tine.Tinebase.registry.get('currentAccount').accountLoginName;

        if (type === 'personal' && owner != loginName) {
            type = 'otherUsers';
        }

        var newPath = path;

        if (type === 'personal' && owner) {
            var pathParts = path.toString().split('/');
            newPath = '/' + pathParts[1] + '/' + loginName;
            if(pathParts[3]) {
                newPath += '/' + pathParts[3];
            }
        }

        var params = {
            method: this.recordClass.getMeta('appName') + '.searchNodes',
            application: this.app.appName,
            owner: owner,
            filter: [
                     {field: 'path', operator:'equals', value: newPath},
                     {field: 'type', operator:'equals', value: 'folder'}
                     ],
            paging: {dir: 'ASC', sort: 'name'}
        };

        return params;
    },

    onBeforeCreateNode: function(attr) {
        Tine.Filemanager.NodeTreePanel.superclass.onBeforeCreateNode.apply(this, arguments);

        attr.leaf = false;

        if(attr.name && typeof attr.name == 'object') {
            Ext.apply(attr, {
                text: Ext.util.Format.htmlEncode(attr.name.name),
                qtip: Tine.Tinebase.common.doubleEncode(attr.name.name)
            });
        }

        // copy 'real' data to a node record NOTE: not a full record as we have no record reader here
        var nodeData = Ext.copyTo({}, attr, this.recordClass.getFieldNames());
        attr.nodeRecord = new this.recordClass(nodeData);

        if(this.dataSafeEnabled && !!attr.nodeRecord.get('pin_protected_node')) {
            attr.cls += ' x-type-data-safe'
        }
        
        if (_.get(arguments[0],'status') === 'pending') {
            attr.cls += ' x-type-data-pending'
        }
        
        attr.cls += ' ' + Tine.Filemanager.Model.Node.getStyles(attr.nodeRecord).join(' ');
    },

    /**
     * initiates tree context menus
     *
     * @private
     */
    initContextMenu: function() {
        const createFolder = {... Tine.Filemanager.nodeActions.CreateFolder};
        createFolder.text = this.app.i18n._(createFolder.text);
        createFolder.handler = () => {
            const currentPath = _.get(this.ctxNode, 'attributes.nodeRecord.data.path');
            const nodeName = Tine.Filemanager.Model.Node.getContainerName();
    
            if (! currentPath) return;
            Ext.MessageBox.prompt(this.app.i18n._('New Folder'), this.app.i18n._('Please enter the name of the new folder:'), async function (btn, text) {
                if (currentPath && btn === 'ok') {
                    if (!text) {
                        Ext.Msg.alert(String.format(this.app.i18n._('No {0} added'), nodeName), String.format(this.app.i18n._('You have to supply a {0} name!'), nodeName));
                        return;
                    }
            
                    if (!Tine.Filemanager.Model.Node.isNameValid(text)) {
                        Ext.Msg.alert(String.format(this.app.i18n._('No {0} added'), nodeName), this.app.i18n._('Illegal characters: ') + text);
                        return;
                    }
            
                    const filename = `${currentPath}${text}/`;
                    await Tine.Filemanager.nodeBackend.createFolder(filename)
                        .then((result) => {
                            this.appendTreeNode(currentPath, result);
                            if (result.data.path === `/shared/${text}/`) {
                                Tine.Filemanager.NodeEditDialog.openWindow({
                                    record: result,
                                    activeTabName: 'Grants'
                                });
                            }
                        })
                        .catch((e) => {
                            if (e.message === "file exists") {
                                Ext.Msg.alert(String.format(this.app.i18n._('No {0} added'), nodeName), this.app.i18n._('Folder with this name already exists!'));
                            }
                        });
                }
            }, this);
        };
        this.ctxMenu = Tine.Filemanager.nodeContextMenu.getMenu({
            actionMgr: Tine.Filemanager.nodeActionsMgr,
            nodeName: this.recordClass.getContainerName(),
            actions: ['reload', createFolder, 'delete', 'rename', 'move', 'edit', 'publish', 'systemLink'],
            scope: this,
            backend: 'Filemanager',
            backendModel: 'Node'
        });

        this.actionUpdater = new Tine.widgets.ActionUpdater({
            recordClass: this.recordClass,
            actions: this.ctxMenu.items
        });
    },

    /**
     * show context menu
     *
     * @param {Ext.tree.TreeNode} node
     * @param {Ext.EventObject} event
     */
    onContextMenu: function(node, event) {
        event.stopEvent();
        Tine.log.debug(node);

        // legacy for reload action
        this.ctxNode = node;

        //@TODO implement selection vs ctxNode if multiselect is allowed
        this.actionUpdater.updateActions(node);

        this.ctxMenu.showAt(event.getXY());
    },

    /**
     * called when tree selection changes
     *
     * @param {} sm     SelectionModel
     * @param {Ext.tree.TreeNode} node
     */
    onSelectionChange: function(sm, node) {
        if (node?.attributes?.status === 'pending') {
            return;
        }
        // this.updateActions(sm, node);
        var grid = this.app.getMainScreen().getCenterPanel(),
            gridSelectionModel = grid.selectionModel,
            actionUpdater = grid.actionUpdater;

        if (this.hasGrid && gridSelectionModel) {
            gridSelectionModel.clearSelections();
        }

        if (actionUpdater) {
            actionUpdater.updateActions(node);
        }

        Tine.Filemanager.NodeTreePanel.superclass.onSelectionChange.call(this, sm, node);
    },

    /**
     * convert filesystem path to treePath
     *
     * NOTE: only the first depth gets converted!
     *       fs pathes of not yet loaded tree nodes can't be converted!
     *
     * @param {String} containerPath
     * @return {String} tree path
     */
    getTreePath: function(containerPath) {
        var _ = window.lodash,
            currentAccount = Tine.Tinebase.registry.get('currentAccount'),
            treePath = '/' + (this.getRootNode()?.id || '') + containerPath
                .replace(/[0-9a-f]{40}\/folders\//, '')
                .replace(new RegExp('^/personal/(' 
                    + _.escapeRegExp(currentAccount.accountLoginName) + '|'
                    + _.escapeRegExp(currentAccount.accountId) + ')' ), '/myUser')
                .replace(/^\/personal/, '/otherUsers')
                .replace(/\/$/, '');

        return treePath;
    },

    /**
     * Expands a specified path in this TreePanel. A path can be retrieved from a node with {@link Ext.data.Node#getPath}
     *
     * NOTE: path does not consist of id's starting from the second depth
     *
     * @param {String} path
     * @param {String} attr (optional) The attribute used in the path (see {@link Ext.data.Node#getPath} for more info)
     * @param {Function} callback (optional) The callback to call when the expand is complete. The callback will be called with
     * (bSuccess, oLastNode) where bSuccess is if the expand was successful and oLastNode is the last node that was expanded.
     */
    expandPath : function(path, attr, callback){
        if (! path.match(/^\/xnode-/)) {
            path = this.getTreePath(path);
        }

        var keys = path.split(this.pathSeparator);
        var curNode = this.root;
        
        if (!curNode) return;
        
        var curPath = curNode.attributes.path;
        var index = 1;
        var f = function(){
            if(++index == keys.length){
                if(callback){
                    callback(true, curNode);
                }
                return;
            }

            if (index > 2) {
                var c = curNode.findChild('path', Tine.Filemanager.Model.Node.sanitize(curPath + keys[index] + '/'));
            } else {
                var c = curNode.findChild('id', keys[index]);
            }
            if(!c){
                if(callback){
                    callback(false, curNode);
                }
                return;
            }
            curNode = c;
            curPath = c.attributes.path;
            c.expand(false, false, f);
        };
        curNode.expand(false, false, f);
    },

    /**
     * Selects the node in this tree at the specified path. A path can be retrieved from a node with {@link Ext.data.Node#getPath}
     * @param {String} path
     * @param {String} attr (optional) The attribute used in the path (see {@link Ext.data.Node#getPath} for more info)
     * @param {Function} callback (optional) The callback to call when the selection is complete. The callback will be called with
     * (bSuccess, oSelNode) where bSuccess is if the selection was successful and oSelNode is the selected node.
     */
    selectPath : function(path, attr, callback) {
        // NOTE: we don't expand here any longer - macos and win don't do it either
        const parent = Tine.Filemanager.Model.Node.dirname(path);
        this.expandPath(parent, attr, function(bSuccess, oLastNode){
            if (oLastNode) {
                const folderNode = _.find(oLastNode.childNodes, (cn) => {
                    return [cn.id, cn.text].indexOf(Tine.Filemanager.Model.Node.basename(path)) >= 0;
                });
                if (folderNode) {
                    folderNode.select();
                }
                if (Ext.isFunction(callback)) {
                    callback.call(true, folderNode);
                }
            }
        }.createDelegate(this));
    },

    /**
     * clone a tree node / create a node from grid node
     *
     * @param node
     * @returns {Ext.tree.AsyncTreeNode}
     */
    cloneTreeNode: function(node, target) {
        var targetPath = target.attributes.path,
            newPath = '',
            copy;

        if(node.attributes) {
            var nodeName = node.attributes.name;
            if(typeof nodeName == 'object') {
                nodeName = nodeName.name;
            }
            newPath = Tine.Filemanager.Model.Node.sanitize(targetPath + nodeName);

            copy = new Ext.tree.AsyncTreeNode({text: node.text, path: newPath, name: node.attributes.name
                , nodeRecord: node.attributes.nodeRecord, account_grants: node.attributes.account_grants});
        }
        else {
            var nodeName = node.data.name;
            if(typeof nodeName == 'object') {
                nodeName = nodeName.name;
            }

            var nodeData = Ext.copyTo({}, node.data, this.recordClass.getFieldNames());
            var newNodeRecord = new this.recordClass(nodeData);

            newPath = Tine.Filemanager.Model.Node.sanitize(targetPath + nodeName);
            copy = new Ext.tree.AsyncTreeNode({text: nodeName, path: newPath, name: node.data.name
                , nodeRecord: newNodeRecord, account_grants: node.data.account_grants});
        }

        copy.attributes.nodeRecord.beginEdit();
        copy.attributes.nodeRecord.set('path', newPath);
        copy.attributes.nodeRecord.endEdit();

        copy.parentNode = target;
        return copy;
    },

    /**
     * handels tree drop of object from outside the browser
     *
     * @param fileSelector
     * @param targetNodeId
     */
    dropIntoTree: async function (fileSelector, event) {

        var treePanel = fileSelector.component,
            app = treePanel.app,
            targetNode,
            targetNodePath;

        var targetNodeId;
        var treeNodeAttribute = event.getTarget('div').attributes['ext:tree-node-id'];
        if (treeNodeAttribute) {
            targetNodeId = treeNodeAttribute.nodeValue;
            targetNode = treePanel.getNodeById(targetNodeId);
            targetNodePath = targetNode.attributes.path;

        }

        let files = fileSelector.getFileList();
        const folderList = _.uniq(_.map(files, (fo) => {
            return fo.fullPath.replace(/\/[^/]*$/, '');
        }));


        if (folderList.includes('') && !Tine.Filemanager.nodeActionsMgr.checkConstraints('create', targetNode.attributes.nodeRecord, _.map(files, Tine.Filemanager.Model.Node.createFromFile))) {
            Ext.MessageBox.alert(
                i18n._('Upload Failed'),
                app.i18n._('It is not permitted to store files in this folder!')
            );

            return;
        }

        await upload(targetNodePath, files);
    },


    /**
     * returns true if node can accept contents
     *
     * @param nodeAttributes
     * @returns boolean
     */
    nodeAcceptsContents: function(nodeAttributes) {
        return !! nodeAttributes;
    }
});

/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager.nodeActions');

require('Filemanager/js/QuickLookPanel');
require('Filemanager/js/FileSystemLinkDialog');

/**
 * @singleton
 */
Tine.Filemanager.nodeActionsMgr = new (Ext.extend(Tine.widgets.ActionManager, {
    constraintsProvider: [],
    actionConfigs: Tine.Filemanager.nodeActions,

    /**
     * register constraint action provider
     * @param {Function} provider function with same signature as checkConstraints
     */
    registerConstraintsProvider: function(provider) {
        this.constraintsProvider.push(provider);
    },

    /**
     * check action constraints for given nodes
     * 
     * @param {String} action (create|delete|move|copy) 
     * @param {Record} targetNode
     * @param {Array}  sourceNodes
     * @param {Object} options
     * @return {Boolean}
     */
    checkConstraints: function(action, targetNode, sourceNodes = [], options = {}) {
        let isAllowed = true;
        const targetPath = _.get(targetNode, 'data.path', _.get(targetNode, 'path'));
        
        if (['create', 'copy', 'move'].indexOf(action) >= 0) {
            // only folders allowed in virtual folders
            if (targetNode.isVirtual()) {
                isAllowed = isAllowed && _.reduce(sourceNodes, (allowed, node) => {
                    return allowed && _.get(node, 'data.type', _.get(node, 'type')) === 'folder';
                }, true);
            }
            
            // add grant for target required
            isAllowed = isAllowed && _.reduce(sourceNodes, (allowed, node) => {
                return allowed && node.id !== targetNode.id
            }, true);
            
            if (action === 'move') {
                isAllowed = isAllowed && _.reduce(sourceNodes, (allowed, node) => {
                    return allowed
                        // delete grant for all sources required
                        && _.get(node, 'data.account_grants.deleteGrant')
                        // sourceFolder must not be parent of target
                        && targetPath.indexOf(_.get(node, 'data.path')) !== 0
                }, true);
            }
            
            // sourceNode != targetNode && source != direct children of target
            isAllowed = isAllowed && _.reduce(sourceNodes, (allowed, node) => {
                const parentId = _.get(node, 'data.parent_id', _.get(node, 'parent_id'));
                return allowed && node.id !== targetNode.id && parentId !== targetNode.id;
            }, true);
        }

        if (action === 'create') {
            // add grant required
            isAllowed = isAllowed && _.get(targetNode, 'data.account_grants.addGrant', false);
        }

        if (action === 'delete') {
            // delete grant required
            isAllowed = isAllowed && _.get(targetNode, 'data.account_grants.deleteGrant', false);
        }

        if (action === 'edit') {
            // edit grant required
            isAllowed = isAllowed && _.get(targetNode, 'data.account_grants.editGrant', false);
        }

        // don't allow any actions 
        if (targetNode.id === 'otherUsers') {
            isAllowed = false;
        }
        
        return _.reduce(this.constraintsProvider, (allowed, constraintsProvider) => {
            return allowed && (constraintsProvider(action, targetNode, sourceNodes, options) !== false);
        }, isAllowed);
    }
}))();

/**
 * helper for disabled field
 *
 * @param action
 * @param grants
 * @param records
 * @param isFilterSelect
 * @param filteredContainers
 * @returns {*}
 */
Tine.Filemanager.nodeActions.actionUpdater = function(action, grants, records, isFilterSelect, filteredContainers) {
    // run default updater
    Tine.widgets.ActionUpdater.prototype.defaultUpdater(action, grants, records, isFilterSelect);
    
    // check filtered node if nothing is selected
    action.initialConfig.filteredContainer = null;
    if (! _.get(records, 'length') && filteredContainers) {
        const filteredContainer = Tine.Tinebase.data.Record.setFromJson(filteredContainers[0], Tine.Filemanager.Model.Node);
        const filteredContainerGrants = _.get(filteredContainers,'[0].account_grants',{});

        records = [filteredContainer];
        Tine.widgets.ActionUpdater.prototype.defaultUpdater(action, filteredContainerGrants, records, false);

        action.initialConfig.filteredContainer = filteredContainer;
    }

    let disabled = _.isFunction(action.isDisabled) ? action.isDisabled() : action.disabled;
    //todo: if record is virtual , disable all actions except the delete action
    
    // node specific checks (@TODO what about folders with quarantined contents?)
    disabled = window.lodash.reduce(records, function(disabled, record) {
        const isVirtual = _.isFunction(record.isVirtual) ? record.isVirtual() : false;
        const isQuarantined = !!+record.get('is_quarantined');
        const constraint = record.get('type') === action.initialConfig.constraint;
        return disabled
            || (!action.initialConfig.allowVirtual && isVirtual)
            || (!action.initialConfig.allowQuarantined && isQuarantined)
            || (action.initialConfig.constraint && !constraint);
    }, disabled);

    action.setDisabled(disabled);
};

// /**
//  * reload
//  */
// Tine.Filemanager.nodeActions.Reload = {
//     app: 'Filemanager',
//     text: 'Reload', // _('Reload'),
//     iconCls: 'x-tbar-loading',
//     handler: function() {
//         var record = this.initialConfig.selections[0];
//         // arg - does not trigger tree children reload!
//         Tine.Filemanager.nodeBackend.loadRecord(record);
//     }
// };

/**
 * create new folder, needs a single folder selection with addGrant
 */
Tine.Filemanager.nodeActions.CreateFolder = {
    app: 'Filemanager',
    requiredGrant: 'addGrant',
    allowMultiple: false,
    // actionType: 'add',
    text: 'Create Folder', // _('Create Folder')
    disabled: true,
    iconCls: 'action_create_folder',
    scope: this,
    handler: function() {
        var app = this.initialConfig.app,
            currentFolderNode = this.initialConfig.selections[0] || this.initialConfig.filteredContainer,
            currentPath = _.get(currentFolderNode, 'data.path'),
            nodeName = Tine.Filemanager.Model.Node.getContainerName();

        if (! currentPath) return;
        const grid = _.get(this, 'initialConfig.selectionModel.grid');
        if (grid) {
            this.editing = true;
            const gridWdgt = grid.ownerCt.ownerCt;
            const defaultFolderName = app.i18n._('New Folder');
            const newRecord = new Tine.Filemanager.Model.Node(Tine.Filemanager.Model.Node.getDefaultData({
                name: defaultFolderName,
                type: 'folder',
                path: `${currentPath}${defaultFolderName}/`,
                account_grants: {
                    addGrant: true,
                    editGrant: true,
                    deleteGrant: true
                }
            }));

            gridWdgt.newInlineRecord(newRecord, 'name', async (localRecord) => {
                let text = localRecord.get('name');
                
                if (!Tine.Filemanager.Model.Node.isNameValid(text)) {
                    Ext.Msg.alert(String.format(app.i18n._('Not renamed {0}'), nodeName), app.i18n._('Illegal characters: ') + text);
                    return;
                }

                return await Tine.Filemanager.nodeBackend.createFolder(`${currentPath}${localRecord.get('name')}/`)
                    .then((result) => {
                        if (result.data.path === `/shared/${text}/`) {
                            Tine.Filemanager.NodeEditDialog.openWindow({
                                record: result,
                                activeTabName: 'Grants'
                            });
                        }
                        return result;
                    })
                    .catch((e) => {
                        window.postal.publish({
                            channel: "recordchange",
                            topic: 'Filemanager.Node.delete',
                            data: localRecord
                        });
                        
                        if (e.message === "file exists") {
                            Ext.Msg.alert(String.format(app.i18n._('No {0} added'), nodeName), app.i18n._('Folder with this name already exists!'));
                        }
                        return false;
                    }).finally(() => {
                        this.editing = false;
                    });
            });
        }
    },
    actionUpdater: function(action, grants, records, isFilterSelect, filteredContainers) {
        var enabled = !isFilterSelect
            && records && records.length === 1
            && records[0].get('type') === 'folder'
            && window.lodash.get(records, '[0].data.account_grants.addGrant', false)
            && Tine.Filemanager.nodeActionsMgr.checkConstraints('create', records[0], [{type: 'folder'}]);

        if (! _.get(records, 'length') && filteredContainers) {
            enabled = _.get(filteredContainers, '[0].account_grants.addGrant', false);
            action.initialConfig.filteredContainer = Tine.Tinebase.data.Record.setFromJson(filteredContainers[0], Tine.Filemanager.Model.Node);
        
            enabled = Tine.Filemanager.nodeActionsMgr.checkConstraints('create', action.initialConfig.filteredContainer, [{type: 'folder'}]) && enabled;
        }
        action.setDisabled(!enabled || this.editing);
    }
};

/**
 * show native file select, upload files, create nodes
 * a single directory node with create grant has to be selected
 * for this action to be active
 * 
 * - allow open node edit dialog with only readGrant,
 * - most panels are readonly if user has no editGrant
 */
// Tine.Filemanager.nodeActions.UploadFiles = {};

/**
 * single file or directory node with readGrant
 */
Tine.Filemanager.nodeActions.Edit = {
    app: 'Filemanager',
    requiredGrant: 'editGrant',
    allowMultiple: false,
    text: 'Edit Properties', // _('Edit Properties')
    iconCls: 'action_edit_file',
    disabled: true,
    // actionType: 'edit',
    scope: this,
    handler: function () {
        const selectedNode = _.get(this, 'initialConfig.selections[0]') ||_.get(this, 'initialConfig.filteredContainer')
        if(selectedNode) {
            Tine.Filemanager.NodeEditDialog.openWindow({record: selectedNode});
        }
    },
    actionUpdater: function(action, grants, records, isFilterSelect, filteredContainers) {
        Tine.Filemanager.nodeActions.actionUpdater(action, grants, records, isFilterSelect, filteredContainers);
        
        const record = records[0];
        const disabledNodeIds = ['myUser', 'shared', 'otherUsers'];
        if (!record) return;
        if (disabledNodeIds.includes(record.id)) {
            action.setDisabled(true);
        } else {
            action.setDisabled(!record.data?.account_grants?.readGrant);
        }
    },
};

/**
 * single file or directory node with editGrant
 */
Tine.Filemanager.nodeActions.Rename = {
    app: 'Filemanager',
    requiredGrant: 'editGrant',
    allowMultiple: false,
    text: 'Rename', // _('Rename')
    iconCls: 'action_rename',
    disabled: true,
    // actionType: 'edit',
    scope: this,
    handler: function () {
        var _ = window.lodash,
            app = this.initialConfig.app,
            record = this.initialConfig.selections[0],
            nodeName = record.get('type') == 'folder' ?
                Tine.Filemanager.Model.Node.getContainerName() :
                Tine.Filemanager.Model.Node.getRecordName();
        const treePanel = this.initialConfig.selectionModel.tree;
        const grid = treePanel ? treePanel.getFilterPlugin().getGridPanel() : this.initialConfig.selectionModel.grid;
        Ext.MessageBox.show({
            title: String.format(i18n._('Rename {0}'), nodeName),
            msg: String.format(i18n._('Please enter the new name of the {0}:'), nodeName),
            buttons: Ext.MessageBox.OKCANCEL,
            value: record.get('name'),
            fn: function (btn, text) {
                if (btn === 'ok') {
                    if (!text) {
                        Ext.Msg.alert(String.format(i18n._('Not renamed {0}'), nodeName), String.format(i18n._('You have to supply a {0} name!'), nodeName));
                        return;
                    }
                    
                    if (!Tine.Filemanager.Model.Node.isNameValid(text)) {
                        Ext.Msg.alert(String.format(app.i18n._('Not renamed {0}'), nodeName), app.i18n._('Illegal characters: ') + text);
                        return;
                    }

                    this.initialConfig.executor(record, text);
                    
                    if (treePanel && grid?.filterToolbar) {
                        const parent = Tine.Filemanager.Model.Node.dirname(record.get('path'));
                        grid.filterToolbar.setValue([{field: 'path', operator: 'equals', value: `${parent}${text}`}]);
                    }
                }
            },
            scope: this,
            prompt: true,
            icon: Ext.MessageBox.QUESTION
        });
    },

    executor: function(record, text) {
        // @TODO validate filename
        const targetPath = Tine.Filemanager.Model.Node.dirname(record.get('path')) + text;
        Tine.Filemanager.nodeBackend.copyNodes([record], targetPath, true, false);
    }
};

/**
 * single file or directory node with editGrant
 */
Tine.Filemanager.nodeActions.SystemLink = {
    app: 'Filemanager',
    requiredGrant: 'readGrant',
    allowMultiple: false,
    allowVirtual: true,
    allowQuarantined: true,
    text: 'System Link', // _('System Link')
    iconCls: 'action_system_link',
    disabled: true,
    // actionType: 'edit',
    scope: this,
    handler: function () {
        var _ = window.lodash,
            app = this.initialConfig.app,
            record = _.get(this, 'initialConfig.selections[0]') ||_.get(this, 'initialConfig.filteredContainer');
    
        Tine.Filemanager.FileSystemLinkDialog.openWindow({
            title: i18n._('System Link'),
            link: record.getSystemLink()
        });
    },
    actionUpdater: Tine.Filemanager.nodeActions.actionUpdater
};

/**
 * one or multiple nodes, all need deleteGrant
 */
Tine.Filemanager.nodeActions.Delete = {
    app: 'Filemanager',
    requiredGrant: 'deleteGrant',
    allowMultiple: true,
    allowQuarantined: true,
    text: 'Delete', // _('Delete')
    disabled: true,
    iconCls: 'action_delete',
    scope: this,
    handler: function (button, event) {
        var app = this.initialConfig.app,
            nodeName = '',
            nodes = _.get(this, 'initialConfig.selections', []).length ?
                _.get(this, 'initialConfig.selections', []) :
                _.compact([_.get(this, 'initialConfig.filteredContainer')]);

        if (nodes && nodes.length) {
            for (var i = 0; i < nodes.length; i++) {
                var currNodeData = nodes[i].data;
                nodeName += Tine.Tinebase.EncodingHelper.encode(typeof currNodeData.name == 'object' ?
                    currNodeData.name.name :
                    currNodeData.name) + '<br />';
            }
        }

        this.conflictConfirmWin = Tine.widgets.dialog.FileListDialog.openWindow({
            modal: true,
            allowCancel: false,
            height: 180,
            width: 300,
            title: app.i18n._('Do you really want to delete the following files?'),
            text: nodeName,
            scope: this,
            handler: async function (button) {
                if (nodes && button === 'yes') {
                    try {
                        // when user delete virtual node , make sure delete the related tasks in localstorage
                        await Tine.Tinebase.uploadManager.removeTasksByNode(nodes);
                        
                        // announce delete before server delete to improve ux
                        nodes.forEach(record => {
                            window.postal.publish({
                                channel: "recordchange",
                                topic: 'Filemanager.Node.delete',
                                data: record.data
                            });
                        });
                        
                        const noneVirtualNodes = nodes.filter(n => n?.data?.status !== Tine.Tinebase.uploadManager.status.CANCELLED);
                        await Tine.Filemanager.deleteNodes(noneVirtualNodes.map(n => n.data.path));
                    } catch (e) {
                        Tine.Tinebase.ExceptionHandler.handleRequestException(e.data);
                    }
                }

                for (var i = 0; i < nodes.length; i++) {
                    var node = nodes[i];

                    if (node.fileRecord) {
                        var upload = await Tine.Tinebase.uploadManager.getUpload(node.fileRecord.get('uploadKey'));
                        if (upload) {
                            upload.setPaused(true);
                            Tine.Tinebase.uploadManager.unregisterUpload(upload.id);
                        }
                    }

                }
            }
        });
    },
    actionUpdater: Tine.Filemanager.nodeActions.actionUpdater
};

/**
 * one node with readGrant
 */
// Tine.Filemanager.nodeActions.Copy = {};

/**
 * one or multiple nodes with read, edit AND deleteGrant
 */
Tine.Filemanager.nodeActions.Move = {
    app: 'Filemanager',
    requiredGrant: 'editGrant',
    allowMultiple: true,
    text: 'Move', // _('Move')
    disabled: true,
    actionType: 'edit',
    scope: this,
    iconCls: 'action_move',
    handler: function() {
        var app = this.initialConfig.app,
            i18n = app.i18n,
            records = this.initialConfig.selections;

        var filePickerDialog = new Tine.Filemanager.FilePickerDialog({
            windowTitle: app.i18n._('Move Items'),
            singleSelect: true,
            mode: 'target',
            constraint: (targetNode) => {
                return Tine.Filemanager.nodeActionsMgr.checkConstraints('move', targetNode, records);
            }
        });

        filePickerDialog.on('apply', function(node) {
            Tine.Filemanager.nodeBackend.copyNodes(records, node[0], true, true);
        });

        filePickerDialog.openWindow();
    }
};

/**
 * one file node with download grant
 */
Tine.Filemanager.nodeActions.Download = {
    app: 'Filemanager',
    requiredGrant: 'downloadGrant',
    allowMultiple: false,
    allowQuarantined: false,
    actionType: 'download',
    text: 'Save locally', // _('Save locally')
    iconCls: 'action_filemanager_save_all',
    disabled: true,
    scope: this,
    init: function() {
        this.hidden = this.hidden || !Tine.Tinebase.configManager.get('downloadsAllowed');
    },
    handler: function() {
        Tine.Filemanager.downloadNode(this.initialConfig.selections[0]);
    },
    actionUpdater: function(action, grants, records, isFilterSelect) {
        const isQuarantined = !action.initialConfig.allowQuarantined && records[0] && !!+records[0].get('is_quarantined');
        const enabled = !isFilterSelect
            && records && records.length === 1
            && (
                String(_.get(records, '[0].data.path', '')).match(/^\/records/)
                || _.get(records, '[0].data.account_grants.downloadGrant', false)
            );

        action.setDisabled(!enabled || isQuarantined);
    }
};

/**
 * one file node with readGrant
 */
Tine.Filemanager.nodeActions.Preview = {
    app: 'Filemanager',
    allowMultiple: false,
    requiredGrant: 'readGrant',
    text: 'Preview', // _('Preview')
    disabled: true,
    iconCls: 'action_preview',
    scope: this,
    constraint: 'file',
    handler: function () {
        var selection = _.get(this, 'initialConfig.selections[0]') ||_.get(this, 'initialConfig.filteredContainer');

        if (selection) {
            if (selection.get('type') === 'file') {
                Tine.Filemanager.QuickLookPanel.openWindow({
                    record: selection,
                    initialApp: this.initialConfig.initialApp || null,
                    sm: this.initialConfig.sm
                });
            }
        }
    },
    actionUpdater: Tine.Filemanager.nodeActions.actionUpdater
};

/**
 * one node with publish grant
 * 
 * - user needs both publishGrant and downloadGrant to execute this action.
 */
Tine.Filemanager.nodeActions.Publish = {
    app: 'Filemanager',
    allowMultiple: false,
    requiredGrant: 'publishGrant',
    text: 'Publish', // _('Publish')
    disabled: true,
    iconCls: 'action_publish',
    scope: this,
    handler: function() {
        const app = this.initialConfig.app;
        const selected = _.get(this, 'initialConfig.selections[0]') || _.get(this, 'initialConfig.filteredContainer');
        if (! selected) return;
        
        const date = new Date();
        const defaultValidDate = Tine.Tinebase.configManager.get('publicDownloadDefaultValidTime', 'Filemanager') ?? 30;
        date.setDate(date.getDate() + defaultValidDate);
        
        this.validDateField  = new Ext.form.DateField({
            fieldLabel: app.i18n._('Valid until'),
            value: date
        });
        
        const passwordDialog = new Tine.Tinebase.widgets.dialog.PasswordDialog({
            allowEmptyPassword: true,
            locked: false,
            questionText: app.i18n._('Download links can be protected with a password. If no password is specified, anyone who knows the link can access the selected files.'),
            additionalFields: [this.validDateField]
        });
        
        passwordDialog.openWindow();
        passwordDialog.on('apply',  (password)=> {
            const record = new Tine.Filemanager.Model.DownloadLink({
                node_id: selected.id,
                expiry_time: this.validDateField.value,
                password: password
            });

            Tine.Filemanager.downloadLinkRecordBackend.saveRecord(record, {
                success: function (record) {
                    Tine.Filemanager.FilePublishedDialog.openWindow({
                        title: selected.data.type === 'folder' ? app.i18n._('Folder has been published successfully') : app.i18n._('File has been published successfully'),
                        record: record,
                        password: password
                    });
                }, failure: Tine.Tinebase.ExceptionHandler.handleRequestException, scope: this
            });
        }, this);
    },
    actionUpdater: function(action, grants, records, isFilterSelect, filteredContainers) {
        Tine.Filemanager.nodeActions.actionUpdater(action, grants, records, isFilterSelect, filteredContainers);
        
        const record = records[0];
        if (record) {
            action.setDisabled(!record.data?.account_grants?.downloadGrant);
        }
    },
};

/**
 * one or multiple file nodes currently uploaded
 */
Tine.Filemanager.nodeActions.PauseUploadAction = {};

/**
 * one or multiple file nodes currently upload paused
 */
Tine.Filemanager.nodeActions.ResumeUploadAction = {};

/**
 * one or multiple file nodes currently uploaded or upload paused
 * @TODO deletes node as well?
 */
Tine.Filemanager.nodeActions.CancelUploadAction = {};

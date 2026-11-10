/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philip Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO         refactor this (don't use Ext.getCmp, etc.)
 */

/*global Ext, Tine, Locale*/

Ext.ns('Tine.Admin.Tags');

/**
 * @namespace   Tine.Admin.Tags
 * @class       Tine.Admin.Tags.EditDialog
 * @extends     Tine.widgets.dialog.EditRecord
 */
Tine.Admin.Tags.EditDialog = Ext.extend(Tine.widgets.dialog.EditRecord, {
    // context of the user manual
    canonicalName: '/Admin/EditDialog/Tag',

    
    /**
     * var tag
     */
    tag: null,

    windowNamePrefix: 'AdminTagEditDialog_',
    id: 'tagDialog',
    layout: 'fit',
    border: false,
    labelWidth: 120,
    labelAlign: 'top',
    
    handlerApplyChanges: function (button, event, closeWindow) {
        var form = this.getForm();
        
        if (form.isValid()) {
            Ext.MessageBox.wait(this.translation.gettext('Please wait'), this.translation.gettext('Updating Tag'));
            
            var tag = this.tag;
            
            // fetch rights
            tag.data.rights = [];
            this.rightsStore.each(function (item) {
                tag.data.rights.push(item.data);
            });
            
            // fetch contexts
            tag.data.contexts = [];
            var anycontext = true;
            var contextPanel = Ext.getCmp('adminSharedTagsContextPanel');
            contextPanel.getRootNode().eachChild(function (node) {
                if (node.attributes.checked) {
                    tag.data.contexts.push(node.id);
                } else {
                    anycontext = false;
                }
            });
            if (anycontext) {
                tag.data.contexts = ['any'];
            } else {
                // keep contexts of apps not shown in the tree
                tag.data.contexts.push(...this.hiddenContexts);
            }
            tag.data.context_models = this.getContextModels();
            
            form.updateRecord(tag);
            
            Ext.Ajax.request({
                params: {
                    method: 'Admin.saveTag', 
                    tagData: tag.data
                },
                success: function (response) {
                    this.fireEvent('update', Ext.util.JSON.encode(this.tag.data));
                    Ext.MessageBox.hide();
                    if (closeWindow === true) {
                        this.window.close();
                    } else {
                        this.onRecordLoad(response);
                    }
                },
                failure: function (result, request) {
                    var responseText = Ext.util.JSON.decode(result.responseText),
                        message = (responseText && responseText.message) ? responseText.message : 'unknown error';
                    
                    Ext.MessageBox.alert(
                        this.translation._('Failed'),
                        String.format(this.translation._('Could not save tag ({0})'), message)
                    );
                },
                scope: this 
            });
        } else {
            Ext.MessageBox.alert(this.translation.gettext('Errors'), this.translation.gettext('Please fix the errors noted.'));
        }
    },

    /**
     * function updateRecord
     */
    updateRecord: function (tagData) {
        // if tagData is empty (=array), set to empty object because array wont work!
        if (tagData.length === 0) {
            tagData = {};
        }
        this.tag = new Tine.Tinebase.Model.Tag(tagData, tagData.id ? tagData.id : 0);
        
        if (! tagData.rights) {
            tagData.rights = Tine.Tinebase.configManager.get('anyoneAccountDisabled') ? [] : [{
                tag_id: '', //todo!
                account_name: 'Anyone',
                account_id: 0,
                account_type: 'anyone',
                view_right: true,
                use_right: true
            }];
        }
        
        this.rightsStore.loadData({
            results:    tagData.rights,
            totalcount: tagData.rights.length
        });
        
        this.anyContext = ! tagData.contexts || tagData.contexts.indexOf('any') > -1;
        this.appModels = _.groupBy(tagData.context_models || [], (model) => model.split('_')[0]);
        this.createTreeNodes(tagData.appList);
        this.updateTreeNodes();
        this.showAppModels(null);
        this.getForm().loadRecord(this.tag);
    },

    /**
     * function updateToolbarButtons
     */
    updateToolbarButtons: function (rights) {
       /* if(_rights.editGrant === true) {
            Ext.getCmp('tagDialog').action_saveAndClose.enable();
            Ext.getCmp('tagDialog').action_applyChanges.enable();
        }
       */

    },
    
    createTreeNodes: function (appList) {
        // clear old childs
        var toRemove = [];
        this.rootNode.eachChild(function (node) {
            toRemove.push(node);
        });
        // don't duplicate tree nodes on 'apply changes'
        toRemove.forEach((node) => node.remove());
        // NOTE: apps might be hidden as the models of an app are not available (e.g. no right to run the app)
        this.hiddenContexts = [];
        this.hiddenModelApps = [];

        for (var i = 0, j = appList.length; i < j; i += 1) {
            
            var appData = appList[i];
            if (appData.name === 'Tinebase') {
                continue;
            }
            // only apps with models with tags
            if (!(this.availableModels[appData.name] || []).length) {
                if (this.anyContext || this.tag.get('contexts').indexOf(appData.id) > -1) {
                    this.hiddenContexts.push(appData.id);
                }
                if ((this.appModels[appData.name] || []).length) {
                    this.hiddenModelApps.push(appData.name);
                }
                continue;
            }
            // get translated app title
            var app = Tine.Tinebase.appMgr.get(appData.name),
                appTitle = (app) ? app.getTitle() : appData.name;
            
            this.rootNode.appendChild(new Ext.tree.TreeNode({
                text: appTitle,
                appTitle: appTitle,
                appName: appData.name,
                id: appData.id,
                checked: this.anyContext || this.tag.get('contexts').indexOf(appData.id) > -1,
                leaf: true,
                iconCls: 'x-tree-node-leaf-checkbox'
            }));
        }
    },

    /**
     * models of the checked apps (apps with models are restricted to these models)
     */
    getContextModels: function () {
        const models = _.flatten(this.hiddenModelApps.map((appName) => this.appModels[appName]));
        this.rootNode.eachChild((node) => {
            if (node.attributes.checked) {
                models.push(...(this.appModels[node.attributes.appName] || []));
            }
        });
        return models;
    },

    updateTreeNodes: function () {
        this.rootNode.eachChild((node) => {
            const count = (this.appModels[node.attributes.appName] || []).length;
            node.setText(node.attributes.appTitle + (count ?
                ` (${String.format(this.translation.ngettext('restricted to {0} model', 'restricted to {0} models', count), count)})` : ''));
            node.getUI()[count ? 'addClass' : 'removeClass']('admin-tag-context-restricted');
        });
    },

    /**
     * shows the models of the app (node) to restrict the app to
     */
    showAppModels: function (node) {
        const panel = this.modelsPanel;
        panel.removeAll(true);

        if (!node) {
            panel.setTitle(this.translation.gettext('Restrict to Models'));
            panel.add({
                xtype: 'box',
                cls: 'admin-tag-context-models-hint',
                html: Ext.util.Format.htmlEncode(this.translation.gettext('The tag can be used in the checked applications. Select an application to restrict the tag to some of its models.'))
            });
        } else {
            const appName = node.attributes.appName;
            const selected = this.appModels[appName] || [];
            const models = _.uniq((this.availableModels[appName] || []).concat(selected));

            panel.setTitle(String.format(this.translation.gettext('Restrict {0} to Models'), Ext.util.Format.htmlEncode(node.attributes.appTitle)));
            panel.add({
                xtype: 'box',
                cls: 'admin-tag-context-models-hint',
                html: Ext.util.Format.htmlEncode(models.length ?
                    this.translation.gettext('Without selection the tag can be used for all models of the application.') :
                    this.translation.gettext('The application has no models with tags.'))
            });
            _.sortBy(models.map((model) => {
                const recordClass = Tine.Tinebase.data.RecordMgr.get(model);
                return { model, name: recordClass ? recordClass.getRecordsName() : model };
            }), 'name').forEach(({ model, name }) => {
                panel.add({
                    xtype: 'checkbox',
                    hideLabel: true,
                    name: `context_model_${model}`,
                    cls: 'admin-tag-context-model',
                    modelName: model,
                    boxLabel: `${Ext.util.Format.htmlEncode(name)} <span class="admin-tag-context-model-class">${Ext.util.Format.htmlEncode(model.split('_Model_')[1] || model)}</span>`,
                    checked: selected.indexOf(model) > -1,
                    listeners: {
                        check: (cb, checked) => this.onModelCheck(node, model, checked),
                        scope: this
                    }
                });
            });
        }
        panel.doLayout();
    },

    onModelCheck: function (node, model, checked) {
        const appName = node.attributes.appName;
        const models = _.without(this.appModels[appName] || [], model);
        this.appModels[appName] = checked ? models.concat(model) : models;
        // restricting an app implies using it
        if (checked && !node.attributes.checked) {
            node.getUI().toggleCheck(true);
        }
        this.updateTreeNodes();
    },

    /**
     * unchecking an app removes its models
     */
    onContextCheckChange: function (node, checked) {
        if (checked || !(this.appModels[node.attributes.appName] || []).length) return;
        delete this.appModels[node.attributes.appName];
        this.updateTreeNodes();
        if (this.contextTree.getSelectionModel().getSelectedNode() === node) {
            this.showAppModels(node);
        }
    },

    /**
     * function display
     */
    getFormContents: function () {

        this.rootNode = new Ext.tree.TreeNode({
            text: this.translation.gettext('Allowed Contexts'),
            expanded: true,
            draggable: false,
            allowDrop: false
        });
        var contextTree = this.contextTree = new Ext.tree.TreePanel({
            id: 'adminSharedTagsContextPanel',
            region: 'west',
            width: 260,
            split: true,
            autoScroll: true,
            rootVisible: true,
            border: false,
            root: this.rootNode,
            listeners: {
                checkchange: this.onContextCheckChange,
                scope: this
            }
        });
        contextTree.getSelectionModel().on('selectionchange', (sm, node) => {
            this.showAppModels(node && node !== this.rootNode ? node : null);
        });

        // only models with tags can be selected (modelconfig: hasTags, legacy models: tags field)
        this.availableModels = _.reduce(Tine.Tinebase.data.RecordMgr.items, (byApp, recordClass) => {
            if (_.isFunction(recordClass.hasField) && recordClass.hasField('tags')
                && recordClass.getMeta('appName') !== 'Tinebase') {
                (byApp[recordClass.getMeta('appName')] = byApp[recordClass.getMeta('appName')] || [])
                    .push(recordClass.getPhpClassName());
            }
            return byApp;
        }, {});

        this.modelsPanel = new Ext.Panel({
            cls: 'admin-tag-context-models',
            region: 'center',
            border: false,
            autoScroll: true,
            layout: 'form',
            bodyStyle: 'padding: 5px;',
            title: this.translation.gettext('Restrict to Models')
        });

        var contextPanel = new Ext.Panel({
            title: this.translation.gettext('Context'),
            canonicalName: 'Contexts',
            layout: 'border',
            border: false,
            items: [contextTree, this.modelsPanel]
        });
        // sort nodes in context panel by text property
        var treeSorter = new Ext.tree.TreeSorter(contextTree, {
            folderSort: true,
            dir: "asc"
        });
        
        this.rightsPanel = new Tine.widgets.account.PickerGridPanel({
            title: this.translation.gettext('Account Rights'),
            canonicalName: 'Rights',
            store: this.rightsStore,
            recordClass: Tine.Admin.Model.TagRight,
            hasAccountPrefix: true,
            selectType: 'both',
            selectTypeDefault: 'group',
            selectRole: true,
            showHidden: true,
            configColumns: [
                new Ext.ux.grid.CheckColumn({
                    header: this.translation.gettext('View'),
                    dataIndex: 'view_right',
                    width: 55
                }),
                new Ext.ux.grid.CheckColumn({
                    header: this.translation.gettext('Use'),
                    dataIndex: 'use_right',
                    width: 55
                })
            ]
        });

        var editTagDialog = {
            layout: 'border',
            border: false,
            // scroll instead of squeezing the rights/contexts tabpanel below its minHeight
            autoScroll: true,
            items: [{
                region: 'north',
                xtype: 'columnform',
                border: false,
                frame: true,
                autoHeight: true,
                items: [[{
                    columnWidth: 0.8,
                    fieldLabel: this.translation.gettext('Tag Name'), 
                    name: 'name',
                    allowBlank: false,
                    maxLength: 255
                }, {
                    xtype: 'colorfield',
                    columnWidth: 0.2,
                    fieldLabel: this.translation.gettext('Color'),
                    name: 'color'
                }], [{
                    columnWidth: 1,
                    name: 'description',
                    fieldLabel: this.translation.gettext('Description'),
                    anchor: '100%',
                    xtype: 'textarea',
                    height: 150
                }]]
            }, {
                region: 'center',
                xtype: 'tabpanel',
                minHeight: 250,
                activeTab: 0,
                deferredRender: false,
                defaults: { autoScroll: true },
                border: true,
                plain: true,
                items: [
                    this.rightsPanel, 
                    contextPanel
                ]
            }]
        };
        
        return editTagDialog;
    },
    
    initComponent: function () {
        this.tag = this.tag ? this.tag : new Tine.Tinebase.Model.Tag({}, 0);
        
        this.translation = new Locale.Gettext();
        this.translation.textdomain('Admin');
        
        Ext.Ajax.request({
            scope: this,
            success: this.onRecordLoad,
            params: {
                method: 'Admin.getTag',
                tagId: this.tag.id
            }
        });
        
        this.rightsStore = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'account_id',
            fields: Tine.Admin.Model.TagRight
        });
        
        this.items = this.getFormContents();
        Tine.Admin.Tags.EditDialog.superclass.initComponent.call(this);
    },
    
    onRender: function (ct, position) {
        Tine.widgets.dialog.EditDialog.superclass.onRender.call(this, ct, position);
        
        // generalized keybord map for edit dlgs
        var map = new Ext.KeyMap(this.el, [{
            key: [10, 13], // ctrl + return
            ctrl: true,
            fn: this.handlerApplyChanges.createDelegate(this, [true], true),
            scope: this
        }]);

        this.loadMask = new Ext.LoadMask(ct, {msg: String.format(i18n._('Transferring {0}...'), this.translation.gettext('Tag'))});
        this.loadMask.show();
    },
    
    onRecordLoad: function (response) {
        this.getForm().findField('name').focus(false, 250);
        var recordData = Ext.util.JSON.decode(response.responseText);
        this.updateRecord(recordData);
        
        if (! this.tag.id) {
            this.window.setTitle(this.translation.gettext('Add New Tag'));
        } else {
            this.window.setTitle(String.format(this.translation._('Edit Tag "{0}"'), this.tag.get('name')));
        }

        this.loadMask.hide();
    }     
});

/**
 * Admin Tag Edit Popup
 */
Tine.Admin.Tags.EditDialog.openWindow = function (config) {
    config.tag = config.tag ? config.tag : new Tine.Tinebase.Model.Tag({}, 0);
    var window = Tine.WindowFactory.getWindow({
        width: 650,
        height: 400,
        name: Tine.Admin.Tags.EditDialog.prototype.windowNamePrefix + config.tag.id,
        contentPanelConstructor: 'Tine.Admin.Tags.EditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

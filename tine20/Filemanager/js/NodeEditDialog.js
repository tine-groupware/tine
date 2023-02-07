/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager');

/**
 * @namespace   Tine.Filemanager
 * @class       Tine.Filemanager.NodeEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 *
 * <p>Node Compose Dialog</p>
 * <p></p>
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Filemanager.NodeEditDialog
 */
Tine.Filemanager.NodeEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @private
     */
    windowNamePrefix: 'NodeEditWindow_',
    appName: 'Filemanager',
    tbarItems: null,
    evalGrants: true,
    showContainerSelector: false,
    displayNotes: true,
    requiredSaveGrant: 'readGrant',

    /**
     * @type Tine.Filemanager.DownloadLinkGridPanel
     */
    downloadLinkGrid: null,

    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Filemanager');
        this.recordClass = Tine.Filemanager.Model.Node;
        this.recordProxy = Tine.Filemanager.nodeBackend;
        this.downloadAction = Tine.Filemanager.nodeActionsMgr.get('download');

        this.tbarItems = [this.downloadAction];

        Tine.Filemanager.NodeEditDialog.superclass.initComponent.call(this);
    },

    /**
     * folder or file?
     */
    getFittingTypeTranslation: function (isWindowTitle) {
        if (isWindowTitle) {
            return this.record.data.type == 'folder' ? this.app.i18n._('Edit folder') : this.app.i18n._('edit file');
        } else {
            return this.record.data.type == 'folder' ? this.app.i18n._('Folder') : this.app.i18n._('File');
        }
    },

    /**
     * executed when record is loaded
     * @private
     */
    onRecordLoad: function() {
        Tine.Filemanager.NodeEditDialog.superclass.onRecordLoad.apply(this, arguments);

        this.window.setTitle(this.getFittingTypeTranslation(true));

        if (this.record.data.type !== 'folder') {
            this.grantsPanel.setDisabled(true);
        }
    },
    
    afterRender: function () {
        Tine.Filemanager.NodeEditDialog.superclass.afterRender.apply(this, arguments);
    
        if (this.activeTabName) {
            const activeTab = _.find(this.tabPanelItems, (item) => {
                return item?.title === i18n._(this.activeTabName);
            });
            if (activeTab?.id) {
                this.grantsPanel.ownerCt.setActiveTab(activeTab.id);
            }
        }
    },

    /**
     * returns dialog
     * @return {Object}
     * @private
     */
    getFormItems: function() {
        var me = this,
            _ = window.lodash,
            fsConfig = Tine.Tinebase.configManager.get('filesystem'),
            formFieldDefaults = {
                xtype: 'textfield',
                anchor: '100%',
                labelSeparator: '',
                columnWidth: .5,
                readOnly: true,
                disabled: true
            };

        this.downloadLinkGrid = new Tine.Filemanager.DownloadLinkGridPanel({
            node: this.record,
            app: this.app,
            title: this.app.i18n._('Public Links'),
            editDialog: this
        });

        // require('./GrantsPanel');
        
        this.grantsPanel = new Tine.Filemanager.GrantsPanel({
            app: this.app,
            editDialog: this
        });

        var revisionPanel = {};

        if (_.get(fsConfig, 'modLogActive', false)) {
            revisionPanel = new Tine.Filemanager.RevisionPanel({
                editDialog: this
            });
        }

        this.tabPanelItems = [{
            title: this.getFittingTypeTranslation(false),
            autoScroll: true,
            border: false,
            frame: true,
            layout: 'border',
            items: [{
                region: 'center',
                layout: 'hfit',
                border: false,
                plugins: [{
                    ptype: 'ux.itemregistry',
                    key: ['Filemanager-Node-EditDialog-NodeTab-CenterPanel']
                }],
                items: [{
                    xtype: 'fieldset',
                    layout: 'hfit',
                    autoHeight: true,
                    title: this.getFittingTypeTranslation(false),
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: formFieldDefaults,
                        items: [[{
                            fieldLabel: this.app.i18n._('Name'),
                            requiredGrant: 'editGrant',
                            name: 'name',
                            allowBlank: false,
                            readOnly: false,
                            columnWidth: .75,
                            disabled: false
                        }, {
                            fieldLabel: this.app.i18n._('Type'),
                            name: 'contenttype',
                            columnWidth: .25
                        }], [{
                            xtype: 'displayfield',
                            name: 'isIndexed',
                            hideLabel: true,
                            fieldClass: 'x-ux-displayfield-text',
                            width: 10,
                            setValue: function (value) {
                                var string, color, html = '';
                                if (Tine.Tinebase.configManager.get('filesystem.index_content', 'Tinebase') && me.record.get('type') == 'file') {
                                    string = value ? me.app.i18n._('Indexed') : me.app.i18n._('Not yet indexed');
                                    color = value ? 'green' : 'yellow';
                                    html = ['<span style="color:', color, ' !important;" qtip="', string, '">&bull;</span>'].join('');
                                }

                                this.setRawValue(html);
                            }
                        }, {
                            xtype: 'displayfield',
                            name: 'path',
                            hideLabel: true,
                            fieldClass: 'x-ux-displayfield-text',
                            htmlEncode: true,
                            columnWidth: 1
                        }], [
                            Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
                                userOnly: true,
                                useAccountRecord: true,
                                blurOnSelect: true,
                                fieldLabel: this.app.i18n._('Created By'),
                                name: 'created_by'
                            }), {
                                fieldLabel: this.app.i18n._('Creation Time'),
                                name: 'creation_time',
                                xtype: 'datefield'
                            }
                        ], [
                            Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
                                userOnly: true,
                                useAccountRecord: true,
                                blurOnSelect: true,
                                fieldLabel: this.app.i18n._('Modified By'),
                                name: 'last_modified_by'
                            }), {
                                fieldLabel: this.app.i18n._('Last Modified'),
                                name: 'last_modified_time',
                                xtype: 'datefield'
                            }
                        ]]
                    }]
                }, revisionPanel]
            }, {
                // activities and tags
                layout: 'ux.multiaccordion',
                animate: true,
                region: 'east',
                width: 210,
                split: true,
                collapsible: true,
                collapseMode: 'mini',
                header: false,
                margins: '0 5 0 5',
                border: true,
                items: [
                    new Ext.Panel({
                        title: this.app.i18n._('Description'),
                        iconCls: 'descriptionIcon',
                        layout: 'form',
                        labelAlign: 'top',
                        border: false,
                        items: [{
                            style: 'margin-top: -4px; border 0px;',
                            labelSeparator: '',
                            xtype: 'textarea',
                            name: 'description',
                            hideLabel: true,
                            grow: false,
                            preventScrollbars: false,
                            anchor: '100% 100%',
                            emptyText: this.app.i18n._('Enter description'),
                            requiredGrant: 'editGrant'
                        }]
                    }),
                    new Tine.widgets.tags.TagPanel({
                        app: 'Filemanager',
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    })
                ]
            }]
        },
            new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: 'Tinebase_Model_Tree_Node'
            }),
            this.downloadLinkGrid,
            {
                xtype: 'Tine.Filemanager.UsagePanel', 
                app: this.app, 
                editDialog: this, 
                requiredGrant: 'editGrant',
            },
            this.grantsPanel
        ];

        if (_.get(fsConfig, 'enableNotifications', false) && this.record.data.type === 'folder') {
            var notificationPanel = new Tine.Filemanager.NotificationPanel({
                app: this.app,
                editDialog: this
            });
            this.tabPanelItems.push(notificationPanel)
        }
        
        this.tabPanel = {
            xtype: 'tabpanel',
            plain:true,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }, {
                ptype: 'ux.itemregistry',
                key:   'Filemanager-Node-EditDialog-TabPanel'
            }],
            activeTab: this.activeTab ?? 0,
            border: false,
            items: this.tabPanelItems
        };
        
        return this.tabPanel;
    }
});

/**
 * Filemanager Edit Popup
 *
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Filemanager.NodeEditDialog.openWindow = function (config) {
    const id = config.recordId ?? config.record?.id ?? 0;
    this.activeTabName = config.activeTabName ?? null;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 570,
        name: Tine.Filemanager.NodeEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Filemanager.NodeEditDialog',
        contentPanelConstructorConfig: config
    });

    return window;
};

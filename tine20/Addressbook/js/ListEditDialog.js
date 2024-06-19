/*
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Frederic Heihoff <heihoff@sh-systems.eu>
 * @copyright   Copyright (c) 2009-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/*global Ext, Tine*/

Ext.ns('Tine.Addressbook');

require('Addressbook/js/MailinglistPanel');

/**
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.ListEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * Addressbook Edit Dialog <br>
 * 
 * @author      Frederic Heihoff <heihoff@sh-systems.eu>
 */
Tine.Addressbook.ListEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    windowNamePrefix: 'ListEditWindow_',
    appName: 'Addressbook',
    recordClass: 'Addressbook.List',
    showContainerSelector: true,
    multipleEdit: true,
    displayNotes: true,
    enablePrinting: true,

    /**
     * init component
     */
    initComponent: function () {
        this.on('load', this.resolveMemberData, this);
        this.printer = Tine.Addressbook.Printer.ListRenderer;

        this.memberGridPanel = new Tine.Addressbook.ListMemberRoleGridPanel({
            region: "center",
            frame: true,
            margins: '6 0 0 0'
        });
       
        this.supr().initComponent.apply(this, arguments);
    },

    getFormItems: function () {
        var tabpanelItems = [{
            title: this.app.i18n.n_('Group', 'Groups', 1),
            border: false,
            frame: true,
            layout: 'border',
            items: [{
                region: 'north',
                items: new Ext.form.Label({
                    text: '.',
                    ref: '../../../sysGroupNote',
                })
            },{
                region: 'center',
                layout: 'border',
                items: [{
                    xtype: 'fieldset',
                    region: 'north',
                    autoHeight: true,
                    title: this.app.i18n._('Group Information'),
                    items: [{
                        xtype: 'panel',
                        layout: 'hbox',
                        align: 'stretch',
                        items: [{
                            flex: 1,
                            xtype: 'columnform',
                            autoHeight: true,
                            style:'padding-right: 5px;',
                            items: [[{
                                columnWidth: 1,
                                fieldLabel: this.app.i18n._('Name'),
                                name: 'name',
                                maxLength: 64,
                                allowBlank: false
                            }], [{
                                columnWidth: 1,
                                xtype: 'textfield',
                                fieldLabel: this.app.i18n._('E-Mail'),
                                name: 'email',
                                vtype: 'email',
                                maxLength: 255,
                                allowBlank: true,
                                checkState: function (editDialog, field) {
                                    if (editDialog?.mailingListPanel) {
                                        const checked = editDialog.mailingListPanel.isMailinglistCheckbox.checked;
                                        const field = editDialog.getForm().findField('email');
                                        field.setVisible(checked);
                                        editDialog.doLayout();
                                    }
                                },
                                disabled: ! Tine.Tinebase.common.hasRight('manage_list_email_options', 'Addressbook'),
                            }], [new Tine.Tinebase.widgets.keyfield.ComboBox({
                                columnWidth: 0.75,
                                fieldLabel: this.app.i18n._('List type'),
                                name: 'list_type',
                                app: 'Addressbook',
                                keyFieldName: 'listType',
                                value: '',
                                hidden: this.app.featureEnabled('featureMailinglist')
                            }), {
                                columnWidth: 0.25,
                                xtype: 'checkbox',
                                fieldLabel: this.app.i18n._('System accounts only'),
                                name: 'account_only',
                                anchor: '100%',
                                disabled: true
                            }
                            ]]
                        }]
                    }]
                },
                    // TODO allow user to switch between those two grid panels (card layout?)
                    this.memberGridPanel
                ]
            }, {
                // activities and tags
                region: 'east',
                layout: 'ux.multiaccordion',
                animate: true,
                width: 210,
                split: true,
                collapsible: true,
                collapseMode: 'mini',
                header: false,
                margins: '0 5 0 5',
                border: true,
                items: [
                    new Ext.Panel({
                        // @todo generalise!
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
                            maxLength: 255,
                            grow: false,
                            preventScrollbars: false,
                            anchor: '100% 100%',
                            emptyText: this.app.i18n._('Enter description'),
                            requiredGrant: 'editGrant'
                        }]
                    }),
                    new Tine.widgets.tags.TagPanel({
                        app: 'Addressbook',
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    })
                ]
            }]
        },
            new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: (this.record && ! this.copyRecord) ? this.record.id : '',
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })
        ];

        if (Tine.Tinebase.registry.get('manageImapEmailUser') &&
            Tine.Tinebase.registry.get('manageSmtpEmailUser') &&
            this.app.featureEnabled('featureMailinglist'))
        {
            this.mailingListPanel = new Tine.Addressbook.MailinglistPanel({
                app: this.app,
                editDialog: this,
            });
            tabpanelItems.push(this.mailingListPanel);
        }

        return {
            xtype: 'tabpanel',
            border: false,
            plain: true,
            activeTab: 0,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            items: tabpanelItems
        };
    },
    
    /**
     * checks if form data is valid
     * 
     * @return {Boolean}
     */
    isValid: function () {
        var form = this.getForm();
        var isValid = true;
        
        // you need to fill in one of: n_given n_family org_name
        // @todo required fields should depend on salutation ('company' -> org_name, etc.)
        //       and not required fields should be disabled (n_given, n_family, etc.)
        if (form.findField('name').getValue() === '') {
            var invalidString = String.format(this.app.i18n._('{0} must be given'), this.app.i18n._('Name'));
            
            form.findField('name').markInvalid(invalidString);
            
            isValid = false;
        }
        
        return isValid && Tine.Addressbook.ListEditDialog.superclass.isValid.apply(this, arguments);
    },
    
    /**
     * onRecordLoad
     */
    resolveMemberData: function (editDialog, record, ticketFn) {
        if (this.record.id) {
            if (this.record.get('account_only') === true) {
                this.memberGridPanel.searchCombo.userOnly = true;
            }
        }
    },
    checkStates: function() {
        if (this.loadRequest) {
            return _.delay(_.bind(this.checkStates, this), 250);
        }
        this.supr().checkStates.call(this);
        
        const hasEditGrant = !this.record.id || this.form.findField('container_id')?.selectedContainer?.account_grants?.editGrant;
        const isSysGroup = this.record.get('type') === 'group';
        const allowEditSysFields = hasEditGrant && 
            (!isSysGroup || Tine.Tinebase.common.hasRight('manage_accounts', 'Admin'));
        
        this.sysGroupNote.setText(isSysGroup ?
            this.app.i18n._("This is a system group. To edit this group you need the Admin.ManageAccounts right.") : '');
        
        ['name', 'description', 'email', 'account_only'].forEach((fieldName) => {this.getForm().findField(fieldName).setReadOnly(!allowEditSysFields)});
        this.memberGridPanel.setReadOnly(!allowEditSysFields);
        this.mailingListPanel?.setReadOnly(!allowEditSysFields);
        this.doLayout();
    }
});

/**
 * Opens a new List edit dialog window
 * 
 * @return {Ext.ux.Window}
 */
Tine.Addressbook.ListEditDialog.openWindow = function (config) {
    
    // if a container is selected in the tree, take this as default container
    var treeNode = Ext.getCmp('Addressbook_List_Tree') ? Ext.getCmp('Addressbook_List_Tree').getSelectionModel().getSelectedNode() : null;
    if (treeNode && treeNode.attributes && treeNode.attributes.container.type) {
        config.forceContainer = treeNode.attributes.container;
    } else {
        config.forceContainer = null;
    }
    
    const id = config.recordId ?? config.record?.id ?? 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 610,
        name: Tine.Addressbook.ListEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Addressbook.ListEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

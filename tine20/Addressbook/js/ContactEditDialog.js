/*
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*global Ext, Tine*/
import { getAddressPanels } from "./AddressPanel";
import contactPropertiesGrid from "./ContactPropertiesGrid";

Ext.ns('Tine.Addressbook');

/**
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.ContactEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * Addressbook Edit Dialog <br>
 *
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Addressbook.ContactEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * parse address button
     * @type Ext.Button
     */
    parseAddressButton: null,

    windowNamePrefix: 'ContactEditWindow_',
    appName: 'Addressbook',
    recordClass: 'Addressbook.Model.Contact',
    showContainerSelector: true,
    multipleEdit: true,
    displayNotes: true,

    preferredAddressBusinessCheckbox: null,
    preferredAddressPrivateCheckbox: null,

    getFormItems: function () {
        if (Tine.Tinebase.configManager.get('useMapService')) {
            this.mapPanel = new Tine.Addressbook.MapPanel({
                pos: 30,
                layout: 'fit',
                title: this.app.i18n._('Map'),
                disabled: (Ext.isEmpty(this.record.get('adr_one_lon')) || Ext.isEmpty(this.record.get('adr_one_lat'))) && (Ext.isEmpty(this.record.get('adr_two_lon')) || Ext.isEmpty(this.record.get('adr_two_lat')))
            });

            Tine.widgets.dialog.MultipleEditDialogPlugin.prototype.registerSkipItem(this.mapPanel);

        } else {
            this.mapPanel = new Ext.Panel({
                layout: 'fit',
                title: this.app.i18n._('Map'),
                disabled: true,
                html: ''
            });
        }

        if (Tine.Tinebase.common.hasRight('run', 'Calendar', null) && Tine.Tinebase.appMgr.get('Addressbook').featureEnabled('featureContactEventList') && this.record.id !== 0) {
            this.contactEventPanel = new Tine.Calendar.ContactEventsGridPanel({
                editDialog: this,
                hasFavoritesPanel: false
            });
        } else {
            this.contactEventPanel = null;
        }
        const emailContactToolTip =  this.record.data?.type === 'email_account' ? 
            this.app.i18n._('This field is automatically taken from the email account and therefore cannot be edited here.')
            : '';
    
        var contactNorthPanel = {
            xtype: 'fieldset',
            region: 'north',
            autoHeight: true,
            title: this.app.i18n._('Personal Information'),
            items: [{
                xtype: 'panel',
                layout: 'hbox',
                align: 'stretch',
                plugins: [{
                    ptype: 'ux.itemregistry',
                    key: 'Tine.Addressbook.editDialog.northPanel'
                }],
                items: [{
                    flex: 1,
                    xtype: 'columnform',
                    autoHeight: true,
                    style: 'padding-right: 5px;',
                    items: [
                        [new Tine.Tinebase.widgets.keyfield.ComboBox({
                            fieldLabel: this.app.i18n._('Salutation'),
                            name: 'salutation',
                            app: 'Addressbook',
                            keyFieldName: 'contactSalutation',
                            value: '',
                            columnWidth: 0.35,
                            listeners: {
                                scope: this,
                                'select': function (combo, record, index) {
                                    var jpegphoto = this.getForm().findField('jpegphoto');
                                    // set new empty photo depending on chosen salutation only if user doesn't have own image
                                    jpegphoto.setDefaultImage(record.json.image || 'images/icon-set/icon_undefined_contact.svg');
                                }
                            }
                        }), {
                            columnWidth: Tine.Tinebase.appMgr.get('Addressbook').featureEnabled('featureShortName') ? 0.35 : 0.65,
                            fieldLabel: this.app.i18n._('Title'),
                            name: 'n_prefix',
                            maxLength: 64
                        }, {
                        // This was Phil's idea...
                            columnWidth: Tine.Tinebase.appMgr.get('Addressbook').featureEnabled('featureShortName') ? 0.30 : 0.001,
                            fieldLabel: this.app.i18n._('Short Name'),
                            name: 'n_short',
                            maxLength: 10,
                            hidden: !Tine.Tinebase.appMgr.get('Addressbook').featureEnabled('featureShortName'),
                        }], [{
                            columnWidth: 0.35,
                            fieldLabel: this.app.i18n._('First Name'),
                            name: 'n_given',
                            maxLength: 64,
                        }, {
                            columnWidth: 0.30,
                            fieldLabel: this.app.i18n._('Middle Name'),
                            name: 'n_middle',
                            maxLength: 64,
                        }, {
                            columnWidth: 0.35,
                            fieldLabel: this.app.i18n._('Last Name'),
                            name: 'n_family',
                            maxLength: 255,
                            readOnly: this.record.data?.type === 'email_account',
                            qtip: emailContactToolTip,
                        }], [{
                            columnWidth: 0.65,
                            xtype: 'tine.widget.field.AutoCompleteField',
                            recordClass: this.recordClass,
                            fieldLabel: this.app.i18n._('Company / Organisation'),
                            name: 'org_name',
                            maxLength: 255,
                            readOnly: this.record.data?.type === 'email_account',
                            qtip: emailContactToolTip,
                        }, {
                            columnWidth: 0.35,
                            fieldLabel: this.app.i18n._('Unit'),
                            xtype: 'tine.widget.field.AutoCompleteField',
                            recordClass: this.recordClass,
                            name: 'org_unit',
                            maxLength: 64
                        }
                        ]
                    ]
                },
                    new Ext.ux.form.ImageField({
                        name: 'jpegphoto',
                        width: 90,
                        height: 120,
                        border: true,
                        borderColor: this.record.get('color'),
                        getExtraContextActions: () => {
                            return [
                                {
                                    text: i18n._('Color'),
                                    iconCls: 'action_changecolor',
                                    scope: this,
                                    menu: new Ext.menu.ColorMenu({
                                        scope: this,
                                        listeners: {
                                            show: function (cmp) {
                                                const colorPalette = cmp.items.get(0)
                                                colorPalette.suspendEvents()
                                                colorPalette.select(this.record.get('color'))
                                                colorPalette.resumeEvents()
                                            },
                                            select: function (p, color) {
                                                let oldColor = p.scope.record.get('color');
                                                p.scope.record.set('color', color)
                                                let elem = p.scope.find('name', 'jpegphoto')[0].el;
                                                elem.applyStyles({
                                                    border: '2px solid #'+color
                                                });

                                                if (!oldColor) {
                                                    let w = parseInt(elem.getStyle('width')),
                                                        h = parseInt(elem.getStyle('height'));
                                                    elem.applyStyles({
                                                        width: (w-2)+'px',
                                                        height: (h-2)+'px'
                                                    });
                                                }
                                            },
                                            scope: this
                                        }
                                    })
                                }
                            ]
                        }
                    })
                ]
            }, {
                xtype: 'columnform',
                items: [[
                    !Tine.Tinebase.appMgr.get('Addressbook').featureEnabled('featureIndustry') ?
                        {
                            columnWidth: 0.64,
                            xtype: 'combo',
                            fieldLabel: this.app.i18n._('Display Name'),
                            name: 'n_fn',
                            disabled: true
                        } :
                        (
                            new Tine.Addressbook.IndustrySearchCombo({
                                fieldLabel: this.app.i18n._('Industry'),
                                columnWidth: 0.64,
                                name: 'industry'
                            })
                        ), {
                        columnWidth: 0.36,
                        fieldLabel: this.app.i18n._('Job Title'),
                        name: 'title',
                        xtype: 'tine.widget.field.AutoCompleteField',
                        recordClass: this.recordClass,
                        maxLength: 64
                    }, {
                        width: 110,
                        xtype: 'extuxclearabledatefield',
                        fieldLabel: this.app.i18n._('Birthday (private)'),
                        name: 'bday',
                        requiredGrant: 'privateDataGrant'
                    }
                ]]
            }]
        };

        var contactCenterPanel = {
            xtype: 'fieldset',
            region: 'center',
            layout: 'fit',
            title: this.app.i18n._('Contact Information'),
            plugins: [{
                ptype: 'ux.itemregistry',
                key: 'Tine.Addressbook.editDialog.centerPanel'
            }],
            items: [{
                layout: 'hbox',
                layoutConfig: {
                    align : 'stretch',
                    pack  : 'start'
                },
                items: [contactPropertiesGrid({
                    flex: 1,
                    recordClass: this.recordClass,
                    fields: _.sortBy(_.filter(this.recordClass.getModelConfiguration().fields, field => {
                        return field.specialType?.match(/^Addressbook_Model_ContactProperties_(Phone|Email)/) && !field.disabled;
                    }), ['uiconfig.order'])
                }), contactPropertiesGrid({
                    flex: 1,
                    recordClass: this.recordClass,
                    fields: _.sortBy(_.filter(this.recordClass.getModelConfiguration().fields,field => {
                        return field.specialType?.match(/^Addressbook_Model_ContactProperties_(Url|InstantMessenger)/) && !field.disabled;

                    }).concat(
                        _.find(this.recordClass.getModelConfiguration().fields, {fieldName: 'language'})
                    ), ['uiconfig.order'])
                })]
            }]
        }
        this.postalAddressesTabPanel = Ext.create({
            xtype: 'tabpanel',
            region: 'south',
            border: false,
            deferredRender: false,
            height: 160,
            split: true,
            activeTab: 0,
            defaults: {
                frame: true
            },
            plugins: [{
                ptype: 'ux.itemregistry',
                key: 'Tine.Addressbook.editDialog.southPanel'
            }],
            items: getAddressPanels()
        });

        // activities and tags
        var contactEastPanel = {
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
            plugins: [{
                ptype: 'ux.itemregistry',
                key: 'Tine.Addressbook.editDialog.eastPanel'
            }],
            items: [
                new Ext.Panel({
                    // @todo generalise!
                    title: this.app.i18n._('Note'),
                    iconCls: 'descriptionIcon',
                    layout: 'form',
                    labelAlign: 'top',
                    border: false,
                    items: [{
                        style: 'margin-top: -4px; border 0px;',
                        labelSeparator: '',
                        xtype: 'textarea',
                        name: 'note',
                        hideLabel: true,
                        grow: false,
                        preventScrollbars: false,
                        anchor: '100% 100%',
                        emptyText: this.app.i18n._('Enter Note'),
                        requiredGrant: 'editGrant'
                    }]
                }), new Ext.Panel({
                    title: this.app.i18n._('Groups'),
                    iconCls: 'tinebase-accounttype-group',
                    layout: 'fit',
                    border: false,
                    bodyStyle: 'border:1px solid #B5B8C8;',
                    items: [
                        this.groupsPanel
                    ]
                }), new Tine.widgets.tags.TagPanel({
                    app: 'Addressbook',
                    border: false,
                    bodyStyle: 'border:1px solid #B5B8C8;'
                })
            ]
        };

        var contactTab = {
            title: this.app.i18n.n_('Contact', 'Contacts', 1),
            border: false,
            frame: true,
            layout: 'border',
            items: [{
                region: 'center',
                layout: 'border',
                items: [
                    contactNorthPanel,
                    contactCenterPanel,
                    this.postalAddressesTabPanel
                ]
            }, contactEastPanel]
        };

        var tabs = [
            contactTab,
            this.mapPanel,
            new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: (this.record && !this.copyRecord) ? this.record.id : '',
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            }),
        ];

        if (this.contactEventPanel) {
            tabs.push(this.contactEventPanel);
        }

        return {
            xtype: 'tabpanel',
            border: false,
            plain: true,
            activeTab: 0,
            plugins: [{
                ptype: 'ux.itemregistry',
                key: 'Tine.Addressbook.editDialog.mainTabPanel'
            }, {
                ptype : 'ux.tabpanelkeyplugin'
            }],
            defaults: {
                hideMode: 'offsets'
            },
            items: tabs
        };
    },

    /**
     * init component
     */
    initComponent: function () {
        this.initToolbar();
        this.groupsPanel = new Tine.Addressbook.contactListsGridPanel({
            initialLoadAfterRender: false,
            frame: false
        });
        Tine.Addressbook.ContactEditDialog.superclass.initComponent.apply(this, arguments);
    },

    /**
     * initToolbar
     */
    initToolbar: function() {
        this.parseAddressButton = new Ext.Action({
            text: Tine.Tinebase.appMgr.get('Addressbook').i18n._('Parse address'),
            handler: this.onParseAddress,
            iconCls: 'action_parseAddress',
            disabled: false,
            scope: this,
            enableToggle: true
        });
        this.printButton = new Ext.Action({
            text: Tine.Tinebase.appMgr.get('Addressbook').i18n._('Print contact'),
            handler: this.onPrint,
            iconCls:'action_print',
            disabled: false,
            scope: this
        });

        this.tbarItems = this.tbarItems || [];
        this.tbarItems = this.tbarItems.concat([this.parseAddressButton, this.printButton]);
    },

    onPrint: function(printMode) {
        this.onRecordUpdate();
        var renderer = new Tine.Addressbook.Printer.ContactRenderer();
        renderer.print(this);
    },

    /**
     * parse address handler
     *
     * opens message box where user can paste address
     *
     * @param {Ext.Button} button
     */
    onParseAddress: function (button) {
        if (button.pressed) {
            Ext.Msg.prompt(this.app.i18n._('Paste address'), this.app.i18n._('Please paste an address or a URI to a vcard that should be parsed:'), function(btn, text) {
                if (btn == 'ok'){
                    this.parseAddress(text);
                } else if (btn == 'cancel') {
                    button.toggle();
                }
            }, this, 100);
        } else {
            button.setText(this.app.i18n._('Parse address'));
            this.tokenModePlugin.endTokenMode();
        }
    },

    /**
     * send address to server + fills record/form with parsed data + adds unrecognizedTokens to description box
     *
     * @param {String} address
     */
    parseAddress: function(address) {
        Tine.log.debug('parsing address ... ');

        Tine.Addressbook.parseAddressData(address, function(result, response) {
            Tine.log.debug('parsed address:');
            Tine.log.debug(result);

            if (result.hasOwnProperty('exceptions')) {
                Ext.Msg.alert(this.app.i18n._('Failed to parse address!'), this.app.i18n._('The address could not be read.'));
            } else {
                // only set the fields that could be detected
                Ext.iterate(result.contact, function(key, value) {
                    if (value && ! this.record.get(key)) {
                        this.record.set(key, value);
                    }
                }, this);

                var oldNote = (this.record.get('note')) ? this.record.get('note') : '';

                if (result.hasOwnProperty('unrecognizedTokens')) {
                    this.record.set('note', result.unrecognizedTokens.join(' ') + oldNote);
                }

                this.onRecordLoad();

                this.parseAddressButton.setText(this.app.i18n._('End token mode'));
                this.tokenModePlugin.startTokenMode();
            }
        }, this);
    },

    /**
     * checkDisableEmailField
     */
    checkDisableEmailField: function () {
         if (this.record.get('email')){

            if (Tine.Tinebase.registry.get('primarydomain') && Tine.Tinebase.registry.get('primarydomain') !== ''
                && this.record.get('type') === 'user')
            {
                return true;
            }
        }
        return false;
    },
    

    /**
     * onRecordLoad
     */
    onRecordLoad: function () {
        // NOTE: it comes again and again till
        if (this.rendered) {
            var container = this.record.get('container_id');

            // handle default container
            // TODO should be generalized
            if (! this.record.id && ! Ext.isObject(container)) {
                container = this.app.getMainScreen().getWestPanel().getContainerTreePanel().getSelectedContainer('addGrant', Tine.Addressbook.registry.get('defaultAddressbook'));
                this.record.set('container_id', container);
            }

            if (this.mapPanel instanceof Tine.Addressbook.MapPanel) {
                this.mapPanel.onRecordLoad(this.record);
            }
        }
        if (this.record.id) {
            this.groupsPanel.store.loadData(this.record.get('groups'));

            const preferredAddress = +this.record.get('preferred_address');
            if (this.postalAddressesTabPanel.items.get(preferredAddress)) {
                this.postalAddressesTabPanel.setActiveTab(preferredAddress);
            }
        }
        
        this.supr().onRecordLoad.apply(this, arguments);
        
        if(Tine.Tinebase.registry.get('currentAccount').contact_id == this.record.id) {
            this.enableOwnPrivateFields();
        }
    },

    /**
     * Enable fields we have no privateData grant for, when this is the users own contact (special case).
     */
    enableOwnPrivateFields: function() {
        const fields = [
            'bday',
            'email_home',
            'tel_cell_private',
            'tel_home',
            'tel_fax_home',
            'adr_two_countryname',
            'adr_two_locality',
            'adr_two_postalcode',
            'adr_two_region',
            'adr_two_street',
            'adr_two_street2',
        ]
        fields.forEach((field) => {
            const item = this.getForm().findField(field);
            if (item) item.setDisabled(false);
        })
    }
});

/**
 * Opens a new contact edit dialog window
 *
 * @return {Ext.ux.Window}
 */
Tine.Addressbook.ContactEditDialog.openWindow = function (config) {
    const id = config.recordId ?? config.record?.id ?? 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 610,
        name: Tine.Addressbook.ContactEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Addressbook.ContactEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};


Ext.ux.ItemRegistry.registerItem('Addressbook-Contact-EditDialog-ActivitiesPanel', {
    height: 30,
    readOnly: true,
    hidden: true,
    xtype: 'textarea',
    listeners: {
        afterrender: function() {
            const editDialog = this.findParentBy(function (c) {
                return c instanceof Tine.widgets.dialog.EditDialog
            });
            const grants = _.get(editDialog.getForm().findField('container_id'), 'selectedRecord.data.account_grants', {});
            const hasRequiredGrant = grants.adminGrant || grants.privateGrant;
            this[!hasRequiredGrant ? 'show': 'hide']();
            this.setValue(editDialog.app.i18n._('You do not have the required private grant to view history.'))
        }
    }
}, -10);

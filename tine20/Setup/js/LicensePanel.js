/*
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2009-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine', 'Tine.Setup');

/**
 * Licensecheck Panel
 *
 * @namespace   Tine.Setup
 * @class       Tine.Setup.LicensePanel
 * @extends     Ext.Panel
 *
 * <p>Licensecheck Panel</p>
 * <p><pre>
 * </pre></p>
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Setup.LicensePanel
 */
Tine.Setup.LicensePanel = Ext.extend(Tine.Tinebase.widgets.form.ConfigPanel, {

    /**
     * @property actionToolbar
     * @type Ext.Toolbar
     */
    actionToolbar: null,

    /**
     * @property contextMenu
     * @type Ext.Menu
     */
    contextMenu: null,

    /**
     * @private
     */
    border: false,

    /**
     * license
     */
    license: null,


    /**
     * init component
     */
    initComponent: function () {
        this.initActions();
        
        Tine.Setup.LicensePanel.superclass.initComponent.call(this);
    },
    
       /**
     * @private
     */
    onRender: function (ct, position) {
        Tine.Setup.LicensePanel.superclass.onRender.call(this, ct, position);
        this.initLicense();
    },

    /**
     * init store
     * @private
     */
    initLicense: function () {
        Ext.Ajax.request({
            params: {
                method: 'Setup.getLicense'
            },
            scope: this,
            success: function (response) {
                var data = Ext.util.JSON.decode(response.responseText);

                Ext.getCmp('serialNumber').setValue(data.serialNumber);
                Ext.getCmp('maxUsers').setValue(data.maxUsers);
                Ext.getCmp('validFrom').setValue(new Date(data.validFrom.date));
                Ext.getCmp('validTo').setValue(new Date(data.validTo.date));
                
                Tine.Setup.registry.replace('licenseCheck', !!data.serialNumber);

                this.loadMask.hide();
            }
        });
    },

    initActions: function () {
        this.action_saveLicense = new Ext.Action({
            text: this.app.i18n._('Save'),
            iconCls: 'setup_action_save_config',
            scope: this,
            handler: this.onSaveLicense,
            disabled: false
        });

        this.actionToolbar = new Ext.Toolbar({
            items: [
                this.action_saveLicense
            ]
        });
    },

    /**
     * Save and check license keys
     *
     * @returns {boolean}
     */
    onSaveLicense: function () {
        Ext.Ajax.request({
            params: {
                method: 'Setup.saveLicense',
                license: Ext.getCmp('license_licensekey').getValue(),
                privatekey: Ext.getCmp('license_privatekey').getValue()
            },
            scope: this,
            success: function (response) {
                var data = Ext.util.JSON.decode(response.responseText);

                if (data.hasOwnProperty('error') && data.error == false || ! data.serialNumber) {
                    Ext.Msg.alert('Status', this.app.i18n._('Your license is not valid.'));
                    Tine.Setup.registry.replace('licenseCheck', false);
                    Ext.getCmp('serialNumber').setValue(data.serialNumber);
                    Ext.getCmp('maxUsers').setValue(data.maxUsers);
                    Ext.getCmp('validFrom').reset();
                    Ext.getCmp('validTo').reset();
                } else {
                    Ext.getCmp('serialNumber').setValue(data.serialNumber);
                    Ext.getCmp('maxUsers').setValue(data.maxUsers);
                    Ext.getCmp('validFrom').setValue(new Date(data.validFrom.date));
                    Ext.getCmp('validTo').setValue(new Date(data.validTo.date));
                    Tine.Setup.registry.replace('licenseCheck', true);
                }
            }
        })
        return true;
    },

    /**
     * @private
     *
     */
    getFormItems: function () {
        return [{
            defaults: {
                tabIndex: this.getTabIndex
            },
            border: false,
            autoScroll: true,
            items: [{
                xtype: 'fieldset',
                title: this.app.i18n._('License Information'),
                collapsible: false,
                autoHeight: true,
                defaults: {
                    readOnly: true
                },
                defaultType: 'textfield',
                items: [{
                    fieldLabel: this.app.i18n._('Contract ID'),
                    name: 'serialNumber',
                    id: 'serialNumber',
                    width: 200,
                    emptyText: this.app.i18n._('No valid license')
                }, {
                    fieldLabel: this.app.i18n._('Maximum Users'),
                    name: 'maxUsers',
                    width: 200,
                    id: 'maxUsers',
                    emptyText: this.app.i18n._('No valid license')
                },{
                    fieldLabel: this.app.i18n._('Valid from'),
                    name: 'validFrom',
                    id: 'validFrom',
                    xtype: 'datefield',
                    width: 200,
                    emptyText: this.app.i18n._('No valid license')
                },{
                    fieldLabel: this.app.i18n._('Valid to'),
                    name: 'validTo',
                    id: 'validTo',
                    xtype: 'datefield',
                    width: 200,
                    emptyText: this.app.i18n._('No valid license')
                }]
            }, {
                xtype: 'fieldset',
                title: this.app.i18n._('License Configuration'),
                collapsible: false,
                autoHeight: true,
                defaults: {
                    width: 200,
                    anchor: '-20'
                },
                defaultType: 'textfield',
                items: [{
                    fieldLabel: this.app.i18n._('License Key'),
                    name: 'license_licensekey',
                    id: 'license_licensekey',
                    xtype: 'textarea',
                    height: 250
                }, {
                    fieldLabel: this.app.i18n._('Installation Key'),
                    name: 'license_privatekey',
                    id: 'license_privatekey',
                    xtype: 'textarea',
                    height: 250
                }]
            }]
        }];
    }
});

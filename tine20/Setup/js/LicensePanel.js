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

    initActions: function() {
        this.actionToolbar = new Ext.Toolbar({
            items: []
        });
    },


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
                this.setLicenseInformation(response);

                this.loadMask.hide();
            }
        });
    },

    /**
     * Sets license data to textfields
     * @param response
     */
    setLicenseInformation: function (response) {
        var data = Ext.util.JSON.decode(response.responseText);

        if (data.hasOwnProperty('error') && data.error == false || ! data.serialNumber) {
            if (data.status == 'status_license_invalid') {
                Ext.Msg.alert('Status', this.app.i18n._('Your license is not valid.'));
            }
            Tine.Setup.registry.replace('licenseCheck', false);
            Ext.getCmp('contractId').reset();
            Ext.getCmp('serialNumber').reset();
            Ext.getCmp('maxUsers').reset();
            Ext.getCmp('validFrom').reset();
            Ext.getCmp('validTo').reset();
        } else {
            Ext.getCmp('contractId').setValue(data.contractId);
            Ext.getCmp('serialNumber').setValue(data.serialNumber);
            Ext.getCmp('maxUsers').setValue(data.maxUsers);
            Ext.getCmp('validFrom').setValue(new Date(data.validFrom.date));
            Ext.getCmp('validTo').setValue(new Date(data.validTo.date));
            Tine.Setup.registry.replace('licenseCheck', true);
        }
    },

    /**
     * If license uploaded successfully ..
     */
    onFileReady: function () {
        Ext.Ajax.request({
            params: {
                method: 'Setup.uploadLicense',
                tempFileId: this.uploadLicense.getTempFileId()
            },
            scope: this,
            success: function (response) {
                this.setLicenseInformation(response);
            }
        })
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
                    width: 200,
                    readOnly: true
                },
                defaultType: 'textfield',
                items: [{
                    fieldLabel: this.app.i18n._('Contract ID'),
                    name: 'contractId',
                    id: 'contractId',
                    emptyText: this.app.i18n._('No valid license')
                }, {
                    fieldLabel: this.app.i18n._('Serial Number'),
                    name: 'serialNumber',
                    id: 'serialNumber',
                    emptyText: this.app.i18n._('No valid license')
                }, {
                    fieldLabel: this.app.i18n._('Maximum Users'),
                    name: 'maxUsers',
                    id: 'maxUsers',
                    emptyText: this.app.i18n._('No valid license')
                },{
                    fieldLabel: this.app.i18n._('Valid from'),
                    name: 'validFrom',
                    id: 'validFrom',
                    xtype: 'datefield',
                    emptyText: this.app.i18n._('No valid license')
                },{
                    fieldLabel: this.app.i18n._('Valid to'),
                    name: 'validTo',
                    id: 'validTo',
                    xtype: 'datefield',
                    emptyText: this.app.i18n._('No valid license')
                }]
            }, {
                xtype: 'fieldset',
                title: this.app.i18n._('License Configuration'),
                collapsible: false,
                autoHeight: true,
                defaults: {
                    anchor: '-20',
                    width: 200
                },
                items: [{
                    xtype: 'tw.uploadbutton',
                    fieldLabel: this.app.i18n._('Upload from disk'),
                    ref: '../../uploadLicense',
                    text: String.format(this.app.i18n._('Select file containing your license key')),
                    handler: this.onFileReady,
                    allowedTypes: null,
                    scope: this
                }]
            }]
        }];
    }
});

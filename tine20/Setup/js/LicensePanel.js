/*
 * Tine 2.0
 *
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2009-2015 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * license type switch
     *
     * * allowed values: BusinessEdition
     *
     * TODO: read this from config
     *
     * @property licenseType
     * @type String
     */
    licenseType: 'BusinessEdition',

    /**
     * license
     */
    license: null,

    initActions: function() {
        var items = (Tine.Setup.registry.get('version') && Tine.Setup.registry.get('version').buildType === 'DEVELOPMENT') ? [ new Ext.Action({
            text: this.app.i18n._('Delete current Subscription'),
            iconCls: 'setup_action_uninstall',
            scope: this,
            handler: this.onDeleteLicense
        })] : [];

        this.actionToolbar = new Ext.Toolbar({
            items: items
        });
    },

    onDeleteLicense: function() {
        this.loadMask.show();
        Ext.Ajax.request({
            params: {
                method: 'Setup.deleteLicense'
            },
            scope: this,
            success: function (response) {
                this.loadMask.hide();
                this.setLicenseInformation(response);

                // TODO reset upload button (setText/Icon?)
                //this.uploadLicense.setText('');
            }
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
        this.loadMask.show();
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
        var data = (response.responseText) ? Ext.util.JSON.decode(response.responseText) : {};

        if (data.status && data.status == 'status_license_invalid') {
            Ext.Msg.alert('Status', this.app.i18n._('Your Subscription is not valid.'));
        }

        if (data.hasOwnProperty('error') && data.error == false || ! data.serialNumber) {
            Tine.Setup.registry.replace('licenseCheck', false);
            Ext.getCmp('serialNumber').reset();
            Ext.getCmp('maxUsers').reset();
            Ext.getCmp('organization').reset();
            
            if (this.licenseType === 'BusinessEdition') {
                Ext.getCmp('contractId').reset();
                Ext.getCmp('validFrom').reset();
                Ext.getCmp('validTo').reset();
            }
        } else {
            Ext.getCmp('serialNumber').setValue(data.serialNumber);
            Ext.getCmp('maxUsers').setValue(data.maxUsers);
            Ext.getCmp('organization').setValue(data.organization);
    
            if (this.licenseType === 'BusinessEdition') {
                Ext.getCmp('contractId').setValue(data.contractId);
                Ext.getCmp('validFrom').setValue(new Date(Date.parseDate(String(data.validFrom.date).substr(0, 19), Date.patterns.ISO8601Long)));
                Ext.getCmp('validTo').setValue(new Date(Date.parseDate(String(data.validTo.date).substr(0, 19), Date.patterns.ISO8601Long)));
            }

            Tine.Setup.registry.replace('licenseCheck', data.status && data.status === 'status_license_ok');
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
        var licenseFields = [],
            licenseConfiguration = this.licenseType === 'BusinessEdition' ? {
                xtype: 'fieldset',
                title: this.app.i18n._('Subscription Configuration'),
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
                    text: String.format(this.app.i18n._('Select file containing your Subscription key')),
                    handler: this.onFileReady,
                    allowedTypes: null,
                    uploadTempFileMethod: 'Setup.uploadTempFile',
                    uploadUrl: 'setup.php',
                    scope: this
                }]
            } : {};

        switch (this.licenseType) {
            case 'BusinessEdition':
                const emptyText = i18n._('No valid Subscription');
                licenseFields = [{
                    fieldLabel: i18n._('Contract ID'),
                    name: 'contractId',
                    id: 'contractId',
                    emptyText: emptyText
                }, {
                    fieldLabel: i18n._('Organization'),
                    name: 'organization',
                    id: 'organization',
                    emptyText: emptyText
                }, {
                    fieldLabel: i18n._('Activation Key'),
                    name: 'serialNumber',
                    id: 'serialNumber',
                    emptyText: emptyText
                }, {
                    fieldLabel: i18n._('Maximum Users (0=unlimited)'),
                    name: 'maxUsers',
                    id: 'maxUsers',
                    emptyText: emptyText
                }, {
                    fieldLabel: i18n._('Valid from'),
                    name: 'validFrom',
                    id: 'validFrom',
                    xtype: 'datetimefield',
                    emptyText: emptyText
                }, {
                    fieldLabel: i18n._('Valid to'),
                    name: 'validTo',
                    id: 'validTo',
                    xtype: 'datetimefield',
                    emptyText: emptyText
                }];
                break;
        }

        return [{
            defaults: {
                tabIndex: this.getTabIndex
            },
            border: false,
            autoScroll: true,
            items: [{
                xtype: 'fieldset',
                title: this.app.i18n._('Subscription Information'),
                collapsible: false,
                autoHeight: true,
                defaults: {
                    width: 200,
                    readOnly: true
                },
                defaultType: 'textfield',
                items: licenseFields
            }, licenseConfiguration
            ]
        }];
    }
});

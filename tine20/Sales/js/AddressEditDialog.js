/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

/**
 * @namespace   Tine.Sales
 * @class       Tine.Sales.AddressEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Address Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.AddressEditDialog
 */
Tine.Sales.AddressEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    tbarItems: null,
    evalGrants: false,
    
    windowWidth: 700,
    windowHeight: 500,

    displayNotes: true,
    
    /**
     * just update the contract grid panel, no persisten
     * 
     * @type String
     */
    mode: 'local',
    loadRecord: false,
    
    initComponent: function() {
        if (Ext.isString(this.additionalConfig)) {
            Ext.apply(this, Ext.decode(this.additionalConfig));
        }
        
        Tine.Sales.AddressEditDialog.superclass.initComponent.call(this);
        _.each(Tine.Sales.Model.Address.getModelConfiguration().uiconfig.contactManagedFields, fieldName => {
            this.form.findField(fieldName).fixedIf = (v, record) => _.some(record.get('relations'), rel => rel.type === 'CONTACTADDRESS')
        })
    },

    checkStates: function() {
        Tine.Sales.AddressEditDialog.superclass.checkStates.call(this);

        const rel = _.find(this.record.get('relations'), {type: 'CONTACTADDRESS'});
        this.contactManagedInfo.setVisible(!!rel);
    },

    /**
     * returns canonical path part
     * @returns {string}
     */
    getCanonicalPathSegment: function () {
        return [
            this.supr().getCanonicalPathSegment.call(this),
            Ext.util.Format.capitalize(this.addressType)
        ].join(Tine.Tinebase.CanonicalPath.separator);
    },

    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * @return {Object}
     * @private
     */
    getFormItems: function() {
        var formFieldDefaults = {
            xtype:'textfield',
            anchor: '100%',
            labelSeparator: '',
            columnWidth: 1/2
        };
        
        
        var items = [
            [{
                xtype: 'v-alert',
                ref: '../../../../../../contactManagedInfo',
                hidden: true,
                columnWidth: 1,
                variant: 'info',
                label: this.app.formatMessage('This address is managed by the linked contact. Changes to the readonly fields can be done in that contact directly.'),
            }], [{
                name: 'name_shorthand',
                columnWidth: .2,
                fieldLabel: this.app.i18n._('Name shorthand')
            }, {
                xtype: 'widget-keyfieldcombo',
                columnWidth: .2,
                app:   'Sales',
                keyFieldName: 'languagesAvailable',
                fieldLabel: this.app.i18n._('Language'),
                name: 'language',
                requiredGrant: 'editGrant'
            }, this.fieldManager('email', {columnWidth: .6})], [{
               columnWidth: .045,
               xtype:'button',
               iconCls: 'applyContactData',
               tooltip: Ext.util.Format.htmlEncode(this.app.i18n._('Apply postal address')),
               fieldLabel: '&nbsp;',
               lazyLoading: false,
               listeners: {
                    scope: this,
                    click: function() {
                        Ext.iterate(this.fixedFields.get('parentRecord'), function(property, value) {
                            var split = property.split(/_/);
                            if (split[0] == 'adr') {
                                if (value) {
                                    this.getForm().findField(split[1]).setValue(value);
                                }
                            }
                        }, this);
                    }
               }
            },
                this.fieldManager('name', {columnWidth: .95})
            ], [
                this.fieldManager('prefix1', {columnWidth: 1})
            ], [
                this.fieldManager('prefix2', {columnWidth: 1})
            ], [
                this.fieldManager('prefix3', {columnWidth: 1})
            ], [
                this.fieldManager('street'),
                this.fieldManager('pobox')
            ], [
                this.fieldManager('postalcode'),
                this.fieldManager('locality')
            ], [
                this.fieldManager('region'),
                this.fieldManager('countryname')
            ]
        ];
        
        if (this.addressType == 'billing') {
            items.push([{
                name: 'custom1',
                fieldLabel: this.app.i18n._('Number Debit')
            }]);
        }
        
        return {
            xtype: 'tabpanel',
            defaults: {
                hideMode: 'offsets'
            },
            border: false,
            plain: true,
            activeTab: 0,
            items: [{
                title: this.app.i18n._('Address'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    xtype: 'fieldset',
                    layout: 'hfit',
                    region: 'center',
                    autoHeight: true,
                    title: this.app.i18n._('Address'),
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: formFieldDefaults,
                        items: items
                    }]
                }]
            }]
        };
    }
});

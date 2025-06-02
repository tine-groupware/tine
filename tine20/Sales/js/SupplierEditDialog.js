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
 * @class       Tine.Sales.SupplierEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Supplier Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.SupplierEditDialog
 */
Tine.Sales.SupplierEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    windowWidth: 900,
    windowHeight: 800,
    displayNotes: true,
    
    /**
     * 
     */
    isValid: function() {
        var isValid = true,
            form = this.getForm();
        
        isValid = Tine.Sales.SupplierEditDialog.superclass.isValid.call(this)
            
        if (Ext.isEmpty(form.findField('adr_postalcode').getValue()) && Ext.isEmpty(form.findField('adr_pobox').getValue())) {
            isValid = false;
            var msg = this.app.i18n._('Either postalcode or postbox is required!');
            form.markInvalid( {'adr_postalcode': msg, 'adr_pobox': msg});
        }

        return isValid;
    },
    
    /**
     * executed after record got updated from proxy
     */
    onRecordLoad: function() {
        // interrupt process flow until dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        Tine.Sales.SupplierEditDialog.superclass.onRecordLoad.call(this);
        
        if (this.copyRecord) {
            this.doCopyRecord();
            this.window.setTitle(this.app.i18n._('Copy Supplier'));
        } else {
            if (! this.record.id) {
                this.window.setTitle(this.app.i18n._('Add New Supplier'));
            } else {
                this.window.setTitle(String.format(this.app.i18n._('Edit Supplier "{0}"'), this.record.getTitle()));
                //this.getForm().findField('number').disable();
            }
        }
    },
    
    /**
     * duplicate(s) found exception handler
     * 
     * @todo: make this globally, smoothly the virtual fields (modelconfig) don't fit anywhere
     * 
     * @param {Object} exception
     */
    onDuplicateException: function(exception) {
        this.onRecordUpdate();
        exception.clientRecord = this.record.data;
        Tine.Sales.SupplierEditDialog.superclass.onDuplicateException.call(this, exception);
    },
    
    /**
     * Fill address with contact data, if not set already
     * 
     * @param {} combo
     * @param {} record
     * @param {} index
     */
    onSelectContactPerson: function(combo, record, index) {
        var form = this.getForm();
        if (record.get('adr_one_street') && ! form.findField('adr_street').getValue()) {
            var ar = ['street', 'postalcode', 'region', 'locality', 'countryname'];
            for (var index = 0; index < ar.length; index++) {
                form.findField('adr_' + ar[index]).setValue(record.get('adr_one_' + ar[index]));
            }
        }
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
            columnWidth: .5
        };
        
        this.clipboardButton = new Ext.Button({
           columnWidth: 5/100,
           iconCls: 'clipboard',
           tooltip: Ext.util.Format.htmlEncode(this.app.i18n._('Copy address to the clipboard')),
           fieldLabel: '&nbsp;',
           lazyLoading: false,
           listeners: {
                scope: this,
                click: function() {
                    this.onRecordUpdate();
                    Tine.Sales.addToClipboard(this.record);
                }
           }
        });
        
        var currency = Tine.Sales.registry.get('config').ownCurrency.value;
        
        return {
            xtype: 'tabpanel',
            defaults: {
                hideMode: 'offsets'
            },
            border: false,
            plain: true,
            activeTab: 0,
            items: [{
            title: this.app.i18n._('Supplier'),
            autoScroll: true,
            border: false,
            frame: true,
            layout: 'border',
            items: [{
                region: 'center',
                layout: 'hfit',
                border: false,
                items: [{
                    xtype: 'fieldset',
                    layout: 'hfit',
                    autoHeight: true,
                    title: this.app.i18n._('Core Data'),
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: formFieldDefaults,
                        items: [[{
                                fieldLabel: this.app.i18n._('Supplier Number'),
                                name: 'number',
                                allowBlank: true,
                                columnWidth: .250,
                                minValue: 1,
                                maxValue: 4294967296,
                                xtype: 'uxspinner',
                                useThousandSeparator : false,
                                strategy: new Ext.ux.form.Spinner.NumberStrategy({
                                    incrementValue : 1,
                                    alternateIncrementValue: 1,
                                    minValue: 1,
                                    maxValue: 4294967296,
                                    allowDecimals : false
                                })
                            }, {
                                columnWidth: .750,
                                allowBlank: false,
                                fieldLabel: this.app.i18n._('Name'),
                                name: 'name',
                                xtype: 'tine.widget.field.AutoCompleteField',
                                recordClass: this.recordClass
                            }], [Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
                                    columnWidth: 1/2,
                                    blurOnSelect: true,
                                    name: 'cpextern_id',
                                    allowBlank: true,
                                    fieldLabel: this.app.i18n._('Contact Person (external)'),
                                    listeners: {
                                        scope: this,
                                        select: this.onSelectContactPerson
                                    }
                            }), Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
                                    columnWidth: 1/2,
                                    blurOnSelect: true,
                                    name: 'cpintern_id',
                                    allowBlank: true,
                                    fieldLabel: this.app.i18n._('Contact Person (internal)')
                                })
                        ]]
                    }]
                }, {
                    xtype: 'fieldset',
                    layout: 'hfit',
                    autoHeight: true,
                    title: this.app.i18n._('Accounting'),
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: formFieldDefaults,
                        items: [
                            [{
                                name: 'vatid',
                                fieldLabel: this.app.i18n._('VAT ID')
                            }, {
                                name: 'credit_term',
                                fieldLabel: this.app.i18n._('Credit Term (days)'),
                                xtype: 'uxspinner',
                                strategy: new Ext.ux.form.Spinner.NumberStrategy({
                                    incrementValue : 1,
                                    alternateIncrementValue: 1,
                                    minValue: 0,
                                    maxValue: 1024,
                                    allowDecimals : false
                                })
                            }], [{
                                name: 'currency',
                                fieldLabel: this.app.i18n._('Currency'),
                                value: currency,
                                allowBlank: false
                            }, {
                                name: 'currency_trans_rate',
                                fieldLabel: this.app.i18n._('Currency Translation Rate'),
                                xtype: 'uxspinner',
                                strategy: new Ext.ux.form.Spinner.NumberStrategy({
                                    incrementValue : 0.01,
                                    alternateIncrementValue: 0.1,
                                    minValue: 0.01,
                                }),
                                allowDecimals : true,
                                decimalPrecision: 2,
                            }], [{
                                name: 'iban',
                                fieldLabel: this.app.i18n._('IBAN')
                            }, {
                                name: 'bic',
                                fieldLabel: this.app.i18n._('BIC')
                            }]]
                    }]
                }, {
                    xtype: 'fieldset',
                    layout: 'hfit',
                    autoHeight: true,
                    title: this.app.i18n._('Postal Address'),
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: formFieldDefaults,
                        items: [
                            [{
                                name: 'adr_name',
                                fieldLabel: this.app.i18n._('Name'),
                                columnWidth: 47/100
                            }, {
                                name: 'adr_email',
                                fieldLabel: this.app.i18n._('Email'),
                                columnWidth: 47/100
                            }, {
                                name: 'adr_street',
                                fieldLabel: this.app.i18n._('Street'),
                                columnWidth: 47/100
                            }, {
                                name: 'adr_pobox',
                                fieldLabel: this.app.i18n._('Postbox'),
                                columnWidth: 47/100
                                
                            }], [{
                                name: 'adr_postalcode',
                                allowBlank: false,
                                fieldLabel: this.app.i18n._('Postalcode'),
                                columnWidth: 47/100
                            }, {
                                name: 'adr_locality',
                                allowBlank: true,
                                fieldLabel: this.app.i18n._('Locality'),
                                columnWidth: 47/100
                            }], [{
                                name: 'adr_region',
                                fieldLabel: this.app.i18n._('Region'),
                                columnWidth: 47/100
                            }, {
                                xtype: 'widget-countrycombo',
                                name: 'adr_countryname',
                                fieldLabel: this.app.i18n._('Country'),
                                columnWidth: 47/100
                            }
                            ], [{
                                name: 'adr_prefix1',
                                fieldLabel: this.app.i18n._('Prefix'),
                                columnWidth: 47/100
                            }, {
                                name: 'adr_prefix2',
                                fieldLabel: this.app.i18n._('Additional Prefix'),
                                columnWidth: 48/100
                            }, this.clipboardButton]
                        ]
                    }]
                }, {
                    xtype: 'fieldset',
                    layout: 'hfit',
                    autoHeight: true,
                    title: this.app.i18n._('Miscellaneous'),
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: formFieldDefaults,
                        items: [[
                            {
                                columnWidth: 1/3,
                                fieldLabel: this.app.i18n._('Web'),
                                xtype: 'urlfield',
                                name: 'url',
                                maxLength: 128,
                            }
                        ]]
                    }]
                }]
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
                        app: 'Sales',
                        border: false,
                        bodyStyle: 'border:1px solid #B5B8C8;'
                    })
                ]
            }]
        },
           new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: 'Sales_Model_Supplier'
            })
        ]};
    }
});

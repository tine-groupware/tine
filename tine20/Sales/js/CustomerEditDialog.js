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
 * @class       Tine.Sales.CustomerEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Customer Compose Dialog</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.CustomerEditDialog
 */
Tine.Sales.CustomerEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    windowWidth: 900,
    windowHeight: 1000,
    displayNotes: true,
    
    initComponent: function() {
        Tine.Sales.CustomerEditDialog.superclass.initComponent.call(this);
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

        const form = this.getForm();
        const postalAdr = this.record.get('postal') || {};
        Object.keys(postalAdr).forEach((fieldName) => {
            const field = form.findField(`adr_${fieldName}`);
            if (field) {
                field.setValue(postalAdr[fieldName], this.record);
            }
        });

        Tine.Sales.CustomerEditDialog.superclass.onRecordLoad.call(this);
        
        if (this.copyRecord) {
            this.doCopyRecord();
            this.window.setTitle(this.app.i18n._('Copy Customer'));
        } else {
            if (! this.record.id) {
                this.window.setTitle(this.app.i18n._('Add New Customer'));
            } else {
                this.window.setTitle(String.format(this.app.i18n._('Edit Customer "{0}"'), this.record.getTitle()));
                this.getForm().findField('number').disable();
            }
        }
    },

    /**
     * executed when record gets updated from form
     */
    onRecordUpdate: function(callback, scope) {
        var form = this.getForm();

        const postalAdr = this.record.get('postal') || {};
        form.items.items.forEach((field) => {
            if (field?.name?.match(/^adr_/)) {
                postalAdr[field.name.replace(/^adr_/, '')] = field.getValue()
            }
        });
        this.record.set('postal', postalAdr)

        Tine.Sales.CustomerEditDialog.superclass.onRecordUpdate.apply(this, arguments);
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
        Tine.Sales.CustomerEditDialog.superclass.onDuplicateException.call(this, exception);
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
        const fieldManager = _.bind(Tine.widgets.form.FieldManager.get,
            Tine.widgets.form.FieldManager, this.recordClass.getMeta('appName'), this.recordClass.getMeta('modelName'), _,
            Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);

        var formFieldDefaults = {
            xtype:'textfield',
            anchor: '100%',
            labelSeparator: '',
            columnWidth: .5
        };
        
        this.clipboardButton = new Ext.Button({
           columnWidth: 5/100,
           iconCls: 'clipboard form-item-button',

           tooltip: Ext.util.Format.htmlEncode(this.app.i18n._('Copy address to the clipboard')),
           fieldLabel: '&nbsp;',
           lazyLoading: false,
           listeners: {
                scope: this,
                click: function() {
                    this.onRecordUpdate();

                    Tine.Sales.addToClipboard(Tine.Tinebase.data.Record.setFromJson(this.record.get('postal'), Tine.Sales.Model.Address), this.record.get('name'));
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
            title: this.app.i18n._('Customer'),
            autoScroll: true,
            border: false,
            frame: true,
            layout: 'border',
            defaults: { autoScroll: true },
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
                        items: [[
                            fieldManager('number', {
                                columnWidth: .250
                            }), {
                                columnWidth: .750,
                                allowBlank: false,
                                fieldLabel: this.app.i18n._('Name'),
                                name: 'name',
                                xtype: 'tine.widget.field.AutoCompleteField',
                                recordClass: this.recordClass
                            }], [Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
                                    columnWidth: .5,
                                    blurOnSelect: true,
                                    name: 'cpextern_id',
                                    allowBlank: true,
                                    fieldLabel: this.app.i18n._('Contact Person (external)'),
                                    listeners: {
                                        scope: this,
                                        select: this.onSelectContactPerson
                                    }
                            }), Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
                                    columnWidth: .5,
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
                            },
                                fieldManager('vat_procedure')
                            ], [{
                                name: 'currency',
                                fieldLabel: this.app.i18n._('Currency'),
                                value: currency
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
                                nullable: true,
                            }], [{
                                name: 'iban',
                                fieldLabel: this.app.i18n._('IBAN')
                            }, {
                                name: 'bic',
                                fieldLabel: this.app.i18n._('BIC')
                            }], [{
                                name: 'discount',
                                fieldLabel: this.app.i18n._('Discount (%)'),
                                xtype: 'uxspinner',
                                strategy: new Ext.ux.form.Spinner.NumberStrategy({
                                    incrementValue : 0.1,
                                    alternateIncrementValue: 1,
                                    minValue: 0,
                                    maxValue: 100,
                                }),
                                allowDecimals : true,
                                decimalPrecision: 1,
                                nullable: true,
                                decimalSeparator: Tine.Tinebase.registry.get('decimalSeparator')
                            }, {
                                name: 'credit_term',
                                fieldLabel: this.app.i18n._('Credit Term (days)'),
                                xtype: 'uxspinner',
                                strategy: new Ext.ux.form.Spinner.NumberStrategy({
                                    incrementValue : 1,
                                    alternateIncrementValue: 1,
                                    minValue: 0,
                                    maxValue: 1024,
                                }),
                                allowDecimals : false,
                                nullable: true
                            }
                        ]]
                    }]
                }, {
                    xtype: 'fieldset',
                    hidden: this.denormalizationRecordClass,
                    layout: 'hfit',
                    autoHeight: true,
                    title: this.app.i18n._('Debitors'),
                    items: [
                        fieldManager('debitors')
                    ]
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
                                name: 'adr_name_shorthand',
                                columnWidth: .2,
                                fieldLabel: this.app.i18n._('Name shorthand')
                            }, {
                                xtype: 'widget-keyfieldcombo',
                                columnWidth: .2,
                                app:   'Sales',
                                keyFieldName: 'languagesAvailable',
                                fieldLabel: this.app.i18n._('Language'),
                                name: 'adr_language',
                                requiredGrant: 'editGrant'
                            }, {
                                name: 'adr_email',
                                fieldLabel: this.app.i18n._('Email'),
                                columnWidth: .55
                            }, this.clipboardButton],[{
                                name: 'adr_name',
                                fieldLabel: this.app.i18n._('Name'),
                                columnWidth: 1
                            }], [{
                                name: 'adr_prefix1',
                                fieldLabel: this.app.i18n._('Prefix 1'),
                                columnWidth: 1
                            }], [{
                                name: 'adr_prefix2',
                                fieldLabel: this.app.i18n._('Prefix 2'),
                                columnWidth: 1
                            }], [{
                                name: 'adr_prefix3',
                                fieldLabel: this.app.i18n._('Prefix 3'),
                                columnWidth: 1
                            }, ], [{
                                name: 'adr_street',
                                fieldLabel: this.app.i18n._('Street'),
                                columnWidth: 0.5
                            }, {
                                name: 'adr_pobox',
                                fieldLabel: this.app.i18n._('Postbox'),
                                columnWidth: 0.5
                                
                            }], [{
                                name: 'adr_postalcode',
                                fieldLabel: this.app.i18n._('Postalcode'),
                                columnWidth: 0.5
                            }, {
                                name: 'adr_locality',
                                fieldLabel: this.app.i18n._('Locality'),
                                columnWidth: 0.5
                            }], [{
                                name: 'adr_region',
                                fieldLabel: this.app.i18n._('Region'),
                                columnWidth: 0.5
                            }, {
                                xtype: 'widget-countrycombo',
                                name: 'adr_countryname',
                                fieldLabel: this.app.i18n._('Country'),
                                columnWidth: 0.5
                            }
                            ]
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
                                columnWidth: 1,
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
                record_model: 'Sales_Model_Customer'
            })
        ]};
    }
});

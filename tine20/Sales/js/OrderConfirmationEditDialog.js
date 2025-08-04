/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Sales');

/**
 * OrderConfirmation edit dialog
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.OrderConfirmationEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>OrderConfirmation Edit Dialog</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.OrderConfirmationGridPanel
 */
Tine.Sales.OrderConfirmationEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    windowWidth: 650,
    windowHeight: 350,

    displayNotes: true,
    
    
    /**
     * init component
     */
    initComponent: function () {
        Tine.Sales.OrderConfirmationEditDialog.superclass.initComponent.call(this);
    },

    /**
     * called on multiple edit
     * @return {Boolean}
     */
    isMultipleValid: function() {
        return true;
    },
    
    /**
     * @see: Tine.widgets.dialog.EditDialog.onRecordLoad
     */
    onRecordLoad: function() {
        Tine.Sales.OrderConfirmationEditDialog.superclass.onRecordLoad.call(this);
        
        if (! this.copyRecord && ! this.record.id) {
            this.window.setTitle(this.app.i18n._('Add New Order Confirmation'));
        }
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     */
    getFormItems: function() {
        return {
            xtype: 'tabpanel',
            plain:true,
            activeTab: 0,
            border: false,
            defaults: {
                hideMode: 'offsets'
            },
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }],
            items:[
                {
                title: this.app.i18n.n_('Order Confirmation', 'Order Confirmations', 1),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype:'textfield',
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: 1/2
                    },
                    items: [[{
                        fieldLabel: this.app.i18n._('Number'),
                        name: 'number',
                        disabled: (! Tine.Tinebase.common.hasRight('change', 'Sales', 'oc_number') && this.record.id),
                        allowBlank: true // autoset if empty
                    },{
                        fieldLabel: this.app.i18n._('Title'),
                        name: 'title',
                        allowBlank: false
                    }], [{
                            xtype: 'tinerelationpickercombo',
                            fieldLabel: this.app.i18n._('Contract'),
                            editDialog: this,
                            allowBlank: false,
                            app: 'Sales',
                            recordClass: Tine.Sales.Model.Contract,
                            relationType: 'CONTRACT',
                            relationDegree: 'sibling',
                            modelUnique: true,
                            columnWidth: 1
                        }], [{
                            xtype: 'tinerelationpickercombo',
                            fieldLabel: this.app.i18n._('Offer'),
                            editDialog: this,
                            allowBlank: true,
                            app: 'Sales',
                            recordClass: Tine.Sales.Model.Offer,
                            relationType: 'OFFER',
                            relationDegree: 'sibling',
                            modelUnique: true,
                            columnWidth: 1
                        }]
                    ]
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
                            // bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
                }]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: 'Sales_Model_OrderConfirmation'
            })]
        };
    }
});

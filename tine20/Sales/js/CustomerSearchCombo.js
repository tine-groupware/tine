/*
 * Tine 2.0
 * Sales combo box and store
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Sales');

/**
 * Customer selection combo box
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.CustomerSearchCombo
 * @extends     Ext.form.ComboBox
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.CustomerSearchCombo
 */
Tine.Sales.CustomerSearchCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    
    allowBlank: false,
    minListWidth: 200,
    
    //private
    initComponent: function(){
        this.recordClass = Tine.Sales.Model.Customer;
        this.recordProxy = Tine.Sales.customerBackend;

        Tine.Sales.CustomerSearchCombo.superclass.initComponent.call(this);
        
        this.displayField = 'fulltext';
        this.sortBy = 'number';
    },

    // checkState: function(editDialog, record) {
    //     const division = this.getDivision();
    //     if (division) {
    //         // check if customer has
    //     }
    // },
    //
    // onBeforeQuery: function(qevent){
    //     Tine.Sales.CustomerSearchCombo.superclass.onBeforeQuery.apply(this, arguments);
    //
    //     const filter = this.store.baseParams.filter;
    //     const division = this.getDivision();
    //
    //     if (division) {
    //         // @TODO fallback to postal adr. if customer has no debitor
    //         filter.push({field: 'debitors', operator: 'definedBy', value: [{
    //             field: 'division_id', operator: 'definedBy', value: [{
    //                 field: ':id', operator: 'equals', value: division.id
    //         }]}]})
    //     }
    // },
    //
    // getDivision: function() {
    //     const form = this.findParentBy((c) => { return c instanceof Ext.form.FormPanel }).getForm();
    //     const category = form.findField('document_category').selectedRecord;
    //     return category?.data?.division_id;
    // }
});

Tine.widgets.form.RecordPickerManager.register('Sales', 'Customer', Tine.Sales.CustomerSearchCombo);
Tine.widgets.form.RecordPickerManager.register('Sales', 'Document_Customer', Tine.Sales.CustomerSearchCombo);

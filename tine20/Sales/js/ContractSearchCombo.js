/*
 * Tine 2.0
 * Sales combo box and store
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Sales');

/**
 * Contract selection combo box
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.ContractSearchCombo
 * @extends     Ext.form.ComboBox
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.ContractSearchCombo
 */
Tine.Sales.ContractSearchCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    /**
     * @cfg {Bool} showClosed
     */
    showClosed: false,

    allowBlank: false,
    minListWidth: 200,
    /**
     * @property showClosedBtn
     * @type Ext.Button
     */
    showClosedBtn: null,
    
    //private
    initComponent: function(){
        this.recordClass = Tine.Sales.Model.Contract;
        this.recordProxy = Tine.Sales.contractBackend;

        Tine.Sales.ContractSearchCombo.superclass.initComponent.call(this);
        
        this.displayField = 'fulltext';
        this.sortBy = 'number';
    },

    initList: function() {
        Tine.Sales.ContractSearchCombo.superclass.initList.apply(this, arguments);

        if (this.pageTb && ! this.showClosedBtn) {
            this.showClosedBtn = new Tine.widgets.grid.FilterButton({
                text: this.app.i18n._('Show closed'),
                iconCls: 'action_showArchived',
                field: 'end_date',
                pressed: this.showClosed,
                scope: this,
                handler: function() {
                    this.showClosed = this.showClosedBtn.pressed;
                    this.store.load();
                }

            });

            this.pageTb.add('-', this.showClosedBtn);
            this.pageTb.doLayout();
        }
    },

    /**
     * apply showClosed value
     */
    onStoreBeforeLoadRecords: function(o, options, success, store) {
        if (this.showClosedBtn) {
            this.showClosedBtn.setValue(options.params.filter);
        }
    },

    /**
     * append showClosed value
     */
    onBeforeLoad: function (store, options) {
        Tine.Sales.ContractSearchCombo.superclass.onBeforeLoad.apply(this, arguments);

        if (this.showClosedBtn) {
            Ext.each(store.baseParams.filter, function(filter, idx) {
                if (filter.field == 'end_date'){
                    store.baseParams.filter.remove(filter);
                }
            }, this);

            if (this.showClosedBtn.getValue().value === false) {
                store.baseParams.filter.push({field: 'end_date', operator: 'after', value: new Date().add('s',-1)});
            }
        }
    }



});

Tine.widgets.form.RecordPickerManager.register('Sales', 'Contract', Tine.Sales.ContractSearchCombo);


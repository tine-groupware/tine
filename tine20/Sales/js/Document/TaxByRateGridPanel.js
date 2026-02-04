/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import PositionGridPanel from "../DocumentPosition/AbstractGridPanel";

const TaxByRateGridPanel = Ext.extend(Tine.widgets.grid.QuickaddGridPanel, {
    autoHeight: true,
    quickaddMode: 'sorted',
    autoExpandColumn: 'tax_amount',
    quickaddMandatory: 'tax_rate',

    clicksToEdit: 1,

    allowCreateNew: true,
    resetAllOnNew: true,
    enableBbar: true,

    ddSortCol: 'sorting',
    sortInc: 10000,
    isFormField: true,

    initComponent() {
        this.app = Tine.Tinebase.appMgr.get('Sales')
        this.columns = ['net_amount', 'tax_rate', 'tax_amount', 'gross_amount']
        this.defaultSortInfo = {
            field: 'tax_rate',
            direction: 'ASC'
        }
        this.recordClass = Tine.Tinebase.data.RecordMgr.get('Sales.Document_SalesTax')

        this.on('afteredit', this.onAfterEditSalesTax, this);
        // this.on('afterEditQuickAdd', this.onAfterEditSalesTax, this);

        TaxByRateGridPanel.superclass.initComponent.call(this)
    },

    onAfterEditSalesTax(e) {
        // console.error(e)
        const originalValues = {... e.record.data}

        originalValues[e.field] = e.originalValue

        const toFixed = Tine.Sales.Model.DocumentPosition_PurchaseInvoice.toFixed
        let net_amount, tax_rate, tax_amount, gross_amount
        switch (e.field) {
            case 'net_amount':
                tax_amount = e.value * e.record.get('tax_rate') / 100
                gross_amount = e.value + tax_amount
                if (this.forceAutoValues || toFixed(originalValues['net_amount'] * originalValues['tax_rate'] / 100) === toFixed(originalValues['tax_amount'])) {
                    e.record.set('tax_amount', toFixed(tax_amount))
                }
                if (this.forceAutoValues || toFixed(originalValues['net_amount'] + originalValues['tax_amount']) === toFixed(originalValues['gross_amount'])) {
                    e.record.set('gross_amount', toFixed(gross_amount))
                }
                break;

            case 'tax_rate':
                const f = e.value / originalValues.tax_rate;
                tax_amount = originalValues.tax_amount * f;
                e.record.set('tax_amount', tax_amount);
                e.record.set('net_amount', originalValues.gross_amount - tax_amount);
                break;

        }
    },

    initActionsAndToolbars() {
        TaxByRateGridPanel.superclass.initActionsAndToolbars.call(this)
        this.actionCreate.setHidden(true);
        this.actionEdit.setHidden(true);

    },

    onRowDblClick() {},

    setCurrencySymbol: PositionGridPanel.prototype.setCurrencySymbol,

    getColumnModel() {
        const colModel = TaxByRateGridPanel.superclass.getColumnModel.call(this);
        _.each(colModel.columns, col => {
            col.width = 100
            if (col.dataIndex === 'tax_rate') {
                col.quickaddField.value = Tine.Tinebase.configManager.get('salesTax');
            }
        })

        return colModel;
    }
})

export default TaxByRateGridPanel
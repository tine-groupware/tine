/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const TaxByRateGridPanel = Ext.extend(Tine.widgets.grid.QuickaddGridPanel, {
    autoHeight: true,
    quickaddMode: 'sorted',
    autoExpandColumn: 'tax_amount',
    quickaddMandatory: 'tax_amount',

    clicksToEdit: 1,

    allowCreateNew: true,
    resetAllOnNew: true,
    enableBbar: true,

    ddSortCol: 'sorting',
    sortInc: 10000,
    isFormField: true,

    initComponent() {
        this.app = Tine.Tinebase.appMgr.get('Sales')
        this.columns = ['tax_amount', 'tax_rate']
        this.defaultSortInfo = {
            field: 'tax_rate',
            direction: 'ASC'
        }
        this.recordClass = Tine.Tinebase.data.RecordMgr.get('Sales.Document_SalesTax')

        this.on('afteredit', this.onAfterEditSalesTax, this);

        TaxByRateGridPanel.superclass.initComponent.call(this)
    },

    onAfterEditSalesTax(e) {
        console.error(e)
        const originalValues = {... e.record.data}

        originalValues[e.field] = e.originalValue

        if (e.field === 'tax_rate') {
            const tax_amount = originalValues.tax_amount / originalValues.tax_rate * e.value;
            e.record.set('tax_amount', tax_amount);

        }
    },

    initActionsAndToolbars() {
        TaxByRateGridPanel.superclass.initActionsAndToolbars.call(this)
        this.actionCreate.setHidden(true);
        this.actionEdit.setHidden(true);

    },

    onRowDblClick() {},

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
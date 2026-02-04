/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import './AbstractEditDialog'
import InvoicePositionGridPanel from "../DocumentPosition/AbstractGridPanel";

Ext.ns('Tine.Sales.Document');

Tine.Sales.Document_InvoiceEditDialog = Ext.extend(Tine.Sales.Document_AbstractEditDialog, {
    statusFieldName: 'invoice_status',

    initComponent () {
        Tine.Sales.Document_InvoiceEditDialog.superclass.initComponent.call(this)
    },

    getRecordFormItems() {
        const items = Tine.Sales.Document_InvoiceEditDialog.superclass.getRecordFormItems.call(this)

        const rows = items[0].items
        const row = _.find(rows, row => _.indexOf(row, this.fields.credit_term) >= 0)
        const colIdx = _.indexOf(row, this.fields.credit_term)

        row.splice(colIdx+1, 1, this.fields.payment_reminders, {xtype: 'label', html: '&nbsp', columnWidth: 1/5})

        return items
    }
});

Ext.reg('sales-document-position-invoice-gridpanel', InvoicePositionGridPanel)
Tine.widgets.form.FieldManager.register('Sales', 'Document_Invoice', 'positions', {
    xtype: 'sales-document-position-invoice-gridpanel',
    recordClass: 'Sales.DocumentPosition_Invoice',
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG)

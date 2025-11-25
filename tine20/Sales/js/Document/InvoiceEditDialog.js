/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import './AbstractEditDialog'
import InvoicePositionGridPanel from "../DocumentPosition/AbstractGridPanel";

Ext.ns('Tine.Sales.Document');

Tine.Sales.Document_InvoiceEditDialog = Ext.extend(Tine.Sales.Document_AbstractEditDialog, {
    statusFieldName: 'invoice_status',

    initComponent () {
        this.supr().initComponent.call(this)
    },

    // getRecordFormItems() {
    //     const rtnVal = this.supr().getRecordFormItems.call(this)
    //     const items = rtnVal[0].items
    //     const placeholder = {xtype: 'label', html: '&nbsp', columnWidth: 1/5}
    //
    //     const invoicePeriodLine = [this.fields.service_period_start, this.fields.service_period_end, {... placeholder}, {... placeholder}, {... placeholder}]
    //     const rIdx = _.indexOf(items, _.find(items, {line: 'references'}))
    //     items.splice(rIdx+1, 0, invoicePeriodLine)
    //
    //     return rtnVal
    // }
});

Ext.reg('sales-document-position-invoice-gridpanel', InvoicePositionGridPanel)
Tine.widgets.form.FieldManager.register('Sales', 'Document_Invoice', 'positions', {
    xtype: 'sales-document-position-invoice-gridpanel',
    recordClass: 'Sales.DocumentPosition_Invoice',
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG)

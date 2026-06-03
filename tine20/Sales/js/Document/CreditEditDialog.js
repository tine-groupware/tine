/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import './AbstractEditDialog'
import CreditPositionGridPanel from "../DocumentPosition/AbstractGridPanel";

Ext.ns('Tine.Sales.Document');

Tine.Sales.Document_CreditEditDialog = Ext.extend(Tine.Sales.Document_AbstractEditDialog, {
    statusFieldName: 'credit_status',

    initComponent () {
        Tine.Sales.Document_CreditEditDialog.superclass.initComponent.call(this)
        this.writeableAfterBooked = this.writeableAfterBooked.concat(['pay_at', 'paid_at'])

    },

    getRecordFormItems() {
        const items = Tine.Sales.Document_CreditEditDialog.superclass.getRecordFormItems.call(this)

        const rows = items[0].items
        const row = _.find(rows, row => _.findIndex(row, { name: 'payment_means'}) >=0 );
        row.splice(1, 2, {xtype: 'label', html: '&nbsp', columnWidth: 1/5}, this.fields.pay_at, this.fields.paid_at);

        return items
    }
});

Ext.reg('sales-document-position-credit-gridpanel', CreditPositionGridPanel)
Tine.widgets.form.FieldManager.register('Sales', 'Document_Credit', 'positions', {
    xtype: 'sales-document-position-credit-gridpanel',
    recordClass: 'Sales.DocumentPosition_Credit',
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG)

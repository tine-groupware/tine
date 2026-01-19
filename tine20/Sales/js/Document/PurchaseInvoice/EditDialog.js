/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import FieldTriggerPlugin from "ux/form/FieldTriggerPlugin";

Ext.ns('Tine.Sales');

import DocumentPosition_PurchaseInvoice from "../../Model/DocumentPosition/PurchaseInvoice";
import EvaluationDimensionForm from "widgets/form/EvaluationDimensionForm";
import PaymentMeansField from '../PaymentMeansField'
import PositionGridPanel from "./PositionGridPanel";
import TaxByRateField from "../TaxByRateField";

Tine.Sales.Document_PurchaseInvoiceEditDialog = Ext.extend(Tine.Sales.Document_AbstractEditDialog, {
    windowWidth: 1240,
    windowHeight: 1300,

    recordClass: 'Sales.Document_PurchaseInvoice',
    statusFieldName: 'purchase_invoice_status',
    forceAutoValues: false,

    checkStates () {
        Tine.Sales.Document_PurchaseInvoiceEditDialog.superclass.checkStates.apply(this, arguments)
        this.getForm().findField('positions_net_sum')?.setVisible(true);
        this.getForm().findField('positions_gross_sum')?.setVisible(true);
    },

    getRecordFormItems: function() {
        const fields = this.fields = Tine.widgets.form.RecordForm.getFormFields(this.recordClass, (fieldName, config, fieldDefinition) => {
        })

        const placeholder = {xtype: 'label', html: '&nbsp', columnWidth: 1/5}
        return [{
            region: 'center',
            xtype: 'columnform',
            columnLayoutConfig: {
                enableResponsive: true,
            },
            items: [
                [fields.document_number, fields.document_proforma_number || placeholder, fields[this.statusFieldName], fields.document_category, fields.document_language],
                // NOTE: contract_id waits for contract rewrite
                [/*fields.contract_id, */ _.assign(fields.supplier_id, {columnWidth: 2/5}), _.assign(fields.recipient_id, {columnWidth: 3/5})],
                _.assign([ _.assign(fields.buyer_reference, {columnWidth: 2/5}), fields.purchase_order_reference, fields.project_reference, fields.contact_id], {line: 'references'}),
                [fields.service_period_start, fields.service_period_end, _.assign({ ...placeholder } , {columnWidth: 2/5}), fields.date],
                [{ xtype: 'sales-document-position-purchase-invoice-gridpanel', lang: 'de' }],
                [_.assign({ ...placeholder } , {columnWidth: 2/5}), _.assign(fields.positions_discount_sum, {columnWidth: 1/5}), _.assign(fields.positions_net_sum, {columnWidth: 1/5}), _.assign(fields.positions_gross_sum, {columnWidth: 1/5})],
                [_.assign({ ...placeholder } , {columnWidth: 2/5}), fields.invoice_discount_type, fields.invoice_discount_percentage, fields.invoice_discount_sum],
                [{ ...placeholder }, fields.net_sum, fields.vat_procedure, fields.sales_tax_by_rate, fields.gross_sum],
                [new PaymentMeansField({editDialog: this, columnWidth: 2/5}), fields.credit_term, fields.payment_means_used, { ...placeholder }],
                // [{xtype: 'textarea', name: 'boilerplate_Posttext', allowBlank: false, enableKeyEvents: true, height: 70, fieldLabel: `${this.app.i18n._('Boilerplate')}: Posttext`}],
                [new EvaluationDimensionForm({recordClass: this.recordClass})]
            ]
        }]
    }
})
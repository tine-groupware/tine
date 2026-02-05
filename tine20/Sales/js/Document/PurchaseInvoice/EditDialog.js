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
    writeableAfterBooked: ['payment_means_used', 'pay_at', 'paid_at', 'paid_amount'],

    async assertStatusChange() {
        const isValid = _.reduce(['date', 'approver'], (isValid, field) => {
            if (! this.getForm().findField(field).getValue()) {
                this.getForm().findField(field).markInvalid(this.app.formatMessage('Value must not be empty.'))
                return false
            }
            return isValid && true
        }, true)


        if (!isValid) {
            await Ext.MessageBox.show({
                    icon: Ext.MessageBox.WARNING,
                    buttons: Ext.MessageBox.OK,
                    title: this.app.formatMessage('Required Data Missing'),
                    msg: this.app.formatMessage('Please provide the required data before changing the workflow status.'),
                }
            )
            return false
        }
    },

    checkStates () {
        if (this.loadRequest) {
            return _.delay(_.bind(this.checkStates, this), 250)
        }

        const lastRecord = Tine.Tinebase.data.Record.clone(this.lastRecord || this.record);

        Tine.Sales.Document_PurchaseInvoiceEditDialog.superclass.checkStates.apply(this, arguments)
        this.getForm().findField('positions_net_sum')?.setVisible(true);
        this.getForm().findField('positions_gross_sum')?.setVisible(true);

        if (this.forceAutoValues || this.calcDueDate(lastRecord)?.toJSON?.() === this.getForm().findField('due_at').getValue()?.toJSON?.()) {
            this.getForm().findField('due_at').setValue(this.calcDueDate(this.record))
        }

        if (this.getForm().findField('date').getValue() && this.getForm().findField('credit_term').getValue()) {}

    },

    calcDueDate: function(record) {
        const date = record.get('date')
        const creditTerm = record.get('credit_term')
        return _.isDate(date) && ['', null, undefined].indexOf(creditTerm) < 0 ? date.add(Date.DAY, creditTerm) : null
    },

    getRecordFormItems: function() {
        const fields = this.fields = Tine.widgets.form.RecordForm.getFormFields(this.recordClass, (fieldName, config, fieldDefinition) => {
            switch (fieldName) {
                case 'supplier_id':
                    config.listeners = config.listeners || {};
                    config.listeners.select = (combo, record, index) => {
                        fields['credit_term']?.setValue(record.get('credit_term'))
                        fields['document_currency'].setValue(record.get('currency') || fields['document_currency'].getValue())
                        // fields['document_language'].setValue(record.get('language') || fields['document_language'].getValue())
                        // if (record.get('discount')) {
                        //     fields['invoice_discount_type'].setValue('PERCENTAGE')
                        //     fields['invoice_discount_percentage'].setValue(record.get('discount'))
                        // }
                        const vatProcedure = record.get('vat_procedure')
                        if (vatProcedure) {
                            fields['vat_procedure']?.setValue(vatProcedure)
                        }
                    }
                    break;
                case 'paid_at':
                    config.listeners = config.listeners || {};
                    config.listeners.select = (combo, record, index) => {
                        if (!fields.paid_amount.getValue()) {
                            fields.paid_amount.setValue(fields.gross_sum.getValue())
                        }
                    }
            }
        })

        const placeholder = {xtype: 'label', html: '&nbsp', columnWidth: 1/5}
        return [{
            region: 'center',
            xtype: 'columnform',
            columnLayoutConfig: {
                enableResponsive: true,
            },
            items: [
                [fields.document_number, fields.external_invoice_number, placeholder, placeholder, fields.document_currency],
                [ Object.assign(fields[this.statusFieldName], {columnWidth: 2/5}), fields.approver, placeholder, placeholder],
                // NOTE: contract_id waits for contract rewrite
                [/*fields.contract_id, */ _.assign(fields.supplier_id, {columnWidth: 2/5}), _.assign({ ...placeholder }, {columnWidth: 3/5})],
                _.assign([ _.assign(fields.buyer_reference, {columnWidth: 2/5}), fields.purchase_order_reference, fields.project_reference, fields.contact_id], {line: 'references'}),
                [fields.service_period_start, fields.service_period_end, _.assign({ ...placeholder } , {columnWidth: 2/5}), fields.date],
                [{ xtype: 'sales-document-position-purchase-invoice-gridpanel', lang: 'de' }],
                [_.assign({ ...placeholder } , {columnWidth: 2/5}), _.assign(fields.positions_discount_sum, {columnWidth: 1/5}), _.assign(fields.positions_net_sum, {columnWidth: 1/5}), _.assign(fields.positions_gross_sum, {columnWidth: 1/5})],
                [_.assign({ ...placeholder } , {columnWidth: 2/5}), fields.invoice_discount_type, fields.invoice_discount_percentage, fields.invoice_discount_sum],
                [{ ...placeholder }, fields.net_sum, fields.vat_procedure, fields.sales_tax_by_rate, fields.gross_sum],
                [new PaymentMeansField({editDialog: this, columnWidth: 1/5}), fields.credit_term,  fields.due_at, fields.payment_reminders, fields.pay_at],
                [fields.paid_at, fields.paid_amount, fields.payment_means_used, { ...placeholder }, { ...placeholder }],
                // [{xtype: 'textarea', name: 'boilerplate_Posttext', allowBlank: false, enableKeyEvents: true, height: 70, fieldLabel: `${this.app.i18n._('Boilerplate')}: Posttext`}],
                [new EvaluationDimensionForm({recordClass: this.recordClass})]
            ]
        }]
    }
})
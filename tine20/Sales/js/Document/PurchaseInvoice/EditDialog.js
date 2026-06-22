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
import { PersonaContainer, Personas } from "ux/vue/PersonaContainer";
import RecordEditFieldTriggerPlugin from "widgets/form/RecordEditFieldTriggerPlugin";

Tine.Sales.Document_PurchaseInvoiceEditDialog = Ext.extend(Tine.Sales.Document_AbstractEditDialog, {
    windowWidth: 1240,
    windowHeight: 1300,

    recordClass: 'Sales.Document_PurchaseInvoice',
    statusFieldName: 'purchase_invoice_status',
    forceAutoValues: false,

    initComponent() {
        Tine.Sales.Document_PurchaseInvoiceEditDialog.superclass.initComponent.call(this)
        this.missingSupplierText = this.app.i18n._('This invoice could not be assigned to a supplier. Please select an existing supplier or create a new one.')

        this.writeableAfterBooked = this.writeableAfterBooked.concat(['payment_means_used', 'pay_at', 'paid_at', 'paid_amount'])
    },

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

    async onRecordLoad() {
        Tine.Sales.Document_PurchaseInvoiceEditDialog.superclass.onRecordLoad.call(this)

        const supplierData = this.fields.supplier_id.getValue()
        if (supplierData && supplierData.id && !supplierData.original_id) {
            const supplier = await this.getSupplier(supplierData)
            this.fields.supplier_id.setValue(supplier)
        }

        const me = this
        if (_.get(this.record, 'data.xprops.is_imported_edocument')) {
            this.infoBox.setText(this.app.i18n._('This is an imported e-invoice. Accounting data is write-protected. '))
            this.infoBox.setVisible(true);
            this.setBookedFieldsReadOnly(true);

            const statusField = this.fields[this.statusFieldName]
            const booked = statusField.store.getById(statusField.getValue())?.json.booked
            if (!booked) {
                this.fields.supplier_id.setReadOnly(false)
                this.fields.approver.setReadOnly(false)
            }
        }
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
                        if (!record) return;
                        fields['credit_term']?.setValue(record.get('credit_term'))
                        fields['document_currency'].setValue(record.get('currency') || fields['document_currency'].getValue())
                        fields['payment_means_used'].setValue(record.get('payment_means_default') || fields['payment_means_used'].getValue())
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
                    config.validateValue = value => {
                        if (! this.fields.supplier_id.getValue()?.original_id) {
                            this.fields.supplier_id.markInvalid(this.missingSupplierText)
                            return false
                        }
                        return true
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
                [{ xtype: 'v-alert', variant: 'info', columnWidth: 1, ref: '../../../../../infoBox', hidden: true, label: '...' }],
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
    },

    getSupplier: function(supplierData) {
        return new Promise((resolve, reject) => {
            const onCheck = (field, checked) => {
                if (!checked) return
                const form = field.ownerCt
                const existing = form.existingSupplierRadio === field
                form.existingSupplierCombo.setDisabled(!existing)
                form.newSupplierCombo.setDisabled(existing)
            }

            const win = Tine.WindowFactory.getWindow({
                layout: 'fit',
                width: 370,
                height: 300,
                modal: true,
                title: this.app.i18n._('Assign Supplier'),
                closable: false,
                items: new Tine.Tinebase.dialog.Dialog({
                    enableKeyEvents: true,
                    allowCancel: false,
                    layout: 'hbox',
                    layoutConfig: {
                        padding: '5',
                        align: 'stretch'
                    },
                    items: [ new PersonaContainer({
                        persona: Personas.ERROR_SEVERE,
                        flex: 0,
                        width: 100,
                        height: 200
                    }), {
                        flex: 1,
                        border: false,
                        layout: 'form',
                        ref: 'supplierForm',
                        labelAlign: 'top',
                        items: [{
                            xtype: 'v-alert',
                            variant: 'info',
                            label: this.app.i18n._('This invoice could not be assigned to a supplier. Please select an existing supplier or create a new one.')
                        }, {
                            xtype: 'radio',
                            boxLabel: this.app.i18n._('Assign existing supplier'),
                            name: 'supplier-src',
                            checked: true,
                            ref: 'existingSupplierRadio',
                            listeners: { check: onCheck }
                        }, {
                            xtype: 'tinerecordpickercombobox',
                            recordClass: 'Sales.Supplier',
                            hideLabel: true,
                            ref: 'existingSupplierCombo',
                        }, {
                            xtype: 'radio',
                            boxLabel: this.app.i18n._('Create new supplier'),
                            hideLabel: true,
                            name: 'supplier-src',
                            ref: 'newSupplierRadio',
                            listeners: { check: onCheck }
                        }, {
                            xtype: 'tinerecordpickercombobox',
                            recordClass: 'Sales.Supplier',
                            hideLabel: true,
                            ref: 'newSupplierCombo',
                            value: supplierData,
                            disabled: true,
                            hideTrigger: true,
                            hideTrigger2: true,
                            plugins: [new RecordEditFieldTriggerPlugin({
                                editDialogConfig: {
                                    mode: 'local'
                                }
                            })]
                        }]
                    }],
                    getEventData: function() {
                        const isExisting = this.supplierForm.existingSupplierRadio.checked
                        const supplier = this.supplierForm[isExisting ? 'existingSupplierCombo' : 'newSupplierCombo'].selectedRecord
                        return { isExisting, supplier }
                    },
                    listeners: {
                        beforeapply: async (data) => {
                            if (! data.supplier) return false
                            if (data.isExisting) {
                                if (await Ext.MessageBox.show({
                                    icon: Ext.MessageBox.QUESTION,
                                    buttons: Ext.MessageBox.OKCANCEL,
                                    title: this.app.formatMessage('Update Existing Supplier?'),
                                    msg: this.app.formatMessage('The selected supplier does not have an electronic address. Do you want to update it with the data from this e-invoice now?')
                                }) === 'ok') {
                                    // @TODO update more data? have a diff dialog?!
                                    data.supplier.set('vatid', supplierData.vatid)
                                    data.supplier.set('eas_id', supplierData.eas_id)
                                    data.supplier.set('electronic_address', supplierData.electronic_address)
                                    resolve(await Tine.Sales.saveSupplier(data.supplier.getData()))
                                }
                                resolve(data.supplier)
                            } else {
                                // create new supplier
                                resolve(await Tine.Sales.saveSupplier(Object.assign(supplierData, { original_id: null })))
                            }
                        }
                    }
                })
            })
        })
    }
})
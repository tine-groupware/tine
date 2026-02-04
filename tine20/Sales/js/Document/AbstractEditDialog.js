/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import FieldTriggerPlugin from "../../../Tinebase/js/ux/form/FieldTriggerPlugin";

Ext.ns('Tine.Sales');

import { BoilerplatePanel } from './BoilerplatePanel'
import { getSums as getPositionsSums } from  '../DocumentPosition/AbstractGridPanel'
import EvaluationDimensionForm from "../../../Tinebase/js/widgets/form/EvaluationDimensionForm";
import PaymentMeansField from './PaymentMeansField'
import TaxByRateField from "./TaxByRateField";
import Record from "../../../Tinebase/js/data/Record";

Tine.Sales.Document_AbstractEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    windowWidth: 1240,
    windowHeight: 1300,

    statusFieldName: null,
    forceAutoValues: true,

    initComponent() {
        Tine.Sales.Document_AbstractEditDialog.superclass.initComponent.call(this)

        // add boilerplate panel/management
        if (this.recordClass.hasField('boilerplates')) {
            this.items.get(0).insert(1, new BoilerplatePanel({}));
        }

        // status handling
        this.fields[this.statusFieldName].on('beforeselect', this.onBeforeStatusSelect, this)
    },

    async onBeforeStatusSelect(statusField, status, idx) {
        if (await Ext.MessageBox.confirm(
            this.app.i18n._('Confirm Status Change'),
            this.app.i18n._('Changing this workflow status might not be revertible. Proceed anyway?')
        ) !== 'yes') {
            return false;
        }

        if (await this.assertDocumentDate() === false) return false

        if (this.record.phantom || this.record.modified) {
            // make sure changes are saved even if booking fails
            await this.applyChanges()
        }

        _.delay(async () => {
            try {
                await this.applyChanges()
            } catch (exception) {
                this.loadRecord('remote');
                Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
            }

        }, 150);
    },

    async assertDocumentDate() {
        const booked = this.getForm().findField(this.statusFieldName).store.data.items.find((r) => r.id === this.record.get(this.statusFieldName)).json.booked
        if (!booked && this.record.get('date') && this.record.get('date').format('Ymd') !== new Date().format('Ymd') && await Ext.MessageBox.show({
            icon: Ext.MessageBox.QUESTION,
            buttons: Ext.MessageBox.YESNO,
            title: this.app.formatMessage('Change Document Date?'),
            msg: this.app.formatMessage('Change document date from { date } to today?', {date: Tine.Tinebase.common.dateRenderer(this.record.get('date'))}),
        }) === 'yes') {
            this.getForm().findField('date').setValue(new Date().clearTime());
        }
    },

    checkStates () {
        if (this.loadRequest) {
            return _.delay(_.bind(this.checkStates, this), 250)
        }

        // default category
        const categoryField = this.getForm().findField('document_category');
        if (categoryField && !categoryField.selectedRecord) {
            const category = Tine.Tinebase.data.Record.setFromJson(Tine.Tinebase.configManager.get('documentCategoryDefault', 'Sales'), 'Sales.Document_Category');
            categoryField.setValue(category);
            categoryField.onSelect(category, 0);
        }

        const currencyField = this.getForm().findField('document_currency');
        if (currencyField) {
            const currency = currencyField.store.getById(currencyField.getValue());
            const currencySymbol = currency ? currency.get('symbol') || currency.get('shortName') : (currencyField.getValue() || '')
            this.getForm().items.each(field => {
                _.isFunction(field?.setCurrencySymbol) ? field.setCurrencySymbol(currencySymbol) : null
            })
        }

        const possField = this.getForm().findField('positions')
        const positions = possField.getValue() || []
        const sums = getPositionsSums(positions)
        const lastSums = getPositionsSums(possField.lastValue || [])
        possField.lastValue = _.cloneDeep(positions)

        Object.keys(sums).forEach((fld) => {
            if (! fld.match(/_sum$/)) return

            if (this.recordClass.hasField(fld) && (this.record.get(fld) || 0) === lastSums[fld]) {
                this.record.set(fld, sums[fld])
            }

            const field = this.getForm().findField(fld)
            if (field && (field.getValue() ||0) === lastSums[fld]) {
                field.setValue(sums[fld], this.record)
                field.lastValue = lastSums[fld]
            }
        })

        if (!positions.length && (
            (this.getForm().findField('positions_gross_sum')?.getValue() && !this.getForm().findField('positions_net_sum')?.getValue()) ||
            (this.getForm().findField('gross_sum')?.getValue() && !this.getForm().findField('net_sum')?.getValue())) ) {

            this.document_price_type = 'gross';
        }

        const document_price_type = positions.length ? sums.document_price_type : (this.document_price_type || 'net');

        // make sure discount calculations run
        if (this.getForm().findField('invoice_discount_sum')) {
            this.getForm().findField('invoice_discount_sum').price_field = document_price_type === 'gross' ? 'positions_gross_sum' : 'positions_net_sum';
            this.getForm().findField('invoice_discount_sum').net_field = document_price_type === 'gross' ? 'gross_sum' : 'net_sum';
        }
        Tine.Sales.Document_AbstractEditDialog.superclass.checkStates.apply(this, arguments)

        this.getForm().findField('positions_net_sum')?.setVisible(document_price_type !== 'gross');
        this.getForm().findField('positions_gross_sum')?.setVisible(document_price_type === 'gross');

        const autoValues = (record, sums, document_price_type) => {
            let positions_net_sum, positions_gross_sum, net_sum, sales_tax, sales_tax_by_rate, gross_sum
            if (document_price_type === 'gross') {
                if (record.get('positions').length) {
                    // sales_tax & sales_tax_by_rate
                    // ok discount is already applied -> lower sales_tax_by_rate by discount rate
                    sales_tax = this.recordClass.toFixed(Object.keys(sums['gross_sum_by_tax_rate']).reduce((a, rate) => {
                        const factor = (1 - record.get('invoice_discount_sum') / record.get('positions_gross_sum')) || 0
                        ['sales_tax_by_rate', 'net_sum_by_tax_rate', 'gross_sum_by_tax_rate'].forEach(key => sums[key][rate] = sums[key][rate] * factor)
                        return a + sums['sales_tax_by_rate'][rate]
                    }, 0))
                } else {
                    if (!record.get('sales_tax_by_rate')?.length || record.get('sales_tax_by_rate').length === 1) {
                        sales_tax_by_rate = record.get('sales_tax_by_rate')?.[0]?.tax_rate || Tine.Tinebase.configManager.get('salesTax')
                        sales_tax = record.get('sales_tax_by_rate')?.[0]?.tax_amount || 0
                        sales_tax = (record.get('gross_sum') || 0) - (record.get('gross_sum') || 0) / (1 + sales_tax_by_rate / 100)
                    } else {
                        // manual tax breakdown
                        sales_tax = _.sum(_.map(record.data.sales_tax_by_rate, 'tax_amount'))
                    }
                }

                positions_net_sum = this.recordClass.toFixed(record.get('positions_gross_sum') - sales_tax);
                net_sum = this.recordClass.toFixed(positions_net_sum - record.get('invoice_discount_sum'));

            } else {
                if (record.get('positions').length) {
                    sales_tax = this.recordClass.toFixed(Object.keys(sums['net_sum_by_tax_rate']).reduce((a, rate) => {
                        sums['sales_tax_by_rate'][rate] = (sums['net_sum_by_tax_rate'][rate] - record.get('invoice_discount_sum') * ((sums['net_sum_by_tax_rate'][rate] / record.get('positions_net_sum')) || 0)) * rate / 100
                        return a + sums['sales_tax_by_rate'][rate]
                    }, 0))
                } else {
                    if (!record.get('sales_tax_by_rate')?.length || record.get('sales_tax_by_rate').length === 1) {
                        sales_tax_by_rate = record.get('sales_tax_by_rate')?.[0]?.tax_rate || Tine.Tinebase.configManager.get('salesTax')
                        sales_tax = record.get('sales_tax_by_rate')?.[0]?.tax_amount || 0
                        sales_tax = (record.get('net_sum') || 0) / 100 * sales_tax_by_rate;
                    } else {
                        // manual tax breakdown
                        sales_tax = _.sum(_.map(record.data.sales_tax_by_rate, 'tax_amount'))
                    }
                }

                positions_gross_sum = this.recordClass.toFixed(this.recordClass.toFixed(record.get('positions_net_sum')) + sales_tax);
                gross_sum = this.recordClass.toFixed(positions_gross_sum - record.get('invoice_discount_sum'));
            }

            // reformat sales_tax_by_rate
            if (record.get('positions').length) {
                sales_tax_by_rate = Object.keys(sums['sales_tax_by_rate']).reduce((a, rate) => {
                    const oldRate = _.find( record.get('sales_tax_by_rate') || [], {tax_rate: Number(rate)}) ||
                        Tine.Sales.Model.Document_SalesTax.setFromJson({}).data

                    return a.concat(_.isNumber(Number(rate)) ? [Object.assign(oldRate, {
                        'net_amount': sums['net_sum_by_tax_rate'][rate],
                        'tax_rate': Number(rate),
                        'tax_amount': sums['sales_tax_by_rate'][rate],
                        'gross_amount': sums['gross_sum_by_tax_rate'][rate]
                    })] : [])
                }, Tine.Tinebase.common.assertComparable([]))
            } else {
                if (!record.get('sales_tax_by_rate')?.length || record.get('sales_tax_by_rate').length === 1) {
                    const tax_rate = Number(sales_tax_by_rate)
                    const tax_amount = sales_tax
                    const net_amount = this.recordClass.toFixed(tax_amount / tax_rate * 100)
                    const gross_amount = this.recordClass.toFixed(net_amount + tax_amount)

                    const oldRate = _.find(record.get('sales_tax_by_rate') || [], {tax_rate: Number(tax_rate)})

                    sales_tax_by_rate = !oldRate && tax_amount === 0 && net_amount === 0 && gross_amount === 0 ? null :
                        Tine.Tinebase.common.assertComparable([Object.assign(oldRate || Tine.Sales.Model.Document_SalesTax.setFromJson({}).data, {
                            net_amount,
                            tax_rate,
                            tax_amount,
                            gross_amount
                        })])
                } else {
                    // manual tax breakdown
                    sales_tax_by_rate = record.get('sales_tax_by_rate')
                }
            }
            sales_tax = _.reduce(sales_tax_by_rate, (a, tax) => a + tax.tax_amount, 0);

            
            return { positions_net_sum, positions_gross_sum, net_sum, sales_tax, sales_tax_by_rate, gross_sum };
        }

        this.lastRecord = this.lastRecord || this.record
        const { positions_net_sum: last_positions_net_sum, positions_gross_sum: last_positions_gross_sum, net_sum: last_net_sum, sales_tax: last_sales_tax, sales_tax_by_rate: last_sales_tax_by_rate, gross_sum: last_gross_sum } = autoValues(this.lastRecord, lastSums, this.lastRecord.get('positions').length ? lastSums.document_price_type : (this.document_price_type || 'net'));
        const { positions_net_sum, positions_gross_sum, net_sum, sales_tax, sales_tax_by_rate, gross_sum } = autoValues(this.record, sums, document_price_type);

        if (document_price_type === 'gross') {
            if (this.forceAutoValues || (this.getForm().findField('positions_net_sum')?.getValue() || 0) === (last_positions_net_sum || 0)) {
                this.record.set('positions_net_sum', positions_net_sum);
                this.getForm().findField('positions_net_sum')?.setValue(positions_net_sum);
            }
            if (this.forceAutoValues || (this.getForm().findField('net_sum')?.getValue() || 0) === (last_net_sum || 0)) {
                this.record.set('net_sum', net_sum);
                this.getForm().findField('net_sum')?.setValue(net_sum);
            }
        }
        if (this.forceAutoValues || (this.getForm().findField('sales_tax')?.getValue() || 0) === (last_sales_tax || 0)) {
            this.record.set('sales_tax', sales_tax);
            this.getForm().findField('sales_tax')?.setValue(sales_tax);
        }
        if (this.forceAutoValues || ['null', JSON.stringify(last_sales_tax_by_rate)].indexOf(JSON.stringify(this.getForm().findField('sales_tax_by_rate')?.getValue())) >= 0) {
            this.record.set('sales_tax_by_rate', sales_tax_by_rate);
            this.getForm().findField('sales_tax_by_rate')?.setValue(sales_tax_by_rate);
        }
        if (document_price_type === 'net') {
            if (this.forceAutoValues || (this.getForm().findField('positions_gross_sum')?.getValue() || 0) === (last_positions_gross_sum || 0)) {
                this.record.set('positions_gross_sum', positions_gross_sum);
                this.getForm().findField('positions_gross_sum')?.setValue(positions_gross_sum);
            }
            if (this.forceAutoValues || (this.getForm().findField('gross_sum')?.getValue() || 0) === (last_gross_sum || 0)) {
                this.record.set('gross_sum', gross_sum);
                this.getForm().findField('gross_sum')?.setValue(gross_sum);
            }
        }

        // handle booked state
        const statusField = this.fields[this.statusFieldName]
        const booked = statusField.store.getById(statusField.getValue())?.json.booked
        if (booked) { // there is no transition booked -> unbooked
            this.getForm().items.each((field) => {
                if (_.get(field, 'initialConfig.readOnly')) return;
                if ([this.statusFieldName, 'description', 'buyer_reference', 'contact_id', 'tags', 'attachments', 'relations', 'payment_reminders'].concat(this.writeableAfterBooked || []).indexOf(field.name) < 0
                    && !field.name?.match(/(^shared_.*)|(.*_recipient_id$)|(^eval_dim_.*)/)) {
                    field.setReadOnly(booked);
                }
            });
        }

        // check service period contains all positions
        let servicePeriodAdopted = false
        const serviceStart = this.getForm().findField('service_period_start')?.getValue();
        const minPosServiceStart = _.reduce(positions, (minDate, pos) => {
            return !minDate ? pos.service_period_start : (pos.service_period_start < minDate ? pos.service_period_start : minDate)
        }, null)
        if (serviceStart && minPosServiceStart && serviceStart > minPosServiceStart) {
            this.getForm().findField('service_period_start')?.setValue(minPosServiceStart);
            servicePeriodAdopted = true
        }
        const serviceEnd = this.getForm().findField('service_period_end')?.getValue();
        const maxServiceEnd = _.reduce(positions, (maxDate, pos) => {
            return !maxDate ? pos.service_period_end : (pos.service_period_end > maxDate ? pos.service_period_end : maxDate)
        }, null)
        if (serviceEnd && maxServiceEnd > serviceEnd) {
            this.getForm().findField('service_period_end')?.setValue(maxServiceEnd);
            servicePeriodAdopted = true
        }

        if (servicePeriodAdopted) {
            Ext.MessageBox.show({
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.WARNING,
                title: this.app.i18n._('Service Start Extended'),
                msg: this.app.i18n._("The service period of the invoice has been extended so that it now includes the service periods of all items.")
            });
        }

        // handle eval_dim division subfilter
        this.getForm().items.each((field) => {
            if (field.name?.match(/(^eval_dim_.*)/) && !field._documentEditDialogEvalDimBeforeLoadApplied) {
                field.store.on('beforeload', (store, options) => {
                    const category = this.getForm().findField('document_category')?.selectedRecord;
                    const division = category ? _.get(category, 'data.division_id.id') : this.record.get('division_id')?.id;
                    store.baseParams.filter = store.baseParams.filter.concat([
                        { condition: 'OR', filters: [
                            { field: 'divisions', operator: 'definedBy', value: null },
                            { field: 'divisions', operator: 'definedBy', value: [
                                { field: 'division_id', operator: 'equals', value: division }
                            ]}
                        ] }
                    ])

                })
                field._documentEditDialogEvalDimBeforeLoadApplied = true;
            }
        })

        this.lastRecord = Tine.Tinebase.data.Record.clone(this.record);
    },

    getRecordFormItems: function() {
        const fields = this.fields = Tine.widgets.form.RecordForm.getFormFields(this.recordClass, (fieldName, config, fieldDefinition) => {
            switch (fieldName) {
                case 'document_category':
                    config.listeners = config.listeners || {};
                    config.listeners.beforeselect = async (combo, category, index) => {
                        const division = _.get(category, 'data.division_id');
                        const customer = this.getForm().findField('customer_id').selectedRecord;
                        if (_.uniq(_.map(customer.get('debitors') || [], 'division_id.id')).indexOf(division.id) < 0) {
                            Ext.Msg.alert(this.app.i18n._('No Matching Debitor'), this.app.formatMessage("The category <b>{category}</b> can't be selected as the customer <b>{customer}</b> has no debitor for the division <b>{division.title}</b> of the category.", {category: await category.getTitle(), customer: await customer.getTitle(), division}));
                            return false;
                        }
                    }
                    config.listeners.select = (combo, record, index) => {
                        this.getForm().items.each((field) => {
                            if (field.name?.match(/(^eval_dim_.*)/)) {
                                field.lastQuery = Tine.Tinebase.data.Record.generateUID();
                            }
                        });
                        _.forEach(record?.data, (val, key) => {
                            if (key.match(/^eval_dim_(.*)/) && this.getForm().findField(key) && val) {
                                this.getForm().findField(key).setValue(val);
                            }
                        });
                    }
                    break;
                case 'customer_id':
                    config.listeners = config.listeners || {};
                    config.listeners.beforeselect = async (combo, record, index) => {
                        const category = this.getForm().findField('document_category').selectedRecord;
                        const division = _.get(category, 'data.division_id');
                        if (_.uniq(_.map(record.get('debitors') || [], 'division_id.id')).indexOf(division.id) < 0) {
                            Ext.Msg.alert(this.app.i18n._('No Matching Debitor'), this.app.formatMessage("The customer <b>{customer}</b> can't be selected as it has no debitor for the division <b>{division.title}</b> of this documents' category <b>{category}</b>.", {customer: await record.getTitle(), division, category: category.getTitle()}));
                            return false;
                        }
                    }
                    config.listeners.select = (combo, record, index) => {
                        fields['credit_term']?.setValue(record.get('credit_term'))
                        fields['document_language'].setValue(record.get('language') || fields['document_language'].getValue())
                        if (record.get('discount')) {
                            fields['invoice_discount_type'].setValue('PERCENTAGE')
                            fields['invoice_discount_percentage'].setValue(record.get('discount'))
                        }
                        const vatProcedure = record.get('vat_procedure')
                        if (vatProcedure) {
                            fields['vat_procedure']?.setValue(vatProcedure)
                        }
                    }
                    break;
                case 'recipient_id':
                    config.listeners = config.listeners || {}
                    config.listeners.select = (combo, record, index) => {
                        if (record?.get && record?.get('language')) {
                            fields['document_language'].setValue(record?.get('language'))
                        }
                        fields['buyer_reference'].setValue(_.get(record, 'data.debitor_id.buyer_reference', ''))
                        this.record.set('debitor_id', _.get(record, 'data.debitor_id', null))
                    }
                    config.plugins = config.plugins || []
                    config.plugins.push(new FieldTriggerPlugin({
                        triggerClass: 'SalesDebitor',
                        qtip: this.app.i18n._('Open Debitor'),
                        onTriggerClick: () => {
                            // @TODO open document debitor once dispatch config gets denormalized
                            const debitorId = this.record.get('debitor_id').original_id || this.record.get('debitor_id').id
                            Tine.Sales.DebitorEditDialog.openWindow({recordId: debitorId, record: {id: debitorId}, mode: 'remote'})
                        }
                    }))
                    // more logic in Tine.Sales.AddressSearchCombo
                    break;
                case 'vat_procedure':
                    config.listeners = config.listeners || {}
                    config.listeners.select = (combo, record, index) => {
                        const positions = fields['positions'].getValue()
                        positions.forEach((positionData, idx) => {
                            const position = Tine.Tinebase.data.Record.setFromJson(positionData, fields['positions'].recordClass)
                            const productTaxRate = _.get(positionData, 'product_id.salestaxrate', 0)
                            if (record.id === 'standard' && position.get('sales_tax_rate') === 0 && productTaxRate) {
                                position.set('sales_tax_rate', productTaxRate)
                            } else if (record.id !== 'standard' && position.get('sales_tax_rate')) {
                                if (position.get('unit_price_type') === 'gross') {
                                    position.set('unit_price', position.get('unit_price') - (position.get('sales_tax')/position.get('quantity') || 0))
                                    position.set('unit_price_type', 'net')
                                }
                                position.set('sales_tax_rate', 0)
                            }
                            position.computePrice()
                            positions[idx] = position.getData()
                        })
                        this.getForm().findField('positions')?.setValue(positions)
                    }
                    break;
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
                [fields.document_number, fields.document_proforma_number || placeholder, fields[this.statusFieldName], fields.document_category, fields.document_language],
                // NOTE: contract_id waits for contract rewrite
                [/*fields.contract_id, */ _.assign(fields.customer_id, {columnWidth: 2/5}), _.assign(fields.recipient_id, {columnWidth: 3/5})],
                _.assign([ _.assign(fields.buyer_reference, {columnWidth: 2/5}), fields.purchase_order_reference, fields.project_reference, fields.contact_id], {line: 'references'}),
                [fields.service_period_start, fields.service_period_end, _.assign({ ...placeholder } , {columnWidth: 3/5})],
                [ _.assign(fields.document_title, {columnWidth: 3/5}), { ...placeholder }, fields.date ],
                [{xtype: 'textarea', name: 'boilerplate_Pretext', allowBlank: false, enableKeyEvents: true, height: 70, fieldLabel: `${this.app.i18n._('Boilerplate')}: Pretext`}],
                [fields.positions],
                [_.assign({ ...placeholder } , {columnWidth: 3/5}), _.assign(fields.positions_discount_sum, {columnWidth: 1/5}), _.assign(fields.positions_net_sum, {columnWidth: 1/5}), _.assign(fields.positions_gross_sum, {columnWidth: 1/5})],
                [_.assign({ ...placeholder } , {columnWidth: 2/5}), fields.invoice_discount_type, fields.invoice_discount_percentage, fields.invoice_discount_sum],
                [{ ...placeholder }, fields.net_sum, fields.vat_procedure, fields.sales_tax_by_rate, fields.gross_sum],
                [new PaymentMeansField({editDialog: this, columnWidth: 2/5}), fields.credit_term, _.assign({ ...placeholder } , {columnWidth: 2/5})],
                [{xtype: 'textarea', name: 'boilerplate_Posttext', allowBlank: false, enableKeyEvents: true, height: 70, fieldLabel: `${this.app.i18n._('Boilerplate')}: Posttext`}],
                [new EvaluationDimensionForm({recordClass: this.recordClass})]
            ]
        }]
    }

});

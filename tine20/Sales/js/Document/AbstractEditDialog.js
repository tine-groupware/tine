/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

import { BoilerplatePanel } from './BoilerplatePanel'
import EvaluationDimensionForm from "../../../Tinebase/js/widgets/form/EvaluationDimensionForm";

Tine.Sales.Document_AbstractEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    windowWidth: 1240,
    windowHeight: 1300,

    statusFieldName: null,
    
    initComponent() {
        Tine.Sales.Document_AbstractEditDialog.superclass.initComponent.call(this)

        // add boilerplate panel/management
        this.items.get(0).insert(1, new BoilerplatePanel({}));

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

        if (this.record.phantom) {
            // new documents need to be saved first to get a proforma number
            await this.applyChanges()
        }

        _.delay(async () => {
            try {
                await this.applyChanges()
            } catch (exception) {
                Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
                this.getForm().findField(this.statusFieldName).setValue(this.record.modified[this.statusFieldName]);
            }

        }, 150);
    },

    checkStates () {
        if(this.loadRequest){
            return _.delay(_.bind(this.checkStates, this), 250)
        }

        // default category
        const categoryField = this.getForm().findField('document_category');
        if (!categoryField.selectedRecord) {
            const category = Tine.Tinebase.data.Record.setFromJson(Tine.Tinebase.configManager.get('documentCategoryDefault', 'Sales'), 'Sales.Document_Category');
            categoryField.setValue(category);
            categoryField.onSelect(category, 0);
        }

        const positions = this.getForm().findField('positions').getValue(); //this.record.get('positions')
        const sums = positions.reduce((a, pos) => {
            a['positions_net_sum'] = (a['positions_net_sum'] || 0) + (pos['net_price'] || 0)
            a['positions_discount_sum'] = (a['positions_discount_sum'] || 0) + (pos['position_discount_sum'] || 0)

            const rate = pos['sales_tax_rate'] || 0
            a['sales_tax_by_rate'][rate] = (a['sales_tax_by_rate'].hasOwnProperty(rate) ? a['sales_tax_by_rate'][rate] : 0) + (pos['sales_tax'] || 0)
            a['net_sum_by_tax_rate'][rate] = (a['net_sum_by_tax_rate'].hasOwnProperty(rate) ? a['net_sum_by_tax_rate'][rate] : 0) + (pos['net_price'] || 0)

            return a;
        }, {positions_net_sum:0, positions_discount_sum: 0, sales_tax_by_rate: {}, net_sum_by_tax_rate: {}})

        Object.keys(sums).forEach((fld) => {
            if (this.recordClass.hasField(fld)) {
                this.record.set(fld, sums[fld])
            }
            this.getForm().findField(fld)?.setValue(sums[fld])
        })

        // make sure discount calculations run
        Tine.Sales.Document_AbstractEditDialog.superclass.checkStates.apply(this, arguments)

        this.record.set('sales_tax', Object.keys(sums['net_sum_by_tax_rate']).reduce((a, rate) => {
            sums['sales_tax_by_rate'][rate] = (sums['net_sum_by_tax_rate'][rate] - this.record.get('invoice_discount_sum') * ((sums['net_sum_by_tax_rate'][rate] / this.record.get('positions_net_sum'))||0)) * rate / 100
            return a + sums['sales_tax_by_rate'][rate]
        }, 0))
        this.record.set('sales_tax_by_rate', Object.keys(sums['sales_tax_by_rate']).reduce((a, rate) => {
            return a.concat(Number(rate) ? [{'tax_rate': Number(rate), 'tax_sum': sums['sales_tax_by_rate'][rate]}] : [])
        }, Tine.Tinebase.common.assertComparable([])))
        this.getForm().findField('sales_tax_by_rate')?.setValue(this.record.get('sales_tax_by_rate'))
        this.getForm().findField('sales_tax')?.setValue(this.record.get('sales_tax'))

        this.record.set('gross_sum', this.record.get('positions_net_sum') - this.record.get('invoice_discount_sum') + this.record.get('sales_tax'))
        this.getForm().findField('gross_sum')?.setValue(this.record.get('gross_sum'))

        // handle booked state
        const statusField = this.fields[this.statusFieldName]
        const booked = statusField.store.getById(statusField.getValue())?.json.booked
        this.getForm().items.each((field) => {
            if (_.get(field, 'initialConfig.readOnly')) return;
            if ([this.statusFieldName, 'description', 'customer_reference', 'contact_id', 'tags', 'attachments', 'relations'].indexOf(field.name) < 0
            && !field.name?.match(/(^shared_.*)|(.*_recipient_id$)|(^eval_dim_.*)/)) {
                field.setReadOnly(booked);
            }
        });

        // handle eval_dim division subfilter
        this.getForm().items.each((field) => {
            if (field.name?.match(/(^eval_dim_.*)/) && !field._documentEditDialogEvalDimBeforeLoadApplied) {
                field.store.on('beforeload', (store, options) => {
                    const category = this.getForm().findField('document_category').selectedRecord;
                    const division = _.get(category, 'data.division_id.id');
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

    },

    getRecordFormItems: function() {
        const fields = this.fields = Tine.widgets.form.RecordForm.getFormFields(this.recordClass, (fieldName, config, fieldDefinition) => {
            switch (fieldName) {
                case 'document_category':
                    config.listeners = config.listeners || {}
                    config.listeners.select = (combo, record, index) => {
                        _.forEach(record?.data, (val, key) => {
                            if (key.match(/^eval_dim_(.*)/) && this.getForm().findField(key) && val) {
                                this.getForm().findField(key).setValue(val);
                            }
                        });
                    }
                    break;
                case 'customer_id':
                    config.listeners = config.listeners || {}
                    config.listeners.select = (combo, record, index) => {
                        fields['credit_term'].setValue(record.get('credit_term'))
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
                    }
                    // more logic in Tine.Sales.AddressSearchCombo
                    break;
                case 'vat_procedure':
                    config.listeners = config.listeners || {}
                    config.listeners.select = (combo, record, index) => {
                        const positions = fields['positions'].getValue()
                        positions.forEach((positionData, idx) => {
                            const position = Tine.Tinebase.data.Record.setFromJson(positionData, fields['positions'].recordClass)
                            const productTaxRate = _.get(positionData, 'product_id.salestaxrate', 0)
                            if (record.id === 'taxable' && position.get('sales_tax_rate') === 0 && productTaxRate) {
                                position.set('sales_tax_rate', productTaxRate)
                            } else if (record.id !== 'taxable' && position.get('sales_tax_rate')) {
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
            items: [
                [fields.document_number, fields.document_proforma_number || placeholder, fields[this.statusFieldName], fields.document_category, fields.document_language],
                _.assign([fields.customer_id, _.assign(fields.recipient_id, {columnWidth: 2/5}), fields.contact_id, fields.customer_reference], {line: 'recipient'}),
                [ _.assign(fields.document_title, {columnWidth: 3/5}), { ...placeholder }, fields.date ],
                [{xtype: 'textarea', name: 'boilerplate_Pretext', allowBlank: false, enableKeyEvents: true, height: 70, fieldLabel: `${this.app.i18n._('Boilerplate')}: Pretext`}],
                [fields.positions],
                [_.assign({ ...placeholder } , {columnWidth: 3/5}), fields.positions_discount_sum, fields.positions_net_sum],
                [_.assign({ ...placeholder } , {columnWidth: 2/5}), fields.invoice_discount_type, fields.invoice_discount_percentage, fields.invoice_discount_sum],
                [{ ...placeholder }, fields.net_sum, fields.vat_procedure, fields.sales_tax, fields.gross_sum],
                [fields.credit_term, _.assign({ ...placeholder } , {columnWidth: 4/5})],
                [{xtype: 'textarea', name: 'boilerplate_Posttext', allowBlank: false, enableKeyEvents: true, height: 70, fieldLabel: `${this.app.i18n._('Boilerplate')}: Posttext`}],
                [new EvaluationDimensionForm({recordClass: this.recordClass})]
            ]
        }]
    }

});

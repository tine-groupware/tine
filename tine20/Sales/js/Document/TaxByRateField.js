/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import TaxByRateGridPanel from "./TaxByRateGridPanel";

const TaxByRateField = Ext.extend(Ext.ux.form.LayerCombo, {
    minLayerWidth: 400,
    validationEvent: 'blur',

    // triggerClass: 'sales-tax-by-rate-trigger',

    initComponent() {
        this.formConfig = Object.assign(this.formConfig || {}, {
            hideLabels: true
        })

        this.gridPanel = new TaxByRateGridPanel({
            autoHeight: false,
            height: 140
        })

        TaxByRateField.superclass.initComponent.call(this)
    },

    getItems() {
        return this.gridPanel
    },

    setFormValue(value) {
        this.gridPanel.setStoreFromArray(value || [])
    },

    getFormValue() {
        return this.gridPanel.getFromStoreAsArray(true)
    },

    setValue(value, editDialog) {
        value = _.cloneDeep(value)
        TaxByRateField.superclass.setValue.call(this, value, editDialog)
        // @TODO take currency from record/document
        this.setRawValue(this.valueToString(value))
    },

    setCurrencySymbol: function(currencySymbol) {
        if (currencySymbol !== this.currencySymbol) {
            this.currencySymbol = currencySymbol
            this.gridPanel.setCurrencySymbol(currencySymbol)
            this.setRawValue(this.valueToString(this.currentValue))
        }
    },

    valueToString(value) {
        return _.map(_.filter(value, tax => tax.tax_rate > 0), tax => `${Ext.util.Format.money(tax.tax_amount, {currencySymbol: this.currencySymbol})} (${tax.tax_rate}%)`).join(', ')
    },

    processValue(value) {
        value = (String(value).match(/^[0-9,. ]+%$/) ? `${this.currentValue.length === 1 ? this.currentValue[0].tax_amount/this.currentValue[0].tax_rate * parseFloat(String(value).replace(',', '.').replace('%', '')) : 0} (${value})` : value) || 0;
        value = _.reduce(_.compact(String(value).split(/%\),?/)), (a, v) => {
            let [t,r] = String(v).split(/[^0-9,.]+\(/)

            const tax_rate = parseFloat(String(r).replace(',', '.')) || Tine.Tinebase.configManager.get('salesTax')
            const tax_amount = parseFloat(String(t).replace(',', '.')) || 0
            const net_amount = Tine.Sales.Model.Document_InvoiceMixin.statics.toFixed(tax_amount / tax_rate * 100)
            const gross_amount = Tine.Sales.Model.Document_InvoiceMixin.statics.toFixed(net_amount + tax_amount)

            return a.concat({ net_amount, tax_rate, tax_amount, gross_amount })
        }, [])

        if (value?.length !== this.currentValue?.length || _.reduce(value, (a,v) => {
            const existing = _.find(this.currentValue, {tax_rate: v.tax_rate})
            return a || !existing || Tine.Sales.Model.Document_InvoiceMixin.statics.toFixed(existing.tax_amount) !== v.tax_amount
        }, false)) {
            const oldValue = this.currentValue
            this.currentValue = value
            this.gridPanel.setStoreFromArray(value)
            this.fireEvent('change', this, value, oldValue)
        }

        this.setRawValue(this.valueToString(value))
    },
})

Ext.reg('sales-taxbyrate-field', TaxByRateField)

export default TaxByRateField
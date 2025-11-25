/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import TaxByRateGridPanel from "./TaxByRateGridPanel";

const TaxByRateField = Ext.extend(Ext.ux.form.LayerCombo, {
    minLayerWidth: 200,
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

    // enable readonly layer
    updateEditState() {},

    setFormValue(value) {
        this.gridPanel.setStoreFromArray(_.filter(value, tax => tax.tax_amount > 0) || [])
    },

    getFormValue() {
        return this.gridPanel.getFromStoreAsArray(true)
    },

    setValue(value, editDialog) {
        TaxByRateField.superclass.setValue.apply(this, arguments)
        // @TODO take currency from record/document
        this.setRawValue(this.valueToString(value))
    },

    valueToString(value) {
        return _.map(_.filter(value, tax => tax.tax_rate > 0), tax => `${Ext.util.Format.money(tax.tax_amount)} (${tax.tax_rate}%)`).join(', ')
    },

    processValue(value) {
        if (String(value).match(/[0-9.,]+/)) {
            this.currentValue = value = [{tax_amount: parseFloat(String(value).replace(',', '.')), tax_rate: Tine.Tinebase.configManager.get('salesTax')}]
            this.gridPanel.setStoreFromArray(value)
        }
        this.setRawValue(this.valueToString(value))
    },
})

Ext.reg('sales-taxbyrate-field', TaxByRateField)

export default TaxByRateField
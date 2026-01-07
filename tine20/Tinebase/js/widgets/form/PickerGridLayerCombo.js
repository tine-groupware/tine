/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const PickerGridLayerCombo = Ext.extend(Ext.ux.form.LayerCombo, {
    minLayerWidth: 200,
    validationEvent: 'blur',

    initComponent() {
        this.formConfig = Object.assign(this.formConfig || {}, {
            hideLabels: true
        })

        this.gridPanel = new Tine.widgets.grid.PickerGridPanel(Object.assign({}, this,{
            autoHeight: false,
            height: 140
        }))

        PickerGridLayerCombo.superclass.initComponent.call(this)
    },

    getItems() {
        return this.gridPanel
    },

    // enable readonly layer
    updateEditState() {},

    setFormValue(value) {
        this.gridPanel.setValue(value || [])
    },

    getFormValue() {
        return this.gridPanel.getValue()
    },

    setValue(value, editDialog) {
        PickerGridLayerCombo.superclass.setValue.apply(this, arguments)
        this.valueToString(value).then((s) => this.setRawValue(s))
    },

    async valueToString(value) {
        return (await Promise.all(_.map(this.currentValue, d => this.recordClass.setFromJson(d).getTitle().asString()))).join(', ')
    },
})

export default PickerGridLayerCombo

Ext.reg('wdgt.pickergrid-layercombo', PickerGridLayerCombo);
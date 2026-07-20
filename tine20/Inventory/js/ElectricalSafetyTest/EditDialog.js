// import {bind, delay} from "lodash";

Tine.Inventory.ElectricalSafetyTestEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    initComponent: function() {
        Tine.Inventory.ElectricalSafetyTestEditDialog.superclass.initComponent.call(this);

        this.getForm().findField('equipment_id').additionalFilters = this.getEEFilter?.()
    },

    onAfterRecordLoad: function () {
        this.initialConfig.readOnly = this.record.phantom;
        Tine.Inventory.ElectricalSafetyTestEditDialog.superclass.onAfterRecordLoad.call(this);
    },

    checkStates: function() {
        Tine.Inventory.ElectricalSafetyTestEditDialog.superclass.checkStates.call(this);

        const ee = this.getForm().findField('equipment_id').selectedRecord;
        const protection_class = this.eeRecord ? this.eeRecord.get('protection_class') : ee?.get('protection_class');

        this.getForm().findField('protective_conductor_resistance').setVisible(['I'].indexOf(protection_class) >= 0);
        this.getForm().findField('insulation_resistance').setVisible(['I', 'II'].indexOf(protection_class) >= 0);
        this.getForm().findField('protective_conductor_current').setVisible(['I'].indexOf(protection_class) >= 0);
        this.getForm().findField('touch_current').setVisible(['I', 'II'].indexOf(protection_class) >= 0);
    }
})

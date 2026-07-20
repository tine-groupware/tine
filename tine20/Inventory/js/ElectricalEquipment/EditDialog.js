import { find, wrap, bind, delay } from "lodash";

Tine.Inventory.ElectricalEquipmentEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    initComponent: function() {
        Tine.Inventory.ElectricalEquipmentEditDialog.superclass.initComponent.call(this);

        const estField = this.getForm().findField('electrical_safety_tests');
        estField.editDialogConfig = estField.editDialogConfig || {};
        estField.editDialogConfig.eeRecord = this.record;

        const eeCol = find(estField.colModel.config, {id: 'equipment_id'});
        eeCol.renderer = wrap(eeCol.renderer, (func, ...args ) => {
            args[0] = this.record.getData();
            return func(...args);
        });
    },
    checkStates: function() {
        Tine.Inventory.ElectricalEquipmentEditDialog.superclass.checkStates.call(this);

        // add ee to grid


    }

    // getRecordFormItems: function() {
    //     const me = this
    //     const fields = this.fields = Tine.widgets.form.RecordForm.getFormFields(this.recordClass, (fieldName, config, fieldDefinition) => {
    //         switch (fieldName) {
    //             case 'electrical_safety_tests':
    //                 config.columns = ['test_date', 'equipment_id', 'test_passed']
    //                 // config.editDialogConfig = {
    //                     // hideFields: ['equipment_id']
    //                 // }
    //                 break;
    //         }
    //     });
    // }
})

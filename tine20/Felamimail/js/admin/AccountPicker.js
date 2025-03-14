AccountPicker = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    recordClass: 'Felamimail.Account', // @TODO: switch to Admin.EmailAccount once implemented and announced
    initComponent() {
        this.recordProxy = new Tine.Tinebase.data.RecordProxy({
            appName: 'Admin',
            modelName: 'EmailAccount',
            recordClass: this.recordClass,
            idProperty: 'id'
        });

        AccountPicker.superclass.initComponent.call(this)
    }
})


Ext.reg('felamimail-admin-accountpicker', AccountPicker)
Tine.widgets.form.RecordPickerManager.register('Admin', 'EmailAccount', AccountPicker)
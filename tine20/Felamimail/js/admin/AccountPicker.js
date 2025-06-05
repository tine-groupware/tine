AccountPicker = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    recordClass: 'Admin.EmailAccount',

    allowPersonal: false,

    initComponent: function() {
        this.recordProxy = new Tine.Tinebase.data.RecordProxy({
            appName: 'Admin',
            modelName: 'EmailAccount',
            recordClass: Tine.Tinebase.data.RecordMgr.get(this.recordClass),
            idProperty: 'id'
        });

        if (this.allowPersonal !== true && !this.additionalFilters) {
            this.additionalFilters = [
                { field: 'type', operator: 'startswith', value: 'shared' }
            ]
        }

        AccountPicker.superclass.initComponent.call(this)
    }
})


Ext.reg('felamimail-admin-accountpicker', AccountPicker)
Tine.widgets.form.RecordPickerManager.register('Admin', 'EmailAccount', AccountPicker)
AccountPicker = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    recordClass: 'Admin.EmailAccount',
    wtf: 'fuckfuckfuc',

    initComponent: function() {
        this.recordProxy = new Tine.Tinebase.data.RecordProxy({
            appName: 'Admin',
            modelName: 'EmailAccount',
            recordClass: Tine.Tinebase.data.RecordMgr.get(this.recordClass),
            idProperty: 'id'
        });

        AccountPicker.superclass.initComponent.call(this)
    }
})


Ext.reg('felamimail-admin-accountpicker', AccountPicker)
Tine.widgets.form.RecordPickerManager.register('Admin', 'EmailAccount', AccountPicker)
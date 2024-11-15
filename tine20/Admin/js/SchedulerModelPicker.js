const modelPicker = Ext.extend(Tine.Tinebase.widgets.form.ModelPicker, {
    // emptyText: 'Built in configs',
    // fieldLabel: 'Task Type',
    allowBlank: true,
    initComponent() {
        this.availableModels = _.get(Tine.Admin.Model.SchedulerTask.getModelConfiguration(), 'fields.config_class.config.availableModels');
        this.supr().initComponent.call(this);
        const app =  Tine.Tinebase.appMgr.get('Admin');
        this.emptyText = app.i18n._('Built in configs');
    },
    checkState(editDialog, record) {
        this.setDisabled(record && !!+record.get('is_system'));
        editDialog.getForm().findField('config').setDisabled(record && !!+record.get('is_system'));
    }
})

Ext.reg('admin-schedulertask-modelpicker', modelPicker);

Tine.widgets.form.FieldManager.register('Admin', 'SchedulerTask', 'config_class', {
    xtype: 'admin-schedulertask-modelpicker'
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);

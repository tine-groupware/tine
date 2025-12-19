const FieldWrapper = Ext.extend(Ext.Container, {
    layout: 'fit',
    cls: 'custom-field',
    initComponent() {
        const editDialog = this.findParentBy(function (c) { return c instanceof Tine.widgets.dialog.EditDialog});
        this.items = [Tine.widgets.customfields.Field.get(this.app, this.fields, this.config, editDialog)];

        FieldWrapper.superclass.initComponent.call(this);
    },
})

Ext.reg('customfieldwrapper', FieldWrapper)

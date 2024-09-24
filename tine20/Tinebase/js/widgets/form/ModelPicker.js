/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.widgets.form');

Tine.Tinebase.widgets.form.ModelPicker = Ext.extend(Ext.form.ComboBox, {
    includeAppName: true,

    availableModels: null,

    availableModelsRegExp: null,

    allowBlank: false,
    forceSelection: true,
    displayField: 'modelName',
    valueField: 'className',
    mode: 'local',

    initComponent() {
        const availableModels = this.availableModels?.map((model) => { return Tine.Tinebase.data.RecordMgr.get(model) });
        const availableModelsRegExp = this.availableModelsRegExp ? new RegExp(this.availableModelsRegExp.replaceAll('/','')) : null;

        this.emptyText = i18n._('Search for Model...');

        this.store = new Ext.data.ArrayStore({
            fields: ['className', 'modelName'],
            data: Tine.Tinebase.data.RecordMgr.items.reduce((models, recordClass) => {
                const className = recordClass.getPhpClassName();
                let name = recordClass.getRecordsName();

                name = !name || name === 'records'? recordClass.getMeta('modelName') : name;
                const label = (this.includeAppName ? recordClass.getAppName() + ' ' : '') + name + (this.includeClassName ? ` (${className})` : '');

                if ((!availableModels || availableModels.indexOf(recordClass) >= 0)
                    && (!availableModelsRegExp || availableModelsRegExp.test(className))) {
                    models.push([className, label]);
                }

                return models;
            }, [])
        });
        
        Tine.Tinebase.widgets.form.ModelPicker.superclass.initComponent.call(this);
    }
});

Ext.reg('tw-modelpicker', Tine.Tinebase.widgets.form.ModelPicker);

const modelPicker = Ext.extend(Tine.Tinebase.widgets.form.ModelPicker, {
    emptyText: 'Built in configs',
    fieldLabel: 'Task Type',
    allowBlank: true,
    checkState(editDialog, record) {
        this.setDisabled(record && !!+record.get('is_system'));
        editDialog.getForm().findField('config').setDisabled(record && !!+record.get('is_system'));
    }
})

Ext.reg('admin-schedulertask-modelpicker', modelPicker);
window.setTimeout(() => {
    Tine.widgets.form.FieldManager.register('Admin', 'SchedulerTask', 'config_class', {
        xtype: 'admin-schedulertask-modelpicker'
    }, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);
}, 500);



Ext.reg('tw-modelspickers', Ext.extend(Tine.widgets.grid.PickerGridPanel, {
    searchComboConfig: {
        xtype: 'tw-modelpicker',
        allowBlank: true,
        includeClassName: true
    },
    recordClass: Tine.Tinebase.data.Record.create([
        { name: 'model' },
        { name: 'modelName' },
        { name: 'className' },
    ], {
        appName: 'Tinebase',
        modelName: 'Models',
        idProperty: 'className',
        titleProperty: 'modelName',
        recordName: 'Model', // gettext('GENDER_Model')
        recordsName: 'Models' // ngettext('Model', 'Models', n)
    }),
    isFormField: true,
    getValue: function() {
        return _.map(this.supr().getValue.call(this), 'className');
    },
    setValue: function(value) {
        return this.supr().setValue.call(this, _.map(value, v => { return _.isString(v) ? { className: v, modelName: `${Tine.Tinebase.data.RecordMgr.get(v)?.getRecordsName() || v} (${v})` } : v }));
    }
}));
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.widgets.form');

Tine.Tinebase.widgets.form.ModelPicker = Ext.extend(Ext.form.ComboBox, {
    includeAppName: null,

    availableModels: null,

    availableModelsRegExp: null,

    allowBlank: false,
    forceSelection: true,
    displayField: 'modelName',
    valueField: 'className',
    mode: 'local',
    useRecordName: false,

    initComponent() {
        const availableModels = this.availableModels?.map((model) => { return Tine.Tinebase.data.RecordMgr.get(model) });
        const availableModelsRegExp = this.availableModelsRegExp ? new RegExp(this.availableModelsRegExp.replaceAll('/','')) : null;
        const models = Tine.Tinebase.data.RecordMgr.items.reduce((models, recordClass) => {
            const className = recordClass.getPhpClassName();
            if ((!availableModels || availableModels.indexOf(recordClass) >= 0)
                && (!availableModelsRegExp || availableModelsRegExp.test(className))) {
                models.push(recordClass);
            }
            return models;
        }, []);

        this.includeAppName = _.isBoolean(this.includeAppName) ? this.includeAppName : Object.keys(_.groupBy(models, (recordClass) => recordClass.getAppName())).length > 1;
        this.emptyText = i18n._('Search for Model...');
        this.store = new Ext.data.ArrayStore({
            fields: ['className', 'modelName'],
            data: models.map((recordClass) => {
                const className = recordClass.getPhpClassName();
                let name = this.useRecordName ? recordClass.getRecordName() : recordClass.getRecordsName();
                name = !name || name === 'records' ? recordClass.getMeta('modelName') : name;
                const label = (this.includeAppName ? recordClass.getAppName() + ' ' : '') + name + (this.includeClassName ? ` (${className})` : '');
                return [className, label];
            })
        });

        Tine.Tinebase.widgets.form.ModelPicker.superclass.initComponent.call(this);
    }
});

Ext.reg('tw-modelpicker', Tine.Tinebase.widgets.form.ModelPicker);

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
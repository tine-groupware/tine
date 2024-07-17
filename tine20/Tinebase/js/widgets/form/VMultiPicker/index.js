/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Sohan Deshar<s.deshar@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import BootstrapVueNext from 'bootstrap-vue-next'
import RecordsDisplayContainer from "./RecordsDisplayContainer.vue";
Ext.ns('Tine.Tinebase.widgets.form')

Tine.Tinebase.widgets.form.VMultiPicker = Ext.extend(Ext.BoxComponent, {
    vueHandle: null,
    vueEventBus: null,
    injectKey: null,
    props: null,

    emptyText: '',

    autoHeight: true,

    isFormField: true,

    records: null,

    initComponent: function() {
        this.emptyText = this.emptyText || (this.readOnly || this.disabled ? '' : i18n._('Search for records ...'));
        this.recordClass = Tine.Tinebase.data.RecordMgr.get(this.recordClass);

        // Autodetect if our record has additional metadata for the refId Record or is only a cross table
        if (this.refIdField) {
            const dataFields = _.difference(this.recordClass.getDataFields(), [this.refIdField]);

            this.isMetadataModelFor = this.isMetadataModelFor || dataFields.length === 1 /* precisely this is a cross-record */ ? dataFields[0] : null;
            this.metaDataFields = _.difference(dataFields, [this.isMetadataModelFor]);
            this.mappingRecordClass = this.isMetadataModelFor ? this.recordClass.getField(this.isMetadataModelFor).getRecordClass() : null;
        }

        const { reactive } = window.vue
        this.vueEventBus = window.mitt()
        this.injectKey = 'injectKey'+this.id
        this.props = reactive({
            injectKey: this.injectKey,
            records: this.records,
            emptyText: this.emptyText
        })
        Tine.Tinebase.widgets.form.VMultiPicker.superclass.initComponent.call(this)
    },

    onRender: function(ct, position){
        Tine.Tinebase.widgets.form.VMultiPicker.superclass.onRender.apply(this, arguments)
        this.vueEventBus.on('onTriggerClick', this.onTriggerClick.bind(this))
        this.vueEventBus.on('removeRecord', this.removeRecordById.bind(this))
        this.renderUI()
    },

    renderUI: function() {
        // if(!Object.keys(this.props.records).length) {
        if(this.props.records === null || this.props.records.size === 0) {
            this.initCombo(false)
            this.vueHandle?.unmount()
            this.vueHandle = null
            this.pickerCombo.render(this.el)
        } else {
            this.initCombo(true)
            if (!this.vueHandle){
                this.createVueApp()
                this.vueHandle.mount(this.el.dom)
            }
        }
    },

    createVueApp: function (){
        const {createApp, h} = window.vue
        this.vueHandle = createApp({
            render: () => h(RecordsDisplayContainer, this.props)
        })
        this.vueHandle.provide(this.injectKey, this.vueEventBus)
        this.vueHandle.use(BootstrapVueNext)
    },

    initCombo: function(listMode= false){
        // if the current mode of pickerCombo is same as arg(listMode), there is no need to reinit the combo
        // if (this.listMode === listMode ) return
        this.pickerCombo?.destroy()
        const recordClass = this.mappingRecordClass || this.recordClass;

        const  {allowMultiple, renderTo, filter, listeners, value, ... searchComboConfig} = this.initialConfig;
        if(searchComboConfig.xtype === 'tinerecordspickercombobox'){
            // prevent recursive recordsPicker
            delete searchComboConfig.xtype;
        }
        this.searchComboConfig = this.searchComboConfig || {};
        Object.assign(this.searchComboConfig, searchComboConfig);

        this.pickerCombo = Tine.widgets.form.RecordPickerManager.get(
            recordClass.getMeta('appName'),
            recordClass.getMeta('modelName'),
            Object.assign({
                editDialogConfig: this.editDialogConfig,
                // isMetadataModelFor: this.isMetadataModelFor,
                // requiredGrant: this.requiredGrant,
                listMode
            }, this.searchComboConfig)
        )
        if (listMode) this.pickerCombo.listAlignEl = this.el
        this.pickerCombo.on('select', this.onSelect, this)
        // this.listMode = listMode
    },

    onSelect: function(combo, record){
        if ( record === "" ) return

        if (this.isMetadataModelFor) {
            if (this.props.records && _.find(Array.from(this.props.records.values()),
                        r => record.id === r.get(this.isMetadataModelFor).id )) return // no duplicates
            // if (this.props.records && _.find(this.props.records,
            //     r => record.id === r.get(this.isMetadataModelFor).id )) return // no duplicates
            const recordData = this.getRecordDefaults()
            recordData[this.isMetadataModelFor] = record.getData()
            record = Tine.Tinebase.data.Record.setFromJson(recordData, this.recordClass)
            record.phantom = true
        }

        this.addRecord(record)
    },

    getRecordDefaults: function() {
        const defaults = {...this.recordDefaults || {} }
        if (this.refIdField) {
            defaults[this.refIdField] = this.parentEditDialog?.record?.getId()
        }

        return defaults
    },

    addRecord: function(record) {
        if ( record === "" ) return
        const oldValue = JSON.stringify(this.getValue())
        if ( this.props ) {
            if ( this.props.records === null ) this.props.records = new Map()
            this.props.records.set(record.getId(), record)
            // if ( this.props.records === null ) this.props.records = []
            // this.props.records.push(record)
        } else {
            if (this.records === null ) this.records = new Map()
            this.records.set(record.getId(), record)
            // if (this.records === null ) this.records = []
            // this.records.push(record)
        }
        const newValue = JSON.stringify(this.getValue())
        if (newValue !== oldValue){
            this.fireEvent('change', this, newValue, oldValue)
            this.fireEvent('select', this, newValue, oldValue)
        }
        this.renderUI()
    },

    setRawValue: function(ri) {
        if (this.props) {
            this.props.records = ri
        }
    },
    /*
    * Called after the component is resized, this method is empty by default but can be implemented by any
    * subclass that needs to perform custom logic after a resize occurs.
    * @param {Number} adjWidth The box-adjusted width that was set
    * @param {Number} adjHeight The box-adjusted height that was set
    * @param {Number} rawWidth The width that was originally specified
    * @param {Number} rawHeight The height that was originally specified
    */
    onResize : function(adjWidth, adjHeight, rawWidth, rawHeight){
        this.pickerCombo.setWidth(adjWidth)
        Tine.Tinebase.widgets.form.VMultiPicker.superclass.onResize.apply(this, arguments)
    },

    searchRecordByText: Ext.emptyFn,

    removeRecordById: function(id) {
        this.props.records.delete(id)
        // _.delete(this.props.records, rec => rec.getId() === id)
        this.renderUI()
        const newValue = JSON.stringify(this.getValue())
        this.fireEvent('change', this, newValue)
        this.fireEvent('select', this, newValue)
    },

    onTriggerClick: function() {
        this.initCombo(true)
        this.pickerCombo.render(this.el)
    },

    beforeDestroy: function() {
        this.vueHandle?.unmount()
    },

    getValue: function(){
        return this.props ?
            this.props.records ?
                // this.props.records
                _.map(Array.from(this.props.records.values()), val => val.getData())
                : []
            : []
    },

    setValue: function(value, editDialog){
        _.forEach(value, (recordData) => {
            this.addRecord(Tine.Tinebase.data.Record.setFromJson(recordData, this.recordClass))
        })
    },

    /* needed for isFormField cycle */
    markInvalid: Ext.form.Field.prototype.markInvalid,
    clearInvalid: Ext.form.Field.prototype.clearInvalid,
    getMessageHandler: Ext.form.Field.prototype.getMessageHandler,
    getName: Ext.form.Field.prototype.getName,

    validate: function() { return true; },

    isValid: function() { return true },

    reset: function() {
        this.props.records = new Map()
        this.records = new Map()
    }
})

Ext.reg('tinerecordspickercombobox', Tine.Tinebase.widgets.form.VMultiPicker)

// legacy
Tine.Tinebase.widgets.form.RecordsPickerCombo = Tine.Tinebase.widgets.form.VMultiPicker;
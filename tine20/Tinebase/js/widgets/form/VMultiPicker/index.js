/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Sohan Deshar<s.deshar@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import BootstrapVueNext from 'bootstrap-vue-next'
import RecordsDisplayContainer from "./RecordsDisplayContainer.vue";
import * as async from 'async'
Ext.ns('Tine.Tinebase.widgets.form')

Tine.Tinebase.widgets.form.VMultiPicker = Ext.extend(Ext.BoxComponent, {
    vueHandle: null,
    vueEventBus: null,
    injectKey: null,
    readOnly: false,
    props: null,

    emptyText: '',

    autoHeight: true,

    isFormField: true,

    records: null,

    recordRenderer: null,

    /**
     * Number of lines the box can grow to be.
     * NOTE: if set, the container in which the Picker is rendered has to adjust it's height to show the full content.
     *
     * @type {number|null} MultiLine
     */
    multiLine: null,

    initComponent: function() {
        this.emptyText = this.emptyText || (this.readOnly || this.disabled ? '' : i18n._('Search for records...'));
        this.recordClass = Tine.Tinebase.data.RecordMgr.get(this.recordClass);

        // Autodetect if our record has additional metadata for the refId Record or is only a cross table
        if (this.refIdField) {
            const dataFields = _.difference(this.recordClass.getDataFields(), [this.refIdField]);

            this.isMetadataModelFor = this.isMetadataModelFor || (dataFields.length === 1 /* precisely this is a cross-record */ ? dataFields[0] : null);
            this.metaDataFields = _.difference(dataFields, [this.isMetadataModelFor]);
            this.mappingRecordClass = this.isMetadataModelFor ? this.recordClass.getField(this.isMetadataModelFor).getRecordClass() : null;
            if (! this.recordRenderer) {
                if(this.isMetadataModelFor) {
                    if (Tine.widgets.grid.RendererManager.has(this.mappingRecordClass.getMeta('appName'), this.mappingRecordClass.getMeta('modelName'), this.isMetadataModelFor)) {
                        this.recordRenderer = Tine.widgets.grid.RendererManager.get(this.mappingRecordClass.getMeta('appName'), this.mappingRecordClass.getMeta('modelName'), this.isMetadataModelFor)
                    } else if (String(this.recordClass.getMeta('titleProperty')).match(/[{ ]/)) {
                        // record.getTitle (see RecordTag)
                    } else {
                        this.recordRenderer = mr => {
                            const data = mr.get(this.isMetadataModelFor)
                            const record = data.get ? data.get : Tine.Tinebase.data.Record.setFromJson(data, this.mappingRecordClass)
                            return record.getTitle()
                        }
                    }
                }
            }

        }

        const { reactive } = window.vue
        this.vueEventBus = window.mitt()
        this.injectKey = 'injectKey'+this.id
        this.props = reactive({
            injectKey: this.injectKey,
            records: this.records,
            emptyText: this.emptyText,
            recordRenderer: this.recordRenderer,
            multiLine: this.multiLine,
            readOnly: this.readOnly,
        })
        if (_.isArray(this.value)) {
            this.setValue(this.value)
        }
        Tine.Tinebase.widgets.form.VMultiPicker.superclass.initComponent.call(this)
    },

    onRender: function(ct, position){
        Tine.Tinebase.widgets.form.VMultiPicker.superclass.onRender.apply(this, arguments)
        this.vueEventBus.on('onTriggerClick', this.onTriggerClick.bind(this))
        this.vueEventBus.on('removeRecord', this.removeRecordById.bind(this))
        this.vueEventBus.on('pickerResize', this.handleResize.bind(this))
        this.renderUI()
    },

    handleResize: function(e) {
        // TODO
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
            if (!this.vueHandle && this.el && this.el.dom){
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

        let  {allowMultiple, renderTo, filter, listeners, value, recordClass, ... searchComboConfig} = this.initialConfig;
        recordClass = this.mappingRecordClass || this.recordClass;

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

    addRecord: async function(record) {
        // NOTE: getValue get's overwritten e.g. in filterToolbar
        const getValue = _.bind(Tine.Tinebase.widgets.form.VMultiPicker.prototype.getValue, this)

        if ( record === "" ) return
        const oldValue = JSON.stringify(getValue())

        let records;
        if ( this.props ) {
            if ( this.props.records === null ) this.props.records = new Map()
            records = this.props.records
        } else {
            if (this.records === null ) this.records = new Map()
            records = this.records
        }
        if (records.get(record.getId())) return;

        // @TODO beforeselect with an other vue VM in it crashes this picker
        this.fireAsyncEvent('beforeselect', this, record).then(() => {
            records.set(record.getId(), record)

            this.value = getValue()
            const newValue = JSON.stringify(this.value)
            if (newValue !== oldValue) {
                // NOTE: with stringify and parse we get rid of the proxies
                this.fireEvent('select', this, record)
                this.fireEvent('change', this, Tine.Tinebase.common.assertComparable(JSON.parse(newValue)), Tine.Tinebase.common.assertComparable(JSON.parse(oldValue)))
            }
            // @TODO: don't rerender / reinit all stuff here?!
            this.renderUI()
        }).catch(e => {});
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
        // NOTE: getValue get's overwritten e.g. in filterToolbar
        const getValue = _.bind(Tine.Tinebase.widgets.form.VMultiPicker.prototype.getValue, this)

        this.props.records.delete(id)
        // _.delete(this.props.records, rec => rec.getId() === id)
        this.renderUI()
        this.value = getValue()
        const newValue = JSON.stringify(this.value)
        this.fireEvent('change', this, Tine.Tinebase.common.assertComparable(JSON.parse(newValue)))
        this.fireEvent('select', this, Tine.Tinebase.common.assertComparable(JSON.parse(newValue)))
    },

    onTriggerClick: function() {
        this.initCombo(true)
        this.pickerCombo.render(this.el)
    },

    beforeDestroy: function() {
        this.vueHandle?.unmount()
    },

    /**
     * returns recordData of selected Records
     *
     * @NOTE single record pickers just return recordId with getValue and have selectedRecord property to get the whole record
     *       we might want to adopt dataflow here?
     *
     * @returns {Array<Object>|*[]}
     */
    getValue: function(){
        return Tine.Tinebase.common.assertComparable(this.props ?
            this.props.records ?
                // this.props.records
                _.map(Array.from(this.props.records.values()), val => JSON.parse(JSON.stringify(val.getData())))
                : []
            : [])
    },

    setValue: function(value, record) {
        this.reset()
        this.suspendEvents()
        async.forEach(value, async (recordData) => {
            await this.addRecord(Tine.Tinebase.data.Record.setFromJson(recordData, this.recordClass))
        }).then (() => {
            this.resumeEvents()
        })
    },

    setReadOnly : function(readOnly){
        Ext.form.Field.prototype.setReadOnly.call(this, readOnly)
        this.props.readOnly = readOnly;
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
    },

    validateBlur: function(e) {
        return !this.el.contains(e.target) && this.pickerCombo.validateBlur(e);
    },
})

Ext.reg('tinerecordspickercombobox', Tine.Tinebase.widgets.form.VMultiPicker)

// legacy
Tine.Tinebase.widgets.form.RecordsPickerCombo = Tine.Tinebase.widgets.form.VMultiPicker;
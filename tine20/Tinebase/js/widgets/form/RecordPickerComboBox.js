/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*global Ext, Tine*/

Ext.ns('Tine.Tinebase.widgets.form');

import { expandFilter } from 'util/filterSpec';
import RecordEditFieldTriggerPlugin from './RecordEditFieldTriggerPlugin';
import { getLocalizedLangPicker } from '../form/LocalizedLangPicker'

/**
 * @namespace   Tine.Tinebase.widgets.form
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @class       Tine.Tinebase.widgets.form.RecordPickerComboBox
 * @extends     Ext.form.ComboBox
 *
 * <p>Abstract base class for recordPickers like account/group pickers </p>
 *
 * Usage:
 * <pre><code>
var resourcePicker = new Tine.Tinebase.widgets.form.RecordPickerComboBox({
    recordClass: Tine.Calendar.Model.Resource
});
   </code></pre>
 */
Tine.Tinebase.widgets.form.RecordPickerComboBox = Ext.extend(Ext.ux.form.ClearableComboBox, {
    /**
     * @cfg {bool} blurOnSelect
     * blur this combo when record got selected, useful to be used in editor grids (defaults to false)
     */
    blurOnSelect: false,

    /**
     * @cfg {Tine.Tinebase.data.Record} recordClass
     * model of record to be picked (required)
     */
    recordClass: null,

    /**
     * @cfg {Tine.Tinebase.data.RecordProxy} recordProxy
     * record backend
     */
    recordProxy: null,

    /**
     * @property app
     * @type Tine.Tinebase.Application
     */
    app: null,

    /**
     * @type Tine.Tinebase.data.Record selectedRecord
     * @property selectedRecord
     * The last record which was selected
     */
    selectedRecord: null,

    /**
     * sort by field
     *
     * @type String
     */
    sortBy: null,

    /**
     * sort direction
     *
     * @type String
     */
    sortDir: 'ASC',

    /**
     * if set to false, it is not possible to add the same record handled in this.editDialog
     * this.editDialog must also be set
     *
     * @cfg {Boolean} allowLinkingItself
     */
    allowLinkingItself: null,

    /**
     * the editDialog, the form is nested in. Just needed if this.allowLinkingItself is set to false
     *
     * @type Tine.widgets.dialog.EditDialog editDialog
     */
    editDialog: null,

    /**
     * always use additional filter
     *
     * @type {Array}
     */
    additionalFilters: null,

    /**
     * config spec for additionalFilters
     *
     * @type: {object} e.g.
     * additionalFilterConfig: {config: { 'name': 'configName', 'appName': 'myApp'}}
     * additionalFilterConfig: {preference: {'appName': 'myApp', 'name': 'preferenceName}}
     * additionalFilterConfig: {favorite: {'appName': 'myApp', 'id': 'favoriteId', 'name': 'optionallyuseaname'}}
     */
    additionalFilterSpec: null,

    /**
     * in case field is a denormalizationOf an other record
     *
     * @type {Tine.Tinebase.data.Record} denormalizationRecordClass
     */
    denormalizationRecordClass: null,

    /**
     * lasy load record if id is given only
     */
    lasyLoading: true,

    /**
     * @cfg {Boolean} useEditPlugin
     */
    useEditPlugin: false,

    triggerAction: 'all',
    pageSize: 50,
    forceSelection: true,
    minListWidth: 300,

    // NOTE: minWidth gets not evaluated by ext - it's just a hint for consumers!
    minWidth: 180,

    initComponent: function () {
        // allow to initialize with string
        this.recordClass = Tine.Tinebase.data.RecordMgr.get(this.recordClass);
        this.recordProxy = this.recordProxy || new Tine.Tinebase.data.RecordProxy({
            recordClass: this.recordClass
        });

        const modelConfig = this.recordClass.getModelConfiguration();
        if (modelConfig?.denormalizationOf) {
            // denormalizationOf means we get denormalization data, but select/pick fresh records
            this.denormalizationRecordClass = this.recordClass;
            this.recordClass = Tine.Tinebase.data.RecordMgr.get(modelConfig.denormalizationOf);
            this.recordProxy =  Tine[this.recordClass.getMeta('appName')][this.recordClass.getMeta('modelName').toLowerCase() + 'Backend'];
            this.plugins = (this.plugins || []).concat(new RecordEditFieldTriggerPlugin(Ext.applyIf(this.recordEditPluginConfig || {}, {
                allowCreateNew: false,
                preserveJsonProps: 'original_id',
                qtip: window.i18n._('Edit copy'),
                editDialogConfig: {
                    mode: 'local',
                    denormalizationRecordClass: this.denormalizationRecordClass
                }
            })));
            this.useEditPlugin = false;
        }

        this.app = Tine.Tinebase.appMgr.get(this.recordClass.getMeta('appName'));
        this.displayField = this.displayField || this.recordClass.getMeta('titleProperty');
        this.valueField = this.recordClass.getMeta('idProperty');
        this.disableClearer = ! this.allowBlank;

        this.emptyText = _.isString(this.emptyText) ? this.emptyText :
            (this.readOnly || this.disabled ? '' : String.format(i18n._('Search for {0} ...'), this.recordClass.getRecordName() || _('Record')));

        this.loadingText = i18n._('Searching...');

        this.sortBy = this.sortBy || this.recordClass.getModelConfiguration()?.defaultSortInfo?.field;
        this.pageSize = parseInt(Tine.Tinebase.registry.get('preferences').get('pageSize'), 10) || this.pageSize;

        this.store = this.store || new Tine.Tinebase.data.RecordStore({
            remoteSort: true,
            readOnly: true,
            proxy: this.recordProxy || undefined,
            totalProperty: this.totalProperty,
            root: this.root,
            recordClass: this.recordClass
        });

        this.on('beforequery', this.onBeforeQuery, this);
        this.initTemplate();

        this.additionalFilters = expandFilter(this.additionalFilterSpec, this.additionalFilters);

        this.plugins = this.plugins || [];
        if (this.useEditPlugin) {
            this.plugins.push(new RecordEditFieldTriggerPlugin(Ext.applyIf(this.recordEditPluginConfig || {}, {
                allowCreateNew: !(this.additionalFilterSpec || this.additionalFilters?.length >0)
            })));
        }

        Tine.Tinebase.widgets.form.RecordPickerComboBox.superclass.initComponent.call(this);
    },

    initList() {
        Tine.Tinebase.widgets.form.RecordPickerComboBox.superclass.initList.apply(this, arguments);
        this.ownLangPicker = getLocalizedLangPicker(this.recordClass);
        if (this.ownLangPicker && this.pageTb) {
            if (this.localizedLangPicker) {
                this.ownLangPicker.setValue(this.localizedLangPicker.getValue())
                this.localizedLangPicker.on('change', (picker, lang) => {
                    this.lastQuery = null
                    this.ownLangPicker.setValue(lang)
                })
            }

            this.ownLangPicker.on('select', (picker, lang) => {
                this.pageTb.doRefresh()
                this.hasFocus = true
                this.expand()
            })
            this.pageTb.insert(10, this.ownLangPicker);
            this.pageTb.doLayout();
        }
    },

    /**
     * respect record.getTitle method
     */
    initTemplate: function() {
        if (! this.tpl) {
            this.tpl = new Ext.XTemplate('<tpl for="."><div class="x-combo-list-item" {[this.getQtip(values.' + this.recordClass.getMeta('idProperty') + ')]}>{[this.getTitle(values.' + this.recordClass.getMeta('idProperty') + ')]}</div></tpl>', {
                getTitle: (function(id) {
                    const record = this.getStore().getById(id)

                    const options = {}
                    if (this.ownLangPicker) {
                        options.language = this.ownLangPicker.getValue()
                    }

                    let title = record ? record.getTitle(options) : ' ';
                    title = title && this.app ? this.app.i18n._hidden(title) : title;

                    return Ext.util.Format.htmlEncode(title)
                }).createDelegate(this),
                getQtip: (function(id) {
                    const record = this.getStore().getById(id)
                    const qtipText = this.getListItemQtip(record);
                    return qtipText ? `ext:qtip="${Tine.Tinebase.common.doubleEncode(qtipText)}"` : '';
                }).createDelegate(this)
            })
        }
    },

    initValue: function() {
        Tine.Tinebase.widgets.form.RecordPickerComboBox.superclass.initValue.call(this);
        this.originalSelectedRecord = this.selectedRecord;
    },

    reset: function() {
        Tine.Tinebase.widgets.form.RecordPickerComboBox.superclass.reset.call(this);
        this.selectedRecord = this.originalSelectedRecord;
    },

    getListItemQtip(record) {
        let value = _.get(record, 'data.description', '');

        if (this.ownLangPicker) {
            const language = this.ownLangPicker.getValue();
            value = _.get(_.find(value, { language }) || _.get(value, '[0]'), 'text', '');
        }

        return value;
    },

    // TODO re-init this.list if it goes away?
    // NOTE: we sometimes lose this.list (how?). prevent error by checking existence.
    doResize: function(w){
        if(!Ext.isDefined(this.listWidth) && this.list){
            var lw = Math.max(w, this.minListWidth);
            this.list.setWidth(lw);
            this.innerList.setWidth(lw - this.list.getFrameWidth('lr'));
        }
    },

    /**
     * prepare paging and sort
     *
     * @param {Ext.data.Store} store
     * @param {Object} options
     */
    onBeforeLoad: function (store, options) {
        Tine.Tinebase.widgets.form.RecordPickerComboBox.superclass.onBeforeLoad.call(this, store, options);

        Ext.apply(options.params, {
            sort: (this.sortBy) ? this.sortBy : this.displayField,
            dir: this.sortDir
        });
    },

    /**
     * use beforequery to set query filter
     *
     * @param {Object} qevent
     */
    onBeforeQuery: function (qevent) {
        var filter = [
            {field: 'query', operator: 'contains', value: qevent.query }
        ];
        if (this.additionalFilters !== null && this.additionalFilters.length > 0) {
            for (var i = 0; i < this.additionalFilters.length; i++) {
                filter.push(this.additionalFilters[i]);
            }
        }
        if (this.ownLangPicker) {
            _.find(filter, {field: 'query'}).clientOptions = {
                language: this.ownLangPicker.getValue()
            }
        }
        this.store.baseParams.filter = filter;
        this.tpl.lastQuery = qevent.query;
    },

    /**
     * get last usesed query
     *
     * @returns {String}
     */
    getLastQuery: function() {
        return this.lastQuery;
    },

    /**
     * relay contextmenu events
     *
     * @param {Ext.Container} ct
     * @param {Number} position
     * @private
     */
    onRender : function(ct, position){
        Tine.Tinebase.widgets.form.RecordPickerComboBox.superclass.onRender.call(this, ct, position);

        var c = this.getEl();

        this.mon(c, {
            scope: this,
            contextmenu: Ext.emptyFn
        });

        this.relayEvents(c, ['contextmenu']);

        this.localizedLangPicker = this.localizedLangPicker || this.findParentBy((c) => {return c.localizedLangPicker})?.localizedLangPicker
    },

    /**
     * store a copy of the selected record
     *
     * @param {Tine.Tinebase.data.Record} record
     * @param {Number} index
     */
    onSelect: function (record, index) {
        this.selectedRecord = record;
        return Tine.Tinebase.widgets.form.RecordPickerComboBox.superclass.onSelect.apply(this, arguments);
    },

    /**
     * on keypressed("enter") event to add record
     *
     * @param {Tine.Addressbook.SearchCombo} combo
     * @param {Event} event
     */
    onSpecialkey: function (combo, event) {
        if (event.getKey() === event.ENTER) {
            var id = combo.getValue();
            var record = this.store.getById(id);
            this.onSelect(record);
        }
    },

    /**
     * set value and prefill store if needed
     *
     * @param {mixed} value
     */
    setValue: function (value) {
        if (value) {

            if (Ext.isObject(value) && typeof(value.get) !== 'function') {
                // value is record data
                value = this.recordProxy ? this.recordProxy.recordReader({responseText: Ext.encode(value)}) : new this.recordClass(value)
            }

            if (typeof(value.get) === 'function') {
                // value is a record
                const existingRecord = this.store.getById(value.get(this.recordClass.getMeta('idProperty')))
                if (existingRecord) {
                    this.store.remove(existingRecord);
                }
                this.store.addSorted(value);

                value = value.get(this.valueField);
            } else if (Ext.isPrimitive(value) && value == this.getValue()) {
                // value is the current id
                return this.setValue(this.selectedRecord);
            } else if (value && Ext.isString(value) && !value.match(/^{/) && !value.match(/^current/) && !value.match(/\s/) && !this.store.getById(value) && this.lasyLoading && this.selectedRecord?.getTitle?.() !== value) {
                // value is an id
                try {
                    this.recordProxy.promiseLoadRecord(value).then(record => {
                        this.suspendEvents();
                        this.setValue(record);
                        this.resumeEvents();
                    }).catch()
                } catch (e) {/* do nothing */}
            }
        }

        var r = (value !== "") ? this.findRecord(this.valueField, /* id = */ value) : null,
            text = value,
            description = '';

        if (r){
            text = (typeof r.getComboBoxTitle === "function") ? r.getComboBoxTitle() : r.getTitle({
                language: this.localizedLangPicker?.getValue()
            });
            description = r.get('description') || description;
            this.selectedRecord = r;
            if (this.allowLinkingItself === false) {
                // check if editDialog exists
                if (this.editDialog && this.editDialog.record && r.getId() == this.editDialog.record.getId()) {
                    Ext.MessageBox.show({
                        title: i18n._('Failure'),
                        msg: i18n._('You tried to link a record with itself. This is not allowed!'),
                        buttons: Ext.MessageBox.OK,
                        icon: Ext.MessageBox.ERROR
                    });
                    return;
                }
            }

        } else if (Ext.isDefined(this.valueNotFoundText)){
            text = this.valueNotFoundText;
        }
        this.lastSelectionText = text;
        if (this.hiddenField){
            this.hiddenField.value = Ext.value(value, '');
        }

        const setValue = _.bind(Tine.Tinebase.widgets.form.RecordPickerComboBox.superclass.setValue, this);
        if (text && text.registerReplacer) {
            text.registerReplacer((text) => {
                // check if value is still valid
                if (value === this.value) {
                    setValue(text);
                    this.value = value;
                }
            });
        } else {
            setValue(text);
        }

        var el = this.getEl();
        if (el) {
            el.set({qtip: Tine.Tinebase.common.doubleEncode(description)});
        }

        this.value = value;
        return this;
    },

    clearValue: function () {
        Tine.Tinebase.widgets.form.RecordPickerComboBox.superclass.clearValue.apply(this, arguments);
        this.selectedRecord = null;
    },

    getValue: function() {
        let value = Tine.Tinebase.widgets.form.RecordPickerComboBox.superclass.getValue.apply(this, arguments);

        if (this.denormalizationRecordClass && this.selectedRecord) {
            // NOTE: denormalized records are depended records, so we need to send all data (or empty string to delete)
            value = { ...this.selectedRecord.data };
            value.original_id = this.selectedRecord.json.original_id || value[this.valueField];
        }

        if (this.inEditor && this.selectedRecord) {
            // NOTE: in editorGrids we need the data to show render the title
            value = { ...this.selectedRecord.data };
        }

        return Tine.Tinebase.common.assertComparable(value);
    },
});
Ext.reg('tinerecordpickercombobox', Tine.Tinebase.widgets.form.RecordPickerComboBox);

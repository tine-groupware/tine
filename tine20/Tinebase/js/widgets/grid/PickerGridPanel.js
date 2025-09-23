/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

require('../../../css/widgets/PickerGridPanel.css');
const { getLocalizedLangPicker } = require("../form/LocalizedLangPicker");

Ext.ns('Tine.widgets.grid');

/**
 * Picker GridPanel
 *
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.PickerGridPanel
 * @extends     Ext.grid.GridPanel
 *
 * <p>Picker GridPanel</p>
 * <p><pre>
 * </pre></p>
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.widgets.grid.PickerGridPanel
 */
Tine.widgets.grid.PickerGridPanel = Ext.extend(Ext.grid.EditorGridPanel, {
    /**
     * @cfg {Class} editDialogClass (optional)
     * editDialogClass having a static openWindow method
     */
    editDialogClass: null,

    /**
     * @cfg {Object} editDialogConfig (optional)
     */
    editDialogConfig: null,

    /**
     * @cfg {bool}
     * enable bottom toolbar
     */
    enableBbar: true,

    /**
     * @cfg {bool}
     * enable top toolbar (with search combo)
     */
    enableTbar: null,

    /**
     * store to hold records
     *
     * @type Ext.data.Store
     * @property store
     */
    store: null,

    /**
     * record class
     * @cfg {Tine.Tinebase.data.Record} recordClass
     */
    recordClass: null,

    /**
     * defaults for new records of this.recordClass
     * @cfg {Object} recordClass
     */
    recordDefaults: null,

    /**
     * refIdField for new dependend records
     * @cfg {String} refIdField
     */
    refIdField: null,

    /**
     * record class
     * @cfg {Tine.Tinebase.data.Record} recordClass
     */
    searchRecordClass: null,

    isMetadataModelFor: null,

    /**
     * allow editing of metadataFor Record (cell dbl click in grid)
     * @cfg {Boolean} allowMetadataForEditing
     */
    allowMetadataForEditing: true,

    metaDataFields: null,
    
    /**
     * search combo config
     * @cfg {} searchComboConfig
     */
    searchComboConfig: null,

    /**
     * is the row selected after adding?
     * @type Boolean
     */
    selectRowAfterAdd: true,

    /**
     * is the row highlighted after adding?
     * @type Boolean
     */
    highlightRowAfterAdd: false,

    /**
     * @type Ext.Menu
     * @property contextMenu
     */
    contextMenu: null,

    /**
     * @cfg {Array} contextMenuItems
     * additional items for contextMenu
     */
    contextMenuItems: null,

    /**
     * @cfg {Array} Array of column's config objects where the config options are in
     */
    configColumns: null,

    /**
     * @cfg {Bool} readOnly
     */
    readOnly: false,

    /**
     * @cfg {Bool} allowCopy
     */
    allowCopy: false,

    /**
     * @cfg {Bool} allowCreateNew
     * allow to create new records (local mode only atm.!)
     */
    allowCreateNew: false,

    /**
     * @cfg {Bool} allowDelete
     * allow to delete records
     */
    allowDelete: true,

    /**
     * @cfg {Bool} Duplicate
     * allow to select same record multiple times
     */
    allowDuplicatePicks: false,

    /**
     * config spec for additionalFilters - passed to RecordPicker
     *
     * @type: {object} e.g.
     * additionalFilterConfig: {config: { 'name': 'configName', 'appName': 'myApp'}}
     * additionalFilterConfig: {preference: {'appName': 'myApp', 'name': 'preferenceName}}
     * additionalFilterConfig: {favorite: {'appName': 'myApp', 'id': 'favoriteId', 'name': 'optionallyuseaname'}}
     */
    additionalFilterSpec: null,

    parentEditDialog: null,

    itemRegistryCmpKey: 'PickerGrid',

    cls: 'x-wdgt-pickergrid',
    /**
     * @private
     */
    initComponent: function() {

        if (this.disabled) {
            this.disabled = false;
            this.readOnly = true;
        }

        this.contextMenuItems = (this.contextMenuItems !== null) ? this.contextMenuItems : [];
        this.configColumns = (this.configColumns !== null) ? this.configColumns : [];
        this.searchComboConfig = this.searchComboConfig || {};
        this.searchComboConfig.additionalFilterSpec = this.additionalFilterSpec;

        this.recordClass = _.isString(this.recordClass) ? Tine.Tinebase.data.RecordMgr.get(this.recordClass) : this.recordClass;
        this.labelField = this.labelField ? this.labelField : (this.recordClass && this.recordClass.getMeta ? this.recordClass.getMeta('titleProperty') : null);
        this.recordName = this.recordName ? this.recordName : (this.recordClass && this.recordClass.getRecordName ? this.recordClass.getRecordName() || i18n._('Record') : i18n._('Record'));

        if (String(this.labelField).match(/{/)) {
            this.labelField = this.labelField.match(/(?:{{\s*)(\w+)/)[1];
        }
        this.autoExpandColumn = this.autoExpandColumn? this.autoExpandColumn : this.labelField;

        const modelConf = (this.recordClass.getModelConfiguration ? this.recordClass.getModelConfiguration() : {}) || {};

        // Autodetect if our record has additional metadata for the refId Record or is only a cross table
        if (this.refIdField) {
            const dataFields = _.difference(this.recordClass.getDataFields(), [this.refIdField]);

            this.isMetadataModelFor = this.isMetadataModelFor || dataFields.length === 1 && this.recordClass.getModelConfiguration().fields[dataFields[0]].type === 'record' /* precisely this is a cross-record */ ? dataFields[0] : null;
            this.metaDataFields = _.difference(dataFields, [this.isMetadataModelFor]);
            this.columns = this.columns || (this.isMetadataModelFor ? [this.isMetadataModelFor].concat(this.metaDataFields) : null);
        }

        this.on('afterrender', this.onAfterRender, this);
        this.initComponentMixin();
        Tine.widgets.grid.PickerGridPanel.superclass.initComponent.call(this);
    },

    // NOTE: shared with Tine.widgets.grid.QuickaddGridPanel
    initComponentMixin: function () {
        this.initStore();
        this.initGrid();
        this.initActionsAndToolbars();


        this.on('celldblclick', this.onRowDblClick, this);

        if (! this.editDialogConfig?.mode) {
            this.editDialogConfig = this.editDialogConfig || {};
            const modelConfig = _.isFunction(this.recordClass?.getModelConfiguration) ? this.recordClass.getModelConfiguration() : null;
            const refConfig = _.get(modelConfig, `fields.${this.refIdField}.config`, {});
            const foreignFieldDefinition = _.get(Tine.Tinebase.data.RecordMgr.get(refConfig.appName, refConfig.modelName)?.getModelConfiguration(), `fields.${refConfig.foreignField}`, {});
            const dependentRecords = _.get(foreignFieldDefinition, `config.dependentRecords`, false);
            const isJSONStorage = _.toUpper(_.get(foreignFieldDefinition, `config.storage`, '')) === 'JSON';
            const hasNoAPI = _.isFunction(this.recordClass.getMeta) && !_.get(Tine, `${this.recordClass.getMeta('appName')}.search${_.upperFirst(this.recordClass.getMeta('modelName'))}s`)

            this.editDialogConfig.mode = this.editDialogConfig.mode || (hasNoAPI || isJSONStorage || modelConfig?.isDependent || dependentRecords ? 'local' : 'remote');
        }
    },

    onAfterRender: function() {
        this.parentEditDialog = this.parentEditDialog || this.findParentBy(function (c) {
            return c instanceof Tine.widgets.dialog.EditDialog
        });

        this.setReadOnly(this.readOnly);
    },

    setReadOnly: function(readOnly) {
        this.readOnly = readOnly;
        var _ = window.lodash;

        var tbar = this.getTopToolbar();
        if (tbar) {
            this.getTopToolbar().items.each(function (item) {
                if (Ext.isFunction(item.setDisabled)) {
                    item.setDisabled(readOnly);
                } else if (item) {
                    item.disabled = readOnly;
                }
            }, this);
            tbar[readOnly ? 'hide' : 'show']();
        }
        var bbar = this.getBottomToolbar();
        if (bbar) {
            bbar[readOnly ? 'hide' : 'show']();
        }
        if (_.get(this, 'actionRemove.setDisabled')) {
            this.actionRemove.setDisabled(readOnly);
        }
        // pickerCombos doesn´t show
        this.doLayout();
    },

    onBeforeEdit: function(o) {
        if (this.isMetadataModelFor && this.isMetadataModelFor === o.field) {
            o.cancel = true;
        }
        return Tine.widgets.grid.PickerGridPanel.superclass.onBeforeEdit.apply(this, arguments);
    },

    /**
     * init store
     * @private
     */
    initStore: function() {

        if (!this.store) {
            this.store = new Ext.data.JsonStore({
                sortInfo: this.defaultSortInfo || {
                    field: this.labelField,
                    direction: 'DESC'
                },
                fields: this.recordClass
            });
        }

        // focus+select new record
        this.store.on('add', this.focusAndSelect, this);
        this.store.on('beforeload', this.showLoadMask, this);
        this.store.on('load', this.hideLoadMask, this);

        this.store.on('add', this.onStoreChange, this);
        this.store.on('update', this.onStoreChange, this);
        this.store.on('remove', this.onStoreChange, this);
    },

    focusAndSelect: function(store, records, index) {
        (function() {
            if (this.rendered) {
                if (this.selectRowAfterAdd) {
                    this.getView().focusRow(index);
                    this.getSelectionModel().selectRow(index);
                } else if (this.highlightRowAfterAdd && records.length === 1){
                    // some eyecandy
                    var row = this.getView().getRow(index);
                    Ext.fly(row).highlight();
                }
            }
        }).defer(300, this);
    },

    /**
     * init actions and toolbars
     */
    initActionsAndToolbars: function() {
        const hasEditDialog = this.editDialogClass || Tine.widgets.dialog.EditDialog.getConstructor(this.recordClass);
        const useEditDialog = !this.isMetadataModelFor || !this.metaDataFields || _.isArray(this.metaDataFields) && this.metaDataFields.length;

        this.actionCreate = new Ext.Action({
            text: String.format(i18n._('Create {0}'), this.recordName),
            hidden: !hasEditDialog || !this.allowCreateNew || !useEditDialog,
            handler: this.onCreate.bind(this, []),
            iconCls: 'action_add'
        });

        this.actionEdit = new Ext.Action({
            text: String.format(i18n._('Edit {0}'), this.recordName),
            hidden: !hasEditDialog || !useEditDialog,
            scope: this,
            disabled: true,
            actionUpdater: function(action, grants, records) {
                action.setDisabled(!records.length);
            },
            handler: () => {
                const record = this.selModel.getSelected();
                const row = this.store.indexOf(record);
                this.onRowDblClick(this, row, null);
            },
            iconCls: 'action_edit'
        });

        this.actionRemove = this.deleteAction = new Ext.Action({
            text: String.format(i18n._('Remove {0}'), this.recordName),
            hidden: this.hasOwnProperty('allowDelete') && !this.allowDelete,
            disabled: true,
            scope: this,
            handler: this.onRemove,
            iconCls: 'action_delete',
            actionUpdater: this.actionRemoveUpdater
        });

        this.actionCopy = new Ext.Action({
            text: String.format(i18n._('Copy {0}'), this.recordName),
            hidden: !hasEditDialog || !useEditDialog || !this.allowCopy,
            scope: this,
            disabled: true,
            actionUpdater: function(action, grants, records) {
                action.setDisabled(!records.length || records.length > 1);
            },
            handler: () => {
                const record = this.selModel.getSelected();
                const row = this.store.indexOf(record);
                this.editDialogConfig.copyRecord = true;
                this.onRowDblClick(this, row, null);
                delete this.editDialogConfig.copyRecord;
            },
            iconCls: 'action_editcopy'
        });

        // init actions
        this.actionUpdater = new Tine.widgets.ActionUpdater({
            recordClass: this.recordClass,
            evalGrants: this.evalGrants
        });
        this.actionUpdater.addActions([
            this.actionCreate,
            this.actionEdit,
            this.actionRemove,
            this.actionCopy
        ]);

        this.selModel.on('selectionchange', function(sm) {
            this.actionUpdater.updateActions(sm);
        }, this);

        var contextItems = [this.actionCreate, this.actionCopy, this.actionEdit, this.actionRemove];
        this.contextMenu = new Ext.menu.Menu({
            plugins: _.concat([{
                ptype: 'ux.itemregistry',
                key:   'Tinebase-MainContextMenu'
            }], this.recordClass?.getMeta ? {
                ptype: 'ux.itemregistry',
                key:   `${this.recordClass.getMeta('appName')}-${this.recordClass.getMeta('recordName')}-${this.itemRegistryCmpKey}-ContextMenu`
            } : []),
            items: contextItems.concat(this.contextMenuItems || [])
        });
        this.actionUpdater.addActions(this.contextMenu.items);

        // removes temporarily added items
        this.contextMenu.on('hide', function() {
            if(this.contextMenu.hasOwnProperty('tempItems') && this.contextMenu.tempItems.length) {
                Ext.each(this.contextMenu.tempItems, function(item) {
                    this.contextMenu.remove(item.itemId);
                }, this);
            }
            this.contextMenu.tempItems = [];
        }, this);

        if (this.enableBbar) {
            this.bbar = new Ext.Toolbar({
                plugins: _.concat([], this.recordClass?.getMeta ? {
                    ptype: 'ux.itemregistry',
                    key:   `${this.recordClass.getMeta('appName')}-${this.recordClass.getMeta('recordName')}-${this.itemRegistryCmpKey}-Bbar`
                } : []),
                items: [
                    this.actionCreate,
                    this.actionEdit,
                    this.actionRemove
                ].concat(this.contextMenuItems || [])
            });
            this.actionUpdater.addActions(this.bbar.items);

            if (_.isFunction(this.recordClass?.getModelConfiguration)) {
                this.localizedLangPicker = getLocalizedLangPicker(this.recordClass)
                if (this.localizedLangPicker) {
                    this.store.localizedLang = this.localizedLangPicker.getValue()
                    this.localizedLangPicker.on('change', (picker, lang) => {
                        this.store.localizedLang = lang
                        this.getView().refresh()
                    })
                    this.bbar.add('->', this.localizedLangPicker);
                }
            }
        }

        this.enableTbar = _.isBoolean(this.enableTbar) ? this.enableTbar : (!this.refIdField || this.isMetadataModelFor);

        if (this.enableTbar) {
            this.initTbar();
        }
    },

    actionRemoveUpdater: function(action, grants, records) {
        action.setDisabled(this.readOnly || !records.length);
    },

    /**
     * init top toolbar
     */
    initTbar: function() {
        this.tbar = new Ext.Toolbar({
            items: [
                this.getSearchCombo()
            ],
            listeners: {
                scope: this,
                resize: this.onTbarResize
            }
        });
    },

    onTbarResize: function(tbar) {
        if (tbar.items.getCount() == 1) {
            var combo = tbar.items.get(0),
                gridWidth = this.getGridEl().getWidth(),
                offsetWidth = combo.getEl() ? combo.getEl().getLeft() - this.getGridEl().getLeft() : 0;

            if (tbar.items.getCount() == 1) {
                tbar.items.get(0).setWidth(gridWidth - offsetWidth);
            }
        }
    },

    /**
     * init grid (column/selection model, ctx menu, ...)
     */
    initGrid: function() {
        this.colModel = this.getColumnModel();

        this.selModel = new Ext.grid.RowSelectionModel({multiSelect:true});

        // remove non-plugin config columns
        var nonPluginColumns = [];
        for (var i=0; i < this.configColumns.length; i++) {
            if (!this.configColumns[i].init || typeof(this.configColumns[i].init) != 'function') {
                nonPluginColumns.push(this.configColumns[i]);
            }
        }
        for (var i=0; i < nonPluginColumns.length; i++) {
            this.configColumns.remove(nonPluginColumns[i]);
        }
        this.plugins = (this.plugins || []).concat(this.configColumns);
        this.plugins.push(new Ext.ux.grid.GridViewMenuPlugin({}))

        // // on selectionchange handler
        // this.selModel.on('selectionchange', function(sm) {
        //     var rowCount = sm.getCount();
        //     this.actionRemove.setDisabled(this.readOnly || rowCount == 0);
        // }, this);

        // on rowcontextmenu handler
        this.on('rowcontextmenu', this.onRowContextMenu.createDelegate(this), this);
    },

    /**
     * take columns property if defined, otherwise create columns from record class propery
     * @return {}
     */
    getColumnModel: function() {
        var _ = window.lodash,
            me = this;

        if (! this.colModel) {
            if (!this.columns) {
                var labelColumn = {
                    id: this.labelField,
                    header: this.recordClass.getRecordsName(),
                    dataIndex: this.labelField,
                    renderer: this.labelRenderer ? this.labelRenderer : function(v,m,r) { return Ext.util.Format.htmlEncode(Ext.isFunction(r.getTitle) ? r.getTitle() : v)}
                };

                this.columns = [labelColumn];
            } else {
                // convert string cols
                const fieldManager = _.bind(Tine.widgets.form.FieldManager.get,
                    Tine.widgets.form.FieldManager, me.recordClass.getMeta('appName'), me.recordClass.getMeta('modelName'), _,
                    Tine.widgets.form.FieldManager.CATEGORY_PROPERTYGRID);

                _.each(me.columns, function(col, idx) {
                    if (_.isString(col)) {
                        var config = Tine.widgets.grid.ColumnManager.get(me.recordClass.getMeta('appName'), me.recordClass.getMeta('modelName'), col, 'editDialog');
                        if (config) {
                            me.columns[idx] = config;
                            config.editor = fieldManager(col);
                        }
                    }
                });
                _.remove(me.columns, _.isString)
            }

            this.colModel = new Ext.grid.ColumnModel({
                defaults: {
                    sortable: false
                },
                columns: this.columns
            });
        }

        this.hideHeaders = this.hasOwnProperty('hideHeaders') ? this.hideHeaders : (!this.columns || this.columns.length < 2);
        if (this.columns && this.autoExpandColumn && !_.find(this.columns, {dataIndex: this.autoExpandColumn})) {
            this.autoExpandColumn = this.columns[0]?.dataIndex;
        }
        return this.colModel;
    },

    /**
     * that's the context menu handler
     * @param {} grid
     * @param {} row
     * @param {} e
     */
    onRowContextMenu: function(grid, row, e) {
        e.stopEvent();

        if (this.fireEvent('beforecontextmenu', grid, row, e) === false) return;

        var selModel = grid.getSelectionModel();
        if(!selModel.isSelected(row)) {
            selModel.selectRow(row);
        }

        this.contextMenu.showAt(e.getXY());
    },

    /**
     * @return {Tine.Tinebase.widgets.form.RecordPickerComboBox|this.searchComboClass}
     */
    getSearchCombo: function() {
        if (! this.searchCombo) {
            const searchComboConfig = {...this.searchComboConfig || {}};

            if (this.isMetadataModelFor) {
                const mappingFieldDef = this.recordClass.getField(this.isMetadataModelFor);
                if (_.get(mappingFieldDef, 'fieldDefinition.type') !== 'record') {
                    this.allowMetadataForEditing = false;
                    Object.assign(searchComboConfig, Tine.widgets.form.FieldManager.get(this.recordClass.getMeta('appName'), this.recordClass, this.isMetadataModelFor, Tine.widgets.form.FieldManager.CATEGORY_PROPERTYGRID), {
                        allowBlank: true,
                    });
                } else {
                    this.searchRecordClass = mappingFieldDef.getRecordClass();
                    Object.assign(searchComboConfig, _.get(this.searchRecordClass.getModelConfiguration?.(), 'uiconfig.searchComboConfig', {}));
                }

                Object.assign(searchComboConfig, _.get(mappingFieldDef, 'fieldDefinition.uiconfig', {}));
                searchComboConfig.useEditPlugin = searchComboConfig.hasOwnProperty('useEditPlugin') ? searchComboConfig.useEditPlugin : true;
            }

            Ext.apply(searchComboConfig, {
                lasyLoading: false,
                blurOnSelect: true,
                listeners: {
                    scope: this,
                    select: this.onAddRecordFromCombo
                }
            });

            if (searchComboConfig.xtype) {
                this.searchCombo = Ext.create(searchComboConfig);
            } else {
                const recordClass = (this.searchRecordClass !== null) ? Tine.Tinebase.data.RecordMgr.get(this.searchRecordClass) : this.recordClass;
                const appName = recordClass.getMeta('appName');
                this.searchCombo = Tine.widgets.form.RecordPickerManager.get(appName, recordClass, searchComboConfig);
            }
        }

        return this.searchCombo;
    },

    /**
     * Is called when a record gets selected in the picker combo
     *
     * @param {Ext.form.ComboBox} picker
     * @param {Record} recordToAdd
     */
    onAddRecordFromCombo: function(picker, recordToAdd) {
        // sometimes there are no record data given
        if (! recordToAdd) {
           return;
        }
        
        if (this.isMetadataModelFor) {
            var recordData = Object.assign({}, this.recordClass.getDefaultData(), this.getRecordDefaults());
            recordData[this.isMetadataModelFor] = recordToAdd.getData();
            // copy (reference data from metadata record, e.g. config class for dynamic metadata records)
            Ext.copyTo(recordData, recordData[this.isMetadataModelFor], picker.copyOnSelectProps);
            if (picker.xtype === 'tw-modelpicker') {
                recordData[this.isMetadataModelFor] = recordData[this.isMetadataModelFor].className;
            }
            if (picker.xtype === 'widget-keyfieldcombo') {
                recordData[this.isMetadataModelFor] = recordData[this.isMetadataModelFor].id;
            }
            var record =  Tine.Tinebase.data.Record.setFromJson(recordData, this.recordClass);
            record.phantom = true;

            // check if already in
            const probeMetaData = record.get(this.isMetadataModelFor);
            const existingRecord = this.store.findBy(function (r) {
                const metaData = r.get(this.isMetadataModelFor) ?? '';
                if ((metaData?.id || metaData) === (probeMetaData?.id || probeMetaData)) {
                    return true;
                }
            }, this);
            if (existingRecord === -1 || this.allowDuplicatePicks) {
                if (this.fireEvent('beforeaddrecord', record, this) !== false) {
                    this.store.add([record]);
                    this.fireEvent('add', this, [record]);
                }
            }
        } else {
            var record = new this.recordClass(Ext.applyIf(recordToAdd.data, this.getRecordDefaults()), recordToAdd.id);
            record.phantom = true;
            // check if already in
            if (! this.store.getById(record.id)) {
                if (this.fireEvent('beforeaddrecord', record, this) !== false) {
                    this.store.add([record]);
                    this.fireEvent('add', this, [record]);
                }
            }
        }

        picker.reset();
    },

    onCreate: function(recordData) {
        const record = Tine.Tinebase.data.Record.setFromJson(Object.assign(recordData || {}, this.recordClass.getDefaultData(), this.getRecordDefaults()), this.recordClass);
        record.phantom = true;
        const editDialogClass = this.editDialogClass || Tine.widgets.dialog.EditDialog.getConstructor(this.recordClass);
        const mode = this.editDialogConfig?.mode || editDialogClass.prototype.mode;

        editDialogClass.openWindow(_.assign({
            openerCt: this,
            record: Ext.encode(record.getData()),
            recordId: record.getId(),
            needsUpdateEvent: true,
            listeners: {
                scope: this,
                update: this.onEditDialogRecordUpdate
            }
        }, this.editDialogConfig || {}));
    },

    getRecordDefaults: function() {
        const defaults = {...this.recordDefaults || {} };
        if (this.refIdField) {
            defaults[this.refIdField] = this.parentEditDialog?.record?.getId();
        }

        return defaults;
    },

    /**
     * remove handler
     *
     * @param {} button
     * @param {} event
     */
    onRemove: function(button, event) {
        var selectedRows = this.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            if (this.fireEvent('beforeremoverecord', selectedRows[i]) !== false) {
                this.store.remove(selectedRows[i]);
            }
        }
        if (this.deleteOnServer === true) {
            this.recordClass.getProxy().deleteRecords(selectedRows);
        }
    },

    /**
     * key down handler
     * @private
     */
    onKeyDown: function(e){
        // no keys for quickadds etc.
        if (e.getTarget('input') || e.getTarget('textarea')) return;

        switch (e.getKey()) {
            case e.A:
                // select all records
                this.getSelectionModel().selectAll(true);
                e.preventDefault();
                break;
            case e.DELETE:
                // delete selected record(s)
                this.onRemove();
                break;
        }
    },

    showLoadMask: function() {
        var me = this;
        return me.afterIsRendered()
            .then(function() {
                if (! me.loadMask) {
                    me.loadMask = new Ext.LoadMask(me.getEl(), {msg: String.format(i18n._('Loading {0}...'), me.recordClass.getRecordsName())});
                }
                me.loadMask.show.defer(100, me.loadMask);
            });
    },

    hideLoadMask: function() {
        if (this.loadMask) {
            this.loadMask.hide.defer(100, this.loadMask);
        }
        return Promise.resolve();
    },

    setValue: function(recordsdata) {
        var me = this,
            selectRowAfterAdd = me.selectRowAfterAdd,
            highlightRowAfterAdd = me.highlightRowAfterAdd;

        me.highlightRowAfterAdd = false;
        me.selectRowAfterAdd = false;

        me.store.clearData();
        _.each(recordsdata, function(recordData) {
            var record = Tine.Tinebase.data.Record.setFromJson(recordData, me.recordClass);
            if (!recordData.hasOwnProperty(me.recordClass.getMeta('idProperty'))) {
                record.phantom = true;
            }
            me.store.addSorted(record);
        });

        (function() {
            me.highlightRowAfterAdd = highlightRowAfterAdd;
            me.selectRowAfterAdd = selectRowAfterAdd;

            me.actionUpdater.updateActions([]);
        }).defer(300, me)
    },

    getValue: function() {
        var me = this,
            data = [];

        Tine.Tinebase.common.assertComparable(data);

        me.store.each(function(record) {
            data.push(record.data);
        });

        return data;
    },

    onStoreChange: function() {
        const currentValue = this.getValue();
        this.fireEvent('change', this, currentValue, this.currentValue);
        this.currentValue = currentValue;
    },

    /* needed for isFormField cycle */
    markInvalid: Ext.form.Field.prototype.markInvalid,
    clearInvalid: Ext.form.Field.prototype.clearInvalid,
    getMessageHandler: Ext.form.Field.prototype.getMessageHandler,
    getName: Ext.form.Field.prototype.getName,
    validate: function() { return true; },

    // NOTE: shared with Tine.widgets.grid.QuickaddGridPanel
    onRowDblClick: function(grid, row, col, e) {
        var me = this,
            editDialogClass = this.editDialogClass || Tine.widgets.dialog.EditDialog.getConstructor(me.recordClass),
            record = me.store.getAt(row),
            editDialogConfig = { ... this.editDialogConfig || {} },
            updateFn = me.onEditDialogRecordUpdate;

        // in case of metadataFor / simple cross tables we might edit the metadataFor / referenced record
        if(this.allowMetadataForEditing && this.isMetadataModelFor && this.isMetadataModelFor === this.colModel.getColumnAt(col)?.dataIndex) {
            const recordClass = this.recordClass.getField(this.isMetadataModelFor).getRecordClass();
            editDialogClass = Tine.widgets.dialog.EditDialog.getConstructor(recordClass);
            record = Tine.Tinebase.data.Record.setFromJson(record.get(this.isMetadataModelFor), recordClass);
            editDialogConfig.mode = record.phantom ? 'local' : 'remote';
            updateFn = (updatedRecordData) => {
                const updatedRecord = Tine.Tinebase.data.Record.setFromJson(updatedRecordData, recordClass);
                me.store.getAt(row).set(this.isMetadataModelFor, updatedRecord.getData());
                me.store.getAt(row).commit();
            }
        }

        if (this.fireEvent('beforeeditrecord', record, this) === false) return;

        if (editDialogClass) {
            editDialogClass.openWindow(_.assign({
                openerCt: this,
                record: JSON.stringify(record.getData()),
                recordId: record.getId(),
                readOnly: this.readOnly,
                listeners: {
                    scope: me,
                    update: updateFn
                }
            }, editDialogConfig));
        }
    },
    
    // recordChange from editDialog
    onEditDialogRecordUpdate: function(updatedRecord) {
        if (!updatedRecord.data) {
            updatedRecord = Tine.Tinebase.data.Record.setFromJson(updatedRecord, this.recordClass)
        }

        var idx = this.store.indexOfId(updatedRecord.id),
            isSelected = this.getSelectionModel().isSelected(idx);

        if (idx >= 0) {
            if(this.fireEvent("beforeupdaterecord", updatedRecord, this) === false) return;
            this.getStore().removeAt(idx);
            this.getStore().insert(idx, [updatedRecord]);
        } else {
            if (this.fireEvent('beforeaddrecord', updatedRecord, this) === false) return;
            this.getStore().add(updatedRecord);
        }

        if (isSelected) {
            this.getSelectionModel().selectRow(idx, true);
        }

        this.fireEvent('update', this, updatedRecord);
    }
});

Ext.reg('wdgt.pickergrid', Tine.widgets.grid.PickerGridPanel);

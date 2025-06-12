/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.grid');

/**
 * quickadd grid panel
 *
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.QuickaddGridPanel
 * @extends     Ext.ux.grid.QuickaddGridPanel
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.widgets.grid.QuickaddGridPanel
 */
Tine.widgets.grid.QuickaddGridPanel = Ext.extend(Ext.ux.grid.QuickaddGridPanel, {

    /**
     * @cfg {Tine.Tinebase.data.Record} recordClass
     */
    recordClass: null,

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
     * editDialog which contains this gird
     * NOTE: don't confuse with editdialogC(onfig|lass) which is for editdialog of this.recordClass!!!
     * @cfg {Tine.Tinebase.widgets.Dialog.EditDialog} editDialog
     */
    editDialog: null,

    /**
     * @cfg {String} parentRecordField
     */
    parentRecordField: null,

    /**
     * @cfg {Bool} useBBar
     */
    useBBar: false,

    /**
     * @cfg {Bool} enableTbar
     */
    enableTbar: false,

    /**
     * @cfg {Bool} readOnly
     */
    readOnly: false,

    /**
     * @private
     */
    clicksToEdit:'auto',
    frame: true,
    itemRegistryCmpKey: 'QuickAddGridPanel',
    /**
     * @private
     */
    initComponent: function() {
        Ext.copyTo(Tine.widgets.grid.QuickaddGridPanel.prototype, Tine.widgets.grid.PickerGridPanel.prototype, [
            'initActionsAndToolbars',
            'onRowContextMenu',
            'onCreate',
            'getRecordDefaults',
            'onRemove',
            'actionRemoveUpdater',
            'onRowDblClick',
            'onEditDialogRecordUpdate',
            'initComponentMixin'
            // 'setReadOnly' // see setReadOnly above
        ]);

        this.recordClass = Tine.Tinebase.data.RecordMgr.get(this.recordClass);
        this.modelConfig = this.modelConfig ||
            _.get(this, 'recordClass.getModelConfiguration') ? this.recordClass.getModelConfiguration() : null;
        this.recordName = this.recordName ? this.recordName : (this.recordClass && this.recordClass.getRecordName ? this.recordClass.getRecordName() || i18n._('Record') : i18n._('Record'));
        this.labelField = this.labelField ? this.labelField : (this.recordClass && this.recordClass.getMeta ? this.recordClass.getMeta('titleProperty') : null);
        this.enableBbar = Ext.isBoolean(this.enableBbar) ? this.enableBbar : this.useBBar;

        const parent = this.findParentBy(function(c){return !!c.record})
            || this.findParentBy(function(c) {return c.editDialog});

        if (this.parentRecordField && !this.editDialog) {
            this.editDialog = _.get(parent, 'editDialog');
        }
        if (this.editDialog && this.parentRecordField) {
            this.editDialog.on('load', this.onParentRecordLoad, this);
            this.editDialog.on('recordUpdate', this.onParentRecordUpdate, this);
        }

        this.initComponentMixin();

        Tine.widgets.grid.QuickaddGridPanel.superclass.initComponent.call(this);
        this.on('rowcontextmenu', this.onRowContextMenu, this);
        this.on('newentry', this.onNewentry, this);
    },

    initStore: function() {
        if (!this.store) {
            this.store = new Ext.data.SimpleStore({
                sortInfo: this.defaultSortInfo || {
                    field: this.labelField,
                    direction: 'DESC'
                },
                fields: this.recordClass
            });
        }
    },

    setReadOnly: function(readOnly) {
        Tine.widgets.grid.PickerGridPanel.prototype.setReadOnly.apply(this, arguments);
        Tine.widgets.grid.QuickaddGridPanel.superclass.setReadOnly.apply(this, arguments);
    },

    /**
     * init grid
     */
    initGrid: function() {
        this.plugins = this.plugins || [];
        this.plugins.push(new Ext.ux.grid.GridViewMenuPlugin({}));

        this.selModel = new Ext.grid.RowSelectionModel();

        this.cm = (! this.cm) ? this.getColumnModel() : this.cm;
    },

    /**
     * get column model
     *
     * @return {Ext.grid.ColumnModel}
     */
    getColumnModel: function() {
        var _ = window.lodash,
            me = this;

        if (! this.colModel) {
            if (this.columns) {
                // convert string cols
                _.each(me.columns, function(col, idx) {
                    if (_.isString(col)) {
                        var addConfig = _.get(me, 'columnsConfig.' + col, {});
                        var config = Tine.widgets.grid.ColumnManager.get(me.recordClass.getMeta('appName'), me.recordClass.getMeta('modelName'), col, 'editDialog', addConfig);
                        // NOTE: in editor grids we need a type based certain min-width
                        if (config) {
                            me.columns[idx] = config;
                            _.each(['quickaddField', 'editor'], function(prop) {
                                config[prop] = Ext.ComponentMgr.create(Tine.widgets.form.FieldManager.getByModelConfig(
                                    me.recordClass.getMeta('appName'),
                                    me.recordClass.getMeta('modelName'),
                                    col,
                                    Tine.widgets.form.FieldManager.CATEGORY_PROPERTYGRID
                                ));
                                config.width = config.width || config[prop].minWidth ? Math.max(config.width || 0, config[prop].minWidth || 0) : config.width;
                            });
                        }
                    }
                });
                _.remove(me.columns, _.isString)
            }

            this.colModel = new Ext.grid.ColumnModel({
                defaults: {
                    sortable: false
                },
                columns: this.columns || []
            });
        }
        return this.colModel;
    },

    /**
     * new entry event -> add new record to store
     *
     * @param {Object} recordData
     * @return {Boolean}
     */
    onNewentry: function(recordData) {
        const defaultData = Ext.apply(Ext.isFunction(this.recordClass.getDefaultData) ?
                this.recordClass.getDefaultData() : {}, this.getRecordDefaults());

        defaultData.id = recordData.id ?? defaultData.id ?? Tine.Tinebase.data.Record.generateUID();
        const newRecord = Tine.Tinebase.data.Record.setFromJson(defaultData, this.recordClass);
        newRecord.phantom = true;

        _.each(recordData, function(val, key) {
            if (val) {
                // we want to see the red dirty triangles
                Tine.Tinebase.common.assertComparable(val);
                newRecord.set(key, '');
            }

            newRecord.set(key, val);
        });

        if (this.fireEvent('beforeaddrecord', newRecord, this) !== false) {
            this.store.insert(0 , [newRecord]);
        }

        return true;
    },

    /**
     * get next available id
     * @return {Number}
     */
    getNextId: function() {
        var newid = this.store.getCount() + 1;

        while (this.store.getById(newid)) {
            newid++;
        }

        return newid;
    },

    /**
     * get values from store (as array)
     *
     * @param {Array}
     */
    setStoreFromArray: function(data) {
        this.store.removeAll();
        _.each(data, (recordData) => {
            const record = Tine.Tinebase.data.Record.setFromJson(recordData, this.recordClass);
            this.store.addSorted(record);
        });

        this.actionUpdater.updateActions(this.getSelectionModel());
    },

    /**
     * get values from store (as array)
     *
     * @return {Array}
     */
    getFromStoreAsArray: function(deleteAutoIds) {
        const result = Tine.Tinebase.common.assertComparable([]);
        const data = this.store.snapshot || this.store;
        data.each(function(record) {
            var data = record.data;
            if (deleteAutoIds && String(data.id).match(/ext-gen/)) {
                delete data.id;
            }
            result.push(data);
        }, this);

        return result;
    },

    onParentRecordLoad: function(editDialog, record, ticketFn) {
        var _ = window.lodash,
            me = this,
            data = _.get(record, 'data.' + me.parentRecordField) || [],
            idProperty = me.recordClass.getMeta('idProperty'),
            copyOmitFields = _.filter(me.recordClass.getModelConfiguration().fields, {copyOmit: true});

        /* generic client driven resolve attempt
        var byType = _.groupBy(me.recordClass.getModelConfiguration().fields, 'type'),
            recordsByType = _.groupBy(_.get(byType, 'record', []), function(f) {
                return _.get(f, 'config.appName', '') + '.' + _.get(f, 'config.modelName', '')
            }),
            idMap = {};

        _.assign(byType, recordsByType);

        _.each(['user', 'Addressbook.Contact'], function(type) {
            _.each(byType[type], function(field) {
                idMap[type] = _.uniq(_.concat(_.get(idMap, type, []), _.compact(_.map(data, field.key))));
            });
        });

        // resolve all user and Addressbook.contact -> argh, there is no user API
        */

        if (me.editDialog.copyRecord) {
            _.each(data, function(recordData) {
                recordData[idProperty] = Tine.Tinebase.data.Record.generateUID();
                _.each(copyOmitFields, function (copyOmitField) {
                    delete (recordData[copyOmitField.key]);
                });
            });
        }
        me.setStoreFromArray(data);

    },

    onParentRecordUpdate: function(editDialog, record) {
        var _ = window.lodash,
            me = this,
            data = me.getFromStoreAsArray();

        record.set(me.parentRecordField, data);
    }
});

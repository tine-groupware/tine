/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.BL');

Tine.Tinebase.BL.BLConfigPanel = Ext.extend(Tine.widgets.grid.QuickaddGridPanel, {

    /**
     * @cfg {Tine.Tinebase.data.Record} owningRecordClass
     * record class with blConfig field
     */
    owningRecordClass: null,

    /**
     * @cfg {String}
     * field name in owningRecordClass where blConfig is configured/stored
     */
    owningField: 'blpipe',

    /**
     * @cfg {String}
     * path to get/put data
     */
    dataPath: 'data.blpipe',
    
    /**
     * cfg {Record} record class having a dynamicRecord field
     */
    recordClass: null,

    /**
     * @cfg {String} 
     * property holding the dynamic record
     */
    dynamicRecordField: 'configRecord',
    

    initComponent: function() {
        var _ = window.lodash,
            me = this;
        
        if (! this.owningRecordClass && this.editDialog) {
            this.owningRecordClass = this.editDialog.recordClass;
        }

        this.recordClass = this.recordClass  || Tine.Tinebase.data.RecordMgr.get(_.get(this.owningRecordClass.getField(this.owningField), 'fieldDefinition.config.recordClassName'));
        this.modelConfig = this.modelConfig || _.get(this, 'recordClass.getModelConfiguration') ? this.recordClass.getModelConfiguration() : null;
        this.classNameField = this.classNameField || _.get(this.recordClass.getField(this.dynamicRecordField), 'fieldDefinition.config.refModelField', 'classname');
        this.title = this.hasOwnProperty('title') ? this.title : this.recordClass.getRecordsName();
        this.quickaddMandatory = this.classNameField;
        this.autoExpandColumn = this.dynamicRecordField;
            

        // @TODO: move to fieldManager?
        this.BLElementConfigClassNames = _.get(this.recordClass.getField(this.classNameField), 'fieldDefinition.config.availableModels', [])
        this.BLElementPicker = new Ext.form.ComboBox({
            listWidth: 200,
            store: _.reduce(this.BLElementConfigClassNames, function(arr, classname) {
                var recordClass = Tine.Tinebase.data.RecordMgr.get(classname);
                if (recordClass) {
                    arr.push([classname, recordClass.getRecordName() /*, recordClass.getDescription()*/]);
                }
                return arr;
            }, []),
            typeAhead: true,
            triggerAction: 'all',
            emptyText: i18n._('Add new Element...'),
            expandOnFocus:true,
            blurOnSelect: true
        });

        _.assign(this, Tine.widgets.grid.GridPanel.prototype.initGenericColumnModel.call(this));
        
        this.on('beforeaddrecord', this.onBeforeAddBLElementRecord, this);

        this.supr().initComponent.call(this);
    },
    
    customizeColumns: function(columns) {
        _.each(columns, (col) => {
            col.editor = Tine.widgets.form.FieldManager.get(this.recordClass.getMeta('appName'), this.recordClass, col.dataIndex, Tine.widgets.form.FieldManager.CATEGORY_PROPERTYGRID);
        });
        _.assign(_.find(columns, {dataIndex: this.classNameField}), {
            quickaddField: this.BLElementPicker,
            editor: null // be cautious, fieldManager might return smth.
        });
    },
    
    onRender: function() {
        this.supr().onRender.apply(this, arguments);

        if (! this.editDialog) {
            this.editDialog = this.findParentBy(function (c) {
                return c instanceof Tine.widgets.dialog.EditDialog
            });
        }
        
        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('recordUpdate', this.onRecordUpdate, this);

        // NOTE: in case we are rendered after record was load
        this.onRecordLoad(this.editDialog, this.editDialog.record);
    },

    onBeforeAddBLElementRecord: function(newRecord) {
        this.openEditDialog(newRecord);

        return false;
    },

    onRowDblClick: function(grid, row, col) {
        if (! this.readOnly) {
            var configWrapper = this.store.getAt(row);

            this.openEditDialog(configWrapper);
        }
    },

    openEditDialog: function(configWrapper) {

        var recordClass = Tine.Tinebase.data.RecordMgr.get(configWrapper.get(this.classNameField)),
            editDialogClass = Tine.widgets.dialog.EditDialog.getConstructor(recordClass),
            configRecord = configWrapper.get(this.dynamicRecordField) || {};

        if (! configRecord.data) {
            configRecord = Tine.Tinebase.data.Record.setFromJson(configRecord, recordClass);
        }

        if (editDialogClass) {
            editDialogClass.openWindow(Object.assign({
                mode: 'local',
                record: Ext.encode(configRecord.data),
                recordId: configRecord.getId(),
                blConfigPanel: this,
                configWrapper: configWrapper,
                needsUpdateEvent: true,
                listeners: {
                    scope: this,
                    'update': function (updatedRecord) {
                        if (!updatedRecord.data) {
                            updatedRecord = Tine.Tinebase.data.Record.setFromJson(updatedRecord, recordClass)
                        }
                        Tine.Tinebase.common.assertComparable(updatedRecord.data);
                        configWrapper.set(this.dynamicRecordField, updatedRecord.data);

                        if (this.store.indexOf(configWrapper) < 0) {
                            this.store.add([configWrapper]);
                        }
                    }
                }
            }, this.editDialogConfig));
        }
    },

    onRecordLoad: function(editDialog, record) {
        var _ = window.lodash,
            data = _.get(record, this.dataPath) || [];

        this.setStoreFromArray(data);
    },

    onRecordUpdate: function(editDialog, record) {
        var _ = window.lodash,
            data = this.getFromStoreAsArray();

        _.set(record, this.dataPath, data)
    },

});

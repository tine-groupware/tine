/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*global Ext, Tine*/

import FieldTriggerPlugin from "../../ux/form/FieldTriggerPlugin";
import asString from "ux/asString"

Ext.ns('Tine.Tinebase.widgets.form.RecordEditField');


Tine.Tinebase.widgets.form.RecordEditField = Ext.extend(Ext.form.TriggerField, {
    /**
     * @cfg {Bool} allowDelete show delete trigger
     */
    enableDelete: false,

    /**
     * @cfg {Object} editDialogConfig (optional)
     */
    editDialogConfig: null,

    itemCls: 'tw-recordEditField',
    triggerClass: 'action_edit',
    editable: false,
    expandOnFocus: true,
    blurOnSelect: true,

    initComponent: async function () {
        var _ = window.lodash;

        this.recordClass = Tine.Tinebase.data.RecordMgr.get(this.appName, this.modelName);
        this.emptyText = i18n._('No record');
        
        this.plugins = this.plugins || [];
        if (this.enableDelete === true) {
            this.trigger_delete = new FieldTriggerPlugin({
                triggerClass: 'action_delete',
                onTriggerClick: () => {
                    this.clearValue();
                }
            });
            this.plugins.push(this.trigger_delete);
        }

        this.editDialogConfig = this.editDialogConfig || {};

        Tine.Tinebase.widgets.form.RecordEditField.superclass.initComponent.call(this);
    },

    assertRecordClass: function(owningRecord) {
        if (! owningRecord) return;

        // if the field is a dynamicRecord, get classname and adopt this.recordClass
        this.owningRecord = owningRecord;
        const owningRecordClass = _.get(this.owningRecord, 'constructor');
        const owningRecordFieldDefinitions = _.get(owningRecordClass, 'getFieldDefinitions') ? owningRecordClass.getFieldDefinitions() : null;
        const ownFieldDefinition = _.get(_.find(owningRecordFieldDefinitions, {name: this.fieldName}), 'fieldDefinition');
        const classNameField = _.get(ownFieldDefinition, 'config.refModelField');
        const className = _.get(this.owningRecord, 'data.'+classNameField)
            || _.get(ownFieldDefinition, 'config.modelName'); // not yet dynamic field :)
        const currentRecordClass = this.recordClass;
        this.recordClass = className ? Tine.Tinebase.data.RecordMgr.get(className) || this.recordClass : this.recordClass;
        if (currentRecordClass !== this.recordClass) {
            this.setValue(this.recordData, owningRecord)
        }
    },
    
    setValue : function(v, owningRecord){
        this.recordData = Tine.Tinebase.common.assertComparable(_.get(v, 'data', v));
        this.recordData = ((_.isString(this.recordData) && this.recordData === '[]') || (_.isArray(this.recordData) && this.recordData.length === 0)) ? null : this.recordData; // transform server defaults
        this.assertRecordClass(owningRecord);

        const valueRecord = this.recordClass && this.recordData ? Tine.Tinebase.data.Record.setFromJson(this.recordData, this.recordClass) : null;
        if (valueRecord && this.recordClass && !this.recordData.hasOwnProperty(this.recordClass.getMeta('idProperty'))) {
            valueRecord.phantom = true;
        }
        Promise.resolve().then(async () => {
            let text = valueRecord ? await asString(valueRecord.getTitle()) || '...' : '';
            Tine.Tinebase.widgets.form.RecordEditField.superclass.setValue.call(this, text);
        });

        if (this.trigger_delete && this.enableDelete === true ) {
            const visible = !!valueRecord;
            this.trigger_delete.setVisible(visible);
        }
    },
    
    getValue : function(){
        return this.recordData;
    },

    processValue : function(value){
        return this.getValue();
    },

    validateValue : function(value){
        const valueRecord = this.recordClass && this.recordData ? Tine.Tinebase.data.Record.setFromJson(this.recordData, this.recordClass) : null;
        const isValid = valueRecord ? valueRecord.isValid() : !!this.allowBlank;

        if (! isValid) {
            this.markInvalid(window.formatMessage('{recordName} is not valid.', {recordName: this.recordClass?.getRecordName() || window.formatMessage('Record')}));
        }
        return isValid;
    },

    /**
     * clear value
     */
    clearValue: function () {
        this.setValue('');
        
        if (this.trigger_delete && this.enableDelete === true) {
            this.trigger_delete.setVisible(false);
        }
    },

    checkState: function(editDialog, owningRecord) {
        this.assertRecordClass(owningRecord);
    },

    getRecordDefaults: () => {
        return {};
    },

    onTriggerClick: function () {
        this.assertRecordClass(this.owningRecord);
        if (! this.recordClass) {
            alert('select model');
            return;
        }
        
        let me = this;
        const editDialogClass = Tine.widgets.dialog.EditDialog.getConstructor(this.recordClass);

        if (! editDialogClass) {
            return;
        }

        if (!this.recordData) {
            const record = Tine.Tinebase.data.Record.setFromJson(Object.assign(this.recordData || {}, this.recordClass.getDefaultData(), this.getRecordDefaults()), this.recordClass);
            record.phantom = true;
            this.recordData = record.getData();
        }
        this.editDialogConfig.mode = this.editDialogConfig.mode || 'local';

        editDialogClass.openWindow(Object.assign({
            record: this.recordData,
            needsUpdateEvent: true,
            listeners: {
                scope: me,
                'update': (updatedRecord) => {
                    let record = !updatedRecord.data ? Tine.Tinebase.data.Record.setFromJson(updatedRecord, me.recordClass) : updatedRecord;
                    Tine.Tinebase.common.assertComparable(record);
                    this.setValue(record);
                    this.fireEvent('select', this, this.getValue());
                    if (this.blurOnSelect) {
                        this.fireEvent('blur', this);
                    }
                },
                'cancel': () => {
                    if (new Date().getTime() - 1000 < this.blurOnSelectLastRun) {
                        return;
                    }
                    
                    _.delay(() => {
                        this.blurOnSelectLastRun = new Date().getTime();
                        const focusClass = this.focusClass;
                        this.focusClass = '';
                        Ext.form.TriggerField.superclass.onBlur.call(this);
                        this.focusClass = focusClass;
                    }, 100);
                }
            }
        }, this.editDialogConfig));
    }
});

Ext.reg('tw-recordEditField', Tine.Tinebase.widgets.form.RecordEditField);

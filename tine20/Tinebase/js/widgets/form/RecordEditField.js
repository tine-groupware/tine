/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*global Ext, Tine*/

import FieldTriggerPlugin from "../../ux/form/FieldTriggerPlugin";

Ext.ns('Tine.Tinebase.widgets.form.RecordEditField');


Tine.Tinebase.widgets.form.RecordEditField = Ext.extend(Ext.form.TriggerField, {
    /**
     * @cfg {Bool} allowDelete show delete trigger
     */
    enableDelete: false,
    
    itemCls: 'tw-recordEditField',
    triggerClass: 'action_edit',
    editable: false,
    
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

        Tine.Tinebase.widgets.form.RecordEditField.superclass.initComponent.call(this);
    },

    assertRecordClass: function(owningRecord) {
        if (! owningRecord) return;

        // if the field is a dynamicReccord, get classname and adopt this.recordClass
        this.owningRecord = owningRecord;
        const owningRecordClass = _.get(this.owningRecord, 'constructor');
        const owningRecordFieldDefinitions = _.get(owningRecordClass, 'getFieldDefinitions') ? owningRecordClass.getFieldDefinitions() : null;
        const ownFieldDefinition = _.get(_.find(owningRecordFieldDefinitions, {name: this.fieldName}), 'fieldDefinition');
        const classNameField = _.get(ownFieldDefinition, 'config.refModelField');
        const className = _.get(this.owningRecord, 'data.'+classNameField)
            || _.get(ownFieldDefinition, 'config.modelName'); // not yet dynamic field :)
        this.recordClass = className ? Tine.Tinebase.data.RecordMgr.get(className) || this.recordClass : this.recordClass;
    },
    
    setValue : function(v, owningRecord){
        this.recordData = _.get(v, 'data', v);
        this.assertRecordClass(owningRecord);
    
        const valueRecord = this.recordClass && this.recordData ? Tine.Tinebase.data.Record.setFromJson(this.recordData, this.recordClass) : null;
        Promise.resolve().then(async () => {
            let text = valueRecord ? valueRecord.getTitle() || '...' : '';
            if (text && text.asString) {
                text = await text.asString();
            }
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
        
        editDialogClass.openWindow({
            mode: 'local',
            record: this.recordData,
            needsUpdateEvent: true,
            listeners: {
                scope: me,
                'update': (updatedRecord) => {
                    let record = !updatedRecord.data ? Tine.Tinebase.data.Record.setFromJson(updatedRecord, me.recordClass) : updatedRecord;
                    Tine.Tinebase.common.assertComparable(record);
                    this.setValue(record);
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
        });
    }
});

Ext.reg('tw-recordEditField', Tine.Tinebase.widgets.form.RecordEditField);

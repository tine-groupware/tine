/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import FieldTriggerPlugin from "../../ux/form/FieldTriggerPlugin"

class RecordEditFieldTriggerPlugin extends FieldTriggerPlugin {
    allowCreateNew = true

    /**
     * properties from record.json to preserve when editing (see Ext.copyTo for syntax)
     * @type {string}
     */
    preserveJsonProps = ''

    editDialogMode = null

    triggerClass = 'action_edit'

    constructor(config) {
        super(config)
        _.assign(this, config)
    }

    async init (field) {
        this.visible = this.allowCreateNew
        await super.init(field)
    }
    
    assertState() {
        super.assertState.call(this);
        this.setVisible((!!this.field.selectedRecord || this.allowCreateNew));
        this.setTriggerClass(!!this.field.selectedRecord ? 'action_edit' : 'action_add');

        if (this.field.readOnly || this.field.disabled) {
            this.setVisible(!!this.field.selectedRecord);
            this.setTriggerClass('action_preview');
        }
    }

    // allow to configure defaults from outside
    async getRecordDefaults() {
        return {}
    }

    async onTriggerClick () {
        // let me = this;
        let editDialogClass = Tine.widgets.dialog.EditDialog.getConstructor(this.field.recordClass);

        if (editDialogClass) {
            const record = this.field.selectedRecord || Object.assign(Tine.Tinebase.data.Record.setFromJson(Ext.apply(this.field.recordClass.getDefaultData(), await this.getRecordDefaults()), this.field.recordClass), {phantom: true});
            const mode = this.editDialogMode ?? this.editDialogConfig?.mode ?? editDialogClass.prototype.mode;

            // 2024-10-01 do net set id to 0 any longer, solved by phantom: true
            //            otherwise we can't open multiple dialogs with new records
            // if (!this.field.selectedRecord && mode === 'remote') {
            //     // prevent loading non existing remote record
            //     record.setId(0);
            // }
            if (this.attachments) {
                record.set('attachments', this.attachments);
            }
            editDialogClass.openWindow(Object.assign({mode, record,
                recordId: record.getId(),
                needsUpdateEvent: true,
                readOnly: mode.match(/local/) && (this.field.readOnly || this.field.disabled),
                listeners: {
                    scope: this,
                    'update': (updatedRecord) => {
                        let record = !updatedRecord.data ? Tine.Tinebase.data.Record.setFromJson(updatedRecord, this.field.recordClass) : updatedRecord;
                        Tine.Tinebase.common.assertComparable(record.data);
                        if (this.field.selectedRecord && this.preserveJsonProps) {
                            Ext.copyTo(record.json, this.field.selectedRecord.json, this.preserveJsonProps)
                        }
                        // here we loose record.json data from old record! -> update existing record? vs. have preserveJSON props? // not a problem?
                        if (mode.match(/local/)) {
                            this.field.setValue(record);
                        }
                        this.field.onSelect(record, 0);
                    },
                    'cancel': () => {
                        if (new Date().getTime() - 1000 < this.field.blurOnSelectLastRun) return;
                        _.delay(() => {
                            this.field.blurOnSelectLastRun = new Date().getTime();
                            const focusClass = this.field.focusClass;
                            this.field.focusClass = '';
                            Ext.form.TriggerField.superclass.onBlur.call(this.field);
                            this.field.focusClass = focusClass;
                        }, 100);
                    }
                }
            }, this.editDialogConfig || {}));
        }
    }
}

export default RecordEditFieldTriggerPlugin

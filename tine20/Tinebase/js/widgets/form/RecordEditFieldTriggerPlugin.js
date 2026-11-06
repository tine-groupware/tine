/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import FieldTriggerPlugin from "../../ux/form/FieldTriggerPlugin"
import { takeOriginalChanges } from "../dialog/denormalization"

class RecordEditFieldTriggerPlugin extends FieldTriggerPlugin {
    allowCreateNew = true

    readyOnly = false

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

        if (this.isReadOnly()) {
            this.setVisible(!!this.field.selectedRecord);
            this.setTriggerClass('action_preview');
        }
    }

    // allow to configure defaults from outside
    async getRecordDefaults() {
        return {}
    }

    async onTriggerClick (plugin, e) {
        const originalId = this.field.selectedRecord?.get('original_id');
        if (this.field.denormalizationRecordClass && originalId && !this.isReadOnly()) {
            // the field holds a copy (denormalization) of an original record -> let the user choose which one to edit
            this.menu = this.menu || new Ext.menu.Menu({
                items: [{
                    text: window.i18n._('Edit this copy') + Tine.widgets.form.FieldManager.getDescriptionHTML(this.getCopyDescription()),
                    iconCls: 'action_edit',
                    handler: () => this.editRecord(),
                    listeners: {
                        // the info icon explains the copy (quicktip on hover/click)
                        click: (item, e) => !e.getTarget('.field-description')
                    }
                }, {
                    text: window.i18n._('Edit original'),
                    iconCls: 'action_edit',
                    handler: () => this.editOriginal()
                }]
            });

            // NOTE: the quicktip of the trigger would cover the menu, it's shown on click as well
            const quickTip = Ext.QuickTips.getQuickTip();
            const qtip = this.qtip;
            quickTip?.cancelShow(e.getTarget());
            quickTip?.hide();
            this.setQtip('');
            this.menu.on('hide', () => this.setQtip(qtip), this, { single: true });

            this.menu.show(e.getTarget(), 'tl-bl?');
            return;
        }

        return this.editRecord();
    }

    /**
     * explains the copy (denormalization) of the original record
     *
     * @return {String}
     */
    getCopyDescription() {
        const editDialog = this.field.findParentBy((cmp) => cmp instanceof Tine.widgets.dialog.EditDialog);
        const contextRecordClass = editDialog?.recordClass || this.field.contextRecordClass;

        return formatMessage('{contextRecordGender, select, male {The associated} female {The associated} other {The associated}} {contextRecordName} keeps its own copy. Changes to this copy apply only to {contextRecordGender, select, male {this} female {this} other {this}} {contextRecordName}, {recordGender, select, male {the} female {the} other {the}} original {recordName} remains unchanged.', {
            recordName: this.field.recordClass.getRecordName(),
            recordGender: this.field.recordClass.getRecordGender(),
            contextRecordName: contextRecordClass.getRecordName(),
            contextRecordGender: contextRecordClass.getRecordGender(),
        });
    }

    isReadOnly() {
        return !!(this.readyOnly || this.field.readOnly || this.field.disabled);
    }

    /**
     * edits the original of the copy (denormalization) and takes its changes into the copy
     */
    async editOriginal() {
        const originalId = this.field.selectedRecord.get('original_id');
        const editDialogClass = Tine.widgets.dialog.EditDialog.getConstructor(this.field.recordClass);

        editDialogClass.openWindow({
            recordId: originalId,
            record: { id: originalId },
            mode: 'remote',
            listeners: {
                scope: this,
                'update': async (recordData) => {
                    const updated = await takeOriginalChanges(this.field.selectedRecord.copy(), recordData, this.field.denormalizationRecordClass, this.field.app);
                    this.field.setValue(updated);
                    this.field.onSelect(updated, 0);
                }
            }
        });
    }

    /**
     * tracks the local changes of the copy (denormalization) in record.modified (see takeOriginalChanges)
     *
     * @param {Object|String|Tine.Tinebase.data.Record} updatedRecord - data of the copy edit dialog
     * @param {Tine.Tinebase.data.Record} record - updatedRecord as record
     * @return {Tine.Tinebase.data.Record}
     */
    trackLocalChanges(updatedRecord, record) {
        if (updatedRecord.data) {
            // copy with the changes of the original from the copy dialog, it knows its local changes
            const copy = this.field.recordClass.setFromJson(JSON.stringify(updatedRecord.getData()));
            copy.modified = { ...updatedRecord.modified };
            return copy;
        }

        const copy = this.field.selectedRecord.copy();
        Object.keys(record.data).forEach((name) => copy.set(name, record.data[name]));
        record.modified = { ...copy.modified };
        return record;
    }

    async editRecord () {
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
                readOnly: mode.match(/local/) && this.isReadOnly(),
                listeners: {
                    scope: this,
                    'update': (updatedRecord) => {
                        let record = !updatedRecord.data ? Tine.Tinebase.data.Record.setFromJson(updatedRecord, this.field.recordClass) : updatedRecord;
                        if (this.field.denormalizationRecordClass && this.field.selectedRecord && mode.match(/local/)) {
                            record = this.trackLocalChanges(updatedRecord, record);
                        }
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

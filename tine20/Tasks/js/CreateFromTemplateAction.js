/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import FieldTriggerPlugin from "../../Tinebase/js/ux/form/FieldTriggerPlugin";

Promise.all([Tine.Tinebase.appMgr.isInitialised('Tasks'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Tasks')
    const templateContainer = Tine.Tinebase.configManager.get('templateContainer', 'Tasks')

    Ext.preg('tasks.createFromTempalte', { init: Ext.emptyFn })
    // no config, no buttons!
    if (! templateContainer) return;

    const getComboList = (config) => {
        const recordClass = Tine.Tinebase.data.RecordMgr.get('Tasks.Task');
        const comboList = Tine.widgets.form.RecordPickerManager.get(recordClass.getMeta('appName'), recordClass.getMeta('modelName'), Object.assign({
            hidden: true,
            listMode: true,
            resizable: true,
            additionalFilters: [
                { field: 'container_id', operator: 'equals', value: templateContainer }
            ]
        }, config));

        comboList.on('select', (cmp, record) => {
            comboList.reset();
            // @TODO: copying records in edit dialog is the wrong place
            //        should be done in record an needs parameters
            //        so copy here, open without remote load
            const copy = Tine.Tasks.TaskEditDialog.prototype.getCopyRecordData.call(this, record, Tine.Tasks.Model.Task, true);

            if (config.onTemplateSelect) {
                config.onTemplateSelect(copy);
            } else {
                Tine.Tasks.TaskEditDialog.openWindow(Object.assign({
                    record,
                    copyRecord: true,
                    omitCopyTitle: true
                }, config.editDialogConfig || {}))
            }
        });

        return comboList;
    }

    const getAction = (config) => {
        return new Ext.Action(Object.assign({
            text: app.i18n._('Create from Template'),
            iconCls: 'tasks-action_create_from_template',
            menu: [],
            handler: async (btn) => {
                if (! btn.comboList) {
                    btn.comboList = getComboList({
                        listAlignEl: btn.el
                    })

                    const container = btn.el.up('td').createChild({tag: 'div'});
                    btn.comboList.render(container);
                }

                btn.comboList[btn.comboList.hidden ? 'show' : 'hide']();
            }
        }, config))
    }

    class TriggerPlugin extends FieldTriggerPlugin {
        triggerClass = 'tasks-action_create_from_template'
        qtip = app.i18n._('Create from Template')
        onTriggerClick = function () {
            const wrap = this.field.el.up('.x-small-editor')
            this.comboList = getComboList({
                listAlign: 'tl-br',
                listAlignEl: wrap,
                editDialogConfig: this.editDialogConfig,
                onTemplateSelect: this.onTemplateSelect
            })
            const container = wrap.createChild({tag: 'div'})
            this.comboList.render(container)

            this.comboList[this.comboList.hidden ? 'show' : 'hide']();
        }
    }

    // const action = getAction({})
    // const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}
    // Ext.ux.ItemRegistry.registerItem(`Tasks-Task-GridPanel-ActionToolbar-leftbtngrp`, Ext.apply(new Ext.Button(action), medBtnStyle), 5)

    Ext.preg('tasks.createFromTempalte', TriggerPlugin)
});
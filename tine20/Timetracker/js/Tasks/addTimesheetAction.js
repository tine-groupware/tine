Promise.all([
    Tine.Tinebase.appMgr.isInitialised('Timetracker'),
    Tine.Tinebase.appMgr.isInitialised('Tasks')
]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Timetracker');

    const addTimesheetAction = new Ext.Action({
        text: app.i18n._('Add Timesheet'),
        iconCls: 'TimetrackerTimesheet',
        handler: function () {
            const task = this.baseAction.task;

            const defaults = Tine.Timetracker.TimesheetGridPanel.prototype.getRecordDefaults.call(this)
            defaults.timeaccount_id = task.get('timeaccount');
            defaults.source_model = task.constructor.getPhpClassName();
            defaults.source = task
            defaults.description = task.get('summary')

            // open local, save remote
            const record = Tine.Tinebase.data.Record.setFromJson(defaults, 'Timetracker.Timesheet');
            record.phantom = true;

            Tine.widgets.dialog.EditDialog.getConstructor('Timetracker.Timesheet').openWindow({
                record: Ext.encode(record.getData()),
                recordId: record.getId()
            });
        },
        actionUpdater: function(action, grants, records, isFilterSelect, filteredContainers) {
            let enabled = records.length === 1;

            action.baseAction.task = _.get(records, '[0]');

            action.setDisabled(!enabled);
            action.baseAction.setDisabled(!enabled); // WTF?
        }
    });


    Ext.ux.ItemRegistry.registerItem('Tasks-Task-QuickAddGridPanel-Bbar', addTimesheetAction, 40);
    Ext.ux.ItemRegistry.registerItem('Tasks-Task-QuickAddGridPanel-ContextMenu', addTimesheetAction, 40);

    Ext.ux.ItemRegistry.registerItem('Tasks-Task-GridPanel-ContextMenu', addTimesheetAction, 40);
    Ext.ux.ItemRegistry.registerItem('Tasks-Task-GridPanel-ActionToolbar-leftbtngrp', Ext.apply(new Ext.Button(addTimesheetAction), {
        scale: 'medium',
        rowspan: 2,
        iconAlign: 'top'
    }), 40);

})
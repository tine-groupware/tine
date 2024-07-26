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

            const defaults = Tine.Timetracker.Model.Timesheet.getDefaultData()
            defaults.timeaccount_id = task.get('timeaccount');
            defaults.source_model = task.constructor.getPhpClassName();
            defaults.source = task
            defaults.description = task.get('summary')

            // open local, save remote
            const record = Tine.Tinebase.data.Record.setFromJson(defaults, 'Timetracker.Timesheet');
            record.phantom = true;

            Tine.widgets.dialog.EditDialog.getConstructor('Timetracker.Timesheet').openWindow({
                record: Ext.encode(record.getData()),
                recordId: record.getId(),
                contentPanelConstructorInterceptor: async (config) => {
                    const record = Tine.Tinebase.data.Record.setFromJson(config.record, 'Timetracker.Timesheet');

                    const timeaccount_id = record.get('timeaccount_id')
                    if (_.isString(timeaccount_id)) {
                        // resolve timeaccount to fix title
                        record.set('timeaccount_id', _.get(await Tine.Timetracker.searchTimeaccounts([{field: 'id', operator: 'equals', value: timeaccount_id}]), 'results[0]', timeaccount_id));
                    }

                    config.record = record.getData();
                }
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

Ext.ux.ItemRegistry.registerItem('Tasks-Task-EditDialog-TabPanel',  Ext.extend(Ext.Panel, {
    border: false,
    requiredGrant: 'editGrant',
    layout: 'fit',
    autoScroll: true,

    initComponent: function() {
        const me = this;
        this.app = Tine.Tinebase.appMgr.get('Timetracker');
        this.title = this.app.getTitle();

        this.timeAccountPicker = Tine.widgets.form.RecordPickerManager.get('Timetracker','Timeaccount',{
            fieldLabel: Tine.Timetracker.Model.Timeaccount.getRecordName(),
        });

        this.timesheetGridPanel = new Tine.Timetracker.TimesheetGridPanel({
            flex: 1,
            hasQuickSearchFilterToolbarPlugin: false,
            stateIdSuffix: '-Tasks.Task',
            initialLoadAfterRender: false,
            showColumns: ['start_date', 'account_id', 'accounting_time'],
            hideColumnsMode: 'hide',
            filterConfig: { hidden: true, maxHeight: 0 },
            bbar: [],
            initComponent() {
                Tine.Timetracker.TimesheetGridPanel.prototype.initComponent.call(this)
                this.filterToolbar.setValue([]) // get rid of default filters
                this.filterToolbar.ownerCt.hide();
                this.filterToolbar.onBeforeLoad = _.wrap(_.bind(this.filterToolbar.onBeforeLoad, this.filterToolbar), _.bind(me.onBeforeLoadTimesheets, me))
                this.bottomToolbar.add([this.action_addInNewWindow, this.action_editInNewWindow, this.action_deleteRecord])
            },
            getRecordDefaults: function() {
                const defaults = Tine.Timetracker.TimesheetGridPanel.prototype.getRecordDefaults.call(this)
                defaults.timeaccount_id = me.timeAccountPicker.selectedRecord;
                defaults.source_model = me.editDialog.recordClass.getPhpClassName();
                defaults.source = me.record
                defaults.description = me.record.get('summary')

                return defaults;
            },
        });

        this.items = [{
            layout: 'vbox',
            pack: 'start',
            border: false,
            items: [{
                layout: 'form',
                labelAlign: 'top',
                frame: true,
                // hideLabels: false,
                width: '100%',
                items: [
                    this.timeAccountPicker,
                    { xtype: 'label', text: this.app.i18n._('Timesheets of this Task:') }
                ]},
                this.timesheetGridPanel
            ]
        }];

        this.supr().initComponent.call(this);
    },


    onBeforeLoadTimesheets: function(func, store, options) {
        if (! this.record?.get('timeaccount')) return false

        const result = func(store, options)
        const filters = _.get(options.params.filter, '[0].filters[0].filters')

        if (!_.find(filters, {field: 'timeaccount_id'})) {
            filters.push({field: 'source:Tasks_Model_Task', operator: 'equals', value: this.record.id});
        }
    },

    onRecordLoad: async function(editDialog, record) {
        this.record = record;

        this.timesheetGridPanel.setDisabled(record.phantom);
        this.timeAccountPicker.setValue(record.get('timeaccount'));

        if (! record.phantom) {
            this.timesheetGridPanel.store.reload();
            // this.timesheetGridPanel.editDialogConfig.fixedFields = {
            //     // process_status: 'ACCEPTED',
            //     timeaccount_id: record.get('timeaccount'),
            // }
        }

    },

    setReadOnly: function(readOnly) {
        this.readOnly = readOnly;
        // @TODO: set panel to readonly if user has no grants!
    },

    onRecordUpdate: function(editDialog, record) {
        record.set('timeaccount', this.timeAccountPicker.getValue());
    },

    setOwnerCt: function(ct) {
        this.ownerCt = ct;

        if (! this.editDialog) {
            this.editDialog = this.findParentBy(function (c) {
                return c instanceof Tine.widgets.dialog.EditDialog
            });
        }

        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('recordUpdate', this.onRecordUpdate, this);

        // NOTE: in case record is already loaded
        if (! this.setOwnerCt.initialOnRecordLoad) {
            this.setOwnerCt.initialOnRecordLoad = true;
            this.onRecordLoad(this.editDialog, this.editDialog.record);
        }

    }

}), 2);

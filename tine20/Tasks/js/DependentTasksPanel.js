/**
 * Tine 2.0
 *
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * simple task panel to be used in editDialogs like projects, crm, ...
 *
 * @TODO support default data like container_id
 */
export default Ext.extend(Tine.widgets.grid.QuickaddGridPanel, {
    recordClass: 'Tasks.Task',
    useBBar: true,
    parentRecordField: 'tasks',
    quickaddMandatory: 'summary',
    allowCreateNew: true,

    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Tasks');
        this.editDialogConfig = this.editDialogConfig || {};
        this.editDialogConfig.mode = this.editDialogConfig.mode || 'load(remote):save(local)';
        this.editDialogConfig.dependendTaskPanel = this;
        this.viewConfig = _.assign(this.viewConfig || {},{
            getRowClass: this.getRowClass.createDelegate(this)
        });

        this.columns = [
            {
                id: 'summary',
                header: this.app.i18n._("Summary"),
                width: 130,
                dataIndex: 'summary',
                sortable: true,
                quickaddField: new Ext.form.TriggerField({
                    emptyText: this.app.i18n._('Add a task...'),
                    maxLength: 255,
                    hideTrigger: true,
                    plugins: [{
                        ptype: 'tasks.createFromTempalte',
                        editDialogConfig: this.editDialogConfig,
                        onTemplateSelect: (taskData) => {
                            const e = window.event;
                            const record = Tine.Tinebase.data.Record.setFromJson(Object.assign(taskData || {}, this.recordClass.getDefaultData(), this.getRecordDefaults()), this.recordClass);
                            record.phantom = true;

                            this.store.add(record);
                            if (!e.ctrlKey && !e.altKey && !e.shiftKey) {
                                this.onCreate(taskData)
                            }
                        }
                    }]
                })
            }, {
                id: 'due',
                header: this.app.i18n._("Due Date"),
                width: 100,
                dataIndex: 'due',
                renderer: Tine.Tinebase.common.dateRenderer,
                editor: new Ext.ux.form.ClearableDateField({
                    //format : 'd.m.Y'
                }),
                quickaddField: new Ext.ux.form.ClearableDateField({
                    //value: new Date(),
                    //format : "d.m.Y"
                }),
                sortable: true
            }, {
                id: 'priority',
                header: this.app.i18n._("Priority"),
                width: 70,
                dataIndex: 'priority',
                renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Tasks', 'taskPriority'),
                editor: {
                    xtype: 'widget-keyfieldcombo',
                    app: 'Tasks',
                    keyFieldName: 'taskPriority'
                },
                sortable: true,
                quickaddField: new Tine.Tinebase.widgets.keyfield.ComboBox({
                    app: 'Tasks',
                    keyFieldName: 'taskPriority'
                })
            }, {
                id: 'dependens_on',
                header: this.app.i18n._("Depends on"),
                dataIndex: 'dependens_on',
                renderer: Tine.widgets.grid.RendererManager.get('Tasks', 'Task', 'dependens_on', Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL),
                width: 75
            }, {
                id: 'percent',
                header: this.app.i18n._("Percent"),
                width: 70,
                dataIndex: 'percent',
                renderer: Ext.ux.PercentRenderer,
                editor: new Ext.ux.PercentCombo({
                    autoExpand: true,
                    blurOnSelect: true
                }),
                sortable: true,
                quickaddField: new Ext.ux.PercentCombo({
                    autoExpand: true
                })
            }, {
                id: 'status',
                header: this.app.i18n._("Status"),
                width: 100,
                dataIndex: 'status',
                renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Tasks', 'taskStatus'),
                sortable: true,
                editor: {
                    xtype: 'widget-keyfieldcombo',
                    app: 'Tasks',
                    keyFieldName: 'taskStatus'
                },
                quickaddField: new Tine.Tinebase.widgets.keyfield.ComboBox({
                    app: 'Tasks',
                    keyFieldName: 'taskStatus',
                    value: 'NEEDS-ACTION'
                })
            }, {
                id: 'organizer',
                header: this.app.i18n._('Responsible'),
                width: 100,
                dataIndex: 'organizer',
                renderer: Tine.Tinebase.common.accountRenderer,
                sortable: true,
                quickaddField: Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
                    userOnly: true,
                    useAccountRecord: true,
                    blurOnSelect: true,
                    selectOnFocus: true,
                    allowBlank: true,
                    value: Tine.Tinebase.registry.get('currentAccount')
                })            
            }
        ];

        this.supr().initComponent.call(this);

        if (this.filter) {
            this.filterButton = new Ext.Toolbar.Button({
                enableToggle: true,
                pressed: true,
                stateful: true,
                stateId: 'my-dependent-tasks-filter',
                stateEvents: ['toggle'],
                iconCls: 'action_filter',
                text: this.app.i18n._('My open tasks'),
                listeners: {
                    toggle: this.applyFilter,
                    scope: this
                }
            });
            this.getBottomToolbar().add('->', this.filterButton);
            this.editDialog.on('load', this.applyFilter, this);
        }
    },

    applyFilter: async function() {
        this.filteredData = null;

        if (this.filterButton.pressed) {
            const {results: filteredData} = await Tine.Tasks.searchTasks(this.filter);
            this.filteredData = filteredData;

        //     this.store.filterBy((r) => {
        //         return _.find(filteredData, {id: r.id});
        //     })
        // } else {
        //     this.store.clearFilter();
        }

        this.getView().refresh()
    },

    /**
     * Return CSS class to apply to rows depending upon due status
     *
     * @param {Tine.Tasks.Model.Task} record
     * @param {Integer} index
     * @return {String}
     */
    getRowClass: function(record, index) {
        let classNames = [];

        const due = record.get('due');

        if(record.get('status') == 'COMPLETED') {
            classNames.push('tasks-grid-completed');
        } else  if (due) {
            var dueDay = due.format('Y-m-d');
            var today = new Date().format('Y-m-d');

            if (dueDay == today) {
                classNames.push('tasks-grid-duetoday');
            } else if (dueDay < today) {
                classNames.push('tasks-grid-overdue');
            }
        }

        if (this.filteredData && !_.find(this.filteredData, { id: record.id })) {
            classNames.push('tine-grid-row-nolongerinfilter');
        }

        return _.join(_.uniq(classNames), ' ');
    }
});

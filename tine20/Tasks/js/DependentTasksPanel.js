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
    editDialogConfig: { mode: 'load(remote):save(local)' },
    quickaddMandatory: 'summary',
    allowCreateNew: true,
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Tasks');
        this.editDialogConfig.dependendTaskPanel = this;
        this.columns = [
            {
                id: 'summary',
                header: this.app.i18n._("Summary"),
                width: 130,
                dataIndex: 'summary',
                quickaddField: new Ext.form.TextField({
                    emptyText: this.app.i18n._('Add a task...')
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
                })
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
                quickaddField: new Tine.Tinebase.widgets.keyfield.ComboBox({
                    app: 'Tasks',
                    keyFieldName: 'taskPriority'
                })
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
                quickaddField: new Ext.ux.PercentCombo({
                    autoExpand: true
                })
            }, {
                id: 'status',
                header: this.app.i18n._("Status"),
                width: 100,
                dataIndex: 'status',
                renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Tasks', 'taskStatus'),
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
            }
        ];

        this.supr().initComponent.call(this);
    }
});
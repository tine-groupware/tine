import {avatarRenderer} from "../../Addressbook/js/renderers";

Ext.reg('tasks.dependency', Ext.extend(Tine.widgets.grid.PickerGridPanel, {
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Tasks');
        const me = this;

        if (this.dependendTaskPanel) {
            // hand over dependendTaskPanel recursively
            this.editDialogConfig = {
                mode: 'local',
                dependendTaskPanel: this.dependendTaskPanel
            };

            this.searchComboConfig = {
                mode: 'local',
                listEmptyText: this.app.i18n._('Use the show all button below to search for all tasks'),
                recordEditPluginConfig: {
                    editDialogConfig: this.editDialogConfig,
                    getRecordDefaults: async () => {
                        const parentTaskEditDialog = this.findParentBy(function(c) {return c instanceof Tine.widgets.dialog.EditDialog});
                        return {
                            container_id: parentTaskEditDialog.getForm().findField('container_id').selectedRecord?.getData()
                        }
                    }
                }
            }

            // have btn to switch to external tasks
            this.searchComboConfig.initList = function() {
                Tine.Tinebase.widgets.form.RecordPickerComboBox.prototype.initList.apply(this, arguments);

                if (this.pageTb && ! this.showAllBtn) {
                    this.showAllBtn = new Ext.Button({
                        text: me.app.i18n._('Show all'),
                        iconCls: 'action_showArchived',
                        enableToggle: true,
                        pressed: this.showAll,
                        scope: this,
                        handler: function() {
                            // this.store.clearFilter();
                            this.showAll = this.showAllBtn.pressed;
                            if (this.showAll) {
                                this.store.mode = 'remote';
                                this.store.load();
                            } else {
                                this.onBeforeLoad(this.store, {});
                                this.store.loadData(me.dependendTaskPanel.store.getData());
                                this.store.remove(this.store.getById(me.findParentBy(function(c) {return c instanceof Tine.widgets.dialog.EditDialog})?.recordId));
                                this.store.clearFilter();
                                this.store.fireEvent('datachanged', this.store);
                                this.onLoad();
                            }
                        }
                    });

                    this.pageTb.add('-', this.showAllBtn);
                    this.pageTb.doLayout();
                }
            }
        }

        this.supr().initComponent.call(this);

        if (this.dependendTaskPanel) {

            const parantTaskId = this.findParentBy(function(c) {return c instanceof Tine.widgets.dialog.EditDialog})?.recordId;
            // use local store from dependendTaskPanel for task pickers
            // grr cross window
            this.getSearchCombo().store.loadData(this.dependendTaskPanel.store.getData());
            this.getSearchCombo().store.remove(this.getSearchCombo().store.getById(parantTaskId));

            // prevent recursion
            this.on('beforeaddrecord', (r) => {
                const task = Tine.Tinebase.data.Record.setFromJson(r.get(this.isMetadataModelFor), Tine.Tasks.Model.Task);
                if (task.id === parantTaskId) {
                    Ext.ux.MessageBox.msg(this.app.i18n._('Invalid selection'), this.app.i18n._('A task cannot depend on itself'))
                    return false;
                }
            });

            this.store.on('add', (store, rs) => {
                // add to dependendTaskPanel.store and mark it created by us
                rs.forEach((r) => {
                    const task = Tine.Tinebase.data.Record.setFromJson(r.get(this.isMetadataModelFor), Tine.Tasks.Model.Task);
                    if (! this.dependendTaskPanel.store.getById(task.id)) {
                        this.dependendTaskPanel.store.add(Object.assign(task, {_createdBy: this.id}));
                    }
                })
            });
            this.store.on('update', (store, r) => {
                // update in dependendTaskPanel.store
                const task = Tine.Tinebase.data.Record.setFromJson(r.get(this.isMetadataModelFor), Tine.Tasks.Model.Task);
                const existing = this.dependendTaskPanel.store.getById(task.id);
                if (existing) {
                    this.dependendTaskPanel.store.replaceRecord(existing, task);
                }
            });
            this.store.on('remove', (store, r) => {
                // delete from dependendTaskPanel.store if we created it this run
                const task = Tine.Tinebase.data.Record.setFromJson(r.get(this.isMetadataModelFor), Tine.Tasks.Model.Task);
                const existing = this.dependendTaskPanel.store.getById(task.id);
                if (existing && existing._createdBy === this.id) {
                    this.dependendTaskPanel.store.remove(existing);
                }
            });
        }

        this.colModel.config[0].renderer = Tine.widgets.grid.RendererManager.get('Tasks', 'Task', 'dependedTask', Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL)
    }
}));


Tine.Tinebase.appMgr.isInitialised('Tasks').then(() => {
    // const source = Tine.widgets.grid.RendererManager.get('Tasks', 'Task', 'source', Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL);
    const summary = Tine.widgets.grid.RendererManager.get('Tasks', 'Task', 'summary', Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL);
    const status = Tine.Tinebase.widgets.keyfield.Renderer.get('Tasks', 'taskStatus', 'icon');
    const organizer = (account) => {
        // resolved?
        return account && account.accountLastName ? avatarRenderer('', {}, Tine.Tinebase.data.Record.setFromJson(account, Tine.Tinebase.Model.User)) : '';
    };

    // single record
    const task = value => {
        const task = Tine.Tinebase.data.Record.setFromJson(value, Tine.Tasks.Model.Task);
        // <div class="tasks-dependency-source">${source(task.get('source'), {}, task)}</div>
        return `${summary(task.get('summary'), {}, task)} ${organizer(task.get('organizer'), {}, task)} ${status(task.get('status'), {}, task)}`;
    }
    Tine.widgets.grid.RendererManager.register('Tasks', 'Task', 'dependedTask', task, Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL);

    // TaskDependency
    const tasks = vs => {
        return _.join(_.map(_.map(vs, 'depends_on'), task), ', ' );
    }
    Tine.widgets.grid.RendererManager.register('Tasks', 'Task', 'dependens_on', tasks, Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL);
});




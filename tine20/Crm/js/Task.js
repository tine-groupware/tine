/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO         use Tine.widgets.grid.LinkGridPanel
 */
 
Ext.ns('Tine.Crm.Task');

/**
 * @namespace   Tine.Crm
 * @class       Tine.Crm.Task.GridPanel
 * @extends     Ext.ux.grid.QuickaddGridPanel
 * 
 * Lead Dialog Tasks Grid Panel
 * 
 * <p>
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 */
Tine.Crm.Task.GridPanel = Ext.extend(Ext.ux.grid.QuickaddGridPanel, {
    /**
     * grid config
     * @private
     */
    quickaddMandatory: 'summary',
    clicksToEdit: 1,
    enableColumnHide:false,
    enableColumnMove:false,
    
    /**
     * The record currently being edited
     * 
     * @type Tine.Crm.Model.Lead
     * @property record
     */
    record: null,

    /**
     * store to hold all contacts
     * 
     * @type Ext.data.Store
     * @property store
     */
    store: null,
    
    /**
     * @type Ext.Menu
     * @property contextMenu
     */
    contextMenu: null,

    /**
     * @type Array
     * @property otherActions
     */
    otherActions: null,
    
    /**
     * @type function
     * @property recordEditDialogOpener
     */
    recordEditDialogOpener: null,

    /**
     * record class
     * @cfg {Tine.Tasks.Model.Task} recordClass
     */
    recordClass: null,
    
    /**
     * @private
     */
    initComponent: function() {
        // init properties
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('Crm');
        this.title = this.app.i18n._('Tasks');
        this.recordEditDialogOpener = Tine.Tasks.TaskEditDialog.openWindow;
        this.recordClass = Tine.Tasks.Model.Task;
        
        this.storeFields = Tine.Tasks.Model.TaskArray;
        this.storeFields.push({name: 'relation'});   // the relation object
        this.storeFields.push({name: 'relation_type'});
        
        // create delegates
        this.initStore = Tine.Crm.LinkGridPanel.initStore.createDelegate(this);
        this.initActions = Tine.Crm.LinkGridPanel.initActions.createDelegate(this);
        this.initGrid = Tine.Crm.LinkGridPanel.initGrid.createDelegate(this);
        //this.onUpdate = Tine.Crm.LinkGridPanel.onUpdate.createDelegate(this);

        // call delegates
        this.initStore();
        this.initActions();
        this.initGrid();
        
        // init store stuff
        this.store.setDefaultSort('due', 'asc');
        
        this.view = new Ext.grid.GridView({
            emptyText: this.app.i18n._('No Tasks to display'),
            onLoad: Ext.emptyFn,
            listeners: {
                beforerefresh: function(v) {
                    v.scrollTop = v.scroller.dom.scrollTop;
                },
                refresh: function(v) {
                    v.scroller.dom.scrollTop = v.scrollTop;
                }
            }
        });
        
        this.on('afteredit', this.onAfterEdit);
        
        this.on('newentry', function(taskData){
            var newTask = taskData;
            newTask.relation_type = 'task';
            
            // get first responsible person and add it to task as organizer
            var i = 0;
            while (this.record.data.relations.length > i && this.record.data.relations[i].type != 'responsible') {
                i++;
            }
            if (! newTask.organizer) {
                if (this.record.data.relations[i] && this.record.data.relations[i].type == 'responsible' && this.record.data.relations[i].related_record.account_id != '') {
                    newTask.organizer = Tine.Tinebase.registry.get('currentAccount');
                }
            } else {
                var contactRecord = this.organizerQuickAdd.selectedRecord;
                
                if (contactRecord) {
                    var organizer = {
                        accountId: contactRecord.get('account_id'),
                        accountDisplayName: contactRecord.get('n_fileas')
                    };
                    newTask.organizer = organizer;
                }
            }
            
            // add new task to store
            newTask.id = Tine.Tinebase.data.Record.generateUID();
            this.store.loadData([newTask], true);

            Tine.Tasks.saveTask(newTask).then((savedTask) => {
                this.onUpdate(savedTask);
            });
            
            return true;
        }, this);
        
        // hack to get percentage editor working
        this.on('rowclick', function(grid,row,e) {
            var cell = Ext.get(grid.getView().getCell(row,1));
            var dom = cell.child('div:last');
            while (cell.first()) {
                cell = cell.first();
                cell.on('click', function(e){
                    e.stopPropagation();
                    grid.fireEvent('celldblclick', grid, row, 1, e);
                });
            }
        }, this);
        
        Tine.Crm.Task.GridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * is called on after edit to set related records
     * @param {} o
     */
    onAfterEdit: function(o) {
        if (o.field == 'organizer') {
            var contactRecord = this.organizerEditor.selectedRecord;
            
            if (contactRecord) {
                var organizer = {
                    accountId: contactRecord.get('account_id'),
                    accountDisplayName: contactRecord.get('n_fileas')
                };
                
                o.record.set('organizer', organizer);
            } else {
                if (o.originalValue.accountId == o.value) {
                    o.record.set('organizer', o.originalValue);
                }
            }
        }
        Tine.Tasks.saveTask(o.record.data).then((savedTask) => {
            this.onUpdate(savedTask);
        });
    },
    
    
    /**
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        
        this.organizerQuickAdd = Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
            userOnly: true,
            useAccountRecord: true,
            scope: this,
            allowBlank: true
        });
        
        this.organizerEditor = Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
            userOnly: true,
            useAccountRecord: true,
            scope: this
        });
        
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true
            },
            columns: [
                 {
                    id: 'summary',
                    header: this.app.i18n._("Summary"),
                    width: 150,
                    quickaddField: new Ext.form.TextField({
                        emptyText: this.app.i18n._('Add a task...')
                    })
                }, {
                    id: 'due',
                    header: this.app.i18n._("Due Date"),
                    width: 110,
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
                    width: 80,
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
                    width: 80,
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
                    width: 92,
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
                }, {
                    id: 'organizer',
                    header: this.app.i18n._("Organizer"),
                    width: 180,
                    renderer: Tine.Tinebase.common.usernameRenderer,
                    quickaddField: this.organizerQuickAdd,
                    editor: this.organizerEditor
                }
            ]}
        );
    },
    
    /**
     * update event handler for related tasks
     * 
     * TODO use generic function
     */
    onUpdate: function(task) {
        var response = {
            responseText: task
        };
        task = Tine.Tasks.taskBackend.recordReader(response);
        
        Tine.log.debug('Tine.Crm.Task.GridPanel::onUpdate - Task has been updated:');
        Tine.log.debug(task);
        
        // remove task relations to prevent cyclic relation structure
        task.data.relations = null;
        
        var myTask = this.store.getById(task.id);
        
        if (myTask) {
            // copy values from edited task
            myTask.beginEdit();
            for (var p in task.data) {
                myTask.set(p, task.get(p));
            }
            myTask.endEdit();
            myTask.commit();
            
        } else {
            task.data.relation_type = 'task';
            this.store.add(task);
        }
    }
});

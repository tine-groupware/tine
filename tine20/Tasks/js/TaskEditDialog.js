/*
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Tasks');

import './DependencyPanel'

/**
 * @namespace   Tine.Tasks
 * @class       Tine.Tasks.TaskEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Tasks Edit Dialog</p>
 * <p>
 * TODO         refactor this: remove initRecord/containerId/relatedApp, 
 *              adopt to normal edit dialog flow and add getDefaultData to task model
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Tasks.TaskEditDialog
 */
 Tine.Tasks.TaskEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    /**
     * @cfg {Number} containerId
     */
    containerId: -1,
    
    /**
     * @cfg {String} relatedApp
     */
    relatedApp: '',
    
    /**
     * @private
     */
    labelAlign: 'side',
    
    /**
     * @private
     */
    windowNamePrefix: 'TasksEditWindow_',
    appName: 'Tasks',
    recordClass: 'Tine.Tasks.Model.Task',
    // recordProxy: Tine.Tasks.JsonBackend,
    showContainerSelector: true,
    displayNotes: true,

    /**
     * @private
     */
    initComponent: function() {
        this.alarmPanel = new Tine.widgets.dialog.AlarmPanel({});
        this.sourceRenderer = Tine.widgets.grid.RendererManager.get('Tasks', 'Task', 'source', Tine.widgets.grid.RendererManager.CATEGORY_DISPLAYPANEL);
        Tine.Tasks.TaskEditDialog.superclass.initComponent.call(this);
    },
    
    /**
     * executed when record is loaded
     * @private
     */
    onRecordLoad: function() {
        // interrupt process flow until dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        Tine.Tasks.TaskEditDialog.superclass.onRecordLoad.apply(this, arguments);
        this.handleCompletedDate();
        
        // update tabpanels
        this.alarmPanel.onRecordLoad(this.record);
        
        if (! this.copyRecord && ! this.record.id) {
            this.window.setTitle(this.app.i18n._('Add New Task'));
        }

        const source = this.record.get('source');
        this.sourceHint.setVisible(!! source);
        this.sourceHint.setText(source ? this.app.i18n._('This Task is part of:') + ' ' + this.sourceRenderer(source, {}, this.record) : '...');
    },
    
    /**
     * executed when record is updated
     * @private
     */
    onRecordUpdate: function() {
        Tine.Tasks.TaskEditDialog.superclass.onRecordUpdate.apply(this, arguments);
        this.alarmPanel.onRecordUpdate(this.record);
    },
    
    /**
     * handling for the completed field
     * @private
     */
    handleCompletedDate: function() {
        
        var statusStore = Tine.Tinebase.widgets.keyfield.StoreMgr.get('Tasks', 'taskStatus'),
            status = this.getForm().findField('status').getValue(),
            statusRecord = statusStore.getById(status),
            completedField = this.getForm().findField('completed'),
            percentField = this.getForm().findField('percent');

        if (statusRecord) {
            if (statusRecord.get('is_open') !== 0) {
                completedField.setValue(null);
                completedField.setDisabled(true);
            } else {
                if (! Ext.isDate(completedField.getValue())){
                    completedField.setValue(new Date());
                }
                percentField.setValue(100);
                completedField.setDisabled(false);
            }
        }
        
    },
    
    /**
     * checks if form data is valid
     * 
     * @return {Boolean}
     */
    isValid: function() {
        var isValid = true;
        
        var dueField = this.getForm().findField('due'),
            dueDate = dueField.getValue(),
            alarms = this.alarmPanel.alarmGrid.getFromStoreAsArray();
            
        if (! Ext.isEmpty(alarms) && ! Ext.isDate(dueDate)) {
            dueField.markInvalid(this.app.i18n._('You have to supply a due date, because an alarm ist set!'));
            
            isValid = false;
        }
        
        return isValid && Tine.Tasks.TaskEditDialog.superclass.isValid.apply(this, arguments);
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * @private
     */
    getFormItems: function() {
        return {
            xtype: 'tabpanel',
            plain:true,
            activeTab: 0,
            border: false,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }, {
                ptype: 'ux.itemregistry',
                key:   'Tasks-Task-EditDialog-TabPanel'
            }],
            defaults: {
                hideMode: 'offsets'
            },
            items:[{
                title: this.app.i18n.n_('Task', 'Tasks', 1),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                items: [{
                    region: 'center',
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype:'textfield',
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: .333
                    },
                    items: [[{
                        xtype: 'v-alert',
                        variant: 'info',
                        columnWidth: 1,
                        ref: '../../../../../sourceHint',
                        label: '...'
                    }],[{
                        columnWidth: 1,
                        fieldLabel: this.app.i18n._('Summary'),
                        name: 'summary',
                        listeners: {render: function(field){field.focus(false, 250);}},
                        maxLength: 255,
                        allowBlank: false
                    }], [new Ext.ux.form.DateTimeField({
                            defaultTime: '12:00',
                            fieldLabel: this.app.i18n._('Due date'),
                            name: 'due',
                            listeners: {scope: this, change: this.validateDue},
                            columnWidth: 1/3,
                        }),
                        this.fieldManager('estimated_duration', {
                            columnWidth: 1/6
                        }),
                        new Tine.Tinebase.widgets.keyfield.ComboBox({
                            fieldLabel: this.app.i18n._('Priority'),
                            name: 'priority',
                            app: 'Tasks',
                            keyFieldName: 'taskPriority',
                            columnWidth: 1/6,
                        }),
                        Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
                            userOnly: true,
                            fieldLabel: this.app.i18n._('Organizer / Responsible'),
                            emptyText: i18n._('Add Responsible ...'),
                            useAccountRecord: true,
                            name: 'organizer',
                            allowBlank: true,
                            columnWidth: 1/3,
                        })
                    ], [{
                        columnWidth: 1,
                        fieldLabel: this.app.i18n._('Notes'),
                        emptyText: this.app.i18n._('Enter description...'),
                        name: 'description',
                        xtype: 'textarea',
                        height: 200
                    }], [
                        new Ext.ux.PercentCombo({
                            fieldLabel: this.app.i18n._('Percentage'),
                            editable: false,
                            name: 'percent'
                        }), 
                        new Tine.Tinebase.widgets.keyfield.ComboBox({
                            app: 'Tasks',
                            keyFieldName: 'taskStatus',
                            fieldLabel: this.app.i18n._('Status'),
                            name: 'status',
                            value: 'NEEDS-ACTION',
                            allowBlank: false,
                            listeners: {scope: this, 'change': this.handleCompletedDate}
                        }), 
                        new Ext.ux.form.DateTimeField({
                            allowBlank: true,
                            defaultTime: '12:00',
                            fieldLabel: this.app.i18n._('Completed'),
                            name: 'completed'
                        })
                    ], [
                        this.fieldManager('attendees', {columnWidth: 1})
                    ], [
                        this.fieldManager('dependens_on', {dependendTaskPanel: this.dependendTaskPanel, columnWidth: .5}),
                        this.fieldManager('dependent_taks', {dependendTaskPanel: this.dependendTaskPanel, columnWidth: .5})
                    ]]
                }, {
                    // activities and tags
                    layout: 'ux.multiaccordion',
                    animate: true,
                    region: 'east',
                    width: 210,
                    split: true,
                    collapsible: true,
                    collapseMode: 'mini',
                    header: false,
                    margins: '0 5 0 5',
                    border: true,
                    items: [
                        new Tine.widgets.tags.TagPanel({
                            app: 'Tasks',
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
                }]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: (this.record) ? this.record.id : '',
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            }), this.alarmPanel
            ]
        };
    },

     validateDue: function() {
         var dueField = this.getForm().findField('due'),
             due = dueField.getValue();
         
         if (Ext.isDate(due) && due.getTime() - Date.now() <= 0) {
             dueField.markInvalid(this.app.i18n._('Attention: This due date is in the past!'));
         }
     },

     getCopyRecordData: function (record, recordClass, omitCopyTitle) {
         const recordData = Tine.Tasks.TaskEditDialog.superclass.getCopyRecordData.apply(this, arguments);
         recordData.id = Tine.Tinebase.data.Record.generateUID();
         recordData.uid = Tine.Tinebase.data.Record.generateUID();

         // @TODO get correct container (needs an option)
         const templateContainer = Tine.Tinebase.configManager.get('templateContainer', 'Tasks')

         if ((recordData.container_id?.id || recordData.container_id) === templateContainer) {
             const defaultData = Tine.Tasks.Model.Task.getDefaultData();
             recordData.container_id = defaultData.container_id;
         }

         recordData.attendees = _.map(record.get('attendees'), (attendee) => {
             return Object.assign(attendee, {
                 id: null,
                 task_id: recordData.id
             });
         });

         // @TODO copy? (needs an option)
         recordData.dependens_on = null

         // @TODO clear? (needs an option)
         recordData.dependent_taks = _.map(record.get('dependent_taks'), (taskDependency) => {
             // @TODO is dependent task fully resolved here?
             const task = Tine.Tinebase.data.Record.setFromJson(taskDependency.task_id, recordClass);
             return Object.assign({... taskDependency}, {
                 id: null,
                 depends_on: recordData.id,
                 task_id: Tine.Tasks.TaskEditDialog.prototype.getCopyRecordData.call(this, task, recordClass, omitCopyTitle)
             });
         });

         if (record.get('container_id')?.id === Tine.Tinebase.configManager.get('templateContainer', 'Tasks')) {
             recordData.container_id = Tine.Tasks.Model.Task.getDefaultData().container_id;
         }

         return recordData;
     }
});

/**
 * Tasks Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Tasks.TaskEditDialog.openWindow = function (config) {
    const id = config.recordId ?? config.record?.id ?? 0;
    var window = Tine.WindowFactory.getWindow({
        width: 900,
        height: 800,
        name: Tine.Tasks.TaskEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Tasks.TaskEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

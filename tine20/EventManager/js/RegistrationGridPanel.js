Tine.widgets.form.FieldManager.register('EventManager', 'Event', 'registrations', {
    xtype: 'wdgt.pickergrid',
    name: 'registrations',
    isFormField: true,
    allowCreateNew: true,
    enableBbar: false,
    enableHdMenu: false,
    clicksToEdit: 1,
    refIdField: 'eventId',
    isMetadataModelFor: 'name',
    height: 200,
    hideLabel: true,
    layout: 'fit',
    border: false,
    app: null,


    initComponent() {
        _.set(this, 'editDialogConfig.mode', 'local');
        this.recordDefaults = {

        };
        
        this.recordClass = Tine.EventManager.Model.Registration;
        this.app = Tine.Tinebase.appMgr.get('EventManager');
        
        Tine.widgets.grid.PickerGridPanel.prototype.initComponent.call(this)
    },

    getColumnModel: function() {

        this.contactEditor = new Tine.Addressbook.SearchCombo({
            hidden: true,
            userOnly: false,
            allowBlank: true
        });

        const columns = [
            {id: 'name', dataIndex: 'name', header: this.app.i18n._('Name'), scope: this,
                width: 60
            },
        ];

        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: false,
                editable: true
            },
            columns: columns
        });
    },

}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);
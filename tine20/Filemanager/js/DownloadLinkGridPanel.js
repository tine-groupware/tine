/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager');

/**
 * File grid panel
 * 
 * @namespace   Tine.Filemanager
 * @class       Tine.Filemanager.DownloadLinkGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>DownloadLink Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Filemanager.FileGridPanel
 */
Tine.Filemanager.DownloadLinkGridPanel = Ext.extend(Ext.grid.EditorGridPanel, {
    
    // private
    frame: true,
    border: true,
    autoScroll: true,
    layout: 'fit',
    autoExpandColumn: 'url',
    requiredGrant: 'publishGrant',
    enableHdMenu: false,
    
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        var _ = window.lodash;
        
        this.recordProxy = Tine.Filemanager.downloadLinkRecordBackend;
        this.recordClass = Tine.Filemanager.Model.DownloadLink;

        this.editDialog.on('load', this.onRecordLoad, this);
        
        this.store = new Ext.data.Store({
            fields: this.recordClass,
            proxy: this.recordProxy,
            reader: this.recordProxy.getReader(),
            remoteSort: false,
            sortInfo: {
                field: 'expiry_time',
                direction: 'DESC'
            },
            listeners: {
                scope: this,
                'beforeload': this.onStoreBeforeload,
                'update': this.onStoreUpdate
            }
        });
        
        this.actionCreate = new Ext.Action({
            text: this.app.i18n._('Create Public Link'),
            disabled: true,
            scope: this,
            handler: this.onCreate,
            iconCls: 'action_add'
        });

        this.actionCopy = new Ext.Action({
            text: this.app.i18n._('Copy Public Link'),
            disabled: false,
            scope: this,
            handler: this.onCopy,
            iconCls: 'clipboard'
        });
        
        this.actionRemove = new Ext.Action({
            text: i18n._('Remove record'),
            disabled: true,
            scope: this,
            handler: this.onRemove,
            iconCls: 'action_delete'
        });
        
        this.tbar = [
            this.actionCreate,
            this.actionCopy,
            this.actionRemove
        ];
        
        this.contextMenu = new Ext.menu.Menu({
            plugins: [{
                ptype: 'ux.itemregistry',
                key:   'Tinebase-MainContextMenu'
            }],
            items: [
                this.actionCreate,
                this.actionCopy,
                this.actionRemove
            ]
        });
        
        this.cm = this.getColumnModel();
        this.sm = new Ext.grid.RowSelectionModel({multiSelect:true});

        this.plugins = this.plugins ? this.plugins : [];
        this.plugins.push(new Ext.ux.grid.GridViewMenuPlugin({}));

        // on selectionchange handler
        this.sm.on('selectionchange', function(sm) {
            var rowCount = sm.getCount();
            this.actionRemove.setDisabled(rowCount == 0);
        }, this);
        
        // on rowcontextmenu handler
        this.on('rowcontextmenu', this.onRowContextMenu.createDelegate(this), this);
        
        Tine.Filemanager.DownloadLinkGridPanel.superclass.initComponent.call(this);
        
        this.initialLoad();
    },

    onCopy: function() {
        let selectedRows = this.getSelectionModel().getSelections();
        Tine.Filemanager.FilePublishedDialog.openWindow({record:selectedRows['0']})
    },

    onRecordLoad: function(editDialog, record, ticketFn) {
        var _ = window.lodash,
            evalGrants = editDialog.evalGrants,
            hasRequiredGrant = !evalGrants || _.get(record, record.constructor.getMeta('grantsPath') + '.' + this.requiredGrant);

        this.actionCreate.setDisabled(!hasRequiredGrant);
    },
    
    /**
     * that's the context menu handler
     * @param {} grid
     * @param {} row
     * @param {} e
     */
    onRowContextMenu: function(grid, row, e) {
        e.stopEvent();
        
        this.fireEvent('beforecontextmenu', grid, row, e);
        
        var sm = grid.getSelectionModel();
        if (!sm.isSelected(row)) {
            sm.selectRow(row);
        }
        
        this.contextMenu.showAt(e.getXY());
    },
    
    onCreate: function() {
        var passwordDialog = new Tine.Tinebase.widgets.dialog.PasswordDialog({
            allowEmptyPassword: true,
            locked: false,
            questionText: i18n._('Download links can be protected with a password. If no password is specified, anyone who knows the link can access the selected files.')
        });
        passwordDialog.openWindow();

        passwordDialog.on('apply', function (password) {
            if (! this.createMask) {
                this.createMask = new Ext.LoadMask(this.getEl(), {
                    msg: this.app.i18n._('Creating new Download Link...')
                });
            }
            this.createMask.show();

            var date = new Date();
            date.setDate(date.getDate() + 30);

            var record = new this.recordClass({node_id: this.editDialog.record.get('id'), expiry_time: date, password: password});
            this.recordProxy.saveRecord(record, {success: this.onAfterCreate, scope: this});
        }, this);
    },
    
    onAfterCreate: function() {
        this.store.load();
        this.createMask.hide();
    },
    
    onAfterDelete: function() {
        this.store.load();
        this.deleteMask.hide();
    },
    
    /**
     * remove handler
     * 
     * @param {} button
     * @param {} event
     */
    onRemove: function(button, event) {
        
        var selectedRows = this.getSelectionModel().getSelections();
        
        if (selectedRows.length == 0) {
            return;
        }
        
        if (! this.deleteMask) {
            this.deleteMask = new Ext.LoadMask(this.getEl(), {
                msg: selectedRows.length > 1 ? this.app.i18n._('Deleting Download Links...') : this.app.i18n._('Deleting Download Link...')
            });
        }
        
        this.deleteMask.show();
        
        this.recordProxy.deleteRecords(selectedRows, {success: this.onAfterDelete, scope: this});
    },
    
    /**
     * called before store queries for data
     */
    onStoreBeforeload: function(store, options) {
        options.params = options.params || {};
        // allways start with an empty filter set!
        // this is important for paging and sort header!
        options.params.filter = [{field: 'node_id', operator: 'in', value: this.editDialog.record.get('id') }];
    },
    
    /**
     * called when the store gets updated, e.g. from editgrid
     * 
     * @param {Ext.data.store} store
     * @param {Tine.Tinebase.data.Record} record
     * @param {String} operation
     */
    onStoreUpdate: function(store, record, operation) {
        switch (operation) {
            case Ext.data.Record.EDIT:
                // don't save these records. Add them to the parents' record store
                this.recordProxy.saveRecord(record, {
                    scope: this,
                    success: function(updatedRecord) {
                        store.commitChanges();

                        // update record in store to prevent concurrency problems
                        record.data = updatedRecord.data;
                    }
                });
                break;
            case Ext.data.Record.COMMIT:
                //nothing to do, as we need to reload the store anyway.
                break;
        }
    },
    
    /**
     * returns cm
     * 
     * @return Ext.grid.ColumnModel
     * @private
     * 
     * TODO    add more columns
     */
    getColumnModel: function(){
        var columns = [{ 
                id: 'url',
                header: this.app.i18n._('URL'),
                width: 250,
                hidden: false,
                readOnly: true,
                disabled: true
            }, {
                id: 'created_by',
                header: this.app.i18n._("Created By"),
                width: 150,
                sortable: true,
                hidden: false,
                renderer: Tine.Tinebase.common.usernameRenderer
            }, {
                id: 'creation_time',
                header: this.app.i18n._("Creation Time"),
                width: 100,
                sortable: true,
                renderer: Tine.Tinebase.common.dateTimeRenderer,
                hidden: true
            }, {
                id: 'expiry_time',
                header: this.app.i18n._("Valid until"),
                width: 100,
                sortable: true,
                hidden: false,
                renderer: Tine.Tinebase.common.dateTimeRenderer,
                editor: new Ext.ux.form.ClearableDateField()
            }, {
                id: 'password',
                header: this.app.i18n._("Password"),
                width: 70,
                sortable: true,
                renderer: Tine.Tinebase.common.booleanRenderer,
                hidden: false
            }, {
                id: 'access_count',
                header: this.app.i18n._("Access Count"),
                width: 70,
                sortable: true,
                hidden: false
            }, {
                id: 'last_modified_time',
                header: this.app.i18n._("Last Modified Time"),
                width: 100,
                sortable: true,
                hidden: true,
                renderer: Tine.Tinebase.common.dateTimeRenderer
            }, {
                id: 'last_modified_by',
                header: this.app.i18n._("Last Modified By"),
                width: 150,
                sortable: true,
                hidden: true,
                renderer: Tine.Tinebase.common.usernameRenderer 
            }
        ];
        
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                resizable: true
            },
            columns: columns
        });
    },
    
    /**
     * preform the initial load of grid data
     */
    initialLoad: function() {
        this.store.load.defer(10, this.store);
    }
});

/**
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  AccessLog
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philip Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Admin.accessLog');

/**
 * AccessLog 'mainScreen'
 * 
 * @static
 */
Tine.Admin.accessLog.show = function () {
    var app = Tine.Tinebase.appMgr.get('Admin');
    if (! Tine.Admin.accessLog.gridPanel) {
        Tine.Admin.accessLog.gridPanel = new Tine.Admin.accessLog.GridPanel({
            app: app
        });
    }
    else {
        Tine.Admin.accessLog.gridPanel.loadGridData.defer(100, Tine.Admin.accessLog.gridPanel, []);
    }

    app.getMainScreen().setActiveContentPanel(Tine.Admin.accessLog.gridPanel, true);
    app.getMainScreen().setActiveToolbar(Tine.Admin.accessLog.gridPanel.actionToolbar, true);
};

/**
 * AccessLog grid panel
 * 
 * @namespace   Tine.Admin.accessLog
 * @class       Tine.Admin.accessLog.GridPanel
 * @extends     Tine.widgets.grid.GridPanel
 */
Tine.Admin.accessLog.GridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    
    recordClass: Tine.Admin.Model.AccessLog,
    recordProxy: Tine.Admin.accessLogBackend,
    defaultSortInfo: {field: 'li', direction: 'DESC'},
    evalGrants: false,
    gridConfig: {
        id: 'gridAdminAccessLogs',
        autoExpandColumn: 'login_name'
    },
    
    initComponent: function() {
        this.gridConfig.columns = this.getColumns();
        Tine.Admin.accessLog.GridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * init actions with actionToolbar, contextMenu and actionUpdater
     * 
     * @private
     */
    initActions: function() {
        
        this.initDeleteAction();
        
        this.actionUpdater.addActions([
            this.action_deleteRecord
        ]);
        
        this.actionToolbar = new Ext.Toolbar({
            items: [{
                xtype: 'buttongroup',
                columns: 1,
                items: [
                    Ext.apply(new Ext.Button(this.action_deleteRecord), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top',
                        arrowAlign:'right'
                    })
                ]}
             ]
        });
        
        if (this.filterToolbar && typeof this.filterToolbar.getQuickFilterField == 'function') {
            this.actionToolbar.add('->', this.filterToolbar.getQuickFilterField());
        }
        
        this.contextMenu = new Ext.menu.Menu({
            items: [this.action_deleteRecord],
            plugins: [{
                ptype: 'ux.itemregistry',
                key:   'Tinebase-MainContextMenu'
            }]
        });
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterPanel: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n._('Access Log'),  field: 'query',         operators: ['contains']},
                {label: this.app.i18n._('IP Address'),  field: 'ip'},
                {label: this.app.i18n._('User'),        field: 'account_id',    valueType: 'user'},
                {label: this.app.i18n._('Login Time'),  field: 'li',            valueType: 'datetime', pastOnly: true        },
                {label: this.app.i18n._('Logout Time'), field: 'lo',            valueType: 'datetime', pastOnly: true        },
                {label: this.app.i18n._('Client Type'), field: 'clienttype'}
            ],
            defaultFilter: 'query',
            filters: [
                {field: 'li',           operator: 'within', value: 'weekThis'}
                //{field: 'clienttype',   operator: 'equals', value: 'JSON-RPC'}
            ],
            plugins: [
                new Tine.widgets.grid.FilterToolbarQuickFilterPlugin()
            ]
        });
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
    },
    
    /**
     * returns cm
     * @private
     */
    getColumns: function() {
        const columns = [
            { header: this.app.i18n._('Session ID'), id: 'sessionid', width: 200, hidden: true},
            { header: this.app.i18n._('Login Name'), id: 'login_name'},
            { header: this.app.i18n._('Name'), id: 'account_id', width: 170, sortable: false, renderer: Tine.Tinebase.common.usernameRenderer},
            { header: this.app.i18n._('IP Address'), id: 'ip', width: 150},
            { header: this.app.i18n._('Login Time'), id: 'li', width: 140, renderer: Tine.Tinebase.common.dateTimeRenderer},
            { header: this.app.i18n._('Logout Time'), id: 'lo', width: 140, renderer: Tine.Tinebase.common.dateTimeRenderer},
            { header: this.app.i18n._('Result'), id: 'result', width: 110, renderer: this.resultRenderer, scope: this},
            { header: this.app.i18n._('User Agent'), id: 'user_agent', width: 90},
            { header: this.app.i18n._('Client Type'), id: 'clienttype', width: 50}
        ];
        return columns;
    },
    
    /**
     * result renderer
     * 
     * @param {} _value
     * @param {} _cellObject
     * @param {} _record
     * @param {} _rowIndex
     * @param {} _colIndex
     * @param {} _dataStore
     * @return String
     */
    resultRenderer: function(_value, _cellObject, _record, _rowIndex, _colIndex, _dataStore) {
        var gridValue;
        
        switch (_value) {
            case -102 :
                gridValue = this.app.i18n._('user blocked');
                break;

            case -101 :
                gridValue = this.app.i18n._('password expired');
                break;

            case -100 :
                gridValue = this.app.i18n._('user disabled');
                break;

            case -3 :
                gridValue = this.app.i18n._('invalid password');
                break;

            case -2 :
                gridValue = this.app.i18n._('ambiguous username');
                break;

            case -1 :
                gridValue = this.app.i18n._('user not found');
                break;

            case 0 :
                gridValue = this.app.i18n._('failure');
                break;

            case 1 :
                gridValue = this.app.i18n._('success');
                break;
        }
        
        return gridValue;
    }
});

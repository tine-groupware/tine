/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Admin.sambaMachine');

/**
 * Samba machine 'mainScreen'
 */
Tine.Admin.sambaMachine.show = function() {
    var app = Tine.Tinebase.appMgr.get('Admin');
    if (! Tine.Admin.sambaMachine.gridPanel) {
        Tine.Admin.sambaMachine.gridPanel = new Tine.Admin.SambaMachineGridPanel({
            app: app
        });
    } else {
        setTimeout ("Ext.getCmp('gridAdminComputers').getStore().load({ params: { start:0, limit:50 } })", 100);
    }
    
    Tine.Tinebase.MainScreen.setActiveContentPanel(Tine.Admin.sambaMachine.gridPanel, true);
    Tine.Tinebase.MainScreen.setActiveToolbar(Tine.Admin.sambaMachine.gridPanel.actionToolbar, true);
};

/**
 * SambaMachine grid panel
 *
 * @namespace   Tine.Admin.sambaMachine
 * @class       Tine.Admin.SambaMachineGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 */
Tine.Admin.SambaMachineGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    // model generics
    recordClass: Tine.Admin.Model.SambaMachine,
    recordProxy: Tine.Admin.sambaMachineBackend,
    defaultSortInfo: {field: 'accountLoginName', direction: 'ASC'},
    evalGrants: false,
    gridConfig: {
        id: 'gridAdminComputers',
        autoExpandColumn: 'accountDisplayName'
    },
    
    initComponent: function() {
        this.gridConfig.columns = this.getColumns();
        Tine.Admin.SambaMachineGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterPanel: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n._('Computer Name'),    field: 'query',       operators: ['contains']}
                //{label: this.app.i18n._('Description'),    field: 'description', operators: ['contains']},
            ],
            defaultFilter: 'query',
            filters: [],
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
    getColumns: function(){
        const columns = [
            {id: 'accountId', header: this.app.i18n._("ID"), width: 100, hidden: true},
            {id: 'accountLoginName', header: this.app.i18n._("Name"), width: 350 },
            {id: 'accountDisplayName', header: this.app.i18n._("Description"), width: 350 }
        ];
        return columns;
    }
});

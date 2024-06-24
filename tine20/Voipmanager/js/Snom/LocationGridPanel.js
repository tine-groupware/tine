/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Voipmanager');

/**
 * Context grid panel
 */
Tine.Voipmanager.SnomLocationGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    // model generics
    recordClass: Tine.Voipmanager.Model.SnomLocation,
    evalGrants: false,
    
    // grid specific
    defaultSortInfo: {field: 'description', direction: 'ASC'},
    gridConfig: {
        autoExpandColumn: 'description'
    },
    
    initComponent: function() {
        this.recordProxy = Tine.Voipmanager.SnomLocationBackend;
        this.gridConfig.columns = this.getColumns();
        this.actionToolbarItems = this.getToolbarItems();
        Tine.Voipmanager.SnomLocationGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterToolbar: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: i18n._('Quick Search'),    field: 'query',    operators: ['contains']}
            ],
            defaultFilter: 'query',
            filters: [],
            plugins: [
                new Tine.widgets.grid.FilterToolbarQuickFilterPlugin()
            ]
        });
    },   
    
    /**
     * returns cm
     * @private
     * 
     */
    getColumns: function(){
        const columns = [
            { id: 'firmware_interval', header: this.app.i18n._('FW Interval'), width: 10, hidden: true },
            { id: 'update_policy', header: this.app.i18n._('Update Policy'), width: 30, hidden: true },
            { id: 'registrar', header: this.app.i18n._('Registrar'), width: 100, hidden: true },
            { id: 'admin_mode', header: this.app.i18n._('Admin Mode'), width: 10, hidden: true },
            { id: 'ntp_server', header: this.app.i18n._('NTP Server'), width: 50, hidden: true },
            { id: 'webserver_type', header: this.app.i18n._('Webserver Type'), width: 30, hidden: true },
            { id: 'https_port', header: this.app.i18n._('HTTPS Port'), width: 10, hidden: true },
            { id: 'http_user', header: this.app.i18n._('HTTP User'), width: 15, hidden: true },
            { id: 'id', header: this.app.i18n._('id'), width: 10, hidden: true },
            { id: 'name', header: this.app.i18n._('Name'), dataIndex: 'name', width: 80, sortable: true },
            { id: 'description', header: this.app.i18n._('Description'), dataIndex: 'description', width: 350, sortable: true }
        ];
        return columns;
    },
    
    initDetailsPanel: function() { return false; },
    
    /**
     * return additional tb items
     * 
     * @todo add duplicate button
     * @todo move export buttons to single menu/split button
     */
    getToolbarItems: function(){
       
        return [

        ];
    } 
});

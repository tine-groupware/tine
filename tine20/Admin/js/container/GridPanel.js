/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.Admin.container');


/**
 * Container grid panel
 * 
 * @namespace   Tine.Admin.container
 * @class       Tine.Admin.container.GridPanel
 * @extends     Tine.widgets.grid.GridPanel
 */
Tine.Admin.container.GridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    
    /**
     * @cfg
     */
    newRecordIcon: 'admin-action-add-container',
    recordClass: Tine.Admin.Model.Container,
    recordProxy: Tine.Admin.containerBackend,
    defaultSortInfo: {field: 'name', direction: 'ASC'},
    evalGrants: false,
    gridConfig: {
        autoExpandColumn: 'name'
    },
    
    /**
     * initComponent
     */
    initComponent: function() {
        this.gridConfig.cm = this.getColumnModel();
        Tine.Admin.container.GridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * returns column model
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                hidden: true,
                resizable: true
            },
            columns: this.getColumns()
        });
    },
    
    /**
     * returns columns
     * @private
     * @return Array
     */
    getColumns: function(){
        const columns = [
            { header: this.app.i18n._('ID'), id: 'id', hidden: false },
            { header: this.app.i18n._('Container Name'), id: 'name', hidden: false},
            { header: this.app.i18n._('Application'), id: 'application_id', renderer: this.appRenderer.createDelegate(this), hidden: false },
            { header: this.app.i18n._('Type'), id: 'type', renderer: this.typeRenderer.createDelegate(this), hidden: false },
            { header: this.app.i18n._('Container Order'), id: 'order', hidden: true }
        ];
        return columns;
    },
    
    /**
     * returns application name
     * 
     * @param {Object} value
     * @return {String}
     */
    appRenderer: function(value) {
        return this.app.i18n._(value.name);
    },
    
    /**
     * returns translated type
     * 
     * @param {Object} value
     * @return {String}
     */
    typeRenderer: function(value) {
        return this.app.i18n._(value);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterPanel: function() {
        var typeStore = [['shared', this.app.i18n._('shared')], ['personal', this.app.i18n._('personal')]];
        
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n._('Container'),       field: 'query',    operators: ['contains']},
                {label: this.app.i18n._('Type'), defaultValue: 'shared', valueType: 'combo', field: 'type', store: typeStore},
                {filtertype: 'admin.application', app: this.app}
            ],
            defaultFilter: 'query',
            filters: [
                {field: 'type', operator: 'equals', value: 'shared'}
            ],
            plugins: [
                new Tine.widgets.grid.FilterToolbarQuickFilterPlugin()
            ]
        });
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
    }
});

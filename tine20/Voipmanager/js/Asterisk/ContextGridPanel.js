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
Tine.Voipmanager.AsteriskContextGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    // model generics
    recordClass: Tine.Voipmanager.Model.AsteriskContext,
    evalGrants: false,
    
    // grid specific
    defaultSortInfo: {field: 'name', direction: 'ASC'},
    gridConfig: {
        autoExpandColumn: 'description'
    },
    
    initComponent: function() {
        this.recordProxy = Tine.Voipmanager.AsteriskContextBackend;
        this.gridConfig.columns = this.getColumns();
        this.actionToolbarItems = this.getToolbarItems();
        Tine.Voipmanager.AsteriskContextGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * returns cm
     * @private
     * 
     */
    getColumns: function(){
        const columns = [
            { id: 'id', header: this.app.i18n._("id"), width: 10, hidden: true },
            { id: 'name', header: this.app.i18n._("Name"), width: 100, renderer: function(name) { return Ext.util.Format.htmlEncode(name); } },
            { id: 'description', header: this.app.i18n._("Description"), width: 350, renderer: function(description) { return Ext.util.Format.htmlEncode(description); } }
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

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
 * Software grid panel
 */
Tine.Voipmanager.SnomSoftwareGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    // model generics
    recordClass: Tine.Voipmanager.Model.SnomSoftware,
    evalGrants: false,
    
    // grid specific
    defaultSortInfo: {field: 'description', direction: 'ASC'},
    gridConfig: {
        autoExpandColumn: 'description'
    },
    
    initComponent: function() {
        this.recordProxy = Tine.Voipmanager.SnomSoftwareBackend;
        this.gridConfig.columns = this.getColumns();
        this.actionToolbarItems = this.getToolbarItems();
        Tine.Voipmanager.SnomSoftwareGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * returns cm
     * @private
     * 
     */
    getColumns: function(){
        const columns = [
            { id: 'id', header: this.app.i18n._('id'), width: 20, hidden: true },
            { id: 'name', header: this.app.i18n._('name'), width: 150 },
            { id: 'description', header: this.app.i18n._('Description'), width: 250 }
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

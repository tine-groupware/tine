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
Tine.Voipmanager.SnomSettingGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    // model generics
    recordClass: Tine.Voipmanager.Model.SnomSetting,
    evalGrants: false,
    
    // grid specific
    defaultSortInfo: {field: 'description', direction: 'ASC'},
    gridConfig: {
        autoExpandColumn: 'description'
    },
    
    initComponent: function() {
        this.recordProxy = Tine.Voipmanager.SnomSettingBackend;
        this.gridConfig.columns = this.getColumns();
        this.actionToolbarItems = this.getToolbarItems();
        Tine.Voipmanager.SnomSettingGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * returns cm
     * @private
     * 
     */
    getColumns: function(){
        const columns = [
            { id: 'id', header: this.app.i18n._('Id'), width: 30, hidden: true },
            { id: 'name', header: this.app.i18n._('name'), width: 150, sortable: true },
            { id: 'description', header: this.app.i18n._('description'), width: 200, sortable: true },
            { id: 'web_language', header: this.app.i18n._('web_language'), width: 10, hidden: true },
            { id: 'language', header: this.app.i18n._('language'), width: 10, hidden: true },
            { id: 'display_method', header: this.app.i18n._('display_method'), width: 10, hidden: true },
            { id: 'mwi_notification', header: this.app.i18n._('mwi_notification'), width: 10, hidden: true },
            { id: 'mwi_dialtone', header: this.app.i18n._('mwi_dialtone'), width: 10, hidden: true },
            { id: 'headset_device', header: this.app.i18n._('headset_device'), width: 10, hidden: true },
            { id: 'message_led_other', header: this.app.i18n._('message_led_other'), width: 10, hidden: true },
            { id: 'global_missed_counter', header: this.app.i18n._('global_missed_counter'), width: 10, hidden: true },
            { id: 'pickup_indication', header: this.app.i18n._('Pickup indication'), width: 10, hidden: true },
            { id: 'scroll_outgoing', header: this.app.i18n._('scroll_outgoing'), width: 10, hidden: true },
            { id: 'show_local_line', header: this.app.i18n._('show_local_line'), width: 10, hidden: true },
            { id: 'show_call_status', header: this.app.i18n._('show_call_status'), width: 10, hidden: true },
            { id: 'call_waiting', header: this.app.i18n._('call_waiting'), width: 25, hidden: true }
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

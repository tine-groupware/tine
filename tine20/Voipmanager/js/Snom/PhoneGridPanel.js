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
Tine.Voipmanager.SnomPhoneGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    // model generics
    recordClass: Tine.Voipmanager.Model.SnomPhone,
    evalGrants: false,
    
    // grid specific
    defaultSortInfo: {field: 'description', direction: 'ASC'},
    gridConfig: {
        autoExpandColumn: 'description'
    },
    
    initComponent: function() {
        this.recordProxy = Tine.Voipmanager.SnomPhoneBackend;
        this.gridConfig.columns = this.getColumns();
        this.actionToolbarItems = this.getToolbarItems();
 
        // add context menu actions
        var action_resetHttpClientInfo = new Ext.Action({
           text: this.app.i18n._('reset phones HTTP authentication'), 
           handler: this.resetHttpClientInfo,
           iconCls: 'action_resetHttpClientInfo',
           scope: this
        });
        
        var action_openPhonesWebGui = new Ext.Action({
           text: this.app.i18n._('Open phones web gui'), 
           handler: this.openPhonesWebGui,
           iconCls: 'action_openPhonesWebGui',
           scope: this
        });
        
        this.contextMenuItems = [action_resetHttpClientInfo, action_openPhonesWebGui];
        
        Tine.Voipmanager.SnomPhoneGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * returns cm
     * @private
     * 
     */
    getColumns: function(){
        const columns = [
            { id: 'id', header: this.app.i18n._('Id'), width: 30, hidden: true },
            { id: 'macaddress', header: this.app.i18n._('MAC address'), width: 50, sortable: true },
            { id: 'description', header: this.app.i18n._('description'), sortable: true },
            { id: 'location_id', header: this.app.i18n._('Location'), width: 70, renderer: function(_data,_obj, _rec) { return _rec.data.location; } },
            { id: 'template_id', header: this.app.i18n._('Template'), width: 70, renderer: function(_data,_obj, _rec) { return _rec.data.template; } },
            { id: 'ipaddress', header: this.app.i18n._('IP Address'), width: 50, sortable: true },
            { id: 'current_software', header: this.app.i18n._('Software'), width: 50, sortable: true },
            { id: 'current_model', header: this.app.i18n._('current model'), width: 70, hidden: true },
            { id: 'redirect_event', header: this.app.i18n._('redirect event'), width: 70, hidden: true },
            { id: 'redirect_number', header: this.app.i18n._('redirect number'), width: 100, hidden: true },
            { id: 'redirect_time', header: this.app.i18n._('redirect time'), width: 25, hidden: true },
            { id: 'settings_loaded_at', header: this.app.i18n._('settings loaded at'), width: 100, hidden: true, renderer: Tine.Tinebase.common.dateTimeRenderer },
            { id: 'last_modified_time', header: this.app.i18n._('last modified'), width: 100, hidden: true, renderer: Tine.Tinebase.common.dateTimeRenderer }
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
    },
    
    /**
     * onclick handler for resetHttpClientInfo
     */
    resetHttpClientInfo: function(_button, _event) {
        Ext.MessageBox.confirm('Confirm', 'Do you really want to send HTTP Client Info again?', function(_button){
            if (_button == 'yes') {
            
                var phoneIds = [];
                
                var selectedRows = this.selectionModel.getSelections();
                for (var i = 0; i < selectedRows.length; ++i) {
                    phoneIds.push(selectedRows[i].id);
                }
                
                Ext.Ajax.request({
                    url: 'index.php',
                    params: {
                        method: 'Voipmanager.resetHttpClientInfo',
                        phoneIds: phoneIds
                    },
                    text: 'sending HTTP Client Info to phone(s)...',
                    success: function(_result, _request){
                        // not really needed to reload store
                        //Ext.getCmp('Voipmanager_Phones_Grid').getStore().reload();
                    },
                    failure: function(result, request){
                        Ext.MessageBox.alert('Failed', 'Some error occured while trying to send HTTP Client Info to the phone(s).');
                    }
                });
            }
        }, this);
    },
    
    /**
     * onclick handler for openPhonesWebGui
     */
    openPhonesWebGui: function(_button, _event) {
        var phoneIp;
                
        var selectedRows = this.selectionModel.getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            phoneIp = selectedRows[i].get('ipaddress');
            if (phoneIp && phoneIp.length >= 7) {
                window.open('http://' + phoneIp, '_blank',  'width=1024,height=768,scrollbars=1');
            }
        }
    }
});

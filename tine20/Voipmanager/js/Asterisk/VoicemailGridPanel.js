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
 * Voicemail grid panel
 */
Tine.Voipmanager.AsteriskVoicemailGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    // model generics
    recordClass: Tine.Voipmanager.Model.AsteriskVoicemail,
    evalGrants: false,
    
    // grid specific
    defaultSortInfo: {field: 'fullname', direction: 'ASC'},
    gridConfig: {
        autoExpandColumn: 'fullname'
    },
    
    initComponent: function() {
        this.recordProxy = Tine.Voipmanager.AsteriskVoicemailBackend;
        this.gridConfig.columns = this.getColumns();
        this.actionToolbarItems = this.getToolbarItems();
        Tine.Voipmanager.AsteriskVoicemailGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * returns cm
     * @private
     * 
     */
    getColumns: function(){
        const columns = [
            { id: 'id', header: this.app.i18n._('id'), width: 10, hidden: true },
            { id: 'mailbox', header: this.app.i18n._('mailbox'), width: 50, sortable: true },
            { id: 'context', header: this.app.i18n._('context'), width: 70, sortable: true },
            { id: 'fullname', header: this.app.i18n._('fullname'), width: 180, sortable: true },
            { id: 'email', header: this.app.i18n._('email'), width: 120, sortable: true },
            { id: 'pager', header: this.app.i18n._('pager'), width: 120, sortable: true },
            { id: 'tz', header: this.app.i18n._('tz'), width: 10, hidden: true },
            { id: 'attach', header: this.app.i18n._('attach'), width: 10, hidden: true },
            { id: 'saycid', header: this.app.i18n._('saycid'), width: 10, hidden: true },
            { id: 'dialout', header: this.app.i18n._('dialout'), width: 10, hidden: true },
            { id: 'callback', header: this.app.i18n._('callback'), width: 10, hidden: true },
            { id: 'review', header: this.app.i18n._('review'), width: 10, hidden: true },
            { id: 'operator', header: this.app.i18n._('operator'), width: 10, hidden: true },
            { id: 'envelope', header: this.app.i18n._('envelope'), width: 10, hidden: true },
            { id: 'sayduration', header: this.app.i18n._('sayduration'), width: 10, hidden: true },
            { id: 'saydurationm', header: this.app.i18n._('saydurationm'), width: 10, hidden: true },
            { id: 'sendvoicemail', header: this.app.i18n._('sendvoicemail'), width: 10, hidden: true },
            { id: 'delete', header: this.app.i18n._('delete'), width: 10, hidden: true },
            { id: 'nextaftercmd', header: this.app.i18n._('nextaftercmd'), width: 10, hidden: true },
            { id: 'forcename', header: this.app.i18n._('forcename'), width: 10, hidden: true },
            { id: 'forcegreetings', header: this.app.i18n._('forcegreetings'), width: 10, hidden: true },
            { id: 'hidefromdir', header: this.app.i18n._('hidefromdir'), width: 10, hidden: true }
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

/*
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Frederic Heihoff <heihoff@sh-systems.eu>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Addressbook');

/**
 * List grid panel
 * 
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.ListGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>List Grid Panel</p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Frederic Heihoff <heihoff@sh-systems.eu>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Addressbook.ListGridPanel
 */
Tine.Addressbook.ListGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * record class
     * @cfg {Tine.Addressbook.Model.List} recordClass
     */
    recordClass: 'Addressbook.List',
    
    /**
     * grid specific
     * @private
     */ 
    defaultSortInfo: {field: 'name', direction: 'ASC'},
    copyEditAction: true,
    felamimail: false,
    multipleEdit: false,
    duplicateResolvable: false,
    
    /**
     * @cfg {Bool} hasDetailsPanel 
     */
    hasDetailsPanel: false,
    
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        this.recordProxy = Tine.Addressbook.listBackend;
        
        // check if felamimail is installed and user has run right and wants to use felamimail in adb
        if (Tine.Felamimail && Tine.Tinebase.common.hasRight('run', 'Felamimail') && Tine.Felamimail.registry.get('preferences').get('useInAdb')) {
            this.felamimail = (Tine.Felamimail.registry.get('preferences').get('useInAdb') == 1);
        }
        this.filterToolbar = this.filterToolbar || this.getFilterToolbar();

        if (this.hasDetailsPanel) {
            this.detailsPanel = this.getDetailsPanel();
        }

        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
        
        Tine.Addressbook.ListGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * @private
     */
    initActions: function() {        
        Tine.Addressbook.ListGridPanel.superclass.initActions.call(this);
    },

    /**
     * returns details panel
     * 
     * @private
     * @return {Tine.Addressbook.ListGridDetailsPanel}
     */
    getDetailsPanel: function() {
        return new Tine.Addressbook.ListGridDetailsPanel({
            gridpanel: this,
            il8n: this.app.i18n
        });
    }
});

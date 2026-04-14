/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Addressbook');

Tine.Addressbook.ContactTreePanel = function(config) {
    Ext.apply(this, config);
    
    this.id = 'Addressbook_Contact_Tree';
    this.filterMode = 'filterToolbar';
    this.recordClass = Tine.Addressbook.Model.Contact;
    Tine.Addressbook.ContactTreePanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Addressbook.ContactTreePanel , Tine.widgets.container.TreePanel);

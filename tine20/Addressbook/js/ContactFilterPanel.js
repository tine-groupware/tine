/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Addressbook');

Tine.Addressbook.ContactFilterPanel = function(config) {
    Ext.apply(this, config);
    Tine.Addressbook.ContactFilterPanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Addressbook.ContactFilterPanel, Tine.widgets.persistentfilter.PickerPanel, {
    filter: [{field: 'model', operator: 'equals', value: 'Addressbook_Model_ContactFilter'}]
});

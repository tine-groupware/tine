/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Tine.Addressbook.ContactsSearchCombo = Ext.extend(Tine.Tinebase.widgets.form.VMultiPicker, {

    initComponent: function(){
        this.app = Tine.Tinebase.appMgr.get('Addressbook');

        this.recordClass = this.recordClass || Tine.Addressbook.Model.Contact;

        this.emptyText = this.emptyText || (this.readOnly || this.disabled ? '' : (this.userOnly ?
                this.app.i18n._('Search for users ...') :
                this.app.i18n._('Search for Contacts ...')
        ));

        Tine.Addressbook.ContactsSearchCombo.superclass.initComponent.call(this);
    },

    getValue: function() {
        let value = Tine.Addressbook.ContactsSearchCombo.superclass.getValue.call(this);
        if (this.useAccountRecord) {
            value = _.map(value, 'account_id');
        }

        return value;
    },

    setValue: async function (value) {
        if (value && value.length && _.isObject(value[0]) && !value[0].hasOwnProperty('account_id')) {
            // value is account record, but we need contacts!
            value = (await Tine.Addressbook.searchContacts([{field: 'id', operator: 'in', value: _.map(value, 'contact_id')}])).results;
        }

        Tine.Addressbook.ContactsSearchCombo.superclass.setValue.call(this, value);
    }

});

Ext.reg('addressbookcontactspicker', Tine.Addressbook.ContactsSearchCombo);
Tine.widgets.form.RecordPickerManager.register('Addressbook', 'Contacts', Tine.Addressbook.ContactsSearchCombo);

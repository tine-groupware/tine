/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
/*global Ext, Tine*/
 
Ext.ns('Tine.Addressbook.Model');

Tine.Addressbook.Model.ContactMixin = {
    /**
     * returns true if record has an email address
     * @return {Boolean}
     */
    hasEmail: function() {
        let result = false;
        _.each(this.modelConfiguration.fields, (field) => { 
            if (field?.specialType === 'Addressbook_Model_ContactProperties_Email' && this.get(field.fieldName)) {
               result = true;
            }
        });
        return result;
    },
    
    /**
     * returns true preferred email if available
     * @return {String}
     */
    getPreferredEmail: function() {
        let preferredType = this.get('preferred_email') ?? 'email';
        const email = this.get(preferredType);
        if (!email || email === '') {
            _.each(this.modelConfiguration.fields, (field) => {
                if (field?.specialType === 'Addressbook_Model_ContactProperties_Email' && this.get(field.fieldName)) {
                    preferredType = field.fieldName;
                }
            });
        }
        
        return {
            email: this.get(preferredType),
            type: preferredType
        };
    },

    getPreferredAddressObject: function() {
        const prefix = (this.get('preferred_address') || 'adr_one') + '_';
        return _.reduce(this.data, (a, v, k) => {
            return Object.assign(a, _.startsWith(k, prefix) ? _.set({}, k.replace(prefix, ''), v) : {});
        }, {});
    },

    statics: {
    }
};

/**
 * email address model
 */
Tine.Addressbook.Model.EmailAddress = Tine.Tinebase.data.Record.create([
    {name: 'n_fileas'},
    {name: 'name'},
    {name: 'email_type_field'},
    {name: 'email'},
    {name: 'emails'},
    {name: 'type'},
    {name: 'contact_record'},
], {
    appName: 'Addressbook',
    modelName: 'EmailAddress',
    titleProperty: 'name',
    // ngettext('Email Address', 'Email Addresses', n); gettext('Email Addresses');
    recordName: 'Email Address',
    recordsName: 'Email Addresses',
    containerProperty: 'container_id',
    // ngettext('Addressbook', 'Addressbooks', n); gettext('Addressbooks');
    containerName: 'Addressbook',
    containersName: 'Addressbooks',
    copyOmitFields: ['group_id'],

    getPreferredEmail: function() {
        let preferredType = this.get('preferred_email');
        const email = this.get(preferredType);
        if (!email || email === '') preferredType = 'email';
        
        return { 
            email: this.get(preferredType),
            type: preferredType
        };
    },
    
    isPreferred: function(token) {
        if (token?.contact_record?.['preferred_email'] && token?.email_type_field) {
            const field = token.contact_record['preferred_email'];
            return field === token.email_type_field && token.contact_record[field] === token.email;
        }
        return false;
    },
    
    getEmailFields() {
        const fields = Tine.Addressbook.Model.Contact.getModelConfiguration().fields;
        return _.filter(fields, (field) => {
            return field?.specialType === 'Addressbook_Model_ContactProperties_Email';
        });
    },
});

/**
 * get filtermodel of emailaddress model
 * 
 * @namespace Tine.Addressbook.Model
 * @static
 * @return {Array} filterModel definition
 */ 
Tine.Addressbook.Model.EmailAddress.getFilterModel = function() {
    return [
        {label: i18n._('Quick search'),       field: 'query',              operators: ['contains']}
    ];
};

/**
 * Industry model
 */
Tine.Addressbook.Model.Industry = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'}
], {
    appName: 'Addressbook',
    modelName: 'Industry',
    titleProperty: 'name',
    // ngettext('Industry', 'Industries', n); gettext('Industries');
    recordName: 'Industry',
    recordsName: 'Industries'
});

/**
 * get filtermodel of Industry model
 *
 * @namespace Tine.Addressbook.Model
 * @static
 * @return {Array} filterModel definition
 */
Tine.Addressbook.Model.Industry.getFilterModel = function() {
    return [
        {label: i18n._('Quick search'),       field: 'query',              operators: ['contains']}
    ];
};

/**
 * Structure (fake) model
 */
Tine.Addressbook.Model.Structure = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'name'},
    {name: 'description'}
], {
    appName: 'Addressbook',
    modelName: 'Structure',
    titleProperty: 'name',
    // ngettext('Structure', 'Structures', n); gettext('Structures');
    recordName: 'Structure',
    recordsName: 'Structures'
});

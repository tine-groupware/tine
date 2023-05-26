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
        return this.get('email') || this.get('email_home');
    },
    
    /**
     * returns true preferred email if available
     * @return {String}
     */
    getPreferredEmail: function(preferred) {
        preferred = preferred || 'email';
        const other = preferred === 'email' ? 'email_home' : 'email';
            
        return (this.get(preferred) || this.get(other));
    },
    
    getTitle: function() {
        var result = this.get('n_fileas');

        var tinebaseApp = new Tine.Tinebase.Application({
            appName: 'Tinebase'
        });
        if (tinebaseApp.featureEnabled('featureShowAccountEmail')) {
            var email = this.getPreferredEmail();
            if (email) {
                result += ' (' + email + ')';
            }
        }

        return result;
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
    {name: 'email_type'},
    {name: 'email'}, 
    {name: 'type'},
    {name: 'record_id'}
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

    getPreferredEmail: function(preferred) {
        preferred = preferred || 'email';
        const other = preferred === 'email' ? 'email_home' : 'email';
    
        if (! this.get("email") && ! this.get("email_home")) {
            return this.get("emails");
        } else {
            return (this.get(preferred) || this.get(other));
        }
    }
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

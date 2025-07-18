/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.ContactSearchCombo
 * @extends     Tine.Addressbook.SearchCombo
 * 
 * <p>Email Search ComboBox</p>
 * <p></p>
 * <pre></pre>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.ContactSearchCombo
 */
Tine.Felamimail.ContactSearchCombo = Ext.extend(Tine.Addressbook.SearchCombo, {

    /**
     * @cfg {Boolean} forceSelection
     */
    forceSelection: false,
    
    /**
     * @private
     */ 
    valueIsList: false,

    /**
     * no path filter for emails!
     */
    addPathFilter: false,
    
    recordEditPluginConfig: false,
    additionalFilterSpec: {},
    
    /**
     * @private
     */
    initComponent: function() {
        // Search Lists and Contacts
        this.recordClass = Tine.Addressbook.Model.EmailAddress;
        this.recordProxy = Tine.Addressbook.emailAddressBackend;
        
        this.tpl = new Ext.XTemplate(
            '<tpl for="."><div class="search-item">',
            '{[this.encode(values)]}',
            '</div></tpl>',
            {
                encode: this.renderEmailAddressAndIcon.createDelegate(this),
            }
        );
        
        Tine.Felamimail.ContactSearchCombo.superclass.initComponent.call(this);
        
        this.store.on('load', this.onStoreLoad, this);
    },
    
    /**
     * use beforequery to set query filter
     *
     * @param {Event} qevent
     */
    onBeforeQuery: function (qevent) {
        Tine.Felamimail.ContactSearchCombo.superclass.onBeforeQuery.apply(this, arguments);
    
        const filter = this.store.baseParams.filter;
        const queryFilter = _.find(filter, {field: 'query'});
        _.remove(filter, queryFilter);

        filter.push({field: 'name_email_query', operator: 'contains', value: queryFilter.value});
    },
    
    /**
     * override default onSelect
     * - set email/name as value
     * 
     * @param {} record
     * @private
     */
    onSelect: function(record, index) {
        this.selectedRecord = record;
        if (this.selectedRecord) {
            this.setRawValue('');
        }
        this.value = this.getValue();
        this.collapse();
        this.fireEvent('select', this, record, index);
    },
    
    /**
     * always return raw value
     * 
     * @return String
     */
    getValue: function() {
        return this.getRawValue();
    },
  
    /**
     * always set valueIsList to false
     *
     * @param value
     */
    setValue: function(value) {
       Tine.Felamimail.ContactSearchCombo.superclass.setValue.call(this, value); 
    }, 
    
    /**
     * on load handler of combo store
     * -> add additional record if contact has multiple email addresses
     * 
     * @param {} store
     * @param {} records
     * @param {} options
     */
    onStoreLoad: function(store, records, options) {
        this.removeInvalidRecords(store);
    },
    
    getAddressIconClass: function(token) {
        let data = {tip: 'E-Mail', iconClass: 'EmailAccount'};
        
        switch (token?.type) {
            case 'user':
                data = {tip: 'Contact of a user account', iconClass: 'Account'};
                break;
            case 'mailingListMember':
                data = {tip: 'Mailing List Member', iconClass: 'Account'};
                break;
            case 'responsible':
                data = {tip: 'E-Mail', iconClass: 'Contact'};
                break;
            case 'mailingList':
                data = {tip: 'Mailing List', iconClass: 'MailingList'};
                break;
            case 'email_account':
                data = {tip: 'E-Mail', iconClass: 'EmailAccount'};
                break;
            case 'group':
                data = {tip: 'System Group', iconClass: 'Group'};
                break;
            case 'list':
                data = {tip: 'Group', iconClass: 'List'};
                break;
            case 'groupMember':
            case 'listMember':
                data = {tip: 'Group Member', iconClass: 'GroupMember'};
                break;
            case 'contact':
                data = {tip: 'Contact', iconClass: 'Contact'};
                break;
            default :
                if (token?.contact_record) data = {tip: 'Contact', iconClass: 'Contact'};
                break;
        }
        if (token?.email_type_field) {
            const emailFields = Tine.Addressbook.Model.EmailAddress.prototype.getEmailFields();
            const emailField = emailFields.find((f) => { return f.fieldName === token?.email_type_field});
            if (emailField?.requiredGrants && emailField.requiredGrants.includes('privateDataGrant')) {
                data = {tip: 'Email (private)', iconClass: 'Private'};
            }
        }
        return data;
    },
    
    renderEmailAddressAndIcon(token) {
        const i18n = Tine.Tinebase.appMgr.get('Addressbook').i18n;
        // render icon
        const iconEl =  document.createElement('div');
        const iconData = this.getAddressIconClass(token);
        iconEl.className = `tine-combo-icon renderer_type${iconData.iconClass}Icon`;
        iconEl.setAttribute('ext:qtip', i18n._(iconData.tip));
        
        // render email
        const emailEl =  document.createElement('div');
        const renderEmail = token.email !== '' && token.name !== '' ? ` < ${token.email} >` : token.email;
        const note = token?.note && token.note !== '' ? ` ( ${token.note} )` : '';
        if (token.qtip) emailEl.setAttribute('ext:qtip', token.qtip);
        emailEl.className = 'responsive-grid-text-small';
        emailEl.innerHTML = Ext.util.Format.htmlEncode(token.name)
            + '<b>' + Ext.util.Format.htmlEncode(renderEmail) + '</b>'
            + note;
        
        const el =  document.createElement('div');
        el.className = 'tinebase-property-field';
        el.append(iconEl, emailEl);
        
        const isPreferred = Tine.Addressbook.Model.EmailAddress.prototype.isPreferred(token);
        const preferredIconEl =  document.createElement('div');
        preferredIconEl.className = `tine-combo-icon renderer_PreferredIcon`;
        preferredIconEl.setAttribute('ext:qtip', i18n._('Preferred Email'));
        if (isPreferred) el.append(preferredIconEl);

        return el.outerHTML;
    },
    
    removeInvalidRecords(store) {
        const processedEmails = new Set();
        const recordsToRemove = [];

        store.each((record) => {
            if (!this.validate(record)) {
                recordsToRemove.push(record);
                return;
            }
            const emailString = Tine.Felamimail.getEmailStringFromContact(record);
            if (processedEmails.has(emailString)) {
                recordsToRemove.push(record);
            } else {
                processedEmails.add(emailString);
            }
        });

        recordsToRemove.forEach(record => {
            const idx = store.indexOf(record);
            if (idx !== -1) {
                store.removeAt(idx);
                Tine.log.debug(`remove duplicate email from ${record.data.type}: ${record.data.email}`);
            }
        });
    },
    
    validate (record) {
        return true;
    }
});
Ext.reg('felamimailcontactcombo', Tine.Felamimail.ContactSearchCombo);

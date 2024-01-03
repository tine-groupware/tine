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
            '{[this.getIcon(values)]}',
            '<span style="padding-left: 5px;">',
            '{[this.encode(values)]}',
            '</span>',
            '</div></tpl>',
            {
                encode: this.renderEmailAddress.createDelegate(this),
                getIcon: this.renderAddressIconCls.createDelegate(this)
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

    doQuery : function(q, forceAll){
        // always load store otherwise the recipients will not be updated
        this.store.load({
            params: this.getParams(q)
        });
  
        Tine.Felamimail.ContactSearchCombo.superclass.doQuery.apply(this, arguments);
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
    
    renderAddressIconCls: function(token) {
        const i18n = Tine.Tinebase.appMgr.get('Addressbook').i18n;
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
            case 'email_home':
                data = {tip: 'Email (private)', iconClass: 'Private'};
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
                if (token?.contact_record !== '') data = {tip: 'Contact', iconClass: 'Contact'};
                break;
        }
    
        if (token?.email_type === 'email_home') data = {tip: 'Email (private)', iconClass: 'Private'};
        
        return '<div class="tine-combo-icon renderer AddressbookIconCls renderer_type' + data.iconClass + 'Icon" ext:qtip="' 
            + Ext.util.Format.htmlEncode(i18n._(data.tip)) + '"/></div>';
    },
    
    renderEmailAddress(token) {
        const renderEmail = token.email !== '' && token.name !== '' ? ` < ${token.email} >` : token.email;
        const note = token?.note && token.note !== '' ? ` ( ${token.note} )` : '';
        return Ext.util.Format.htmlEncode(token.name) 
            + '<b>' + Ext.util.Format.htmlEncode(renderEmail) + '</b>'
            + Ext.util.Format.htmlEncode(note);
    },
    
    removeInvalidRecords(store) {
        store.each((record) => {
            const idx = store.indexOf(record);
            if (!this.validate(record)){
                store.removeAt(idx);
                return;
            }
            const duplicates = store.queryBy((contact) => {
                return record.id !== contact.id && Tine.Felamimail.getEmailStringFromContact(record) === Tine.Felamimail.getEmailStringFromContact(contact);
            });
            if (duplicates.getCount() > 0) {
                Tine.log.debug(`remove duplicate email from ${record.data.type}: ${record.data.email}`);
                store.removeAt(idx);
            }
        });
    },
    
    validate (record) {
        return true;
    }
});
Ext.reg('felamimailcontactcombo', Tine.Felamimail.ContactSearchCombo);

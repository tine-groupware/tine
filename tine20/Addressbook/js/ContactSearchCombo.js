/*
 * Tine 2.0
 * contacts combo box and store
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

import {preferredAddressRender} from './renderers'
Ext.ns('Tine.Addressbook');

/**
 * contact selection combo box
 * 
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.SearchCombo
 * @extends     Ext.form.ComboBox
 * 
 * <p>Contact Search Combobox</p>
 * <p><pre>
 * TODO         make this a twin trigger field with 'clear' button?
 * TODO         add switch to filter for expired/enabled/disabled user accounts
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Addressbook.SearchCombo
 * 
 * TODO         add     forceSelection: true ?
 */
import RecordEditFieldTriggerPlugin from "../../Tinebase/js/widgets/form/RecordEditFieldTriggerPlugin";

Tine.Addressbook.ContactSearchCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    
    /**
     * @cfg {Boolean} userOnly
     */
    userOnly: false,

    /**
     * @cfg {Boolean} noEditPlugin
     */
    noEditPlugin: false,

    /**
     * @cfg {Boolean} addPathFilter
     */
    addPathFilter: true,

    /**
     * use account objects/records in get/setValue
     * 
     * @cfg {Boolean} legacy
     * @legacy
     * 
     * TODO remove this later
     */
    useAccountRecord: false,
    allowBlank: true,
    
    itemSelector: 'div.search-item',
    minListWidth: 350,
    
    //private
    initComponent: function(){
        this.app = Tine.Tinebase.appMgr.get('Addressbook');

        if (this.recordClass === null) {
            this.recordClass = Tine.Addressbook.Model.Contact;
            // this.recordProxy = Tine.Addressbook.contactBackend;
        }

        this.emptyText = this.emptyText || (this.readOnly || this.disabled ? '' : (this.userOnly ?
            this.app.i18n._('Search for users ...') :
            this.app.i18n._('Search for Contacts ...')
        ));

        this.useEditPlugin = this.hasOwnProperty('useEditPlugin') ? this.useEditPlugin : (!this.userOnly && !this.useAccountRecord && !this.noEditPlugin);

        this.initTemplate();
        Tine.Addressbook.SearchCombo.superclass.initComponent.call(this);
    },
    
    /**
     * is called in accountMode to reset the value
     * @param value
     */
    processValue: function(value) {
        if (this.useAccountRecord) {
            if (value == '') {
                this.accountId = null;
                this.selectedRecord = null;
            }
        }
        return Tine.Addressbook.SearchCombo.superclass.processValue.call(this, value);
    },

    /**
     * use beforequery to set query filter
     * 
     * @param {Event} qevent
     */
    onBeforeQuery: function(qevent){
        Tine.Addressbook.SearchCombo.superclass.onBeforeQuery.apply(this, arguments);

        const filter = this.store.baseParams.filter;

        if (this.addPathFilter) {

            const queryFilter = _.find(filter, {field: 'query'});
            const pathFilter = {field: 'path', operator: 'contains', value: queryFilter.value};

            _.remove(filter, queryFilter);

            filter.push({
                condition: "OR", filters: [
                    queryFilter,
                    pathFilter
                ]
            });
        }

        if (this.userOnly) {
            filter.push({field: 'type', operator: 'equals', value: 'user'});
        }
    },
    
    /**
     * init template
     * @private
     */
    initTemplate: function() {
        // Custom rendering Template
        if (! this.tpl) {
            this.tpl = new Ext.XTemplate(
                '<tpl for="."><div class="search-item addressbook-search-combo x-combo-list-item">',
                    '<table>',
                        '<tr>',
                            '<td style="min-width: 20px;">{[Tine.Addressbook.ContactGridPanel.contactTypeRenderer(null, null, values)]}</td>',
                            '<td width="30%"><b>{[Tine.Addressbook.ContactGridPanel.displayNameRenderer(values.n_fileas)]}</b><br/>' +
                                '{[Tine.Tinebase.EncodingHelper.encode(values.org_name)]}<br/>{[this.encode(values.org_unit)]}</td>',
                            '<td width="25%">{[this.preferredAddressRender(null, null, values)]}<br/>' +
                                '{[this.encode(values.email)]}</td>',
                            '<td width="25%">{[Tine.Tinebase.EncodingHelper.encode(values.tel_work)]}<br/>{[Tine.Tinebase.EncodingHelper.encode(values.tel_cell)]}</td>',
                            '<td width="50px">',
                                '<img width="45px" height="39px" src="{jpegphoto}" />',
                            '</td>',
                        '</tr>',
                    '</table>',
                    '{[Tine.widgets.path.pathsRenderer(values.paths, this.getLastQuery())]}',
                '</div></tpl>', {
                    getLastQuery: this.getLastQuery.createDelegate(this),
                    preferredAddressRender
                }
            );
        }
    },
    
    getValue: function() {
        if (this.serverValue) {
            return this.serverValue;
        }

        if (this.useAccountRecord) {
            if (this.selectedRecord) {
                return this.selectedRecord.get('account_id') || this.selectedRecord.get('accountId');
            } else {
                return this.accountId;
            }
        } else {
            return Tine.Addressbook.SearchCombo.superclass.getValue.call(this);
        }
    },

    setValue: function (value) {
        if (this.useAccountRecord) {
            if (value) {
                if (value === 'currentAccount') {
                    this.serverValue = value;
                    value = Tine.Tinebase.registry.get('currentAccount');
                }
                if(value.accountId) {
                    // account object
                    this.selectedRecord = this.selectedAccount = Tine.Tinebase.data.Record.setFromJson(value, Tine.Tinebase.Model.User);
                    this.accountId = value.accountId;
                    value = value.accountDisplayName;
                } else if (typeof(value.get) == 'function') {
                    // account record
                    this.selectedRecord = this.selectedAccount = value;
                    this.accountId = value.get('id');
                    value = value.get('name');
                }
            } else {
                if (value === 'currentContact') {
                    this.serverValue = value;
                    value = Tine.Tinebase.registry.get('userContact');
                }

                this.accountId = null;
                this.selectedRecord = null;
            }
        }
        return Tine.Addressbook.SearchCombo.superclass.setValue.call(this, value);
    }

});

// legacy
Tine.Addressbook.SearchCombo = Tine.Addressbook.ContactSearchCombo;

Ext.reg('addressbookcontactpicker', Tine.Addressbook.ContactSearchCombo);
Tine.widgets.form.RecordPickerManager.register('Addressbook', 'Contact', Tine.Addressbook.ContactSearchCombo);

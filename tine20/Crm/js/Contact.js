/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO         use Tine.widgets.grid.LinkGridPanel
 */

import {preferredAddressRender} from '../../Addressbook/js/renderers'
Ext.ns('Tine.Crm.Contact');

/**
 * @namespace   Tine.Crm.Contact
 * @class       Tine.Crm.Contact.Combo
 * @extends     Tine.Addressbook.SearchCombo
 * 
 * Lead Dialog Contact Search Combo
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Tine.Crm.Contact.Combo = Ext.extend(Tine.Addressbook.SearchCombo, {

    valueField: 'id',
    
    /**
     * store to hold all contacts of grid
     * 
     * @type Ext.data.Store
     * @property contactsStore
     */
    contactsStore: null,
    
    /**
     * override default onSelect
     * 
     * TODO add some logic to determine if contact is customer or partner
     */
    onSelect: function(record) {
        var data = {
            relation_type: (record.get('type') == 'user') ? 'responsible' : 'customer'
        };
        
        // check if already in
        if (! this.contactsStore.getById(record.id)) {
            var recordToAdd = new this.contactsStore.recordType(Ext.apply(data, record.data), record.id);
            this.contactsStore.add([recordToAdd]);
        }
            
        this.collapse();
        this.clearValue();
    }
});

/**
 * @namespace   Tine.Crm.Contact
 * @class       Tine.Crm.Contact.GridPanel
 * @extends     Ext.grid.EditorGridPanel
 * 
 * Lead Dialog Contact Grid Panel
 * 
 * <p>
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Tine.Crm.Contact.GridPanel = Ext.extend(Ext.grid.EditorGridPanel, {
    /**
     * grid config
     * @private
     */
    autoExpandColumn: 'n_fileas',
    clicksToEdit: 1,
    baseCls: 'contact-grid',
    
    /**
     * The record currently being edited
     * 
     * @type Tine.Crm.Model.Lead
     * @property record
     */
    record: null,
    
    /**
     * store to hold all contacts
     * 
     * @type Ext.data.Store
     * @property store
     */
    store: null,
    
    /**
     * @type Ext.Menu
     * @property contextMenu
     */
    contextMenu: null,

    /**
     * @type Array
     * @property otherActions
     */
    otherActions: null,
    
    /**
     * @type function
     * @property recordEditDialogOpener
     */
    recordEditDialogOpener: null,
    
    /**
     * record class
     * @cfg {Tine.Addressbook.Model.Contact} recordClass
     */
    recordClass: null,
    
    /**
     * @private
     */
    initComponent: function() {
        // init properties
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('Crm');
        this.recordEditDialogOpener = Tine.Addressbook.ContactEditDialog.openWindow;
        this.recordClass = Tine.Addressbook.Model.Contact;

        this.storeFields = Tine.Addressbook.Model.ContactArray;
        this.storeFields.push({name: 'relation'});   // the relation object           
        this.storeFields.push({name: 'relation_type'});
        
        // create delegates
        this.initStore = Tine.Crm.LinkGridPanel.initStore.createDelegate(this);
        this.initActions = Tine.Crm.LinkGridPanel.initActions.createDelegate(this);
        this.initGrid = Tine.Crm.LinkGridPanel.initGrid.createDelegate(this);
        //this.onUpdate = Tine.Crm.LinkGridPanel.onUpdate.createDelegate(this);

        this.initStore();
        this.initOtherActions();
        this.initActions();
        this.initGrid();

        // add contact type to "add" action
        this.actionAdd.contactType = 'customer';

        // init store stuff
        this.store.setDefaultSort('type', 'asc');
        
        Tine.Crm.Contact.GridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * init other actions and tbar (change contact type and contact search combo
     */
    initOtherActions: function() {
        this.actionChangeContactTypeCustomer = new Ext.Action({
            requiredGrant: 'editGrant',
            contactType: 'customer',
            text: this.app.i18n._('Customer'),
            tooltip: this.app.i18n._('Change type to Customer'),
            iconCls: 'contactIconCustomer',
            scope: this,
            handler: this.onChangeContactType
        });
        
        this.actionChangeContactTypeResponsible = new Ext.Action({
            requiredGrant: 'editGrant',
            contactType: 'responsible',
            text: this.app.i18n._('Responsible'),
            tooltip: this.app.i18n._('Change type to Responsible'),
            iconCls: 'contactIconResponsible',
            scope: this,
            handler: this.onChangeContactType
        });
    
        this.actionChangeContactTypePartner = new Ext.Action({
            requiredGrant: 'editGrant',
            contactType: 'partner',
            text: this.app.i18n._('Partner'),
            tooltip: this.app.i18n._('Change type to Partner'),
            iconCls: 'contactIconPartner',
            scope: this,
            handler: this.onChangeContactType
        });
        var otherActionItems = [
           this.actionChangeContactTypeCustomer,
           this.actionChangeContactTypeResponsible,
           this.actionChangeContactTypePartner
        ];
        this.otherActions = [new Ext.Action({
            text: this.app.i18n._('Change contact type'),
            requiredGrant: 'editGrant',
            disabled: true,
            menu: otherActionItems
        })];
        
        this.tbar = new Ext.Panel({
            layout: 'fit',
            items: [
                // TODO perhaps we could add an icon/button (i.e. edit-find.png) here
                new Tine.Crm.Contact.Combo({
                    contactsStore: this.store,
                    emptyText: this.app.i18n._('Search for Contacts to add ...')
                })
            ]
        });
    },
    
    /**
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                resizable: true,
            },
            columns: [            
                {id:'id', header: "id", width: 25, hidden: true },
                {id:'n_fileas', header: this.app.i18n._('Name'), width: 200, sortable: true, renderer: 
                    function(val, meta, record) {
                        var org_name           = Ext.isEmpty(record.data.org_name) === false ? record.data.org_name : ' ';
                        var n_fileas           = Ext.isEmpty(record.data.n_fileas) === false ? record.data.n_fileas : ' ';
                        var formated_return = '<b>' + Ext.util.Format.htmlEncode(n_fileas) + '</b><br />' + Ext.util.Format.htmlEncode(org_name);
                        
                        return formated_return;
                    }
                },
                {id:'contact_one', header: this.app.i18n._("Address"), dataIndex: 'adr_one_locality', width: 140, sortable: false, renderer: preferredAddressRender},
                {id:'tel_work', header: this.app.i18n._("Data"), width: 140, sortable: false, renderer: function(val, meta, record) {
                        var translation = new Locale.Gettext();
                        translation.textdomain('Crm');
                        var tel_work           = Ext.isEmpty(record.data.tel_work) === false ? translation._('Phone') + ': ' + record.data.tel_work : ' ';
                        var tel_cell           = Ext.isEmpty(record.data.tel_cell) === false ? translation._('Cellphone') + ': ' + record.data.tel_cell : ' ';
                        var formated_return = Ext.util.Format.htmlEncode(tel_work) + '<br/>' + Ext.util.Format.htmlEncode(tel_cell) + '<br/>';
                        return formated_return;
                    }
                }, {
                    id:'relation_type', 
                    header: this.app.i18n._("Role"), 
                    width: 150,
                    sortable: true,
                    renderer: Tine.Crm.Contact.typeRenderer,
                    editor: new Tine.Crm.Contact.TypeComboBox({
                        autoExpand: true,
                        blurOnSelect: true,
                        listClass: 'x-combo-list-small'
                    })
                }
            ]}
        );
    },
    
    /**
     * onclick handler for changeContactType
     */
    onChangeContactType: function(_button, _event) {
        var selectedRows = this.getSelectionModel().getSelections();
        
        for (var i = 0; i < selectedRows.length; ++i) {
            selectedRows[i].data.relation_type = _button.contactType;
        }
        
        this.store.fireEvent('dataChanged', this.store);
    },
    
    /**
     * update event handler for related contacts
     * 
     * TODO use generic function?
     */
    onUpdate: function(contact) {
        var response = {
            responseText: contact
        };
        contact = Tine.Tinebase.data.Record.setFromJson(response?.results, Tine.Addressbook.Model.Contact);
        
        Tine.log.debug('Tine.Crm.Contact.GridPanel::onUpdate - Contact has been updated:');
        Tine.log.debug(contact);
        
        // remove contact relations to prevent cyclic relation structure
        contact.data.relations = null;
        
        var myContact = this.store.getById(contact.id);
        if (myContact) {
            myContact.beginEdit();
            for (var p in contact.data) {
                myContact.set(p, contact.get(p));
            }
            myContact.endEdit();
            myContact.commit();
        } else {
            contact.data.relation_type = 'customer';
            this.store.add(contact);
        }
    }
});

/**
 * @namespace   Tine.Crm.Contact
 * @class       Tine.Crm.Contact.TypeComboBox
 * @extends     Ext.form.ComboBox
 * 
 * Contact type selection combobox
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Tine.Crm.Contact.TypeComboBox = Ext.extend(Ext.form.ComboBox, {
    /**
     * @cfg {bool} autoExpand Autoexpand comboBox on focus.
     */
    autoExpand: false,
    /**
     * @cfg {bool} blurOnSelect blurs combobox when item gets selected
     */
    blurOnSelect: false,
    
    displayField: 'label',
    valueField: 'relation_type',
    mode: 'local',
    triggerAction: 'all',
    lazyInit: false,
    forceSelection: true,
    allowBlank: false,
    
    //private
    initComponent: function() {
        
        const translation = new Locale.Gettext();
        translation.textdomain('Crm');
        
        Tine.Crm.Contact.TypeComboBox.superclass.initComponent.call(this);
        // always set a default
        if (!this.value) {
            this.value = 'customer';
        }
            
        this.store = new Ext.data.SimpleStore({
            fields: ['label', 'relation_type'],
            data: [
                    [translation._('Customer'), 'customer'],
                    [translation._('Partner'), 'partner'],
                    [translation._('Responsible'), 'responsible']
                ]
        });
        
        if (this.autoExpand) {
            this.lazyInit = false;
            this.on('focus', function(){
                this.selectByValue(this.getValue());
                this.onTriggerClick();
            });
        }
        
        if (this.blurOnSelect){
            this.on('select', function(){
                this.fireEvent('blur', this);
            }, this);
        }
    }
});
Ext.reg('leadcontacttypecombo', Tine.Crm.Contact.TypeComboBox);

/**
 * contact type renderer function
 * 
 * @param   string type
 * @return  contact type icon
 */
Tine.Crm.Contact.typeRenderer = function(type)
{
    var translation = new Locale.Gettext();
    translation.textdomain('Crm');
    
    switch ( type ) {
        case 'responsible':
            var iconClass = 'contactIconResponsible';
            var qTip = translation._('Responsible');
            break;
        case 'customer':
            var iconClass = 'contactIconCustomer';
            var qTip = translation._('Customer');
            break;
        case 'partner':
            var iconClass = 'contactIconPartner';
            var qTip = translation._('Partner');
            break;
    }
    
    var icon = '<img class="x-menu-item-icon contactIcon ' + iconClass + '" src="library/ExtJS/resources/images/default/s.gif" ext:qtip="' + Ext.util.Format.htmlEncode(qTip) + '"/>';
    
    return icon;
};

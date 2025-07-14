/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.Felamimail');

/**
 * Contact grid panel
 * 
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.ContactGridPanel
 * @extends     Tine.Addressbook.ContactGridPanel
 * 
 * <p>Contact Grid Panel</p>
 * <p>
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.ContactGridPanel
 */
Tine.Felamimail.ContactGridPanel = Ext.extend(Tine.Addressbook.ContactGridPanel, {

    hasDetailsPanel: false,
    hasFavoritesPanel: false,
    hasQuickSearchFilterToolbarPlugin: false,
    stateId: 'FelamimailContactGrid',
    
    gridConfig: {
        autoExpandColumn: 'n_fileas',
        enableDragDrop: false,
        clicksToEdit: 'auto',
        gridType: Ext.grid.EditorGridPanel,
    },
    
    /**
     * the message record with recipients
     * @type Tine.Felamimail.Model.Message
     */
    messageRecord: null,
    
    /**
     * felamimail app, needed for translations
     * @type Tine.Felamimail.Application
     */
    felamimailApp: null,
    
    
    /**
     * inits this cmp
     * @private
     */
    initComponent: function() {
        this.addEvents(
            /**
             * @event addcontacts
             * Fired when contacts are added
             */
            'addcontacts'
        );
        
        this.app = Tine.Tinebase.appMgr.get('Addressbook');
        this.felamimailApp = Tine.Tinebase.appMgr.get('Felamimail');
        
        Tine.Felamimail.ContactGridPanel.superclass.initComponent.call(this);
        
        this.grid.disableResponsiveLayout =  true;
        this.grid.on('rowdblclick', this.onRowDblClick, this);
        this.grid.on('cellclick', this.onCellClick, this);
        this.store.on('load', this.onContactStoreLoad, this);
    },
    
    /**
     * initialises the filter panel 
     * 
     * @param {Object} config
     */
    initFilterPanel: function(config) {
        this.defaultFilters = [
            {field: 'email_query', operator: 'contains', value: '@'}
        ];

        Tine.Felamimail.ContactGridPanel.superclass.initFilterPanel.call(this, {
            useQuickFilter: false
        });
    },
    
    /**
     * returns array with columns
     * 
     * @return {Array}
     */
    getColumns: function() {
        const columns = Tine.Felamimail.ContactGridPanel.superclass.getColumns.call(this);
        
        columns.push({
            id: 'selected_email',
            header: this.felamimailApp.i18n._('Selected Email'),
            renderer: this.preferredEmailRenderer.createDelegate(this),
            width: 400,
            hidden: false
        })
        
        // hide all columns except name/company/email/email_home (?)
        Ext.each(columns, (column) => {
            if (!['org_name', 'selected_email'].includes(column.id)) {
                column.hidden = true;
            }
        });
        
        this.radioTpl = new Ext.XTemplate('<input',
            ' name="' + this.id + '_{id}"',
            ' value="{type}"',
            ' type="radio"',
            ' autocomplete="off"',
            ' class="x-form-radio x-form-field"',
            ' {checked}',
        '>');
        
        Ext.each(['To', 'Cc', 'Bcc', 'None'], function(type) { // i18n._('None')
            columns.push({
                header: this.felamimailApp.i18n._(type),
                dataIndex: Ext.util.Format.lowercase(type),
                width: 50,
                hidden: false,
                renderer: this.typeRadioRenderer.createDelegate(this, [type], 0)
            });
            
        }, this);
        
        return columns;
    },
    
    /**
     * render type radio buttons in grid
     *
     * @param field
     * @param cell
     * @param contact
     * @return {String}
     */
    preferredEmailRenderer: function(field, cell, contact) {
        const token = this.getSelectedToken(contact).token;
        if (!token) return '';
        const block =  document.createElement('span');
        block.className = 'tinebase-contact-link';
        block.innerHTML = Tine.Felamimail.ContactSearchCombo.prototype.renderEmailAddressAndIcon(token);
        return block.outerHTML;
    },
    
    /**
     * render type radio buttons in grid
     *
     * @param {String} type
     * @param {String} value
     * @param {Object} metaData
     * @param {Object} record
     * @param {Number} rowIndex
     * @param {Number} colIndex
     * @param {Store} store
     * @return {String}
     */
    typeRadioRenderer: function(type, value, metaData, record, rowIndex, colIndex, store) {
        if (! record.hasEmail()) return '';
        const lowerType = Ext.util.Format.lowercase(type);

        return this.radioTpl.apply({
            id: record.id,
            type: lowerType,
            checked:  this.getSelectedToken(record).type === lowerType ? 'checked' : '',
        });
    },

    getSelectedToken(contact) {
        const defaultToken = Tine.Felamimail.GridPanelHook.prototype.getRecipientTokenFromContact(contact);
        const result = {
            token: null,
            type: 'none',
        };
        ['to', 'cc', 'bcc'].forEach((type) => {
            if (Ext.isArray(this.messageRecord.data[type])) {
                const existToken = _.find(this.messageRecord.data[type], (token) => {
                    return token.contact_record.id === contact.id;
                });
                if (existToken) {
                    result.token = existToken;
                    result.type = type;
                }
            }
        });
        if (!result.token) {
            const existToken = _.find(this.updatedTokens,  (token) => {
                return token.contact_record.id === contact.id;
            });
            result.token = existToken ?? defaultToken;
        }
        return result;
    },
    
    /**
     * called after a new set of Records has been loaded
     *
     * @param  {Ext.data.Store} this.store
     * @return {Void}
     * @param store
     * @param records
     * @param options
     */
    onContactStoreLoad: function(store, records, options) {
        if (Ext.isObject(this.messageRecord)) {
            _.each(records, (contact) => {
                const existData = this.getSelectedToken(contact);
                if (existData.token) this.setTypeRadio(contact, existData.type);
            })
        }
    },
    
    /**
     * cell click handler -> update recipients in record
     * 
     * @param {Grid} grid
     * @param {Number} row
     * @param {Number} col
     * @param {Event} e
     */
    onCellClick: function (grid, row, col, e) {
        const contact = this.store.getAt(row);
        const typeToSet = this.grid.getColumnModel().getDataIndex(col);
        if (typeToSet === 'selected_email') {
            let i = 0;
            let target = e.target;
            while (i < 7) {
                if (target.className.includes('selected_email')) break;
                target = target?.parentNode;
                i++;
            }
            const position = Ext.fly(target).getXY();
            position[1] = position[1] + Ext.fly(target).getHeight();
            const emailFields = Tine.Addressbook.Model.EmailAddress.prototype.getEmailFields();
            const items = [];
            _.map(emailFields, (field) => {
                const fieldName = field.fieldName;
                if (!contact.data[fieldName]) return;
                const token = Tine.Felamimail.GridPanelHook.prototype.getRecipientTokenFromContact(contact, fieldName);
                if (!token) return;
                items.push(new Ext.Action({
                    text: Tine.Felamimail.ContactSearchCombo.prototype.renderEmailAddressAndIcon(token),
                    iconCls: target.textContent.includes(token.email) ? 'action_enable' : '',
                    handler: async (item) => {
                        if (!this.updatedTokens) this.updatedTokens = [];
                        const existToken = _.find(this.updatedTokens,  (token) => {
                            return token.contact_record.id === contact.id;
                        });
                        if (existToken) this.updatedTokens.remove(existToken);
                        this.updatedTokens.push(token);
                        
                        this.updateRecipients(contact, null, token);
                        this.grid.getView().refresh();
                    },
                }));
            });
            const contextMenu = new Ext.menu.Menu({items: items});
            contextMenu.showAt(position);
        }
        if (['to', 'cc', 'bcc', 'none'].indexOf(typeToSet) === -1) {
            // some other column has been clicked
            return;
        }
        
        if (!contact.hasEmail() && typeToSet !== 'none') {
            this.setTypeRadio(contact, 'none');
        } else {
            this.setTypeRadio(contact, typeToSet);
            this.updateRecipients(contact, typeToSet);
        }
    },
    
    /**
     * update recipient
     *
     * @param {Tine.Addressbook.Model.Contact} contact
     * @param {String} typeToSet
     * @param tokenToSet
     */
    updateRecipients: function (contact, typeToSet = null, tokenToSet = null) {
        const existData = this.getSelectedToken(contact);
        if (!existData.token || existData.type === typeToSet) return;
        if (!typeToSet) typeToSet = existData.type;
        if (!tokenToSet) tokenToSet = existData.token;
        
        if (existData.type !== 'none') this.messageRecord.data[existData.type].remove(existData.token);
        if (typeToSet !== 'none') this.messageRecord.data[typeToSet].push(tokenToSet);

        Tine.log.debug('Tine.Felamimail.ContactGridPanel::updateRecipients() - adding email ' + existData.token.email + ' to ' + typeToSet);
    },
    
    /**
     * update type radio buttons dom
     * 
     * @param {Array} records of type Tine.Addressbook.Model.Contact
     * @param {String} type
     */
    setTypeRadio: function(records, type) {
        const rs = [].concat(records);
    
        Ext.each(rs, function(r) {
            if (r.hasEmail() || type === 'none') {
                Ext.select('input[name=' + this.id + '_' + r.id + ']', this.grid.el).each(function(el) {
                    el.dom.checked = type === el.dom.value;
                });
                this.updateRecipients(r, type);
            }
        }, this);
    },
    
    /**
     * Return CSS class to apply to rows depending upon email set or not
     * 
     * @param {Tine.Addressbook.Model.Contact} record
     * @param {Integer} index
     * @return {String}
     */
    getViewRowClass: function(record, index) {
        var className = '';
        
        if (! record.hasEmail()) {
            className = 'felamimail-no-email';
        }
        
        return className;
    },
    
    /**
     * @private
     */
    initActions: function() {
        this.actions_addAsTo = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.felamimailApp.i18n._('Add as "To"'),
            disabled: true,
            iconCls: 'action_add',
            actionUpdater: this.updateRecipientActions,
            handler: this.onAddContact.createDelegate(this, ['to']),
            allowMultiple: true,
            scope: this
        });

        this.actions_addAsCc = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.felamimailApp.i18n._('Add as "Cc"'),
            disabled: true,
            iconCls: 'action_add',
            actionUpdater: this.updateRecipientActions,
            handler: this.onAddContact.createDelegate(this, ['cc']),
            allowMultiple: true,
            scope: this
        });

        this.actions_addAsBcc = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.felamimailApp.i18n._('Add as "Bcc"'),
            disabled: true,
            iconCls: 'action_add',
            actionUpdater: this.updateRecipientActions,
            handler: this.onAddContact.createDelegate(this, ['bcc']),
            allowMultiple: true,
            scope: this
        });
        
        this.actions_setToNone = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.felamimailApp.i18n._('Remove from recipients'),
            disabled: true,
            iconCls: 'action_delete',
            actionUpdater: this.updateRecipientActions,
            handler: this.onAddContact.createDelegate(this, ['none']),
            allowMultiple: true,
            scope: this
        });
        
        //register actions in updater
        this.actionUpdater.addActions([
            this.actions_addAsTo,
            this.actions_addAsCc,
            this.actions_addAsBcc,
            this.actions_setToNone
        ]);
    },
    
    /**
     * updates context menu
     * 
     * @param {Ext.Action} action
     * @param {Object} grants grants sum of grants
     * @param {Object} records
     */
    updateRecipientActions: function(action, grants, records) {
        if (records.length > 0) {
            var emptyEmails = true;
            for (var i=0; i < records.length; i++) {
                if (records[i].hasEmail()) {
                    emptyEmails = false;
                    break;
                }
            }
            
            action.setDisabled(emptyEmails);
        } else {
            action.setDisabled(true);
        }
    },
    
    /**
     * on add contact -> fires addcontacts event and passes rows + type
     * 
     * @param {String} type
     */
    onAddContact: function(type) {
        var sm = this.grid.getSelectionModel(),
            selectedRows = sm.getSelections();
            
        this.setTypeRadio(selectedRows, type);

        // search contacts if all pages are selected (filter select)
        if (sm.isFilterSelect) {
            this['AddressLoadMask'] = new Ext.LoadMask(Ext.getBody(), {msg: this.felamimailApp.i18n._('Loading Email Addresses')});
            this['AddressLoadMask'].show();
            
            var contact = null;
            Tine.Addressbook.searchContacts(sm.getSelectionFilter(), null, function(response) {
                Ext.each(response.results, function(contactData) {
                    contact = new Tine.Addressbook.Model.Contact(contactData);
                    this.updateRecipients(contact, type);
                }, this);
                
                this['AddressLoadMask'].hide();
            }.createDelegate(this));
        }
    },
    
    // /**
    //  * row doubleclick handler
    //  * 
    //  * @param {} grid
    //  * @param {} row
    //  * @param {} e
    //  */
    // onRowDblClick: function(grid, row, e) {
    //     this.onAddContact('to');
    // }, 
    
    /**
     * returns rows context menu
     * 
     * @return {Ext.menu.Menu}
     */
    getContextMenu: function() {
        if (! this.contextMenu) {
            var items = [
                this.actions_addAsTo,
                this.actions_addAsCc,
                this.actions_addAsBcc,
                this.actions_setToNone
            ];
            this.contextMenu = new Ext.menu.Menu({
                items: items,
                plugins: [{
                    ptype: 'ux.itemregistry',
                    key:   'Tinebase-MainContextMenu'
                }]
            });
        }
        return this.contextMenu;
    }
});

Ext.reg('felamimailcontactgrid', Tine.Felamimail.ContactGridPanel);

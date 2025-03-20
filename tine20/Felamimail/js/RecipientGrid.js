/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.RecipientGrid
 * @extends     Ext.grid.EditorGridPanel
 * 
 * <p>Recipient Grid Panel</p>
 * <p>grid panel for to/cc/bcc recipients</p>
 * <pre>
 * </pre>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.RecipientGrid
 */
Tine.Felamimail.RecipientGrid = Ext.extend(Ext.grid.EditorGridPanel, {
    
    /**
     * @private
     */
    cls: 'felamimail-recipient-grid',
    
    /**
     * the message record
     * @type Tine.Felamimail.Model.Message
     * @property record
     */
    record: null,
    
    /**
     * message compose dlg
     * @type Tine.Felamimail.MessageEditDialog
     */
    composeDlg: null,
    
    /**
     * @type Ext.Menu
     * @property contextMenu
     */
    contextMenu: null,
    
    /**
     * @type Ext.data.SimpleStore
     * @property store
     */
    store: null,
    
    /**
     * @cfg {Boolean} autoStartEditing
     */
    autoStartEditing: false,
    
    /**
     * @cfg {String} autoExpandColumn
     * auto expand column of grid
     */
    autoExpandColumn: 'address',
    autoExpandMax : 2000,
    
    /**
     * @cfg {Number} clicksToEdit
     * clicks to edit for editor grid panel
     */
    clicksToEdit: 2,
    
    /**
     * @cfg {Number} numberOfRecordsForFixedHeight
     */
    numberOfRecordsForFixedHeight: 6,

    /**
     * @cfg {Boolean} header
     * show header
     */
    header: false,
    
    /**
     * @cfg {Boolean} border
     * show border
     */
    border: false,
    
    /**
     * @cfg {Boolean} deferredRender
     * deferred rendering
     */
    deferredRender: false,
    
    forceValidation: true,

    skipAutoCheckboxSelection: true,
    enableDrop: true,
    ddGroup: 'recipientDDGroup',
    
    /**
     * options are saved in onAfterEdit
     * 
     * @type Ext.data.Record
     */
    lastEditedRecord: null,
    
    /**
     * grid view config
     * - do not mark records as dirty
     * 
     * @type Object
     */
    viewConfig: {
        markDirty: false
    },

    massMailingMode: false,

    /**
     * @private
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Felamimail');
        this.initialLoad = true;
        this.initStore();
        this.initColumnModel();
        this.initActions();
        this.sm = new Ext.grid.RowSelectionModel();
        
        Tine.Felamimail.RecipientGrid.superclass.initComponent.call(this);
        
        this.on('rowcontextmenu', this.onCtxMenu, this);
        // this is relayed by the contact search combo
        this.on('contextmenu', this.onCtxMenu.createDelegate(this, [this, null], 0), this);
        this.on('cellclick', this.onCellClick, this);
        this.on('beforeedit', this.onBeforeEdit, this);
        this.on('afteredit', this.onAfterEdit, this);
        
        this.postalSubscriptions = [];
        this.postalSubscriptions.push(postal.subscribe({
            channel: "recordchange",
            topic: 'Addressbook.*.*',
            callback: _.bind(this.onContactUpdate, this)
        }));
    },
    
    onContactUpdate: async function (contactData) {
        if (! contactData.email) return;
        const existingRecords = this.store.queryBy(function (item) {
            const token = item.get('address');
            return contactData.id === token?.contact_record?.id || this.lastEditedRecord?.id === item.id;
        }, this);
        
        existingRecords.items.forEach(async function (record) {
            const existingToken = record.get('address');
            const token = {...existingToken};
            token.type = contactData.type;
            token.n_fileas = contactData.n_fileas;
            token.email_type_field = existingToken.email_type_field;
            token.email = contactData[token.email_type_field];
            token.name = contactData.n_fn;
            token.contact_record = contactData;
            await this.updateRecipientsToken(record, [token], record.get('type'));
        }, this);
        // reload store otherwise the recipients will not be updated
        this.searchCombo.store.load({
            params: this.searchCombo.getParams('')
        });
    },
    
    /**
     * show context menu
     * 
     * @param {Tine.Felamimail.RecipientGrid} grid
     * @param {Number} row
     * @param {Event} e
     */
    onCtxMenu: async function (grid, row, e) {
        const targetInput = e.getTarget('input[type=text]', 1, true);
        if (targetInput) return;
    
        const activeRow = (row === null) ? ((this.activeEditor) ? this.activeEditor.row : 0) : row;
        e.stopEvent();

        const sm = grid.getSelectionModel();
        if (! sm.isSelected(activeRow)) {
            sm.selectRow(activeRow);
        }
    },
    
    /**
     * init store
     * @private
     */
    initStore: async function () {
        if (!this.record) {
            this.initStore.defer(200, this);
            return false;
        }
    
        this.store = new Ext.data.SimpleStore({
            fields: ['type', 'address']
        });
        
        await this.initRecord().then(() => {
            this.syncRecipientsToStore(['to', 'cc', 'bcc'], this.record);
        })
        this.initialLoad = false;
    
        this.store.on('update', this.onUpdateStore, this);
        this.store.on('add', this.onAddStore, this);
    },
    
    /**
     * init cm
     * @private
     */
    initColumnModel: function() {
        this.searchCombo = new Tine.Felamimail.ContactSearchCombo({
            lazyInit: false,
            listEmptyText: false,
            listeners: {
                scope: this,
                specialkey: this.onSearchComboSpecialkey,
                select: this.onSearchComboSelect,
                render: (combo) => {
                    combo.getEl().on('paste', async (e, dom) => {
                        e = e.browserEvent;
                        const clipboardData = e.clipboardData || window.clipboardData;
                        const pastedData = clipboardData.getData('Text');
                        // replace new line to comma , and then remove start/ending special chars
                        const value = _.compact(_.map(pastedData.replace(/\r?\n+/g, '\n').split('\n'), (email) => {
                            return String(email).replace(/^[,;]+|[,;]+$/, '');
                        })).join(',').replace(/^[,;]+|[,;]+$/, '');

                        if (!this.loadMask) {
                            this.loadMask = new Ext.LoadMask(Ext.getBody(), {msg: this.app.i18n._('Loading Mail Addresses')});
                        }
                        this.loadMask.show();
           
                        const contacts = await Tine.Tinebase.common.findContactsByEmailString(value);
                        this.searchCombo.setRawValue('');
                        await this.updateRecipientsToken(null, contacts);

                        this.loadMask.hide();
                    })
                },
                blur: async (combo) => {
                    const value = combo.getRawValue();

                    Tine.log.debug('Tine.Felamimail.MessageEditDialog::onSearchComboBlur() -> current value: ' + value);
    
                    if (value !== '') {
                        const sm = this.getSelectionModel();
                        const records = sm.getSelections();
                        const oldRecord = this.lastEditedRecord ?? records[0];
                        const recipients = await Tine.Tinebase.common.findContactsByEmailString(value);
                        await this.updateRecipientsToken(oldRecord, recipients, null, true);
                    }
                }
            },
            validate: (record) => {
                return this.validateRecipientToken(record?.data).isValid;
            },
        });
        
        this.RecipientTypeCombo = new Ext.form.ComboBox({
            typeAhead     : false,
            triggerAction : 'all',
            lazyRender    : true,
            editable      : false,
            mode          : 'local',
            value         : null,
            forceSelection: true,
            lazyInit      : false,
            store         : [
                ['to',  this.app.i18n._('To:')],
                ['cc',  this.app.i18n._('Cc:')],
                ['bcc', this.app.i18n._('Bcc:')]
            ],
            listeners: {
                focus: function(combo) {
                    combo.onTriggerClick();
                },
                select: function(combo, record) {
                    this.fireEvent('blur', this);
                }
            }
        });
    
        this.cm = new Ext.grid.ColumnModel([
            {
                resizable: true,
                id: 'type',
                width: 104,
                menuDisabled: true,
                header: 'type',
                renderer: (value) => {
                    let result = '';
                    if (this.record.get('massMailingFlag')) {
                        const qtip = Ext.util.Format.htmlEncode(this.app.i18n._('A separate mail is send to each recipient.'));
                        return `<div ext:qtip="${qtip}">${this.app.i18n._('Mass')}:</div>`;
                    } else {
                        const qtip = Ext.util.Format.htmlEncode(this.app.i18n._('Click here to set To/CC/BCC.'));
                        const type = Ext.util.Format.capitalize(value);

                        result = '<div ext:qtip="' + qtip + '">' + Ext.util.Format.htmlEncode(`${this.app.i18n._hidden(type)}:`) + '</div>';
                        return Tine.Tinebase.common.cellEditorHintRenderer(result);
                    }
                },
                editor: this.RecipientTypeCombo
            }, {
                resizable: true,
                menuDisabled: true,
                id: 'address',
                header: 'address',
                editor: this.searchCombo,
                //columnWidth: 1,
                renderer: (token) => {
                    if (! token?.email) return '';
                    const block =  document.createElement('span');
                    block.className = 'tinebase-contact-link';
                    block.innerHTML = this.searchCombo.renderEmailAddressAndIcon(token);
                    return block.outerHTML;
                },
            }
        ]);
    },
    
    /**
     * specialkey is pressed in search combo
     * 
     * @param {Combo} combo
     * @param {Event} e
     */
    onSearchComboSpecialkey: async function (combo, e) {
        const value = combo.getRawValue();
        const key = e.getKey();
        
        Tine.log.debug(`Tine.Felamimail.MessageEditDialog::onSearchComboSpecialkey() -> key : ${key}, current value: ${value}`);
        
        if (!this.activeEditor) return;

        switch (key) {
            case e.BACKSPACE:
                if (value === '' && this.store.getCount() > 1 && this.activeEditor.row > 0) {
                    this.store.remove(this.activeEditor.record);
                    this.activeEditor.row -= 1;
                    const record = this.store.getAt(this.activeEditor.row);
                    this.activeEditor.record.set('address', record.data.address);
                    const selModel = this.getSelectionModel();
                    if (! selModel.isSelected(this.activeEditor.row)) {
                        selModel.selectRow(this.activeEditor.row);
                    }
                    this.setFixedHeight(false);
                    this.ownerCt.doLayout();
                    this.startEditing.defer(100, this, [this.activeEditor.row, this.activeEditor.col]);
                }
                break;
            case e.ESC:
                // TODO should ESC close the compose window if search combo is already empty?
//            if (value == '') {
//                this.fireEvent('specialkey', this, e);
//            }
                if (this.activeEditor.startValue === '') {
                    this.startEditing.defer(100, this, [this.activeEditor.row, this.activeEditor.col]);
                }
                break;
            case e.TAB:
            case e.ENTER:
                // jump to subject if we are in the last row, and it is empty OR TAB was pressed
                if (combo?.selectedRecord) {
                    await this.onSearchComboSelect(combo, combo.selectedRecord);
                }
                if (this.store.getCount() === this.activeEditor?.row + 1) {
                    Tine.log.debug(`Tine.Felamimail.MessageEditDialog::onSearchComboSpecialkey() -> last row`);
        
                    if (value === '') {
                        this.fireEvent('specialkey', combo, e);
                    }
                }
                break;
        }
    
        return true;
    },
    
    onSearchComboSelect: async function (combo, value , startValue) {
        Tine.log.debug('Tine.Felamimail.MessageEditDialog::onSearchComboSelect()');
    
        if (!value || !value?.data) {
            this.onDelete();
            return;
        }
        const token = value.data;
        await this.updateRecipientsToken(this.lastEditedRecord, [token], null , true);
        combo.selectedRecord = null;
    },
    
    /**
     * validate recipient token
     *
     * default validation:
     * - all contacts are valid
     * 
     * validation in mass mailing mode: 
     * - all contacts are valid, but the list contacts should show the tip accordingly
     * 
     * @param token
     */
    validateRecipientToken(token) {
        let result = {isValid: true, tip: 'skip checking token'};
        if (!token || !this.massMailingMode) {
            return  result;
        }
        // in mass mailing mode, list should be valid to be displayed in searchContactCombo,
        if (token?.emails) {
            return {isValid: true, tip: this.app.i18n._('Send message to individual list members instead')};
        }
        return result;
    },
    
    /**
     * start editing (check if message compose dlg is saving/sending first)
     * 
     * @param {} row
     * @param {} col
     */
    startEditing: function(row, col) {
        this.lastEditedRecord = this.store.getAt(row);
        
        if (this.massMailingMode && col === 0) return;
        if (! this.composeDlg || ! this.composeDlg.saving) {
            Tine.Felamimail.RecipientGrid.superclass.startEditing.apply(this, arguments);
        }
    },
    
    preEditValue : function(r, field){
        const value = r.data[field];
        if (value?.email) {
            return this.autoEncode && Ext.isString(value.email) ? Ext.util.Format.htmlDecode(value.email) : value.email;
        } else {
            return Tine.Felamimail.RecipientGrid.superclass.preEditValue.apply(this, arguments);
        }
    },
    
    /**
     * on contact click
     *
     * @param e
     */
    onContactClick : async function (e) {
        let target = e.target;
        
        //skip non tinebase-contact-link target
        // Traverse up the DOM hierarchy until reaching the top-level parent
        let isContactLink = false;
        let i = 0;
        while (i < 7) {
            if (target.className.includes('tinebase-contact-link') ||
                target?.parentNode?.className.includes('tinebase-contact-link')) {
                isContactLink = true;
            }
            target = target?.parentNode;
            if (target.className.includes('x-grid3-col-address')) {
                break;
            }
            i++;
        }
        if (!isContactLink) return;
        
        const row = this.getView().findRowIndex(target);
        const col = this.getView().findCellIndex(target);
        const record = this.store.getAt(row);
        const position = Ext.fly(target).getXY();
        position[1] = position[1] + Ext.fly(target).getHeight();
        const targetInput = e.getTarget('input[type=text]', 1, true);
        const existingToken = record.get('address');
        this.lastEditedRecord = record;
        
        if (targetInput || ! record || record.get('address') === '' || col !== 1 || row === false || ! existingToken?.email) {
            return;
        }
        
        const contactCtxMenu = await Tine.Tinebase.tineInit.getEmailContextMenu(targetInput, existingToken.email, existingToken.name, existingToken.type);

        const adb = Tine.Tinebase.appMgr.get('Addressbook');
        if (adb) {
            let index = 0;
            switch (existingToken.type) {
                case 'mailingList':
                case 'group':
                case 'list':
                    const item = new Ext.Action({
                        text: this.app.i18n._('Resolve to single contact'),
                        iconCls: '',
                        handler: async (item) => {
                            const members = record?.data?.address?.emails;
                            await this.updateRecipientsToken(record, members, record.get('type'), true);
                        },
                    });
                    contactCtxMenu.insert(index, item);
                    index ++;
                    break;
                default :
                    const {results : tokens} = await Tine.Addressbook.searchRecipientTokensByEmailArrays([existingToken.email], [existingToken.name]);
                    const options = [];
                    const emailFields = Tine.Addressbook.Model.EmailAddress.prototype.getEmailFields();
                    _.each(tokens, (token) => {
                        const selected = token.email === existingToken.email;
                        const emailField = emailFields.find((f) => {return f.fieldName === token.email_type_field});

                        if (options.includes(emailField.fieldName)) return;
                        const emailItem = new Ext.Action({
                            text: Tine.Felamimail.ContactSearchCombo.prototype.renderEmailAddressAndIcon(token),
                            iconCls: selected ? 'action_enable' : '',
                            handler: async (item) => {
                                if (selected) return;
                                const newSelectedToken = _.find(tokens, (token) => {
                                    return item.text.includes(token.email)
                                });
                                await this.updateRecipientsToken(record, [newSelectedToken], record.get('type'));
                            },
                        });
                        
                        contactCtxMenu.insert(index, emailItem);
                        options.push(emailField.fieldName);
                        index++;
                    });
                    break;
            }
            if (index > 0) {
                contactCtxMenu.insert(index, '-');
            }
        }
        contactCtxMenu.addMenuItem(this.action_remove);
        
        if (!this.isDbClick) {
            contactCtxMenu.showAt(position);
        }
    },
    
    /**
     * resolve recipient token
     *
     * default:
     * - when the list email is not empty. it should not be expanded, user should resolve it to members manually
     * - when the list email is empty, it should be resolve to members automatically.
     *
     * mass mailing mode:
     * - all the list email should be removed and expanded to members automatically
     *
     * @param token
     */
    resolveRecipientToken(token) {
        if (token === '') return [];
        if (!this.validateRecipientToken(token).isValid) return [];
        if (token?.emails) {
            if (token.email === '' || this.massMailingMode) {
                // list without email and emails should be ignored
                if (token.emails?.length === 0) return [];
                // mass mailing mode should always expand the list
                const members = [];
                const contactsToResolve = [];
                token.emails.forEach((member) => {
                    const result = this.validateRecipientToken(member);
                    member.type = token.type + 'Member';
                    member.note = this.app.i18n._('from') + ' ' + token.name;
                    if (result?.isValid) {
                        members.push(member);
                    } else {
                        contactsToResolve.push({result: result, token: member});
                    }
                })
                this.showInvalidContactDialog(contactsToResolve, ['Ok']);
                return members;
            }
        }
        return [token];
    },
    
    // private
    onCellDblClick: function onCellClick(g, row, col, e) {
        this.isDbClick = true;
        this.startEditing(row, col);
    },
    
    // private
    onCellClick: async function onCellClick(g, row, col, e) {
        this.isDbClick = false;
        
        if (col === 0) {
            this.startEditing(row, col);
        } 
        if (col === 1) {
            await this.onContactClick(e);
        }
    },
    
    // private
    onMouseDown: async function (e, target) {
        const row = this.getView().findRowIndex(target);
        if (row === false) return;
    
        const col = this.getView().findCellIndex(target);
        const record = this.store.getAt(row);
        const activeRow = (row === null) ? ((this.activeEditor) ? this.activeEditor.row : 0) : row;
        const selModel = this.getSelectionModel();
    
        if (!selModel.isSelected(activeRow)) {
            selModel.selectRow(activeRow);
        }
    
        if (col === 1 && record.get('address') === '') {
            this.startEditing.defer(50, this, [row, col]);
        }
    },
    
    /**
     * init actions / ctx menu
     * @private
     */
    initActions: function() {
        this.action_remove = new Ext.Action({
            text: i18n._('Remove'),
            handler: this.onDelete,
            iconCls: 'action_delete',
            scope: this,
            disable: false
        });
        
        this.contextMenu = new Ext.menu.Menu({
            items:  this.action_remove
        });
    },

    /**
     * start editing after render
     * @private
     */
    afterRender: async function () {
        Tine.Felamimail.RecipientGrid.superclass.afterRender.call(this);
        // kill x-scrollers
        this.el.child('div[class=x-grid3-scroller]').setStyle('overflow-x', 'hidden');
    },

    /**
     * set grid to fixed height if it has more than X records
     *  
     * @param {} doLayout
     */
    setFixedHeight: function (doLayout) {
        if (this.store.getCount() > this.numberOfRecordsForFixedHeight) {
            this.setHeight(155);
        } else {
            this.setHeight(this.store.getCount()*24 + 1);
        }

        if (doLayout && doLayout === true) {
            this.ownerCt.doLayout();
        }
    },
    
    /**
     * store has been updated
     * 
     * @param {} store
     * @param {} record
     * @param {} operation
     * @private
     */
    onUpdateStore: function(store, record, operation) {
        this.syncRecipientsToRecord();
    },
    
    /**
     * on add event of store
     * 
     * @param {} store
     * @param {} records
     * @param {} index
     */
    onAddStore: function(store, records, index) {
        this.syncRecipientsToRecord();
    },
    
    /**
     * sync grid with record
     * -> update record to/cc/bcc
     */
    syncRecipientsToRecord: function() {
        Tine.Tinebase.common.assertComparable(this.record.data.to);
        Tine.Tinebase.common.assertComparable(this.record.data.cc);
        Tine.Tinebase.common.assertComparable(this.record.data.bcc);
        
        // update record recipient fields
        this.record.set('to', Tine.Tinebase.common.assertComparable([]));
        this.record.set('cc', Tine.Tinebase.common.assertComparable([]));
        this.record.set('bcc', Tine.Tinebase.common.assertComparable([]));
        
        // update record recipient fields
        this.store.each(function(record, index){
            const addressData = record.get('address') ?? '';
            const type = record.get('type') ?? '';
            
            if (type !== '' && addressData !== '') {
                this.record.data[type].push(addressData);
            }
        }, this);
    },
    
    /**
     * resolve recipients to token
     * 
     */
    initRecord: async function() {
        const promises = [];
        ['to', 'cc', 'bcc'].forEach((type) => {
            const promise = new Promise(async (resolve) => {
                let contacts = this.record.data[type];

                await Promise.all(contacts.map(async (contact) => {
                    if (_.isString(contact) && (contact.includes(',') || contact.includes(';'))) {
                        return await Tine.Tinebase.common.findContactsByEmailString(contact);
                    } else {
                        return contact;
                    }
                })).then((result) => {
                    contacts = result.flat();
                });

                let emails = _.filter(contacts, (addressData) => {return _.isString(addressData)});
                if (emails.length > 0) {
                    emails = _.join(emails, ', ');
                    const resolvedContacts = await Tine.Tinebase.common.findContactsByEmailString(emails);
                    _.each(contacts, (addressData, idx) => {
                        if (_.isString(addressData)) {
                            contacts[idx] = _.find(resolvedContacts, (contact) => {
                                return addressData.includes(contact.email);
                            });
                        }
                    })
                }
                this.record.data[type] = contacts;
                resolve();
            });
            promises.push(promise);
        })
        await Promise.all(promises);
    },

    /**
     * sync grid with record
     * -> update store
     *
     * @param {Array} types
     * @param {Tine.Felamimail.Model.Message} record
     * @param {Boolean} setHeight
     * @param {Boolean} clearStore
     */
    syncRecipientsToStore: function (types, record, setHeight, clearStore) {
        if (clearStore) {
            this.store.removeAll(true);
        }
        
        record = record || this.record;
        
        let contacts = [];
        
        types.forEach((type) => {
            const data = record.data[type].map((addressData) => new Ext.data.Record({type: type, 'address': addressData}));
            contacts = contacts.concat(data);
        });
        
        this.store.add(contacts);
        this.store.sort('address');
    
        if (setHeight && setHeight === true) {
            this.setFixedHeight(true);
        }
        
        this.addEmptyRowAndDoLayout(contacts.length === 0);
    },

    // save selected record for usage in onAfterEdit
    onEditComplete: async function (ed, value, startValue) {
        const row = ed.row;
        const col = ed.col;
        const rowRecord = this.store.getAt(row);
        let contact = rowRecord?.data?.address ?? '';
        rowRecord.selectedRecord = contact ?? null;
        
        if (col === 1) {
            if (contact !== '' && startValue === contact.email) return;
            value = contact;
        }
        
        Tine.Felamimail.RecipientGrid.superclass.onEditComplete.call(this, ed, value, startValue);
    },
    
  

    /**
     * after edit
     * 
     * @param {} o
     */
    onAfterEdit: async function (o) {
        Tine.log.debug('Tine.Felamimail.MessageEditDialog::onAfterEdit()');
        Tine.log.debug(o);
        
        if (o.field === 'address') {
            Ext.fly(this.getView().getCell(o.row, o.column)).removeClass('x-grid3-td-address-editing');
            if (o.originalValue !== '' && o.value === '') {
                this.store.remove(o.record);
                this.setFixedHeight(true);
            }

            if (o.originalValue !== '') {
                this.addEmptyRowAndDoLayout(true);
            }
        }
    
        if (o.field === 'type') {
            if (o.value !== '' && o.record.get('address') !== '') {
                const contact = o.record.get('address');
                await this.updateRecipientsToken(o.record, [contact], o.value);
            }
        }
    },
    
    /**
     * delete handler
     */
    onDelete: function(btn, e) {
        const sm = this.getSelectionModel();
        const records = sm.getSelections();
        Ext.each(records, function(record) {
            if (record.get('address') !== '' && this.store.getCount() > 0) {
                this.store.remove(record);
                this.store.fireEvent('update', this.store);
            }
        }, this);
        
        this.setFixedHeight(true);
        this.addEmptyRowAndDoLayout(true);
    },
    
    /**
     * on before edit
     * 
     * @param {} o
     */
    onBeforeEdit: function(o) {
        if (this.record.get('massMailingFlag') && o.column === 0) {
            o.cancel = true;
            return;
        }
        this.getView().el.select('.x-grid3-td-address-editing').removeClass('x-grid3-td-address-editing');
        Ext.fly(this.getView().getCell(o.row, o.column)).addClass('x-grid3-td-address-editing');
    },
    
    /**
     * add recipients to grid store
     */
    updateRecipientsToken: async function (oldContactRecord = null, recipientTokens = null, type, autoStartEdit = false, addEmptyRow = true) {
        if (recipientTokens?.length === 0 && recipientTokens !== '') return;
        // resolve recipients
        if (!recipientTokens) {
            if (!oldContactRecord?.data?.address) return;
            recipientTokens = this.resolveRecipientToken(oldContactRecord.data.address);
        } else {
            recipientTokens = recipientTokens.reduce((resolvedTokens, token) => { return resolvedTokens.concat(this.resolveRecipientToken(token)); }, []);
        }
        
        const sm = this.getSelectionModel();
        const records = sm.getSelections();
        const oldRecord = this.store.indexOf(oldContactRecord) > -1 ? oldContactRecord : records[0];
        const index = oldRecord ? this.store.indexOf(oldRecord) : -1;
        
        if (!type) {
            type = this.activeEditor ? this.activeEditor.record.data.type 
                : this.lastActiveEditor ? this.lastActiveEditor.record.data.type 
                : this.lastEditedRecord ? this.lastEditedRecord.data.type
                : 'to';
        }
        
        const skipUpdateCheck = recipientTokens.length === 1;
        if (skipUpdateCheck) {
            const token = recipientTokens[0];
            const oldEmailData = oldRecord.get('address');
            let skipUpdate = oldEmailData !== '';

            _.each(token, (value, key) => {
                const keysToValidate = ['type', 'n_fileas', 'email_type_field', 'email', 'name', 'note'];
                if (keysToValidate.includes(key) && oldEmailData[key] !== value) skipUpdate = false;
            })
            if (skipUpdate) return;
        }
        
        if (index > -1 && oldRecord.get('address') !== '') this.store.remove(oldRecord);
        
        const updatedRecords = [];
        const existingRecords = [];
        // collect and ignore existing records
        recipientTokens.forEach((token) => {
            if (!token.email) return;
            const existingRecipientToken = this.store.findBy(function (record) {
                const addressData = record.get('address') ?? '';
                    if (addressData !== ''
                        && addressData?.email === token.email
                        && addressData?.name === token.name) {
                        existingRecords.push(record);
                        return true;
                    }
            }, this);
            if (existingRecipientToken === -1) {
                if (this.validateRecipientToken(token).isValid) {
                    const record = new Ext.data.Record({type: type, 'address': token});
                    updatedRecords.push(record);
                    this.lastEditedRecord = record;
                }
            }
        })
        // add new resolved records to store
        if (updatedRecords.length > 0) {
            if (index > -1) {
                this.store.insert(index, updatedRecords.reverse());
            } else {
                this.store.add(updatedRecords);
            }
        }
        
        // highlight all the resolved records in UI
        const highlightRecords = updatedRecords.concat(existingRecords);
        highlightRecords.forEach((record) => {
            const row = this.getView().getRow(this.store.indexOf(record));
            if (row) {
                Ext.fly(row).highlight('',{duration: 2});
                this.getView().focusRow(row);
            }
        })
        
        if (existingRecords.length) await new Promise((delay) => setTimeout(delay, 1000));
        if (addEmptyRow) this.addEmptyRowAndDoLayout(autoStartEdit);
    },
    
    /**
     * adds row and adjusts layout
     *
     * @param autoStartEdit
     */
    addEmptyRowAndDoLayout: function(autoStartEdit) {
        const record = this.activeEditor ? this.activeEditor.record : this.lastEditedRecord;
        const existingEmptyRecords = this.store.queryBy(function (record) {
            if (! record?.data?.address || record.data.address === '') return true;
        }, this);
        
        const emptyRecord = new Ext.data.Record({
            type: record?.data?.type ?? 'to',
            'address': ''
        });

        existingEmptyRecords.items.forEach((record) => {
            this.store.remove(record);
        })
        
        this.store.add(emptyRecord);
        this.store.commitChanges();
        this.setFixedHeight(false);
        
        if (this.ownerCt) this.ownerCt.doLayout();
    
        const selModel = this.getSelectionModel();
        const emptyRecordIdx = this.store.indexOf(emptyRecord);
        
        if (!selModel.isSelected(emptyRecordIdx)) selModel.selectRow(emptyRecordIdx);
        if (autoStartEdit) this.startEditing.defer(100, this, [emptyRecordIdx, 1]);
    },
    
    async updateMassMailingRecipients() {
        if (!this.store) return;
        const contactsToResolve = [];
        
        this.store.each((record) => {
            const token = record.data.address;
            const result = this.validateRecipientToken(token);
            // token with emails should be collected and resolved to list members
            if (!result.isValid || token?.emails) {
                contactsToResolve.push({result: result, token: token, record: record});
            }
        })

        const result = await this.showInvalidContactDialog(contactsToResolve);
        if (result && contactsToResolve.length > 0) {
            const records = _.map(contactsToResolve, 'record');
            await Promise.all(records.map((record) => {
                const members = record?.data?.address?.emails;
                const membersToUpdate = members && members?.length > 0 ? members : null;
                return this.updateRecipientsToken(record, membersToUpdate, 'bcc', false, false);
            }));
            this.addEmptyRowAndDoLayout(true);
        }
        if (this.massMailingMode) {
            this.store.each((record) => {
                record.set('type', 'bcc');
                record.commit();
            });
        }
        this.view.refresh();
        
        if (this.composeDlg) {
            if (!result) this.composeDlg.onToggleMassMailing();
        }
    },
    
    showInvalidContactDialog(contactsToResolve,  buttonOptions = ['No', 'Yes']) {
        return new Promise((resolve) => {
            if (!this.massMailingMode) return resolve(false);
            if (contactsToResolve.length === 0) return resolve(true);
            const text = contactsToResolve.map((item) => {
                item.token.qtip = Ext.util.Format.htmlEncode(item?.result?.tip ?? '');
                const block =  document.createElement('div');
                block.innerHTML = this.searchCombo.renderEmailAddressAndIcon(item.token);
                return block.outerHTML;
            })
            const dialog = Tine.widgets.dialog.FileListDialog.openWindow({
                modal: true,
                allowCancel: false,
                height: 180,
                width: 500,
                title: this.app.i18n._('The following recipients will be removed from this mass mail'),
                text: text.join('</br>'),
                scope: this,
                buttonOptions: buttonOptions,
                handler: async (button) => {
                    resolve(['YES', 'OK'].includes(button.toUpperCase()));
                }
            });
        });
    },
});

Ext.reg('felamimailrecipientgrid', Tine.Felamimail.RecipientGrid);

/*
 * Tine 2.0
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const { retryAllRejectedPromises } = require('promises-to-retry');
import waitFor from "util/waitFor.es6";

require('./MessageFileAction');

Ext.namespace('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.MessageEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 *
 * <p>Message Compose Dialog</p>
 * <p>This dialog is for composing emails with recipients, body and attachments.
 * you can choose from which account you want to send the mail.</p>
 *
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 *
 * @param       {Object} config
 * @constructor
 * Create a new MessageEditDialog
 */
Tine.Felamimail.MessageEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    /**
     * @cfg {Boolean} autoSave
     * enable autosave as draft
     */
    autoSave: true,

    /**
     * @cfg {Array/String} bcc
     * initial config for bcc
     */
    bcc: null,

    /**
     * @cfg {String} body
     */
    msgBody: '',

    /**
     * @cfg {Array/String} cc
     * initial config for cc
     */
    cc: null,

    /**
     * @cfg {Array} of Tine.Felamimail.Model.Message (optionally encoded)
     * messages to forward
     */
    forwardMsgs: null,

    /**
     * @cfg {String} accountId
     * the accout id this message is sent from
     */
    accountId: null,

    /**
     * @cfg {Tine.Felamimail.Model.Message} (optionally encoded)
     * message to reply to
     */
    replyTo: null,

    /**
     * @cfg {Tine.Felamimail.Model.Message} (optionally encoded)
     * message to use as draft/template
     */
    draftOrTemplate: null,

    /**
     * @cfg {Boolean} (defaults to false)
     */
    replyToAll: false,

    /**
     * @cfg {String} subject
     */
    subject: '',

    /**
     * @cfg {Array/String} to
     * initial config for to
     */
    to: null,

    /**
     * validation error message
     * @type String
     */
    validationErrorMessage: '',

    /**
     * array with e-mail-addresses used as recipients
     * @type {Array}
     */
    mailAddresses: null,
    /**
     * json-encoded selection filter and to
     * @type {String} selectionFilter
     */
    selectionFilter: null,

    /**
     * holds default values for the record
     * @type {Object}
     */
    recordDefaults: null,

    /**
     * @type {String}
     */
    quotedPGPMessage: null,

    /**
     * @type {Boolean}
     */
    isDraft: false,

    /**
     * @type {Boolean}
     */
    isTemplate: false,

    /**
     * @type {String}
     */
    draftUid: null,

    /**
     * @type {String}
     */
    templateId: null,

    /**
     * @private
     */
    windowNamePrefix: 'MessageEditWindow_',
    appName: 'Felamimail',
    recordClass: Tine.Felamimail.Model.Message,
    recordProxy: Tine.Felamimail.messageBackend,
    loadRecord: false,
    evalGrants: false,
    hideAttachmentsPanel: true,

    bodyStyle: 'padding:0px',

    /**
     * overwrite update toolbars function (we don't have record grants)
     * @private
     */
    updateToolbars: Ext.emptyFn,

    massMailingPlugins: ['all'],

    // private
    initComponent: function () {
        this.autoSave = Tine.Tinebase.appMgr.get('Felamimail').featureEnabled('autoSaveDrafts');
        let me = this;

        if (me.autoSave) {
            me.trottledsaveAsDraft = _.throttle(_.bind(me.saveAsDraft, me), 5000, {leading: false});
            me.saveAsDraftPromise = Promise.resolve();
        }

        me.on('beforecancel', me.onBeforeCancel, this);

        Tine.Felamimail.MessageEditDialog.superclass.initComponent.call(this);

        Tine.Felamimail.mailvelopeHelper.mailvelopeLoaded.then(function () {
            me.button_toggleEncrypt.setVisible(true);
        })['catch'](function () {
            Tine.log.info('mailvelope not available');
        });
    },

    /**
     * init buttons
     */
    initButtons: function () {
        this.fbar = [];

        this.action_send = new Ext.Action({
            text: this.app.i18n._('Send'),
            handler: this.onSaveAndClose,
            iconCls: 'FelamimailIconCls',
            disabled: false,
            scope: this
        });

        this.action_searchContacts = new Ext.Action({
            text: this.app.i18n._('Search Recipients'),
            handler: this.onSearchContacts,
            iconCls: 'AddressbookIconCls',
            disabled: false,
            scope: this
        });

        this.action_saveAsDraft = new Ext.Action({
            text: this.app.i18n._('Save As Draft'),
            handler: this.onSaveInFolder.createDelegate(this, ['drafts_folder']),
            iconCls: 'action_saveAsDraft',
            disabled: false,
            scope: this
        });

        this.action_saveAsTemplate = new Ext.Action({
            text: this.app.i18n._('Save As Template'),
            handler: this.onSaveInFolder.createDelegate(this, ['templates_folder']),
            iconCls: 'action_saveAsTemplate',
            disabled: false,
            scope: this,
            listeners: {
                scope: this,
                selectionchange: this.onFileMessageSelectionChange
            }
        });

        this.action_fileRecord = new Tine.Felamimail.MessageFileAction({
            mode: 'selectOnly',
            composeDialog: this,
            listeners: {
                scope: this,
                selectionchange: this.onFileMessageSelectionChange
            }
        });

        this.button_fileMessage = new Ext.SplitButton(this.action_fileRecord);

        this.action_toggleReadingConfirmation = new Ext.Action({
            text: this.app.i18n._('Reading Confirmation'),
            handler: this.onToggleReadingConfirmation,
            iconCls: 'felamimail-action-reading-confirmation',
            disabled: false,
            scope: this,
            enableToggle: true
        });
        this.button_toggleReadingConfirmation = Ext.apply(new Ext.Button(this.action_toggleReadingConfirmation), {
            tooltip: this.app.i18n._('Activate this toggle button to receive a reading confirmation.')
        });

        this.action_expectedAnswer = new Tine.Felamimail.MessageExpectedAnswerAction({
            mode: 'selectOnly',
            composeDialog: this,
            originalMessage: this.replyTo,
            listeners: {
                scope: this,
                selectionchange: this.onFileMessageSelectionChange
            }
        });

        this.button_ExpectedAnswer = Ext.apply(new Ext.Button(this.action_expectedAnswer), {
            tooltip: this.app.i18n._('If you select one of these options, you will receive a notification when no reply to the email has been received by the configured deadline')
        });

        this.action_toggleEncrypt = new Ext.Action({
            text: this.app.i18n._('Encrypt Email'),
            toggleHandler: this.onToggleEncrypt,
            iconCls: 'felamimail-action-decrypt',
            disabled: false,
            pressed: false,
            hidden: true,
            scope: this,
            enableToggle: true
        });
        this.button_toggleEncrypt = Ext.apply(new Ext.Button(this.action_toggleEncrypt), {
            tooltip: this.app.i18n._('Encrypt email using Mailvelope')
        });

        this.action_massMailing = new Ext.Action({
            text: this.app.i18n._('Mass Mailing'),
            handler: this.onToggleMassMailing,
            iconCls: 'FelamimailIconCls',
            disabled: false,
            scope: this,
            enableToggle: true
        });
        this.button_massMailing = Ext.apply(new Ext.Button(this.action_massMailing), {
            tooltip: this.app.i18n._('Activate this toggle button to send the mail separately to each recipient.')
        });

        this.tbar = new Ext.Toolbar({
            defaults: {height: 43},
            items: [{
                xtype: 'buttongroup',
                columns: 8,
                items: [
                    Ext.apply(new Ext.Button(this.action_send), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }),
                    Ext.apply(new Ext.Button(this.action_cancel), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }),
                    Ext.apply(new Ext.Button(this.action_searchContacts), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top',
                        tooltip: this.app.i18n._('Click to search for and add recipients from the Addressbook.')
                    }),
                    this.action_saveAsDraft,
                    this.button_toggleReadingConfirmation,
                    this.button_massMailing,
                    this.button_fileMessage,
                    this.action_saveAsTemplate,
                    this.button_toggleEncrypt,
                    this.button_ExpectedAnswer,
                ]
            }]
        });
    },


    /**
     * @private
     */
    initRecord: function () {
        this.decodeMsgs();

        this.recordDefaults = Tine.Felamimail.Model.Message.getDefaultData();

        if (this.mailAddresses) {
            this.recordDefaults.to = Ext.decode(this.mailAddresses);
        } else if (this.selectionFilter) {
            // put filter into to, cc or bcc of record and the loading be handled by resolveRecipientFilter
            const filterAndTo = Ext.decode(this.selectionFilter);
            this.record.set(filterAndTo.to.toLowerCase(), filterAndTo.filter);
        }

        if (!this.record) {
            this.record = new Tine.Felamimail.Model.Message(this.recordDefaults, 0);
        }
        this.initAccountCombo();
        this.initFrom();
        this.initRecipients();
        this.initSubject();
        this.initContent();

        const currentAccount = Tine.Tinebase.appMgr.get('Felamimail').getAccountStore().getById(this.record.get('account_id'));

        // legacy handling:...
        // TODO add this information to attachment(s) + flags and remove this
        if (this.replyTo) {
            this.record.set('flags', '\\Answered');
            this.record.set('original_id', this.replyTo.id);
        } else if (this.forwardMsgs) {
            this.record.set('flags', 'Passed');
            this.record.set('original_id', this.forwardMsgs[0].id);
        } else if (this.draftOrTemplate) {
            this.record.set('original_id', this.draftOrTemplate.id);
        }
        
        this.button_fileMessage.toggle(currentAccount.get('message_sent_copy_behavior') !== 'skip');

        Tine.log.debug('Tine.Felamimail.MessageEditDialog::initRecord() -> record:');
        Tine.log.debug(this.record);
    },

    /**
     * show loadMask (loadRecord is false in this dialog)
     * @param {} ct
     * @param {} position
     */
    onRender: function (ct, position) {
        Tine.Felamimail.MessageEditDialog.superclass.onRender.call(this, ct, position);
        this.showLoadMask();
    },

    /**
     * handle attachments: attaches message attachments depends on emailForward preference
     * - keeps attachments as they are (emailForward -> message or when the message is draft or Template)
     * - keeps original message/attachments and attach original email as .eml attachment(messageAndAsAttachment)
     * - only attach original email as .eml attachment (onlyAsAttachment)
     *
     * @param {Tine.Felamimail.Model.Message} message
     */
    handleAttachmentsOfExistingMessage: function (message) {
        if (!this.isForwardedMessage() && !this.draftOrTemplate) return;

        let attachments = [];
        let forwardMode = !this.isForwardedMessage()
            ? ''
            : Tine[this.app.appName].registry.get('preferences').get('emlForward');

        if (forwardMode === 'message' || this.draftOrTemplate) {
            Ext.each(message.get('attachments'), function (attachment) {
                if (String(attachment['partId']).match(/winmail/)) {
                    // we found a winmail extration attachment -> forward the original winmail.dat
                    forwardMode = 'onlyAsAttachment';
                    attachments = [];
                    return false;
                } else {
                    attachment = {
                        name: attachment['filename'],
                        type: attachment['content-type'],
                        size: attachment['size'],
                        id: message.id + '_' + attachment['partId']
                    };
                    attachments.push(attachment);
                }
            }, this);
        }

        if (forwardMode === 'onlyAsAttachment' || forwardMode === 'messageAndAsAttachment') {
            const node = message.get('from_node');
            let rfc822Attachment = {
                name: message.get('subject'),
                type: 'message/rfc822',
                size: message.get('size'),
                id: message.id
            };
            if (node) {
                rfc822Attachment = _.assign(rfc822Attachment, {
                    type: 'file',
                    size: node.size,
                    path: node.path,
                    name: node.name,
                    attachment_type: 'attachment',
                });
            }
            attachments.push(rfc822Attachment);
        }

        this.record.set('attachments', attachments);
    },

    /**
     * inits body and attachments from reply/forward/template
     *
     * @param {} message
     */
    initContent: function (message) {
        const account = Tine.Tinebase.appMgr.get('Felamimail').getAccountStore().getById(this.record.get('account_id'));
        if (!message) message = this.getMessageFromConfig();

        // we follow the account compose_format when fetch msg failed
        const composeFormat =  this.recordProxy.getFormatConfig('compose_format', message, account);
        
        if (!this.record.get('body')) {
            if (!this.msgBody && message) {
                if (!message.bodyIsFetched() || composeFormat !== message.getBodyType()) {
                    // self callback when body needs to be (re) fetched
                    if (!this.fetchRequestCount) this.fetchRequestCount = 0;
                    return this.recordProxy.fetchBody(message, composeFormat, {
                        success: (result) => {
                            this.initContent(result);
                            this.fetchRequestCount = 0;
                        },
                        // set format to message body format if fetch fails
                        failure: async (e) => {
                            this.fetchRequestCount++;
                            if (this.fetchRequestCount === 3 || !message.bodyIsFetched()) return null;
                            
                            if (e?.code === 404) {
                                await Tine.Felamimail.getMessageFromNode(message.data.id, composeFormat)
                                    .then(async (response) => {
                                        message = Tine.Felamimail.messageBackend.recordReader({responseText: Ext.util.JSON.encode(response)});
                                    }).catch((exception) => {
                                        console.error(exception);
                                    });
                            }
                            this.initContent(message);
                        }
                    });
                }
                this.setMessageBody(message, account, composeFormat);
                this.handleAttachmentsOfExistingMessage(message);
                
                const folder = this.app.getFolderStore().getById(message.get('folder_id'));
                if (folder) {
                    this.isDraft = folder.get('globalname') === account.get('drafts_folder');
                    this.isTemplate = folder.get('globalname') === account.get('templates_folder');

                    if (this.isDraft) {
                        this.record.set('messageuid', message.get('messageuid'));
                        this.draftUid = message.get('messageuid');
                    }

                    if (this.isTemplate) {
                        this.templateId = message.get('id');
                    }
                }
            }
            this.record.set('body', this.msgBody);
        }
        this.record.set('content_type', composeFormat);

        if (this.attachments) {
            this.handleExternalAttachments();
        }

        delete this.msgBody;

        this.onRecordLoad();
    },

    /**
     * handle attachments like external URLs (COSR)
     *
     * TODO: check if this overwrites existing attachments in some cases
     */
    handleExternalAttachments: function () {
        this.attachments = Ext.isArray(this.attachments) ? this.attachments : [this.attachments];
        var attachments = [];
        Ext.each(this.attachments, function (attachment) {

            // external URL with COSR header enabled
            if (Ext.isString(attachment)) {
                attachment = {
                    url: attachment
                };
            }

            attachments.push(attachment);
        }, this);

        this.record.set('attachments', attachments);
        delete this.attachments;
    },

    /**
     * set message body: converts newlines, adds quotes
     *
     * @param {Tine.Felamimail.Model.Message} message
     * @param {Tine.Felamimail.Model.Account} account
     * @param {String}                        format
     */
    setMessageBody: function (message, account, format) {
        var preparedParts = message.get('preparedParts');

        this.msgBody = message.get('body');

        if (preparedParts && preparedParts.length > 0) {
            if (preparedParts[0].contentType === 'application/pgp-encrypted') {
                this.quotedPGPMessage = preparedParts[0].preparedData;

                this.msgBody = this.msgBody + this.app.i18n._('Encrypted Content');

                var me = this;
                this.isRendered().then(function () {
                    me.button_toggleEncrypt.toggle();
                });
            }
        }

        if (this.replyTo) {
            if (format === 'text/plain') {
                this.msgBody = String('> ' + this.msgBody).replace(/\r?\n/g, '\n> ');
            } else {
                const blockquote = document.createElement('blockquote');
                blockquote.className = 'felamimail-body-blockquote';
                if (message.getBodyType() === 'text/plain') {
                    blockquote.innerText = this.msgBody;
                } else {
                    blockquote.innerHTML = this.msgBody;
                }
                this.msgBody = '<br/>' + blockquote.outerHTML;
            }
        }

        if (this.isForwardedMessage()) {
            if (format === 'text/plain') {
                this.msgBody = String('> ' + this.msgBody).replace(/\r?\n/g, '\n> ');
            } else {
                const forwardEl = document.createElement('div');
                forwardEl.className = 'felamimail-body-forwarded';
                if (message.getBodyType() === 'text/plain') {
                    forwardEl.innerText = this.msgBody;
                } else {
                    forwardEl.innerHTML = this.msgBody;
                }
                this.msgBody = '<br/>' + forwardEl.outerHTML;
            }
        }

        this.msgBody = this.getQuotedMailHeader(format) + this.msgBody;

        if (this.isForwardedMessage()) {
            const forwardMode = Tine[this.app.appName].registry.get('preferences').get('emlForward');
            if (forwardMode === 'onlyAsAttachment') {
                this.msgBody = '';
            }
        }
    },

    /**
     * returns true if message is forwarded
     *
     * @return {Boolean}
     */
    isForwardedMessage: function () {
        return (this.forwardMsgs && this.forwardMsgs.length === 1);
    },

    getSignaturePosition: function (account) {
        return _.get(account, 'data.signature_position', 'below') ?? null;
    },

    /**
     * add default signature to message
     *
     */
    addDefaultSignature: function () {
        if (this.draftOrTemplate) return;
        this.updateSignature(this.record.get('account_id'), null, this.record.get('body'));
    
        const bodyContent = this.record.get('body');
        const format = this.record.get('content_type');
        const ch = format === 'text/html' ? '<br>' : '\n';
        if (bodyContent && bodyContent !== '' && !bodyContent.startsWith(ch)) {
            this.record.set('body', `${ch}${ch}${bodyContent}`);
            this.msgBody = this.record.get('body');
            this.bodyCards.layout.activeItem.setValue(this.msgBody);
        }
    },


    /**
     * get account signature text
     *
     * @param {Tine.Felamimail.Model.Account} account
     * @param {String} format
     * @param signature
     */
    getSignature: function (account, format, signature) {
        let signatureText = Tine.Felamimail.getSignature(account, signature);

        if (format === 'text/plain') {
            signatureText = Tine.Tinebase.common.html2text(signatureText);
        }

        return signatureText;
    },

    /**
     * inits / sets sender of message
     */
    initFrom: function () {
        if (!this.record.get('account_id')) {
            if (!this.accountId) {
                const message = this.getMessageFromConfig();
                const availableAccounts = this.accountCombo.store;
                const fromEmail = message ? message.get('from_email') : null;
                const fromAccountIdx = availableAccounts.find('email', fromEmail);
                const fromAccount = availableAccounts.getAt(fromAccountIdx);
                const folderId = message ? message.get('folder_id') : null;
                const folder = folderId ? Tine.Tinebase.appMgr.get('Felamimail').getFolderStore().getById(folderId) : null;
                let accountId = folder ? folder.get('account_id') : null;

                if (!accountId) {
                    const activeAccount = Tine.Tinebase.appMgr.get('Felamimail').getActiveAccount();
                    accountId = (activeAccount) ? activeAccount.id : null;
                }

                if (! this.replyTo && !this.isForwardedMessage()) {
                    this.from = fromAccount;
                }
                this.accountId = accountId;
            }

            const currentAccountId = this.accountId;
            const defaultAccountId = Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount');
            const currentAccount = Tine.Tinebase.appMgr.get('Felamimail').getAccountStore().getById(this.accountId);

            if (!currentAccount.data?.account_grants?.addGrant) {
                this.accountId = defaultAccountId;
                this.showReplacedMailSenderNotification(currentAccountId, defaultAccountId);
            }

            this.record.set('account_id', this.accountId);
        }
        delete this.accountId;
    },

    /**
     * after render
     */
    afterRender: async function () {
        Tine.Felamimail.MessageEditDialog.superclass.afterRender.apply(this, arguments);
    
        this.getEl().on(Ext.EventManager.useKeydown ? 'keydown' : 'keypress', this.onKeyPress, this);
        this.recipientGrid.on('specialkey', function (field, e) {
            this.onKeyPress(e);
        }, this);
    
        this.htmlEditor.on('keydown', function (ed, e) {
            this.onKeyPress(e);
        }, this);
    
        this.htmlEditor.on('toggleFormat', this.onToggleFormat, this);
        this.initHtmlEditorDD();
        
        await waitFor(() => {
            return this.recipientGrid.store.getCount() > 0
        });
        _.delay(() => {
            // recipientGrid should have 1 empty item by default
            if (this.recipientGrid.store.getCount() > 1) {
                if (!this.subjectField.getValue()) {
                    this.subjectField.focus();
                } else {
                    this.htmlEditor.focus();
                }
            }
        }, 1000);
    },


    initHtmlEditorDD: function () {
        return;
        if (!this.htmlEditor.rendered) {
            return this.initHtmlEditorDD.defer(500, this);
        }

        this.htmlEditor.getDoc().addEventListener('dragover', function (e) {
            this.action_addAttachment.plugins[0].onBrowseButtonClick();
        }.createDelegate(this));

        this.htmlEditor.getDoc().addEventListener('drop', function (e) {
            this.action_addAttachment.plugins[0].onDrop(Ext.EventObject.setEvent(e));
        }.createDelegate(this));
    },

    /**
     * on key press
     * @param {} e
     * @param {} t
     * @param {} o
     */
    onKeyPress: function (e, t, o) {
        if ((e.getKey() === e.TAB || e.getKey() === e.ENTER) && !e.shiftKey) {
            if (e.getTarget('input[name=subject]')) {
                this.htmlEditor.focus.defer(50, this.htmlEditor);
            } else if (e.getTarget('input[type=text]')) {
                this.subjectField.focus.defer(50, this.subjectField);
            }
        }
        if (e.getTarget('body')) {
            if (e.getKey() === e.ENTER && e.ctrlKey) {
                this.onSaveAndClose();
            } else if (e.getKey() === e.TAB && e.shiftKey) {
                this.subjectField.focus.defer(50, this.subjectField);
            }
        }

        this.checkStates();
    },

    checkStates: function() {
        if (this.recipientGrid.initialLoad) {
            return _.delay(_.bind(this.checkStates, this), 250);
        }
        Tine.Felamimail.MessageEditDialog.superclass.checkStates.apply(this, arguments);
        if (this.autoSave && _.keys(this.record.getChanges()).length) {
            this.trottledsaveAsDraft();
        }
    },

    saveAsDraft: function() {
        let me = this;

        me.record.set('messageuid', me.draftUid);
        me.record.commit();

        me.action_saveAsDraft.setIconClass('x-btn-wait');

        return me.saveAsDraftPromise = retryAllRejectedPromises([() => {
            return Tine.Felamimail.saveDraft(me.record.data)
                // TODO log failures here for debugging
                .then((savedDraft) => {
                    if (!me.draftUid) {
                        this.updateFolderCount('drafts_folder', 1);
                    }

                    me.draftUid = savedDraft.messageuid;
                })
                .finally(() => {
                    me.action_saveAsDraft.setIconClass('action_saveAsDraft');
                });
            }
        ], {
            maxAttempts: 5, delay: 500
        });
    },

    deleteDraft: async function (draftUid) {
        return await Tine.Felamimail.deleteDraft(draftUid, this.record.get('account_id'));
    },

    onBeforeCancel: function() {
        if (this.autoSave) {
            this.trottledsaveAsDraft.cancel();
        }
        if (this.draftUid) {
            Ext.MessageBox.show({
                title: this.app.i18n._('Discard this Draft?'),
                msg: this.app.i18n._('Do you want to discard the current draft?'),
                buttons: Ext.MessageBox.YESNO,
                fn: (btn) => {
                    this.showLoadMask()
                        .then(() => {
                            return btn === 'yes' ?
                                this.deleteDraft(this.draftUid)
                                    .then(() => {
                                        this.updateFolderCount('drafts_folder', -1);
                                    })
                                : this.saveAsDraft();
                        })
                        .then(_.bind(this.window.close, this.window, true))

                },
                icon: Ext.MessageBox.QUESTION
            });

            return false;
        }
    },

    /**
     * returns message passed with config
     *
     * @return {Tine.Felamimail.Model.Message}
     */
    getMessageFromConfig: function () {
        return this.replyTo ? this.replyTo :
            this.forwardMsgs && this.forwardMsgs.length === 1 ? this.forwardMsgs[0] :
                this.draftOrTemplate ? this.draftOrTemplate : null;
    },

    /**
     * inits to/cc/bcc
     */
    initRecipients: function () {
        if (this.replyTo) {
            this.initReplyRecipients();
        }

        Ext.each(['to', 'cc', 'bcc'], function (field) {
            if (this.draftOrTemplate) {
                this[field] = this.draftOrTemplate.get(field);
            }

            if (!this.record.get(field)) {
                this[field] = Ext.isArray(this[field]) ? this[field] : Ext.isString(this[field]) ? [this[field]] : [];
                this.record.set(field, this[field]);
            }
            delete this[field];

            this.resolveRecipientFilter(field);
        }, this);
    },

    /**
     * init recipients from reply/replyToAll information
     */
    initReplyRecipients: async function () {
        // should resolve recipients here , save data
        this.to = this.getReplyToEmail();

        if (this.replyToAll) {
            if (!Ext.isArray(this.to)) {
                this.to = [this.to];
            }
            this.to = this.to.concat(this.replyTo.get('to'));
            this.cc = this.replyTo.get('cc');

            // remove own email and all non-email strings/objects from to/cc
            const account = Tine.Tinebase.appMgr.get('Felamimail').getAccountStore().getById(this.record.get('account_id'));
            const ownEmailRegexp = new RegExp(window.lodash.escapeRegExp(account.get('email')));

            Ext.each(['to', 'cc'], function (field) {
                this[field] = _.filter(this[field], (addressData) => {
                    const email = addressData.email ?? addressData;
                    return Ext.isString(email) && email.match(/@/) && ! ownEmailRegexp.test(email);
                });
            }, this);
        }
    },

    getReplyToEmail() {
        // should resolve recipients here , save data
        const replyToHeader = this.replyTo.get('headers')['reply-to'];
        const replyToEmail = this.replyTo.get('from_email');
        const replyToName = this.replyTo.get('from_name');
        const replyToToken = this.replyTo.get('from')?.[0];

        // reply-to header has the highest priority
        if (replyToHeader) return replyToHeader;
        // we might get the recipient token from server
        if (replyToToken && replyToToken?.email) return this.replyTo.get('from');
        if (replyToEmail) {
            return [{
                'email': replyToEmail,
                'email_type_field': '',
                'type': '',
                'n_fileas': '',
                'name': replyToName ?? '',
                'contact_record': ''
            }];
        }


        return [];
    },

    /**
     * resolve recipient filter / queries addressbook
     *
     * @param {String} field to/cc/bcc
     */
    resolveRecipientFilter: function (field) {
        if (!Ext.isEmpty(this.record.get(field))
            && Ext.isObject(this.record.get(field)[0])
            && (this.record.get(field)[0].operator || this.record.get(field)[0].condition)
        ) {
            // found a filter
            var filter = this.record.get(field);
            this.record.set(field, []);

            this['AddressLoadMask'] = new Ext.LoadMask(Ext.getBody(), {msg: this.app.i18n._('Loading Email Addresses')});
            this['AddressLoadMask'].show();

            Tine.Addressbook.searchContacts(filter, null, async function (response) {
                const mailAddresses = await Tine.Felamimail.GridPanelHook.prototype.getMailAddresses(response.results);
                this.record.set(field, mailAddresses);
                this.recipientGrid.syncRecipientsToStore([field], this.record, true, false);
                this['AddressLoadMask'].hide();
            }.createDelegate(this));
        }
    },

    /**
     * sets / inits subject
     */
    initSubject: function () {
        if (!this.record.get('subject')) {
            if (!this.subject) {
                if (this.replyTo) {
                    this.setReplySubject();
                } else if (this.forwardMsgs) {
                    this.setForwardSubject();
                } else if (this.draftOrTemplate) {
                    this.subject = this.draftOrTemplate.get('subject');
                }
            }
            this.record.set('subject', this.subject);
        }

        delete this.subject;
    },

    /**
     * setReplySubject -> this.subject
     *
     * removes existing prefixes + just adds 'Re: '
     */
    setReplySubject: function () {
        var replyPrefix = 'Re: ',
            replySubject = (this.replyTo.get('subject')) ? this.replyTo.get('subject') : '',
            replySubject = replySubject.replace(/^((re|aw|antw|fwd|odp|sv|wg|tr|rép):\s*)*/i, replyPrefix);

        this.subject = replySubject;
    },

    /**
     * setForwardSubject -> this.subject
     */
    setForwardSubject: function () {
        this.subject = this.app.i18n._('Fwd:') + ' ';
        this.subject += this.forwardMsgs.length === 1 ?
            this.forwardMsgs[0].get('subject') :
            String.format(this.app.i18n._('{0} Message', '{0} Messages', this.forwardMsgs.length));
    },

    /**
     * decode this.replyTo / this.forwardMsgs from interwindow json transport
     */
    decodeMsgs: function () {
        if (Ext.isString(this.draftOrTemplate)) {
            this.draftOrTemplate = new this.recordClass(Ext.decode(this.draftOrTemplate));
        }

        if (Ext.isString(this.replyTo)) {
            this.replyTo = new this.recordClass(Ext.decode(this.replyTo));
        }

        if (Ext.isString(this.forwardMsgs)) {
            var msgs = [];
            Ext.each(Ext.decode(this.forwardMsgs), function (msg) {
                msgs.push(new this.recordClass(msg));
            }, this);

            this.forwardMsgs = msgs;
        }
    },

    /**
     * fix input fields layout
     */
    fixLayout: function () {
        if (!this.subjectField.rendered || !this.accountCombo.rendered || !this.recipientGrid.rendered) {
            return;
        }

        var scrollWidth = this.recipientGrid.getView().getScrollOffset();
        this.subjectField.setWidth(this.subjectField.getWidth() - scrollWidth + 1);
        this.accountCombo.setWidth(this.accountCombo.getWidth() - scrollWidth + 1);
    },

    /**
     * save message in folder
     *
     * @param {String} folderField
     */
    onSaveInFolder: function (folderField) {
        if (this.autoSave) {
            this.trottledsaveAsDraft.cancel();
        }
        
        this.flattenHtmlElements();
        this.onRecordUpdate();

        var account = Tine.Tinebase.appMgr.get('Felamimail').getAccountStore().getById(this.record.get('account_id')),
            folderName = account.get(folderField);

        Tine.log.debug('onSaveInFolder() - Save message in folder ' + folderName);
        Tine.log.debug(this.record);

        if (!folderName || folderName == '') {
            Ext.MessageBox.alert(
                i18n._('Failed'),
                String.format(this.app.i18n._('{0} account setting empty.'), folderField)
            );
        } else if (this.attachmentGrid.isUploading()) {
            Ext.MessageBox.alert(
                i18n._('Failed'),
                this.app.i18n._('Files are still uploading.')
            );
        } else {
            this.loadMask.show();
            if (this.autoSave) {
                this.trottledsaveAsDraft.cancel();
            }
            this.recordProxy.saveInFolder(this.record, folderName, {
                scope: this,
                success: function (record) {
                    try {
                        this.fireEvent('update', Ext.util.JSON.encode(this.record.data));
                    } finally {
                        if (this.loadMask) {
                            this.hideLoadMask();
                        }
                    }                    
                    Promise.resolve()
                        .then(() => {
                            if (this.draftUid) {
                                return this.deleteDraft(this.draftUid)
                            } else {
                                this.updateFolderCount(folderField, 1);
                            }
                        })
                        .then(_.bind(this.window.close, this.window, this));
                },
                failure: Tine.Felamimail.handleRequestException.createInterceptor(function () {
                        this.hideLoadMask();
                    }, this
                ),
                timeout: 150000 // 3 minutes
            });
        }
    },

    updateFolderCount: function (folderName, count) {
        const account = this.app.getActiveAccount();
        const targetFolderId = account ? account.getSpecialFolderId(folderName) : null;
        const targetFolder = targetFolderId ? this.app.getFolderStore().getById(targetFolderId) : null;

        if (targetFolder) {
            targetFolder.set('cache_unreadcount', targetFolder.get('cache_unreadcount') + count);
            targetFolder.set('cache_totalcount', targetFolder.get('cache_totalcount') + count);
            targetFolder.set('cache_status', 'pending');
            targetFolder.commit();
        } else {
            Tine.log.info('Tine.Felamimail.MessageEditDialog::updateFolderCount() - target folder ' + folderName + ' no found');
        }
    },

    /**
     * toggle mass mailing
     *
     * @param {} button
     * @param {} e
     */
    onToggleMassMailing: async function (button, e) {
        const active = !this.record.get('massMailingFlag');
        if (!active && this.button_massMailing.pressed) this.button_massMailing.toggle();
        if (this.massMailingMode === active) return;
        this.record.set('massMailingFlag', active);
        await this.switchMassMailingMode(active);
    },
    
    async switchMassMailingMode(active) {
        this.massMailingMode = active;
        if (this.recipientGrid) this.recipientGrid.massMailingMode = active;
        this.massMailingInfoText.setVisible(active);
        if (active) {
            await this.recipientGrid.updateMassMailingRecipients();
        }
        this.doLayout();
    },

    onFileMessageSelectionChange: function(btn, selection) {
        const text = this.app.formatMessage('{locationCount, plural, one {This message will be filed at the following location} other {This message will be filed at the following locations}}: {locationsHtml}', {
                locationCount: selection.length,
                locationsHtml: Tine.Felamimail.MessageFileAction.getFileLocationText(selection, ', ')
            });

        this.messageFileInfoText.update(text);
        this.messageFileInfoText.setVisible(selection.length);

        this.doLayout();
    },

    /**
     * toggle Request Reading Confirmation
     */
    onToggleReadingConfirmation: function () {
        this.record.set('reading_conf', (!this.record.get('reading_conf')));
    },

    onToggleEncrypt: function (btn, e) {
        btn.setIconClass(btn.pressed ? 'felamimail-action-encrypt' : 'felamimail-action-decrypt');

        const account = Tine.Tinebase.appMgr.get('Felamimail').getAccountStore().getById(this.record.get('account_id'));
        const text = this.bodyCards.layout.activeItem.getValue() || this.record.get('body');
        const format = this.record.getBodyType();
        const textEditor = format === 'text/plain' ? this.textEditor : this.htmlEditor;

        this.bodyCards.layout.setActiveItem(btn.pressed ? this.mailvelopeWrap : textEditor);

        if (btn.pressed) {
            var me = this,
                textMsg = Tine.Tinebase.common.html2text(text),
                quotedMailHeader = '';

            if (this.quotedPGPMessage) {
                textMsg = this.getSignature(account, 'text/plain');
                quotedMailHeader = Ext.util.Format.htmlDecode(me.getQuotedMailHeader('text/plain'));
                quotedMailHeader = quotedMailHeader.replace(/\n/, "\n>");

            }

            Tine.Felamimail.mailvelopeHelper.getKeyring().then(function (keyring) {
                mailvelope.createEditorContainer('#' + me.mailvelopeWrap.id, keyring, {
                    predefinedText: textMsg,
                    quotedMailHeader: quotedMailHeader,
                    quotedMail: me.quotedPGPMessage,
                    keepAttachments: true,
                    quota: 32 * 1024 * 1024
                }).then(function (editor) {
                    me.mailvelopeEditor = editor;
                });
            });

            this.southPanel.collapse();
            this.southPanel.setVisible(false);
            this.btnAddAttachemnt.setDisabled(true);
            this.signatureCombo.setDisabled(true);
        } else {
            this.mailvelopeEditor = null;
            delete this.mailvelopeEditor;
            this.mailvelopeWrap.update('');

            this.southPanel.setVisible(true);
            this.btnAddAttachemnt.setDisabled(false);
            this.signatureCombo.setDisabled(false);
        }
    },

    /**
     * toggle format
     */
    onToggleFormat: function () {
        const source = this.bodyCards.layout.activeItem;
        const format = source.mimeType;
        const target = format === 'text/plain' ? this.htmlEditor : this.textEditor;
        const convert = format === 'text/plain' ?
                Ext.util.Format.nl2br :
                Tine.Tinebase.common.html2text;

        if (format.match(/^text/)) {
            this.record.set('content_type', format);
            this.bodyCards.layout.setActiveItem(target);
            target.setValue(convert(source.getValue()));
        } else {
            // ignore toggle request for encrypted content
        }
    },

    /**
     * get quoted mail header
     *
     * @param format
     * @returns {String}
     */
    getQuotedMailHeader: function (format) {
        let header = '';
        if (this.replyTo) {
            const date = (this.replyTo.get('sent'))
                ? this.replyTo.get('sent')
                : ((this.replyTo.get('received')) ? this.replyTo.get('received') : new Date());

            header = String.format(this.app.i18n._('On {0}, {1} wrote'),
                Tine.Tinebase.common.dateTimeRenderer(date),
                Ext.util.Format.htmlEncode(this.replyTo.get('from_name'))
            ) + ':\n';
        } else if (this.isForwardedMessage()) {
            const forwardMode = Tine[this.app.appName].registry.get('preferences').get('emlForward');

            if (forwardMode !== 'onlyAsAttachment') {
                header = String.format('{0}-----' + this.app.i18n._('Original message') + '-----{1}',
                        format === 'text/plain' ? '' : '<br /><b>',
                        format === 'text/plain' ? '\n' : '</b><br />')
                    + Tine.Felamimail.GridPanel.prototype.formatHeaders(this.forwardMsgs[0].get('headers'), false, true, format == 'text/plain')
                    + (format === 'text/plain' ? '\n' : '<br /><br />');
            }
        }
        if (format === 'text/html' && header !== '') {
            const span = document.createElement('span');
            span.className = 'felamimail-body-quoted-header';
            span.innerHTML = header;
            return span.outerHTML;
        }
        return header;
    },

    /**
     * search for contacts as recipients
     */
    onSearchContacts: function () {
        Tine.Felamimail.RecipientPickerDialog.openWindow({
            record: Ext.encode(Ext.copyTo({}, this.record.data, ['subject', 'to', 'cc', 'bcc'])),
            listeners: {
                scope: this,
                'update': function (record) {
                    const messageWithRecipients = Ext.isString(record) ? new this.recordClass(Ext.decode(record)) : record;
                    this.recipientGrid.syncRecipientsToStore(['to', 'cc', 'bcc'], messageWithRecipients, true, true);
                }
            }
        });
    },

    /**
     * executed after record got updated from proxy
     *
     * @private
     */
    onRecordLoad: async function () {
        // interrupt process flow till dialog is rendered
        if (!this.rendered || (this.record.get('content_type') !== 'text/plain' && !this.htmlEditor?.initialized)) {
            this.onRecordLoad.defer(250, this);
            return;
        }

        let title = this.app.i18n._('Compose email:');
        const editor = this.record.get('content_type') === 'text/plain' ? this.textEditor : this.htmlEditor;

        if (this.record.get('subject')) {
            title = title + ' ' + this.record.get('subject');
        }
        this.window.setTitle(title);

        if (!this.button_toggleEncrypt.pressed) {
            editor.setValue(this.record.get('body'));
            this.bodyCards.layout.setActiveItem(editor);
        }

        // to make sure we have all recipients (for example when composing from addressbook with "all pages" filter)
        var ticketFn = this.onAfterRecordLoad.deferByTickets(this),
            wrapTicket = ticketFn();
        this.fireEvent('load', this, this.record, ticketFn);
        wrapTicket();

        this.getForm().loadRecord(this.record);
        this.attachmentGrid.loadRecord(this.record);
        if (this.from) {
            this.accountCombo.setValue(this.from.id);
        }
        
        this.addDefaultSignature();
        this.updateFileLocations();
        this.onFileMessageSelectionChange('', this.action_fileRecord.getSelected());

        if (this.record.get('massMailingFlag')) {
            this.button_massMailing.toggle();
            await this.switchMassMailingMode(this.record.get('massMailingFlag'));
        }

        this.onAfterRecordLoad();
    },

    updateFileLocations: function () {
        const selections = this?.button_fileMessage?.pressed ? this.action_fileRecord.getSelected() : [];
        const fileLocations = [];
        const imapFolderIds = [];
        selections.forEach((item) => {
            if (item?.type === 'folder') {
                if (item?.record_id?.id) imapFolderIds.push(item.record_id.id);
            } else {
                fileLocations.push(item);
            }
        })
        
        this.record.set('fileLocations', fileLocations);
        this.record.set('sent_copy_folder', imapFolderIds);
    },

    /**
     * overwrite, just hide the loadMask
     */
    onAfterRecordLoad: function () {
        (function() {
            var autoSave = this.autoSave;
            this.autoSave = false;

            this.checkStates();
            this.record.commit();

            this.autoSave = autoSave;
        }).defer(100, this);

        if (this.loadMask) {
            this.hideLoadMask();
        }
    },

    /**
     * executed when record gets updated from form
     * - add attachments to record here
     * - add alias / from
     *
     * @private
     */
    onRecordUpdate: function () {
        this.record.data.attachments = [];
        var attachmentData = null;

        const format = this.bodyCards.layout.activeItem.mimeType;
        if (format.match(/^text/)) {
            const editor = format === 'text/plain' ? this.textEditor : this.htmlEditor;

            this.record.set('content_type', format);
            this.record.set('body', editor.getValue());
        }

        this.attachmentGrid.store.each(function (attachment) {
            var fileData = Ext.copyTo({}, attachment.data, ['tempFile', 'name', 'path', 'size', 'type', 'id', 'attachment_type', 'password']);
            this.record.data.attachments.push(fileData);
        }, this);

        const accountId = this.accountCombo.getValue();
        const account = this.accountCombo.getStore().getById(accountId);
        const emailFrom = account.get('email');

        this.record.set('from_email', emailFrom);
        this.record.set('from_name', account.get('from'));

        Tine.Felamimail.MessageEditDialog.superclass.onRecordUpdate.call(this);
        this.record.set('expected_answer', this.action_expectedAnswer?.answer);
        this.record.set('account_id', account.get('original_id'));
        this.updateFileLocations();

        // need to sync once again to make sure we have the correct recipients
        this.recipientGrid.syncRecipientsToRecord();
    },

    onAfterApplyChanges: async function(closeWindow) {
        // grr. onRecordLoad hides loadMask
        this.showLoadMask.defer(10, this);

        if (this.autoSave) {
            await this.saveAsDraftPromise
                .then(() => {
                    if (this.draftUid) {
                        // autodelete draft when message is send
                        return this.deleteDraft(this.draftUid)
                    }
                })
        }

        Tine.Felamimail.MessageEditDialog.superclass.onAfterApplyChanges.call(this, closeWindow);
    },

    /**
     * init attachment grid + add button to toolbar
     */
    initAttachmentGrid: function () {
        if (!this.attachmentGrid) {
            this.attachmentGrid = new Tine.Felamimail.AttachmentUploadGrid({
                editDialog: this,
                fieldLabel: this.app.i18n._('Attachments'),
                hideLabel: true,
                filesProperty: 'attachments',
                // TODO     think about that -> when we deactivate the top toolbar, we lose the dropzone for files!
                //showTopToolbar: false,
                anchor: '100% 95%'
            });

            // add file upload button to toolbar

            this.action_addAttachment = this.attachmentGrid.getAddAction();
            this.action_addAttachment.plugins[0].dropElSelector = 'div[id=' + this.id + ']';

            this.attachmentGrid.on('filesSelected', function (nodes) {
                this.southPanel.expand();
            }, this);

            this.btnAddAttachemnt = new Ext.Button(this.action_addAttachment);
            this.tbar.get(0).insert(2, Ext.apply(this.btnAddAttachemnt, {
                scale: 'medium',
                rowspan: 2,
                iconAlign: 'top'
            }));
        }
    },

    /**
     * init account (from) combobox
     *
     * - need to create a new store with an account record for each alias
     */
    initAccountCombo: function () {
        var accountStore = Tine.Tinebase.appMgr.get('Felamimail').getAccountStore(),
            accountComboStore = new Ext.data.ArrayStore({
                fields: Tine.Felamimail.Model.Account
            });

        var aliasAccount = null,
            aliases = null,
            id = null;

        accountStore.each(function (account) {
            aliases = [account.get('email')];
            if (account.get('type') === 'system') {
                // add identities / aliases to store (for systemaccounts)
                var user = Tine.Tinebase.registry.get('currentAccount');
                var systemAliases = _.get(user, 'emailUser.emailAliases', []) || [];
                if ((systemAliases.length > 0) && (typeof systemAliases[0].dispatch_address !== 'undefined')) {
                    var systemAliasAdresses = _.reduce(systemAliases, (aliases, alias) => {
                        if (!!+alias.dispatch_address) {
                            aliases.push(alias.email);
                        }
                        return aliases;
                    }, []);
                } else {
                    var systemAliasAdresses = systemAliases;
                }

                aliases = aliases.concat(systemAliasAdresses);
            }

            for (var i = 0; i < aliases.length; i++) {
                id = (i == 0) ? account.id : Ext.id();
                aliasAccount = account.copy(id);
                if (i > 0) {
                    aliasAccount.data.id = id;
                    aliasAccount.set('email', aliases[i]);
                }
                let name = aliasAccount.get('from') ? aliasAccount.get('from') : aliasAccount.get('name');
                aliasAccount.set('name', name + ' (' + aliases[i] + ')');
                aliasAccount.set('original_id', account.id);

                // only add to combo if the account has email send grant
                if (aliasAccount.data?.account_grants?.addGrant) {
                    accountComboStore.add(aliasAccount);
                }
            }
        }, this);

        this.accountCombo = new Ext.form.ComboBox({
            name: 'account_id',
            ref: '../../accountCombo',
            plugins: [Ext.ux.FieldLabeler],
            fieldLabel: this.app.i18n._('From'),
            displayField: 'name',
            valueField: 'id',
            editable: false,
            triggerAction: 'all',
            store: accountComboStore,
            mode: 'local',
            listeners: {
                scope: this,
                select: this.onFromSelect
            }
        });
    },

    initSignatureCombo: function() {
        let me = this;
        let signature = this.app.getDefaultSignature(this.record.get('account_id'));

        this.signatureCombo = new Ext.form.ComboBox({
            displayField: 'name',
            value: _.get(signature, 'data.name', this.app.i18n._('None')),
            editable: false,
            triggerAction: 'all',
            store: new Ext.data.JsonStore({
                fields: Tine.Felamimail.Model.Signature,
                listeners: {
                    scope: this,
                    beforeload: (store, options) => {
                        let account = this.app.getAccountStore().getById(this.record.get('account_id'));
                        let signatures = _.concat(
                            {name: this.app.i18n._('None'), id: 'none'},
                            _.get(account, 'data.signatures', [])
                        );
                        store.loadData(signatures);
                        me.signatureCombo.lastQuery = Tine.Tinebase.data.Record.generateUID();
                        return false;
                    }
                }
            }),
            listeners: {
                scope: this,
                beforeselect: (combo, signature, index) => {
                    this.updateSignature(signature.get('account_id'), signature);
                }
            }
        });
    },

    /**
     * updates signature in mail body
     *
     */
    updateSignature: function(accountId = null, newSignatureRecord = null, bodyContent = '') {
        accountId = accountId && accountId !== '' ? accountId : this.record.get('account_id');
        bodyContent = this.bodyCards.layout.activeItem.getValue() || bodyContent;
        const format = this.record.get('content_type');
    
        const oldAccount = this.app.getAccountStore().getById(this.record.get('account_id'));
        const oldSignatureRecord = this.signatureCombo.store.data.items.find((r) => r?.data?.name === this.signatureCombo.getValue());
        const oldSignature = this.getSignature(oldAccount, format, oldSignatureRecord);
        const oldPosition = this.getSignaturePosition(oldAccount);

        const newAccount = this.app.getAccountStore().getById(accountId);
        const newSignature = this.getSignature(newAccount, format, newSignatureRecord);
        const newPosition = this.getSignaturePosition(newAccount);
        let matches = [];

        if (format === 'text/plain') {
            const resolvedSignature = newSignature === '' ? '' : `--\n${newSignature}`;
            if (oldSignature !== '') {
                matches = [...bodyContent.matchAll(new RegExp(`-*\\s*${oldSignature}`, 'g'))];
            }

            if (matches.length > 0) {
                const match = oldPosition === 'above' ? matches[0] : matches[matches.length - 1];
                const startPos = match.index;
                const endPos = match.index + match[0].length;
                if (oldPosition === newPosition) {
                    // always remove old signature and replace new signature if found , it might be in the middle of the message
                    bodyContent = bodyContent.slice(0, startPos) + resolvedSignature + bodyContent.slice(endPos);
                } else {
                    bodyContent = bodyContent.slice(0, startPos) + bodyContent.slice(endPos);
                    bodyContent = this.appendSignatureText(bodyContent, format, newPosition, resolvedSignature);
                }
            } else {
                bodyContent = this.appendSignatureText(bodyContent, format, newPosition, resolvedSignature);
            }
        } else {
            matches = this.htmlEditor.getDoc().getElementsByClassName('felamimail-body-signature-current');
            while (matches.length > 0) {
                // if the existing signature is unchanged, it should be removed here
                matches[0].outerHTML = matches[0].innerHTML.replace(`--<br>${oldSignature}`, '');
            }
            const resolvedSignature = newSignature === '' ? '' : `--<br>${newSignature}`;
            // bodyContent = this.appendSignatureHTML(format, newPosition, resolvedSignature);
            const signatureElement = document.createElement('span');
            signatureElement.className = 'felamimail-body-signature-current';
            signatureElement.innerHTML = resolvedSignature;
            bodyContent = this.appendHtmlNode(signatureElement, newPosition);
        }

        this.record.set('body', bodyContent);
        this.msgBody = this.record.get('body');
        this.bodyCards.layout.activeItem.setValue(bodyContent);
    },

    appendSignatureText(bodyContent, format, position, signatureText) {
        const ch = '\n';
        if (position === 'below' && !bodyContent.endsWith(ch)) bodyContent = `${bodyContent}${ch}${ch}`;
        if (position === 'above' && !bodyContent.startsWith(ch)) bodyContent = `${ch}${ch}${bodyContent}`;
        return position === 'above' ? `${ch}${ch}${signatureText}${bodyContent}` : `${bodyContent}${signatureText}`;
    },
    
    appendHtmlNode(element, position, targetClassName = null) {
        const quotedHeader = this.htmlEditor.getDoc().getElementsByClassName('felamimail-body-quoted-header')[0];
        const blockquote = this.htmlEditor.getDoc().getElementsByClassName('felamimail-body-blockquote')[0];
        const targetElement = targetClassName ? this.htmlEditor.getDoc().getElementsByClassName(targetClassName)[0] : null;
        let el = targetElement;
        
        if (!targetElement) el = position === 'above' ? quotedHeader : (blockquote || quotedHeader);
        const insertNewLines = (target, count) => {
            for (let i = 0; i < count; i++) {
                if (!target.previousSibling || target.previousSibling?.nodeName !== 'BR') target.insertAdjacentHTML("beforebegin", '<br>');
                target = target.previousSibling;
            }
            return target;
        }
        if (!el || position === 'below') {
            this.htmlEditor.getDoc().getElementsByTagName('body')[0].appendChild(element);
        } else {
            if (position === 'above') {
                const target = insertNewLines(el, 2);
                target.insertAdjacentElement("beforebegin", element);
            }
        }
        // add newline above new appended element
        el = this.htmlEditor.getDoc().getElementsByClassName(element.className)[0];
        if (el.innerHTML !== '') {
            insertNewLines(el, 2);
        }
        return this.htmlEditor.getDoc().getElementsByTagName('body')[0].innerHTML;
    },

    /**
     * if 'account_id' is changed we need to update the signature
     *
     * @param {} combo
     * @param {} record
     * @param {} index
     */
    onFromSelect: function (combo, record, index) {
        const newAccountId = record.get('original_id');
        const defaultSignature = this.app.getDefaultSignature(newAccountId);
        this.updateSignature(newAccountId, defaultSignature);
        this.signatureCombo.setValue(_.get(defaultSignature, 'data.name', this.app.i18n._('None')));

        // update reply-to
        const replyTo = record.get('reply_to');
        if (replyTo && replyTo !== '') {
            this.replyToField.setValue(replyTo);
        }

        this.record.set('account_id', newAccountId);
    },

    /**
     * returns dialog
     *
     * NOTE: when this method gets called, all initialisation is done.
     *
     * @return {Object}
     * @private
     */
    getFormItems: function () {

        this.initAttachmentGrid();
        this.initSignatureCombo();

        this.recipientGrid = new Tine.Felamimail.RecipientGrid({
            record: this.record,
            i18n: this.app.i18n,
            hideLabel: true,
            composeDlg: this,
            autoStartEditing: !this.AddressLoadMask,
        });

        this.southPanel = new Ext.Panel({
            region: 'south',
            layout: 'form',
            height: 150,
            split: true,
            collapseMode: 'mini',
            header: false,
            collapsible: true,
            collapsed: (this.record.bodyIsFetched() && (!this.record.get('attachments') || this.record.get('attachments').length == 0)),
            items: [this.attachmentGrid]
        });

        this.textEditor = new Ext.Panel({
            layout: 'fit',
            mimeType: 'text/plain',
            cls: 'felamimail-edit-text-plain',
            flex: 1,  // Take up all *remaining* vertical space
            setValue: function (v) {
                return this.items.get(0).setValue(v);
            },
            getValue: function () {
                return this.items.get(0).getValue();
            },
            tbar: ['->', {
                iconCls: 'x-edit-toggleFormat',
                tooltip: this.app.i18n._('Convert to formated text'),
                handler: this.onToggleFormat,
                scope: this
            }],
            items: [
                new Ext.form.TextArea({
                    fieldLabel: this.app.i18n._('Body'),
                    name: 'body_text'
                })
            ]
        });

        this.htmlEditor = new Tine.Felamimail.ComposeEditor({
            border: false,
            fieldLabel: this.app.i18n._('Body'),
            name: 'body_html',
            mimeType: 'text/html',
            flex: 1  // Take up all *remaining* vertical space
        });

        this.mailvelopeWrap = new Ext.Container({
            flex: 1,  // Take up all *remaining* vertical space
            mimeType: 'application/pgp-encrypted',
            getValue: function () {
                return '';
            }
        });

        const activeAccount = Tine.Tinebase.appMgr.get('Felamimail').getActiveAccount();
        return {
            border: false,
            frame: true,
            layout: 'border',
            items: [
                {
                    ref: '../messageInfoFormPanel',
                    layout: 'form',
                    region: 'north',
                    border:  false,
                    autoHeight: true,
                    align: 'left',
                    labelWidth: 250,
                    cls: 'felamimail-compose-info',
                    margins: '0 0 10 0',
                    defaults: {
                        hidden: true,
                        xtype: 'label',
                    },
                    items: [
                        {
                            // message file info text
                            ref: '../../messageFileInfoText',
                            style: 'padding: 5px; display: block;',
                        },
                        {
                            // mass mailing info text
                            html: this.app.i18n._('NOTE: This mail will be sent as a mass mail, i.e. each recipient will get his or her own copy.'),
                            ref: '../../massMailingInfoText',
                            style: 'padding: 5px; display: block;',
                        }
                    ]
                },
                {
                    region: 'center',
                    layout: {
                        align: 'stretch',  // Child items are stretched to full width
                        type: 'vbox'
                    },
                    listeners: {
                        'afterlayout': this.fixLayout,
                        scope: this
                    },
                    items: [
                        this.accountCombo,
                        {
                            // extuxclearabletextfield would be better, but breaks the layout big tim
                            // TODO fix layout (equal width of input boxes)!
                            xtype: 'textfield',
                            plugins: [Ext.ux.FieldLabeler],
                            fieldLabel: this.app.i18n._('Reply-To Email'),
                            name: 'reply_to',
                            ref: '../../replyToField',
                            hidden: ! Tine.Tinebase.appMgr.get('Felamimail').featureEnabled('showReplyTo'),
                            emptyText: this.app.i18n._('Add email address here for reply-to'),
                            // reply-to from account or email
                            value: activeAccount ? activeAccount.get('reply_to') : ''
                        },
                        this.recipientGrid,
                        {
                            xtype: 'textfield',
                            plugins: [Ext.ux.FieldLabeler],
                            fieldLabel: this.app.i18n._('Subject'),
                            name: 'subject',
                            ref: '../../subjectField',
                            enableKeyEvents: true,
                            maxLength: 998,
                            // prevent long input
                            autoCreate: {tag: 'input', type: 'text', maxlength: '900'},
                            listeners: {
                                scope: this,
                                // update title on keyup event
                                'keyup': function (field, e) {
                                    if (!e.isSpecialKey()) {
                                        this.window.setTitle(
                                            this.app.i18n._('Compose email:') + ' '
                                            + field.getValue()
                                        );
                                    }
                                },
                                'focus': function (field) {
                                    this.subjectField.focus(true, 100);
                                }
                            }
                        }, new Ext.Toolbar({
                            items: ['->', {xtype: 'tbtext', text: this.app.i18n._('Signature') + ':'}, this.signatureCombo]
                        }), {
                            layout: 'card',
                            ref: '../../bodyCards',
                            activeItem: 0,
                            flex: 1,
                            items: [
                                this.textEditor,
                                this.htmlEditor,
                                this.mailvelopeWrap
                            ]

                        }]
                }, this.southPanel]
        };
    },

    /**
     * is form valid (checks if attachments are still uploading / recipients set)
     *
     * @return {Boolean}
     */
    isValid: function () {
        var me = this;
        return Tine.Felamimail.MessageEditDialog.superclass.isValid.call(me).then(function () {
            if (me.attachmentGrid.isUploading()) {
                return Promise.reject(me.app.i18n._('Files are still uploading.'));
            }

            return me.validateRecipients();
        });
    },

    /**
     *
     * @return {Promise}
     */
    validateSystemlinkRecipients: function () {
        var me = this;

        return new Promise(async function (fulfill, reject) {
            const recipients = [];
            const resolvePromise = fulfill;

            me.recipientGrid.getStore().each(async function (recipient) {
                let address = recipient.get('address');

                if (!address) {
                    return;
                }

                if ( address?.email) {
                    address = address?.email;
                }

                recipients.push(await me.extractMailFromString(address));
            });

            var hasSystemlinks = false;

            me.attachmentGrid.getStore().each(function (attachment) {
                if (attachment.get('attachment_type') === 'systemlink_fm') {
                    hasSystemlinks = true;
                    return false;
                }
            });

            if (hasSystemlinks) {
                Tine.Felamimail.doMailsBelongToAccount(recipients).then(function (res) {
                    resolvePromise(Object.values(res))
                });
            } else {
                resolvePromise(false);
            }

        });
    },

    extractMailFromString: async function (string) {
        return await import(/* webpackChunkName: "Tinebase/js/email-addresses" */ 'email-addresses').then(({default: addrs}) => {
            const parsed = addrs.parseOneAddress(string.replace(',', ''));
            return parsed?.address;
        });
    },

    /**
     * generic apply changes handler
     * - NOTE: overwritten to check here if the subject is empty and if the user wants to send an empty message
     *
     * @param {Ext.Button} button
     * @param {Event} event
     * @param {Boolean} closeWindow
     */
    onApplyChanges: function (closeWindow, emptySubject, passwordSet, nonSystemAccountRecipients) {
        var me = this,
            _ = window.lodash;

        Tine.log.debug('Tine.Felamimail.MessageEditDialog::onApplyChanges()');

        this.loadMask.show();
        if (this.autoSave) {
            this.trottledsaveAsDraft.cancel();
        }

        if (Tine.Tinebase.appMgr.isEnabled('Filemanager') && undefined === nonSystemAccountRecipients) {
            this.validateSystemlinkRecipients().then(function (mails) {
                me.onApplyChanges(closeWindow, emptySubject, passwordSet, mails)
            });
            return;
        } else if (_.isArray(nonSystemAccountRecipients) && nonSystemAccountRecipients.length > 0) {
            let records = _.filter(me.recipientGrid.getStore().data.items, function (rec) {
                let match = false;
                _.each(nonSystemAccountRecipients, function (mail) {
                    if (null !== rec.get('address').match(new RegExp(mail))) {
                        match = true;
                    }
                });

                return match;
            }.bind({'nonSystemAccountRecipients': nonSystemAccountRecipients}));

            _.each(records, function (rec) {
                var index = me.recipientGrid.getStore().indexOf(rec),
                    row = me.recipientGrid.view.getRow(index);

                row.classList.add('felamimail-is-external-recipient');
            });

            Ext.MessageBox.confirm(
                this.app.i18n._('Warning'),
                this.app.i18n._('Some attachments are of type “systemlinks”, but some recipients (marked in yellow) could not be validated as accounts on this installation. Only recipients with an active account will be able to open these attachments.') + "<br /><br />" + this.app.i18n._('Are you sure you want to send?'),
                function (button) {
                    if (button == 'yes') {
                        me.onApplyChanges(closeWindow, emptySubject, passwordSet, false);
                    } else {
                        this.hideLoadMask();
                    }
                },
                this
            );
            return;
        }

        // If filemanager attachments are possible check if passwords are required to enter
        if (Tine.Tinebase.appMgr.isEnabled('Filemanager') && passwordSet !== true) {
            var attachmentStore = this.attachmentGrid.getStore();

            if (attachmentStore.find('attachment_type', 'download_protected_fm') !== -1) {
                var dialog = new Tine.Tinebase.widgets.dialog.PasswordDialog();
                dialog.openWindow();

                // password entered
                dialog.on('apply', function (password) {
                    attachmentStore.each(function (attachment) {
                        if (attachment.get('attachment_type') === 'download_protected_fm') {
                            attachment.data.password = password;
                        }
                    });

                    me.onApplyChanges(closeWindow, emptySubject, true, nonSystemAccountRecipients);
                });

                // user presses cancel in dialog => allow to submit again or edit mail and so on!
                dialog.on('cancel', function () {
                    this.hideLoadMask();
                }, this);
                return;
            }
        }

        if (!emptySubject && !this.getForm().findField('subject').getValue()) {
            Tine.log.debug('Tine.Felamimail.MessageEditDialog::onApplyChanges - empty subject');
            Ext.MessageBox.confirm(
                this.app.i18n._('Empty subject'),
                this.app.i18n._('Are you sure you want to send a message with an empty subject?'),
                function (button) {
                    Tine.log.debug('Tine.Felamimail.MessageEditDialog::doApplyChanges - button: ' + button);
                    if (button == 'yes') {
                        this.onApplyChanges(closeWindow, true, true, nonSystemAccountRecipients);
                    } else {
                        this.hideLoadMask();
                    }
                },
                this
            );

            return;
        }
        this.flattenHtmlElements();

        this.record.set('body', this.bodyCards.layout.activeItem.getValue());

        Tine.log.debug('Tine.Felamimail.MessageEditDialog::doApplyChanges - call parent');
        this.doApplyChanges(closeWindow);
    },
    
    flattenHtmlElements() {
        const classesToRemove = [
            'felamimail-body-signature-current',
            'felamimail-body-manage-consent-link'
        ];
        classesToRemove.forEach(( className) => {
            const matches = this.htmlEditor.getDoc().getElementsByClassName(className);
            while (matches.length > 0) matches[0].outerHTML = matches[0].innerHTML;
        });
        
    },

    /**
     * checks recipients
     *
     * @return {Boolean}
     */
    validateRecipients: function () {
        var me = this;

        return new Promise(function (fulfill, reject) {
            var to = me.record.get('to'),
                cc = me.record.get('cc'),
                bcc = me.record.get('bcc'),
                bcc = me.record.get('bcc'),
                all = [].concat(to).concat(cc).concat(bcc);

            if (all.length == 0) {
                reject(me.app.i18n._('No recipients set.'));
            }

            if (me.button_toggleEncrypt.pressed && me.mailvelopeEditor) {
                // always add own address so send message can be decrypted
                all.push(me.record.get('from_email'));
                
                all = all.map(function (item) {
                    const email = item.email ?? item;
                    return addressparser.parse(email.replace(/,/g, '\\\\,'))[0].address;
                });

                return Tine.Felamimail.mailvelopeHelper.getKeyring().then(function (keyring) {
                    keyring.validKeyForAddress(all).then(function (result) {
                        var missingKeys = [];
                        for (var address in result) {
                            if (!result[address]) {
                                missingKeys.push(address);
                            }
                        }

                        if (missingKeys.length) {
                            reject(String.format(
                                me.app.i18n._('Cannot encrypt message. Public keys for the following recipients are missing: {0}'),
                                Ext.util.Format.htmlEncode(missingKeys.join(', '))
                            ));
                        } else {
                            // NOTE: we sync message here as we have a promise at hand and onRecordUpdate is done before validation
                            return me.mailvelopeEditor.encrypt(all).then(function (armoredMessage) {
                                me.record.set('body', armoredMessage);
                                me.record.set('content_type', 'text/plain');
                                // NOTE: Server would spoil MIME structure with attachments
                                me.record.set('attachments', '');
                                me.record.set('has_attachment', false);
                                fulfill(true);
                            });
                        }
                    });
                });
            } else {
                fulfill(true);
            }
        });
    },

    /**
     * get validation error message
     *
     * @return {String}
     */
    getValidationErrorMessage: function () {
        return this.validationErrorMessage;
    },

    /**
     * show notification for replaced mail sender
     *
     */
    showReplacedMailSenderNotification: function(currentAccountId, newAccountId) {
        const currentAccount = this.app.getAccountStore().getById(currentAccountId);
        const newAccount = this.app.getAccountStore().getById(newAccountId);

        const title = this.app.i18n._('Attention');
        const message = String.format(this.app.i18n._(
            'The from-address for this email was changed to {0}, as you do not have send mail rights for the account {1}'), newAccount?.data?.email, currentAccount?.data?.email);

        Ext.ux.MessageBox.msg(title, message, 5);
    },
});

/**
 * Felamimail Edit Popup
 *
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Felamimail.MessageEditDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 750,
        height: 700,
        name: Tine.Felamimail.MessageEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Felamimail.MessageEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

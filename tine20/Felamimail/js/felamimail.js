/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import '../styles/Felamimail.scss';
import './Model';
import './GridPanelHook';
import './AttachmentUploadGrid';
import './FolderStore';
import './FolderSelect';
import './FolderSelectPanel';
import './sieve/VacationEditDialog';
import './sieve/RuleEditDialog';
import './sieve/RulesGridPanel';
import './sieve/RulesDialog';
import './sieve/RuleConditionsPanel';
import './sieve/NotificationDialog';
import './TreeLoader';
import './TreePanel';
import './MimeDisplayManager';
import './GridDetailsPanel';
import './PGPDetailsPanel';
import './GridPanel';
import './MessageDisplayDialog';
import './MessageEditDialog';
import './AccountEditDialog';
import './ContactSearchCombo';
import './RecipientGrid';
import './MailDetailsPanel';
// require('Tinebase/js/Application');
// require('Tinebase/js/ux/ItemRegistry');
// require('Tinebase/js/widgets/MainScreen');
import './admin/AccountGridPanel';
import './admin/AccountPicker';
import './Application';
import './MainScreen';
import './ComposeEditor';
import './ContactGrid';
import './RecipientPickerFavoritePanel';
import './RecipientPickerDialog';
import './FolderFilterModel';
import './MailvelopeHelper';

/**
 * get email string (n_fileas <email@host.tld>) from contact
 *
 * @param {Tine.Addressbook.Model.Contact} contact
 * @return {String}
 */
Tine.Felamimail.getEmailStringFromContact = function(contact) {
    var result = contact.get('n_fileas') + ' <';
    result += contact.getPreferredEmail().email;
    result += '>';

    return result;
};

/**
 * gets default signature text of given account
 *
 * @param {String|Tine.Felamimail.Model.Account} account
 * @param signatureRecord
 * @return {String}
 */
Tine.Felamimail.getSignature = function(account = null, signatureRecord = null) {
    const app = Tine.Tinebase.appMgr.get('Felamimail');
    let signatureText = '';

    account = _.isString(account) ? app.getAccountStore().getById(account) : account;
    account = account || app.getActiveAccount();

    if (account) {
        Tine.log.info('Tine.Felamimail.getSignature() - Fetch signature of account ' + account.id + ' (' + account.name + ')');
        signatureRecord = signatureRecord || app.getDefaultSignature(account);

        if (signatureRecord && signatureRecord.id !== 'none') {
            // NOTE: signature is always in html, nl2br here would cause duplicate linebreaks!
            signatureText = _.get(signatureRecord, 'data.signature', '');
        }
    }

    return signatureText;
};

/**
 * generic exception handler for felamimail (used by folder and message backends and updateMessageCache)
 *
 * TODO move all 902 exception handling here!
 * TODO invent requery on 902 with cred. dialog
 *
 * @param {Tine.Exception|Object} exception
 */
Tine.Felamimail.handleRequestException = function(exception) {
    if (! exception.code && exception.responseText) {
        // we need to decode the exception first
        var response = Ext.util.JSON.decode(exception.responseText);
        exception = response.data || response;
    }

    Tine.log.warn('Request exception :');
    Tine.log.warn(exception);

    var app = Tine.Tinebase.appMgr.get('Felamimail');

    switch (exception.code) {
        case 910: // Felamimail_Exception_IMAP
        case 911: // Felamimail_Exception_IMAPServiceUnavailable
            Ext.Msg.show({
                title:   app.i18n._('IMAP Error'),
                msg:     exception.message ? exception.message : app.i18n._('No connection to IMAP server.'),
                icon:    Ext.MessageBox.ERROR,
                buttons: Ext.Msg.OK
            });
            break;

        case 912: // Felamimail_Exception_IMAPInvalidCredentials
            var accountId   = exception.account && exception.account.id ? exception.account.id : '',
                account     = accountId ? app.getAccountStore().getById(accountId): null,
                imapStatus  = account ? account.get('imap_status') : null;

            if (account) {
                account.set('all_folders_fetched', true);
                account.commit();
                if (account.get('type') == 'system') {
                    // just show message box for system accounts
                    Ext.Msg.show({
                        title:   app.i18n._('IMAP Credentials Error'),
                        msg:     app.i18n._('Your email login details are incorrect. Please check them or contact your administrator'),
                        icon:    Ext.MessageBox.ERROR,
                        buttons: Ext.Msg.OK
                    });
                } else {
                    app.showCredentialsDialog(account, exception.username);
                }
            } else {
                exception.code = 910;
                return this.handleRequestException(exception);
            }
            break;

        case 913: // Felamimail_Exception_IMAPFolderNotFound
            Ext.Msg.show({
                title:   app.i18n._('IMAP Error'),
                msg:     app.i18n._('One of your folders was deleted or renamed by another client. Please update the folder list of this account.'),
                icon:    Ext.MessageBox.ERROR,
                buttons: Ext.Msg.OK
            });
            // TODO reload account root node
            break;

        case 914: // Felamimail_Exception_IMAPMessageNotFound
            Tine.log.notice('Message was deleted by another client.');

            // remove message from store and select next message
            var requestParams = Ext.util.JSON.decode(exception.request).params,
                centerPanel = app.getMainScreen().getCenterPanel(),
                msg = centerPanel.getStore().getById(requestParams.id);

            if (msg) {
                var sm = centerPanel.getGrid().getSelectionModel(),
                    selectedMsgs = sm.getSelectionsCollection(),
                    nextMessage = centerPanel.getNextMessage(selectedMsgs);

                centerPanel.getStore().remove(msg);
                if (nextMessage) {
                    sm.selectRecords([nextMessage]);
                }
            }
            break;

        case 920: // Felamimail_Exception_SMTP
            Ext.Msg.show({
                title:   app.i18n._('SMTP Error'),
                msg:     exception.message ? exception.message : app.i18n._('No connection to SMTP server.'),
                icon:    Ext.MessageBox.ERROR,
                buttons: Ext.Msg.OK
            });
            break;

        case 930: // Felamimail_Exception_Sieve
            Ext.Msg.show({
                title:   app.i18n._('Sieve Error'),
                msg:     exception.message ? exception.message : app.i18n._('No connection to Sieve server.'),
                icon:    Ext.MessageBox.ERROR,
                buttons: Ext.Msg.OK
            });
            break;

        case 931: // Felamimail_Exception_SievePutScriptFail
            Ext.Msg.show({
                title:   app.i18n._('Save Sieve Script Error'),
                msg:     app.i18n._('Could not save script on Sieve server.') + (exception.message ? ' (' + exception.message + ')' : ''),
                icon:    Ext.MessageBox.ERROR,
                buttons: Ext.Msg.OK
            });
            break;

        default:
            Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
            break;
    }
};

/**
 * get flags store
 *
 * @param {Boolean} reload
 * @return {Ext.data.JsonStore}
 */
Tine.Felamimail.loadFlagsStore = function(reload) {

    var store = Ext.StoreMgr.get('FelamimailFlagsStore');

    if (!store) {
        // create store (get from initial registry data)
        store = new Ext.data.JsonStore({
            fields: Tine.Felamimail.Model.Flag,
            data: Tine.Felamimail.registry.get('supportedFlags'),
            autoLoad: true,
            id: 'id',
            root: 'results',
            totalProperty: 'totalcount'
        });

        Ext.StoreMgr.add('FelamimailFlagsStore', store);
    }

    return store;
};


Tine.Felamimail.registerProtocolHandlerAction = new Ext.Action({
    iconCls: 'FelamimailIconCls',
    handler: function() {
        var url = Tine.Tinebase.common.getUrl() + '#Felamimail/MailTo/%s';
        navigator.registerProtocolHandler('mailto', url, Ext.util.Format.stripTags(Tine.title));
    }
});

Ext.ux.ItemRegistry.registerItem('Tine.Tinebase.MainMenu.userActions', Tine.Felamimail.registerProtocolHandlerAction);

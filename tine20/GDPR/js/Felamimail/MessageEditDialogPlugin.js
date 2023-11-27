
Ext.ns('Tine.GDPR.Felamimail');

Tine.GDPR.Felamimail.MessageEditDialogPlugin = function() {};
Tine.GDPR.Felamimail.MessageEditDialogPlugin.prototype = {
    editDialog: null,
    app: null,

    init: function(editDialog) {
        this.app = Tine.Tinebase.appMgr.get('GDPR');
        this.editDialog = editDialog;
        this.recipientGrid = this.editDialog.recipientGrid;
        this.record = this.editDialog.record;
        this.manageConsentRecordPicker = new Tine.Tinebase.widgets.form.RecordPickerComboBox({
            fieldLabel: this.app.i18n.gettext('Data Intended purpose of this mass mailing'),
            name: 'dataIntendedPurposes',
            width: 300,
            blurOnSelect: true,
            allowBlank: true,
            recordClass: Tine.GDPR.Model.DataIntendedPurpose,
            recordProxy: Tine.GDPR.dataintendedpurposeBackend,
            listeners: {
                scope: this,
                'select': (combo, record, index) => {
                    //TODO: load template from twig config?
                    this.selectedDataIntendedPurpose = record;
                    this.recipientGrid.updateMassMailingRecipients();
                }
            }
        });
        this.editDialog.messageInfoFormPanel.add(this.manageConsentRecordPicker);
        this.editDialog.switchMassMailingMode = this.switchMassMailingMode.createDelegate(this);
        this.recipientGrid.validateRecipientToken = this.validateRecipientToken.createDelegate(this);
    },
    
    switchMassMailingMode(active) {
        this.editDialog.massMailingInfoText.setVisible(active);
        this.manageConsentRecordPicker.setVisible(active);
        this.updateMessageBody(active);
        if (active) this.showDipSelectPicker();
        
        this.recipientGrid.view.refresh();
        this.editDialog.doLayout();
    },
    
    updateMessageBody(active) {
        const locale = Tine.Tinebase.registry.get('locale').locale || 'en';
        const template = Tine.Tinebase.configManager.get('manageConsentEmailTemplate', 'GDPR');
        if (!template) return;
        const translatedTemplate = template?.[locale] ?? null;
        if (!translatedTemplate) return;
        const body = this.record.get('body');
        const format = this.record.get('content_type');
        
        if (format === 'text/plain') {
            if (active  && !body.includes(translatedTemplate)) {
                this.record.set('body', `${body}\n${translatedTemplate}`);
            }
            if (!active && body.includes(translatedTemplate)) {
                this.record.set('body', body.replace(translatedTemplate, ''));
            }
        }
        
        if (format === 'text/html') {
            const consentLinkElement = document.createElement('span');
            consentLinkElement.className = 'felamimail-body-manage-consent-link';
            consentLinkElement.innerHTML = translatedTemplate;
            if (active) {
                this.editDialog.appendHtmlNode(consentLinkElement, 'above', 'felamimail-body-signature-current');
            } else {
                const matches = this.editDialog.htmlEditor.getDoc().getElementsByClassName(consentLinkElement.className);
                while (matches.length > 0) matches[0].parentElement.removeChild(matches[0]);
            }
            this.record.set('body', this.editDialog.bodyCards.layout.activeItem.getValue());
        }
        
        this.editDialog.msgBody = this.record.get('body');
        this.editDialog.bodyCards.layout.activeItem.setValue(this.editDialog.msgBody);
    },
    
    /**
     * validate recipient token in gdpr plugin
     *
     * - skip all validation when it is not in mass mailing mode
     *
     * validation in mass mailing mode:
     * 
     * valid cases when no DataIntendedPurpose selected:
     * - when token type is mailingList/group/list -> but they will resolve to it's members automatically
     * - when token has no contact record -> because it might be an external or a new created contact
     * - when token has no dataIntended purpose records
     * 
     * invalid cases when no DataIntendedPurpose selected:
     * - when contact enables "do not contact this contact at all" in contactEditDialog/GDPR tab
     * - when contact has not consented to this purpose
     * 
     * invalid cases when DataIntendedPurpose is selected:
     * - when contact has not consented to the selected DataIntendedPurpose
     * - when contact consent date is expired
     * - when contact consent date is set but not reached yet
     *
     * @param token
     */
    validateRecipientToken(token) {
        const defaultTip = String.format(this.app.i18n._('In {0} mass mailing mode this recipient will be removed, '), this.app.appName);
        if (!token || !this.recipientGrid.massMailingMode) {
            return  {isValid: true, tip: 'skip checking token'};
        }
        // in mass mailing mode, list should be valid to be displayed in searchContactCombo
        if (token?.emails) {
            const app = this.editDialog.app;
            return {isValid: true, tip: app.i18n._('Send message to individual list members instead')};
        }
        if (!token.contact_record) {
            return {isValid: true, tip: defaultTip + 'GDPR consent missing as no contact record was found'};
        }
        const contact = token.contact_record;
        if (contact?.GDPR_Blacklist) {
            return {isValid: false, tip: defaultTip + this.app.i18n._('as it must not be contacted at all')};
        }
        if (!this.selectedDataIntendedPurpose) {
            return {isValid: true, tip: defaultTip + this.app.i18n._('skip checking contact when data intended purpose has not been selected')};
        }
        if (!contact?.GDPR_DataIntendedPurposeRecord) {
            return {isValid: true, tip: defaultTip + this.app.i18n._('skip checking contact without dataIntended purpose records')};
        }
        const dip = contact.GDPR_DataIntendedPurposeRecord.find((d) => d.intendedPurpose.id === this.selectedDataIntendedPurpose.id);
        if (!dip) {
            return {isValid: false, tip: defaultTip + this.app.i18n._('as contact has not consent to this purpose')};
        }
        if (dip.withdrawDate && new Date() > new Date(dip.withdrawDate)) {
            return {isValid: false, tip: defaultTip + this.app.i18n._('as consent date has expired')};
        }
        if (dip.agreeDate && new Date() < new Date(dip.agreeDate)) {
            return {isValid: true, tip: defaultTip + this.app.i18n._('consent date is set but not reached yet')};
        }
        
        return {isValid: true, tip: 'valid token'};
    },
    
    showDipSelectPicker() {
        const dialog = new Tine.Tinebase.dialog.Dialog({
            windowTitle: this.app.i18n._('Please select a data intended purpose'),
            listeners: {
                apply: (record) => {
                    this.manageConsentRecordPicker.setValue(record);
                    this.selectedDataIntendedPurpose = record;
                    this.recipientGrid.updateMassMailingRecipients();
                },
                cancel: ()  => {
                    this.editDialog.onToggleMassMailing();
                }
            },
            getEventData: function (eventName) {
                if (eventName === 'apply') return this.getForm().findField('dataIntendedPurpose').selectedRecord;
            },
            items: [{
                layout: 'form',
                frame: true,
                width: '100%',
                labelWidth: 300,
                labelAlign: 'top',
                padding: '10px',
                items: [
                    {
                        xtype: 'label',
                        html: "1. " + this.app.i18n._('The recipients will be removed if they have not consent to the selected intended purpose.') + '<br/>'
                            + "2. " + this.app.i18n._("The recipients with 'Must not be contacted' will be removed when no intended purpose is selected."),
                    },
                    Tine.widgets.form.RecordPickerManager.get('GDPR', 'DataIntendedPurpose',
                        { name: 'dataIntendedPurpose'})
                ]
            }],
            openWindow: function (config) {
                if (!this.window) {
                    this.window = Tine.WindowFactory.getWindow(Ext.apply({
                        title: this.windowTitle,
                        closeAction: 'close',
                        modal: true,
                        width: 400,
                        height: 200,
                        layout: 'fit',
                        items: [ this]
                    }, config || {}));
                }
                return this.window;
            }
        });
        dialog.openWindow();
    }
}
Ext.preg('Tine.GDPR.Felamimail.MessageEditDialogPlugin', Tine.GDPR.Felamimail.MessageEditDialogPlugin);
Ext.ux.pluginRegistry.register('/Felamimail/EditDialog/Message', Tine.GDPR.Felamimail.MessageEditDialogPlugin);

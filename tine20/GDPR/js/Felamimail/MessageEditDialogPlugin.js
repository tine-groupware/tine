
Ext.ns('Tine.GDPR.Felamimail');

Tine.GDPR.Felamimail.MessageEditDialogPlugin = function() {};
Tine.GDPR.Felamimail.MessageEditDialogPlugin.prototype = {
    editDialog: null,
    app: null,

    init: function(editDialog) {
        this.app = Tine.Tinebase.appMgr.get('GDPR');
        this.editDialog = editDialog;
        this.recipientGrid = this.editDialog.recipientGrid;
        if (!this.recipientGrid) return;
        if (this.editDialog.massMailingPlugins.includes('poll')) return;
        this.selectedDataIntendedPurpose = '';

        this.record = this.editDialog.record;
        this.manageConsentRecordPicker = new Tine.Tinebase.widgets.form.RecordPickerComboBox({
            fieldLabel: this.app.i18n.gettext('Processing purpose of this mass mailing'),
            name: 'dataIntendedPurposes',
            width: 300,
            allowBlank: true,
            cls: 'felamimail-compose-info',
            recordClass: Tine.GDPR.Model.DataIntendedPurpose,
            recordProxy: Tine.GDPR.dataintendedpurposeBackend,
            listeners: {
                scope: this,
                'select': (combo, record, index) => {
                    //reset the search combo
                    this.recipientGrid.searchCombo.store.load({
                        params: this.recipientGrid.searchCombo.getParams('')
                    });
                    this.selectedDataIntendedPurpose = record;
                    if (this.selectedDataIntendedPurpose) {
                        this.showDipSelectPicker(this.selectedDataIntendedPurpose);
                    }
                }
            }
        });
        this.editDialog.messageInfoFormPanel.add(this.manageConsentRecordPicker);
        this.editDialog.switchMassMailingMode = this.switchMassMailingMode.createDelegate(this);
        this.recipientGrid.validateRecipientToken = this.validateRecipientToken.createDelegate(this);
    },
    
    switchMassMailingMode(active) {
        if (active) this.showDipSelectPicker();

        this.editDialog.massMailingMode = active;
        if (this.recipientGrid) this.recipientGrid.massMailingMode = active;
        this.editDialog.massMailingInfoText.setVisible(active);
        this.manageConsentRecordPicker.setVisible(active);
        this.updateMessageBody(active);
        
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
        const defaultTip = String.format(this.app.i18n._('In {0} mass mailing mode, this recipient will be removed, '), this.app.appName);
        if (!token || !this.recipientGrid.massMailingMode) {
            return  {isValid: true, tip: 'skip checking token'};
        }
        // in mass mailing mode, list should be valid to be displayed in searchContactCombo
        if (token?.emails) {
            const app = this.editDialog.app;
            return {isValid: true, tip: app.i18n._('Send message individually to list members instead')};
        }
        if (!token.contact_record) {
            return {isValid: true, tip: defaultTip + 'GDPR consent missing as no contact record was found'};
        }
        const contact = token.contact_record;
        if (contact?.GDPR_Blacklist) {
            return {isValid: false, tip: defaultTip + this.app.i18n._('as it must not be contacted at all')};
        }
        if (!this.selectedDataIntendedPurpose) {
            return {isValid: true, tip: defaultTip + this.app.i18n._('Skip contact check if no data intended purpose has been selected')};
        }
        if (!contact?.GDPR_DataIntendedPurposeRecord) {
            return {isValid: true, tip: defaultTip + this.app.i18n._('Skip checking contact if no data intended purpose records exist')};
        }
        const dip = contact.GDPR_DataIntendedPurposeRecord.find((d) => d.intendedPurpose.id === this.selectedDataIntendedPurpose.id);
        if (!dip) {
            return {isValid: false, tip: defaultTip + this.app.i18n._('as contact has not consented to this purpose')};
        }
        if (dip.withdrawDate && new Date() > new Date(dip.withdrawDate)) {
            return {isValid: false, tip: defaultTip + this.app.i18n._('as consent date has expired')};
        }
        if (dip.agreeDate && new Date() < new Date(dip.agreeDate)) {
            return {isValid: true, tip: defaultTip + this.app.i18n._('consent date is set but not yet reached')};
        }
        
        return {isValid: true, tip: 'valid token'};
    },
    
    showDipSelectPicker: function(defaultDataIntendedPurpose = '') {
        const isMassMailingMode = this.editDialog.massMailingMode;
        this.dipSelectPicker = new Tine.Tinebase.dialog.Dialog({
            windowTitle: this.app.i18n._('Please select a purpose of processing'),
            listeners: {
                beforeapply: (data) => {
                    if (this.selectedDataIntendedPurpose?.id && !data.recipientMode) {
                        Ext.MessageBox.alert(i18n._('Errors'), i18n._('You need to select an option!'));
                        return false;
                    }
                },
                apply: async (data) => {
                    this.manageConsentRecordPicker.setValue(this.selectedDataIntendedPurpose);
                    if (this.selectedDataIntendedPurpose?.id && data.recipientMode === 'withRecipients') {
                        const { results: tokens } = await Tine.GDPR.getRecipientTokensByIntendedPurpose(this.selectedDataIntendedPurpose?.id)
                        await this.recipientGrid.updateRecipientsToken(null, tokens);
                    }
                    this.recipientGrid.updateMassMailingRecipients();
                    //reset the search combo
                    this.recipientGrid.searchCombo.store.load({
                        params: this.recipientGrid.searchCombo.getParams('')
                    });
                },
                cancel: ()  => {
                    this.selectedDataIntendedPurpose = '';
                    this.manageConsentRecordPicker.setValue(this.selectedDataIntendedPurpose);

                    if (!isMassMailingMode) {
                        this.editDialog.onToggleMassMailing();
                    }
                }
            },
            getEventData: function (eventName) {
                const option = this.getForm().findField('optionGroup').getValue();
                if (eventName === 'apply') return {
                    recipientMode: option || '',
                }
            },
            items: [{
                layout: 'form',
                frame: true,
                width: '100%',
                labelAlign: 'top',
                padding: '10px',
                defaults: {
                    columnWidth: 1,
                },
                items: [
                    {
                        xtype: 'label',
                        html: "1. " + this.app.i18n._('The recipients will be removed if they have not consented to the selected intended purpose.') + '<br/>'
                            + "2. " + this.app.i18n._("The recipients with 'Must not be contacted' will be removed when no intended purpose is selected."),
                    },
                    Tine.widgets.form.RecordPickerManager.get('GDPR', 'DataIntendedPurpose',
                        { 
                            name: 'dataIntendedPurpose',
                            anchor: '100%',
                            value: defaultDataIntendedPurpose,
                            listeners: {
                                scope: this,
                                select: (combo, invoiceRecord, index) => {
                                    this.selectedDataIntendedPurpose = invoiceRecord;
                                    this.dipSelectPicker.ownerCt.optionGroup.setDisabled(!this.selectedDataIntendedPurpose);
                                },
                            }
                        }),
                    {
                        border: false,
                        layout: 'fit',
                        flex: 1,
                        autoScroll: true,
                        items: [{
                            xtype: 'radiogroup',
                            columns: 1,
                            name: 'optionGroup',
                            ref: '../../../optionGroup',
                            disabled: this.selectedDataIntendedPurpose === '',
                            items: [
                                {
                                    boxLabel: this.app.i18n._('Add all contacts having agreed to this purpose'),
                                    name: 'rb-dipr',
                                    inputValue: 'withRecipients'
                                }, {
                                    boxLabel: this.app.i18n._('Compose mass mail without recipients'),
                                    name: 'rb-dipr',
                                    inputValue: 'withoutRecipients'
                                },
                            ]
                        }]
                    }
                ]
            }
            ],
            openWindow: function (config) {
                if (!this.window) {
                    this.window = Tine.WindowFactory.getWindow(Ext.apply({
                        title: this.windowTitle,
                        closeAction: 'close',
                        modal: true,
                        width: 500,
                        height: 250,
                        layout: 'fit',
                        items: [ this]
                    }, config || {}));
                }
                return this.window;
            }
        });
        this.dipSelectPicker.openWindow();
    }
}
Ext.preg('Tine.GDPR.Felamimail.MessageEditDialogPlugin', Tine.GDPR.Felamimail.MessageEditDialogPlugin);
Ext.ux.pluginRegistry.register('/Felamimail/EditDialog/Message', Tine.GDPR.Felamimail.MessageEditDialogPlugin);

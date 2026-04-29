import {PersonaContainer, Personas} from "../../../Tinebase/js/ux/vue/PersonaContainer";

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
        this.draftOrTemplate = this.editDialog.draftOrTemplate;
        this.bootstrapMailRegex = /\s*<meta name="generator" content="bootstrapemail"/;

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
                    } else {
                        this.updateMessageBody();
                    }
                }
            }
        });
        this.massmailingInfo = new Ext.form.Label({
            style: 'padding: 5px; display: block;',
            text: this.app.i18n._("Note: mass mail is sent without GDPR support")
        });
        this.editDialog.messageInfoFormPanel.add(this.manageConsentRecordPicker);
        this.editDialog.messageInfoFormPanel.add(this.massmailingInfo);
        this.editDialog.switchMassMailingMode = this.switchMassMailingMode.createDelegate(this);
        this.recipientGrid.validateRecipientToken = this.validateRecipientToken.createDelegate(this);
        this.recipientGrid.showInvalidContactDialog = this.showInvalidContactDialog.createDelegate(this);
        this.recipientGrid.startEditing = this.startEditing.createDelegate(this);
    },

    startEditing: function(row, col) {
        this.recipientGrid.lastEditedRecord = this.recipientGrid.store.getAt(row);
        if (this.recipientGrid.massMailingMode && col === 0) return;

        const ed = this.recipientGrid.colModel.getCellEditor(col, row);

        if (ed.field?.view && col === 1) {
            const emptyText = this.sendMassMailWithDIP
                ? this.app.i18n._('No matching email address found which agreed to the selected intended purpose.')
                : this.recipientGrid.searchCombo.listEmptyText;

            if (ed.field.view.emptyText !== emptyText) {
                ed.field.view.emptyText = emptyText;
                ed.field.view.el.update(emptyText);
            }
        }

        if (! this.recipientGrid.composeDlg || ! this.recipientGrid.composeDlg.saving) {
            Tine.Felamimail.RecipientGrid.superclass.startEditing.apply(this.recipientGrid, arguments);
        }
    },

    switchMassMailingMode(active, e) {
        if (!active) {
            this.selectedDataIntendedPurpose = '';
            this.massmailingInfo.setVisible(active);
        }
        if (active && e?.type === 'click') this.showDipSelectPicker();
        this.sendMassMailWithDIP = active;
        this.editDialog.massMailingMode = active;
        if (this.recipientGrid) this.recipientGrid.massMailingMode = active;
        this.editDialog.massMailingInfoText.setVisible(active);
        this.manageConsentRecordPicker.setVisible(active);
        this.recipientGrid.view.refresh();
        this.editDialog.doLayout();
    },
    
    async updateMessageBody() {
        const active = !!this.selectedDataIntendedPurpose && this.sendMassMailWithDIP;
        const body = this.record.get('body');
        const format = this.record.get('content_type');
        const doNotUseTemplate = this.dipSelectPicker?.ownerCt ? (this.dipSelectPicker.ownerCt.doNotUseTemplateCheckbox.hidden || this.dipSelectPicker.ownerCt.doNotUseTemplateCheckbox.checked) : !!this.startWithEmptyMail;
        this.startWithEmptyMail = this.draftOrTemplate ? true : doNotUseTemplate;
        this.editDialog.showLoadMask();

        let manageConsentTemplate = '';
        if (active) {
            const result = await Tine.Tinebase.getEmailTwigTemplate('MassMailingPluginManageConsentLink', 'GDPR',
                { dip: this.selectedDataIntendedPurpose }
            );
            manageConsentTemplate = result?.content ?? '';
        }

        this.record.set('context', {
            dip: this.selectedDataIntendedPurpose,
        });

        if (format === 'text/plain') {
            const text = Tine.Tinebase.common.html2text(manageConsentTemplate);

            if (active && !body.includes(text)) {
                this.record.set('body', `${body}\n${text}`);
            }
            if (!active && body.includes(text)) {
                this.record.set('body', body.replace(text, ''));
            }
        }

        if (format === 'text/html') {
            const doc = this.editDialog.htmlEditor.getDoc();
            const consentLinkElementClass = 'felamimail-body-manage-consent-link';
            const matches = doc.getElementsByClassName(consentLinkElementClass);

            while (matches.length > 0) matches[0].parentElement.removeChild(matches[0]);
            const consentLinkElement = document.createElement('div');
            consentLinkElement.className = consentLinkElementClass;

            const isBootstrapMail= this.bootstrapMailRegex.test(this.editDialog.bodyCards.layout.activeItem.getValue());

            if (this.startWithEmptyMail) {
                if (isBootstrapMail) {
                    this.editDialog.bodyCards.layout.activeItem.setValue('');
                }
                if (manageConsentTemplate) {
                    consentLinkElement.innerHTML = manageConsentTemplate;
                    this.editDialog.appendHtmlNode(consentLinkElement, 'above', 'felamimail-body-signature-current');
                }
            } else {
                if (!isBootstrapMail) {
                    this.baseTemplate ??= await Tine.Tinebase.getEmailTwigTemplate('base' , 'Tinebase');
                    this.editDialog.bodyCards.layout.activeItem.setValue(this.baseTemplate?.content ?? '');
                }
                if (active) {
                    const wrapperElement = document.createElement('div');
                    wrapperElement.style.marginTop = '50px';
                    wrapperElement.style.fontSize = '14px';
                    wrapperElement.innerHTML = manageConsentTemplate;
                    consentLinkElement.appendChild(wrapperElement);
                    doc.querySelector('footer').insertAdjacentElement('afterend', consentLinkElement);
                }
            }
            this.record.set('body', this.editDialog.bodyCards.layout.activeItem.getValue());
        }

        this.editDialog.msgBody = this.record.get('body');
        this.editDialog.bodyCards.layout.activeItem.setValue(this.editDialog.msgBody);
        this.editDialog.hideLoadMask();
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
        const defaultTip = String.format(this.app.i18n._('In {0} mass mailing mode, this recipient will be removed, '), this.app.getTitle());
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

    isBodyEmptyAfterRemovingSignature() {
        const body = this.record.get('body');
        const parser = new DOMParser();
        const doc = parser.parseFromString(body, "text/html");

        // Remove the signature span
        const signature = doc.querySelector("span.felamimail-body-signature-current");
        if (signature) signature.remove();

        const text = doc.body.innerText.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, '').trim();
        console.log([...text].map(c => `${c} = U+${c.charCodeAt(0).toString(16).padStart(4,'0')}`));

        return doc.body.innerText
            .replace(/\u200B/g, '')  // remove zero-width spaces (inserted by editor)
            .trim()
            .length === 0;
    },
    
    showDipSelectPicker: function(defaultDataIntendedPurpose = '') {
        const isMassMailingMode = this.editDialog.massMailingMode;
        let hasRecipients = false;
        ['to', 'cc', 'bcc'].forEach(type => {
            if (this.recipientGrid.record.get(type).length > 0) {
                hasRecipients = true;
            }
        })

        const isBootstrapMail= this.bootstrapMailRegex.test(this.editDialog.bodyCards.layout.activeItem.getValue());
        const showUseTemplateButton = !this.draftOrTemplate && (isBootstrapMail || this.isBodyEmptyAfterRemovingSignature());

        this.dipSelectPicker = new Tine.Tinebase.dialog.Dialog({
            windowTitle: this.app.i18n._('Compose a mass email'),
            listeners: {
                beforeapply: (data) => {
                    this.sendMassMailWithDIP = data.sendMassMailWithDIP;
                    this.recipientMode = data.recipientMode;
                    if (this.sendMassMailWithDIP && !this.selectedDataIntendedPurpose) {
                        Ext.MessageBox.alert(i18n._('Errors'), this.app.i18n._('You need to select a purpose!'));
                        return false;
                    }
                },
                apply: async (data) => {
                    this.updateMessageBody();
                    this.manageConsentRecordPicker.setValue(this.selectedDataIntendedPurpose);
                    this.recipientMode = data.recipientMode;
                    if (this.selectedDataIntendedPurpose?.id && this.recipientMode === 'withRecipients') {
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
                    sendMassMailWithDIP: this.ownerCt.sendMassMailWithDIP.getValue()
                }
            },
            items: [{
                xtype: 'panel',
                layout: 'hbox',
                border: false,
                layoutConfig: {
                    align: 'stretch'
                },
                items:[
                    new PersonaContainer({
                        region: 'west',
                        persona: Personas.QUESTION_INPUT,
                        flex: 0,
                        width: 100,
                        height: 200,
                        style: 'padding: 5px; align-content: center;',
                    }),
                    {
                        layout: 'form',
                        frame: true,
                        flex: 1,
                        width: '100%',
                        labelAlign: 'top',
                        padding: '10px',
                        defaults: {
                            columnWidth: 1,
                        },
                        items: [
                            {
                                xtype: 'checkbox',
                                hideLabel: true,
                                boxLabel: String.format(this.app.i18n._('Create mass mail with GDPR support'), this.app.getTitle()),
                                ref: '../../../sendMassMailWithDIP',
                                checked: true,
                                listeners: {
                                    check: (cb, checked) => {
                                        if (!checked) {
                                            this.selectedDataIntendedPurpose = '';
                                            this.dipSelectPicker.ownerCt.optionGroup.setDisabled(true);
                                        }
                                        this.dipSelectPicker.ownerCt.dipPicker.clearValue();
                                        this.dipSelectPicker.ownerCt.dipPicker.setDisabled(!checked);
                                        this.dipSelectPicker.ownerCt.dipPicker.allowBlank = !checked;
                                        this.dipSelectPicker.ownerCt.dipPicker.validate();
                                        this.dipSelectPicker.ownerCt.infoText.setVisible(checked);
                                        this.manageConsentRecordPicker.setVisible(checked);
                                        this.massmailingInfo.setVisible(!checked);
                                        this.recipientGrid.view.refresh();
                                        this.editDialog.doLayout();
                                    }
                                }
                            },
                            {
                                xtype: 'v-alert',
                                ref: '../../../infoText',
                                columnWidth: 1,
                                variant: 'info',
                                label: this.app.formatMessage('The E-Mail gets a sign out link where the recipient can withdraw for the data intended purpose.'),
                            },
                            Tine.widgets.form.RecordPickerManager.get('GDPR', 'DataIntendedPurpose',
                                {
                                    ref: '../../../dipPicker',
                                    name: 'dataIntendedPurpose',
                                    anchor: '100%',
                                    value: defaultDataIntendedPurpose,
                                    fieldLabel: this.app.i18n._('Select a Purpose'),
                                    allowBlank: false,
                                    listeners: {
                                        scope: this,
                                        select: (combo, invoiceRecord, index) => {
                                            this.selectedDataIntendedPurpose = invoiceRecord;
                                            this.dipSelectPicker.ownerCt.optionGroup.setDisabled(!invoiceRecord);
                                        },
                                    }
                                }),
                            {
                                border: false,
                                layout: 'fit',
                                flex: 1,
                                items: [{
                                    xtype: 'radiogroup',
                                    columns: 1,
                                    name: 'optionGroup',
                                    ref: '../../../../optionGroup',
                                    disabled: !this.selectedDataIntendedPurpose,
                                    items: [
                                        {
                                            boxLabel: this.app.i18n._('Add all contacts having agreed to this purpose'),
                                            name: 'rb-dipr',
                                            inputValue: 'withRecipients',
                                        }, {
                                            boxLabel: this.app.i18n._('Compose mass mail without recipients'),
                                            name: 'rb-dipr',
                                            inputValue: 'withoutRecipients',
                                        },
                                    ]
                                }]
                            },
                            {
                                xtype: 'checkbox',
                                hideLabel: true,
                                hidden: !showUseTemplateButton,
                                boxLabel: this.app.i18n._('Do not use Mail Template'),
                                ref: '../../../doNotUseTemplateCheckbox',
                            },
                        ]
                    }
                ]
            }],
            openWindow: function (config) {
                if (!this.window) {
                    this.window = Tine.WindowFactory.getWindow(Ext.apply({
                        title: this.windowTitle,
                        closeAction: 'close',
                        modal: true,
                        width: 650,
                        height: 300,
                        layout: 'fit',
                        items: [ this]
                    }, config || {}));
                }
                return this.window;
            }
        });
        this.dipSelectPicker.openWindow();
        this.dipSelectPicker.ownerCt.optionGroup.setValue(hasRecipients ? 'withoutRecipients' : 'withRecipients');
        this.dipSelectPicker.ownerCt.doNotUseTemplateCheckbox[showUseTemplateButton ? 'show': 'hide']();
    },

    showInvalidContactDialog(contactsToResolve,  buttonOptions = ['No', 'Yes']) {
        return new Promise((resolve) => {
            if (contactsToResolve.length === 0) return resolve(true);

            const dialog = Tine.widgets.dialog.FileListDialog.openWindow({
                modal: true,
                allowCancel: false,
                height: Math.min(500, 160 + Math.max(0, contactsToResolve.length - 1) * 25),
                width: 500,
                title: this.app.i18n._('Missing Consent'),
                text: this.recipientGrid.getInvalidContactData(contactsToResolve),
                alertText: this.app.i18n._('The following recipients will be removed from this mass mailing because they have not provided their consent.'),
                scope: this,
                buttonOptions: buttonOptions,
                handler: async (button) => {
                    resolve(['YES', 'OK'].includes(button.toUpperCase()));
                }
            });
        });
    },
}
Ext.preg('Tine.GDPR.Felamimail.MessageEditDialogPlugin', Tine.GDPR.Felamimail.MessageEditDialogPlugin);
Ext.ux.pluginRegistry.register('/Felamimail/EditDialog/Message', Tine.GDPR.Felamimail.MessageEditDialogPlugin);

/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import FieldTriggerPlugin from "../../ux/form/FieldTriggerPlugin";
import getTwingEnv from "twingEnv";
import {PersonaContainer, Personas} from "../../ux/vue/PersonaContainer";

Ext.ns('Tine.Tinebase.widgets.dialog');

Tine.Tinebase.widgets.dialog.ResetPasswordDialog = Ext.extend(Tine.Tinebase.dialog.Dialog, {
    hasPwGen: true,
    allowEmptyPassword: false,
    record: null,
    contactRecord: null,

    initComponent: function() {
        this.windowTitle = i18n._('Set new password');
        this.questionText = i18n._('Please enter the new Password:');
        const accountBackend = Tine.Tinebase.registry.get('accountBackend');
        this.ldapBackend = (accountBackend === 'Ldap' || accountBackend === 'ActiveDirectory');

        this.hasSmsAdapters = Tine.Tinebase.registry.get('hasSmsAdapters');

        const locale = Tine.Tinebase.registry.get('locale').locale || 'en';
        const smsTemplates = Tine.Tinebase.configManager.get('sms.sms_message_templates', 'Tinebase');
        this.smsNewPasswordTemplate = smsTemplates?.['sms_new_password_template']?.[locale] ?? '';

        this.twingEnv = getTwingEnv();
        const loader = this.twingEnv.getLoader();
        loader.setTemplate('smsNewPasswordTemplate', this.smsNewPasswordTemplate);

        if (!this.contactRecord) {
            this.contactRecord = Tine.Tinebase.data.Record.setFromJson(this.record.get('contact_id'), Tine.Addressbook.Model.Contact);
        }

        this.mustChangeTriggerPlugin = new FieldTriggerPlugin({
            visible: false,
            doAssertState: false,
            triggerConfig: {tag: "div", cls: "x-form-trigger-flat x-form-trigger-plugin x-form-localized-field tinebase-trigger-overlay"},
            onTriggerClick:  Ext.emptyFn,
            qtip: i18n._('Password is expired in accordance with the password policy and needs to be changed'),
            preserveElStyle: true
        })

        const validationText = i18n._('The character string {{ password }} is automatically replaced by the password when the message is sent and must be included in the message.');

        this.items = [{
            border: false,
            frame: true,
            layout: 'border',
            items: [{
                region: 'center',
                border: false,
                xtype: 'columnform',
                labelAlign: 'top',
                formDefaults: {
                    xtype: 'textfield',
                    anchor: '100%',
                    labelSeparator: '',
                    columnWidth: 1
                },
                items: [
                    [{
                        xtype: 'tw-passwordTriggerField',
                        fieldLabel: this.passwordFieldLabel || i18n._('Password'),
                        name: 'password',
                        maxLength: 100,
                        allowBlank: this.allowEmptyPassword,
                        locked: true,
                        clipboard: this.hasPwGen,
                        ref: '../../../../passwordField',
                        value: this.record.get('accountPassword'),
                        listeners: {
                            scope: this,
                            paste: this.onChange,
                            keyup: this.onChange,
                            keydown: this.onKeyDown
                        },
                        columnWidth: 0.5,
                    }, {
                        hideLabel: true,
                        xtype: 'checkbox',
                        boxLabel: i18n.gettext('Password Must Change'),
                        hidden: this.ldapBackend,
                        ctCls: 'admin-checkbox',
                        fieldClass: 'admin-checkbox-box',
                        name: 'password_must_change',
                        plugins: [this.mustChangeTriggerPlugin],
                        columnWidth: 0.5,
                        checked: true,
                    }], [{
                        xtype: 'combo',
                        fieldLabel: i18n.gettext('Mobile'),
                        name: 'sms_phone_number',
                        ref: '../../../../phoneCombo',
                        store: new Ext.data.ArrayStore({
                            idIndex: 0,
                            fields: ['name', 'value', 'display_value']
                        }),
                        mode: 'local',
                        triggerAction: 'all',
                        editable: true,
                        valueField: 'value',
                        displayField: 'display_value',
                        forceSelection: false,
                        hidden: !this.hasSmsAdapters,
                        columnWidth: 0.5,
                        allowBlank: false,
                    }, {
                        hideLabel: true,
                        xtype: 'checkbox',
                        boxLabel: i18n.gettext('Send password via SMS'),
                        hidden: !this.hasSmsAdapters,
                        disabled: false,
                        ctCls: 'admin-checkbox',
                        fieldClass: 'admin-checkbox-box',
                        name: 'send_password_via_sms',
                        ref: '../../../../sendPWDViaSMSCheckbox',
                        checked: true,
                        listeners: {
                            scope: this,
                            check: async function (cb, checked) {
                                this.phoneCombo.setDisabled(!checked);
                                this.phoneCombo.validate();
                                this.smsTemplate.setDisabled(!checked);
                            }
                        },
                        columnWidth: 0.5
                    }, {
                        fieldLabel: i18n.gettext('SMS Message') + Tine.widgets.form.FieldManager.getDescriptionHTML(validationText),
                        xtype: 'textarea',
                        name: 'sms_new_password_template',
                        anchor: '100%',
                        height: 200,
                        ref: '../../../../smsTemplate',
                        labelSeparator: '',
                        columnWidth: 1,
                        allowBlank: false,
                        hidden: !this.hasSmsAdapters,
                        tpl: this.smsNewPasswordTemplate,
                        validator: function (value) {
                            if (!value.includes('{{ password }}')) {
                                return validationText;
                            } else {
                                return true;
                            }
                        },
                    }]
                ]
            }, new PersonaContainer({
                region: 'west',
                persona: Personas.QUESTION_INPUT
            })]
        }];

        if (this.questionText) {
            this.items[0].items[0].items[0].unshift({
                xtype: 'label',
                text: this.questionText
            }, {
                xtype: 'label',
                html: '<br>'
            })
        }
        this.on('apply', async function() {
            this.fireEvent('selected', this.nodes);
        }, this);

        Tine.Tinebase.widgets.dialog.ResetPasswordDialog.superclass.initComponent.call(this);
    },

    afterRender: function () {
        if (this.hasSmsAdapters) {
            this.loadSMSContactPhoneNumbers(this.contactRecord);
            this.onUpdateSMSNewPasswordTemplate(this.contactRecord);
        }
        Tine.Tinebase.widgets.dialog.ResetPasswordDialog.superclass.afterRender.call(this);
    },

    async onUpdateSMSNewPasswordTemplate() {
        const compiledTemplate = await this.twingEnv.render('smsNewPasswordTemplate', {
            password: '{{ password }}',
            app: {branding: {title: Tine.Tinebase.registry.get('brandingTitle')}}
        });

        this.smsTemplate.setValue(compiledTemplate);
    },

    async loadSMSContactPhoneNumbers(contact) {
        const phoneFields = _.sortBy(_.filter(Tine.Addressbook.Model.Contact.getModelConfiguration().fields, (field) => {
            return field?.specialType === 'Addressbook_Model_ContactProperties_Phone' && contact?.data?.[field.fieldName];
        }), (field) => {
            return _.get(field, 'uiconfig.sort')
        });
        const mobilePhones = phoneFields.map((phoneField) => {
            return [phoneField.fieldName, contact?.data?.[phoneField.fieldName], `${contact?.data?.[phoneField.fieldName]} [${phoneField.label}]`];
        });
        const smsMfaConfig =  this.record.get('mfa_configs') ? this.record.get('mfa_configs').find((mfaConfig) => {
            return mfaConfig.config_class === 'Tinebase_Model_MFA_SmsUserConfig';
        }): null;
        if (smsMfaConfig?.config?.cellphonenumber) {
            mobilePhones.push(['mfa', smsMfaConfig.config.cellphonenumber, `${smsMfaConfig.config.cellphonenumber} [MFA]`])
        }
        this.phoneCombo.store.loadData(mobilePhones);
    },

    onKeyDown: function(f, e) {
        if (e.getKey() === e.ENTER) {
            this.onButtonApply()
        }
    },

    /**
     * Disable ok button if no password entered
     * @param el
     */
    onChange: function (el) {
        _.defer(() => {
            const isPasswordEmpty = el.getValue().length === 0;
            this.getForm().findField('password_must_change').setDisabled(false);
            this.buttonApply.setDisabled(!this.allowEmptyPassword && isPasswordEmpty);
            if (this.hasSmsAdapters) {
                if (this.sendPWDViaSMSCheckbox) this.sendPWDViaSMSCheckbox.setDisabled(isPasswordEmpty);
                this.onUpdateSMSNewPasswordTemplate();
            }
        })
    },

    getEventData: function (event) {
        if (event === 'apply') {
            this.record.set('accountPassword', this.getForm().findField('password').getValue());
            this.record.set('password_must_change', this.getForm().findField('password_must_change').getValue())
            return this.record;
        }
    },

    onButtonApply: async function() {
        if (!this.sendPWDViaSMSCheckbox.checked) {
            return Tine.Tinebase.widgets.dialog.ResetPasswordDialog.superclass.onButtonApply.apply(this, arguments);
        }
        if (!this.phoneCombo.validate()) return;
        if (!this.smsTemplate.validate()) return;

        const passwordMustChange = this.getForm().findField('password_must_change').getValue();
        const smsPhoneNumber = this.phoneCombo.getValue();
        const password = this.passwordField.getValue();
        const smsNewPasswordTemplate = this.smsTemplate.getValue();

        await Tine.Admin.resetPassword.setRequestContext({
            'sms-phone-number':     smsPhoneNumber ?? '',
            'sms-new-password-template': smsNewPasswordTemplate ?? '',
        })
        .call(this, this.record, password, passwordMustChange)
        .then((response) => {
            return Tine.Tinebase.widgets.dialog.ResetPasswordDialog.superclass.onButtonApply.apply(this, arguments);
        })
        .catch((e) => {
            Ext.Msg.alert(i18n._('Errors'), this.app.i18n._(e.data.message));
        })
    },


    /**
     * Creates a new pop up dialog/window (acc. configuration)
     *
     * @returns {null}
     */
    openWindow: function (config) {
        config = config || {};
        this.window = Tine.WindowFactory.getWindow(Ext.apply({
            title: this.windowTitle,
            closeAction: 'close',
            modal: true,
            width: 500,
            height: Math.max(250 +
                (Math.ceil(this.questionText.length/70) * 20)),
            layout: 'fit',
            items: this
        }, config));

        return this.window;
    }
});

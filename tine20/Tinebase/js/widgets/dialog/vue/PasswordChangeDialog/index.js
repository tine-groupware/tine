/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Sohan Deshar <sdeshar@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

import {Personas} from "../../../../ux/vue/PersonaContainer";

Ext.ns('Tine', 'Tine.Tinebase', 'Tine.widgets.dialog');

/**
 * @namespace  Tine.Tinebase
 * @class      Tine.Tinebase.PasswordChangeDialog
 * @extends    Tine.widgets.dialog.ModalDialog
 */
Tine.Tinebase.PasswordChangeDialog = Ext.extend(Tine.widgets.dialog.ModalDialog, {

    dialogText: '',
    pwType: 'password',

    initComponent: async function() {
        this.persona = Personas.QUESTION_INPUT
        this.buttons = this.getButtons()
        this.currentAccount = Tine.Tinebase.registry.get('currentAccount');
        this.passwordLabel = this.pwType === 'pin' ? i18n._('PIN') : i18n._('Password');
        this.title = (this.title !== null) ? this.title : String.format(
            i18n._('Change {0} For "{1}"'),
            this.passwordLabel,
            this.currentAccount.accountDisplayName
        );

        this.supr().initComponent.call(this)
        this.contentProps = window.vue.reactive({
            passwordLabel: this.passwordLabel,
            dialogText: this.dialogText,
            askOldPassword: !this.currentAccount.xprops.hasRandomPwd
        })
        const { default: PasswordChangeDialog } = await import(/* webpackChunkName: "Tinebase/js/vPasswordChangeDialog"*/'./PasswordChangeDialog.vue')
        this.dlgContentComponent = PasswordChangeDialog
        this.vueEventBus.on('close', this.destroy.bind(this))
        this.vueEventBus.on('cancel', this.destroy.bind(this))
        this.vueEventBus.on('ok', this.onOk.bind(this))
        this.postInit()
    },

    getButtons: function() {
        return [{
            name: 'cancel',
            text: i18n._('Cancel'),
            iconCls: 'action_cancel',
            eventName: 'cancel'
        }, {
            name: 'ok',
            text: i18n._('Ok'),
            iconCls: 'action_saveAndClose',
            eventName: 'ok'
        }]
    },

    onOk: function(values){
        this.showMask(String.format(i18n._('Changing {0}'), this.passwordLabel));
        const me = this
        // TODO: form validation
        if (values.newPassword === values.newPasswordSecondTime) {
            Ext.Ajax.request({
                params: {
                    method: this.pwType === 'pin' ? 'Tinebase.changePin' : 'Tinebase.changePassword',
                    oldPassword: values.oldPassword,
                    newPassword: values.newPassword
                },
                success: function(_result, _request){
                    me.hideMask()
                    var response = Ext.util.JSON.decode(_result.responseText);
                    if (response.success) {
                        me.destroy()
                        Ext.MessageBox.show({
                            title: i18n._('Success'),
                            msg: String.format(i18n._('Your {0} has been changed.'), me.passwordLabel),
                            buttons: Ext.MessageBox.OK,
                            icon: Ext.MessageBox.INFO
                        });
                        if (me.pwType === 'password') {
                            Ext.Ajax.request({
                                params: {
                                    method: 'Tinebase.updateCredentialCache',
                                    password: values.newPassword
                                }
                            });
                            Tine.Tinebase.registry.set('mustchangepw', '');
                        }
                    } else {
                        Ext.MessageBox.show({
                            title: i18n._('Failure'),
                            msg: Ext.util.Format.nl2br(response.errorMessage),
                            buttons: Ext.MessageBox.OK,
                            icon: Ext.MessageBox.ERROR
                        });
                    }
                },
                scope: this
            });
        } else {
            this.hideMask()
            Ext.MessageBox.show({
                title: i18n._('Failure'),
                msg: String.format(i18n._('{0} mismatch, please correct.'), this.passwordLabel),
                buttons: Ext.MessageBox.OK,
                icon: Ext.MessageBox.ERROR
            });
        }

    }
});

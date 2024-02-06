/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

 // import {PersonaContainer, Personas} from "./ux/vue/PersonaContainer";

Ext.ns('Tine', 'Tine.Tinebase');
 
 /**
  * @namespace  Tine.Tinebase
  * @class      Tine.Tinebase.PasswordChangeDialog
  * @extends    Ext.Window
  */
Tine.Tinebase.PasswordChangeDialog = Ext.extend(Ext.Window, {
    
    id: 'changePassword_window',
    closeAction: 'close',
    modal: true,
    width: 350,
    height: 230,
    minWidth: 350,
    minHeight: 230,
    layout: 'column',
    plain: true,
    title: null,
    // password or pin
    pwType: 'password',
    dialogText: '',

    initComponent: function() {
        this.currentAccount = Tine.Tinebase.registry.get('currentAccount');
        this.passwordLabel = this.pwType === 'pin' ? i18n._('PIN') : i18n._('Password');
        this.title = (this.title !== null) ? this.title : String.format(
            i18n._('Change {0} For "{1}"'),
            this.passwordLabel,
            this.currentAccount.accountDisplayName
        );
        this.items = []
        // const persona = new PersonaContainer({persona: Personas.QUESTION_CONFIRM, columnWidth: .2})
        // this.items.push(persona)
        this.items.push(new Ext.FormPanel({
            // columnWidth: .8,
            autoHeight: true,
            bodyStyle: 'padding:5px;',
            buttonAlign: 'right',
            labelAlign: 'top',
            anchor:'100%',
            id: 'changePasswordPanel',
            defaults: {
                xtype: 'tw-passwordTriggerField',
                anchor: '100%'
            },
            items: [{
                xtype: 'textarea',
                readOnly: true,
                hideLabel: true,
                style: 'padding-bottom: 10px;',
                hidden: !this.dialogText,
                value: this.dialogText
            }, {
                id: 'oldPassword',
                clipboard: false,
                fieldLabel: String.format(i18n._('Old {0}'), this.passwordLabel),
                name:'oldPassword'
            },{
                id: 'newPassword',
                autocomplete: 'new-password',
                fieldLabel: String.format(i18n._('New {0}'), this.passwordLabel),
                name:'newPassword'
            },{
                id: 'newPasswordSecondTime',
                autocomplete: 'new-password',
                fieldLabel: String.format(i18n._('Repeat new {0}'), this.passwordLabel),
                name:'newPasswordSecondTime'
            }],
            buttons: [{
                text: i18n._('Cancel'),
                iconCls: 'action_cancel',
                handler: function() {
                    Ext.getCmp('changePassword_window').close();
                }
            }, {
                text: i18n._('Ok'),
                iconCls: 'action_saveAndClose',
                handler: function() {
                    var form = Ext.getCmp('changePasswordPanel').getForm();
                    var values;
                    if (! this.loadMask) {
                        this.loadMask = new Ext.LoadMask(this.getEl(), {msg: String.format(i18n._('Changing {0}'), this.passwordLabel)});
                    }
                    this.loadMask.show();

                    if (form.isValid()) {
                        values = form.getFieldValues();
                        if (values.newPassword == values.newPasswordSecondTime) {
                            Ext.Ajax.request({
                                params: {
                                    method: this.pwType === 'pin' ? 'Tinebase.changePin' : 'Tinebase.changePassword',
                                    oldPassword: values.oldPassword,
                                    newPassword: values.newPassword
                                },
                                success: function(_result, _request){
                                    this.loadMask.hide();
                                    var response = Ext.util.JSON.decode(_result.responseText);
                                    if (response.success) {
                                        Ext.getCmp('changePassword_window').close();
                                        Ext.MessageBox.show({
                                            title: i18n._('Success'),
                                            msg: String.format(i18n._('Your {0} has been changed.'), this.passwordLabel),
                                            buttons: Ext.MessageBox.OK,
                                            icon: Ext.MessageBox.INFO
                                        });
                                        if (this.pwType === 'password') {
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
                            this.loadMask.hide();
                            Ext.MessageBox.show({
                                title: i18n._('Failure'),
                                msg: String.format(i18n._('{0} mismatch, please correct.'), this.passwordLabel),
                                buttons: Ext.MessageBox.OK,
                                icon: Ext.MessageBox.ERROR
                            });
                        }
                    }
                },
                scope: this
            }]
        }));
        
        Tine.Tinebase.PasswordChangeDialog.superclass.initComponent.call(this);
    }
});

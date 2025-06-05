/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Generic from './Generic'
import getTwingEnv from "../../twingEnv.es6";

class Sms extends Generic {
    constructor (config) {
        super(config)
        this.isOTP = true
        this.windowTitle = i18n._('SMS security code required')
        this.questionText = formatMessage('This area is locked. To unlock it we send a securitycode to via {mfaDevice.device_name}.', this)
        this.passwordFieldLabel = formatMessage('Security code from {mfaDevice.device_name}', this);
        this.additionalFields = [new Ext.Action({
            text: i18n._('Didn\'t receive the SMS?'),
            handler: this.handleSMSNotReceived.createDelegate(this),
            showText: true,
        })
        ]
    }

    handleSMSNotReceived() {
        Ext.MessageBox.prompt(i18n._('Send Support request to admin?'), i18n._('Please enter the message:'), async (btn, message) => {
            if (btn === 'ok') {
                const body = {
                    authToken: this.mfaDevice.config.authToken ?? null,
                    accountLoginName: this.username,
                    date: Date.now(),
                    message: message
                }
                await fetch('Tinebase/sendSupportRequest', {
                    method: 'POST',
                    body: JSON.stringify(body)
                }).then((r) => {
                    Ext.Msg.show({
                        buttons: Ext.Msg.OK,
                        icon: Ext.MessageBox.INFO,
                        title: i18n._('Success'),
                        msg: i18n._('Support request has been sent')
                    });
                }).catch((e) => {
                    Ext.Msg.alert(i18n._('Errors'), i18n._(e.data.message));
                })
            }
        }, this, true, i18n._('I didn\'t receive a SMS message.'));
    }
    
    async unlock (opts) {
        const triggerMFAMethod = this.triggerMFAMethod || Tine.Tinebase_AreaLock.triggerMFA
        const result = triggerMFAMethod(this.mfaDevice.id);
        return super.unlock(opts)
    }
}

export default Sms

/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import waitFor from "util/waitFor.es6";

Ext.namespace('Tine.MatrixSynapseIntegrator');

Tine.MatrixSynapseIntegrator.MainScreen = Ext.extend(Ext.BoxComponent, {
    hideMode: 'visibility', // just initially, see changeHideMode
    url: null,
    autoEl: { tag: 'div', cls: 't-app-matrixsynapseintegrator', cn: [
        { tag: 'iframe', style: 'width:100%; height: 100%; border: none; visibility: hidden;', allow: 'camera; microphone; display-capture', scrolling: 'no' },
        { tag: 'div', cls: 'tine-viewport-waitcycle'}
    ]},

    // @TODO move somewhere
    sha256: async function (message){
        const msgBuffer = new TextEncoder().encode(message);
        const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    },

    changeHideMode: function() {
        // NOTE: we start client in background to show unread count (see Application::init)
        // when hideMode === 'display' element's feature detection fails (e.g. display-table)
        // but with hideMode === 'visibility' element is displayed on bottom of the page when fully loaded
        // so initially we hide by visibility and change to display once feature detection has passed
        if (this.hideMode === 'visibility') {
            this.hideMode = 'display';
            if (this.isHidden()) {
                this.getVisibilityEl().addClass('x-hide-display');
                this.getVisibilityEl().removeClass('x-hide-visibility');
            }
        }
    },

    showClient: function() {
        this.clientFrame.dom.style.visibility = 'visible'
        this.loadingIndicator.hide();
    },

    showUnavailableAlertIf: async function() {
        await this.initPromise
        if (!this.isAvailable && this.isVisible()) {
            Ext.Msg.show({
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.INFO,
                title: this.app.formatMessage('No chat account has been assigned'),
                msg: this.app.formatMessage('You have not yet been assigned an chat account. This view is therefore empty')
            });
        }
    },

    initComponent: function () {
        this.app = Tine.Tinebase.appMgr.get('MatrixSynapseIntegrator');

        this.initPromise = Promise.all([
            this.afterIsRendered(),
            Tine.MatrixSynapseIntegrator.getBootstrapdata().catch(() => {})
        ]).then(async values => {
            const [cmp, bootstrapData] = values;
            this.bootstrapData = bootstrapData;
            const url = Tine.Tinebase.configManager.get('elementUrl', 'MatrixSynapseIntegrator', '')
            this.url = new URL(url.replace('{MATRIX_USER_ID}', await this.sha256(this.bootstrapData?.mx_user_id)));

            this.isAvailable = !!this.bootstrapData && !!url
            if (this.isAvailable) {
                this.clientFrame = this.el.down('iframe')
                this.loadingIndicator = this.el.down('.tine-viewport-waitcycle')
                this.loadingIndicator.on('dblclick', this.showClient, this)

                this.clientFrame.dom.src = this.url.href
            } else {
                this.changeHideMode()
                this.showUnavailableAlertIf()
            }
        })


        window.addEventListener("message", async (event) => {
            // note: elementRequestCredentials is only send by element, if it requires credentials and can not bootstrap from local storage
            if (event.origin !== this.url?.origin) return;
            console.error(event)

            const notificationMap = new Map()
            
            switch (event.data.type) {
                case "elementBootstrapdataRequest":
                    this.changeHideMode()
                    event.source.postMessage(Object.assign({
                        type: "elementBootstrapdataResponse",
                        eventUUID: event.data.eventUUID,
                    }, this.bootstrapData), this.url.origin);
                    break;
                case "elementLogindataRequest":
                    event.source.postMessage(Object.assign({
                        type: "elementLogindataResponse",
                        eventUUID: event.data.eventUUID,
                    }, await Tine.MatrixSynapseIntegrator.getLogindata()), this.url.origin);
                    break;
                case "elementSetupEncryptionDone":
                    this.showClient()
                    break
                case "elementStartupFailure":
                    this.showClient()
                    if (event.data.failure === 'recoveryKeyIncorrect') {
                        const [btn, string] = await Ext.MessageBox.show({
                            title: this.app.formatMessage('Recovery Key or Password Needed'),
                            msg: this.app.formatMessage('{ brandingTitle } needs your recovery key/password to access your chats. The provided key/password will be saved in a secret store on this server.', {
                                brandingTitle: Tine.Tinebase.registry.get('brandingTitle')
                            }),
                            buttons: Ext.MessageBox.OKCANCEL,
                            icon: Ext.MessageBox.QUESTION_INPUT,
                            prompt: true
                        });
                        if (btn === 'ok') {
                            // @TODO save in account, reload frame
                            // await Tine.MatrixSynapseIntegrator.setRecoveryPassword(string)
                            // || await Tine.MatrixSynapseIntegrator.setRecoveryKey(string)

                        } else {
                            // show info for user?
                            // let user use client
                            // ask with element prompt?
                        }
                    }
                    break
                case "elementSendNotification":
                    const notification = new Notification(event.data.notification.title, {
                        body: event.data.notification.body,
                        silent: event.data.notification.silent,
                        icon: event.data.notification.icon,
                    })

                    notificationMap.set(event.data.notification.uuid, notification)

                    notification.onclick = () => {
                        //todo jump to chat tab
                        event.source.postMessage({
                            type: "elementNotificationOnClick",
                            eventUUID: event.data.eventUUID,
                        }, this.url.origin);
                    }
                    break
                case "elementSetNotificationCount":
                    this.app.setDockBadge(event.data.count)
                    // console.error(`ELEMENT-NOTIFICATION-COUNT: ${event.data.count}`)
                    break
                case "elementNotificationClear":
                    // todo: dose this work?
                    const notif = notificationMap.get(event.data.notification.uuid)
                    if (notif.close) {
                        notif.close();
                    }
                    break
                case "elementNotificationPermissionRequest":
                    if (window.Notification.permission === "granted") {
                        event.source.postMessage({
                            type: "elementNotificationPermissionResponse",
                            eventUUID: event.data.eventUUID,
                            grant: "granted",
                        }, this.url.origin);
                    } else {
                        window.Notification.requestPermission().then((grant) => {
                            event.source.postMessage({
                                type: "elementNotificationPermissionResponse",
                                eventUUID: event.data.eventUUID,
                                grant: grant,
                            }, this.url.origin);
                        }).catch(() => {
                            event.source.postMessage({
                                type: "elementNotificationPermissionResponse",
                                eventUUID: event.data.eventUUID,
                                grant: "denied",
                            }, this.url.origin);
                        })
                    }
                    break;
                default:
                    return
            }
        }, false);

        this.supr().initComponent.call(this);
    },
});
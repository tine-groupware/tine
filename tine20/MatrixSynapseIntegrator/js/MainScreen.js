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
    url: null,
    autoEl: { tag: 'div', cls: 't-app-matrixsynapseintegrator', cn: [
        { tag: 'iframe', style: 'width:100%; height: 100%; border: none; visibility: hidden;', allow: 'camera; microphone; display-capture', scrolling: 'no' },
        { tag: 'div', cls: 'tine-viewport-waitcycle'}
    ]},

    clientRPC: async function (type, args, timeout=1000) {
        return new Promise((resolve, reject) => {
            const eventUUID = Tine.Tinebase.data.Record.generateUID()
            this.clientRPCCallbacks[eventUUID] = { resolve, reject }
            this.clientFrame.dom.contentWindow.postMessage({ type, eventUUID, args }, this.url.origin)
            try {
                waitFor(() => !this.clientRPCCallbacks.hasOwnProperty(eventUUID), timeout)
            } catch (e) {
                reject(`${type} rpc call did not respond within ${timeout}ms`)
            }
        })
    },

    // @TODO move somewhere
    sha256: async function (message){
        const msgBuffer = new TextEncoder().encode(message);
        const hashBuffer = await crypto.subtle.digest('SHA-256', msgBuffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    },

    showClient: function() {
        this.clientFrame.dom.style.visibility = 'visible'
        this.loadingIndicator.hide();
    },

    onActivate: async function() {
        await this.showUnavailableAlertIf()
        this.onClientResize()
    },

    onClientResize: function() {
        if (this.clientFrame) {
            // fix ui glitch - element somehow shows half the client only after some time in background
            this.clientFrame.setHeight(this.el.getHeight()-1)
            _.delay(() => {
                this.clientFrame.setHeight(this.el.getHeight())
            }, 100)
        }
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

        this.clientRPCCallbacks = {}

        this.initPromise = Promise.all([
            this.afterIsRendered(),
            Tine.MatrixSynapseIntegrator.getBootstrapdata().catch(() => {})
        ]).then(async values => {
            const [cmp, bootstrapData] = values;
            this.bootstrapData = bootstrapData;
            const url = Tine.Tinebase.configManager.get('elementUrl', 'MatrixSynapseIntegrator', '')
            // shortening hash to 63 chars. Domain labels must not be longer than 63 characters.
            this.url = new URL(url.replace('{MATRIX_USER_ID}', (await this.sha256(this.bootstrapData?.mx_user_id)).slice(0,63)));

            this.isAvailable = !!this.bootstrapData && !!url
            if (this.isAvailable) {
                this.clientFrame = this.el.down('iframe')
                this.loadingIndicator = this.el.down('.tine-viewport-waitcycle')
                this.loadingIndicator.on('dblclick', this.showClient, this)

                this.clientFrame.dom.src = this.url.href
                if (! this.bootstrapData.mx_account.is_initialized) {
                    window.addEventListener('beforeunload', (event) => {
                        if (! this.bootstrapData.mx_account.is_initialized) {
                            event.preventDefault();
                        }
                    });
                    Ext.MessageBox.wait(this.app.formatMessage('{ brandingTitle } is currently setting up your chat account. Please wait a moment and do not close your browser.', {
                        brandingTitle: Tine.Tinebase.registry.get('brandingTitle')
                    }), this.app.i18n._('Please wait ...'), { closable: true, estimate: 30000 })
                } else {
                    window.setTimeout(this.checkClientLoaded.bind(this), 5000)
                }

            } else {
                this.showUnavailableAlertIf()
            }
        })

        this.on('resize', this.onClientResize, this)

        window.addEventListener("message", async (event) => {
            // note: elementRequestCredentials is only send by element, if it requires credentials and can not bootstrap from local storage
            if (event.origin !== this.url?.origin) return;
            console.error(event)

            const notificationMap = new Map()
            
            switch (event.data.type) {
                case "elementBootstrapdataRequest":
                    this.clientIsLoaded = true;
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
                    if (! this.bootstrapData.mx_account.is_initialized) {
                        this.bootstrapData.mx_account.is_initialized = true
                        this.bootstrapData.mx_account.account_id = this.bootstrapData.mx_account.account_id.accountId || this.bootstrapData.mx_account.account_id
                        this.bootstrapData.mx_account = await Tine.MatrixSynapseIntegrator.saveMatrixAccount(this.bootstrapData.mx_account)
                        Ext.MessageBox.hide()
                    }
                    this.showClient()
                    break
                case "elementStartupFailure":
                    this.showClient()
                    if (event.data.failure === 'recoveryKeyIncorrect' || event.data.failure === 'recoveryDataInvalid') {
                        await this.promptRecoveryData()
                        break
                    }

                    if (event.data.failure === 'encryptionKeysLost') {
                        await this.alertUnhandledEncryptionError('Encryption Keys Lost')
                        break
                    }

                    if (event.data.failure === 'localUserDoseNotMatch') {
                        await this.promptClearLocalStorage()
                        break
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
                    if (this.clientRPCCallbacks.hasOwnProperty(event.data.eventUUID)) {
                        const cbs = this.clientRPCCallbacks[event.data.eventUUID]
                        delete this.clientRPCCallbacks[event.data.eventUUID]
                        if (! event.data.hasOwnProperty('failure')) {
                            cbs.resolve(event.data.result)
                        } else {
                            cbs.reject(event.data.failure, event.data)
                        }
                    }
                    return
            }
        }, false);

        this.supr().initComponent.call(this);
    },

    checkClientLoaded: async function() {
        if (!this.clientIsLoaded && await Ext.MessageBox.show({
            title: this.app.formatMessage('Chat could not be loaded'),
            msg: this.app.formatMessage('The Chat program could not be loaded. Try again?'),
            buttons: Ext.MessageBox.YESNO,
            icon: Ext.MessageBox.QUESTION_WARN
        }) === 'yes') {
            this.loadingIndicator.show();
            this.clientFrame.dom.src = Ext.SSL_SECURE_URL
            this.clientFrame.dom.src = this.url.href
            window.setTimeout(this.checkClientLoaded.bind(this), 15000)
        }
    },

    promptRecoveryData: async function() {
        const [btn, recoveryDatum] = await Ext.MessageBox.show({
            title: this.app.formatMessage('Recovery Key or Password Needed'),
            msg: this.app.formatMessage('{ brandingTitle } requires your recovery key or password to decrypt your chats. The recovery data you enter will be stored in secure storage on this { brandingTitle } server.', {
                brandingTitle: Tine.Tinebase.registry.get('brandingTitle')
            }),
            buttons: Ext.MessageBox.OKCANCEL,
            icon: Ext.MessageBox.QUESTION_INPUT,
            prompt: true
        });
        if (btn === 'ok') {
            const [success, type ] = await this.clientRPC('checkRecoveryDatumRequest', { recoveryDatum })
            if (success) {
                this.bootstrapData = await Tine.MatrixSynapseIntegrator[`setRecovery${_.upperFirst(type)}`](recoveryDatum)
                this.clientFrame.dom.src = this.clientFrame.dom.src
                return
            } else {
                if (await Ext.MessageBox.show({
                    title: this.app.formatMessage('Recovery Key or Password Incorrect'),
                    msg: this.app.formatMessage('The recovery data you entered was incorrect. Please try again.'),
                    buttons: Ext.MessageBox.OKCANCEL,
                    icon: Ext.MessageBox.QUESTION_WARN
                }) === 'ok') {
                    return this.promptRecoveryData()
                }
            }
        }
        await Ext.MessageBox.show({
            title: this.app.formatMessage('Manual Management Required'),
            msg: this.app.formatMessage('{ brandingTitle } does not know your correct recovery data and therefore cannot unlock your chats. You can try entering your recovery data directly in the chat program. In this case, { brandingTitle } does not save this data and you may have to enter it multiple times.', {
                brandingTitle: Tine.Tinebase.registry.get('brandingTitle')
            }),
            // { brandingTitle } kennt ihren ihre korrekten Wiederherstellungsdaten nicht und kann kann ihre Chats daher nicht entsperren. Sie können versuchen ihre Wiederherstellungsdaten direkt im Chat Programm eingeben. In diesem Fall speichert { brandingTitle } diese Daten nicht und sie müssen sie ggf. mehrfach eingeben.
            buttons: Ext.MessageBox.OK,
            icon: Ext.MessageBox.INFO_INSTRUCTION
        })
    },

    promptClearLocalStorage: async function() {
        if (await Ext.MessageBox.show({
            title: this.app.formatMessage('Chat storage corrupted', ),
            msg: this.app.formatMessage('Local Chat data is corrupted. This may occur if multiple users use the same computer. To resolve this issue, the local chat data needs to be deleted. This may result in messages being lost. Do you want to delete the local chat data?', {
                brandingTitle: Tine.Tinebase.registry.get('brandingTitle')
            }),
            buttons: Ext.MessageBox.OKCANCEL,
            icon: Ext.MessageBox.QUESTION_INPUT,
        }) === 'ok') {
            await this.clientRPC('clearLocalStorageRequest',)
            this.loadingIndicator.show()
            this.clientFrame.dom.src = Ext.SSL_SECURE_URL
            this.clientFrame.dom.src = this.url.href
            window.setTimeout(this.checkClientLoaded.bind(this), 10000)
        }
        await Ext.MessageBox.show({
            title: this.app.formatMessage('Manual Management Required'),
            msg: this.app.formatMessage('{ brandingTitle } does not know your correct recovery data and therefore cannot unlock your chats. You can try entering your recovery data directly in the chat program. In this case, { brandingTitle } does not save this data and you may have to enter it multiple times.', {
                brandingTitle: Tine.Tinebase.registry.get('brandingTitle')
            }),
            // { brandingTitle } kennt ihren ihre korrekten Wiederherstellungsdaten nicht und kann kann ihre Chats daher nicht entsperren. Sie können versuchen ihre Wiederherstellungsdaten direkt im Chat Programm eingeben. In diesem Fall speichert { brandingTitle } diese Daten nicht und sie müssen sie ggf. mehrfach eingeben.
            buttons: Ext.MessageBox.OK,
            icon: Ext.MessageBox.INFO_INSTRUCTION
        })
    },

    alertUnhandledEncryptionError: async function(error) {
        await Ext.MessageBox.show({
            title: this.app.formatMessage('Unhandled encryption error'),
            msg: this.app.formatMessage('An unhandled error has occurred. Please try reloading { brandingTitle }. If the error persists, contact support. Error:', {
                brandingTitle: Tine.Tinebase.registry.get('brandingTitle')
            }) + `${error}`,
            buttons: Ext.MessageBox.OK,
            icon: Ext.MessageBox.ERROR,
        });
    },
});
/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*global Ext, Tine*/
 
import LoginPanel from "./LoginPanel.vue";
import {BootstrapVueNext} from "bootstrap-vue-next";

Ext.ns('Tine.Tinebase');

/**
 * @namespace   Tine.Tinebase
 * @class       Tine.Tinebase.LoginPanel
 * @extends     Ext.Panel
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Tinebase.LoginPanel = Ext.extend(Ext.BoxComponent, {
    
    /**
     * @cfg {String} defaultUsername prefilled username
     */
    defaultUsername: '',
    
    /**
     * @cfg {String} defaultPassword prefilled password
     */
    defaultPassword: '',

    /**
     * @cfg {String}
     * translated heads up text
     */
    headsUpText: '',

    /**
     * @cfg {String} loginMethod server side login method
     */
    loginMethod: 'Tinebase.login',

    /**
     * @cfg {String} onLogin callback after successful login
     */
    onLogin: Ext.emptyFn,
    
    /**
     * @cfg {Boolean} show infobox (survey, links, text)
     */
    showInfoBox: true,
    
    /**
     * @cfg {String} scope scope of login callback
     */
    scope: null,
    
    // layout: 'fit',
    border: false,

    height: '100%',

    autoWidth: true,

    autoScroll: true,

    setLastLoginUser: function () {
        const lastUser = Ext.util.Cookies.get('TINE20LASTUSERID');
        if (lastUser) {
            // TODO vue pwd focus
            this.vueProps.formState.username = lastUser;
            this.vueEventBus.emit('focusUsernameField');
        }
    },

    _getBrowserSupportStatus: function () {
        let browserSupport = 'compatible';
        if (Ext.isIE6 || Ext.isGecko2 || Ext.isIE || Ext.isNewIE) {
            browserSupport = 'incompatible';
        } else if (
            ! (Ext.isWebKit || Ext.isGecko || Ext.isEdge)
        ) {
            // yepp we also mean -> Ext.isOpera
            browserSupport = 'unknown';
        }
        return browserSupport
    },
    
    initComponent: function () {
        this.supr().initComponent.call(this);

        this.vueProps = window.vue.reactive({
            _this: window.vue.markRaw(this),
            injectKey: this.injectKey,
            formState: {
                username: this.defaultUsername,
                usernameValid: null,
                password: this.defaultPassword,
                passwordValid: null
            }
        })

        this.vueEventBus = window.mitt()
        this.vueHandle = window.vue.createApp({
            render: () => window.vue.h(LoginPanel, this.vueProps)
        })
        this.vueHandle.config.globalProperties.window = window
        this.vueHandle.provide(this.injectKey, this.vueEventBus)
        this.vueHandle.use(BootstrapVueNext)
    },

    /**
     * @deprecated
     */
    checkOIDCLogin: function() {
        var oidcResponse = window.location.hash;
        if (oidcResponse.match(/access_token/)) {
            Ext.MessageBox.wait(String.format(i18n._('Login successful. Loading {0}...'), Tine.title), i18n._('Please wait!'));
            Ext.Ajax.request({
                scope: this,
                params: {
                    method: 'Tinebase.openIDCLogin',
                    oidcResponse: oidcResponse
                },
                timeout: 60000, // 1 minute
                success: this.onLoginSuccess
            });
        }
    },

    onLoginFail: async function(response, request) {
        const exception = _.get(JSON.parse(response.responseText), 'data', {});
        const me = this;

        Ext.MessageBox.hide();
        switch (exception.code) {
            case 630:
                const mfaDevices = exception.mfaUserConfigs
                return Tine.Tinebase.areaLocks.unlock(exception.area, {
                    mfaDevices,
                    username: me.getFormValue().username,
                    USERABORTMethod() { Ext.MessageBox.hide(); },
                    unlockMethod(areaName, MFAUserConfigId, MFAPassword) {
                        me.onLoginPress({MFAUserConfigId, MFAPassword});
                    },
                    triggerMFAMethod(MFAUserConfigId) {
                        if (mfaDevices.length > 1) {
                            me.onLoginPress({MFAUserConfigId});
                        }
                    }
                });
                break;
            case 631:
                return Tine.Tinebase.areaLocks.onMFAFail(exception.area, exception, {retryMethod () {
                    me.onLoginPress();
                }});
                break;
            case 650: // Auth requires redirect
                this.redirect(exception);
                break;
            case 651: // Password required
                this.focusPWField()
                break;
            default:
                return Tine.Tinebase.ExceptionHandler.handleRequestException(response);
                break;
        }
    },

    redirect: function(redirectTo) {
        if (String(redirectTo.method).toUpperCase() !== 'POST') {
            window.location.href = redirectTo.url;
        } else {
            window.document.body.innerHTML = redirectTo.postFormHTML;
            document.getElementsByTagName("form")[0].submit();
        }
    },

    onLoginSuccess: function(response) {
        const responseData = Ext.util.JSON.decode(response.responseText);
        if (responseData.success === true) {
            if (responseData.initialData) {
                window.initialData = window.initialData || {};
                Object.assign(window.initialData, responseData.initialData);
            }
            if (window.initialData?.afterLoginRedirect) {
                return this.redirect(window.initialData.afterLoginRedirect);
            }
            Ext.MessageBox.wait(String.format(i18n._('Login successful. Loading {0}...'), Tine.title), i18n._('Please wait!'));
            
            if (responseData?.assetHash && Tine.clientVersion.assetHash !== responseData.assetHash) {
                Tine.Tinebase.common.reload({
                    keepRegistry: false,
                    clearCache: true
                });
            }
            
            window.document.title = this.originalTitle;
            response.responseData = responseData;
            this.onLogin.call(this.scope, response);
            this.cleanUp();
        } else {
            var modSsl = Tine.Tinebase.registry.get('modSsl');
            var resultMsg = modSsl ? i18n._('There was an error verifying your certificate!') :
                i18n._('Your username and/or your password are wrong!');
            Ext.MessageBox.show({
                title: i18n._('Login failure'),
                msg: resultMsg,
                buttons: Ext.MessageBox.OK,
                icon: Ext.MessageBox.ERROR,
                fn: function () {
                    this.focusPWField()
                }.createDelegate(this)
            });
        }
    },

    getFormValue: function () {
        return this.vueProps.formState
    },

    checkFormValidity: function () {
        // TODO: Proper form validation
        const modSsl = Tine.Tinebase.registry.get('modSsl');
        if (modSsl) this.vueProps.formState.usernameValid = this.vueProps.formState.username.length > 0
        return true
    },

    onExtIDPLoginPress: function (idpID) {
        Ext.MessageBox.wait(i18n._('Logging you in...'), i18n._('Please wait'))
        this._login(null, null, null, {
            'X-TINE20-REQUEST-CONTEXT-idpId': idpID
        })
    },

    _login: function(username, password, additionalParams, headers) {
        Ext.Ajax.request({
            scope: this,
            params: Object.assign({
                method: this.loginMethod,
                username,
                password,
            }, additionalParams),
            headers,
            timeout: 60000, // 1 minute
            success: this.onLoginSuccess,
            failure: this.onLoginFail
        })
    },

    /**
     * do the actual login
     */
    onLoginPress: function (additionalParams) {
        this._credGetAbortController?.abort()
        const values = this.getFormValue()

        if (this.checkFormValidity()) {
            if (values.password) Ext.MessageBox.wait(i18n._('Logging you in...'), i18n._('Please wait'))
            this._login(values.username, values.password, additionalParams)
        } else {
            Ext.MessageBox.alert(i18n._('Errors'), i18n._('Please fix the errors noted.'));
        }
    },

    onRender: function (ct, position) {
        this.supr().onRender.apply(this, arguments);

        this.vueEventBus.on(
            'onLoginPress', this.onLoginPress.bind(this),
        )

        this.vueEventBus.on(
            'onExtIDPLoginPress', this.onExtIDPLoginPress.bind(this),
        )

        this.vm = this.vueHandle.mount(this.el.dom)

        this.map = new Ext.KeyMap(this.el, [{
            key : [10, 13],
            scope : this,
            fn : this.onLoginPress
        }]);

        this.originalTitle = window.document.title;
        var postfix = (Tine.Tinebase.registry.get('titlePostfix')) ? Tine.Tinebase.registry.get('titlePostfix') : '';
        window.document.title = Ext.util.Format.stripTags(Tine.title + postfix + ' - ' + i18n._('Please enter your login data'));

        this.setLastLoginUser()
    },

    focusPWField: function () {
        if (this.vueEventBus) this.vueEventBus.emit('focusPWField')
    },

    cleanUp: function () {
        this.vueHandle.unmount()
        this.vueHandle = null
        this.vueProps = null
        this.vueEventBus.all.clear()
        this.vueEventBus = null
    },

    triggerBrowserCredentialLogin: async function(conditional=false) {
        console.debug("conditional: ", conditional)
        if (!window.PublicKeyCredential) {
            window.alert('Passkey Login not supported.')
            return
        }
        try {
            const rfc4648 = await import(/* webpackChunkName: "Tinebase/js/rfc4648"*/'rfc4648');

            if(this._credGetAbortController) this._credGetAbortController.abort()
            this._credGetAbortController = new AbortController()

            const publicKeyOptions = await Tine.Tinebase.getWebAuthnAuthenticateOptionsForLogin()
            publicKeyOptions.challenge = rfc4648.base64url.parse(publicKeyOptions.challenge, { loose: true });
            const publicKeyCredential = await navigator.credentials.get({
                publicKey: publicKeyOptions,
                password: true,
                mediation: conditional ? "conditional" : "required",
                signal: this._credGetAbortController.signal
            });
            let publicKeyData = {
                id: publicKeyCredential.id,
                type: publicKeyCredential.type,
                rawId: rfc4648.base64url.stringify(new Uint8Array(publicKeyCredential.rawId)),
                response: {
                    clientDataJSON: rfc4648.base64url.stringify(new Uint8Array(publicKeyCredential.response.clientDataJSON)),
                    authenticatorData: rfc4648.base64url.stringify(new Uint8Array(publicKeyCredential.response.authenticatorData)),
                    signature: rfc4648.base64url.stringify(new Uint8Array(publicKeyCredential.response.signature)),
                    userHandle: rfc4648.base64url.stringify(new Uint8Array(publicKeyCredential.response.userHandle))
                },
            }

            this._login(null, null, {
                MFAUserConfigId: null,
                MFAPassword: JSON.stringify(publicKeyData)
            })
        } catch (e) {
            console.log(e.reason, e.message)
            if (e.name === 'AbortError') {
                console.debug(e.reason)
            } else if (e.name === 'NotAllowedError') {
                console.debug(e)
                // @TODO: disable passkey login
                //  @TODO2 disable passkey login if passwordless login is not configured as well
            } else {
                console.assert(e.message)
                if (await Ext.MessageBox.show({
                    icon: Ext.MessageBox.WARNING,
                    buttons: Ext.MessageBox.OKCANCEL,
                    title: i18n._('Error'),
                    msg: i18n._("FIDO2 WebAuthn authentication failed. Try again?")
                }) === 'ok') {
                    return this.triggerBrowserCredentialLogin(false);
                } else {
                    throw new Error('USERABORT');
                }
            }
        }
    }
});

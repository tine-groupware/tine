/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * entry point for sso login client
 */
import(/* webpackChunkName: "Tinebase/js/Tinebase" */ 'Tinebase.js').then(function (libs) {
    libs.lodash.assign(window, libs);
    require('tineInit');

    Tine.Tinebase.tineInit.renderWindow = Tine.Tinebase.tineInit.renderWindow.createInterceptor(function () {
        const mainCardPanel = Tine.Tinebase.viewport.tineViewportMaincardpanel;
        const i18n = new Locale.Gettext();
        i18n.textdomain('SSO');
        const rpInfo = window.initialData.relyingParty;
        const url = window.initialData.url || `${window.location.href}`;

        if (_.get(window, 'initialData.sso.isDeviceAuth') && _.get(window, 'initialData.sso.user')) {
            (async () => {
                if (_.get(window, 'initialData.sso.success')) {
                    await Ext.Msg.show({
                        buttons: null,
                        icon: Ext.MessageBox.INFO_SUCCESS,
                        title: i18n._('Authentication Successfully'),
                        closeable: false,
                        msg: (rpInfo.label ?
                            String.format(i18n._('Please proceed with {0}.'), rpInfo.label) :
                            i18n._('Please proceed with your device or programm.')) + '<br /><br />' + i18n._('You can close this page now.')
                    });
                }

                if (_.get(window, 'initialData.sso.deviceError')) {
                    await Ext.Msg.show({
                        buttons: Ext.Msg.OK,
                        icon: Ext.MessageBox.ERROR,
                        title: i18n._('Could not Find Device'),
                        msg: i18n._('No device authentication request found, please recheck your code!'),
                    });
                }
                Ext.Msg.show({
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.QUESTION,
                    title: i18n._('Enter Device Code'),
                    msg: rpInfo.label ?
                        String.format(i18n._('Please enter the device code for {0}.'), rpInfo.label) :
                        i18n._('Please enter the device code provided by external device or programm.'),
                    closeable: false,
                    prompt: true,
                    value: initialData.sso.userCode || '',
                    fn: async (btn, userCode) => {
                        document.body.innerHTML =`
                            <body>
                                <p class="pulsate">Authenticating Device...</p>
                                <form method="post" action="${url.replace(/device\/user\/.*$/, `device/user/${userCode}`)}">
                                    <input type="hidden" name="confirmed" value="1"/>
                                    <input type="submit" value="continue" style="display: none;"/>
                                </form>
                                <style>
                                    .pulsate {animation: pulsate 1s ease-out; animation-iteration-count: infinite;}
                                    @keyframes pulsate {0% { opacity: 0.5; } 50% { opacity: 1.0; } 100% { opacity: 0.5; }}
                                </style>
                            </body>`;
                        document.getElementsByTagName("form")[0].submit()

                    }
                });
            })();
            return false
        }
        mainCardPanel.layout.container.remove(Tine.loginPanel);
        Tine.loginPanel = new Tine.Tinebase.LoginPanel({
            defaultUsername: Tine.Tinebase.registry.get('defaultUsername'),
            defaultPassword: Tine.Tinebase.registry.get('defaultPassword'),
            allowBrowserPasswordManager: Tine.Tinebase.registry.get('allowBrowserPasswordManager'),
            // headsUpText: i18n._('SSO'),
            infoText:
                (rpInfo.logo ? '<img class="tb-login-infotext-logo" src="' + rpInfo.logo + '" />' : '') +
                (rpInfo.label ? '<p class="tb-login-infotext-label">' + (window.initialData.sso?.userCode ?
                    String.format(i18n._('Login for {0} (external device/programm)'), rpInfo.label) :
                    String.format(i18n._('After successful login you will be redirected to {0}'), rpInfo.label))  +
                '</p>' : '') +
                (rpInfo.description ? '<p class="tb-login-infotext-description">' + rpInfo.description + '</p>' : ''),
            scope: this,
            onLoginPress: function (additionalParams) {
                Ext.MessageBox.wait(window.i18n._hidden('Logging you in...'), window.i18n._hidden('Please wait'));


                const values = this.getFormValue()
                const formData = new FormData();
                formData.append('username', values.username);
                if (values.password) {
                    formData.append('password', values.password);
                }
                Object.keys(window.initialData.sso).forEach((key) => {formData.append(key, window.initialData.sso[key])});
                if (additionalParams !== window) {
                    Object.keys(additionalParams || {}).forEach((key) => {formData.append(key, additionalParams[key])});
                }

                var xhr = new XMLHttpRequest();
                xhr.addEventListener("load", () => {
                    const isJSON = xhr.responseText.match(/^{/);
                    if (xhr.status >= 200 && xhr.status < 300 && !isJSON) {
                        // saml2 post binding (NOTE: we don't know the binding here :-( )
                        window.document.body.innerHTML = xhr.responseText;
                        document.getElementsByTagName("form")[0].submit();
                    } else {
                        if (isJSON) {
                            let response = JSON.parse(xhr.responseText);
                            if (response?.error?.data) {
                                if (response.error.data.method) {
                                    response = response.error.data;
                                } else {
                                    return this.onLoginFail({responseText: JSON.stringify(response.error)});
                                }
                            }
                            if (response?.method) {
                                if (String(response?.method).toUpperCase() !== 'POST') {
                                    window.location.href = response.url;
                                } else {
                                    window.document.body.innerHTML = response.postFormHTML;
                                    document.getElementsByTagName("form")[0].submit();
                                }
                                return;
                            }
                        }
                        Ext.MessageBox.show({
                            title: window.i18n._hidden('Login failure'),
                            msg: window.i18n._hidden('Your username and/or your password are wrong!'),
                            buttons: Ext.MessageBox.OK,
                            icon: Ext.MessageBox.ERROR,
                            fn: () => {
                                this.focusPWField()
                            }
                        });
                    }
                });
                xhr.open("POST", url, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.withCredentials = true;
                xhr.send(formData);
            }
        });
        mainCardPanel.layout.container.add(Tine.loginPanel);
        mainCardPanel.layout.setActiveItem(Tine.loginPanel.id);
        // Tine.loginPanel.doLayout();

        return false;
    });
});

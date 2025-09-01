/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import TineBar from "./TineBar.vue";
import BootstrapVueNext from "bootstrap-vue-next";
Ext.ns('Tine.Tinebase');

import '../MFA/DeviceSelfServiceDialog';
import ColorSchemeSelector from "./barItems/ColorSchemeSelector.vue";

/**
 * Tine 2.0 jsclient main menu
 * 
 * @namespace   Tine.Tinebase
 * @class       Tine.Tinebase.MainMenu
 * @extends     Ext.Toolbar
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Tinebase.TineBar = Ext.extend(Ext.BoxComponent, {

    vueHandle: null,
    vueEventBus: null,
    injectKey: null,
    vueProps: null,

    vueApp: null,

    initComponent: function() {
        this.initActions();
        this.initUserActions();

        // for compatibility
        this.items = new Ext.util.MixedCollection()

        this.barItems = []
        Tine.Tinebase.viewport.colorSchemeAction.__component = window.vue.markRaw(ColorSchemeSelector)
        this.barItems.push(window.vue.markRaw(Tine.Tinebase.viewport.colorSchemeAction))
        if(typeof this.actionLicenseExpire === 'object'){
            this.actionLicenseExpire.registerdItemPos = 0
            this.actionLicenseExpire._showText = true // show txt on bar
            this.barItems.push(window.vue.markRaw(this.actionLicenseExpire))
        }

        this.plugins = this.plugins || [];
        this.plugins.push({
            ptype: 'ux.itemregistry',
            key:   'Tine.Tinebase.MainMenu'
        });

        this.vueEventBus = window.mitt()
        this.injectKey = 'injectKey' + this.id
        this.vueProps = window.vue.reactive({
            parentId: this.id,
            injectKey: this.injectKey,

            parentWidth: this.width,
            parentHeight: this.height,

            mainMenuItems: window.vue.markRaw(this.userActions),
            barItems: this.barItems,

            activeApp: this.activeApp
        })
        this.vueApp = TineBar;

        this.supr().initComponent.call(this);
    },

    add: function(item){
        this.barItems.push(window.vue.markRaw(item))
    },

    onRender: function (ct, position) {
        this.supr().onRender.call(this, ct, position)
        this.vueHandle = window.vue.createApp({
            render: () => window.vue.h(this.vueApp, this.vueProps)
        })
        this.vueHandle.provide(this.injectKey, this.vueEventBus)
        this.vueHandle.use(BootstrapVueNext)
        this.vueHandle.config.globalProperties.window = window
        this.vueHandle.mount(this.el.dom)
    },

    initUserActions: function() {
        if (! this.userActions) {
            this.userActions = [
                this.action_editProfile,
                this.action_showPreferencesDialog,
                this.action_notificationPermissions,
                this.action_changePassword,
                this.action_manageMFADevices,
                this.action_unstuck,
                this.action_aboutTine,
                this.action_logout
            ];

            if (Tine.Tinebase.registry.get('userAccountChanged')) {
                this.action_returnToOriginalUser = new Tine.widgets.account.ChangeAccountAction({
                    returnToOriginalUser: true,
                    text: i18n._('Return to original user account')
                });
                this.userActions = this.userActions.concat(this.action_returnToOriginalUser);
                
            } else if (Tine.Tinebase.registry.get("config") 
                && Tine.Tinebase.registry.get("config").roleChangeAllowed 
                && Tine.Tinebase.registry.get("config").roleChangeAllowed.value) 
            {
                this.action_changeUserAccount = new Tine.widgets.account.ChangeAccountAction({});
                
                var roleChangeAllowed = Tine.Tinebase.registry.get("config").roleChangeAllowed.value,
                    currentAccountName = Tine.Tinebase.registry.get('currentAccount').accountLoginName;
                if (roleChangeAllowed[currentAccountName]) {
                    this.userActions = this.userActions.concat(this.action_changeUserAccount);
                }
            }
            
            var regItems = Ext.ux.ItemRegistry.itemMap['Tine.Tinebase.MainMenu.userActions'] || [];
            
            Ext.each(regItems, function(reg) {
                var addItem = reg.item;
                this.userActions.splice(this.userActions.length - 1, 0, addItem)
            }, this);
        }
        return this.userActions;
    },
    
    getActionByPos(registerItemPosition) {
        return _.filter(this.items.items, (item) => {
            return item?.registerdItemPos === registerItemPosition;
        });
    },
    
    /**
     * initialize actions
     * @private
     */
    initActions: function() {
        this.action_aboutTine = new Ext.Action({
            text: String.format(i18n._('About {0}'), Tine.title),
            handler: this.onAboutTine20,
            iconCls: 'action_about'
        });
        
        this.action_userManual = new Ext.Action({
            text: String.format(i18n._('Help')),
            iconCls: 'action_userManual',
            handler: this.onShowHelp,
            hidden: Tine.Tinebase.common.hasRight('run', 'UserManual'),
            scope: this
        });

        this.action_showDebugConsole = new Ext.Action({
            text: i18n._('Debug Console (Ctrl + F11)'),
            handler: Tine.Tinebase.common.showDebugConsole,
            iconCls: 'tinebase-action-debug-console'
        });
        
        this.action_showPreferencesDialog = new Ext.Action({
            text: i18n._('Preferences'),
            disabled: false,
            handler: this.onEditPreferences,
            iconCls: 'action_adminMode'
        });

        this.action_editProfile = new Ext.Action({
            text: i18n._('Edit Profile'),
            disabled: ! Tine.Tinebase.common.hasRight('manage_own_profile', 'Tinebase'),
            handler: this.onEditProfile,
            iconCls: 'tinebase-accounttype-user'
        });
        
        this.action_changePassword = new Ext.Action({
            text: i18n._('Change password'),
            handler: this.onChangePassword,
            disabled: (! Tine.Tinebase.configManager.get('changepw')),
            iconCls: 'action_password'
        });

        this.action_manageMFADevices = new Ext.Action({
            text: i18n._('Manage MFA Devices'),
            handler: this.onManageMFADevices,
            iconCls: 'action_mfa'
        });

        this.action_logout = new Ext.Action({
            text: i18n._('Logout'),
            tooltip:  String.format(i18n._('Logout from {0}'), Tine.title),
            iconCls: 'action_logOut',
            handler: this.onLogout,
            scope: this
        });

        this.actionLearnMore = new Ext.Action({
            text: String.format(i18n._('Learn more about {0}'), Tine.title),
            tooltip: Tine.weburl,
            iconCls: 'tine-favicon',
            handler: function() {
                window.open(Tine.weburl, '_blank');
            },
            scope: this
        });

        var licenseExpiresIn = Tine.Tinebase.registry.get('licenseExpire'),
            // TODO move this to the license definitions on the server?
            licenseExpiresInThreshold = 90,
            licenseExpiresInThresholdRed = 14,
            licenseExpiredSince = Tine.Tinebase.registry.get('licenseExpiredSince');

        this.actionLicenseExpire = '';
        if (Tine.Tinebase.common.hasRight('show_license_info', 'Tinebase')) {
            if (licenseExpiresIn && licenseExpiresIn < licenseExpiresInThreshold) {
                this.actionLicenseExpire = new Ext.Action({
                    text: String.format(i18n._('The license expires in {0} days'), licenseExpiresIn),
                    tooltip: String.format(i18n._('Please visit the shop at {0}'), Tine.shop),
                    iconCls: licenseExpiresIn < licenseExpiresInThresholdRed ? 'tine-license-red' : 'tine-license',
                    handler: function () {
                        window.open(Tine.shop, '_blank');
                    },
                    scope: this
                });
            } else if (licenseExpiredSince) {
                this.actionLicenseExpire = new Ext.Action({
                    text: String.format(i18n._('Your {0} license expired.'), Tine.title),
                    tooltip: String.format(i18n._('Please visit the shop at {0}'), Tine.shop),
                    iconCls: 'tine-license',
                    handler: function () {
                        window.open(Tine.shop, '_blank');
                    },
                    scope: this
                });
            }
        }
        
        this.action_notificationPermissions = new Ext.Action({
            text: i18n._('Allow desktop notifications'),
            tooltip:  i18n._('Request permissions for WebKit desktop notifications.'),
            iconCls: 'action_edit',
            disabled: ! window.Notification || this.systemTrayNotificationsEnabled(),
            handler: this.requestNotificationPermission,
            scope: this
        });

        /**
         * It's ctrl + l but a friendly Button!
         */
        this.action_unstuck = new Ext.Action({
            text: i18n._('Reload the application'),
            tooltip:  i18n._('Reloads the application and clears caches.'),
            iconCls: 'action_login',
            handler: this.reloadAndClearCache,
            scope: this
        });
    },

    systemTrayNotificationsEnabled: function() {
        return (window.Notification && window.Notification.permission == 'granted')
    },

    requestNotificationPermission: function() {
        window.Notification.requestPermission(Ext.emptyFn);
    },

    reloadAndClearCache: function() {
        Tine.Tinebase.common.reload({
            clearCache: true
        });
    },
    
    /**
     * open new window/tab to show help and tutorial
     */
    onShowHelp: function() {
        window.open(Tine.helpUrl,'_blank');
    },
    
    /**
     * @private
     */
    onAboutTine20: function() {
        var aboutDialog = new Tine.Tinebase.AboutDialog();
        aboutDialog.show();
    },
    
    /**
     * @private
     */
    onChangePassword: function() {
        var passwordDialog = new Tine.Tinebase.PasswordChangeDialog();
        passwordDialog.show();
    },

    /**
     * @private
     */
    onManageMFADevices: function() {
        Tine.Tinebase.MFA.DeviceSelfServiceDialog.openWindow({});
    },

    /**
     * @private
     */
    onEditPreferences: function() {
        Tine.widgets.dialog.Preferences.openWindow({});
    },

    /**
     * @private
     */
    onEditProfile: function() {
        Tine.widgets.dialog.Preferences.openWindow({
            initialCardName: 'Tinebase.UserProfile'
        });
    },
    
    /**
     * the logout button handler function
     * @private
     */
    onLogout: function() {
        if (Tine.Tinebase.registry.get('confirmLogout') != '0') {
            Ext.MessageBox.confirm(i18n._('Confirm'), i18n._('Are you sure you want to log out?'), function(btn, text) {
                if (btn == 'yes') {
                    this._doLogout();
                }
            }, this);
        } else {
            this._doLogout();
        }
    },
    
    /**
     * logout user & redirect
     * @static
     */
    _doLogout: async function() {
        Ext.MessageBox.wait(i18n._('Logging you out...'), i18n._('Please wait!'));
        const response = await Tine.Tinebase.logout();
        // clear the authenticated mod_ssl session
        if (window.crypto && Ext.isFunction(window.crypto.logout)) {
            window.crypto.logout();
        }

        if (response.logoutUrls) {
            const { ssoLogout } = await import(/* webpackChunkName: "SSO/js/logout" */ 'SSO/js/logout');
            await ssoLogout(response)
        }

        return await Tine.Tinebase.common.reload({
            clearCache: true,
            redirectAlways: Tine.Tinebase.configManager.get('redirectAlways'),
            redirectUrl: Tine.Tinebase.configManager.get('redirectUrl')
        });
    }
});

/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Tinebase');

import './MFA/DeviceSelfServiceDialog';

/**
 * Tine 2.0 jsclient main menu
 * 
 * @namespace   Tine.Tinebase
 * @class       Tine.Tinebase.MainMenu
 * @extends     Ext.Toolbar
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Tinebase.MainMenu = Ext.extend(Ext.Toolbar, {
    /**
     * @cfg {Boolean} showMainMenu
     */
    showMainMenu: false,
    style: {'padding': '0px 2px'},
    cls: 'tbar-mainmenu',
    
    /**
     * @type Array
     * @property mainActions
     */
    mainActions: null,
    
    initComponent: function() {
        this.initActions();
        this.onlineStatus = new Ext.ux.ConnectionStatus({
            showIcon: true,
            showText: false
        });
        
        this.items = this.getItems();
        
        this.plugins = this.plugins || [];
        this.plugins.push({
            ptype: 'ux.itemregistry',
            key:   'Tine.Tinebase.MainMenu'
        });

        this.supr().initComponent.call(this);
    },
    
    getItems: function() {
        return [{
            text: Tine.title,
            hidden: !this.showMainMenu,
            menu: {
                id: 'Tinebase_System_Menu', 
                items: this.getMainActions()
        }},
        '->',
        // removed in tine20.com
        //this.actionLearnMore,
        this.actionLicenseExpire,
        // TODO add a bigger spacer here?
        { xtype: 'spacer' },
        {
            text: Ext.util.Format.htmlEncode(String.format(i18n._('User: {0}'), Tine.Tinebase.registry.get('currentAccount').accountDisplayName)),
            menu: this.getUserActions(),
            menuAlign: 'tr-br',
            iconCls: 'tine-grid-row-action-icon ' + (Tine.Tinebase.registry.get('userAccountChanged') ? 'renderer_accountUserChangedIcon' : 'renderer_accountUserIcon')
        },
        Tine.Tinebase.viewport.colorSchemeAction,
        this.onlineStatus, 
        this.action_logout];
    },
    
    /**
     * returns all main actions
     * 
     * @return {Array}
     */
    getMainActions: function() {
        if (! this.mainActions) {
            this.mainActions = [
                this.action_aboutTine,
                this.action_userManual,
                '-',
                this.getUserActions(),
                '-',
                this.action_logout
            ];
            
            if (String(Tine.Tinebase.registry.get("version").buildType).match(/(DEVELOPMENT|DEBUG)/)) {
                this.mainActions.splice(2, 0, '-', this.action_showDebugConsole);
            }
        }
        
        return this.mainActions;
    },
    
    getUserActions: function() {

        if (! this.userActions) {
            this.userActions = [
                this.action_editProfile,
                this.action_showPreferencesDialog,
                this.action_notificationPermissions,
                this.action_changePassword,
                this.action_manageMFADevices,
                this.action_unstuck
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

                this.userActions.push(addItem);
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
            tooltip:  i18n._('Request permissions for webkit desktop notifications.'),
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
            Ext.MessageBox.confirm(i18n._('Confirm'), i18n._('Are you sure you want to logout?'), function(btn, text) {
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

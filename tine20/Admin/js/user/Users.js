/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*global Ext, Tine*/ 

Ext.ns('Tine.Admin.user');

/**
 * Users 'mainScreen'
 * 
 * @static
 */
Tine.Admin.user.show = function () {
    var app = Tine.Tinebase.appMgr.get('Admin');
    if (! Tine.Admin.user.gridPanel) {
        Tine.Admin.user.gridPanel = new Tine.Admin.user.GridPanel({
            app: app
        });
    }
    else {
        Tine.Admin.user.gridPanel.loadGridData.defer(100, Tine.Admin.user.gridPanel, []);
    }

    app.getMainScreen().setActiveContentPanel(Tine.Admin.user.gridPanel, true);
    app.getMainScreen().setActiveToolbar(Tine.Admin.user.gridPanel.actionToolbar, true);
};


/************** models *****************/
Ext.ns('Tine.Admin.Model');

/**
 * Model of an account
 */
Tine.Admin.Model.UserArray = [
    { name: 'accountId' },
    { name: 'accountFirstName' },
    { name: 'accountLastName' },
    { name: 'accountLoginName' },
    { name: 'accountPassword' },
    { name: 'accountDisplayName' },
    { name: 'accountFullName' },
    { name: 'accountStatus' },
    { name: 'groups' },
    { name: 'accountRoles' },
    { name: 'accountPrimaryGroup' },
    { name: 'accountExpires', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'accountLastLogin', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'accountLastPasswordChange', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'password_must_change', type: 'boolean'},
    { name: 'accountLastLoginfrom' },
    { name: 'accountEmailAddress' },
    { name: 'accountHomeDirectory' },
    { name: 'accountLoginShell' },
    { name: 'openid'},
    { name: 'visibility'},
    { name: 'sambaSAM' },
    { name: 'emailUser' },
    { name: 'contact_id' },
    { name: 'container_id' },
    { name: 'filesystemSize'},
    { name: 'effectiveAndLocalQuota'},
    { name: 'mfa_configs'},
    { name: 'xprops'}
];

Tine.Admin.Model.User = Tine.Tinebase.data.Record.create(Tine.Tinebase.Model.genericFields.concat(Tine.Admin.Model.UserArray), {
    appName: 'Admin',
    modelName: 'User',
    idProperty: 'accountId',
    titleProperty: 'accountDisplayName',
    // ngettext('User', 'Users', n); gettext('Users');
    recordName: 'User',
    recordsName: 'Users'
});

/**
 * returns default account data
 * 
 * @namespace Tine.Admin.Model.User
 * @static
 * @return {Object} default data
 */
Tine.Admin.Model.User.getDefaultData = function () {
    var internalAddressbook = Tine.Admin.registry.get('defaultInternalAddressbook'),
        emailUserDefaults = (Tine.Admin.registry.get('config').defaultImapUserSettings && Tine.Admin.registry.get('config').defaultImapUserSettings.value)
            ? Tine.Admin.registry.get('config').defaultImapUserSettings.value : '',
        passwordMustChangeDefault = Tine.Tinebase.configManager.get('defaultPasswordMustChange', 'Admin');
        
    return {
        sambaSAM: '',
        emailUser: emailUserDefaults,
        accountStatus: 'enabled',
        visibility: (internalAddressbook !== null) ? 'displayed' : 'hidden',
        container_id: internalAddressbook,
        password_must_change: passwordMustChangeDefault,
        accountPrimaryGroup: Tine.Admin.registry.get('defaultPrimaryGroup')
    };
};

Tine.Admin.Model.SAMUserArray = [
    { name: 'sid'              },
    { name: 'primaryGroupSID'  },
    { name: 'acctFlags'        },
    { name: 'homeDrive'        },
    { name: 'homePath'         },
    { name: 'profilePath'      },
    { name: 'logonScript'      },
    { name: 'logonTime',     type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'logoffTime',    type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'kickoffTime',   type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'pwdLastSet',    type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'pwdCanChange',  type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'pwdMustChange', type: 'date', dateFormat: Date.patterns.ISO8601Long }
];

Tine.Admin.Model.SAMUser = Tine.Tinebase.data.Record.create(Tine.Admin.Model.SAMUserArray, {
    appName: 'Admin',
    modelName: 'SAMUser',
    idProperty: 'sid',
    titleProperty: null,
    // ngettext('Samba User', 'Samba Users', n);
    recordName: 'Samba User',
    recordsName: 'Samba Users'
});

Tine.Admin.Model.EmailUserArray = [
    { name: 'emailUID' },
    { name: 'emailGID' },
    { name: 'emailMailQuota' },
    { name: 'emailMailSize' },
    { name: 'emailSieveQuota' },
    { name: 'emailSieveSize' },
    { name: 'emailLastLogin', type: 'date', dateFormat: Date.patterns.ISO8601Long },
    { name: 'emailUserId' },
    { name: 'emailAliases' },
    { name: 'emailForwards' },
    { name: 'emailForwardOnly' },
    { name: 'emailAddress' },
    { name: 'emailUsername' }
];

Tine.Admin.Model.EmailUser = Tine.Tinebase.data.Record.create(Tine.Admin.Model.EmailUserArray, {
    appName: 'Admin',
    modelName: 'EmailUser',
    idProperty: 'sid',
    titleProperty: null,
    // ngettext('Email User', 'Email Users', n);
    recordName: 'Email User',
    recordsName: 'Email Users'
});




/************** backends *****************/

Tine.Admin.userBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Admin',
    modelName: 'User',
    recordClass: Tine.Admin.Model.User,
    idProperty: 'accountId'
});

Tine.Admin.samUserBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Admin',
    modelName: 'SAMUser',
    recordClass: Tine.Admin.Model.SAMUser,
    idProperty: 'sid'
});

Tine.Admin.emailUserBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Admin',
    modelName: 'EmailUser',
    recordClass: Tine.Admin.Model.EmailUser,
    idProperty: 'emailUID'
});

Tine.Admin.sharedAddressbookBackend = new Tine.Tinebase.data.RecordProxy({
    appName: 'Admin',
    modelName: 'SharedAddressbook',
    recordClass: Tine.Tinebase.Model.Container,
    idProperty: 'id'
});

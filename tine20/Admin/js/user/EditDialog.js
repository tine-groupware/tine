/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
/*global Ext, Tine*/

// @see https://github.com/ericmorand/twing/issues/332
// #if process.env.NODE_ENV !== 'unittest'
import getTwingEnv from "twingEnv";
// #endif
import FieldInfoPlugin from "ux/form/FieldInfoPlugin";
import XPropsPanel from "widgets/dialog/XPropsPanel";

Ext.ns('Tine.Admin.user');

import MFAPanel from 'MFA/UserConfigPanel';
import FieldTriggerPlugin from "../../../Tinebase/js/ux/form/FieldTriggerPlugin";

/**
 * @namespace   Tine.Admin.user
 * @class       Tine.Admin.UserEditDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * NOTE: this class doesn't use the user namespace as this is not yet supported by generic grid
 * 
 * <p>User Edit Dialog</p>
 * <p>
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Admin.UserEditDialog
 */
Tine.Admin.UserEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    
    /**
     * @private
     */
    windowNamePrefix: 'userEditWindow_',
    appName: 'Admin',
    recordClass: Tine.Admin.Model.User,
    recordProxy: Tine.Admin.userBackend,
    evalGrants: false,
    passwordConfirmWindow: null,
    
    /**
     * @private
     */
    initComponent: function () {
        var accountBackend = Tine.Tinebase.registry.get('accountBackend');
        this.ldapBackend = (accountBackend === 'Ldap' || accountBackend === 'ActiveDirectory');

        this.twingEnv = getTwingEnv();
        const loader = this.twingEnv.getLoader();
        for (const [fieldName, template] of Object.entries(Tine.Tinebase.configManager.get('accountTwig'))) {
            loader.setTemplate(fieldName, template);
        }

        Tine.Admin.UserEditDialog.superclass.initComponent.call(this);
    },

    /**
     * @private
     */
    onRecordLoad: function () {
        // interrupt process flow until dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        // samba user
        var response = {
            responseText: Ext.util.JSON.encode(this.record.get('sambaSAM'))
        };
        this.samRecord = Tine.Admin.samUserBackend.recordReader(response);
        // email user
        var emailResponse = {
            responseText: Ext.util.JSON.encode(this.record.get('emailUser'))
        };
        this.emailRecord = Tine.Admin.emailUserBackend.recordReader(emailResponse);

        if (this.record.get('accountLastPasswordChange')) {
            this.record.set('accountLastPasswordChangeRaw', this.record.get('accountLastPasswordChange'))
        }

        // format dates
        var dateTimeDisplayFields = ['accountLastLogin', 'accountLastPasswordChange', 'logonTime', 'logoffTime', 'pwdLastSet'];
        for (var i = 0; i < dateTimeDisplayFields.length; i += 1) {
            if (dateTimeDisplayFields[i] === 'accountLastLogin' || dateTimeDisplayFields[i] === 'accountLastPasswordChange') {
                this.record.set(dateTimeDisplayFields[i], Tine.Tinebase.common.dateTimeRenderer(this.record.get(dateTimeDisplayFields[i])));
            } else {
                this.samRecord.set(dateTimeDisplayFields[i], Tine.Tinebase.common.dateTimeRenderer(this.samRecord.get(dateTimeDisplayFields[i])));
            }
        }

        this.getForm().loadRecord(this.emailRecord);
        this.getForm().loadRecord(this.samRecord);
        this.record.set('sambaSAM', this.samRecord.data);

        if (Tine.Tinebase.registry.get('manageSmtpEmailUser')) {
            if (this.emailRecord.get('emailAliases')) {
                this.aliasesGrid.setStoreFromArray(this.emailRecord.get('emailAliases'));
            }
            if (this.emailRecord.get('emailForwards')) {
                this.forwardsGrid.setStoreFromArray(this.emailRecord.get('emailForwards'));
            }
        }
        if (Tine.Tinebase.registry.get('manageImapEmailUser')) {
            if (!this.emailRecord.get('emailMailQuota')) this.getForm().findField('emailMailQuota').setValue(null);
        }

        // load stores for memberships
        if (this.record.id) {
            this.storeGroups.loadData(this.record.get('groups'));
            this.storeRoles.loadData(this.record.get('accountRoles'));
        }

        var fileSystem = this.record.get('effectiveAndLocalQuota');
        if (fileSystem && fileSystem.localUsage) {
            this.getForm().findField('personalFSSize').setValue(parseInt(fileSystem.localUsage));
        }

        var xprops = this.record.get('xprops');
        xprops = Ext.isObject(xprops) ? xprops : {};
        if (xprops.personalFSQuota) {
            this.getForm().findField('personalFSQuota').setValue(xprops.personalFSQuota);
        }

        Tine.Admin.UserEditDialog.superclass.onRecordLoad.call(this);
    },
    
    /**
     * @private
     */
    onRecordUpdate: function () {
        Tine.Admin.UserEditDialog.superclass.onRecordUpdate.call(this);
        
        Tine.log.debug('Tine.Admin.UserEditDialog::onRecordUpdate()');
        
        var form = this.getForm();
        form.updateRecord(this.samRecord);
        if (this.samRecord.dirty) {
            // only update sam record if something changed
            this.unsetLocalizedDateTimeFields(this.samRecord, ['logonTime', 'logoffTime', 'pwdLastSet']);
            this.record.set('sambaSAM', '');
            this.record.set('sambaSAM', this.samRecord.data);
        }

        form.updateRecord(this.emailRecord);
        // get aliases / forwards
        if (Tine.Tinebase.registry.get('manageSmtpEmailUser')) {
            // forcing blur of quickadd grids
            this.aliasesGrid.doBlur();
            this.forwardsGrid.doBlur();
            this.emailRecord.set('emailAliases', this.aliasesGrid.getFromStoreAsArray());
            this.emailRecord.set('emailForwards', this.forwardsGrid.getFromStoreAsArray());
            Tine.log.debug('Tine.Admin.UserEditDialog::onRecordUpdate() -> setting aliases and forwards in e-mail record');
            Tine.log.debug(this.emailRecord);
        }
        this.unsetLocalizedDateTimeFields(this.emailRecord, ['emailLastLogin']);
        this.record.set('emailUser', '');
        this.record.set('emailUser', this.emailRecord.data);
        
        var newGroups = [],
            newRoles = [];
        
        this.storeGroups.each(function (rec) {
            newGroups.push(rec.data.id);
        });
        // add selected primary group to new groups if not exists
        if (newGroups.indexOf(this.record.get('accountPrimaryGroup')) === -1) {
            newGroups.push(this.record.get('accountPrimaryGroup'));
        }
         
        this.storeRoles.each(function (rec) {
            newRoles.push(rec.data.id);
        });
        
        this.record.set('groups', newGroups);
        this.record.set('accountRoles', newRoles);
        
        this.unsetLocalizedDateTimeFields(this.record, ['accountLastLogin']);
        if (this.record.get('accountLastPasswordChangeRaw')) {
            this.record.set('accountLastPasswordChange', this.record.get('accountLastPasswordChangeRaw'))
        }

        if (this.record.get('password_must_change_actual')) {
            this.record.set('password_must_change', this.record.get('password_must_change_actual'))
        }

        var xprops = this.record.get('xprops');
        xprops = Ext.isObject(xprops) ? xprops : {};
        xprops.personalFSQuota = this.getForm().findField('personalFSQuota').getValue();
        Tine.Tinebase.common.assertComparable(xprops);
        this.record.set('xprops', xprops);
    },
    /**
     * need to unset localized datetime fields before saving
     * 
     * @param {Object} record
     * @param {Array} dateTimeDisplayFields
     */
    unsetLocalizedDateTimeFields: function(record, dateTimeDisplayFields) {
        Ext.each(dateTimeDisplayFields, function (dateTimeDisplayField) {
            record.set(dateTimeDisplayField, '');
        }, this);
    },

    /**
     * is form valid?
     * 
     * @return {Boolean}
     */
    isValid: function() {
        return  Tine.Admin.UserEditDialog.superclass.isValid.call(this).then((result) => {
            let errorMessages = '';
            
            if (Tine.Tinebase.registry.get('manageSmtpEmailUser') && ! Tine.Tinebase.registry.get('allowExternalEmail')) {
                const emailValue = this.getForm().findField('accountEmailAddress').getValue();
                if (! Tine.Tinebase.common.checkEmailDomain(emailValue)) {
                    let errorMessage = this.app.i18n._("Domain is not allowed. Check your SMTP domain configuration.") + '<br>';
                    errorMessage += '<br>' + this.app.i18n._("Allowed Domains") + ': <br>';
                    
                    const allowDomains = Tine.Tinebase.common.getAllowedDomains();

                    _.each(allowDomains, (domain) => {
                        if (domain !== '') {
                            errorMessage += '<b> - ' + domain + '</b><br>';
                        }
                    })
                    
                    errorMessages += errorMessage;
                    
                    this.getForm().markInvalid([{
                        id: 'accountEmailAddress',
                        msg: errorMessage
                    }]);
                }
            }

            if (Tine.Tinebase.appMgr.get('Admin').featureEnabled('featurePreventSpecialCharInLoginName')) {
                if (! this.validateLoginName(this.getForm().findField('accountLoginName').getValue())) {
                    const errorMessage = this.app.i18n._("Special characters are not allowed in login name.");
                    errorMessages += errorMessage;
                    this.getForm().markInvalid([{
                        id: 'accountLoginName',
                        msg: errorMessage
                    }]);
                }
            }
            if (errorMessages !== '') {
                return Promise.reject(errorMessages);
            } else {
                return result;
            }
        });
    },
    
    /**
     * Validate confirmed password
     */
    onPasswordConfirm: function () {
        var confirmForm = this.passwordConfirmWindow.items.first().getForm(),
            confirmValues = confirmForm.getFieldValues(),
            passwordStatus = confirmForm.findField('passwordStatus'),
            passwordField = (this.getForm()) ? this.getForm().findField('accountPassword') : null;
        
        if (! passwordField) {
            // oops: something went wrong, this should not happen
            return false;
        }
        
        if (confirmValues.passwordRepeat !== passwordField.getValue()) {
            passwordStatus.el.setStyle('color', 'red');
            passwordStatus.setValue(this.app.i18n.gettext('Passwords do not match!'));
            
            passwordField.passwordsMatch = false;
            passwordField.markInvalid(this.app.i18n.gettext('Passwords do not match!'));
        } else {
            passwordStatus.el.setStyle('color', 'green');
            passwordStatus.setValue(this.app.i18n.gettext('Passwords match!'));
                        
            passwordField.passwordsMatch = true;
            passwordField.clearInvalid();
        }
        
        return passwordField.passwordsMatch ? passwordField.passwordsMatch : passwordStatus.getValue();
    },
    
    /**
     * Get current primary group (selected from combobox or default primary group)
     * 
     * @return {String} - id of current primary group
     */
    getCurrentPrimaryGroupId: function () {
        return this.getForm().findField('accountPrimaryGroup').getValue() || this.record.get('accountPrimaryGroup').id;
    },
    
    /**
     * Init User groups picker grid
     * 
     * @return {Tine.widgets.account.PickerGridPanel}
     */
    initUserGroups: function () {
        this.storeGroups = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Admin.Model.Group
        });
        
        var self = this;
        
        this.pickerGridGroups = new Tine.widgets.account.PickerGridPanel({
            border: false,
            frame: false,
            store: this.storeGroups,
            selectType: 'group',
            selectAnyone: false,
            selectTypeDefault: 'group',
            groupRecordClass: Tine.Admin.Model.Group,
            getColumnModel: function () {
                return new Ext.grid.ColumnModel({
                    defaults: { sortable: true },
                    columns:  [
                        {id: 'name', header: self.app.i18n._('Name'), dataIndex: this.recordPrefix + 'name', renderer: function (val, meta, record) {
                            return record.data.id === self.getCurrentPrimaryGroupId() ? (record.data.name + '<span class="x-item-disabled"> (' + self.app.i18n.gettext('Primary group') + ')<span>') : record.data.name;
                        }}
                    ]
                });
            }
        });
        // disable remove of group if equal to current primary group
        this.pickerGridGroups.selModel.on('beforerowselect', function (sm, index, keep, record) {
            if (record.data.id === this.getCurrentPrimaryGroupId()) {
                return false;
            }
        }, this);
        
        return this.pickerGridGroups;
    },
    
    /**
     * Init User roles picker grid
     * 
     * @return {Tine.widgets.account.PickerGridPanel}
     */
    initUserRoles: function () {
        this.storeRoles = new Ext.data.JsonStore({
            root: 'results',
            totalProperty: 'totalcount',
            id: 'id',
            fields: Tine.Tinebase.Model.Role
        });
        
        this.pickerGridRoles = new Tine.widgets.grid.PickerGridPanel({
            border: false,
            frame: false,
            autoExpandColumn: 'name',
            store: this.storeRoles,
            recordClass: Tine.Tinebase.Model.Role,
            columns: [{id: 'name', header: this.app.i18n.gettext('Name'), sortable: true}],
            initActionsAndToolbars: function () {
                // for now removed abillity to edit role membership
//                Tine.widgets.grid.PickerGridPanel.prototype.initActionsAndToolbars.call(this);
//                
//                this.comboPanel = new Ext.Container({
//                    layout: 'hfit',
//                    border: false,
//                    items: this.getSearchCombo(),
//                    columnWidth: 1
//                });
//                
//                this.tbar = new Ext.Toolbar({
//                    items: this.comboPanel,
//                    layout: 'column'
//                });
            },
            onAddRecordFromCombo: function (recordToAdd) {
                // check if already in
                if (! this.recordStore.getById(recordToAdd.id)) {
                    this.recordStore.add([recordToAdd]);
                }
                this.collapse();
                this.clearValue();
                this.reset();
            }
        });
        // remove listeners for this grid selection model
        this.pickerGridRoles.selModel.purgeListeners();
        
        return this.pickerGridRoles;
    },
    
    /**
     * Init Fileserver tab items
     * 
     * @return {Array} - array ff fileserver tab items
     */
    initFileserver: function () {
        if (this.ldapBackend) {
            return [{
                xtype: 'fieldset',
                title: this.app.i18n.gettext('Unix'),
                autoHeight: true,
                checkboxToggle: false,
                layout: 'hfit',
                items: [{
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype: 'textfield',
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: 0.333
                    },
                    items: [[{
                        fieldLabel: this.app.i18n.gettext('Home Directory'),
                        name: 'accountHomeDirectory',
                        columnWidth: 0.666
                    }, {
                        fieldLabel: this.app.i18n.gettext('Login Shell'),
                        name: 'accountLoginShell'
                    }]]
                }]
            }, {
                xtype: 'fieldset',
                title: this.app.i18n.gettext('Windows'),
                autoHeight: true,
                checkboxToggle: false,
                layout: 'hfit',
                items: [{
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype: 'textfield',
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: 0.333
                    },
                    items: [[{
                        fieldLabel: this.app.i18n.gettext('Home Drive'),
                        name: 'homeDrive',
                        columnWidth: 0.666
                    }, {
                        xtype: 'displayfield',
                        fieldLabel: this.app.i18n.gettext('Login Time'),
                        name: 'logonTime',
                        emptyText: this.app.i18n.gettext('never logged in'),
                        style: this.displayFieldStyle
                    }], [{
                        fieldLabel: this.app.i18n.gettext('Home Path'),
                        name: 'homePath',
                        columnWidth: 0.666
                    }, {
                        xtype: 'displayfield',
                        fieldLabel: this.app.i18n.gettext('Logout Time'),
                        name: 'logoffTime',
                        emptyText: this.app.i18n.gettext('never logged off'),
                        style: this.displayFieldStyle
                    }], [{
                        fieldLabel: this.app.i18n.gettext('Profile Path'),
                        name: 'profilePath',
                        columnWidth: 0.666
                    }, {
                        xtype: 'displayfield',
                        fieldLabel: this.app.i18n.gettext('Password Last Set'),
                        name: 'pwdLastSet',
                        emptyText: this.app.i18n.gettext('never'),
                        style: this.displayFieldStyle
                    }], [{
                        fieldLabel: this.app.i18n.gettext('Logon Script'),
                        name: 'logonScript',
                        columnWidth: 0.666
                    }], [{
                        xtype: 'extuxclearabledatefield',
                        fieldLabel: this.app.i18n.gettext('Password Can Change'),
                        name: 'pwdCanChange',
                        emptyText: this.app.i18n.gettext('not set')
                    }, {
                        xtype: 'extuxclearabledatefield',
                        fieldLabel: this.app.i18n.gettext('Password Must Change'),
                        name: 'pwdMustChange',
                        emptyText: this.app.i18n.gettext('not set')
                    }, {
                        xtype: 'extuxclearabledatefield',
                        fieldLabel: this.app.i18n.gettext('Kick Off Time'),
                        name: 'kickoffTime',
                        emptyText: this.app.i18n.gettext('not set')
                    }]]
                }]
            }];
        }
        
        return [];
    },

    /**
     * Init Filesystem tab items
     *
     * @return {Array} - array of tab items
     */
    initFilesystem: function () {
        return [{
            xtype: 'fieldset',
            title: this.app.i18n.gettext('Filesystem Quota'),
            autoHeight: true,
            checkboxToggle: true,
            layout: 'hfit',
            listeners: {
                scope: this,
                collapse: function () {
                    this.getForm().findField('personalQuota').setValue(null);
                }
            },
            items: [{
                xtype: 'columnform',
                labelAlign: 'top',
                formDefaults: {
                    xtype: 'textfield',
                    anchor: '100%',
                    columnWidth: 0.666
                },
                items: [[{
                    fieldLabel: this.app.i18n.gettext('Quota'),
                    emptyText: this.app.i18n.gettext('no quota set'),
                    name: 'personalFSQuota',
                    xtype: 'extuxbytesfield'
                }], [{
                    fieldLabel: this.app.i18n.gettext('Current Filesystem usage'),
                    name: 'personalFSSize',
                    xtype: 'extuxbytesfield',
                    disabled: true
                }]]
            }]
        }];
    },

    /**
     * Init IMAP tab items
     * 
     * @return {Array} - array of IMAP tab items
     */
    initImap: function () {
        if (Tine.Tinebase.registry.get('manageImapEmailUser')) {
            return [{
                xtype: 'fieldset',
                title: this.app.i18n.gettext('IMAP Quota'),
                autoHeight: true,
                checkboxToggle: true,
                layout: 'hfit',
                listeners: {
                    scope: this,
                    collapse: function() {
                        this.getForm().findField('emailMailQuota').setValue(null);
                    }
                },
                items: [{
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype: 'textfield',
                        anchor: '100%',
                        columnWidth: 0.666
                    },
                    items: [[{
                        fieldLabel: this.app.i18n.gettext('Quota'),
                        emptyText: this.app.i18n.gettext('no quota set'),
                        name: 'emailMailQuota',
                        xtype: 'extuxbytesfield'
                    }], [{
                        fieldLabel: this.app.i18n.gettext('Current Mailbox size'),
                        name: 'emailMailSize',
                        xtype: 'extuxbytesfield',
                        disabled: true
                    }]]
                }]
            }, {
                xtype: 'fieldset',
                title: this.app.i18n.gettext('Sieve Quota'),
                autoHeight: true,
                checkboxToggle: true,
                layout: 'hfit',
                listeners: {
                    scope: this,
                    collapse: function() {
                        this.getForm().findField('emailSieveQuota').setValue(null);
                    }
                },
                items: [{
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype: 'textfield',
                        anchor: '100%',
                        columnWidth: 0.666
                    },
                    items: [[{
                        fieldLabel: this.app.i18n.gettext('Quota'),
                        emptyText: this.app.i18n.gettext('no quota set'),
                        name: 'emailSieveQuota',
                        xtype: 'extuxbytesfield'
                    }], [{
                        fieldLabel: this.app.i18n.gettext('Current Sieve size'),
                        name: 'emailSieveSize',
                        xtype: 'extuxbytesfield',
                        disabled: true
                    }]
                    ]
                }]
            }, {
                xtype: 'fieldset',
                title: this.app.i18n.gettext('Information'),
                autoHeight: true,
                checkboxToggle: false,
                layout: 'hfit',
                items: [{
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype: 'displayfield',
                        anchor: '100%',
                        columnWidth: 0.666,
                        style: this.displayFieldStyle
                    },
                    items: [[{
                        fieldLabel: this.app.i18n.gettext('Last Login'),
                        name: 'emailLastLogin'
                    }]]
                }]
            }];
        }
        
        return [];
    },
    
    /**
     * @private
     * 
     * init email grids
     * @return Array
     * 
     * TODO     add ctx menus
     */
    initSmtp: function () {
        if (! Tine.Tinebase.registry.get('manageSmtpEmailUser')) {
            return [];
        }
        
        this.initAliasesGrid();
        this.initForwardsGrid();

        return [
            [this.aliasesGrid, this.forwardsGrid],
            [{hidden: true},
             {
                fieldLabel: this.app.i18n.gettext('Forward Only'),
                name: 'emailForwardOnly',
                xtype: 'checkbox',
                readOnly: false
            }]
        ];
    },

    getCommonConfig() {
        return {
            autoExpandColumn: 'email',
            quickaddMandatory: 'email',
            frame: false,
            useBBar: true,
            height: 200,
            columnWidth: 0.5,
            recordClass: Ext.data.Record.create([
                { name: 'email' }
            ])
        };
    },
    
    // can other email accounts process aliasse and forward too ? check request
    // how to init them in email account edit dialog ?
    initAliasesGrid: function(additionConfig) {
        let smtpPrimarydomain = Tine.Tinebase.registry.get('primarydomain');
        let smtpSecondarydomains = Tine.Tinebase.registry.get('secondarydomains');

        let domains = (smtpSecondarydomains && smtpSecondarydomains.length) ? smtpSecondarydomains.split(',') : [];
        if (smtpPrimarydomain.length) {
            domains.push(smtpPrimarydomain);
        }
        const app = Tine.Tinebase.appMgr.get('Admin');

        let smtpAliasesDispatchFlag = Tine.Tinebase.registry.get('smtpAliasesDispatchFlag');

        let cm = [{
            id: 'email',
            header: app.i18n.gettext('E-mail Alias'),
            width: 260,
            hideable: false,
            sortable: true,
            quickaddField: new Ext.form.TextField({
                emptyText: app.i18n.gettext('Add an alias address...'),
                vtype: 'email'
            }),
            editor: new Ext.form.TextField({allowBlank: false}),
        }];

        let gridPlugins = [];
        if (smtpAliasesDispatchFlag) {
            this.aliasesDispatchCheckColumn = new Ext.grid.CheckColumn({
                id: 'dispatch_address',
                header: '...',
                tooltip: app.i18n.gettext('This alias can be used for sending e-mails.'),
                width: 40,
                hideable: false,
                sortable: true
            })
            cm.push(this.aliasesDispatchCheckColumn);
            gridPlugins.push(this.aliasesDispatchCheckColumn);
        }
        const config = _.assign(this.getCommonConfig(), additionConfig);

        this.aliasesGrid = new Tine.widgets.grid.QuickaddGridPanel(
            Ext.apply({
                onNewentry: function(value) {
                    const split = value.email ? value.email.split('@') : [];
                    if (split.length !== 2 || split[1].split('.').length < 2) {
                        return false;
                    }
                    const domain = split[1];
                    if (domains.indexOf(domain) > -1) {
                        if (smtpAliasesDispatchFlag) {
                            value.dispatch_address = 1;
                        }
                        Tine.widgets.grid.QuickaddGridPanel.prototype.onNewentry.call(this, value);
                    } else {
                        Ext.MessageBox.show({
                            buttons: Ext.Msg.OK,
                            icon: Ext.MessageBox.WARNING,
                            title: app.i18n._('Domain not allowed'),
                            msg: String.format(app.i18n._('The domain {0} of the alias {1} you tried to add is neither configured as primary domain nor set as a secondary domain in the setup.'
                                + ' Please add this domain to the secondary domains in SMTP setup or use another domain which is configured already.'),
                                '<b>' + domain + '</b>', '<b>' + value.email + '</b>')
                        });
                        return false;
                    }
                },
                cm: new Ext.grid.ColumnModel(cm),
                plugins: gridPlugins
            },config)
        );
        return this.aliasesGrid;
    },

    initForwardsGrid: function(additionConfig) {
        let aliasesStore = this.aliasesGrid.getStore();
        const app = Tine.Tinebase.appMgr.get('Admin');
        let record = this.record ?? additionConfig.record;
        const config = _.assign(this.getCommonConfig(), additionConfig);

        this.forwardsGrid = new Tine.widgets.grid.QuickaddGridPanel(
            Ext.apply({
                onNewentry: function(value) {
                    if (value.email === record.get('accountEmailAddress') || aliasesStore.find('email', value.email) !== -1) {
                        Ext.MessageBox.show({
                            buttons: Ext.Msg.OK,
                            icon: Ext.MessageBox.WARNING,
                            title: app.i18n._('Forwarding to self'),
                            msg: app.i18n._('You are not allowed to set a forwarding e-mail address that is identical to the users primary e-mail or one of his aliases.')
                        });
                        return false;
                    } else {
                        Tine.widgets.grid.QuickaddGridPanel.prototype.onNewentry.call(this, value);
                    }
                },
                cm: new Ext.grid.ColumnModel([{
                    id: 'email',
                    header: app.i18n.gettext('E-mail Forward'),
                    width: 300,
                    hideable: false,
                    sortable: true,
                    quickaddField: new Ext.form.TextField({
                        emptyText: app.i18n.gettext('Add a forwarding address...'),
                        vtype: 'email'
                    }),
                    editor: new Ext.form.TextField({allowBlank: false})
                }])
            }, config)
        );
        
        return this.forwardsGrid;
    },

    initPasswordConfirmWindow: function() {
        this.passwordConfirmWindow = new Ext.Window({
            title: this.app.i18n.gettext('Password confirmation'),
            closeAction: 'hide',
            modal: true,
            width: 300,
            height: 150,
            items: [{
                xtype: 'form',
                bodyStyle: 'padding: 5px;',
                buttonAlign: 'right',
                labelAlign: 'top',
                anchor: '100%',
                monitorValid: true,
                defaults: { anchor: '100%' },
                items: [{
                    xtype: 'tw-passwordTriggerField',
                    locked: true,
                    autocomplete: 'new-password',
                    id: 'passwordRepeat',
                    fieldLabel: this.app.i18n.gettext('Repeat password'),
                    name: 'passwordRepeat',
                    validator: this.onPasswordConfirm.createDelegate(this),
                    listeners: {
                        scope: this,
                        specialkey: function (field, event) {
                            if (event.getKey() === event.ENTER) {
                                // call OK button handler
                                this.passwordConfirmWindow.items.first().buttons[1].handler.call(this);
                            }
                        }
                    }
                }, {
                    xtype: 'displayfield',
                    hideLabel: true,
                    id: 'passwordStatus',
                    value: this.app.i18n.gettext('Passwords do not match!')
                }],
                buttons: [{
                    text: i18n._('Cancel'),
                    iconCls: 'action_cancel',
                    scope: this,
                    handler: function () {
                        this.passwordConfirmWindow.hide();
                    }
                }, {
                    text: i18n._('Ok'),
                    formBind: true,
                    iconCls: 'action_saveAndClose',
                    scope: this,
                    handler: function () {
                        var confirmForm = this.passwordConfirmWindow.items.first().getForm();

                        // check if confirm form is valid (we need this if special key called button handler)
                        if (confirmForm.isValid()) {
                            this.passwordConfirmWindow.hide();
                            // focus email field
                            this.getForm().findField('accountEmailAddress').focus(true, 100);
                        }
                    }
                }]
            }],
            listeners: {
                scope: this,
                show: function (win) {
                    var confirmForm = this.passwordConfirmWindow.items.first().getForm();

                    confirmForm.reset();
                    confirmForm.findField('passwordRepeat').focus(true, 500);
                }
            }
        });
        this.passwordConfirmWindow.render(document.body);
    },

    /**
     * @private
     */
    getFormItems: function () {
        this.displayFieldStyle = {
            border: 'silver 1px solid',
            padding: '3px',
            height: '11px'
        };

        if (Tine.Tinebase.appMgr.get('Admin').featureEnabled('featureForceRetypePassword')) {
            this.initPasswordConfirmWindow();
        }

        this.MFAPanel = new MFAPanel({
            app: this.app,
            height: 130,
            title: false,
            account: this.record,
            editDialog: this,
        });

        this.mustChangeTriggerPlugin = new FieldTriggerPlugin({
            visible: false,
            triggerConfig: {tag: "div", cls: "x-form-trigger-flat x-form-trigger-plugin x-form-localized-field tinebase-trigger-overlay"},
            onTriggerClick:  Ext.emptyFn,
            qtip: i18n._('Password is expired in accordance with the password policy and needs to be changed'),
            preserveElStyle: true
        })
        this.saveInaddressbookFields = this.getSaveInAddessbookFields(this);
        this.saveInaddressbookFields.push({
            hideLabel: true,
            xtype: 'checkbox',
            boxLabel: this.app.i18n.gettext('Password Must Change'),
            hidden: this.ldapBackend,
            ctCls: 'admin-checkbox',
            fieldClass: 'admin-checkbox-box',
            name: 'password_must_change',
            plugins: [this.mustChangeTriggerPlugin]
        });
        
        var config = {
            xtype: 'tabpanel',
            deferredRender: false,
            border: false,
            plain: true,
            activeTab: 0,
            items: [{
                title: this.app.i18n.gettext('Account'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'hfit',
                items: [{
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype: 'textfield',
                        anchor: '100%',
                        labelSeparator: '',
                        columnWidth: 0.333
                    },
                    items: [[{
                        fieldLabel: this.app.i18n.gettext('First name'),
                        name: 'accountFirstName',
                        columnWidth: 0.5,
                        enableKeyEvents: true,
                        listeners: {
                            scope: this,
                            keyup: this.suggestNameBasedProps,
                            render: function (field) {
                                field.focus(false, 250);
                                field.selectText();
                            }
                        }
                    }, {
                        fieldLabel: this.app.i18n.gettext('Last name'),
                        name: 'accountLastName',
                        allowBlank: false,
                        columnWidth: 0.5,
                        enableKeyEvents: true,
                        listeners: {
                            scope: this,
                            keyup: this.suggestNameBasedProps
                        }
                    }], [{
                        fieldLabel: this.app.i18n.gettext('Display name'),
                        name: 'accountDisplayName',
                        allowBlank: false,
                        columnWidth: 0.5
                    }, {
                        fieldLabel: this.app.i18n.gettext('Full Name'),
                        name: 'accountFullName',
                        allowBlank: false,
                        columnWidth: 0.5,
                        plugins: [new FieldInfoPlugin({qtip: this.app.i18n._('Full name is used to create the distinguishedName (DN) in AD integration')})]
                    }], [{
                        fieldLabel: this.app.i18n.gettext('Login name'),
                        name: 'accountLoginName',
                        allowBlank: false,
                        columnWidth: 0.5
                    }, {
                        fieldLabel: this.app.i18n.gettext('Password'),
                        xtype: 'tw-passwordTriggerField',
                        id: 'accountPassword',
                        name: 'accountPassword',
                        locked: true,
                        autocomplete: 'new-password',
                        columnWidth: 0.5,
                        passwordsMatch: true,
                        enableKeyEvents: true,
                        listeners: {
                            scope: this,
                            blur: function (field) {
                                var fieldValue = field.getValue();
                                if (fieldValue !== '' && this.passwordConfirmWindow) {
                                    // show password confirmation
                                    // NOTE: we can't use Ext.Msg.prompt because field has to be of inputType: 'password'
                                    this.passwordConfirmWindow.show.defer(100, this.passwordConfirmWindow);
                                }
                            },
                            destroy: function () {
                                if (this.passwordConfirmWindow) {
                                    this.passwordConfirmWindow.destroy();
                                }
                            },
                            keydown: function (field) {
                                if (this.passwordConfirmWindow) {
                                    field.passwordsMatch = false;
                                }
                            }
                        },
                        validateValue: function (value) {
                            return (this.passwordsMatch);
                        }
                    }], [{
                        xtype: 'fieldset',
                        title: this.app.i18n.gettext('Multi Factor Authentication Config'),
                        autoHeight: true,
                        checkboxToggle: false,
                        columnWidth: 1,
                        layout: 'hfit',
                        items: [this.MFAPanel]
                    }],  [{
                        vtype: 'email',
                        fieldLabel: this.app.i18n.gettext('E-mail'),
                        name: 'accountEmailAddress',
                        id: 'accountEmailAddress',
                        columnWidth: 0.5,
                        getValue: function() {
                            const value = Ext.form.TextField.prototype.getValue.apply(this, arguments)
                            return value || null
                        }
                    }, {
                        //vtype: 'email',
                        fieldLabel: this.app.i18n.gettext('OpenID'),
                        emptyText: '(' + this.app.i18n.gettext('Login name') + ')',
                        name: 'openid',
                        columnWidth: 0.5
                    }], [{
                        xtype: 'tinerecordpickercombobox',
                        fieldLabel: this.app.i18n.gettext('Primary group'),
                        listWidth: 250,
                        name: 'accountPrimaryGroup',
                        blurOnSelect: true,
                        allowBlank: false,
                        recordClass: Tine.Admin.Model.Group,
                        listeners: {
                            scope: this,
                            'select': function (combo, record, index) {
                                // refresh grid
                                if (this.pickerGridGroups) {
                                    this.pickerGridGroups.getView().refresh();
                                }
                            }
                        }
                    }, new Tine.Tinebase.widgets.keyfield.ComboBox({
                        fieldLabel: this.app.i18n._('User Type'),
                        name: 'type',
                        hidden: !Tine.Tinebase.appMgr.get('Admin').featureEnabled('featureChangeUserType'),
                        app: 'Tinebase',
                        keyFieldName: 'userTypes',
                    }), {
                        xtype: 'combo',
                        fieldLabel: this.app.i18n.gettext('Status'),
                        name: 'accountStatus',
                        mode: 'local',
                        triggerAction: 'all',
                        allowBlank: false,
                        editable: false,
                        store: [
                            ['enabled',  this.app.i18n.gettext('enabled')],
                            ['disabled', this.app.i18n.gettext('disabled')],
                            ['expired',  this.app.i18n.gettext('expired')],
                            ['blocked',  this.app.i18n.gettext('blocked')]
                        ],
                        listeners: {
                            scope: this,
                            select: function (combo, record) {
                                switch (record.data.field1) {
                                    case 'blocked':
                                        Ext.Msg.alert(this.app.i18n._('Invalid Status'),
                                            this.app.i18n._('Blocked status is only valid if the user tried to login with a wrong password to often. It is not possible to set this status here.'));
                                        combo.setValue(combo.startValue);
                                        break;
                                    case 'expired':
                                        this.getForm().findField('accountExpires').setValue(new Date());
                                        break;
                                    case 'enabled':
                                        var expiryDateField = this.getForm().findField('accountExpires'),
                                            expiryDate = expiryDateField.getValue(),
                                            now = new Date();
                                            
                                        if (expiryDate < now) {
                                            expiryDateField.setValue('');
                                        }
                                        break;
                                    default:
                                        // do nothing
                                }
                            }
                        }
                    }, {
                        xtype: 'extuxclearabledatefield',
                        fieldLabel: this.app.i18n.gettext('Expires'),
                        name: 'accountExpires',
                        emptyText: this.app.i18n.gettext('never')
                    }], this.saveInaddressbookFields
                    ]
                }, {
                    xtype: 'fieldset',
                    title: this.app.i18n.gettext('Information'),
                    autoHeight: true,
                    checkboxToggle: false,
                    layout: 'hfit',
                    items: [{
                        xtype: 'columnform',
                        labelAlign: 'top',
                        formDefaults: {
                            xtype: 'displayfield',
                            anchor: '100%',
                            labelSeparator: '',
                            columnWidth: 0.333,
                            style: this.displayFieldStyle
                        },
                        items: [[{
                            fieldLabel: this.app.i18n.gettext('Last login at'),
                            name: 'accountLastLogin',
                            emptyText: this.ldapBackend ? this.app.i18n.gettext("don't know") : this.app.i18n.gettext('never logged in')
                        }, {
                            fieldLabel: this.app.i18n.gettext('Last login from'),
                            name: 'accountLastLoginfrom',
                            emptyText: this.ldapBackend ? this.app.i18n.gettext("don't know") : this.app.i18n.gettext('never logged in')
                        }, {
                            fieldLabel: this.app.i18n.gettext('Password set'),
                            name: 'accountLastPasswordChange',
                            emptyText: this.app.i18n.gettext('never'),
                            plugins: [this.mustChangeTriggerPlugin]
                        }, {
                            fieldLabel: this.app.i18n.gettext('Account ID'),
                            name: 'accountId',
                        },]]
                    }]
                }]
            }, {
                title: this.app.i18n.gettext('User groups'),
                border: false,
                frame: true,
                layout: 'fit',
                items: this.initUserGroups()
            }, {
                title: this.app.i18n.gettext('User roles'),
                border: false,
                frame: true,
                layout: 'fit',
                items: this.initUserRoles()
            }, {
                title: this.app.i18n.gettext('Fileserver'),
                disabled: !this.ldapBackend,
                border: false,
                frame: true,
                items: this.initFileserver()
            }, {
                title: this.app.i18n.gettext('Filesystem'),
                border: false,
                frame: true,
                items: this.initFilesystem()
            }, {
                title: this.app.i18n.gettext('IMAP'),
                disabled: ! Tine.Tinebase.registry.get('manageImapEmailUser'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'hfit',
                items: this.initImap()
            }, {
                xtype: 'columnform',
                title: this.app.i18n.gettext('SMTP'),
                disabled: ! Tine.Tinebase.registry.get('manageSmtpEmailUser'),
                border: false,
                frame: true,
                labelAlign: 'top',
                formDefaults: {
                    xtype: 'textfield',
                    anchor: '100%',
                    labelSeparator: '',
                    columnWidth: 0.5
                },
                items: this.initSmtp()
            }].concat(this.app.featureEnabled('xpropsEditor') ? [new XPropsPanel({})] : [])
        };
        return config;
    },

    afterRender: function () {
        Tine.Admin.UserEditDialog.superclass.afterRender.call(this);

        let changeAfter = Tine.Tinebase.configManager.get('userPwPolicy.pwPolicyChangeAfter', 'Tinebase')
        if (!changeAfter || changeAfter === 0) return

        let lastChangeDate = this.record.get('accountLastPasswordChange')

        let maxDate = new Date(lastChangeDate).add('d', changeAfter)
        if (maxDate > new Date()) return

        let lastChangeField = this.getForm().findField('accountLastPasswordChange'),
            mustChangeField = this.getForm().findField('password_must_change')

        lastChangeField.addClass('tinebase-warning')
        mustChangeField.disable()

        this.record.set('password_must_change_actual', this.record.get('password_must_change'))
        this.record.set('password_must_change', 1)
        this.mustChangeTriggerPlugin.visible = true
    },

    suggestNameBasedProps: function(field, e) {
        // suggest for new users only!
        if (!this.record.id) {
            // accountFullName (cn im AD) + accountDisplayName(displayname im AD) + accountLoginName + accountEmailAddress
            Object.keys(Tine.Tinebase.configManager.get('accountTwig')).asyncForEach(async (fieldName) => {
                if (fieldName === 'accountEmailAddress' && ! Tine.Tinebase.registry.get('primarydomain')) {
                    // skip email without configured domain
                    return;
                }
                const field = this.getForm().findField(fieldName);
                // suggest for unchanged fields only
                if (field && (!field.suggestedValue || field.getValue() === field.suggestedValue)) {
                    this.onRecordUpdate();
                    // @FIXME twing can't cope with null values yet, remove this once twing fixed it
                    const accountData = JSON.parse(JSON.stringify(this.record.data).replace(/:null([,}])/g, ':""$1'));
                    const suggestion = await this.twingEnv.render(fieldName, {account: accountData, email: {primarydomain: Tine.Tinebase.registry.get('primarydomain')}});

                    field.setValue(suggestion);
                    this.record.set(fieldName, suggestion);
                    field.suggestedValue = suggestion;
                }
            });
        }
    },

    /**
     * @param value
     * @return boolean
     */
    validateLoginName: function (value) {
        return value.match(/^[a-z\d._-]+$/i) !== null;
    },
    
    getSaveInAddessbookFields(scope, hidden) {
        this.app = Tine.Tinebase.appMgr.get('Admin');
        
        return [{
            xtype: 'combo',
            fieldLabel: this.app.i18n.gettext('Visibility'),
            name: 'visibility',
            mode: 'local',
            triggerAction: 'all',
            allowBlank: false,
            editable: false,
            hidden: hidden ?? false,
            value: 'hidden',
            store: [['displayed', this.app.i18n.gettext('Display in addressbook')], ['hidden', this.app.i18n.gettext('Hide from addressbook')]],
            listeners: {
                scope: scope,
                select: function (combo, record) {
                    // disable container_id combo if hidden
                    var addressbookContainerCombo = scope.getForm().findField('container_id');
                    addressbookContainerCombo.setDisabled(record.data.field1 === 'hidden');
                    if (addressbookContainerCombo.getValue() === '') {
                        addressbookContainerCombo.setValue(null);
                    }
                }
            }
        }, {
            xtype: 'tinerecordpickercombobox',
            fieldLabel: this.app.i18n.gettext('Saved in Addressbook'),
            name: 'container_id',
            blurOnSelect: true,
            allowBlank: false,
            forceSelection: true,
            listWidth: 250,
            recordClass: Tine.Tinebase.Model.Container,
            disabled: scope.record.get('visibility') === 'hidden',
            hidden: hidden ?? false,
            recordProxy: Tine.Admin.sharedAddressbookBackend,
            listeners: {
                specialkey: function(combo, e) {
                    if (e.getKey() == e.TAB && ! e.shiftKey) {
                        // move cursor to first input field (skip display fields)
                        // @see 0008226: when tabbing in user edit dialog, wrong tab content is displayed
                        e.preventDefault();
                        e.stopEvent();
                        scope.getForm().findField('accountFirstName').focus();
                    }
                },
                scope: scope
            }
        }];
    }
});

/**
 * User Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Admin.UserEditDialog.openWindow = function (config) {
    
    const id = config.recordId ?? config.record?.id ?? 0;
    var window = Tine.WindowFactory.getWindow({
        width: 600,
        height: 520,
        name: Tine.Admin.UserEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Admin.UserEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

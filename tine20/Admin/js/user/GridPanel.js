/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Admin.user');


/**
 * User grid panel
 * 
 * @namespace   Tine.Admin.user
 * @class       Tine.Admin.user.GridPanel
 * @extends     Tine.widgets.grid.GridPanel
 */
Tine.Admin.user.GridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * @property isLdapBackend
     * @type Boolean
     */
    isLdapBackend: false,
    
    newRecordIcon: 'action_addContact',
    recordClass: Tine.Admin.Model.User,
    recordProxy: Tine.Admin.userBackend,
    defaultSortInfo: {field: 'accountLoginName', direction: 'ASC'},
    evalGrants: false,
    gridConfig: {
        id: 'gridAdminUsers',
        autoExpandColumn: 'accountDisplayName'
    },
    
    initComponent: function() {
        this.gridConfig.cm = this.getColumnModel();
        this.isLdapBackend = Tine.Tinebase.registry.get('accountBackend') == 'Ldap';
        this.isEmailBackend = Tine.Tinebase.registry.get('manageImapEmailUser');
        Tine.Admin.user.GridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * init actions with actionToolbar, contextMenu and actionUpdater
     * 
     * @private
     */
    initActions: function() {
        this.actionEnable = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Enable Account'),
            allowMultiple: true,
            disabled: true,
            handler: this.enableDisableButtonHandler.createDelegate(this, ['enabled']),
            iconCls: 'action_enable',
            actionUpdater: this.enableDisableActionUpdater.createDelegate(this, [['disabled', 'blocked', 'expired']], true)
        });
    
        this.actionDisable = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Disable Account'),
            allowMultiple: true,
            disabled: true,
            handler: this.enableDisableButtonHandler.createDelegate(this, ['disabled']),
            iconCls: 'action_disable',
            actionUpdater: this.enableDisableActionUpdater.createDelegate(this, [['enabled']], true)
        });
    
        this.actionResetPassword = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Reset Password'),
            disabled: true,
            handler: this.resetPasswordHandler,
            iconCls: 'action_password',
            scope: this
        });

        this.actionUpdater.addActions([
            this.actionEnable,
            this.actionDisable,
            this.actionResetPassword
        ]);
        
        Tine.Admin.user.GridPanel.superclass.initActions.call(this);
    },
    
    /**
     * initialises filter toolbar
     */
    initFilterPanel: function() {
        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            filterModels: [
                {label: this.app.i18n.n_('User', 'Users', 1),    field: 'query',       operators: ['contains']}
                //{label: this.app.i18n._('Description'),    field: 'description', operators: ['contains']},
            ],
            defaultFilter: 'query',
            filters: [],
            plugins: [
                new Tine.widgets.grid.FilterToolbarQuickFilterPlugin()
            ]
        });
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
    },

    /**
     * on update after edit
     *
     * @param {String|Tine.Tinebase.data.Record} record
     */
    onUpdateRecord: function (record) {
        Tine.Admin.customfield.GridPanel.superclass.onUpdateRecord.apply(this, arguments);

        // reload app if current user changed
        const oldRecord = Tine.Tinebase.registry.get('currentAccount');
        const updatedRecord = Ext.util.JSON.decode(record);

        if (oldRecord.accountId === updatedRecord.accountId) {
            const needsRestart = ['accountEmailAddress', 'accountDisplayName', 'mfa_configs'].some(key =>
                JSON.stringify(oldRecord[key]) !== JSON.stringify(updatedRecord[key])
            );
            if (needsRestart) Tine.Tinebase.common.confirmApplicationRestart();
        }
    },

    /**
     * add custom items to action toolbar
     * 
     * @return {Object}
     */
    getActionToolbarItems: function() {
        return [
            Ext.apply(new Ext.Button(this.actionEnable), {
                scale: 'medium',
                rowspan: 2,
                iconAlign: 'top'
            }),
            Ext.apply(new Ext.Button(this.actionDisable), {
                scale: 'medium',
                rowspan: 2,
                iconAlign: 'top'
            }),
            Ext.apply(new Ext.Button(this.actionResetPassword), {
                scale: 'medium',
                rowspan: 2,
                iconAlign: 'top'
            })
        ];
    },
    
    /**
     * add custom items to context menu
     * 
     * @return {Array}
     */
    getContextMenuItems: function() {
        var items = [
            '-',
            this.actionEnable,
            this.actionDisable,
            '-',
            this.actionResetPassword
        ];
        
        return items;
    },
    
    /**
     * returns column model
     * 
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                hidden: true,
                resizable: true
            },
            columns: this.getColumns()
        });
    },
    
    /**
     * returns columns
     * @private
     * @return Array
     */
    getColumns: function(){
        const columns = [
            { header: this.app.i18n._('ID'), id: 'accountId' },
            { header: this.app.i18n._('Status'), id: 'accountStatus', hidden: this.isLdapBackend, width: 30, renderer: this.statusRenderer},
            { header: this.app.i18n._('Display name'), id: 'accountDisplayName', hidden: false},
            { header: this.app.i18n._('Login name'), id: 'accountLoginName', hidden: false},
            { header: this.app.i18n._('Last name'), id: 'accountLastName'},
            { header: this.app.i18n._('First name'), id: 'accountFirstName'},
            { header: this.app.i18n._('Email'), id: 'accountEmailAddress', hidden: false},
            { header: this.app.i18n._('Email usage'), id: 'emailQuota', dataIndex: 'emailUser', hidden: this.isEmailBackend, renderer: this.emailQuotaRenderer, sortable: false},
            { header: this.app.i18n._('Filesystem usage'), id: 'fileQuota', dataIndex: 'filesystemSize', renderer: this.fileQuotaRenderer, hidden: false, sortable: false},
            { header: this.app.i18n._('OpenID'), id: 'openid' },
            { header: this.app.i18n._('Last login at'), id: 'accountLastLogin', hidden: this.isLdapBackend, renderer: Tine.Tinebase.common.dateTimeRenderer},
            { header: this.app.i18n._('Last login from'), id: 'accountLastLoginfrom', width: 120, hidden: this.isLdapBackend},
            { header: this.app.i18n._('MFA Configured'), id: 'mfa_configs', renderer: this.mfaRenderer.createDelegate(this), hidden: false},
            { header: this.app.i18n._('Password Must Change'), id: 'must_change_password', renderer: this.mustChangeRenderer, hidden: false, sortable: false},
            { header: this.app.i18n._('Password changed'), id: 'accountLastPasswordChange', renderer: this.dateTimeRenderer, hidden: false},
            { header: this.app.i18n._('Expires'), id: 'accountExpires', renderer: Tine.Tinebase.common.dateTimeRenderer, hidden: false},
            { header: this.app.i18n._('Full Name'), id: 'accountFullName'}
        ];
        
        return columns.concat(this.getModlogColumns());
    },

    dateTimeRenderer: function ($_iso8601, metadata, _cellObject, _record, _rowIndex, _colIndex, _dataStore) {
        let rendered = Tine.Tinebase.common.dateTimeRenderer($_iso8601, metadata)
        let hoverText = i18n._('Password is expired in accordance with the password policy and needs to be changed')

        if (!$_iso8601) {
            metadata.css += ' tinebase-warning'
            metadata.cellAttr = 'title="'+hoverText+'"'
            return rendered
        }

        let changeAfter = Tine.Tinebase.configManager.get('userPwPolicy.pwPolicyChangeAfter', 'Tinebase')
        if (!changeAfter || changeAfter === 0) return rendered

        let maxDate = new Date($_iso8601).add('d', changeAfter)
        if (maxDate < new Date()) {
            metadata.css += ' tinebase-warning'
            metadata.cellAttr = 'title="'+hoverText+'"'
        }
        return rendered
    },

    mustChangeRenderer: function (_value, metadata) {
        if (_value === 'expired') {
            const hoverText = i18n._('Password is expired in accordance with the password policy and needs to be changed')
            metadata.cellAttr = 'title="'+hoverText+'"'
        } else {
            metadata.cellAttr = ''
        }
        if (_value !== null) {
            return Tine.Tinebase.common.booleanRenderer('1')
        }
        return Tine.Tinebase.common.booleanRenderer('0')
    },

    enableDisableButtonHandler: function(status) {
        var accountIds = new Array();
        var selectedRows = this.grid.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            accountIds.push(selectedRows[i].id);
        }
        
        Ext.Ajax.request({
            url : 'index.php',
            method : 'post',
            params : {
                method : 'Admin.setAccountState',
                accountIds : accountIds,
                status: status
            },
            scope: this,
            callback : function(_options, _success, _response) {
                if(_success === true) {
                    var result = Ext.util.JSON.decode(_response.responseText);
                    if(result.success === true) {
                        this.loadGridData({
                            removeStrategy: 'keepBuffered'
                        });
                    }
                }
            }
        });
    },
    
    /**
     * updates enable/disable actions
     * 
     * @param {Ext.Action} action
     * @param {Object} grants grants sum of grants
     * @param {Object} records
     * @param {Boolean} isFilterSelect
     * @param {Array} requiredAccountStatus
     */
    enableDisableActionUpdater: function(action, grants, records, isFilterSelect, requiredAccountStatus) {
        let enabled = records.length > 0;
        if (requiredAccountStatus) {
            Ext.each(records, function (record) {
                enabled &= requiredAccountStatus.indexOf(record.get('accountStatus')) >= 0;// === requiredAccountStatus;
                return enabled;
            }, this);
        }
        
        action.setDisabled(!enabled);
    },
    
    /**
     * reset password
     *
     */
    resetPasswordHandler: function(_button, _event) {
        const passwordDialog = new Tine.Tinebase.widgets.dialog.ResetPasswordDialog({
            record: this.grid.getSelectionModel().getSelected(),
        });
        passwordDialog.openWindow();
        passwordDialog.on('apply', async (record) => {
            this.grid.getStore().reload();
        }, this);
    },
    
    statusRenderer: function (_value, _cellObject, _record, _rowIndex, _colIndex, _dataStore) {
        const statusMap = {
            blocked: 'images/icon-set/icon_action_minus.svg',
            enabled: 'images/icon-set/icon_ok.svg',
            disabled: 'images/icon-set/icon_stop.svg',
            expired: 'images/icon-set/icon_time.svg'
        };

        return statusMap[_value] ? `<img class='tine-keyfield-icon' src='${statusMap[_value]}' width='16' height='16'/>` : Ext.util.Format.htmlEncode(_value);
    },

    mfaRenderer: function (_value, _cellObject, _record, _rowIndex, _colIndex, _dataStore) {
        const hasMFA = _.isArray(_value) && _value.length;
        const icon = this.statusRenderer(hasMFA ? 'enabled' : 'disabled');
        const text = hasMFA ? _.map(_value , 'mfa_config_id').join(', ') : '';
        return `<span>${icon} ${text}</span>`;

    },

    //shared with Tine.Felamimail.admin.showAccountGridPanel
    emailQuotaRenderer: function(_value) {
        var quota = _value['emailMailQuota'] ? _value['emailMailQuota'] : 0,
            size = _value['emailMailSize'] ? _value['emailMailSize'] : 0;

        return Tine.widgets.grid.QuotaRenderer(size, quota, /*use SoftQuota*/ false);
    },

    fileQuotaRenderer: function(_value, _cellObject, _record) {
        var accountConfig = _record.get('xprops') ? _record.get('xprops') : null,
            quotaConfig = Tine.Tinebase.configManager.get('quota'),
            quota = accountConfig && accountConfig['personalFSQuota'] ? parseInt(accountConfig['personalFSQuota'])  : quotaConfig.totalByUserInMB * 1024 * 1024,
            size =  _record.get('filesystemSize') ? parseInt(_record.get('filesystemSize')) : 0;

        return Tine.widgets.grid.QuotaRenderer(size, quota, /*use SoftQuota*/ true);
    }
});

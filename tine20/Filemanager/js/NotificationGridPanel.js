/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager');

Tine.Filemanager.NotificationGridPanel = Ext.extend(Tine.widgets.account.PickerGridPanel, {
    app: null,

    selectType: 'both',
    selectAnyone: false,
    selectMyself: true,

    userCombo: null,
    groupCombo: null,

    currentUser: null,

    editDialog: null,
    actionUpdater: null,

    enableTbar: true,

    initComponent: function () {
        var _ = window.lodash;

        this.app = this.app || Tine.Tinebase.appMgr.get('Filemanager');

        // init actions
        this.actionUpdater = new Tine.widgets.ActionUpdater({
            recordClass: Tine.Filemanager.Model.Node,
            evalGrants: true
        });

        this.currentUser = Tine.Tinebase.registry.get('currentAccount');
        this.initColumns();

        if (!_.get(this.editDialog, 'record.data.account_grants.adminGrant', false)) {
            this.selectType = 'myself';
        }

        Tine.Filemanager.NotificationGridPanel.superclass.initComponent.apply(this, arguments);


        this.on('beforeedit', this.onBeforeEdit.createDelegate(this));

        this.getSelectionModel().on('selectionchange', function (sm) {
            this.actionUpdater.updateActions(sm);
        }, this);
    },

    initColumns: function () {
        var me = this;
        this.configColumns = [
            new Ext.ux.grid.CheckColumn({
                id: 'active',
                header: this.app.i18n._('Notification'),
                tooltip: this.app.i18n._('Notification active'),
                dataIndex: 'active',
                width: 55,
                onBeforeCheck: function (checkbox, record) {
                    return this.checkGrant(record);
                }.createDelegate(me)
            }), {
                id: 'summary',
                dataIndex: 'summary',
                width: 150,
                sortable: true,
                header: this.app.i18n._('Summary'),
                renderer: function (value) {
                    return value ?
                        String.format(me.app.i18n.ngettext('Once a Day', 'Every {0} Days', value), value) :
                        me.app.i18n._('No');
                },
                editor: new Ext.form.ComboBox({
                    triggerAction: 'all',
                    lazyRender: false,
                    editable: false,
                    mode: 'local',
                    forceSelection: true,
                    allowBlank: false,
                    expandOnFocus: true,
                    blurOnSelect: true,
                    autoSelect: true,
                    store: [
                        [0, me.app.i18n._('Don\'t summarize')],
                        [1, me.app.i18n._('Once a day')],
                        [3, me.app.i18n._('Every 3 days')],
                        [7, me.app.i18n._('Once a week')],
                        [30, me.app.i18n._('Once a month')], // somehow once a month
                        [365, me.app.i18n._('Once a year')] // somehow once a year
                    ]
                })
            }
        ];
    },

    onBeforeEdit: function (e) {
        return this.checkGrant(e.record);
    },

    checkGrant: function (record) {
        var _ = window.lodash;

        var userHasAdminGrant = _.get(this.editDialog, 'record.data.account_grants.adminGrant', false);

        // get id if it's from notification props, if its a record which was added or if it's a group which was added
        var id = (_.get(record, 'data.account_id', _.get(record, 'data.accountId')) || _.get(record, 'data.group_id')) || record.id;

        if (!userHasAdminGrant && id !== this.currentUser.accountId) {
            return false;
        }

        return true;
    },

    getColumnModel: function () {
        if (!this.colModel) {
            this.colModel = new Ext.grid.ColumnModel({
                defaults: {
                    sortable: true
                },
                columns: [
                    {
                        id: 'name',
                        header: this.app.i18n._('Name'),
                        dataIndex: this.recordPrefix + 'name',
                        renderer: this.accountRenderer.createDelegate(this)
                    }
                ].concat(this.configColumns)
            });
        }

        return this.colModel;
    },

    accountRenderer: function (value, meta, record) {
        if (!record) {
            return '';
        }

        var _ = window.lodash;

        var iconCls = 'tine-grid-row-action-icon renderer ' + (_.get(record, 'data.accountType') === 'user' ? 'renderer renderer_accountUserIcon' : 'renderer_accountGroupIcon');

        return '<div class="' + iconCls + '">&#160;</div>' + Ext.util.Format.htmlEncode(_.get(record, 'data.accountName') || value);
    },

    resetCombobox: function (combo) {
        combo.collapse();
        combo.clearValue();
        combo.reset();
    },

    getContactSearchCombo: function () {
        if (!this.userCombo) {
            this.userCombo = new Tine.Addressbook.SearchCombo({
                hidden: true,
                accountsStore: this.store,
                emptyText: i18n._('Search for users ...'),
                newRecordClass: this.recordClass,
                newRecordDefaults: this.recordDefaults,
                recordPrefix: this.recordPrefix,
                userOnly: true,
                additionalFilters: (this.showHidden) ? [{field: 'showDisabled', operator: 'equals', value: true}] : []
            });

            this.userCombo.onSelect = this.onAddRecordFromCombo.createDelegate(this, [this.userCombo], true);
        }

        return this.userCombo;
    },

    onAddMyself: function () {
        var currentUser = Tine.Tinebase.registry.get('currentAccount'),
            record,
            recordData = (this.recordDefaults !== null) ? this.recordDefaults : {};

        // user record
        recordData['active'] = true;
        recordData['summary'] = null;
        recordData['accountId'] = currentUser.accountId;
        recordData['accountType'] = 'user';
        recordData['accountName'] = currentUser.accountDisplayName;

        record = new this.recordClass(recordData, currentUser.accountId);

        // check if already in
        if (! this.store.getById(record.id)) {
            this.store.add([record]);
        }
    },

    onAddRecordFromCombo: function (recordToAdd, index, combo) {
        var _ = window.lodash,
            id = _.get(recordToAdd, 'data.account_id') || _.get(recordToAdd, 'data.group_id');

        // If there is no admin grant, only allow to edit the own record
        if (!_.get(this.editDialog, 'record.data.account_grants.adminGrant', false) && id !== this.currentUser.accountId) {
            Ext.Msg.alert(i18n._('No permission'), 'You are only allowed to edit your own notifications.');

            this.resetCombobox(combo);
            return false;
        }

        var record = {
            'active': true,
            'summary': null,
            'accountId': id,
            'accountType': _.get(recordToAdd, 'data.type', null),
            'accountName': _.get(recordToAdd, 'data.n_fileas') || _.get(recordToAdd, 'data.name') || i18n._('all')
        };

        if (this.store.getById(id)) {
            this.resetCombobox(combo);
            return false;
        }

        this.store.loadData([record], true);

        this.resetCombobox(combo);

    },

    /**
     * init actions and toolbars
     */
    initActionsAndToolbars: function () {
        this.actionRemove = new Ext.Action({
            text: i18n._('Remove record'),
            disabled: true,
            scope: this,
            handler: this.onRemove,
            iconCls: 'action_deleteContact',
            actionUpdater: function (action, grants, records, isFilterSelect) {
                var adminGrant = window.lodash.get(this.editDialog, 'record.data.account_grants.adminGrant', false),
                    accountId = window.lodash.get(records, '[0].data.accountId', null);

                if (accountId !== null) {
                    action.setDisabled(!adminGrant && (accountId !== this.currentUser.accountId));
                }
            }
        });

        var contextItems = [this.actionRemove];
        this.contextMenu = new Ext.menu.Menu({
            plugins: [{
                ptype: 'ux.itemregistry',
                key: 'Tinebase-MainContextMenu'
            }],
            items: contextItems.concat(this.contextMenuItems)
        });

        // removes temporarily added items
        this.contextMenu.on('hide', function () {
            if (this.contextMenu.hasOwnProperty('tempItems') && this.contextMenu.tempItems.length) {
                Ext.each(this.contextMenu.tempItems, function (item) {
                    this.contextMenu.remove(item.itemId);
                }, this);
            }
            this.contextMenu.tempItems = [];
        }, this);

        if (this.enableBbar) {
            this.bbar = new Ext.Toolbar({
                items: [
                    this.actionRemove
                ].concat(this.contextMenuItems)
            });
        }

        if (this.enableTbar) {
            this.initTbar();
        }

        this.actionUpdater.addActions([this.actionRemove]);
    },

    onRemove: function () {
        var selectedRows = this.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; ++i) {
            if (this.checkGrant(selectedRows[i])) {
                this.store.remove(selectedRows[i]);
            }
        }
    },

    getGroupSearchCombo: function () {
        if (!this.groupCombo) {
            this.groupCombo = new Tine.Tinebase.widgets.form.RecordPickerComboBox({
                hidden: true,
                accountsStore: this.store,
                blurOnSelect: true,
                recordClass: this.groupRecordClass,
                newRecordClass: this.recordClass,
                newRecordDefaults: this.recordDefaults,
                recordPrefix: this.recordPrefix,
                emptyText: this.app.i18n._('Search for groups ...')
            });

            this.groupCombo.onSelect = this.onAddRecordFromCombo.createDelegate(this, [this.groupCombo], true);
        }

        return this.groupCombo;
    }
})
;
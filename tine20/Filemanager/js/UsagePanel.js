/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager');

/**
 * @namespace   Tine.Filemanager
 * @class       Tine.Filemanager.UsagePanel
 * @extends     Ext.Panel
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 *
 * @param       {Object} config
 * @constructor
 */
Tine.Filemanager.UsagePanel = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Array} additionalFields
     * additional fields for the quota form
     */
    additionalFields: null,
    
    layout: 'fit',
    border: false,
    requiredGrant: 'adminGrant',

    initComponent: function() {
        this.app = this.app || Tine.Tinebase.appMgr.get('Filemanager');
        this.title = this.app.i18n._('Usage');

        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('recordUpdate', this.onRecordUpdate, this);
        
        var _ = window.lodash,
            fsConfig = Tine.Tinebase.configManager.get('quota'),
            showQuotaUi = _.get(fsConfig, 'showUI', true),
            bytesRenderer = Tine.Tinebase.common.byteRenderer.createDelegate(this, [2, undefined], 3),
            columns = [
                {header: this.app.i18n._('Size'), width: 150, sortable: true, renderer: bytesRenderer, dataIndex: 'size'}
            ];

        this.hasOwnQuotaCheckbox = new Ext.form.Checkbox({
            requiredGrant: this.requiredGrant,
            hidden: !showQuotaUi,
            disabled: true,
            boxLabel: this.app.i18n._('This folder has own quota'),
            listeners: {scope: this, check: this.onOwnQuotaCheck}
        });
        this.quotaField = Ext.ComponentMgr.create({
            requiredGrant: this.requiredGrant,
            hidden: !showQuotaUi,
            fieldLabel: this.app.i18n.gettext('Quota'),
            emptyText: this.app.i18n.gettext('No quota set (examples: 10 GB, 900 MB)'),
            name: 'quota',
            xtype: 'extuxbytesfield'
        });
        this.effetiveUsageField = Ext.ComponentMgr.create({
            fieldLabel: this.app.i18n.gettext('Current Usage'),
            name: 'effectiveQuota',
            xtype: 'displayfield'
        });

        this.hasOwnQuotaDescription = new Ext.form.Label({
            hidden: !showQuotaUi,
            columnWidth: 1,
            text: this.app.i18n._("The quota applies recursively to files and subfolders. Please note, the effective quota can differ if a parent folder has set a lower quota.")
        });
        
        this.byTypeStore = new Ext.data.ArrayStore({
            fields: [
                {name: 'type'},
                {name: 'size', type: 'int'},
                {name: 'revision_size', type: 'int'}
            ]
        });

        this.byUserStore = new Ext.data.ArrayStore({
            fields: [
                {name: 'user'},
                {name: 'size', type: 'int'},
                {name: 'revision_size', type: 'int'}
            ]
        });

        if (Tine.Tinebase.configManager.get('filesystem.modLogActive', 'Tinebase')) {
            columns.push({header: this.app.i18n._('Revision Size'), width: 150, sortable: true, renderer: bytesRenderer, dataIndex: 'revision_size'});
        }

        this.byTypeGrid = new Ext.grid.GridPanel({
            store: this.byTypeStore,
            flex: 1,
            columns: [
                {id:'type',header: this.app.i18n._('File Type'), width: 160, sortable: true},
            ].concat(columns),
            stripeRows: true,
            autoExpandColumn: 'type',
            title: this.app.i18n._('Usage by File Type'),
            // baseCls: 'ux-arrowcollapse'
        });

        this.byUserGrid = new Ext.grid.GridPanel({
            store: this.byUserStore,
            flex: 1,
            columns: [
                {id:'user',header: this.app.i18n._('User'), width: 160, sortable: true},
            ].concat(columns),
            stripeRows: true,
            autoExpandColumn: 'user',
            title: this.app.i18n._('Usage by User'),
            // baseCls: 'ux-arrowcollapse'
        });
        
        this.quotaPanel = {
            layout: 'form',
            frame: true,
            hideLabels: true,
            width: '100%',
            items: [{
                xtype: 'columnform',
                items: [
                    [this.effetiveUsageField, this.hasOwnQuotaCheckbox, this.quotaField]
                        .concat(this.additionalFields ? this.additionalFields : []),
                    [this.hasOwnQuotaDescription]
                ]
            }]
        };
        
        this.items = [{
            layout: 'vbox',
            align: 'stretch',
            pack: 'start',
            border: false,
            items: [
                this.quotaPanel,
                this.byTypeGrid,
                this.byUserGrid
            ]
        }];
        this.supr().initComponent.call(this);
    },

    afterRender: function() {
        this.supr().afterRender.call(this);
        const editDialog = this.findParentBy(function(c){return !!c.record});
        this.record = editDialog ? editDialog.record : {};
        
        Tine.Filemanager.getFolderUsage(this.record.id, this.onFolderUsageLoad.createDelegate(this));
    },

    onFolderUsageLoad: function(response) {
        var _ = lodash,
            me = this,
            userNameMap = {};

        _.forEach(_.get(response, 'contacts', []), function(contact) {
            userNameMap[contact.id] = contact;
        });

        me.byTypeStore.loadData(_.map(_.get(response, 'type', []), function(o, type) {
            return [type, o.size, o.revision_size];
        }));

        me.byUserStore.loadData(_.map(_.get(response, 'createdBy', []), function(o, user) {
            return [lodash.get(userNameMap, user + '.n_fn' , me.app.i18n._('unknown')), o.size, o.revision_size];
        }));
        const quota = this.record.get('quota');
        if (quota !== null && quota !== '') {
            this.hasOwnQuotaCheckbox.setValue(true);
        }
        this.quotaField.setValue(quota);
    },

    onRecordLoad: function(editDialog, record, ticketFn) {
        const effectiveQuota = _.get(record, 'data.effectiveAndLocalQuota.effectiveQuota', null);
        const effectiveUsage = _.get(record, 'data.effectiveAndLocalQuota.effectiveUsage');
        const hasOwnQuota = record.get('quota') ?? false;
        
        const quotaManageRight = !_.get(record, 'data.account_grants.adminGrant', false)
            || record.get('type') !== 'folder';
        this.hasOwnQuotaCheckbox.setDisabled(quotaManageRight);
        this.hasOwnQuotaCheckbox.setValue(hasOwnQuota);
        this.quotaField.setDisabled(! hasOwnQuota || quotaManageRight);
        
        this.effetiveUsageField.setValue(Tine.widgets.grid.QuotaRenderer(effectiveUsage, effectiveQuota, true));
    },

    onOwnQuotaCheck: function(cb, checked) {
        this.quotaField.setDisabled(! checked);
        if (! checked) {
            this.quotaField.setValue(null);
        }
    },

    onRecordUpdate: function(editDialog, record) {
        const quota = this.quotaField.getValue() ?? null;

        record.set('quota', quota);
    }
});

Ext.reg('Tine.Filemanager.UsagePanel', Tine.Filemanager.UsagePanel);

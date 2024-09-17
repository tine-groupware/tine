/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Felamimail');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.AttachmentUploadGrid
 * @extends     Ext.grid.GridPanel
 *
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 */
Tine.Felamimail.AttachmentUploadGrid = Ext.extend(Tine.widgets.grid.FileUploadGrid, {
    /**
     * Store with all valid attachment types
     */
    attachmentTypeStore: null,
    app: null,

    clicksToEdit: 1,
    currentRecord: null,

    initComponent: function () {
        this.app = this.app || Tine.Tinebase.appMgr.get('Felamimail');

        this.attachmentTypeStore = new Ext.data.JsonStore({
            fields: ['id', 'name'],
            data: this.getAttachmentMethods()
        });

        Tine.Felamimail.AttachmentUploadGrid.superclass.initComponent.call(this);

        this.on('beforeedit', this.onBeforeEdit.createDelegate(this));
        this.store.on('add', this.onStoreAddRecords, this);

        if (this.action_rename) {
            _.set(this, 'action_rename.initialConfig.actionUpdater', this.renameActionUpdater);
        }
    },

    onStoreAddRecords: function(store, rs, idx) {
        _.each(rs, (r) => {
            _.set(r, 'data.attachment_type', 'attachment');
        });
    },

    renameActionUpdater: function(action, grants, records, isFilterSelect, filteredContainers) {
        const isTempfile = !!_.get(records, '[0].data.tempFile');
        const enabled = !!isTempfile;

        action.setDisabled(!enabled);
        action.baseAction.setDisabled(!enabled);
    },

    onBeforeEdit: function (e) {
        var record = e.record;
        this.currentRecord = record;
    },

    getAttachmentMethods: function () {
        var methods = [{
            id: 'attachment',
            name: this.app.i18n._('Attachment')
        }];

        if (!Tine.Tinebase.appMgr.isEnabled('Filemanager')) {
            return methods;
        }

        if(!Tine.Tinebase.appMgr.get('Felamimail').featureEnabled('onlyPwDownloadLink')) {
            methods = methods.concat([{
                    id: 'download_public_fm',
                    name: this.app.i18n._('Filemanager (Download link)')
                }]
            );
        }

        methods = methods.concat([
            {
                id: 'download_protected_fm',
                name: this.app.i18n._('Filemanager (Download link, password)')
            }, {
                id: 'systemlink_fm',
                name: this.app.i18n._('Filemanager (Systemlink)')
            }]
        );

        return methods;
    },

    /**
     * Override columns
     */
    getColumns: function () {
        var me = this;

        var combo = new Ext.form.ComboBox({
            blurOnSelect: true,
            expandOnFocus: true,
            listWidth: 250,
            minListWidth: 250,
            mode: 'local',
            value: 'attachment',
            displayField: 'name',
            valueField: 'id',
            store: me.attachmentTypeStore,
            disableKeyFilter: true,
            queryMode: 'local'
        });

        combo.doQuery = function (q, forceAll, uploadGrid) {
            this.store.clearFilter();

            this.store.filterBy(function (record, id) {
                const isFilemanagerNode = !_.has(uploadGrid.currentRecord, 'data.input'); // check if upload or fm node
                
                // attach fm nodes only with download grant
                if (isFilemanagerNode && !_.get(uploadGrid.currentRecord, 'data.account_grants.downloadGrant', true) && id === 'attachment') {
                    return false;
                }

                // only fm files can be system links
                if (!isFilemanagerNode && id === 'systemlink_fm') {
                    return false
                }

                // if no grants, then its not from fm
                if (!isFilemanagerNode && id.startsWith('download_')) {
                    return false;
                }

                return true;
            }.createDelegate(this, [uploadGrid.currentRecord], true));

            this.onLoad();
        }.createDelegate(combo, [this], true);
        
        const columns = [{
            id: 'attachment_type',
            sortable: true,
            width: 150,
            header: this.app.i18n._('Attachment Type'),
            tooltip: this.app.i18n._('Click icon to change'),
            listeners: {},
            value: 'attachment',
            renderer: function (value) {
                if (!value) {
                    return null;
                }

                var record = me.attachmentTypeStore.getById(value);

                if (!record) {
                    return null;
                }

                return Tine.Tinebase.common.cellEditorHintRenderer(record.get('name'));
            },
            editor: combo
        }, {
            resizable: true,
            id: 'name',
            flex: 1,
            header: i18n._('name'),
            renderer: Ext.ux.PercentRendererWithName
        }, {
            resizable: true,
            id: 'size',
            header: i18n._('size'),
            renderer: Ext.util.Format.fileSize
        }, {
            resizable: true,
            id: 'type',
            width: 150,
            header: i18n._('type')
        }];
        return columns;
    }
});

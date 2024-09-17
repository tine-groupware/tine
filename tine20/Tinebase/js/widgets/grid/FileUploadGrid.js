/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id
 */

/*global Ext, Tine*/

Ext.ns('Tine.widgets.grid');

import '../file/SelectionDialog';

/**
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.FileUploadGrid
 * @extends     Ext.grid.GridPanel
 *
 * <p>FileUpload grid for dialogs</p>
 * <p>
 * </p>
 *
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 *
 * @param       {Object} config
 *
 * @constructor Create a new  Tine.widgets.grid.FileUploadGrid
 */
Tine.widgets.grid.FileUploadGrid = Ext.extend(Ext.grid.EditorGridPanel, {

    /**
     * @cfg filesProperty
     * @type String
     */
    filesProperty: 'files',

    /**
     * @cfg showTopToolbar
     * @type Boolean
     * TODO     think about that -> when we deactivate the top toolbar, we lose the dropzone for files!
     */
    //showTopToolbar: null,

    /**
     * @cfg {Bool} readOnly
     */
    readOnly: false,

    /**
     * config values
     * @private
     */
    header: false,
    border: false,
    deferredRender: false,
    autoExpandColumn: 'name',
    showProgress: true,

    i18nFileString: null,


    fileSelectionDialog: null,

    /**
     * init
     * @private
     */
    initComponent: function () {
        this.addEvents(
            /**
             * @event filesSelected
             * Fired once files where selected from filemanager or from a local source
             */
            'filesSelected'
        );
        
        this.i18nFileString = this.i18nFileString ? this.i18nFileString : i18n._('File');

        this.record = this.record || null;

        // init actions
        this.actionUpdater = new Tine.widgets.ActionUpdater({
            evalGrants: false
        });

        this.initSelectionModel();
        this.initToolbarAndContextMenu();
        this.initStore();
        this.initColumnModel();

        this.actionUpdater.updateActions(this.selModel, [_.get(this, 'record.data')]);

        if (!this.plugins) {
            this.plugins = [];
        }

        this.plugins.push(new Ext.ux.grid.GridViewMenuPlugin({}));

        this.enableHdMenu = false;

        Tine.widgets.grid.FileUploadGrid.superclass.initComponent.call(this);
        
        this.on('rowcontextmenu', function (grid, row, e) {
            e.stopEvent();
            var selModel = grid.getSelectionModel();
            if (!selModel.isSelected(row)) {
                selModel.selectRow(row);
            }
            this.contextMenu.showAt(e.getXY());
        }, this);

        if (!this.record || this.record.id === 0) {
            this.on('celldblclick', function (grid, rowIndex, columnIndex, e) {
                // Don't download if the cell has an editor, just go on with the event
                if (grid.getColumns()[columnIndex] && grid.getColumns()[columnIndex].editor) {
                    return true;
                }

                // In case cell has no editor, just assume a download is intended
                e.stopEvent();
                this.onDownload()
            }, this);
        }

        this.postalSubscriptions = [];
        this.postalSubscriptions.push(postal.subscribe({
            channel: "recordchange",
            topic: 'Tinebase.TempFile.*',
            callback: this.onTempFileChanges.createDelegate(this)
        }));

    },

    onDestroy: function() {
        _.each(this.postalSubscriptions, (subscription) => {subscription.unsubscribe()});
        return Tine.widgets.grid.FileUploadGrid.superclass.onDestroy.call(this);
    },
    
    /**
     * bus notified about record changes
     */
    onTempFileChanges: function(data, e) {
        var existingRecord = _.find(this.store.data.items, (item) => {return _.get(item, 'data.id') === data.id});
        if (existingRecord && e.topic.match(/\.update/)) {
            existingRecord.beginEdit();
            _.each(data, (v, k) => {
                const p = _.find(existingRecord.fields.items, {name: k});
                if (p && /* preserve uniq name */ k !== 'name') {
                    existingRecord.set(k, v);
                }
            });
            _.assign(_.get(existingRecord, 'data.tempFile', {}), data);
            existingRecord.commit();
        } else if (existingRecord && e.topic.match(/\.delete/)) {
            this.store.remove(existingRecord);
        } else {
            const record = new Ext.ux.file.Upload.file(JSON.parse(JSON.stringify(data)), data.id);
            record.set('tempFile', JSON.parse(JSON.stringify(data)));
            this.store.addUnique(record, 'name');
        }
        // NOTE: grid doesn't update selections itself
        this.actionUpdater.updateActions(this.selModel, [_.get(this, 'record.data')]);
    },

    setReadOnly: function (readOnly) {
        this.readOnly = readOnly;
        this.action_add.setDisabled(readOnly);
        this.action_remove.setDisabled(readOnly);
    },

    /**
     * on upload failure
     * @private
     */
    onUploadFail: function (uploader, fileRecord) {

        var dataSize;
        if (fileRecord.html5upload) {
            dataSize = Tine.Tinebase.registry.get('maxPostSize');
        }
        else {
            dataSize = Tine.Tinebase.registry.get('maxFileUploadSize');
        }

        Ext.MessageBox.alert(
            i18n._('Upload Failed'),
            i18n._('Could not upload file. Filesize could be too big. Please notify your Administrator. Max upload size:') + ' ' + parseInt(dataSize, 10) / 1048576 + ' MB'
        );

        this.getStore().remove(fileRecord);
        if (this.loadMask) this.loadMask.hide();
    },

    /**
     * on remove
     * @param {} button
     * @param {} event
     */
    onRemove: async function (button, event) {
    
        var selectedRows = this.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; i += 1) {
            this.store.remove(selectedRows[i]);
            var upload = await Tine.Tinebase.uploadManager.getUpload(selectedRows[i].get('uploadKey'));
            if (upload) {
                upload.setPaused(true);
            }
        }
    },


    /**
     * on pause
     * @param {} button
     * @param {} event
     */
    onPause: async function (button, event) {
    
        var selectedRows = this.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; i++) {
            var upload = await Tine.Tinebase.uploadManager.getUpload(selectedRows[i].get('uploadKey'));
            if (upload) {
                upload.setPaused(true);
            }
        }
        this.getSelectionModel().deselectRange(0, this.getSelectionModel().getCount());
    },


    /**
     * on resume
     * @param {} button
     * @param {} event
     */
    onResume: async function (button, event) {
    
        var selectedRows = this.getSelectionModel().getSelections();
        for (var i = 0; i < selectedRows.length; i++) {
            var upload = await Tine.Tinebase.uploadManager.getUpload(selectedRows[i].get('uploadKey'));
            upload.resumeUpload();
        }
        this.getSelectionModel().deselectRange(0, this.getSelectionModel().getCount());
    },


    /**
     * init toolbar and context menu
     * @private
     */
    initToolbarAndContextMenu: function () {
        var me = this;

        this.action_add = new Ext.Action(this.getAddAction());

        this.action_remove = new Ext.Action({
            text: String.format(i18n._('Remove {0}'), this.i18nFileString),
            iconCls: 'action_remove',
            scope: this,
            disabled: true,
            handler: this.onRemove
        });

        this.action_pause = new Ext.Action({
            text: i18n._('Pause upload'),
            iconCls: 'action_pause',
            scope: this,
            handler: this.onPause,
            actionUpdater: this.isPauseEnabled
        });

        this.action_resume = new Ext.Action({
            text: i18n._('Resume upload'),
            iconCls: 'action_resume',
            scope: this,
            handler: this.onResume,
            actionUpdater: this.isResumeEnabled
        });

        this.action_download = new Ext.Action({
            requiredGrant: 'readGrant',
            allowMultiple: false,
            actionType: 'download',
            text: i18n._('Download'),
            handler: this.onDownload,
            iconCls: 'action_download',
            scope: this,
            disabled: true,
            hidden: !Tine.Tinebase.configManager.get('downloadsAllowed')
        });

        this.action_email = new Ext.Action({
            requiredGrant: 'readGrant',
            allowMultiple: true,
            actionType: 'download',
            text: i18n._('Send by E-Mail'),
            handler: this.onSendByEmail,
            iconCls: 'action_composeEmail',
            scope: this,
            disabled: true,
            hidden: !Tine.Tinebase.configManager.get('downloadsAllowed') ||
                !Tine.Tinebase.common.hasRight('run', 'Felamimail')
        });

        let contextActions = [];
        if (Tine.Tinebase.common.hasRight('run', 'Filemanager')) {
            this.action_rename = Tine.Filemanager.nodeActionsMgr.get('rename', {
                initialApp: this.app,
                sm: this.getSelectionModel(),
                executor: function(record, text) {
                    if (_.isFunction(_.get(record, 'set'))) {
                        record.set('name', text);
                        const tempFile = record.get('tempFile');
                        if (tempFile) {
                            _.set(tempFile, 'name', text);
                        }
                    }
                }
            });
            this.action_preview = Tine.Filemanager.nodeActionsMgr.get('preview', {
                initialApp: this.app,
                sm: this.getSelectionModel()
            });
            contextActions = contextActions.concat([this.action_rename, this.action_preview]);
        }

        this.tbar = new Ext.Toolbar({
            items: [
                this.action_add,
                this.action_remove,
                this.action_download
            ],
            plugins: [{
                ptype: 'ux.itemregistry',
                key: 'Tinebase-FileUploadGrid-Toolbar'
            }],
        });

        contextActions = contextActions.concat([
            this.action_download,
            this.action_email,
            '-',
            this.action_remove,
            this.action_pause,
            this.action_resume
        ]);

        contextActions = contextActions.concat(this.additionalContextActions || []);

        this.contextMenu = new Ext.menu.Menu({
            plugins: [{
                ptype: 'ux.itemregistry',
                key: 'Tinebase-MainContextMenu'
            }, {
                ptype: 'ux.itemregistry',
                key: 'Tinebase-FileUploadGrid-ContextMenu'
            }],
            items: contextActions
        });

        this.actionUpdater.addActions(this.tbar.items);
        this.actionUpdater.addActions(this.contextMenu.items);
    },

    /**
     * init store
     * @private
     */
    initStore: function () {
        this.store = new Ext.data.SimpleStore({
            fields: Ext.ux.file.Upload.file
        });

        this.store.on('add', this.onStoreAdd, this);

        this.loadRecord(this.record);
    },

    onStoreAdd: function (store, records, idx) {
        Ext.each(records, function (attachment) {
            if (attachment.get('url')) {
                // we can't use Ext.data.connection here as we can't control xhr obj. directly :-(
                var me = this,
                    url = attachment.get('url'),
                    name = url.split('/').pop(),
                    xhr = new XMLHttpRequest();

                xhr.open('GET', url, true);
                xhr.responseType = 'blob';

                store.suspendEvents();
                attachment.set('name', name);
                attachment.set('type', name.split('.').pop());
                store.resumeEvents();

                xhr.onprogress = function (e) {
                    var progress = Math.floor(100 * e.loaded / e.total) + '% loaded';
                    console.log(e);
                };


                xhr.onload = function (e) {
//                    attachment.set('type', xhr.response.type);
//                    attachment.set('size', xhr.response.size);
                    
                    const upload = new Ext.ux.file.Upload({
                        file: new File([xhr.response], name),
                        type: xhr.response.type,
                        size: xhr.response.size,
                        id: Tine.Tinebase.uploadManager.generateUploadId()
                    });
                    // work around chrome bug which dosn't take type from blob
                    upload.file.fileType = xhr.response.type;
                    
                    upload.on('uploadfailure', me.onUploadFail, me);
                    upload.on('uploadcomplete', me.onUploadComplete, upload.fileRecord);
                    upload.on('uploadstart', Tine.Tinebase.uploadManager.onUploadStart, me);

                    upload.upload();

                    store.remove(attachment);
                    store.add(upload.fileRecord);
                };

                xhr.send();

            }
        }, this);
    },

    /**
     * download file
     *
     * @param {} button
     * @param {} event
     */
    onDownload: function (button, event) {
        var _ = window.lodash,
            selectedRows = this.getSelectionModel().getSelections(),
            fileRow = selectedRows[0],
            recordId = _.get(this.record, 'id', false),
            tempFile = fileRow.get('tempFile');

        if (recordId !== false && (!recordId || (Ext.isObject(tempFile && tempFile.status !== 'complete')))) {
            Tine.log.debug('Tine.widgets.grid.FileUploadGrid::onDownload - file not yet available for download');
            return;
        }

        Tine.log.debug('Tine.widgets.grid.FileUploadGrid::onDownload - selected file:');
        Tine.log.debug(fileRow);

        if (Ext.isObject(tempFile)) {
            this.downloadTempFile(tempFile.id);
        } else {
            this.downloadRecordAttachment(recordId, fileRow.id)
        }
    },

    onSendByEmail: function (button, event) {
        const selectedRows = this.getSelectionModel().getSelections();
        const attachments = selectedRows.map((record) => { return Object.assign({... record.data}, {attachment_type: 'attachment'}) });
        Tine.Felamimail.MessageEditDialog.openWindow({
            record: new Tine.Felamimail.Model.Message(Object.assign(Tine.Felamimail.Model.Message.getDefaultData(), { attachments }))
        });
    },

    /**
     * returns add action
     *
     * @return {Object} add action config
     */
    getAddAction: function () {
        var me = this;

        return {
            text: String.format(i18n._('Add {0}'), me.i18nFileString),
            iconCls: 'action_attach',
            scope: me,
            plugins: [{
                ptype: 'ux.browseplugin',
                multiple: true,
                enableFileDialog: false,
                dropElSelector: 'div[id=' + this.id + ']',
                handler: (fileSelection) => {
                    me.onFilesSelect(fileSelection.getFileList());
                }
            }],
            handler: me.openDialog
        };
    },

    // Constructs a new dialog and opens it. Better to construct a new one everyt
    openDialog: function () {
        const win = new Tine.Tinebase.widgets.file.SelectionDialog.openWindow({
            windowId: this.id,
            mode: 'source',
            constraint: 'file',
            listeners: {
                scope: this,
                apply: this.onFilesSelect
            }
        });
    },

    /**
     * populate grid store
     *
     * @param {} record
     */
    loadRecord: function (record) {
        if (record && record.get(this.filesProperty)) {
            var files = record.get(this.filesProperty);
            for (var i = 0; i < files.length; i += 1) {
                const existing = this.store.getById(files[i].id);
                if (existing) {
                    _.each(files[i], (value, key) => {
                        existing.set(value, key);
                    });
                } else {
                    var file = new Ext.ux.file.Upload.file(files[i]);
                    file.data.status = 'complete';
                    this.store.addUnique(file, 'name');
                }
            }
        }
    },

    /**
     * init cm
     */
    initColumnModel: function () {
        this.cm = new Ext.grid.ColumnModel(this.getColumns());
    },

    getColumns: function () {
        const columns = [
            { resizable: true, id: 'name', width: 300, header: i18n._('name'), renderer: Ext.ux.PercentRendererWithName },
            { resizable: true, id: 'size', header: i18n._('size'), renderer: Ext.util.Format.fileSize },
            { resizable: true, id: 'type', width: 70, header: i18n._('type') }
        ];
        return columns;
    },

    /**
     * init sel model
     * @private
     */
    initSelectionModel: function () {
        this.selModel = new Ext.grid.RowSelectionModel({multiSelect: true});

        this.selModel.on('selectionchange', function (selModel) {
            var rowCount = selModel.getCount();
            this.action_remove.setDisabled(this.readOnly || rowCount === 0);
            this.actionUpdater.updateActions(selModel, [_.get(this, 'record.data')]);

        }, this);
    },

    /**
     * upload new file and add to store
     */
    onFilesSelect: function (fileList) {
        if (_.get(fileList, '[0].type') === 'fm_node') {
            this.onFileSelectFromFilemanager(fileList);
        } else {
             _.each(fileList, (file) => {
                const upload =  new Ext.ux.file.Upload({
                    file: file,
                    id: Tine.Tinebase.uploadManager.generateUploadId(),
                    isFolder: false
                });
                
                upload.on('uploadfailure', this.onUploadFail, this);
                upload.on('uploadcomplete', this.onUploadComplete, upload.fileRecord);
                upload.on('uploadstart', Tine.Tinebase.uploadManager.onUploadStart, this);
                upload.on('uploadinitial', this.onUploadInitial, this);
                upload.upload();
            });
        }

        this.fireEvent('filesSelected');
    },

    /**
     * Add one or more files from filemanager
     *
     * @param nodes
     */
    onFileSelectFromFilemanager: function (fileLocations) {
        var me = this;

        _.each(fileLocations, async (fileLocation) => {
            const fileRecord = new Ext.ux.file.Upload.file({
                name: _.get(fileLocation, 'node_id.name'),
                size: _.get(fileLocation, 'node_id.size'),
                type: _.get(fileLocation, 'node_id.contenttype'),
                id: _.get(fileLocation, 'node_id.id'),
                status: 'uploading',
                progress: 80
            });
            this.store.addUnique(fileRecord, 'name');

            Tine.Tinebase.createTempFile(fileLocation).then((tempFileData) => {
                fileRecord.beginEdit();
                fileRecord.set('id', _.get(fileRecord, 'data.id', tempFileData.id));
                fileRecord.set('tempFile', tempFileData);
                fileRecord.set('status', 'complete');
                fileRecord.set('progress', 100);
                fileRecord.commit();
            });
        });
    },

    onUploadComplete: function (upload, fileRecord) {
        fileRecord.beginEdit();
        fileRecord.set('status', 'complete');
        fileRecord.set('progress', 100);
        try {
            fileRecord.commit(false);
        } catch (e) {
            console.log(e);
        }
        Tine.Tinebase.uploadManager.onUploadComplete();
    },

    onUploadInitial: function (upload, fileRecord) {
        if (fileRecord.get('status') !== 'failure') {
            this.store.addUnique(fileRecord, 'name');
        }
    },

    /**
     * returns true if files are uploading atm
     *
     * @return {Boolean}
     */
    isUploading: function () {
        var uploadingFiles = this.store.query('status', 'uploading');
        return (uploadingFiles.getCount() > 0);
    },

    isPauseEnabled: function (action, grants, records) {

        for (var i = 0; i < records.length; i++) {
            if (records[i].get('type') === 'folder') {
                action.hide();
                return;
            }
        }

        for (var i = 0; i < records.length; i++) {
            if (!records[i].get('status') || (records[i].get('type ') !== 'folder' && records[i].get('status') !== 'paused'
                    && records[i].get('status') !== 'uploading' && records[i].get('status') !== 'pending')) {
                action.hide();
                return;
            }
        }

        action.show();

        for (var i = 0; i < records.length; i++) {
            if (records[i].get('status')) {
                action.setDisabled(false);
            }
            else {
                action.setDisabled(true);
            }
            if (records[i].get('status') && records[i].get('status') !== 'uploading') {
                action.setDisabled(true);
            }

        }
    },

    isResumeEnabled: function (action, grants, records) {
        for (var i = 0; i < records.length; i++) {
            if (records[i].get('type') === 'folder') {
                action.hide();
                return;
            }
        }

        for (var i = 0; i < records.length; i++) {
            if (!records[i].get('status') || (records[i].get('type ') !== 'folder' && records[i].get('status') !== 'uploading'
                    && records[i].get('status') !== 'paused' && records[i].get('status') !== 'pending')) {
                action.hide();
                return;
            }
        }

        action.show();

        for (var i = 0; i < records.length; i++) {
            if (records[i].get('status')) {
                action.setDisabled(false);
            }
            else {
                action.setDisabled(true);
            }
            if (records[i].get('status') && records[i].get('status') !== 'paused') {
                action.setDisabled(true);
            }

        }
    },

    downloadRecordAttachment: function (recordId, id) {
        new Ext.ux.file.Download({
            params: {
                method: 'Tinebase.downloadRecordAttachment',
                requestType: 'HTTP',
                nodeId: id,
                recordId: recordId,
                modelName: this.app.name + '_Model_' + this.editDialog.modelName
            }
        }).start();
    },

    downloadTempFile: function (id) {
        new Ext.ux.file.Download({
            params: {
                method: 'Tinebase.downloadTempfile',
                requestType: 'HTTP',
                tmpfileId: id
            }
        }).start();
    }
});

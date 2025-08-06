/*
 * Tine 2.0
 *
 * @package     EventManager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.EventManager');

Tine.EventManager.Selections_FileEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    initComponent: function () {
        this.app = this.app || Tine.Tinebase.appMgr.get('EventManager');
        this.editDialog = this;
        Tine.EventManager.Selections_FileEditDialog.superclass.initComponent.call(this);
    },

    getFormItems: function () {
        return {
            xtype: 'tabpanel',
            border: false,
            plain: true,
            activeTab: 0,
            items: [this.getFileUploadGrid()]
        }
    },

    getFileUploadGrid: function () {
        if (this.gridPanel) {
            return this.gridPanel;
        }

        this.gridPanel = new Tine.EventManager.FileOptionUploadGrid({
            editDialog: this,
            filesProperty: 'node_id',
            readOnly: false,
            header: false,
            border: false,
            deferredRender: false,
            autoExpandColumn: 'name',
            showProgress: true,
            i18nFileString: null,
            fileSelectionDialog: null,
        });

        this.gridPanel.on('filesSelected', this.onFilesSelected, this);

        return this.gridPanel;
    },

    onFilesSelected: function () {
        const uploadedFiles = Array.from(this.gridPanel.store.data.items);
        if (uploadedFiles.length > 0) {
            uploadedFiles.forEach((uploadedFile) => {
                if (uploadedFile.data.input) {
                    this.waitForUploadComplete(uploadedFile, (fileRecord) => {
                        this.linkFileToRecord(fileRecord);
                    });
                } else {
                    this.deleteOldUploadedFile(uploadedFile);
                }
            });
        }
    },

    waitForUploadComplete: function (fileRecord, callback) {
        let checkStatus = () => {
            if (fileRecord.get('status') === 'complete') {
                callback(fileRecord);
            } else if (fileRecord.get('status') === 'failure') {
                console.error('File upload failed');
            } else {
                setTimeout(checkStatus, 500);
            }
        };
        checkStatus();
    },

    linkFileToRecord: function (fileRecord) {
        let tempFile = fileRecord.get('tempFile');
        if (tempFile && tempFile.id) {
            if (this.record) {
                this.record.set('node_id', tempFile.id);
                this.record.set('file_name', tempFile.name);
                this.record.set('file_size', tempFile.size);
                this.record.set('file_type', tempFile.type);
                // Update the form field if it exists
                let nodeIdField = this.getForm().findField('node_id');
                let fileName = this.getForm().findField('file_name');
                let fileSize = this.getForm().findField('file_size');
                let fileType = this.getForm().findField('file_type');
                if (nodeIdField) {
                    nodeIdField.setValue(tempFile.id);
                    fileName.setValue(tempFile.name);
                    fileSize.setValue(tempFile.size);
                    fileType.setValue(tempFile.type);
                }
            }
        }
    },

    onApplyChanges: function (button, event, closeWindow) {
        let uploadedFiles = this.gridPanel.store.data.items;
        if (uploadedFiles.length > 0 && !this.record.get('node_id')) {
            let completedFiles = uploadedFiles.filter(file => file.get('status') === 'complete');
            if (completedFiles.length > 0) {
                this.linkFileToRecord(completedFiles[0]);
            }
        }
        Tine.EventManager.FileOptionEditDialog.superclass.onApplyChanges.call(this, button, event, closeWindow);
    },

    deleteOldUploadedFile: function (uploadedFile) {
        this.gridPanel.store.remove(uploadedFile);
    }
});

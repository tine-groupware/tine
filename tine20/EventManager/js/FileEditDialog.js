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
        this.allowedFilesTypes = ['.pdf', '.doc', '.docx', '.png', '.jpeg', '.txt', '.html', '.htm', '.jpg', '.csv', '.xlsx', '.xls'];
        Tine.EventManager.Selections_FileEditDialog.superclass.initComponent.call(this);
    },

    getFormItems: function () {
        const fieldManager = _.bind(
            Tine.widgets.form.FieldManager.get,
            Tine.widgets.form.FieldManager,
            this.appName,
            this.modelName,
            _,
            Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG
        );
        return {
            xtype: 'tabpanel',
            border: false,
            plain: true,
            activeTab: 0,
            items: [{
                xtype: 'fieldset',
                title: this.app.i18n._('File Acknowledgement'),
                items: [
                    fieldManager('file_acknowledgement'),
                ]
            },{
                xtype: 'fieldset',
                title: this.app.i18n._('File Upload'),
                layout: 'fit',
                items: [
                    this.getFileUploadGrid()
                ]
            }
            ]
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
        this.gridPanel.on('fileRemoved', this.onFileRemoved, this);

        return this.gridPanel;
    },

    validateFiles: function (uploadedFiles) {
        if (uploadedFiles.length > 1) {
            Ext.MessageBox.show({
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.WARNING,
                title: this.app.i18n._('Multiple files not allowed'),
                msg: this.app.i18n._('Please select only one file.')
            });
            return false;
        }

        // Validate file type for each file
        for (let i = 0; i < uploadedFiles.length; i++) {
            let uploadedFile = uploadedFiles[i];
            let fileName = uploadedFile.get('name') || uploadedFile.data.name;

            if (fileName) {
                let uploadedFileType = '.' + fileName.split('.').pop().toLowerCase();

                if (this.allowedFilesTypes.indexOf(uploadedFileType) === -1) {
                    Tine.log.info(this.app.i18n._('File type not allowed: ') + uploadedFileType);
                    Ext.MessageBox.show({
                        buttons: Ext.Msg.OK,
                        icon: Ext.MessageBox.WARNING,
                        title: this.app.i18n._('File type not allowed'),
                        msg: this.app.i18n._('The type of the file "') + fileName + this.app.i18n._('" is not allowed. Please select another file.')
                    });
                    return false;
                }
            }
        }

        return true;
    },

    onFilesSelected: function () {
        const uploadedFiles = Array.from(this.gridPanel.store.data.items);
        if (!this.validateFiles(uploadedFiles)) {
            uploadedFiles.forEach((invalidFile) => {
                if (invalidFile.phantom) {
                    this.deleteOldUploadedFile(invalidFile);
                }
            });
            return;
        }
    },

    onFileRemoved: function (removedFile) {
        this.clearFileDataFromRecord();
    },

    clearFileDataFromRecord: function () {
        if (this.record) {
            this.record.set('node_id', null);
            this.record.set('file_name', null);
            this.record.set('file_size', null);
            this.record.set('file_type', null);
        }
    },

    linkFileToRecord: function (fileRecord) {
        let tempFile = fileRecord.get('tempFile');
        if (tempFile && tempFile.id) {
            if (this.record) {
                this.record.set('node_id', tempFile.id);
                this.record.set('file_name', tempFile.name);
                this.record.set('file_size', tempFile.size);
                this.record.set('file_type', tempFile.type);
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
        if (this.gridPanel.store.data.items.length === 0) {
            this.clearFileDataFromRecord();
        }
    }
});

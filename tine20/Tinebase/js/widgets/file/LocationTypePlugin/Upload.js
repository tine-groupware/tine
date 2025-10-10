/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

require('./Abstract');
require('../../form/FileSelectionArea');

Ext.ns('Tine.Tinebase.widgets.file.LocationTypePlugin');

Tine.Tinebase.widgets.file.LocationTypePlugin.Upload = function(config) {
    Ext.apply(this, config);

    this.name = i18n._('My Device');
};

Ext.extend(Tine.Tinebase.widgets.file.LocationTypePlugin.Upload, Tine.Tinebase.widgets.file.LocationTypePlugin.Abstract, {
    locationType: 'upload',
    iconCls: 'action_upload',
    
    /**
     * @cfg {String} uploadMode select|upload
     */
    uploadMode: 'select',

    getSelectionDialogArea: async function(area, cmp) {
        if (! this.selectionDialogInitialised) {
            this.cmp = cmp;
            const allowedTypes = cmp.allowedTypes || cmp.constraint && ['file', 'folder'].indexOf(cmp.constraint) < 0 && ! _.isFunction(cmp.constraint) ? _.compact(String(cmp.constraint).replace(/^\/\(?/, '').replace(/\)?\$\//, '').split('|')) : null

            this.pluginPanel = new Tine.widgets.form.FileSelectionArea(Ext.apply({
                text: i18n._('Select or drop file to upload'),
                multiple: cmp.allowMultiple,
                allowedTypes
            }, _.get(this, 'cmp.pluginConfig.' + this.plugin, {})));

            this.pluginPanel.on('fileSelected', this.onFilesSelected, this);

            this.selectionDialogInitialised = true;
        }
        return _.get(this, area);
    },
    
    getFileList: async function() {
        if (this.uploadMode === 'select') {
            return this.pluginPanel.fileList;
        } else {
            await Ext.MessageBox.show({
                icon: Ext.MessageBox.INFO_WAIT,
                title: i18n._('Please wait'),
                msg: i18n._('Uploading...'),
                width:500,
                progress:true,
                closable:false,
                animEl: this.pluginPanel.getEl()
            })

            const uploads = _.map(this.pluginPanel.fileList, file => {
                const upload = new Ext.ux.file.Upload({file})
                upload.on('uploadprogress', e => {
                    const total = _.sum(_.map(uploads, 'fileSize'))
                    const uploded = _.sum(_.map(uploads, f => f.uploadProgress/100 * f.fileSize))
                    Ext.MessageBox.updateProgress(uploded/total);
                })
                return upload
            })

            return Promise.all(_.map(uploads, async upload => {
                upload.upload()
                await upload.promise
                const tempFile = upload.fileRecord.get('tempFile')
                return {
                    fileLocation: {
                        model_name: 'Tinebase_Model_FileLocation_TempFile',
                        location: Object.assign({temp_file_id: tempFile.id}, tempFile)
                    }
                }
            }))
        }
    },

    onFilesSelected: function(fileList, event) {
        this.cmp.onButtonApply();
    },

    manageButtonApply: function(buttonApply) {
        if (this.uploadMode === 'select') {
            buttonApply.setDisabled(true);
        }
    }
});

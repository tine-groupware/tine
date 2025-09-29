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

Tine.EventManager.Selections_FileUploadGrid = Ext.extend(Tine.widgets.grid.FileUploadGrid, {

    loadRecord: function (record) {
        if (record && record.get(this.filesProperty)) {
            const node_id = record.get(this.filesProperty);
            let fileData = {
                tempFile : node_id,
                name : record.data.file_name,
                path : "",
                size : record.data.file_size,
                type : record.data.file_type,
                id : node_id}
            const file = new Ext.ux.file.Upload.file(fileData, node_id);
            file.data.status = 'complete';
            this.store.addUnique(file, 'name');
        }
    },

    onRemove: function () {
        let sm = this.getSelectionModel();
        let records = sm.getSelections();

        if (records.length > 0) {
            records.forEach(record => {
                this.fireEvent('fileRemoved', record);
            });
        }
        Tine.EventManager.FileUploadGrid.superclass.onRemove.call(this);
    },
});
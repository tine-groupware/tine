/*
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const WebDAVCollectionPicker = Ext.extend(Ext.form.ComboBox, {


    collectionName: 'Collection',


    displayField:'name',
    valueField:'id',
    forceSelection: true,
    mode: 'local',
    triggerAction: 'all',
    selectOnFocus:true,
    filterAnyMatch: true,

    /**
     * @private
     */
    initComponent: function() {
        this.store = new Ext.data.JsonStore({
            root: 'results',
            id: 'shortName',
            fields: ['uri', 'name', 'color', 'type', 'acl'],
            remoteSort: false,
            sortInfo: {
                field: 'name',
                direction: 'ASC'
            }
        });

        this.emptyText = window.formatMessage('Select a { collectionName }...', { collectionName: this.collectionName });

        WebDAVCollectionPicker.superclass.initComponent.call(this);
    },

    doQuery: async function(q, forceAll) {
        try {
            const editDialog = this.findParentBy(function (c) {
                return c instanceof Tine.widgets.dialog.EditDialog
            })
            const cloudAccount = editDialog.getForm().findField('cloud_account_id').selectedRecord?.id

            const result = await Tine.Tinebase.getCloudAccountWebDAVCollections(cloudAccount)
            console.error(result)
        } catch(e) {

        } finally {

        }
    }
})

Ext.reg('CloudAccount.WebDAVCollectionPicker', WebDAVCollectionPicker);

export default WebDAVCollectionPicker
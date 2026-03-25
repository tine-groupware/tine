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
    type: 'VEVENT',

    displayField: 'name',
    valueField: 'uri',
    forceSelection: true,
    mode: 'local',
    triggerAction: 'all',
    selectOnFocus: true,
    filterAnyMatch: true,
    tpl: '<tpl for="."><div class="x-combo-list-item">' +
        '<span style="color:{color};" class="dark-reverse">&nbsp;◉&nbsp;</span>' +
        '<span>{name}</span>' +
        // '<span>{acl}</span>' +
    '</div></tpl>',

    /**
     * @private
     */
    initComponent: function () {
        this.store = new Ext.data.JsonStore({
            root: 'results',
            id: 'shortName',
            fields: ['acl', 'color', 'name', 'owner_email', 'owner_principal', 'type', 'uri'],
            remoteSort: false,
            sortInfo: {
                field: 'name',
                direction: 'ASC'
            }
        });

        this.emptyText = window.formatMessage('Select a { collectionName }...', {collectionName: this.collectionName});

        WebDAVCollectionPicker.superclass.initComponent.call(this);
    },

    doQuery: async function (q, forceAll) {
        try {
            const editDialog = this.findParentBy(function (c) {
                return c instanceof Tine.widgets.dialog.EditDialog
            })
            const cloudAccount = editDialog.getForm().findField('cloud_account_id').selectedRecord?.id

            this.onBeforeLoad()
            this.expand()

            const result = await Tine.Tinebase.getCloudAccountWebDAVCollections(cloudAccount)

            this.store.loadData(_.filter(result, {type: this.type}))

        } catch (e) {

        } finally {

        }
    }
})

Ext.reg('CloudAccount.WebDAVCollectionPicker', WebDAVCollectionPicker);

export default WebDAVCollectionPicker
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * @TODO rework - not longer appropriate with new dispatchConfig
 * additional action for attachmentGrids to in/exclude attachments to dispatchments
 */
Promise.all([Tine.Tinebase.appMgr.isInitialised('Sales'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Sales')
    const config = {
        text: app.i18n._('Show Dispatch History'),
        iconCls: `action_dispatch_history`,
        hidden: true,
        initComponent(){
            this.on('afterrender', (cmp, ownerCt) => {
                this.editDialog = cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog})
                this.recordClass = this.editDialog.recordClass
                this.documentType = _.get(this.recordClass.getPhpClassName().match(/Sales_Model_Document_(.+)/), '[1]')
                this.setVisible(!!this.documentType)

                const ap = this.editDialog.attachmentsPanel
                ap?.actionUpdater.updateActions(ap.selModel, [_.get(ap, 'record.data')])
            });
            this.supr().initComponent.call(this)
        },
        actionUpdater(cmp, grants, records, isFilterSelect, filteredContainers) {
            if (!this.documentType) return

            let enabled = records.length === 1
            enabled = enabled && !!_.get(_.find(this.editDialog.record.get('attached_documents'), { node_id: records[0].id }), 'dispatch_history.length', 0)
            cmp.setDisabled(!enabled)
        },
        handler: async function(cmp) {
            const attachment = this.initialConfig.selections[0]
            const attached_document = _.find(this.editDialog.record.get('attached_documents'), { node_id: attachment.id })
            Tine.Sales.Document_AttachedDocumentEditDialog.openWindow({
                mode: 'local',
                readOnly: true,
                record: attached_document
            })
        }
    }

    Ext.ux.ItemRegistry.registerItem('Tinebase-FileUploadGrid-Toolbar', Ext.extend(Ext.Button, config), 2)
    Ext.ux.ItemRegistry.registerItem('Tinebase-FileUploadGrid-ContextMenu', Ext.extend(Ext.menu.Item, config), 2)

})
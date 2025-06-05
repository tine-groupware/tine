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
        text: app.i18n._('Include to Dispatch Document'),
        iconCls: `action_dispatch_document`,
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

            let enabled = records.length

            // toDelete -> not attached // toUpdate -> is attached
            const migration = cmp.migration = _.map(records, 'id').getMigration(_.map(this.editDialog.record.get('attached_documents'), 'node_id'))

            // don't allow to exclude already dispatched attachments as we loose history otherwise
            enabled = _.reduce(migration.toUpdate, (accu, node_id) => {
                return accu && !_.get(_.find(this.editDialog.record.get('attached_documents'), { node_id}), 'dispatch_history.length', 0)
            }, enabled)

            // can't cope with phantom attachments
            enabled = _.reduce(records, (accu, attachment) => {
                return accu && !attachment.get('tempFile')
            }, enabled)

            const operation = cmp.operation = migration.toUpdate.length === records.length ? 'exclude' : (migration.toDelete.length === records.length ? 'include' : null)
            this.setText(operation === 'exclude' ? app.i18n._('Exclude from Dispatch Document') : app.i18n._('Include to Dispatch Document'))
            enabled = enabled && !!operation

            cmp.setDisabled(!enabled)
        },
        handler: async function(cmp) {
            const attached_documents = this.editDialog.record.get('attached_documents') || []
            const records = cmp.initialConfig.selections
            _.forEach(records, (record) => {
                if (cmp.operation === 'exclude') {
                    _.remove(this.editDialog.record.get('attached_documents'), {node_id: record.id})
                } else {
                    // note: setting paperslip or edocument to supporting_document might lead to trubble... let's hope for edicated users
                    attached_documents.push({id: Tine.Tinebase.data.Record.generateUID(), type: 'supporting_document', node_id: record.id, created_for_seq: 0})
                }
            })
            this.editDialog.record.set('attached_documents', attached_documents)

            const ap = this.editDialog.attachmentsPanel
            ap.actionUpdater.updateActions(ap.selModel, [_.get(ap, 'record.data')])
        }
    }

    Ext.ux.ItemRegistry.registerItem('Tinebase-FileUploadGrid-Toolbar', Ext.extend(Ext.Button, config), 2)
    Ext.ux.ItemRegistry.registerItem('Tinebase-FileUploadGrid-ContextMenu', Ext.extend(Ext.menu.Item, config), 2)

})
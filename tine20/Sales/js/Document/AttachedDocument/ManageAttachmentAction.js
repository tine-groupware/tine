/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * supporting documents in the attachments grid of sales documents: context menu action to mark / unmark attachments
 * as supporting documents (dispatched with the document if the dispatch config contains the document type supporting
 * document) and a pill in the name column of marked attachments
 *
 * NOTE: only supporting documents are managed here - paperslips and edocuments are managed by the server, it also
 *       ignores changes of them sent by the client (Sales_Frontend_Json::_sanitizeAttachedDocumentsFromJson)
 * NOTE: new uploads have no node yet - they are marked with the id of their temp file, the server replaces it with
 *       the id of the node it creates (Sales_Frontend_Json::_save)
 */
const TYPE_SUPPORTING_DOCUMENT = 'supportingDoc'

/**
 * @param {Tine.widgets.dialog.EditDialog} editDialog
 * @param {Ext.data.Record} attachment row of the attachments grid
 * @return {String|null} node id of a stored attachment, temp file id of a new upload, null while uploading
 */
const getNodeKey = (editDialog, attachment) => {
    if (attachment.get('status') && attachment.get('status') !== 'complete') return null
    const tempFileId = attachment.get('tempFile')?.id
    if (!tempFileId) return attachment.id || null
    // saved meanwhile (apply changes), but the grid row is still the upload
    const stored = _.find(editDialog.record.get('attachments'), (a) => !a.tempFile && a.name === attachment.get('name'))
    return stored?.id || tempFileId
}

/**
 * shows a pill after the name of attachments which are marked as supporting documents
 *
 * @param {Tine.widgets.dialog.EditDialog} editDialog of a sales document
 */
export const decorateAttachmentsGrid = (editDialog) => {
    const grid = editDialog.attachmentsPanel
    const cm = grid?.getColumnModel?.()
    const nameColumn = cm?.getColumnById('name')
    if (!nameColumn || !editDialog.recordClass.hasField('attached_documents')) return

    const renderer = nameColumn.renderer
    nameColumn.renderer = function (value, metadata, record) {
        const html = renderer.apply(this, arguments)
        const nodeKey = getNodeKey(editDialog, record)
        if (!nodeKey || !_.find(editDialog.record.get('attached_documents'), { node_id: nodeKey, type: TYPE_SUPPORTING_DOCUMENT })) {
            return html
        }
        const app = Tine.Tinebase.appMgr.get('Sales')
        const qtip = Ext.util.Format.htmlEncode(app.i18n._('Dispatched with the document if its dispatch configuration contains supporting documents.'))
        return `${html}<span class="sales-supporting-document-pill" ext:qtip="${qtip}">${Ext.util.Format.htmlEncode(app.i18n._('Supporting Document'))}</span>`
    }

    // attached_documents may change without changes of the attachments (e.g. apply changes)
    editDialog.on('load', () => grid.rendered && grid.getView().refresh())
}

Promise.all([Tine.Tinebase.appMgr.isInitialised('Sales'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Sales')

    const getEditDialog = (cmp) => {
        const grid = cmp.parentMenu?.openerCt || cmp.ownerCt?.openerCt
        const editDialog = grid?.editDialog
        return editDialog?.recordClass?.hasField?.('attached_documents') ? editDialog : null
    }

    const config = {
        text: app.i18n._('Mark as Supporting Document'),
        iconCls: 'action_dispatch_document',
        hidden: true,
        actionUpdater(cmp, grants, records) {
            const editDialog = getEditDialog(cmp)
            cmp.setVisible(!!editDialog)
            if (!editDialog) return

            const attachedDocuments = editDialog.record.get('attached_documents') || []
            const types = _.map(records, (attachment) => {
                const nodeKey = getNodeKey(editDialog, attachment)
                return nodeKey ? _.get(_.find(attachedDocuments, { node_id: nodeKey }), 'type', '') : null
            })

            // paperslips / edocuments and running uploads can't be changed, mixed selections are ambiguous
            const operation = cmp.operation = !records.length || _.includes(types, null) ? null :
                (_.every(types, (type) => type === TYPE_SUPPORTING_DOCUMENT) ? 'unmark' :
                    (_.every(types, (type) => type === '') ? 'mark' : null))

            cmp.setText(operation === 'unmark' ? app.i18n._('Unmark as Supporting Document') : app.i18n._('Mark as Supporting Document'))
            cmp.setDisabled(!operation)
        },
        handler(cmp) {
            const editDialog = getEditDialog(cmp)
            if (!editDialog || !cmp.operation) return

            const nodeIds = _.map(cmp.initialConfig.selections, (attachment) => getNodeKey(editDialog, attachment))
            let attachedDocuments = _.cloneDeep(editDialog.record.get('attached_documents') || [])
            if (cmp.operation === 'unmark') {
                attachedDocuments = _.reject(attachedDocuments, (ad) => ad.type === TYPE_SUPPORTING_DOCUMENT && _.includes(nodeIds, ad.node_id))
            } else {
                attachedDocuments = attachedDocuments.concat(_.map(nodeIds, (node_id) => ({
                    id: Tine.Tinebase.data.Record.generateUID(),
                    type: TYPE_SUPPORTING_DOCUMENT,
                    node_id,
                    created_for_seq: editDialog.record.get('document_seq') || 0
                })))
            }
            editDialog.record.set('attached_documents', attachedDocuments)

            const grid = cmp.parentMenu?.openerCt || cmp.ownerCt?.openerCt
            grid?.getView?.().refresh()
            grid?.actionUpdater?.updateActions(grid.getSelectionModel(), [_.get(grid, 'record.data')])
        }
    }

    Ext.ux.ItemRegistry.registerItem('Tinebase-FileUploadGrid-ContextMenu', Ext.extend(Ext.menu.Item, config), 2)
})

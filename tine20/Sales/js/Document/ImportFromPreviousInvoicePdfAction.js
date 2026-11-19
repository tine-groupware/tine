/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import AbstractAction from './AbstractAction'

/**
 * invoice dialog action: reads the positions of an (auto) invoice again from the pdf of the previous invoice of the
 * contract (Sales.importFromPreviousInvoicePdf) - the positions are replaced and the invoice is saved
 *
 * Only for unbooked invoices whose positions were read from that pdf (xprop positionsFromPreviousInvoicePdf, not
 * taken into copies) and if the server can parse such documents (registry canParseUnstructuredDocuments).
 */
Promise.all([Tine.Tinebase.appMgr.isInitialised('Sales'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Sales')
    if (!app.getRegistry().get('canParseUnstructuredDocuments')) return

    const XPROP = 'positionsFromPreviousInvoicePdf'

    const action = new AbstractAction({
        documentType: 'Invoice',
        text: app.i18n._('Read Positions Again'),
        tooltip: app.i18n._('Reads the positions again from the PDF of the previous invoice of the contract.'),
        iconCls: 'SalesInvoiceFromFile',
        hidden: true,
        actionUpdater(action, grants, records) {
            const record = records.length === 1 ? records[0] : null
            const booked = record && _.find(action.statusDef.records, { id: record.get(action.statusFieldName) })?.booked
            const visible = !!record && !booked && !!record.get('xprops')?.[XPROP]
            // NOTE: action is the component (button) here, see AbstractAction
            action.setVisible(visible)
            action.baseAction?.setHidden(!visible)
        },
        async handler(cmp) {
            AbstractAction.prototype.handler.apply(this, arguments)
            const record = this.unbooked?.[0]
            if (!record || !this.editDialog) return

            const buttonId = await Ext.MessageBox.show({
                title: app.i18n._('Read Positions Again?'),
                msg: app.i18n._('The positions are read again from the PDF of the previous invoice and replace the current ones. Your own changes to this invoice are lost.'),
                buttons: Ext.MessageBox.YESNO,
                icon: Ext.MessageBox.QUESTION_WARN,
                stateId: 'sales-document-invoice-import-from-previous-invoice-pdf'
            })
            // NOTE: a skipped message box (doNotShowAgain) resolves with 'yes'
            if (buttonId !== 'yes') return

            Ext.MessageBox.show({
                title: i18n._('Please wait'),
                msg: app.i18n._('Reading the invoice from the file. This may take a few minutes...'),
                icon: Ext.MessageBox.INFO_WAIT,
                width: 500,
                wait: true,
                closable: false
            })
            try {
                const updated = Tine.Tinebase.data.Record.setFromJson(await Tine.Sales.importFromPreviousInvoicePdf(record.id), this.recordClass)
                Ext.MessageBox.hide()
                await this.editDialog.loadRecord(updated)
            } catch (e) {
                Ext.MessageBox.hide()
                await Ext.MessageBox.show({
                    title: app.i18n._('Reading the invoice from the file failed'),
                    msg: Ext.util.Format.htmlEncode(e.message || e.data?.message || ''),
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.MessageBox.ERROR_MILD
                })
            }
        }
    })

    const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top' }
    Ext.ux.ItemRegistry.registerItem('Sales-Document_Invoice-editDialog-Toolbar', Ext.apply({ xtype: 'button', overrides: medBtnStyle }, action), 60)
})

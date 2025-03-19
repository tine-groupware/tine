/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import DispatchHistoryGridPanel from "./DispatchHistoryGridPanel"

const DispatchHistoryDialog = Ext.extend(Tine.Tinebase.dialog.Dialog, {
    record: null,
    editDialog: null,

    initComponent() {
        this.app = Tine.Tinebase.appMgr.get('Sales')
        this.items = new DispatchHistoryGridPanel({
            record: this.record,
            editDialog: this.editDialog
        })
        return this.supr().initComponent.call(this)
    }
})

Tine.Sales.Document_DispatchHistoryDialog = DispatchHistoryDialog

DispatchHistoryDialog.openWindow = (config) => {
    return Tine.WindowFactory.getWindow({
        width: 600,
        height: 600,
        name: 'Sales-Dispatch-Dialog-' + config.record.id,
        contentPanelConstructor: 'Tine.Sales.Document_DispatchHistoryDialog',
        contentPanelConstructorConfig: config
    })
}

export default DispatchHistoryDialog
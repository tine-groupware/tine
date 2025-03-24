/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import AbstractAction from "./AbstractAction";
Promise.all([Tine.Tinebase.appMgr.isInitialised('Sales'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Sales')

    const getAction = (type, config) => {
        config.recordClass = Tine.Tinebase.data.RecordMgr.get(`Sales.Document_${type}`)
        config.recordName = config.recordClass.getRecordName()

        return new AbstractAction(Object.assign({
            documentType: type,
            text: app.formatMessage('Copy { recordName }', config),
            iconCls: `action_editcopy`,
            actionUpdater(action, grants, records, isFilterSelect, filteredContainers) {
                let enabled = records.length === 1
                action.setDisabled(!enabled)
                action.baseAction.setDisabled(!enabled) // WTF?
            },
            async handler(cmp) {
                let record = this.initialConfig.selections[0]
                const editDialogClass = Tine.widgets.dialog.EditDialog.getConstructor(this.recordClass);
                editDialogClass.openWindow(Object.assign({
                    openerCt: this,
                    contentPanelConstructorInterceptor: async (config) => {
                        const api = Tine.Sales[`copy${this.recordClass.getMeta('modelName')}`]
                        const copiedData = await api(record.id, false)
                        const copiedRecord = Tine.Tinebase.data.Record.setFromJson(copiedData, this.recordClass)
                        copiedRecord.phantom = true
                        Object.assign(config, {
                            record: copiedRecord.getData()
                        })
                    }

                }, config.editDialogConfig || {}));
            }

        }, config))
    }

    ['Offer', 'Order', 'Delivery', 'Invoice'].forEach((type) => {
        const action = getAction(type, {})
        const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}
        Ext.ux.ItemRegistry.registerItem(`Sales-Document_${type}-GridPanel-ContextMenu`, action, 5)
        Ext.ux.ItemRegistry.registerItem(`Sales-Document_${type}-GridPanel-ActionToolbar-leftbtngrp`, Ext.apply(new Ext.Button(action), medBtnStyle), 5)
        Ext.ux.ItemRegistry.registerItem(`Sales-Document_${type}-editDialog-Toolbar`, Ext.apply(new Ext.Button(action), medBtnStyle), 50)
    })
})


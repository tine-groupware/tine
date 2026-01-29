/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import PurchaseInvoice from '../../Model/Document/PurchaseInvoice'
import { get } from 'lodash'

Promise.all([Tine.Tinebase.appMgr.isInitialised('Purchasing'),
    Tine.Tinebase.appMgr.isInitialised('Sales'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Sales')

    const getAction = (config) => {
        return new Ext.Action(Object.assign({
            text: app.i18n._('Import as Purchase Invoice'),
            iconCls: `SalesDocument_PurchaseInvoice`,
            actionUpdater(action, grants, records, isFilterSelect, filteredContainers) {
                const contenttype = get(records, '[0].data.contenttype', get(records, '[0].data.cache.data.contenttype'))
                action[records.length === 1 && ['application/xml', 'application/pdf'].indexOf(contenttype) >= 0 ? 'show' : 'hide']()
            },
            handler: async (cmp, e, importNonXR, fileLocation) => {
                const maskEl = cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog || c instanceof Tine.widgets.MainScreen || c instanceof  Tine.Tinebase.dialog.Dialog || c instanceof Tine.Felamimail?.MessageDisplayDialog}).getEl()
                const mask = new Ext.LoadMask(maskEl, { msg: app.i18n._('Importing purchase invoice. Please wait...') })

                try {
                    fileLocation = fileLocation || await config.getFileLocation(cmp)

                    mask.show()
                    const purchaseInvoice = PurchaseInvoice.setFromJson(await Tine.Sales.importPurchaseInvoice(fileLocation, !!importNonXR))
                    mask.hide()

                    const documents = [purchaseInvoice]
                    await Ext.MessageBox.show({
                        buttons: Ext.Msg.OK,
                        icon: Ext.MessageBox.INFO,
                        title: app.formatMessage('Purchase Invoice Created:'),
                        msg: documents.map((document) => {
                            return `<a href="#" data-record-class="Sales_Model_Document_PurchaseInvoice" data-record-id="${document.id}">${document.getTitle()}</a>`
                        }).join('<br />')
                    });

                } catch (e) {
                    mask.hide()
                    if (e.data.code === 920) {
                        if (await Ext.MessageBox.confirm(
                            e.data.title,
                            e.data.message + '<br /><br />' +
                                app.formatMessage('Do you want to create an empty purchase invoice with the file as attachment?', { })
                        ) !== 'yes') { return false }

                        return cmp.handler(cmp, e, true, fileLocation)
                    } else {
                        e.data.title = app.formatMessage('Importing purchase invoice not Possible');
                        await Tine.Tinebase.ExceptionHandler.handleRequestException(e);
                    }
                }

            }
        }, config))
    }

    // purchase invoice grid
    const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}
    Ext.ux.ItemRegistry.registerItem(`Sales-Document_PurchaseInvoice-GridPanel-ActionToolbar-leftbtngrp`, Ext.apply(new Ext.Button(getAction({
        text: app.i18n._('Import Purchase Invoice'),
        actionUpdater: Ext.emptyFn,
        getFileLocation: async (cmp) => {
            return new Promise(resolve => {
                Tine.Tinebase.widgets.file.SelectionDialog.openWindow({
                    mode: 'source',
                    constraint: /(xml|pdf)$/,
                    allowMultiple: false,
                    defaultLocationType: 'local',
                    uploadMode: 'upload',
                    listeners: {
                        scope: this,
                        apply: (e) => {
                            resolve(e.fileLocation)
                        }
                    }
                });
            })
        }
    })), medBtnStyle), 32)

    // fmail
    Ext.ux.ItemRegistry.registerItem('Tine.Felamimail.MailDetailPanel.AttachmentMenu', getAction({
        getFileLocation: (cmp) => {
            let record = cmp.selection = [...cmp.initialConfig.selections][0]
            return {
                model_name: 'Felamimail_Model_AttachmentCache_FileLocation',
                location: {
                    cache_id: `Felamimail_Model_Message:${record.id}`
                }
            }
        }
    }), 16);

    // filemanager
    Ext.ux.ItemRegistry.registerItem('Filemanager-Node-GridPanel-ContextMenu', getAction({
        getFileLocation: (cmp) => {
            let record = cmp.selection = [...cmp.initialConfig.selections][0]
            return {
                model_name: 'Filemanager_Model_FileLocation',
                location: {
                    fm_path: record.data.path
                }
            }
        }
    }), 100);
})
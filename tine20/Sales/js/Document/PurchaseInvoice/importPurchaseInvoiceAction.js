// from grid: allow multi-upload
// from editDialog: allow single-upload
// from Filemanager: trigger api

import PurchaseInvoice from '../../Model/Document/PurchaseInvoice'
import { get } from 'lodash'

Promise.all([Tine.Tinebase.appMgr.isInitialised('Purchasing'),
    Tine.Tinebase.appMgr.isInitialised('Sales'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Sales')

    // challenges:
    // - new window design
    // - upload widget (single/multiple)

    const getAction = () => {

    }

    const importFromPickerAction = new Ext.Action({
        text: app.i18n._('Import Purchase Invoice'),
        iconCls: `SalesDocument_PurchaseInvoice`,
        actionUpdater(action, grants, records, isFilterSelect, filteredContainers) {
            // const contenttype = get(records, '[0].data.contenttype', get(records, '[0].data.cache.data.contenttype'))
            // action[records.length === 1 && ['application/xml', 'application/pdf'].indexOf(contenttype) >= 0 ? 'show' : 'hide']()
        },
        handler: async (cmp) => {
            const filePickerDialog = new Tine.Filemanager.FilePickerDialog({
                constraint: /(xml|pdf)$/,
                // singleSelect: true,
                // requiredGrants: ['addGrant']
            });

            // filePickerDialog.on('selected', this.onFilemanagerNodesSelected.createDelegate(this, [item, e], 0));
            // this.filePickerDialog.on('apply', async (node) => {
            //     node = node[0] ?? node;
            //     const config = await Tine.OnlyOfficeIntegrator.getEmbedUrlForNodeId(node.id);
            //
            //     this.docEditor.insertImage(_.assign(config, {
            //         "fileType": node.path.split('.').pop(),
            //     }));
            // });

            filePickerDialog.openWindow();

            // let record = cmp.selection = [...cmp.initialConfig.selections][0]
            // record = record.data.cache ? record.data.cache : record

            // const maskEl = cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog || c instanceof Tine.widgets.MainScreen || c instanceof  Tine.Tinebase.dialog.Dialog || c instanceof Tine.Felamimail.MessageDisplayDialog}).getEl()
            // const mask = new Ext.LoadMask(maskEl, { msg: app.i18n._('Importing purchase invoice. Please wait...') })
            //
            //
            //
            // try {
            //     mask.show()
            //     const purchaseInvoice = PurchaseInvoice.setFromJson(await Tine.Sales.importPurchaseInvoice({
            //         location: {
            //             model_name: 'Filemanager_Model_FileLocation',
            //             record: {
            //                 fm_path: record.data.path
            //             }
            //         }
            //     }))
            //     mask.hide()
            //
            //     const documents = [purchaseInvoice]
            //     await Ext.MessageBox.show({
            //         buttons: Ext.Msg.OK,
            //         icon: Ext.MessageBox.INFO,
            //         title: app.formatMessage('Purchase Invoice Created:'),
            //         msg: documents.map((document) => {
            //             return `<a href="#" data-record-class="Sales_Model_Document_PurchaseInvoice" data-record-id="${document.id}">${document.getTitle()}</a>`
            //         }).join('<br />')
            //     });
            //
            // } catch (e) {
            //     mask.hide()
            //     e.data.title = app.formatMessage('Importing purchase invoice not Possible');
            //     await Tine.Tinebase.ExceptionHandler.handleRequestException(e);
            // }

        }
    })

    // const action = getAction(type, {})
    const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}
    // Ext.ux.ItemRegistry.registerItem(`Sales-Document_${type}-GridPanel-ContextMenu`, action, 42)
    Ext.ux.ItemRegistry.registerItem(`Sales-Document_PurchaseInvoice-GridPanel-ActionToolbar-leftbtngrp`, Ext.apply(new Ext.Button(importFromPickerAction), medBtnStyle), 32)
    // Ext.ux.ItemRegistry.registerItem(`Sales-Document_${type}-editDialog-Toolbar`, Ext.apply(new Ext.SplitButton(action), medBtnStyle), 30)


    const importFromFileAction = new Ext.Action({
        text: app.i18n._('Import as Purchase Invoice'),
        iconCls: `SalesDocument_PurchaseInvoice`,
        actionUpdater(action, grants, records, isFilterSelect, filteredContainers) {
            const contenttype = get(records, '[0].data.contenttype', get(records, '[0].data.cache.data.contenttype'))
            action[records.length === 1 && ['application/xml', 'application/pdf'].indexOf(contenttype) >= 0 ? 'show' : 'hide']()
        },
        handler: async (cmp) => {
            let record = cmp.selection = [...cmp.initialConfig.selections][0]
            record = record.data.cache ? record.data.cache : record

            const maskEl = cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog || c instanceof Tine.widgets.MainScreen || c instanceof  Tine.Tinebase.dialog.Dialog || c instanceof Tine.Felamimail.MessageDisplayDialog}).getEl()
            const mask = new Ext.LoadMask(maskEl, { msg: app.i18n._('Importing purchase invoice. Please wait...') })


            try {
                mask.show()
                const purchaseInvoice = PurchaseInvoice.setFromJson(await Tine.Sales.importPurchaseInvoice({
                    location: {
                        model_name: 'Filemanager_Model_FileLocation',
                        record: {
                            fm_path: record.data.path
                        }
                    }
                }))
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
                e.data.title = app.formatMessage('Importing purchase invoice not Possible');
                await Tine.Tinebase.ExceptionHandler.handleRequestException(e);
            }

        }
    })

    // fmail
    Ext.ux.ItemRegistry.registerItem('Tine.Felamimail.MailDetailPanel.AttachmentMenu', importFromFileAction, 16);

    // filemanager
    Ext.ux.ItemRegistry.registerItem('Filemanager-Node-GridPanel-ContextMenu', importFromFileAction, 100);
})
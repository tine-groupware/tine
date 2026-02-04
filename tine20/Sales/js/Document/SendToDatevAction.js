/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Promise.all([Tine.Tinebase.appMgr.isInitialised('Sales'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Sales')

    const getAction = (modelName, config) => {
        return new Ext.Action(Object.assign({
            text: app.i18n._('Send as Email to Datev'),
            iconCls: `action_export`,
            allowMultiple: true,
            actionUpdater(action, grants, records, isFilterSelect, filteredContainers) {
                const enabled = records.length > 0 && Boolean(records[0]['id']);
                action.setDisabled(!enabled)
                action.baseAction.setDisabled(!enabled) // WTF?
            },
            async handler(cmp) {
                const editDialog = cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog})
                const selections = this.initialConfig.selections ?? [];
                if (selections.length === 0) return; 
                const method =  `search${modelName}s`;
                const {results: invoices} = await Tine.Sales[method]([{field: 'id', operator: 'in', value: _.map(selections, 'data.id')}]);
                if (!await validate(invoices)) return;
                
                const allAttachments = {};
                invoices.forEach((invoice) => { 
                    allAttachments[invoice.id] = invoice.attachments.map((a) => a.id);
                });
    
                this.attachmentTreePanel = new Ext.tree.TreePanel({
                    padding: '10px',
                    height: 100,
                    width: 250,
                    autoScroll: true,
                    rootVisible: false,
                    border: false,
                    root: new Ext.tree.TreeNode({
                        text: 'root',
                        draggable: false,
                        allowDrop: false,
                        id: 'root'
                    }),
                });
                
                invoices.forEach((invoice) => {
                    const node = new Ext.tree.TreeNode({
                        text: invoice?.document_number ?? invoice?.number ?? invoice?.fulltext,
                        id: invoice.id,
                        leaf: true,
                        expanded: invoice?.attachments?.length > 0,
                    });
        
                    invoice.attachments.forEach((attachment, idx) => {
                        const isValid = ['Document_Invoice', 'Invoice'].includes(modelName) ? 
                            !(/.*(timesheet|resource|ip-volum).*/).test(attachment.name)
                            : true;
                        const attachmentNode = new Ext.tree.TreeNode({
                            text: attachment.name,
                            id: attachment.id,
                            disabled: !isValid,
                            checked: isValid && idx === 0,
                            leaf: true,
                            iconCls: 'x-tree-node-leaf-checkbox',
                        });
                        attachmentNode.on('checkchange', (node, checked) => {
                            let hasSelection = false;
                            node.parentNode.childNodes.forEach((n) => {
                                if (n.ui.isChecked()) {
                                    hasSelection = true;
                                }
                            })
                            if (!hasSelection) {
                                node.ui.toggleCheck(true);
                            }
                        });
                        node.appendChild(attachmentNode);
                    })
        
                    this.attachmentTreePanel.getRootNode().appendChild(node);
                });
                
                try {
                    const dialog = new Tine.Tinebase.dialog.Dialog({
                        windowTitle: app.i18n._('Select invoice'),
                        items: [this.attachmentTreePanel],
                        openWindow: function (config) {
                            if (this.window) return this.window;
                            config = config || {};
                            this.window = Tine.WindowFactory.getWindow(Ext.apply({
                                height: 300,
                                width: 350,
                                title: this.windowTitle,
                                closeAction: 'close',
                                modal: true,
                                layout: 'fit',
                                items: [this]
                            }, config));
                            return this.window;
                        },
                        listeners: {
                            apply: async () => {
                                const root = this.attachmentTreePanel.getRootNode();
                                root.eachChild(async (invoiceNode) => {
                                    const attachments = invoiceNode.childNodes.filter((node) => node?.attributes?.checked);
                                    allAttachments[invoiceNode.id] = attachments.map((a) => a.id);
                                    await Tine.Sales.exportInvoicesToDatevEmail(modelName, allAttachments)
                                        .then((result) => {
                                            if (editDialog && result.results.length > 0) {
                                                editDialog.loadRecord('remote');
                                            }
                                        });
                                });
                            }
                        },
                    });
                    dialog.openWindow();
                } catch (e) {
                    Tine.Tinebase.ExceptionHandler.handleRequestException(e.data);
                }
            }
        }, config))
    };
    const validate = (invoices) => {
        return new Promise(function (fulfill, reject) {
            const DatevSentInvoices = invoices.filter((pi) => {return pi?.last_datev_send_date});
            if (DatevSentInvoices.length === 0) {
                return fulfill(true);
            } else {
                Ext.MessageBox.confirm(
                    i18n._('Confirm'),
                    app.i18n._('The DATEV email has already been sent. Do you want to continue anyway?'),
                    function (button) {
                        if (button === 'yes') {
                            fulfill(true);
                        } else {
                            reject(false);
                        }
                    }, this
                );
            }
        });
    };
    
    ['Document_PurchaseInvoice','PurchaseInvoice', 'Document_Invoice', 'Invoice'].forEach((modelName) => {
        const configName = modelName.match(/PurchaseInvoice$/) ? 'datevRecipientEmailsPurchaseInvoice' : 'datevRecipientEmailsInvoice';
        const datevRecipients = Tine.Tinebase.configManager.get(configName, 'Sales');
        if (!datevRecipients || datevRecipients.length === 0) return;
        const action = getAction(modelName, {});
        const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'};
        Ext.ux.ItemRegistry.registerItem(`Sales-${modelName}-GridPanel-ContextMenu`, action, 2);
        Ext.ux.ItemRegistry.registerItem(`Sales-${modelName}-GridPanel-ActionToolbar-leftbtngrp`, Ext.apply(new Ext.Button(action), medBtnStyle), 30);
        Ext.ux.ItemRegistry.registerItem(`Sales-${modelName}-editDialog-Toolbar`, Ext.apply(new Ext.Button(action), medBtnStyle), 10);
    })
})

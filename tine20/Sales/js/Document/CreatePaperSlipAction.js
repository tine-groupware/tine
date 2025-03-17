/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

// @see https://github.com/ericmorand/twing/issues/332
// #if process.env.NODE_ENV !== 'unittest'
import getTwingEnv from "twingEnv";
// #endif

/**
 * create paper slip of given document
 * @param config
 *  maskEl
 *  record
 *  recordClass
 *  editDialog
 *
 * @returns {Promise<void>}
 */
const createAttachedDocument = async (config) => {
    const app = Tine.Tinebase.appMgr.get('Sales')
    const win = config.win || window
    const recordClass = config.recordClass || config.record.constructor
    const recordName = recordClass.getRecordName()
    const typeName = config.type === 'paperslip' ? app.formatMessage('Paper Slip') : app.formatMessage('eDocument')
    const api = config.type === 'paperslip' ? Tine.Sales.createPaperSlip : Tine.Sales.createEDocument
    let record
    let attachedDocument
    let mask
    if (config.maskEl) {
        const maskMsg = app.formatMessage('Creating { recordName } { typeName }', {recordName, typeName})
        mask = new win.Ext.LoadMask(config.maskEl, {msg: maskMsg})
        mask.show()
    }

    try {
        record = !config.editDialog ? config.record :
            (config.editDialog.record.isModified() ? Tine.Tinebase.data.Record.setFromJson(await config.editDialog.applyChanges(), recordClass) : config.editDialog.record)
        attachedDocument = record.getAttachedDocument(config.type)
        if (!attachedDocument || config.force) {
            record = Tine.Tinebase.data.Record.setFromJson(await api(recordClass.getPhpClassName(), config.record.id), recordClass)
            config.editDialog && config.editDialog.loadRecord ? await config.editDialog.loadRecord(record) : null
            window.postal.publish({
                channel: "recordchange",
                topic: [app.appName, recordClass.getMeta('modelName'), 'update'].join('.'),
                data: {...record.data}
            });
            attachedDocument = record.getAttachedDocument(config.type)
        }
    } catch (e) {

        await win.Ext.MessageBox.show({
            buttons: Ext.Msg.OK,
            icon: Ext.MessageBox.WARNING,
            title: app.formatMessage('There where Errors:'),
            msg: app.formatMessage('Cannot create { typeName } for { recordName }: { title } ({e.code}) { e.message }', { recordName, typeName, title: config.record.getTitle(), e })
        });
    }

    mask ? mask.hide() : null;

    return { record, attachedDocument }
};

Promise.all([Tine.Tinebase.appMgr.isInitialised('Sales'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Sales')

    const getAction = (type, config) => {
        const recordClass = Tine.Tinebase.data.RecordMgr.get(`Sales.Document_${type}`)
        return new Ext.Action(Object.assign({
            text: config.text || app.formatMessage('Print Paper Slip'),
            iconCls: `action_print`,
            actionUpdater(action, grants, records, isFilterSelect, filteredContainers) {
                let enabled = records.length === 1
                action.setDisabled(!enabled)
                action.baseAction.setDisabled(!enabled) // WTF?
            },
            async handler(cmp, e) {
                let record = this.initialConfig.selections[0];
                const editDialog = cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog})

                if (editDialog) {
                    try {
                        await editDialog.isValid()
                    } catch (e) {
                        return
                    }
                }

                const getMailAction = async (win, record, paperSlip) => {
                    const recipientData = _.get(record, 'data.recipient_id.data', _.get(record, 'data.recipient_id')) || {};
                    paperSlip.attachment_type = 'attachment';

                    return new Ext.Button({
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top',
                        text: app.formatMessage('Send by E-Mail'),
                        iconCls: `action_composeEmail`,
                        disabled: !recipientData.email,
                        handler: () => {
                            win.Tine.Felamimail.MessageEditDialog.openWindow({
                                contentPanelConstructorInterceptor: async (config) => {
                                    const mailDefaults = win.Tine.Felamimail.Model.Message.getDefaultData();
                                    const emailBoilerplate = _.find(record.get('boilerplates'), (bp) => { return bp.name === 'Email'});
                                    let body = '';
                                    if (emailBoilerplate) {
                                        this.twingEnv = getTwingEnv();
                                        const loader = this.twingEnv.getLoader();
                                        loader.setTemplate(`${record.id}-email`, emailBoilerplate.boilerplate);
                                        body = await this.twingEnv.render(`${record.id}-email`, {record: record.data});
                                        if (mailDefaults.content_type === 'text/html') {
                                            body = Ext.util.Format.nl2br(body);
                                        }
                                    }

                                    const mailRecord = new win.Tine.Felamimail.Model.Message(Object.assign(mailDefaults, {
                                        subject: `${record.constructor.getRecordName()} ${record.get('document_number')}: ${record.get('document_title')}`,
                                        body: body,
                                        to: [`${recipientData.name} < ${recipientData.email} >`],
                                        attachments: [paperSlip]
                                    }), 0);

                                    Object.assign(config, {
                                        record: mailRecord
                                    });
                                }
                                // listeners: {
                                //     update: (mail) => {
                                //         const docType = editDialog.record.constructor.getMeta('recordName');
                                //         const currentStatus = editDialog.record.get(editDialog.statusFieldName);
                                //         let changeStatusTo = null;
                                //
                                //         if (docType === 'Invoice' && currentStatus === 'STATUS_BOOKED') {
                                //             changeStatusTo = 'SHIPPED';
                                //         } else if (docType === 'Offer' && currentStatus === 'DRAFT') {
                                //             // don't change status - might still be a draft!
                                //         }
                                //
                                //         editDialog.getForm().findField(editDialog.statusFieldName).set(changeStatusTo);
                                //
                                //         debugger
                                //     }
                                // }
                            });
                        },
                    });
                };

                const paperSlipConfig = { record, recordClass, editDialog, type: 'paperslip' }
                paperSlipConfig.force = e.ctrlKey || e.altKey
                if (Tine.OnlyOfficeIntegrator) {
                    Tine.OnlyOfficeIntegrator.OnlyOfficeEditDialog.openWindow({
                        id: record.id,
                        contentPanelConstructorInterceptor: async (config) => {
                            const isPopupWindow = config.window.popup
                            const win = isPopupWindow ? config.window.popup : window
                            const mainCardPanel = isPopupWindow ? win.Tine.Tinebase.viewport.tineViewportMaincardpanel : await config.window.afterIsRendered()
                            isPopupWindow ? mainCardPanel.get(0).hide() : null;

                            const {record, attachedDocument } = await createAttachedDocument(Object.assign(paperSlipConfig, { win, maskEl: mainCardPanel.el }))
                            Object.assign(config, {
                                recordData: attachedDocument,
                                id: attachedDocument.id,
                                // tbarItems: [await getMailAction(win, record, paperSlip)]
                            });
                        }
                    })
                } else {
                    const maskEl = cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog || c instanceof Tine.widgets.MainScreen }).getEl()
                    await createAttachedDocument(Object.assign(paperSlipConfig, { maskEl }))
                    alert('OnlyOfficeIntegrator missing -> find paperSlip in attachments')
                }
            }

        }, config))
    }

    ['Offer', 'Order', 'Delivery', 'Invoice'].forEach((type) => {
        const action = getAction(type, {})
        const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}
        Ext.ux.ItemRegistry.registerItem(`Sales-Document_${type}-editDialog-Toolbar`, Ext.apply(new Ext.Button(action), medBtnStyle), 10)
    })
})

export {
    createAttachedDocument
}
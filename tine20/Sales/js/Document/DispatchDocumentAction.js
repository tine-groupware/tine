/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

// @see https://github.com/ericmorand/twing/issues/332
// #if process.env.NODE_ENV !== 'unittest'
import getTwingEnv from "twingEnv";
// #endif

import AbstractAction from "./AbstractAction"
import { createAttachedDocument } from "./CreatePaperSlipAction"
import DispatchHistoryDialog from "./DispatchHistoryDialog"

// dispatching is done by the server based on dispatch configs. (Sales_Frontend_Json->dispatchDocument)
// also for manual dispatching the server creates the necessary documents and sets the document to MANUAL_DISPATCH state
// for each manual config, the server creates a dispatch-history-record stating the instructions
// (NOTE: as long as we don't support automatic uploads type 'upload' also acts as manual type)
// for batch processing user can follow these steps and set the history-records to "COMPLETED"
//   - when the user sets a history-record to "COMPLETED" we ask the user for "notes/records" and store them in the same record
//   - when all records are set to "COMPLETED" server sets the document to dispatched
// for a single dispatch we notify user directly about his manual task(s)
//
// dispatching manually via email:
// users might want to dispatch manually via mail. we support this with a separate action (single record only)
//   - if manual task are open, ask user if he wants to complete on of those tasks (multi options, allowMultiple)
//   - else inform user that he does an additional dispatch without respecting dispatch configs (ok/cancel)
//     - ask user which email address to use
//     - ask for documents to include
//     - open email-compose
//     - and ask user if document status should be set to dispatched after the mail is sent (yes/no)
//     - create dispatch-history-record
//     - optionally set manual tasks "COMPLETED"
//     - optionally set document dispatched


const setDispatched = async function(config) {
    const app = Tine.Tinebase.appMgr.get('Sales')
    const win = config.win || window
    const recordClass = config.recordClass || config.record.constructor
    const recordName = recordClass.getRecordName()

    const maskMsg = app.formatMessage('Set { recordName } dispatched', { recordName })
    const mask = new win.Ext.LoadMask(config.maskEl, { msg: maskMsg })
    mask.show()

    const docType = config.record.constructor.getMeta('recordName')
    const statusFieldName = `${docType.toLowerCase()}_status`
    const currentStatus = config.record.get(statusFieldName)

    let changeStatusTo = null;
    if (docType === 'Invoice' && currentStatus === 'BOOKED') {
        changeStatusTo = 'DISPATCHED';
    } else if (docType === 'Offer' && currentStatus === 'DRAFT') {
        // don't change status - might still be a draft!
    }
    if (changeStatusTo &&  !config.dispatchHistoryRecords.length && await Ext.MessageBox.confirm(
        app.formatMessage('Mark Document Dispatched?'),
        app.formatMessage('Do you want to set the Document Status to "Dispatched"?')
    ) === 'yes') {
        config.record.set(statusFieldName, changeStatusTo)
        if (config.editDialog) {
            config.editDialog.getForm().findField(statusFieldName).setValue(changeStatusTo)
            await config.editDialog.applyChanges()
            config.record = config.editDialog.record
        } else {
            config.record = await config.record.getProxy().promiseSaveRecord(config.record)
        }
    }

    if (config.dispatchHistoryRecords.length) {
        // add history records with type success
        // @TODO
    }

    mask.hide()
}


Promise.all([Tine.Tinebase.appMgr.isInitialised('Sales'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Sales')

    const getAction = (type, config) => {
        return new AbstractAction({
            documentType: type,
            text: config.text || app.formatMessage('Dispatch Document'),
            iconCls: `action_dispatch_document`,
            actionUpdater(action, grants, records, isFilterSelect, filteredContainers) {
                let enabled = records.length === 1

                enabled = records.reduce((enabled, record) => {
                    return enabled && _.find(action.statusDef.records, {id: record.get(action.statusFieldName) })?.booked
                }, enabled)

                action.baseAction.setDisabled(!enabled) // this is the action which sets all instances
            },
            handler: async function(cmp) {
                AbstractAction.prototype.handler.call(this, cmp);

                let record = this.selections = [...this.initialConfig.selections][0]
                const win = window
                const docType = record.constructor.getMeta('recordName')
                const className = record.constructor.getPhpClassName()
                const statusFieldName = `${docType.toLowerCase()}_status`
                const currentStatus = record.get(statusFieldName)

                if (currentStatus === 'DISPATCHED' && Ext.MessageBox.confirm(
                    app.formatMessage('Nothing to do'),
                   app.formatMessage('This document is already dispatched!') + '<br /><br />' +
                        app.formatMessage('Do you want to dispatch again?')
                ) !== 'yes') {
                    return false;
                }

                if (currentStatus !== 'MANUAL_DISPATCH') {
                    this.mask.show()

                    try {
                        const success = await Tine.Sales.dispatchDocument(className, record.id)
                    } catch (e) {
                        await Ext.MessageBox.show({
                            buttons: Ext.Msg.OK,
                            icon: Ext.MessageBox.WARNING,
                            title: this.app.formatMessage('Dispatching not Possible'),
                            msg: e.message
                        })
                        this.mask.hide()
                        return
                    }

                    record = await record.constructor.getProxy().promiseLoadRecord(record)
                    this.editDialog ? this.editDialog.loadRecord(record, true) : null

                    this.mask.hide()
                }

                if (record.get(statusFieldName) === 'MANUAL_DISPATCH') {
                    await Ext.MessageBox.alert(
                        app.formatMessage('Manual Dispatch Needed'),
                        app.formatMessage('All automatic dispatch steps are completed, you need to fulfill the remaining tasks manually.')
                    )

                    DispatchHistoryDialog.openWindow({
                        editDialog: this.editDialog,
                        record
                    })
                }
            },
            menu: [new AbstractAction({
                documentType: type,
                text: app.formatMessage('Manual Dispatch via Email'),
                iconCls: `SalesEDocument_Dispatch_Email`,
                handler: async function(cmp) {
                    this.initialConfig = this.parentMenu.ownerCt.initialConfig;
                    AbstractAction.prototype.handler.call(this, cmp);

                    let record = this.selections = [...this.initialConfig.selections][0]
                    const win = window
                    const docType = record.constructor.getMeta('recordName')
                    const statusFieldName = `${docType.toLowerCase()}_status`
                    const currentStatus = record.get(statusFieldName)
                    const isDispatched = ['DISPATCHED', 'MANUAL_DISPATCH'].indexOf(currentStatus) >= 0
                    const dispatchHistoryRecords = record.get('dispatch_history')

                    if (!dispatchHistoryRecords.length && await Ext.MessageBox.confirm(
                        app.formatMessage('Bypass Dispatch Configs?'), (isDispatched ?
                         '<b>' + app.formatMessage('This document is already dispatched!') + '</b><br /><br />' : '') +
                         app.formatMessage('You are about to manually dispatch this document without evaluating the configured dispatch type. Do you want to proceed?')
                    ) !== 'yes') {
                        return false;
                    }

                    let emailRecipients; try { emailRecipients = _.map(await Tine.widgets.dialog.MultiOptionsDialog.getOption(Object.assign({
                            title: app.formatMessage('Select Recipients'),
                            questionText: app.formatMessage('Please select the recipients email addresses. You also can select other addresses in the email dialog later.'),
                            allowMultiple: true,
                            allowEmpty: true,
                            allowCancel: true,
                            width: 800
                        }, await (async ()=> {
                            const debitor = record.get('debitor_id')
                            const customer = await Tine.Sales.getCustomer(record.data.customer_id.original_id)

                            const emails = []
                                .concat(debitor.edocument_dispatch_type === 'Sales_Model_EDocument_Dispatch_Email' && debitor.edocument_dispatch_config.email ?
                                    { name: debitor.edocument_dispatch_config.email, text: app.formatMessage('{ adr } (Dispatch email of debitor)', { adr: debitor.edocument_dispatch_config.email })} : [])
                                .concat(debitor.eas_id.code === 'EM' && debitor.electronic_address ?
                                    { name: debitor.electronic_address, text: app.formatMessage('{ adr } (Electronic address of debitor)', { adr: debitor.electronic_address })} : [])
                                .concat(record.data.recipient_id.email ?
                                    { name: `${record.data.recipient_id.name} < ${record.data.recipient_id.email} >`, text: app.formatMessage('{ adr } (Email of document recipient)', { adr: `${record.data.recipient_id.name} < ${record.data.recipient_id.email} >` })} : [])
                                .concat(customer.postal?.email ?
                                    { name: `${customer.postal.name} < ${customer.postal.email} >`, text: app.formatMessage('{ adr } (Email of customer postal address)', { adr: `${customer.postal.name} < ${customer.postal.email} >` })} : [])
                                .concat(_.reduce(Tine.Addressbook.Model.Contact.getModelConfiguration().fields, (accu, field) => {
                                    return accu.concat(field.specialType === 'Addressbook_Model_ContactProperties_Email' && !field.disabled && _.get(customer, `cpextern_id.${field.fieldName}`) ?
                                        { name: `${customer.cpextern_id.n_fileas} < ${customer.cpextern_id[field.fieldName]} >`, text: app.formatMessage("{ adr } ({type} of customer's external contact person)", { adr: `${customer.cpextern_id.n_fileas} < ${customer.cpextern_id[field.fieldName]} >`, type: Tine.Tinebase.appMgr.get('Addressbook').i18n._hidden(field.label) })} : [])
                                }, []))
                                .concat(_.reduce(Tine.Addressbook.Model.Contact.getModelConfiguration().fields, (accu, field) => {
                                    return accu.concat(field.specialType === 'Addressbook_Model_ContactProperties_Email' && !field.disabled && _.get(customer, `cpintern_id.${field.fieldName}`) ?
                                        { name: `${customer.cpintern_id.n_fileas} < ${customer.cpintern_id[field.fieldName]} >`, text: app.formatMessage("{ adr } ({type} of customer's internal contact person)", { adr: `${customer.cpintern_id.n_fileas} < ${customer.cpintern_id[field.fieldName]} >`, type: Tine.Tinebase.appMgr.get('Addressbook').i18n._hidden(field.label) })} : [])
                                }, []))

                            return {options: emails, height: emails.length * 50 + 100}
                        })()

                    )), 'name') } catch (e) {/* USERABORT */ return }

                    // autocheck paperslip, ubl and supporting_documents, offer all other attachments
                    let paperslip = record.getAttachedDocument('paperslip')
                    let edocument = record.getAttachedDocument('ubl')
                    let docs = _.concat([
                        { name: 'paperslip', text: app.formatMessage('Paperslip ({ filename })', {filename: paperslip ? paperslip.name : app.formatMessage('Generated when dispatched')}), file: paperslip, checked: true },
                        { name: 'ubl', text: app.formatMessage('eDocument ({ filename })', {filename: edocument ? edocument.name : app.formatMessage('Generated when dispatched')}), file: edocument, checked: true }
                    ], _.reduce(record.get('attachments'), (docs, attachment) => {
                        const attachedDocument = _.find(record.get('attached_documents'), { node_id: attachment.id })
                        if (! attachedDocument || attachedDocument.type === 'supporting_document') {
                            docs.push({ name: attachment.id, text: attachment.name, file: attachment, checked: !!attachedDocument})
                        }
                        return docs
                    }, []))

                    try {
                        docs = await Tine.widgets.dialog.MultiOptionsDialog.getOption({
                            title: app.formatMessage('Please Select Files to Dispatch'),
                            questionText: app.formatMessage('Please select the files which should be dispatched.'),
                            allowMultiple: true,
                            allowEmpty: false,
                            allowCancel: true,
                            height: docs.length * 30 + 100,
                            options: docs
                        })
                    } catch (e) {/* USERABORT */ return }

                    // @TODO if manual task are open, ask user if he wants to complete those tasks (multi options, allowMultiple)
                    const dispatchProcesses = _.groupBy(_.sortBy(record.get('dispatch_history'), 'dispatch_date'), (dh) => `${dh.dispatch_id}-${dh.dispatch_parent_id}-${dh.dispatch_transport}`)
                    const openProcesses = _.reduce(dispatchProcesses, (accu, dhs, group) => {
                        return _.concat(accu,dhs[0].dispatch_transport === 'Sales_Model_EDocument_Dispatch_Manual' && !_.find(dhs, { type: 'success' }) ? _.find(dhs, {type: 'start'}) : [])
                    }, [])


                    this.mask.show()

                    // create paperslip/ubl if nessesary
                    let promises = [];
                    if (_.find(docs, { name: 'paperslip' }) && !paperslip) {
                        promises.push(createAttachedDocument({
                            record,
                            type: 'paperslip',
                            maskEl: this.maskEl,
                            editDialog: this.editDialog
                        }).then( ret => {
                            _.find(docs, { name: 'paperslip' }).file = ret.attachedDocument
                        }))
                    }
                    if (_.find(docs, { name: 'ubl' }) && !edocument) {
                        promises.push(createAttachedDocument({
                            record,
                            type: 'ubl',
                            maskEl: this.maskEl,
                            editDialog: this.editDialog
                        }).then( ret => {
                            _.find(docs, { name: 'ubl' }).file = ret.attachedDocument
                        }))
                    }

                    const dispatchedConfig = {
                        maskEl: this.maskEl,
                        editDialog: this.editDialog,
                        docs,
                        record,
                        dispatchHistoryRecords,
                        win
                    }

                    win.Tine.Felamimail.MessageEditDialog.openWindow({
                        contentPanelConstructorInterceptor: async (config) => {
                            await Promise.allSettled(promises)
                            const mailDefaults = win.Tine.Felamimail.Model.Message.getDefaultData()
                            const emailBoilerplate = _.find(record.get('boilerplates'), (bp) => { return bp.name === 'Email'})
                            let body = ''
                            if (emailBoilerplate) {
                                this.twingEnv = getTwingEnv()
                                const loader = this.twingEnv.getLoader()
                                loader.setTemplate(`${record.id}-email`, emailBoilerplate.boilerplate)
                                body = await this.twingEnv.render(`${record.id}-email`, record.data)
                                if (mailDefaults.content_type === 'text/html') {
                                    body = Ext.util.Format.nl2br(body)
                                }
                            }

                            const mailRecord = new win.Tine.Felamimail.Model.Message(Object.assign(mailDefaults, {
                                subject: `${record.constructor.getRecordName()} ${record.get('document_number')}` + (record.get('document_title') ? `: ${record.get('document_title')}` : ''),
                                body: body,
                                to: emailRecipients,
                                attachments: _.map(docs, (doc) => {
                                    return Object.assign(doc.file, { attachment_type: 'attachment' })
                                })
                            }), 0)

                            Object.assign(config, {
                                record: mailRecord,
                                onRecordUpdate: function() {
                                    Tine.Felamimail.MessageEditDialog.prototype.onRecordUpdate.call(this)
                                    this.record.data.fileLocations.push({
                                        model: 'Sales_Model_Document_DispatchHistory',
                                        record_id : {
                                            dispatch_id: Tine.Tinebase.data.Record.generateUID(),
                                            document_type: record.constructor.getPhpClassName(),
                                            document_id: record.id,
                                            dispatch_date: new Date(),
                                            dispatch_transport: 'Sales_Model_EDocument_Dispatch_Manual',
                                            dispatch_report: app.formatMessage('Manually dispatched without evaluating the configured dispatch type'),
                                            type: 'success'
                                        },
                                        type: 'attachment'
                                    })
                                }
                            })
                        },
                        listeners: {
                            update: async (mail) => {
                                // save document so that server manages doc state
                                if (config.editDialog) {
                                    await config.editDialog.applyChanges()
                                    config.record = config.editDialog.record
                                } else {
                                    config.record = await config.record.getProxy().promiseSaveRecord(config.record)
                                }
                            }
                        }
                    });

                    this.mask.hide()

                    if (this.errorMsgs.length) {
                        await Ext.MessageBox.show({
                            buttons: Ext.Msg.OK,
                            icon: Ext.MessageBox.WARNING,
                            title: this.app.formatMessage('There where Errors:'),
                            msg: this.errorMsgs.join('<br />')
                        })
                    }
                }
            })]
        })
    }


    ['Invoice'].forEach((type) => {
        const action = getAction(type, {})
        const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}
        Ext.ux.ItemRegistry.registerItem(`Sales-Document_${type}-GridPanel-ContextMenu`, action, 2)
        Ext.ux.ItemRegistry.registerItem(`Sales-Document_${type}-editDialog-Toolbar`, Ext.apply(new Ext.SplitButton(action), medBtnStyle), 50)
        Ext.ux.ItemRegistry.registerItem(`Sales-Document_${type}-GridPanel-ActionToolbar-leftbtngrp`, Ext.apply(new Ext.SplitButton(action), medBtnStyle), 30)
    })
})
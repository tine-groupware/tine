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

import AbstractAction from "./AbstractAction";
import { createAttachedDocument } from "./CreatePaperSlipAction";

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
        changeStatusTo = 'SHIPPED';
    } else if (docType === 'Offer' && currentStatus === 'DRAFT') {
        // don't change status - might still be a draft!
    }
    if (changeStatusTo) {
        config.record.set(statusFieldName, changeStatusTo)
        if (config.editDialog) {
            config.editDialog.getForm().findField(statusFieldName).setValue(changeStatusTo)
            await config.editDialog.applyChanges()
            config.record = config.editDialog.record
        } else {
            config.record = await config.record.getProxy().promiseSaveRecord(config.record)
        }
    }

    // set attached documents dispatched
    const promises = _.map(config.docs, (doc) => {
        const attachedDocument = _.find(config.record.get('attached_documents'), { node_id: doc.file.id });
        attachedDocument.dispatch_history = attachedDocument.dispatch_history || [];
        attachedDocument.dispatch_history.push({
            attached_document_id: attachedDocument.id,
            dispatch_date: new Date(),
            dispatch_transport: config.dispatch_transport,
            dispatch_report: config.dispatch_report
        })
        return Tine.Sales.saveDocument_AttachedDocument(attachedDocument)
    })
    _.each(await Promise.allSettled(promises), doc => {
        // let's reload document
    })

    if (config.editDialog) {
        config.editDialog.loadRecord('remote')
    } else {
        config.record = await config.record.getProxy().promiseLoadRecord(config.record)
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
                let enabled = records.length

                enabled = records.reduce((enabled, record) => {
                    return enabled && _.find(action.statusDef.records, {id: record.get(action.statusFieldName) })?.booked
                }, enabled)

                action.baseAction.setDisabled(!enabled) // this is the action which sets all instances
            },
            handler: async function(cmp) {
                AbstractAction.prototype.handler.call(this, cmp);

                // let record = [...this.initialConfig.selections][0]
                // let paperSlip

                let record = this.selections = [...this.initialConfig.selections][0]
                const dispatchType = record.get('debitor_id').edocument_dispatch_type || 'manual'
                const win = window


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
                    dispatchType,
                    win
                }

                if (dispatchType === 'email') {
                    win.Tine.Felamimail.MessageEditDialog.openWindow({
                        contentPanelConstructorInterceptor: async (config) => {
                            await Promise.allSettled(promises)
                            const recipientData = _.get(record, 'data.recipient_id.data', _.get(record, 'data.recipient_id')) || {};
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
                                to: [`${recipientData.name} < ${recipientData.email} >`],
                                attachments: _.map(docs, (doc) => {
                                    return Object.assign(doc.file, { attachment_type: 'attachment' })
                                })
                            }), 0)

                            Object.assign(config, {
                                record: mailRecord
                            })
                        },
                        listeners: {
                            update: (mail) => {
                                setDispatched(Object.assign(dispatchedConfig, {
                                    report: mail // mhh, we could save the mail as attachment?!
                                                 // e.g. by fileTo tempfile?... no api's yet :-(
                                }))
                            }
                        }
                    });
                } else {
                    // @TODO other dispatchTypes
                    await Promise.allSettled(promises)
                    await Ext.MessageBox.alert(
                        app.formatMessage('Manual Dispatch'),
                        app.formatMessage('Dispatch documents where created, please download and dispatch manually.')
                    )
                    setDispatched(dispatchedConfig)
                }

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
        })
    }


    ['Invoice'].forEach((type) => {
        const action = getAction(type, {})
        const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}
        Ext.ux.ItemRegistry.registerItem(`Sales-Document_${type}-GridPanel-ContextMenu`, action, 2)
        Ext.ux.ItemRegistry.registerItem(`Sales-Document_${type}-editDialog-Toolbar`, Ext.apply(new Ext.Button(action), medBtnStyle), 50)
        Ext.ux.ItemRegistry.registerItem(`Sales-Document_${type}-GridPanel-ActionToolbar-leftbtngrp`, Ext.apply(new Ext.Button(action), medBtnStyle), 30)
    })
})
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import AbstractAction from "./AbstractAction";

Promise.all([Tine.Tinebase.appMgr.isInitialised('Sales'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Sales')

    const getAction = (type, config) => {
        return new AbstractAction({
            documentType: type,
            text: app.i18n._('Book Document'),
            iconCls: `action_book_document`,
            actionUpdater(action, grants, records, isFilterSelect, filteredContainers) {
                let enabled = records.length

                enabled = records.reduce((enabled, record) => {
                    return enabled && !_.find(action.statusDef.records, {id: record.get(action.statusFieldName) })?.booked
                }, enabled)

                // action.setDisabled(!enabled) // this is the component itsef
                action.baseAction.setDisabled(!enabled) // this is the action which sets all instances
            },
            handler: async function(cmp) {
                AbstractAction.prototype.handler.call(this, cmp);

                // @TODO: maybe we should define default booked state somehow? e.g. offer should be accepted (not only send) or let the user select?
                const bookedState = this.statusDef.records.find((r) => { return r.booked })
                this.mask.show()

                try {
                    // check if date is set and ask if user want's to change it to today
                    const notToday = _.reduce(this.unbooked, (acc, record) => {
                        return _.concat(acc, record.get('date') && record.get('date').format('Ymd') !== new Date().format('Ymd') ? record : []);
                    }, [])
                    if (notToday.length) {
                        _.each(await Tine.widgets.dialog.MultiOptionsDialog.getOption({
                            title: this.app.formatMessage('Change Document Date?'),
                            questionText: this.app.formatMessage('Please select the { sourceRecordsName } where you want to change the document date to today.', { sourceRecordsName: this.recordClass.getRecordsName() }),
                            allowMultiple: true,
                            allowEmpty: true,
                            allowCancel: false,
                            height: notToday.length * 30 + 100,
                            options: notToday.map((source) => {
                                return { text: source.getTitle() + ': ' + Tine.Tinebase.common.dateRenderer(source.get('date')), name: source.id, checked: false, source }
                            })
                        }), (option) => { _.find(unbooked, { id: option.name }).set('date', new Date().clearTime()); debugger});
                    }
                } catch (e) {/* USERABORT -> continue */ }

                await this.unbooked.asyncForEach(async (record) => {
                    if (record.phantom) {
                        record = await this.recordClass.getProxy().promiseSaveRecord(record)
                        if (this.recordClass === this.editDialog?.recordClass) {
                            this.editDialog ? await this.editDialog.loadRecord(record) : null
                        }
                    }
                    record.set(this.statusFieldName, bookedState.id)
                    let updatedRecord
                    try {
                        updatedRecord = await this.recordClass.getProxy().promiseSaveRecord(record)
                        this.selections.splice.apply(this.selections, [this.selections.indexOf(record), 1].concat(updatedRecord ? [updatedRecord] : []))
                        if (this.recordClass === this.editDialog?.recordClass) {
                            this.editDialog ? await this.editDialog.loadRecord(updatedRecord) : null
                        }
                    } catch (e) {
                        record.reject()
                        this.errorMsgs.push(this.app.formatMessage('Cannot book { sourceDocument }: ({e.code}) { e.message }', { sourceDocument: record.getTitle(), e }))
                    }
                })
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
    ['Offer', 'Order', 'Delivery', 'Invoice'].forEach((type) => {
        const action = getAction(type, {})
        const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}
        Ext.ux.ItemRegistry.registerItem(`Sales-Document_${type}-GridPanel-ContextMenu`, action, 2)
        Ext.ux.ItemRegistry.registerItem(`Sales-Document_${type}-editDialog-Toolbar`, Ext.apply(new Ext.Button(action), medBtnStyle), 30)
        Ext.ux.ItemRegistry.registerItem(`Sales-Document_${type}-GridPanel-ActionToolbar-leftbtngrp`, Ext.apply(new Ext.Button(action), medBtnStyle), 30)
    })
})
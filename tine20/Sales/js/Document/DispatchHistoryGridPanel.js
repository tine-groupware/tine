/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import { getDispatchAction } from './DispatchDocumentAction'
import '../Model/Document/DispatchHistory'

const DispatchHistoryGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {

    /**
     * @cfg {Sales.Document_Abstract} required
     * */
    record: null,

    recordClass: 'Sales.Document_DispatchHistory',
    initialLoadAfterRender: false,
    editDialog: true,
    allowCreateNew: false,
    allowDelete: false,
    usePagingToolbar: false,
    stateful: false,
    initFilterPanel: Ext.emptyFn,
    listenMessageBus: false,

    initComponent() {
        this.app = Tine.Tinebase.appMgr.get('Sales')
        this.modelConfig = this.recordClass.getModelConfiguration()

        this.store = new Ext.data.GroupingStore({
            groupField: 'dispatch_process',
            reader: new Ext.data.JsonReader({}, this.recordClass),
            // data: this.dispatchHistoryRecords,
            sortInfo: {
                field: 'dispatch_date',
                direction: 'ASC'
            }
        })

        this.loadData(this.record)

        this.gridConfig  = this.gridConfig || {};
        this.gridConfig.view = new Ext.grid.GroupingView({
            forceFit: true,
            showGroupName: true,
            enableGroupingMenu: false,
            hideGroupedColumn: true,
            getGroupText: function (values) {
                const startRecord = values.rs[0]
                const currRecord = _.last(values.rs)
                const groupState = Tine.Tinebase.widgets.keyfield.Renderer.get('Sales', 'dispatchHistoryType', 'icon')(currRecord.get('type'))
                return `${groupState} ${startRecord.getGroupName()}`
            },
            enableRowBody: true,
            getRowClass: function(record, rowIndex, rp, ds){
                rp.body = `<p style="margin-left: 30px;">${Ext.util.Format.htmlEncode(record.data.dispatch_report)}</p>`;
                return 'x-grid3-row-expanded';
            }
        })

        this.action_markCompleted = new Ext.Action({
            disabled: true,
            text: this.app.i18n._('Mark Process Completed'),
            iconCls: 'sales-complete-dispatch',
            actionUpdater: (action, grants, records, isFilterSelect, filteredContainers) => {
                let enabled = records.length === 1

                enabled = enabled && !_.find(this.store.data.items, { data: { dispatch_process: records[0]?.data.dispatch_process, type: 'success' } })

                action.setDisabled(!enabled)
            },
            handler: async (cmp) => {
                const record = cmp.initialConfig.selections[0]
                await Ext.MessageBox.show({
                    icon: Ext.MessageBox.QUESTION,
                    buttons: Ext.MessageBox.OKCANCEL,
                    multiline: true,
                    height: 400,
                    title: this.app.formatMessage('Enter Report'),
                    msg: this.app.formatMessage('Please Report what you did to complete this dispatch process.'),
                }).then(async (args) => {
                    const [btn, text] = args
                    if (btn === 'ok') {
                        const maskEl = (cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog || c instanceof Tine.widgets.MainScreen }) || this).getEl()
                        const mask = new Ext.LoadMask(maskEl, { msg: this.maskMsg || this.app.i18n._('Please wait...') })
                        mask.show()

                        const dhData = Ext.copyTo({
                            dispatch_report: text,
                            type: 'success',
                            dispatch_date: new Date()
                        }, record.data, 'dispatch_process, dispatch_id, parent_dispatch_id, document_id, document_type, dispatch_transport')

                        this.record.get('dispatch_history').push(dhData)
                        this.loadData(this.record)

                        const updatedRecord = this.editDialog ?
                            await this.editDialog.applyChanges() :
                            await Tine.Tinebase.data.RecordMgr.get(record.get('document_type')).getProxy().promiseSaveRecord(this.record)

                        mask.hide()
                    }
                })
            }
        })



        this.action_completeByMail = new Ext.Action({
            disabled: true,
            text: this.app.formatMessage('Send Email for this Process'),
            iconCls: `SalesEDocument_Dispatch_Email`,
            actionUpdater: (action, grants, records, isFilterSelect, filteredContainers) => {
                let enabled = records.length === 1
                action.setDisabled(!enabled)
            },
            handler: async (cmp) => {

                cmp.startRecord = _.find(this.store.data.items, { data: { dispatch_process: cmp.initialConfig.selections[0]?.data.dispatch_process, type: 'start' } })


                if (cmp.startRecord) {
                    cmp.app = this.app
                    cmp.record = this.record
                    cmp.editDialog = this.editDialog
                    cmp.dispatchHistoryStartRecord = cmp.startRecord.get('dispatch_config')
                    cmp.on('sentmail', (cmp) => {
                        this.loadData(cmp.record)
                    })

                    _.find(getDispatchAction(cmp.startRecord.get('document_type').split('_').pop(), {}).initialConfig.menu, { initialConfig: { iconCls: 'SalesEDocument_Dispatch_Email' }}).initialConfig.handler.call(cmp, cmp, true)
                }
            }
        });

        const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}
        this.tbar = [
            Ext.apply(new Ext.Button(this.action_markCompleted), medBtnStyle),
            Ext.apply(new Ext.Button(this.action_completeByMail), medBtnStyle)
        ]

        this.supr().initComponent.call(this)

        this.actionUpdater.addAction(this.action_markCompleted)
        this.actionUpdater.addAction(this.action_completeByMail)
    },

    loadData(record) {
        this.record = record
        this.dispatchHistoryRecords = _.sortBy(this.record.get('dispatch_history'), 'dispatch_date')
        this.dispatchHistoryRecords.forEach(dh => {
            dh = dh.data || dh;
            dh.dispatch_process = `${dh.dispatch_id}-${dh.dispatch_parent_id}-${dh.dispatch_transport}`
        })

        this.store.loadData(this.dispatchHistoryRecords)
        this.store.applyGroupField()
    }
});

export default DispatchHistoryGridPanel
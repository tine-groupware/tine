/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

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

    initComponent() {
        this.app = Tine.Tinebase.appMgr.get('Sales')
        this.modelConfig = this.recordClass.getModelConfiguration()

        this.dispatchHistoryRecords = this.record.get('dispatch_history')
        this.dispatchHistoryRecords.forEach(dh => {
            dh = dh.data || dh;
            dh.dispatch_process = `${dh.dispatch_id}-${dh.dispatch_parent_id}-${dh.dispatch_transport}`
        })

        this.store = new Ext.data.GroupingStore({
            groupField: 'dispatch_process',
            reader: new Ext.data.JsonReader({}, this.recordClass),
            data: this.dispatchHistoryRecords,
            sortInfo: {
                field: 'dispatch_date',
                direction: 'ASC'
            }
        })

        this.gridConfig  = this.gridConfig || {};
        this.gridConfig.view = new Ext.grid.GroupingView({
            forceFit: true,
            showGroupName: true,
            enableGroupingMenu: false,
            hideGroupedColumn: true,
            getGroupText: function (values) {
                const startRecord = values.rs[0]
                const transportName = Tine.Tinebase.data.RecordMgr.get(startRecord.get('dispatch_transport')).getRecordName()
                //@TODO add state icon from last Record once type is a keyField
                return `${transportName} - ${startRecord.get('dispatch_report')}`;
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

                        this.dispatchHistoryRecords.push(dhData)
                        const updatedRecord = await Tine.Tinebase.data.RecordMgr.get(record.get('document_type')).getProxy().promiseSaveRecord(this.record)
                        this.editDialog ? this.editDialog.loadRecord(updatedRecord, true) : null

                        this.store.add(new this.recordClass(dhData))

                        mask.hide()
                    }
                })
            }
        })

        const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}
        this.tbar = [Ext.apply(new Ext.Button(this.action_markCompleted), medBtnStyle)]

        this.supr().initComponent.call(this)

        this.actionUpdater.addAction(this.action_markCompleted)
    }
});

export default DispatchHistoryGridPanel
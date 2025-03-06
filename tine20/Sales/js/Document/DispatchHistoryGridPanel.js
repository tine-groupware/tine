/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const DispatchHistoryGridPanel = Ext.extend(Ext.grid.GridPanel, {

    /**
     * @cfg {Sales.Document_DispatchHistory[]} [required]
     */
    dispatchHistoryRecords: null,

    initComponent() {
        this.app = Tine.Tinebase.appMgr.get('Sales')
        this.recordClass = Tine.Tinebase.data.RecordMgr.get('Sales.Document_DispatchHistory')

        const recordFields = this.recordClass.prototype.fields
        if (recordFields.keys.indexOf('grouping') < 0) {
            recordFields.add(new Ext.data.Field({
                "name": 'grouping',
                "label": this.app.i18n._("Dispatch Process")
            }))
        }

        this.dispatchHistoryRecords.forEach(dh => {
            // @TODO how to separate multiple steps of the same transport in one custom dispatch? (one record or other field)
            dh.data.grouping = `${dh.dispatch_id}-${dh.dispatch_transport}`
        })

        this.store = new Ext.data.GroupingStore({
            groupField: 'grouping',
            reader: new Ext.data.JsonReader({}, this.recordClass),
            data: this.dispatchHistoryRecords,
            sortInfo: {
                field: 'dispatch_date',
                direction: 'ASC'
            }
        })

        this.view = new Ext.grid.GroupingView({
            emptyGroupText: this.app.i18n._('Generic'),
            // forceFit: true,
            showGroupName: false,
            // enableNoGroups: false,
            enableGroupingMenu: false,
            hideGroupedColumn: true
        })

        return this.supr().initComponent.call(this)
    }
});

export default DispatchHistoryGridPanel
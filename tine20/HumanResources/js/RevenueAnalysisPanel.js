/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

Tine.HumanResources.RevenueAnalysisPanel = Ext.extend(Tine.widgets.grid.GridPanel, {

    /**
     * @property {Ext.ux.form.PeriodPicker} periodPicker
     */
    periodPicker: null,

    recordClass: 'Tine.HumanResources.Model.Employee',
    stateId: 'HumanResources-RevenueAnalysisPanel',
    autoRefreshInterval: null,
    listenMessageBus: false,
    displaySelectionHelper: false,
    


    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('HumanResources');

        const sumsPanel = new Ext.ux.display.DisplayPanel({
            items: [{
                flex: 1,
                layout: 'ux.display',
                labelWidth: 150,
                autoScroll: true,
                layoutConfig: {
                    background: 'solid'
                },
                items: [{
                    xtype: 'label',
                    cls: 'x-ux-display-header',
                    text: this.app.i18n._('Totals of the selected lines')
                }].concat(_.reduce(this.getColumns(), (accu, col) => {
                    if (Tine.HumanResources.Model.Employee.getFieldNames().indexOf(col.dataIndex) < 0) {
                        accu.push({
                            xtype: 'ux.displayfield',
                            name: col.dataIndex,
                            htmlEncode: false,
                            ctCls: 'tine-tinebase-recorddisplaypanel-displayfield',
                            fieldLabel: col.header,
                            renderer: col.renderer
                        })
                    }
                    return accu
                }, []))
            }]
        })
        const updateSums = () => {
            let records = this.selectionModel.getCount() ? this.selectionModel.getSelections() : this.store.data.items

            const sums = _.reduce(records, (sums, record) => {
                const result = record.data || {}
                _.each(sums, (v,k) => sums[k] = sums[k] + (result[k] ? parseFloat(result[k], 10) : 0))
                return sums;
            }, {recordedAmount:0,clearedAmount:0,totalcount:0,totalcountbillable:0,totalsum:0,totalsumbillable:0,turnOverGoal:0,workingTimeTarget:0})

            sums.recordedPercent = Math.round(100 * (sums.recordedAmount || 0) / sums.turnOverGoal)
            sums.clearedPercent = Math.round(100 * (sums.clearedAmount || 0) / sums.turnOverGoal)
            sums.workingTimePercent = Math.round(100 * (sums.totalsum || 0) / sums.workingTimeTarget)

            const sumsRecord = new this.recordClass({})
            Object.assign(sumsRecord.data, sums)
            sumsPanel.loadRecord(sumsRecord)
        }

        this.detailsPanel = {
            xtype: 'widget-detailspanel',
            // singleRecordPanel: sumsPanel,
            multiRecordsPanel: sumsPanel,
            // defaultInfosPanel: sumsPanel,
            // showDefault: updateSums,
            showMulti: updateSums
        };

        this.periodPicker = new Ext.ux.form.PeriodPicker({
            availableRanges: 'week,month,quarter,year',
            listeners: {
                'change': _.bind(this.onPeriodChange, this)
            }
        });

        this.recordProxy = Tine.HumanResources.employeeBackend;
        this.defaultSortInfo = {
            field: 'account_id'
        };

        this.i18nRecordName = 'Revenue Analysis';

        this.defaultFilters = [{
            field: 'employment_end', operator: 'after', value: new Date().clearTime().getLastDateOfMonth().add(Date.DAY, 1)
        }];

        const additionalItems = [];
        this.actionToolbar = new Ext.Toolbar({
            canonicalName: [this.recordClass.getMeta('modelName'), 'ActionToolbar'].join(Tine.Tinebase.CanonicalPath.separator),
            items: [{
                xtype: 'buttongroup',
                layout: 'toolbar',
                buttonAlign: 'left',
                enableOverflow: true,
                plugins: [{
                    ptype: 'ux.itemregistry',
                    key:   this.app.appName + '-' + this.recordClass.prototype.modelName + '-GridPanel-ActionToolbar-leftbtngrp'
                }],
                items: [Ext.apply(
                    new Ext.Button({
                        text: this.app.i18n._('Reload'),
                        tooltip: this.app.i18n._('Reload all data'),
                        scope: this,
                        handler: this.resolveRecords,
                        iconCls: 'x-tbar-loading'
                    }), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    }), Ext.apply(
                    new Ext.Button({
                        text: this.app.i18n._('Export'),
                        tooltip: this.app.i18n._('Export all data'),
                        scope: this,
                        handler: this.exportRecords,
                        iconCls: 'action_export'
                    }), {
                        scale: 'medium',
                        rowspan: 2,
                        iconAlign: 'top'
                    })]
            }]
        });

        this.tbar = [{
            text: this.app.i18n._('Period'),
            xtype: 'tbtext'
        }, this.periodPicker]

        this.supr().initComponent.call(this)
    },

    initGrid: function() {
        let columns = this.getColumns();

        this.gridConfig.autoExpandColumn = 'account_id';
        // this.gridConfig.forceFit = false;

        this.gridConfig.cm = new Ext.grid.ColumnModel({
            defaults: {
                width: 30,
                fixed: true,
                resizable: false,
                sortable: false,
                menuDisabled: true
            },
            columns: columns
        });

        Tine.HumanResources.RevenueAnalysisPanel.superclass.initGrid.call(this);
    },

    getColumns: function() {

        const defaults = {fixed: false, resizable: true, sortable: true, menuDisabled: false, width: 200};
        let colManager = _.bind(Tine.widgets.grid.ColumnManager.get, Tine.widgets.grid.ColumnManager,
            'HumanResources',
            'Employee',
            _,
            'mainScreen',
            _
        );
        const rendererManager = _.bind(Tine.widgets.grid.RendererManager.get,
            Tine.widgets.grid.RendererManager, 'Timetracker', 'Timesheet', _,
            Tine.widgets.grid.RendererManager.CATEGORY_GRIDPANEL);

        let columns = [
            colManager('number', {... defaults}),
            _.assign(colManager('account_id'), Object.assign({width: 100}, defaults)),
            _.assign(colManager('division_id'), Object.assign({width: 100}, defaults)),
            _.assign({ header: this.app.i18n._('Turnover Target'), dataIndex: 'turnOverGoal', renderer: _.bind(Ext.util.Format.money, this, _, _, false) }, defaults),
            _.assign({ header: this.app.i18n._('Turnover Recorded'), dataIndex: 'recordedAmount', renderer: _.bind(Ext.util.Format.money, this, _, _, false) }, defaults),
            _.assign({ header: this.app.i18n._('Turnover Recorded %'), dataIndex: 'recordedPercent', renderer: (v,m,r) => { return Ext.ux.PercentRenderer(Math.round(100 * (r.data.recordedAmount || 0) / r.data.turnOverGoal)) } }, defaults),
            _.assign({ header: this.app.i18n._('Turnover Cleared'), dataIndex: 'clearedAmount', renderer: _.bind(Ext.util.Format.money, this, _, _, false) }, defaults),
            _.assign({ header: this.app.i18n._('Turnover Cleared %'), dataIndex: 'clearedPercent', renderer: (v,m,r) => { return Ext.ux.PercentRenderer(Math.round(100 * (r.data.clearedAmount || 0) / r.data.turnOverGoal)) } }, defaults),
            // Tine.widgets.grid.RendererManager.get('HumanResources', 'MonthlyWTReport', 'working_time_target')
            _.assign({ header: this.app.i18n._('Working Time Target'), dataIndex: 'workingTimeTarget', renderer: Ext.ux.form.DurationSpinner.durationRenderer }, defaults),
            _.assign({ header: this.app.i18n._('Working Time Recorded'), dataIndex: 'totalsum', renderer:  Ext.ux.form.DurationSpinner.durationRenderer }, defaults),
            _.assign({ header: this.app.i18n._('Working Time %'), dataIndex: 'workingTimePercent', renderer: (v,m,r) => { return Ext.ux.PercentRenderer(Math.round(100 * (r.data.totalsum || 0) / r.data.workingTimeTarget)) } }, defaults)
        ];

        return columns;
    },

    /**
     * called before store queries for data
     */
    onStoreBeforeload: function(store, options) {
        Tine.HumanResources.RevenueAnalysisPanel.superclass.onStoreBeforeload.apply(this, arguments);
    },

    /**
     * called after a new set of Records has been loaded
     *
     * @param  {Ext.data.Store} this.store
     * @param  {Array}          loaded records
     * @param  {Array}          load options
     * @return {Void}
     */
    onStoreLoad: function(store, records, options) {
        Tine.HumanResources.RevenueAnalysisPanel.superclass.onStoreLoad.apply(this, arguments);

        this.resolveRecords();
    },

    resolveRecords: async function() {
        this.showLoadMask()

        await Promise.all(_.map(this.store.data.items, async (record) => Tine.Timetracker.searchTimesheets([{
            "field": "start_date",
            "operator": "within",
            "value": this.periodPicker.getValue(),
        }, {
            "field": "account_id",
            "operator": "equals",
            "value": record.data.account_id,
        }], {start: 0, limit: 1}).then(result => {
            result.workingTimeTarget = result.workingTimeTarget/60
            Object.assign(record.data, result)
        })))

        this.grid.getView().refresh();
        this.hideLoadMask()
    },

    exportRecords: async function () {
        const columns = _.filter(this.grid.getColumnModel().config, {hidden: false})
        const data = [_.map(columns, 'header')]

        _.forEach(this.store.data.items, (r) => {
            data.push(_.map(columns, (column) => {
                const value = r.get(column.dataIndex);
                return this.quoteCsv(Ext.util.Format.stripTags(column.renderer ? column.renderer(value, null, r) : value)) || '';
            }))
        })

        const csvContent = "data:text/csv;charset=utf-8,"
            + data.map(e => e.join(",")).join(Ext.isWindows ? "\r\n" : "\n");

        const link = document.createElement("a");
        link.setAttribute("href", encodeURI(csvContent));
        link.setAttribute("download", "revenue_analysis.csv");
        document.body.appendChild(link);
        link.click();
    },

    quoteCsv: function(v) {
        v = String(v).replace(/"/g, '""');
        if (v.search(/("|,|\n)/g) >= 0)
            v = '"' + v + '"';

        return v;
    },

    onPeriodChange: function(pp, period) {
        this.resolveRecords()
    },

    // prevent default
    onRowClick: Ext.emptyFn,
    onRowDblClick: Ext.emptyFn,

    showLoadMask: function() {
        if (! this.loadMask) {
            this.loadMask = new Ext.LoadMask(this.getEl(), {msg: this.app.i18n._("Loading revenue analysis data...")});
        }
        this.loadMask.show.defer(100, this.loadMask);
        return Promise.resolve();
    },

    hideLoadMask: function() {
        this.loadMask.hide.defer(100, this.loadMask);
        return Promise.resolve();
    },

})

Ext.reg('humanresources.revenueanalysis', Tine.HumanResources.RevenueAnalysisPanel);

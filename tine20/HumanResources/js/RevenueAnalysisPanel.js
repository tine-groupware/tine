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
    autoRefreshInterval: null,
    listenMessageBus: false,
    displaySelectionHelper: false,
    


    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Timetracker');

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
            _.assign({ header: this.app.i18n._('Turnover Target'), dataIndex: 'turnOverGoal', renderer: Ext.util.Format.money }, defaults),
            _.assign({ header: this.app.i18n._('Turnover Recorded'), dataIndex: 'recordedAmount', renderer: Ext.util.Format.money }, defaults),
            _.assign({ header: this.app.i18n._('Turnover Cleared'), dataIndex: 'clearedAmount', renderer: Ext.util.Format.money }, defaults),
            _.assign({ header: this.app.i18n._('Turnover %'), dataIndex: 'clearedAmount', renderer: (v,m,r) => { return Tine.Tinebase.common.percentRenderer(Math.round(100 * (r.data.clearedAmount || 0) / r.data.turnOverGoal)) } }, defaults),
            _.assign({ header: this.app.i18n._('Working Time Target'), dataIndex: 'workingTimeTarget', renderer: rendererManager('duration') }, defaults),
            _.assign({ header: this.app.i18n._('Working Time Recorded'), dataIndex: 'totalsum', renderer:  rendererManager('duration') }, defaults),
            _.assign({ header: this.app.i18n._('Working Time %'), dataIndex: 'totalsum', renderer: (v,m,r) => { return Tine.Tinebase.common.percentRenderer(Math.round(100 * (r.data.totalsum || 0) / r.data.workingTimeTarget)) } }, defaults)
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
            Object.assign(record.data, result)
        })))
        const sums = _.reduce(this.store.data.items, (sums, record) => {
            const result = record.data || {}
            _.each(sums, (v,k) => sums[k] = sums[k] + (result[k] ? parseFloat(result[k], 10) : 0))
            return sums;
        }, {clearedAmount:0,totalcount:0,totalcountbillable:0,totalsum:0,totalsumbillable:0,turnOverGoal:0,workingTimeTarget:0})

        this.grid.getView().refresh();
        this.hideLoadMask()
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

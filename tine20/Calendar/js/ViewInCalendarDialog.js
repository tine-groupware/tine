/*
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Calendar');

/**
 * @namespace Tine.Calendar
 * @class Tine.Calendar.ViewInCalendarDialog
 * @extends Ext.Panel
 */
Tine.Calendar.ViewInCalendarDialog = Ext.extend(Ext.Panel, {
    cls: 'tw-editdialog',
    layout: 'fit',
    border: false,
    windowNamePrefix: 'ViewInCalendarWindow_',
    canonicalName: ['', 'Calendar', 'ViewInCalendarDialog'].join(Tine.Tinebase.CanonicalPath.separator),

    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        this.recordClass = Tine.Calendar.Model.Event;
        this.recordProxy = Tine.Calendar.backend;

        if (Ext.isString(this.record)) {
            this.record = this.recordProxy.recordReader({responseText: this.record});
        }

        this.store = new Ext.data.JsonStore({
            fields: Tine.Calendar.Model.Event,
            proxy: Tine.Calendar.backend,
            reader: new Ext.data.JsonReader({}),
            listeners: {
                scope: this,
                'beforeload': this.onStoreBeforeload,
            },
        });

        this.pagingToolbar = new Tine.Calendar.PagingToolbar({
            view: 'week',
            store: this.store,
            dtStart: this.record.get('dtstart'),
            showReloadBtn: true,
            showTodayBtn: false,
            listeners: {
                scope: this,
                change: this.onStoreLoad,
                refresh: this.onStoreLoad,
            }
        });

        this.calendarView = new Tine.Calendar.DaysView({
            store: this.store,
            startDate: this.pagingToolbar.getPeriod().from,
            numOfDays: 7,
            height: 400,
            readOnly: true,
        });
        this.calendarView.getSelectionModel().on('selectionchange', this.onViewSelectionChange, this);

        this.detailsPanel = new Tine.Calendar.iMIPDetailsPanel(this);
        this.detailsPanel.on('updateEvent', this.onStoreLoad.createDelegate(this));
        this.detailsPanel.on('afterrender', this.onStoreLoad.createDelegate(this));
        this.detailsPanel.attendeeCombo.defaultValue = this.targetAttendeeRecord.id;
        this.detailsPanel.attendeeCombo.on('select', function (combo, rec) {
            this.targetAttendeeRecord = rec;
            this.filterToolbar.filterStore.each(function (filter) {
                const field = filter.get('field');
                if (field === 'attender') {
                    filter.set('value', [this.targetAttendeeRecord.data]);
                    filter.formFields.value.setValue([this.targetAttendeeRecord.data]);
                }
            }, this);
            this.filterToolbar.onFiltertrigger();
        },this);

        this.fbar = ['->', {
            text: i18n._('Ok'),
            minWidth: 70,
            ref: '../buttonApply',
            scope: this,
            handler: this.onButtonApply,
            iconCls: 'action_saveAndClose'
        }];

        this.filterToolbar = new Tine.widgets.grid.FilterToolbar({
            app: this.app,
            recordClass: this.recordClass,
            filterModels: this.recordClass.getFilterModel(),
            defaultFilter: 'query',
            store: this.store,
            filters: [
                {field: 'attender', operator: 'in', value: [this.targetAttendeeRecord.data]},
                {field: 'attender_status', operator: 'notin', value: 'DECLINED'},
            ],
        });

        this.items = [{
            layout: 'border',
            border: false,
            layoutConfig: {
                align:'stretch'
            },
            items: [{
                region: 'north',
                border: false,
                layout: 'fit',
                autoHeight: true,
                items: [
                    this.detailsPanel.actionToolbar,
                ]
            }, {
                region: 'center',
                layout: 'fit',
                border: false,
                items: [
                    this.filterToolbar,
                    this.pagingToolbar,
                    this.calendarView
                ]
            }, {
                region: 'south',
                border: false,
                collapsible: true,
                collapseMode: 'mini',
                header: false,
                split: true,
                layout: 'fit',
                height: 200,
                items: this.detailsPanel
            }]
        }];
        Tine.Calendar.ViewInCalendarDialog.superclass.initComponent.call(this);
    },

    onViewSelectionChange: function(sm, selections) {
        this.detailsPanel.onDetailsUpdate(sm);
    },

    selectTargetEvent() {
        const target = this.calendarView.store.getById(this.record.id);
        const sm = this.calendarView.getSelectionModel();
        if (target) {
            sm.select(target);
        }
    },

    afterRender: function() {
        Tine.Calendar.ViewInCalendarDialog.superclass.afterRender.call(this);

        this.window.setTitle(this.app.i18n._('Edit Event invitation'));
        this.loadMask = new Ext.LoadMask(this.getEl(), {msg: i18n._('Please Wait')});
    },

    onStoreBeforeload: function(store, options) {
        if (this.loadMask) this.loadMask.show();

        if (!options) options = {};
        options.callback = () => {
            this.store.each(function(event) {
                if (event.ui) event.ui.setOpacity(0.5, 0);
            }, this);

            if (this.targetAttendeeRecord) this.calendarView.attendeeRecord = this.targetAttendeeRecord;

            this.store.add([this.record]);

            if (this.record?.ui) this.record.ui.setOpacity(1, 0);
            if (this.loadMask) this.loadMask.hide();

            this.selectTargetEvent();
        };

        const period = this.pagingToolbar.getPeriod();
        this.store.baseParams.filter = this.filterToolbar.getValue();
        this.store.baseParams.filter.push({field: 'period', operator: 'within', value: period});

        if (this.record.get('id')) {
            this.store.baseParams.filter.push({field: 'id', operator: 'not', value: this.record.get('id')});
        }

        this.calendarView.updatePeriod(period);
    },

    onStoreLoad: function(store, options) {
        this.store.removeAll();
        this.store.load();
    },

    onButtonApply: function() {
        this.fireEvent('apply', this, Ext.encode(this.record.data));
        this.purgeListeners();
        this.window.close();
    }
});

/**
 * Opens a new free time serach dialog window
 *
 * @return {Ext.ux.Window}
 */
Tine.Calendar.ViewInCalendarDialog.openWindow = function (config) {
    if (! _.isString(config.record)) {
        config.record = Ext.encode(config.record.data);
    }

    return Tine.WindowFactory.getWindow({
        width: 1024,
        height: 768,
        name: Tine.Calendar.ViewInCalendarDialog.prototype.windowNamePrefix + _.get(config, 'record.id', 0),
        contentPanelConstructor: 'Tine.Calendar.ViewInCalendarDialog',
        contentPanelConstructorConfig: config
    });
};
/*
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import './Model/Timesheet';
import './Tasks/timeaccountingPanel';
import './Tasks/addTimesheetAction';

Ext.ns('Tine.Timetracker');

Tine.widgets.grid.RendererManager.register('Timetracker', 'Timesheet', 'timeaccount_closed', function(row, index, record) {
    var isopen = (record.data.timeaccount_id.is_open == '1');
    return Tine.Tinebase.common.booleanRenderer(!isopen);
});

Tine.widgets.grid.RendererManager.register('Timetracker', 'Timesheet', 'timeaccount_id', function(row, index, record) {
    var record = new Tine.Timetracker.Model.Timeaccount(record.get('timeaccount_id'));
    var closedText = record.get('is_open') ? '' : (' (' + Tine.Tinebase.appMgr.get('Timetracker').i18n._('closed') + ')');
    return Ext.util.Format.htmlEncode(record.get('number') ? (record.get('number') + ' - ' + record.get('title') + closedText) : '');
});

Tine.widgets.grid.RendererManager.register('Timetracker', 'Timesheet', 'accounting_time', function(row, index, record) {
    let value = record.data.accounting_time;

    if (!value && !record.data.is_billable) {
        const factor = record.data.accounting_time_factor;
        const duration = record.data.duration;
        const roundingMinutes = Tine.Tinebase.configManager.get('accountingTimeRoundingMinutes', 'Timetracker') || 15;
        const roundingMethod = Tine.Tinebase.configManager.get('accountingTimeRoundingMethod', 'Timetracker') || 'round';
        value = Math[roundingMethod](factor * duration / roundingMinutes) * roundingMinutes;
    }
    value = Tine.Tinebase.common.minutesRenderer(value) ?? '';

    if (!record.data.is_billable) return '<span style="text-decoration: line-through;">' + value + '</span>';
    return value;
});

Tine.widgets.grid.RendererManager.register('Timetracker', 'Timesheet', 'accounting_time_factor', function(row, index, record) {
    const value = Ext.util.Format.htmlEncode(record.data.accounting_time_factor);
    if (!record.data.is_billable && value) return '<span style="text-decoration: line-through;">' + value + '</span>';
    return value;
});

Tine.widgets.grid.RendererManager.register('Timetracker', 'Timeaccount', 'status', function(row, index, record) {
    return Tine.Tinebase.appMgr.get('Timetracker').i18n._hidden(record.get('status'));
});

Tine.widgets.grid.RendererManager.register('Timetracker', 'Timeaccount', 'is_open', function(row, index, record) {
    var i18n = Tine.Tinebase.appMgr.get('Timetracker').i18n;
    return record.get('is_open') ? i18n._('open') : i18n._('closed');
});

Tine.widgets.grid.RendererManager.register('Timetracker', 'Timeaccount', 'budget_filled_level', function(row, index, record) {
    const budget = record.get('budget') ?? 0;
    if (budget === 0) return null;
    const percent = record.get('budget_filled_level') ?? 0;
    const level = percent < 80 ? 'Below' : (percent < 100 ? 'Over' : 'Limit');
    return Ext.ux.PercentRenderer({
        percent: percent,
        colorClass : `PercentRenderer-progress-bar${level}`
    });
});


// add renderer for invoice position gridpanel
Tine.Timetracker.HumanHourRenderer = function(value) {
    return Ext.util.Format.round(value, 2);
};

Tine.Timetracker.registerRenderers = function() {
    
    if (! Tine.hasOwnProperty('Sales') || ! Tine.Sales.hasOwnProperty('InvoicePositionQuantityRendererRegistry')) {
        Tine.Timetracker.registerRenderers.defer(10);
        return false;
    }
    
    Tine.Sales.InvoicePositionQuantityRendererRegistry.register('Timetracker_Model_Timeaccount', 'hour', Tine.Timetracker.HumanHourRenderer);
};

Tine.Timetracker.registerRenderers();

Tine.Timetracker.registerAccountables = function() {
    if (! Tine.hasOwnProperty('Sales') || ! Tine.Sales.hasOwnProperty('AccountableRegistry')) {
        Tine.Timetracker.registerAccountables.defer(10);
        return false;
    }
    
    Tine.Sales.AccountableRegistry.register('Timetracker', 'Timeaccount');
};

Tine.Timetracker.registerAccountables();

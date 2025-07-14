/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import {colorLegend} from "../FreeTimePlanningWestPanel";

Tine.Tinebase.ApplicationStarter.isInitialised().then(() => {
    const app = Tine.Tinebase.appMgr.get('HumanResources');

    const defaults = {
        appName: 'HumanResources',
        group: app.i18n._('Working Time Tracking'),
        groupIconCls: 'HumanResourcesTimetrackerHooks',
        config: {
            hideColumnsMode: 'hide',
            stateIdSuffix: '-timetrackerhook',
        }
    };

    Tine.widgets.MainScreen.registerContentType('Timetracker', _.merge({
        modelName: 'DailyWTReport',
        config: {
            showColumns: ['tags', 'employee_id', 'date', 'working_time_target', 'working_time_actual', 'working_time_correction',
                'working_time_total', 'working_time_balance']
        }
    }, defaults));

    Tine.widgets.MainScreen.registerContentType('Timetracker', _.merge({
        modelName: 'MonthlyWTReport',
        config: {
            showColumns: ['tags', 'employee_id', 'month', 'working_time_balance_previous', 'working_time_target', 'working_time_correction',
                'working_time_actual', 'working_time_balance']
        }
    }, defaults));

    Tine.widgets.MainScreen.registerContentType('Timetracker', _.merge({
        modelName: 'FreeTime',
    }, defaults));

    Tine.widgets.MainScreen.registerContentType('Timetracker', _.merge({
        contentType: 'FreeTimePlanning',
        text: app.i18n._('Absence Planning'),
        xtype: 'humanresources.freetimeplanning',
    }, defaults));

    Ext.ux.ItemRegistry.registerItem('Tine.Timetracker.FreeTimePlanning.WestPanelPortalColumn', colorLegend);
});

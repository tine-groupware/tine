/*
 * Tine 2.0
 * 
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

import '../styles/HumanResources.scss'
import './Application';
import './Models'
import './EmployeeGridPanel'
import './ExceptionHandler'
import './ContractEditDialog'
import './EmployeeEditDialog'
import './FreeDayGridPanel'
import './FreeTimeEditDialog'
import './ContractDetailsPanel'
import './ContractGridPanel'
import './FreeTimeEmployeeFilter'
import './AccountGridPanel'
import './EmployeeEditDialogFreeTimeGridPanel'
import './AccountEditDialog'
import './WorkingTimeSchemeEditDialog'
import './DailyWTReportGridPanel'
import './DailyWTReportEditDialog'
import './MonthlyWTReportGridPanel'
import './MonthlyWTReportEditDialog'
import './WTRCorrectionPicker'
import './WTRCorrectionEditDialog'
import './FreeTimeGridPanel'
import './FreeTimePlanningWestPanel'
import './FreeTimePlanningPanel'
import './DivisionEditDialog'
import './Timetracker/hooks'
import './AttendanceRecorder'
import './freeTimeType'
import './RevenueAnalysisPanel'

/**
 * register special renderer for contract workingtime_json
 */
Tine.widgets.grid.RendererManager.register('HumanResources', 'Contract', 'workingtime_json', function(v, m, r) {
    var _ = window.lodash;
    // NOTE: workingtime_json is not longer used
    v = _.get(r, 'data.working_time_scheme.json', 0);

    if (! v) {
        return 0;
    }
    var object = Ext.isString(v) ? Ext.decode(v) : v;
    var sum = 0;
    for (var i=0; i < object.days.length; i++) {
        sum = sum + parseFloat(object.days[i]);
    }
    return sum/3600;
});

// working time schema translations
Tine.widgets.grid.RendererManager.register('HumanResources', 'WorkingTimeScheme', 'type', function(v) {
    var i18n = Tine.Tinebase.appMgr.get('HumanResources').i18n;
    switch(String(v)) {
        case 'template': v = i18n._('Template'); break;
        case 'individual': v = i18n._('Individual'); break;
        case 'shared': v = i18n._('Shared'); break;
    }

    return v;
});
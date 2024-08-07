/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */


class convertTsPtWtToTimeSlotExplainer extends Ext.form.TextArea {

    initComponent() {
        this.readOnly = true;
        const app = Tine.Tinebase.appMgr.get('HumanResources');

        this.value = app.i18n._('Prefer workingtime over projecttime timesheets on a daily bases.') + "\n\n" +
            app.i18n._('Timesheets generated by the Attendance Recorders are created on the so called workingtime timeaccount of the division of the employee whereas projecttime timesheets are booked on the corrensponding project timeaccounts. Employee workingtime of a day is calculated by summing up all timesheets ot the day from this employee. If workingtime and projecttime is tracked in parallel this rule preferes workingtime on a daily bases.') + "\n\n" +
            app.i18n._('If workingtime is found for a given day only workingtime timesheets of this day will be included into workingtime calculation.');

        this.supr().initComponent.call(this);
    }
};
Ext.reg('hr-convertTsPtWtToTimeSlotExplainer', convertTsPtWtToTimeSlotExplainer)
Ext.ux.ItemRegistry.registerItem('HumanResources-BLDailyWTReport_ConvertTsPtWtToTimeSlot-RecordForm', {
    height: 150,
    xtype: 'hr-convertTsPtWtToTimeSlotExplainer'
}, 10);
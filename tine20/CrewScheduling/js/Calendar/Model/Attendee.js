/*
 * tine-groupware
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Promise.all([
    Tine.Tinebase.appMgr.isInitialised('Calendar'),
    Tine.Tinebase.appMgr.isInitialised('CrewScheduling')
]).then(() => {
    const app = Tine.Tinebase.appMgr.get('CrewScheduling');

    const attendeeFields = Tine.Calendar.Model.Attender.prototype.fields;

    // attendee is no mc yet :-(
    if (attendeeFields.keys.indexOf('crewscheduling_roles') < 0) {
        attendeeFields.add(new Ext.data.Field({
            "name": 'crewscheduling_roles',
            "label": app.i18n._("Crewscheduling Roles"),
            // "type": "Calendar.EventType",
            // "fieldDefinition": {
            //     "name": 'crewscheduling_roles',
            //     "label": app.i18n._("Crewscheduling Roles"),
            //     "type": "records",
            //     "disabled": false,
            //     "nullable": true,
            //     "owningApp": "CrewScheduling",
            //     "validators": {
            //         "allowEmpty": true,
            //     },
            //     "config": {
            //         "appName": "CrewScheduling",
            //         "application": "CrewScheduling",
            //         "modelName": "AttendeeRole",
            //         "dependentRecords": true,
            //         "refIdField": "record"
            //     }
            // }
        }));
    }
});
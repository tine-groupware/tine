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

    const eventFields = Tine.Calendar.Model.Event.prototype.fields;

    // event is no mc yet :-(
    if (eventFields.keys.indexOf('cs_roles_configs') < 0) {
        eventFields.add(new Ext.data.Field({
            "name": 'cs_roles_configs',
            "label": app.i18n._("Crewscheduling Role Configs")
        }));
    }
});
/**
 * Tine 2.0
 *
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Tasks.Model');

// // Task model
// Tine.Tasks.Model.TaskArray = Tine.Tinebase.Model.genericFields.concat([
//     { name: 'id' },
//     { name: 'uid' },
//     { name: 'percent', header: 'Percent' },
//     { name: 'completed', type: 'date', dateFormat: Date.patterns.ISO8601Long },
//     { name: 'due', type: 'date', dateFormat: Date.patterns.ISO8601Long },
//     // ical common fields
//     { name: 'class' },
//     { name: 'description' },
//     { name: 'geo' },
//     { name: 'location' },
//     { name: 'organizer' },
//     { name: 'originator_tz' },
//     { name: 'priority' },
//     { name: 'status' },
//     { name: 'summary' },
//     { name: 'url' },
//     // ical common fields with multiple appearance
//     { name: 'attach' },
//     { name: 'attendee' },
//     { name: 'tags' },
//     { name: 'comment' },
//     { name: 'contact' },
//     { name: 'related' },
//     { name: 'resources' },
//     { name: 'rstatus' },
//     // scheduleable interface fields
//     { name: 'dtstart', type: 'date', dateFormat: Date.patterns.ISO8601Long },
//     { name: 'duration', type: 'date', dateFormat: Date.patterns.ISO8601Long },
//     { name: 'recurid' },
//     // scheduleable interface fields with multiple appearance
//     { name: 'exdate' },
//     { name: 'exrule' },
//     { name: 'rdate' },
//     { name: 'rrule' },
//     { name: 'source_model' },
//     { name: 'source' },
//     // tine 2.0 notes field
//     { name: 'notes'},
//     // tine 2.0 alarms field
//     { name: 'alarms'},
//     // relations with other objects
//     { name: 'relations'},
//     { name: 'attachments'}
// ]);
//
// /**
//  * Task record definition
//  */
// Tine.Tasks.Model.Task = Tine.Tinebase.data.Record.create(Tine.Tasks.Model.TaskArray, {
//     appName: 'Tasks',
//     modelName: 'Task',
//     idProperty: 'id',
//     titleProperty: 'summary',
//     // ngettext('Task', 'Tasks', n); gettext('Tasks');
//     recordName: 'Task',
//     recordsName: 'Tasks',
//     containerProperty: 'container_id',
//     // ngettext('to do list', 'to do lists', n); gettext('to do lists');
//     containerName: 'to do list',
//     containersName: 'to do lists'
// });

Tine.Tasks.Model.TaskMixin = {
    statics: {
        /**
         * returns default account data
         *
         * @namespace Tine.Tasks.Model.Task
         * @static
         * @return {Object} default data
         */
        getDefaultData: function() {
            var app = Tine.Tinebase.appMgr.get('Tasks'),
                prefs = app.getRegistry().get('preferences');

            var data =  {
                'class': 'PUBLIC',
                percent: 0,
                organizer: Tine.Tinebase.registry.get('currentAccount'),
                container_id: app.getMainScreen().getWestPanel().getContainerTreePanel().getDefaultContainer(),
                status: 'NEEDS-ACTION',
                priority: 200
            };

            if (prefs.get('defaultalarmenabled')) {
                data.alarms = [{minutes_before: parseInt(prefs.get('defaultalarmminutesbefore'), 10)}];
            }

            return data;
        },
        /**
         * @namespace Tine.Tasks.Model.Task
         *
         * get closed status ids
         *
         * @return {Array} status ids objects
         * @static
         */
        getClosedStatus: function() {
            var reqStatus = [];

            Tine.Tinebase.widgets.keyfield.StoreMgr.get('Tasks', 'taskStatus').each(function(status) {
                if (! status.get('is_open')) {
                    reqStatus.push(status.get('id'));
                }
            }, this);

            return reqStatus;
        }
    }
};

Tine.Tasks.Model.Status = Tine.Tinebase.data.Record.create([
    { name: 'id' },
    { name: 'value' },
    { name: 'icon' },
    { name: 'system' },
    { name: 'is_open' },
    { name: 'i18nValue' }
], {
    appName: 'Tasks',
    modelName: 'Status',
    idProperty: 'id',
    titleProperty: 'i18nValue',
    // ngettext('Status', 'Status', n); gettext('Status');
    recordName: 'Status',
    recordsName: 'Status'
});

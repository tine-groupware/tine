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
    // ngettext('Status', 'Statuses', n); gettext('Status');
    recordName: 'Status',
    recordsName: 'Status'
});

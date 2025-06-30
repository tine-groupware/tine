/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import { HTMLProxy } from "../../../Tinebase/js/twingEnv.es6";
import asString from "../../../Tinebase/js/ux/asString";
Ext.ns('Tine.Timetracker.Model');

Tine.Timetracker.Model.TimesheetMixin = {
    getTitle: function() {
        return new HTMLProxy(new Promise(async (resolve) => {
            let timeaccount = this.get('timeaccount_id');
            const description = Ext.util.Format.ellipsis(this.get('description'), 30, true);
            let timeaccountTitle = '';

            if (timeaccount) {
                if (typeof(timeaccount.get) !== 'function') {
                    timeaccount = new Tine.Timetracker.Model.Timeaccount(timeaccount);
                }
                timeaccountTitle = await asString(timeaccount.getTitle());
                timeaccountTitle = timeaccountTitle ? '[' + timeaccountTitle + '] ' : '';
            }

            resolve(timeaccountTitle + description);
        }));
    },

    statics: {
        getDefaultData(defaults) {
            // dd from modelConfig
            const dd = Tine.Tinebase.data.Record.getDefaultData(Tine.Timetracker.Model.Timesheet, defaults);

            // specific defaults
            return Object.assign({
                account_id: Tine.Tinebase.registry.get('currentAccount'),
                start_date: new Date()
            }, dd);
        }
    }
}

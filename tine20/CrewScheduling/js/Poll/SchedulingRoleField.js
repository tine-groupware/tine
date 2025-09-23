/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import { getRoles } from '../Model/schedulingRole'

Promise.all([Tine.Tinebase.appMgr.isInitialised('CrewScheduling'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('CrewScheduling')



    const SchedulingRoleField = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {

        initComponent: function () {
            this.supr().initComponent.call(this);
            (async () => {
                const roles = _.filter(await getRoles(), (role) => {
                    return _.get(role, 'account_grants.createPollGrant') || _.get(role, 'account_grants.adminGrant')
                });
                this.store.loadData(roles)
                this.mode = 'local'
                // this.store.remoteSort = false
            })();
        }
    })

    Ext.reg('cs-poll-schedulingrolefield', SchedulingRoleField)


})
/**
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/*global Ext, Tine*/

import Record from 'data/Record'

const Employee = Record.create([], {
    appName: 'HumanResources',
    modelName: 'Employee',
})

Employee.getDefaultData = (defaults) => {
    defaults = Record.getDefaultData(Employee, defaults)

    const app = Tine.Tinebase.appMgr.get('HumanResources')
    const recorders = app.getRegistry().get('attendance_recorder')

    return Object.assign({
        ar_wt_device_id: recorders?.wt_device?.id,
        ar_pt_device_id: recorders?.pt_device?.id,
    }, defaults);
}

export default Employee

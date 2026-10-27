/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Record from 'data/Record'

const DispatchHistory = Record.create([], {
    appName: 'Sales',
    modelName: 'Document_DispatchHistory',

    getGroupName: function() {
        const transportName = Tine.Tinebase.data.RecordMgr.get(this.get('dispatch_transport')).getRecordName()
        return `${transportName} - ${this.get('dispatch_report')}`;
    }
})

export default DispatchHistory


/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*global Ext, Tine*/

import Record from 'data/Record'
import common from 'common'

const Poll = Record.create([], {
    appName: 'CrewScheduling',
    modelName: 'Poll',

    getUrl: function (participantId) {
        return common.getUrl() + 'CrewScheduling/view/Poll/' + this.id + (participantId ? ('/' + participantId.data?.id || participantId.id || participantId) : '');
    }
})

export default Poll
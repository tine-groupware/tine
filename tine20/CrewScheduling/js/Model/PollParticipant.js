/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Record from 'data/Record'

const Participant = Record.create([], {
    appName: 'CrewScheduling',
    modelName: 'PollParticipant'
})

export default Participant
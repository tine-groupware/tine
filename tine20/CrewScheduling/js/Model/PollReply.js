/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Record from 'data/Record'
import {isArray, reduce} from 'lodash'
import { HTMLProxy } from 'twingEnv.es6';
import asString from 'ux/asString';
import Poll from "./Poll";

const statusOrder = ['NEEDS-ACTION', 'ACCEPTED', 'TENTATIVE', 'DECLINED', 'IMPOSSIBLE']

// const
const PollReply = Record.create([], {
    appName: 'CrewScheduling',
    modelName: 'PollReply'
})

/**
 * get event_ref from event
 *
 * NOTE: poll replies could reference to recur event instances before they get saved.
 * *     as recur_id are unique per container only we prefix by base_event_it which makes ref unique
 *
 * @param event
 * @returns {string|*}
 */
PollReply.getEventRef = (event) => {
    const eventData = event.data || event
    return eventData.recur_id ? `${eventData.base_event_id}/${eventData.recur_id}` : eventData.id
}


/**
 * get combined status
 *
 * @param {CrewScheduling.PollReply[]} pollReplies
 * @returns {string}
 */
PollReply.getCombinedStatus = (...pollReplies) => {
    return reduce(pollReplies, (merged, pollReply) => {
        const status = pollReply.data?.status || pollReply.status || pollReply
        return statusOrder.indexOf(status) > statusOrder.indexOf(merged) ? status : merged;
    }, 'NEEDS-ACTION');
}

export default PollReply
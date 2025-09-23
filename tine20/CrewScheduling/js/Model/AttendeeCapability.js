/*
 * tine-groupware
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import { map, forEach, groupBy, join, flatten, filter, find, get, reduce } from 'lodash';
import * as calEventType from "../Calendar/Model/eventType";
import * as csRole from "./schedulingRole"

/**
 * holds capabilities for an attendee
 */
class AttendeeCapability {
    constructor(attendee) {
        this.attendee = attendee
    }

    // needs complex computing
    getRoles() {}

    // trivial attendee property
    getSites() {}

    // trivial attendee property (ChurchEdition CF btw...?)
    // not exactly Capabilities
    getPartners() {}

    // trivial attendee property (ChurchEdition CF btw...?)
    getFavoriteDays() {}

    // oposite of busytimes (stored/delivered as busyIds as of freeTimeSearch)
    // capable to join all events but ...
    getFreeTime(period) {}

    /* grr this is validation / possibilities depending on events
    isBusy(event) {}

    isFavoriteDay(event) {}
    */

}

export default AttendeeCapability
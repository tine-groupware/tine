/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */
/*global Ext, Tine*/

import Record from 'data/Record'
import {get, set, each, filter, find, uniq, reduce, isArray, map, indexOf} from 'lodash'

const FreeTime = Record.create([], {
    appName: 'HumanResources',
    modelName: 'FreeTime',
})

/**
 * prepares raw feastAndFreeDays response for client processing
 *
 * @param feastAndFreeDays
 */
FreeTime.prepareFeastAndFreeDays = (feastAndFreeDays)=> {
    // sort freedays into freetime
    let allFreeDays = get(feastAndFreeDays, 'allFreeDays', []);
    let freeTimeTypes =  get(feastAndFreeDays, 'freeTimeTypes', []);
    each(get(feastAndFreeDays, 'allFreeTimes', []), (freeTime) => {
        set(freeTime, 'freedays', filter(allFreeDays, {freetime_id: freeTime.id}), []);
        set(freeTime, 'type', find(freeTimeTypes, {id: freeTime.type}));
    });
}

/**
 *
 * @param {Array} feastAndFreeDaysCache
 * @param {Date} day
 */
FreeTime.isExcludeDay = (feastAndFreeDaysCache, day) => {
    const feastAndFreeDays  = get(feastAndFreeDaysCache, day.format('Y'));
    const excludeDays = [].concat(
        get(feastAndFreeDays, 'excludeDates', []),
        get(feastAndFreeDays, 'feastDays', [])
    );

    return feastAndFreeDays ? !!find(excludeDays, {date: day.format('Y-m-d 00:00:00.000000')}) : false;
}

/**
 * get all FreeTimes of give day
 *
 * @param {Array} feastAndFreeDaysCache
 * @param {Date|Date[]}day
 */
FreeTime.getFreeTimes = (feastAndFreeDaysCache, day) => {
    return uniq(reduce(isArray(day) ? day : [day], (freeTimes, day) => {
        let feastAndFreeDays  = get(feastAndFreeDaysCache, day.format('Y'), []);
        let freeDayIds = map(filter(get(feastAndFreeDays, 'allFreeDays', []), {date: day.format('Y-m-d 00:00:00')}), 'freetime_id');
        return freeTimes.concat(filter(get(feastAndFreeDays, 'allFreeTimes', []), (freeTime) => {return indexOf(freeDayIds, freeTime.id) >= 0}));
    }, []));
}
export default FreeTime

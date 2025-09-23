/*
 * tine-groupware
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import { get, filter, find, map } from 'lodash';
let types = []
let initialRequest = false;

/**
 * @param arry<id> ids optional, return all if not st
 * @returns {Promise<void>}
 */
const getTypes = async (ids) => {
    if (! initialRequest) {
        initialRequest = Tine.Calendar.searchEventTypes();
    }
    types = get(await initialRequest, 'results');
    ids = ids && ids.length && ids[0].id ? map(ids, 'id') : ids
    return ids ? filter(types, (type) => {return ids.indexOf(type.id) >= 0}) : types;
}

const getType = async (id) => {
    return find(await getTypes(), {id});
}

export {
    getTypes,
    getType
}
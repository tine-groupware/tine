/*
 * tine-groupware
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import {get, set, filter, find, uniq, map, flatten, concat, reduce, union, intersection} from 'lodash';
import * as async from 'async';
import * as calEventType from "../Calendar/Model/eventType";

let roles = []
// let lists = []

let initialRoleRequest = false;
let initialListRequest = false;

/**
 * returns SchedulingRoles optionally filtered by list of given roleIds
 * 
 * @param {String[]} [ids] optional, list of roleIds returns all if not st
 * @returns {Promise<CrewScheduling.SchedulingRole[]>}
 */
const getRoles = async (ids) => {
    if (! initialRoleRequest) {
        initialRoleRequest = Tine.CrewScheduling.searchSchedulingRoles([], {sort: 'order', dir: 'ASC'});
    }
    roles = get(await initialRoleRequest, 'results');
    return ids ? filter(roles, (role) => {return ids.indexOf(role.id) >= 0}) : roles;
}

const getRole = async (id) => {
    return find(await getRoles(), {id});
}

/**
 * returns all eventType configs of given role
 * @NOTE this are configured as eventType->Role configs in UI
 *
 * @TODO add external cache! don't cahce in property!
 * @param role
 * @returns {Promise<CrewScheduling.EventTypeConfig[]>}
 */
const getEventTypeConfigs = async (role) => {
    if (! role.eventTypeConfigs) {
        role.eventTypeConfigs = reduce(await calEventType.getTypes(), (accu, eventType) => {
            return accu.concat(find(eventType.cs_role_configs, { scheduling_role: { id: role.id } }) || [])
        }, [])
    }

    return role.eventTypeConfigs
}

/**
 * returns map eventType: capable members - for given role itself and all overridden eventTypes of that role
 *
 * @NOTE helpful to reduce amount of data which needs to be validated
 *
 * @param {CrewScheduling.SchedulingRole} role
 * @returns {Promise<{eventTypeId:contactId[]}>} map null is key for generic/typeless role
 */
const getEventTypeCapableContactsMap = async (role) => {
    const map = { null: await getCapableContacts(role.role_attendee_required_groups, role.role_attendee_required_groups_operator) }
    return async.reduce(await getEventTypeConfigs(role), map, async (accu, typeConfig) => {
        return set(accu, typeConfig.event_type.id || typeConfig.event_type, await getCapableContacts(
            typeConfig.role_attendee_required_groups?.length ? typeConfig.role_attendee_required_groups : role.role_attendee_required_groups,
            typeConfig.role_attendee_required_groups_operator || role.role_attendee_required_groups_operator
        ))
    })
}

/**
 * get all capable members (contactIds)
 *
 * @NOTE helpful to reduce amount of data which needs to be validated
 *
 * @param {String[]|CrewScheduling.RequiredGroups[]} requiredList
 * @param {String} operator 'AND'|'OR'
 * @returns {Promise<String[]>} array of contactIds
 */
const getCapableContacts = async (requiredList, operator) => {
    const allLists = await getLists()

    // flatten CrewScheduling.RequiredGroups
    requiredList = get(requiredList, '[0]group.id') ? map(requiredList, 'group.id') : requiredList

    const memberMap = map(requiredList || [], (listId) => {
        return find(allLists, { id: listId })?.members || []
    })

    return operator === 'AND' ? intersection(... memberMap) : union(... memberMap)
}

/**
 * get all involved lists (from gloabl SchedulingRoles and eventTypes)
 *
 * @returns {Promise<Addressbook.List[]>}
 */
const getLists = async () => {
    const roles = await getRoles()
    const types = await calEventType.getTypes()

    const roleGroupIds = uniq(map(flatten(map(roles, 'role_attendee_required_groups')), 'group.id'))
    const typeGroupIds =uniq(map(flatten(map(flatten(map(types, 'cs_role_configs')), 'role_attendee_required_groups')), 'group.id'))

    if (! initialListRequest) {
        initialListRequest = Tine.Addressbook.searchLists([
            { field: 'id', operator: 'in', value: uniq(concat(roleGroupIds, typeGroupIds)) }
        ])
    }
    let lists = get(await initialListRequest, 'results');

    return lists
}

export {
    getRoles,
    getRole,
    getLists,
    getCapableContacts,
    getEventTypeConfigs,
    getEventTypeCapableContactsMap
}
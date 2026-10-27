/*
 * tine-groupware
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import { map, cloneDeep, each, filter, find, get, reduce, isString, groupBy, uniq } from 'lodash';
import * as async from 'async';
import { getType, getTypes } from "../Calendar/Model/eventType";
import { getRole } from "./schedulingRole";
import Record from "data/Record";
import Event from 'Calendar/Model/Event'
import { validateEventRoleConfigCapability } from './AttendeeValidation'

const actionOrder = ['none', 'tentative', 'forbidden'];

/**
 * get eventRoleConfigs of given attendee grouped by given and all other roles
 *
 * @param {Calendar.Attender} attendee
 * @param {CrewScheduling.EventRoleConfig[]}
 * @param {CrewScheduling.SchedulingRole} [role]
 * @returns {Promise<{givenRoleAttERCs: CrewScheduling.EventRoleConfig[], otherRolesAttERCs: CrewScheduling.EventRoleConfig[]}>}
 */
const getEventRoleConfigsOfAttendee = async (eventRoleConfigs, attendee, role) => {
    const attendeeData = attendee.data || attendee
    const attendeeRoles = attendeeData.crewscheduling_roles || []

    const attendeeERCs = await async.map(attendeeRoles, async(attendeeRole) => {
        // direct match from eventRoleConfigs
        return await async.detect(eventRoleConfigs, async eRC => await getRoleTypesKey(eRC) === await getRoleTypesKey(attendeeRole)) ||
        // upstream config otherwise
        await createFromRoleTypes(/* { event_types: ...}*/ attendeeRole)
    })

    const grouped = groupBy(attendeeERCs, (eRC) => {
        if (isString(role)) {
            console.error('do not check by id!')
            return (eRC.role.id || eRC.id) === role ? 'givenRoleAttERCs' : 'otherRolesAttERCs'
        } else {
            return (eRC.role.key || eRC.key) === role.key ? 'givenRoleAttERCs' : 'otherRolesAttERCs'
        }
    })
    grouped.givenRoleAttERCs = grouped.givenRoleAttERCs || []
    grouped.otherRolesAttERCs = grouped.otherRolesAttERCs || []

    return grouped
}

/**
 * get count of attendee having the given CrewScheduling.SchedulingRole - Calendar.EventType[] combination assigned
 *
 * @param {CrewScheduling.EventRoleConfig} eventRoleConfig
 * @param {Calendar.Event} event
 * @param {Object|Function} [predicate=_.identity]
 * @returns {Promise<Calendar.Attendee[]>}
 */
const getEventRoleAttendee = async (eventRoleConfig, event, predicate) => {
    const key = await getRoleTypesKey(eventRoleConfig)
    const attendees = filter(map(event.data?.attendee || event.attendee, attendee => attendee.data || attendee), predicate)
    return (await async.filter(attendees, async (attendee) => {
        return async.detect((attendee.data?.crewscheduling_roles || attendee.crewscheduling_roles), async eRC => {
            return await getRoleTypesKey(eRC) === key
        })
    }))
}

/**
 * return unambiguous key for CrewScheduling.SchedulingRole - Calendar.EventType[] combinations
 * RTsKey?
 *
 * @param {CrewScheduling.EventRoleConfig|CrewScheduling.AttendeeRole} item
 * @returns {Promise<sting>}
 */
const getRoleTypesKey = async (item) => {
    const d = item.data || item
    const role = isString(d.role) ? (await getRole(d.role)) : (d.role.data || d.role)
    const types = await async.map(d.event_types, async (type) => { return isString(type) ? (await getType(type)) : type})

    return `${role.key}:${uniq(map(types, 'short_name')).sort().join('&')}`
}

/**
 * creates roleConfigs from given RoleTypes(Key) combination
 *
 * fetches type->role configs (role upstream if not overwridden in type) and merges them
 * NOTE: no autosplit here! resulting eRC might not be valid
 *
 * @param {Calendar.Attendeerole|CrewScheduling.EventRoleConfig} roleTypes
 * @returns {Promise<CrewScheduling.EventRoleConfig>}
 */
const createFromRoleTypes = async (roleTypes) => {
    roleTypes = roleTypes.data || roleTypes
    const role = isString(roleTypes.role) ? await getRole(roleTypes.role) : roleTypes.role
    // const eventTypes =await async.map(roleTypes.event_types, async (type) => { return isString(type) ? (await getType(type)) : type})
    const eventTypes = await getTypes(roleTypes.event_types)
    const roleConfig = {
        id: Record.generateUID(),
        role: role.data || role,
        event_types: eventTypes,
        exceedance_action: role.exceedance_action,
        shortfall_action: role.shortfall_action,
        num_required_role_attendee: role.num_required_role_attendee || 1,
        same_role_same_attendee: 'may',
        other_role_same_attendee: true,
    }

    each(roleTypes.event_types, eventType => {
        const configOverride = find(eventType.cs_role_configs, csRoleConfig => csRoleConfig.scheduling_role.id === role.id)
        configOverride ? mergeEventRoleConfig(roleConfig, configOverride) : null
    })
    return roleConfig
}

/**
 * creates roleConfigs (or modifies given) from given eventTypes optionally filtered by given role
 *
 * fetches type->role's and their config (overwrites) from all types and merges them per role
 *
 * @param {Calendar.EventTypes[]} eventTypes (cross records)
 * @param {CrewScheduling.EventRoleConfig[]} [roleConfigs] existing roleConfigs to adopt/modify (untested!)
 * @return {Promise<CrewScheduling.EventRoleConfig[]>}
 */
const createFromEventTypes = async (eventTypes, roleConfigs) => {
    return async.reduce(await getTypes(map(eventTypes, 'event_type.id')), roleConfigs || [], async (roleConfigs, eventType) => {
        await async.forEach(cloneDeep(eventType.cs_role_configs), async (roleConfig) => {
            let processed = false;

            // take defaults from role if type specific roleConfig is not set
            const role = await getRole(roleConfig.scheduling_role.id);
            ['num_required_role_attendee', 'exceedance_action', 'shortfall_action'].forEach((prop) => {
                roleConfig[prop] = roleConfig[prop] ?? role[prop];
            });

            // check if we can add this type into an existing config (same_role_same_attendee: Behavior if this role is also required from other event types)
            if (eventType.same_role_same_attendee !== 'must_not') {
                each(filter(roleConfigs, (rc) => { return rc.role.id === roleConfig.scheduling_role.id }), (/* existingEventRoleConfig */ e) => {
                    // check if event_types allow to merge
                    if (!processed && !find(e.event_types, {same_role_same_attendee: 'must_not'})) {
                        e.event_types = [...e.event_types].concat(eventType);
                        mergeEventRoleConfig(e, roleConfig)
                        processed = true
                    }
                });
            }

            // otherwise add new roleConfig
            if (! processed) {
                roleConfigs.push({
                    id: Record.generateUID(),
                    role: roleConfig.scheduling_role,
                    event_types: [eventType],
                    exceedance_action: roleConfig.exceedance_action,
                    shortfall_action: roleConfig.shortfall_action,
                    num_required_role_attendee: roleConfig.num_required_role_attendee || 1,
                    same_role_same_attendee: roleConfig.same_role_same_attendee,
                    other_role_same_attendee: roleConfig.other_role_same_attendee,
                });
            }
        });
        return roleConfigs;
    });
};

/**
 * merge event role configs
 * 
 * @param {CrewScheduling.EventRoleConfig} e existing role config
 * @param {CrewScheduling.EventRoleConfig} roleConfig overwrides
 * @returns {CrewScheduling.EventRoleConfig}
 */
const mergeEventRoleConfig = (e, roleConfig) => {
    e.exceedance_action = mergeActions(roleConfig.exceedance_action, e.exceedance_action);
    e.shortfall_action = mergeActions(roleConfig.shortfall_action, e.shortfall_action);
    e.num_required_role_attendee = Math.max(roleConfig.num_required_role_attendee || 1, e.num_required_role_attendee || 1);
    e.same_role_same_attendee = [roleConfig.same_role_same_attendee, e.same_role_same_attendee].indexOf('must') >=0 ? 'must' : 'may';
    e.other_role_same_attendee = !!+roleConfig.other_role_same_attendee && !!+e.other_role_same_attendee;
    return e
}

const _getFromEventCache = {}

/**
 * returns eventRoleConfigs from given event optionally filtered for one given role
 *
 * @param {Calendar.Event} event
 * @param {CrewScheduling.SchedulingRole} [role] optional
 * @returns {Promise<CrewScheduling.EventRoleConfig[]>}
 */
const getFromEvent = async (event, role) => {
    const eventData = event.data || event
    let roleConfigs = eventData.cs_roles_configs || [];

    if (! roleConfigs.length) {
        // NOTE: we can't handle if user deletes all configs...
        const eventTypes = eventData.event_types;
        const cacheKey = map(eventTypes, 'id').join('-');
        roleConfigs = _getFromEventCache[cacheKey];
        if (! roleConfigs) {
            roleConfigs = _getFromEventCache[cacheKey] = await createFromEventTypes(eventTypes);
        }
    }

    return role ? filter(roleConfigs, (roleConfig) => {
        return roleConfig.role.id === (get(role.data || role, 'role.id', role.data?.id || role.id) || role)
    }) : roleConfigs;
};

/**
 * merges actions due to prio
 * @param {string} action1
 * @param {string} action2
 * @param ...
 * @returns {tring}
 */
const mergeActions = function() {
    return reduce(arguments, (merged, action) => {
        return actionOrder.indexOf(action) > actionOrder.indexOf(merged) ? action : merged;
    }, 'none');
};

// try {
//     Ext.ns('Tine.CrewScheduling.Model');
//
//     Tine.CrewScheduling.Model.EventRoleConfigMixin = {
//         statics: {
//             mergeActions,
//             createFromEventTypes,
//             getFromEvent
//         }
//     }
// } catch (e) { /* e.g. jest */ }

export {
    mergeActions,
    createFromEventTypes,
    getFromEvent,
    getRoleTypesKey,
    getEventRoleAttendee,
    getEventRoleConfigsOfAttendee,
    createFromRoleTypes
}
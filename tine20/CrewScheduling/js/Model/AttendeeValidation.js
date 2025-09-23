/*
 * tine-groupware
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import { map, forEach, groupBy, join, flatten, filter, find, get, reduce, isObject, compact, indexOf, remove } from 'lodash';
import * as async from 'async';
import * as calEventType from "../Calendar/Model/eventType"
import * as csRole from "./schedulingRole"
import * as eRC from "./eventRoleConfig"
import {getLists} from "./schedulingRole";
import PollReply from "./PollReply";

const wkdays = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA']
/**
 * get attendee validation with config
 */
export default class AttendeeValidation {

    constructor(/* opts */ {
        formatMessage
    }) {
        console.assert(formatMessage, 'opts.formatMessge is mandentory')
        this.formatMessage = formatMessage
    }




    // vs eventRoleConfig.getRoleTypesKey
    // vs Tine.Calendar.Model.Attender.getAttendeeStore.getSignature
    async getKey (event, roleConfig) {
        return `${event.id}:${roleConfig.role.id}:${map(roleConfig.event_type, 'id').join(':')}`
    }

    mergeValidation (validation, ...validations) {
        forEach(validations, (v) => {
            validation.isValid = validation.isValid && v.isValid
            validation.messages = validation.messages.concat(v.messages)
        })

        return validation
    }

    /**
     * get attendee validation regarding existing event role configs for given event and attendee
     *
     * NOTE: if the event has no event role config for the given role and a role is given, validation is done
     *       regarding upstream config of this role
     *
     * @param {Calendar.Attendee} attendee
     * @param {Calendar.Event} event
     * @param {CrewScheduling.SchedulingRole} [role] validate for given role only
     * @return {Promise<{isValid: boolean, messages: String[]}>}
     */
    async validate (attendee, event, role) {
        const validation = await this.validateBasics(attendee, event, role)

        // @TODO rethink how to validate existing attendee
        // all role configs from event of role
        const eRCs = await eRC.getFromEvent(event, role)
        if (! eRCs.length && role) {
            eRCs.push(await eRC.createFromRoleTypes({ role, event_types: [] }))
        }

        await async.forEach(eRCs, async (eRC) => {
            this.mergeValidation(validation, await this.validateEventRoleConfigCapability(attendee, event, eRC))
        })

        return validation
    }

    /**
     * validate basics
     *
     * @param {Calendar.Attendee} attendee
     * @param {Calendar.Event} event
     * @param {CrewScheduling.SchedulingRole} role
     * @returns {Promise<{isValid: boolean, messages: String[]}>}
     */
    async validateBasics (attendee, event, role) {
        const validation = {
            isValid: true,
            messages: []
        }

        this.mergeValidation(validation, await this.validateSite(attendee, event))
        this.mergeValidation(validation, await this.validatePollReply(attendee, event, role))
        if (['ACCEPT', 'TENTATIVE'].indexOf(validation.pollStatus) < 0) {
            this.mergeValidation(validation, await this.validateFavoriteDays(attendee, event))
            this.mergeValidation(validation, await this.validateFreeBusyInfos(attendee, event))
        }
        
        return validation
    }
    
    /**
     * validate attendee regarding event site
     *
     * @param {Calendar.Attendee} attendee
     * @param {Calendar.Event} event
     * @returns {Promise<{isValid: boolean, messages: String[]}>}
     */
    async validateSite (attendee, event) {
        const validation = {
            isValid: true,
            messages: [],
        }
        const user = get(attendee, 'data.user_id', get(attendee, 'user_id', attendee))
        const userSiteIds = map(get(user, 'data.sites', user.sites), userSite => {
            return get(userSite, 'site.id', userSite.site)
        })

        const eventSite = get(event, 'data.event_site', event.event_site)
        const eventSiteId = get(eventSite, 'id', eventSite)

        if (eventSiteId && userSiteIds.length && userSiteIds.indexOf(eventSiteId) < 0) {
            validation.isValid = false
            validation.messages.push(this.formatMessage('The attendee is not assigned to the event site'))
        }

        return validation
    }

    /**
     * validate attendee regarding his poll reply
     * @NOTE we use attendee.pollReplies for the moment which is computed by computeCSMembersCapabilities and not yet unit-testable
     *
     * @param {Calendar.Attendee} attendee
     * @param {Calendar.Event} event
     * @param {CrewScheduling.SchedulingRole} role
     * @returns {Promise<{isValid: boolean, messages: String[]}>}
     */
    async validatePollReply (attendee, event, role) {
        const user = get(attendee, 'data.user_id', get(attendee, 'user_id', attendee))
        const validation = {
            isValid: true,
            messages: [],
            pollStatus: get(user, `pollReplies.${PollReply.getEventRef(event)}.${role.role?.id || role.id || role}.status`)
        }

        if (validation.pollStatus === 'DECLINED') {
            validation.isValid = false
            validation.messages.push(this.formatMessage('The person has indicated in a survey that they are not available for this service at this time.'))
        }

        return validation
    }

    /**
     * validate attendee regarding his favorite days
     *
     * @param {Calendar.Attendee} attendee
     * @param {Calendar.Event} event
     * @returns {Promise<{isValid: boolean, messages: String[]}>}
     */
    async validateFavoriteDays (attendee, event) {
        const validation = {
            isValid: true,
            messages: [],
        }
        const user = get(attendee, 'data.user_id', get(attendee, 'user_id', attendee))
        const days = compact(get(user, 'customfields.favorite_day', '').split(','))

        if (days.length && indexOf(days, wkdays[event.get('dtstart').getDay()]) < 0) {
            validation.isValid = false
            validation.messages.push(this.formatMessage('The attendee favorite days do not contain the weekday of the event'))
        }

        return validation
    }

    /**
     * validate attendee regarding his freeBusy state
     * @NOTE we use attendee.busyIds for the moment which is computed by computeCSMembersCapabilities and not yet unit-testable
     *
     * Long version
     *  - attendee.fbInfo is computed by AttendeeProxy as HTML as of searchAttendee!
     *  - an existing event attendee does not have it!
     *  - we don't have conflicts in the demo data! -> have someone like "Birgit Belegt" with an 10 YRS meeting?
     *  - Tine.Calendar.FreeBusyInfo -> fbInfo zu einem Event und was da mit den gesuchten Teilnehmern los ist
     *  - attendee.fbInfo bricht es dann wieder auf einen user herunter und hat eine zeile (<br> sepaiert) pro fbEvent)
     *  - in den Zeilen stehen als attribute auch die eventid's und der status drinnen...
     *  - das parsen wir in der dienstplanung wieder raus und speicher attendee.busyIds (eventId's)
     *
     * @param {Calendar.Attendee} attendee
     * @param {Calendar.Event} event
     * @returns {Promise<{isValid: boolean, messages: String[]}>}
     */
    async validateFreeBusyInfos (attendee, event) {
        const validation = {
            isValid: true,
            messages: [],
        }

        const user = get(attendee, 'data.user_id', get(attendee, 'user_id', attendee))
        if (indexOf(user.busyIds, event.id) >= 0) {
            validation.isValid = false
            validation.messages.push(this.formatMessage('The attendee is busy during the event'))
        }

        return validation
    }

    /**
     * validate attendee role-capabilities with one eventRoleConfig
     * 
     * @NOTE to validate role-capabilities regarding existing attendee with his assigned roles/types combinations use something else ;-)
     * 
     * existing attendee has n role->type[] records (attendee.get('crewscheduling_roles') otherwise it's a "member" from memberSelectionPanel
     *
     * @param attendee {Calendar.Attendee}
     * @param {Calendar.Event} event
     * @param eventRoleConfig {CrewScheduling.EventRoleConfig|CrewScheduling.AttendeeRole}
     * @returns {Promise<void>}
     */
    async validateEventRoleConfigCapability (attendee, event, eventRoleConfig) {
        const attendeeData = attendee.data || attendee
        const eventTypes = eventRoleConfig.event_types?.length ? eventRoleConfig.event_types : [{ id: null, name: this.formatMessage('Without Event Type') }]
        const role = await csRole.getRole(eventRoleConfig.role.id)

        // validate generic capability to perform role for types
        const validation = await this.validateEventTypesCapability(attendee, role, eventTypes, {
            same_role_same_attendee: eventRoleConfig.same_role_same_attendee
        })

        if (validation.resolvableByRoleSplit) {
            validation.messages[validation.messages.length-1] = validation.messages[validation.messages.length-1] + "\n" +
                this.formatMessage('NOTE: It is allowed to resolve this by splitting up role to perform for different event types with different attendee.')
        }


        const eRCs = await eRC.getFromEvent(event)
        const existingAttendee = find(event.data?.attendee || event.attendee, (candidate) => {
            const candidateData = candidate.data || candidate
            return attendeeData.user_type === candidateData.user_type && (attendeeData.user_id?.id || attendeeData.user_id) === (candidateData.user_id?.id || candidateData.user_id)
        })

        const { givenRoleAttERCs, otherRolesAttERCs } = await eRC.getEventRoleConfigsOfAttendee(eRCs, existingAttendee || attendee, role)
        await async.forEach(givenRoleAttERCs, async (givenRoleAttERC) => { givenRoleAttERC.roleTypesKey = await eRC.getRoleTypesKey(givenRoleAttERC)})
        const eventRoleConfigKey = await eRC.getRoleTypesKey(eventRoleConfig)
        const removed = remove(givenRoleAttERCs, { roleTypesKey: eventRoleConfigKey })
        // NOTE: for existingAttendee, eventRoleConfig might be a roleType from his crewscheduling_roles -> in this case take the eRC from event
        if (removed.length) eventRoleConfig = removed[0]
        if (existingAttendee && removed.length && (attendee.data || attendee) !== (existingAttendee.data || existingAttendee)) {
            validation.isValid = false
            validation.messages.push(this.formatMessage('The person is already assigned to this service.'))
        }

        if (map(givenRoleAttERCs, 'same_role_same_attendee').indexOf('must_not') >= 0) {
            // 1: current/given attendee: givenRoleAttERCs.any(SRSA === `must_not`)
            validation.isValid = false
            validation.messages.push(this.formatMessage('The attendee is already assigned to an other service of the same crew scheduling role. This service does not allow the same person to take another service with the same crew scheduling role.'))
        }


        if (map(otherRolesAttERCs, r => !!+r.other_role_same_attendee).indexOf(false) >= 0) {
            // 2: current/given attendee: otherRolesAttERCs.any(ORSA !== `yes`)
            validation.isValid = false
            validation.messages.push(this.formatMessage('The attendee is already assigned to an other service which does not allow to perform additional services of other crew scheduling roles.'))
        }

        const attendeeRoles = attendeeData.crewscheduling_roles || []
        const attendeeKeys = await async.map(attendeeRoles, eRC.getRoleTypesKey)

        let someOtherTakesRole = false
        if (await async.detect(event.data?.attendee || event.attendee, async (other) => {
            const otherData = other.data || other
            if (attendeeData.user_type === otherData.user_type && (attendeeData.user_id?.id || attendeeData.user_id) === (otherData.user_id?.id || otherData.user_id)) return

            const { givenRoleAttERCs, otherRolesAttERCs } = await eRC.getEventRoleConfigsOfAttendee(eRCs, other, role)
            someOtherTakesRole = someOtherTakesRole || givenRoleAttERCs.length
            if (map(givenRoleAttERCs, 'same_role_same_attendee').indexOf('must') >= 0)  return true
        })) {
            // 3: all other attendee: existingERCs(of role).any(SRSA === `must`)
            validation.isValid = false
            validation.messages.push(this.formatMessage('An other attendee already performs a service with the same crew scheduling role and it requires that all services of that role are performed by the same person.'))
        }

        if (existingAttendee || attendeeKeys.indexOf(eventRoleConfigKey) < 0) {

            //  4: all other attendee: existingERCs(of role).length && eRCs(of role).any(SRSA === `must`)
            if (someOtherTakesRole && eventRoleConfig.same_role_same_attendee === 'must') {
                validation.isValid = false
                validation.messages.push(this.formatMessage('This service requires the same person to perform all services with the same crew scheduling role. However someone else is already assigned to an other service of the same crew scheduling role.'))
            }

            // 5: current/given attendee: givenRoleAttERCs.length && eRCs(of role).any(SRSA === `must_not`)
            if (givenRoleAttERCs.length && eventRoleConfig.same_role_same_attendee === 'must_not') {
                validation.isValid = false
                validation.messages.push(this.formatMessage('This service does not allow the same person to take other services with the same crew scheduling role. However the attendee is already assigned to an other service of this crew scheduling role.'))
            }

            // 6: current/given attendee: otherRolesAttERCs.length && eRCs(of role).any(ORSA !== `yes`)
            if (otherRolesAttERCs.length && !!+eventRoleConfig.other_role_same_attendee !== true) {
                // console.log(otherRolesAttERCs)
                validation.isValid = false
                validation.messages.push(this.formatMessage('This service does not allow the same person to take other services with the other crew scheduling roles. However the attendee is already assigned to an other service with an other crew scheduling role.'))
            }
        }

        if (eventRoleConfig.exceedance_action === 'forbidden') {
            const acceptedCount = (await eRC.getEventRoleAttendee(eventRoleConfig, event, {status: 'ACCEPTED'})).length
            if (
                    (!existingAttendee && acceptedCount >= eventRoleConfig.num_required_role_attendee)
                ||  (acceptedCount > eventRoleConfig.num_required_role_attendee))
            {
                validation.isValid = false
                validation.messages.push(this.formatMessage('The required attendee count of this service is already reached and the service configuration does not allow to exceed it.'))
            }
        }

        return validation
    }

    /**
     * validates given role-types config
     *
     * @NOTE attendee roles are not evaluated, you need to pass them if you want to validate them
     * 
     * @param {Calendar.Attendee} attendee
     * @param {CrewScheduling.SchedulingRole} role
     * @param {Calendar.EventType[]} eventTypes
     * @param {Object} configOverrides [configOverrides] optional
     * @returns {Promise<{isValid: boolean, messages: String[]}>, invalidTypes: {typeValidation: {isValid: boolean, messages: String[]}>}, eventType: Calendar.EventType}}
     */
    async validateEventTypesCapability (attendee, role, eventTypes, configOverrides) {
        role = await csRole.getRole(role.id || role)
        const validation = {
            isValid: true,
            messages: [],
        }

        validation.invalidTypes = await async.reduce(eventTypes, [], async (a, eventType) => {
            const typeValidation = await this.validateEventTypeCapability(attendee, role, eventType)
            return typeValidation.isValid ? a : a.concat(Object.assign(typeValidation, { eventType }))
        })

        let same_role_same_attendee = configOverrides.same_role_same_attendee
        let source = this.formatMessage('CrewScheduling config of the event')

        if (! same_role_same_attendee) {
            const map = groupBy(eventTypes, 'same_role_same_attendee')
            forEach(['must_not', 'must', 'may'], value => {
                if (map.hasOwnProperty(value)) {
                    same_role_same_attendee = value
                    source = this.formatMessage('Event type config of { eventTypeNames }', { eventTypeNames: join(map(map[value], 'name'), ', ') })
                    return false
                }
            })
        }

        const msgData = { role, source, eventTypeNames: map(validation.invalidTypes, 'eventType.name').join(', '), messages: map(flatten(map(validation.invalidTypes, 'messages')), (message) => { return `  * ${message}` }).join('\n') }

        if (same_role_same_attendee === 'must_not' && eventTypes.length > 1) {
            validation.isValid = false
            validation.messages.push(this.formatMessage('For the role {role.name} attendee for different event types must not be the same (required by { source }).', msgData))
        } else if (validation.invalidTypes.length) {
            if (same_role_same_attendee === 'must') {
                validation.isValid = false
                validation.messages.push(this.formatMessage('For the role {role.name} attendee must be capable to perform for all of the event types **{ eventTypeNames }** (required by { source }). However following criteria are not met:', msgData) + '\n' + msgData.messages )
            } else if (validation.invalidTypes.length === eventTypes.length) {
                validation.isValid = false
                validation.messages.push(this.formatMessage('For the role {role.name}, attendee is not capable to perform for any of the event types **{ eventTypeNames }**. The following criteria are not met:', msgData) + '\n' + msgData.messages )
            } else {
                validation.isValid = false
                validation.resolvableByRoleSplit = true;
                validation.messages.push(this.formatMessage('For the role {role.name}, attendee is not capable to perform for the event types **{ eventTypeNames }**.', msgData) + '\n' + msgData.messages )
            }
        }

        return validation
    }

    /**
     * validate if given attendee is capable for given role and eventType
     * 
     * @param {Calendar.Attendee} attendee
     * @param {CrewScheduling.SchedulingRole} role
     * @param {Calendar.EventType} eventType
     * @returns {Promise<{isValid: boolean, messages: String[]}>}
     */
    async validateEventTypeCapability (attendee, role, eventType) {
        const typeValidation = {
            isValid: true,
            messages: [],
        }

        // NOTE we need to compute ourseve and can't use AttendeeCapability as we woun't have messages otherwise
        // const eventTypeId = (isObject(eventType) ? eventType.id : eventType) || null
        // const capableContactsMap = await csRole.getEventTypeCapableContactsMap(role)
        // const capableContactIds = capableContactsMap[capableContactsMap.hasOwnProperty(eventTypeId) ? eventTypeId : null]

        const contact = get(attendee, 'data.user_id', attendee.user_id)
        const typeConfig = find(get(await calEventType.getType(eventType?.id), 'cs_role_configs'), (roleConfig) => {
            return roleConfig.scheduling_role.id === role.id
        })
        let listIds = typeConfig?.role_attendee_required_groups?.length ? typeConfig.role_attendee_required_groups : role.role_attendee_required_groups || []
        const operator = typeConfig?.role_attendee_required_groups_operator || role.role_attendee_required_groups_operator || 'OR'

        // expand lists
        listIds = get(listIds, '[0]group.id') ? map(listIds, 'group.id') : listIds
        const requiredList = filter(await csRole.getLists(), list => { return listIds.indexOf(list.id) >= 0 })

        const attendeeLists = filter(requiredList, (list) => {
            const isMember = list.members.indexOf(contact.id) >= 0
            if (operator === 'AND' && !isMember) {
                typeValidation.isValid = false;
                typeValidation.messages.push(this.formatMessage('For event type **{ eventType.name }** attendee needs to be member in group { list.name }.', { list, eventType }))
            }
            return isMember
        })
        if (operator === 'OR' && !attendeeLists.length) {
            typeValidation.isValid = false;
            typeValidation.messages.push(this.formatMessage('For event type **{ eventType.name }** attendee needs to be member in one of the the following groups: { listNames }.', { eventType, listNames: map(requiredList, 'name').join(', ') || 'no groups configured' }))
        }

        return typeValidation
    }

    /**
     * returns all possible eventRoleConfigs respecting constraints from (other) roles which are already taken by this or other attendee
     *
     * NOTE: validates regarding roleConfigs only (_not_ site, freebusy etc.)
     *
     * NOTE: does not check if all/multiple/combinations of the returned configs are also possible at once
     *       but it checks constrains regarding attendee.crewscheduling_roles (CrewScheduling.AttendeeRole[]) of given attendee
     *       to implement a multiselect you have to recheck possible eventRolecConfigs after each (de)selection with adopted attendee
     *
     * RETHIK: we shouldn't come out with zero ERc's this should be prevented by validation
     *
     * @param {Calendar.Attender} attendee
     * @param {Calendar.Event} event
     * @param {CrewScheduling.SchedulingRole} role
     * @returns {Promise<CrewScheduling.EventRoleConfig[]>}
     */
    async getPossibleEventRoleConfigs (attendee, event, role) {
        const eRCs = await eRC.getFromEvent(event, role)
        if (!eRCs.length && role) {
            eRCs.push(await eRC.createFromRoleTypes({role, event_types: []}))
        }

        const attendeeData = attendee.data || attendee
        const attendeeRoles = attendeeData.crewscheduling_roles || []
        const attendeeKeys = await async.map(attendeeRoles, eRC.getRoleTypesKey)

        return async.reduce(eRCs, [], async (memo, eventRoleConfig) => {
            // can't take an eRC twice ;-)
            if (attendeeKeys.indexOf(await eRC.getRoleTypesKey(eventRoleConfig)) >= 0) return memo

            const validation = await this.validateEventRoleConfigCapability(attendee, event, eventRoleConfig)
            // if (!validation.isValid) console.error(validation)
            return memo.concat(validation.isValid ? eventRoleConfig : [])
        })
    }

}
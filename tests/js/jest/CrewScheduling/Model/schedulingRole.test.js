import { find, map } from 'lodash';
import { getCapableContacts, getEventTypeCapableContactsMap } from "CrewScheduling/Model/schedulingRole";
import { getTypes } from "CrewScheduling/Calendar/Model/eventType";
const csRole = require("CrewScheduling/Model/schedulingRole");


beforeAll(() => {

    global.Tine = {}; Tine.Addressbook = Tine.Calendar = Tine.CrewScheduling = {}
    Tine.Addressbook.searchLists = async () => {
        return require('../data/lists.json').result;
    }
    Tine.Calendar.searchEventTypes = async () => {
        return require('../data/eventTypes.json').result;
    }
    Tine.CrewScheduling.searchSchedulingRoles = async () => {
        return require('../data/schedulingRoles.json').result;
    }
    return
});

afterAll(() => {
    delete global.Tine
});

describe('schedulingRole', () => {
    describe('getRoles', () => {
        it('returns all roles with empty arguments', async () => {
            const allRoles = await csRole.getRoles()
            expect(allRoles.length).toBeGreaterThan(10)
            expect(allRoles[0]).toHaveProperty('name')
            expect(allRoles[0]).toHaveProperty('role_attendee_required_groups_operator')
        })
        it('returns subset of roles with given ids', async () => {
            const allRoles = await csRole.getRoles()
            const firstRole = await csRole.getRoles([allRoles[0].id])
            expect(firstRole.length).toBe(1)
            expect(firstRole[0].id).toBe(allRoles[0].id)
        })
    })
    describe('getRole', () => {
        it('returns single role', async () => {
            const allRoles = await csRole.getRoles()
            const firstRole = await csRole.getRole(allRoles[0].id)
            expect(firstRole).toEqual(allRoles[0])
        })
    })
    describe('getEventTypeConfigs', () => {
        it('returns type configs for all configured types', async () => {
            const typeChP = find(await getTypes(), { short_name: 'ChP'})
            const typeEuc = find(await getTypes(), { short_name: 'Euc'})

            const allRoles = await csRole.getRoles()

            const ltgTypeConfigs = await csRole.getEventTypeConfigs(find(allRoles, { name: 'Leitung' }))
            expect(ltgTypeConfigs.length).toBe(15)
            expect(find(ltgTypeConfigs, { event_type: typeChP.id })).toHaveProperty('same_role_same_attendee')
            expect(find(ltgTypeConfigs, { event_type: typeEuc.id })).toBe(undefined)

            expect((await csRole.getEventTypeConfigs(find(allRoles, { name: 'Zelebrant' }))).length).toBe(40)
        })
    })
    describe('getEventTypeCapableContactsMap', () => {
        const getEventTypeCapableContactsMap = () => {
            return new Promise(async resolve => {
                const allRoles = await csRole.getRoles()
                const celMembersMap = await csRole.getEventTypeCapableContactsMap(find(allRoles, { key: 'CEL' }))
                resolve(celMembersMap)
            })
        }

        it('returns base config on null key', async () => {
            const celMembersMap = await getEventTypeCapableContactsMap()

            const membersBase = celMembersMap[null]
            expect(membersBase.length).toEqual(7)
        })
        it('returns special config on event type id key', async () => {
            const celMembersMap = await getEventTypeCapableContactsMap()
            const typeEuc = find(await getTypes(), { short_name: 'Euc'})
            const membersEuc = celMembersMap[typeEuc.id]
            expect(membersEuc.length).toEqual(2)
        })
    })
})
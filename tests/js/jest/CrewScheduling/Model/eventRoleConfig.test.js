import { find, map, filter } from 'lodash';
import * as eRC from 'CrewScheduling/Model/eventRoleConfig';
import { createFromEventTypes } from 'CrewScheduling/Model/eventRoleConfig'
import { getTypes } from "CrewScheduling/Calendar/Model/eventType";
import { getRoles } from "CrewScheduling/Model/schedulingRole";
import Attendee from 'Calendar/Model/Attendee'

import testEvents from '../data/testEvents'
const testUser = require('../data/attendee.json').user.results

beforeAll(() => {
    global.Tine = {}; Tine.Calendar = Tine.CrewScheduling = {}
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


// const event = 
test('event role config is created from given event type "Heilige Messe"', async () => {
    const hlMesse = find(await getTypes(), { short_name: 'HlM' });
    const taufe = find(await getTypes(), { short_name: 'Tau' });
    const config = await eRC.createFromEventTypes([{ event_type: hlMesse }, { event_type: taufe }]);

    const min = find(config, { role: { name: 'Ministranten' } })
    expect(min.exceedance_action).toEqual('none')
    expect(min.shortfall_action).toEqual('none')
    expect(min.num_required_role_attendee).toEqual(2)

    const cel = find(config, { role: { name: 'Zelebrant' } })
    expect(cel.exceedance_action).toEqual('forbidden')
    expect(cel.shortfall_action).toEqual('tentative')
    expect(cel.num_required_role_attendee).toEqual(1)
    expect(cel.event_types.length).toEqual(2)
    expect(cel.same_role_same_attendee).toEqual('must')
    expect(cel.other_role_same_attendee).toEqual(false)
    expect(map(cel.event_types, 'short_name').sort()).toEqual(['HlM', 'Tau'])
})

describe('getFromEvent', () => {
    const event = find(testEvents, { data: { summary: 'Heilige Messe mit Taufe' }}).copy()
    it('is created for fresh events', async() => {
        const eRCs = await eRC.getFromEvent(event);
        expect(eRCs.length).toEqual(7)
        expect(filter(eRCs, {role: {key: 'CEL'}}).length).toEqual(1)
    })

    it('is returned as is for events where is it already set', async() => {
        let eRCs = await eRC.getFromEvent(event);
        eRCs[7] = Object.assign({}, eRCs[0], {role: find(await getRoles(), {key: 'CCE'})});

        eRCs = await eRC.getFromEvent(event);
        expect(eRCs.length).toEqual(8)
    })

    it('is filtered by given roles', async() => {
        const eRCs = await (eRC.getFromEvent(event, find(await getRoles(), {key: 'CEL'})))
        expect(eRCs.length).toEqual(1)
    })
})

describe('getEventRoleAttendeeCount', () => {

    it('returns count for given roleConfig and event', async() => {
        const cel = find(await getRoles(), {key: 'CEL'})
        const cozel = find(await getRoles(), {key: 'CCE'})
        const min = find(await getRoles(), {key: 'MIN'})
        const hlMesse = find(await getTypes(), { short_name: 'HlM' })
        const taufe = find(await getTypes(), { short_name: 'Tau' })

        const event = find(testEvents, { data: { summary: 'Heilige Messe mit Taufe' }}).copy()
        const eRCs = await eRC.getFromEvent(event, cel);
        eRCs[1] = Object.assign({}, eRCs[0], {role: cozel})
        eRCs[2] = Object.assign({}, eRCs[1], {event_types: [taufe]})
        eRCs[3] = Object.assign({}, eRCs[1], {role: min})

        const schwesterSchwentine = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Schwester Schwentine"})})
        const pfarrerPfeiffer = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Pfarrer Pfeiffer"})})
        const dirkZivilli = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Dirk Zivilli"})})
        event.set('attendee', [
            { user_type: 'user', 'user_id': pfarrerPfeiffer,     crewscheduling_roles: [{ role: cel, event_types: [hlMesse, taufe] }] },
            { user_type: 'user', 'user_id': schwesterSchwentine, crewscheduling_roles: [{ role: cozel, event_types: [hlMesse, taufe] }] },
            { user_type: 'user', 'user_id': dirkZivilli,         crewscheduling_roles: [{ role: cozel, event_types: [hlMesse, taufe] }] },
            { user_type: 'user', 'user_id': 4, crewscheduling_roles: [{ role: cozel, event_types: [taufe] }] },
            { user_type: 'user', 'user_id': 5, crewscheduling_roles: [{ role: min, event_types: [hlMesse, taufe] }] }
        ])

        expect((await eRC.getEventRoleAttendee(eRCs[0], event)).length).toEqual(1) // pfarrerPfeiffer
        expect((await eRC.getEventRoleAttendee(eRCs[1], event)).length).toEqual(2) // schwesterSchwentine, dirkZivilli
        expect((await eRC.getEventRoleAttendee(eRCs[2], event)).length).toEqual(1) // 4
        expect((await eRC.getEventRoleAttendee(eRCs[3], event)).length).toEqual(1) // 5
    })

    describe('getEventRoleConfigsOfAttendee', () => {
        it('gets eventRoleConfigs of given attendee grouped by given and all other roles', async() => {
            const hlMesse = find(await getTypes(), { short_name: 'HlM' })
            const taufe = find(await getTypes(), { short_name: 'Tau' })

            const event = find(testEvents, { data: { summary: 'Heilige Messe mit Taufe' }}).copy()

            const schwesterSchwentine = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Schwester Schwentine"})})
            const pfarrerPfeiffer = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Pfarrer Pfeiffer"})})
            const dirkZivilli = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Dirk Zivilli"})})
            event.set('attendee', [
                { user_type: 'user', 'user_id': pfarrerPfeiffer,     crewscheduling_roles: [
                    { role: find(await getRoles(), {key: 'CEL'}), event_types: [hlMesse, taufe] }, // exact match from eventRoleConfigs
                    { role: find(await getRoles(), {key: 'ORG'}), event_types: [hlMesse, taufe] }  // creates new upstream config
                ] },
                { user_type: 'user', 'user_id': schwesterSchwentine, crewscheduling_roles: [{ role: find(await getRoles(), {key: 'CCE'}), event_types: [hlMesse, taufe] }] },
                // { user_type: 'user', 'user_id': dirkZivilli,         crewscheduling_roles: [{ role: find(await getRoles(), {key: 'CCE'}), event_types: [hlMesse, taufe] }] },
                // { user_type: 'user', 'user_id': 4, crewscheduling_roles: [{ role: find(await getRoles(), {key: 'CCE'}), event_types: [taufe] }] },
                // { user_type: 'user', 'user_id': 5, crewscheduling_roles: [{ role: find(await getRoles(), {key: 'MIN'}), event_types: [hlMesse, taufe] }] }
            ])

            const eRCs = await eRC.getFromEvent(event, find(await getRoles(), {key: 'CEL'}));

            const { givenRoleAttERCs, otherRolesAttERCs } = await eRC.getEventRoleConfigsOfAttendee(eRCs, event.get('attendee')[0], find(await getRoles(), {key: 'CEL'}))
            expect(givenRoleAttERCs.length).toEqual(1)
            expect(givenRoleAttERCs[0].role.key).toEqual('CEL')
            expect(otherRolesAttERCs.length).toEqual(1)
            expect(otherRolesAttERCs[0].role.key).toEqual('ORG')
            expect(otherRolesAttERCs[0].other_role_same_attendee).toEqual(false) // ORG config overwride

        })
    })
    // describe.only('getPossibleEventRoleConfigs', () => {
    //     it('returns all possible eventRoleConfigs respecting constraints from (other) roles which are already taken by this or other attendee', async() => {
    //         const event = find(testEvents, { data: { summary: 'Heilige Messe mit Taufe' }}).copy()
    //
    //         // add LTG for Hlm and LTG for Tau
    //         const eRCs = await eRC.getFromEvent(event, find(await getRoles(), {key: 'CEL'}));
    //         eRCs.push(Object.assign({}, eRCs[0], {role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'HlM' }) ]}))
    //         eRCs.push(Object.assign({}, eRCs[0], {role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'Tau' }) ]}))
    //         event.set('cs_roles_configs', eRCs)
    //
    //         // add LTG to this event -> which one?
    //         const dirkZivilli = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Dirk Zivilli"})})
    //         let choices = await eRC.getPossibleEventRoleConfigs(dirkZivilli, event, find(await getRoles(), {key: 'LTG'}))
    //         expect(choices[0].event_types[0].short_name).toEqual('HlM')
    //         expect(choices[1].event_types[0].short_name).toEqual('Tau')
    //
    //         // now add him for HlM
    //         dirkZivilli.set('crewscheduling_roles', [{ role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'HlM' }) ] }])
    //         event.set('attendee', [dirkZivilli])
    //
    //         // LTG for HlM and Tau is allowed at the same time!
    //         choices = await eRC.getPossibleEventRoleConfigs(dirkZivilli, event, find(await getRoles(), {key: 'LTG'}))
    //         expect(choices[0].event_types[0].short_name).toEqual('Tau')
    //
    //         // other_role_same_attendee of LTG prohibits MIN (and other roles)
    //         choices = await eRC.getPossibleEventRoleConfigs(dirkZivilli, event, find(await getRoles(), {key: 'MIN'}))
    //         expect(choices.length).toEqual(0)
    //
    //         // same_role_same_attendee: 'must' disallowes for others
    //         const pfarrerPfeiffer = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Pfarrer Pfeiffer"})})
    //         choices = await eRC.getPossibleEventRoleConfigs(pfarrerPfeiffer, event, find(await getRoles(), {key: 'LTG'}))
    //         expect(choices.length).toEqual(0)
    //
    //         eRCs.push(Object.assign({}, eRCs[0], {role: find(await getRoles(), {key: 'MIN'}), event_types: [ find(await getTypes(), { short_name: 'HlM' }) ], same_role_same_attendee: 'may', other_role_same_attendee: false}))
    //         choices = await eRC.getPossibleEventRoleConfigs(pfarrerPfeiffer, event, find(await getRoles(), {key: 'MIN'}))
    //         expect(choices.length).toEqual(1)
    //
    //         const schwesterSchwentine = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Schwester Schwentine"}), status: 'ACCEPTED'})
    //         choices = await eRC.getPossibleEventRoleConfigs(schwesterSchwentine, event, find(await getRoles(), {key: 'MIN'}))
    //         expect(choices.length).toEqual(1)
    //
    //         // now add her for MIN
    //         schwesterSchwentine.set('crewscheduling_roles', [{ role: find(await getRoles(), {key: 'MIN'}), event_types: [ find(await getTypes(), { short_name: 'HlM' }) ] }])
    //         event.set('attendee', [dirkZivilli, schwesterSchwentine])
    //
    //         // pfarrerPfeiffer is forbidden by exedance action
    //         choices = await eRC.getPossibleEventRoleConfigs(pfarrerPfeiffer, event, find(await getRoles(), {key: 'MIN'}))
    //         expect(choices.length).toEqual(0)
    //     })
    //
    //     it('respects ORSA', async() => {
    //         const event = find(testEvents, { data: { summary: 'Fortbildung mit Eucharistiefeier' }}).copy()
    //
    //         const paulEhrlich = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Paul Ehrlich"})})
    //
    //         // add him for LTG
    //         paulEhrlich.set('crewscheduling_roles', [{ role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'Fbi' }) ] }])
    //         event.set('attendee', [paulEhrlich])
    //
    //         // other_role_same_attendee = false of CEL should prohibit choice
    //         let choices = await eRC.getPossibleEventRoleConfigs(paulEhrlich, event, find(await getRoles(), {key: 'CEL'}))
    //         expect(choices.length).toEqual(0)
    //     })
    // })
})

import { find, map } from 'lodash';
import * as eRC from 'CrewScheduling/Model/eventRoleConfig'
import Attendee from 'Calendar/Model/Attendee'
import AttendeeValidation from 'CrewScheduling/Model/AttendeeValidation'
import formatMessage from 'format-message'
import { getTypes } from "CrewScheduling/Calendar/Model/eventType";
import { getRoles } from "CrewScheduling/Model/schedulingRole";

import testEvents from '../data/testEvents'

formatMessage.setup({
    missingTranslation: 'ignore'
});

const validator = new AttendeeValidation({formatMessage})
const testUser = require('../data/attendee.json').user.results

beforeAll(async () => {
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

describe('validate', () => {
    it('validates site, weekday, freeBusy and capabilitis of new members', async () => {
        const attendee = new Attendee({user_type: 'user', user_id: {... find(testUser, { n_fn: "Michael Eins"})}})
        attendee.data.user_id.customfields.favorite_day = "MO"


        const event = find(testEvents, event => event.data.event_site && event.data.event_site.id !== attendee.data.user_id.sites[0].site.id && event.data.dtstart.getDay !== 1)
        attendee.data.user_id.busyIds = [event.id]

        const eventRoleConfigs = await eRC.getFromEvent(event)
        const validation = await validator.validate(attendee, event, eventRoleConfigs[0].role)
        // console.log(validation)
        expect(validation).toHaveProperty('isValid', false)
        expect(validation.messages).toContain('The attendee is not assigned to the event site')
        expect(validation.messages).toContain('The attendee favorite days do not contain the weekday of the event')
        expect(validation.messages).toContain('The attendee is busy during the event')
        expect(validation.messages.pop()).toMatch(/must be capable to perform/)
    })
})

describe('validateSite', () => {
    describe('is valid if event has no site', () => {
        const event = find(testEvents, { data: { event_site: null }})
        it('for attendee without sites set', async () => {
            const attendee = new Attendee({user_type: 'user', user_id: find(testUser, { sites: []})})
            expect(await validator.validateSite(attendee, event)).toHaveProperty('isValid', true)
        })

        it('for attendee with one ore more sites set', async () => {
            const attendee = new Attendee({user_type: 'user', user_id: find(testUser, (user) => user.sites.length > 0)})
            expect(await validator.validateSite(attendee, event)).toHaveProperty('isValid', true)
        })
    })

    describe('is valid if event has site', () => {
        const event = find(testEvents, event => event.data.event_site)
        it('for attendee without sites set', async () => {
            const attendee = new Attendee({user_type: 'user', user_id: find(testUser, { sites: []})})
            expect(await validator.validateSite(attendee, event)).toHaveProperty('isValid', true)
        })

        it('for attendee with matching site set', async () => {
            const attendee = new Attendee({user_type: 'user', user_id: find(testUser, (user) => map(user.sites, 'site.id').indexOf(event.data.event_site.id) >= 0)})
            expect(await validator.validateSite(attendee, event)).toHaveProperty('isValid', true)
        })
    })

    describe('is invalid if event has site', () => {
        it('for attendee with one ore more other sites set', async () => {
            const attendee = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Michael Eins"})})
            const event = find(testEvents, event => event.data.event_site && event.data.event_site.id !== attendee.data.user_id.sites[0].site.id)

            expect(await validator.validateSite(attendee, event)).toHaveProperty('isValid', false)
        })
    })
})

describe('validateEventRoleConfigCapability', () => {
    describe('validates test events', () => {
        describe('Fortbildung mit Eucharistiefeier', () => {
            const event = find(testEvents, { data: { summary: 'Fortbildung mit Eucharistiefeier' }})
            const schwesterSchwentine = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Schwester Schwentine"})})
            const pfarrerPfeiffer = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Pfarrer Pfeiffer"})})

            // @TODO add 'Wortgottesfeier' to demodata and validate that CEL might be performed by Dirk

            it('LTG may be performord by "Schwester Schwentine" (any user of "0000 Users")', async () => {
                const eventRoleConfigs = await eRC.getFromEvent(event)
                const ltgConfig = find(eventRoleConfigs, { role: { key: 'LTG' } })

                const validationResult = await validator.validateEventRoleConfigCapability(schwesterSchwentine, event, ltgConfig)
                expect(validationResult).toHaveProperty('isValid', true)
            })

            it('CEL may be performord by "Pfarrer, Pfeiffer" (any user of "0110 - Pfarrer" or "0120 Pastor - Pater - Kaplan")', async () => {
                const eventRoleConfigs = await eRC.getFromEvent(event)
                const celConfig = find(eventRoleConfigs, { role: { key: 'CEL' } })

                expect(await validator.validateEventRoleConfigCapability(pfarrerPfeiffer, event, celConfig)).toHaveProperty('isValid', true)
            })

            it('CEL may NOT be performord by "Schwester Schwentine"', async () => {
                const eventRoleConfigs = await eRC.getFromEvent(event)
                const celConfig = find(eventRoleConfigs, { role: { key: 'CEL' } })

                let validationResult = await validator.validateEventRoleConfigCapability(schwesterSchwentine, event, celConfig)
                expect(validationResult).toHaveProperty('isValid', false)
            })
        })

        // diakon kann taufe, aber nicht HM, CEL ist nicht splitbar -> pech
        describe('Heilige Messe mit Taufe', () => {
            const event = find(testEvents, { data: { summary: 'Heilige Messe mit Taufe' }})
            const dirkZivilli = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Dirk Zivilli"})})

            it('CEL may NOT be performord by "Dirk Zivilli" not even by splitting', async () => {
                const eventRoleConfigs = await eRC.getFromEvent(event)
                const celConfig = find(eventRoleConfigs, { role: { key: 'CEL' } })

                let validationResult = await validator.validateEventRoleConfigCapability(dirkZivilli, event, celConfig)
                expect(validationResult).toHaveProperty('isValid', false)
                expect(validationResult).not.toHaveProperty('resolvableByRoleSplit', true)
            })
        })
    })

    describe('finds constructed edge cases', () => {

        let validationMessage1 = 'The attendee is already assigned to an other service of the same crew scheduling role. This service does not allow the same person to take another service with the same crew scheduling role.'
        it(validationMessage1, async () => {
            const event = find(testEvents, { data: { summary: 'Heilige Messe mit Taufe' }}).copy()

            // add testing eRCs
            const eRCs = await eRC.getFromEvent(event, find(await getRoles(), {key: 'CEL'}));
            eRCs.push(Object.assign({}, eRCs[0], {same_role_same_attendee: 'must_not', role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'Tau' }) ]}))
            eRCs.push(Object.assign({}, eRCs[0], {role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'HlM' }) ]}))
            event.set('cs_roles_configs', eRCs)

            const memberDirkZivilli = new Attendee({user_type: 'user', user_id: {... find(testUser, { n_fn: "Dirk Zivilli"}) }})

            // add dirkZivilli as LTG for Tau which prohibits further LTG roles
            const attendeeDirkZivilli = memberDirkZivilli.copy().set('crewscheduling_roles', [{ role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'Tau' }) ] }])
            event.set('attendee', [attendeeDirkZivilli])

            let validationResult = await validator.validateEventRoleConfigCapability(memberDirkZivilli, event, eRCs[eRCs.length-2])
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain('The person is already assigned to this service.')

            validationResult = await validator.validateEventRoleConfigCapability(attendeeDirkZivilli, event, eRCs[eRCs.length-2])
            expect(validationResult).toHaveProperty('isValid', true)

            validationResult = await validator.validateEventRoleConfigCapability(memberDirkZivilli, event, eRCs[eRCs.length-1])
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage1)

            // finally add the invalid service an validate existing attendee
            attendeeDirkZivilli.data.crewscheduling_roles.push({ role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'HlM' }) ] })
            validationResult = await validator.validateEventRoleConfigCapability(attendeeDirkZivilli, event, eRCs[eRCs.length-1])
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage1)


            validationResult = await validator.validateEventRoleConfigCapability(attendeeDirkZivilli, event, eRCs[eRCs.length-2])
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage5)
        })

        let validationMessage2 = 'The attendee is already assigned to an other service which does not allow to perform additional services of other crew scheduling roles.'
        it(validationMessage2, async () => {
            const event = find(testEvents, { data: { summary: 'Heilige Messe mit Taufe' }}).copy()

            const eRCs = await eRC.getFromEvent(event);
            const memberPfarrerPfeiffer = new Attendee({user_type: 'user', user_id: {... find(testUser, { n_fn: "Pfarrer Pfeiffer"}) }})

            const attendeePfarrerPfeiffer = memberPfarrerPfeiffer.copy().set('crewscheduling_roles', [{ role: find(await getRoles(), {key: 'CEL'}), event_types: [ find(await getTypes(), { short_name: 'HlM' }), find(await getTypes(), { short_name: 'Tau' }) ] }])
            event.set('attendee', [attendeePfarrerPfeiffer])

            let validationResult = await validator.validateEventRoleConfigCapability(attendeePfarrerPfeiffer, event, find(eRCs, { role: { key: 'CEL'} }))
            expect(validationResult).toHaveProperty('isValid', true)

            validationResult = await validator.validateEventRoleConfigCapability(memberPfarrerPfeiffer, event, find(eRCs, { role: { key: 'MIN'} }))
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage2)

            // add the invalid service anyway and make sure validation finds it
            attendeePfarrerPfeiffer.data.crewscheduling_roles.push(find(eRCs, { role: { key: 'MIN'} }))
            validationResult = await validator.validateEventRoleConfigCapability(attendeePfarrerPfeiffer, event, find(eRCs, { role: { key: 'MIN'} }))
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage2)

            validationResult = await validator.validateEventRoleConfigCapability(attendeePfarrerPfeiffer, event, find(eRCs, { role: { key: 'CEL'} }))
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage6)
        })

        let validationMessage3 = 'An other attendee already performs a service with the same crew scheduling role and it requires that all services of that role are performed by the same person.'
        it(validationMessage3, async () => {
            const event = find(testEvents, { data: { summary: 'Heilige Messe mit Taufe' }}).copy()

            // add testing eRCs
            const eRCs = await eRC.getFromEvent(event, find(await getRoles(), {key: 'CEL'}));
            eRCs.push(Object.assign({}, eRCs[0], {role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'Tau' }) ]}))
            eRCs.push(Object.assign({}, eRCs[0], {same_role_same_attendee: 'may', role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'HlM' }) ]}))
            event.set('cs_roles_configs', eRCs)

            const memberDirkZivilli = new Attendee({user_type: 'user', user_id: {... find(testUser, { n_fn: "Dirk Zivilli"}) }})

            // add dirkZivilli as LTG for Tau which prohibits further LTG roles
            const attendeeDirkZivilli = memberDirkZivilli.copy().set('crewscheduling_roles', [{ role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'Tau' }) ] }])
            event.set('attendee', [attendeeDirkZivilli])


            let validationResult = await validator.validateEventRoleConfigCapability(attendeeDirkZivilli, event, eRCs[eRCs.length-2])
            expect(validationResult).toHaveProperty('isValid', true)

            validationResult = await validator.validateEventRoleConfigCapability(attendeeDirkZivilli, event, eRCs[eRCs.length-1])
            expect(validationResult).toHaveProperty('isValid', true)

            const memberPfarrerPfeiffer = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Pfarrer Pfeiffer"})})
            validationResult = await validator.validateEventRoleConfigCapability(memberPfarrerPfeiffer, event, eRCs[eRCs.length-1])
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage3)

            // add the invalid service
            const attendeePfarrerPfeiffer = memberPfarrerPfeiffer.copy().set('crewscheduling_roles', [eRCs[eRCs.length-1]])
            event.data.attendee.push(attendeePfarrerPfeiffer)

            // validate attendee
            validationResult = await validator.validateEventRoleConfigCapability(attendeePfarrerPfeiffer, event, eRCs[eRCs.length-1])
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage3)

            // there is an other attendee with LTG but my own LTG config requires same_role_same_attendee: 'must' (case 4)
            validationResult = await validator.validateEventRoleConfigCapability(attendeeDirkZivilli, event, { role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'Tau' }) ] })
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage4)

        })

        let validationMessage4 = 'This service requires the same person to perform all services with the same crew scheduling role. However someone else is already assigned to an other service of the same crew scheduling role.'
        it(validationMessage4, async () => {
            const event = find(testEvents, { data: { summary: 'Heilige Messe mit Taufe' }}).copy()

            // add testing eRCs
            const eRCs = await eRC.getFromEvent(event, find(await getRoles(), {key: 'CEL'}));
            eRCs.push(Object.assign({}, eRCs[0], {same_role_same_attendee: 'may', role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'Tau' }) ]}))
            eRCs.push(Object.assign({}, eRCs[0], {role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'HlM' }) ]}))
            event.set('cs_roles_configs', eRCs)

            // add dirkZivilli as LTG for Tau which prohibits further LTG roles
            const memberDirkZivilli = new Attendee({user_type: 'user', user_id: {... find(testUser, { n_fn: "Dirk Zivilli"}) }})

            // add dirkZivilli as LTG for Tau which prohibits further LTG roles
            const attendeeDirkZivilli = memberDirkZivilli.copy().set('crewscheduling_roles', [{ role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'Tau' }) ] }])
            event.set('attendee', [attendeeDirkZivilli])

            let validationResult = await validator.validateEventRoleConfigCapability(attendeeDirkZivilli, event, eRCs[eRCs.length-2])
            expect(validationResult).toHaveProperty('isValid', true)

            validationResult = await validator.validateEventRoleConfigCapability(attendeeDirkZivilli, event, eRCs[eRCs.length-1])
            expect(validationResult).toHaveProperty('isValid', true)

            const memberPfarrerPfeiffer = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Pfarrer Pfeiffer"})})
            validationResult = await validator.validateEventRoleConfigCapability(memberPfarrerPfeiffer, event, eRCs[eRCs.length-1])
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage4)

            // add the invalid service
            const attendeePfarrerPfeiffer = memberPfarrerPfeiffer.copy().set('crewscheduling_roles', [eRCs[eRCs.length-1]])
            event.data.attendee.push(attendeePfarrerPfeiffer)

            // validate attendee
            validationResult = await validator.validateEventRoleConfigCapability(attendeePfarrerPfeiffer, event, eRCs[eRCs.length-1])
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage4)

            validationResult = await validator.validateEventRoleConfigCapability(attendeeDirkZivilli, event, { role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'Tau' }) ] })
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage3) // case 3!
        })

        let validationMessage5 = 'This service does not allow the same person to take other services with the same crew scheduling role. However the attendee is already assigned to an other service of this crew scheduling role.'
        it(validationMessage5, async () => {
            const event = find(testEvents, { data: { summary: 'Heilige Messe mit Taufe' }}).copy()

            // add testing eRCs
            const eRCs = await eRC.getFromEvent(event, find(await getRoles(), {key: 'CEL'}));
            eRCs.push(Object.assign({}, eRCs[0], {same_role_same_attendee: 'must_not', role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'Tau' }) ]}))
            eRCs.push(Object.assign({}, eRCs[0], {role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'HlM' }) ]}))
            event.set('cs_roles_configs', eRCs)

            // add dirkZivilli as LTG for HlM
            const memberDirkZivilli = new Attendee({user_type: 'user', user_id: {... find(testUser, { n_fn: "Dirk Zivilli"}) }})

            // add dirkZivilli as LTG for Tau which prohibits further LTG roles
            const attendeeDirkZivilli = memberDirkZivilli.copy().set('crewscheduling_roles', [{ role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'HlM' }) ] }])
            event.set('attendee', [attendeeDirkZivilli])

            let validationResult = await validator.validateEventRoleConfigCapability(attendeeDirkZivilli, event, eRCs[eRCs.length-1])
            expect(validationResult).toHaveProperty('isValid', true)

            validationResult = await validator.validateEventRoleConfigCapability(memberDirkZivilli, event, eRCs[eRCs.length-2])
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage5)

            // add the invlaid service
            attendeeDirkZivilli.data.crewscheduling_roles.push(eRCs[eRCs.length-2])
            validationResult = await validator.validateEventRoleConfigCapability(attendeeDirkZivilli, event, eRCs[eRCs.length-2])
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage5)

            validationResult = await validator.validateEventRoleConfigCapability(attendeeDirkZivilli, event, eRCs[eRCs.length-1])
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage1) // case 1!
        })

        let validationMessage6 = 'This service does not allow the same person to take other services with the other crew scheduling roles. However the attendee is already assigned to an other service with an other crew scheduling role.'
        it(validationMessage6, async () => {
            const event = find(testEvents, { data: { summary: 'Heilige Messe mit Taufe' }}).copy()

            const eRCs = await eRC.getFromEvent(event);
            const memberPfarrerPfeiffer = new Attendee({user_type: 'user', user_id: {... find(testUser, { n_fn: "Pfarrer Pfeiffer"}) }})

            const attendeePfarrerPfeiffer = memberPfarrerPfeiffer.copy().set('crewscheduling_roles', [{ role: find(await getRoles(), {key: 'MIN'}), event_types: [ find(await getTypes(), { short_name: 'HlM' }), find(await getTypes(), { short_name: 'Tau' }) ] }])
            event.set('attendee', [attendeePfarrerPfeiffer])

            let validationResult = await validator.validateEventRoleConfigCapability(memberPfarrerPfeiffer, event, find(eRCs, { role: { key: 'MIN'} }))
            expect(validationResult).toHaveProperty('isValid', true)

            validationResult = await validator.validateEventRoleConfigCapability(memberPfarrerPfeiffer, event, find(eRCs, { role: { key: 'CEL'} }))
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage6)

            // add the invalid service
            attendeePfarrerPfeiffer.data.crewscheduling_roles.push(find(eRCs, { role: { key: 'CEL'} }))

            validationResult = await validator.validateEventRoleConfigCapability(attendeePfarrerPfeiffer, event, { role: find(await getRoles(), {key: 'CEL'}), event_types: [ find(await getTypes(), { short_name: 'HlM' }), find(await getTypes(), { short_name: 'Tau' }) ] })
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage6)

            validationResult = await validator.validateEventRoleConfigCapability(attendeePfarrerPfeiffer, event, { role: find(await getRoles(), {key: 'MIN'}), event_types: [ find(await getTypes(), { short_name: 'HlM' }), find(await getTypes(), { short_name: 'Tau' }) ] })
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage2)
        })

        const validationMessage7 = 'The required attendee count of this service is already reached and the service configuration does not allow to exceed it.'
        it(validationMessage7, async () => {
            const event = find(testEvents, { data: { summary: 'Heilige Messe mit Taufe' }}).copy()

            // const eRCs = [Object.assign({... find(await eRC.getFromEvent(event), { role: { key: 'BLU'} })}, {num_required_role_attendee: 1, exceedance_action: 'forbidden'})]
            // event.set('cs_roles_configs', eRCs)
            const eRCs = { ... await eRC.getFromEvent(event, find(await getRoles(), {key: 'MIN'})) }
            Object.assign(eRCs[0], {num_required_role_attendee: 1, exceedance_action: 'forbidden', role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'Tau' }) ]})
            event.set('cs_roles_configs', eRCs)

            const memberPfarrerPfeiffer = new Attendee({user_type: 'user', user_id: {... find(testUser, { n_fn: "Pfarrer Pfeiffer"}) }})

            const attendeePfarrerPfeiffer = memberPfarrerPfeiffer.copy()
                .set('crewscheduling_roles', [find(eRCs, { role: { key: 'LTG'} })])
                .set('status', 'ACCEPTED')
            event.set('attendee', [attendeePfarrerPfeiffer])

            let validationResult = await validator.validateEventRoleConfigCapability(attendeePfarrerPfeiffer, event, find(eRCs, { role: { key: 'LTG'} }))
            expect(validationResult).toHaveProperty('isValid', true)

            // if there are alrady enough accepted member don't allow to add new members when exceedance action is forbidden
            const memberPaulEhrlich = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Paul Ehrlich"})})
            validationResult = await validator.validateEventRoleConfigCapability(memberPaulEhrlich, event, find(eRCs, { role: { key: 'LTG'} }))
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage7)

            // add invalid attendee
            const attendeePaulEhrlich = memberPaulEhrlich.copy()
                .set('crewscheduling_roles', [find(eRCs, { role: { key: 'LTG'} })])
            event.data.attendee.push(attendeePaulEhrlich)

            // as paul did not yet accept it's a valid member though...
            validationResult = await validator.validateEventRoleConfigCapability(attendeePfarrerPfeiffer, event, find(eRCs, { role: { key: 'LTG'} }))
            expect(validationResult).toHaveProperty('isValid', true)
            validationResult = await validator.validateEventRoleConfigCapability(attendeePaulEhrlich, event, find(eRCs, { role: { key: 'LTG'} }))
            expect(validationResult).toHaveProperty('isValid', true)

            // after paul accepted both attendee are invalid
            attendeePaulEhrlich.set('status', 'ACCEPTED')
            validationResult = await validator.validateEventRoleConfigCapability(attendeePfarrerPfeiffer, event, find(eRCs, { role: { key: 'LTG'} }))
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage7)
            validationResult = await validator.validateEventRoleConfigCapability(attendeePaulEhrlich, event, find(eRCs, { role: { key: 'LTG'} }))
            expect(validationResult).toHaveProperty('isValid', false)
            expect(validationResult.messages.length).toEqual(1)
            expect(validationResult.messages).toContain(validationMessage7)
        })
    })

})

describe('getPossibleEventRoleConfigs', () => {
    it('returns all possible eventRoleConfigs respecting constraints from (other) roles which are already taken by this or other attendee', async() => {
        const event = find(testEvents, { data: { summary: 'Heilige Messe mit Taufe' }}).copy()

        // add LTG for Hlm and LTG for Tau
        const eRCs = await eRC.getFromEvent(event, find(await getRoles(), {key: 'CEL'}));
        eRCs.push(Object.assign({}, eRCs[0], {role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'HlM' }) ]}))
        eRCs.push(Object.assign({}, eRCs[0], {role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'Tau' }) ]}))
        event.set('cs_roles_configs', eRCs)

        // add LTG to this event -> which one?
        const dirkZivilli = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Dirk Zivilli"})})
        let choices = await validator.getPossibleEventRoleConfigs(dirkZivilli, event, find(await getRoles(), {key: 'LTG'}))
        expect(choices[0].event_types[0].short_name).toEqual('HlM')
        expect(choices[1].event_types[0].short_name).toEqual('Tau')

        // now add him for HlM
        dirkZivilli.set('crewscheduling_roles', [{ role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'HlM' }) ] }])
        event.set('attendee', [dirkZivilli])

        // LTG for HlM and Tau is allowed at the same time!
        choices = await validator.getPossibleEventRoleConfigs(dirkZivilli, event, find(await getRoles(), {key: 'LTG'}))
        expect(choices[0].event_types[0].short_name).toEqual('Tau')

        // other_role_same_attendee of LTG prohibits MIN (and other roles)
        choices = await validator.getPossibleEventRoleConfigs(dirkZivilli, event, find(await getRoles(), {key: 'MIN'}))
        expect(choices.length).toEqual(0)

        // same_role_same_attendee: 'must' disallowes for others
        const pfarrerPfeiffer = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Pfarrer Pfeiffer"})})
        choices = await validator.getPossibleEventRoleConfigs(pfarrerPfeiffer, event, find(await getRoles(), {key: 'LTG'}))
        expect(choices.length).toEqual(0)

        eRCs.push(Object.assign({}, eRCs[0], {role: find(await getRoles(), {key: 'MIN'}), event_types: [ find(await getTypes(), { short_name: 'HlM' }) ], same_role_same_attendee: 'may', other_role_same_attendee: false}))
        choices = await validator.getPossibleEventRoleConfigs(pfarrerPfeiffer, event, find(await getRoles(), {key: 'MIN'}))
        expect(choices.length).toEqual(1)

        const schwesterSchwentine = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Schwester Schwentine"}), status: 'ACCEPTED'})
        choices = await validator.getPossibleEventRoleConfigs(schwesterSchwentine, event, find(await getRoles(), {key: 'MIN'}))
        expect(choices.length).toEqual(1)

        // now add her for MIN
        schwesterSchwentine.set('crewscheduling_roles', [{ role: find(await getRoles(), {key: 'MIN'}), event_types: [ find(await getTypes(), { short_name: 'HlM' }) ] }])
        event.set('attendee', [dirkZivilli, schwesterSchwentine])

        // pfarrerPfeiffer is forbidden by exedance action
        choices = await validator.getPossibleEventRoleConfigs(pfarrerPfeiffer, event, find(await getRoles(), {key: 'MIN'}))
        expect(choices.length).toEqual(0)
    })

    it('respects ORSA', async() => {
        const event = find(testEvents, { data: { summary: 'Fortbildung mit Eucharistiefeier' }}).copy()

        const paulEhrlich = new Attendee({user_type: 'user', user_id: find(testUser, { n_fn: "Paul Ehrlich"})})

        // add him for LTG
        paulEhrlich.set('crewscheduling_roles', [{ role: find(await getRoles(), {key: 'LTG'}), event_types: [ find(await getTypes(), { short_name: 'Fbi' }) ] }])
        event.set('attendee', [paulEhrlich])

        // other_role_same_attendee = false of CEL should prohibit choice
        let choices = await validator.getPossibleEventRoleConfigs(paulEhrlich, event, find(await getRoles(), {key: 'CEL'}))
        expect(choices.length).toEqual(0)
    })
})
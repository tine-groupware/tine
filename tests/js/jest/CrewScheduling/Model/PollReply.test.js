import PollReply  from 'CrewScheduling/Model/PollReply'

const modelConfigs = require('../data/modelConfigs.json')
PollReply.setModelConfiguration(modelConfigs.PollReply)


describe('PollReply', () => {
    it('supports lasy model init', () => {
        const pollReply = new PollReply({
            poll_participant_id: 'poll_participant_id',
            event_id: 'event_id',
            status: 'ACCEPTED'
        }, 1)

        expect(pollReply.get('status')).toEqual('ACCEPTED')
    })
})
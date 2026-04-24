import Event from 'Calendar/Model/Event'
import Attendee from 'Calendar/Model/Attendee'

const modelConfigs = require('./modelConfigs.json')
Event.setModelConfiguration(modelConfigs.Event)
Attendee.setModelConfiguration(modelConfigs.Attender)

const testEvents = require('./events.json').result.results.map((eventData) => {
    return Event.setFromJson(eventData)
})

export default testEvents
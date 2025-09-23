import Record from 'data/Record'
import Event  from 'Calendar/Model/Event'

const testEvents = require('./events.json').result.results.map((eventData) => {
    return Record.setFromJson(eventData, Event)
})

export default testEvents
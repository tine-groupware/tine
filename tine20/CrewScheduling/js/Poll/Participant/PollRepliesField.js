/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import { get, find, each, groupBy } from 'lodash'
import { HTMLProxy, Expression } from 'twingEnv.es6'
import { htmlEncode } from 'Ext/util/Format'
import asString from 'ux/asString'
import Stringable from 'ux/Stringable'
import PollReply from "../../Model/PollReply"
import { dateTimeRenderer } from 'common'
import Event from 'Calendar/Model/Event'

Promise.all([Tine.Tinebase.appMgr.isInitialised('CrewScheduling'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('CrewScheduling')

    const PollRepliesField = Ext.extend(Tine.widgets.grid.PickerGridPanel, {
        pollEventsCache: null,
        onRowDblClick: () => {},
        onRowContextMenu: () => {},
        initComponent() {
            this.pollEventsCache = {}
            this.supr().initComponent.call(this)

            find(this.colModel.config, { id: 'event_ref'}).renderer = (value, metadata, record) => {
                return new HTMLProxy(new Promise(async (resolve) => {
                    await this.pollEventsCache[this.record.get('poll_id')]
                    const ref = record.data.event_ref // NOTE value is outdated here
                    resolve(new Expression(`${dateTimeRenderer(ref.event.data.dtstart, metadata)} - ${htmlEncode(await asString(ref.event.getTitle()))}`));
                }));
            }
        },

        setValue(value, record) {
            this.supr().setValue.apply(this, arguments)

            this.record = record
            const pollId = this.record.get('poll_id')
            if (! this.pollEventsCache[pollId]) {
                this.pollEventsCache[pollId] = Tine.CrewScheduling.searchEvents(pollId).then((response) => {
                    each(response.results, (eventData) => {
                        const event_ref = PollReply.getEventRef(eventData)
                        const record = this.store.getAt(this.store.find('event_ref', event_ref))
                        if (record) {
                            const ref = new Stringable(event_ref, () => event_ref)
                            ref.event = Event.setFromJson(eventData)
                            ref.sortValue = eventData.dtstart
                            record.data.event_ref = ref
                        }
                    })
                    this.store.sort('event_ref')
                    return response.results
                })
            }


        }

    })

    Ext.reg('cs-poll-participant-repliesfield', PollRepliesField)

})
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Record from 'data/Record'
import FileLocation from "../FileLocation";
import { get, isFunction } from 'lodash'

const re = /^\/(records)\/.*/

const RecordAttachment = Record.create([], {
    appName: 'Tinebase',
    modelName: 'FileLocation_RecordAttachment',

    statics: {
        create: function(record) {
            const path = get(record, 'data.path', record.path)
            const [, ,model, record_id, ...rest] = path.split('/');

            return RecordAttachment.setFromJson({
                record_id,
                model,
                name: rest.join('/')
            })
        },
        isResponsibleFor: function(data) {
            return re.exec(String(get(data, 'data.path', get(data, 'path'))))
        }
    }
})

FileLocation.registerModel(RecordAttachment)

export default RecordAttachment
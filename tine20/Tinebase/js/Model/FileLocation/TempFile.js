/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Record from 'data/Record'
import FileLocation from "../FileLocation";
import { get, isFunction, isString } from 'lodash'

const TempFile = Record.create([], {
    appName: 'Tinebase',
    modelName: 'FileLocation_TempFile',

    statics: {
        create: function(record) {
            let tempFile = get(record, 'data.tempFile', record.tempFile)
            tempFile = isString(tempFile) ? JSON.parse(tempFile) : tempFile

            return TempFile.setFromJson({
                temp_file_id: tempFile.id,
                name: tempFile.name,
                type: tempFile.type
            })
        },
        isResponsibleFor: function(data) {
            data = data?.data || data
            return data.hasOwnProperty('tempFile') && data.tempFile
        }
    }
})

FileLocation.registerModel(TempFile)

export default TempFile
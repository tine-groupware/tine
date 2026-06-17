/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Record from 'data/Record'
import Tinebase_FileLocation from "/Model/FileLocation";
import { get, isFunction } from 'lodash'

const re = /^\/(personal|shared)\/.*/

const FileLocation = Record.create([], {
    appName: 'Filemanager',
    modelName: 'FileLocation',

    statics: {
        create: function(record) {
            return FileLocation.setFromJson({
                fm_path: get(record, 'data.path', record.path)
            })
        },
        isResponsibleFor: function(data) {
            const isFilemanagerNode = re.exec(String(get(data, 'data.path', get(data, 'path'))))

            return isFunction(get(data, 'constructor.getPhpClassName')) ?
                data.constructor.getPhpClassName() === 'Filemanager_Model_Node' && isFilemanagerNode :
                data.hasOwnProperty('path') && isFilemanagerNode;
        }
    }
})

Tinebase_FileLocation.registerModel(FileLocation)

export default FileLocation
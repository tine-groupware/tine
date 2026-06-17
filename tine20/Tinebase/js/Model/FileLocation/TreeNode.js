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

const re = /^\/(personal|shared|records)\/.*/

const TreeNode = Record.create([], {
    appName: 'Tinebase',
    modelName: 'FileLocation_TreeNode',

    statics: {
        create: function(record) {
            return TreeNode.setFromJson({
                fm_path: get(record, 'data.path', record.path)
            })
        },
        isResponsibleFor: function(data) {
            const isOtherNode = re.exec(String(get(data, 'data.path', get(data, 'path'))))
            return isFunction(get(data, 'constructor.getPhpClassName')) ?
                data.constructor.getPhpClassName() === 'Tinebase_Model_Tree_Node' && !isOtherNode :
                data.hasOwnProperty('path') && data.path && !isOtherNode;
        }
    }
})

FileLocation.registerModel(TreeNode)

export default TreeNode
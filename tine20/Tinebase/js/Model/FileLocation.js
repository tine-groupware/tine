/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Record from 'data/Record'
import { isString, find } from 'lodash'

const models = {}
let modelsInitialized = false

const FileLocation = Record.create([], {
    appName: 'Tinebase',
    modelName: 'FileLocation',

    statics: {
        create: function(record, model) {
            if (!modelsInitialized) {
                modelsInitialized = true
                const requireModule = require.context('./FileLocation', false, /\.js$/)
                requireModule.keys().forEach(requireModule)
            }

            model = isString(model) ? models[model] : model;
            if (!model) {
                model = find(models, (m) => m.isResponsibleFor(record))
            }

            return FileLocation.setFromJson({
                model_name: model.getPhpClassName(),
                location: model.create(record)
            })
        },


        registerModel: function(model) {
            models[model.getPhpClassName()] = model;
        }
    }
})

export default FileLocation
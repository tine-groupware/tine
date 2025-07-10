/*
 * Tine 2.0
 *
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const { extend, each } = require("Ext/core/core/Ext");
const MixedCollection = require("Ext/util/MixedCollection");
const { isFunction, get, isString} = require('lodash');

const RecordMgr = extend(MixedCollection, {
    add: function(record) {
        if (! isFunction(record.getMeta)) {
            throw new Error('only records of type Tinebase.data.Record could be added');
        }
        var appName = record.prototype.appName,
            modelName = record.prototype.modelName;

        if (! appName && modelName) {
            throw new Error('appName and modelName must be in the metadatas');
        }

        RecordMgr.superclass.add.call(this, appName + '.' + modelName, record);
    },

    get: function(appName, modelName) {
        if (! appName && isFunction(get(modelName, 'getMeta'))) {
            return modelName;
        }
        if (! appName) return;
        if (isFunction(appName.getField)) {
            return appName;
        }
        if (! modelName && appName.modelName) {
            modelName = appName.modelName;
        }
        if (appName.appName) {
            appName = appName.appName;
        }

        if (isString(appName) && !modelName) {
            appName = appName.replace(/^Tine[._]/, '')
                .replace(/[._]Model[._]/, '.');

            let appPart = appName.match(/^.+\./);
            if (appPart) {
                modelName = appName.replace(appPart[0], '')
                appName = appPart[0].replace(/\.$/, '');
            }
        }

        if (! isString(appName)) {
            throw new Error('appName must be a string');
        }

        each([appName, modelName], function(what) {
            if (! isString(what)) return;
            var parts = what.split(/(?:_Model_)|(?:\.)/);
            if (parts.length > 1) {
                appName = parts[0];
                modelName = parts[1];
            }
        });

        return RecordMgr.superclass.get.call(this, appName + '.' + modelName);
    }
});

module.exports = new RecordMgr(true);
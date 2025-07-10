/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2012-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * central config
 */
const configManager = function(){
    return {
        get: function(name, appName) {
            var registry = appName && Tine[appName] ? Tine[appName].registry : Tine.Tinebase.registry,
                config = registry ? registry.get('config') : false,
                pathParts = String(name).split('.'),
                path = pathParts.join('.value.') + (pathParts.length == 1 ? '.value' : '');

            return lodash.get(config, path);
        },
        set: function(name, value, appName) {
            var registry = appName && Tine[appName] ? Tine[appName].registry : Tine.Tinebase.registry,
                config = (registry ? registry.get('config') : false) || {},
                pathParts = String(name).split('.'),
                path = pathParts.join('.value.') + (pathParts.length == 1 ? '.value' : '');

            lodash.set(config, path, value);
            registry.set('config', config);
        }
    }
}();

module.exports = configManager;
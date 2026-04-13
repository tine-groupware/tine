/*
 * Tine 2.0
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.namespace('Tine.Sales');

/**
 * allows all accountables to register (needed for accountable combo box)
 */
Tine.Sales.AccountableRegistry = function() {
    var accountables = {};

    return {
        /**
         * return all accountables as array
         *
         * @return {Array}
         */
        getArray: function() {
            var ar = [];
            Ext.iterate(accountables, function(key, value) {
                ar.push(value);
            });

            return ar;
        },

        /**
         * register accountable
         *
         * @param {String} appName
         * @param {String} modelName
         */
        register: function(appName, modelName) {
            var key = appName + modelName;
            if (! accountables.hasOwnProperty(key)) {
                accountables[key] = {appName: appName, modelName: modelName};
            }
        },

        /**
         * check if a renderer is explicitly registered
         *
         * @param {String} appName
         * @param {String} modelName
         * @return {Boolean}
         */
        has: function(appName, modelName) {
            var key = appName + modelName;
            return accountables.hasOwnProperty(key);
        }
    }
}();

Tine.Sales.AccountableRegistry.register('Sales', 'Product');

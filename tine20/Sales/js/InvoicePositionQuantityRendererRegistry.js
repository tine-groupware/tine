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
 * renders the quantity of the invoice position
 */
Tine.Sales.InvoicePositionQuantityRendererRegistry = function() {
    var renderers = {};

    return {
        /**
         * return renderer
         *
         * @param {String} phpModelName
         * @return {Function}
         */
        get: function(phpModelName, unit) {
            var unit = unit.replace(/\s/, '');
            if (renderers.hasOwnProperty(phpModelName+unit)) {
                return renderers[phpModelName+unit];
            } else {
                // default function
                return function(value, row, rec) {
                    return value;
                }
            }
        },

        /**
         * register renderer
         *
         * @param {String} phpModelName
         * @param {Function} func
         */
        register: function(phpModelName, unit, func) {
            var unit = unit.replace(/\s/, '');
            renderers[phpModelName+unit] = func;
        },

        /**
         * check if a renderer is explicitly registered
         *
         * @param {String} phpModelName
         * @return {Boolean}
         */
        has: function(phpModelName, unit) {
            var unit = unit.replace(/\s/, '');
            return renderers.hasOwnProperty(phpModelName+unit);
        }
    }
}();


/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import * as colors from './util/colors';

Ext.ns('Tine.Tinebase');

/**
 * @namespace Tine.Tinebase
 * @class Tine.Tinebase.ColorManager
 * computes color sets (color, light, text, lightText) for colors
 *
 * NOTE: pure color helpers are in util/colors
 *
 * @constructor
 * Creates a new color manager
 * @param {Object} config
 */
Tine.Tinebase.ColorManager = function(config) {
    Ext.apply(this, config);
    this.customColorSchemataCache = {};
};

Ext.apply(Tine.Tinebase.ColorManager.prototype, {

    /**
     * gray color set
     *
     * @type Object
     * @property gray
     */
    gray: colors.gray,

    /**
     * color sets for colors from colorPalette
     *
     * @type Object
     * @property colorSchemata
     */
    colorSchemata: colors.colorSchemata,

    /**
     * @param {String} color hex color with or without leading #
     * @return {Object} colorset
     */
    getSchema(color) {
        if (! colors.normalizeColor(color)) {
            return this.gray;
        }

        return colors.getSchema(color, {
            schemata: this.colorSchemata,
            getCustom: this.getCustomSchema.bind(this)
        });
    },

    getCustomSchema: function(color) {
        if (!this.customColorSchemataCache[color]) {
            this.customColorSchemataCache[color] = colors.getCustomSchema(color);
        }

        return this.customColorSchemataCache[color];
    },

    /**
     * returns the colorset of the color property of the given record
     *
     * @param {Tine.Tinebase.data.Record} record
     * @return {Object|null} colorset or null if record has no (unique) color property or no color is set
     */
    getRecordSchema: function(record) {
        const colorField = record?.constructor ? colors.getColorField(record.constructor) : null;
        const color = colorField ? record.get(colorField) : null;

        return color ? this.getSchema(color) : null;
    },

    shadeColor: function(shade, color) {
        return colors.shadeColor(shade, color);
    },

    getTextColor: function (colorString) {
        return colors.getTextColor(colorString);
    }
});

Tine.Tinebase.ColorManager.str2dec = colors.str2dec;
Tine.Tinebase.ColorManager.compare = colors.compare;
Tine.Tinebase.ColorManager.stringToColour = colors.stringToColour;
Tine.Tinebase.ColorManager.getColorField = colors.getColorField;

Tine.Tinebase.colorMgr = new Tine.Tinebase.ColorManager({});

export default Tine.Tinebase.ColorManager;

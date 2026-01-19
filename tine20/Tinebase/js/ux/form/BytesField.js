/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Ext.ux', 'Ext.ux.form');

/**
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.BytesField
 * @extends     Ext.form.NumberField
 */
Ext.ux.form.BytesField = Ext.extend(Ext.form.NumberField, {
    /**
     * @cfg {String} basePow
     * change this if the value is measured other than in bytes
     */
    basePow: 0,

    /**
     * @cfg {Boolean} useDecimalValues
     * true for decimal units (1000-based), false for binary units (1024-based)
     */
    useDecimalValues: false,

    /**
     * @cfg {String} forceUnit
     */
    forceUnit: null,

    binarySuffixes: ['bytes', 'kib', 'mib', 'gib', 'tib', 'pib', 'eib', 'zib', 'yib'],
    decimalSuffixes: ['bytes', 'kb', 'mb', 'gb', 'tb', 'pb', 'eb', 'zb', 'yb'],

    decimalPrecision: 2,
    minValue: 0,
    baseChars: "0123456789bBkKmMgGtTpPeEzZyY ",

    initComponent: function() {
        this.decimalSeparator = Tine.Tinebase.registry.get('decimalSeparator');

        // Determine which suffix set to use based on forceUnit or useDecimalValues
        this.suffixes = this.useDecimalValues ? this.decimalSuffixes : this.binarySuffixes;

        if (this.forceUnit) {
            if (this.decimalSuffixes.includes(this.forceUnit.toLowerCase())) {
                this.useDecimalValues = true;
                this.suffixes = this.decimalSuffixes;
            }
        }

        this.divisor = this.useDecimalValues ? 1000 : 1024;
        this.validateRe = new RegExp('([0-9' + this.decimalSeparator + ']+)\\s*([a-zA-Z]+)');
        this.supr().initComponent.apply(this, arguments);
    },

    validateValue: function(value) {
        const parts = String(value).match(this.validateRe);
        const number = parts ? parts[1] : value;
        const suffix = parts ? parts[2] : this.suffixes[this.basePow];

        if (!this.supr().validateValue.call(this, number)) {
            return false;
        }

        if (! _.reduce(_.concat(this.decimalSuffixes, this.binarySuffixes), function(r, s) {
            return r || String(s).match(new RegExp('^' + suffix, 'i'));
        }, false)) {
            this.markInvalid(String.format(i18n._('{0} is not a valid unit'), suffix));
            return false;
        }

        return true;
    },

    parseValue: function(value) {
        const parts = String(value).match(this.validateRe);
        const number = parts ? parts[1] : value;
        const suffix = parts ? parts[2] : this.suffixes[this.basePow];
        const normalizedSuffix = String(suffix).toLowerCase();
        let index = this.binarySuffixes.indexOf(normalizedSuffix);
        if (index === -1) {
            index = this.decimalSuffixes.indexOf(normalizedSuffix);
        }
        const pow = index > -1 ? index : 0;

        if (value === '' || value === null) return null;

        value = this.supr().parseValue.call(this, number);
        value = value * Math.pow(this.divisor, pow);

        value = value / Math.pow(this.divisor, this.basePow);

        // NOTE: decimals might come from bytes
        value = Math.round(value);

        return value;
    },

    setValue: function(value) {
        this.supr().setValue.call(this, value);

        value = value !== null && value !== '' ?
            Tine.Tinebase.common.byteFormatter(value * Math.pow(this.divisor, this.basePow), this.forceUnit, this.decimalPrecision, this.useDecimalValues) :
            this.emptyText;

        this.setRawValue(value);

        return this;
    }
});
Ext.reg('extuxbytesfield', Ext.ux.form.BytesField);

/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Ext.ux', 'Ext.ux.form');

/**
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.MoneyField
 * @extends     Ext.form.MoneyField
 */
Ext.ux.form.MoneyField = Ext.extend(Ext.ux.form.NumberField, {

    currencySymbol: null,

    initComponent: function() {
        this.setCurrencySymbol(this.currencySymbol ?? Tine.Tinebase.registry.get('currencySymbol'));
        this.decimalPrecision = 2;
        this.decimalSeparator = Tine.Tinebase.registry.get('decimalSeparator');

       Ext.ux.form.MoneyField.superclass.initComponent.apply(this, arguments);
    },

    setCurrencySymbol: function(currencySymbol) {
        if (currencySymbol !== this.currencySymbol) {
            this.currencySymbol = currencySymbol;
            this.suffix = this.currencySymbol ? ` ${this.currencySymbol}` : '';
            if (this.rendered) {
                this.setValue(this.getValue());
            }
        }
    }
});

Ext.reg('extuxmoneyfield', Ext.ux.form.MoneyField);

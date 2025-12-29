/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.form');

Tine.widgets.form.DiscountField = Ext.extend(Ext.ux.form.MoneyField, {
    price_field: null,
    type_field: null,
    percentage_field: null,
    sum_field: null,
    net_field: null,

    /**
     * @cgf {Boolean} singleField
     * discount is handled in UI with a single field only
     */
    singleField: false,

    allowNegative: false,
    validateOnBlur: true,

    initComponent: function() {
        this.baseChars = this.baseChars + '%' + Tine.Tinebase.registry.get('currencySymbol');
        this.lastValues = {};

        Tine.widgets.form.DiscountField.superclass.initComponent.apply(this, arguments);
    },

    autoConfig: function(record) {
        if (record) {
            this.record = record;
            // autoconfig corresponding fields
            this.sum_field = this.sum_field || this.fieldName;
            ['type', 'percentage'].forEach((fld) => {
                if (!this[`${fld}_field`]) {
                    const fldName = this.fieldName.replace(/_sum$/, `_${fld}`)
                    if (this.record.constructor.hasField(fldName)) {
                        this[`${fld}_field`] = fldName;
                    }
                }
            })
        }
    },

    setValue: function(value, record) {
        this.autoConfig(record);

        if (this.type_field && this.record.get(this.type_field) && this.singleField) {
            this.suffix = ' ' + (this.record.get(this.type_field) === 'PERCENTAGE' ? '%' : Tine.Tinebase.registry.get('currencySymbol'));
            // this.decimalPrecision = this.suffix === ' %' ? 0 : 2;
            // in single field mode we show percentage here
            if (this.record.get(this.type_field) === 'PERCENTAGE') {
                value = this.record.get(this.percentage_field)
            }
        }

        const rtn = Tine.widgets.form.DiscountField.superclass.setValue.call(this, value, record)
    },

    checkState: function(editDialog, record) {
        this.autoConfig(record);
        this.autoValues(record, editDialog?.getForm(), this);

        const type = record.get(this.type_field)
        editDialog?.getForm().findField(this.percentage_field)?.setReadOnly(this.singleField === false && type !== 'PERCENTAGE');
        editDialog?.getForm().findField(this.sum_field)?.setReadOnly(this.singleField === false && type !== 'SUM');
    },

    validateValue: function(value) {
        value = this.stripSuffix(value);
        return Tine.widgets.form.DiscountField.superclass.validateValue.call(this, value);
    },

    parseValue: function(value) {
        value = this.stripSuffix(value);
        return Tine.widgets.form.DiscountField.superclass.parseValue.call(this, value);
    },

    stripSuffix: function(value) {
        if (! this.singleField) return value;
        const parts = String(value).trim().match(/([0-9,.]*)\s*([^0-9,.]*)/);
        this.suffix = parts.length && ['%', Tine.Tinebase.registry.get('currencySymbol')].indexOf(parts[2]) >=0 ? ` ${parts[2]}` : this.suffix;
        // this.decimalPrecision = this.suffix === ' %' ? 0 : 2;
        if (this.type_field && this.record) {
            const type = this.suffix === ' %' ? 'PERCENTAGE' : 'SUM';
            this.record.set(this.type_field, type);
            this.findParentBy((ct) => {return ct.getForm})?.getForm().findField(this.type_field)?.setValue(type);
        }
        return parts.length ? parts[1] : 0;
    },

    autoValues(record, form, config) {
        const toFixed = Tine.Sales.Model.DocumentPosition_PurchaseInvoice.toFixed;

        const autoValues = {};

        // current values before calc
        const currentValues = ['price', 'type', 'sum', 'percentage', 'net'].reduce((v, f) => {
            const fieldName = config[`${f}_field`];
            const field = form?.findField(fieldName);
            const value = field ? field.getValue() : record.get(fieldName);
            if (value === null) autoValues[fieldName] = fieldName === 'type' ? 'SUM' : 0; // initialize fields
            return _.set(v, fieldName, value);
        }, {});
        const currentComputed = this.computeValues(currentValues, config);
        const lastComputed = this.computeValues(this.lastValues, config);

        const modified = Object.keys(currentValues).reduce((m, f) => {
            return Object.assign(m, currentValues[f] !== this.lastValues[f] ? _.set({}, f, this.lastValues[f] || 0) : {})
        }, {});

        // console.error(form ? 'form' : 'editorgrid', modified)

        if (this.autoValuesReverseMode || (modified.hasOwnProperty([config.net_field]) && currentValues[config.net_field])) {
            // reverse calculations
            this.autoValuesReverseMode = true;
            let price

            // calc percentage in sum mode on net or sum change
            if (currentValues[config.type_field] === 'SUM' && (modified.hasOwnProperty([config.net_field]) || modified.hasOwnProperty([config.sum_field]))) {
                price = currentValues[config.net_field] + currentValues[config.sum_field]
                const percentage = currentValues[config.sum_field] / price * 100
                if (toFixed(currentValues[config.percentage_field]) === toFixed(this.lastValues[config.sum_field] / this.lastValues[config.price_field] * 100)) {
                    autoValues[config.percentage_field] = percentage;
                }
            }

            // calc sum in percentage mode on net or percentage change
            if (currentValues[config.type_field] === 'PERCENTAGE' && (modified.hasOwnProperty([config.net_field]) || modified.hasOwnProperty([config.percentage_field]))) {
                price = currentValues[config.net_field] / (1- currentValues[config.percentage_field]/100);
                const sum = price - currentValues[config.net_field];
                if (toFixed(currentValues[config.sum_field]) === toFixed(this.lastValues[config.price_field] - this.lastValues[config.net_field])) {
                    autoValues[config.sum_field] = sum;
                }
            }

            if(price !== undefined && toFixed(currentValues[config.price_field]) === toFixed(lastComputed[config.price_field])) {
                autoValues[config.price_field] = price;
            }

        } else {
            // calc sum if we are in percentage mode and percentage or price changed
            if (currentValues[config.type_field] === 'PERCENTAGE' && (modified.hasOwnProperty([config.price_field]) || modified.hasOwnProperty([config.percentage_field]))) {
                if (toFixed(currentValues[config.sum_field]) === toFixed(lastComputed[config.sum_field])) {
                    autoValues[config.sum_field] = currentComputed[config.sum_field];
                }
            }

            // calc percentage if we are in sum mode and sum or price changed
            if (currentValues[config.type_field] === 'SUM' && (modified.hasOwnProperty([config.price_field]) || modified.hasOwnProperty([config.sum_field]))) {
                if (toFixed(currentValues[config.percentage_field]) === toFixed(lastComputed[config.percentage_field])) {
                    autoValues[config.percentage_field] = currentComputed[config.percentage_field];
                }
            }

            // calc net price on sum, percentage or price change
            if (modified.hasOwnProperty([config.sum_field]) || modified.hasOwnProperty([config.percentage_field]) || modified.hasOwnProperty([config.price_field])) {
                if (toFixed(currentValues[config.net_field]) === toFixed(lastComputed[config.net_field])) {
                    autoValues[config.net_field] = currentComputed[config.net_field];
                }
            }
        }


        ['price', 'type', 'sum', 'percentage', 'net'].forEach((fld) => {
            const fieldName = config[`${fld}_field`];

            if (autoValues.hasOwnProperty(fieldName)) {
                this.lastValues[fieldName] = autoValues[fieldName];
                const value = toFixed(autoValues[fieldName])
                if (record.constructor.hasField(fieldName)) {
                    record.set(fieldName, value);
                }
                form?.findField(fieldName)?.setValue(value, record);
            } else {
                this.lastValues[fieldName] = currentValues[fieldName];
            }
        });
    },

    computeValues(data, config) {
        const values = {}
        values[config.price_field] = data[config.price_field] || 0;
        values[config.type_field] = data[config.type_field] || 'SUM';
        values[config.sum_field] = values[config.type_field] === 'SUM' ? (data[config.sum_field] || 0) : (config.singleField ? (data[config.sum_field] || 0) : (data[config.percentage_field] || 0)) / 100 * values[config.price_field];
        values[config.percentage_field] = values[config.type_field] === 'PERCENTAGE' ? (config.singleField ? (values[config.sum_field] || 0) : (data[config.percentage_field] || 0)) : (((values[config.sum_field] || 0) / values[config.price_field]) || 0) * 100;
        values[config.net_field] = values[config.price_field] - values[config.sum_field];

        return values;
    }

});

Ext.reg('discountfield', Tine.widgets.form.DiscountField);

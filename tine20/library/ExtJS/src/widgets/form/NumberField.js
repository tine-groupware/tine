/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
/**
 * @class Ext.form.NumberField
 * @extends Ext.form.TextField
 * Numeric text field that provides automatic keystroke filtering and numeric validation.
 * @constructor
 * Creates a new NumberField
 * @param {Object} config Configuration options
 * @xtype numberfield
 */
Ext.form.NumberField = Ext.extend(Ext.form.TextField,  {
    /**
     * @cfg {RegExp} stripCharsRe @hide
     */
    /**
     * @cfg {RegExp} maskRe @hide
     */
    /**
     * @cfg {String} fieldClass The default CSS class for the field (defaults to "x-form-field x-form-num-field")
     */
    fieldClass: "x-form-field x-form-num-field",
    /**
     * @cfg {Boolean} allowDecimals False to disallow decimal values (defaults to true)
     */
    allowDecimals : true,
    /**
     * @cfg {String} decimalSeparator Character(s) to allow as the decimal separator (defaults to '.')
     */
    decimalSeparator : ".",
    /**
     * @cfg {Number} decimalPrecision The maximum precision to display after the decimal separator (defaults to 2)
     */
    decimalPrecision : 2,
    /**
     * @cfg {Boolean} allowNegative False to prevent entering a negative sign (defaults to true)
     */
    allowNegative : true,
    /**
     * @cfg {Number} minValue The minimum allowed value (defaults to Number.NEGATIVE_INFINITY)
     */
    minValue : Number.NEGATIVE_INFINITY,
    /**
     * @cfg {Number} maxValue The maximum allowed value (defaults to Number.MAX_VALUE)
     */
    maxValue : Number.MAX_VALUE,
    /**
     * @cfg {String} minText Error text to display if the minimum value validation fails (defaults to "The minimum value for this field is {minValue}")
     */
    minText : "The minimum value for this field is {0}",
    /**
     * @cfg {String} maxText Error text to display if the maximum value validation fails (defaults to "The maximum value for this field is {maxValue}")
     */
    maxText : "The maximum value for this field is {0}",
    /**
     * @cfg {String} nanText Error text to display if the value is not a valid number.  For example, this can happen
     * if a valid character like '.' or '-' is left in the field with no number (defaults to "{value} is not a valid number")
     */
    nanText : "{0} is not a valid number",
    /**
     * @cfg {String} baseChars The base set of characters to evaluate as valid numbers (defaults to '0123456789').
     */
    baseChars : "0123456789",
    /**
     * @cfg {Boolean} coloredValue
     */
    coloredValue: false,

    // private
    initEvents : function(){
        var allowed = this.baseChars + '';
        if (this.allowDecimals) {
            allowed += this.decimalSeparator;
        }
        if (this.allowNegative) {
            allowed += '-';
        }
        this.maskRe = new RegExp('[' + Ext.escapeRe(allowed) + ']');
        Ext.form.NumberField.superclass.initEvents.call(this);
    },

    // private
    validateValue : function(value){
        if(!Ext.form.NumberField.superclass.validateValue.call(this, value)){
            return false;
        }
        if(value.length < 1){ // if it's blank and textfield didn't flag it then it's valid
             return true;
        }
        value = String(value).replace(this.decimalSeparator, ".");
        if(isNaN(value)){
            this.markInvalid(String.format(this.nanText, value));
            return false;
        }
        var num = this.parseValue(value);
        if(num < this.minValue){
            this.markInvalid(String.format(this.minText, this.minValue));
            return false;
        }
        if(num > this.maxValue){
            this.markInvalid(String.format(this.maxText, this.maxValue));
            return false;
        }
        return true;
    },

    getValue : function(){
        const value = Ext.form.NumberField.superclass.getValue.call(this);
        return value === this.emptyValue ? value : this.fixPrecision(this.parseValue(value));
    },

    setValue : function(v){
    	v = Ext.isNumber(v) ? v : parseFloat(String(v).replace(this.decimalSeparator, "."));
        if (this.rendered && this.coloredValue) {
            this.el.removeClass(['x-form-num-field-positiv', 'x-form-num-field-negativ'])
            this.el.addClass(`x-form-num-field-${v < 0 ? 'negativ' : 'positiv'}`);
        }
        // back to string...
        v = isNaN(v) ? '' : String(v).replace(".", this.decimalSeparator);
        return Ext.form.NumberField.superclass.setValue.call(this, v);
    },
    
    /**
     * Replaces any existing {@link #minValue} with the new value.
     * @param {Number} value The minimum value
     */
    setMinValue : function(value){
        this.minValue = Ext.num(value, Number.NEGATIVE_INFINITY);
    },
    
    /**
     * Replaces any existing {@link #maxValue} with the new value.
     * @param {Number} value The maximum value
     */
    setMaxValue : function(value){
        this.maxValue = Ext.num(value, Number.MAX_VALUE);    
    },

    // private
    parseValue : function(value){
        value = parseFloat(String(value).replace(this.decimalSeparator, "."));
        return isNaN(value) ? '' : value;
    },

    // private
    fixPrecision : function(value){
        var nan = isNaN(value);
        if(!this.allowDecimals || this.decimalPrecision == -1 || nan || !value){
           return nan ? '' : value;
        }
        return parseFloat(parseFloat(value).toFixed(this.decimalPrecision));
    },

    beforeBlur : function(){
        var v = this.parseValue(this.getRawValue());
        if(!Ext.isEmpty(v)){
            this.setValue(this.fixPrecision(v));
        }
    }
});
Ext.reg('numberfield', Ext.form.NumberField);

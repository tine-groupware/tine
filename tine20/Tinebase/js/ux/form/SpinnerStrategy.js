/**
 * Copyright (c) 2008, Steven Chim
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 * 
 *     * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 *     * The names of its contributors may not be used to endorse or promote products derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * Abstract Strategy
 *
 * @namespace   Ext.ux.form.Spinner
 * @class       Ext.ux.form.Spinner.Strategy
 * @extends     Ext.util.Observable
 */
Ext.ux.form.Spinner.Strategy = function(config){
    Ext.apply(this, config);
};

Ext.extend(Ext.ux.form.Spinner.Strategy, Ext.util.Observable, {
    defaultValue : 0,
    minValue : undefined,
    maxValue : undefined,
    incrementValue : 1,
    alternateIncrementValue : 5,
    validationTask : new Ext.util.DelayedTask(),
    
    init: function(cmp) {},
    
    onSpinUp : function(field){
        this.spin(field, false, false);
    },

    onSpinDown : function(field){
        this.spin(field, true, false);
    },

    onSpinUpAlternate : function(field){
        this.spin(field, false, true);
    },

    onSpinDownAlternate : function(field){
        this.spin(field, true, true);
    },

    spin : function(field, down, alternate){
        this.validationTask.delay(500, function(){field.validate();});
        //extend
    },

    fixBoundries : function(value){
        return value;
        //overwrite
    }
    
});

/**
 * Concrete Strategy: Numbers
 * 
 * @namespace   Ext.ux.form.Spinner
 * @class       Ext.ux.form.Spinner.NumberStrategy
 * @extends     Ext.ux.form.Spinner.Strategy
 */
Ext.ux.form.Spinner.NumberStrategy = function(config){
    Ext.ux.form.Spinner.NumberStrategy.superclass.constructor.call(this, config);
};

Ext.extend(Ext.ux.form.Spinner.NumberStrategy, Ext.ux.form.Spinner.Strategy, {

    init : function(field) {
        field.setValue = Ext.ux.form.NumberField.prototype.setValue;
        field.parseValue = Ext.ux.form.NumberField.prototype.parseValue;
        field.validateValue = Ext.ux.form.NumberField.prototype.validateValue;
        field.getValue = Ext.ux.form.NumberField.prototype.getValue;
        field.fixPrecision = Ext.ux.form.NumberField.prototype.fixPrecision;
        field.beforeBlur = Ext.ux.form.NumberField.prototype.beforeBlur;
        field.minText = Ext.ux.form.NumberField.prototype.minText;
        field.maxText = Ext.ux.form.NumberField.prototype.maxText;
        field.decimalSeparator =  Ext.ux.form.NumberField.prototype.decimalSeparator;
        field.thousandSeparator =  Ext.ux.form.NumberField.prototype.thousandSeparator;
        field.useThousandSeparator =  field.useThousandSeparator === undefined ? Ext.ux.form.NumberField.prototype.useThousandSeparator : field.useThousandSeparator;
        this.decimalPrecision = field.decimalPrecision ? field.decimalPrecision : Ext.ux.form.NumberField.prototype.decimalPrecision;
        field.allowDecimals = field.allowDecimals ? field.allowDecimals : this.allowDecimals;

        field.el.applyStyles('text-align: right');
    },
    spin : function(field, down, alternate){
        Ext.ux.form.Spinner.NumberStrategy.superclass.spin.call(this, field, down, alternate);

        var v = parseFloat(field.getValue());
        var incr = (alternate == true) ? this.alternateIncrementValue : this.incrementValue;

        (down == true) ? v -= incr : v += incr ;
        v = (isNaN(v)) ? this.defaultValue : v;
        v = this.fixBoundries(v);
        field.setValue(v);
    },

    fixBoundries : function(value){
        var v = value;

        if(this.minValue != undefined && v < this.minValue){
            v = this.minValue;
        }
        if(this.maxValue != undefined && v > this.maxValue){
            v = this.maxValue;
        }

        return this.fixPrecision(v);
    },
    
    // private
    fixPrecision : function(value){
        var nan = isNaN(value);
        if(!this.allowDecimals || this.decimalPrecision == -1 || nan || !value){
            return nan ? '' : value;
        }
        return parseFloat(parseFloat(value).toFixed(this.decimalPrecision));
    }
});


/**
 * Concrete Strategy: Date
 *
 * @namespace   Ext.ux.form.Spinner
 * @class       Ext.ux.form.Spinner.DateStrategy
 * @extends     Ext.ux.form.Spinner.Strategy
 */
Ext.ux.form.Spinner.DateStrategy = function(config){
    Ext.ux.form.Spinner.DateStrategy.superclass.constructor.call(this, config);
};
Ext.ux.form.Spinner.DateStrategy.isNegRe = /^ *- */;

Ext.extend(Ext.ux.form.Spinner.DateStrategy, Ext.ux.form.Spinner.Strategy, {
    allowNegative: true,
    defaultValue : new Date(),
    format : "Y-m-d",
    incrementValue : 1,
    incrementConstant : Date.DAY,
    alternateIncrementValue : 1,
    alternateIncrementConstant : Date.MONTH,

    spin : function(field, down, alternate){
        Ext.ux.form.Spinner.DateStrategy.superclass.spin.call(this, field, down, alternate);

        var v = field.getRawValue(),
            isNegValue = v.match(Ext.ux.form.Spinner.DateStrategy.isNegRe);

        if (isNegValue) {
            v = v.replace(Ext.ux.form.Spinner.DateStrategy.isNegRe, '');
        }
        
        v = Date.parseDate(v, this.format);
        var dir = (down == true) ? -1 : 1 ;
        var incr = (alternate == true) ? this.alternateIncrementValue : this.incrementValue;
        var dtconst = (alternate == true) ? this.alternateIncrementConstant : this.incrementConstant;

        if(typeof this.defaultValue == 'string'){
            this.defaultValue = Date.parseDate(this.defaultValue, this.format);
        }

        // transition form 0
        if (v.format('H:i:s') == '00:00:00' && this.allowNegative) {
            isNegValue = down;
        }

        if (isNegValue) {
            dir = dir*-1;
        }

        v = (v) ? v.add(dtconst, dir*incr) : this.defaultValue;

        if (v.format('H:i:s') == '00:00:00') {
            isNegValue = false;
        }

        v = this.fixBoundries(v);
        field.setRawValue((isNegValue ? '- ' : '') + Ext.util.Format.date(v,this.format));
    },
    
    //private
    fixBoundries : function(date){
        var dt = date;
        var min = (typeof this.minValue == 'string') ? Date.parseDate(this.minValue, this.format) : this.minValue ;
        var max = (typeof this.maxValue == 'string') ? Date.parseDate(this.maxValue, this.format) : this.maxValue ;

        if(this.minValue != undefined && dt < min){
            dt = min;
        }
        if(this.maxValue != undefined && dt > max){
            dt = max;
        }

        return dt;
    }

});


/**
 * Concrete Strategy: Time
 *
 * @namespace   Ext.ux.form.Spinner
 * @class       Ext.ux.form.Spinner.TimeStrategy
 * @extends     Ext.ux.form.Spinner.DateStrategy
 */
Ext.ux.form.Spinner.TimeStrategy = function(config){
    Ext.ux.form.Spinner.TimeStrategy.superclass.constructor.call(this, config);
};

Ext.extend(Ext.ux.form.Spinner.TimeStrategy, Ext.ux.form.Spinner.Strategy, {
    format : "H:i",
    incrementValue : 1,
    incrementConstant : Date.MINUTE,
    alternateIncrementValue : 1,
    alternateIncrementConstant : Date.HOUR,

    constantMap: {
        [Date.SECOND]: 1,
        [Date.MINUTE]: 60,
        [Date.HOUR]: 3600,
    },

    spin : function(field, down, alternate){
        Ext.ux.form.Spinner.DateStrategy.superclass.spin.call(this, field, down, alternate);

        let v = field.getValue() * (field.baseUnit === 'minutes' ? 60 : 1);

        const dir = (down === true) ? -1 : 1 ;
        const incr = (alternate === true) ? this.alternateIncrementValue : this.incrementValue;
        const constant = this.constantMap[(alternate === true) ? this.alternateIncrementConstant : this.incrementConstant];

        v = v + dir*incr*constant;
        v = this.fixBoundries(v);

        const [H,i,s] = Ext.ux.form.DurationSpinner.getTimeParts(v);

        field.setRawValue((v<0 ? '- ' : '') + this.format
            .replace('H', H)
            .replace('i', i)
            .replace('s', s));

    },

    //private
    fixBoundries : function(v, field){
        var min = (typeof this.minValue == 'string') ? Ext.ux.form.DurationSpinner.parse(this.minValue, field.baseUnit) : this.minValue ;
        var max = (typeof this.maxValue == 'string') ? Ext.ux.form.DurationSpinner.parse(this.maxValue, field.baseUnit) : this.maxValue ;

        if(this.minValue != undefined && v < min){
            v = min;
        }
        if(this.maxValue != undefined && v > max){
            v = max;
        }

        return v;
    }
});

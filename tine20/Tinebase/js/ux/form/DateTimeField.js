/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/*global Ext*/

Ext.ns('Ext.ux', 'Ext.ux.form');

/**
 * A combination of datefield and timefield
 * 
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.DateTimeField
 * @extends     Ext.form.Field
 */
Ext.ux.form.DateTimeField = Ext.extend(Ext.form.Field, {
    autoEl: 'div',
    value: '',
    disabledClass: '',
    increment: 15,
    timeEditable: true,
    markedInvalid: false,
    minWidth: 160,
    allowBlank: true,
    
    /**
     * @cfg {Object} config to be applied to date field 
     */
    dateConf: null,
    
    /**
     * @cfg {Object} config to be applied to time field 
     */
    timeConf: null,
    
    /*
     * @cfg {String} Default Time in the form HH:MM, if null current time is used
     */
    defaultTime: null,
    
    initComponent: function () {
        Ext.ux.form.DateTimeField.superclass.initComponent.call(this);
        this.lastValues = [];
        this.on('change', this.onChange, this);

    },
    
    /**
     * Sets the default time when using the first time or after clearing the datefield
     * @param {Ext.form.Field}
     * @param {DateTime} newValue
     * @param {DateTime} oldValue
     */
    onChange: function(f, newValue, oldValue) {

        if(oldValue == '' && newValue) {
            var newDate = newValue.clone(),
                now = new Date(),
                times = (this.defaultTime) ? this.defaultTime.split(':') : [now.getHours(), now.getMinutes()];
                
            
            newDate.setHours(parseInt(times[0]));
            newDate.setMinutes(parseInt(times[1]));
                
            f.setValue(newDate);
        }
    },
    
    clearInvalid: function () {
        this.markedInvalid = false;
        
        if (this.dateField) {
            this.dateField.clearInvalid();
        }
        if (this.timeField) {
            this.timeField.clearInvalid();
        }
    },
    
    clearTime: function () {
        var dateTime = this.getValue();
        if (Ext.isDate(dateTime)) {
            this.setValue(this.getValue().clearTime(true));
        } else {
            this.timeField.setValue(new Date().clearTime());
        }
    },
    
    combineDateTime: function (date, time) {
        date = Ext.isDate(date) ? date : new Date.clearTime();
        
        if (Ext.isDate(time)) {
            date = date.clone();
            date.clearTime();
            date = date.add(Date.HOUR, time.getHours());
            date = date.add(Date.MINUTE, time.getMinutes());
        }
        
        return date;
    },
    
    getName: function () {
        return this.name;
    },
    
    getValue: function () {
        if (! this.dateField) {
            return this.value;
        }
        
        var date = this.dateField.getValue();
        var time = this.timeField.getValue();
        
        // this is odd, why doesn't Ext.form.TimeField a Date datatype?
        if (! Ext.isDate(time)) {
            time = Date.parseDate(time, 'H:i');
        }
        
        if (Ext.isDate(date)) {
            date = this.combineDateTime(date, time);
        }
        
        return date;
    },

    getRawValue: function() {
        if (! this.dateField) {
            return this.value;
        }

        var date = this.dateField.getRawValue();
        var time = this.timeField.getRawValue();

        return date + ' ' + time;
    },

    validateValue : function(value){
        if(!Ext.form.DateField.superclass.validateValue.call(this, value)){
            return false;
        }
        return true;
    },

    
    markInvalid: function (msg) {
        this.markedInvalid = true;
        
        this.dateField.markInvalid(msg);
        this.timeField.markInvalid(msg);
    },

    addClass: function (cls) {
        if(this.el){
            this.dateField.addClass(cls);
            this.timeField.addClass(cls);
        }else{
            this.cls = this.cls ? this.cls + ' ' + cls : cls;
        }
        return this;
    },

    removeClass: function (cls) {
        if(this.el){
            this.dateField.removeClass(cls);
            this.timeField.removeClass(cls);
        }else if(this.cls){
            this.cls = this.cls.split(' ').remove(cls).join(' ');
        }
        return this;
    },
    
    onRender: function (ct, position) {
        var me = this,
            dateField = (this.allowBlank) ? Ext.ux.form.ClearableDateField : Ext.form.DateField;

        this.width = this.width || this.minWidth;
        //Ext.ux.form.DateTimeField.superclass.onRender.call(this, ct, position);
        this.el = document.createElement(this.autoEl);
        this.el.id = this.getId();
        this.el = Ext.get(this.el);
        ct.dom.insertBefore(this.el.dom, position);
        this.el.applyStyles('overflow:visible;');

        this.el.blur = this.el.blur.createSequence(function() {
            me.dateField.el.blur();
            me.timeField.el.blur();
        });

        this.dateField = new dateField(Ext.apply({
            lazyRender: false,
            renderTo: this.el,
            ownerCt: this,
            readOnly: this.readOnly || this.dateFieldReadOnly,
            hideTrigger: this.hideTrigger,
            disabled: this.disabled || this.dateFieldDisabled,
            tabIndex: this.tabIndex === -1 ? this.tabIndex : false,
            allowBlank: this.allowBlank,
            value: this.value,
            listeners: {
                scope: this,
                change: this.onDateChange,
                select: this.onDateChange,
                focus: this.onDateFocus,
                blur: this.onDateBlur
            }
        }, this.dateConf || {}));
        
        this.timeField = new Ext.form.TimeField(Ext.apply({
            noFix: true,
            lazyRender: false,
            increment: this.increment,
            renderTo: this.el,
            readOnly: this.readOnly || this.timeFieldReadOnly,
            hideTrigger: this.hideTrigger,
            disabled: this.disabled || this.timeFieldDisabled,
            editable: this.timeEditable,
            tabIndex: this.tabIndex === -1 ? this.tabIndex : false,
            allowBlank: this.allowBlank,
            value: this.value,
            listeners: {
                scope: this,
                change: this.onTimeChange,
                select: this.onTimeChange,
                focus: this.onTimeFocus,
                blur: this.onTimeBlur
            }
        }, this.timeConf || {}));
        
    },

    triggerBlur: function() {
        if (this.dateField.hasFocus) {
            this.dateField.triggerBlur();
        } else if (this.timeField.hasFocus) {
            this.timeField.triggerBlur();
        }
    },

    onDateFocus: function () {
        if (! this.timeField.hasFocus) {
            this.hasFocus = true;
            this.fireEvent('focus', this);
        }
    },
    
    onDateBlur: function () {
        if (! this.timeField.hasFocus) {
            this.hasFocus = false;
            this.fireEvent('blur', this);
        }
    },

    onTimeFocus: function () {
        if (! this.dateField.hasFocus) {
            this.hasFocus = true;
            this.fireEvent('focus', this);
        }
    },

    onTimeBlur: function () {
        if (! this.dateField.hasFocus) {
            this.hasFocus = false;
            this.fireEvent('blur', this);
        }
    },

    validateBlur: function(e) {
        return !this.el.contains(e.target) && this.dateField.validateBlur(e) && this.timeField.validateBlur(e);
    },

    onDateChange: function () {
        var newValue = this.getValue();
        this.setValue(newValue);
        this.fireEvent('change', this, newValue, this.lastValues.length > 1 ? this.lastValues[this.lastValues.length - 2] : '');
    },
    
    onResize: function (w, h) {
        Ext.ux.form.DateTimeField.superclass.onResize.apply(this, arguments);
        
        if(this.allowBlank) {
            var korrel = [0.65, 0.35];
        } else {
            var korrel = [0.55, 0.45];
        }
        
        // needed for readonly
        this.el.setHeight(20);
        
        this.el.setStyle({'position': 'relative'});
        
        this.dateField.wrap.setStyle({'position': 'absolute'});
        var dateFieldWidth = Math.abs(w * korrel[0] - 5);
        this.dateField.setWidth(dateFieldWidth);
        this.dateField.wrap.setWidth(dateFieldWidth);
        
        this.timeField.wrap.setStyle({'position': 'absolute'});
        var timeFieldWidth = Math.abs(w * korrel[1]);
        this.timeField.setWidth(timeFieldWidth);
        this.timeField.wrap.setWidth(timeFieldWidth);
        this.timeField.wrap.setLeft(dateFieldWidth + 5);
    },
    
    onTimeChange: function () {
        var newValue = this.getValue();
        this.setValue(newValue);
        this.fireEvent('change', this, newValue, this.lastValues.length > 1 ? this.lastValues[this.lastValues.length - 2] : '');
    },
    
    setDisabled: function (bool, what) {
        if (what !== 'time') {
            if (this.dateField) {
                this.dateField.setDisabled(bool);
            } else {
                this.dateFieldDisabled = bool;
            }
        }
        
        if (what !== 'date') {
            if(this.timeField) {
                this.timeField.setDisabled(bool);
            } else {
                this.timeFieldDisabled = bool;
            }
        }
        
        Ext.ux.form.DateTimeField.superclass.setDisabled.call(this, bool);
    },
    
    setReadOnly: function (bool, what) {
        if (what !== 'time' ) {
            if (this.dateField) {
                this.dateField.setReadOnly(bool);
            } else {
                this.dateFieldReadOnly = true;
            }
        }
        
        if (what !== 'date') {
            if (this.timeField) {
                this.timeField.setReadOnly(bool);
            } else {
                this.timeFieldReadOnly = true;
            }
        }

        Ext.ux.form.DateTimeField.superclass.setReadOnly.call(this, bool);
    },
    
    setRawValue: Ext.EmptyFn,
    
    setValue: function (value, record, skipHistory) {
        // NOTE: skipHistory was second param. don't know if this is used somehwere
        skipHistory = !_.isSet(skipHistory) && _.isBoolean(record) ? record : skipHistory;
        if (! skipHistory) {
            this.lastValues.push(value);
        }
        
        if (this.dateField && this.timeField) {
            this.dateField.setValue(value);
            this.timeField.setValue(Ext.isDate(value) ? value.clone() : value);
        }
        
        this.value = value;
    },
    
    undo: function () {
        if (this.lastValues.length > 1) {
            this.lastValues.pop();
            this.setValue(this.lastValues[this.lastValues.length - 1], true);
        } else {
            this.reset();
        }
    },
    
    isValid: function (preventMark) {
        return this.dateField.isValid(preventMark) && this.timeField.isValid(preventMark) && ! this.markedInvalid;
    }
});
Ext.reg('datetimefield', Ext.ux.form.DateTimeField);

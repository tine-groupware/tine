/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
/**
 * @class Ext.form.DateField
 * @extends Ext.form.TriggerField
 * Provides a date input field with a {@link Ext.DatePicker} dropdown and automatic date validation.
 * @constructor
 * Create a new DateField
 * @param {Object} config
 * @xtype datefield
 */
Ext.form.DateField = Ext.extend(Ext.form.TriggerField,  {
    /**
     * @cfg {String} format
     * The default date format string which can be overriden for localization support.  The format must be
     * valid according to {@link Date#parseDate} (defaults to <tt>'m/d/Y'</tt>).
     */
    format : "m/d/Y",
    /**
     * @cfg {String} altFormats
     * Multiple date formats separated by "<tt>|</tt>" to try when parsing a user input value and it
     * does not match the defined format (defaults to
     * <tt>'m/d/Y|n/j/Y|n/j/y|m/j/y|n/d/y|m/j/Y|n/d/Y|m-d-y|m-d-Y|m/d|m-d|md|mdy|mdY|d|Y-m-d'</tt>).
     */
    altFormats : "m/d/Y|n/j/Y|n/j/y|m/j/y|n/d/y|m/j/Y|n/d/Y|m-d-y|m-d-Y|m/d|m-d|md|mdy|mdY|d|Y-m-d",
    /**
     * @cfg {String} disabledDaysText
     * The tooltip to display when the date falls on a disabled day (defaults to <tt>'Disabled'</tt>)
     */
    disabledDaysText : "Disabled",
    /**
     * @cfg {String} disabledDatesText
     * The tooltip text to display when the date falls on a disabled date (defaults to <tt>'Disabled'</tt>)
     */
    disabledDatesText : "Disabled",
    /**
     * @cfg {String} minText
     * The error text to display when the date in the cell is before <tt>{@link #minValue}</tt> (defaults to
     * <tt>'The date in this field must be after {minValue}'</tt>).
     */
    minText : "The date in this field must be equal to or after {0}",
    /**
     * @cfg {String} maxText
     * The error text to display when the date in the cell is after <tt>{@link #maxValue}</tt> (defaults to
     * <tt>'The date in this field must be before {maxValue}'</tt>).
     */
    maxText : "The date in this field must be equal to or before {0}",
    /**
     * @cfg {String} invalidText
     * The error text to display when the date in the field is invalid (defaults to
     * <tt>'{value} is not a valid date - it must be in the format {format}'</tt>).
     */
    invalidText : "{0} is not a valid date - it must be in the format {1}",
    /**
     * @cfg {String} triggerClass
     * An additional CSS class used to style the trigger button.  The trigger will always get the
     * class <tt>'x-form-trigger'</tt> and <tt>triggerClass</tt> will be <b>appended</b> if specified
     * (defaults to <tt>'x-form-date-trigger'</tt> which displays a calendar icon).
     */
    triggerClass : 'x-form-date-trigger',
    /**
     * @cfg {Boolean} showToday
     * <tt>false</tt> to hide the footer area of the DatePicker containing the Today button and disable
     * the keyboard handler for spacebar that selects the current date (defaults to <tt>true</tt>).
     */
    showToday : true,
    /**
     * @cfg {Date/String} minValue
     * The minimum allowed date. Can be either a Javascript date object or a string date in a
     * valid format (defaults to null).
     */
    /**
     * @cfg {Date/String} maxValue
     * The maximum allowed date. Can be either a Javascript date object or a string date in a
     * valid format (defaults to null).
     */
    /**
     * @cfg {Array} disabledDays
     * An array of days to disable, 0 based (defaults to null). Some examples:<pre><code>
// disable Sunday and Saturday:
disabledDays:  [0, 6]
// disable weekdays:
disabledDays: [1,2,3,4,5]
     * </code></pre>
     */
    /**
     * @cfg {Array} disabledDates
     * An array of "dates" to disable, as strings. These strings will be used to build a dynamic regular
     * expression so they are very powerful. Some examples:<pre><code>
// disable these exact dates:
disabledDates: ["03/08/2003", "09/16/2003"]
// disable these days for every year:
disabledDates: ["03/08", "09/16"]
// only match the beginning (useful if you are using short years):
disabledDates: ["^03/08"]
// disable every day in March 2006:
disabledDates: ["03/../2006"]
// disable every day in every March:
disabledDates: ["^03"]
     * </code></pre>
     * Note that the format of the dates included in the array should exactly match the {@link #format} config.
     * In order to support regular expressions, if you are using a {@link #format date format} that has "." in
     * it, you will have to escape the dot when restricting dates. For example: <tt>["03\\.08\\.03"]</tt>.
     */
    /**
     * @cfg {String/Object} autoCreate
     * A {@link Ext.DomHelper DomHelper element specification object}, or <tt>true</tt> for the default element
     * specification object:<pre><code>
     * autoCreate: {tag: "input", type: "text", size: "10", autocomplete: "off"}
     * </code></pre>
     */

    // private
    defaultAutoCreate : {tag: "input", type: "text", size: "10", autocomplete: "off"},

    initComponent : function(){
        Ext.form.DateField.superclass.initComponent.call(this);

        this.addEvents(
            /**
             * @event select
             * Fires when a date is selected via the date picker.
             * @param {Ext.form.DateField} this
             * @param {Date} date The date that was selected
             */
            'select'
        );

        const format = [].concat(this.format ? (this.format?.Date || this.format) : ['medium']);
        _.pull(format, 'wkday');
        this.format = format.map((v) => {
            return Locale.getTranslationData('Date', v) || v;
        }).join(' ');

        if(Ext.isString(this.minValue)){
            this.minValue = this.parseDate(this.minValue);
        }
        if(Ext.isString(this.maxValue)){
            this.maxValue = this.parseDate(this.maxValue);
        }
        if ((this.value === undefined || this.value === null) && this.default === 'CURRENT_TIMESTAMP') {
            this.value = this.fullDateTime = new Date().clearTime();
        }
        this.disabledDatesRE = null;
        this.initDisabledDays();
    },
    
    initEvents: function() {
        Ext.form.DateField.superclass.initEvents.call(this);
        this.keyNav = new Ext.KeyNav(this.el, {
            "down": function(e) {
                this.onTriggerClick();
            },
            scope: this,
            forceKeyDown: true
        });
    },


    // private
    initDisabledDays : function(){
        if(this.disabledDates){
            var dd = this.disabledDates,
                len = dd.length - 1, 
                re = "(?:";
                
            Ext.each(dd, function(d, i){
                re += Ext.isDate(d) ? '^' + Ext.escapeRe(d.dateFormat(this.format)) + '$' : dd[i];
                if(i != len){
                    re += '|';
                }
            }, this);
            this.disabledDatesRE = new RegExp(re + ')');
        }
    },

    /**
     * Replaces any existing disabled dates with new values and refreshes the DatePicker.
     * @param {Array} disabledDates An array of date strings (see the <tt>{@link #disabledDates}</tt> config
     * for details on supported values) used to disable a pattern of dates.
     */
    setDisabledDates : function(dd){
        this.disabledDates = dd;
        this.initDisabledDays();
        if(this.menu){
            this.menu.picker.setDisabledDates(this.disabledDatesRE);
        }
    },

    /**
     * Replaces any existing disabled days (by index, 0-6) with new values and refreshes the DatePicker.
     * @param {Array} disabledDays An array of disabled day indexes. See the <tt>{@link #disabledDays}</tt>
     * config for details on supported values.
     */
    setDisabledDays : function(dd){
        this.disabledDays = dd;
        if(this.menu){
            this.menu.picker.setDisabledDays(dd);
        }
    },

    /**
     * Replaces any existing <tt>{@link #minValue}</tt> with the new value and refreshes the DatePicker.
     * @param {Date} value The minimum date that can be selected
     */
    setMinValue : function(dt){
        this.minValue = (Ext.isString(dt) ? this.parseDate(dt) : dt);
        if(this.menu){
            this.menu.picker.setMinDate(this.minValue);
        }
    },

    /**
     * Replaces any existing <tt>{@link #maxValue}</tt> with the new value and refreshes the DatePicker.
     * @param {Date} value The maximum date that can be selected
     */
    setMaxValue : function(dt){
        this.maxValue = (Ext.isString(dt) ? this.parseDate(dt) : dt);
        if(this.menu){
            this.menu.picker.setMaxDate(this.maxValue);
        }
    },

    // private
    validateValue : function(value){
        value = this.formatDate(value);
        if(!Ext.form.DateField.superclass.validateValue.call(this, value)){
            return false;
        }
        if(value.length < 1){ // if it's blank and textfield didn't flag it then it's valid
             return true;
        }
        var svalue = value;
        value = this.parseDate(value);
        if(!value){
            this.markInvalid(String.format(this.invalidText, svalue, this.format));
            return false;
        }
        var time = value.getTime();
        if(this.minValue && time < this.minValue.getTime()){
            this.markInvalid(String.format(this.minText, this.formatDate(this.minValue)));
            return false;
        }
        if(this.maxValue && time > this.maxValue.getTime()){
            this.markInvalid(String.format(this.maxText, this.formatDate(this.maxValue)));
            return false;
        }
        if(this.disabledDays){
            var day = value.getDay();
            for(var i = 0; i < this.disabledDays.length; i++) {
                if(day === this.disabledDays[i]){
                    this.markInvalid(this.disabledDaysText);
                    return false;
                }
            }
        }
        var fvalue = this.formatDate(value);
        if(this.disabledDatesRE && this.disabledDatesRE.test(fvalue)){
            this.markInvalid(String.format(this.disabledDatesText, fvalue));
            return false;
        }
        
        this.fullDateTime = value;
        return true;
    },

    // private
    // Provides logic to override the default TriggerField.validateBlur which just returns true
    validateBlur : function(){
        return !this.menu || !this.menu.isVisible();
    },

    /**
     * Returns the current date value of the date field.
     * @return {Date} The date value
     */
    getValue : function(){
        // return this.parseDate(Ext.form.DateField.superclass.getValue.call(this)) || "";
        
        // return the value that was set (has time information when unchanged in client) 
        // and not just the date part!
        var value =  this.fullDateTime;
        return value || this.emptyValue;
    },

    /**
     * Sets the value of the date field.  You can pass a date object or any string that can be
     * parsed into a valid date, using <tt>{@link #format}</tt> as the date format, according
     * to the same rules as {@link Date#parseDate} (the default format used is <tt>"m/d/Y"</tt>).
     * <br />Usage:
     * <pre><code>
//All of these calls set the same date value (May 4, 2006)

//Pass a date object:
var dt = new Date('5/4/2006');
dateField.setValue(dt);

//Pass a date string (default format):
dateField.setValue('05/04/2006');

//Pass a date string (custom format):
dateField.format = 'Y-m-d';
dateField.setValue('2006-05-04');
</code></pre>
     * @param {String/Date} date The date or valid date string
     * @return {Ext.form.Field} this
     */
    setValue : function(date){
        /**
         * fix timezone handling for date picker
         *
         * The getValue function always returns 00:00:00 as time. So if a form got filled
         * with a date like 2008-10-01T21:00:00 the form returns 2008-10-01T00:00:00 although
         * the user did not change the fieled.
         *
         * In a multi timezone context this is fatal! When a user in a certain timezone set
         * a date (just a date and no time information), this means in his timezone the
         * time range from 2008-10-01T00:00:00 to 2008-10-01T23:59:59.
         * _BUT_ for an other user sitting in a different timezone it means e.g. the
         * time range from 2008-10-01T02:00:00 to 2008-10-02T21:59:59.
         *
         * So on the one hand we need to make sure, that the date picker only returns
         * changed datetime information when the user did a change.
         *
         * @todo On the other hand we
         * need adjust the day +/- one day according to the timeshift.
         */
        // get value must not return a string representation, so we convert this always here
        // before memorisation
        if (Ext.isString(date)) {
            var v = Date.parseDate(date, Date.patterns.ISO8601Long);
            if (Ext.isDate(v)) {
                date = v;
            } else {
                date = Ext.form.DateField.prototype.parseDate.call(this, date);
            }
        }

        // preserve original datetime information
        this.fullDateTime = date;
        
        return Ext.form.DateField.superclass.setValue.call(this, this.formatDate(this.parseDate(date)));
    },

    // private
    parseDate : function(value){
        if(!value || Ext.isDate(value)){
            return value;
        }
        var v = Date.parseDate(value, this.format);
        if(!v && this.altFormats){
            if(!this.altFormatsArray){
                this.altFormatsArray = this.altFormats.split("|");
            }
            for(var i = 0, len = this.altFormatsArray.length; i < len && !v; i++){
                v = Date.parseDate(value, this.altFormatsArray[i]);
            }
        }
        return v;
    },

    // private
    onDestroy : function(){
		Ext.destroy(this.menu, this.keyNav);
        Ext.form.DateField.superclass.onDestroy.call(this);
    },

    // private
    formatDate : function(date){
        return Ext.isDate(date) ? date.dateFormat(this.format) : date;
    },

    /**
     * @method onTriggerClick
     * @hide
     */
    // private
    // Implements the default empty TriggerField.onTriggerClick function to display the DatePicker
    onTriggerClick : function(){
        if(this.disabled){
            return;
        }
        if(this.menu == null){
            this.menu = new Ext.menu.DateMenu({
                hideOnClick: false,
                focusOnSelect: false
            });
        }
        this.onFocus();
        Ext.apply(this.menu.picker,  {
            minDate : this.minValue,
            maxDate : this.maxValue,
            disabledDatesRE : this.disabledDatesRE,
            disabledDatesText : this.disabledDatesText,
            disabledDays : this.disabledDays,
            disabledDaysText : this.disabledDaysText,
            format : this.format,
            showToday : this.showToday,
            minText : String.format(this.minText, this.formatDate(this.minValue)),
            maxText : String.format(this.maxText, this.formatDate(this.maxValue))
        });
        this.menu.picker.setValue(this.getValue() || new Date());
        this.menu.show(this.el, "tl-bl?");
        this.menuEvents('on');
    },
    
    //private
    menuEvents: function(method){
        this.menu[method]('select', this.onSelect, this);
        this.menu[method]('hide', this.onMenuHide, this);
        this.menu[method]('show', this.onFocus, this);
    },
    
    onSelect: function(m, d){
        this.setValue(d);
        this.fireEvent('select', this, d);
        this.menu.hide();
    },
    
    onMenuHide: function(){
        this.focus(false, 60);
        this.menuEvents('un');
    },

    // private
    beforeBlur : function(){
        const v = this.parseDate(this.getRawValue()) ?? '';
        this.setValue(v);
    }

    /**
     * @cfg {Boolean} grow @hide
     */
    /**
     * @cfg {Number} growMin @hide
     */
    /**
     * @cfg {Number} growMax @hide
     */
    /**
     * @hide
     * @method autoSize
     */
});
Ext.reg('datefield', Ext.form.DateField);

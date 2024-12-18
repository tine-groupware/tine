/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */

Ext.apply(Date.prototype, {
    // private
    dateFormat : function(format) {
        const fmt = format ? (format?.Date || format) : ['wkday', 'medium'];
        if (_.isArray(fmt)) {
            format = fmt.map((v) => {
                return Locale.getTranslationData('Date', v) || v;
            }).join(' ');
        }

        if (Date.formatFunctions[format] == null) {
            Date.createFormat(format);
        }
        return Date.formatFunctions[format].call(this);
    },

    /**
     * Get the timezone abbreviation of the current date (equivalent to the format specifier 'T').
     *
     * Note: The date string returned by the javascript Date object's toString() method varies
     * between browsers (e.g. FF vs IE) and system region settings (e.g. IE in Asia vs IE in America).
     * For a given date string e.g. "Thu Oct 25 2007 22:55:35 GMT+0800 (Malay Peninsula Standard Time)",
     * getTimezone() first tries to get the timezone abbreviation from between a pair of parentheses
     * (which may or may not be present), failing which it proceeds to get the timezone abbreviation
     * from the GMT offset portion of the date string.
     * @return {String} The abbreviated timezone name (e.g. 'CST', 'PDT', 'EDT', 'MPST' ...).
     */
    getTimezone : function() {
        // the following list shows the differences between date strings from different browsers on a WinXP SP2 machine from an Asian locale:
        //
        // Opera  : "Thu, 25 Oct 2007 22:53:45 GMT+0800" -- shortest (weirdest) date string of the lot
        // Safari : "Thu Oct 25 2007 22:55:35 GMT+0800 (Malay Peninsula Standard Time)" -- value in parentheses always gives the correct timezone (same as FF)
        // FF     : "Thu Oct 25 2007 22:55:35 GMT+0800 (Malay Peninsula Standard Time)" -- value in parentheses always gives the correct timezone
        // IE     : "Thu Oct 25 22:54:35 UTC+0800 2007" -- (Asian system setting) look for 3-4 letter timezone abbrev
        // IE     : "Thu Oct 25 17:06:37 PDT 2007" -- (American system setting) look for 3-4 letter timezone abbrev
        //
        // this crazy regex attempts to guess the correct timezone abbreviation despite these differences.
        // step 1: (?:\((.*)\) -- find timezone in parentheses
        // step 2: ([A-Z]{1,4})(?:[\-+][0-9]{4})?(?: -?\d+)?) -- if nothing was found in step 1, find timezone from timezone offset portion of date string
        // step 3: remove all non uppercase characters found in step 1 and 2
        return this.toString().replace(/^.* (?:\((.*)\)|([A-Z]{1,4})(?:[\-+][0-9]{4})?(?: -?\d+)?)$/, "$1$2").replace(/[^A-Z]/g, "");
    },

    /**
     * Get the offset from GMT of the current date (equivalent to the format specifier 'O').
     * @param {Boolean} colon (optional) true to separate the hours and minutes with a colon (defaults to false).
     * @return {String} The 4-character offset string prefixed with + or - (e.g. '-0600').
     */
    getGMTOffset : function(colon) {
        return (this.getTimezoneOffset() > 0 ? "-" : "+")
            + String.leftPad(Math.floor(Math.abs(this.getTimezoneOffset()) / 60), 2, "0")
            + (colon ? ":" : "")
            + String.leftPad(Math.abs(this.getTimezoneOffset() % 60), 2, "0");
    },

    /**
     * Get the numeric day number of the year, adjusted for leap year.
     * @return {Number} 0 to 364 (365 in leap years).
     */
    getDayOfYear: function() {
        var num = 0,
            d = this.clone(),
            m = this.getMonth(),
            i;

        for (i = 0, d.setDate(1), d.setMonth(0); i < m; d.setMonth(++i)) {
            num += d.getDaysInMonth();
        }
        return num + this.getDate() - 1;
    },

    /**
     * Get the numeric ISO-8601 week number of the year.
     * (equivalent to the format specifier 'W', but without a leading zero).
     * @return {Number} 1 to 53
     */
    getWeekOfYear : function() {
        // adapted from http://www.merlyn.demon.co.uk/weekcalc.htm
        var ms1d = 864e5, // milliseconds in a day
            ms7d = 7 * ms1d; // milliseconds in a week

        return function() { // return a closure so constants get calculated only once
            var DC3 = Date.UTC(this.getFullYear(), this.getMonth(), this.getDate() + 3) / ms1d, // an Absolute Day Number
                AWN = Math.floor(DC3 / 7), // an Absolute Week Number
                Wyr = new Date(AWN * ms7d).getUTCFullYear();

            return AWN - Math.floor(Date.UTC(Wyr, 0, 7) / ms7d) + 1;
        }
    }(),

    /**
     * Checks if the current date falls within a leap year.
     * @return {Boolean} True if the current date falls within a leap year, false otherwise.
     */
    isLeapYear : function() {
        var year = this.getFullYear();
        return !!((year & 3) == 0 && (year % 100 || (year % 400 == 0 && year)));
    },

    /**
     * Get the first day of the current month, adjusted for leap year.  The returned value
     * is the numeric day index within the week (0-6) which can be used in conjunction with
     * the {@link #monthNames} array to retrieve the textual day name.
     * Example:
     * <pre><code>
     var dt = new Date('1/10/2007');
     document.write(Date.dayNames[dt.getFirstDayOfMonth()]); //output: 'Monday'
     </code></pre>
     * @return {Number} The day number (0-6).
     */
    getFirstDayOfMonth : function() {
        var day = (this.getDay() - (this.getDate() - 1)) % 7;
        return (day < 0) ? (day + 7) : day;
    },

    /**
     * Get the last day of the current month, adjusted for leap year.  The returned value
     * is the numeric day index within the week (0-6) which can be used in conjunction with
     * the {@link #monthNames} array to retrieve the textual day name.
     * Example:
     * <pre><code>
     var dt = new Date('1/10/2007');
     document.write(Date.dayNames[dt.getLastDayOfMonth()]); //output: 'Wednesday'
     </code></pre>
     * @return {Number} The day number (0-6).
     */
    getLastDayOfMonth : function() {
        return this.getLastDateOfMonth().getDay();
    },


    /**
     * Get the date of the first day of the month in which this date resides.
     * @return {Date}
     */
    getFirstDateOfMonth : function() {
        return new Date(this.getFullYear(), this.getMonth(), 1);
    },

    /**
     * Get the date of the last day of the month in which this date resides.
     * @return {Date}
     */
    getLastDateOfMonth : function() {
        return new Date(this.getFullYear(), this.getMonth(), this.getDaysInMonth());
    },

    /**
     * Get the number of days in the current month, adjusted for leap year.
     * @return {Number} The number of days in the month.
     */
    getDaysInMonth: function() {
        var daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        return function() { // return a closure for efficiency
            var m = this.getMonth();

            return m == 1 && this.isLeapYear() ? 29 : daysInMonth[m];
        }
    }(),

    /**
     * Get the English ordinal suffix of the current day (equivalent to the format specifier 'S').
     * @return {String} 'st, 'nd', 'rd' or 'th'.
     */
    getSuffix : function() {
        switch (this.getDate()) {
            case 1:
            case 21:
            case 31:
                return "st";
            case 2:
            case 22:
                return "nd";
            case 3:
            case 23:
                return "rd";
            default:
                return "th";
        }
    },

    /**
     * Creates and returns a new Date instance with the exact same date value as the called instance.
     * Dates are copied and passed by reference, so if a copied date variable is modified later, the original
     * variable will also be changed.  When the intention is to create a new variable that will not
     * modify the original instance, you should create a clone.
     *
     * Example of correctly cloning a date:
     * <pre><code>
     //wrong way:
     var orig = new Date('10/1/2006');
     var copy = orig;
     copy.setDate(5);
     document.write(orig);  //returns 'Thu Oct 05 2006'!

     //correct way:
     var orig = new Date('10/1/2006');
     var copy = orig.clone();
     copy.setDate(5);
     document.write(orig);  //returns 'Thu Oct 01 2006'
     </code></pre>
     * @return {Date} The new Date instance.
     */
    clone : function() {
        return new Date(this.getTime());
    },

    /**
     * Checks if the current date is affected by Daylight Saving Time (DST).
     * @return {Boolean} True if the current date is affected by DST.
     */
    isDST : function() {
        // adapted from http://extjs.com/forum/showthread.php?p=247172#post247172
        // courtesy of @geoffrey.mcgill
        return new Date(this.getFullYear(), 0, 1).getTimezoneOffset() != this.getTimezoneOffset();
    },

    /**
     * Attempts to clear all time information from this Date by setting the time to midnight of the same day,
     * automatically adjusting for Daylight Saving Time (DST) where applicable.
     * (note: DST timezone information for the browser's host operating system is assumed to be up-to-date)
     * @param {Boolean} clone true to create a clone of this date, clear the time and return it (defaults to false).
     * @return {Date} this or the clone.
     */
    clearTime : function(clone) {
        if (clone) {
            return this.clone().clearTime();
        }

        // get current date before clearing time
        var d = this.getDate();

        // clear time
        this.setHours(0);
        this.setMinutes(0);
        this.setSeconds(0);
        this.setMilliseconds(0);

        if (this.getDate() != d) { // account for DST (i.e. day of month changed when setting hour = 0)
            // note: DST adjustments are assumed to occur in multiples of 1 hour (this is almost always the case)
            // refer to http://www.timeanddate.com/time/aboutdst.html for the (rare) exceptions to this rule

            // increment hour until cloned date == current date
            for (var hr = 1, c = this.add(Date.HOUR, hr); c.getDate() != d; hr++, c = this.add(Date.HOUR, hr));

            this.setDate(d);
            this.setHours(c.getHours());
        }

        return this;
    },

    /**
     * Provides a convenient method for performing basic date arithmetic. This method
     * does not modify the Date instance being called - it creates and returns
     * a new Date instance containing the resulting date value.
     *
     * Examples:
     * <pre><code>
     // Basic usage:
     var dt = new Date('10/29/2006').add(Date.DAY, 5);
     document.write(dt); //returns 'Fri Nov 03 2006 00:00:00'

     // Negative values will be subtracted:
     var dt2 = new Date('10/1/2006').add(Date.DAY, -5);
     document.write(dt2); //returns 'Tue Sep 26 2006 00:00:00'

     // You can even chain several calls together in one line:
     var dt3 = new Date('10/1/2006').add(Date.DAY, 5).add(Date.HOUR, 8).add(Date.MINUTE, -30);
     document.write(dt3); //returns 'Fri Oct 06 2006 07:30:00'
     </code></pre>
     *
     * @param {String} interval A valid date interval enum value.
     * @param {Number} value The amount to add to the current date.
     * @return {Date} The new Date instance.
     */
    add : function(interval, value) {
        var d = this.clone();
        if (!interval || value === 0) return d;

        switch(interval.toLowerCase()) {
            case Date.MILLI:
                d.setMilliseconds(this.getMilliseconds() + value);
                break;
            case Date.SECOND:
                d.setSeconds(this.getSeconds() + value);
                break;
            case Date.MINUTE:
                d.setMinutes(this.getMinutes() + value);
                break;
            case Date.HOUR:
                d.setHours(this.getHours() + value);
                break;
            case Date.DAY:
                d.setDate(this.getDate() + value);
                break;
            case Date.MONTH:
                var day = this.getDate();
                if (day > 28) {
                    day = Math.min(day, this.getFirstDateOfMonth().add('mo', value).getLastDateOfMonth().getDate());
                }
                d.setDate(day);
                d.setMonth(this.getMonth() + value);
                break;
            case Date.YEAR:
                d.setFullYear(this.getFullYear() + value);
                break;
        }
        return d;
    },

    /**
     * Checks if this date falls on or between the given start and end dates.
     * @param {Date} start Start date
     * @param {Date} end End date
     * @return {Boolean} true if this date falls on or between the given start and end dates.
     */
    between : function(start, end) {
        var t = this.getTime();
        return start.getTime() <= t && t <= end.getTime();
    }
});


/**
 * Formats a date given the supplied format string.
 * @param {String} format The format string.
 * @return {String} The formatted date.
 * @method format
 */
Date.prototype.format = Date.prototype.dateFormat;

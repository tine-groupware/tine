/**
 * @class Ext.util.Format
 * Reusable data formatting functions
 * @singleton
 */
var trimRe = /^\s+|\s+$/g,
    stripTagsRE = /<\/?[^>]+>/gi,
    stripScriptsRe = /(?:<script.*?>)((\n|\r|.)*?)(?:<\/script>)/ig,
    nl2brRe = /\r?\n/g,
    // NOTE: we want to flag colorful emojis only, therefore we can't use the emoji-regex lib
    emojiRE = new RegExp('([' +
        '\u{23E9}-\u{23EF}\u{23F0}\u{23f3}' + // https://www.sql-und-xml.de/unicode-database/miscellaneous-technical.html
        // '\u{2600}-\u{26ff}' + // https://www.sql-und-xml.de/unicode-database/miscellaneous-symbols.html -> most of them are monochrome, exceptions next line
        '\u{2614}-\u{2615}\u{267F}\u{26A1}\u{26AA}\u{26AB}\u{26C4}\u{26C5}\u{26CE}\u{26D4}\u{26EA}\u{26F2}\u{26F3}\u{26F5}\u{26FA}\u{26FD}' +
        // '\u{2700}-\u{27bf}' + // https://www.sql-und-xml.de/unicode-database/dingbats.html -> most of them are monochrome
        '\u{2705}\u{270A}-\u{270B}\u{2728}\u{274C}\u{274E}\u{2753}-\u{2755}\u{2757}' +
        '\u{2b50}-\u{2b50}\u{2b55}' + // https://www.sql-und-xml.de/unicode-database/miscellaneous-symbols-and-arrows.html > partly as other of them are monochrome
        '\u{1f004}\u{1f0cf}\u{1F030}\u{1f170}\u{1F062}-\u{1F062}' + // cards, mahjong and domino
        '\u{1F18A}-\u{1F1AC}' + // https://www.sql-und-xml.de/unicode-database/enclosed-alphanumeric-supplement.html -> partly as other of them are monochrome
        '\u{1F201}\u{1F21A}\u{1F22F}\u{1F232}-\u{1F23A}\u{1F250}\u{1F251}' + // https://www.sql-und-xml.de/unicode-database/enclosed-ideographic-supplement.html -> partly as other of them are monochrome
        '\u{1f300}-\u{1f5ff}' + // https://www.sql-und-xml.de/unicode-database/miscellaneous-symbols-and-pictographs.html
        '\u{1f600}-\u{1f64f}' + // https://www.sql-und-xml.de/unicode-database/emoticons.html
        '\u{1f680}-\u{1f6ff}' + // https://www.sql-und-xml.de/unicode-database/transport-and-map-symbols.html
        '\u{1f900}-\u{1f9ff}' + // https://www.sql-und-xml.de/unicode-database/supplemental-symbols-and-pictographs.html
    '])[\u{1F3FB}-\u{1F3FF}]{0,1}', 'ug'); // skintone modifier

/*!
 * Ext JS Library 3.1.1
 * Copyright(c) 2006-2010 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */

// import emojiRegex from 'emoji-regex';
var Format = function(){
    return {
        /**
         * Truncate a string and add an ellipsis ('...') to the end if it exceeds the specified length
         * @param {String} value The string to truncate
         * @param {Number} length The maximum length to allow before truncating
         * @param {Boolean} word True to try to find a common work break
         * @return {String} The converted text
         */
        ellipsis : function(value, len, word){
            if(value && value.length > len){
                if(word){
                    var vs = value.substr(0, len - 2),
                        index = Math.max(vs.lastIndexOf(' '), vs.lastIndexOf('.'), vs.lastIndexOf('!'), vs.lastIndexOf('?'));
                    if(index == -1 || index < (len - 15)){
                        return value.substr(0, len - 3) + "...";
                    }else{
                        return vs.substr(0, index) + "...";
                    }
                } else{
                    return value.substr(0, len - 3) + "...";
                }
            }
            return value;
        },

        /**
         * Checks a reference and converts it to empty string if it is undefined
         * @param {Mixed} value Reference to check
         * @return {Mixed} Empty string if converted, otherwise the original value
         */
        undef : function(value){
            return value !== undefined ? value : "";
        },

        /**
         * Checks a reference and converts it to the default value if it's empty
         * @param {Mixed} value Reference to check
         * @param {String} defaultValue The value to insert of it's undefined (defaults to "")
         * @return {String}
         */
        defaultValue : function(value, defaultValue){
            return value !== undefined && value !== '' ? value : defaultValue;
        },

        /**
         * Convert certain characters (&, <, >, and ') to their HTML character equivalents for literal display in web pages.
         * @param {String} value The string to encode
         * @return {String} The encoded text
         */
        htmlEncode : function(value){
            if (_.get(value, 'isExpression')) {
                return value;
            }
            return !value ? value : String(value).replace(/&/g, "&amp;").replace(/>/g, "&gt;").replace(/</g, "&lt;").replace(/"/g, "&quot;")
                .replace(emojiRE, '<em class="emoji">$1</em>');
        },

        /**
         * wrap emojis like 😊 in value with <em class="emoji">😊</em>
         * usefull for dark mode
         * @param value
         * @returns {*|string}
         */
        wrapEmojis : function(value){
            return !value ? value :  String(value).replace(emojiRE, '<em class="emoji">$1</em>');
        },

        /**
         * Convert certain characters (<, >, and ') to their HTML character equivalents but modify them with wit a space and @@@ to separate them from links
         * so we can linkify these links without including characters that are not actual part of them (for example a tailing >) 
         * as they are found in Teams Invitations, in the second step we replace these strings by the proper Characters
         * 
         * @param {String} value The string to encode
         * @return {String} The encoded text
         */
        linkSaveHtmlEncodeStepOne : function(value){
            return !value ? value : String(value).replace(/>/g, " @@@gt@@@ ").replace(/</g, " @@@lt@@@ ").replace(/"/g, " @@@quot@@@ ");
        },

        /**
         * Replace our custom html characters with the proper characters
         *
         * @param {String} value The string to encode
         * @return {String} The encoded text
         */
        linkSaveHtmlEncodeStepTwo : function(value){
            return !value ? value : String(value).replaceAll(" @@@gt@@@ ", "&gt;").replaceAll(" @@@lt@@@ ", "&lt;").replaceAll(" @@@quot@@@ ", "&quot;");
        },

        /**
         * Convert certain characters (&, <, >, and ') from their HTML character equivalents.
         * @param {String} value The string to decode
         * @return {String} The decoded text
         */
        htmlDecode : function(value){
            return !value ? value : String(value).replace(/&gt;/g, ">").replace(/&lt;/g, "<").replace(/&quot;/g, '"').replace(/&amp;/g, "&");
        },

        /**
         * Trims any whitespace from either side of a string
         * @param {String} value The text to trim
         * @return {String} The trimmed text
         */
        trim : function(value){
            return String(value).replace(trimRe, "");
        },

        /**
         * Returns a substring from within an original string
         * @param {String} value The original text
         * @param {Number} start The start index of the substring
         * @param {Number} length The length of the substring
         * @return {String} The substring
         */
        substr : function(value, start, length){
            return String(value).substr(start, length);
        },

        /**
         * Converts a string to all lower case letters
         * @param {String} value The text to convert
         * @return {String} The converted text
         */
        lowercase : function(value){
            return String(value).toLowerCase();
        },

        /**
         * Converts a string to all upper case letters
         * @param {String} value The text to convert
         * @return {String} The converted text
         */
        uppercase : function(value){
            return String(value).toUpperCase();
        },

        /**
         * Converts the first character only of a string to upper case
         * @param {String} value The text to convert
         * @return {String} The converted text
         */
        capitalize : function(value){
            return !value ? value : value.charAt(0).toUpperCase() + value.substr(1).toLowerCase();
        },

        // private
        call : function(value, fn){
            if(arguments.length > 2){
                var args = Array.prototype.slice.call(arguments, 2);
                args.unshift(value);
                return eval(fn).apply(window, args);
            }else{
                return eval(fn).call(window, value);
            }
        },

        /**
         * Format a number as US currency
         * @param {Number/String} value The numeric value to format
         * @return {String} The formatted currency string
         */
        usMoney : function(v){
            v = (Math.round((v-0)*100))/100;
            v = (v == Math.floor(v)) ? v + ".00" : ((v*10 == Math.floor(v*10)) ? v + "0" : v);
            v = String(v);
            var ps = v.split('.'),
                whole = ps[0],
                sub = ps[1] ? '.'+ ps[1] : '.00',
                r = /(\d+)(\d{3})/;
            while (r.test(whole)) {
                whole = whole.replace(r, '$1' + ',' + '$2');
            }
            v = whole + sub;
            if(v.charAt(0) == '-'){
                return '-$' + v.substr(1);
            }
            return "$" +  v;
        },

        /**
         * Parse a value into a formatted date using the specified format pattern.
         * @param {String/Date} value The value to format (Strings must conform to the format expected by the javascript Date object's <a href="http://www.w3schools.com/jsref/jsref_parse.asp">parse()</a> method)
         * @param {String} format (optional) Any valid date format string (defaults to 'm/d/Y')
         * @return {String} The formatted date string
         */
        date : function(v, format){
            if(!v){
                return "";
            }
            if(!Ext.isDate(v)){
                v = new Date(Date.parse(v));
            }
            return v.dateFormat(format || "m/d/Y");
        },

        /**
         * Returns a date rendering function that can be reused to apply a date format multiple times efficiently
         * @param {String} format Any valid date format string
         * @return {Function} The date formatting function
         */
        dateRenderer : function(format){
            return function(v){
                return Ext.util.Format.date(v, format);
            };
        },
        
        /**
         * Strips all HTML tags
         * @param {Mixed} value The text from which to strip tags
         * @return {String} The stripped text
         */
        stripTags : function(v){
            return !v ? v : String(v).replace(stripTagsRE, "");
        },

        /**
         * Strips all script tags
         * @param {Mixed} value The text from which to strip script tags
         * @return {String} The stripped text
         */
        stripScripts : function(v){
            return !v ? v : String(v).replace(stripScriptsRe, "");
        },

        /**
         * Simple format for a file size (xxx bytes, xxx KB, xxx MB)
         * @param {Number/String} size The numeric value to format
         * @return {String} The formatted file size
         */
        fileSize : function(size){
            return Tine.Tinebase.common.byteFormatter(size);
        },

        /**
         * It does simple math for use in a template, for example:<pre><code>
         * var tpl = new Ext.Template('{value} * 10 = {value:math("* 10")}');
         * </code></pre>
         * @return {Function} A function that operates on the passed value.
         */
        math : function(){
            var fns = {};
            return function(v, a){
                if(!fns[a]){
                    fns[a] = new Function('v', 'return v ' + a + ';');
                }
                return fns[a](v);
            }
        }(),

        /**
         * Rounds the passed number to the required decimal precision.
         * @param {Number/String} value The numeric value to round.
         * @param {Number} precision The number of decimal places to which to round the first parameter's value.
         * @return {Number} The rounded value.
         */
        round : function(value, precision) {
            var result = Number(value);
            if (typeof precision == 'number') {
                precision = Math.pow(10, precision);
                result = Math.round(value * precision) / precision;
            }
            return result;
        },

        /**
         * Formats the number according to the format string.
         * <div style="margin-left:40px">examples (123456.789):
         * <div style="margin-left:10px">
         * 0 - (123456) show only digits, no precision<br>
         * 0.00 - (123456.78) show only digits, 2 precision<br>
         * 0.0000 - (123456.7890) show only digits, 4 precision<br>
         * 0,000 - (123,456) show comma and digits, no precision<br>
         * 0,000.00 - (123,456.78) show comma and digits, 2 precision<br>
         * 0,0.00 - (123,456.78) shortcut method, show comma and digits, 2 precision<br>
         * To reverse the grouping (,) and decimal (.) for international numbers, add /i to the end.
         * For example: 0.000,00/i
         * </div></div>
         * @param {Number} v The number to format.
         * @param {String} format The way you would like to format this text.
         * @return {String} The formatted number.
         */
        number: function(v, format) {
            if(!format){
		        return v;
		    }
		    v = Ext.num(v, NaN);
            if (isNaN(v)){
                return '';
            }
		    var comma = ',',
		        dec = '.',
		        i18n = false,
		        neg = v < 0;
		
		    v = Math.abs(v);
		    if(format.substr(format.length - 2) == '/i'){
		        format = format.substr(0, format.length - 2);
		        i18n = true;
		        comma = '.';
		        dec = ',';
		    }
		
		    var hasComma = format.indexOf(comma) != -1, 
		        psplit = (i18n ? format.replace(/[^\d\,]/g, '') : format.replace(/[^\d\.]/g, '')).split(dec);
		
		    if(1 < psplit.length){
		        v = v.toFixed(psplit[1].length);
		    }else if(2 < psplit.length){
		        throw ('NumberFormatException: invalid format, formats should have no more than 1 period: ' + format);
		    }else{
		        v = v.toFixed(0);
		    }
		
		    var fnum = v.toString();
		    if(hasComma){
		        psplit = fnum.split('.');
		
		        var cnum = psplit[0], parr = [], j = cnum.length, m = Math.floor(j / 3), n = cnum.length % 3 || 3;
		
		        for(var i = 0; i < j; i += n){
		            if(i != 0){
		                n = 3;
		            }
		            parr[parr.length] = cnum.substr(i, n);
		            m -= 1;
		        }
		        fnum = parr.join(comma);
		        if(psplit[1]){
		            fnum += dec + psplit[1];
		        }
		    }
		
		    return (neg ? '-' : '') + format.replace(/[\d,?\.?]+/, fnum);
        },

        /**
         * Returns a number rendering function that can be reused to apply a number format multiple times efficiently
         * @param {String} format Any valid number format string for {@link #number}
         * @return {Function} The number formatting function
         */
        numberRenderer : function(format){
            return function(v){
                return Ext.util.Format.number(v, format);
            };
        },

        /**
         * Selectively do a plural form of a word based on a numeric value. For example, in a template,
         * {commentCount:plural("Comment")}  would result in "1 Comment" if commentCount was 1 or would be "x Comments"
         * if the value is 0 or greater than 1.
         * @param {Number} value The value to compare against
         * @param {String} singular The singular form of the word
         * @param {String} plural (optional) The plural form of the word (defaults to the singular with an "s")
         */
        plural : function(v, s, p){
            return v +' ' + (v == 1 ? s : (p ? p : s+'s'));
        },
        
        /**
         * Converts newline characters to the HTML tag &lt;br/>
         * @return {String} The string with embedded &lt;br/> tags in place of newlines.
         * @param v
         */
        nl2br : function(v){
            return Ext.isEmpty(v) ? '' : v.replace(nl2brRe, '<br/>');
        },

        tab2nbsp : function(v, num = 4) {
            return Ext.isEmpty(v) ? '' : v.replace(/\t/, '&nbsp;'.repeat(num));
        }
    }
}();

module.exports = Format
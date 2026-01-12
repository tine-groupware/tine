/*
 * Tine 2.0
 *
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*global Ext, Tine, Locale*/

const { isObject, isArray, map, isString, get, escapeRegExp, each, find, compact, cloneDeep } = require('lodash')
const { htmlEncode, htmlDecode, date: dateFormat, number, round, capitalize} = require('Ext/util/Format')
const { parse, parsePurified, parseInline, parseInlinePurified } = require('util/markdown')

/**
 * static common helpers
 */
const common = {
    /**
     *
     * @param {String} part
     */
    getUrl: function(part) {
        var pathname = window.location.pathname.replace('index.php', ''),
            hostname = window.location.host,
            protocol = window.location.protocol,
            url;

        switch (part) {
            case 'path':
                url = pathname;
                break;
            case 'full':
            default:
                url = protocol + '//' + hostname + pathname;
                break;
        }

        return url;

    },

    /**
     * reload client
     *
     * @param {Object} options
     *      {Boolean} keepRegistry
     *      {Boolean} clearCache
     *      {Boolean} redirectAlways
     *      {String} redirectUrl
     */
    reload: async function(options) {
        options = options || {};

        if (! options.keepRegistry) {
            Tine.Tinebase.tineInit.isReloading = true;
            await Tine.Tinebase.tineInit.clearRegistry();
        }

        if (! options.redirectAlways && options.redirectUrl && options.redirectUrl != '') {
            // redirect only after logout (redirectAlways == false) - we can't wait for the browser here...
            // @todo how can we move that to the server?
            // @see https://github.com/tine20/tine20/issues/6236
            window.setTimeout(function () {
                window.location = options.redirectUrl;
            }, 500);
        } else {
            // give browser some time to clear registry
            window.setTimeout(function () {
                window.location.reload(!!options.clearCache);
            }, 500);
        }
    },

    showDebugConsole: function () {
        if (! Ext.debug) {
            var head = document.getElementsByTagName("head")[0],
                scriptTag = document.createElement("script");

            scriptTag.setAttribute("src", 'library/ExtJS/src/debug.js');
            scriptTag.setAttribute("type", "text/javascript");
            head.appendChild(scriptTag);

            var scriptEl = Ext.get(scriptTag);
            scriptEl.on('load', function () {
                Ext.log('debug console initialised');
            });
            scriptEl.on('fail', function () {
                Ext.Msg.alert('could not activate debug console');
            });
        } else {
            Ext.log('debug console reactivated');
        }
    },

    /**
     * Returns emails from string or recipient token
     * @param emails
     */
    emailRenderer: function (emails) {
        if (Array.isArray(emails)) {
            emails = emails.map((email) => {
                return email?.email ?? email;
            })
        }
        return emails;
    },

    /**
     * Returns localised date and time string
     *
     * @param {mixed} $_iso8601
     * @param metadata
     * @see date
     * @return {String} localised date and time
     */
    dateTimeRenderer: function ($_iso8601, metadata) {
        const data = Tine.Tinebase.common.dateRenderer.call(this, $_iso8601, metadata) + ' '
            + Tine.Tinebase.common.timeRenderer.call(this, $_iso8601, metadata);
        if (metadata) metadata.css = 'tine-gird-cell-datetime';
        return data;
    },

    /**
     * Returns localised date string
     *
     * @param {mixed} date
     * @param metadata
     * @param formatArray
     * @see Ext.util.Format.date
     * @return {String} localised date
     */
    dateRenderer: function (date, metadata) {
        const format = this?.format ? (this.format?.Date || this.format) : ['wkday', 'medium'];
        const dateObj = date instanceof Date ? date : Date.parseDate(date, Date.patterns.ISO8601Long);
        const formatDate = (key) => key === 'wkday'
            ? dateObj.format('l').substr(0, 2)
            : dateFormat(dateObj, Locale.getTranslationData('Date', key));
        
        const isGridCell = isObject(metadata) && metadata.hasOwnProperty('cellAttr');
        if (isGridCell) {
            metadata.css = (metadata.css || '') + ' tine-gird-cell-date';
        }
        
        return dateObj ? map(format, (key) => {
            return isGridCell ? `<span class="date-renderer-${key}">${formatDate(key)}</span>` : formatDate(key);
        }).join(' ') : '';
    },
  
    /**
     * Returns localised number string with two digits if no format is given
     *
     * @param {Number} v The number to format.
     * @param {String} format The way you would like to format this text.
     * @see Ext.util.Format.number
     *
     * @return {String} The formatted number.
     */
    floatRenderer: function(v, format) {
        if (! format) {
            // default format by locale and with two decimals
            format = '0' + Tine.Tinebase.registry.get('thousandSeparator') + '000' + Tine.Tinebase.registry.get('decimalSeparator') + '00';
        }
        return number(v, format);
    },

    /**
     * Renders a float or integer as percent
     *
     * @param {Number} v The number to format.
     * @see Ext.util.Format.number
     *
     * @return {String} The formatted number.
     */
    percentRenderer: function(v, type, nullable) {
        if (['', null, undefined].indexOf(v) >= 0 && nullable) {
            return '';
        }

        if (! Ext.isNumber(v)) {
            v = parseInt(v, 10) || 0;
        }

        v = number(v, (type == 'float' ? '0.00' : '0'));

        if (type == 'float') {
            var decimalSeparator = Tine.Tinebase.registry.get('decimalSeparator');
            if (decimalSeparator == ',') {
                v = v.replace(/\./, ',');
            }
        }

        return v + ' %';
    },

    /**
     * Returns localised time string
     *
     * @param {mixed} date
     * @param metadata
     * @see Ext.util.Format.date
     * @return {String} localised time
     */
    timeRenderer: function (date, metadata) {
        const format = this?.format ? (this.format?.Time || this.format) : ['medium'];
        const dateObj = date instanceof Date ? date : Date.parseDate(date, Date.patterns.ISO8601Time);
        const formatTime = (key) => dateFormat(dateObj, Locale.getTranslationData('Time', key));
        
        const isGridCell = isObject(metadata) && metadata.hasOwnProperty('cellAttr');
        if (isGridCell) {
            metadata.css = (metadata.css || '') + ' tine-gird-cell-time';
        }
        
        return dateObj ? map(format, (key) => {
            return isGridCell ? `<span class="time-renderer-${key}">${formatTime(key)}</span>` : formatTime(key);
        }).join(' ') : '';
    },

    /**
     * renders bytes for filesize
     * @param {Integer} value
     * @param {Object} metadata
     * @param {Tine.Tinebase.data.Record} record
     * @param {Integer} decimals
     * @param {Boolean} useDecimalValues
     * @return {String}
     */
    byteRenderer: function (value, metadata, record, decimals, useDecimalValues) {
        if (isNaN(parseInt(value, 10))) {
            return '';
        }
        return Tine.Tinebase.common.byteFormatter(value, null, decimals, useDecimalValues);
    },

    /**
     * format byte values
     * @param {String} value
     * @param {Boolean} forceUnit
     * @param {Integer} decimals
     * @param {Boolean} useDecimalValues
     */
    byteFormatter: function(value, forceUnit, decimals, useDecimalValues) {
        value = parseInt(value, 10);
        decimals = Ext.isNumber(decimals) ? decimals : 2;
        var decimalSeparator = Tine.Tinebase.registry.get('decimalSeparator'),
            suffix = useDecimalValues ?
                ['Bytes', 'Bytes', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'] :
                ['Bytes', 'Bytes', 'kiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'] ,
            divisor = useDecimalValues ? 1000 : 1024;

        if (forceUnit) {
            var i = suffix.indexOf(forceUnit);
            i = (i == -1) ? 0 : i;
        } else {
            for (var i=0,j; i<suffix.length; i++) {
                if (value < Math.pow(divisor, i)) break;
            }
        }
        value = ((i<=1) ? value : round(value/(Math.pow(divisor, Math.max(1, i-1))), decimals)) + ' ' + suffix[i];

        return String(value).replace('.', decimalSeparator);
    },

    /**
     * Returns rendered tags for grids
     *
     * @param {mixed} tags
     * @return {String} tags as colored squares with qtips
     *
     * TODO add style for tag divs
     */
    tagsRenderer: function (tags) {
        let result = '';
        if (tags) {
            for (var i = 0; i < tags.length; i += 1) {
                if (tags[i] && tags[i].name) {
                    var qtipText = Tine.Tinebase.common.doubleEncode(tags[i].name);
                    if (tags[i].description) {
                        qtipText += ' | ' + Tine.Tinebase.common.doubleEncode(tags[i].description);
                    }
                    if (tags[i].occurrence) {
                        qtipText += ' (' + i18n._('Usage:&#160;') + tags[i].occurrence + ')';
                    }
                    result += '<div ext:qtip="' + qtipText + '" class="tb-grid-tags dark-reverse" style="background-color:' + (tags[i].color ? tags[i].color : '#fff') + ';">&#160;</div>';
                }
            }
        }

        return result;
    },

    /**
     * render single tag
     *
     * @param {Tine.Tinebase.Model.Tag} tag
     */
    tagRenderer: function(tag) {
        if (! Tine.Tinebase.common.tagRenderer.tpl) {
            Tine.Tinebase.common.tagRenderer.tpl = new Ext.XTemplate(
                '<div class="tb-grid-tags dark-reverse" style="background-color:{values.color};">&#160;</div>',
                '<div class="x-widget-tag-tagitem-text" ext:qtip="',
                    '{[this.encode(values.name)]}',
                    '<tpl if="type == \'personal\' ">&nbsp;<i>(' + i18n._('personal') + ')</i></tpl>',
                    '</i>&nbsp;[{occurrence}]',
                    '<tpl if="description != null && description.length &gt; 1"><hr>{[this.encode(values.description)]}</tpl>" >',

                    '&nbsp;{[this.encode(values.name)]}',
                    '<tpl if="type == \'personal\' ">&nbsp;<i>(' + i18n._('personal') + ')</i></tpl>',
                '</div>',
            {
                encode: function(value) {
                     if (value) {
                        return Tine.Tinebase.common.doubleEncode(value);
                    } else {
                        return '';
                    }
                }
            }).compile();
        }

        var result =  i18n._('No Information');

        if (tag && Ext.isFunction(tag.beginEdit)) {
            // support records
            tag = tag.data;
        } else if (arguments[2] && Ext.isFunction(arguments[2].beginEdit)) {
            // support grid renderers
            tag = arguments[2].data;
        }

        // non objects are treated as ids and -> No Information
        if (Ext.isObject(tag)) {
            result = Tine.Tinebase.common.tagRenderer.tpl.apply(tag);
        }

        return result;
    },

    /**
     * markdown renderer - fulltext, special type
     * @param {String} v The string to format.
     * @param {Object} metadata Grid metadata object
     * @param {Object} record Grid record
     * @return {String} The formatted string.
     */
    markdownRenderer: function(v, metadata, record) {
        if ([null, undefined].indexOf(v) >= 0) {
            return '';
        }

        const cellId = `markdown-cell-${Ext.id()}`;
        const initialContent = `<div id="${cellId}" class="tb-markdown-loading">${Ext.util.Format.htmlEncode(v)}</div>`;

        parsePurified(v).then(html => {
            const element = document.getElementById(cellId);
            if (element) {
                element.className = 'tb-markdown-rendered';
                element.innerHTML = html;
            }
        }).catch(err => {
            console.error('Markdown rendering error:', err);
            const element = document.getElementById(cellId);
            if (element) {
                element.className = 'tb-markdown-error';
                element.innerHTML = Ext.util.Format.htmlEncode(v);
            }
        });

        return initialContent;
    },

    /**
     * Returns rendered containers
     *
     * @TODO show qtip with grants
     *
     * @param {mixed} container
     * @return {String}
     */
    containerRenderer: function(container, metaData) {
        // lazy init tempalte
        if (! Tine.Tinebase.common.containerRenderer.tpl) {
            Tine.Tinebase.common.containerRenderer.tpl = new Ext.XTemplate(
                '<div class="x-tree-node-leaf x-unselectable file">',
                    '<img class="x-tree-node-icon" unselectable="on" src="', Ext.BLANK_IMAGE_URL, '">',
                    '<span style="color: {color};">&nbsp;&#9673;&nbsp</span>',
                    '<span> ', '{name}','</span>',
                '</div>'
            ).compile();
        }

        var result =  i18n._('No Information');

        // support container records
        if (container && Ext.isFunction(container.beginEdit)) {
            container = container.data;
        }

        // non objects are treated as ids and -> No Information
        if (Ext.isObject(container)) {
            var name = Ext.isFunction(container.beginEdit) ? container.get('name') : container.name,
                color = Ext.isFunction(container.beginEdit) ? container.get('color') : container.color;

            if (name) {
                result = Tine.Tinebase.common.containerRenderer.tpl.apply({
                    name: htmlEncode(name).replace(/ /g,"&nbsp;"),
                    color: color ? color : '#808080'
                });
            } else if (Ext.isObject(metaData)) {
                metaData.css = 'x-form-empty-field';
            }
        }

        return result;
    },

    applicationRenderer: function(applicationId) {
        const app = Tine.Tinebase.appMgr.getById(applicationId?.id || applicationId);
        if (!app) return '';

        const translation = new Locale.Gettext();
        translation.textdomain(app.name);
        return translation.gettext(app.name);
    },

    /**
     * Returns rendered relations
     *
     * @param {mixed} container
     * @return {String}
     *
     * TODO use/invent renderer registry to show more information on relations
     */
    relationsRenderer: function(relations, metaData) {
        if (isString(relations)) {
            // e.g. history panel
            return relations;
        }

        // _('No Access') - we need this string in other apps if relation is not shown e.g. record_removed_reason
        var result = '';
        if (relations) {
            for (var i = 0; i < relations.length; i += 1) {
                if (relations[i]) {
                    var qtipText = Tine.Tinebase.common.doubleEncode(relations[i].type);
                    result += '<div ext:qtip="' + qtipText + '" class="tb-grid-tags dark-reverse" style="background-color:white"' + ';">&#160;</div>';
                }
            }
        }
        return result;
    },

    /**
     * Returns prettyfied minutes
     *
     * @param  {Number} minutes
     * @param  format -> {0} will be replaced by Hours, {1} with minutes
     * @param  {String} leadingZeros add leading zeros for given item {i|H}
     * @return {String}
     */
    minutesRenderer: function (minutes, format, leadingZeros) {
        let i = minutes % 60,
            H = Math.floor(minutes / 60);

        if (leadingZeros && (Ext.isString(leadingZeros) || leadingZeros === true)) {
            if (leadingZeros === true || (leadingZeros.match(/i/) && String(i).length === 1)) {
                i = '0' + String(i);
            }
            if (leadingZeros === true || (leadingZeros.match(/H/) && String(H).length === 1)) {
                H = '0' + String(H);
            }
        }

        if (! format || ! Ext.isString(format)) {
            const isGridCell = isObject(format) && format.hasOwnProperty('cellAttr');
            const mediumRenderer = (i, H) => {
                const minutes = i> 0 ? String.format(i18n.ngettext('{0} minute', '{0} minutes', i), i) : '';
                const hours = H > 0 ? String.format(i18n.ngettext('{0} hour', '{0} hours', H), H) : '';
                const duration = hours && minutes ? `${hours}, ${minutes}` : hours || minutes;
                return `<span class="duration-renderer-medium">${duration}</span>`;
            }
            const smallRenderer = (i, H) => {
                if (i && i < 10) i = `0${i}`;
                return `<span class="duration-renderer-small">${H || '0'}:${i || '00'}</span>`;
            }
            return isGridCell ? `${mediumRenderer(i, H)}${smallRenderer(i, H)}` : mediumRenderer(i, H);
        }

        return String.format(format, H, i);
    },

    /**
     * Returns prettyfied seconds
     *
     * @param  {Number} seconds
     * @return {String}
     */
    secondsRenderer: function (seconds) {

        var s = seconds % 60,
            m = Math.floor(seconds / 60),
            result = '';

        var secondResult = String.format(i18n.ngettext('{0} second', '{0} seconds', s), s);

        if (m) {
            result = Tine.Tinebase.common.minutesRenderer(m);
        }

        if (s) {
            if (result !== '') {
                result += ', ';
            }
            result += secondResult;
        }

        return result;
    },

    /**
     * Returns the formated username
     *
     * @param {object} account object
     * @return {string} formated user display name
     */
    usernameRenderer: function (accountObject) {
        var result = (accountObject) ? accountObject.accountDisplayName : '';

        return htmlEncode(result);
    },

    /**
     * Returns a username or groupname with according icon in front
     */
    accountRenderer: function (accountObject, metadata, record, rowIndex, colIndex, store) {
        if (! accountObject) {
            return '';
        }
        let type, iconCls, displayName, email;

        if (accountObject.accountDisplayName) {
            type = get(record, 'data.account_type', 'user');
            const contactRecord = Tine.Tinebase.data.Record.setFromJson({
                n_fileas: accountObject.accountDisplayName, 
                email: accountObject?.accountEmailAddress
            }, Tine.Addressbook.Model.Contact);
            displayName = contactRecord.getTitle();
        } else if (accountObject.name && ! get(record, 'data.account_type')) {
            type = 'group';
            displayName = accountObject.name;
        } else if (record && record.data.name) {
            type = record.data.type;
            displayName = record.data.name;

        // so odd, - new records, picked via pickerGridPanel
        } else if (record && record.data.account_name) {
            type = record.data.account_type;
            displayName = get(record, 'data.account_name.name', record.data.account_name);
        }

        if (displayName === 'Anyone') {
            displayName = i18n._(displayName);
            type = 'group';
        }

        iconCls = 'tine-grid-row-action-icon renderer renderer_account' + capitalize(type) + 'Icon';
        return '<div class="' + iconCls  + '">&#160;</div>' + htmlEncode(displayName || '');
    },

    /**
     * Returns account type icon
     *
     * @return String
     */
    accountTypeRenderer: function (type) {
        var iconCls = 'tine-grid-row-action-icon ' + (type === 'user' ? 'renderer_accountUserIcon' : 'renderer_accountGroupIcon');

        return '<div style="background-position: 0px" class="' + iconCls  + '">&#160;</div>';
    },

    /**
     * Returns dropdown hint icon for editor grid columns with comboboxes
     *
     * @return String
     */
    cellEditorHintRenderer: function (value) {
        return '<div style="position:relative">' + value + '<div class="tine-grid-cell-hint">&#160;</div></div>';
    },

    /**
     * return yes or no in the selected language for a boolean value
     *
     * @param {string} value
     * @return {string}
     */
    booleanRenderer: function (value) {
        const translationString = String.format("{0}",(Boolean(value) && value !== "0")
            ? Locale.getTranslationData('Question', 'yes')
            : Locale.getTranslationData('Question', 'no'));

        return translationString.substr(0, translationString.indexOf(':'));
    },

    /**
     * i18n renderer
     *
     * NOTE: needs to be bound to i18n object!
     *
     * renderer: Tine.Tinebase.common.i18nRenderer.createDelegate(this.app.i18n)
     * @param original
     */
    i18nRenderer: function(original) {
        return this._hidden(original);
    },

    /**
     * color renderer
     *
     * @param color
     */
    colorRenderer: function(color) {
        // normalize
        color = String(color).replace('#', '');

        return '<div class="dark-reverse" style="background-color: #' + htmlEncode(color) + '">&#160;</div>';
    },

    urlRenderer: function (url) {
        return '<a href=' + Tine.Tinebase.EncodingHelper.encode(url, 'href') + ' target="_blank">' + Tine.Tinebase.EncodingHelper.encode(url, 'shorttext') + '</a>';
    },

    /**
     * currency renderer
     *
     * @param currency
     */
    currencyRenderer: function(currency) {
        let result = currency;
        const currencyList = Locale.getTranslationList('CurrencyList');
        if (currencyList) {
            for (const [shortName, translatedName] of Object.entries(currencyList)) {
                const values = String(translatedName).split(':');
                if (shortName.includes(currency)) {
                    result = shortName !== values[1] ? `${shortName} - ${values[1]}` : shortName;
                }
            }
        }
        return result;
    },

    countryRenderer: function (v) {
        return Locale.getTranslationData('CountryList', v);
    },

    /**
     * foreign record renderer
     *
     * @param record
     * @param metadata
     *
     * TODO use title fn? allow to configure displayed field(s)?
     */
    foreignRecordRenderer: function(record, metaData) {
        return record && record.name ? record.name : '';
    },

    /**
     * sorts account/user objects
     *
     * @param {Object|String} user_id
     * @return {String}
     */
    accountSortType: function(user_id) {
        if (user_id && user_id.accountDisplayName) {
            return user_id.accountDisplayName;
        } else if (user_id && user_id.n_fileas) {
            return user_id.n_fileas;
        } else if (user_id && user_id.name) {
            return user_id.name;
        } else {
            return user_id;
        }
    },

    /**
     * sorts records
     *
     * @param {Object} record
     * @return {String}
     */
    recordSortType: function(record) {
        if (record && Ext.isFunction(record.getTitle)) {
            return record.getTitle();
        } else if (record && record.id) {
            return record.id;
        } else {
            return record;
        }
    },

    /**
     * check whether given value can be interpreted as true
     *
     * @param {String|Integer|Boolean} value
     * @return {Boolean}
     */
    isTrue: function (value) {
        return value === 1 || value === '1' || value === true || value === 'true';
    },

    /**
     * check whether object is empty (has no property)
     *
     * @param {Object} obj
     * @return {Boolean}
     */
    isEmptyObject: function (obj) {
        for (var name in obj) {
            if (obj.hasOwnProperty(name)) {
                return false;
            }
        }
        return true;
    },

    /**
     * clone function
     *
     * @param {Object/Array} o Object or array to clone
     * @return {Object/Array} Deep clone of an object or an array
     */
    clone: function (o) {
        if (! o || 'object' !== typeof o) {
            return o;
        }

        if ('function' === typeof o.clone) {
            return o.clone();
        }

        var c = '[object Array]' === Object.prototype.toString.call(o) ? [] : {},
            p, v;

        for (p in o) {
            if (o.hasOwnProperty(p)) {
                v = o[p];
                if (v && 'object' === typeof v) {
                    c[p] = Tine.Tinebase.common.clone(v);
                }
                else {
                    c[p] = v;
                }
            }
        }
        return c;
    },

    /**
     * assert that given object is comparable
     *
     * @param {mixed} o
     * @param {bool} clone
     * @return {mixed} o
     */
    assertComparable: function(o, clone) {
        if (isObject(o) || isArray(o)) {
            if (clone) o = cloneDeep(o);
            common.applyComparableToString(o);
        }

        return o;
    },

    /**
     * apply Ext.encode as toString functino to given object
     *
     * @param {mixed} o
     */
    applyComparableToString: function(o) {
        o.toString = function() {return JSON.stringify(o)};
    },

    /**
     * check if user has right to view/manage this application/resource
     *
     * @param   {String}      right (view, admin, manage)
     * @param   {String}      application
     * @param   {String}      resource (for example roles, accounts, ...)
     * @returns {Boolean}
     */
    hasRight: function (right, application, resource) {

        if (! (Tine && Tine[application] && Tine[application].registry && Tine[application].registry.get('rights'))) {
            if (Tine.Tinebase.tineInit.isReloading) {
                Tine.log.info('Tine 2.0 is reloading ...');
            } else if (! Tine.Tinebase.appMgr) {
                console.error('Tine.Tinebase.appMgr not yet available');
            } else if (Tine.Tinebase.appMgr.get(application)) {
                console.error('Tine.' + application + '.rights is not available, initialisation Error! Reloading app.');
                // reload registry/mainscreen - registry problem?
                Tine.Tinebase.common.reload({});
            }
            return false;
        }
        var userRights = Tine[application].registry.get('rights'),
            allAppRights = Tine[application].registry.get('allrights');

        if (allAppRights && right === 'view' && allAppRights.indexOf('view') < 0) {
            // switch to run as app has no view right
            right = 'run';
        }

        var result = false;

        for (var i = 0; i < userRights.length; i += 1) {
            if (userRights[i] === 'admin') {
                result = true;
                break;
            }

            if (right === 'view' && (userRights[i] === 'view_' + resource || userRights[i] === 'manage_' + resource)) {
                result = true;
                break;
            }

            if (right === 'manage' && userRights[i] === 'manage_' + resource) {
                result = true;
                break;
            }

            if (right === userRights[i]) {
                result = true;
                break;
            }
        }

        return result;
    },

    /**
     * returns random integer number
     * @param {Integer} min
     * @param {Integer} max
     * @return {Integer}
     */
    getRandomNumber: function (min, max) {
        if (min > max) {
            return -1;
        }
        if (min === max) {
            return min;
        }
        return min + parseInt(Math.random() * (max - min + 1), 10);
    },
    /**
     * HTML-encodes a string twice
     * @param {String} value
     * @return {String}
     */
    doubleEncode: function(value) {
        return htmlEncode(htmlEncode(value));
    },

    /**
     * simple html to text conversion
     *
     * @param html
     * @returns {String}
     */
    html2text: function(html) {
        const text = html.replace(/\n/g, ' ')
            .replace(/(<br[^>]*>)/g, '\n--br')
            .replace(/(<li[^>]*>)/g, '\n * ')
            .replace(/<(blockquote|div|dl|dt|dd|form|h1|h2|h3|h4|h5|h6|hr|p|pre|table|tr|td|li|section|header|footer)[^>]*>(?!\s*\<\/\1\>)/g, '\n--block')
            .replace(/<style(.+?)\/style>/g, '')
            .replace(/<(.+?)>/g, '')
            .replace(/&nbsp;/g, ' ')
            .replace(/--block(\n--block)+/g, '--block')
            .replace(/--block\n--br/g, '')
            .replace(/(--block|--br)/g, '');

        return htmlDecode(text);
    },

    /**
     * linkify text
     *
     * @param {String} text
     * @param {Ext.Element|Function} cb
     */
    linkifyText: function(text, cb, scope) {
        require.ensure(["linkifyjs", "linkify-html"], function() {
            const { default: linkifyHtml } = require('linkify-html');
            let linkifyed = linkifyHtml(text);

            if (cb) {
                if (Ext.isFunction(cb)) {
                    cb.call(scope || window, linkifyed);
                } else {
                    cb.update(linkifyed);
                }
            }
        }, 'Tinebase/js/linkify');
    },

    /**
     * find record from target
     *
     * @param target
     */
    findRecordFromTarget: function(target) {
        let recordClass = null;
        let recordId = null;
        // find record from dataset
        if (target?.dom?.dataset && target.dom.dataset.recordClass) {
            recordClass = target.dom.dataset.recordClass;
            recordId = target.dom.dataset.recordId;
        }

        // find record from deeplink
        const urlRegex = '^' + escapeRegExp(Tine.Tinebase.common.getUrl());
        const recordRegex = '#\/(?<appName>[a-zA-Z]+)\/(?<modelName>[a-zA-Z]+)\/(?<recordId>[a-f0-9]{40,})';
        const regex = new RegExp(`${urlRegex}${recordRegex}`);
        const matches = target?.dom?.href.match(regex);
        if (matches?.groups?.appName && matches?.groups?.modelName && matches?.groups?.recordId) {
            recordClass = `${matches.groups.appName}_Model_${matches.groups.modelName}`;
            recordId = matches.groups.recordId;
        }

        return [recordClass, recordId];
    },

    /**
     * linkify text
     *
     * @param {String} text
     * @param {Ext.Element|Function} cb
     */
    findEmailData: function(text, cb, scope) {
        const matches = text.match(/(:(?<email>[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9._-]+))(:(?<name>.*))?/mi);
        return matches?.groups ?? null;
    },

    /**
     * find contacts by email string
     *
     * return recipients token
     *
     * @param addresses
     */
    findContactsByEmailString: async function (addresses) {
        const result = [];

        if (!addresses || addresses === '') {
            return result;
        }

        addresses = addresses.replaceAll(';', ',');
        addresses = addresses.replace(/['"]+/g, '');
        addresses = compact(addresses.split(','));
        
        const {default: addrs} = await import(/* webpackChunkName: "Tinebase/js/email-addresses" */ 'email-addresses')

        const parsedList = [];
        addresses.forEach((address) => {
            address = addrs.parseOneAddress({
                input: address, 
                partial: true
            });
            if (address) parsedList.push(address);
        })

        const emails = map(parsedList, (parsed) => {
            return parsed.address ?? '';
        });
        const names = map(parsedList, (parsed) => {
            return parsed?.name ?? '';
        });

        const {results: tokens} = await Tine.Addressbook.searchRecipientTokensByEmailArrays(emails, names);

        each(parsedList, (parsed) => {
            if (!parsed.address) return;
            const existingToken = find(tokens, function (token) {
                return parsed.address === token['email'];
            });

            const token = existingToken ?? {
                'email': parsed.address ?? '',
                'email_type_field': '',
                'type': '',
                'n_fileas': '',
                'name': parsed?.name ?? '',
                'contact_record': ''
            };

            result.push(token);
        });

        return result;
    },

    /**
     * Confirm application restart
     *
     * @param Boolean closewindow
     */
    confirmApplicationRestart: function (closewindow) {
        Ext.Msg.confirm(i18n._('Confirm'), i18n._('Restart application to apply the new configuration?'), function (btn) {
            if (btn == 'yes') {
                // reload mainscreen to make sure registry gets updated
                Tine.Tinebase.common.reload();
                if (closewindow) {
                    window.close();
                }
            }
        }, this);
    },

    /**
     * Math.trunc polyfill
     *
     * https://developer.mozilla.org/de/docs/Web/JavaScript/Reference/Global_Objects/Math/trunc
     *
     * @param x
     * @return {*}
     */
    trunc: function (x) {
        if (isNaN(x)) {
            return NaN;
        }
        if (x > 0) {
            return Math.floor(x);
        }
        return Math.ceil(x);
    },

    /**
     * check valid email domain (if email domain is set in config)
     *
     * @param {String} email
     */
    checkEmailDomain: function(email) {
        const result = {
            isValid: true,
            errorMessage: false,
            isInternalDomain: false,
        }

        if (!email || !Tine.Tinebase.registry.get('primarydomain')) {
            return result;
        }

        const matchedDomainType = this.getMatchedEmailDomainType(email);
        const isInternalDomain = ['primarydomain', 'secondarydomains'].includes(matchedDomainType);
        const isExternalDomain = ['additionalexternaldomains', 'custom'].includes(matchedDomainType);
        const allowAnyExternalDomains = Tine.Tinebase.registry.get('allowAnyExternalDomains');
        const manageSmtpEmailUser = Tine.Tinebase.registry.get('manageSmtpEmailUser');

        result.isInternalDomain = isInternalDomain;
        result.isValid = isInternalDomain || (manageSmtpEmailUser && isExternalDomain && (allowAnyExternalDomains || matchedDomainType === 'additionalexternaldomains'));

        if (!result.isValid) {
            result.errorMessage = i18n._("Domain is not allowed. Check your SMTP domain configuration.") + '<br>';
            result.errorMessage += '<br>' + i18n._("Allowed Domains") + ': <br>';

            const allowDomains = Tine.Tinebase.common.getAllowedDomains();
            Object.values(allowDomains).flat().forEach((domain) => {
                if (domain !== '') {
                    result.errorMessage += '- ' + domain + '<br>';
                }
            })
        }

        return result;
    },

    getMatchedEmailDomainType(email) {
        if (!email) return false;
        const emailData = email.split('@');
        if (!emailData[1]) return false;

        const domain = emailData[1];
        const domainRegex = '[a-z0-9-\\.]+\\.[a-z]{2,63}';
        const emailRegex = new RegExp(`^([a-z0-9_+\\-\\.&]+@${domainRegex})$`, 'i');
        let matchedDomainType = emailRegex.test(email) ? 'custom' : false;

        const allowedDomains = this.getAllowedDomains();
        Object.entries(allowedDomains).forEach(([key, domains]) => {
            if (domains.includes(domain)) {
                matchedDomainType = key;
            }
        });
        return matchedDomainType;
    },

    getAllowedDomains: function() {
        const allowedDomains = {
            primarydomain: [],
            secondarydomains: [],
            additionalexternaldomains: []
        };

        Object.keys(allowedDomains).forEach(type => {
            const domains = Tine.Tinebase.registry.get(type);
            if (Ext.isString(domains)) {
                allowedDomains[type] = domains.split(',')
                    .map(d => d.trim())
                    .filter(d => d.length > 0);
            }
        });

        return allowedDomains;
    },

    getMimeIconCls: function(mimeType) {
        return 'mime-content-type-' + mimeType.replace(/\/.*$/, '') +
            ' mime-suffix-' + (mimeType.match(/\+/) ? mimeType.replace(/^.*\+/, '') : 'none') +
            ' mime-type-' + mimeType
                .replace(/\//g, '-slash-')
                .replace(/\./g, '-dot-')
                .replace(/\+/g, '-plus-');
    },
};

module.exports = common;

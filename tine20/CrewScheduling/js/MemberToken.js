/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.CrewScheduling');

require('../css/memberToken.css');

/**
 * @namespace   Tine.CrewScheduling
 * @class       Tine.CrewScheduling.MemberToken
 * @extends     Ext.Template
 *
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.CrewScheduling.MemberToken = function() {
    this.app = Tine.Tinebase.appMgr.get('CrewScheduling');

    Tine.CrewScheduling.MemberToken.superclass.constructor.call(this,
        '<tpl for=".">',
            '<table class="cs-member-token" style="{[this.getTokenStyles(values)]}" tine-cs-token-id="{signatureId}"><tr>',
                '<td class="cs-count">{[Ext.isNumber(values.count) ? values.count : "?"]}</td>',
                '<td class="cs-initials" ext:qtip="{[this.doubleEncode(values.n_fileas)]}">{initials}</td>',
                '<td class="cs-name">{n_fileas}</td>',
                '<td class="cs-dates">{[this.renderDays(values.days || [])]}</td>',
                '<td class="cs-partners">{[this.renderPartners(values.partners || [])]}</td>',
                '<td class="cs-roles">{[this.renderRoles(values.crewscheduling_roles || values.roles || [])]}</td>',
            '</tr></table>',
        '</tpl>', {
        renderDays: this.renderDays.createDelegate(this),
        renderPartners: this.renderPartners.createDelegate(this),
        renderRoles: this.renderRoles.createDelegate(this),
        getTokenStyles: this.getTokenStyles
    });
};

Ext.extend(Tine.CrewScheduling.MemberToken, Ext.XTemplate, {

    wkdays: ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'],


    getTokens: function (records, asHTML, glue) {
        var _ = window.lodash,
            me = this,
            tokens = _.map(records, _.bind(me.getToken, me));

        return asHTML ? tokens.join(glue || '\n') : tokens;
    },

    getToken: function (record) {
        var _ = window.lodash,
            data = _.isFunction(record.beginEdit) ? record.data : record;

        return this.apply(this.prepareData(data));
    },

    getTokenStyles: Ext.emptyFn,

    prepareData : function(data) {
        return Object.assign({}, data.user_id, {
            count: data.count,
            fbInfo: data.fbInfo,
            initials: data.user_id.n_short ? data.user_id.n_short
                : _.join(_.map(_.map([data.user_id.n_given, data.user_id.n_family], _.toUpper), _.head), ''),
            signatureId: Tine.Calendar.Model.Attender.getAttendeeStore.getSignature(data)
        });
    },

    renderDays: function(dates) {
        var _ = window.lodash,
            me = this,
            segment = [],
            segments = [],
            html = '';

        // contents only if days are limited
        if (!dates.length%7) return html;

        // map to numeric Date:format('w')
        dates = _.map(dates, _.partial(_.indexOf, me.wkdays, _, undefined, undefined));
        // sort by wkStart
        dates = _.sortBy(dates, function(wkday) {return (wkday+7-Ext.DatePicker.prototype.startDay)%7; });
        // find connected wkdays as segments
        _.each(dates, function(value, key) {
            if (! segment.length) {
                segment = [value];
            } else if ((dates[key-1]+1)%7 == value) {
                segment.push(value);
            } else {
                segments.push([segment.shift(), segment.pop()]);
                segment = [value];
            }
        });
        segments.push([segment.shift(), segment.pop()]);

        // generate html
        _.each(segments, function(segment) {
            html += String.format('<span class="cs-dates-segment">{0}</span>',
                Date.dayNames[segment[0]].substring(0, 2) + (_.isNumber(segment[1]) ?
                    ('-' + Date.dayNames[segment[1]].substring(0, 2)) : '')
            );
        });

        return html;
    },

    renderPartners: function(partners) {
        var _ = window.lodash,
            partnersNames = _.map(_.map(partners, 'n_fileas'), Ext.util.Format.htmlEncode).join('<br/>'),
            html = '';

        // contents only if partners
        if (! partners.length) return html;

        return String.format('<span class="cs-partners" ext:qtip="{0}" />',
            Ext.util.Format.htmlEncode(
                this.app.i18n._('Start drag gesture here to include the following partners:') + '<br />'
            ) + Ext.util.Format.htmlEncode(partnersNames)
        );

    },

    renderRoles: function(roles) {
        var _ = window.lodash,
            me = this;

        return _.map(roles, _.bind(me.renderRole, me)).join('\n');
    },

    renderRole: function(role) {
        const eventTypes = role.data?.event_types ||role.event_types;
        role = role.data || role; role = role.role || role; role = role.data || role;

        return String.format('<span class="cs-role" style="border-color: {2};' +
            ' background: linear-gradient(rgb({3}, {4}, {5}));" ' +
            'ext:qtip="{1}" >{0}</span>',

            Ext.util.Format.htmlEncode(role.key),
            Tine.Tinebase.common.doubleEncode(role.name) + (eventTypes ? (`&nbsp;(${_.map(eventTypes, 'name').join(', ')})`) : ''),
            role.color,
            role.colorRGB ? role.colorRGB[0] : null,
            role.colorRGB ? role.colorRGB[1] : null,
            role.colorRGB ? role.colorRGB[2] : null
        );
    }
});
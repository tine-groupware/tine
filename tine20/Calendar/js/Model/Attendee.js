/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Record from 'data/Record'
import { accountSortType } from 'common'

/**
 * @class Attendee
 * @extends Record
 * Attender Record Definition
 */
const Attendee = Record.create([
    {name: 'id'},
    {name: 'cal_event_id'},
    {name: 'user_id', sortType: accountSortType },
    {name: 'user_type'},
    {name: 'role', type: 'keyField', keyFieldConfigName: 'attendeeRoles'},
    {name: 'quantity'},
    {name: 'status', type: 'keyField', keyFieldConfigName: 'attendeeStatus'},
    {name: 'status_authkey'},
    {name: 'displaycontainer_id'},
    {name: 'transp'},
    {name: 'checked'}, // filter grid helper field
    {name: 'fbInfo'}   // helper field
], {
    appName: 'Calendar',
    modelName: 'Attender',
    idProperty: 'id',
    titleProperty: 'name',
    // ngettext('Attender', 'Attendee', n); gettext('Attendee');
    recordName: 'Attender',
    recordsName: 'Attendee',
    containerProperty: 'cal_event_id',
    // ngettext('Event', 'Events', n); gettext('Events');
    containerName: 'Event',
    containersName: 'Events',

    /**
     * gets name of attender
     *
     * @return {String}
     */
    getTitle: function() {
        var p = Tine.Calendar.AttendeeGridPanel.prototype;
        return p.renderAttenderName.call(p, this.get('user_id'), false, this);
    },

    getCompoundId: function(mapGroupmember) {
        var type = this.get('user_type');
        type = mapGroupmember && type == 'groupmember' ? 'user' : type;

        return type + '-' + this.getUserId();
    },

    /**
     * returns true for external contacts
     */
    isExternal: function() {

        var isExternal = false,
            user_type = this.get('user_type');
        if (user_type == 'user' || user_type == 'groupmember') {
            isExternal = !this.getUserAccountId();
        }

        return isExternal;
    },

    /**
     * returns account_id if attender is/has a user account
     *
     * @return {String}
     */
    getUserAccountId: function() {
        var user_type = this.get('user_type');
        if (user_type == 'user' || user_type == 'groupmember') {
            var user_id = this.get('user_id');
            if (! user_id) {
                return null;
            }

            // we expect user_id to be a user or contact object or record
            if (typeof user_id.get == 'function') {
                if (user_id.get('contact_id')) {
                    // user_id is a account record
                    return user_id.get('accountId');
                } else {
                    // user_id is a contact record
                    return user_id.get('account_id');
                }
            } else if (user_id.hasOwnProperty('contact_id')) {
                // user_id contains account data
                return user_id.accountId;
            } else if (user_id.hasOwnProperty('account_id')) {
                // user_id contains contact data
                return user_id.account_id;
            }

            // this might happen if contact resolved, due to right restrictions
            return user_id;

        }
        return null;
    },

    /**
     * returns id of attender of any kind
     */
    getUserId: function() {
        var user_id = this.get('user_id');
        var user_type = this.get('user_type');

        if (user_type == 'email') {
            return this.get('user_email');
        }

        var userData = (typeof user_id?.get == 'function') ? user_id.data : user_id;
        if (!userData) {
            return null;
        }

        if (typeof userData != 'object') {
            return userData;
        }

        switch (user_type) {
            case 'user':
            case 'groupmember':
            case 'memberOf':
                if (userData.hasOwnProperty('contact_id')) {
                    // userData contains account
                    return userData.contact_id;
                } else if (userData.hasOwnProperty('account_id')) {
                    // userData contains contact
                    return userData.id;
                } else if (userData.group_id) {
                    // userData contains list
                    return userData.id;
                } else if (userData.list_id) {
                    // userData contains group
                    return userData.list_id;
                }
                break;
            default:
                return userData.id
                break;
        }
    },

    getIconCls: function() {
        var type = this.get('user_type'),
            cls = 'tine-grid-row-action-icon cal-attendee-type-';

        switch(type) {
            case 'user':
                cls = 'tine-grid-row-action-icon renderer_typeAccountIcon';
                break;
            case 'group':
                cls = 'tine-grid-row-action-icon renderer_accountGroupIcon';
                break;
            default:
                cls += type;
                break;
        }

        return cls;
    }
});

Attendee.getSortOrder = function(user_type) {
    var sortOrders = {
        'user': 1,
        'groupmemeber': 1,
        'group': 2,
        'resource': 3
    };

    return sortOrders[user_type] || 4;
};

/**
 * @namespace Tine.Calendar.Model
 *
 * get default data for a new attender
 *
 * @return {Object} default data
 * @static
 */
Attendee.getDefaultData = function(overrides) {
    return _.assign({
        // @TODO have some config here? user vs. default?
        user_type: 'any',
        role: 'REQ',
        quantity: 1,
        status: 'NEEDS-ACTION'
    }, overrides);
};

/**
 * @namespace Tine.Calendar.Model
 *
 * get default data for a new resource
 *
 * @return {Object} default data
 * @static
 */
Attendee.getDefaultResourceData = function() {
    return {
        user_type: 'resource',
        role: 'REQ',
        quantity: 1,
        status: 'NEEDS-ACTION'
    };
};

/**
 * @namespace Tine.Calendar.Model
 *
 * creates store of attender objects
 *
 * @param  {Array} attendeeData
 * @return {Ext.data.Store}
 * @static
 */
Attendee.getAttendeeStore = function(attendeeData) {
    var attendeeStore = new Ext.data.SimpleStore({
        fields: Attendee.getFieldDefinitions(),
        sortInfo: {field: 'user_id', direction: 'ASC'}
    });

    if (Ext.isString(attendeeData)) {
        attendeeData = Ext.decode(attendeeData || null);
    }

    Ext.each(attendeeData, function(attender) {
        if (attender) {
            var record = new Attendee(attender, attender.id && Ext.isString(attender.id) ? attender.id : Ext.id());
            if (record.get('user_id') == "currentContact") {
                record.set('user_id', Tine.Tinebase.registry.get('userContact'));
            }
            attendeeStore.addSorted(record);
        }
    });

    return attendeeStore;
};

/**
 * returns attender record of current account if exists, else false
 * @static
 */
Attendee.getAttendeeStore.getMyAttenderRecord = function(attendeeStore) {
    var currentAccountId = Tine.Tinebase.registry.get('currentAccount').accountId;
    var myRecord = false;

    attendeeStore.each(function(attender) {
        var userAccountId = attender.getUserAccountId();
        if (userAccountId == currentAccountId) {
            myRecord = attender;
            return false;
        }
    }, this);

    return myRecord;
};

/**
 * returns attendee record of given attendee if exists, else false
 * @static
 */
Attendee.getAttendeeStore.getAttenderRecord = function(attendeeStore, attendee) {
    var attendeeRecord = false;

    if (! Ext.isFunction(attendee.beginEdit)) {
        attendee = new Attendee(attendee, attendee.id);
    }

    attendeeStore.each(function(r) {
        var attendeeType = [attendee.get('user_type')];

        // add groupmember for user
        if (attendeeType[0] == 'user') {
            attendeeType.push('groupmember');
        }
        if (attendeeType[0] == 'groupmember') {
            attendeeType.push('user');
        }

        if (attendeeType.indexOf(r.get('user_type')) >= 0 && r.getUserId() == attendee.getUserId()) {
            attendeeRecord = r;
            return false;
        }
    }, this);

    return attendeeRecord;
};

Attendee.getAttendeeStore.signatureDelimiter = ';';

Attendee.getAttendeeStore.getSignature = function(attendee) {
    var _ = window.lodash;

    attendee = _.isFunction(attendee.beginEdit) ? attendee.data : attendee;
    return [attendee.cal_event_id, attendee.user_type, attendee.user_id.id || attendee.user_id, _.map(attendee.crewscheduling_roles, (csRole) => {
            return (csRole.role.id || csRole.role) + ':' + _.map(csRole.data?.event_types || csRole.event_types, (eventType) => {
                return eventType.data?.id || eventType.id;
            }).join('&')
        }).join(',')]
    .join(Attendee.getAttendeeStore.signatureDelimiter);
};

Attendee.getAttendeeStore.fromSignature = function(signatureId) {
    var ids = signatureId.split(Attendee.getAttendeeStore.signatureDelimiter);

    return new Attendee({
        cal_event_id: ids[0],
        user_type: ids[1],
        user_id: ids[2],
        crewscheduling_roles: ids[3] // @TODO do we need to dehydrate here?
    });
}

/**
 * returns attendee data
 * optinally fills into event record
 */
Attendee.getAttendeeStore.getData = function(attendeeStore, event) {
    var attendeeData = [];

    Tine.Tinebase.common.assertComparable(attendeeData);

    attendeeStore.each(function (attender) {
        var user_id = attender.get('user_id');
        if (user_id || attender.get('user_type') === 'email'/* && user_id.id*/) {
            if (typeof user_id?.get == 'function') {
                attender.data.user_id = user_id.data;
            }

            attendeeData.push(attender.data);
        }
    }, this);

    if (event) {
        event.set('attendee', attendeeData);
    }

    return attendeeData;
};

export default Attendee

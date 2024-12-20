/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Calendar', 'Tine.Calendar.Model');

Tine.Calendar.Model.Event = require('./Model/Event').default

// register calendar filters in addressbook
Tine.widgets.grid.ForeignRecordFilter.OperatorRegistry.register('Addressbook', 'Contact', {
    foreignRecordClass: 'Calendar.Event',
    linkType: 'foreignId', 
    filterName: 'ContactAttendeeFilter',
    // i18n._('Event (as attendee)')
    label: 'Event (as attendee)'
});
Tine.widgets.grid.ForeignRecordFilter.OperatorRegistry.register('Addressbook', 'Contact', {
    foreignRecordClass: 'Calendar.Event',
    linkType: 'foreignId', 
    filterName: 'ContactOrganizerFilter',
    // i18n._('Event (as organizer)')
    label: 'Event (as organizer)'
});

// example for explicit definition
//Tine.widgets.grid.FilterRegistry.register('Addressbook', 'Contact', {
//    filtertype: 'foreignrecord',
//    foreignRecordClass: 'Calendar.Event',
//    linkType: 'foreignId', 
//    filterName: 'ContactAttendeeFilter',
//    // i18n._('Event attendee')
//    label: 'Event attendee'
//});

/**
 * @namespace Tine.Calendar.Model
 * @class Tine.Calendar.Model.EventJsonBackend
 * @extends Tine.Tinebase.data.RecordProxy
 * 
 * JSON backend for events
 */
Tine.Calendar.Model.EventJsonBackend = Ext.extend(Tine.Tinebase.data.RecordProxy, {
    appName: 'Calendar',
    modelName: 'Event',
    recordClass: Tine.Calendar.Model.Event,

    /**
     * Creates a recuring event exception
     * 
     * @param {Tine.Calendar.Model.Event} event
     * @param {Boolean} deleteInstance
     * @param {Boolean} deleteAllFollowing
     * @param {Object} options
     * @return {String} transaction id
     */
    createRecurException: function(event, deleteInstance, deleteAllFollowing, checkBusyConflicts, options) {
        options = options || {};
        options.params = options.params || {};
        options.beforeSuccess = function(response) {
            return [this.recordReader(response)];
        };
        
        var p = options.params;
        p.method = this.appName + '.createRecurException';
        p.recordData = event.data;
        p.deleteInstance = deleteInstance ? 1 : 0;
        p.deleteAllFollowing = deleteAllFollowing ? 1 : 0;
        p.checkBusyConflicts = checkBusyConflicts ? 1 : 0;
        
        return this.doXHTTPRequest(options);
    },

    promiseCreateRecurException: function(event, deleteInstance, deleteAllFollowing, checkBusyConflicts, options) {
        var me = this;
        return new Promise(function (fulfill, reject) {
            try {
                me.createRecurException(event, deleteInstance, deleteAllFollowing, checkBusyConflicts, Ext.apply(options || {}, {
                    success: function (r) {
                        fulfill(r);
                    },
                    failure: function (error) {
                        reject(new Error(error));
                    }
                }));
            } catch (error) {
                if (Ext.isFunction(reject)) {
                    reject(new Error(options));
                }
            }
        });
    },

    /**
     * delete a recuring event series
     * 
     * @param {Tine.Calendar.Model.Event} event
     * @param {Object} options
     * @return {String} transaction id
     */
    deleteRecurSeries: function(event, options) {
        options = options || {};
        options.params = options.params || {};
        
        var p = options.params;
        p.method = this.appName + '.deleteRecurSeries';
        p.recordData = event.data;
        
        return this.doXHTTPRequest(options);
    },
    
    
    /**
     * updates a recuring event series
     * 
     * @param {Tine.Calendar.Model.Event} event
     * @param {Object} options
     * @return {String} transaction id
     */
    updateRecurSeries: function(event, checkBusyConflicts, options) {
        options = options || {};
        options.params = options.params || {};
        options.beforeSuccess = function(response) {
            return [this.recordReader(response)];
        };
        
        var p = options.params;
        p.method = this.appName + '.updateRecurSeries';
        p.recordData = event.data;
        p.checkBusyConflicts = checkBusyConflicts ? 1 : 0;
        
        return this.doXHTTPRequest(options);
    }
});

Tine.Calendar.eventBackend = Tine.Calendar.backend = new Tine.Calendar.Model.EventJsonBackend({});

Tine.Calendar.Model.Attender = require('./Model/Attendee').default;

// PROXY
Tine.Calendar.Model.AttenderProxy = function(config) {
    Tine.Calendar.Model.AttenderProxy.superclass.constructor.call(this, config);
    this.jsonReader.readRecords = this.readRecords.createDelegate(this);
};
Ext.extend(Tine.Calendar.Model.AttenderProxy, Tine.Tinebase.data.RecordProxy, {
    /**
     * provide events to do an freeBusy info checkup for when searching attendee
     *
     * @cfg {Function} freeBusyEventsProvider
     */
    freeBusyEventsProvider: Ext.emptyFn,

    recordClass: Tine.Calendar.Model.Attender,

    searchRecords: function(filter, paging, options) {
        var _ = window.lodash,
            fbEvents = _.union([].concat(this.freeBusyEventsProvider()));

        _.set(options, 'params.ignoreUIDs', _.union(_.map(fbEvents, 'data.uid')));
        _.set(options, 'params.events', _.map(fbEvents, function(event) {
            return event.getSchedulingData();
        }));

        return Tine.Calendar.Model.AttenderProxy.superclass.searchRecords.call(this, filter, paging, options);
    },

    readRecords : function(resultData){
        var _ = window.lodash,
            totalcount = 0,
            fbEvents = _.compact([].concat(this.freeBusyEventsProvider())),
            records = [],
            fbInfos = _.map(fbEvents, function(fbEvent) {
                return new Tine.Calendar.FreeBusyInfo(resultData.freeBusyInfo[fbEvent.id]);
            });

        _.each(['user', 'group', 'resource'], function(type) {
            var typeResult = _.get(resultData, type, {}),
                typeCount = _.get(typeResult, 'totalcount', 0),
                typeData = _.get(typeResult, 'results', []);

            totalcount += +typeCount;
            _.each(typeData, function(userData) {
                var id = type + '-' + userData.id,
                    attendeeData = _.assign(Tine.Calendar.Model.Attender.getDefaultData(), {
                        id: id,
                        user_type: type,
                        user_id: userData
                    }),
                    attendee = new Tine.Calendar.Model.Attender(attendeeData, id);

                if (fbEvents.length) {
                    attendee.set('fbInfo', _.map(fbInfos, function(fbInfo, idx) {
                        return fbInfo.getStateOfAttendee(attendee, fbEvents[idx]);
                    }).join('<br >'));
                }
                records.push(attendee);
            });
        });

        return {
            success : true,
            records: records,
            totalRecords: totalcount
        };
    }
});

Tine.Calendar.Model.Resource = require('./Model/Resource').default;

/**
 * @namespace   Tine.Calendar.Model
 * @class       Tine.Calendar.Model.ResourceType
 * @extends     Tine.Tinebase.data.Record
 * ResourceType Record Definition
 */
Tine.Calendar.Model.ResourceType = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'is_location'},
    {name: 'value'},
    {name: 'icon'},
    {name: 'color'},
    {name: 'system'},
], {
    appName: 'Calendar',
    modelName: 'ResourceType',
    idProperty: 'id'
});

/**
 * @namespace   Tine.Calendar.Model
 * @class       Tine.Calendar.Model.iMIP
 * @extends     Tine.Tinebase.data.Record
 * iMIP Record Definition
 */
Tine.Calendar.Model.iMIP = Tine.Tinebase.data.Record.create([
    {name: 'id'},
    {name: 'ics'},
    {name: 'method'},
    {name: 'originator'},
    {name: 'userAgent'},
    {name: 'event'},
    {name: 'existing_event'},
    {name: 'preconditions'}
], {
    appName: 'Calendar',
    modelName: 'iMIP',
    idProperty: 'id'
});

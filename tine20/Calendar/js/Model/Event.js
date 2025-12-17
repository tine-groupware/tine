/* 
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Record from 'data/Record'

// @see https://github.com/ericmorand/twing/issues/332
// #if process.env.NODE_ENV !== 'unittest'
import {default as getTwingEnv, Expression } from "twingEnv.es6";
// #endif

/**
 * @class Event
 * @extends Record
 * Event record definition
 */
const Event = Record.create(Record.genericFields.concat([
    { name: 'id' },
    { name: 'dtend', type: 'date', dateFormat: 'Y-m-d H:i:s' },
    { name: 'transp' },
    // ical common fields
    { name: 'class' },
    { name: 'description' },
    { name: 'geo' },
    { name: 'adr_lon' },
    { name: 'adr_lat' },
    { name: 'location' },
    { name: 'location_record' },
    { name: 'event_site', type: 'record', fieldDefinition: { config: {
        // we need some fieldDef to get grouping store working
        appName: 'Addressbook',
        modelName: 'Contact',
    }}},
    { name: 'organizer_type' },
    { name: 'organizer' },
    { name: 'organizer_email' },
    { name: 'organizer_displayname' },
    { name: 'priority' },
    { name: 'status' },
    { name: 'summary' },
    { name: 'url' },
    { name: 'uid' },
    // ical common fields with multiple appearance
    //{ name: 'attach' },
    { name: 'attendee' },
    { name: 'alarms'},
    { name: 'tags' },
    { name: 'notes'},
    { name: 'attachments'},
    { name: 'event_types', type: 'records', fieldDefinition: { config: {
        // we need some fieldDef to get filter working
        appName: 'Calendar',
        modelName: 'EventTypes',
        refIdField: 'record',
        dependentRecords: true
    }}},
    //{ name: 'contact' },
    //{ name: 'related' },
    //{ name: 'resources' },
    //{ name: 'rstatus' },
    // scheduleable interface fields
    { name: 'dtstart', type: 'date', dateFormat: 'Y-m-d H:i:s' },
    { name: 'recurid' },
    { name: 'base_event_id' },
    // scheduleable interface fields with multiple appearance
    { name: 'exdate' },
    //{ name: 'exrule' },
    //{ name: 'rdate' },
    { name: 'xprops' },
    { name: 'rrule' },
    { name: 'poll_id' },
    { name: 'mute' },
    { name: 'is_all_day_event', type: 'bool'},
    { name: 'rrule_until', type: 'date', dateFormat: 'Y-m-d H:i:s' },
    { name: 'rrule_constraints' },
    { name: 'originator_tz' },
    // grant helper fields
    {name: 'addGrant'       , type: 'bool'},
    {name: 'readGrant'      , type: 'bool'},
    {name: 'editGrant'      , type: 'bool'},
    {name: 'deleteGrant'    , type: 'bool'},
    {name: 'exportGrant'    , type: 'bool'},
    {name: 'freebusyGrant'  , type: 'bool'},
    {name: 'privateGrant'   , type: 'bool'},
    {name: 'syncGrant'      , type: 'bool'},
    // relations
    { name: 'relations',   omitDuplicateResolving: true},
    { name: 'customfields', omitDuplicateResolving: true}
]), {
    appName: 'Calendar',
    modelName: 'Event',
    idProperty: 'id',
    titleProperty: 'summary',
    // ngettext('Event', 'Events', n); gettext('Events');
    recordName: 'Event',
    recordsName: 'Events',
    containerProperty: 'container_id',
    grantsPath: 'data',
    // ngettext('Calendar', 'Calendars', n); gettext('Calendars');
    containerName: 'Calendar',
    containersName: 'Calendars',
    copyOmitFields: ['uid', 'recurid'],
    allowBlankContainer: false,
    copyNoAppendTitle: true,

    /**
     * default duration for new events
     */
    defaultEventDuration: 60,

    /**
     * returns displaycontainer with orignialcontainer as fallback
     *
     * @return {Array}
     */
    getDisplayContainer: function() {
        var displayContainer = this.get('container_id');
        var currentAccountId = Tine.Tinebase.registry.get('currentAccount').accountId;

        var attendeeStore = this.getAttendeeStore();

        attendeeStore.each(function(attender) {
            var userAccountId = attender.getUserAccountId();
            if (userAccountId == currentAccountId) {
                var container = attender.get('displaycontainer_id');
                if (container) {
                    displayContainer = container;
                }
                return false;
            }
        }, this);

        return displayContainer;
    },

    /**
     * is this event a recuring base event?
     *
     * @return {Boolean}
     */
    isRecurBase: function() {
        return !!this.get('rrule') && !this.get('recurid');
    },

    /**
     * is this event a recuring exception?
     *
     * @return {Boolean}
     */
    isRecurException: function() {
        return !! this.get('recurid') && ! this.isRecurInstance();
    },

    /**
     * is this event an recuring event instance?
     *
     * @return {Boolean}
     */
    isRecurInstance: function() {
        return this.id && Ext.isFunction(this.id.match) && this.id.match(/^fakeid/);
    },

    /**
     * returns store of attender objects
     *
     * @param  {Array} attendeeData
     * @return {Ext.data.Store}
     */
    getAttendeeStore: function() {
        return Tine.Calendar.Model.Attender.getAttendeeStore(this.get('attendee'));
    },

    /**
     * returns attender record of current account if exists, else false
     */
    getMyAttenderRecord: function() {
        var attendeeStore = this.getAttendeeStore();
        return Tine.Calendar.Model.Attender.getAttendeeStore.getMyAttenderRecord(attendeeStore);
    },

    getSchedulingData: function() {
        var _ = window.lodash,
            schedulingData = _.pick(this.data, ['uid', 'originator_tz', 'dtstart', 'dtend', 'is_all_day_event',
                'transp', 'recurid', 'base_event_id', 'rrule', 'rrule_until', 'exdate', 'rrule_constraints']);

        // NOTE: for transistent events id is not part of data but we need the transistent id e.g. for freeBusy info
        schedulingData.id = this.id;
        return schedulingData;
    },

    inPeriod: function(period) {
        return this.get('dtstart').between(period.from, period.until) ||
            this.get('dtend').between(period.from, period.until);
    },

    hasPoll: function() {
        var _ = window.lodash;
        return ! +_.get(this, 'data.poll_id.closed', true);
    },

    getPollUrl: function(pollId) {
        if (! pollId) {
            pollId = this.get('poll_id');
            if (pollId.id) {
                pollId = pollId.id;
            }
        }
        return Tine.Tinebase.common.getUrl() + 'Calendar/view/poll/' + pollId ;
    },

    getTitle: function() {
        if (! this.constructor.titleTwing) {
            const app = Tine.Tinebase.appMgr.get(this.appName);
            const template = app.getRegistry().get('preferences').get('webEventTitleTemplate') + '{% if poll_id and not poll_id.closed %}\uFFFD{% endif %}';
            const twingEnv = getTwingEnv();
            const loader = twingEnv.getLoader();

            loader.setTemplate(
                this.constructor.getPhpClassName() + 'Title',
                app.i18n._hidden(template)
            );

            this.constructor.titleTwing = twingEnv;
        }

        return this.constructor.titleTwing.renderProxy(this.constructor.getPhpClassName() + 'Title', this.data);
    },

    isRescheduled: function (event) {
        return this.get('dtstart').toString() !== event.get('dtstart').toString() ||
            this.get('dtend').toString()!== event.get('dtend').toString();
    },

    getComboBoxTitle: function() {
        return this.get('summary') + ' (' + Tine.Tinebase.common.dateTimeRenderer(this.get('dtstart')) + ')';
    }
});

/**
 * get default data for a new event
 *
 * @return {Object} default data
 * @static
 */
Event.getDefaultData = function() {
    var app = Tine.Tinebase.appMgr.get('Calendar'),
        prefs = app.getRegistry().get('preferences'),
        defaultAttendeeStrategy = prefs.get('defaultAttendeeStrategy') || 'me',
        interval = prefs.get('interval') || 15,
        mainScreen = app.getMainScreen(),
        centerPanel = mainScreen.getCenterPanel(),
        westPanel = mainScreen.getWestPanel(),
        container = westPanel.getContainerTreePanel().getDefaultContainer(),
        organizer = (defaultAttendeeStrategy != 'me' && container && container.ownerContact) ? container.ownerContact : Tine.Tinebase.registry.get('userContact'),
        dtstart = new Date().clearTime().add(Date.HOUR, (new Date().getHours() + 1)),
        makeEventsPrivate = prefs.get('defaultSetEventsToPrivat'),
        eventClass = null,
        period = centerPanel.getCalendarPanel(centerPanel.activeView).getView().getPeriod();

    // if dtstart is out of current period, take start of current period
    if (period.from.getTime() > dtstart.getTime() || period.until.getTime() < dtstart.getTime()) {
        dtstart = period.from.clearTime(true).add(Date.HOUR, 9);
    }

    if (makeEventsPrivate == 1) {
        eventClass =  'PRIVATE';
    }

    var defaultAttendee = Event.getDefaultAttendee(organizer, container),
        defaultLocationResource = Event.getDefaultLocation(defaultAttendee);
    var data = {
        id: 'new-' + Ext.id(),
        summary: '',
        'class': eventClass,
        dtstart: dtstart,
        dtend: dtstart.add(Date.MINUTE, Event.getMeta('defaultEventDuration')),
        status: 'CONFIRMED',
        container_id: container,
        transp: 'OPAQUE',
        editGrant: true,
        // needed for action updater / save and close in edit dialog
        readGrant: true,
        organizer_type: 'contact',
        organizer: organizer,
        attendee: defaultAttendee,
        location: defaultLocationResource ? defaultLocationResource.name : null,
        location_record: defaultLocationResource ? Event.getDefaultLocationRecord(defaultLocationResource) : null,
        mute: false
    };

    if (+prefs.get('defaultalarmenabled')) {
        data.alarms = [{minutes_before: parseInt(prefs.get('defaultalarmminutesbefore'), 10)}];
    }

    app.emit('createEvent', data);

    return data;
};

Event.getDefaultAttendee = function(organizer, container) {
    var app = Tine.Tinebase.appMgr.get('Calendar'),
        mainScreen = app.getMainScreen(),
        centerPanel = mainScreen.getCenterPanel(),
        westPanel = mainScreen.getWestPanel(),
        filteredAttendee = westPanel.getAttendeeFilter().getValue() || [],
        defaultAttendeeData = Tine.Calendar.Model.Attender.getDefaultData(),
        defaultResourceData = Tine.Calendar.Model.Attender.getDefaultResourceData(),
        filteredContainers = westPanel.getContainerTreePanel().getFilterPlugin().getFilter().value || [],
        prefs = app.getRegistry().get('preferences'),
        defaultAttendeeStrategy = prefs.get('defaultAttendeeStrategy') || 'me',// one of['me', 'intelligent', 'calendarOwner', 'filteredAttendee', 'none']
        defaultAttendee = [],
        calendarResources = Tine.Tinebase.appMgr.get('Calendar').calendarResources;

    // shift -> change intelligent <-> me
    if (Ext.EventObject.shiftKey) {
        defaultAttendeeStrategy = defaultAttendeeStrategy == 'intelligent' ? 'me' :
            defaultAttendeeStrategy == 'me' ? 'intelligent' :
                defaultAttendeeStrategy;
    }

    // alt -> prefer calendarOwner in intelligent mode
    if (defaultAttendeeStrategy == 'intelligent') {
        defaultAttendeeStrategy = filteredAttendee.length && !Ext.EventObject.altKey > 0 ? 'filteredAttendee' :
            filteredContainers.length > 0 ? 'calendarOwner' :
                'me';
    }

    switch(defaultAttendeeStrategy) {
        case 'none':
            break;
        case 'me':
            defaultAttendee.push(Ext.apply(Tine.Calendar.Model.Attender.getDefaultData(), {
                user_type: 'user',
                user_id: Tine.Tinebase.registry.get('userContact'),
                status: 'ACCEPTED'
            }));
            break;

        case 'filteredAttendee':
            var attendeeStore = Tine.Calendar.Model.Attender.getAttendeeStore(filteredAttendee),
                ownAttendee = Tine.Calendar.Model.Attender.getAttendeeStore.getMyAttenderRecord(attendeeStore);

            attendeeStore.each(function(attendee){
                var attendeeData = Ext.applyIf(Ext.decode(Ext.encode(attendee.data)), defaultAttendeeData);

                switch (attendeeData.user_type.toLowerCase()) {
                    case 'memberof':
                        attendeeData.user_type = 'group';
                        break;
                    case 'resource':
                        Ext.apply(attendeeData, defaultResourceData);
                        break;
                    default:
                        break;
                }

                if (attendee == ownAttendee) {
                    attendeeData.status = 'ACCEPTED';
                }
                defaultAttendee.push(attendeeData);
            }, this);
            break;

        case 'calendarOwner':
            var addedOwnerIds = [];

            Ext.each(filteredContainers, function(filteredContainer){
                if (filteredContainer.ownerContact && filteredContainer.type && filteredContainer.type == 'personal') {
                    var attendeeData = Ext.apply(Tine.Calendar.Model.Attender.getDefaultData(), {
                        user_type: 'user',
                        user_id: filteredContainer.ownerContact
                    });

                    if (attendeeData.user_id.id == organizer.id){
                        attendeeData.status = 'ACCEPTED';
                    }

                    if (addedOwnerIds.indexOf(filteredContainer.ownerContact.id) < 0) {
                        defaultAttendee.push(attendeeData);
                        addedOwnerIds.push(filteredContainer.ownerContact.id);
                    }
                } else if (filteredContainer.type && filteredContainer.type == 'shared' && calendarResources) {
                    Ext.each(calendarResources, function(calendarResource) {
                        if (calendarResource.container_id.id == filteredContainer.id) {
                            var attendeeData = Ext.apply(Tine.Calendar.Model.Attender.getDefaultData(), {
                                user_type: 'resource',
                                user_id: calendarResource,
                                status: calendarResource.status
                            });
                            defaultAttendee.push(attendeeData);
                        }
                    }, this);
                }
            }, this);

            if (container && container.ownerContact && addedOwnerIds.indexOf(container.ownerContact.id) < 0) {
                var attendeeData = Ext.apply(Tine.Calendar.Model.Attender.getDefaultData(), {
                    user_type: 'user',
                    user_id: container.ownerContact
                });

                if (container.ownerContact.id == organizer.id){
                    attendeeData.status = 'ACCEPTED';
                }

                defaultAttendee.push(attendeeData);
                addedOwnerIds.push(container.ownerContact.id);
            }
            break;
    }

    return defaultAttendee;
};

Event.getDefaultLocation = function(defaultAttendee) {
    var location = null;
    if (defaultAttendee) {
        _.forEach(defaultAttendee, function(attendee) {
            if (attendee.user_type == 'resource') {
                var type = Tine.Tinebase.widgets.keyfield.StoreMgr.get('Calendar', 'resourceTypes').getById(lodash.get(attendee, 'user_id.type', {}))
                if (type?.get('is_location')) {
                    location =  attendee.user_id;
                }
            }
        });
    }

    return location;
};

Event.getDefaultLocationRecord = function(resource) {
    var relations = resource.relations,
        locationId = relations ? _.findIndex(relations, function(k) { return k.type == 'LOCATION'; }) : -1,
        locationContact = locationId >= 0 ? relations[locationId].related_record : null;

    if (locationContact) {
        return locationContact;
    } else {
        var siteId = relations ? _.findIndex(relations, function (k) {
                return k.type == 'SITE';
            }) : -1,
            siteContact = siteId >= 0 ? relations[siteId].related_record : null;

        if (siteContact) {
            return siteContact;
        }
    }


    return null;
};

Event.getFilterModel = function() {
    var app = Tine.Tinebase.appMgr.get('Calendar'),
        filter = [
            {label: i18n._('Quick Search'), field: 'query', operators: ['contains']},
            {label: app.i18n._('Summary'), field: 'summary'},
            {label: app.i18n._('Location'), field: 'location'},
            {filtertype: 'addressbook.contact', field: 'location_record', label: app.i18n._('Location Contact')},
            {label: app.i18n._('Description'), field: 'description', operators: ['contains', 'notcontains']},
            // _('GENDER_Calendar')
            {filtertype: 'tine.widget.container.filtermodel', app: app, recordClass: Event, /*defaultOperator: 'in',*/ defaultValue: {path: Tine.Tinebase.container.getMyNodePath()}},
            {filtertype: 'calendar.attendee'},
            {
                label: app.i18n._('Attendee Status'),
                gender: app.i18n._('GENDER_Attendee Status'),
                field: 'attender_status',
                filtertype: 'tine.widget.keyfield.filter',
                app: app,
                keyfieldName: 'attendeeStatus',
                defaultOperator: 'notin',
                defaultValue: ['DECLINED']
            },
            {
                label: app.i18n._('Attendee Role'),
                gender: app.i18n._('GENDER_Attendee Role'),
                field: 'attender_role',
                filtertype: 'tine.widget.keyfield.filter',
                app: app,
                keyfieldName: 'attendeeRoles'
            },
            {filtertype: 'addressbook.contact', field: 'organizer', label: app.i18n._('Organizer')},
            {filtertype: 'tinebase.tag', app: app},
            {
                label: app.i18n._('Status'),
                gender: app.i18n._('GENDER_Status'),
                field: 'status',
                filtertype: 'tine.widget.keyfield.filter',
                app: { name: 'Calendar' },
                keyfieldName: 'eventStatus',
                defaultAll: true
            },
            {
                label: app.i18n._('Blocking'),
                gender: app.i18n._('GENDER_Blocking'),
                field: 'transp',
                filtertype: 'tine.widget.keyfield.filter',
                app: { name: 'Calendar' },
                keyfieldName: 'eventTransparencies',
                defaultAll: true
            },
            {
                app: app,
                filtertype: 'calendar.weekday',
            },
            {
                label: app.i18n._('Classification'),
                gender: app.i18n._('GENDER_Classification'),
                field: 'class',
                filtertype: 'tine.widget.keyfield.filter',
                app: { name: 'Calendar' },
                keyfieldName: 'eventClasses',
                defaultAll: true
            },
            {label: i18n._('Last Modified Time'), field: 'last_modified_time', valueType: 'datetime'},
            //{label: i18n._('Last Modified by'),                                                  field: 'last_modified_by',   valueType: 'user'},
            {label: i18n._('Creation Time'), field: 'creation_time', valueType: 'datetime'},
            //{label: i18n._('Created by'),                                                        field: 'created_by',         valueType: 'user'},
            {
                filtertype: 'calendar.rrule',
                app: app
            }
        ];

    if (app.featureEnabled('featureEventType')) {
        filter.push({filtertype: 'foreignrecord', linkType: 'foreignId', app: app, ownRecordClass: 'Calendar.Event', ownField: 'event_types', foreignRecordClass: 'Calendar.EventTypes'});
    }
    if (Tine.Tinebase.featureEnabled('featureSite')) {
        filter.push({filtertype: 'tinebase.site', app: app, field: 'event_site'});
    }

    return filter;
};

Event.datetimeRenderer = function(dt) {
    if (dt && Ext.isString(dt)) {
        dt = new Date(dt);
    }
    const app = Tine.Tinebase.appMgr.get('Calendar');
    if (! dt) return app.i18n._('Unknown date');
    //TODO: return html element and get the format ?
    const dateObj = dt instanceof Date ? dt : Date.parseDate(dt, 'Y-m-d H:i:s');
    return String.format(app.i18n._("{0} {1} o'clock"), dt.format('l') + ', ' + Ext.util.Format.date(dateObj, Locale.getTranslationData('Date', 'medium')), dt.format('H:i'));
};

export default Event
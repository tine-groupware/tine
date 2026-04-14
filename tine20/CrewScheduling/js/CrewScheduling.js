/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
import './Calendar/Model/Attendee';
import './Calendar/Model/Event';
import './Calendar/AttendeeGridPanelPlugin';
import './Calendar/EventCSPanel';

Ext.ns('Tine.CrewScheduling');

require('./MainScreen');
require('../css/crewScheduling.css');

/**
 * @namespace   Tine.CrewScheduling
 * @class       Tine.CrewScheduling.Application
 * @extends     Tine.Tinebase.Application
 */
Tine.CrewScheduling.Application = Ext.extend(Tine.Tinebase.Application, {

    /**
     * Get translated application title of the calendar application
     *
     * @return {String}
     */
    getTitle: function() {
        return this.i18n.ngettext('CrewScheduling', 'CrewSchedulings', 1);
    },
    

    registerCoreData: function() {
        Tine.log.info('Tine.CrewScheduling.Application - registering core data ... ');
        Tine.CoreData.Manager.registerGrid('cs_scheduling_role', Tine.CrewScheduling.SchedulingRoleGridPanel, {});
    }
});

Tine.Addressbook.Model.ContactMixin.statics.favorite_day = {
    initComponent: function() {
        var bydayItems = [],
            wkdays = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];

        for (var i=0,d; i<7; i++) {
            d = (i+Ext.DatePicker.prototype.startDay)%7;
            bydayItems.push({
                boxLabel: Date.dayNames[d],
                name: wkdays[d],
                checked: false
            })
        }

        this.byday = new Ext.form.CheckboxGroup({
            cls: 'favorite_days',
            hideLabel: true,
            items: bydayItems
        });

        this.supr().initComponent.call(this);
    },
    afterRender: function() {
        this.supr().afterRender.call(this);
        this.byday.render(this.el.parent());
        this.el.setStyle('display', 'none');
    },
    getValue: function() {
        var values = this.byday.getValue(),
            result = [],
            _ = window.lodash;

        _.each(values, function(day) {
            result.push(day.name);
        });

        return result.toString();
    },
    setValue: function(v) {
        var _ = window.lodash,
            values = _.isString(v) ? v.split(',') :
                     _.isArray(v) ? v : [];

        if (this.byday.items instanceof Array) {
            _.each(this.byday.items, function (day) {
                if (_.indexOf(values, day.name) > -1) {
                    day.checked = true;
                }
            });
        }

        return this;
    }
};

/**
 * auto fill event site if resource of type room having a SITE relation is added
 * // @TODO remove on EBHH #1690 cleanup
 */
Tine.Calendar.Model.Event.site = {
    initComponent: function() {
        Tine.Tinebase.widgets.form.RecordPickerComboBox.prototype.initComponent.call(this);
        if (Tine.Tinebase.featureEnabled('featureSite')) return;
        this.editDialog.attendeeGridPanel.on('beforenewattendee', function( attendeeGridPanel, newAttendee, event) {
            this.autoAddSiteIf(this.editDialog, event);
        }, this);
    },
    autoAddSiteIf: function(editDialog, record) {
        var _  = window.lodash;
        editDialog.attendeeGridPanel.store.each(_.bind(function(attendee) {
            if (attendee.get('user_type') == 'resource'
                && Tine.Tinebase.widgets.keyfield.StoreMgr.get('Calendar', 'resourceTypes')
                    .getById(attendee.get('user_id').type).get('is_location')
                && attendee.get('user_id').relations) {
                _.each(attendee.get('user_id').relations, _.bind(function (relation) {
                    if (relation.type == 'SITE') {
                        this.setValue(relation.related_record);
                    }
                }, this));
            }
        }, this));
    }
};

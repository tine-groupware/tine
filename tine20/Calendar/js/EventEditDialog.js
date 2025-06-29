/*
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
import OrganizerCombo from "./OrganizerCombo";

Ext.ns('Tine.Calendar');

import FieldTriggerPlugin from "Tinebase/js/ux/form/FieldTriggerPlugin"

require('./Printer/EventRecord');
require('./FreeTimeSearchDialog');
require('./PollPanel');

/**
 * @namespace Tine.Calendar
 * @class Tine.Calendar.EventEditDialog
 * @extends Tine.widgets.dialog.EditDialog
 * Calendar Edit Dialog <br>
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Calendar.EventEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    labelAlign: 'side',
    windowNamePrefix: 'CalEventEditWindow_',
    appName: 'Calendar',
    recordClass: Tine.Calendar.Model.Event,
    showContainerSelector: false,
    displayNotes: true,
    requiredSaveGrant: '',

    mode: 'local',

    saveEvent: function(record, options, additionalArguments) {
        // NOTE: only mainscreen can handle busyConflicts
        additionalArguments.checkBusyConflicts = 0;

        return Tine.Calendar.Model.EventJsonBackend.prototype.saveRecord.apply(this.recordProxy, arguments);
        //
        // var cp = this.app.getMainScreen().getCenterPanel(),
        //     activePanel = cp.getCalendarPanel(cp.activeView),
        //     activeView = activePanel.getView();
        //
        // activeView.showEvent(record)
        //     .then(function() {
        //         // save via mainscreen to show fbExceptions and recurring decistions
        //         // but we would need to foreground the window and this is not possible for the browser
        //     });
    },

    onResize: function() {
        Tine.Calendar.EventEditDialog.superclass.onResize.apply(this, arguments);
        this.setTabHeight.defer(100, this);
    },
    
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * @return {Object} components this.itmes definition
     */
    getFormItems: function() {
        var timeIncrement = parseInt(this.app.getRegistry().get('preferences').get('timeIncrement'));

        return {
            xtype: 'tabpanel',
            plugins: [{
                ptype: 'ux.itemregistry',
                key: 'Calendar-Event-EditDialog-TabPanel'
            }, {
                ptype : 'ux.tabpanelkeyplugin'
            }],
            defaults: {
                hideMode: 'offsets'
            },
            plain:true,
            activeTab: 0,
            border: false,
            items:[{
                title: this.app.i18n.n_('Event', 'Events', 1),
                border: false,
                frame: true,
                layout: 'border',
                layoutConfig: {
                    enableResponsive: true,
                    stackEastLevel: 2,
                    responsiveBreakpointOverrides: [{level: 2, width: 700}]
                },
                items: [{
                    region: 'center',
                    layout: 'hfit',
                    border: false,
                    items: [{
                        layout: 'hbox',
                        items: [{
                            margins: '5',
                            width: 100,
                            xtype: 'label',
                            text: this.app.i18n._('Summary')
                        }, {
                            flex: 1,
                            xtype:'textfield',
                            name: 'summary',
                            listeners: {render: function(field){field.focus(false, 250);}},
                            allowBlank: false,
                            requiredGrant: 'editGrant',
                            maxLength: 1024
                        }]
                    }, {
                        layout: 'hbox',
                            hidden: !this.app.featureEnabled('featureEventType'),
                        items: [{
                            margins: '5',
                            width: 100,
                            // height: 30,
                            xtype: 'label',
                            text: this.app.i18n._('Event Types')
                        }, {
                            flex: 1,
                            xtype:'tinerecordspickercombobox',
                            name: 'event_types',
                            recordClass: 'Calendar.EventTypes',
                            refIdField: 'record',
                            searchComboConfig: {useEditPlugin: false},
                            editDialogConfig: {mode: 'local'},
                            isMetadataModelFor: 'event_type',
                            requiredGrant: 'editGrant',
                        }]
                    }, {
                                layout: 'hbox',
                                hidden: !Tine.Tinebase.featureEnabled('featureSite'),
                                items: [{
                                    margins: '5',
                                    width: 100,
                                    // height: 30,
                                    xtype: 'label',
                                    text: this.app.i18n._('Site')
                                }, {
                                    flex: 1,
                                    xtype:'addressbookcontactpicker',
                                    name: 'event_site',
                                    userOnly: false,
                                    useAccountRecord: false,
                                    searchComboConfig: {useEditPlugin: false},
                                    requiredGrant: 'editGrant',
                                    recordEditPluginConfig: {allowCreateNew: false},
                                    emptyText: this.app.i18n._('Search for sites...'),
                                    additionalFilterSpec: {
                                        config: {
                                            name: 'siteFilter',
                                            appName: 'Tinebase'
                                        }
                                    }
                                }]
                    }, {
                        layout: 'hbox',
                        items: [{
                            margins: '5',
                            width: 100,
                            xtype: 'label',
                            text: this.app.i18n._('View')
                        }, Ext.apply(this.perspectiveCombo, {
                            flex: 1
                        })]
                    }, {
                        layout: 'hbox',
                        height: 135,
                        layoutConfig: {
                            align : 'stretch',
                            pack  : 'start'
                        },
                        items: [{
                            flex: 1,
                            xtype: 'fieldset',
                            layout: 'hfit',
                            margins: '0 5 0 0',
                            title: this.app.i18n._('Details'),
                            items: [{
                                xtype: 'columnform',
                                labelAlign: 'side',
                                labelWidth: 100,
                                formDefaults: {
                                    xtype:'textfield',
                                    anchor: '100%',
                                    labelSeparator: '',
                                    columnWidth: .7
                                },
                                items: [[{
                                        xtype:'label',
                                        width: 105,
                                        text: this.app.i18n._('Event Location'),
                                    }, {
                                        columnWidth: 1/3,
                                        hideLabel: true,
                                        name: 'location_record',
                                        requiredGrant: 'editGrant',
                                        allowBlank: true,
                                        xtype: 'addressbookcontactpicker',
                                        userOnly: false,
                                        useAccountRecord: false,
                                        blurOnSelect: true,
                                        selectOnFocus: true,
                                        readOnly: false,
                                        maxLength: 1024,
                                        recordEditPluginConfig: {allowCreateNew: false,},
                                        listeners: {
                                            scope: this,
                                            'select': function (combo, rec) {
                                                this.form.findField('location').setValue(rec.get('n_fn'));
                                            }
                                        }
                                    }, {
                                        columnWidth: 2/3,
                                        hideLabel: true,
                                        name: 'location',
                                        requiredGrant: 'editGrant',
                                        maxLength: 255
                                }], [{
                                    xtype: 'datetimefield',
                                    fieldLabel: this.app.i18n._('Start Time'),
                                    listeners: {scope: this, change: this.onDtStartChange},
                                    name: 'dtstart',
                                    allowBlank: false,
                                    increment: timeIncrement,
                                    requiredGrant: 'editGrant'
                                }, {
                                    columnWidth: .19,
                                    xtype: 'checkbox',
                                    hideLabel: true,
                                    boxLabel: this.app.i18n._('whole day'),
                                    listeners: {scope: this, check: this.onAllDayChange},
                                    name: 'is_all_day_event',
                                    requiredGrant: 'editGrant'
                                }], [{
                                    xtype: 'datetimefield',
                                    fieldLabel: this.app.i18n._('End Time'),
                                    listeners: {scope: this, change: this.onDtEndChange},
                                    name: 'dtend',
                                    allowBlank: false,
                                    increment: timeIncrement,
                                    requiredGrant: 'editGrant'
                                }, {
                                    columnWidth: .3,
                                    xtype: 'combo',
                                    hideLabel: true,
                                    readOnly: true,
                                    hideTrigger: true,
                                    disabled: true,
                                    name: 'originator_tz',
                                    requiredGrant: 'editGrant'
                                }], [ this.containerSelectCombo = new Tine.widgets.container.SelectionComboBox({
                                    columnWidth: 1,
                                    id: this.app.appName + 'EditDialogContainerSelector' + Ext.id(),
                                    fieldLabel: i18n._('Saved in'),
                                    ref: '../../../../../../../../containerSelect',
                                    //width: 300,
                                    //listWidth: 300,
                                    name: this.recordClass.getMeta('containerProperty'),
                                    treePanelClass: Tine.Calendar.TreePanel,
                                    recordClass: this.recordClass,
                                    containerName: this.app.i18n.n_hidden(this.recordClass.getMeta('containerName'), this.recordClass.getMeta('containersName'), 1),
                                    containersName: this.app.i18n._hidden(this.recordClass.getMeta('containersName')),
                                    appName: this.app.appName,
                                    requiredGrant: 'readGrant',
                                    requiredGrants: ['addGrant'],
                                    disabled: true
                                }), Ext.apply(this.perspectiveCombo.getAttendeeContainerField(), {
                                    columnWidth: 1
                                })]]
                            }]
                        }, {
                            width: 130,
                            xtype: 'fieldset',
                            title: this.app.i18n._('Status'),
                            items: [{
                                xtype: 'widget-keyfieldcombo',
                                app:   'Calendar',
                                keyFieldName: 'eventStatus',
                                width: 115,
                                hideLabel: true,
                                value: 'CONFIRMED',
                                name: 'status',
                                requiredGrant: 'editGrant',
                                listeners: {
                                    beforeselect: (combo, status, index) => {
                                        Ext.MessageBox.confirm(
                                            this.app.i18n._('Update status for all attendees?'),
                                            this.app.i18n._('You are about to change the status of the event itself, not just your own. Do you really want to change the event status for all attendees?'), function (btn) {
                                                if (btn === 'yes') {
                                                    combo.setValue(status.id);
                                                }
                                            }, this);
                                        return false;
                                    }
                                }
                            }, {
                                xtype: 'checkbox',
                                hideLabel: true,
                                boxLabel: this.app.i18n._('non-blocking'),
                                name: 'transp',
                                requiredGrant: 'editGrant',
                                getValue: function() {
                                    var bool = Ext.form.Checkbox.prototype.getValue.call(this);
                                    return bool ? 'TRANSPARENT' : 'OPAQUE';
                                },
                                setValue: function(value) {
                                    var bool = (value == 'TRANSPARENT' || value === true);
                                    return Ext.form.Checkbox.prototype.setValue.call(this, bool);
                                }
                            }, Ext.apply(this.perspectiveCombo.getAttendeeTranspField(), {
                                hideLabel: true
                            }), {
                                xtype: 'checkbox',
                                hideLabel: true,
                                boxLabel: this.app.i18n._('Private'),
                                name: 'class',
                                requiredGrant: 'editGrant',
                                getValue: function() {
                                    var bool = Ext.form.Checkbox.prototype.getValue.call(this);
                                    return bool ? 'PRIVATE' : 'PUBLIC';
                                },
                                setValue: function(value) {
                                    var bool = (value == 'PRIVATE' || value === true);
                                    return Ext.form.Checkbox.prototype.setValue.call(this, bool);
                                }
                            }, Ext.apply(this.perspectiveCombo.getAttendeeStatusField(), {
                                width: 115,
                                hideLabel: true
                            })]
                        }]
                    }, {
                        xtype: 'tabpanel',
                        deferredRender: false,
                        activeTab: 0,
                        border: false,
                        height: 235,
                        form: true,
                        items: [
                            this.attendeeGridPanel,
                            this.rrulePanel,
                            this.alarmPanel
                        ].concat(Tine.Tinebase.appMgr.get('Calendar').featureEnabled('featurePolls') ? this.pollPanel : [])
                    }]
                }, {
                    // activities and tags
                    region: 'east',
                    layout: 'ux.multiaccordion',
                    animate: true,
                    width: 200,
                    split: true,
                    collapsible: true,
                    collapseMode: 'mini',
                    header: false,
                    margins: '0 5 0 5',
                    border: true,
                    plugins: [{
                        ptype: 'ux.itemregistry',
                        key:   this.app.appName + '-' + this.recordClass.prototype.modelName + '-editDialog-eastPanel'
                    }],
                    items: [
                        new Ext.Panel({
                            // @todo generalise!
                            title: this.app.i18n._('Description'),
                            iconCls: 'descriptionIcon',
                            layout: 'form',
                            labelAlign: 'top',
                            border: false,
                            items: [{
                                hideLabel: true,
                                xtype:'textfield',
                                width: '100%',
                                itemCls: 'cal-urlfield',
                                name: 'url',
                                emptyText: this.app.i18n._('URL'),
                                requiredGrant: 'editGrant'
                            }, {
                                style: 'margin-top: -4px; border 0px;',
                                labelSeparator: '',
                                xtype:'textarea',
                                name: 'description',
                                hideLabel: true,
                                grow: false,
                                preventScrollbars:false,
                                anchor:'100% 100%',
                                emptyText: this.app.i18n._('Enter a description'),
                                requiredGrant: 'editGrant'
                            }]
                        }),
                        new Tine.widgets.tags.TagPanel({
                            app: 'Calendar',
                            border: false,
                            bodyStyle: 'border:1px solid #B5B8C8;'
                        })
                    ]
                }]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: (this.record) ? this.record.id : '',
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName'),
                getRecordId: (function() {
                    return this.record.isRecurInstance() ? this.record.get('base_event_id') : this.record.get('id');
                }).createDelegate(this)
            })]
        };
    },

    onFreeTimeSearch: function() {
        this.onRecordUpdate();
        Tine.Calendar.FreeTimeSearchDialog.openWindow({
            record: this.record,
            listeners: {
                scope: this,
                apply: this.onFreeTimeSearchApply
            }
        });
    },

    onFreeTimeSearchApply: function(dialog, recordData) {
        this.record = this.recordProxy.recordReader({responseText: recordData});
        this.onRecordLoad();
    },

    /**
     * mute first alert
     * 
     * @param {} button
     * @param {} e
     */
    onMuteNotificationOnce: function (button, e) {
        this.record.set('mute', button.pressed);
        button.setText(Tine.Tinebase.appMgr.get('Calendar').i18n._(button.pressed ?
            'Notifications are disabled' : 'Notifications are enabled'
        ));
    },

    onPrint: function(printMode) {
        this.onRecordUpdate();
        var renderer = new Tine.Calendar.Printer.EventRenderer();
        renderer.print(this);
    },

    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');

        this.addEvents(
            /**
             * @event dtStartChange
             * @desc  Fired when dtstart chages in UI
             * @param {Json String} oldValue, newValue
             */
            'dtStartChange'
        );

        this.recordProxy = new Tine.Calendar.Model.EventJsonBackend({
            saveRecord: this.saveEvent.createDelegate(this)
        });

        this.action_freeTimeSearch = new Ext.Action({
            text: Tine.Tinebase.appMgr.get('Calendar').i18n._('Free Time Search'),
            handler: this.onFreeTimeSearch,
            iconCls: 'cal-action_fretimesearch',
            disabled: false,
            scope: this
        });

        this.button_muteNotification =  new Ext.Button(new Ext.Action({
            text: Tine.Tinebase.appMgr.get('Calendar').i18n._('Notifications are enabled'),
            handler: this.onMuteNotificationOnce,
            iconCls: 'action_mute_noteification',
            disabled: false,
            scope: this,
            enableToggle: true,
        }));

        this.tbarItems = [new Ext.Button(this.action_freeTimeSearch), this.button_muteNotification
        , new Ext.Button(new Ext.Action({
            text: Tine.Tinebase.appMgr.get('Calendar').i18n._('Print Event'),
            handler: this.onPrint,
            iconCls:'action_print',
            disabled: false,
            scope: this
        }))];

        var organizerCombo;
        this.attendeeGridPanel = new Tine.Calendar.AttendeeGridPanel({
            editDialog: this,
            bbar: [{
                xtype: 'label',
                html: Tine.Tinebase.appMgr.get('Calendar').i18n._('Organizer') + "&nbsp;"
            }, organizerCombo = new OrganizerCombo({
                width: 300,
                forceSelection: true, // no selection of external organizers here yet
                onOrganizerSelect: (data) => {
                    _.each(data, (val, key) => { this.record.set(key, val) })
                }
            })]
        });
        
        // auto location
        this.attendeeGridPanel.on('afteredit', function(o) {
            if (o.field == 'user_id' && o.record.get('user_type') == 'resource' ) {
                var typeId = _.get(o.record, 'data.user_id.type'),
                    type = Tine.Tinebase.widgets.keyfield.StoreMgr.get('Calendar', 'resourceTypes').getById(typeId);

                if (type?.get('is_location')) {
                    this.setLocationRecord(o.record);
                }
            }

            this.checkStates();
        }, this);

        this.attendeeGridPanel.store.on('remove', function(store, record, idx) {
            if(_.get(record, 'data.user_id', false)){
                if (_.get(record, 'data.user_type') !== 'ressources') return;

                //remove location if location is location from deleted ressource
                var typeId = _.get(record, 'data.user_id.type'),
                    type = Tine.Tinebase.widgets.keyfield.StoreMgr.get('Calendar', 'resourceTypes').getById(typeId),
                    locationName = this.attendeeGridPanel.renderAttenderResourceName(record.get('user_id'), {noIcon: true}),
                    locationField = this.getForm().findField('location');

                if (type?.get('is_location') && locationName === locationField.getValue()) {
                    locationField.setValue('');
                }
            }
        }, this);
        
        this.on('render', function() {this.getForm().add(organizerCombo);}, this);

        this.pollPanel = new Tine.Calendar.PollPanel({
            editDialog : this
        });
        this.rrulePanel = new Tine.Calendar.RrulePanel({
            eventEditDialog : this
        });
        this.alarmPanel = new Tine.widgets.dialog.AlarmPanel({});
        this.attendeeStore = this.attendeeGridPanel.getStore();
        this.attendeeStore.on('add', this.onAttendeeStoreChange, this);
        this.attendeeStore.on('clear', this.onAttendeeStoreChange, this);
        this.attendeeStore.on('load', this.onAttendeeStoreChange, this);
        this.attendeeStore.on('datachanged', this.onAttendeeStoreChange, this);
        this.attendeeStore.on('remove', this.onAttendeeStoreChange, this);

        // a combo with all attendee + origin/organizer
        this.perspectiveCombo = new Tine.Calendar.PerspectiveCombo({
            editDialog: this
        });

        this.initMessageBus();

        Tine.Calendar.EventEditDialog.superclass.initComponent.call(this);
        this.addAttendee();
        // Start with unmodified Record
        this.record.commit();
    },

    initMessageBus: function() {
        this.postalSubscriptions = [];
        this.postalSubscriptions.push(postal.subscribe({
            channel: "recordchange",
            topic: 'Calendar.Event.*',
            callback: this.onRecordChanges.createDelegate(this)
        }));
    },

    /**
     * bus notified about record changes
     */
    onRecordChanges: async function(data, e) {
        if (data?.[0] && this.recordId === data[0].recordId && data[0].verb === 'delete') {
            this.window.close(true);
        }
    },

    setLocationRecord: function(resource, overwrite = false) {
        const locationField = this.getForm().findField('location');
        const locationRecordField = this.getForm().findField('location_record');
        const siteField = this.getForm().findField('event_site');

        if (! locationField.getValue() || overwrite) {
            locationField.setValue(
                this.attendeeGridPanel.renderAttenderResourceName(resource.get('user_id'), {noIcon: true})
            );
        }

        var relations = _.get(resource, 'data.user_id.relations'),
            locationId = relations ? _.findIndex(relations, function(k) { return k.type == 'LOCATION'; }) : -1,
            locationContact = locationId >= 0 ? relations[locationId].related_record : null,
            siteId = relations ? _.findIndex(relations, function (k) { return k.type == 'SITE'; }) : -1,
            siteContact = siteId >= 0 ? relations[siteId].related_record : null;

        if (locationContact && (!locationRecordField.getValue() || overwrite)) {
            locationRecordField.setValue(locationContact);
        } else if (siteContact && (!locationRecordField.getValue() || overwrite)) {
            locationRecordField.setValue(siteContact);
        }

        if (Tine.Tinebase.featureEnabled('featureSite') && siteContact && (!siteField.getValue() || overwrite)) {
            siteField.setValue(siteContact)
        }
    },

    /**
     * if this addRelations is set, iterate and create attendee
     */
    addAttendee: function() {
        var attendee = this.record.get('attendee');
        var attendee = Ext.isArray(attendee) ? attendee : [];
        
        if (Ext.isArray(this.plugins)) {
            for (var index = 0; index < this.plugins.length; index++) {
                if (this.plugins[index].hasOwnProperty('addRelations')) {

                    var config = this.plugins[index].hasOwnProperty('relationConfig') ? this.plugins[index].relationConfig : {};
                    
                    for (var index2 = 0; index2 < this.plugins[index].addRelations.length; index2++) {
                        var item = this.plugins[index].addRelations[index2];
                        var attender = Ext.apply({
                            user_type: 'user',
                            role: 'REQ',
                            quantity: 1,
                            status: 'NEEDS-ACTION',
                            user_id: item
                        }, config);
                        
                        attendee.push(attender);
                    }
                }
            }
        }
        
        this.record.set('attendee', attendee);
    },
    
    /**
     * checks if form data is valid
     * 
     * @return {Boolean}
     */
    isValid: function() {
        var isValid = this.validateDtStart() && this.validateDtEnd();
        
        if (! this.rrulePanel.isValid()) {
            isValid = false;
            
            this.rrulePanel.ownerCt.setActiveTab(this.rrulePanel);
        }
        
        return isValid && Tine.Calendar.EventEditDialog.superclass.isValid.apply(this, arguments);
    },
     
    onAllDayChange: function(checkbox, isChecked) {
        var dtStartField = this.getForm().findField('dtstart');
        var dtEndField = this.getForm().findField('dtend');
        dtStartField.setDisabled(isChecked, 'time');
        dtEndField.setDisabled(isChecked, 'time');
        
        if (isChecked) {
            dtStartField.clearTime();
            var dtend = dtEndField.getValue();
            if (Ext.isDate(dtend) && dtend.format('H:i:s') != '23:59:59') {
                dtEndField.setValue(dtend.clearTime(true).add(Date.HOUR, 24).add(Date.SECOND, -1));
            }
            
        } else {
            dtStartField.undo();
            dtEndField.undo();
        }
    },
    
    onDtEndChange: function(dtEndField, newValue, oldValue) {
        this.validateDtEnd();
    },
    
    /**
     * on dt start change
     * 
     * @param {} dtStartField
     * @param {} newValue
     * @param {} oldValue
     */
    onDtStartChange: function(dtStartField, newValue, oldValue) {
        if (this.validateDtStart() == false) {
            return false;
        }
        
        if (Ext.isDate(newValue) && Ext.isDate(oldValue)) {
            var dtEndField = this.getForm().findField('dtend'),
                dtEnd = dtEndField.getValue();
                
            if (Ext.isDate(dtEnd)) {
                var duration = dtEnd.getTime() - oldValue.getTime(),
                    newDtEnd = newValue.add(Date.MILLI, duration);
                dtEndField.setValue(newDtEnd);
                this.validateDtEnd();
            }
        }

        this.fireEvent('dtStartChange', Ext.util.JSON.encode({newValue: newValue, oldValue: oldValue || newValue}));
    },
    
    /**
     * copy record
     * 
     * TODO change attender status?
     */
    doCopyRecord: function() {
        var _ = window.lodash;

        // Calendar is the only app with record based grants -> user gets edit grant for all fields when copying
        this.record.set('editGrant', true);

        // BUT: add Grant is container based!
        this.record.set('addGrant', _.get(this.record, 'data.container_id.account_grants.addGrant', false));

        if (this.record.get('status') == 'CANCELED') {
            this.record.set('status', 'CONFIRMED')
        }

        Tine.Calendar.EventEditDialog.superclass.doCopyRecord.call(this);

        const attendeeStore = Tine.Calendar.Model.Attender.getAttendeeStore(this.record.data.attendee);
        const ownAttendee = Tine.Calendar.Model.Attender.getAttendeeStore.getMyAttenderRecord(attendeeStore);
        const allAttendee = Tine.Tinebase.common.assertComparable([]);


        attendeeStore.each(attendee => {
            if(attendee !== ownAttendee) {
                attendee.set('status', 'NEEDS-ACTION');
            }

            allAttendee.push(Object.assign({... attendee.data}, {id: null}));
        });


        this.record.set('attendee', allAttendee);

        // remove event_types ids
        Ext.each(this.record.data.event_types, function(type) {
            delete type.id;
        }, this);


        Tine.log.debug('Tine.Calendar.EventEditDialog::doCopyRecord() -> record:');
        Tine.log.debug(this.record);
    },

    /**
     * executed after record got updated from proxy
     */
    onRecordLoad: function() {
        if (String(this.record.id).match(/new-ext-gen/)) {
            this.record.set('id', '');
        }

        this.omitCopyTitle = this.record.hasPoll();
        this.getForm().findField('summary').allowBlank = !this.record.get('editGrant');
        Tine.Calendar.EventEditDialog.superclass.onRecordLoad.call(this);
    },

    isNewRecord: function () {
        return !this.record || !(this.record.get && this.record.get('creation_time'))
    },

    /**
     * is called after all subpanels have been loaded
     */
    onAfterRecordLoad: function() {
        Tine.Calendar.EventEditDialog.superclass.onAfterRecordLoad.call(this);

        // disable relations panel for non persistent exceptions till we have the baseEventId
        if (this.record.isRecurInstance()) {
            this.relationsPanel.setDisabled(true);
        }
        this.attendeeGridPanel.onRecordLoad(this.record);
        this.rrulePanel.onRecordLoad(this.record);
        this.alarmPanel.onRecordLoad(this.record);

        this.perspectiveCombo.loadPerspective();
        // disable container selection combo if user has no right to edit
        this.containerSelect.setDisabled.defer(20, this.containerSelect, [(! this.record.get('editGrant'))]);
        
        // disable time selectors if this is a whole day event
        if (this.record.get('is_all_day_event')) {
            this.onAllDayChange(null, true);
        }
        this.button_muteNotification.pressed = !!+this.record.get('mute');
        this.button_muteNotification.setText(!!+this.record.get('mute') ?
            Tine.Tinebase.appMgr.get('Calendar').i18n._('Notifications are disabled') :
            Tine.Tinebase.appMgr.get('Calendar').i18n._('Notifications are enabled'));
    },

    /**
     * generic apply changes handler
     * @param {Boolean} closeWindow
     */
    onApplyChanges: function(closeWindow) {
        if (this.app.featureEnabled('featureEventNotificationConfirmation') && !+this.record.get('mute')) {
            Ext.MessageBox.confirm(
                this.app.i18n._('Send Notification?'),
                this.app.i18n._('Changes to this event may trigger notifications. Click the button labeled "Notifications are enabled" to switch to "Notifications are disabled".'),
                function (button) {
                    if (button === 'yes') {
                        Tine.Calendar.EventEditDialog.superclass.onApplyChanges.call(this,closeWindow);
                    }
                },
                this
            );
            return;
        } else {
            Tine.Calendar.EventEditDialog.superclass.onApplyChanges.call(this, closeWindow);
        }
    },

    onRecordUpdate: function() {
        Tine.Calendar.EventEditDialog.superclass.onRecordUpdate.apply(this, arguments);
        this.attendeeGridPanel.onRecordUpdate(this.record);
        this.rrulePanel.onRecordUpdate(this.record);
        this.alarmPanel.onRecordUpdate(this.record);
        this.perspectiveCombo.updatePerspective();
    },

    onAttendeeStoreChange: function() {
        // NOTE: mind the add new attendee row!
        this.action_freeTimeSearch.setDisabled(this.attendeeStore.getCount() < 2);
    },
    setTabHeight: function() {
        var eventTab = this.items.first().items.first();
        var centerPanel = eventTab.items.first();
        var tabPanel = centerPanel.items.last();
        tabPanel.setHeight(centerPanel.getEl().getBottom() - tabPanel.getEl().getTop());
    },
    
    validateDtEnd: function() {
        var dtStart = this.getForm().findField('dtstart').getValue(),
            dtEndField = this.getForm().findField('dtend'),
            dtEnd = dtEndField.getValue(),
            endTime = this.adjustTimeToUserPreference(dtEndField.getValue(), 'daysviewendtime');
        
        if (! Ext.isDate(dtEnd)) {
            dtEndField.markInvalid(this.app.i18n._('The end date is not valid'));
            return false;
        } else if (Ext.isDate(dtStart) && dtEnd.getTime() - dtStart.getTime() <= 0) {
            dtEndField.markInvalid(this.app.i18n._('The end date must be after the start date'));
            return false;
        } else if (! Tine.Tinebase.configManager.get('daysviewallowallevents', 'Calendar')
                && this.getForm().findField('is_all_day_event').checked === false
                && !! Tine.Tinebase.configManager.get('daysviewcroptime', 'Calendar') && dtEnd > endTime)
        {
            dtEndField.markInvalid(this.app.i18n._('The end time cannot be later than the configured time.'));
            return false;
        } else {
            dtEndField.clearInvalid();
            return true;
        }
    },
    
    /**
     * adjusts given date (end/start) to user preference (hours)
     * 
     * @param {Date} dateValue
     * @param {String} prefKey
     * @return {Date}
     */
    adjustTimeToUserPreference: function(dateValue, prefKey) {
        let userPreferenceDate = dateValue;
        const prefs = this.app.getRegistry().get('preferences');
        const hour = prefs.get(prefKey).split(':')[0];
        
        // adjust date to user preference
        userPreferenceDate.setHours(hour);
        userPreferenceDate.setMinutes(0);
        if (prefKey == 'daysviewendtime' && userPreferenceDate.format('H:i') == '00:00') {
            userPreferenceDate = userPreferenceDate.add(Date.MINUTE, -1);
        }
        
        return userPreferenceDate;
    },
    
    validateDtStart: function() {
        var dtStartField = this.getForm().findField('dtstart'),
            dtStart = dtStartField.getValue(),
            startTime = this.adjustTimeToUserPreference(dtStartField.getValue(), 'daysviewstarttime');
        
        if (! Ext.isDate(dtStart)) {
            dtStartField.markInvalid(this.app.i18n._('The start date is not valid'));
            return false;
        } else if (! Tine.Tinebase.configManager.get('daysviewallowallevents', 'Calendar')
                && this.getForm().findField('is_all_day_event').checked === false
                && !! Tine.Tinebase.configManager.get('daysviewcroptime', 'Calendar')
                && dtStart < startTime)
        {
            dtStartField.markInvalid(this.app.i18n._('The start date cannot be earlier than the configured time.'));
            return false;
        } else {
            dtStartField.clearInvalid();
            return true;
        }
    }
});

/**
 * Opens a new event edit dialog window
 * 
 * @return {Ext.ux.Window}
 */
Tine.Calendar.EventEditDialog.openWindow = function (config) {
    // record is JSON encoded here...
    var id = config.recordId ? config.recordId : 0;
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 505,
        name: Tine.Calendar.EventEditDialog.prototype.windowNamePrefix + id,
        contentPanelConstructor: 'Tine.Calendar.EventEditDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

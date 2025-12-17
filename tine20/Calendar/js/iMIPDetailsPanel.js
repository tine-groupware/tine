/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Calendar');

/**
 * display panel for MIME type text/calendar
 * 
 * NOTE: this panel is registered on Tine.Calendar::init
 * 
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.iMIPDetailsPanel
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @constructor
 */
Tine.Calendar.iMIPDetailsPanel = Ext.extend(Tine.Calendar.EventDetailsPanel, {
    /**
     * @cfg {Object} preparedPart
     * server prepared text/calendar iMIP part 
     */
    preparedPart: null,
    
    /**
     * @property actionToolbar
     * @type Ext.Toolbar
     */
    actionToolbar: null,
    
    /**
     * @property iMIPrecord
     * @type Tine.Calendar.Model.iMIP
     */
    iMIPrecord: null,


    allowViewInCalendar: true,

    /**
     * init this component
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');

        this.resolveIMIP(this.messageRecord.get('preparedParts')[0].preparedData);
        this.initIMIPToolbar();

        this.on('afterrender', async (cmp, ownerCt) => {
            const attendeeList = this.getAttendeeList();
            await this.attendeeCombo.syncStore(attendeeList);
            this.showIMIP();
        });
        this.on('updateEvent', async (result) => {
            this.resolveIMIP(result);
            this.showIMIP();
        });

        Tine.Calendar.iMIPDetailsPanel.superclass.initComponent.call(this);
    },

    resolveIMIP(data) {
        if (data) {
            this.prepareData = data;
            this.iMIPrecord = new Tine.Calendar.Model.iMIP(this.prepareData);
        }

        if (! this.iMIPrecord.get('event') || ! Ext.isFunction(this.iMIPrecord.get('event').beginEdit)) {
            this.iMIPrecord.set('event', Tine.Calendar.backend.recordReader({
                responseText: Ext.util.JSON.encode(this.prepareData.event)
            }));
        }
        if (this.iMIPrecord.get('existing_event') && !Ext.isFunction(this.iMIPrecord.get('existing_event').beginEdit)) {
            this.iMIPrecord.set('existing_event', Tine.Calendar.backend.recordReader({
                responseText: Ext.util.JSON.encode(this.iMIPrecord.get('existing_event'))
            }));
        }
    },

    /**
     * process IMIP
     *
     * @param {String} status
     */
    processIMIP: async function (status, range) {
        if (this.iMIPrecord.get('event').isRecurBase() && status !== 'ACCEPTED' && !range) {
            Tine.widgets.dialog.MultiOptionsDialog.openWindow({
                title: this.app.i18n._('Reply to Recurring Event'),
                questionText: this.app.i18n._('You are responding to a recurring event. What would you like to do?'),
                height: 170,
                scope: this,
                options: [
                    {text: this.app.i18n._('Respond to the whole series'), name: 'series'},
                    {text: this.app.i18n._('Do not respond'), name: 'cancel'}
                ],
                handler: function (option) {
                    if (option !== 'cancel') {
                        this.processIMIP(status, option);
                    }
                }
            });
            return;
        }

        if (!this.targetAttendeeRecord) return;

        const attendeeContainers = this.iMIPrecord.get('attendeeContainersAvailable');
        const userId = this.targetAttendeeRecord.data.user_id?.id ?? this.targetAttendeeRecord.data.user_id;
        const options = Object.entries(attendeeContainers)
            .filter(([key]) => key.includes(userId))
            .flatMap(([, records]) => records.map(record => ({ text: record.name, name: record })));

        if (options.length === 0) return;

        const processUpdate = (selectedOption) => {
            const targetAttendeeData = {...this.targetAttendeeRecord.data};
            if (selectedOption) {
                targetAttendeeData.displaycontainer_id = selectedOption;
            }
            targetAttendeeData.status = status;

            this.getLoadMask().show();

            Tine.Calendar.iMIPProcess(this.iMIPrecord.data, targetAttendeeData, (result, response) => {
                if (!response.error) {
                    this.targetAttendeeRecord.set('status', status);
                    this.targetAttendeeRecord.set('displaycontainer_id', selectedOption);
                }
                this.fireEvent('updateEvent', result);
            }, this);
        };

        if (this.targetAttendeeRecord.get('status') !== 'NEEDS-ACTION' || options.length === 1) {
            return processUpdate();
        }

        Tine.widgets.dialog.MultiOptionsDialog.openWindow({
            title: this.app.i18n._('Update Status'),
            questionText: this.app.i18n._('Please select a calendar before update your status'),
            height: 170,
            allowCancel: true,
            scope: this,
            options: options,
            handler: function(option) {
                if (option !== 'cancel') {
                    processUpdate(option);
                } else {
                    this.updateInfo();
                }
            }
        });
    },

    /**
     * iMIP action toolbar
     */
    initIMIPToolbar: function() {
        this.viewInCalendarAction = new Ext.Button(new Ext.Action({
            handler: () => {
                Tine.Calendar.ViewInCalendarDialog.openWindow({
                    record: this.iMIPrecord.get('existing_event') ?? this.iMIPrecord.get('event'),
                    messageRecord: this.messageRecord,
                    targetAttendeeRecord: this.targetAttendeeRecord,
                    allowViewInCalendar: false,
                });
            },
            icon: 'images/icon-set/icon_calendar.svg',
            tooltip: this.app.i18n._('View and Edit Event In Calendar'),
            hidden: !this.allowViewInCalendar,
            style: "margin: 0 10px;",
        }));

        // Create the static text display with hover functionality
        this.attendeeTextContainer = new Ext.Container({
            hidden: true,
            cls: 'attendee-text-container',
            listeners: {
                scope: this,
                render: function(c) {
                    c.getEl().on({
                        scope: this,
                        click: ()=> {
                            this.activateCombo();
                        },
                    });
                }
            },
            items: [
                this.attendeeLabel = new Ext.Component({
                    autoEl: {
                        tag: 'div',
                        cls: 'xtb-text',
                        html: 'Select Attendee'
                    }
                }),
            ]
        });

        // Create the combo (initially hidden)
        this.attendeeCombo = new Tine.Calendar.AttendeeCombo({
            eventRecord: this.iMIPrecord.get('existing_event') ?? this.iMIPrecord.get('event'),
            messageRecord: this.messageRecord,
            defaultValue: 'current',
            width: 300,
            listeners: {
                scope: this,
                select: (combo, record)=> {
                    this.updateInfo();
                },
            }
        });

        // Create the static text display with hover functionality
        this.attendeeComboContainer = new Ext.Container({
            hidden: true,
            items: [this.attendeeCombo]
        });

        // Create a container that will hold both the text and combo
        this.idPrefix = Ext.id();
        this.statusActions = [];
        this.statuses = Tine.Tinebase.widgets.keyfield.StoreMgr.get('Calendar', 'attendeeStatus');
        const existingEvent = this.iMIPrecord.get('existing_event');
        this.statuses.each((item) => {
            // hide the save action if event is already in the calendar , usually external invite does not saved
            if (existingEvent && item.id === 'NEEDS-ACTION') {
                this.statuses.remove(item);
            }
        });
        this.statuses.each((status) => {
            this.statusActions.push(new Ext.Button(new Ext.Action({
                id: this.idPrefix + '-tglbtn-' + status.id,
                text: status.id === 'NEEDS-ACTION' ? this.app.i18n._('Save') : status.get('i18nValue'),
                xtype: 'tbbtnlockedtoggle',
                enableToggle: true,
                handler: this.processIMIP.createDelegate(this, [status.id]),
                icon: status.get('icon'),
                hidden: true,
                toggleGroup: 'attendee-status-tglgroup',
                tooltip: status.id === 'NEEDS-ACTION' ? this.app.i18n._('Save to calendar without responding') : status.get('i18nValue'),
            })));
        });

        // add more actions here (no spam / apply / crush / send event / ...)
        this.iMIPclause = new Ext.Toolbar.TextItem({
            text: '',
            style: "margin: 5px 10px;",
        });

        this.descriptionField = new Ext.form.VueAlert({
            variant: 'warning',
            hidden: true,
        });

        this.tbar = this.actionToolbar = new Ext.Panel({
            layout: 'fit',
            border: false,
            items: [
                this.descriptionField,
                {
                    xtype: 'container',
                    cls: 'cal-event-response-panel',
                    items: [
                        this.iMIPclause,
                        {
                            xtype: 'container',
                            cls: 'cal-event-response-panel-group-right',
                            items: [
                                {
                                    xtype: 'container',
                                    cls: 'subgroup',
                                    items: [
                                        this.attendeeTextContainer,
                                        this.attendeeComboContainer,
                                        this.viewInCalendarAction
                                    ],
                                },
                                {
                                    xtype: 'container',
                                    cls: 'subgroup',
                                    items: this.statusActions,
                                }
                            ]
                        }
                    ]
                },
            ]
        });
    },

    activateCombo() {
        this.attendeeTextContainer.hide();
        this.attendeeComboContainer.show();
        this.attendeeComboContainer.focus();
        this.attendeeCombo.onTriggerClick();
    },

    deactivateCombo() {
        this.attendeeComboContainer.hide();
        this.attendeeTextContainer.show();

        if (this.targetAttendeeRecord) {
            const prefix = this.targetAttendeeRecord.get('status') === 'NEEDS-ACTION' ? this.app.i18n._('Set response for') + ':' : '';
            const attendeeName = this.targetAttendeeRecord.get('displayText') || this.targetAttendeeRecord.get('user_id').n_fileas || '';
            this.attendeeLabel.update(`${prefix}<span style="padding: 0 20px;">${attendeeName}</span>`);
        }
    },

    getTargetAttendeeRecord() {
        const selectedAttendeeId = this.attendeeCombo.getValue();
        if (!selectedAttendeeId) return null;

        this.targetAttendeeRecord = this.attendeeCombo.store.getById(selectedAttendeeId);

        const attendeeContainers = this.iMIPrecord.get('attendeeContainersAvailable');
        const attendeeList = this.getAttendeeList();

        if (this.targetAttendeeRecord) {
            const targetAttendeeDataFromEvent = attendeeList.find((r) => {
                return r.id === selectedAttendeeId;
            });

            if (targetAttendeeDataFromEvent?.displaycontainer_id) {
                this.targetAttendeeRecord.set('displaycontainer_id', targetAttendeeDataFromEvent.displaycontainer_id);
            } else {
                Object.entries(attendeeContainers).forEach((key) => {
                    const user = this.targetAttendeeRecord.get('user_id');
                    if (user && key[0].includes(user.id)) {
                        this.targetAttendeeRecord.set('displaycontainer_id', key[1][0]);
                    }
                })
            }
        }

        return this.targetAttendeeRecord;
    },

    getAttendeeList() {
        const existingEvent = this.iMIPrecord.get('existing_event');
        const event = this.iMIPrecord.get('event');
        if (existingEvent && existingEvent.get('attendee').length > 0) {
            return existingEvent.get('attendee');
        }

        const myAttender = event.getMyAttenderRecord().data;
        if (myAttender?.user_id?.id) {
            myAttender.id = myAttender.user_id.id;
        }
        return myAttender ? [myAttender] : [];
    },

    /**
     * show/layout iMIP panel
     */
    showIMIP: function () {
        this.updateInfo();
        this.getLoadMask().hide();

        const singleRecordPanel = this.getSingleRecordPanel();
        singleRecordPanel.setVisible(true);
        singleRecordPanel.setHeight(200);
        singleRecordPanel.loadRecord(this.record);
    },

    updateInfo() {
        const preconditions = this.iMIPrecord.get('preconditions');
        const method = this.iMIPrecord.get('method');
        const event = this.iMIPrecord.get('event');
        const existingEvent = this.iMIPrecord.get('existing_event');
        let attenderRecord = this.getTargetAttendeeRecord();
        this.record = (existingEvent && !preconditions) ? existingEvent : event;
        const myAttenderRecord = existingEvent ? existingEvent.getMyAttenderRecord() : event.getMyAttenderRecord();
        let showActions = false;
        let text = '';

        // show container from existing event if exists
        if (existingEvent?.data?.container_id) {
            event.set('container_id', existingEvent.data.container_id);
        }

        if (!attenderRecord) {
            attenderRecord = myAttenderRecord;
        }
        if (myAttenderRecord) {
            const isInRecipientList = this.messageRecord.data['to'].find((to) => {return myAttenderRecord.get('user_id').email === to.email});
            if (!isInRecipientList) {
                this.descriptionField.setText(this.app.i18n._("Attention! , this is an invitation for ") + this.messageRecord.data['to'][0]['name']);
                this.descriptionField.show();
            }
        }

        // check preconditions
        const uid = this.record.get('recurid') || this.record.get('uid');

        if (preconditions?.[uid]) {
            const precondition = preconditions[uid];
            if (precondition.hasOwnProperty('EVENTEXISTS')) {
                text = this.app.i18n._("The event of this message does not exist");
            }
            else if (precondition.hasOwnProperty('ORIGINATOR')) {
                // display spam box -> might be accepted by user?
                text = this.app.i18n._("The sender is not authorized to update the event");
            }
            else if (precondition.hasOwnProperty('RECENT')) {
                text = this.app.i18n._("This message has already been processed.");
            }
            else if (precondition.hasOwnProperty('ATTENDEE')) {
                // party crush button?
                if (myAttenderRecord) {
                    showActions = true;
                }
                text = this.app.i18n._("You are not an attendee of this event");
            }
            else if (precondition.hasOwnProperty('NOTDELETED')) {
                text = this.app.i18n._("This event has been deleted by the organizer");
            }
            else if (precondition.hasOwnProperty('ORGANIZER')) {
                // fixme: check precondition failed, allow it in be ?
                text = this.app.i18n._("You are the organizer of this event.");
            }
            else {
                text = this.app.i18n._("Unsupported message");
            }
        }
        // method specific text / actions
        if (!preconditions?.[uid]) {
            switch (method) {
                case 'REQUEST':
                    if (attenderRecord) {
                        showActions = true;
                    }

                    if (!myAttenderRecord) {
                        // might happen in shared folders -> we might want to become a party crusher?
                        text = this.app.i18n._("This is an event invitation for someone else.");
                        break;
                    }
                    if (existingEvent && attenderRecord && attenderRecord.get('status') !== 'NEEDS-ACTION'
                        && (event.get('external_seq') <= existingEvent.get('external_seq')
                            || event.get('seq') <= existingEvent.get('seq'))) {
                        const attendeeName = (attenderRecord.get('user_id').n_fn ?? attenderRecord.get('user_id').name);
                        text = attendeeName + ' ' + this.app.i18n._("has already replied to this event invitation.");
                    }
                    if (existingEvent && existingEvent.isRescheduled(event)) {
                        text = this.app.i18n._('The event got rescheduled.');
                    }
                    break;
                case 'REPLY':
                    // Someone replied => autoprocessing atm.
                    text = this.app.i18n._('An invited attendee responded to the invitation.');
                    break;
                case 'CANCEL':
                    text = this.app.i18n._('This event has been canceled.');
                    this.allowViewInCalendar = false;
                    break;
                default:
                    text = this.app.i18n._("Unsupported method");
                    this.allowViewInCalendar = false
                    break;
            }
        }

        this.iMIPclause.setText(text);
        this.viewInCalendarAction[(!this.allowViewInCalendar || !attenderRecord) ? 'hide' : 'show']();

        if (showActions) {
            Ext.each(this.statusActions, function (action) {
                action.show();
            });
            this.deactivateCombo();
            this.actionToolbar.doLayout();
        }

        this.statuses.each((status) => {
            const freqBtn = Ext.getCmp(this.idPrefix + '-tglbtn-' + status.id);

            if (freqBtn && attenderRecord) {
                freqBtn.toggle(status.id === attenderRecord.get('status'));
            }
            // hide the save action if event is already in the calendar , usually external invite is not saved
            if (!attenderRecord) {
                freqBtn.hide();
            }
        })
    }
});

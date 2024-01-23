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
    
    /**
     * @property statusActions
     * @type Array
     */
    statusActions:[],
    
    /**
     * init this component
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        
        this.iMIPrecord = new Tine.Calendar.Model.iMIP(this.preparedPart.preparedData);
        if (! this.iMIPrecord.get('event') || ! Ext.isFunction(this.iMIPrecord.get('event').beginEdit)) {
            this.iMIPrecord.set('event', Tine.Calendar.backend.recordReader({
                responseText: Ext.util.JSON.encode(this.preparedPart.preparedData.event)
            }));
        }
        
        this.initIMIPToolbar();
        
        this.on('afterrender', this.showIMIP, this);
        
        Tine.Calendar.iMIPDetailsPanel.superclass.initComponent.call(this);
    },
    
    /**
     * (re) prepare IMIP
     */
    prepareIMIP: function() {
        this.iMIPclause.setText(this.app.i18n._('Checking Calendar Data...'));
        
        Tine.Calendar.iMIPPrepare(this.iMIPrecord.data, function(result, response) {
            this.preparedPart.preparedData = result;
            if (response.error) {
                // give up!
                this.iMIPrecord.set('preconditions', {'GENERIC': 'generic problem'});
            } else {
                this.iMIPrecord = new Tine.Calendar.Model.iMIP(result);
                this.iMIPrecord.set('event', Tine.Calendar.backend.recordReader({
                    responseText: Ext.util.JSON.encode(result.event)
                }));
            }
            
            this.showIMIP();
        }, this);
    },
    
    /**
     * process IMIP
     * 
     * @param {String} status
     */
    processIMIP: function(status, range) {
        if (this.iMIPrecord.get('event').isRecurBase() && status != 'ACCEPTED' && !range) {
            Tine.widgets.dialog.MultiOptionsDialog.openWindow({
                title: this.app.i18n._('Reply to Recurring Event'),
                questionText: this.app.i18n._('You are responding to an recurring event. What would you like to do?'),
                height: 170,
                scope: this,
                options: [
                    {text: this.app.i18n._('Respond to whole series'), name: 'series'},
                    {text: this.app.i18n._('Do not respond'), name: 'cancel'}
                ],
                
                handler: function(option) {
                    if (option != 'cancel') {
                        this.processIMIP(status, option);
                    }
                }
            });
            return;
        }
        
        Tine.log.debug('Tine.Calendar.iMIPDetailsPanel::processIMIP status: ' + status);
        this.getLoadMask().show();
        
        Tine.Calendar.iMIPProcess(this.iMIPrecord.data, status, function(result, response) {
            this.preparedPart.preparedData = result;
            if (response.error) {
                // precondition changed?  
                return this.prepareIMIP();
            }
            
            // load result
            this.iMIPrecord = new Tine.Calendar.Model.iMIP(result);
            this.iMIPrecord.set('event', Tine.Calendar.backend.recordReader({
                responseText: Ext.util.JSON.encode(result.event)
            }));
            this.showIMIP();
        }, this);
    },
    
    /**
     * iMIP action toolbar
     */
    initIMIPToolbar: function() {
        var singleRecordPanel = this.getSingleRecordPanel();
        
        this.actions = [];
        this.statusActions = [];
        
        Tine.Tinebase.widgets.keyfield.StoreMgr.get('Calendar', 'attendeeStatus').each(function(status) {
            // NEEDS-ACTION is not appropriate in iMIP context
            if (status.id == 'NEEDS-ACTION') return;
            
            this.statusActions.push(new Ext.Action({
                text: status.get('i18nValue'),
                handler: this.processIMIP.createDelegate(this, [status.id]),
                icon: status.get('icon')
            }));
        }, this);
        
        this.actions = this.actions.concat(this.statusActions);
        
        // add more actions here (no spam / apply / crush / send event / ...)
        
        this.iMIPclause = new Ext.Toolbar.TextItem({
            text: ''
        });
        this.tbar = this.actionToolbar = new Ext.Toolbar({
            style: 'overflow: auto;',
            items: [{
                    xtype: 'tbitem',
                    cls: 'CalendarIconCls',
                    width: 16,
                    height: 16,
                    style: 'margin: 3px 5px 2px 5px;'
                },
                this.iMIPclause,
                '->'
            ].concat(this.actions)
        });
    },
    
    /**
     * show/layout iMIP panel
     */
    showIMIP: function() {
        var singleRecordPanel = this.getSingleRecordPanel(),
            preconditions = this.iMIPrecord.get('preconditions'),
            method = this.iMIPrecord.get('method'),
            event = this.iMIPrecord.get('event'),
            existingEvent = this.iMIPrecord.get('existing_event') ? Tine.Calendar.backend.recordReader({
                responseText: Ext.util.JSON.encode(this.iMIPrecord.get('existing_event'))
            }) : null,
            myAttenderRecord = existingEvent ? existingEvent.getMyAttenderRecord() : event.getMyAttenderRecord(),
            myAttenderstatus = myAttenderRecord ? myAttenderRecord.get('status') : null;
            
        // show container from existing event if exists
        if (existingEvent && existingEvent.container_id) {
            event.set('container_id', existingEvent.container_id);
        }
            
        // reset actions
        Ext.each(this.actions, function(action) {action.setHidden(true)});
        
        // check preconditions
        if (preconditions) {
            if (preconditions.hasOwnProperty('EVENTEXISTS')) {
                this.iMIPclause.setText(this.app.i18n._("The event of this message does not exist"));
            }
            
            else if (preconditions.hasOwnProperty('ORIGINATOR')) {
                // display spam box -> might be accepted by user?
                this.iMIPclause.setText(this.app.i18n._("The sender is not authorised to update the event"));
            }
            
            else if (preconditions.hasOwnProperty('RECENT')) {
//            else if (preconditions.hasOwnProperty('TOPROCESS')) {
                this.iMIPclause.setText(this.app.i18n._("This message is already processed"));
            }
            
            else if (preconditions.hasOwnProperty('ATTENDEE')) {
                // party crush button?
                this.iMIPclause.setText(this.app.i18n._("You are not an attendee of this event"));
            }

            else if (preconditions.hasOwnProperty('NOTDELETED')) {
                this.iMIPclause.setText(this.app.i18n._("This event has been deleted by the organizer"));
            }

            else if (preconditions.hasOwnProperty('NOTCANCELLED')) {
                this.iMIPclause.setText(this.app.i18n._("This event has been canceled."));
            }
            
            else {
                this.iMIPclause.setText(this.app.i18n._("Unsupported message"));
            }
        } 
        
        // method specific text / actions
        else {
            switch (method) {
                case 'REQUEST':
                    if (! myAttenderRecord) {
                        // might happen in shared folders -> we might want to become a party crusher?
                        this.iMIPclause.setText(this.app.i18n._("This is an event invitation for someone else."));
                        break;
                    } else if (existingEvent && myAttenderstatus !== 'NEEDS-ACTION'
                        && (event.get('external_seq') <= existingEvent.get('external_seq')
                        || event.get('seq') <= existingEvent.get('seq'))) {
                        this.iMIPclause.setText(this.app.i18n._("You have already replied to this event invitation."));
                    } else if (existingEvent && existingEvent.isRescheduled(event)) {
                        this.iMIPclause.setText(this.app.i18n._('The event got rescheduled. Set your response to:'));
                    } else {
                        this.iMIPclause.setText(this.app.i18n._('You received an event invitation. Set your response to:'));
                    }
                    Ext.each(this.statusActions, function(action) {action.setHidden(false)});
                    break;
                    
                    
                case 'REPLY':
                    // Someone replied => autoprocessing atm.
                    this.iMIPclause.setText(this.app.i18n._('An invited attendee responded to the invitation.'));
                    break;
                    
                case 'CANCEL':
                    this.iMIPclause.setText(this.app.i18n._('This event has been canceled.'));
                    break;

                default:
                    this.iMIPclause.setText(this.app.i18n._("Unsupported method"));
                    break;
            }
        }
        
        this.getLoadMask().hide();
        singleRecordPanel.setVisible(true);
        singleRecordPanel.setHeight(150);
        
        this.record = existingEvent && ! preconditions ? existingEvent : event;
        singleRecordPanel.loadRecord(this.record);
    }
});

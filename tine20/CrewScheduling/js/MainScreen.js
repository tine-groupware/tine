/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import {get} from "lodash";

Ext.ns('Tine.CrewScheduling');

import * as csRole from './Model/schedulingRole';
import * as calEventType from './Calendar/Model/eventType';
import * as async from 'async';
// import AttendeeCapability from './Model/AttendeeCapability';
import * as eventRoleConfig from './Model/eventRoleConfig';
import AttendeeValidation from './Model/AttendeeValidation';
import * as eRC from "./Model/eventRoleConfig";
import Poll from './Model/Poll';
import PollParticipant from "./Model/PollParticipant";
import PollReply from "./Model/PollReply";
import './Poll/SchedulingRoleField';
import './Poll/ParticipantsField';
import './Poll/Participant/PollRepliesField';
import PollGridDialog from './Poll/GridDialog';
import './Poll/SchedulingRoleField';
import {getRoleTypesKey} from "./Model/eventRoleConfig";

require('./MemberSelectionPanel');
require('./EventMembersGrid');
require('./ExportDialog');

/**
 * @namespace   Tine.CrewScheduling
 * @class       Tine.CrewScheduling.MainScreen
 * @extends     Ext.Panel
 *
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.CrewScheduling.MainScreen = Ext.extend(Ext.Panel, {
    /**
     * @property {Tine.Tinebase.Application} app
     */
    app: null,
    /**
     * @property {Tine.Tinebase.widgets.form.RecordsPickerCombo} siteCombo
     */
    siteCombo: null,
    /**
     * @property {Ext.ux.form.PeriodPicker} periodPicker
     */
    periodPicker: null,
    /**
     * @property {Tine.Tinebase.data.RecordStore} eventStore
     */
    eventStore: null,
    /**
     * @property {Tine.Tinebase.data.RecordStore} csRolesStore
     */
    csRolesStore: null,
    /**
     * @property {Tine.Tinebase.data.RecordStore} csPositionsStore
     */
    csPositionsStore: null,
    /**
     * @property {Tine.Tinebase.data.RecordStore} csGroupsStore
     */
    csGroupsStore: null,
    /**
     * @property {Tine.Tinebase.data.RecordStore} csMembersStore
     */
    csMembersStore: null,
    /**
     * @property {Tine.Tinebase.data.RecordStore} csPollsStore
     */
    csPollsStore: null,
    /**
     * @property {Tine.CrewScheduling.MemberSelectionPanel} memberSelectionPanel
     */
    memberSelectionPanel: null,
    /**
     * @property {Tine.CrewScheduling.EventMembersGrid} membersGrid
     */
    membersGrid: null,
    /**
     * @property {Ext.Button} buttonCancel
     */
    buttonCancel: null,
    /**
     * @property {Ext.Button} buttonApply
     */
    buttonApply: null,
    /**
     * @property {AttendeeValidation}
     */
    attendeeValidation: null,

    /* private */
    layout: 'border',
    colorManager: null,

    initComponent: function() {
        var _ = window.lodash,
            me = this;

        this.app = Tine.Tinebase.appMgr.get('CrewScheduling');

        this.csRolesStore = new Tine.Tinebase.data.RecordStore({
            readOnly: true,
            autoLoad: false,
            remoteSort: true,
            sortInfo: {field: 'order', direction: 'ASC'},
            recordClass: Tine.CrewScheduling.Model.SchedulingRole
        });

        this.csGroupsStore = new Tine.Tinebase.data.RecordStore({
            readOnly: true,
            autoLoad: false,
            recordClass: Tine.Addressbook.Model.List
        });

        this.csMembersStore = new Tine.Tinebase.data.RecordStore({
            readOnly: true,
            autoLoad: false,
            recordClass: Tine.Calendar.Model.Attender,
            proxy: new Tine.Calendar.Model.AttenderProxy({
                freeBusyEventsProvider: _.bind(function() {
                    return this.eventStore.data.items;
                }, this)
            })
        });

        this.csPollsStore = new Tine.Tinebase.data.RecordStore({
            readOnly: true,
            autoLoad: false,
            recordClass: Tine.CrewScheduling.Model.Poll
        });

        this.eventStore = new Tine.Tinebase.data.RecordStore({
            readOnly: true,
            autoLoad: false,
            remoteSort: false,
            sortInfo: {field: 'dtstart', direction: 'ASC'},
            recordClass: Tine.Calendar.Model.Event,
            proxy: Tine.Calendar.backend,
            pruneModifiedRecords: true,
            getModifiedRecords: function() {return window.lodash.filter(this.modified, {dirty: true});}
        });

        this.initBaseLayout();
        Tine.CrewScheduling.MainScreen.superclass.initComponent.call(this);

        this.siteCombo.on('change', this.onSiteComboChange, this);
        this.periodPicker.on('change', this.onPeriodChange, this , {buffer: 250});
        this.eventStore.on('load', this.manageButtonApply, this);
        this.eventStore.on('update', this.manageButtonApply, this);

        this.eventStore.on('update', this.onRecordChanges, this);
        this.eventStore.on('add', (store, records, idx) => {
            _.forEach(records, (record) => {
                this.onRecordChanges(store, record, 'add');
            })
        })

        this.colorManager = new Tine.Calendar.ColorManager({});

        this.attendeeValidation = new AttendeeValidation({ formatMessage: this.app.formatMessage.bind(this.app) });
    },

    afterRender: function() {
        Tine.CrewScheduling.MainScreen.superclass.afterRender.call(this);

        var _ = window.lodash,
            me = this;

        this.membersGrid.on('updateMemberCount', this.onUpdateMemberCount, this);
        this.membersGrid.getSelectionModel().on('selectionchange', this.onMemberGridSelectionChange, this, {buffer: 50});
        this.membersGrid.getColumnModel().on('configchange', this.onMemberGridSelectionChange, this, {buffer: 200});

        this.showLoadMask()
            .then(_.bind(me.loadCSRoles, me))
            // .then(_.bind(me.loadEventTypesByPosition, me))
            .then(_.bind(me.loadRuntimeData, me))
            .then(_.bind(me.hideLoadMask, me));


        this.dragZone = new Ext.dd.DragZone(this.getEl(), {
            ddgroup: 'cs-members',
            scroll: false,

            getDragData: function(e) {
                var _ = window.lodash,
                    v = me.memberSelectionPanel.dataView,
                    sourceEl = e.getTarget(v.itemSelector, 50),
                    csTokenId = sourceEl ? Ext.fly(sourceEl).getAttribute('tine-cs-token-id') : null,
                    [cal_event_id, user_type, user_id, role] = csTokenId ? csTokenId.split(';') : [],
                    dragRecord = user_id ? me.memberSelectionPanel.store.getById(`${user_type}-${user_id}`) : null,
                    partnerIds = _.map(_.get(dragRecord, 'data.user_id.partners', []), 'id'),
                    partners = _.filter(v.store.data.items, function(member) {
                        return _.indexOf(partnerIds, _.get(member, 'data.user_id.id')) >= 0;
                    }),
                    isPartnerSelect = !! e.getTarget('.cs-partners'),
                    eventMemberTokenIds = cal_event_id ? _.compact(_.uniq(_.concat(me.membersGrid.selectedTokens, csTokenId))) : [],
                    selected = eventMemberTokenIds.length ? _.compact(eventMemberTokenIds.map(memberTokenId => {
                        // @TODO: add patners from same event when isPartnerSelect
                        return me.memberSelectionPanel.store.getById(memberTokenId.split(';').splice(1,2).join('-'));
                    })) : _.compact(_.union(v.getSelectedRecords()
                        .concat(dragRecord)
                        .concat(isPartnerSelect ? partners : []))
                    ),
                    tokens = me.memberSelectionPanel.memberToken.getTokens(selected, true, '<br />'),
                    ddEl = document.createElement('div');

                ddEl.innerHTML = tokens;
                ddEl.style.height = selected.length * 22 + 'px';
                ddEl.id = Ext.id();

                return selected.length ? {
                    ddel: ddEl,
                    sourceEl,
                    repairXY: Ext.fly(sourceEl).getXY(),
                    sourceStore: v.store,
                    selected,
                    eventMemberTokenIds
                } : null;
            },

            onStartDrag: function(x, y) {
                me.fireEvent('dragStart', this, this.dragData);
            },

            onEndDrag: function(data, e) {
                me.fireEvent('dragEnd', this, data);
            },

            getRepairXY: function() {
                return this.dragData.repairXY;
            }
        });

        this.on('dragStart', this.membersGrid.onDragStart, this.membersGrid);
        this.on('dragEnd', this.membersGrid.onDragEnd, this.membersGrid);

    },

    initBaseLayout: function() {
        this.items = [{
            ref: 'northPanel',
            region: 'north',
            layout: 'hbox',
            layoutConfig: { align: 'stretch' },
            height: 150,
            split: true,
            border: false,
            items: [{
                width: 220,
                xtype: 'form',
                layout: 'form',
                buttonAlign: 'center',
                labelAlign: 'top',
                border: false,
                style: 'border-right: 1px solid #99bbe8;',
                bodyStyle: 'padding: 5px;',
                canonicalName: 'ConfigSection',
                tbar: [{
                        text: this.app.i18n._('Reload'),
                        tooltip: this.app.i18n._('Reload all data'),
                        ref: '../../../buttonRefresh',
                        scope: this,
                        handler: this.onButtonCancel,
                        iconCls: 'cs-refresh'
                    }, {
                        text: this.app.i18n._('Filter'),
                        tooltip: this.app.i18n._('Configure data filters'),
                        ref: '../../../buttonFilter',
                        scope: this,
                        handler: this.onButtonFilter,
                        iconCls: 'action_filter'
                    }, {
                        // @TODO enable if user has at least createPollGrant for one schedulingRole
                        text: this.app.i18n._('Polls'),
                        ref: '../../../buttonPolls',
                        tooltip: this.app.i18n._('Manage Polls'),
                        iconCls: 'cs_poll',
                        handler: this.onButtonPolls,
                        scope: this

                    }, '->', {
                        tooltip: this.app.i18n._('Show more actions'),
                        iconCls: 'action_more',
                        getMenuClass: Ext.emptyFn, // supress arrow
                        menu: [(this.buttonExport = new Ext.Action({
                            text: this.app.i18n._('Export'),
                            tooltip: this.app.i18n._('Export and optionally send data'),
                            iconCls: 'action_export',
                            handler: this.onButtonExport,
                            scope: this

                            })), (this.buttonAutoSchedule = new Ext.Action({
                            text: this.app.i18n._('Auto Schedule'),
                            tooltip: this.app.i18n._('Auto assign attendee for selected / all cells'),
                            scope: this,
                            handler: this.onButtonAutoSchedule,
                            iconCls: 'cs-auto-scheduling'
                        }))]
                    }],
                items: [{
                    fieldLabel: this.app.i18n._('Site'),
                    ref: '../../siteCombo',
                    xtype: 'tinerecordspickercombobox',
                    width: 209, // WTF - 100% is not working
                    recordClass: Tine.Addressbook.Model.Contact,
                    resizeable: true,
                    additionalFilterSpec: {
                        config: { name: 'siteFilter', appName: 'Addressbook' }
                    }
                }/*, { // have liturgical only for the moment
                    fieldLabel: this.app.i18n._('Event Types'),
                    ref: '../../eventTypesCombo',
                    xtype: 'tinerecordspickercombobox',
                    width: 209, // WTF - 100% is not working
                    recordClass: 'Calendar.EventTypes',
                    refIdField: 'record',
                    searchComboConfig: {useEditPlugin: false},
                    editDialogConfig: {mode: 'local'},
                    isMetadataModelFor: 'event_type',
                    requiredGrant: 'editGrant',
                }*/, {
                    ref: '../../periodPicker',
                    xtype: 'ux-period-picker',
                    availableRanges: 'day,week,month,quarter'
                }],
                fbar: [{
                    text: i18n._('Cancel'),
                    minWidth: 70,
                    ref: '../../../buttonCancel',
                    scope: this,
                    handler: this.onButtonCancel,
                    iconCls: 'action_cancel'
                }, {
                    text: i18n._('Ok'),
                    minWidth: 70,
                    ref: '../../../buttonApply',
                    scope: this,
                    disabled: true,
                    handler: this.onButtonApply,
                    iconCls: 'action_saveAndClose'
                }]
            }, new Tine.CrewScheduling.MemberSelectionPanel({
                ref: '../memberSelectionPanel',
                store: this.csMembersStore,
                border: false,
                flex: 1,
                memberTokenConfig: {
                    getTokenStyles: this.getMemberTokenStyles.createDelegate(this)
                }
            })]
        }, new Tine.CrewScheduling.EventMembersGrid({
            ref: 'membersGrid',
            region: 'center',
            border: false,
            layout: 'fit',
            store: this.eventStore,
            csRolesStore: this.csRolesStore,
            csMembersStore: this.csMembersStore,
            mainScreen: this
        })];
    },

    onUpdateMemberCount: function(memberGrid, memberCounts) {
        var _ = window.lodash,
            me = this;

        _.each(memberCounts, function(count, key) {
            var attendee = me.csMembersStore.getById(key.replace(';', '-'));
            if (attendee) {
                attendee.data.user_id.count = count;
            }
        });

        this.memberSelectionPanel.updateMemberCounts(memberCounts);
    },

    // filter selection panel
    onMemberGridSelectionChange: function() {
        const gridView = this.membersGrid.getView();
        const cm = this.membersGrid.getColumnModel();
        const visibleRoles = this.membersGrid.rolesVisible; //cm.config.filter((col) => { return !col.hidden });

        let selection = this.membersGrid.getSelectionModel().getSelections();
        if (! selection.length && visibleRoles.length !== this.csRolesStore.getCount()) {
            // convert hidden cols into implicit selection (to avoid data reloads)
            const numRows = this.membersGrid.store.getCount();
            visibleRoles.forEach((key) => {
                const colIdx = cm.config.indexOf(_.first(cm.config.filter((c) => {return String(c.id).match(key)})));
                if (colIdx >=0) {
                    selection = selection.concat(_.zip(Array.from(Array(numRows).keys()), Array(numRows).fill(colIdx)));
                }
            });
        }

        this.memberSelectionPanel.setFilterCells(_.compact(_.map(selection, function(index) {
            return _.get(Ext.fly(gridView.getCell(index[0], index[1]))?.query('.cs-members-cell'), '[0].id');
        })));
    },

    onSiteComboChange: function(combo, value, oldValue) {
        var _ = window.lodash,
            me = this;

        if (JSON.stringify(value) == JSON.stringify(oldValue)) return;

        this.ifDismissChanges()
            .then(_.bind(me.showLoadMask, me))
            .then(_.bind(function() {
                me.siteCombo.originalValue = me.siteCombo.getValue();
            }), me)
            .then(_.bind(me.loadRuntimeData, me))
            .then(async () => {
                const siteRecords = _.map(this.siteCombo.getValue(), (siteData) => {
                    return Tine.Tinebase.data.Record.setFromJson(siteData, 'Addressbook.Contact')
                });
                // @TODO remove #site on EBHH #1690 cleanup and display site filed if feature is enabled only
                this.membersGrid.setGrouping(siteRecords.length ? (Tine.Tinebase.featureEnabled('featureSite') ? 'event_site' : '#site') : '', siteRecords);
            })
            .then(_.bind(me.hideLoadMask, me))
            .catch(_.bind(function() {
                me.siteCombo.reset();
            }), me);
    },

    onPeriodChange: function(combo, value, oldValue) {
        var _ = window.lodash,
            me = this;

        if (JSON.stringify(value) == JSON.stringify(oldValue)) return;

        // @TODO we don't need to reload groups & members, but freetime
        this.ifDismissChanges()
            .then(_.bind(me.showLoadMask, me))
            .then(_.bind(function() {
                me.periodPicker.originalValue = me.periodPicker.getValue();
            }), me)
            .then(_.bind(me.loadRuntimeData, me))
            .then(_.bind(me.hideLoadMask, me))
            .catch(_.bind(function() {
                me.periodPicker.reset();
            }), me);

    },

    manageButtonApply: function() {
        this.buttonApply.setDisabled(!this.eventStore.getModifiedRecords().length);
        this.buttonExport.setDisabled(this.eventStore.getModifiedRecords().length);
    },

    onButtonPolls: function() {
        PollGridDialog.openWindow({})
    },

    onButtonExport: function() {
        Tine.CrewScheduling.ExportDialog.openWindow(this.membersGrid.getState());
    },

    onButtonAutoSchedule: async function() {
        await Ext.MessageBox.show({
            icon: Ext.MessageBox.INFO_WAIT,
            title: this.app.i18n._('Please wait'),
            msg: this.app.i18n._('Computing Schedule...'),
            width:500,
            progress:true,
            closable:false,
            animEl: this.getEl()
        })

        const cellIds = _.map((this.membersGrid.getSelectionModel().getSelectedCell() ?
            this.membersGrid.getEl().query('.x-grid3-cell-selected .cs-members-cell') :
            this.membersGrid.getEl().query('.cs-members-cell')).filter((el) => { return el.parentElement.parentElement.style.display !== 'none' }), 'id')

        // get stats of all services with fillingProbability > 0 of given event
        const getEventServiceStats = async event => {
            return await async.reduce(await eventRoleConfig.getFromEvent(event), [], async (eventServicesStats, eRC) => {
                const num_required_role_attendee = eRC.num_required_role_attendee
                const currentAttendee = await eventRoleConfig.getEventRoleAttendee(eRC, event)
                const missingCount = num_required_role_attendee - currentAttendee.length
                const roleId = eRC.role.id || eRC.role
                const cellId = `${event.id}:${roleId}`
                const selected = cellIds.indexOf(cellId) >= 0
                const possibleAttendee = _.reduce(this.csMembersStore.data.items, (a, member) => {
                    return a.concat(member.data.user_id.possibleUsages.indexOf(cellId) >=0 && !_.find(currentAttendee, { user_id: { id: member.data.user_id.id } }) ? member : [])
                }, [])

                const fillingProbability = _.reduce(possibleAttendee, (a, member) => {
                    return a + (1 + ['NEEDS-ACTION', 'TENTATIVE', 'ACCEPTED'].indexOf(_.get(member, `data.user_id.pollReplies[${PollReply.getEventRef(event)}].${roleId}.status`, 'NEEDS-ACTION')))
                }, 0) / missingCount

                return eventServicesStats.concat(selected && missingCount && fillingProbability > 0 ? { event, cellId, eRC, missingCount, fillingProbability, possibleAttendee } : [])
            })
        }

        // NOTE: service stats need to be recomputed for a event after each attendee change in the event
        let servicesStats = await async.reduce(this.eventStore.data.items, [], async (serviceStats, event) => {
            return serviceStats.concat(await getEventServiceStats(event))
        })
        const initialServiceCount = servicesStats.length
        const progressText = this.app.i18n._('{0} at {1} ({2}/{3})')

        await async.whilst( async () => !!servicesStats.length, async () => {
            const serviceStats = _.pullAt(_.sortBy(servicesStats, 'fillingProbability'), 0)[0]

            Ext.MessageBox.updateProgress((initialServiceCount-servicesStats.length)/initialServiceCount, String.format(progressText,
                serviceStats.event.get('summary'),
                Tine.Tinebase.common.dateRenderer(serviceStats.event.get('dtstart')),
                initialServiceCount-servicesStats.length,
                initialServiceCount
            ))

            const possibleAttendee = _.sortBy(serviceStats.possibleAttendee, ['data.user_id.count', 'data.user_id.possibleUsages.length'])

            // prefer ACCEPTED
            const attendee = _.find(possibleAttendee, member => _.get(member, `data.user_id.pollReplies[${PollReply.getEventRef(serviceStats.event)}].${serviceStats.roleId}.status`) === 'ACCEPTED') || possibleAttendee[0]

            // @TODO fill all if shortcut action is fallout otherwise just one at a time
            this.membersGrid.addMembersToCell(serviceStats.cellId, [attendee], {role: serviceStats.eRC.role, event_types: serviceStats.eRC.event_types})
            await new Promise((resolve) => { window.setTimeout(resolve, 10) }) // give dom time to update view

            servicesStats = await async.reduce(_.uniq(_.map(servicesStats, 'event')), [], async (serviceStats, event) => {
                return serviceStats.concat(await getEventServiceStats(event))
            })
        })

        Ext.MessageBox.hide()
    },

    onButtonCancel: function() {
        var _ = window.lodash,
            me = this;
        this.ifDismissChanges()
            .then(_.bind(me.showLoadMask, me))
            .then(_.bind(me.loadRuntimeData, me))
            .then(_.bind(me.hideLoadMask, me))
            .catch();
    },

    onButtonApply: async function() {
        var _ = window.lodash,
            me = this,
            modified = this.eventStore.getModifiedRecords(),
            progressText = this.app.i18n._('{0} at {1} ({2}/{3})'),
            errors = [];

        await Ext.MessageBox.show({
            icon: Ext.MessageBox.INFO_WAIT,
            title: this.app.i18n._('Please wait'),
            msg: this.app.i18n._('Saving event:'),
            width:500,
            progress:true,
            closable:false,
            animEl: this.getEl()
        });

        _.reduce(modified, function(promise, event, idx) {
            return promise.then(function() {
                Ext.MessageBox.updateProgress((idx+0.5)/(modified.length), String.format(progressText,
                    event.get('summary'),
                    Tine.Tinebase.common.dateRenderer(event.get('dtstart')),
                    idx+1,
                    modified.length
                ));

                return Tine.Calendar.backend.promiseLoadRecord(event)
                    .then(_.bind(me.mergeEvent, me, event))
                    .then(_.bind(function(event) {
                        Ext.MessageBox.updateProgress((idx+1)/(modified.length));

                        return event.isRecurBase() || event.isRecurInstance() ?
                            Tine.Calendar.backend.promiseCreateRecurException(event, 0, 0, 0) :
                            Tine.Calendar.backend.promiseSaveRecord(event);
                    }, me))
                    .catch(function(error) {
                        errors.push(error);
                    })
                    .then(_.bind(function(updatedEvent) {
                        const existingEvent = me.eventStore.getById(event.id)
                        var idx = me.eventStore.indexOf(existingEvent);
                        me.eventStore.remove(existingEvent);
                        me.eventStore.insert(idx, [updatedEvent]);
                    }, me));

            });
        }, Promise.resolve())
            .then(_.bind(function() {
                Ext.MessageBox.hide();
            }, me))
            .then(function() {
                return Promise[errors.length ? 'reject' : 'resolve']();
            })
            .then(_.bind(me.showLoadMask, me))
            .then(_.bind(me.loadRuntimeData, me))
            .then(_.bind(me.hideLoadMask, me))
            .catch(_.bind(function(e) {

                Ext.Msg.show({
                    title: me.app.i18n._('Errors'),
                    msg:  me.app.i18n._('Some Events could not be saved. (see red triangles)'),
                    buttons: Ext.Msg.OK,
                    animEl: me.getEl(),
                    icon: Ext.MessageBox.ERROR
                });
            }, me));

    },

    // take all data from fetched Event and merge attendee
    mergeEvent: function(myEvent, fetchedEvent) {
        myEvent.beginEdit()
        _.each(fetchedEvent.data, function(value, field) {
            // @TODO: Calendar.getEvent does ot resolve event_types (at least for recur instances which are not yet saved (fakeid...))
            if (field !== 'attendee' && field !== 'event_types') {
                if (field == 'customfields') {
                    _.each(_.get(fetchedEvent.data, field, {}), function(value, cfName) {
                        myEvent.set('#' + cfName, value);
                    });
                } else {
                    myEvent.set(field, value);
                }
            }
        });

        // do we need to merge attendee?

        myEvent.endEdit()
        return myEvent;
    },

    ifDismissChanges: function() {
        var me = this;

        return new Promise(function(fulfill, reject) {
            if (me.eventStore.getModifiedRecords().length) {
                Ext.Msg.show({
                    title: me.app.i18n._('Dismiss Changes?'),
                    msg: me.app.i18n._('You have unsaved changes. Do you really want to continue?'),
                    buttons: Ext.Msg.YESNO,
                    fn: function(btn, text){
                        if (btn == 'yes'){
                            fulfill('dismiss changes');
                        } else {
                            reject('do not dismiss changes');
                        }
                    },
                    animEl: me.getEl(),
                    icon: Ext.MessageBox.QUESTION
                });
            } else {
                fulfill();
            }
        });
    },

    loadRuntimeData: function() {
        var _ = window.lodash,
            me = this;

        return me.loadCSGroups()
            .then(_.bind(me.loadEvents, me))
            .then(_.bind(me.loadCSMembers, me))
            .then(_.bind(me.loadPollReplies, me))
            .then(_.bind(me.computeCSMembersCapabilities, me))
            .then(_.bind(function() {
                me.membersGrid.getView().refresh();
            }, me))
            .then(_.bind(me.checkUnusedAttendee, me))
            .catch(_.bind(function(error) {
                me.loadMask.hide();
                Tine.Tinebase.ExceptionHandler.handleException(error);
            }, me));
    },

    // loadEventTypesByPosition: function() {
    //     var _ = window.lodash,
    //         me = this;
    //
    //     return Tine.ChurchEdition.celebrantConstraintsProvider.getEventTypesByPosition()
    //         .then(_.bind(function(eventTypesByPosition) {
    //             me.eventTypesByPosition = eventTypesByPosition;
    //         }, me));
    // },

    /**
     * load eventStore with all events:
     * - fitting in current period
     * @TODO - stateful filter for liturgical planning?
     * @TODO - have favorites here!
     * - which have a church_event type marked as liturgy
     * - having a relation to one of the sites / having event_site set to one of the sites
     * @TODO remove #site on EBHH #1690 cleanup
     *
     * @returns {Promise}
     */
    loadEvents: function() {
        var me = this,
            _ = window.lodash,
            csSiteCf = Tine.widgets.customfields.ConfigManager.getConfig('Calendar', 'Calendar_Model_Event', 'site'),
            csEventTypeCf = Tine.widgets.customfields.ConfigManager.getConfig('Calendar', 'Calendar_Model_Event', 'church_event_type'),
            sites = me.siteCombo.getValue() || [];

        me.eventStore.baseParams.filter = [
            {field: 'period', operator: 'within', value: this.periodPicker.getValue()},
            // @TODO find generic conceept e.g. filterToolbar and favorites
            {field: 'event_types', operator: 'definedBy?condition=and&setOperator=oneOf', value: [
                {field: 'event_type', operator: 'definedBy?condition=and&setOperator=one0f', value: [
                    {field: 'liturgie', operator: 'equals', value: 1}
            ]}]}
        ];

        if (sites.length) {
            if (! Tine.Tinebase.featureEnabled('featureSite')) {
                me.eventStore.baseParams.filter.push(
                    {field: 'customfield', operator: 'AND', value: {'cfId': csSiteCf.id, value: [
                                {field: "id", operator: "in", value: _.map(sites, 'id')}]}
                    });
            } else {
                me.eventStore.baseParams.filter.push({field: 'event_site', operator: 'definedBy?condition=and&setOperator=oneOf', value: [
                    { field: ':id', operator: 'in', value: sites }
                ]});
            }
        }

        return me.eventStore.promiseLoad()
            .then(() => {
                // hide events user has no readGrant (e.g. freebusy only)
                me.eventStore.each((r) => {
                    if (! r.get('readGrant')) {
                        me.eventStore.remove(r);
                    }
                })
            });
    },

    /**
     * load csRolesStore with all CrewScheduling roles
     *
     * @returns {Promise}
     */
    loadCSRoles: async function() {
        const colorMap = Object.keys(Tine.Calendar.ColorManager.prototype.colorSchemata);

        this.csRolesStore.loadData(await csRole.getRoles());
        this.csRolesStore.each((item, idx) => {
            const color = String(item.get('color') || '').replace(/^#/, '').toUpperCase();
            // @TODO!
            item.members = [];

            let schema = this.colorManager.colorSchemata[color || colorMap[idx]];
            schema = schema ? schema : this.colorManager.getCustomSchema(color || '969696');

            item.color = schema.color;
            item.colorRGB = item.data.colorRGB = Tine.Calendar.ColorManager.str2dec(schema.light);
        });
    },

    /**
     * load csGroupsStore with all related groups:
     * - all groups from all cs.role
     * - all groups from all calendar.eventType
     *
     * @returns {Promise}
     */
    loadCSGroups: async function(csRoles) {
        const lists = await csRole.getLists();

        this.csGroupsStore.loadData(lists);
        return this.csGroupsStore;
    },

    /**
     * load csMembersStore with all contacts which are member in one of the csGroups
     * @TODO add freetime search periods
     *
     * @returns {Promise}
     */
    loadCSMembers: function(csGroups) {
        var _ = window.lodash,
            me = this,
            members = _.union([].concat.apply([], _.map(me.csGroupsStore.data.items, 'data.members'))),
            sites = me.siteCombo.getValue() || [];

        me.csMembersStore.baseParams.filter = [
            {field: 'type', value: ['user']},
            {field: 'userFilter', value:
                {condition: 'AND', filters: [
                    {field: 'id', operator: 'in', value: members}
                ]}
            }
        ];

        if (sites.length && Tine.Tinebase.featureEnabled('featureSite')) {
            me.csMembersStore.baseParams.filter[1].value.filters.push({condition: 'OR', filters: [
                {field: 'sites', operator: 'equals', value: null},
                {field: 'sites', operator: 'definedBy?condition=and&setOperator=oneOf', value: [
                    {field: 'site', operator: 'definedBy?condition=and&setOperator=one0f', value: [
                        {field: ':id', operator: 'in', value: sites}
                    ]}
                ]}
            ]})
        }

        me.csMembersStore.on('load', function() {
            me.csMembersStore.sort('user_id', 'ASC');
        }, me, {single: true});

        // unbind store from view
        me.memberSelectionPanel.dataView.bindStore();
        return me.csMembersStore.promiseLoad();

    },

    loadPollReplies: async function() {
        const period = this.periodPicker.getValue();

        this.csPollsStore.baseParams.filter = [
            { field: 'from', operator: 'before', value: period.until },
            { field: 'until', operator: 'after', value: period.from }
        ];

        return this.csPollsStore.promiseLoad();
    },

    onRecordChanges: function (store, event, operation) {
        // recompute capabilities of all members regarding event
        async.forEach(this.csMembersStore.snapshot?.items || this.csMembersStore.data.items, async (attendee) => {
            const removed = _.remove(attendee.data.user_id.possibleUsages, (key) => { return key.startsWith(event.id) });
            attendee.data.user_id.possibleUsages = attendee.data.user_id.possibleUsages.concat(await this.getPossibleUsages(attendee, event));
        });
    },

    computeCSMembersCapabilities: function() {
        return new Promise(async (fulfill, reject) => {
            try {
                const memberCounts = this.membersGrid.getMemberCount();

                // get contacts for required roles
                const capableContactsMaps = await async.reduce(await csRole.getRoles(), {}, async (accu, role) => {
                    return _.set(accu, role.id, await csRole.getEventTypeCapableContactsMap(role));
                })


                // contactid.eventid.roleid.reply
                const pollReplyMap = {}
                _.forEach(this.csPollsStore.data.items, poll => {
                    _.forEach(poll.data.participants, participant => {
                        _.forEach(participant.poll_replies, poll_reply => {
                            // console.error(participant.contact_id.n_fileas, poll_reply.event_ref, poll.data.scheduling_role.name, poll_reply.status)
                            _.set(pollReplyMap, `${participant.contact_id.id}.${poll_reply.event_ref}.${poll.data.scheduling_role.id}`, Object.assign({ poll, participant }, poll_reply))
                        })
                    })
                })


                // map: member -> csRoles, days, favorites
                await async.forEach(this.csMembersStore.data.items, async (attendee) => {
                    const member = attendee.get('user_id');

                    member.count = _.get(memberCounts, 'user/' + member.id, 0);

                    /** IDEA
                    const capabilities = new AttendeeCapability(member);
                    member.possibleUsages = async.reduce(await capabilities.getRoles(), [], (accu, role) => {
                        return _.concat(accu, this.attendeeValidation.validate(event, attendee, role) ? `${event.id}:${role.id}`: []);
                    });
                    */

                    member.roles = _.reduce(this.csRolesStore.data.items, (accu, role) => {
                        // NOTE: member is capable if he's capable for one of the types
                        _.forEach(capableContactsMaps[role.id], (capableMembers, typeId) => {
                            if (capableMembers.indexOf(member.id) >= 0) {
                                accu.push(role);
                                return false;
                            }
                        })
                        return accu
                    }, []);

                    member.days = _.compact(_.get(member, 'customfields.favorite_day', '').split(','));
                    member.partners = _.compact(_.get(member, 'customfields.favorite_partner'), []);

                    var dom = document.createElement('div');
                    dom.innerHTML = attendee.get('fbInfo');
                    member.busyIds = _.compact(_.reduce(Ext.fly(dom).query('.cal-fbinfo-state'), function(result, el) {
                        var eventId = Ext.fly(el).getAttributeNS('tine', 'calendar-event-id'),
                            stateId =  Ext.fly(el).getAttributeNS('tine', 'calendar-freebusy-state-id');

                            return result.concat(stateId > 1 ? eventId : null);
                    }, []));
                    member.siteIds = _.map(_.get(member, 'sites', []), 'site.id')

                    member.pollReplies = pollReplyMap[member.id] || {};

                    // finally compute possible member cells (eventId:roleId)
                    member.possibleUsages = await async.reduce(this.eventStore.data.items, [], async (possibleUsages, event) => {
                        return possibleUsages.concat(await this.getPossibleUsages(attendee, event));
                    });
                });

                // bind store
                this.memberSelectionPanel.dataView.bindStore(this.csMembersStore);
                fulfill(this.csMembersStore.data.items);
                this.memberSelectionPanel.dataView.refresh()
            } catch(e) {
                reject(e);
            }
        });
    },

    /**
     * get possible usages (<eventId>:<roleId>) for given member and event
     *
     * NOTE: depends on member props from computeCSMembersCapabilities
     *
     * @param member
     * @param event
     * @returns {Promise<unknown[]|*[]>}
     */
    getPossibleUsages: async function(attendee, event) {
        var member = attendee.get('user_id'),
            busyEventIds = member.busyIds,
            wkdays = Tine.CrewScheduling.MemberToken.prototype.wkdays,
            days = member.days,
            possibleDay = !days.length || _.indexOf(days, wkdays[event.get('dtstart').format('w')]) >= 0,
            free = _.indexOf(busyEventIds, event.get('id')) < 0,
            siteMatch = !Tine.Tinebase.featureEnabled('featureSite') || _.get(await this.attendeeValidation.validateSite(attendee, event), 'isValid');

        return siteMatch ? _.compact(await async.map(member.roles, async (role) => {
            // NOTE: we come here if attendee is capable for at least role/type combination
            //       this computation is necessary for memberTokens anyway so we can use it to reduce amount of validations

            const pollStatus = _.get(member, `pollReplies.${event.id}.${role.id}.status`)
            let typeValidation = (possibleDay && free && pollStatus !== 'DECLINED') || ['ACCEPT', 'TENTATIVE'].indexOf(pollStatus) >= 0

            if (typeValidation) {
                const eventRoleConfigs = await eventRoleConfig.getFromEvent(event, role);
                if (!eventRoleConfigs.length) {
                    eventRoleConfigs.push(await eRC.createFromRoleTypes({role, event_types: []}));
                }

                // validate if the attendee is capable for one of the configured eventRoleConfigs
                typeValidation = await async.reduce(eventRoleConfigs, false, async (accu, eventRoleConfig) => {
                    const validationResult = await this.attendeeValidation.validateEventRoleConfigCapability(attendee, event, eventRoleConfig)
                    return accu || _.get(validationResult, 'isValid');
                });
            }

            return typeValidation ? event.get('id') + ':' + role.get('id') : null;
        })) : [];
    },

    getMemberTokenStyles: function(member) {
        const selectedCells = _.map(_.filter(this.membersGrid.getSelectionModel().getSelectedCell() ?
            this.membersGrid.getEl().query('.x-grid3-cell-selected .cs-members-cell') :
            this.membersGrid.getEl().query('.cs-members-cell'), (el) => { return el.parentElement.parentElement.style.display !== 'none' }), 'id')
        const buckets = ['IMPOSSIBLE', 'DECLINED', 'TENTATIVE', 'ACCEPTED', 'NEEDS-ACTION']
        const counts = _.reduce(buckets, (accu, bucket) => { return _.set(accu, bucket, 0)}, {})
        let total = 0;
        _.forEach(selectedCells, cellId => {
            const [eventId, roleId] = cellId.split(':');
            if (_.find(member.roles, { id: roleId})) {
                const status = _.isArray(member.possibleUsages) && _.indexOf(member.possibleUsages, cellId) < 0 ? 'IMPOSSIBLE' :
                    _.get(member, `pollReplies.${PollReply.getEventRef(this.eventStore.getById(eventId))}.${roleId}.status`, 'NEEDS-ACTION')

                counts[status] = (counts[status] || 0) + 1
                total = ++total
            }
        })

        let pos = 0
        let background = 'background-image: linear-gradient(45deg'
        _.forEach(buckets, (bucket, idx) => {
            const pct = Math.round(counts[bucket]/total*100)
            background += `, var(--cs-poll-${bucket.toLowerCase()}) ${pos}% ${pos = pos + pct}%`
        })
        background += ');'

        return `${background}`;
    },
    /**
     * chek if events have exiting attendee w.o, crewscheduling_roles and suggest to assign if possible
     */
    checkUnusedAttendee: async function() {
        const options = await async.reduce(this.eventStore.data.items, [], async (options, event) => {
            let eRCs = await eRC.getFromEvent(event)

            await async.forEach(event.get('attendee'), async (attendeeData) => {
                // NOTE: we need to work with the members from memberStore to have the computed stuff
                const compoundId = `${attendeeData.user_type}-${attendeeData.user_id.id}`
                const attendee = this.csMembersStore.getById(compoundId)
                if (! attendee) return;

                let num = 1
                if (!attendeeData.crewscheduling_roles?.length) {
                    await async.forEach(eRCs, async eventRoleConfig => {
                        const baseValidation = await this.attendeeValidation.validateBasics(attendee, event, eventRoleConfig)
                        if (baseValidation.isValid && (await this.attendeeValidation.validateEventRoleConfigCapability(attendee, event, eventRoleConfig)).isValid) {
                            options.push({
                                eventId: event.id, compoundId, eventRoleConfig,
                                name: Tine.Tinebase.data.Record.generateUID(),
                                text: Tine.Tinebase.common.dateTimeRenderer(event.get('dtstart')) + ' ' + await event.getTitle().asString() + ' ⮕ ' +
                                    await Tine.Calendar.AttendeeGridPanel.prototype.renderAttenderName.call(Tine.Calendar.AttendeeGridPanel.prototype, attendee.get('user_id'), {noIcon: true}, attendee).asString() + ' ' +
                                    `${eventRoleConfig.role.name} (${_.map(eventRoleConfig.event_types, 'name').join(', ')  || this.app.i18n._('Without Event Type')})`,
                                checked: num++ === 1
                            })
                        }
                    })
                }
            })
            return options
        });

        if (options.length) {
            try {
                let membersToAssign = await Tine.widgets.dialog.MultiOptionsDialog.getOption({
                    title: this.app.formatMessage('Assign Existing Attendee?'),
                    questionText: this.app.formatMessage('The following persons are attendee in the respective events, but are not yet assigned to services. Please select the services for which these persons are to be assigned.'),
                    allowMultiple: true,
                    allowEmpty: true,
                    allowCancel: true,
                    height: options.length * 30 + 100,
                    options: options
                })
                async.forEach(membersToAssign, async (memberOption) => {
                    await async.timeout(Ext.emptyFn, 10)(null, () => { // NOTE: we need to throttle here to avoid ui glitches
                        this.membersGrid.addMembersToCell(`${memberOption.eventId}:${memberOption.eventRoleConfig.role.id}`, [this.csMembersStore.getById(memberOption.compoundId)], memberOption.eventRoleConfig)
                    });
                })

            } catch (e) {/* USERABORT */}
        }
    },

    showLoadMask: function() {
        if (! this.loadMask) {
            this.loadMask = new Ext.LoadMask(this.getEl(), {msg: this.app.i18n._("Loading Crew Scheduling data...")});
        }
        this.loadMask.show.defer(100, this.loadMask);
        return Promise.resolve();
    },

    hideLoadMask: function() {
        this.loadMask.hide.defer(100, this.loadMask);
        return Promise.resolve();
    },

    /**
     * returns canonical path part
     * @returns {string}
     */
    getCanonicalPathSegment: function () {
        return ['',
            this.app.appName,
            'MainScreen',
        ].join(Tine.Tinebase.CanonicalPath.separator);
    },

});

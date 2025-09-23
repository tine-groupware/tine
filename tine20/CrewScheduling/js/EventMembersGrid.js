/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
import {find} from "lodash";

Ext.ns('Tine.CrewScheduling');

import { getFromEvent, getEventRoleAttendee } from './Model/eventRoleConfig'
import AttendeeValidation from './Model/AttendeeValidation';
import 'ux/grid/ColumnHeaderGroup.js'
import * as async from 'async';
import * as markdown from 'util/markdown'
require('../css/eventMembersGrid.css');
require('./MemberToken');
require('./MemberSelectionDialog');
import { HTMLProxy, Expression } from "twingEnv.es6";
import PollReply from "./Model/PollReply";


/**
 * @namespace   Tine.CrewScheduling
 * @class       Tine.CrewScheduling.EventMembersGrid
 * @extends     Ext.grid.GridPanel
 *
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.CrewScheduling.EventMembersGrid = Ext.extend(Ext.grid.GridPanel, {
    /**
     * @property {Tine.Tinebase.Application} app
     */
    app: null,
    /**
     * @cfg {Tine.Tinebase.data.RecordStore} csRolesStore
     */
    csRolesStore: null,
    /**
     * @cfg {Tine.Tinebase.data.RecordStore} csMembersStore
     */
    csMembersStore: null,
    /**
     * @cfg {String|Function} group
     */
    group: null,
    /**
     * @property {Tine.CrewScheduling.MemberToken} memberToken
     */
    memberToken: null,
    /**
     * @property {Array} selectedTokens
     */
    selectedTokens: null,
    /**
     * @property {Function} debouncedUpdateMemberCounts
     */
    debouncedUpdateMemberCounts: null,
    /**
     * @property {String[]} rolesVisible (stateful)
     */
    rolesVisible: null,
    /**
     * @property {AttendeeValidation}
     */
    attendeeValidation: null,

    /** private **/
    layout:'fit',
    border: false,
    stateful: true,
    stateId: 'CrewScheduling-EventMembersGrid',
    cls: 'cs-event-member-grid',
    enableColumnMove: false,

    canonicalName: 'EventMembersGrid',
    disableResponsiveLayout: true,

    initComponent: function() {
        var _ = window.lodash,
            me = this;

        this.app = Tine.Tinebase.appMgr.get('CrewScheduling');
        this.csRolesStore.on('load', this.configureColumns, this, { buffer: 100 });
        this.rolesVisible = [];

        const onGroupChange = _.debounce(_.bind(this.configureColumns, this), 100);
        this.groupingCollection = new Tine.Tinebase.data.GroupedStoreCollection({
            store: this.store,
            group: this.group,
            listeners: {
                add: onGroupChange,
                remove: onGroupChange
            }
        });

        this.plugins = this.plugins || [];
        this.plugins.push(new (Ext.extend(Ext.ux.grid.GridViewMenuPlugin, {
            _beforeColMenuShow: _.bind(this.beforeColMenuShow, this),
            _handleHdMenuClick: _.bind(this.handleHdMenuClick, this)
        }))({}));

        this.plugins.push(this.groupsHeaderPlugin = new Ext.ux.grid.ColumnHeaderGroup({
            rows: []
        }));

        this.eventsColumn = {
            header: this.app.i18n._('Event'),
            width: 219,
            sortable: false,
            fixed: true,
            resizable: false,
            menuDisabled: true,
            // dataIndex: 'dtstart',
            renderer: function() {
                // NOTE: dynamic call - maybe someone want's to wrap the renderer
                return me.renderEvent.apply(me, arguments);
            }
        };
        this.columns =  [this.eventsColumn];

        this.memberToken = new Tine.CrewScheduling.MemberToken();
        this.selectedTokens = [];

        this.viewConfig = {
            emptyText: this.app.i18n._('No events to show yet...'),
            forceFit: true,
            getRowClass: this.getRowClass
        };

        this.sm = new Ext.ux.grid.MultiCellSelectionModel({});
        this.sm.handleMouseDown = this.sm.handleMouseDown.createInterceptor(this.handleViewMouseDown, this);

        this.on('headerclick', this.onHeaderClick, this);
        this.on('celldblclick', this.onCellDblClick, this);

        this.debouncedUpdateMemberCounts = _.debounce(_.bind(me.updateMemberCounts, me), 100);

        this.initTemplates();

        this.attendeeValidation = new AttendeeValidation({ formatMessage: this.app.formatMessage.bind(this.app) });

        Tine.CrewScheduling.EventMembersGrid.superclass.initComponent.call(this);
    },

    afterRender: function() {
        Tine.CrewScheduling.EventMembersGrid.superclass.afterRender.apply(this, arguments);

        this.on('keydown', this.onKeyDown, this);

        var _ = window.lodash,
            me = this;

        this.dropZone = new Ext.dd.DropZone(this.getView().scroller.dom, {
            ddgroup: 'cs-members',
            getTargetFromEvent: _.bind(me.getTargetFromEvent, me),
            onNodeEnter: _.bind(me.onNodeEnter, me),
            onNodeOut: _.bind(me.onNodeOut, me),
            onNodeOver: _.bind(me.onNodeOver, me),
            onNodeDrop: _.bind(me.onNodeDrop, me)
        });
    },

    /**
     * set grouping
     *
     * @param {Stirng|Function} group
     * @param {Array} fixedGroups
     */
    setGrouping: function(group, fixedGroups) {
        this.group = this.groupingCollection.group = group;
        this.groupingCollection.setFixedGroups(fixedGroups); // auto applies
    },

    addMembersToCell: function(cellId, members, crewscheduling_roles) {
        var _ = window.lodash,
            me = this,
            ids = cellId.split(':'),
            roleId = ids.pop(),
            eventId = ids.join(':'),
            event = me.store.getById(eventId),
            asHelper = Tine.Calendar.Model.Attender.getAttendeeStore,
            attendeeData = event.get('attendee'),
            attendeeStore = asHelper(JSON.stringify(attendeeData)),
            role = me.csRolesStore.getById(roleId);

        Tine.Tinebase.common.assertComparable(attendeeData);

        _.each(members, function(member) {

            // if member already attends to event -> take her
            var existing = asHelper.getAttenderRecord(attendeeStore, member),
                attendee = existing || member.copy();

            // set attendee role
            attendee.set('id', '');
            attendee.set('cal_event_id', event.id);
            attendee.set('crewscheduling_roles', Tine.Tinebase.common.assertComparable((attendee.get('crewscheduling_roles') || []).concat(crewscheduling_roles)));

            if (! existing) {
                member.count++;

                // remove 'virtual' cols
                attendee.data.user_id = _.omit(attendee.data.user_id, ['days', 'partners', 'count', 'groups', 'roles']);
                attendee.data.user_id.roles = attendee.data.crewscheduling_roles;

                attendeeStore.add(attendee);
            }
        });

        asHelper.getData(attendeeStore, event);
        me.updateMemberCounts(me.getMemberCount());
        return true;
    },

    getMemberCount: function() {
        var _ = window.lodash,
            d = Tine.Calendar.Model.Attender.getAttendeeStore.signatureDelimiter,
            re = new RegExp(['.+','(.+','.+)','.*'].join(d));

        return _.countBy(_.map(Ext.fly(this.getEl()).query('.cs-member-token'), function(token) {
            return Ext.fly(token).getAttribute('tine-cs-token-id').match(re)[1];
        }));
    },

    updateMemberCounts: function(memberCounts) {
        var _ = window.lodash,
            me = this,
            el = this.getEl();

        memberCounts = memberCounts || this.getMemberCount();

        _.each(memberCounts, function(count, key) {
            Ext.fly(el).select('table[tine-cs-token-id*=' + key + '] td.cs-count').update(count);

            _.each(me.store.data.items, function(event) {
                var attendee = _.get(event, 'data.attendee', []),
                    userId = key.replace('user' + Tine.Calendar.Model.Attender.getAttendeeStore.signatureDelimiter, ''),
                    current = _.find(attendee, function(attendee) {
                        return _.get(attendee, 'user_id.id') == userId;
                    });

                if (current) {
                    _.set(current, 'user_id.count', count);
                }
            });
        });

        this.fireEvent('updateMemberCount', this, memberCounts);
    },

    markDirty: async function(event, role, dirty) {
        Ext.fly(await this.getCell(event, role))[dirty ? 'addClass' : 'removeClass']('x-grid3-dirty-cell');
    },

    getRow: function(event) {
        return this.getView().getRow(this.store.indexOf(event));
    },

    getCell: async function(event, role) {
        const groupName = _.get(await this.groupingCollection.getGroupNames(event), '[0]', '').replaceAll( ' ', '¿');
        return this.getView().getCell(this.store.indexOf(event), _.findIndex(this.getColumnModel().config, {dataIndex: `${groupName}:${role.data.key}`}));
    },

    getTargetFromEvent: function(e) {
        return e.getTarget('.cs-members');
    },

    onDragStart: function(selectionPanel, data) {
        var _ = window.lodash,
            me = this,
            possibleUsagesSum = _.union(_.concat.apply(_, _.map(data.selected, 'data.user_id.possibleUsages', [])));

        // mask invalid cells
        _.each(me.getEl().query('.cs-members-cell'), function (el) {
            if (_.indexOf(possibleUsagesSum, el.id) < 0) {
                Ext.fly(el.parentElement.parentElement).addClass('cs-member-cell-disabled');
                Ext.fly(el.parentElement.parentElement).setOpacity(0.2);
            }
            const [eventId, roleId] = el.id.split(':');
            const statusSum = PollReply.getCombinedStatus.apply({}, _.compact(_.map(data.selected, attendee => {return _.get(attendee, `data.user_id.pollReplies.${PollReply.getEventRef(me.store.getById(eventId))}.${roleId}.status`)})))
            if (statusSum !== 'NEEDS-ACTION') {
                Ext.fly(el.firstChild).setStyle('background-color', `var(--cs-poll-${statusSum.toLowerCase()})`);
                Ext.fly(el.firstChild).addClass(`${statusSum}-black`);
            }
        });
    },

    onNodeEnter: function(target, dd, e, data) {
        const targetCellId = e.getTarget('.cs-members-cell').id;
        const memberCellIds = _.map(Ext.fly(e.getTarget('.x-grid3-row')).select('.cs-members-cell').elements, 'id');

        Ext.fly(target).addClass('cs-member-cell-highlight');

        if (!e.getTarget('.cs-event-readonly')) {
            _.each(data.selected, (attendee) => {
                const signatureId = Tine.Calendar.Model.Attender.getAttendeeStore.getSignature(attendee);
                const possibleTargetCells = _.intersection(memberCellIds, _.get(attendee, 'data.user_id.possibleUsages', ''));

                if (!possibleTargetCells.length || (possibleTargetCells.indexOf(targetCellId) < 0 && data.eventMemberTokenIds.length && possibleTargetCells.length !== 1)) {
                    Ext.fly(dd.dragElId).select('table.cs-member-token[tine-cs-token-id=' + signatureId + ']').setOpacity(0.3, true);
                }
            });
        }
    },

    onNodeOver: function(target, dd, e, data) {
        return e.getTarget('.cs-member-cell-disabled') || e.getTarget('.cs-event-readonly') ?
            Ext.dd.DropZone.prototype.dropNotAllowed :
            Ext.dd.DropZone.prototype.dropAllowed;
    },

    onNodeOut: function(target, dd, e, data) {
        Ext.fly(dd.dragElId).select('table.cs-member-token').setOpacity(1, true);
        Ext.fly(target).removeClass('cs-member-cell-highlight');
    },

    onNodeDrop: async function(target, dd, e, data) {
        const targetCellId = e.getTarget('.cs-members-cell').id;
        const memberCellIds = _.map(Ext.fly(e.getTarget('.x-grid3-row')).select('.cs-members-cell').elements, 'id');
        const ids = targetCellId.split(':');
        const roleId = ids.pop();
        const role = this.csRolesStore.getById(roleId);
        const eventId = ids.join(':');
        const event = this.store.getById(eventId);
        const asHelper = Tine.Calendar.Model.Attender.getAttendeeStore;
        const attendeeData = event.get('attendee')
        const attendeeStore = asHelper(JSON.stringify(attendeeData));

        if (e.getTarget('.cs-member-cell-disabled')) {
            const validations = await async.map(data.selected, async member => {
                return Object.assign( await this.mainScreen.attendeeValidation.validate(member, event, role), { member });
            });
            Ext.Msg.show({
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.ERROR_MILD,
                title: this.app.i18n._('The following persons cannot be added to the selected service'),
                closeable: false,
                msg: await markdown.parse((await async.map(validations, async (validation) => {
                    return `##### ${await validation.member.getTitle().asString()}\n` +
                        (await async.map(validation.messages, async message => {
                            return `* ${ message }`
                        })).join('\n')

                })).join('\n\n'))
            });

            return false;
        }
        if(e.getTarget('.cs-event-readonly')) return false;

        return await async.reduce(data.selected, true, async (ret, member) => {

            let removedAttendees;
            try {
                dd.proxy.el.setZIndex(100);

                let existing;
                if (data.eventMemberTokenIds.length) {
                    // attendee from other eventMemberCell
                    existing = member;
                    removedAttendees = this.removeMembers(data.eventMemberTokenIds); // NOTE: all existing are removed in first iteration!
                } else {
                    // member from memberSelectionPanel
                    existing = asHelper.getAttenderRecord(attendeeStore, member);
                }

                const attendee = existing || member.copy();
                const attendeeName = await attendee.getTitle().asString();
                const roleConfigs = await this.attendeeValidation.getPossibleEventRoleConfigs(attendee, event, role);

                const roleConfig = roleConfigs.length > 1 ? _.find(roleConfigs, {id: await Tine.widgets.dialog.MultiOptionsDialog.getOption({
                    title: this.app.formatMessage('Select Service for { attendeeName }', { attendeeName }),
                    questionText: this.app.formatMessage('There are different services to perform. Please select which service is to be performed by { attendeeName }.', { attendeeName }),
                    allowMultiple: false,
                    allowEmpty: false,
                    allowCancel: true,
                    height: roleConfigs.length * 30 + 100,
                    options: await async.map(roleConfigs, async roleConfig => { return { name: roleConfig.id,
                        text: '<span>' + _.map(roleConfig.event_types, 'description').join(', ') + ` <span class="cs-count">${(await getEventRoleAttendee(roleConfig, event)).length}</span></span>`
                    } })
                })}) : (roleConfigs[0] || []);

                return ret && this.addMembersToCell(targetCellId, [attendee], {role, event_types: roleConfig.event_types});
            } catch (e) {
                /* USERABORT */
                if (removedAttendees) {
                    // add them back
                    _.each(removedAttendees, removedAttendee => {
                        const event = this.store.getById(removedAttendee.get('cal_event_id'));
                        const asHelper = Tine.Calendar.Model.Attender.getAttendeeStore;
                        const attendeeData = event.get('attendee');
                        const attendeeStore = asHelper(JSON.stringify(attendeeData));
                        attendeeStore.add(removedAttendee);
                        asHelper.getData(attendeeStore, event);
                    })
                    this.updateMemberCounts(this.getMemberCount());
                }
                return false;
            } finally {
                dd.proxy.el.setZIndex(10000);
            }
        });
    },

    onDragEnd: function(selectionPanel, data) {
        var _ = window.lodash,
            me = this;

        _.each(me.getEl().query('.cs-members-cell'), function (el) {
            Ext.fly(el.parentElement.parentElement).removeClass('cs-member-cell-disabled');
            Ext.fly(el.parentElement.parentElement).setOpacity(1);
            Ext.fly(el.firstChild).setStyle('background-color', 'transparent');
            Ext.fly(el.firstChild).removeClass(['IMPOSSIBLE-black', 'NEEDS_ACTION-black', 'TENTATIVE-black', 'ACCEPTED-black', 'DECLINED-black']);
        });
    },

    onClick: function(e) {
        Tine.CrewScheduling.EventMembersGrid.superclass.onClick.apply(this, arguments);

        this.view.focusEl.focus();
        
        if (Ext.isTouchDevice) {
            var t = e.getTarget('.x-grid3-cell'),
                v = this.view,
                row = v.findRowIndex(t),
                cell = v.findCellIndex(t);

            if (cell) {
                this.getSelectionModel().selectCell([row, cell]);
                v.focusCell(row, cell);
                Tine.CrewScheduling.MemberSelectionDialog.openWindow({});
            }
           return;
        }

        var _ = window.lodash,
            el = this.getEl(),
            target = e.getTarget('.cs-member-token'),
            signatureId = target ? Ext.fly(target).getAttribute('tine-cs-token-id') : '',
            isSelected = _.indexOf(this.selectedTokens, signatureId) >= 0,
            isMulitSelect = (e.button === 0 && (e.shiftKey || e.ctrlKey)) || Ext.isTouchDevice;

        // handle token selection
        if (target) {
            e.stopEvent();

            if (!isMulitSelect) {
                _.each(this.selectedTokens, function(signatureId) {
                    el.select('table[tine-cs-token-id*=' + signatureId + ']').removeClass('x-view-selected');
                });
                this.selectedTokens = [];
            }

            if (isMulitSelect && isSelected) {
                _.pull(this.selectedTokens, signatureId);
                el.select('table[tine-cs-token-id*=' + signatureId + ']').removeClass('x-view-selected');
            } else {
                this.selectedTokens.push(signatureId);
                Ext.fly(target).addClass('x-view-selected');
            }
        }

    },

    onContextMenu: function(e) {
        Tine.CrewScheduling.EventMembersGrid.superclass.onContextMenu.apply(this, arguments);

        var _ = window.lodash,
            el = this.getEl(),
            target = e.getTarget('.cs-member-token'),
            signatureId = target ? Ext.fly(target).getAttribute('tine-cs-token-id') : '',
            isSelected = _.indexOf(this.selectedTokens, signatureId) >= 0;

        if (target) {
            e.stopEvent();

            if (! isSelected) {
                this.selectedTokens.push(signatureId);
                Ext.fly(target).addClass('x-view-selected');
            }

            var menu = new Ext.menu.Menu({
                items: [{
                    text: this.app.i18n._('Remove Attendee'),
                    iconCls: 'action_delete',
                    scope: this,
                    handler: function() {this.removeMembers();}
                }],
                plugins: [{
                    ptype: 'ux.itemregistry',
                    key:   'Tinebase-MainContextMenu'
                }]
            });
            menu.showAt(e.getXY());
        }
    },

    handleViewMouseDown : function(g, rowIndex, columnIndex, e){
        // prevent scrollTop
        this.getView().focusCell(rowIndex, columnIndex);

        // handle spinner
        if (e.getTarget('.cs-role-number')) {
            e.stopEvent();

            var event = this.store.getAt(rowIndex),
                role = this.csRolesStore.getAt(columnIndex -1),
                numberField = '#minCount_' + role.get('key'),
                currentNumber = +event.get(numberField) || 0;

            if (e.getTarget('.rs-role-number-readonly') || e.getTarget('.cs-event-readonly')) {
                return;
            }

            if (e.getTarget('.cs-role-number-up')) {
                event.set(numberField, ++currentNumber);
            }
            if (e.getTarget('.cs-role-number-down') && currentNumber > 0) {
                event.set(numberField, --currentNumber);
            }

            return false;
        }

        // select row
        else if (! columnIndex) {
            var sm = this.getSelectionModel(),
                index = [rowIndex, columnIndex],
                isSelected = _.reduce(_.tail(this.getColumnModel().config), function(s,c,i) {return s && sm.isSelected([rowIndex, i+1])}, true),
                isMultiSelect = e.ctrlKey || e.shiftKey;

            // NOTE: we need to original focus to prevent screll out of view
            _.defer(() => {
                if (isSelected && isMultiSelect) {
                    sm.deselectRange ([rowIndex, 1], [rowIndex, this.getColumnModel().config.length]);
                } else {
                    sm.selectRange([rowIndex, 1], [rowIndex, this.getColumnModel().config.length], isMultiSelect);
                }
            });
            return false;
        }

        return true;
    },

    onCellDblClick: function(grid, row, col, e) {
        if(!col) {
            const event = this.store.getAt(row);
            let loadedEvent
            Tine.Calendar.EventEditDialog.openWindow({
                contentPanelConstructorInterceptor: async (config) => {
                    // work on fresh event to prevent concurrency conflicts
                    loadedEvent = await Tine.Calendar.backend.promiseLoadRecord(config.recordId);
                    const clientEvent = Tine.Calendar.backend.recordReader({responseText: config.record});
                    // show edited attendee in dialog
                    Tine.CrewScheduling.MainScreen.prototype.mergeEvent.call(this, clientEvent, loadedEvent);
                    config.record = clientEvent;
                },
                record: Ext.encode(event.data),
                recordId: event.data.id,
                listeners: {
                    scope: this,
                    update: async function (eventJson) {
                        await Ext.MessageBox.show({
                            icon: Ext.MessageBox.INFO_WAIT,
                            title: this.app.i18n._('Please wait'),
                            msg: this.app.i18n._('Saving event:'),
                            width:500,
                            progress:true,
                            closable:false,
                            animEl: this.getEl()
                        });
                        
                        const rowEl = this.getView().getRow(row);

                        Ext.fly(rowEl).select('.cs-members-cell').elements.map
                        const eventToUpdate = Tine.Calendar.backend.recordReader({responseText: eventJson});

                        Ext.MessageBox.updateProgress(0.3);
                        const updatedEvent = eventToUpdate.isRecurBase() || eventToUpdate.isRecurInstance() ?
                            await Tine.Calendar.backend.promiseCreateRecurException(eventToUpdate, 0, 0, 0) :
                            await Tine.Calendar.backend.promiseSaveRecord(eventToUpdate);

                        this.store.replaceRecord(event, updatedEvent);
                        Ext.MessageBox.updateProgress(1);
                        async.timeout(Ext.emptyFn, 1000)(null, () => { Ext.MessageBox.hide() });
                    }
                }
            });
        }
    },

    // select column
    onHeaderClick: function(grid, colIndex, e) {
        var _ = window.lodash,
            me = this,
            sm = this.getSelectionModel(),
            isMultiSelect = e.ctrlKey || e.shiftKey,
            isSelected = _.reduce(this.store.data.items, function(s,r,i) {return s && sm.isSelected([i, colIndex])}, true),
            cells = _.map(this.store.data.items, function(r,i) {return [i, colIndex]});

        if (isSelected && isMultiSelect) {
            _.each(cells, function(cell) {
                sm.deselectCell(cell);
            });
        } else {
            sm.selectCells(cells, isMultiSelect);
        }
    },

    onKeyDown: function(e){
        if ([e.BACKSPACE, e.DELETE].indexOf(e.getKey()) !== -1) {
            this.removeMembers();
        }
    },

    /**
     *
     * @param {Collection} members
     */
    removeMembers: function(members) {
        var _ = window.lodash,
            me = this,
            asHelper = Tine.Calendar.Model.Attender.getAttendeeStore,
            signatureIds = _.map([].concat(members || this.selectedTokens), function(member) {
                return _.isString(member) ? member :
                    Tine.Calendar.Model.Attender.getAttendeeStore.getSignature(member);
            }),
            memberAttendees = _.map(signatureIds, asHelper.fromSignature),
            removedAttendee = [];

        _.each(memberAttendees, function(memberAttendee, key) {
            var event = me.store.getById(memberAttendee.get('cal_event_id')),
                eventAttendees = asHelper(event.get('attendee')),
                eventAttendee = asHelper.getAttenderRecord(eventAttendees, memberAttendee);

            _.pull(me.selectedTokens, signatureIds[key]);

            eventAttendees.remove(eventAttendee);
            removedAttendee.push(eventAttendee);
            asHelper.getData(eventAttendees, event);
        });

        me.updateMemberCounts(me.getMemberCount());
        return removedAttendee;
    },

    /**
     * returns view row class
     */
    getRowClass: function(record, index, rowParams, store) {
        var className = 'cs-event-row';

        if (!record.data.editGrant) {
            className += '  cs-event-readonly';
        }

        return className;
    },

    renderEvent: function(value, metadata, record, rowIndex, colIndex, store) {
        metadata.css = 'cs-members-event-wrap';

        if (record.dirty) {
            metadata.css += '  x-grid3-dirty-cell';
        }
        if(_.get(record, 'data.status') === 'CANCELLED') {
            metadata.css += ' cs-event-CANCELLED';
        }

        return this.templates.event.apply(record.data);
    },

    /**
     * returns attendee of given role
     * 
     * @param {Calendar.Attendee[]} attendee
     * @param {CrewScheduling.SchedulingRole} role
     * @returns {Calendar.Attendee[]}
     */
    filterAttendeeByRole: function(attendee, role) {
        return _.filter(attendee, attendee => { return _.filter(attendee.crewscheduling_roles, { role: { id: role.id } }).length })
    },

    // align height to max number of attendee
    renderMembersDropZone: function(role, value, metadata, record, rowIndex, colIndex, store) {
        metadata.css = 'cs-members-cell-wrap';
        return new HTMLProxy(new Promise(async (resolve) => {
            var _ = window.lodash,
                me = this,
                groupName = String(metadata.id.split(':')[0]).replaceAll('¿', ' '),
                matchesGroup = (await this.groupingCollection.getGroupNames(record)).indexOf(groupName) >= 0,
                attendees = record.get('attendee'),

                eventRoleConfigs = await getFromEvent(record, role),
                readonlyCount = true, // maybe later... eventRoleConfigs.length !== 1,
                minCount = _.reduce(eventRoleConfigs, (count, eventRoleConfig) => {
                    return count + eventRoleConfig.num_required_role_attendee;
                }, 0),

                csMembersStore = me.csMembersStore,
                asHelper = Tine.Calendar.Model.Attender.getAttendeeStore,
                // roleAttendees = _.map(_.filter(attendees, attendee => { return _.filter(attendee.crewscheduling_roles, { role: { id: role.id } }).length }), function (roleAttendee) {
                roleAttendees = _.map(this.filterAttendeeByRole(attendees, role), function (roleAttendee) {
                    var existing = asHelper.getAttenderRecord(csMembersStore, roleAttendee);

                    if (existing) {
                        const orig = roleAttendee;
                        roleAttendee = existing.copy();
                        roleAttendee.data.cal_event_id = record.id;
                        roleAttendee.data.user_id = JSON.parse(JSON.stringify(roleAttendee.data.user_id));
                        roleAttendee.data.crewscheduling_roles = [...orig.crewscheduling_roles];
                        roleAttendee.data.user_id.crewscheduling_roles = roleAttendee.data.crewscheduling_roles
                    }

                    return roleAttendee;
                });

            if (!matchesGroup) {
                return '';
            }

            if (record.isModified('attendee')) {
                if (JSON.stringify(me.getMembersSignature(roleAttendees)) !== JSON.stringify(me.getMembersSignature(this.filterAttendeeByRole(record.modified.attendee, role)))) {
                    this.markDirty(record, role, true);
                }
            }

            me.debouncedUpdateMemberCounts();

            resolve( new Expression(this.templates.membersCell.apply({
                id: record.id + ':' + role.id,
                memberTokens: me.memberToken.getTokens(roleAttendees, true, '<br />'),
                required: minCount,
                readonlyCount: readonlyCount,
                height: Math.max(25, Math.min(10, Math.max(roleAttendees.length, minCount)) * 24),
                state: minCount > roleAttendees.length ? '-not' : minCount < roleAttendees.length ? '-more-than' : '',
                color: role.color,
                colorR: role.colorRGB[0],
                colorG: role.colorRGB[1],
                colorB: role.colorRGB[2]
            })));
        }));
    },

    /**
     * build signature from user_type, user_id and role of given attendee
     *
     * @param {Array} attendee
     * @return {String}
     */
    getMembersSignature: function(attendee) {
        var _ = window.lodash,
            asHelper = Tine.Calendar.Model.Attender.getAttendeeStore;

        return JSON.stringify(_.reduce(attendee, function (result, a) {
            result.push(asHelper.getSignature(a));
            return result;
        }, []));
    },

    // reconfigure columns
    configureColumns: function() {
        const columns = [this.eventsColumn];
        const groups = this.groupingCollection.keys;
        const allRoles = _.map(this.csRolesStore.data.items, 'data.key');

        this.rolesVisible = _.uniq(this.rolesVisible.length ?  _.intersection(this.rolesVisible, allRoles) : allRoles);

        (groups || ['']).forEach((groupName) => {
            this.csRolesStore.each(function(role) {
                const name = role.get('name')
                const emblem = this.memberToken.renderRole(role);

                if (this.rolesVisible.indexOf(role.get('key')) < 0) return;

                columns.push({
                    // NOTE: ext can't cope with spaces in id's (which get clss)
                    id: `${String(groupName).replaceAll(' ', '¿')}:${role.get('key')}`,
                    header: name + emblem,
                    tooltip: role.get('description'),
                    sortable: false,
                    width: 100,
                    menuDisabled: true,
                    renderer: this.renderMembersDropZone.createDelegate(this, [role], 0)
                });
            }, this);
        });

        this.getColumnModel().rows = [];

        if (groups) {
            this.getColumnModel().rows.push([{
                // header: 'Events',
                colspan: 1
            }].concat(groups.map((groupName) => {
                return {
                    header: `<b>${groupName}</b>`,
                    align: 'center',
                    colspan: this.rolesVisible.length
                };
            })));
        }

        this.getColumnModel().setConfig(columns);
        this.getView().updateHeaders();
    },

    getState: function() {
        return {
            rolesVisible: this.rolesVisible
        }
    },

    applyState: function(state) {
        Object.assign(this, state);
    },

    beforeColMenuShow: function(colMenu) {
        colMenu.removeAll();
        this.csRolesStore.each((role) => {
            const name = role.get('name')
            const emblem = this.memberToken.renderRole(role);

            colMenu.add(new Ext.menu.CheckItem({
                itemId: role.get('key'),
                text: name + emblem,
                checked: this.rolesVisible.indexOf(role.get('key')) >= 0,
                hideOnClick:false
            }));
        });

        _.find(this.plugins, '_addMenuTitle')._addMenuTitle()
    },

    handleHdMenuClick: function(item) {
        this.rolesVisible = _[item.checked ? 'pull' : 'concat'](this.rolesVisible, item.itemId);
        this.saveState();
        this.configureColumns();
    },

    initTemplates: function() {
        var ts = this.templates || {};

        ts.event = new Ext.XTemplate(
            '<div class="cs-event">',
                '<div class="cs-event-date">{[this.renderDate(values.dtstart)]}</div>',
                '<div class="cs-event-time">{[this.renderTime(values.dtstart, values.dtend)]}</div>',
                '<div class="cs-event-summary">{[this.encode(values.summary)]}</div>',
            '</div>', {
                encode: Ext.util.Format.htmlEncode,
                renderDate: function(dtstart) {
                    var cal = Tine.Tinebase.appMgr.get('Calendar'),
                        format = cal.i18n._hidden(Tine.Calendar.DaysView.prototype.dayFormatString);

                    return String.format(format, dtstart.format('l'), dtstart.format('j'), dtstart.format('F'))
                },
                renderTime: function(dtstart, dtend) {
                    return dtstart.format('H:i') + '-' + dtend.format('H:i');
                }
            }
        );

        ts.membersCell = new Ext.XTemplate(
            '<div class="cs-members-cell cs-members-state{state}-enough" style="height: {height}px;" id="{id}">',
                '<div class="cs-members" style="',
                    // ' border-color: {color};'
                    // ' background: linear-gradient(rgba({colorR}, {colorG}, {colorB}, 1), rgba(255,0,0,0));',
                '">{memberTokens}</div>',
                '<div class="cs-role-number<tpl if="readonlyCount"> rs-role-number-readonly</tpl>">',
                    '<div class="cs-role-number-up"></div>',
                    '<div class="cs-role-number-required">{required}</div>',
                    '<div class="cs-role-number-down"></div>',
                '</div>',
            '</div>'
        );

        for(var k in ts){
            var t = ts[k];
            if(t && typeof t.compile == 'function' && !t.compiled){
                t.disableFormats = true;
                t.compile();
            }
        }

        this.templates = ts;
    }
});


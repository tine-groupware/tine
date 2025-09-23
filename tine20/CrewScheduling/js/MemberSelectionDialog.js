/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.CrewScheduling');

require('./MemberToken');

/**
 * @namespace   Tine.CrewScheduling
 * @class       Tine.CrewScheduling.MemberSelectionDialog
 * @extends     Ext.Panel
 *
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.CrewScheduling.MemberSelectionDialog = Ext.extend(Ext.Panel, {
    // private
    width: 650,
    height: 250,
    cls: 'tw-editdialog',
    layout: 'fit',
    windowNamePrefix: 'CalFreeTimeSearchWindow_',
    optionsStateId: 'FreeTimeSearchOptions',

    initComponent: function() {
        var _ = window.lodash;
        this.app = Tine.Tinebase.appMgr.get('CrewScheduling');
        this.memberToken = new Tine.CrewScheduling.MemberToken();

        this.fbar = ['->', {
            text: i18n._('Cancel'),
            minWidth: 70,
            ref: '../buttonCancel',
            scope: this,
            handler: this.onButtonCancel,
            iconCls: 'action_cancel'
        }, {
            text: i18n._('Ok'),
            minWidth: 70,
            ref: '../buttonApply',
            scope: this,
            handler: this.onButtonApply,
            iconCls: 'action_saveAndClose'
        }];

        var me = this,
            mainScreen = me.app.getMainScreen(),
            selectedCell = mainScreen.membersGrid.getSelectionModel().getSelectedCell(),
            cellId = Ext.fly(mainScreen.membersGrid.view.getCell(selectedCell[0], selectedCell[1])).query('.cs-members-cell')[0].id,
            ids = cellId.split(':'),
            roleId = ids.pop(),
            eventId = ids.join(':'),
            event = mainScreen.eventStore.getById(eventId),
            attendee = event.get('attendee'),
            role = mainScreen.csRolesStore.getById(roleId),
            roleAttendee = _.map(_.filter(attendee, {role: role.get('key')}), function(attendee) {
                return _.get(mainScreen.memberSelectionPanel.store.getById('user-' + attendee.user_id.id), 'data') || attendee;
            }),
            asHelper = Tine.Calendar.Model.Attender.getAttendeeStore,
            roleAttendeeSignatures = _.map(roleAttendee, asHelper.getSignature),
            possibleAttendee = _.map(_.filter(mainScreen.memberSelectionPanel.store.getRange(), function(attendee) {
                return _.indexOf(roleAttendeeSignatures, asHelper.getSignature(attendee)) < 0;
            }), 'data');

        this.possibleAttendee = possibleAttendee;
        this.roleAttendee = roleAttendee;
        this.initialRoleAttendee = _.concat(roleAttendee, []);

        this.items = [{
            layout: 'hbox',
            border: false,
            layoutConfig: {
                align : 'stretch',
                pack  : 'start'
            },
            items: [{
                flex: 1,
                layout: 'fit',
                border: false,
                title: this.app.i18n._('Available Attendee'),
                html: me.memberToken.getTokens(this.possibleAttendee, true, '<br />'),
                cls: 'cs-member-select-dialog-attendee-available',
                style: 'box-sizing: border-box; border-right: 1px solid #99bbe8;',
                ref: '../availableMembersBox'
            }, {
                flex: 1,
                layout: 'fit',
                border: false,
                title: this.app.i18n._('Selected Attendee'),
                html: me.memberToken.getTokens(this.roleAttendee, true, '<br />'),
                cls: 'cs-member-select-dialog-attendee-selected',
                ref: '../membersBox'
            }]
        }];

        Tine.CrewScheduling.MemberSelectionDialog.superclass.initComponent.call(this);
    },

    afterRender: function() {
        Tine.CrewScheduling.MemberSelectionDialog.superclass.afterRender.call(this);

        this.mon(this.getEl(), 'click', this.onClick, this);

        this.window.setTitle(this.app.i18n._('Select Attendee'));
    },

    onClick: function(e) {
        var _ = window.lodash,
            me = this,
            target = e.getTarget('.cs-member-token'),
            asHelper = Tine.Calendar.Model.Attender.getAttendeeStore,
            userId = target ? _.get(asHelper.fromSignature(Ext.fly(target).getAttribute('tine-cs-token-id')), 'data.user_id') : '',
            current = null,
            action = e.getTarget('.cs-member-select-dialog-attendee-selected') ? 'remove' : 'add';

        if (! target) {
            return;
        }

        if (action == 'add') {
            current = _.find(this.possibleAttendee, function(attendee) {
                return _.get(attendee, 'user_id.id') == userId;
            });

            _.set(current, 'user_id.count', _.get(current, 'user_id.count', 0) + 1);
            _.pull(this.possibleAttendee, current);
            this.roleAttendee.push(current);
        } else {
            current = _.find(this.roleAttendee, function(attendee) {
                return _.get(attendee, 'user_id.id') == userId;
            });

            _.set(current, 'user_id.count', _.get(current, 'user_id.count', 0) - 1);
            _.pull(this.roleAttendee, current);
            this.possibleAttendee.push(current);
        }

        this.availableMembersBox.update(me.memberToken.getTokens(this.possibleAttendee, true, '<br />'));
        this.membersBox.update(me.memberToken.getTokens(this.roleAttendee, true, '<br />'));
    },

    onButtonCancel: function() {
        this.fireEvent('cancel', this);
        this.purgeListeners();
        this.window.close();
    },

    onButtonApply: function() {
        var _ = window.lodash,
            me = this,
            mainScreen = me.app.getMainScreen(),
            selectedCell = mainScreen.membersGrid.getSelectionModel().getSelectedCell(),
            cellId = Ext.fly(mainScreen.membersGrid.view.getCell(selectedCell[0], selectedCell[1])).query('.cs-members-cell')[0].id,
            toRemove = _.difference(this.initialRoleAttendee, this.roleAttendee),
            toAdd = _.difference(this.roleAttendee, this.initialRoleAttendee);

        mainScreen.membersGrid.removeMembers(toRemove);
        mainScreen.membersGrid.addMembersToCell(cellId, _.map(toAdd, function(attendee) {
            return mainScreen.memberSelectionPanel.store.getById('user-' + attendee.user_id.id);
        }));

        this.fireEvent('apply', this);
        this.purgeListeners();
        this.window.close();
    }
});

Tine.CrewScheduling.MemberSelectionDialog.openWindow = function(config) {
    return Tine.WindowFactory.getWindow({
        modal: true,
        width: Tine.CrewScheduling.MemberSelectionDialog.prototype.width,
        height: Tine.CrewScheduling.MemberSelectionDialog.prototype.height,
        name: Tine.Calendar.EventEditDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.CrewScheduling.MemberSelectionDialog',
        contentPanelConstructorConfig: config
    });
};
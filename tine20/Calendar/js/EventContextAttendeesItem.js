/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */


Tine.Calendar.EventContextAttendeesItem = Ext.extend(Ext.menu.Item, {
    event: null,
    datetime: null,

    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');

        this.text = this.app.i18n._('Attendee/Resources')

        this.hidden = this.app.getMainScreen().getCenterPanel().isActiveView('Grid') || ! (
            this.event
            && this.event.get('editGrant')
            && Tine.Tinebase.appMgr.get('Calendar').featureEnabled('featureExtendedEventContextActions')
        );

        this.menu = [];
        this.view = this.app.getMainScreen().getCenterPanel().getCalendarPanel(this.app.getMainScreen().getCenterPanel().activeView).getView();

        var attendeeFilter = Ext.state.Manager.get('calendar-attendee-filter-grid'),
            explicitAttendee = attendeeFilter && Ext.isArray(attendeeFilter.explicitAttendee) ? attendeeFilter.explicitAttendee : [],
            currentAttendee = Tine.Calendar.Model.Attender.getAttendeeStore(this.event ? this.event.get('attendee') : []);

        currentAttendee.each(function(r) {
            this.menu.push(Object.assign({
                record: r,
                checked: true,
                handleClick: this.handleClick
            }, this.renderAttender(r)));
        }, this);

        Ext.each(explicitAttendee, function(attendeeData) {
            var r = new Tine.Calendar.Model.Attender(attendeeData, 'new-' + Ext.id()),
                isCurrent = Tine.Calendar.Model.Attender.getAttendeeStore.getAttenderRecord(currentAttendee, r);

            if (! isCurrent) {
                this.menu.push(Object.assign({
                    record: r,
                    checked: false,
                    handleClick: this.handleClick
                }, this.renderAttender(r)));
            }
        }, this);

        var other = new Ext.Action({
            text: this.app.i18n._('Additional Attendees...'),
            handler: this.onOtherClick,
            scope: this
        });
        this.menu.push(other);

        Tine.Calendar.EventContextAttendeesItem.superclass.initComponent.call(this);

        this.menu.on('hide', this.onMenuHide, this);

        // NOTE: the submenu is rendered lazily on hover - async names (HTMLProxy) would never be replaced in the DOM
        this.menu.items.each(function(item) {
            if (item.namePromise) {
                item.namePromise.then((name) => {
                    if (!item.isDestroyed) {
                        item.setText(item.typeHtml + Ext.util.Format.htmlEncode(name));
                    }
                });
            }
        }, this);
    },

    /**
     * returns menu item config (text) for given attender
     * attendee names might be rendered async (e.g. contact title templates). In this case the name gets
     * resolved and set later, see initComponent
     *
     * @param {Tine.Calendar.Model.Attender} attender
     * @return {Object} {text, typeHtml, namePromise}
     */
    renderAttender: function(attender) {
        const proto = Tine.Calendar.AttendeeGridPanel.prototype;
        const name = proto.renderAttenderName.call(proto, attender.get('user_id'), false, attender);
        // NOTE: String.prototype has asString too - plain strings are already html (e.g. resource icon + name)
        const isAsync = !!name?.isHTMLProxy;
        const typeHtml = proto.renderAttenderType.call(proto, attender.get('user_type'), {}, attender)
            .replace('<div', '<div style="display: inline-block; vertical-align: middle; margin: 0 5px 0 0; background-position: 0px;"');

        return {
            typeHtml: typeHtml,
            namePromise: isAsync ? name.asString() : null,
            text: typeHtml + (isAsync ? '&#160;' : (name || ''))
        };
    },

    /**
     * prevent menu hide
     */
    handleClick: function(e) {
        if(this.setChecked && !this.disabled && !(this.checked && this.group)){// disable unselect on radio item
            this.setChecked(!this.checked);
            this.parentMenu.dirty = true;
        }
        e.stopEvent();
    },

    /**
     * show extra window & prevent autosave
     */
    onOtherClick: function() {
        this.menu.un('hide', this.onMenuHide, this);

        var data = [],
            fakeRecord = this.event.copy();

        Ext.each(this.getSelectedRecords(), function(r) {
            data.push(r.data)
        }, this);
        fakeRecord.set('attendee', '');
        fakeRecord.set('attendee', data);

        this.attendeeGridPanel = new Tine.Calendar.AttendeeGridPanel({
            header: false,
            border: false
        });
        this.attendeeGridPanel.onRecordLoad(fakeRecord);

        var win = Tine.WindowFactory.getWindow({
            layout: 'fit',
            width: 500,
            height: 300,
            modal: true,
            title: this.app.i18n._('Attendee'),
            items: [{
                xtype: 'form',
                buttonAlign: 'right',
                border: false,
                layout: 'fit',
                items: this.attendeeGridPanel,
                buttons: [{
                    text: i18n._('Cancel'),
                    minWidth: 70,
                    scope: this,
                    handler: function (btn) {win.close()},
                    iconCls: 'action_cancel'
                }, {
                    text: i18n._('Ok'),
                    minWidth: 70,
                    scope: this,
                    handler: function(btn) {
                        this.attendeeGridPanel.onRecordUpdate(this.event);
                        this.view.editing = this.event;
                        this.app.getMainScreen().getCenterPanel().onUpdateEvent(this.event, false, 'update');
                        win.close();
                    },
                    variant: 'primary',
                    iconCls: 'action_saveAndClose'
                }]
            }]
        });

        win.show();
    },

    /**
     * autosave
     */
    onMenuHide: function() {
        if (this.updateRecord(this.getSelectedRecords())) {
            this.menu.un('hide', this.onMenuHide, this);
        }
    },

    updateRecord: function(selectedRecords) {
        var data = [];

        Ext.each(selectedRecords, function(r) {
            data.push(r.data)
        }, this);

        if (this.menu.dirty) {
            this.event.set('attendee', '');
            this.event.set('attendee', data);
            this.view.editing = this.event;
            this.app.getMainScreen().getCenterPanel().onUpdateEvent(this.event, false, 'update');
            this.ownerCt.destroy.defer(100, this.ownerCt);
            return true;
        }
    },

    getSelectedRecords: function() {
        var records = [];

        this.menu.items.each(function(item) {
            if (item.checked) {
                records.push(item.record);
            }
        }, this);

        return records;
    }
});

Ext.ux.ItemRegistry.registerItem('Calendar-MainScreenPanel-ContextMenu', Tine.Calendar.EventContextAttendeesItem, 140);

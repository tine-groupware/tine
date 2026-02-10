/*
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
import asString from "../../Tinebase/js/ux/asString";

Ext.ns('Tine.Calendar');

/**
 * @namespace   Tine.Calendar
 * @class       Tine.Calendar.AttendeeCombo
 * @extends     Ext.form.ComboBox
 * @author      Ching En Cheng <c.cheng@metaways.de>
 *
 */
Tine.Calendar.AttendeeCombo = Ext.extend(Ext.form.ComboBox, {
    eventRecord: null,
    messageRecord: null,
    defaultValue: 'current',
    
    typeAhead: true,
    triggerAction: 'all',
    lazyRender:true,
    mode: 'local',
    valueField: 'id',
    displayField: 'displayText',
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Calendar');
        this.initStore();

        Tine.Calendar.AttendeeCombo.superclass.initComponent.call(this);
    },

    initStore: function() {
        this.store = new Ext.data.Store({
            fields: Tine.Calendar.Model.Attender.getFieldDefinitions().concat([{name: 'displayText'}]),
        });
        this.organizer = this.eventRecord.get('organizer');
        this.originRecord = this.eventRecord.getMyAttenderRecord();
        this.currentAccount = Tine.Tinebase.registry.get('currentAccount');
    },
    
    syncStore: async function(attendeeData) {
        this.store.removeAll();

        const attendeePromise = attendeeData.asyncForEach(async (attendee) => {
            if (!attendee?.user_id) return null;

            const attendeeRecord = new Tine.Calendar.Model.Attender(attendee, 'new-' + Ext.id());
            let suffix = '';//Tine.Calendar.Model.Attender.getRecordName();
            const displaycontainer = attendeeRecord.get('displaycontainer_id');
            const userType = attendeeRecord.get('user_type');
            const isCurrent = attendeeRecord.get('user_id').email === this.currentAccount.accountEmailAddress;

            if (userType === 'group') return null;

            if (displaycontainer) {
                const grants = displaycontainer.account_grants;
                let hasRequireGrant = grants && !!grants.editGrant;

                if (userType === 'resource' && grants) {
                    suffix = this.app.i18n._('Resource');
                    hasRequireGrant = !!grants.resourceStatusGrant;
                }
                if (!hasRequireGrant) {
                    return null;
                }
            } else {
                if (!isCurrent) return null;
                attendee.id = 'current';
            }

            // Determine suffix based on attendee role
            if (this.organizer.email === attendeeRecord.get('user_id').email) {
                suffix = this.app.i18n._('Organizer');
            }
            if (attendee.user_id.id === this.currentAccount.contact_id) {
                suffix = this.app.i18n._('Me');
            }

            const displayName = await Tine.Calendar.AttendeeGridPanel.prototype.renderAttenderName(attendeeRecord.get('user_id'), {noIcon: true}, attendeeRecord).asString();
            suffix = suffix ? `(${suffix})` : '';
            attendeeRecord.set('displayText', `${displayName} ${suffix}`);
            attendeeRecord.id = attendee.id;

            // Check if this is the origin record
            if (this.defaultValue === 'current' && isCurrent) {
                this.originRecord = attendeeRecord;
                this.defaultValue = attendeeRecord.id;
            }
            if (attendee.status_authkey || isCurrent) {
                this.store.add(attendeeRecord);
            }
            return attendeeRecord;
        });

        // Wait for all attendee processing to complete
        await Promise.resolve(attendeePromise);

        this.setValue(this.defaultValue);

        if (!this.store.getById(this.getValue())) {
            this.setValue(this.defaultValue);
        }
    }
});
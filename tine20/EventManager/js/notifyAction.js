/*
 * Tine 2.0
 *
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 */


Promise.all([Tine.Tinebase.appMgr.isInitialised('EventManager'), Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('EventManager');

    const translation = new Locale.Gettext();
    translation.textdomain('EventManager');

    const COMM_PARTICIPANT = '1';
    const COMM_REGISTRANT = '2';
    const COMM_PARTICIPANT_CC_REGISTRANT = '3';

    const getParticipantResponsibility = function (responsibility) {
        switch (responsibility) {
            case '1':
                responsibility = 'Participant';
                break
            case '2':
                responsibility = 'Chair';
                break
            case '3':
                responsibility = 'Speaker';
                break
        }
        return responsibility;
    }

    const getParticipantStatus = function (status) {
        switch (status) {
            case '1':
                status = 'Confirmed';
                break
            case '2':
                status = 'Waiting list';
                break
            case '3':
                status = 'Cancelled';
                break
        }
        return status;
    }

    const getContactEmail = function (contact) {
        if (!contact || typeof contact !== 'object') {
            return '';
        }
        const preferred = contact.preferred_email ? contact[contact.preferred_email] : '';
        return String(preferred || contact.email || '').trim();
    }

    const getKeyFieldId = function (value) {
        if (value && typeof value === 'object') {
            value = value.id;
        }
        return value === null || value === undefined ? '' : String(value);
    }

    const getRegistrationRecipients = function (registration) {
        const participantEmail = getContactEmail(registration.participant);
        const registrantEmail = registration.has_registrant ? getContactEmail(registration.registrant) : '';

        let to = participantEmail;
        let cc = '';

        if (registrantEmail) {
            switch (getKeyFieldId(registration.communication_preference)) {
                case COMM_REGISTRANT:
                    to = registrantEmail;
                    break;
                case COMM_PARTICIPANT_CC_REGISTRANT:
                    to = participantEmail;
                    cc = registrantEmail;
                    break;
                default:
                    to = participantEmail;
            }
        }

        if (!to) {
            to = participantEmail || registrantEmail;
            cc = '';
        }

        return {
            to: to ? [to] : [],
            cc: cc && cc.toLowerCase() !== to.toLowerCase() ? [cc] : [],
        };
    }

    const collectEmails = function (registrations) {
        const emails = new Map();
        registrations.forEach(registration => {
            const recipients = getRegistrationRecipients(registration);
            [...recipients.to, ...recipients.cc].forEach(email => {
                const key = email.toLowerCase();
                if (!emails.has(key)) {
                    emails.set(key, email);
                }
            });
        });
        return Array.from(emails.values());
    }

    const openMessageDialog = function (emails) {
        const activeAccount = Tine.Tinebase.appMgr.get('Felamimail').getActiveAccount();

        const record = new Tine.Felamimail.Model.Message({
            subject: `${translation.gettext('Information regarding your involvement')}`,
            body: '',
            bcc: emails
        }, 0);

        Tine.Felamimail.MessageEditDialog.openWindow({
            accountId: activeAccount ? activeAccount.id : null,
            record: record
        });
    }

    const filterRegistrations = function (registrations, responsibility, status, defaultStatus) {
        return registrations.filter(registration => {
            const func = registration.function ? getParticipantResponsibility(registration.function) : responsibility;
            const recStatus = registration.status ? getParticipantStatus(registration.status) : defaultStatus;
            return (responsibility === func || responsibility === 'All')
                && (status === 'All' || recStatus === status);
        });
    }

    const notifyEventRegistrations = function (item, status, emptyMessage) {
        const responsibility = item !== 'All' ? item : 'All';

        const selections = this.mainScreen.EventGridPanel.selectionModel.selections.items ?? []
        if (selections.length === 0) return;

        const registrations = selections.map((selection) => selection.get('registrations') || []).flat();
        const matching = filterRegistrations(registrations, responsibility, status, status);
        const emails = collectEmails(matching);

        if (emails.length > 0) {
            openMessageDialog(emails);
        } else {
            Ext.MessageBox.alert(translation.gettext('Missing Participants'), emptyMessage);
        }
    }

    const sendMailToParticipants = function (item) {
        notifyEventRegistrations.call(
            this,
            item,
            'Confirmed',
            translation.gettext('Currently there are no participants confirmed in this event')
        );
    }

    const sendMailToWaitingListParticipants = function (item) {
        notifyEventRegistrations.call(
            this,
            item,
            'Waiting list',
            translation.gettext('Currently there are no participants signed for this event')
        );
    }

    const actionParticipantsConfig = {
        app: app,
        allowMultiple: false,
        iconCls: 'action_email_forward',
        text: app.i18n._('Notify Participants'),
        actionUpdater(action, grants, records) {
            let enabled = records.length >= 1
            if (enabled) {
                if (records.some(record => record.data.registrations && record.data.registrations.length > 0)) {
                    enabled = true;
                    const hasWaitingList = records.some(record => {
                        if (record.data.registrations && record.data.registrations.length > 0) {
                            return record.data.registrations.some(reg => reg.status === '2')
                        }
                    })
                    const waitingListMenuItem = action.menu.items.get(4)
                    if (waitingListMenuItem) {
                        waitingListMenuItem.setDisabled(!hasWaitingList)
                    }
                } else {
                    enabled = false;
                }
            }
            action.setDisabled(!enabled)
            action.baseAction.setDisabled(!enabled)
        },
        menu: [{
            app: app,
            text: app.i18n._('Participant'),
            handler: sendMailToParticipants.createDelegate(app, ['Participant']),
        }, {
            text: app.i18n._('Chair'),
            handler: sendMailToParticipants.createDelegate(app, ['Chair']),
        }, {
            text: app.i18n._('Speaker'),
            handler: sendMailToParticipants.createDelegate(app, ['Speaker']),
        }, {
            text: app.i18n._('All Confirmed Participants'),
            handler: sendMailToParticipants.createDelegate(app, ['All']),
        }, {
            text: app.i18n._('Waiting List'),
            handler: sendMailToWaitingListParticipants.createDelegate(app, ['All']),
        }],

        handler: sendMailToParticipants.createDelegate(app, ['All']),
    }

    const actionParticipants = new Ext.Action(actionParticipantsConfig);

    // Registrations inside EventEditDialog:

    let actionParticipantsRegistrations;
    let sharedRecords = [];

    const sendMailToSelectedRegistrations = function (item, status, records) {
        const responsibility = item === 'All' ? 'All' : item;
        if (records.length === 0) {
            records = (actionParticipantsRegistrations.initialConfig.selections || []).map(r => r.data ?? r);
        }

        const matching = filterRegistrations(records, responsibility, status, status);
        const emails = collectEmails(matching);

        if (emails.length > 0) {
            openMessageDialog(emails);
        } else {
            Ext.MessageBox.alert(translation.gettext('Missing Participants'), translation.gettext('Currently there are no confirmed participants'));
        }
    }

    const actionParticipantsRegistrationsConfig = {
        app: app,
        allowMultiple: false,
        iconCls: 'action_email_forward',
        text: app.i18n._('Notify Participants'),
        actionUpdater(action, grants, records) {
            sharedRecords = [];
            const regs = action.app.mainScreen.EventGridPanel.selectionModel.selections.items;
            if (regs.some(reg => (reg.data ?? reg).registrations)) {
                const flatRegs = (regs[0].data ?? regs[0]).registrations.flat();
                sharedRecords.push(...flatRegs);
            }
            let enabled = true;
            if (enabled) {
                const hasWaitingList = records.some(record => (record.data ?? record).status === '2')
                const waitingListMenuItem = action.menu.items.get(2)
                if (waitingListMenuItem) {
                    waitingListMenuItem.setDisabled(!hasWaitingList)
                }
            }
            action.setDisabled(!enabled)
            action.baseAction.setDisabled(!enabled)
        },
        menu: [{
            app: app,
            text: app.i18n._('All Confirmed Participants'),
            handler: function () { sendMailToSelectedRegistrations('All', 'Confirmed', sharedRecords); },
        }, {
            text: app.i18n._('All Selected Participants'),
            handler: function () { sendMailToSelectedRegistrations('All', 'All', []); },
        }, {
            text: app.i18n._('Waiting List'),
            handler: function () { sendMailToSelectedRegistrations('All', 'Waiting list', sharedRecords); },
        }],

        handler: function () { sendMailToSelectedRegistrations('All', 'Confirmed', sharedRecords); },
    }

    actionParticipantsRegistrations = new Ext.Action(actionParticipantsRegistrationsConfig);

    const smallBtnStyle = { scale: 'small', rowspan: 1, iconAlign: 'left'}
    const mediumBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}

    //Participants
    Ext.ux.ItemRegistry.registerItem(`EventManager-Event-GridPanel-ActionToolbar-leftbtngrp`, Ext.apply(new Ext.SplitButton(actionParticipants), mediumBtnStyle), 30)
    Ext.ux.ItemRegistry.registerItem(`EventManager-Event-GridPanel-ContextMenu`, actionParticipants, 2)
    Ext.ux.ItemRegistry.registerItem(`EventManager-Registration-GridPanel-ContextMenu`, actionParticipantsRegistrations, 2)
    Ext.ux.ItemRegistry.registerItem(`EventManager-Registration-PickerGrid-Bbar`, Ext.apply(new Ext.SplitButton(actionParticipantsRegistrations), smallBtnStyle), 2)
    Ext.ux.ItemRegistry.registerItem(`EventManager-Registration-PickerGrid-ContextMenu`, actionParticipantsRegistrations, 2)
});

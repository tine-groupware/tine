/*
 * Tine 2.0
 *
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */


Promise.all([Tine.Tinebase.appMgr.isInitialised('EventManager'), Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('EventManager');

    const translation = new Locale.Gettext();
    translation.textdomain('EventManager');


    const sendMailToParticipants = async function (item) {
        let responsibility = 'All';
        if (item !== 'All') {
            responsibility = item;
        }

        const selections = this.mainScreen.EventGridPanel.selectionModel.selections.items ?? []
        if (selections.length === 0) return;
        let participants = [];
        let emails = [];
        let registrations = selections.map((selection) => selection.get('registrations'));
        registrations = registrations.flat();
        if (registrations.length > 0) {
            registrations.forEach(registration => {
                let func = registration.function? getParticipantResponsibility(registration.function) : responsibility;
                if (responsibility === func || responsibility === 'All') {
                    participants.push(registration.participant);
                }
            });
            if (participants.length > 0) {
                let emailsSet = new Set(participants.map((participant) => participant[participant.preferred_email]));
                emails = Array.from(emailsSet);
                if (emails.length > 0) {
                    const activeAccount = Tine.Tinebase.appMgr.get('Felamimail').getActiveAccount();

                    const record = new Tine.Felamimail.Model.Message({
                        subject: `${translation.gettext('Information regarding your involvement')}`,
                        body: '',
                        bcc: emails
                    }, 0);

                    var popupWindow = Tine.Felamimail.MessageEditDialog.openWindow({
                        accountId: activeAccount ? activeAccount.id : null,
                        record: record
                    });
                }
            } else {
                Ext.MessageBox.alert(translation.gettext('Missing Participants'), translation.gettext('Currently there are no participants signed for this event'));
            }
        } else {
            Ext.MessageBox.alert(translation.gettext('Missing Participants'), translation.gettext('Currently there are no participants signed for this event'));
        }
    }

    const getParticipantResponsibility = function (responsibility) {
        switch (responsibility) {
            case '1':
                responsibility = 'Attendee';
                break
            case '2':
                responsibility = 'Speaker';
                break
            case '3':
                responsibility = 'Moderator';
                break
            case '4':
                responsibility = 'Employee';
                break
        }
        return responsibility;
    }

    const actionParticipantsConfig = {
        app: app,
        allowMultiple: false,
        iconCls: 'action_email_forward',
        text: app.i18n._('Notify Participants'),
        actionUpdater(action, grants, records) {
            let enabled = records.length >= 1
            action.setDisabled(!enabled)
            action.baseAction.setDisabled(!enabled)
        },
        menu: [{
            app: app,
            text: app.i18n._('Attendee'),
            handler: sendMailToParticipants.createDelegate(app, ['Attendee']),
        }, {
            text: app.i18n._('Speaker'),
            handler: sendMailToParticipants.createDelegate(app, ['Speaker']),
        }, {
            text: app.i18n._('Moderator'),
            handler: sendMailToParticipants.createDelegate(app, ['Moderator']),
        }, {
            text: app.i18n._('Employee'),
            handler: sendMailToParticipants.createDelegate(app, ['Employee']),
        }, {
            text: app.i18n._('All'),
            handler: sendMailToParticipants.createDelegate(app, ['All']),
        }],

        handler: sendMailToParticipants.createDelegate(app, ['All']),
    }

    const actionParticipants = new Ext.Action(actionParticipantsConfig);
    const smallBtnStyle = { scale: 'small', rowspan: 1, iconAlign: 'left'}
    const mediumBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}

    //Participants
    Ext.ux.ItemRegistry.registerItem(`EventManager-Event-GridPanel-ActionToolbar-leftbtngrp`, Ext.apply(new Ext.SplitButton(actionParticipants), mediumBtnStyle), 30)
    Ext.ux.ItemRegistry.registerItem(`EventManager-Event-GridPanel-ContextMenu`, actionParticipants, 2)
    Ext.ux.ItemRegistry.registerItem(`EventManager-Registration-GridPanel-ContextMenu`, actionParticipants, 2)
    Ext.ux.ItemRegistry.registerItem(`EventManager-Registration-PickerGrid-Bbar`, Ext.apply(new Ext.SplitButton(actionParticipants), smallBtnStyle), 2)
    Ext.ux.ItemRegistry.registerItem(`EventManager-Registration-PickerGrid-ContextMenu`, actionParticipants, 2)
});

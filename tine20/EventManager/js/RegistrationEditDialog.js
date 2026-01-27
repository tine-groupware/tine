/*
 * Tine 2.0
 *
 * @package     EventManager
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

Ext.namespace('Tine.EventManager');

Tine.EventManager.RegistrationEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    initComponent: function () {
        Tine.EventManager.RegistrationEditDialog.superclass.initComponent.call(this);
        this.on('beforerender', this.onBeforeRender, this);
        this.on('afterrender', this.onAfterRender, this);
    },

    onBeforeRender: function () {
        this.setSelectionConfigClassListener();
        this.showReasonWaitingList();
    },

    onAfterRender: function () {
        (function () {
            const registrantField = this.form.findField('registrant');
            const hasRegistrantField = this.form.findField('has_registrant');
            const participantField = this.form.findField('participant');
            this.setParticipantListener();

            if (!hasRegistrantField.getValue()) {
                registrantField.hide();
                if (registrantField.getValue() && participantField.getValue() && registrantField.getValue().original_id !== participantField.getValue().original_id) {
                    registrantField.show();
                }
            } else {
                if (registrantField.getValue() && participantField.getValue() && registrantField.getValue().original_id === participantField.getValue().original_id) {
                    registrantField.hide();
                }
            }
        }).defer(100, this);

        this.setStatusListener();
        this.waitingListListener();
        this.hasRegistrantListener();
        let registerOthers = this.form.openerCt.parentEditDialog.record.data.register_others;
        if (registerOthers === '2') {
            const hasRegistrantField = this.form.findField('has_registrant');
            hasRegistrantField.hide();
        }
    },

    setSelectionConfigClassListener: function () {
        return this.form.findField('booked_options').on('change', function (combo, records) {
            records.forEach((record) => {
                if (!record.selection_config_class) {
                    let option_config_class = record.option.option_config_class;
                    let appModel = option_config_class.split("Model_");
                    let selection = appModel[1].split("Option");
                    record.selection_config_class = "EventManager_Model_Selections_" + selection[0];
                }
            });
        },this);
    },

    setParticipantListener: function () {
        this.form.findField('participant').on('select', function (combo, record, index) {
            this.form.findField('registrant').setValue(combo.getValue());
        }, this);
    },

    setStatusListener: function () {
        return this.form.findField('status').on('change', function (combo) {
            this.showReasonWaitingList();
        },this);
    },

    showReasonWaitingList: function () {
        const registration_deadline = this.form.openerCt.parentEditDialog.record.data.registration_possible_until;
        const statusField = this.form.findField('status');
        const reasonField = this.form.findField('reason_waiting_list');
        if (statusField.getValue() === "2") {
            const deadlineTime = new Date(registration_deadline).getTime();
            const currentTime = Date.now();
            if (currentTime > deadlineTime) {
                reasonField.setValue("2");
            } else {
                reasonField.setValue("1");
            }
            reasonField.show();

            if (reasonField.wrap) {
                reasonField.wrap.setWidth('auto');
                reasonField.wrap.setDisplayed(true);
            }
        } else {
            reasonField.setValue("3");
            reasonField.hide();
        }
    },

    waitingListListener : function () {
        const total_places = this.form.openerCt.parentEditDialog.record.data.total_places;
        const available_places = this.form.openerCt.parentEditDialog.record.data.available_places;
        const registrations = this.form.openerCt.parentEditDialog.record.data.registrations;
        let registrations_count = 0;
        registrations.forEach((registration) => {
            if (registration.status !== "3" && registration.status !== "2") {
                registrations_count += 1;
            }
        })
        if (available_places <= 0 && total_places <= registrations_count) {
            const statusField = this.form.findField('status');
            if (statusField.getValue() !== "3") {
                statusField.setValue("2");
            }

            statusField.on('expand', function (combo) {
                setTimeout(function () {
                    const listId = combo.list ? combo.list.id : null;
                    let list = null;
                    if (listId) {
                        list = document.getElementById(listId);
                    } else {
                        const comboLists = Ext.query('.x-combo-list');
                        list = comboLists[comboLists.length - 1];
                    }
                    if (list) {
                        const items = Ext.query('.x-combo-list-item', list);
                        combo.getStore().each(function (record, index) {
                            if (record.get('id') === '1' && items[index]) {
                                items[index].style.setProperty('color', '#999', 'important');
                                items[index].style.setProperty('background-color', '#f0f0f0', 'important');
                                items[index].style.setProperty('opacity', '0.6', 'important');
                            }
                        });
                    }
                }, 10);
            }, this);

            statusField.on('beforeselect', function (combo, record, index) {
                // prevent selection of value confirmed
                return record.get(combo.valueField) !== "1";
            }, this);

            statusField.on('select', function (combo, record, index) {
                const reasonField = this.form.findField('reason_waiting_list');
                if (statusField.getValue() !== "2") {
                    reasonField.setValue("3");
                    reasonField.hide();
                    this.doLayout();
                } else {
                    this.showReasonWaitingList();
                }
            }, this);
        }
    },

    hasRegistrantListener : function () {
        const hasRegistrantField = this.form.findField('has_registrant');
        const registrantField = this.form.findField('registrant');
        const participantField = this.form.findField('participant');

        hasRegistrantField.on('change', function (checkbox, checked) {
            if (checked) {
                if (!participantField.getValue()) {
                    Ext.MessageBox.show({
                        buttons: Ext.Msg.OK,
                        icon: Ext.MessageBox.WARNING,
                        title: this.app.i18n._('Missing Participant'),
                        msg: this.app.i18n._('Please select a participant first')
                    });
                    checkbox.setValue(false);
                    return;
                }
                registrantField.show();
                if (registrantField.wrap) {
                    registrantField.wrap.setWidth('auto');
                    registrantField.wrap.setDisplayed(true);
                }

                const value = participantField.getValue();
                registrantField.setValue(value);

                if (registrantField.el && registrantField.el.dom) {
                    registrantField.el.dom.style.height = 'auto';
                    registrantField.el.dom.style.minHeight = '18px';
                }
            } else {
                registrantField.setValue(participantField.getValue());
                registrantField.hide();
            }

            this.doLayout();
        },this);
    },

    onSaveAndClose: function () {
        const hasRegistrantField = this.form.findField('has_registrant');
        const registrantField = this.form.findField('registrant');
        const participantField = this.form.findField('participant');

        if (registrantField.getValue() && participantField.getValue() && registrantField.getValue().original_id === participantField.getValue().original_id) {
            registrantField.hide();
            hasRegistrantField.setValue(false);
        }

        if (!hasRegistrantField.getValue()) {
            if (registrantField.getValue() && participantField.getValue() && registrantField.getValue().original_id !== participantField.getValue().original_id) {
                registrantField.setValue(participantField.getValue());
                hasRegistrantField.setValue(true);
            } else {
                registrantField.hide();
            }
        }

        this.supr().onSaveAndClose.apply(this, arguments);
    }
});
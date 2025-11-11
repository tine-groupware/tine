/*
 * Tine 2.0
 *
 * @package     EventManager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
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
    },

    onAfterRender: function () {
        this.setStatusListener();
        this.waitingListListener();
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
                reasonField.show();
            } else {
                reasonField.setValue("1");
                reasonField.show();
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
});
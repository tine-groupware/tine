/*
 * Tine 2.0
 *
 * @package     EventManager
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

import FieldTriggerPlugin from "ux/form/FieldTriggerPlugin";

Ext.namespace('Tine.EventManager');

Tine.EventManager.OptionEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    initComponent: function () {
        Tine.EventManager.OptionEditDialog.superclass.initComponent.call(this);
        this.app = Tine.Tinebase.appMgr.get('EventManager');
        this.translation = new Locale.Gettext();
    },

    onRender: function () {
        Tine.EventManager.OptionEditDialog.superclass.onRender.apply(this, arguments);
        this.setupDisplayTrigger();
        this.setupOptionRequiredTrigger();
    },

    setupDisplayTrigger: function () {
        const displayField = this.form.findField('display');
        displayField.on('select', function () {

            if (!displayField.plugins) {
                displayField.plugins = [];
            }

            if (displayField.plugins.length > 0) {
                for (let i = displayField.plugins.length - 1; i >= 0; i--) {
                    const plugin = displayField.plugins[i];
                    if (plugin instanceof FieldTriggerPlugin) {
                        displayField.plugins.splice(i, 1);
                    }
                }
            }
            // remove the action_edit image
            const actionEditTrigger = document.querySelector('.x-form-trigger.action_edit');
            if (actionEditTrigger) {
                actionEditTrigger.remove();
            }

            if (displayField.getValue() === "2") {
                const triggerPlugin = new FieldTriggerPlugin({
                    triggerClass: 'action_edit',
                    qtip: this.app.i18n._('Edit Rules'),
                    onTriggerClick: () => {
                        Tine.EventManager.OptionRelationEditDialog.openWindow({
                            record: this.record,
                        });
                    }
                });

                displayField.plugins.push(triggerPlugin);
                triggerPlugin.init(displayField);
            }
        },this);
    },

    setupOptionRequiredTrigger: function () {
        const requiredField = this.form.findField('option_required');
        requiredField.on('select', function () {

            if (!requiredField.plugins) {
                requiredField.plugins = [];
            }

            if (requiredField.plugins.length > 0) {
                for (let i = requiredField.plugins.length - 1; i >= 0; i--) {
                    const plugin = requiredField.plugins[i];
                    if (plugin instanceof FieldTriggerPlugin) {
                        requiredField.plugins.splice(i, 1);
                    }
                }
            }
            // remove the action_edit image
            const actionEditTrigger = document.querySelector('.x-form-trigger.action_edit');
            if (actionEditTrigger) {
                actionEditTrigger.remove();
            }

            if (requiredField.getValue() === "3") {
                const triggerPlugin = new FieldTriggerPlugin({
                    triggerClass: 'action_edit',
                    qtip: this.app.i18n._('Edit Rules'),
                    onTriggerClick: () => {
                        Tine.EventManager.OptionRelationEditDialog.openWindow({
                            record: this.record,
                        });
                    }
                });

                requiredField.plugins.push(triggerPlugin);
                triggerPlugin.init(requiredField);
            }
        },this);
    },

    getFormItems: function () {
        const me = this;
        const fieldManager = _.bind(
            Tine.widgets.form.FieldManager.get,
            Tine.widgets.form.FieldManager,
            this.appName,
            this.modelName,
            _,
            Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG
        );

        return {
            xtype: 'tabpanel',
            border: false,
            plain: true,
            activeTab: 0,
            items: [{
                title: this.app.i18n._('Option'),
                frame: true,
                layout: 'form',
                width: '100%',
                items: [{
                    xtype: 'columnform',
                    labelAlign: 'top',
                    formDefaults: {
                        xtype: 'textfield',
                        anchor: '100%',
                        columnWidth: 1
                    },
                    items: [
                        [fieldManager('name_option')],
                        [fieldManager('option_config_class')],
                        [fieldManager('option_config')],
                        [fieldManager('group')],
                        [fieldManager('sorting')],
                        [fieldManager('level')],
                        [fieldManager('option_required')],
                        [fieldManager('display')],
                    ]
                }]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    },
});
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

    onAfterRecordLoad: function () {
        Tine.EventManager.OptionEditDialog.superclass.onAfterRecordLoad.apply(this, arguments);

        const typeField = this.form.findField('option_config_class');
        if (typeField?.getValue()) {
            this.updateTypeDescription(typeField.getValue());
        }
    },

    afterRender: function () {
        Tine.EventManager.OptionsRuleEditDialog.superclass.afterRender.call(this);
        this.setOptionConfigClassTypeListener();
    },

    setOptionConfigClassTypeListener: function () {
        const typeField = this.form.findField('option_config_class');
        typeField.on('select', function () {
            let value = typeField.value;
            this.updateTypeDescription(value);
        }, this);
    },

    getTypeDescriptions: function () {
        return {
            'EventManager_Model_TextOption':      this.app.i18n._('A simple text option that displays a fixed text to the participant in the registration form.'),
            'EventManager_Model_CheckboxOption':  this.app.i18n._('A checkbox option that allows participants to opt in or out of something, e.g. a dietary preference. The name will be displayed next to the checkbox. If the price and description have a value, they will be displayed underneath the checkbox on the website. Die remaining available spaces will not be displayed for the participants, this is set for internal information purposes.'),
            'EventManager_Model_FileOption':      this.app.i18n._('A file option that allows participants to acknowledge a document or upload a file, such as a document or image. If in the configuration the acknowledge checkbox is set, participants will be able to download the file and asked to acknowledge the document with a checkbox. If the participant should upload a file they will be able to download the file set in the configuration and upload a new one e.g. the same document signed.'),
            'EventManager_Model_TextInputOption': this.app.i18n._('A text input option that allows participants to enter free-form text, such as a name or comment. E.g. allergies.'),
        };
    },

    updateTypeDescription: function (value) {
        if (!this.typeDescriptionPanel) return;
        const descriptions = this.getTypeDescriptions();
        const description = descriptions[value];

        value = value.replace('EventManager_Model_', '');
        value = value.replace('Option', '');
        const type = this.app.i18n._(value);

        if (description) {
            this.typeDescriptionPanel.update('<b>' + this.app.i18n._('Option type') + ' ' + type + ':</b> ' + description);
            this.typeDescriptionPanel.show();
        } else {
            this.typeDescriptionPanel.hide();
        }

        this.typeDescriptionPanel.ownerCt?.doLayout();
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

        this.typeDescriptionPanel = new Ext.Panel({
            xtype: 'panel',
            border: false,
            hidden: true,
            bodyStyle: 'padding: 8px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; color: #555;',
            html: ''
        });

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
                },
                    this.typeDescriptionPanel
                ]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    },
});
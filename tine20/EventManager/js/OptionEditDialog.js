/*
 * Tine 2.0
 *
 * @package     EventManager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.EventManager');

Tine.EventManager.OptionEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    initComponent: function () {
        Tine.EventManager.OptionEditDialog.superclass.initComponent.call(this);
        this.app = Tine.Tinebase.appMgr.get('EventManager');
    },

    onAfterRecordLoad: function () {
        Tine.EventManager.OptionEditDialog.superclass.onAfterRecordLoad.call(this);
        this.setDisplayListener();
        this.setOptionRequiredListener();
        this.checkAndShowRuleButton();
    },

    checkAndShowRuleButton: function () {
        if (this.shouldShowRuleButton()) {
            this.addRuleEditButton();
        }
    },

    shouldShowRuleButton: function () {
        return this.record && (this.record.get('display') === '2' || this.record.get('option_required') === '3');
    },

    addRuleEditButton: function () {
        if (this.ruleEditButton) {
            return;
        }

        const tabPanel = this.items.first();
        const optionTab = tabPanel.items.first();
        const mainPanel = optionTab.items.first();
        const columnForm = mainPanel.items.first();

        // create and add the button for the option rules
        this.ruleEditButton = new Ext.form.FieldSet({
            title: '',
            border: false,
            columnWidth: 1,
            items: [{
                xtype: 'button',
                text: this.app.i18n._('Edit Rules'),
                iconCls: 'action_edit',
                handler: this.openRuleEditDialog,
                scope: this,
                width: 150
            }]
        });

        columnForm.add(this.ruleEditButton);
        this.refreshLayout();
    },

    removeRuleEditButton: function () {
        if (this.ruleEditButton) {
            const tabPanel = this.items.first();
            const optionTab = tabPanel.items.first();
            const mainPanel = optionTab.items.first();
            const columnForm = mainPanel.items.first();

            columnForm.remove(this.ruleEditButton);
            this.ruleEditButton = null;
            columnForm.doLayout();
        }
    },

    updateRuleButton: function () {
        if (this.shouldShowRuleButton()) {
            this.addRuleEditButton();
        } else {
            this.removeRuleEditButton();
        }
    },

    refreshLayout: function () {
        const tabPanel = this.items.first();
        const optionTab = tabPanel.items.first();
        const mainPanel = optionTab.items.first();
        const columnForm = mainPanel.items.first();

        columnForm.doLayout();
        mainPanel.doLayout();
        optionTab.doLayout();
        tabPanel.doLayout();
        this.doLayout();
    },

    setDisplayListener: function () {
        this.form.findField('display').on('change', function () {
            this.updateRuleButton();
        },this);
    },

    setOptionRequiredListener: function () {
        this.form.findField('option_required').on('change', function () {
            this.updateRuleButton();
        },this);
    },

    openRuleEditDialog: function () {
        // Check if the openWindow method exists, if not create the window manually
        if (typeof Tine.EventManager.OptionRelationEditDialog.openWindow === 'function') {
            const ruleEditWindow = Tine.EventManager.OptionRelationEditDialog.openWindow({
                record: this.record,
                listeners: {
                    scope: this,
                    'update': this.onRuleUpdate
                }
            });
            return ruleEditWindow;
        } else {
            // Fallback: create window manually using WindowFactory
            const ruleEditWindow = Tine.WindowFactory.getWindow({
                width: 600,
                height: 500,
                name: 'OptionRelationEditWindow_' + (this.record.id || 'new'),
                contentPanelConstructor: 'Tine.EventManager.OptionRelationEditDialog',
                contentPanelConstructorConfig: {
                    record: this.record,
                    listeners: {
                        scope: this,
                        'update': this.onRuleUpdate
                    }
                }
            });
            return ruleEditWindow;
        }
    },

    onRuleUpdate: function (updatedRecordData, mode, dialog) {
        try {
            // Parse the JSON data if it's a string
            const updatedData = typeof updatedRecordData === 'string' ?
                Ext.util.JSON.decode(updatedRecordData) : updatedRecordData;

            if (updatedData.option_rule !== undefined) {
                this.record.set('option_rule', updatedData.option_rule);
            }
            if (updatedData.rule_type !== undefined) {
                this.record.set('rule_type', updatedData.rule_type);
            }

            // Mark the record as modified so the main dialog knows there are changes
            this.record.modified = this.record.modified || {};
            if (updatedData.option_rule !== undefined) {
                this.record.modified['option_rule'] = this.record.data['option_rule'];
            }
            if (updatedData.rule_type !== undefined) {
                this.record.modified['rule_type'] = this.record.data['rule_type'];
            }
            this.fireEvent('recordUpdate', this, this.record);
            if (this.actionUpdater) {
                this.actionUpdater.updateActions([this.record]);
            }
        } catch (e) {
            Ext.MessageBox.alert('Error', 'Failed to update rules: ' + e.message);
        }
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
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'vbox',
                items: [{
                    xtype: 'panel',
                    layout: 'hbox',
                    align: 'stretch',
                    items: [{
                        flex: 1,
                        xtype: 'columnform',
                        autoHeight: true,
                        items: [
                            [fieldManager('name_option')],
                            [fieldManager('option_config_class')],
                            [fieldManager('option_config')],
                            [fieldManager('group')],
                            [fieldManager('sorting')],
                            [fieldManager('level')],
                            [fieldManager('display')],
                            [fieldManager('option_required')],
                        ]
                    }]
                }]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    },
});

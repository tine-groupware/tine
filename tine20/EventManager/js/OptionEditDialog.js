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
        this.on('beforerender', this.onBeforeRender, this);
    },

    onBeforeRender: function () {
        this.form.findField('option_rule').setDisabled(true);
        this.form.findField('rule_type').setDisabled(true);

        if (this.record.get('display') === '2') { // value 2 is 'if'
            this.form.findField('option_rule').setDisabled(false);
            this.form.findField('rule_type').setDisabled(false);
        }

        if (this.record.get('option_required') === '3') { // value 3 is 'if'
            this.form.findField('option_rule').setDisabled(false);
            this.form.findField('rule_type').setDisabled(false);
        }

        this.setDisplayListener();
        this.setOptionRequiredListener();
    },

    setDisplayListener: function () {
        this.form.findField('display').on('change', function () {
            if (this.form.findField('display').getValue() === '2') { // value 2 is 'if'
                this.form.findField('option_rule').setDisabled(false);
                this.form.findField('rule_type').setDisabled(false);
            } else {
                this.form.findField('option_rule').setDisabled(true);
                this.form.findField('rule_type').setDisabled(true);
            }
        },this);
    },

    setOptionRequiredListener: function () {
        this.form.findField('option_required').on('change', function () {
            if (this.form.findField('option_required').getValue() === '3') { // value 3 is 'if'
                this.form.findField('option_rule').setDisabled(false);
                this.form.findField('rule_type').setDisabled(false);
            } else {
                this.form.findField('option_rule').setDisabled(true);
                this.form.findField('rule_type').setDisabled(true);
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
                            [fieldManager('display')],
                            [fieldManager('option_required')],
                            [fieldManager('group')],
                            [fieldManager('sorting')],
                            [fieldManager('level')],
                            [fieldManager('option_rule')],
                            [fieldManager('rule_type')],
                        ]
                    }]
                }]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    }
});

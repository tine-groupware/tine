/*
 * tine Groupware
 *
 * @package     EventManager
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2025-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

Ext.namespace('Tine.EventManager');

Tine.EventManager.OptionsRuleEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    initComponent: function () {
        this.app = Tine.Tinebase.appMgr.get('EventManager');
        this.optionsById = {};
        Tine.EventManager.OptionsRuleEditDialog.superclass.initComponent.call(this);
        this.on('beforerender', this.onBeforeRender, this);
    },

    getFormItems: function () {
        const fieldManager = _.bind(
            Tine.widgets.form.FieldManager.get,
            Tine.widgets.form.FieldManager,
            'EventManager', 'OptionsRule', _,
            Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG
        );

        this.refOptionCombo = new Ext.form.ComboBox({
            fieldLabel: this.app.i18n._('Event Option'),
            name: 'ref_option_select',
            store: new Ext.data.JsonStore({
                idProperty: 'id',
                fields: ['id', 'name_option'],
                data: []
            }),
            mode: 'local',
            triggerAction: 'all',
            editable: false,
            forceSelection: true,
            allowBlank: false,
            valueField: 'id',
            displayField: 'name_option',
            columnWidth: 1
        });

        return {
            border: false,
            frame: true,
            layout: 'fit',
            items: [{
                xtype: 'columnform',
                autoHeight: true,
                items: [
                    [this.refOptionCombo],
                    [fieldManager('criteria')],
                    [fieldManager('value')]
                ]
            }]
        };
    },

    onBeforeRender: function () {
        this.toggleValueVisibility(false);
    },

    afterRender: function () {
        Tine.EventManager.OptionsRuleEditDialog.superclass.afterRender.call(this);
        this.fillRefOptions();
        this.syncRefOption();
        this.setCriteriaListener();
    },

    fillRefOptions: function () {
        const relationDialog = this.form.openerCt.parentEditDialog;
        const ownId = relationDialog.record.id;
        const options = (relationDialog.eventOptions || []).filter(o => o.id !== ownId);

        this.optionsById = _.keyBy(options, 'id');
        this.refOptionCombo.store.loadData(options);
    },

    syncRefOption: function () {
        if (!this.rendered || !this.record) return;

        const current = this.record.get('ref_option_field');
        const currentId = current?.id ?? current;
        const store = this.refOptionCombo.store;

        if (currentId && this.optionsById[currentId]) {
            this.refOptionCombo.setValue(currentId);
        } else if (store.getCount()) {
            this.refOptionCombo.setValue(store.getAt(0).id);
        } else {
            this.refOptionCombo.clearValue();
        }
    },

    onAfterRecordLoad: function () {
        Tine.EventManager.OptionsRuleEditDialog.superclass.onAfterRecordLoad.apply(this, arguments);
        this.syncRefOption();
    },

    onRecordUpdate: function () {
        Tine.EventManager.OptionsRuleEditDialog.superclass.onRecordUpdate.apply(this, arguments);
        const opt = this.optionsById[this.refOptionCombo.getValue()];
        this.record.set('ref_option_field', opt ? {...opt} : null);
    },

    setCriteriaListener: function () {
        const criteriaField = this.getForm().findField('criteria');
        if (criteriaField) {
            criteriaField.on('select', function (combo, rec) {
                if (!(rec.id === '1' || rec.id === '2')) {
                    this.toggleValueVisibility(true);
                } else {
                    this.toggleValueVisibility(false);
                }
            }, this);
        }
    },

    toggleValueVisibility: function (visible) {
        const valueField = this.getForm().findField('value');
        if (valueField) {
            const container = valueField.findParentByType('container') || valueField.findParentByType('fieldset');

            if (container) {
                if (visible) {
                    container.show();
                    valueField.show();
                } else {
                    valueField.hide();
                    container.hide();
                }
                container.doLayout();
            }
        }
    },
});

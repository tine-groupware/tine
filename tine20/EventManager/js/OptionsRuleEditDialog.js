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

Tine.EventManager.OptionsRuleEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    initComponent: function () {
        Tine.EventManager.OptionsRuleEditDialog.superclass.initComponent.call(this);
        this.app = Tine.Tinebase.appMgr.get('EventManager');
        this.on('beforerender', this.onBeforeRender, this);
    },

    onBeforeRender: function () {
        this.toggleValueVisibility(false);
    },

    afterRender: function () {
        Tine.EventManager.OptionsRuleEditDialog.superclass.afterRender.call(this);
        let eventId = this.form.openerCt.parentEditDialog.record.data.event_id;
        this.form.findField('ref_option_field').additionalFilters = [{field: 'event_id', operator: 'equals', value: eventId}]
        this.setCriteriaListener();
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

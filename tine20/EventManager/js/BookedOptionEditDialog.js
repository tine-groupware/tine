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

Tine.EventManager.BookedOptionEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    initComponent: function () {
        Tine.EventManager.BookedOptionEditDialog.superclass.initComponent.call(this);
        this.on('beforerender', this.onBeforeRender, this);
    },

    onBeforeRender: function () {
        this.setSelectionConfigClassListener();
        let eventId = this.form.openerCt.parentEditDialog.record.data.event_id;
        this.form.findField('option').additionalFilters = [{field: 'event_id', operator: 'equals', value: eventId}]
        this.form.findField('selection_config_class').hidden = true;
    },

    setSelectionConfigClassListener: function () {
        this.form.findField('option').on('select', function (combo, rec) {
            let option_config_class = rec.get('option_config_class');
            let appModel = option_config_class.split("Model_");
            let selection = appModel[1].split("Option");
            let selection_config_class = "EventManager_Model_Selections_" + selection[0];
            this.form.findField('selection_config_class').setValue(selection_config_class);
        },this);
    },
});

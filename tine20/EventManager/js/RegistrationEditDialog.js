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

Tine.EventManager.RegistrationEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    initComponent: function () {
        Tine.EventManager.RegistrationEditDialog.superclass.initComponent.call(this);
        this.on('beforerender', this.onBeforeRender, this);
    },
    onBeforeRender: function () {
        this.setSelectionConfigClassListener();
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
});

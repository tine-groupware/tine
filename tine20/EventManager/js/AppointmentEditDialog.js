/*
 * tine Groupware
 *
 * @package     EventManager
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

Ext.namespace('Tine.EventManager');

Tine.EventManager.AppointmentEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    initComponent: function () {
        Tine.EventManager.AppointmentEditDialog.superclass.initComponent.call(this);
        const sessionNumberField = this.form.findField('session_number');
        if (sessionNumberField) {
            sessionNumberField.hide();
            this.doLayout();
        }
    },
});
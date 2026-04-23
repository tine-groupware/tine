/*
 * Tine 2.0
 *
 * @package     GDPR
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

Ext.namespace('Tine.GDPR');

Tine.GDPR.DataIntendedPurposeEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    checkStates: function() {
        this.form.findField('url').setVisible(!!this.form.findField('is_self_registration').getValue());
    },
});
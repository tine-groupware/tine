/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.UserManual');

Tine.UserManual.UserManualAction = function(config) {
    return new Ext.Action({
        text: String.format(i18n._('User Manual')),
        iconCls: 'UserManualIconCls',
        handler: function () {
            Tine.UserManual.UserManualDialog.openWindow({});
        },
        scope: this
    });
};

Ext.ux.ItemRegistry.registerItem('Tine.Tinebase.MainMenu', Tine.UserManual.UserManualAction, 30);
Ext.ux.ItemRegistry.registerItem('Tine.Tinebase.AppMenu.Additionals', Tine.UserManual.UserManualAction, 15);
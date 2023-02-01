/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.UserManual');

Tine.UserManual.ContextItem = function(config) {
    return new Ext.Action({
        hidden: !Tine.UserManual.registry,
        text: String.format(i18n._('Help')),
        iconCls: 'UserManualIconCls',
        handler: function () {
            Tine.UserManual.UserManualDialog.openWindow({
                context: Tine.Tinebase.CanonicalPath.getPath(this.context)
            });
        },
        scope: this,
        listeners: {
            scope: this,
            // NOTE: we need to make sure to have the context when the menu was opened
            //       as the context menu might be constructed before the context click.
            // NOTE: maybe this fails, as between construction and rendering might be
            //       mouse move events? - in this case let's implement a getLastContext()
            //       based on on Ext.EventManager
            render: function(cmp) {
                this.context = Tine.Tinebase.MainContextMenu.getCmp(Ext.EventObject);
            }
        }
    });
};

Ext.ux.ItemRegistry.registerItem('Tinebase-MainContextMenu', Ext.menu.Separator, 10000);
Ext.ux.ItemRegistry.registerItem('Tinebase-MainContextMenu', Tine.UserManual.ContextItem, 10100);

Tine.Tinebase.appMgr.isInitialised('UserManual').then(() => {
    const app = Tine.Tinebase.appMgr.get('UserManual');
    const i18n = app.i18n;

    Tine.UserManual.DebugItem = new Ext.Action({
        hidden: !Tine.Tinebase.registry || Tine.Tinebase.registry.get('version')['buildType'] != 'DEVELOPMENT',
        text: String.format(i18n._('Show Canonical Path')),
        iconCls: 'usermanual-action-debug',
        handler: function () {
            Ext.MessageBox.show({
                title: i18n._('Canonical Path'),
                msg: Tine.Tinebase.CanonicalPath.getPath(this.context),
                icon: Ext.MessageBox.INFO,
                buttons: Ext.Msg.OK
            });
        },
        scope: this,
        listeners: {
            scope: this,
            // NOTE: we need to make sure to have the context when the menu was opened
            //       as the context menu might be constructed before the context click.
            // NOTE: maybe this fails, as between construction and rendering might be
            //       mouse move events? - in this case let's implement a getLastContext()
            //       based on on Ext.EventManager
            render: function(cmp) {
                this.context = Tine.Tinebase.MainContextMenu.getCmp(Ext.EventObject);
            }
        }
    });

    Ext.ux.ItemRegistry.registerItem('Tinebase-MainContextMenu', Tine.UserManual.DebugItem, 10200);
});
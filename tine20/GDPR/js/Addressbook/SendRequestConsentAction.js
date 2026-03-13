/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

let getAction;
Promise.all([Tine.Tinebase.appMgr.isInitialised('GDPR'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('GDPR')
    getAction = (config) => {
        return new Ext.Action(Object.assign({
            text: config.text || app.formatMessage('Request Consent'),
            qtip: config.qtip || app.formatMessage('Send Request Consent message to contacts'),
            iconCls: `FelamimailIconCls`,
            requiredGrant: 'readGrant',
            async handler(cmp) {
                const editDialog = cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog})
                const gridPanel = Tine.widgets.grid.GridPanel.getByChildCmp(cmp);
                const selections = gridPanel?.selectionModel ? gridPanel.selectionModel.getSelections() : [editDialog?.record];

                if (selections.length === 0) return;

                const mailAddresses = await Tine.Felamimail.GridPanelHook.prototype.getMailAddresses(selections);
                var popupWindow = Tine.Felamimail.MessageEditDialog.openWindow({
                    massMailingPlugins: ['poll', 'all'],
                    contentPanelConstructorInterceptor: async (config) => {
                        const { subject, content } = await Tine.Tinebase.getEmailTwigTemplate('UpdatePrivacyConsent', 'GDPR')

                        config.record = new Tine.Felamimail.Model.Message({
                            subject: Ext.util.Format.trim(Ext.util.Format.htmlDecode(subject)),
                            massMailingFlag: true,
                            bcc: mailAddresses,
                            body: content
                        }, 0);
                    },
                });
            }

        }, config))
    }

    const action = getAction({})
    Ext.ux.ItemRegistry.registerItem(`Addressbook-Contact-GridPanel-ContextMenu`, action, 80)
})

export { getAction as getSendRequestConsentAction }
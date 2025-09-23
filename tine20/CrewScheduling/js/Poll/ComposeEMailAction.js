/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Promise.all([Tine.Tinebase.appMgr.isInitialised('CrewScheduling'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('CrewScheduling')

    const getAction = (config) => {
        return new Ext.Action(Object.assign({
            text: config.text || app.formatMessage('Send Mail'),
            qtip: config.qtip || app.formatMessage('Compose Mail for all Attendee'),
            iconCls: `cs_poll_mail`,
            actionUpdater(action, grants, records, isFilterSelect, filteredContainers) {
                let enabled = records.length === 1
                enabled = enabled && _.get(records, '[0].data.account_grants.sendEmailsGrant') || _.get(records, '[0].data.account_grants.adminGrant')
                enabled = enabled && ! _.get(records, '[0].phantom')

                action.setDisabled(!enabled)
                action.baseAction.setDisabled(!enabled) // WTF?
            },
            async handler(cmp) {
                let record = this.initialConfig.selections[0]
                Tine.Felamimail.MessageEditDialog.openWindow({
                    contentPanelConstructorInterceptor: async (config) => {
                        const template = !!+record.get('is_closed') ? 'pollResults' : 'pollInvitation'
                        const { subject, html } = await Tine.CrewScheduling.getPollMessage(template, record.id)
                        // @TODO fixme, invent better handling to skip gdpr client plugin
                        config.massMailingPlugins = ['poll']
                        config.record = new Tine.Felamimail.Model.Message({
                            subject: Ext.util.Format.trim(Ext.util.Format.htmlDecode(subject)),
                            body: html,
                            massMailingFlag: true,
                            bcc: _.map(record.data.participants, participant => {
                                const contact = Tine.Tinebase.data.Record.setFromJson(participant.contact_id, 'Addressbook.Contact')
                                return Tine.Felamimail.GridPanelHook.prototype.getRecipientTokenFromContact(contact)
                            })
                        })
                    }
                });
            }

        }, config))
    }

    const action = getAction({})
    const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}
    Ext.ux.ItemRegistry.registerItem(`CrewScheduling-Poll-GridPanel-ContextMenu`, action, 49)
    Ext.ux.ItemRegistry.registerItem(`CrewScheduling-Poll-GridPanel-ActionToolbar-leftbtngrp`, Ext.apply(new Ext.Button(action), medBtnStyle), 39)
    Ext.ux.ItemRegistry.registerItem(`CrewScheduling-Poll-editDialog-Toolbar`, Ext.apply(new Ext.Button(action), medBtnStyle), 100)

})

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
            text: config.text || app.formatMessage('Open Poll'),
            qtip: config.qtip || app.formatMessage('Open Poll Page'),
            iconCls: `cs_poll`,
            actionUpdater(action, grants, records, isFilterSelect, filteredContainers) {
                let enabled = records.length === 1
                enabled = enabled && ! _.get(records, '[0].phantom')
                
                action.setDisabled(!enabled)
                action.baseAction.setDisabled(!enabled) // WTF?
            },
            async handler(cmp) {
                let record = this.initialConfig.selections[0]
                window.open(record.getUrl(), '_blank')
            }

        }, config))
    }

    const action = getAction({})
    const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}
    Ext.ux.ItemRegistry.registerItem(`CrewScheduling-Poll-GridPanel-ContextMenu`, action, 40)
    Ext.ux.ItemRegistry.registerItem(`CrewScheduling-Poll-GridPanel-ActionToolbar-leftbtngrp`, Ext.apply(new Ext.Button(action), medBtnStyle), 38)
    Ext.ux.ItemRegistry.registerItem(`CrewScheduling-Poll-editDialog-Toolbar`, Ext.apply(new Ext.Button(action), medBtnStyle), 90)

})

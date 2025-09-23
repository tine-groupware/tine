/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import('./ComposeEMailAction')
import('./OpenPollPageAction')

const GridDialog = Ext.extend(Tine.Tinebase.dialog.Dialog, {

    initComponent() {
        this.app = Tine.Tinebase.appMgr.get('CrewScheduling')
        this.pollGridPanel = new Tine.CrewScheduling.PollGridPanel({

        })

        this.tbar = this.pollGridPanel.getActionToolbar()
        this.items = [this.pollGridPanel]

        return this.supr().initComponent.call(this)
    }
})

Tine.CrewScheduling.PollGridDialog = GridDialog

GridDialog.openWindow = (config) => {
    return Tine.WindowFactory.getWindow({
        width: 1024,
        height: 600,
        name: 'CrewScheduling.PollGridDialog',
        contentPanelConstructor: 'Tine.CrewScheduling.PollGridDialog',
        contentPanelConstructorConfig: config
    })
}

export default GridDialog
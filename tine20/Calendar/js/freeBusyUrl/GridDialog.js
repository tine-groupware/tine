/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import * as markdown from 'util/markdown'
import FreeBusyUrl from "../Model/FreeBusyUrl";
import './urlRenderer';

const GridDialog = Ext.extend(Tine.Tinebase.dialog.Dialog, {

    initComponent() {
        this.app = Tine.Tinebase.appMgr.get('Calendar')
        const owner_class = this.resourceId ? 'Calendar_Model_Resource' : 'Tinebase_Model_User'
        const owner_id = this.resourceId || this.personalOwnerId

        const gridPanel = new Tine.Calendar.FreeBusyUrlGridPanel({
            ownActionToolbar: true,
            editDialog: true,
            usePagingToolbar: false,
            stateful: false,
            initFilterPanel: Ext.emptyFn,
            defaultFilters: [
                { field: 'owner_class', operator: 'equals', value: owner_class },
                { field: 'owner_id', operator: 'equals', value: owner_id }
            ],
            editDialogConfig: {
                hideFields: ['owner_class', 'owner_id'],
                fixedFields: {
                    owner_class,
                    owner_id
                }
            },
            createNewRecord() {
                const record = FreeBusyUrl.setFromJson({})
                record.phantom = true

                return record
            }
        })

        this.items = [gridPanel]

        this.afterIsRendered().then( () => {
            this.showInfo()
        })

        return this.supr().initComponent.call(this)
    },

    async showInfo() {
        Ext.Msg.show({
            buttons: Ext.Msg.OK,
            icon: Ext.MessageBox.INFO_INSTRUCTION,
            title: this.app.formatMessage('Create individual Free/Busy URLs'),
            stateId: 'calendar-freebusyurl-info',
            msg: `
                ${this.app.formatMessage('In { brandingTitle } you can create individual Free/Busy URLs for each person you want to share your free/busy information with.', { brandingTitle: Tine.Tinebase.registry.get('brandingTitle')})}
                <br /><br />
                ${this.app.formatMessage('Access to your free/busy information is revoked by just deleting the Free/Busy URL for that person.')}
            `
        });
    }
})

Tine.Calendar.FreeBusyUrlGridDialog = GridDialog

GridDialog.openWindow = (config) => {
    return Tine.WindowFactory.getWindow({
        width: 600,
        height: 600,
        name: 'Calendar-FreeBusyUrl-GridDialog-' + (config.resourceId || config.personalOwnerId),
        contentPanelConstructor: 'Tine.Calendar.FreeBusyUrlGridDialog',
        contentPanelConstructorConfig: config
    })
}

export default GridDialog
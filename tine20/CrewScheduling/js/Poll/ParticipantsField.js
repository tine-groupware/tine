/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import { getRole, getEventTypeCapableContactsMap } from '../Model/schedulingRole'

Promise.all([Tine.Tinebase.appMgr.isInitialised('CrewScheduling'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('CrewScheduling')



    const ParticipantsField = Ext.extend(Tine.widgets.grid.PickerGridPanel, {
        // onRowDblClick: () => {},
        // onRowContextMenu: () => {},

        initComponent() {
            this.allowCreateNew = false
            this.allowDelete = false
            this.supr().initComponent.call(this)
        },

        checkState: async function (editDialog, field) {
            this.readOnly = true
            this.getTopToolbar().hide()
            this.getBottomToolbar().hide()

            // const record = editDialog.r
            const roleId = editDialog.getForm().findField('scheduling_role').getValue()
            if (editDialog.record.phantom && roleId && roleId !== this.roleId) {
                this.roleId = roleId
                this.showLoadMask()
                const role = await getRole(roleId)
                const capableContactsMap = await getEventTypeCapableContactsMap(role)
                // @TODO load all events in period (other filters) and take capable contacts only (mind the site too)!
                const contactIds = _.uniq(_.reduce(Object.keys(capableContactsMap), (accu, typeId) => { return accu.concat(capableContactsMap[typeId])}, []))
                const { results: contacts } = await Tine.Addressbook.searchContacts([{ field: 'id', operator: 'in', value: contactIds }])
                this.store.loadData(_.map(contacts, (contact) => { return {
                    poll_id: editDialog.record.id,
                    contact_id: contact
                }}))
                this.hideLoadMask()
            }


        }
    })

    Ext.reg('cs-poll-participantsfield', ParticipantsField)

    // Tine.widgets.form.FieldManager.register('CrewScheduling', 'Poll', 'participants', {
    //     xtype: 'CrewScheduling-Poll-ParticipantsField',
    //     height: 300,
    // }, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);

})
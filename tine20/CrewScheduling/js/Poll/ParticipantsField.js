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

        initComponent() {
            this.allowCreateNew = false
            this.allowDelete = false
            this.supr().initComponent.call(this)
        },

        checkState: async function (editDialog, field) {
            this.readOnly = true
            this.getTopToolbar().hide()
            this.getBottomToolbar().hide()

            // @TODO recheck on open and ask user for add/deletes?
            const roleId = editDialog.getForm().findField('scheduling_role').getValue()
            const sitesIds = _.map(editDialog.getForm().findField('sites').getValue(), 'site_id.id')
            const eventTypesIds = _.map(editDialog.getForm().findField('event_types').getValue(), 'event_type_id.id')
            const key = _.compact([roleId].concat(sitesIds).concat(eventTypesIds))
            if (editDialog.record.phantom && roleId && key !== this.key) {
                this.key = key
                this.showLoadMask()
                const role = await getRole(roleId)
                const capableContactsMap = await getEventTypeCapableContactsMap(role)
                const contactIds = _.uniq(_.reduce(Object.keys(capableContactsMap), (accu, typeId) => {
                    return accu.concat(eventTypesIds?.length < 1 || eventTypesIds.indexOf(typeId) >= 0 ? capableContactsMap[typeId] : [])
                }, []))
                const { results: contacts } = await Tine.Addressbook.searchContacts([
                    { field: 'id', operator: 'in', value: contactIds },
                ].concat(sitesIds?.length < 1 ? [] : [ { condition: 'OR', filters: [
                    // either no site set
                    { field: 'sites', operator: 'definedBy?condition=and&setOperator=oneOf', value: null },
                    // or site is one of the selected
                    { field: 'sites', operator: 'definedBy?condition=and&setOperator=oneOf', value: [
                        { field: "site", operator: "definedBy?condition=and&setOperator=oneOf", value: [
                            {field: ':id', operator: 'in', value: sitesIds } ] } ] }
                    ] }
                ]))
                this.store.loadData(_.map(contacts, (contact) => { return {
                    poll_id: editDialog.record.id,
                    contact_id: contact
                }}))
                this.hideLoadMask()
            }

            this.setFieldLabel(this.initialConfig.fieldLabel + ` (${this.store.getCount()})`)

        }
    })

    Ext.reg('cs-poll-participantsfield', ParticipantsField)

    // Tine.widgets.form.FieldManager.register('CrewScheduling', 'Poll', 'participants', {
    //     xtype: 'CrewScheduling-Poll-ParticipantsField',
    //     height: 300,
    // }, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);

})
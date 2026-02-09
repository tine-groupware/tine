/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Promise.all([Tine.Tinebase.appMgr.isInitialised('Sales'),
    Tine.Tinebase.appMgr.isInitialised('Addressbook'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Sales')

    const getAction = (config) => {
        return new Ext.Action(Object.assign({
            text: config.text || app.formatMessage('Create from Contact ...'),
            iconCls: `SalesAddressFromContact`,
            hidden: true,
            actionUpdater(action, grants, records, isFilterSelect, filteredContainers) {
                action.setVisible(records[0].phantom && !records[0].constructor.hasField('original_id'))
            },
            async handler(cmp) {
                const dialog = getDialog({
                    listeners: {
                        apply: async (eventData) => {
                            const editDialog = cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog})
                            let linkType = 'CONTACTADDRESS'
                            if (editDialog.recordClass.getMeta('modelName') === 'Customer') {
                                linkType = 'CONTACTCUSTOMER'
                                config.fieldPrefix = config.fieldPrefix || 'adr_'
                                editDialog.getForm().findField('name').setValue(eventData.contact.get('org_name'))
                                editDialog.getForm().findField('url').setValue(eventData.contact.get('url'))
                                eventData.address.name = eventData.create_link ? eventData.address.name:  eventData.contact.get('org_name')
                            }
                            if (eventData.create_link) {
                                const relationStore = editDialog.relationsPanel.store
                                relationStore.add(relationStore.recordType.setFromJson({
                                    type: linkType,
                                    related_degree: 'parent',
                                    own_id: editDialog.record.getId(),
                                    own_model: editDialog.recordClass.getPhpClassName(),
                                    related_id: eventData.contact.getId(),
                                    related_model: 'Addressbook_Model_Contact',
                                }))
                            }
                            _.each(eventData.address, (value, key) => {
                                const fieldName = `${config.fieldPrefix || ''}${key}`
                                editDialog.getForm().findField(fieldName)?.setValue(value)
                            })

                        }
                    }
                })
            }
        }, config))
    }

    const getDialog = (config) => {
        return Tine.WindowFactory.getWindow({
            layout: 'fit',
            width: 400,
            height: 250,
            padding: '5px',
            modal: true,
            title: app.i18n._('Create from Contact ...'),
            items: new Tine.Tinebase.dialog.Dialog(_.defaultsDeep({
                xtype: 'form',
                layout: 'hbox',
                layoutConfig: {
                    padding: '5',
                    align: 'stretch'
                },
                listeners: { beforeapply: (eventData) => { return !!eventData.contact }},
                getEventData: async function (eventName) {
                    const contact = this.getForm().findField('contact').selectedRecord
                    return {
                        contact,
                        address: contact ? await Tine.Sales.contactToAdress(contact.getData()) : null,
                        create_link: this.getForm().findField('create_link').checked
                    }
                },
                items: [{
                    xtype: 'vpersona',
                    width: 100,
                    persona: 'question_input'
                }, {
                    xtype: 'columnform',
                    flex: 1,
                    labelAlign: 'top',
                    border: false,
                    items: [

                        [Tine.widgets.form.RecordPickerManager.get('Addressbook', 'Contact', {
                            fieldLabel: app.i18n._('Addressbook Contact'),
                            name: 'contact',
                            allowBlank: false,
                        })], [{
                            xtype: 'v-alert',
                            variant: 'info',
                            label: app.formatMessage('Contacts and sales addresses can be linked to each other so that changes to the contact automatically change the linked sales address and it is not possible to change the corresponding address fields directly in Sales.')
                        }], [{
                            xtype: 'checkbox',
                            checked: false,
                            boxLabel: app.formatMessage('Link sales address to given addressbook contact.'),
                            name: 'create_link',
                        }]
                    ]
                }]
            }, config))
        });
    }

    const action = getAction({})
    const medBtnStyle = { scale: 'medium', rowspan: 2, iconAlign: 'top'}
    Ext.ux.ItemRegistry.registerItem(`Sales-Customer-editDialog-Toolbar`, Ext.apply(new Ext.Button(action), medBtnStyle), 40)
    Ext.ux.ItemRegistry.registerItem(`Sales-Address-editDialog-Toolbar`, Ext.apply(new Ext.Button(action), medBtnStyle), 40)

});
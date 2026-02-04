/*
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import WebDAVCollectionPicker from "CloudAccount/WebDAVCollectionPicker";

Tine.Calendar.SyncContainerConfigEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    initComponent: function() {
        this.recordProxy = {
            saveRecord: this.saveSyncContainerConfig.createDelegate(this),
        }

        Tine.Calendar.SyncContainerConfigEditDialog.superclass.initComponent.call(this)

        this.getForm().findField('cloud_account_id').plugins[0].editDialogConfig = {
            hideFields: ['owner_id'],
            fixedFields: {
                owner_id: Tine.Tinebase.registry.get('currentAccount'),
                type: 'Tinebase_Model_CloudAccount_CalDAV'
            }
        }
    },

    initRecord: function() {
        this.record = this.recordClass.setFromJson(this.containerData?.xprops?.syncContainer || this.recordClass.getDefaultData())
        this.onRecordLoad.defer(10, this)
    },

    saveSyncContainerConfig: async function(record, options, additionalArguments) {
        try {
            const containerData = this.containerData?.xprops?.syncContainer ? this.containerData : {
                application_id: Tine.Tinebase.appMgr.get('Calendar').id,
                model: 'Calendar_Model_Event',
                name: record.get('external_container_name'),
                color: record.get('external_container_color'),
            }

            _.set(containerData, 'xprops.syncContainer', record.getData())

            if (! this.containerData?.xprops?.syncContainer) {
                // new calendar
                const personalOwnerId = Tine.Tinebase.container.pathIsPersonalNode(this.containerData.path)

                if(this.containerData.id === 'personal' && personalOwnerId === Tine.Tinebase.registry.get('currentAccount').accountId) {
                    containerData.type = this.containerData.id
                    containerData.owner_id = personalOwnerId
                } else if (this.containerData.id === 'shared') {
                    containerData.type = this.containerData.id
                } else {
                    throw new Error('Only own personal and shared containers can be created')
                }

            }
            const savedContainerData = await Tine.Tinebase_Container.saveContainer(containerData)
            this.fireEvent('containerSaved', savedContainerData)
            options.success.call(options.scope || window, record.constructor.setFromJson(savedContainerData.xprops.syncContainer))
        } catch (e) {
            options.failure.call(options.scope || window, e)
        }

    },

    checkStates () {
        if (this.loadRequest) {
            return _.delay(_.bind(this.checkStates, this), 250)
        }
        this.fields.calendar_path.setReadOnly(!this.fields.cloud_account_id.selectedRecord)
        this.fields.external_container_name.setReadOnly(!+this.fields.container_name_locally_overwritten.getValue())
        this.fields.external_container_color.setReadOnly(!+this.fields.container_color_locally_overwritten.getValue())
        this.fields.external_owner.setReadOnly(!+this.fields.external_owner_locally_overwritten.getValue())
    },

    getRecordFormItems: function() {
        const fields = this.fields = Tine.widgets.form.RecordForm.getFormFields(this.recordClass, (fieldName, config, fieldDefinition) => {
            switch (fieldName) {
                case 'calendar_path':
                    config.xtype = 'CloudAccount.WebDAVCollectionPicker'
                    config.type = 'VEVENT'
                    config.collectionName = 'Calendar'
                    config.listeners = config.listeners || {}
                    config.listeners.select = (combo, record, index) => {
                        fields['external_container_name'].setValue(record.get('name'))
                        fields['container_name_locally_overwritten'].setValue(false)
                        fields['external_container_color'].setValue(record.get('color'))
                        fields['container_color_locally_overwritten'].setValue(false)
                        fields['external_owner'].setValue(record.get('owner'))
                        fields['external_owner_locally_overwritten'].setValue(false)
                        fields['own_privilege_set'].setValue(record.get('acl'))
                    }
                    break;
            }

            if (fieldName.match(/overwritten$/)) {
                config.fieldLabel = '&nbsp;'
                config.hideLabel = false
            }
        })

        return [{
            region: 'center',
            xtype: 'columnform',
            columnLayoutConfig: {
                enableResponsive: true,
            },
            items: [
                [fields.cloud_account_id],
                [fields.calendar_path],
                [fields.external_container_name, fields.container_name_locally_overwritten],
                [fields.external_container_color, fields.container_color_locally_overwritten],
                [fields.external_owner, fields.external_owner_locally_overwritten],
                [fields.own_privilege_set],
                [fields.last_successful_sync, fields.last_failed_sync],
                [fields.sync_history],

            ]
        }]
    }
});

Promise.all([
    Tine.Tinebase.appMgr.isInitialised('Calendar'),
    Tine.Tinebase.ApplicationStarter.isInitialised(),
]).then(() => {
    // tweak to have virtual name field in generic dialog
    // const app = Tine.Tinebase.appMgr.get('Calendar');
    //
    // Tine.Calendar.Model.SyncContainerConfig.getModelConfiguration().fields.name = {
    //     appName: "Calendar",
    //     fieldName: "name",
    //     key: "name",
    //     label: "Name",
    //     type: "string",
    //     validators: { allowEmpty: true },
    //     uiconfig: { sorting: -10 }
    // }
    //
    // const fields = Tine.Calendar.Model.SyncContainerConfig.prototype.fields
    // if (fields.keys.indexOf('name') < 0) {
    //     fields.add(new Ext.data.Field({
    //         "name": 'name',
    //         "label": app.i18n._("Name"),
    //     }))
    // }

})


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
        Tine.Calendar.SyncContainerConfigEditDialog.superclass.initComponent.call(this)

        this.getForm().findField('cloud_account_id').plugins[0].editDialogConfig = {
            hideFields: ['owner_id'],
            fixedFields: {
                owner_id: Tine.Tinebase.registry.get('currentAccount'),
                type: 'Tinebase_Model_CloudAccount_CalDAV'
            }
        }
        // this.getForm().findField('cloud_account_id').on('select')
        // plugin in path also?
    }

});

// tweak to have virtual name field in generic dialog
Promise.all([
    Tine.Tinebase.appMgr.isInitialised('Calendar'),
    Tine.Tinebase.ApplicationStarter.isInitialised(),
]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Calendar');

    Tine.Calendar.Model.SyncContainerConfig.getModelConfiguration().fields.name = {
        appName: "Calendar",
        fieldName: "name",
        key: "name",
        label: "Name",
        type: "string",
        validators: { allowEmpty: true },
        uiconfig: { sorting: -10 }
    }

    const fields = Tine.Calendar.Model.SyncContainerConfig.prototype.fields
    if (fields.keys.indexOf('name') < 0) {
        fields.add(new Ext.data.Field({
            "name": 'name',
            "label": app.i18n._("Name"),
        }))
    }


    Tine.widgets.form.FieldManager.register('Calendar', 'SyncContainerConfig', 'calendar_path', {
        xtype: 'CloudAccount.WebDAVCollectionPicker',
        recordClass: 'Sales.DocumentPosition_Invoice',
    }, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG)
})


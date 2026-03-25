/*
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Tine.Tinebase.CloudAccountEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    initComponent: function() {
        Tine.Tinebase.CloudAccountEditDialog.superclass.initComponent.call(this)

        this.testBtn = Ext.create({
            xtype: 'button',
            columnWidth: 1,
            text: this.app.i18n._('Test Connection'),
            handler: this.testCloudAccountAccess,
            scope: this
        })

        this.recordForm.add(this.testBtn)
    },

    testCloudAccountAccess: async function(cmp, alert) {
        this.onRecordUpdate()

        cmp.setIconClass('x-btn-wait')
        cmp.setDisabled(1)
        try {
            const result = await Tine.Tinebase.testCloudAccountAccess(this.record.getData())
            if (alert) {
                await Ext.Msg.show({
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.INFO_SUCCESS,
                    title: i18n._('Cloud Account Access Possible'),
                    msg: i18n._('Cloud connect to cloud account with given data.')
                })
            }
            return true
        } catch (e) {
            console.error(e)
            if (alert) {
                await Ext.Msg.show({
                    buttons: Ext.Msg.OK,
                    icon: Ext.MessageBox.ERROR,
                    title: i18n._('Cloud Account Access Failure'),
                    msg: i18n._('Cloud not connect to cloud account with given data.') + '<br /><br />' +
                        this.app.i18n._hidden(e.message)
                })
            }
            return false;
        } finally {
            cmp.setIconClass('')
            cmp.setDisabled(0)
        }
    },

    onApplyChanges: function(closeWindow) {
        this.showLoadMask()

        this.testCloudAccountAccess(this.testBtn, false).then(async success => {
            if (success || await Ext.Msg.show({
                buttons: Ext.Msg.YESNO,
                icon: Ext.MessageBox.ERROR,
                title: i18n._('Cloud Account Access Failure'),
                msg: i18n._('Cloud not connect to cloud account with given data.') + '<br /><br />' +
                    '<b>' + this.app.i18n._('Save cloud account anyway?') + '</b>'
            }) === 'yes') {
                Tine.Tinebase.CloudAccountEditDialog.superclass.onApplyChanges.call(this, closeWindow)
            } else {
                this.hideLoadMask()
            }
        })
    }

});


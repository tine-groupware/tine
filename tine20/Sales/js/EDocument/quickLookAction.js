
import QuickLookPanel from './QuickLookPanel'
import FileLocation from 'Model/FileLocation'

Promise.all([
    Tine.Tinebase.appMgr.isInitialised('Tinebase'),
    Tine.Tinebase.appMgr.isInitialised('Filemanager'),
    Tine.Tinebase.appMgr.isInitialised('Sales')
]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Sales');

    const actionConfig = {
        app: app,
        allowMultiple: false,
        // disabled: true,
        hidden: true,
        iconCls: 'action_onlyoffice_edit',
        text: app.i18n._('View eDocument'),

        handler: function () {
            const record = this.selections[0];
            let recordData = record.toString();

            Tine.WindowFactory.getWindow({
                name: `EDocumentQuickLookPanel-${record.id}`,
                width: 800,
                height: 1024,
                contentPanelConstructor: 'Tine.Tinebase.dialog.Dialog',
                contentPanelConstructorConfig: {
                    layout: 'fit',
                    applyButtonText: null,
                    contentPanelConstructorInterceptor: function (config) {
                        config.app = Tine.Tinebase.appMgr.get('Sales')
                        config.cancelButtonText = config.app.i18n._('Close')
                        config.items = [{
                                xtype: 'Sales.EDocumentQuickLookPanel',
                                nodeRecord: record
                            }
                        ]
                    }
                },

            });
        },

        actionUpdater: async function (action, grants, records, isFilterSelect) {
            this.action = action;
            action.setVisible(false);

            const fileName = _.get(records, '[0].data.name') || _.get(records, '[0].data.filename');
            const fileExtension = Tine.Filemanager.Model.Node.getExtension(fileName);

            if (!isFilterSelect && records && records.length === 1 && ['xml', 'pdf'].indexOf(fileExtension) !== -1) {
                const fileLocation = FileLocation.create(records[0])

                action.setVisible(await Tine.Sales.isEDocumentFile(fileLocation))
            }

        }
    };

    // preview panel
    Ext.ux.ItemRegistry.registerItem('Tine-Filemanager-QuicklookPanel', new Ext.Action(_.assign(actionConfig, {scope: actionConfig})), 5);

    // fmail
    Ext.ux.ItemRegistry.registerItem('Tine.Felamimail.MailDetailPanel.AttachmentMenu', new Ext.Action(_.assign(actionConfig, {scope: actionConfig})), 15);

    // upload grids
    Ext.ux.ItemRegistry.registerItem('Tinebase-FileUploadGrid-Toolbar', new Ext.Action(_.assign(actionConfig, {scope: actionConfig})), 5);
})
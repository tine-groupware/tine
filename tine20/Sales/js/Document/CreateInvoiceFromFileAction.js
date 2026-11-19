/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * menu item of the add button of the invoice grid: creates a new invoice from a pdf (local or from the filemanager),
 * e.g. an invoice of another program, an offer, an order or a delivery note
 *
 * The server reads the file (Sales.createInvoiceFromFileLocation, docling + llm) and returns an unsaved invoice. It is
 * shown in a new invoice edit dialog - nothing is saved until the user saves the dialog. Only shown if the server can
 * parse such documents (registry canParseUnstructuredDocuments, see Sales_Model_Document_Invoice).
 *
 * NOTE: popup blockers only allow new windows right after a user action in the same window. So the edit dialog opens
 *       directly on the click and the file selection dialog is shown as Ext window inside of it (a popup opened
 *       from the file selection popup gets lost: that popup closes on apply, the new window then starts as main
 *       window). The upload of a local file and the request run in the edit dialog afterwards.
 */
Promise.all([Tine.Tinebase.appMgr.isInitialised('Sales'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Sales')
    if (!app.getRegistry().get('canParseUnstructuredDocuments')) return

    const recordClass = Tine.Tinebase.data.RecordMgr.get('Sales.Document_Invoice')

    /**
     * @param {Window} win window of the edit dialog
     * @return {Promise<Object|null>} apply event data of the file selection dialog (a File for local files), null
     *                                if the selection was cancelled
     */
    const selectFile = (win) => new Promise((resolve) => {
        const prototype = win.Tine.Tinebase.widgets.file.SelectionDialog.prototype
        const extWindow = win.Tine.WindowFactory.getWindow({
            windowType: 'Ext',
            modal: true,
            width: prototype.windowWidth,
            height: prototype.windowHeight,
            name: prototype.windowNamePrefix + Ext.id(),
            contentPanelConstructor: 'Tine.Tinebase.widgets.file.SelectionDialog',
            contentPanelConstructorConfig: {
                mode: 'source',
                constraint: /pdf$/i,
                allowMultiple: false,
                defaultLocationType: 'local',
                uploadMode: 'select',
                listeners: {
                    apply: (selection) => resolve(selection || null),
                    cancel: () => resolve(null)
                }
            }
        })
        extWindow.on('close', () => resolve(null))
    })

    /**
     * @param {Object} selection apply event data of the file selection dialog (a File for local files)
     * @param {Window} win window of the edit dialog (for the progress)
     * @return {Promise<Object>} file location for the api
     */
    const getFileLocation = async (selection, win) => {
        if (selection.fileLocation) {
            return selection.fileLocation
        }
        if (selection.fm_path) {
            return { model_name: 'Filemanager_Model_FileLocation', location: { fm_path: selection.fm_path } }
        }

        // local file: upload it first
        const upload = new win.Ext.ux.file.Upload({ file: selection })
        upload.on('uploadprogress', () => win.Ext.MessageBox.updateProgress(upload.uploadProgress / 100))
        upload.upload()
        await upload.promise
        const tempFile = upload.fileRecord.get('tempFile')
        return {
            model_name: 'Tinebase_Model_FileLocation_TempFile',
            location: Object.assign({ temp_file_id: tempFile.id }, tempFile)
        }
    }

    const action = new Ext.Action({
        text: app.i18n._('Create Invoice from PDF') + Tine.widgets.form.FieldManager.getDescriptionHTML(
            app.i18n._('Reads a PDF, e.g. an invoice of another program, an offer, an order or a delivery note, and proposes a new invoice from it. The data is recognized automatically (AI): please check it before saving.')),
        iconCls: 'SalesInvoiceFromFile',
        handler: (item, e) => {
            // the info icon explains the item (quicktip on hover/click)
            if (e?.getTarget?.('.field-description')) return

            const grid = app.getMainScreen()?.getCenterPanel('Document_Invoice')

            Tine.widgets.dialog.EditDialog.getConstructor(recordClass).openWindow({
                // refresh the invoice grid after saving (like GridPanel.onEditInNewWindow)
                listeners: grid ? { scope: grid, update: grid.onUpdateRecord } : {},
                contentPanelConstructorInterceptor: async (config, win) => {
                    // the file selection / MessageBox wait are shown - don't show the boot splash waitcycle in
                    // addition (like after login, see tineInit)
                    win.Ext.select('.tine-viewport-waitcycle').setDisplayed(false)

                    const selection = await selectFile(win)
                    if (!selection) {
                        // no file - no invoice, the dialog is never shown
                        config.window.close(true)
                        return new Promise(() => {})
                    }

                    const showWait = (msg, progress) => win.Ext.MessageBox.show({
                        title: i18n._('Please wait'),
                        msg,
                        icon: win.Ext.MessageBox.INFO_WAIT,
                        width: 500,
                        progress,
                        wait: !progress,
                        closable: false
                    })
                    try {
                        if (!selection.fileLocation && !selection.fm_path) {
                            showWait(app.i18n._('Uploading the file...'), true)
                        }
                        const fileLocation = await getFileLocation(selection, win)
                        showWait(app.i18n._('Reading the invoice from the file. This may take a few minutes...'))
                        const record = Tine.Tinebase.data.Record.setFromJson(await Tine.Sales.createInvoiceFromFileLocation(fileLocation), recordClass)
                        record.phantom = true
                        config.record = record.getData()
                        win.Ext.MessageBox.hide()
                    } catch (e) {
                        // the dialog opens with an empty invoice (like GridPanel.createNewRecord), the user can fill
                        // it in or close it. NOTE: without record the dialog creates one without json (BoilerplatePanel)
                        const record = Tine.Tinebase.data.Record.setFromJson(recordClass.getDefaultData(), recordClass)
                        record.phantom = true
                        record.setId(0)
                        config.record = record
                        win.Ext.MessageBox.show({
                            title: app.i18n._('Reading the invoice from the file failed'),
                            msg: Ext.util.Format.htmlEncode(e.message || e.data?.message || ''),
                            buttons: win.Ext.MessageBox.OK,
                            icon: win.Ext.MessageBox.ERROR_MILD
                        })
                    }
                }
            })
        }
    })

    Ext.ux.ItemRegistry.registerItem('Sales-Document_Invoice-GridPanel-AddButton', action, 10)
})

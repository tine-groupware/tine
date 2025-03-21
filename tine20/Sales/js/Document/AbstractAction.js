/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const abstractAction = Ext.extend(Ext.Action, {


    /**
     * @param config
     *
     * maskMsg: 'Please Wait...',
     * documentType: '', // one of Offer|Order|Delivery|Invoice
     *
     */
    constructor: function (config) {
        config.app = Tine.Tinebase.appMgr.get('Sales')
        config.recordClass = Tine.Tinebase.data.RecordMgr.get(`Sales.Document_${config.documentType}`)
        config.statusFieldName = `${config.documentType.toLowerCase()}_status`
        config.statusDef = Tine.Tinebase.widgets.keyfield.getDefinitionFromMC(config.recordClass, config.statusFieldName)

        Ext.Action.prototype.constructor.call(this, config);
    },
    // NOTE: action updater is not executed in action but in component of the action
    //       so it does not work to define it here
    // actionUpdater(action, grants, records, isFilterSelect, filteredContainers) {
    //     let enabled = records.length === 1 // no batch processing yet, needs a robust concept!
    //     action.setDisabled(!enabled)
    //     action.baseAction.setDisabled(!enabled) // WTF?
    // },
    handler: async function(cmp) {
        // this.recordsName = recordClass.getRecordsName()
        this.selections = [...this.initialConfig.selections]
        this.errors = []
        this.errorMsgs = []
        this.editDialog = cmp.editDialog || cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog || c instanceof Tine.Tinebase.dialog.Dialog})
        this.maskEl = cmp.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog || c instanceof Tine.widgets.MainScreen || c instanceof  Tine.Tinebase.dialog.Dialog}).getEl()
        this.mask = new Ext.LoadMask(this.maskEl, { msg: this.maskMsg || this.app.i18n._('Please wait...') })

        if (this.statusDef && this.statusFieldName) {
            this.unbooked = this.selections.reduce((unbooked, record) => {
                record.noProxy = true // kill grid autoSave
                const status = record.get(this.statusFieldName)
                return unbooked.concat(this.statusDef.records.find((r) => { return r.id === status })?.booked ? [] : [record])
            }, [])
        }
    }
});

export default abstractAction
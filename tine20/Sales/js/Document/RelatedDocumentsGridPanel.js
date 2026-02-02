/*
 * tine-groupware
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const registrations = {
    Supplier: {
        PurchaseInvoice: {}
    },
    Customer: {
        Invoice: {},
        Delivery: {},
        Order: {},
        Offer: {}
    },
    Debitor: {
        Invoice: {},
        Delivery: {},
        Order: {},
        Offer: {}
    }
}

Promise.all([Tine.Tinebase.appMgr.isInitialised('Sales'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {
    const app = Tine.Tinebase.appMgr.get('Sales')

    _.forEach(Object.keys(registrations), ctx => {
        // const contextRecordClass = Tine.Tinebase.data.RecordMgr.get(`Sales.${context}`)

        _.forEach(Object.keys(registrations[ctx]), docType => {
            // console.error(ctx, docType)
            const recordClass = Tine.Tinebase.data.RecordMgr.get(`Sales.Document_${docType}`)
            const gridPanel = Ext.extend(Tine.Sales[`Document_${docType}GridPanel`], {
                title: recordClass.getRecordsName(),
                hasQuickSearchFilterToolbarPlugin: false,
                stateIdSuffix: `-of-${ctx}`,
                filterConfig: { hidden: true, maxHeight: 0 },
                allowCreateNew: false,
                initComponent() {
                    this.bbar = []
                    Tine.Sales[`Document_${docType}GridPanel`].prototype.initComponent.apply(this, arguments)

                    this.filterToolbar.onBeforeLoad = _.wrap(_.bind(this.filterToolbar.onBeforeLoad, this.filterToolbar), _.bind(this.onBeforeLoad, this))
                    this.bottomToolbar.add([/*this.action_addInNewWindow,*/ this.action_editInNewWindow, this.action_deleteRecord])
                },
                setOwnerCt: function(ct) {
                    this.ownerCt = ct;

                    if (! this.editDialog) {
                        this.editDialog = this.findParentBy(function (c) {
                            return c instanceof Tine.widgets.dialog.EditDialog
                        });
                    }

                    this.ownerCt[(!this.editDialog.record.phantom && !this.editDialog.denormalizationRecordClass ? 'un' : '') +'hideTabStripItem'](this);
                },
                onBeforeLoad(func, store, options) {

                    const result = func(store, options)
                    const filters = _.get(options.params.filter, '[0].filters[0].filters')

                    const ctxField = `${_.toLower(ctx)}_id`
                    const defaultCtxFilter = { field: ctxField, operator: "definedBy?condition=and&setOperator=oneOf", value: [{ field: ":original_id", operator: "equals", value: this.editDialog.record.id }]}
                    const ctxFilter = _.find(filters, { field: ctxField })
                    ctxFilter ? Object.assign(ctxFilter, defaultCtxFilter): filters.push(defaultCtxFilter)

                }
            })

            Ext.ux.ItemRegistry.registerItem(`Sales-${ctx}-EditDialog-TabPanel`, gridPanel, 20)
        })
    })
})
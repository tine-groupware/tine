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
    Contract: {
        Invoice: {},
        Delivery: {},
        Order: {},
        Offer: {}
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
        const gridPanels = []

        _.forEach(Object.keys(registrations[ctx]), docType => {
            // console.error(ctx, docType)
            const title = Tine.Tinebase.data.RecordMgr.get(`Sales.Document_${docType}`).getRecordsName()
            _.forEach([`_${docType}`, `Position_${docType}`], modelNamePart => {
                const gridPanel = ({ title,modelNamePart,
                    xtype: `Sales.Document${modelNamePart}GridPanel`,
                    isPosition: modelNamePart.match(/^Position_/),
                    hasQuickSearchFilterToolbarPlugin: false,
                    stateIdSuffix: `-of-${ctx}`,
                    filterConfig: { hidden: true, maxHeight: 0 },
                    allowCreateNew: false,
                    hideColumns: ['customer_id', 'contract_id', 'Contraxt'],
                    initComponent: function() {
                        this.bbar = []
                        Tine.Sales[`Document${modelNamePart}GridPanel`].prototype.initComponent.apply(this, arguments)

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

                        this.ownerCt[(!this.editDialog.record.phantom && !this.editDialog.denormalizationRecordClass && !this.isPosition? 'un' : '') +'hideTabStripItem'](this);
                    },
                    onBeforeLoad(func, store, options) {
                        const result = func(store, options)
                        const filters = _.get(options.params.filter, '[0].filters[0].filters')

                        let ctxField = `${_.toLower(ctx)}_id`
                        let defaultCtxFilter = ctx === 'Contract' ?
                            { field: ctxField, operator: 'equals', value: this.editDialog.record.id } :
                            { field: ctxField, operator: "definedBy?condition=and&setOperator=oneOf", value: [
                                { field: ":original_id", operator: "equals", value: this.editDialog.record.id }]}
                        if (this.isPosition) {
                            ctxField = 'document_id'
                            defaultCtxFilter = {
                                "field": ctxField,
                                "operator": "definedBy?condition=and&setOperator=oneOf",
                                "value": [ defaultCtxFilter ]
                            }
                        }

                        const ctxFilter = _.find(filters, { field: ctxField })
                        ctxFilter ? Object.assign(ctxFilter, defaultCtxFilter): filters.push(defaultCtxFilter)
                    }
                })

                gridPanels.push(gridPanel)
            })
        })
        if (gridPanels.length === 1) {
            Ext.ux.ItemRegistry.registerItem(`Sales-${ctx}-EditDialog-TabPanel`, gridPanels[0], 20)
        } else {
            const documentsPanel = {
                title: app.i18n._('Documents'),
                xtype: 'tabpanel',
                pills: true,
                activeTab: 0,
                stateful: true,
                stateId: `Sales.$s{ctx}.RelatedDocumentPanel`,
                items: gridPanels,
                plugins: [{
                    init: function(cmp) {
                        cmp.on('render', () => {
                            const container = cmp.header.createChild({tag: 'div', style: 'width: 150px; height: 22px; position: absolute; top: 0px; right: 0px;'});
                            new Ext.form.Checkbox({boxLabel: app.i18n._('Positions'), name: 'showPositions', switch: true, renderTo: container, listeners: { check: cb => {
                                const activeIdx = cmp.items.indexOf(cmp.getActiveTab())
                                const showPositions = !(activeIdx%2)
                                cmp.setActiveTab(activeIdx + (1 * showPositions? 1 : -1))
                                cmp.items.each(grid => grid.ownerCt[(showPositions ^ (!grid.editDialog.record.phantom && !grid.editDialog.denormalizationRecordClass && !grid.isPosition) ? 'un' : '') +'hideTabStripItem'](grid));
                            }}})
                        })
                    }
                }]
            }
            Ext.ux.ItemRegistry.registerItem(`Sales-${ctx}-EditDialog-TabPanel`, documentsPanel, 20)
        }
    })
})
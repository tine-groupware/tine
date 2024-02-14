// reconfigure position grids to have a customer column
Promise.all([Tine.Tinebase.appMgr.isInitialised('Sales'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {

    const app = Tine.Tinebase.appMgr.get('Sales');
    ['Offer', 'Order', 'Delivery', 'Invoice'].forEach((documentName) => {
        const origFn = Tine.Sales[`DocumentPosition_${documentName}GridPanel`].prototype.initGenericColumnModel;
        Tine.Sales[`DocumentPosition_${documentName}GridPanel`].prototype.initGenericColumnModel = function() {
            const customerRenderer = Tine.widgets.grid.RendererManager.getByDataType('Sales', `Document_${documentName}`, 'customer_id');
            const categoryRenderer = Tine.widgets.grid.RendererManager.getByDataType('Sales', `Document_${documentName}`, 'document_category');
            const gridConfig = origFn.call(this);
            gridConfig.cm.config.splice(0, 0, new Ext.grid.Column({
                id: 'customer_id',
                header: app.i18n._("Customer"),
                width: 130,
                dataIndex: 'document_id',
                hidden: false,
                sortable: false,
                renderer: (v) => {
                    return customerRenderer(v.customer_id, {}, v);
                }
            }), new Ext.grid.Column({
                id: 'document_category',
                header: app.i18n._("Category"),
                dataIndex: 'document_id',
                hidden: true,
                sortable: false,
                renderer: (v) => {
                    return categoryRenderer(v.document_category, {}, v);
                }
            }));
            return gridConfig;
        };
    });
});
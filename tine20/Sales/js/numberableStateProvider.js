

Promise.all([Tine.Tinebase.appMgr.isInitialised('Sales'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {

    Tine.Sales.DebitorEditDialog.registerCheckStateProvider('number', function (editDialog, record) {
        const configsAvailable = _.get(record.constructor.getField('number'), 'fieldDefinition.config.configsAvailable', []);
        const bucket_key = `Sales_Model_Debitor#number#${record.get('division_id')}`;
        const bucketConfig = _.find(configsAvailable, { bucket_key });
        if (bucketConfig) {
            this.setDisabled(! bucketConfig.editable);
        }
    });

    ['Offer', 'Order', 'Delivery', 'Invoice'].forEach((type) => {
        ['document_number', 'document_proforma_number'].forEach((fieldName) => {
            Tine.Sales[`Document_${type}EditDialog`].registerCheckStateProvider(fieldName, function (editDialog, record) {
                const configsAvailable = _.get(record.constructor.getField(fieldName), 'fieldDefinition.config.configsAvailable', []);
                const division = _.get(editDialog.getForm().findField('document_category'), 'selectedRecord.data.division_id', '404');
                const bucket_key = `Sales_Model_Document_${type}#${fieldName}#${_.get(division, 'id', division)}`;
                const bucketConfig = _.find(configsAvailable, { bucket_key });
                if (bucketConfig) {
                    this.setDisabled(! bucketConfig.editable);
                }
            });
        });
    })

});
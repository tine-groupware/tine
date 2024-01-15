/*
 * Tine 2.0
 * Sales combo box and store
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Sales');

/**
 * Address selection combo box
 *
 * @namespace   Tine.Sales
 * @class       Tine.Sales.AddressSearchCombo
 * @extends     Ext.form.ComboBox
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.AddressSearchCombo
 */
Tine.Sales.AddressSearchCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {

    minListWidth: 400,
    sortBy: 'locality',
    recordClass: 'Sales.Model.Document_Address',
    resizable: true,
    // mode: 'local',

    initComponent: function() {

        Tine.Sales.AddressSearchCombo.superclass.initComponent.call(this);

        this.on('beforeselect', async (field, adr, idx) => {
            if (adr.get('type') === 'postal') {
                const form = this.findParentBy((c) => {return c instanceof Ext.form.FormPanel}).getForm();
                const category = form.findField('document_category').selectedRecord;
                const division = category?.data?.division_id;
                const selectedCustomer = form.findField('customer_id').selectedRecord;
                const customer = selectedCustomer.json?.original_id ? await Tine.Sales.getCustomer(selectedCustomer.json.original_id) : selectedCustomer.data;
                const debitors = _.filter(customer.debitors, (deb) => { return _.get(deb, 'division_id.id', deb) === division?.id});
                if (debitors) {
                    const debitor = debitors.length > 1 ? await Tine.widgets.dialog.MultiOptionsDialog.getOption({
                        title: this.app.i18n._('Select Debitor'),
                        questionText: this.app.i18n._('Please select debitor') + '</b><br>',
                        height: 150,
                        allowCancel: false,
                        options: debitors.map((debitor) => { return {
                            text: Tine.Tinebase.data.Record.setFromJson(debitor, Tine.Sales.Model.Debitor).getTitle(),
                            value: debitor,
                            name: debitor.id
                        }})
                    }) : debitors[0];

                    adr.data.debitor_id = _.assign({... debitor}, {billing: null, delivery: null});
                }

            }
        }, this)
    },
    // NOTE: customer selection logic is here because customer is no select combo in old invoices module
    checkState: function(editDialog, record) {
        const mc = editDialog?.recordClass?.getModelConfiguration();
        const type = this.type || _.get(mc, `fields.${this.fieldName}.config.type`, 'billing');

        const customerField = editDialog.getForm().findField('customer_id') || editDialog.getForm().findField('customer')
        const customer = customerField?.selectedRecord;
        const customer_id = customer?.json?.original_id || customer?.id;

        const category = editDialog.getForm().findField('document_category')?.selectedRecord;
        const isLegacy = !!editDialog.getForm().findField('contract');
        const division = category?.data?.division_id;

        this.setDisabled(!customer_id);

        if ((this.customer_id && this.customer_id !== customer_id) || (this.division_id && this.division_id !== division.id)) {
            // handle customer changes
            this.clearValue();
        }
        if (customer_id && customer && !this.selectedRecord) {
            let typeRecord = null;
            if (type === 'postal') {
                // is this case used somewhere???
                typeRecord = customer.data?.postal;
            } else {
                const debitors = _.filter(customer.data.debitors, (deb) => { return isLegacy || _.get(deb, 'division_id.id', deb) === division?.id});
                const typeRecords = _.flatten(_.each(_.map(debitors, type), (addrs, idx) => {
                    // have postal addr in each debitor
                    addrs = addrs.concat(customer?.data?.postal ? customer.data.postal : []);
                    // place debitor reference in each addr
                    _.each(addrs, (addr) => {addr.debitor_id = _.assign({... debitors[idx]}, {billing: null, delivery: null})})
                }));
                typeRecord = typeRecords[0];
            }
            if (typeRecord) {
                const address = Tine.Tinebase.data.Record.setFromJson(typeRecord, this.recordClass);
                this.setValue(address);
                this.fireEvent('select', this, address);
            }
        }
        this.customer_id = customer_id;
        this.division_id = division?.id;

        if (! customer_id) {
            this.clearValue();
        } else {
            this.lastQuery = null;
            this.additionalFilters = [{ condition: 'OR', filters: [
                {field: 'customer_id', operator: 'equals', value: customer_id},
                {field: 'debitor_id', operator: 'definedBy', value: [{
                    field: 'customer_id', operator: 'equals', value: customer_id
                }]}
            ]}];

            // always get postal (debitor gets added in beforeloadrecords)
            const typeFilter = [{field: 'type', operator: 'equals', value: 'postal' }];
            this.additionalFilters.push({ condition: 'OR', filters: typeFilter });

            if (type !== 'postal') {
                typeFilter.push({ condition: 'AND', filters: [
                    {field: 'type', operator: 'equals', value: type}
                ] });
                if (division) {
                    typeFilter[1].filters.push({
                        field: 'debitor_id', operator: 'definedBy', value: [{
                            field: 'division_id', operator: 'equals', value: division.id
                        }]
                    });
                }
            }
        }
    }
});

Tine.widgets.form.RecordPickerManager.register('Sales', 'Address', Ext.extend(Tine.Sales.AddressSearchCombo, { recordClass: 'Sales.Model.Address' }));
Tine.widgets.form.RecordPickerManager.register('Sales', 'Document_Address', Tine.Sales.AddressSearchCombo);

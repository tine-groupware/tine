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
                const category = form.findField('document_category')?.selectedRecord;
                const division = category?.data?.division_id;
                const selectedCustomer = form.findField('customer_id')?.selectedRecord;
                if (!selectedCustomer) return;
                const customer = selectedCustomer.json?.original_id ? await Tine.Sales.getCustomer(selectedCustomer.json.original_id) : selectedCustomer.data;
                const debitors = _.filter(customer.debitors, (deb) => { return _.get(deb, 'division_id.id', deb) === division?.id});
                if (debitors) {
                    const debitor = debitors.length > 1 ? await Tine.widgets.dialog.MultiOptionsDialog.getOption({
                        title: this.app.i18n._('Select Debitor'),
                        questionText: this.app.i18n._('Please select a debitor'),
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
            const debitors = _.cloneDeep(_.filter(customer.data.debitors, (deb) => { return isLegacy || _.get(deb, 'division_id.id', deb) === division?.id}));
            let typeRecord = null;
            
            if (type === 'postal') {
                // order/offer recipients
                typeRecord = _.cloneDeep(customer.data?.postal);
                typeRecord.debitor_id = _.assign({... debitors[0]}, {billing: null, delivery: null});
            } else {
                const addrs = _.map(debitors, (debitor) => { return debitor[type].concat(type === 'delivery' ? debitor['billing'] : []) });
                const typeRecords = _.flatten(_.each(addrs, (addrs, idx) => {
                    // have postal addr in each debitor
                    customer?.data?.postal ? addrs.push( _.cloneDeep(customer.data.postal)) : null;
                    // place debitor reference in each addr
                    _.each(addrs, (addr) => {addr.debitor_id = _.assign({... debitors[idx]}, {billing: null, delivery: null})})
                }));
                const order = ['delivery', 'billing', 'postal']
                typeRecords.sort((a,b) => {
                    return order.indexOf(a.type) - order.indexOf(b.type) || b.creation_time > a.creation_time ? 1 : -1;
                })
                // @TODO check if debitor is right?
                //       in orders debitor of 'delivery', 'billing' should match receipient?
                //       in all documents debitor should match precursor receipient?
                typeRecord = typeRecords[0];

                if (!typeRecord && !division) {
                    const addrs =  _.flatten(_.map(_.cloneDeep(customer.data.debitors), (debitor) => {
                        return debitor['billing'];
                    }));
                    typeRecord = addrs[0];
                }
            }
            if (typeRecord && !this.isExplicitlyCleared) {
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
                    {field: 'type', operator: 'in', value: [type].concat(type === 'delivery' ? ['billing'] : [])}
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
    },

    setValue: function () {
        Tine.Sales.AddressSearchCombo.superclass.setValue.apply(this, arguments);
        this.isExplicitlyCleared = false;
    },

    onTrigger1Click: function () {
        Tine.Sales.AddressSearchCombo.superclass.onTrigger1Click.apply(this, arguments);
        this.isExplicitlyCleared = true;
    }
});

Tine.widgets.form.RecordPickerManager.register('Sales', 'Address', Ext.extend(Tine.Sales.AddressSearchCombo, { recordClass: 'Sales.Model.Address' }));
Tine.widgets.form.RecordPickerManager.register('Sales', 'Document_Address', Tine.Sales.AddressSearchCombo);

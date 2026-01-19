import AbstractGridPanel from "../../DocumentPosition/AbstractGridPanel";
import AbstractMixin from "../../Model/DocumentPosition/AbstractMixin";

const PositionGridPanel = Ext.extend(AbstractGridPanel, {
    quickaddMode: 'sorted',
    recordClass: 'Sales.DocumentPosition_PurchaseInvoice',
    fieldName: 'positions',
    dataIndex: 'positions',

    onAfterEditPosition(e) {
        if (! e.field) {
            // new product
            AbstractMixin.computePrice.call(e.record)
        }

        const originalValues = {... e.record.data}

        originalValues[e.field] = e.originalValue

        if (e.record.sumMode) return; // let's see if it's needed...

        const toFixed = Tine.Sales.Model.DocumentPosition_PurchaseInvoice.toFixed

        if (e.field) {
            let f = 'quantity'
            switch (e.field) {
                case 'quantity':
                    f = 'unit_price'
                case 'unit_price':
                    if (toFixed((e.record.get(f) || 0) * e.originalValue) !== (e.record.get('position_price') || 0)) {
                        break
                    }
                    e.record.set('position_price', toFixed((e.record.get(f) || 0) * (e.value || 0)))
                case 'position_price':
                    if (e.record.get('position_discount_type') === 'SUM') {
                        e.record.set('position_discount_percentage', e.record.get('position_discount_sum') / e.record.get('position_price') * 100)
                    } else if (originalValues.position_price / 100 * e.record.get('position_discount_percentage') === e.record.get('position_discount_sum')) {
                        e.record.set('position_discount_sum',  e.record.get('position_price') / 100 * e.record.get('position_discount_percentage'))
                    } else {
                        break
                    }

                case 'position_discount_sum':
                case 'position_discount_percentage': // (not in field list)
                case 'sales_tax_rate':
                case 'unit_price_type':
                    const total = e.record.get('position_price') - e.record.get('position_discount_sum')
                    const originalTotal = originalValues.position_price - originalValues.position_discount_sum
                    if (e.record.get('unit_price_type') === 'gross') {
                        if (toFixed(originalTotal) === (originalValues.gross_price || 0)) {
                            e.record.set('gross_price', toFixed(total))
                            if (toFixed(originalValues.gross_price - originalValues.gross_price * 100 / (100 + originalValues.sales_tax_rate)) === (originalValues.sales_tax || 0)) {
                                const tax = total - total * 100 / (100 + e.record.get('sales_tax_rate'))
                                e.record.set('sales_tax', toFixed(tax))
                                e.record.set('net_price', toFixed(total - tax))
                            }
                        }
                    } else {
                        if ((originalTotal || 0) === (originalValues.net_price || 0)) {
                            e.record.set('net_price', toFixed(total))
                            if (toFixed(originalTotal / 100 * originalValues.sales_tax_rate) === (originalValues.sales_tax || 0)) {
                                const tax = toFixed(total / 100 *  e.record.get('sales_tax_rate'))
                                e.record.set('sales_tax', tax)
                                e.record.set('gross_price', toFixed(total + tax))
                            }
                        }
                    }
                    break;
                case 'gross_price':
                    if (!e.record.get('position_price') && !e.record.get('unit_price')) {
                        e.record.sumMode = e.field
                        const total = e.record.get('gross_price')
                        const tax = total - total * 100 / (100 + e.record.get('sales_tax_rate'))
                        e.record.set('sales_tax', toFixed(tax))
                        e.record.set('net_price', toFixed(total - tax))

                        if (e.record.get('unit_price_type') === 'gross') {
                            e.record.set('position_price', toFixed(total))
                            e.record.set('unit_price', toFixed(total/e.record.get('quantity')))
                        } else {
                            e.record.set('position_price', toFixed(total - tax))
                            e.record.set('unit_price', toFixed((total - tax)/e.record.get('quantity')))
                        }
                    }
                    break;
                case 'net_price':
                    if (!e.record.get('position_price') && !e.record.get('unit_price')) {
                        e.record.sumMode = e.field
                        const total = e.record.get('net_price')
                        const tax = toFixed(total / 100 *  e.record.get('sales_tax_rate'))
                        e.record.set('sales_tax', toFixed(tax))
                        e.record.set('gross_price', toFixed(total + tax))

                        if (e.record.get('unit_price_type') === 'gross') {
                            e.record.set('position_price', toFixed(total + tax))
                            e.record.set('unit_price', toFixed((total + tax)/e.record.get('quantity')))
                        } else {
                            e.record.set('position_price', toFixed(total))
                            e.record.set('unit_price', toFixed((total)/e.record.get('quantity')))
                        }
                        break;
                    }
            }
        }

        this.fireEvent('change', this)
    }
})
Ext.reg('sales-document-position-purchase-invoice-gridpanel', PositionGridPanel)

export default PositionGridPanel
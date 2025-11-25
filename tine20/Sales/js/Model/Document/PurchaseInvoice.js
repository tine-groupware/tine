/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Record from 'data/Record'

const PurchaseInvoice = Record.create([], {
    appName: 'Sales',
    modelName: 'Document_PurchaseInvoice'
})

PurchaseInvoice.toFixed = Tine.Sales.Model.Document_InvoiceMixin.statics.toFixed

export default PurchaseInvoice
/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Record from 'data/Record'

const Credit = Record.create([], {
    appName: 'Sales',
    modelName: 'Document_Credit'
})

Credit.toFixed = Tine.Sales.Model.Document_InvoiceMixin.statics.toFixed

export default Credit
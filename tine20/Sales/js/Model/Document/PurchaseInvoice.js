/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Record from 'data/Record'
import AbstractMixin from './AbstractMixin'
import Delivery from "./Delivery";

const PurchaseInvoice = Record.create([], Object.assign({}, AbstractMixin, {
    appName: 'Sales',
    modelName: 'Document_PurchaseInvoice'
}))

Object.assign(PurchaseInvoice, AbstractMixin.statics)

export default PurchaseInvoice
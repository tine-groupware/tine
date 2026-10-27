/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import Record from 'data/Record'
import AbstractMixin from './AbstractMixin'
import Delivery from "../Document/Delivery";

const PurchaseInvoice = Record.create([], Object.assign({}, AbstractMixin, {
    appName: 'Sales',
    modelName: 'DocumentPosition_PurchaseInvoice',

    computePrice() {
        if (this.get('product_id')?.id) {
            // super.computePrice() // but what if numbers differ here also?
        } else {
            // do nothing, see forms
        }

    }
}))

Object.assign(PurchaseInvoice, AbstractMixin.statics)

export default PurchaseInvoice

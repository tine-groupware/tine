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

const Order = Record.create([], Object.assign({}, AbstractMixin, {
    appName: 'Sales',
    modelName: 'DocumentPosition_Order'
}))

Object.assign(Order, AbstractMixin.statics)

export default Order


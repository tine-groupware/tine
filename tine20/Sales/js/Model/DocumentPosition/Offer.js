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

const Offer = Record.create([], Object.assign({}, AbstractMixin, {
    appName: 'Sales',
    modelName: 'DocumentPosition_Offer'
}))

Object.assign(Offer, AbstractMixin.statics)

export default Offer


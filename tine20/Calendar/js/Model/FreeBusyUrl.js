/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*global Ext, Tine*/

import Record from 'data/Record'
import common from 'common'

const FreeBusyUrl = Record.create([], {
    appName: 'Calendar',
    modelName: 'FreeBusyUrl',

    getTitle() {
        return this.getUrl()
    },
    getUrl() {
        return common.getUrl() + 'Calendar/freebusy/' + this.id;
    },
    set(k, v) {
        Tine.Tinebase.data.Record.prototype.set.call(this, k, v)
        if (k === 'id') {
            this.data.url = this.getUrl()
        }

        return this
    }

})

export default FreeBusyUrl
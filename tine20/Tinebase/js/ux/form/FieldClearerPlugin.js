/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import FieldTriggerPlugin from "./FieldTriggerPlugin"

class FieldClearerPlugin extends FieldTriggerPlugin {
    triggerClass = 'x-form-clear-trigger'
    hideOnEmptyValue = true
    qtip= 'Clear Value' // _('Clear Value')
    visible = false

    async init (field) {
        await super.init(field)
    }

    onTriggerClick () {
        this.field.clearValue?.() || this.field.setValue('')
    }
}

Ext.preg('ux.fieldclearerplugin', FieldClearerPlugin);

export default FieldClearerPlugin

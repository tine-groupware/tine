/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import FieldTriggerPlugin from "./FieldTriggerPlugin"

class FieldClipboardPlugin extends FieldTriggerPlugin {
    triggerClass = 'clipboard'
    hideOnEmptyValue = true

    async init (field) {
        await super.init(field)
    }

    onTriggerClick () {
        this.field.el.dom.select()
        document.execCommand("copy")
        this.field.el.dom.setSelectionRange(0, 0)
        Ext.ux.Notification.show(i18n._('Copied to clipboard'), window.formatMessage('"{value}" was copied to clipboard', {value: Ext.util.Format.htmlEncode(this.field.getValue())}));
    }
}

Ext.preg('ux.fieldclipboardplugin', FieldClipboardPlugin);

export default FieldClipboardPlugin

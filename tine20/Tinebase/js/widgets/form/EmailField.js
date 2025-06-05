/*
 * Tine 2.0
 * 
 * @package     Ext
 * @subpackage  ux
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

import FieldTriggerPlugin from "ux/form/FieldTriggerPlugin";
import FieldClipboardPlugin from 'ux/form/FieldClipboardPlugin'

Ext.ns('Tine.Tinebase.widgets.form');

/**
 * </code></pre>
 *
 * @namespace   Tine.Tinebase.widgets.form
 * @class       Tine.Tinebase.widgets.form.MirrorTextField
 * @extends     Ext.ux.form.IconTextField
 */
Tine.Tinebase.widgets.form.EmailField = Ext.extend(Ext.form.TextField, {
    /**
     * @private
     */
    initComponent: function(){
        this.vtype = 'email';
        this.plugins = this.plugins || [];
        this.plugins.push(new FieldClipboardPlugin());
        this.plugins.push(new FieldTriggerPlugin({
            hideOnEmptyValue: true,
            hideOnInvalidValue: true,
            triggerClass: 'action_composeEmail',
            qtip: i18n._('Compose Email'),
            onTriggerClick: (e) => {
                if (Tine.Felamimail && !e.altKey && !e.ctrlKey) {
                    const record = new Tine.Felamimail.Model.Message(Object.assign({to: this.getValue()}, Tine.Felamimail.Model.Message.getDefaultData()), Tine.Tinebase.data.Record.generateUID());
                    Tine.Felamimail.MessageEditDialog.openWindow({
                        record: record
                    });
                } else {
                    window.open('mailto:' + this.getValue(), '_blank');
                }
            }
        }));
        Tine.Tinebase.widgets.form.EmailField.superclass.initComponent.call(this);
    }
    
});
Ext.reg('tw-emailField', Tine.Tinebase.widgets.form.EmailField);

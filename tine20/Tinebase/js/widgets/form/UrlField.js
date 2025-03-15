/*
 * Tine 2.0
 * 
 * @package     Ext
 * @subpackage  ux
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2026 Metaways Infosystems GmbH (http://www.metaways.de)
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
Tine.Tinebase.widgets.form.UrlField = Ext.extend(Ext.form.TextField, {
    /**
     * @private
     */
    initComponent: function(){
        this.vtype = 'url';
        this.plugins = this.plugins || [];
        this.plugins.push(new FieldClipboardPlugin());
        this.plugins.push(new FieldTriggerPlugin({
            hideOnEmptyValue: true,
            hideOnInvalidValue: true,
            triggerClass: 'action_open_link',
            qtip: i18n._('Open link in new window'),
            onTriggerClick: () => {
                if (this.isValid()) {
                    window.open(this.getValue(), '_blank');
                }
            }
        }));
        Tine.Tinebase.widgets.form.UrlField.superclass.initComponent.call(this);
        this.on('focus', this.onFieldFocus, this);
        this.on('blur', this.onFieldBlur, this);
    },
    
    onFieldFocus: function (el) {
        if (! this.getValue()) {
            this.setValue('https://');
            this.selectText.defer(100, this, [8, 11]);
        }
        this.focus();
    },
    onFieldBlur: function (el) {
        const value = this.getValue();
        if (!value) return;
        if (value === 'https://') {
            this.setValue(null);
        }
        if (value.indexOf('https://http://') === 0 || value.indexOf('https://https://') === 0) {
            this.setValue(this.getValue().substr(7));
        }
    }
});
Ext.reg('urlfield', Tine.Tinebase.widgets.form.UrlField);

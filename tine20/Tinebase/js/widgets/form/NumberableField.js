/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const NumberableField = Ext.extend(Ext.form.TextField, {
    initComponent: function() {

        NumberableField.superclass.initComponent.call(this);
    },

    onFocus: function() {
        if (!this.readOnly && !this.getValue()) {
            this.setValue(this.prefix)
        }
        NumberableField.superclass.onFocus.apply(this, arguments);
    },

    onBlur: function() {
        if (!this.readOnly && this.getValue()===this.prefix) {
            this.setValue(null)
        }
        NumberableField.superclass.onBlur.apply(this, arguments);
    },

    validateValue: function(value) {
        if (NumberableField.superclass.validateValue.apply(this, arguments) === false) {
            return false
        }
        if(!this.readOnly && value && !String(value).match(new RegExp('^' + _.escapeRegExp(this.prefix) + '.*'))) {
            this.markInvalid(window.formatMessage('Value needs to start with { prefix }', { prefix: this.prefix }))
            return false
        }
        return true
    }

});

Ext.reg('numberablefield', NumberableField);

export default NumberableField;
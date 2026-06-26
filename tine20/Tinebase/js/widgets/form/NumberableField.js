/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */
const configProvider = {};

const NumberableField = Ext.extend(Ext.form.TextField, {
    initComponent: function() {
        NumberableField.superclass.initComponent.call(this);
    },

    getConfig: function() {
        const key = [this.appName, this.modelName, this.fieldName].join('-')
        const editDialog = this.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog})
        const record = editDialog.record;
        if (configProvider[key]) {
            return configProvider[key](editDialog, record)
        } else {
            if (this.configsAvailable.length > 1) {
                console.error('FIXME: no configProvider registered for NumberableField ' + key)
            }
            return this.configsAvailable[0]
        }
    },

    onFocus: function() {
        if (!this.readOnly && this.emptyValue === this.getValue()) {
            const config = this.getConfig()
            this.setValue(config.prefix)
        }
        NumberableField.superclass.onFocus.apply(this, arguments);
    },

    onBlur: function() {
        const config = this.getConfig()

        if (!this.readOnly && this.getValue()===config.prefix) {
            this.setValue(this.emptyValue)
        }
        NumberableField.superclass.onBlur.apply(this, arguments);
    },

    checkState: function(field, record) {
        const config = this.getConfig()
        this.setReadOnly(! config.editable);
    },

    validateValue: function(value) {
        if (NumberableField.superclass.validateValue.apply(this, arguments) === false) {
            return false
        }

        const config = this.getConfig()
        if(!this.readOnly && value && value !== this.emptyValue && !String(value).match(new RegExp('^' + _.escapeRegExp(config.prefix) + '.*'))) {
            this.markInvalid(window.formatMessage('Value needs to start with { prefix }', { prefix: config.prefix }))
            return false
        }
        return true
    },

    setDisabled: function(disabled) {
        console.error(disabled)
    }

});

NumberableField.registerConfigProvider = function(appName, modelName, fieldName, configProvider) {
    const key = [appName, modelName, fieldName].join('-');
    configProvider[key] = configProvider;
}

Ext.reg('numberablefield', NumberableField);

export default NumberableField;
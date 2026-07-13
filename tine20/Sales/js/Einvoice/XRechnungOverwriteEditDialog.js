/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Tine.Sales.Einvoice_XRechnungOverwriteEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    initComponent() {
        Tine.Sales.Einvoice_XRechnungOverwriteEditDialog.superclass.initComponent.call(this);

        const valueField = this.getForm().findField('value');
        valueField.validateValue = (value) => {
            if (valueField.constructor.superclass.validateValue.apply(valueField, arguments)) {
                const element = this.getForm().findField('xrechnung_element').selectedRecord;
                const action = this.getForm().findField('action').getValue();
                if (action === 'static' && element.get('type') === 'date' && (!value.match(/^\d{4}-\d{2}-\d{2}$/) || !Ext.isDate(Date.parseDate(value, 'Y-m-d', true)))) {
                    const text = this.app.i18n._('Please enter a valid date in the format YYYY-MM-DD')
                    valueField.markInvalid(text);
                    return false;
                }
                valueField.clearInvalid();
            }
            return true;
        }
    },

    checkStates: function() {
        Tine.Sales.Einvoice_XRechnungOverwriteEditDialog.superclass.checkStates.apply(this, arguments);

        const element = this.getForm().findField('xrechnung_element').selectedRecord;
        const action = this.record.get('action');

        this.getForm().findField('action').setDisabled(!element)
        this.getForm().findField('value').setDisabled(!element || action === 'delete')

        switch (action) {
            case 'delete':
                this.getForm().findField('value').emptyText = this.app.i18n._('Element gets removed from the invoice');
                this.getForm().findField('value').setValue('');
                break;
            case 'dynamic':
                this.getForm().findField('value').emptyText = this.app.i18n._('Dynamic template for the element');
                break;
            case 'static':
                this.getForm().findField('value').emptyText = this.app.i18n._('Static value for the element');
                break;
        }
    }
});
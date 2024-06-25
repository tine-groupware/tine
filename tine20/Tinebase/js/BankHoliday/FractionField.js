/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.BankHoliday');

Tine.Tinebase.BankHoliday.FractionField = Ext.extend(Ext.form.ComboBox, {
    initComponent: function() {
        this.fieldLabel = i18n._('Fraction of the day');
        this.store = [
            [0.25, i18n._('One quarter of the day')],
            [0.33, i18n._('One third of the day')],
            [0.5, i18n._('Half of the day')],
            [0.66, i18n._('Two third of the day')],
            [0.75, i18n._('Three quarters of the day')],
            [1, i18n._('Whole day')]
        ];
        this.supr().initComponent.call(this);
    }
});
Ext.reg('Tine.Tinebase.BankHoliday.FractionField', Tine.Tinebase.BankHoliday.FractionField);
Tine.widgets.form.FieldManager.register('Tinebase', 'BankHoliday', 'fraction', {
    xtype: 'Tine.Tinebase.BankHoliday.FractionField',
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);

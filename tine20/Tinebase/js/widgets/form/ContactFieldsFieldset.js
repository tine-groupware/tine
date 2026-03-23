/*
 * tine Groupware
 *
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 */

Ext.ns('Tine.Tinebase.widgets.form');

const ContactFieldsFieldset = Ext.extend(Ext.form.Field, {
    name: null,
    hideLabel: true, // to remove the labelWidth from the ExtJS layout: 'form'
    labelSeparator: '',
    xtype: 'contactFieldsFieldSet',

    title: null,
    layout: 'column',
    autoHeight: true,
    unwantedFields: [],
    defaultCheckedFields: [],

    checkboxes: null,

    initComponent: function () {
        this.checkboxes = {};
        this.supr().initComponent.call(this);
    },

    getValue: function () {
        if (!this.rendered) {
            if (this._pendingValue && typeof this._pendingValue === 'object') {
                return this._pendingValue;
            }

            const allFields = this._buildContactFields();
            const allFieldsList = [...allFields.checked, ...allFields.unchecked];
            const result = {};
            allFieldsList.forEach(function (field) {
                result[field.name] = this.defaultCheckedFields.includes(field.name);
            }, this);
            return result;
        }

        const result = {};
        Ext.iterate(this.checkboxes, function (fieldName, cb) {
            result[fieldName] = cb.getValue();
        });
        return result;
    },

    setValue: function (value) {
        this._pendingValue = value;

        if (!value || typeof value !== 'object') return;

        Ext.iterate(this.checkboxes, function (fieldName, cb) {
            if (value.hasOwnProperty(fieldName)) {
                cb.setValue(value[fieldName]);
            }
        });
    },

    onRender: function (ct, position) {
        this.el = ct.createChild({
            tag: 'input',
            type: 'hidden',
            name: this.name
        }, position);

        const adbApp = Tine.Tinebase.appMgr.get('Addressbook');
        const fields = this._buildContactFields();
        const allFields = [...fields.checked, ...fields.unchecked];
        const colCount = 3;

        const fieldset = new Ext.form.FieldSet({
            title: this.title || adbApp.i18n._('Contact Fields'),
            layout: 'column',
            autoHeight: true,
            renderTo: ct,
            items: Array.from({ length: colCount }, (_, col) => ({
                columnWidth: 1 / colCount,
                layout: 'form',
                items: allFields
                    .filter((_, i) => i % colCount === col)
                    .map(field => {
                        const cb = new Ext.form.Checkbox({
                            name: 'contact_field_' + field.name,
                            boxLabel: adbApp.i18n._hidden(field.fieldLabel || field.label || field.name),
                            hideLabel: true,
                            checked: this.defaultCheckedFields.includes(field.name)
                        });
                        this.checkboxes[field.name] = cb;
                        return cb;
                    })
            }))
        });

        this.fieldset = fieldset;

        if (this._pendingValue) {
            this.setValue(this._pendingValue);
        }
    },

    _buildContactFields: function () {
        const allFields = Tine.Addressbook.Model.Contact.getFieldDefinitions();
        return {
            checked: allFields.filter(f => this.defaultCheckedFields.includes(f.name)),
            unchecked: allFields.filter(
                f =>
                !this.defaultCheckedFields.includes(f.name) &&
                !this.unwantedFields.includes(f.name)
            )
        };
    }
});

Ext.reg('contactFieldsFieldSet', ContactFieldsFieldset);
export default ContactFieldsFieldset;

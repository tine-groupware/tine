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
    defaultRequiredFields: [],

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
                result[field.name] = {
                    optional: this.defaultCheckedFields.includes(field.name),
                    required: this.defaultRequiredFields.includes(field.name)
                };
            }, this);
            return result;
        }

        const result = {};
        Ext.iterate(this.checkboxes, function (fieldName, cbs) {
            result[fieldName] = {
                optional: cbs.optional.getValue(),
                required: cbs.required.getValue()
            };
        });
        return result;
    },

    setValue: function (value) {
        this._pendingValue = value;

        if (!value || typeof value !== 'object') return;

        Ext.iterate(this.checkboxes, function (fieldName, cbs) {
            if (value.hasOwnProperty(fieldName)) {
                const entry = value[fieldName];
                if (typeof entry === 'object') {
                    cbs.optional.setValue(!!entry.optional);
                    cbs.required.setValue(!!entry.required);
                } else {
                    cbs.optional.setValue(!!entry);
                }
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

        const labelOptional = i18n._('Optional');
        const labelRequired = i18n._('Required');
        const labelField    = i18n._('Field');

        const headerItems = Array.from({ length: colCount }, (_, col) => ({
            columnWidth: 1 / colCount,
            layout: 'form',
            style: 'padding-top: 4px; padding-bottom: 4px;',
            items: [{
                xtype: 'panel',
                html: '<div style="display:flex; align-items:center;">' +
                    '<span style="width:80px; font-size:11px; font-weight:bold; color:#333;">' + labelOptional + '</span>' +
                    '<span style="width:80px; font-size:11px; font-weight:bold; color:#333;">' + labelRequired + '</span>' +
                    '<span style="font-size:11px; font-weight:bold; color:#333;">' + labelField + '</span>' +
                    '</div>',
                border: false,
                bodyStyle: 'background:transparent;'
            }]
        }));

        const columnItems = Array.from({ length: colCount }, (_, col) => ({
            columnWidth: 1 / colCount,
            layout: 'form',
            items: allFields
                .filter((_, i) => i % colCount === col)
                .map(field => {
                    const isChecked  = this.defaultCheckedFields.includes(field.name);
                    const isRequired = this.defaultRequiredFields.includes(field.name);

                    const optionalCb = new Ext.form.Checkbox({
                        checked: isChecked || isRequired,
                        hideLabel: true
                    });
                    const requiredCb = new Ext.form.Checkbox({
                        checked: isRequired,
                        hideLabel: true
                    });

                    requiredCb.on('check', function (cb, checked) {
                        if (checked) {
                            optionalCb.setValue(true);
                        }
                    });
                    optionalCb.on('check', function (cb, checked) {
                        if (!checked) {
                            requiredCb.setValue(false);
                        }
                    });

                    this.checkboxes[field.name] = { optional: optionalCb, required: requiredCb };

                    const label = adbApp.i18n._hidden(field.fieldLabel || field.label || field.name);

                    return {
                        xtype: 'panel',
                        border: false,
                        bodyStyle: 'background:transparent; padding: 1px 0;',
                        layout: 'column',
                        items: [
                            { columnWidth: 0, width: 80, layout: 'form', items: optionalCb },
                            { columnWidth: 0, width: 80, layout: 'form', items: requiredCb },
                            {
                                columnWidth: 1,
                                layout: 'form',
                                items: [{
                                    xtype: 'displayfield',
                                    value: label,
                                    hideLabel: true,
                                    style: 'line-height:22px; padding-left:2px;'
                                }]
                            }
                        ]
                    };
                })
        }));

        const fieldset = new Ext.form.FieldSet({
            title: this.title || adbApp.i18n._('Contact Fields'),
            layout: 'column',
            autoHeight: true,
            renderTo: ct,
            items: [
                {
                    xtype: 'panel',
                    columnWidth: 1,
                    border: false,
                    bodyStyle: 'background:transparent;',
                    layout: 'column',
                    items: headerItems
                },
                {
                    xtype: 'panel',
                    columnWidth: 1,
                    border: false,
                    bodyStyle: 'background:transparent;',
                    layout: 'column',
                    items: columnItems
                }
            ]
        });

        this.fieldset = fieldset;

        if (this._pendingValue) {
            this.setValue(this._pendingValue);
        }
    },

    _buildContactFields: function () {
        const allFields = Tine.Addressbook.Model.Contact.getFieldDefinitions();
        return {
            checked: allFields.filter(f => this.defaultCheckedFields.includes(f.name) || this.defaultRequiredFields.includes(f.name)),
            unchecked: allFields.filter(
                f =>
                !this.defaultCheckedFields.includes(f.name) &&
                !this.defaultRequiredFields.includes(f.name) &&
                !this.unwantedFields.includes(f.name)
            )
        };
    }
});

Ext.reg('contactFieldsFieldSet', ContactFieldsFieldset);
export default ContactFieldsFieldset;

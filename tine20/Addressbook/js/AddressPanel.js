/*
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import formatAddress from "util/postalAddressFormater";

const getAddressPanels = () => {
    const app = Tine.Tinebase.appMgr.get('Addressbook');
    const recordClass = Tine.Tinebase.data.RecordMgr.get('Addressbook', 'Contact');

    const adrFields = _.sortBy(_.filter(recordClass.getModelConfiguration().fields, (field) => {
        return field.type === 'record' && _.get(field, 'config.recordClassName') == 'Addressbook_Model_ContactProperties_Address'
    }), (field) => {return _.get(field, 'uiconfig.order')});

    return adrFields.map((field) => {
        const preferredCheckbox = new Ext.form.Checkbox({
            hideLabel: false,
            fieldLabel: app.i18n._('Preferred Address'),
            name: `${field.fieldName}_is_preferred`,
            listeners: {
                'check': (checkbox, value) => {
                    getForm().findField('preferred_address').setValue(field.fieldName);
                }
            }
        });

        const getForm = () => {
            return preferredCheckbox.findParentBy((c) => { return c.getForm }).getForm();
        }

        const panel = {
            title: app.i18n._hidden(field.label),
            name: field.fieldName,
            xtype: 'columnform',
            listeners: {
                'render': (cmp) => {
                    cmp.mon(cmp.getEl(), 'contextmenu', (e) => {
                        e.stopEvent()
                        const menu = new Ext.menu.Menu({
                            items: [{
                                text: app.i18n._('Copy Postal Address to Clipboard'),
                                iconCls: 'clipboard',
                                handler: async () => {
                                    const aStruct = await formatAddress(cmp.findParentBy(function (c) { return c instanceof Tine.widgets.dialog.EditDialog }).record, field.key + '_')
                                    navigator.clipboard.writeText(aStruct.join('\n'));
                                    Ext.ux.Notification.show(i18n._('Copied to clipboard'), window.formatMessage('"{value}" was copied to clipboard', {value: _.map(aStruct, Ext.util.Format.htmlEncode).join('\n')}));
                                }
                            }],
                        });

                        menu.showAt(e.getXY())
                    })
                }
            },
            items: [[{
                fieldLabel: app.i18n._('Street'),
                name: `${field.fieldName}_street`,
                xtype: 'tine.widget.field.AutoCompleteField',
                recordClass: recordClass,
                maxLength: 64
            }, {
                fieldLabel: app.i18n._('Street 2'),
                name: `${field.fieldName}_street2`,
                maxLength: 64
            }, {
                fieldLabel: app.i18n._('Region'),
                name: `${field.fieldName}_region`,
                xtype: 'tine.widget.field.AutoCompleteField',
                recordClass: recordClass,
                maxLength: 64
            }], [{
                fieldLabel: app.i18n._('Postal Code'),
                name: `${field.fieldName}_postalcode`,
                maxLength: 64
            }, {
                fieldLabel: app.i18n._('City'),
                name: `${field.fieldName}_locality`,
                xtype: 'tine.widget.field.AutoCompleteField',
                recordClass: recordClass,
                maxLength: 64
            }, {
                xtype: 'widget-countrycombo',
                fieldLabel: app.i18n._('Country'),
                name: `${field.fieldName}_countryname`,
                maxLength: 64
            }], [
                preferredCheckbox
            ]]
        };

        //inject fake hidden field to take part of the record get/setValue cycle
        panel.items.push([{
            xtype: 'field',
            name: field.fieldName,
            hidden: true,
            getValue: () => {
                const form = getForm();
                const value = {};
                Tine.Addressbook.Model.ContactProperties_Address.getFieldNames().forEach((fieldName) => {
                    const formField = form.findField(`${field.fieldName}_${fieldName}`);
                    if (formField) {
                        value[fieldName] = formField.getValue();
                        // console.error('getValue', fieldName, _.get(value, fieldName));
                    }
                });
                // console.error('getValue', value);
                return value;
            },
            setValue: (value, record) => {
                // console.error('setValue', field.fieldName, value, record)
                const form = getForm();
                Tine.Addressbook.Model.ContactProperties_Address.getFieldNames().forEach((fieldName) => {
                    const formField = form.findField(`${field.fieldName}_${fieldName}`);
                    if (formField) {
                        // console.error('setValue', fieldName, _.get(value, fieldName));
                        formField.setValue(_.get(value, fieldName), record);
                    }
                });
            }
        }, {
            xtype: 'field',
            name: 'preferred_address',
            hidden: true,
            getValue: () => {
                const form = getForm();
                return _.map(adrFields, 'fieldName').reduce((preferred_address, fieldName) => {
                    const formField = form.findField(`${fieldName}_is_preferred`);
                    return formField && formField.getValue() ? fieldName : preferred_address;
                });
            },
            setValue: (value, record) => {
                const form = getForm();
                _.map(adrFields, 'fieldName').forEach((fieldName) => {
                    const formField = form.findField(`${fieldName}_is_preferred`);
                    if (formField) {
                        formField.suspendEvents();
                        formField.setValue(value === fieldName);
                        formField.resumeEvents();
                    }
                });

                // let's see if our panels are inside a tabPanel and switch to the card
                const cardPanel = preferredCheckbox.findParentBy((c) => { return _.isFunction(_.get(c, 'setActiveTab')) });
                const addressPanel = _.find(_.get(cardPanel, 'items.items'), {name: value});
                if (addressPanel) {
                    cardPanel.setActiveTab(addressPanel);
                    cardPanel.doLayout();
                }
            }
        }]);

        return panel;
    });
}

export {
    getAddressPanels
}
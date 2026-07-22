/*
 * tine Groupware
 *
 * @package     EventManager
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de> Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2021-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */
import './filePanel';
import EvaluationDimensionForm from "../../Tinebase/js/widgets/form/EvaluationDimensionForm";
import ContactFieldsFieldset from "../../Tinebase/js/widgets/form/ContactFieldsFieldset";
import formatAddress from "util/postalAddressFormater";

Ext.namespace('Tine.EventManager');

Tine.EventManager.EventEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    appName: 'EventManager',
    modelName: 'Event',
    windowHeight: 930,
    windowWidth: 1050,

    windowNamePrefix: 'EventEditWindow_',

    optionTemplates: [
        // Verpflegung
        { name_option: 'Konventionell', group: 'Verpflegung', sorting: 1, option_config_class: 'EventManager_Model_CheckboxOption', option_config: {price: 0, description: ''}},
        { name_option: 'Vegetarisch', group: 'Verpflegung', sorting: 2, option_config_class: 'EventManager_Model_CheckboxOption', option_config: {price: 0, description: ''}},
        { name_option: 'Vegan', group: 'Verpflegung', sorting: 3, option_config_class: 'EventManager_Model_CheckboxOption', option_config: {price: 0, description: ''}},
        { name_option: 'Ich nehme nicht an den Mahlzeiten teil', group: 'Verpflegung', sorting: 4, option_config_class: 'EventManager_Model_CheckboxOption', option_config: {price: 0, description: ''}},
        { name_option: 'Allergien und Unverträglichkeiten',group: 'Verpflegung', sorting: 5, option_config_class: 'EventManager_Model_TextInputOption', option_config: {multiple_lines: true, max_characters: 255}},
        // Unterbringung
        { name_option: 'Einzelzimmer', group: 'Unterbringung', sorting: 6, option_config_class: 'EventManager_Model_CheckboxOption', option_config: {price: 0, description: ''}},
        { name_option: 'Doppelzimmer', group: 'Unterbringung', sorting: 7, option_config_class: 'EventManager_Model_CheckboxOption', option_config: {price: 0, description: ''}},
        { name_option: 'Keine Übernachtung', group: 'Unterbringung', sorting: 8, option_config_class: 'EventManager_Model_CheckboxOption', option_config: {price: 0, description: ''}},
        { name_option: 'Doppelzimmer mit...', group: 'Unterbringung', sorting: 9, option_config_class: 'EventManager_Model_TextInputOption', option_config: {multiple_lines: true, max_characters: 255}},
    ],

    initComponent: function () {
        this.app = Tine.Tinebase.appMgr.get('EventManager');
        this.supr().initComponent.apply(this, arguments);

        this.rrulePanel = new Tine.Calendar.RrulePanel({
            eventEditDialog : this
        });
    },

    onRecordLoad: function () {
        this.supr().onRecordLoad.apply(this, arguments);

        if (this.record.id === null || this.record.phantom) {
            const optionsField = this.form.findField('options');
            if (optionsField && optionsField.store.getCount() === 0) {
                this._applyOptionTemplates(optionsField);
            }
        }
    },

    _applyOptionTemplates: function (optionsField) {
        const RecordType = optionsField.store.recordType;
        _.each(this.optionTemplates, function (tpl) {
            const record = new RecordType(Ext.apply({ id: Ext.id(null, 'new-') }, tpl));
            optionsField.store.add(record);
        });
    },

    getFormItems: function () {
        const me = this;
        const fieldManager = _.bind(
            Tine.widgets.form.FieldManager.get,
            Tine.widgets.form.FieldManager,
            this.appName,
            this.modelName,
            _,
            Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG
        );

        return {
            xtype: 'tabpanel',
            border: false,
            plain: true,
            activeTab: 0,
            plugins: [{
                ptype : 'ux.tabpanelkeyplugin'
            }, {
                ptype: 'ux.itemregistry',
                key:   [this.app.appName, this.recordClass.getMeta('modelName'), 'EditDialog-TabPanel'].join('-')
            }],
            items: [{
                title: this.app.i18n._('Sign Up'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'border',
                layoutConfig: {
                    enableResponsive: true,
                },
                items: [{
                    region: 'center',
                    layout: 'vbox',
                    border: false,
                    items: [{
                        xtype: 'fieldset',
                        region: 'north',
                        // autoHeight: true,
                        title: this.app.i18n._('Event Information'),
                        items: [{
                            xtype: 'panel',
                            layout: 'hbox',
                            align: 'stretch',
                            items: [{
                                flex: 1,
                                xtype: 'columnform',
                                autoHeight: true,
                                items: [
                                    [
                                        fieldManager('name'),
                                        fieldManager('subheading'),
                                        fieldManager('event_employee'),
                                    ],
                                    [
                                        fieldManager('start'),
                                        fieldManager('end'),
                                    ],
                                    [
                                        fieldManager('type'),
                                        fieldManager('status'),
                                    ],
                                    [
                                        fieldManager('location_record', {
                                            listeners: {
                                                scope: me,
                                                select: async function (combo, rec) {
                                                    const aStruct = await formatAddress(rec.getPreferredAddressObject());
                                                    this.form.findField('location').setValue(aStruct.join('\n'));
                                                }
                                            }
                                        }),
                                        fieldManager('location', {
                                            xtype: 'textarea',
                                            grow: true,
                                            growMin: 18,
                                            growAppend: '',
                                        }),
                                    ],
                                    [
                                        fieldManager('total_places', {
                                            checkState: function () {
                                                const total = me.form.findField('total_places').getValue() || 0;
                                                const booked = me.form.findField('booked_places').getValue() || 0;
                                                me.form.findField('available_places').setValue(total - booked);
                                            }
                                        }),
                                        fieldManager('booked_places'),
                                        fieldManager('available_places'),
                                    ],
                                    [
                                        fieldManager('fee'),
                                        fieldManager('registration_possible_until'),
                                        fieldManager('register_others'),
                                    ],
                                    [ new EvaluationDimensionForm({
                                        maxItemsPerRow: 2,
                                        recordClass: this.recordClass
                                    })],
                                    [
                                      fieldManager('description')
                                    ],
                                ]
                            }]
                        }]
                    }, {
                        xtype: 'fieldset',
                        flex: 1,
                        title: this.app.i18n._('Event Options'),
                        layout: 'fit',
                        items: [
                            fieldManager('options', {
                                defaultSortInfo: {field: 'sorting', direction: 'ASC'},
                                listeners: {
                                    afterrender: function (grid) {
                                        const cm = grid.getColumnModel();
                                        const colIndex = cm.findColumnIndex('name_option');
                                        if (colIndex !== -1) {
                                            const col = cm.getColumnById(cm.getColumnId(colIndex));
                                            col.renderer = function (value, metaData, record) {
                                                const level = record.get('level');
                                                if (level && level !== '1') {
                                                    return new Array(Number(level)).join('&nbsp;&nbsp;&nbsp;') + Ext.util.Format.htmlEncode(value);
                                                }
                                                return Ext.util.Format.htmlEncode(value);
                                            };
                                        }
                                    }
                                }
                            }),
                        ]
                    }]
                },
                    {
                        // EventManager and tags
                        region: 'east',
                        layout: 'ux.multiaccordion',
                        animate: true,
                        width: 210,
                        split: true,
                        collapsible: true,
                        collapseMode: 'mini',
                        header: false,
                        margins: '0 5 0 5',
                        border: true,
                        items: [
                            new Tine.widgets.tags.TagPanel({
                                app: 'EventManager',
                                border: false,
                            })
                        ]
                }]
            }, {
                title: this.app.i18n._('Registrations'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'form',
                items: [{
                    xtype: 'fieldset',
                    flex: 1,
                    title: this.app.i18n._('Registrations'),
                    layout: 'fit',
                    height: 600,
                    items: [
                        fieldManager('registrations', {
                            defaultData: function () {
                                const available_places = me.form.findField('available_places').getValue();
                                return {
                                    status: available_places <= 0 ? "2" : "1"
                                };
                            },
                            listeners: {
                                render: function (grid) {
                                    grid.on('beforeedit', function (e) {
                                        const total_places = me.form.findField('total_places').getValue() || 0;
                                        const available_places = me.form.findField('available_places').getValue() || 0;
                                        const registrations = me.form.findField('registrations').store.data.items;
                                        let registrations_count = 0;
                                        registrations.forEach(registration => {
                                            if (registration.data.status !== "3" && registration.data.status !== "2") {
                                                registrations_count++;
                                            }
                                        });
                                        const editor = e.grid.getColumnModel().getCellEditor(e.column, e.row);
                                        const statusField = editor.field;
                                        if (statusField && available_places <= 0 && total_places <= registrations_count) {
                                            statusField.on('expand', function (combo) {
                                                setTimeout(function () {
                                                    const listId = combo.list ? combo.list.id : null;
                                                    let list = null;
                                                    if (listId) {
                                                        list = document.getElementById(listId);
                                                    } else {
                                                        const comboLists = Ext.query('.x-combo-list');
                                                        list = comboLists[comboLists.length - 1];
                                                    }
                                                    if (list) {
                                                        const items = Ext.query('.x-combo-list-item', list);
                                                        combo.getStore().each(function (record, index) {
                                                            if (record.get('id') === '1' && items[index]) {
                                                                items[index].style.setProperty('color', '#999', 'important');
                                                                items[index].style.setProperty('background-color', '#f0f0f0', 'important');
                                                                items[index].style.setProperty('opacity', '0.6', 'important');
                                                            }
                                                        });
                                                    }
                                                }, 10);
                                            }, this);

                                            statusField.on('beforeselect', function (combo, record, index) {
                                                // prevent selection of value confirmed
                                                return record.get(combo.valueField) !== "1";
                                            }, this);
                                        }
                                    });

                                    grid.store.on('add', function (store, records, index) {
                                        const total_places = me.form.findField('total_places').getValue() || 0;
                                        const available_places = me.form.findField('available_places').getValue() || 0;
                                        const registrations = me.form.findField('registrations').store.data.items;
                                        let registrations_count = 0;
                                        registrations.forEach(registration => {
                                            if (registration.data.status !== "3" && registration.data.status !== "2") {
                                                registrations_count++;
                                            }
                                        });
                                        if (available_places <= 0 && total_places <= registrations_count) {
                                            Ext.each(records, function (record) {
                                                record.set('status', "2");
                                            });
                                        }
                                    });
                                }
                            }
                        }),
                    ]
                }
                ]
            }, {
                title: this.app.i18n._('Multi-appointment'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'form',
                items: [{
                    xtype: 'fieldset',
                    flex: 1,
                    title: this.app.i18n._('Appointments'),
                    layout: 'fit',
                    items: [
                        fieldManager('appointments', {
                            checkState: function () {
                                let field = me.form.findField('appointments');
                                let sessions = field.getValue();

                                const getDateStr = (sessionDate) => {
                                    if (!sessionDate) return null;
                                    return (sessionDate instanceof Date)
                                        ? `${sessionDate.getFullYear()}-${String(sessionDate.getMonth() + 1).padStart(2, '0')}-${String(sessionDate.getDate()).padStart(2, '0')}`
                                        : sessionDate.substring(0, 10);
                                };

                                const getTimeStr = (time) => {
                                    if (!time || time === '') return null;
                                    if (time instanceof Date) return time.toTimeString().substring(0, 8);
                                    return time.length > 8 ? time.substring(11, 19) : time;
                                };

                                sessions.sort((session1, session2) => {
                                    const date1 = getDateStr(session1['session_date']);
                                    const date2 = getDateStr(session2['session_date']);

                                    if (date1 === null && date2 === null) return 0;
                                    if (date1 === null) return 1;
                                    if (date2 === null) return -1;
                                    if (date1 !== date2) return date1 < date2 ? -1 : 1;

                                    const time1 = getTimeStr(session1['start_time']);
                                    const time2 = getTimeStr(session2['start_time']);
                                    if (time1 === null && time2 === null) return 0;
                                    if (time1 === null) return 1;
                                    if (time2 === null) return -1;
                                    return time1 < time2 ? -1 : (time1 > time2 ? 1 : 0);
                                });

                                sessions.forEach((session, index) => {
                                    session['session_number'] = index + 1;
                                });

                                field.setValue(sessions);
                            }
                        })
                    ]
                }
                ]
            }, {
                title: this.app.i18n._('Registration Contact Fields'),
                autoScroll: true,
                border: false,
                frame: true,
                layout: 'form',
                items: [
                    new ContactFieldsFieldset({
                        title: this.app.i18n._('Contact Fields'),
                        name: 'contact_fields',
                        unwantedFields: ['account_id', 'color', 'groups', 'language', 'org_unit', 'pubkey',
                            'syncBackendIds', 'tel_other', 'tel_pager_normalized', 'type',
                            'tel_cell_private_normalized', 'tel_fax_home_normalized', 'cat_id',
                            'GDPR_DataEditingReason', 'customfields', 'attachments', 'creation_time',
                            'last_modified', 'deleted_by', 'is_deleted', 'deleted_time',
                            'adr_one_lon', 'adr_one_lat', 'adr_two_lon', 'adr_two_lat',
                            'calendar_uri', 'groups_diff', 'note', 'paths', 'tel_prefer',
                            'tel_other_normalized', 'tel_home_normalized', 'sites',
                            'GDPR_DataProvenance', 'GDPR_DataIntendedPurposeRecord', 'relations',
                            'xprops', 'last_modified_by', 'freebusy_uri', 'preferred_address',
                            'room', 'tel_car', 'tel_assistent_normalized', 'tel_prefer_normalized',
                            'tel_cell_normalized', 'tel_work_normalized', 'tel_fax_normalized',
                            'ical_fb_urls', 'GDPR_DataExpiryDate', 'container_id', 'notes',
                            'last_modified_time', 'assistent', 'geo', 'tel_car_normalized',
                            'label', 'id', 'matrix_id', 'GDPR_Blacklist', 'tags', 'created_by',
                            'seq', 'test'],
                        defaultCheckedFields: ['n_given', 'n_middle', 'n_family', 'bday', 'email',
                            'tel_cell', 'tel_work', 'adr_one_street', 'adr_one_street2',
                            'adr_one_postalcode', 'adr_one_locality', 'adr_one_region',
                            'adr_one_countryname'],
                        defaultRequiredFields: ['n_given','n_family', 'email'],
                    })
                ]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    },

    onSaveAndClose: function () {
        const total_places = this.form.findField('total_places').getValue() || 0;
        const available_places = this.form.findField('available_places').getValue() || 0;
        const registrations = this.form.findField('registrations').store.data.items;
        let registrations_count = 0;
        registrations.forEach(registration => {
            if (registration.data.status !== "3" && registration.data.status !== "2") {
                registrations_count++;
            }
        });
        if (available_places <= 0 && total_places !== 0 && total_places <= registrations_count) {
            Ext.MessageBox.show({
                buttons: Ext.Msg.OK,
                icon: Ext.MessageBox.INFO,
                title: this.app.i18n._('Waiting List'),
                msg: this.app.i18n._('This event is fully booked. The person you are registering will be placed on the waiting list.'),
                fn: () => this.supr().onSaveAndClose.apply(this, arguments)
            });
        } else {
            this.supr().onSaveAndClose.apply(this, arguments);
        }
    }
});

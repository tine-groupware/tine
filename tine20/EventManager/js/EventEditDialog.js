/*
 * Tine 2.0
 *
 * @package     EventManager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2021-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
import './filePanel';

Ext.namespace('Tine.EventManager');

Tine.EventManager.EventEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    appName: 'EventManager',
    modelName: 'Event',
    windowHeight: 930,
    windowWidth: 1050,

    windowNamePrefix: 'EventEditWindow_',

    initComponent: function () {
        this.supr().initComponent.apply(this, arguments);
        this.app = Tine.Tinebase.appMgr.get('EventManager');

        this.rrulePanel = new Tine.Calendar.RrulePanel({
            eventEditDialog : this
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
                items: [{
                    region: 'center',
                    layout: 'vbox',
                    border: false,
                    items: [{
                        xtype: 'fieldset',
                        region: 'north',
                        autoHeight: true,
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
                                        fieldManager('start'),
                                        fieldManager('end', {
                                            checkState: function () {
                                                if (me.form.findField('end').getValue() && (me.form.findField('start').getValue() > me.form.findField('end').getValue())) {
                                                    this.setValue('');
                                                    Ext.MessageBox.show({
                                                        buttons: Ext.Msg.OK,
                                                        icon: Ext.MessageBox.WARNING,
                                                        title: me.app.i18n._('Registration'),
                                                        msg: me.app.i18n._('The event should end after the start date')
                                                    });
                                                }
                                            }
                                        }),
                                    ], [
                                        fieldManager('location'),
                                        fieldManager('type'),
                                        fieldManager('total_places'),
                                    ],
                                    [
                                        fieldManager('status'),
                                        fieldManager('fee'),
                                        fieldManager('registration_possible_until', {
                                            checkState: function () {
                                                if (me.form.findField('end').getValue() !== null) {
                                                    if (me.form.findField('registration_possible_until').getValue() && (me.form.findField('end').getValue() < me.form.findField('registration_possible_until').getValue())) {
                                                        this.setValue('');
                                                        Ext.MessageBox.show({
                                                            buttons: Ext.Msg.OK,
                                                            icon: Ext.MessageBox.WARNING,
                                                            title: me.app.i18n._('Registration'),
                                                            msg: me.app.i18n._('One should be able to register before the end date')
                                                        });
                                                    }
                                                }
                                            }
                                        }),
                                    ],
                                    [
                                        fieldManager('booked_places', {
                                            checkState: function () {
                                                this.setValue(me.form.findField('registrations').getStore().getCount());
                                            }
                                        }),
                                        fieldManager('available_places', {
                                            checkState: function () {
                                                this.setValue(me.form.findField('total_places').getValue() - me.form.findField('booked_places').getValue());
                                            }
                                        }),
                                        //fieldManager('is_live'),
                                    ]
                                ]
                            }]
                        }]
                    },
                    {
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
                    },
                    {
                        xtype: 'fieldset',
                        flex: 1,
                        title: this.app.i18n._('Registrations'),
                        layout: 'fit',
                        items: [
                            fieldManager('registrations'),
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
                            new Ext.Panel({
                                // @todo generalise!
                                title: this.app.i18n._('Description'),
                                iconCls: 'descriptionIcon',
                                layout: 'form',
                                labelAlign: 'top',
                                border: false,
                                items: [{
                                    style: 'margin-top: -4px; border 0px;',
                                    labelSeparator: '',
                                    xtype: 'textarea',
                                    name: 'description',
                                    hideLabel: true,
                                    grow: false,
                                    preventScrollbars: false,
                                    anchor: '100% 100%',
                                    emptyText: this.app.i18n._('Enter description'),
                                    requiredGrant: 'editGrant'
                                }]
                            }),
                            new Tine.widgets.tags.TagPanel({
                                app: 'EventManager',
                                border: false,
                            })
                        ]
                }]
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
                                let sessions = me.form.findField('appointments').getValue()
                                sessions.sort((session1, session2) => {
                                    if (session1['session_date'] < session2['session_date']) {
                                        return -1;
                                    }
                                    if (session1['session_date'] > session2['session_date']) {
                                        return 1;
                                    }
                                    return 0;
                                })
                                let counter = 0;
                                sessions.forEach((session) => {
                                    session['session_number'] = counter + 1;
                                    counter += 1;
                                    if (me.form.findField('end').getValue() && session['session_date'] && (me.form.findField('end').getValue() < session['session_date'])) {
                                        session['session_date'] = me.form.findField('end').getValue();
                                        Ext.MessageBox.show({
                                            buttons: Ext.Msg.OK,
                                            icon: Ext.MessageBox.WARNING,
                                            title: me.app.i18n._('Registration'),
                                            msg: me.app.i18n._('The session should take place before the end date. Please change the date or it would be change automatically')
                                        });
                                    }
                                    if (session['session_date'] && me.form.findField('start').getValue() && (session['session_date'] < me.form.findField('start').getValue())) {
                                        session['session_date'] = me.form.findField('start').getValue();
                                        Ext.MessageBox.show({
                                            buttons: Ext.Msg.OK,
                                            icon: Ext.MessageBox.WARNING,
                                            title: me.app.i18n._('Registration'),
                                            msg: me.app.i18n._('The session should start on the same date or after the event started. Please change the date or it would be change automatically')
                                        });
                                    }
                                    if (session['start_time'] && session['end_time'] && (session['start_time'] > session['end_time'])) {
                                        session['end_time'] = '';
                                        Ext.MessageBox.show({
                                            buttons: Ext.Msg.OK,
                                            icon: Ext.MessageBox.WARNING,
                                            title: me.app.i18n._('Registration'),
                                            msg: me.app.i18n._('The session should end after it begun. Please change the end time, or it would be deleted')
                                        });
                                    }
                                })
                            }
                        })
                    ]
                }
                ]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    }
});




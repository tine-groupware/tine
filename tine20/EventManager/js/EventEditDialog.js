/*
 * Tine 2.0
 *
 * @package     EventManager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2021-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.EventManager');

Tine.EventManager.EventEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    appName: 'EventManager',
    modelName: 'Event',
    windowHeight: 930,
    windowWidth: 1050,

    windowNamePrefix: 'EventEditWindow_',
    
    initComponent: function () {
        this.supr().initComponent.apply(this, arguments);
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
                                        fieldManager('end'),
                                    ], [
                                        fieldManager('location'),
                                        fieldManager('type'),
                                        fieldManager('totalPlaces'),
                                    ],
                                    [
                                        fieldManager('status'),
                                        fieldManager('fee'),
                                    ],
                                    [
                                        fieldManager('bookedPlaces', {
                                            checkState: function () {
                                                this.setValue(me.form.findField('registrations').getStore().getCount());
                                            }
                                        }),
                                        fieldManager('availablePlaces', {
                                            checkState: function () {
                                                this.setValue(me.form.findField('totalPlaces').getValue() - me.form.findField('bookedPlaces').getValue());
                                            }
                                        }),
                                        fieldManager('doubleOptIn'),
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
                            fieldManager('options')
                        ]
                    },
                    {
                        xtype: 'fieldset',
                        flex: 1,
                        title: this.app.i18n._('Registrations'),
                        layout: 'fit',
                        items: [
                            fieldManager('registrations')
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
                                bodyStyle: 'border:1px solid #B5B8C8;'
                            })
                        ]
                }]
            }, new Tine.widgets.activities.ActivitiesTabPanel({
                app: this.appName,
                record_id: this.record.id,
                record_model: this.appName + '_Model_' + this.recordClass.getMeta('modelName')
            })]
        };
    }
});




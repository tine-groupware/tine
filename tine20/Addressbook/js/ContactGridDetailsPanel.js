/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO         add preference for sending mails with felamimail or mailto?
 */

Ext.ns('Tine.Addressbook');

/**
 * the details panel (shows contact details)
 *
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.ContactGridDetailsPanel
 * @extends     Tine.widgets.grid.DetailsPanel
 */
Tine.Addressbook.ContactGridDetailsPanel = Ext.extend(Tine.widgets.grid.DetailsPanel, {

    il8n: null,
    felamimail: false,
    panels: [],
    
    getSingleRecordPanel: function() {
        const boxLayout = this.rendered && this.getWidth() < 800 ? 'vbox' : 'hbox';
        const app = Tine.Tinebase.appMgr.get('Addressbook');
        
        if (!this.panels[boxLayout]) {
            this.panels[boxLayout] = new Tine.widgets.display.RecordDisplayPanel({
                layout: 'fit',
                border: false,
                autoScroll: true,
                recordClass: this.recordClass,
                getBodyItems: function() {
                    return [{
                        layout: boxLayout,
                        flex: 1,
                        border: false,
                        layoutConfig: {
                            align: 'stretch',
                        },
                        defaults: {
                            margins: '5 0',
                        },
                        items: [{
                            layout: 'ux.display',
                            layoutConfig: {
                                background: 'solid'
                            },
                            items: [{
                                xtype: 'ux.displayfield',
                                name: 'jpegphoto',
                                hideLabel: true,
                                htmlEncode: false,
                                cls: 'responsive-title',
                                renderer: (value, metaData, record) => {
                                    return Tine.Addressbook.ContactGridPanel.prototype.oneColumnRenderer.call(this, null, null, record)
                                }
                            }]
                        }, {
                            flex: 2,
                            layout: 'ux.display',
                            labelWidth: 60,
                            layoutConfig: {
                                background: 'solid',
                            },
                            items: [{
                                layout: 'hbox',
                                border: false,
                                anchor: '100% 100%',
                                layoutConfig: {
                                    align: 'stretch',
                                },
                                items: [{
                                    layout: 'ux.display',
                                    layoutConfig: {
                                        background: 'inner',
                                        labelLWidth: 100,
                                        declaration: app.i18n._('Business')
                                    },
                                    labelAlign: 'top',
                                    border: false,
                                    flex: 1,
                                    items: [{
                                        xtype: 'ux.displayfield',
                                        name: 'org_name',
                                        hideLabel: true,
                                        htmlEncode: false,
                                        renderer: function (value) {
                                            return '<b>' + Tine.Tinebase.EncodingHelper.encode(value) + '</b>';
                                        }
                                    }, {
                                        xtype: 'ux.displayfield',
                                        name: 'dtstart',
                                        hideLabel: true,
                                        htmlEncode: false,
                                        renderer: Tine.widgets.grid.RendererManager.get('Addressbook', 'Addressbook_Model_Contact', 'addressblock', 'displayPanel').createDelegate(this, {
                                            'street': 'adr_one_street',
                                            'street2': 'adr_one_street2',
                                            'postalcode': 'adr_one_postalcode',
                                            'locality': 'adr_one_locality',
                                            'region': 'adr_one_region',
                                            'countryname': 'adr_one_countryname'
                                        }, true)
                                    }]
                                }, {
                                    layout: 'ux.display',
                                    layoutConfig: {
                                        background: 'inner'
                                    },
                                    labelWidth: 50,
                                    flex: 1,
                                    border: false,
                                    items: [{
                                        xtype: 'ux.displayfield',
                                        name: 'tel_work',
                                        fieldLabel: app.i18n._('Phone')
                                    }, {
                                        xtype: 'ux.displayfield',
                                        name: 'tel_cell',
                                        fieldLabel: app.i18n._('Mobile')
                                    }, {
                                        xtype: 'ux.displayfield',
                                        name: 'tel_fax',
                                        fieldLabel: app.i18n._('Fax')
                                    }, {
                                        xtype: 'ux.displayfield',
                                        name: 'email',
                                        fieldLabel: app.i18n._('E-Mail'),
                                        htmlEncode: false,
                                        renderer: Tine.widgets.grid.RendererManager.get('Addressbook', 'Addressbook_Model_Contact', 'email', 'displayPanel').createDelegate(this)
                                    }, {
                                        xtype: 'ux.displayfield',
                                        name: 'url',
                                        fieldLabel: app.i18n._('Web'),
                                        htmlEncode: false,
                                        renderer: Tine.widgets.grid.RendererManager.get('Addressbook', 'Addressbook_Model_Contact', 'url', 'displayPanel').createDelegate(this)
                                    }]
                                }]
                            }]
                        }, {
                            flex: 2,
                            layout: 'ux.display',
                            labelWidth: 60,
                            layoutConfig: {
                                background: 'solid'
                            },
                            items: [{
                                layout: 'hbox',
                                border: false,
                                anchor: '100% 100%',
                                layoutConfig: {
                                    align: 'stretch'
                                },
                                items: [{
                                    layout: 'ux.display',
                                    layoutConfig: {
                                        background: 'inner',
                                        labelLWidth: 100,
                                        declaration: app.i18n._('Private')
                                    },
                                    labelAlign: 'top',
                                    border: false,
                                    flex: 1,
                                    
                                    // @todo: this field doesn't actually require a certain field, there should be two methods for RenderManager:
                                    //  + get()
                                    //  + getBlock() // block actually doesn't specify a certain field and only an record, the field declaration should come from the modelconfig later
                                    items: [{
                                        xtype: 'ux.displayfield',
                                        name: 'attendee',
                                        hideLabel: true,
                                        htmlEncode: false,
                                        renderer: Tine.widgets.grid.RendererManager.get('Addressbook', 'Addressbook_Model_Contact', 'addressblock', 'displayPanel').createDelegate(this, {
                                            'street': 'adr_two_street',
                                            'street2': 'adr_two_street2',
                                            'postalcode': 'adr_two_postalcode',
                                            'locality': 'adr_two_locality',
                                            'region': 'adr_two_region',
                                            'countryname': 'adr_two_countryname'
                                        }, true)
                                    }]
                                }, {
                                    layout: 'ux.display',
                                    layoutConfig: {
                                        background: 'inner'
                                    },
                                    labelWidth: 50,
                                    flex: 1,
                                    border: false,
                                    items: [{
                                        xtype: 'ux.displayfield',
                                        name: 'tel_home',
                                        fieldLabel: app.i18n._('Phone')
                                    }, {
                                        xtype: 'ux.displayfield',
                                        name: 'tel_cell_private',
                                        fieldLabel: app.i18n._('Mobile')
                                    }, {
                                        xtype: 'ux.displayfield',
                                        name: 'tel_fax_home',
                                        fieldLabel: app.i18n._('Fax')
                                    }, {
                                        xtype: 'ux.displayfield',
                                        name: 'email_home',
                                        fieldLabel: app.i18n._('E-Mail'),
                                        htmlEncode: false,
                                        renderer: Tine.widgets.grid.RendererManager.get('Addressbook', 'Addressbook_Model_Contact', 'email', 'displayPanel').createDelegate(this)
                                    }]
                                }]
                            }]
                        }, {
                            flex: 1,
                            layout: 'fit',
                            
                            border: false,
                            items: [{
                                cls: 'x-ux-display-background-border',
                                xtype: 'ux.displaytextarea',
                                name: 'note'
                            }]
                        }]
                    }];
                }
            });
            if (this.items) this.items.add(this.panels[boxLayout]);
        }
        this.singleRecordPanel = this.panels[boxLayout];
        return this.singleRecordPanel;
    },

    /**
     * add on click event after render
     */
    afterRender: function() {
        Tine.Addressbook.ContactGridDetailsPanel.superclass.afterRender.apply(this, arguments);

        if (this.felamimail === true) {
            this.body.on('click', this.onClick, this);
        }
    },
    
    onClick: function(e) {
     
    },
});

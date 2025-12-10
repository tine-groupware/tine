/**
 * Tine 2.0
 * 
 * @package     Crm
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2025 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

import {preferredAddressRender} from '../../Addressbook/js/renderers'
Ext.namespace('Tine.Crm');

/**
 * @namespace   Tine.Crm
 * @class       Tine.Crm.LeadGridDetailsPanel
 * @extends     Tine.widgets.grid.DetailsPanel
 * 
 * <p>Lead Grid Details Panel</p>
 * <p>
 * </p>
 *
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2025 Metaways Infosystems GmbH (https://www.metaways.de)
 */
Tine.Crm.LeadGridDetailsPanel = Ext.extend(Tine.widgets.grid.DetailsPanel, {
    
    border: false,
    
    /**
     * renders contact names
     * 
     * @param {String} type
     * @return {String}
     * 
     * TODO add mail link
     * TODO all labels should have the same width
     */
    contactRenderer: function(type) {
        
        var relations = this.record.get('relations');
        
        var a = [];
        var fields = [{
            label: (type == 'CUSTOMER') ? this.app.i18n._('Customer') : this.app.i18n._('Partner'),
            dataField: 'n_fileas'
        }, {
            label: this.app.i18n._('Phone'),
            dataField: 'tel_work'
        }, {
            label: this.app.i18n._('Mobile'),
            dataField: 'tel_cell'
        }, {
            label: this.app.i18n._('Fax'),
            dataField: 'tel_fax'
        }, {
            label: this.app.i18n._('Email'),
            dataField: 'email'
        }, {
            label: this.app.i18n._('Web'),
            dataField: 'url'
        }, {
            label: this.app.i18n._('Address'),
            dataField: 'adress'
        }];
        var labelMarkup = '<label class="x-form-item x-form-item-label">';
        
        if (Ext.isArray(relations) && relations.length > 0) {
            // get correct relation type from relations (contact) array
            for (var i = 0; i < relations.length; i++) {
                if (relations[i].type == type) {
                    var data = relations[i].related_record;
                    for (var j=0; j < fields.length; j++) {
                        if (data[fields[j].dataField]) {
                            if (fields[j].dataField == 'url') {
                                a.push(labelMarkup + fields[j].label + ':</label> '
                                    + '<a href="' + Ext.util.Format.htmlEncode(data[fields[j].dataField]) + '" target="_blank">' 
                                    + Ext.util.Format.htmlEncode(data[fields[j].dataField]) + '</a>');
                            } else {
                                a.push(labelMarkup + fields[j].label + ':</label> '  + Ext.util.Format.htmlEncode(data[fields[j].dataField]));
                            }
                        }
                        if(fields[j].dataField == 'adress') {
                            a.push(labelMarkup + fields[j].label + ':</label> ' + preferredAddressRender(null, null, data));
                        }
                    }
                    a.push('');
                }
            }
        }
        
        return a.join("\n");
    },

    /**
     * inits this component
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Crm');
        
        // define piechart stores
        this.leadstatePiechartStore = new Ext.data.JsonStore({
            fields: ['id', 'label', 'total'],
            id: 'id'
        });
        this.leadsourcePiechartStore = new Ext.data.JsonStore({
            fields: ['id', 'label', 'total'],
            id: 'id'
        });
        this.leadtypePiechartStore = new Ext.data.JsonStore({
            fields: ['id', 'label', 'total'],
            id: 'id'
        });

        this.defaultTpl = new Ext.XTemplate(
            '<div class="preview-panel-timesheet-nobreak">',
            '<!-- Preview timeframe -->',
            '<div class="preview-panel preview-panel-timesheet-left">',
            '<div class="bordercorner_1"></div>',
            '<div class="bordercorner_2"></div>',
            '<div class="bordercorner_3"></div>',
            '<div class="bordercorner_4"></div>',
            '<div class="preview-panel-declaration">' /*+ this.app.i18n._('timeframe')*/ + '</div>',
            '<div class="preview-panel-timesheet-leftside preview-panel-left">',
            '<span class="preview-panel-bold">',
            /*'First Entry'*/'<br/>',
            /*'Last Entry*/'<br/>',
            /*'Duration*/'<br/>',
            '<br/>',
            '</span>',
            '</div>',
            '<div class="preview-panel-timesheet-rightside preview-panel-left">',
            '<span class="preview-panel-nonbold">',
            '<br/>',
            '<br/>',
            '<br/>',
            '<br/>',
            '</span>',
            '</div>',
            '</div>',
            '<!-- Preview summary -->',
            '<div class="preview-panel-timesheet-right">',
            '<div class="bordercorner_gray_1"></div>',
            '<div class="bordercorner_gray_2"></div>',
            '<div class="bordercorner_gray_3"></div>',
            '<div class="bordercorner_gray_4"></div>',
            '<div class="preview-panel-declaration">'/* + this.app.i18n._('summary')*/ + '</div>',
            '<div class="preview-panel-timesheet-leftside preview-panel-left">',
            '<span class="preview-panel-bold">',
            this.app.i18n._('Total Leads') + '<br/>',
            this.app.i18n._('Total Turnover') + '<br/>',
            this.app.i18n._('Total Probable Turnover') + '<br/>',
            '</span>',
            '</div>',
            '<div class="preview-panel-timesheet-rightside preview-panel-left">',
            '<span class="preview-panel-nonbold">',
            '{count}<br/>',
            '{turnover}<br/>',
            '{probableturnover}<br/>',
            '</span>',
            '</div>',
            '</div>',
            '</div>'
        );

        this.supr().initComponent.call(this);
    },

    showDefault: function(body) {

        var data = {
            count: this.gridpanel.store.proxy.jsonReader.jsonData?.totalcount ?? 0,
            turnover: Ext.util.Format.money(
                this.gridpanel.store.proxy.jsonReader.jsonData?.total.sum_turnover ?? 0
            ),
            probableturnover: Ext.util.Format.money(
                this.gridpanel.store.proxy.jsonReader.jsonData?.total.sum_probableTurnover ?? 0
            ),
        };

        if (body) {
            this.defaultTpl.overwrite(body, data);
        }
    },

    showMulti: function(sm, body) {

        var data = {
            count: sm.getCount(),
            turnover: 0,
            probableturnover: 0
        };
        sm.each(function(record){
            data.turnover = data.turnover + parseFloat(record.data.turnover ?? 0);
            data.probableturnover = data.probableturnover + parseFloat(record.data.probableTurnover ?? 0);
        });
        data.turnover = Ext.util.Format.money(data.turnover);
        data.probableturnover = Ext.util.Format.money(data.probableturnover);

        this.defaultTpl.overwrite(body, data);
    },

    /**
     * default panel w.o. data
     * 
     * @return {Ext.ux.display.DisplayPanel}
     * 
     * TODO add something useful here
     */
    getDefaultInfosPanel: function() {
        if (! this.defaultInfosPanel) {
            this.defaultInfosPanel = new Ext.ux.display.DisplayPanel({
                layout: 'fit',
                border: false,
                items: []
            });
        }
        
        return this.defaultInfosPanel;
    },

    /**
     * get panel for multi selection aggregates/information
     * 
     * @return {Ext.Panel}
     */
    getMultiRecordsPanel: function() {
        return this.getDefaultInfosPanel();
    },
    
    /**
     * main lead details panel
     * 
     * @return {Ext.ux.display.DisplayPanel}
     * 
     * TODO add tasks / products?
     * TODO add contact icons?
     */
    getSingleRecordPanel: function() {
        if (! this.singleRecordPanel) {
            this.singleRecordPanel = new Ext.ux.display.DisplayPanel ({
                layout: 'fit',
                border: false,
                items: [{
                    layout: 'vbox',
                    border: false,
                    layoutConfig: {
                        align:'stretch'
                    },
                    items: [
                        {
                        layout: 'hbox',
                        flex: 0,
                        height: 16,
                        border: false,
                        style: 'padding-left: 5px; padding-right: 5px',
                        layoutConfig: {
                            align:'stretch'
                        },
                        items: [{
                            flex: 1,
                            xtype: 'ux.displayfield',
                            cls: 'x-ux-display-header',
                            name: 'lead_name'
                        }, {
                            flex: 1,
                            xtype: 'ux.displayfield',
                            style: 'text-align: right;',
                            name: 'container_id',
                            cls: 'x-ux-display-header',
                            htmlEncode: false,
                            renderer: Tine.Tinebase.common.containerRenderer
                        }]
                    }, {
                        layout: 'hbox',
                        flex: 1,
                        border: false,
                        layoutConfig: {
                            padding:'5',
                            align:'stretch'
                        },
                        defaults:{margins:'0 5 0 0'},
                        items: [{
                            flex: 1,
                            layout: 'ux.display',
                            labelWidth: 90,
                            layoutConfig: {
                                background: 'solid',
                                declaration: this.app.i18n._('Status')
                            },
                            items: [{
                                xtype: 'ux.displayfield',
                                name: 'start',
                                fieldLabel: this.app.i18n._('Start'),
                                renderer: Tine.Tinebase.common.dateRenderer
                            }, {
                                xtype: 'ux.displayfield',
                                name: 'end_scheduled',
                                fieldLabel: this.app.i18n._('Estimated end'),
                                renderer: Tine.Tinebase.common.dateRenderer
                            }, {
                                xtype: 'ux.displayfield',
                                name: 'leadtype_id',
                                fieldLabel: this.app.i18n._('Leadtype'),
                                renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Crm', 'leadtypes')
                            }, {
                                xtype: 'ux.displayfield',
                                name: 'leadsource_id',
                                fieldLabel: this.app.i18n._('Leadsource'),
                                renderer: Tine.Tinebase.widgets.keyfield.Renderer.get('Crm', 'leadsources')
                            }]
                        }, {
                            flex: 1,
                            layout: 'ux.display',
                            labelAlign: 'top',
                            autoScroll: true,
                            layoutConfig: {
                                background: 'solid'
                            },
                            items: [{
                                xtype: 'ux.displayfield',
                                name: 'partner',
                                nl2br: true,
                                htmlEncode: false,
                                renderer: this.contactRenderer.createDelegate(this, ['PARTNER'])
                            }]
                        }, {
                            flex: 1,
                            layout: 'ux.display',
                            labelAlign: 'top',
                            autoScroll: true,
                            layoutConfig: {
                                background: 'solid'
                            },
                            items: [{
                                xtype: 'ux.displayfield',
                                name: 'customer',
                                nl2br: true,
                                htmlEncode: false,
                                renderer: this.contactRenderer.createDelegate(this, ['CUSTOMER'])
                            }]
                        }, {
                            flex: 1,
                            layout: 'fit',
                            border: false,
                            items: [{
                                cls: 'x-ux-display-background-border',
                                xtype: 'ux.displaytextarea',
                                name: 'description'
                            }]
                        }]
                    }]
                }]
            });
        }
        
        return this.singleRecordPanel
    },
    
    /**
     * update lead details panel
     * 
     * @param {Tine.Tinebase.data.Record} record
     * @param {Mixed} body
     */
    updateDetails: function(record, body) {
        this.getSingleRecordPanel().loadRecord.defer(100, this.getSingleRecordPanel(), [record]);
    }
});

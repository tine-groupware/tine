/*
 * Tine 2.0
 * 
 * @package     SimpleFAQ
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Patrick Ryser <patrick.ryser@gmail.com>
 * @copyright   Copyright (c) 2007-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine', 'Tine.SimpleFAQ');

/**
 * @namespace   Tine.SimpleFAQ
 * @class       Tine.SimpleFAQ.Application
 * @extends     Tine.Tinebase.Application
 */
Tine.SimpleFAQ.Application = Ext.extend(Tine.Tinebase.Application, {
    getTitle: function() {
        return this.i18n.gettext('FAQ');
    },
    
    findQuestion: function () {
        var app = Tine.Tinebase.appMgr.get('SimpleFAQ');
        
        var qWin = new Ext.Window({
            title: app.i18n.gettext('Find Question'),
            width: 500,
            height: 300,
            layout: 'form',
            labelAlign: 'top',
            bodyStyle: 'padding: 5px',
            modal: true,
            items: [{
                xtype: 'faqpickercombobox',
                fieldLabel: app.i18n.gettext('Search Question'),
                listeners: {
                    scope: this,
                    'select': function (combo, record) {
                        if (record) {
                            qWin.get(1).get(0).setValue(record.get('answer'));
                        }
                    }
                }
            }, {
                xtype: 'fieldset',
                title: app.i18n.gettext('Answer'),
                hideLabels: true,
                bodyStyle: 'padding: 5px',
                items: [{
                    xtype: 'displayfield'
                }]
            }]
        }).show();
    }
});

/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Frederic Heihoff <heihoff@sh-systems.eu>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
import {mailAddressRenderer} from "./renderers";
import { HTMLProxy, Expression } from "twingEnv.es6";

Ext.ns('Tine.Addressbook');

/**
 * the details panel (shows List details)
 * 
 * @namespace   Tine.Addressbook
 * @class       Tine.Addressbook.ListGridDetailsPanel
 * @extends     Tine.widgets.grid.DetailsPanel
 */
Tine.Addressbook.ListGridDetailsPanel = Ext.extend(Tine.widgets.grid.DetailsPanel, {
    
    il8n: null,
    felamimail: false,
    
    /**
     * init
     */
    initComponent: function() {

        // init templates
        this.initTemplate();
        this.initDefaultTemplate();
        
        Tine.Addressbook.ListGridDetailsPanel.superclass.initComponent.call(this);
    },

    /**
     * add on click event after render
     */
    afterRender: function() {
        Tine.Addressbook.ListGridDetailsPanel.superclass.afterRender.apply(this, arguments);
    },
    
    /**
     * init default template
     */
    initDefaultTemplate: function() {
        
        this.defaultTpl = new Ext.XTemplate(
            '<div class="preview-panel-list-nobreak">',    
                '<!-- Preview contacts -->',
                '<div class="preview-panel preview-panel-list-left">',
                    '<div class="bordercorner_1"></div>',
                    '<div class="bordercorner_2"></div>',
                    '<div class="bordercorner_3"></div>',
                    '<div class="bordercorner_4"></div>',
                    '<div class="preview-panel-declaration">' + this.il8n._('Groups') + '</div>',
                    '<div class="preview-panel-list-leftside preview-panel-left">',
                        '<span class="preview-panel-bold">',
                            this.il8n._('Select Group') + '<br/>',
                            '<br/>',
                            '<br/>',
                            '<br/>',
                        '</span>',
                    '</div>',
                '</div>',
            '</div>'        
        );
    },
    
    /**
     * init single List template (this.tpl)
     */
    initTemplate: function() {
        this.tpl = new Ext.XTemplate(
            '<div class="preview-panel-list-nobreak">',    
                '<!-- Preview contacts -->',
                '<div class="preview-panel preview-panel-list-left">',
                    '<div class="bordercorner_1"></div>',
                    '<div class="bordercorner_2"></div>',
                    '<div class="bordercorner_3"></div>',
                    '<div class="bordercorner_4"></div>',
                    '<div class="preview-panel-declaration">' + this.il8n._('Groups') + '</div>',
                    '<div class="preview-panel-list-leftside preview-panel-left" style="width: 100%">',
                        '<span class="preview-panel-bold">',
                            this.il8n._('Group') + '<br/>',
                        '</span>',
                        '<div style="word-wrap:break-word;">{}</div>',
                    '</div>',
                '</div>',
            '</div>'
        );
    }
});

Tine.widgets.grid.RendererManager.register('Addressbook', 'List', 'members', (values, meta, record) => {
    return new HTMLProxy(new Promise(async (resolve) => {
        if (!record.contactNames) {
            const list = await Tine.Addressbook.getList(record.id);
            record.contactNames = await Promise.all(_.map(list.members, contactData => Tine.Addressbook.Model.Contact.setFromJson(contactData).getTitle().asString()));
        }
        resolve(new Expression(record.contactNames.map(Ext.util.Format.htmlEncode).join('<br />')));
    }));
}, 'displayPanel');
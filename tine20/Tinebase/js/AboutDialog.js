/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

 Ext.ns('Tine', 'Tine.Tinebase');

import "./SubscriptionScreen";
 /**
  * @namespace  Tine.Tinebase
  * @class      Tine.Tinebase.AboutDialog
  * @extends    Ext.Window
  */
Tine.Tinebase.AboutDialog = Ext.extend(Ext.Window, {

    closeAction: 'close',
    modal: true,
    width: 400,
    height: 400,
    minWidth: 400,
    minHeight: 400,
    layout: 'fit',
    title: null,

    initAboutTpl: function() {
        this.aboutTpl = new Ext.XTemplate(
            '<div class="tb-about-dlg">',
                '<div class="tb-about-img"><a href="{logoLink}" target="_blank"><img src="{logo}" /></a></div>',
                '<div class="tb-link-home"><a href="{logoLink}" target="_blank">{linkText}</a></div>',
                '<div class="tb-link-home"><a href="{tutorialLink}" target="_blank">{tutorialText}</a></div>',
                '<div class="tb-link-home"><a href="{docsLink}" target="_blank">{docsText}</a></div>',
                '<div class="tb-link-home"><a href="{faqLink}" target="_blank">{faqText}</a></div>',
                '<div class="tb-about-version">Version: {codeName}</div>',
                '<div class="tb-about-build">({packageString})</div>',
                '<div class="tb-about-subscription"><a href="javascript:void()" class="subscription" /></div>',
                '<div class="tb-about-copyright">Copyright: 2007-{[new Date().getFullYear()]}&nbsp;<a href="https://www.metaways.info/" target="_blank">Metaways Infosystems GmbH</a></div>',
                '<div class="tb-about-credits-license"><p><a href="javascript:void()" class="license" /><a href="javascript:void()" class="credits" /></p></div>',
            '</div>'
        );
    },
    
    initComponent: function() {
        this.title = String.format(i18n._('About {0}'), Tine.title);
        
        this.initAboutTpl();
        
        var version = (Tine.Tinebase.registry.get('version')) ? Tine.Tinebase.registry.get('version') : {
            codeName: 'unknown',
            packageString: 'unknown'
        };
        
        this.items = {
            layout: 'fit',
            border: false,
            html: this.aboutTpl.applyTemplate({
                logo: Tine.logo,
                logoLink: Tine.weburl,
                linkText: String.format(i18n._('Learn more about {0}'), Tine.title),
                codeName: version.codeName,
                packageString: version.packageString,
                tutorialLink: 'https://tutorials.tine-groupware.de/',
                tutorialText: i18n._('Video Tutorials'),
                docsLink: 'https://tine-docu.s3web.rz1.metaways.net/',
                docsText: i18n._('Technical Documentation'),
                faqLink: 'https://www.tine-groupware.de/faqs/',
                faqText: i18n._('FAQ')
            }),
            buttons: [{
                text: i18n._('Ok'),
                iconCls: 'action_saveAndClose',
                handler: this.close,
                scope: this
            }]
        };
        
        // create links
        this.on('afterrender', function() {
            var el = this.getEl().select('div.tb-about-dlg div.tb-about-credits-license a.license');
            el.insertHtml('beforeBegin', ' ' + i18n._('Released under different') + ' ');
            el.insertHtml('beforeEnd', i18n._('Open Source Licenses'));
            el.on('click', function(e){
                var ls = new Tine.Tinebase.LicenseScreen();
                ls.show();
                e.stopEvent();
            });
            
            var el = this.getEl().select('div.tb-about-dlg div.tb-about-credits-license a.credits');
            el.insertHtml('beforeBegin', ' ' + i18n._('with the help of our') + ' ');
            el.insertHtml('beforeEnd', i18n._('Contributors'));
            el.on('click', function(e) {
                var cs = new Tine.Tinebase.CreditsScreen();
                cs.show();
                e.stopEvent();
            });
    
            var el = this.getEl().select('div.tb-about-dlg div.tb-about-subscription a.subscription');
            el.insertHtml('beforeEnd', i18n._('Subscription'));
            el.on('click', function(e) {
                var cs = new Tine.Tinebase.SubscriptionScreen();
                cs.show();
                e.stopEvent();
            });
        }, this);

        Tine.Tinebase.AboutDialog.superclass.initComponent.call(this);
    }
});

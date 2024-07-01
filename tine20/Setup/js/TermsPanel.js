/*
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine', 'Tine.Setup');

Tine.Setup.CurrentTermsVersion = 1;

Tine.Setup.TermsPanel = Ext.extend(Ext.Panel, {
    border: false,
    layout: 'fit',
    version: 1,
    saveMethod: 'Setup.saveTerms',

    /**
     * @property actionToolbar
     * @type Ext.Toolbar
     */
    actionToolbar: null,
    
    getLicensePanel: function() {
        let accepted = Tine.Setup.registry.get('acceptedTermsVersion') >= Tine.Setup.CurrentTermsVersion;
        let acceptField = new Ext.form.Checkbox({
            name: 'acceptLicense',
            xtype: 'checkbox',
            boxLabel: this.app.i18n._('I have read the license agreement and accept it'),
            checked: accepted,
            disabled: accepted
        });
        this.acceptFields.push(acceptField);
        
        return new Ext.Panel({
            autoScroll: true,
            layout: 'fit',
            title: this.app.i18n._('License Agreement'),
            bwrapCfg: {tag: 'pre'},
            autoLoad: {
                url: 'LICENSE',
                isUpload: true,
                method: 'GET',
                callback: function(el, s, response) {
                    el.update(Ext.util.Format.nl2br(response.responseText));
                }
            },
            bbar: [acceptField]
        });
    },
    
    getPrivacyPanel: function() {
        let accepted = Tine.Setup.registry.get('acceptedTermsVersion') >= Tine.Setup.CurrentTermsVersion;
        let acceptField = new Ext.form.Checkbox({
            name: 'acceptPrivacy',
            xtype: 'checkbox',
            boxLabel: this.app.i18n._('I have read the privacy agreement and accept it'),
            checked: accepted,
            disabled: accepted
        });
        this.acceptFields.push(acceptField);
        
        return new Ext.Panel({
            autoScroll: true,
            layout: 'fit',
            title: this.app.i18n._('Privacy Agreement'),
            bwrapCfg: {tag: 'pre'},
            autoLoad: {
                url: 'PRIVACY',
                isUpload: true,
                method: 'GET',
                callback: function(el, s, response) {
                    el.update(Ext.util.Format.nl2br(response.responseText));
                }
            },
            bbar: [acceptField]
        });
    },
    
    initActions: function() {
        this.actionToolbar = new Ext.Toolbar({
            items: [{
                text: this.app.i18n._('Accept Terms and Conditions'),
                iconCls: 'setup_checks_success',
                handler: this.onAcceptConditions,
                scope: this
            }]
        });
    },
    
    initComponent: function() {
        this.initActions();
        
        this.acceptFields = [];
        this.items = [{
            layout: 'vbox',
            border: false,
            layoutConfig: {
                align:'stretch'
            },
            items: [{
                layout: 'fit',
                border: false,
                flex: 1,
                items: this.getLicensePanel()
            }, {
                layout: 'fit',
                border: false,
                flex: 1,
                items: this.getPrivacyPanel()
            }]
        }];
        
        this.supr().initComponent.call(this);
    },
    
    onAcceptConditions: function() {
        var isValid = true;
        
        Ext.each(this.acceptFields, function(field) {
            if (! field.getValue()) {
                field.wrap.setStyle('border-bottom', '1px solid red');
                isValid = false;
            } else {
                field.wrap.setStyle('border-bottom', 'none');
            }
        }, this);
        
        if (isValid) {
            Tine.Setup.registry.replace('acceptedTermsVersion', Tine.Setup.CurrentTermsVersion);
            this.saveAcceptTerms(Tine.Setup.CurrentTermsVersion);
        } else {
            Tine.log.notice('Terms not accepted.');
        }
    },

    saveAcceptTerms: function (data) {
        Ext.Ajax.request({
            scope: this,
            params: {
                method: this.saveMethod,
                data: data
            },
            success: function(response) {
            },
            failure: function(response, options) {
                let responseText = Ext.util.JSON.decode(response.responseText),
                  exception = responseText.data ? responseText.data : responseText;
                Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
            }
        });
    }
});

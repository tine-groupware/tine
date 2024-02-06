/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de> 
 * @copyright   Copyright (c) 2007-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.Tinebase');

/**
 * @namespace   Tine.Tinebase
 * @class       Tine.Tinebase.SubscriptionScreen
 * @extends     Ext.Window
 * @author      Ching En Cheng <c.cheng@metaways.de> 
 *   
 * @param {Object} config The configuration options.
 */

Tine.Tinebase.SubscriptionScreen = Ext.extend(Ext.Window, {
    
    closeAction: 'close',
    modal: true,
    width: 400,
    height: 360,
    minWidth: 400,
    minHeight: 360,
    layout: 'fit',
    title: null,
    
    /**
     * init component
     */
    initComponent: function() {
        this.title = i18n._('Subscription');
    
        const subscriptionData = (Tine.Tinebase.registry.get('licenseData')) ? Tine.Tinebase.registry.get('licenseData') : null;
        const licenseStatus = Tine.Tinebase.registry.get('licenseStatus');
        
        switch (licenseStatus) {
            case 'status_license_ok':
                if (subscriptionData) {
                    let featureString = '';
                    let moduleString = '';
        
                    if (subscriptionData.features) {
                        subscriptionData.features.sort();
            
                        _.each(subscriptionData.features, (feature) => {
                            if (feature.includes('.')) {
                                featureString += '<b>' + '*' + '</b> ' + feature + '<br/>';
                            } else {
                                moduleString += '<b>' + '-' + '</b> ' + feature + '<br/>';
                            }
                        })
                    }
                    
                    const validFrom = new Date(Date.parseDate(String(subscriptionData.validFrom.date).substr(0, 19), Date.patterns.ISO8601Long));
                    const validTo = new Date(Date.parseDate(String(subscriptionData.validTo.date).substr(0, 19), Date.patterns.ISO8601Long));
    
                    this.template = new Ext.XTemplate(
                        i18n._('Valid from') + ' : <b>' + validFrom.format('Y-m-d') + '</b>',
                        '<br>' + i18n._('Valid to')  + ' : <b>' + validTo.format('Y-m-d') + '</b>',
                        '<br>' + i18n._('Activation Key') + ' : <b>' + (subscriptionData.serialNumber ?? '') + '</b>',
                        '<br>' + i18n._('Maximum Users (0=unlimited)') + ' : <b>' + (subscriptionData.maxUsers ?? '') + '</b>',
                        '<br>' + i18n._('Organization') + ' : <b>' + (subscriptionData.organization ?? '') + '</b><br/>',
                        '<br>' + i18n._('Activated Features') + ' : <b><br/><br>',
                        moduleString,
                        '<br>' + featureString + '</b><br/>'
                    );
                } else {
                    this.template = new Ext.XTemplate(i18n._('Your Subscription is unavailable'));
                }
                break;
            case 'status_license_invalid':
                this.template = new Ext.XTemplate(i18n._('Your Subscription is not valid'));
                break;
            case 'status_no_license_available':
            default:
                this.template = new Ext.XTemplate(i18n._('Your Subscription is unavailable'));
                break;
        }
        
        this.template.compile();
        
        this.items = {
            layout: 'fit',
            border: false,
            padding: 10,
            autoScroll:true,
            html: this.template ?? '',
            buttons: [{
                text: i18n._('Ok'),
                iconCls: 'action_saveAndClose',
                handler: this.close,
                scope: this
            }]
        };
        
        Tine.Tinebase.SubscriptionScreen.superclass.initComponent.call(this);
    }
});

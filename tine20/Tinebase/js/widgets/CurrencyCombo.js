/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.widgets');

/**
 * Widget for Currency selection
 *
 * @namespace   Tine.widgets
 * @class       Tine.widgets.CurrencyCombo
 * @extends     Ext.form.ComboBox
 */
Tine.widgets.CurrencyCombo = Ext.extend(Ext.form.ComboBox, {
    fieldLabel: 'Currency',
    displayField:'translatedName',
    valueField:'shortName',
    typeAhead: true,
    forceSelection: true,
    mode: 'local',
    triggerAction: 'all',
    selectOnFocus:true,
    
    /**
     * @private
     */
    initComponent: function() {
        this.store = this.getCurrencyStore();
        this.emptyText = i18n._('Select a Currency...');

        // always set a default
        if (!this.value) {
            this.value = Tine.Tinebase.registry.get('config').defaultCurrency.value;
        }

        this.initTemplate();

        Tine.widgets.CurrencyCombo.superclass.initComponent.call(this);
    },

    /**
     * respect record.getTitle method
     */
    initTemplate: function() {
        if (! this.tpl) {
            this.tpl = new Ext.XTemplate(
                '<tpl for=".">',
                '<div class="x-combo-list-item">',
                '<table>',
                '<tr>',
                '<td>{[this.currencyRenderer(values)]}</td>',
                '</tr>',
                '</table>',
                '</div>',
                '</tpl>', this);
        }
    },

    currencyRenderer: function (currencyObject) {
        const values = String(currencyObject.translatedName).split(':');
        return `${currencyObject.shortName} - ${values[0]}`;
    },

    /**
     * @private store has static content
     */
    getCurrencyStore: function(){
        let store = Ext.StoreMgr.get('Currencies');
        if (!store) {
            store = new Ext.data.JsonStore({
                baseParams: {
                    method: 'Tinebase.getCurrencyList'
                },
                root: 'results',
                id: 'shortName',
                fields: Tine.Tinebase.Model.Currency,
                remoteSort: false,
                sortInfo: {
                    field: 'shortName',
                    direction: 'ASC'
                }
            });
            Ext.StoreMgr.add('Currencies', store);
        }

        const currencyList = Locale.getTranslationList('CurrencyList');
        if (currencyList) {
            const storeData = {results: []};
            for (const [shortName, translatedName] of Object.entries(currencyList)) {
                const values = String(translatedName).split(':');
                storeData.results.push({shortName: shortName, translatedName: values[0], symbol: values[1]});
            }
            store.loadData(storeData);
        }
        return store;
    }
});

Ext.reg('widget-currencycombo', Tine.widgets.CurrencyCombo);

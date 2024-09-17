/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.HumanResources');

const colorLegend = Ext.extend(Ext.Panel, {
    layout: 'fit',
    initComponent() {
        this.app = Tine.Tinebase.appMgr.get('HumanResources')
        this.title = this.app.i18n._('Legend');

        this.items = new Tine.HumanResources.FreeTimeTypeGridPanel({
            // height: 500,
            usePagingToolbar: false,
            detailsPanel: false,
            stateIdSuffix: '-Legend',
            initActions: Ext.emptyFn,
            initFilterPanel: Ext.emptyFn,
            showColumns: ['color', 'name'],
            hideMode: 'delete',
            gridConfig: {
                disableResponsiveLayout: true,
            },
            initComponent: function() {
                this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('HumanResources');

                // allow dbclick to open
                this.action_editInNewWindow = new Ext.Action({hidden: true});

                Tine.HumanResources.FreeTimeTypeGridPanel.prototype.initComponent.call(this);
                this.store.on('load', () => {
                    this.setHeight(this.grid.view.el.child('.x-grid3-body').getHeight() + this.grid.view.el.child('.x-grid3-header').getHeight());
                })
            },

            onStoreBeforeload: function(store, options) {
                Tine.HumanResources.FreeTimeTypeGridPanel.prototype.onStoreBeforeload.call(this, store, options);
                options.params.filter.push({field: 'allow_planning', operator: 'equals', value: true});
            }
        })

        colorLegend.superclass.initComponent.call(this);
    },


})

Ext.ux.ItemRegistry.registerItem('Tine.HumanResources.FreeTimePlanning.WestPanelPortalColumn', colorLegend);
export  {colorLegend};

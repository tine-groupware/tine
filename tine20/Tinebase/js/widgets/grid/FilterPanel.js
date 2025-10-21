/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.grid');

Tine.widgets.grid.FilterPanel = function(config) {
    this.filterToolbarConfig = config;
    Ext.copyTo(this, config, 'useQuickFilter, quickFilterConfig, syncFields, stateIdSuffix');

    // the plugins won't work there
    delete this.filterToolbarConfig.plugins;
    
    // apply some filterPanel configs
    Ext.each(['onFilterChange', 'getAllFilterData'], function(p) {
        if (config.hasOwnProperty(p)) {
            this[p] = config[p];
        }
    }, this);
    
    // become filterPlugin
    Ext.applyIf(this, new Tine.widgets.grid.FilterPlugin());
    
    this.filterToolbars = [];
    
    this.addEvents(
        /**
         * @event filterpaneladded
         * Fires when a filterPanel is added
         * @param {Tine.widgets.grid.FilterPanel} this
         * @param {Tine.widgets.grid.FilterToolbar} the filterPanel added
         */
        'filterpaneladded',
        
        /**
         * @event filterpanelremoved
         * Fires when a filterPanel is removed
         * @param {Tine.widgets.grid.FilterPanel} this
         * @param {Tine.widgets.grid.FilterToolbar} the filterPanel removed
         */
        'filterpanelremoved',
        
        /**
         * @event filterpanelactivate
         * Fires when a filterPanel is activated
         * @param {Tine.widgets.grid.FilterPanel} this
         * @param {Tine.widgets.grid.FilterToolbar} the filterPanel activated
         */
        'filterpanelactivate'
    );
    Tine.widgets.grid.FilterPanel.superclass.constructor.call(this, {});
};

Ext.extend(Tine.widgets.grid.FilterPanel, Ext.Panel, {

    /**
     * @property activeFilterPanel
     * @type Tine.widgets.grid.FilterToolbar
     */
    activeFilterPanel: null,
    
    /**
     * @property filterPanels map filterPanelId => filterPanel
     * @type Object
     */
    filterPanels: null,
    
    /**
     * @property criteriaCount
     * @type Number
     */
    criteriaCount: 0,

    useQuickFilter: true,
    stateIdSuffix: '',

    cls: 'tw-ftb-filterpanel',
    layout: 'border',
    border: false,

    /**
     * We expect the filter panel to be layouted
     */
    forceLayout: true,
    
    syncFields: true,
    
    initComponent: function() {
        const filterToolbar = this.addFilterToolbar();
        this.filterModelMap = filterToolbar.filterModelMap;
        this.activeFilterPanel = filterToolbar;
        
        // this.initQuickFilterField();

        this.advancedSearchEnabled = Tine.Tinebase.featureEnabled('featureShowAdvancedSearch') &&
            this.filterToolbarConfig.app.enableAdvancedSearch;

        this.recordClass = this.filterToolbarConfig.recordClass;

        this.items = [{
            region: 'east',
            width: 200,
            border: false,
            layout: 'fit',
            split: true,
            items: [new Tine.widgets.grid.FilterStructureTreePanel({filterPanel: this})]
        }, {
            region: 'center',
            border: false,
            layout: 'card',
            activeItem: 0,
            items: [filterToolbar],
            autoScroll: false,
            listeners: {
                scope: this,
                afterlayout: this.manageHeight
            }
        }];
        
        Tine.widgets.grid.FilterPanel.superclass.initComponent.call(this);
    },
    
    /**
     * is persiting this filterPanel is allowed
     * 
     * @return {Boolean}
     */
    isSaveAllowed: function() {
        return this.activeFilterPanel.allowSaving;
    },

    getAllFilterData: Tine.widgets.grid.FilterToolbar.prototype.getAllFilterData,
    storeOnBeforeload: Tine.widgets.grid.FilterToolbar.prototype.storeOnBeforeload,

    onFilterRowsChange: function() {
        this.activeFilterPanel.doLayout();
        this.manageHeight();
    },

    onFiltertrigger: function() {
        this.activeFilterPanel.onFiltertrigger();
    },

    manageHeight: function() {
        if (this.rendered && this.activeFilterPanel.rendered) {
            var tbHeight = this.activeFilterPanel.getHeight(),
                northHeight = this.layout.north ? this.layout.north.panel.getHeight() + 1 : 0,
                eastHeight = this.layout.east && this.layout.east.panel.getEl().child('ul') ? ((this.layout.east.panel.getEl().child('ul').getHeight()) + 29) : 0,
                maxHeight = Math.round(this.findParentBy((c) => {return c.recordClass}).getHeight() / 4),
                height = Math.min(Math.max(eastHeight, tbHeight + northHeight), maxHeight, Ext.isNumber(this.maxHeight) ? this.maxHeight : Infinity);
            
            this.setHeight(height);

            // manage scrolling
            if (this.layout.center && tbHeight > maxHeight) {
                this.layout.center.panel.el.child('div[class^="x-panel-body"]', true).scrollTop = 1000000;
                this.layout.center.panel.el.child('div[class^="x-panel-body"]', false).applyStyles('overflow-y: auto');
            }
            if (this.layout.east && eastHeight > maxHeight) {
                this.layout.east.panel.el.child('div[class^="x-panel-body"]', true).scrollTop = 1000000;
            }
            this.ownerCt.layout.layout();
        }
    },
    
    onAddFilterToolbar: function() {
        const filterToolbar = this.addFilterToolbar();
        this.setActiveFilterToolbar(filterToolbar);
    },
    
    addFilterToolbar: function(config) {
        config = config || {};
        const filterToolbar = new Tine.widgets.grid.FilterToolbar(Ext.apply({}, this.filterToolbarConfig, config));
        filterToolbar.onFilterChange = this.onFilterChange.createDelegate(this);
        if (this.useQuickFilter) {
            if (!this.quickFilterPlugin) {
                this.quickFilterPlugin = new Tine.widgets.grid.FilterToolbarQuickFilterPlugin(Ext.apply({
                    syncFields: this.syncFields,
                    stateIdSuffix: this.stateIdSuffix,
                }, this.quickFilterConfig));
                this.quickFilterPlugin.init(filterToolbar, this);
            } else {
                this.quickFilterPlugin.initFilterToolbar(filterToolbar);
            }
        }
        this.filterToolbars[filterToolbar.id] = filterToolbar;
        this.criteriaCount++;
        
        if (this.criteriaCount > 1 && filterToolbar.title === filterToolbar.generateTitle()) {
            filterToolbar.setTitle(filterToolbar.title + ' ' + this.criteriaCount);
        }
        this.fireEvent('filterpaneladded', this, filterToolbar);
        return filterToolbar;
    },
    
    /**
     * remove filter panel
     * 
     * @param {mixed} filterToolbar
     */
    removeFilterToolbar: function(filterToolbar) {
        filterToolbar = Ext.isString(filterToolbar) ? this.filterToolbars[filterToolbar] : filterToolbar;
        
        if (! this.filterToolbars[filterToolbar.id].destroying) {
            this.filterToolbars[filterToolbar.id].destroy();
        }
        
        delete this.filterToolbars[filterToolbar.id];
        this.criteriaCount--;
        
        this.fireEvent('filterpanelremoved', this, filterToolbar);
        
        for (const id in this.filterToolbars) {
            if (this.filterToolbars.hasOwnProperty(id)) {
                return this.setActiveFilterToolbar(this.filterToolbars[id]);
            }
        }
    },
    
    setActiveFilterToolbar: function(filterToolbar) {
        filterToolbar = Ext.isString(filterToolbar) ? this.filterToolbars[filterToolbar] : filterToolbar;
        this.activeFilterPanel = filterToolbar;

        if (this.layout.center) {
            this.layout.center.panel.add(filterToolbar);
            this.layout.center.panel.layout.setActiveItem(filterToolbar.id);
        }
        
        filterToolbar.doLayout();

        // solve layout problems (#6332)
        let parentSheet = filterToolbar;
        let activeSheet = parentSheet.activeSheet;
        while (activeSheet && activeSheet.activeSheet !== activeSheet) {
            parentSheet = activeSheet;
            activeSheet = activeSheet.activeSheet;
        }
        if (activeSheet) {
            parentSheet.setActiveSheet(activeSheet);
        }
        
        this.manageHeight.defer(100, this);
        
        this.fireEvent('filterpanelactivate', this, filterToolbar);
    },

    getQuickFilterField: function() {
        return this.quickFilterPlugin.getQuickFilterField();
    },

    getQuickFilterPlugin: function() {
        return this.quickFilterPlugin;
    },

    getValue: function() {
        const filters = [];
        
        for (let id in this.filterToolbars) {
            if (this.filterToolbars.hasOwnProperty(id) && this.filterToolbars[id].isActive) {
                if (this.quickFilterPlugin) this.quickFilterPlugin.ftb = this.filterToolbars[id];
                const filterData = this.filterToolbars[id].getValue();
                filters.push({'condition': 'AND', 'filters': filterData, 'id': id, label: Ext.util.Format.htmlDecode(this.filterToolbars[id].title)});
            }
        }
        
        // NOTE: always trigger an OR condition, otherwise we could lose inactive FilterPanels
        return [{'condition': 'OR', 'filters': filters, id: 'FilterPanel'}];
    },

    setValue: function(value) {
        let id;
// save last filter ?
        let prefs;
        if ((prefs = this.filterToolbarConfig.app.getRegistry().get('preferences')) && prefs.get('defaultpersistentfilter') === '_lastusedfilter_') {
            const lastFilterStateName = this.filterToolbarConfig.recordClass.getMeta('appName') + '-' + this.filterToolbarConfig.recordClass.getMeta('recordName') + this.stateIdSuffix + '-lastusedfilter';
            
            if (Ext.encode(Ext.state.Manager.get(lastFilterStateName)) !== Ext.encode(value)) {
                Tine.log.debug('Tine.widgets.grid.FilterPanel::setValue save last used filter');
                Ext.state.Manager.set(lastFilterStateName, value);
            }
        }
        
        // NOTE: value is always an array representing a filterGroup with condition AND (server limitation)!
        //       so we need to route "alternate criterias" (OR on root level) through this filterGroup for transport
        //       and scrape them out here -> this also means we whipe all other root level filters (could only be implicit once)
        let alternateCriterias = false;
        Ext.each(value, function(filterData) {
            if (filterData.condition && filterData.condition === 'OR') {
                value = filterData.filters;
                alternateCriterias = true;
                return false;
            }
        }, this);
        
        if (! alternateCriterias) {
            // reset criterias
            this.activeFilterPanel.setTitle(this.activeFilterPanel.generateTitle());
            for (id in this.filterToolbars) {
                if (this.filterToolbars.hasOwnProperty(id)) {
                    if (this.filterToolbars[id] !== this.activeFilterPanel) {
                        this.removeFilterToolbar(this.filterToolbars[id]);
                    }
                }
            }
            
            this.activeFilterPanel.setValue(value);
        } 
        
        // OR condition on root level
        else {
            const keepFilterPanels = [],
                activeFilterPanel = this.activeFilterPanel;
            
            Ext.each(value, function(filterData) {
                let filterToolbar;
                
                // refresh existing filter panel
                if (filterData.id && this.filterToolbars.hasOwnProperty(filterData.id)) {
                    filterToolbar = this.filterToolbars[filterData.id];
                }
                
                // create new filterPanel
                else {
                    // NOTE: don't use filterData.id here, it's a ext-comp-* which comes from a different session
                    // and might be a totally different element yet.
                    filterToolbar = this.addFilterToolbar();
                    this.setActiveFilterToolbar(filterToolbar);
                }
                
                filterToolbar.setValue(filterData.filters || []);
                keepFilterPanels.push(filterToolbar.id);
                
                if (filterData.label) {
                    filterToolbar.setTitle(Ext.util.Format.htmlEncode(filterData.label));
                }
                
            }, this);
            
            // (re)activate filterPanel
            this.setActiveFilterToolbar(keepFilterPanels.indexOf(activeFilterPanel.id) > 0 ? activeFilterPanel : keepFilterPanels[0]);
            
            // remove unused panels
            for (id in this.filterToolbars) {
                if (this.filterToolbars.hasOwnProperty(id) && keepFilterPanels.indexOf(id) < 0 && this.filterToolbars[id].isActive === true) {
                    this.removeFilterToolbar(id);
                }
            }
        }
    }
});

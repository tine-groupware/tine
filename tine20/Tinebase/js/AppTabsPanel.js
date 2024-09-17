/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Tinebase');

require('ux/TabPanelStripCompressorPlugin');

/**
 * Main appStarter/picker tab panel
 * 
 * @todo discuss: have a set of default apps?
 * 
 * @namespace   Tine.Tinebase
 * @class       Tine.Tinebase.AppTabsPanel
 * @extends     Ext.TabPanel
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.Tinebase.AppTabsPanel = function(config) {
    Ext.apply(this, config);
    this.plugins = [new Ext.ux.TabPanelSortPlugin({
        dragZoneConfig: {
            onBeforeDrag: this.onBeforeDrag.createDelegate(this),
            scroll: false
        },
        dropZoneConfig: {
            getTargetFromEvent: this.getTargetFromEvent.createDelegate(this)
        }
    }), 'ux.tabpanelstripcompressorplugin'];
    
    Tine.Tinebase.AppTabsPanel.superclass.constructor.call(this, config);
};

Ext.extend(Tine.Tinebase.AppTabsPanel, Ext.TabPanel, {
    stateful: true,
    stateEvents: ['add', 'remove', 'tabchange', 'tabsort'],
    stateId: 'tinebase-mainscreen-apptabs',
    
    /**
     * @cfg {Array} of Strings currentTab ids
     */
    currentTabs: null,
    
    // private
    findTargets : function(e){
        var item = null,
            itemEl = e.getTarget('li:not(.x-tab-edge)', this.strip);

        if(itemEl){
            item = this.getComponent(itemEl.id.split(this.idDelimiter)[1]);
            if(item && item.disabled){
                return {
                    close : null,
                    item : null,
                    el : null
                };
            }
        }
        return {
            close : e.getTarget('.x-tab-strip-close', this.strip),
            item : item,
            el : itemEl
        };
    },
    
    /**
     * init appTabsPanel
     */
    initComponent: function() {
        Ext.apply(this, Ext.state.Manager.get(this.stateId));
        
        this.initMenu();
        
        this.items = [{
            id: this.app2id('menu'),
            title: Tine.title,
            iconCls: 'tine-favicon',
            closable: true,
            noCompress: true,
            listeners: {
                scope: this,
                beforeclose: this.onBeforeTabClose
            }
        }].concat(this.getDefaultTabItems());
        
        // set states last active app to the sessions default app
        //Tine.Tinebase.appMgr.setDefault(this.id2appName(this.activeTab));

        Tine.Tinebase.MainScreen.on('appactivate', this.onActivateApp, this);
        this.on('beforetabchange', this.onBeforeTabChange, this);
        this.on('tabsort', this.onTabChange, this);
        this.on('add', this.onTabChange, this);
        this.on('remove', this.onTabChange, this);
        
        this.supr().initComponent.call(this);
        
        // fake an access stack
        for (var i=1, tabCount=this.items.getCount(); i<tabCount; i++) {
            this.stack.add(this.items.get(i));
        }
    },

    setActiveTab : function(item) {
        this.supr().setActiveTab.apply(this, arguments);

        var appName = this.id2appName(item);
        if (appName !== 'menu') {
            var app = Tine.Tinebase.appMgr.get(appName),
                activeApp = Tine.Tinebase.MainScreen.getActiveApp();

            if (app != activeApp) {
                Tine.Tinebase.router.setRoute(app.getRoute());
            }
        }
    },

    /**
     * init the combined appchooser/tine menu
     */
    initMenu: function() {
        this.appSearchField = new Ext.form.TextField({
            width: '100%',
            cls: 'x-form-field-wrap',
            emptyText: i18n._('Search for Application ...'),
            enableKeyEvents: true,
            listeners: {
                keyup: (f) => {
                    const v = f.getRawValue();
                    this.menu.items.get(0).items.each((appItem) => {
                        if (appItem !== f) {
                            appItem.setVisible(!v || appItem.text.match(new RegExp(v, 'i')));
                        }
                    });
                }
            }
        });
        this.menu = new Ext.menu.Menu({
            layout: 'column',
            width: 400,
            autoHeight: true,
            style: {
                'background-image': 'none'
            },
            defaults: {
                xtype: 'menu',
                floating: false,
                columnWidth: 0.5,
                hidden: false,
                listeners: {
                    scope: this,
                    itemclick: function(item, e) {
                        this.menu.hide();
                    }
                },
                style: {
                    //'border-color': 'transparent'
                    'border': '0'
                }
            },
            items: [{
                items: [this.appSearchField].concat(this.appItems = this.getAppItems()),
                style: {'border-right': '1px solid #E2E2E3'}
            }, {
                plugins: [{
                    ptype: 'ux.itemregistry',
                    key:   'Tine.Tinebase.AppMenu.Additionals'
                }],
                items: Tine.Tinebase.MainScreen.getMainMenu().getMainActions()
            }]
        });
    },
    
    /**
     * executed after render
     */
    afterRender: function() {
        // no state from tab!
        this.activeTab = undefined;

        this.supr().afterRender.apply(this, arguments);
        
        this.menuTabEl = Ext.get(this.getTabEl(0));
        this.menuTabEl.addClass('tine-mainscreen-apptabspanel-menu-tabel');
        
        // remove plain style
        this.header.removeClass('x-tab-panel-header-plain');
    },
    
    /**
     * get app items for the tabPanel
     * 
     * @return {Array}
     */
    getAppItems: function() {
        var appItems = [];
        Tine.Tinebase.appMgr.getAll().each(function(app) {
            if (Tine.Tinebase.common.hasRight('mainscreen', app.appName) && app.hasMainScreen) {
                appItems.push({
                    text: app.getTitle(),
                    iconCls: app.getIconCls(),
                    handler: this.onAppItemClick.createDelegate(this, [app])
                });
            }
        }, this);
        
        return _.sortBy(appItems, 'text');
    },
    
    /**
     * get default tab items configurations
     * 
     * @return {Array}
     */
    getDefaultTabItems: function() {
        if (Ext.isEmpty(this.currentTabs)) {
            this.currentTabs = [this.id2appName(Tine.Tinebase.appMgr.getDefault())];
        }
        
        var tabItems = [];
        
        Ext.each(this.currentTabs, function(appName) {
            var app = Tine.Tinebase.appMgr.get(appName);
            if (app) {
                tabItems.push(this.getTabItem(app));
            }
        }, this);
        
        return tabItems;
    },
    
    /**
     * deny drag on menuEl
     * @param {} e
     * @return {}
     */
    onBeforeDrag: function(data, e) {
        return e.getTarget('li[class*=mainscreen-apptabspanel-menu-tabel]', 10) ? false : true;
    },
    
    /**
     * deny drop on menuEl
     * @param {} e
     * @return {}
     */
    getTargetFromEvent: function(e) {
        var target = e.getTarget('ul[class^=x-tab]', 10),
            li = this.findTargets(e);
            
        if (li.el && li.el == this.menuTabEl.dom) {
            return false;
        }
        
        return target;
    },
            
    /**
     * get tabs state
     * 
     * @return {Object}
     */
    getState: function() {
        return {
            currentTabs: this.currentTabs,
            activeTab: Ext.isNumber(this.activeTab) ? this.activeTab : this.items.indexOf(this.activeTab)
        };
    },
    
    /**
     * get tab item configuration
     * 
     * @param {Tine.Application} app
     * @return {Object}
     */
    getTabItem: function(app) {
        return {
            id: this.app2id(app),
            title: app.getTitle(),
            iconCls: app.getIconCls(),
            closable: true,
            listeners: {
                scope: this,
                beforeclose: this.onBeforeTabClose
            }
        };
    },
    
    /**
     * executed when an app get activated by mainscreen
     * 
     * @param {Tine.Application} app
     */
    onActivateApp: function(app) {
        var tab = this.getItem(this.app2id(app)) || this.add(this.getTabItem(app));
        
        this.setActiveTab(tab);
    },
    
    /**
     * executed when an app item in this.menu is clicked
     * 
     * @param {Tine.Application} app
     */
    onAppItemClick: function(app) {
        Tine.Tinebase.MainScreen.activate(app);
        
        this.menu.hide();
    },
    
    /**
     * executed on tab changes
     * 
     * @param {TabPanel} this
     * @param {Panel} newTab The tab being activated
     * @param {Panel} currentTab The current active tab
     */
    onBeforeTabChange: function(tp, newTab, currentTab) {
        if (this.id2appName(newTab) === 'menu') {
            this.menu[this.menu.isVisible() ? 'hide' : 'show'].defer(10, this.menu, [this.menuTabEl, 'tl-bl']);
            this.appSearchField.reset();
            this.menu.items.get(0).items.each((appItem) => {
                    appItem.setVisible(true);
            });
            return false;
        }
    },
    
    /**
     * executed before a tab panel is closed
     * 
     * @param {Ext.Panel} tab
     * @return {boolean}
     */
    onBeforeTabClose: function(tab) {
        if (this.id2appName(tab) === 'menu') {
            return this.onBeforeTabChange(this, tab, this.activeTab);
        }
        
        // don't close last app panel
        return this.items.getCount() > 2;
    },
    
    /**
     * executed when tabs chages
     */
    onTabChange: function() {
        var tabCount = this.items.getCount();
        var closable = tabCount > 2;
        
        this.currentTabs = [];
        
        for (var i=1, tab, el; i<tabCount; i++) {
            tab = this.items.get(i);
            
            // update currentTabs
            this.currentTabs.push(this.id2appName(tab.id));
            
            // handle closeables
            tab.closable = closable;
            el = this.getTabEl(i);
            if (el) {
                Ext.get(el)[closable ? 'addClass' : 'removeClass']('x-tab-strip-closable');
            }
        }
    },
    
    /**
     * returns appName of given tab/id
     * 
     * @param {Ext.Panel/String/Number} id
     * @return {String} appName
     */
    id2appName: function(id) {
        if (Ext.isNumber(id)) {
            if (Ext.isArray(this.items)) {
                id = this.items[id] ? this.items[id].id : null;
            } else {
                id = this.items.get(id);
            }
        }
        
        if (Ext.isObject(id) && ! Ext.isEmpty(id)) {
            id = id.id;
        }
        
        if (Ext.isString(id)) {
            return id.split('-').pop();
        }
        
        return null;
    },
    
    /**
     * returns tab id of given app
     * @param {Tine.Application/String} app
     */
    app2id: function(app) {
        var appName = app.appName || app;
        
        return this.id + '-' + appName;
    }
});

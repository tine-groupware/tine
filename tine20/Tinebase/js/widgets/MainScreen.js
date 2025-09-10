/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets');

/**
 * @namespace   Tine.widgets
 * @class       Tine.widgets.MainScreen
 * @extends     Ext.Panel
 *
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */
Tine.widgets.MainScreen = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Tine.Tinebase.Application} app
     * instance of the app object (required)
     */
    app: null,
    /**
     * @cfg {String} activeContentType
     */
    activeContentType: null,
    /**
     * @cfg {Array} contentTypes
     */
    contentTypes: null,
    /**
     * @cfg {String} centerPanelClassName
     * name of centerpanel class name suffix in namespace of this app (defaults to GridPanel)
     * the class name will be expanded to Tine[this.appName][contentType + this.centerPanelClassNameSuffix]
     */
    centerPanelClassNameSuffix: 'GridPanel',
    /**
     * @cfg {Bool} useModuleTreePanel
     * use modulePanel (defaults to null -> autodetection)
     */
    useModuleTreePanel: null,
    /**
     * @cfg {Number} northHeight
     */
    northHeight: 44,
    /**
     * @cfg {Number} westWidth
     */
    westWidth: 200,
    
    layout: 'border',
    border: false,
    stateful: true,
    xtype: 'Tine.widgets.MainScreen',

    initComponent: function() {
        var registeredContentTypes = _.get(Tine.widgets.MainScreen.registerContentType, 'registry.' + this.app.appName, []);
        this.contentTypes = (this.contentTypes || []).concat(registeredContentTypes);

        this.useModuleTreePanel = Ext.isArray(this.contentTypes) && this.contentTypes.length > 1;
        this.initLayout();
        this.initMessageBus();
        
        if (this.cls) {
            this.cls = this.cls + ' ' + 't-app-' + this.app.appName.toLowerCase();
        } else {
            this.cls = 't-app-' + this.app.appName.toLowerCase();
        }
        
        this.stateId = this.app.appName + '-mainScreen';

        this.on('resize', this.applyResponsiveLayout, this);

        this.tbar = {
            hidden: true,
            cls: 'tine-mainscreen-grid-responsive-tbar',
            items:
                [{
                    iconCls: 'tine-mainscreen-grid-responsive-func-menu',
                    handler: () => {
                        !this.layout.west.isCollapsed ? this.westRegionPanel.collapse() : Ext.emptyFn() ;
                        this.layout.west.slideOut()
                    }
                }, new Ext.Container({
                    ref: '../searchbarpanel',
                    layout:'card',
                    activeItem: 0,
                    defaults: {
                        border:false
                    },
                    items: []
                }), '->', {
                    iconCls: 'tine-mainscreen-grid-responsive-nav-menu',
                    handler: (btn) => {
                        const tbar = _.get(_.get(this.northCardPanel, 'layout.activeItem'), 'items.items[0]');
                        if (tbar) {
                            tbar.layout.initMore();
                            const moreMenu = tbar.layout.moreMenu;
                            moreMenu.showAll = true;
                            moreMenu[new Date().getTime() - moreMenu.lastVisible < 100 ? 'hide' : 'show'](btn.el);
                            moreMenu.showAll = false;
                        }
                    }
                }]
        }

        Tine.widgets.MainScreen.superclass.initComponent.apply(this, arguments);
    },

    applyResponsiveLayout: function(me, width, height) {
        // @TODO centralize breakpoints / have more breakpoints
        if (!this._initResponsiveLayout) {
            this.northCardPanel.on('beforeshow', () => {
                return  1024 < this.getWidth();
            })
            this._initResponsiveLayout = true
        }

        const isSmall = width < 1024;
        this.westRegionPanel.afterIsRendered().then((panel) => { panel[isSmall ? 'collapse' : 'expand']() });
        this.northCardPanel.afterIsRendered().then((panel) => {panel.setVisible(!isSmall) });

        this.westRegionPanel.on('click', () => { })

        _.defer(() => {
            // NOTE: we need to defer as legacy apps init filter toolbar after mainscreen
            this.getTopToolbar().setVisible(isSmall);
            const qfp = this.getCenterPanel()?.filterToolbar?.getQuickFilterPlugin();
            qfp?.setDetailsHidden(isSmall || qfp.detailsToggleBtn.pressed);
            this.resizeRespFilterBar(isSmall, qfp, me);
            this.doLayout()
        }, true);
    },

    resizeRespFilterBar: (isSmall, qfp, mainscreen) => {
        if (mainscreen.tbar) {
            const searchbar = mainscreen.searchbarpanel.getActionEl();
            const tbarChildren = mainscreen.searchbarpanel.ownerCt.items.items;

            let box1 = tbarChildren[0].getEl();
            let box2 = tbarChildren[3].getEl();

            if (box1 && box2) {
                let totalWidth = box2.getBox().x - box1.getBox().x - box1.getBox().width;
    
                searchbar.setWidth(totalWidth - 50);
            } else {
                console.warn("responsive dropdowns not found");
            }
        }
    },

    onDestroy: function() {
        _.each(this.postalSubscriptions, (subscription) => {subscription.unsubscribe()});
        return this.supr().onDestroy.call(this);
    },
    
    initMessageBus: function() {
        this.postalSubscriptions = [];
        // NOTE: it should be enough to manage on contentType level?
        // _.each(Tine.Tinebase.areaLocks.getLocks(this.app.appName), (areaLock) => {
        //     this.postalSubscriptions.push(postal.subscribe({
        //         channel: "areaLocks",
        //         topic: areaLock + '.*',
        //         callback: this.onAreaLockChange.createDelegate(this)
        //     }));
        // });
    },

    onAreaLockChange: function(data, e) {
        var topic = e.topic,
            locked = !!Tine.Tinebase.areaLocks.getLocks(this.app.appName, true).length,
            cp = this.getCenterPanel(),
            grid = cp ? cp.getGrid() : null,
            store = grid.getStore();

        // shouldn't this be done by the gird itself?
        if (locked) {
            store.removeAll();
            // @TODO: quit bg refresh task?
        } else {
            store.reload();
        }
    },

    /**
     * returns canonical path part
     * @returns {string}
     */
    getCanonicalPathSegment: function () {
        if (this.app) {
            return ['',
                this.app.name,
                'MainScreen'
            ].join(Tine.Tinebase.CanonicalPath.separator);
        }
    },

    getState: function() {
        return {
            westWidth: this.westRegionPanel.getWidth(),
            northHeight: this.northCardPanel.getHeight()
        };
    },
    
    applyState: function(state) {
        this.westRegionPanel.setWidth(state.westWidth);
        if (state.northHeight > 0) {
            // NOTE: in some wired cases the state is 0 - and the user has no option to resize it
            this.northCardPanel.setHeight(state.northHeight);
        }
    },
    
    /**
     * @private
     */
    initLayout: function() {
        this.items = [{
            ref: 'northCardPanel',
            cls: 'tine-mainscreen-centerpanel-north',
            region: 'north',
            layout: 'card',
            activeItem: 0,
            height: this.northHeight,
            border: false,
            items: []
        }, {
            ref: 'centerCardPanel',
            cls: 'tine-mainscreen-centerpanel-center',
            region: 'center',
            animate: true,
            border: false,
            layout: 'card',
            activeItem: 0,
            defaults: {
                hideMode: 'offsets'
            },
            items: []
        }, {
            ref: 'westRegionPanel',
            cls: 'tine-mainscreen-centerpanel-west',
            region: 'west',
            //id: 'west',
            stateful: false,
            split: true,
            width: this.westWidth,
            minSize: 100,
            border: false,
            collapsible:true,
            collapseMode: 'mini',
            header: false,
            layout: 'fit',
            listeners: {
                scope: this,
                resize: this.saveState,
                afterrender: function() {
                    // add to scrollmanager
                    if (arguments[0] && arguments[0].hasOwnProperty('body')) {
                        Ext.dd.ScrollManager.register(arguments[0].body);
                    }
                }
            },
            autoScroll: true,
            tbar: [{
                buttonAlign : 'center'
            }],
            items: [{
                ref: '../moduleCardPanel',
                cls: 'tine-mainscreen-centerpanel-west-modules',
                border: false,
                autoScroll: false,
                autoHeight: true,
                style: {
                    width: '100%'
                },
                layout: 'card',
                activeItem: 0,
                items: []
            }, {
                ref: '../westCardPanel',
                cls: 'tine-mainscreen-centerpanel-west-treecards',
                border: false,
                style: {
                    width: '100%'
                },
                autoScroll: false,
                layout: 'card',
                activeItem: 0,
                items: []
            }]
         }];
    },

    afterRender: function() {
        Tine.widgets.MainScreen.superclass.afterRender.call(this);

        // Tine.Tinebase.areaLocks.registerMaskEl(this.app.appName, this.getEl());
        // _.each(Tine.Tinebase.areaLocks.getLocks(this.app.appName), (areaLock) => {
        //     Tine.Tinebase.areaLocks.unlock(areaLock);
        // });
        this.setActiveContentType(this.activeContentType);
    },

    /**
     * returns active content type
     * 
     * @return {String}
     */
    getActiveContentType: function() {
        return (this.activeContentType) ? this.activeContentType : '';
    },

    getContentTypeDefinition: function(contentType) {
        var _ = window.lodash;

        return _.find(this.contentTypes, {contentType: contentType}) ||
            _.find(this.contentTypes, {model: contentType}) ||
            _.find(this.contentTypes, {modelName: contentType});
    },

    /**
     * get center panel for given contentType
     * 
     * @param {String} contentType
     * @return {Ext.Panel}
     */
    getCenterPanel: function(contentType) {
        contentType = contentType || this.getActiveContentType();
        // if (!contentType) return;

        var def = this.getContentTypeDefinition(contentType) || {},
            suffix = def && def.xtype ? '' : this.centerPanelClassNameSuffix;

        if (! this[contentType + suffix]) {
            // try {
                if (def && def.xtype) {
                    this[contentType + suffix] = Ext.create(def);
                } else if (Tine[def.appName || this.app.appName][contentType + suffix]) {
                    this[contentType + suffix] = new Tine[def.appName || this.app.appName][contentType + suffix](_.merge({
                        app: def.appName ? Tine.Tinebase.appMgr.get(def.appName) : this.app,
                        mainScreen: this,
                        plugins: (() => {
                            const wp = this.getWestPanel();
                            return wp && wp.getFilterPlugin ? [wp.getFilterPlugin(contentType)] : []
                        })()
                    }, def?.config || {}));
                } else {
                    return null;
                }

                if (this[contentType + suffix].cls) {
                    this[contentType + suffix].cls = this[contentType + suffix].cls + ' t-contenttype-' + contentType.toLowerCase();
                } else {
                    this[contentType + suffix].cls = 't-contenttype-' + contentType.toLowerCase();
                }
            // } catch (e) {
            //     Tine.log.error('Could not create centerPanel "Tine.' + this.app.appName + '.' + contentType + suffix + '"');
            //     Tine.log.error(e.stack ? e.stack : e);
            //     this[contentType + suffix] = new Ext.Panel({html: 'ERROR'});
            // }
        }

        return this[contentType + suffix];
    },

    /**
     * get north panel for given contentType
     * 
     * @param {String} contentType
     * @return {Ext.Panel}
     */
    getNorthPanel: function(contentType) {
        contentType = contentType || this.getActiveContentType();
        
        if (! this[contentType + 'ActionToolbar']) {
            try {
                var cp = this.getCenterPanel(contentType);
                if (Ext.isFunction(cp.getActionToolbar)) {
                    this[contentType + 'ActionToolbar'] = cp.getActionToolbar();
                    
                    if (this[contentType + 'ActionToolbar'].cls) {
                        this[contentType + 'ActionToolbar'].cls = this[contentType + 'ActionToolbar'].cls + ' t-contenttype-' + contentType.toLowerCase();
                    } else {
                        this[contentType + 'ActionToolbar'].cls = 't-contenttype-' + contentType.toLowerCase();
                    }
                }
            } catch (e) {
                Tine.log.error('Could not create northPanel');
                Tine.log.error(e.stack ? e.stack : e);
                this[contentType + 'ActionToolbar'] = new Ext.Panel({html: 'ERROR'});
            }
        }

        return this[contentType + 'ActionToolbar'];
    },
    
    /**
     * get module tree panel
     * 
     * @return {Ext.Panel}
     */
    getModuleTreePanel: function() {
        if (! this.moduleTreePanel) {
            if (this.useModuleTreePanel) {
                this.moduleTreePanel = new Tine.widgets.ContentTypeTreePanel({
                    app: this.app,
                    mainScreen: this,
                    contentTypes: this.contentTypes,
                    contentType: this.getActiveContentType()
                });
                var me = this;
                this.moduleTreePanel.on('click', function (node, event) {
                    // NOTE: 'this' is moduleTreePanel here (no scope provided)
                    if(node != this.lastClickedNode) {
                        this.lastClickedNode = node;
                        this.fireEvent('selectionchange');
                    } else if (me.getWestPanel().hasFavoritesPanel) {
                        // select default favorite
                        // NOTE: a lot of models don't have a default favorite defined...
                        me.getWestPanel().getFavoritesPanel().selectDefault();
                    }
                });
            } else {
                this.moduleTreePanel = new Ext.Panel({html:'', border: false, frame: false});
            } 
        }
        return this.moduleTreePanel;
    },
    
    /**
     * get panel for westCardPanel region of given contentType
     *
     * NOTE: do not confuse this with westRegionPanel!
     * 
     * @return {Ext.Panel}
     */
    getWestPanel: function(contentType) {
        contentType = contentType || this.getActiveContentType();

        var _ = window.lodash,
            def = this.getContentTypeDefinition(contentType),
            app = _.get(def, 'app', this.app),
            wpName = _.upperFirst(contentType + 'WestPanel');
            
        if (! this[wpName]) {
            var wpconfig = {
                    app: app,
                    mainScreen: this,
                    contentTypes: this.contentTypes,
                    contentType: contentType,
                    listeners: {
                        scope: this,
                        // clear gird selection on favorite change, module change, container change?
                        selectionchange: function() {
                            var cp = this.getCenterPanel();
                            if(cp) {
                                try {
                                    var grid = cp.getGrid();
                                    if(grid) {
                                        var sm = grid.getSelectionModel();
                                        if(sm) {
                                            sm.clearSelections();
                                            cp.actionUpdater.updateActions(sm);
                                        }
                                    }
                                } catch (e) {
                                    // do nothing - no grid
                                }
                            }
                        }
                    }
                };
            try {
                if (Tine[app.name].hasOwnProperty(wpName)) {
                    this[wpName] = new Tine[app.appName][wpName](wpconfig);
                } else {
                    this[wpName] = new Tine.widgets.mainscreen.WestPanel(wpconfig);
                }
            } catch (e) {
                Tine.log.error('Could not create westPanel');
                Tine.log.error(e.stack ? e.stack : e);
                this[wpName] = new Ext.Panel({html: 'ERROR'});
            }
        }

        return this[wpName];
    },

    /**
     * shows center panel in mainscreen
     */
    showCenterPanel: function() {
        this.setActiveContentPanel(this.getCenterPanel(this.getActiveContentType()), true);
    },

    getQuicksearch: function(contentType, qfp) {
        const search_field = new Ext.ux.SearchField({
            width: 300,
            enableKeyEvents: true,
            ctCls: 'tine-mainscreen-grid-responsive-searchfield',
        });

        //safety check to prevent memory leaks
        delete this.getCenterPanel(contentType).quicksearch;

        this.getCenterPanel(contentType).quicksearch = search_field;

        search_field.onKeyUp = () => {
            qfp.quickFilter.setValue(search_field.getValue())
            qfp.syncField(qfp.quickFilter);
        };

        search_field.onTrigger2Click = () => {
            qfp.onQuickFilterTrigger();
        };
        search_field.onTrigger1Click = () => {
            qfp.onQuickFilterClear();
        };


        return search_field;
    },

    /**
     * shows module tree panel in mainscreen
     */
    showModuleTreePanel: function() {
        this.setActiveModulePanel(this.getModuleTreePanel(), true);
    },
    
    /**
     * shows west panel in mainscreen
     */
    showWestCardPanel: function() {
        // add save favorites button to toolbar if favoritesPanel exists
        var westPanel = this.getWestPanel(),
            favoritesPanel = westPanel.hasFavoritesPanel ? westPanel.getFavoritesPanel() : false,
            westPanelToolbar = this.westRegionPanel.getTopToolbar();

        westPanelToolbar.removeAll();

        if (favoritesPanel) {
            westPanelToolbar.addButton({
                xtype: 'button',
                text: i18n._('Save current view as favorite'),
                iconCls: 'action_saveFilter',
                scope: this,
                handler: function() {
                    favoritesPanel.saveFilter.call(favoritesPanel);
                }
            });

            // westPanelToolbar.show();
            // flat design
            westPanelToolbar.hide();
        } else {
            westPanelToolbar.hide();
        }

        westPanelToolbar.doLayout();

        this.setActiveTreePanel(westPanel, true);
    },
    
    /**
     * shows north panel in mainscreen
     */
    showNorthPanel: function() {
        this.setActiveToolbar(this.getNorthPanel(this.getActiveContentType()), true);
    },

    /**
     * sets the active content type
     *
     * @param {String} contentType to activate
     */
    setActiveContentType: function(contentType) {
        if (contentType === null) {
            // use first valid content type
            if (this.contentTypes && this.contentTypes.length > 0) {
                contentType = this.contentTypes[0].modelName;
            }
        }
        this.activeContentType = contentType;

        this.showWestCardPanel();
        this.showCenterPanel();
        this.showNorthPanel();
        this.showModuleTreePanel();
    },

    /**
     * sets the active content panel
     *
     * @param {Ext.Panel} item Panel to activate
     * @param {Bool} keep keep panel
     */
    setActiveContentPanel: function(panel, keep) {
        Ext.ux.layout.CardLayout.helper.setActiveCardPanelItem(this.centerCardPanel, panel, keep);

        const qfp = panel?.filterToolbar?.getQuickFilterPlugin();
        if (qfp) {
            const quicksearch = this.getQuicksearch(this.getActiveContentType(), qfp);
            Ext.ux.layout.CardLayout.helper.setActiveCardPanelItem(this.searchbarpanel, quicksearch);
        } else {
            console.warn("Could not create responsive quicksearch: quickfilter plugin not found")
        }

    },

    /**
     * sets the active tree panel
     *
     * @param {Ext.Panel} panel Panel to activate
     * @param {Bool} keep keep panel
     */
    setActiveTreePanel: function(panel, keep) {
        Ext.ux.layout.CardLayout.helper.setActiveCardPanelItem(this.westCardPanel, panel, keep);
    },

    /**
     * sets the active module tree panel
     *
     * @param {Ext.Panel} panel Panel to activate
     * @param {Bool} keep keep panel
     */
    setActiveModulePanel: function(panel, keep) {
        Ext.ux.layout.CardLayout.helper.setActiveCardPanelItem(this.moduleCardPanel, panel, keep);
    },

    /**
     * sets item
     *
     * @param {Ext.Toolbar} panel toolbar to activate
     * @param {Bool} keep keep panel
     */
    setActiveToolbar: function(panel, keep) {
        if (panel) {
            if (! this.northCardPanel.isVisible()) {
                this.northCardPanel.show();
                this.northCardPanel.ownerCt.doLayout();
                panel.show(); // Nasty resize prob!
            }

            Ext.ux.layout.CardLayout.helper.setActiveCardPanelItem(this.northCardPanel, panel, keep);
        } else {
            this.northCardPanel.hide();
            this.northCardPanel.ownerCt.doLayout();
        }
    },

    /**
     * gets the currently displayed toolbar
     *
     * @return {Ext.Toolbar}
     */
    getActiveToolbar: function() {
        var northPanel = this.northCardPanel;

        if (northPanel.layout.activeItem && northPanel.layout.activeItem.el) {
            return northPanel.layout.activeItem.el;
        } else {
            return false;
        }
    }
});

/**
 * content type registry
 *
 * @param {String} appName
 * @param {Collection} contentType
 *   contentType:   {String|Object|Model}
 *   text:          {String}
 *   group:         {String} (optional)
 *   xtype:         {String} (optional)
 */
Tine.widgets.MainScreen.registerContentType = function(appName, contentType) {
    var registeredContentTypes = _.get(Tine.widgets.MainScreen.registerContentType, 'registry.' + appName, []);
    if (_.isFunction(contentType.getMeta)) {
        contentType = {
            appName: contentType.getMeta('appName'),
            modelName: contentType.getMeta('modelName')
        };
    }
    
    registeredContentTypes.push(contentType);

    _.set(Tine.widgets.MainScreen.registerContentType, 'registry.' + appName, registeredContentTypes);
};

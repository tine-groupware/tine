/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.mainscreen');

/**
 * @namespace   Tine.widgets.mainscreen
 * @class       Tine.widgets.mainscreen.WestPanel
 * @extends     Ext.ux.Portal
 * 
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 * @todo make save button working again -> move to here
 * 
 * @constructor
 * @xtype       tine.widgets.mainscreen.westpanel
 */
Tine.widgets.mainscreen.WestPanel = function(config) {
    Ext.apply(this, config);
    
    this.defaults = {};
    
    Tine.widgets.mainscreen.WestPanel.superclass.constructor.apply(this, arguments);
};

Ext.extend(Tine.widgets.mainscreen.WestPanel, Ext.ux.Portal, {
    
    /**
     * @cfg {Array} contentTypes
     * Array of Objects
     * Object Properties: "model", "requiredRight"
     * Prop. model (e.g. "Abc") will be expanded to Tine.Application.Model.Abc
     * Prop. requiredRight (e.g. "read") will be expanded to Tine.Tinebase.common.hasRight('read', this.app.appName, recordClass.getMeta('recordsName').toLowerCase())
     * Prop. singularContainerMode (bool): when true, the records of the model doesn't have containers, so no containertreepanel is rendered
     *                                     but a containertreenode must be defined in Application.js
     * Prop. genericCtxActions (Array of Strings e.g. "['rename','grant']") Creates a Tine.widgets.tree.ContextMenu with the actions in this array                                    
     */
    contentTypes: null,

    /**
     * @cfg {String} contentType
     * defines the contentType (e.g. Xyz), which will be expanded to Tine.Application.Model.Xyz, this panel controls 
     */
    contentType: null,
    
    /**
     * @cfg {String} containerTreeClass
     * name of container tree class in namespace of this app (defaults to TreePanel)
     * the class name will be expanded to Tine[this.appName][this.containerTreePanelClassName]
     */
    containerTreePanelClassName: 'TreePanel',
    
    /**
     * @cfg {String} favoritesPanelClassName
     * name of favorites class in namespace of this app (defaults to FilterPanel)
     * the class name will be expanded to Tine[this.appName][this.favoritesPanelClassName]
     */
    favoritesPanelClassName: 'FilterPanel',
    
    /**
     * @cfg {Bool} hasContainerTreePanel
     * west panel has containerTreePanel (defaults to null -> autodetection)
     */
    hasContainerTreePanel: null,

    /**
     * @cfg {Boolean} defaultCollapseContainerTree
     */
    defaultCollapseContainerTree: false,

    /**
     * @cfg {Bool} hasFavoritesPanel
     * west panel has favorites panel (defaults to null -> autodetection)
     */
    hasFavoritesPanel: null,

    /**
     * @cfg {object} NodeGridPanel
     * needs for filterToolBar in EditDialog
     */
    gridPanel: null,
    
    layout: 'column',
    cls : 'x-portal',
    defaultType : 'portalcolumn',
    autoHeight: true,
    border: false,
    stateful: true,
    stateEvents: ['collapse', 'expand', 'drop'],

    /**
     * inits this west panel
     */
    initComponent: function() {
        this.contentTypeDefinition = Tine.widgets.MainScreen.prototype.getContentTypeDefinition.call(this, this.contentType) || {};

        this.recordClass = Tine.Tinebase.data.RecordMgr.get(this.recordClass || this.contentTypeDefinition.recordClass
            || `${this.contentTypeDefinition.appName}.${this.contentTypeDefinition.modelName}`);

        if (! this.app && this.recordClass) {
            this.app = Tine.Tinebase.appMgr.get(this.recordClass.getMeta('appName'));
        }

        this.stateId = this.app.appName + this.getContentType() + '-mainscreen-westpanel';
        this.canonicalName = this.canonicalName ? this.canonicalName : this.getContentType();

        this.hasFavoritesPanel = Ext.isBoolean(this.hasFavoritesPanel) ? this.hasFavoritesPanel : this.getFavoritesPanelConstructor();

        this.hasContainerTreePanel = Ext.isBoolean(this.hasContainerTreePanel) ? this.hasContainerTreePanel :
            this.contentTypeDefinition.singularContainerMode ? false : true;

        this.items = this.getPortalColumn();
        Tine.widgets.mainscreen.WestPanel.superclass.initComponent.apply(this, arguments);
    },
    
    /**
     * called after rendering process
     */
    afterRender: function() {
        Tine.widgets.mainscreen.WestPanel.superclass.afterRender.apply(this, arguments);
        
        this.getPortalColumn().items.each(function(item, idx) {
            // kill x-scrollers
            if (item.getEl && item.getEl()) {
                this.xScrollKiller(item);
            } else {
                item.on('afterrender', this.xScrollKiller, this);
            }
            
            //bubble state events
            // fire selectionchange event of all items as westPanel events
            item.enableBubble(['collapse', 'expand', 'selectionchange']);
        }, this);
    },
    
    /**
     * initializes the stateif no state is saved
     * overwrites the Ext.Component initState method
     */
    initState: function() {
        var state = Ext.state.Manager.get(this.stateId) ? Ext.state.Manager.get(this.stateId) : this.getState();
        
        if(this.fireEvent('beforestaterestore', this, state) !== false){
            this.applyState(Ext.apply({}, state));
            this.fireEvent('staterestore', this, state);
        }
    },
    
    /**
     * applies state to cmp
     * 
     * @param {Object} state
     */
    applyState: function(state) {
        var collection = this.getPortalColumn().items,
            c = new Array(collection.getCount()), k = collection.keys, items = collection.items;

        // do not apply broken state
        if ((_.filter(state.order, function(v) {return _.isNumber(v) && !_.isNaN(v)}).length < items.length)
            || _.uniq(state.order).length !== _.concat(state.order)) {
            return;
        }
    
        Ext.each(state.order, function(position, idx) {
            c[idx] = {key: k[position], value: items[position], index: position};
        }, this);
        
        for(var i = 0, len = collection.length; i < len; i++){
            if (c[i] && c[i].value) {
                items[i] = c[i].value;
                k[i] = c[i].key;
            }
        }
        collection.fireEvent('sort', collection);
        
        collection.each(function(item, idx) {
            if ((! item) || typeof item.addFill === 'function') return;
            if (item.getEl()) {
                item[state.collapsed[idx] ? 'collapse' : 'expand'](false);
            } else {
                item.collapsed = !!state.collapsed[idx];
            }
        }, this);
        
    },
    
    /**
     * returns additional items for the westpanel
     * template fn to be overrwiten by subclasses
     * 
     * @return {Array} of Ext.Panel
     */
    getAdditionalItems: function() {
        return this.additionalItems || [];
    },

    getContentType: function() {
        return (this.contentType) ? this.contentType : '';
    },
    
    /**
     * returns containerTree panel
     * 
     * @return {Tine.Tinebase.widgets.ContainerTreePanel}
     */
    getContainerTreePanel: function(contentType) {
        contentType = contentType || this.contentType;
        const panelName = contentType + 'TreePanel';

        if (! this[panelName]) {
            if (Tine[this.app.appName].hasOwnProperty(panelName)) {
                this[panelName] = new Tine[this.app.appName][panelName]({app: this.app, grid: this.gridPanel});
            } else {
                this[panelName] = new Tine.widgets.persistentfilter.PickerPanel({app: this.app, contentType: contentType, grid: this.gridPanel});
            }
            this[panelName].on('click', function (node, event) {
                // no scope here -> this means containerTree
                if(node != this.lastClickedNode) {
                    this.lastClickedNode = node;
                    this.fireEvent('selectionchange');
                }
            });
        }
        
        return this[panelName];

    },
    
    /**
     * returns favorites panel
     * 
     * @return {Ext.Panel}
     */
    getFavoritesPanelConstructor: function() {
        return Tine[this.recordClass ? this.recordClass.getMeta('appName') : this.app.appName][this.getContentType() + this.favoritesPanelClassName];
    },

    getFavoritesPanel: function() {
        try {
            if(!this[this.contentType + 'FilterPanel']) {
                var fpConfig = {
                    
                    rootVisible : false,
                    border : false,
                    collapsible : true,
                
                    root: null,
                    
                    titleCollapse: true,
                    title: '',
                    baseCls: 'ux-arrowcollapse',
                    
                    app: this.app,
                    contentType: this.contentType,
                    recordClass: this.recordClass,
                    // needs for filterToolBar in EditDialog
                    grid: this.gridPanel,

                    style: {
                        width: '100%',
                        overflow: 'hidden'
                    },
                    
                    treePanel: (this.hasContainerTreePanel) ? this.getContainerTreePanel() : null,
                    listeners: {
                        scope: this,
                        click: function (node, event) {
                            // this is westPanel
                            // fire westpanel event on favorite change
                            if(node != this.lastClickedNode) {
                                this.lastClickedNode = node;
                                this.fireEvent('selectionchange');
                            }
                        }
                    }
                };
                const Panel = this.getFavoritesPanelConstructor() || Tine.widgets.persistentfilter.PickerPanel;
                this[this.contentType + 'FilterPanel'] = new Panel(fpConfig)
            }
        } catch(e) {
            Tine.log.info('No Favorites Panel created');
        }
        
        return this[this.contentType + 'FilterPanel'];
    },
    
    /**
     * returns filter plugin of west panel for given content type
     * 
     * @param {String} contentType
     * @return {Tine.widgets.grid.FilterPlugin}
     */
    getFilterPlugin: function(contentType) {
        
        
        if (this.hasContainerTreePanel) {
            const ctp = this.getContainerTreePanel(contentType);
            if (ctp.getFilterPlugin) {
                return ctp.getFilterPlugin();
            }
        }
        
        return new Tine.widgets.grid.FilterPlugin({
            getValue: function() {
                return [
                ];
            }
        });
    },
    
    /**
     * returns the one and only portalcolumn of this west panel
     * 
     * @return {Ext.ux.PortalColumn}
     */
    getPortalColumn: function() {
        if (! this.portalColumn) {

            var items = [];
            
            if (this.hasContainerTreePanel) {
                var containerTreePanel = this.getContainerTreePanel();
                
                var containersName = containerTreePanel.recordClass
                    ? containerTreePanel.recordClass.getContainersName()
                    : i18n._('containers');
                
                // recheck if container tree is a container tree as in apps not dealing
                // with containers we don't want a collapsed arrow header
                var isContainerTreePanel = typeof containerTreePanel.selectContainerPath === 'function';
                
                if (isContainerTreePanel) {
                    this.defaults = {
                        collapsible: true,
                        baseCls: 'ux-arrowcollapse',
                        animCollapse: true,
                        titleCollapse:true,
                        draggable : true,
                        autoScroll: false
                    };
                }
                
                items.push(Ext.apply(this.getContainerTreePanel(), {
                    title: isContainerTreePanel ? containersName : false,
                    collapsed: isContainerTreePanel && this.defaultCollapseContainerTree
                }, this.defaults));
                
            }
            
            if (this.hasFavoritesPanel) {
                // favorites panel
                items.unshift(Ext.apply(this.getFavoritesPanel(), {
                    title: i18n._('Favorites')
                }, this.defaults));
            }
            
            items = items.concat(this.getAdditionalItems());

            // save original/programatical position
            // NOTE: this has to be done before applyState!
            if (items.length) {
                Ext.each(items, function(item, idx) {
                    item.startPosition = idx;
                }, this);
            }
            this.portalColumn = new Ext.ux.PortalColumn({
                plugins: [{
                    ptype: 'ux.itemregistry',
                    key:   ['Tine', this.app.appName, this.contentType, 'WestPanelPortalColumn'].join('.'),
                    config: {
                        collapsible: true,
                        baseCls: 'ux-arrowcollapse',
                        animCollapse: true,
                        titleCollapse:true,
                        draggable : true,
                        autoScroll: false
                    }
                }],
                columnWidth: 1,
                items: items
            });
            
            this.portalColumn.on('resize', function(cmp, width) {
                this.portalColumn.items.each(function(item) {
                    item.setWidth(width);
                }, this);
            }, this);
        }

        return this.portalColumn;
    },
    
    /**
     * gets state of this cmp
     */
    getState: function() {
        var state = {
            order: [],
            collapsed: []
        };
        
        this.getPortalColumn().items.each(function(item, idx) {
            state.order.push(item.startPosition);
            state.collapsed.push(!!item.collapsed);
        }, this);
        
        return state;
    },
    
    /**
     * kill x scrollers of given component
     * 
     * @param {Ext.Component} cmp
     */
    xScrollKiller:  function(cmp) {
        var panelEls = cmp.getEl().child('div[class^=' + this.defaults.baseCls + '-body]');
        if (panelEls) {
            panelEls.applyStyles('overflow-x: hidden');
            // IE 11 dosn't respect the style :-(
            panelEls.on('scroll', function(e) {
                panelEls.dom.scrollLeft = 0;
            });
        }
    }
});

Ext.reg('tine.widgets.mainscreen.westpanel', Tine.widgets.mainscreen.WestPanel);

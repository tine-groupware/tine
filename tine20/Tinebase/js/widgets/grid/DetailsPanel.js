/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
Ext.ns('Tine.Tinebase.widgets', 'Tine.widgets.grid');

/**
 * grid details panel
 * 
 * @namespace   Tine.widgets.grid
 * @class       Tine.widgets.grid.DetailsPanel
 * @extends     Ext.Panel
 * 
 * <p>Grid Details Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.widgets.grid.DetailsPanel
 */
Tine.widgets.grid.DetailsPanel = Ext.extend(Ext.Panel, {
    /**
     * @cfg {Number} defaultHeight
     * default Heights
     */
    defaultHeight: 125,

    /**
     * @cfg {Ext.data.Record} recordClass
     * record definition class
     */
    recordClass: null,

    /**
     * @cfg {Bool} listenMessageBus
     * listen to messagebus for record changes
     */
    listenMessageBus: true,

    /**
     * @property grid
     * @type Tine.widgets.grid.GridPanel
     */
    grid: null,

    /**
     * @property record
     * @type Tine.Tinebase.data.Record
     */
    record: null,
    
    /**
     * @property defaultInfosPanel holds panel for default information
     * @type Ext.Panel
     */
    defaultInfosPanel: null,
    
    /**
     * @property singleRecordPanel holds panel for single record details
     * @type Ext.Panel
     */
    singleRecordPanel: null,
    
    /**
     * @property multiRecordsPanel holds panel for multi selection aggregates/information
     * @type Ext.Panel
     */
    multiRecordsPanel: null,

    /**
     * @private
     */
    border: false,
    autoScroll: true,
    layout: 'card',
    activeItem: 0,
    contextMenuItems: [],
    /**
     * get panel for default information
     * 
     * @return {Ext.Panel}
     */
    getDefaultInfosPanel: function() {
        if (! this.defaultInfosPanel) {
            if (this.recordClass) {
                this.defaultInfosPanel = new Tine.widgets.display.DefaultDisplayPanel({
                    recordClass : this.recordClass
                });
                this.defaultHeight = Math.max(this.defaultHeight, this.defaultInfosPanel.defaultHeight);
            } else {
                this.defaultInfosPanel = new Ext.Panel(this.defaults);
            }
        }
        return this.defaultInfosPanel;
    },
    
    /**
     * get panel for single record details
     * 
     * @return {Ext.Panel}
     */
    getSingleRecordPanel: function() {
        if (! this.singleRecordPanel) {

            if (this.recordClass) {
                this.singleRecordPanel = new Tine.widgets.display.RecordDisplayPanel({
                    recordClass : this.recordClass,
                    boxLayout: this.isSmall ? 'vbox' : 'hbox',
                });
                this.defaultHeight = Math.max(this.defaultHeight, this.singleRecordPanel.defaultHeight);
            } else {
                this.singleRecordPanel = new Ext.Panel(this.defaults);
            }
        }
        return this.singleRecordPanel;
    },
    
    /**
     * get panel for multi selection aggregates/information
     * 
     * @return {Ext.Panel}
     */
    getMultiRecordsPanel: function() {
        if (! this.multiRecordsPanel) {
            this.multiRecordsPanel = new Ext.Panel(this.defaults);
        }
        return this.multiRecordsPanel;
    },
        
    /**
     * inits this details panel
     */
    initComponent: function() {
        if (!this.tbar) {
            this.editRecordAction = new Ext.Action({
                text: i18n._('Edit'),
                iconCls: 'action_edit',
                scope: this,
                handler: this.onEdit
            });
            this.tbar = [
                new Ext.Action({
                    text: i18n._('Back'),
                    iconCls: 'action_previous',
                    scope: this,
                    handler: this.onClose
                }),
                '->',
                this.editRecordAction,
            ]
            this.useResponsiveTbar = true;
        }

        this.items = [
            this.getDefaultInfosPanel(),
            this.getSingleRecordPanel(),
            this.getMultiRecordsPanel()
        ];
        
        // NOTE: this defaults overwrites configs in already instanciated configs -> see docu
        Ext.each(this.items, function(item) {
            Ext.applyIf(item, {
                border: false,
                autoScroll: true,
                layout: 'fit'
            });
        }, this);
        
        if (this.listenMessageBus && this.recordClass) {
            this.initMessageBus();
        }
        this.afterIsRendered().then(() => {
            this.el.on('contextmenu', (e) => {
                if (!this.menu) {
                    this.menu = new Ext.menu.Menu({
                        items:this.contextMenuItems,
                        plugins: [{
                            ptype: 'ux.itemregistry',
                            key:   this.singleRecordPanel.appName + '-' + this.singleRecordPanel.modelName + '-DetailsPanel'
                        }, {
                            ptype: 'ux.itemregistry',
                            key:   'Tinebase-MainContextMenu'
                        }]
                    });
                }
                const target = e.getTarget('a', 1 , true) ||
                    e.getTarget('input[type=text]', 1 , true) ||
                    e.getTarget('textarea', 1, true);
                
                 if (this.menu.items.length > 0 && window.getSelection().toString() === '' && !target) {
                    e.stopEvent();
                    this.menu.showAt(e.getXY());
                 }
            });
        });
        
        Tine.widgets.grid.DetailsPanel.superclass.initComponent.apply(this, arguments);
        
        if (this.useResponsiveTbar) {
            this.topToolbar.on('resize', this.onToolbarResize, this);
        }
    },
    
    onClose(e) {
        if (!this.gridpanel) return;
        this.gridpanel.setFullScreen(false);
    },

    onEdit(e) {
        if (!this.gridpanel) return;
        this.gridpanel.onEditInNewWindow.call(this.gridpanel, {
            actionType: 'edit'
        });
    },
    
    onToolbarResize() {
        let isSmall = false;
        if (this.gridpanel) {
            isSmall = !!this.isInFullScreenMode && this.gridpanel.isSmallLayout();
        }
        if (isSmall) {
            this.topToolbar.show();
        } else {
            this.topToolbar.hide();
        }
    },
    
    onDestroy: function() {
        _.each(this.postalSubscriptions, (subscription) => {subscription.unsubscribe()});
        return Tine.widgets.grid.DetailsPanel.superclass.onDestroy.call(this);
    },
    
    initMessageBus: function() {
        this.postalSubscriptions = [];
        this.postalSubscriptions.push(postal.subscribe({
            channel: "recordchange",
            topic: [this.recordClass.getMeta('appName'), this.recordClass.getMeta('modelName'), '*'].join('.'),
            callback: this.onRecordChanges.createDelegate(this)
        }));
    },

    /**
     * bus notified about record changes
     */
    onRecordChanges: function(data, e) {
        var _ = window.lodash;

        if (this.singleRecordPanel.isVisible() && this.record.id == data.id) {
            this.record = Tine.Tinebase.data.Record.setFromJson(data, this.recordClass);
            this.updateDetails(this.record, this.getSingleRecordPanel().body);
        }
    },

    /**
     * update template
     * 
     * @param {Tine.Tinebase.data.Record} record
     * @param {Mixed} body
     */
    updateDetails: function(record, body) {
        if (this.tpl) {
            this.tpl.overwrite(body, record.data);
        } else {
            this.getSingleRecordPanel().loadRecord.defer(100, this.getSingleRecordPanel(), [record]);
        }
    },
    
    /**
     * show default template
     * 
     * @param {Mixed} body
     */
    showDefault: function(body) {
        if (this.defaultTpl && body) {
            this.defaultTpl.overwrite(body);
        }
    },
    
    /**
     * show template for multiple rows
     * 
     * @param {Ext.grid.RowSelectionModel} sm
     * @param {Mixed} body
     */
    showMulti: function(sm, body) {
        if (this.multiTpl) {
            this.multiTpl.overwrite(body);
        }
    },
    
    /**
     * bind grid to details panel
     * 
     * @param {Tine.widgets.grid.GridPanel} grid
     */
    doBind: function(grid) {
        this.grid = grid;
        
        /*
        grid.getSelectionModel().on('selectionchange', function(sm) {
            if (this.updateOnSelectionChange) {
                this.onDetailsUpdate(sm);
            }
        }, this);
        */
        
        grid.store.on('load', function(store) {
            this.onDetailsUpdate(grid.getSelectionModel());
        }, this);
    },
    
    /**
     * update details panel
     * 
     * @param {Ext.grid.RowSelectionModel} sm
     */
    onDetailsUpdate: function(sm) {
        var count = sm.getCount();
        if (count === 0 || sm.isFilterSelect) {
            if(this.layout && Ext.isFunction(this.layout.setActiveItem)) {
                this.layout.setActiveItem(this.getDefaultInfosPanel());
            }
            this.showDefault(this.getDefaultInfosPanel().body);
            this.record = null;
        } else if (count === 1) {
            if(this.layout && Ext.isFunction(this.layout.setActiveItem)) {
                this.layout.setActiveItem(this.getSingleRecordPanel());
            }
            this.record = sm.getSelected();
            this.updateDetails(this.record, this.getSingleRecordPanel().body);
        } else if (count > 1) {
            if(this.layout && Ext.isFunction(this.layout.setActiveItem)) {
                this.layout.setActiveItem(this.getMultiRecordsPanel());
            }
            this.record = sm.getSelected();
            this.showMulti(sm, this.getMultiRecordsPanel().body);
        }
    },
    
    /**
     * get load mask
     * 
     * @return {Ext.LoadMask}
     */
    getLoadMask: function() {
        if (! this.loadMask) {
            this.loadMask = new Ext.LoadMask(this.el);
        }
        
        return this.loadMask;
    },
    
    /**
     * Wraps the items with default layout
     * 
     * @param {Array} items
     * @return {Object}
     */
    wrapPanel: function(items, labelWidth) {
        return {
            layout: 'fit',
            border: false,
            items: [{
                layout: 'vbox',
                border: false,
                layoutConfig: {
                    align:'stretch'
                },
                items: [{
                    layout: 'hbox',
                    flex: 1,
                    border: false,
                    layoutConfig: {
                        padding:'5',
                        align:'stretch'
                    },
                    defaults:{
                        margins:'0 5 0 0'
                    },
                    items: [
                        {
                        flex: 2,
                        layout: 'ux.display',
                        labelWidth: labelWidth,
                        padding: 10,
                        layoutConfig: {
                            background: 'solid',
                            margins: '0 5 0 0'
                        },
                        items: items
                    }]
                }]
            }]
        }
    }
});

Ext.reg('widget-detailspanel', Tine.widgets.grid.DetailsPanel);

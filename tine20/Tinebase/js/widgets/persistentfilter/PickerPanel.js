/*
 * Tine 2.0
 * 
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3 @author
 * Cornelius Weiss <c.weiss@metaways.de> @copyright Copyright (c) 2009-2011
 * Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.widgets.persistentfilter');

/**
 * @namespace Tine.widgets.persistentfilter
 * @class Tine.widgets.persistentfilter.PickerPanel
 * @extends Ext.tree.TreePanel
 * 
 * <p>
 * PersistentFilter Picker Panel
 * </p>
 * 
 * @author Cornelius Weiss <c.weiss@metaways.de>
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param {Object}
 *            config
 * @constructor Create a new Tine.widgets.persistentfilter.PickerPanel
 */
Tine.widgets.persistentfilter.PickerPanel = Ext.extend(Ext.tree.TreePanel, {

    /**
     * @cfg {application}
     */
    app : null,

    /**
     * @cfg {String} filterMountId mount point of persistent filter folder
     *      (defaults to null -> root node)
     */
    filterMountId : null,
    /**
     * @cfg {String} contentType mainscreen.activeContentType  
     */
    contentType: null,
    /**
     * @private
     */
    autoScroll : false,
    autoHeight: true,
    border : false,
    rootVisible : false,

    enableDD : true,
    stateId : null,

    /**
     * grid favorites panel belongs to
     * 
     * @type Tine.widgets.grid.GridPanel
     */
    grid : null,

    /**
     * @private
     */
    initComponent : function() {
        this.app = _.isString(this.app) ? Tine.Tinebase.appMgr.get(this.app) : this.app;

        this.stateId = 'widgets-persistentfilter-pickerpanel_' + this.app.name + '_' + this.contentType;

        this.store = this.store || Tine.widgets.persistentfilter.store.getPersistentFilterStore();

        var appName = this.recordClass ? this.recordClass.getMeta('appName') : this.app.appName,
            app = Tine.Tinebase.appMgr.get(appName),
            modelName = this.recordClass ? this.recordClass.getMeta('modelName') : window.lodash.upperFirst(this.contentType),
            filterModelName = this.filterModel || (appName + '_Model_' + modelName),
            state = Ext.state.Manager.get(this.stateId, {});

        if (! this.recordClass) {
            this.recordClass = Tine.Tinebase.data.RecordMgr.get(appName, modelName);
        }

        this.recordCollection = this.store.queryBy(function(record, id) {
            if (record.get('application_id') == app.id) {
                if(this.contentType || this.filterModel) {
                    return [filterModelName, `${filterModelName}Filter`].indexOf(record.get('model')) >=0;
                } else {
                    return true;
                }
            }
            return false;
        }, this);
        // sort filters by translated name
        this.recordCollection.sort('ASC', function (obj1, obj2) {
            var name1 = Ext.util.Format.htmlEncode(app.i18n._hidden(obj1.get('name'))),
                name2 = Ext.util.Format.htmlEncode(app.i18n._hidden(obj2.get('name')));
            
            return name1 > name2 ? 1: -1;
        });
        
        var sorting = 10000;
        
        this.recordCollection.each(function(record) {
            if(state[record.get('id')]) record.set('sorting', state[record.get('id')]);
            else {
                sorting++;
                record.set('sorting',sorting);
            }
        }, this);
                
        this.loader = new Tine.widgets.persistentfilter.PickerTreePanelLoader({
            app : app,
            recordCollection : this.recordCollection,
            contentType: this.contentType
        });

        new Ext.tree.TreeSorter(this, {
            property: 'sorting',
            sortType : function(node) {
                return node.attributes.sorting;
            }
        });

        this.on('nodedrop', function() {
            this.saveState();
        }, this);

        this.filterNode = this.filterNode || new Ext.tree.AsyncTreeNode({
            text : i18n._('My favorites'),
            id : '_persistentFilters',
            leaf : false,
            expanded : true
        });

        if (this.filterMountId === null) {
            this.root = this.filterNode;
        }

        Tine.widgets.persistentfilter.PickerPanel.superclass.initComponent.call(this);

        this.on('click', function(node) {
            if (node.attributes.isPersistentFilter) {
                this.getFilterToolbar().fireEvent('change', this.getFilterToolbar());
                node.select();
                this.onFilterSelect(this.recordCollection.get(node.id));
            } else if (node.id == '_persistentFilters') {
                node.expand();
                return false;
            }
        }, this);

        this.on('contextmenu', this.onContextMenu, this);
        
        this.currentUser = Tine.Tinebase.registry.get('currentAccount');
    },

    /**
     * returns canonical path part
     * @returns {string}
     */
    getCanonicalPathSegment: function () {
        if (this.recordClass) {
            return [
                this.recordClass.getMeta('modelName'),
                'FavoritesPicker',
            ].join(Tine.Tinebase.CanonicalPath.separator);
        }
    },
    /**
     * @private
     */
    afterRender : function() {
        Tine.widgets.persistentfilter.PickerPanel.superclass.afterRender.call(this);

        if (this.filterMountId !== null) {
            this.getNodeById(this.filterMountId).appendChild(this.filterNode);
        }
        // due to dependencies isues we need to wait after render
        var ft = this.getFilterToolbar();
        if (ft) {
            ft.on('change', this.onFilterChange, this);
        }
    },

    /**
     * saves the sort state of the tree
     */
    saveState: function() {
        var state = {};
        var i = 0;

        this.getRootNode().eachChild(function(node) {
            i++;
            node.attributes.sorting = i;
            var rec = this.recordCollection.get(node.attributes.id);
            var oldSort = rec.get('sorting');
            if(oldSort != i) {
                rec.set('sorting', i);
                rec.commit();
            }
            state[node.id] = i;
        }, this);

        Ext.state.Manager.set(this.stateId, state);
    },
    
    /**
     * load grid from saved filter
     * 
     * NOTE: As all filter plugins add their data on the stores beforeload event
     * we need a litte hack to only present a filterid.
     * 
     * When a filter is selected, we register ourselve as latest beforeload,
     * remove all filter data and paste our filter data. To ensure we are always
     * the last listener, we directly remove the listener afterwards
     */
    onFilterSelect : function(persistentFilter) {
        var store = this.getGrid().getStore();

        // NOTE: this can be removed when all instances of filterplugins are
        // removed
        store.on('beforeload', this.storeOnBeforeload, this);
        store.load({persistentFilter : persistentFilter});
        
        if (this.getGrid()?.grid) {
            this.getGrid().grid.getSelectionModel().clearSelections();
        }
    },

    /**
     * storeOnBeforeload
     * 
     * @param {}
     *            store
     * @param {}
     *            options
     */
    storeOnBeforeload : function(store, options) {
        options.params.filter = options.persistentFilter.get('filters');
        store.un('beforeload', this.storeOnBeforeload, this);
    },

    /**
     * called on filtertrigger of filter toolbar
     */
    onFilterChange : function() {
        this.getSelectionModel().clearSelections();
    },

    /**
     * returns additional ctx items
     * 
     * @TODO: make this a hooking approach!
     * 
     * @param {model.PersistentFilter}
     * @return {Array}
     */
    getAdditionalCtxItems : function(filter) {
        var items = [];

        var as = Tine.Tinebase.appMgr.get('ActiveSync');
        if (as) {
            items = items.concat(as.getPersistentFilterPickerCtxItems(this,    filter));
        }

        return items;
    },

    /**
     * returns filter toolbar of mainscreen center panel of app this picker
     * panel belongs to
     */
    getFilterToolbar : function() {
        if (!this.filterToolbar) {
            this.filterToolbar = this.getGrid().filterToolbar;
        }

        return this.filterToolbar;
    },

    getMainScreen: function() {
        return this.mainScreen
            || this.findParentBy((c) => { return c.xtype === 'Tine.widgets.MainScreen' })
            || this.app.getMainScreen();
    },

    /**
     * get grid
     * 
     * @return {Tine.widgets.grid.GridPanel}
     */
    getGrid : function() {
        if (!this.grid) {
            this.grid = this.getMainScreen().getCenterPanel();
        }

        return this.grid;
    },

    /**
     * returns persistent filter tree node
     * 
     * @return {Ext.tree.AsyncTreeNode}
     */
    getPersistentFilterNode : function() {
        return this.filterNode;
    },

    /**
     * handler for ctxmenu clicks on tree nodes
     * 
     * @param {Ext.tree.TreeNode}
     *            node
     * @param {Ext.EventObject}
     *            e
     */
    onContextMenu : function(node, e) {
        if (! node.attributes.isPersistentFilter) {
            return;
        }

        e.stopEvent();
        Tine.log.debug(node);
        
        var record = this.store.getById(node.id);
        var hasManageRight = this.hasRight(node.attributes);
        
        var menu = new Ext.menu.Menu({
            plugins: [{
                ptype: 'ux.itemregistry',
                key:   'Tinebase-MainContextMenu'
            }],
            items : [{
                text : i18n._('Delete Favorite'),
                iconCls : 'action_delete',
                hidden : ! (hasManageRight || this.hasGrant(node.attributes, 'deleteGrant')),
                handler : this.onDeletePersistentFilter.createDelegate(this, [node, e])
            }, {
                text : i18n._('Edit Favorite'),
                iconCls : 'action_edit',
                hidden : ! (hasManageRight || this.hasGrant(node.attributes, 'editGrant')),
                handler : this.onEditPersistentFilter.createDelegate(this, [node, e])
            }, {
                text : i18n._('Overwrite Favorite'),
                iconCls : 'action_saveFilter',
                hidden : ! (hasManageRight || this.hasGrant(node.attributes, 'editGrant')),
                handler : this.onOverwritePersistentFilter.createDelegate(this, [node, e])
            }].concat(this.getAdditionalCtxItems(record))
        });
        
        menu.showAt(e.getXY());
    },

    /**
     * handler to delete filter
     * 
     * @param {Ext.tree.TreeNode}
     *            node
     */
    onDeletePersistentFilter : function(node) {
        Ext.MessageBox.confirm(i18n._('Confirm'), String.format(i18n._('Do you really want to delete the favorite "{0}"?'), node.text), function(_btn) {
            if (_btn == 'yes') {
                Ext.MessageBox.wait(i18n._('Please wait'), String.format(i18n._('Deleting Favorite "{0}"'), this.containerName, node.text));

                var record = this.store.getById(node.id);
                Tine.widgets.persistentfilter.model.persistentFilterProxy.deleteRecords([record], {
                    scope : this,
                    success : function() {
                        this.store.remove(record);
                        this.recordCollection.remove(record);
                        this.filterNode.findChild('id', record.get('id')).remove();
                        Ext.MessageBox.hide();
                    }
                });
            }
        }, this);
    },

    /**
     * handler to rename filter
     * 
     * @param {Ext.tree.TreeNode}
     *            node
     */
    onEditPersistentFilter : function(node) {
        var record = this.store.getById(node.id);
        this.getEditWindow(record);
    },

    /**
     * handler to overwrite filter
     * 
     * @param {Ext.tree.TreeNode}
     *            node
     */
    onOverwritePersistentFilter : function(node) {
        
        var record = this.store.getById(node.id);
        Ext.MessageBox.confirm(i18n._('Overwrite?'), String.format(i18n._('Do you want to overwrite the favorite "{0}"?'), node.text), function(_btn) {
            if (_btn == 'yes') {
                Ext.MessageBox.wait(i18n._('Please wait'), String.format(i18n._('Overwriting Favorite "{0}"'), node.text));
                var ftb = this.getFilterToolbar();
                record.set('filters', ftb.getAllFilterData());
                
                this.createOrUpdateFavorite(record);
            }
        }, this, false, node.text);
    },

    /**
     * save persistent filter
     */
    saveFilter : function() {
        var ftb = this.getFilterToolbar();
        
        var record = this.getNewEmptyRecord();
        
        record.set('filters', ftb.getAllFilterData());
        
        // recheck that current ftb is saveable
        if (!ftb.isSaveAllowed()) {
            Ext.Msg.alert(i18n._('Could not save Favorite'), i18n._('Your current view does not support favorites'));
            return;
        }
        this.getEditWindow(record);
    },
    
    getEditWindow: function(record) {
        if (! record.get('id')) {
            var title = i18n._('Create Favorite');
        } else {
            var title = i18n._('Edit Favorite');
        }
        
        var height = 160;
        if ((this.hasRight() || this.hasGrant(record, 'editGrant')) && ! record.isDefault()) {
            height = 440;
        }
        
        Tine.log.debug('Tine.widgets.persistentfilter.PickerPanel::getEditWindow - create edit window for filter record: ');
        Tine.log.debug(record);
        
        var newWindow = Tine.WindowFactory.getWindow({
            modal : true,
            app : this.app,
            modelName: this.contentType,
            record : record,
            title : title,
            width : 400,
            height : height,
            contentPanelConstructor : 'Tine.widgets.persistentfilter.EditPersistentFilterPanel',
            contentPanelConstructorConfig : null
        });

        newWindow.on('update', function(win) {
            Ext.MessageBox.wait(i18n._('Please wait'), String.format(i18n._('Saving Favorite "{0}"'), record.get('name')));
            this.createOrUpdateFavorite(newWindow.record);
        }, this);
        
    },
    
    /**
     * checks right
     * 
     * @param {Object} filter
     * @return {Boolean}
     */
    hasRight: function(filter) {
        if (filter && filter.created_by == this.currentUser.accountId) {
            return true;
        }
        return (Tine.Tinebase.common.hasRight('manage_shared_' + this.contentType.toLowerCase() + '_favorites', this.app.name))
    },
    
    
    /**
     * check if user has grant on record 
     * 
     * @param {Record} record
     * @param {String} requiredGrant
     * @return {Boolean}
     * 
     * TODO move this to generic Record
     */
    hasGrant: function(record, requiredGrant) {
        var accountGrants = record && record.account_grants ? record.account_grants : 
                (record && record.data && record.data.account_grants ? record.data.account_grants : false),
            result = (accountGrants && accountGrants[requiredGrant] == true);
        Tine.log.debug('hasGrant(' + requiredGrant + ') -> ' + result);
        return result;
    },
    
    /**
     * create or update a favorite
     * 
     * @param {Tine.widgets.persistentfilter.model.PersistentFilter} record
     */
    
    createOrUpdateFavorite : function(record) {
        Tine.log.debug('createOrUpdateFavorite() ->');
        Tine.log.debug(record);
        
        Tine.widgets.persistentfilter.model.persistentFilterProxy.saveRecord(record, {
            scope : this,
            success : function(savedRecord) {
                Tine.log.debug('savedRecord ->');
                Tine.log.debug(record);
                
                var existing = this.recordCollection.get(savedRecord.id);
                
                if (existing) {
                    savedRecord.set('sorting', existing.get('sorting'));
                    this.store.remove(existing);
                    this.recordCollection.remove(existing);
                    
                    this.filterNode.findChild('id', existing.get('id')).remove();
                } else {
                    var sorting = 0;
                    savedRecord.set('sorting', sorting);
                }

                var attr = savedRecord.data;
                this.loader.inspectCreateNode(attr);
                
                this.filterNode.appendChild(new Ext.tree.TreeNode(attr));
                
                this.store.add(savedRecord);
                this.recordCollection.add(savedRecord);
                this.recordCollection.sort();
                
                if(! existing) {
                    this.saveState();
                }
                
                Ext.Msg.hide();
                // reload this filter?
                this.selectFilter(savedRecord);
            }
        });
    },

    /**
     * select given persistent filter
     * 
     * @param {model.PersistentFilter}
     *            persistentFilter
     */
    selectFilter : function(persistentFilter) {
        if (!persistentFilter) {
            return;
        }
        this.getFilterToolbar().fireEvent('change', this.getFilterToolbar());
        var node = this.getNodeById(persistentFilter.id);
        if (node) {
            this.getSelectionModel().select(node);
        } else {
            // mark for selection
            this.getLoader().selectedFilterId = persistentFilter.id;
        }

        this.onFilterSelect(persistentFilter);
    },

    /**
     * select default favorite
     *
     * NOTE: a lot modules/models don't have a default favorite preference
     */
    selectDefault: function() {
        var appName = this.recordClass.getMeta('appName'),
            modelName = this.recordClass.getMeta('modelName'),
            defaultFavorite = Tine.widgets.persistentfilter.model.PersistentFilter.getDefaultFavorite(appName, modelName);

        this.selectFilter(defaultFavorite);
    },
    
    getNewEmptyRecord: function() {
        var model = this.filterModel,
            ftb = this.getFilterToolbar(),
            appName = appName = this.recordClass ? this.recordClass.getMeta('appName') : this.app.appName,
            app = Tine.Tinebase.appMgr.get(appName);
            
        if (!model) {
            var recordClass = this.recordClass || (this.treePanel ? this.treePanel.recordClass : ftb.store.reader.recordType);
            model = recordClass.getMeta('appName') + '_Model_' + recordClass.getMeta('modelName') + 'Filter';
        }

        var record = new Tine.widgets.persistentfilter.model.PersistentFilter({
            application_id : app.id,
            account_id : Tine.Tinebase.registry.get('currentAccount').accountId,
            model : model,
            filters : null,
            name : null,
            description : null
        });
        
        return record;
    }
});

/**
 * @namespace Tine.widgets.persistentfilter
 * @class Tine.widgets.persistentfilter.PickerTreePanelLoader
 * @extends Tine.widgets.tree.Loader
 * 
 * <p>
 * PersistentFilter Picker Panel Tree Loader
 * </p>
 * 
 * @author Cornelius Weiss <c.weiss@metaways.de>
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param {Object}
 *            config
 * @constructor Create a new Tine.widgets.persistentfilter.PickerTreePanelLoader
 */
Tine.widgets.persistentfilter.PickerTreePanelLoader = Ext.extend(Tine.widgets.tree.Loader, {

            /**
             * @cfg {Ext.util.MixedCollection} recordCollection
             */          
            recordCollection: null,

            /**
             * @cfg {String} selectedFilterId id to autoselect
             */
            selectedFilterId : null,

            /**
             * 
             * @param {Ext.tree.TreeNode}
             *            node
             * @param {Function}
             *            callback Function to call after the node has been
             *            loaded. The function is passed the TreeNode which was
             *            requested to be loaded.
             * @param (Object)
             *            scope The cope (this reference) in which the callback
             *            is executed. defaults to the loaded TreeNode.
             */
            requestData : function(node, callback, scope) {
                if (this.fireEvent("beforeload", this, node, callback) !== false) {
                    
                    node.beginUpdate();
                    this.recordCollection.each(function(record) {
                                var n = this.createNode(record.copy().data);
                                if (n) {
                                    node.appendChild(n);
                                }
                            }, this);
                    node.endUpdate();

                    this.runCallback(callback, scope || node, [node]);

                } else {
                    // if the load is cancelled, make sure we notify
                    // the node that we are done
                    this.runCallback(callback, scope || node, []);
                }
            },

            inspectCreateNode : function(attr) {
                var isPersistentFilter = !!attr.model && !!attr.filters;

                var isShared = (attr.account_id === null) ? true : false;

                var addText = '';
                var addClass = '';
                if (isShared) {
                    addText = i18n._('(shared)');
                    addClass = '-shared';
                }

                if (isPersistentFilter) {
                    Ext.apply(attr, {
                        isPersistentFilter : isPersistentFilter,
                        text : Ext.util.Format.htmlEncode(this.app.i18n._hidden(attr.name)),
                        qtip : Ext.util.Format.htmlEncode(attr.description ? this.app.i18n._hidden(attr.description) + ' ' + addText : addText),
                        selected : attr.id === this.selectedFilterId,
                        id : attr.id,

                        sorting : attr.sorting,
                        draggable : true,
                        allowDrop : false,

                        leaf : attr.leaf === false ? attr.leaf : true,
                        cls : 'tinebase-westpanel-node-favorite' + addClass
                    });
                }
            }
        });

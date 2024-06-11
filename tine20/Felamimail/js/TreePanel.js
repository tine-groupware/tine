/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Felamimail');
require('./nodeActions');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.FilterPanel
 * @extends     Tine.widgets.persistentfilter.PickerPanel
 * 
 * <p>Felamimail Favorites Panel</p>
 * 
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.FilterPanel
 */
Tine.Felamimail.FilterPanel = Ext.extend(Tine.widgets.persistentfilter.PickerPanel, {
    filterModel: 'Felamimail_Model_MessageFilter'
});

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.TreePanel
 * @extends     Ext.tree.TreePanel
 * 
 * <p>Account/Folder Tree Panel</p>
 * <p>Tree of Accounts with folders</p>
 * <pre>
 * low priority:
 * TODO         make inbox/drafts/templates configurable in account
 * TODO         disable delete action in account ctx menu if user has no manage_accounts right
 * </pre>
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.TreePanel
 * 
 */
Tine.Felamimail.TreePanel = function(config) {
    Ext.apply(this, config);
    
    this.addEvents(
        /**
         * @event containeradd
         * Fires when a folder was added
         * @param {folder} the new folder
         */
        'containeradd',
        /**
         * @event containerdelete
         * Fires when a folder got deleted
         * @param {folder} the deleted folder
         */
        'containerdelete',
        /**
         * @event containerrename
         * Fires when a folder got renamed
         * @param {folder} the renamed folder
         */
        'containerrename'
    );
        
    Tine.Felamimail.TreePanel.superclass.constructor.call(this);
};

Ext.extend(Tine.Felamimail.TreePanel, Ext.tree.TreePanel, {
    
    /**
     * @property app
     * @type Tine.Felamimail.Application
     */
    app: null,
    
    /**
     * @property accountStore
     * @type Ext.data.JsonStore
     */
    accountStore: null,
    
    /**
     * @type Ext.data.JsonStore
     */
    folderStore: null,
    
    /**
     * @cfg {String} containerName
     */
    containerName: 'Folder',
    
    /**
     * TreePanel config
     * @private
     */
    rootVisible: true,
    
    /**
     * drag n drop
     */ 
    enableDrop: true,
    ddGroup: 'mailToTreeDDGroup',
    
    /**
     * @cfg
     */
    border: false,
    filterMode: 'filterToolbar',
    
    /**
     * define state events
     */
    stateful: true,
    stateId: 'Felamimail-TreePanel',
    stateEvents: ['expandnode', 'collapsenode'],
    
    /**
     * is needed by Tine.widgets.mainscreen.WestPanel to fake container tree panel
     */
    selectContainerPath: Ext.emptyFn,
    
    /**
     * init
     * @private
     */
    initComponent: function() {

        this.recordClass = Tine.Felamimail.Model.Account;

        // get folder store
        this.folderStore = Tine.Tinebase.appMgr.get('Felamimail').getFolderStore();
        
        // init tree loader
        this.loader = new Tine.Felamimail.TreeLoader({
            folderStore: this.folderStore,
            app: this.app
        });

        // set the root node
        this.root = new Ext.tree.TreeNode({
            cls: 'felamimail-node-root',
            text: 'default',
            draggable: false,
            allowDrop: false,
            expanded: true,
            leaf: false,
            id: 'root'
        });
        
        // add account nodes
        this.initAccounts();

        
        // init drop zone
        this.dropConfig = {
            ddGroup: this.ddGroup || 'TreeDD',
            appendOnly: this.ddAppendOnly === true,
            notifyEnter : function() {this.isDropSensitive = true;}.createDelegate(this),
            notifyOut : function() {this.isDropSensitive = false;}.createDelegate(this),
            onNodeOver : function(n, dd, e, data) {
                var node = n.node;
                
                // auto node expand check (only for non-account nodes)
                if(!this.expandProcId && node.attributes.allowDrop && node.hasChildNodes() && !node.isExpanded()){
                    this.queueExpand(node);
                } else if (! node.attributes.allowDrop) {
                    this.cancelExpand();
                }
                return node.attributes.allowDrop ? 'tinebase-tree-drop-move' : false;
            },
            isValidDropPoint: function(n, dd, e, data){
                return n.node.attributes.allowDrop;
            }
        };
        
        // init selection model (multiselect)
        this.selModel = new Ext.tree.MultiSelectionModel({});
        
        // init context menu
        this.initContextMenu();
        
        // add listeners
        this.on('beforestatesave',this.saveStateIf, this);
        this.on('beforeclick', this.onBeforeClick, this);
        this.on('click', this.onClick, this);
        this.on('contextmenu', this.onContextMenu, this);
        this.on('beforenodedrop', this.onBeforenodedrop, this);
        this.on('append', this.onAppend, this);
        this.on('containeradd', this.onFolderAdd, this);
        this.on('containerrename', this.onFolderRename, this);
        this.on('containerdelete', this.onFolderDelete, this);
        this.selModel.on('selectionchange', this.onSelectionChange, this);
        this.folderStore.on('update', this.onUpdateFolderStore, this);

        // call parent::initComponent
        Tine.Felamimail.TreePanel.superclass.initComponent.call(this);
        
        this.treeSorter = new Ext.tree.TreeSorter(this, {
            dir: "asc",
            priorityList: ['INBOX', 'Drafts', 'Sent', 'Templates', 'Junk', 'Trash'],
            priorityProperty: 'globalname',
            doSort : function (node) {
                if(node.ownerTree.getRootNode() !== node) {
                    node.sort(this.sortFn);
                }
            },
        });
    },
    
    /**
     * get state
     */
    getState: function() {
        return {
            paths: this.getExpandedPaths(this.getRootNode()),
            selected: _.get(this.getSelectionModel().getSelectedNode(), 'id'),
            selectedGlobalName: _.get(this.getSelectionModel().getSelectedNode(), 'attributes.globalname'),
        }
    },


    /**
     * get the deepest nodes expanded paths
     *
     * @param node
     */
    getExpandedPaths: function(node) {
        let paths = [];

        if (node.expanded) {
            paths.push(node.getPath());

            if (node.hasChildNodes()) {
                Ext.each(node.childNodes, function(childNode) {
                    paths = paths.concat(this.getExpandedPaths(childNode));
                }, this);
            }
        }

        paths.reverse();
        paths =  _.reduce(paths, (reduced, path) => {
            return reduced.concat(String(reduced[reduced.length-1]).startsWith(path) ? [] : [path]);
        }, []);

        return paths;
    },

    /**
     * set expanded paths
     */
    setExpandedPaths: async function(state) {
        let promises = [];
        
        if (! state?.paths) {
            return;
        }
        
        _.each(state.paths, (path) => {
            const promise = new Promise((resolve, reject) => {
                this.expandPath(path, null, function (success, oLastNode) {
                    if (success) {
                        resolve();
                    } else {
                        reject('expand paths failed.');
                    }
                });
            });
            promises.push(promise);
        });
        
        return Promise.all(promises);
    },
    
    /**
     * add accounts from registry as nodes to root node
     * @private
     */
    initAccounts: function() {
        this.accountStore = this.app.getAccountStore();
        this.accountStore.each(this.addAccountNode, this);

        this.accountStore.on('load', function(store, records) {
            this.root.removeAll();
            _.map(records, _.bind(this.addAccountNode, this));
            this.selectLastSelectedNode();
        }, this);
        this.accountStore.on('add', function(store, records) {
            _.map(records, (record) => {
                const node = this.addAccountNode(record);
                node.expand();
            });
        }, this);
        this.accountStore.on('update', this.onAccountUpdate, this);
        this.accountStore.on('remove', this.deleteAccount, this);
    },
    
    /**
     * initiates tree context menus
     *
     * @private
     */
    initContextMenu: function() {
        this.accountMenu = Tine.widgets.tree.ContextMenu.getMenu({
            app: 'Felamimail',
            actionMgr: Tine.Felamimail.nodeActionsMgr,
            nodeName: this.app.i18n.n_('Account', 'Accounts', 1),
            scope: this,
            backend: 'Felamimail',
            backendModel: 'Account',
            actions: Tine.Felamimail.nodeActions.accountActions,
        });
    
        this.folderMenu = Tine.widgets.tree.ContextMenu.getMenu({
            app: 'Felamimail',
            actionMgr: Tine.Felamimail.nodeActionsMgr,
            nodeName: this.app.i18n.n_('Folder', 'Folders', 1),
            scope: this,
            backend: 'Felamimail',
            backendModel: 'Folder',
            actions: Tine.Felamimail.nodeActions.folderActions,
        });
        
        this.actionUpdater = new Tine.widgets.ActionUpdater({
            recordClass: Tine.Felamimail.Model.Account,
            actions: _.concat(this.accountMenu.items.items, this.folderMenu.items.items),
        });
    },
    
    /**
     * init extra tool tips
     */
    initToolTips: function() {
        this.folderTip = new Ext.ToolTip({
            target: this.getEl(),
            delegate: 'a.x-tree-node-anchor',
            renderTo: document.body,
            listeners: {beforeshow: this.updateFolderTip.createDelegate(this)}
        });
        
        this.folderProgressTip = new Ext.ToolTip({
            target: this.getEl(),
            delegate: '.felamimail-node-statusbox-progress',
            renderTo: document.body,
            listeners: {beforeshow: this.updateProgressTip.createDelegate(this)}
        });
        
        this.folderUnreadTip = new Ext.ToolTip({
            target: this.getEl(),
            delegate: '.felamimail-node-statusbox-unread',
            renderTo: document.body,
            listeners: {beforeshow: this.updateUnreadTip.createDelegate(this)}
        });
    },
    
    /**
     * called when tree selection changes
     * 
     * @param {} sm
     * @param {} node
     */
    onSelectionChange: function(sm, nodes) {
        if (this.filterMode === 'gridFilter' && this.filterPlugin) {
            this.filterPlugin.onFilterChange();
        }
        if (this.filterMode === 'filterToolbar' && this.filterPlugin) {
            
            // get filterToolbar
            let ftb = this.filterPlugin.getGridPanel().filterToolbar;
            // in case of filterPanel
            ftb = ftb.activeFilterPanel ? ftb.activeFilterPanel : ftb;
            
            // set ftb filters according to tree selection
            const oldPathFilters = ftb.filterStore.query('field', 'path');
            const newPathFilter = this.getFilterPlugin().getFilter();
            const grid = this.filterPlugin.getGridPanel();
            const pathFilterValue =  newPathFilter?.value && _.isArray(newPathFilter?.value) ? newPathFilter.value[0] : null;
            const isSentFolder = grid.isSendFolderPath(pathFilterValue);

            // not longer needed as the query filter now contains 'to' as well
            // ftb.defaultFilter = isSentFolder ? 'to_list' : 'query';

            if (oldPathFilters.length > 0) {
                // update path filter
                ftb.supressEvents = true;
                _.each(oldPathFilters.items, (oldPathFilter, idx) => {
                    if ((idx + 1) === oldPathFilters.length) {
                        ftb.setFilterData(oldPathFilter, newPathFilter);
                    } else {
                        ftb.deleteFilter(oldPathFilter);
                    }
                })

                ftb.supressEvents = false;
            } else {
                ftb.addFilter(new ftb.record(newPathFilter));
            }

            ftb.onFiltertrigger();
            
            // finally select the selected node, as filtertrigger clears all selections
            sm.suspendEvents();
            Ext.each(nodes, function(node) {
                sm.select(node, Ext.EventObject, true);
            }, this);
            sm.resumeEvents();
        }
        this.saveState();
    },
  
    /**
     * returns a filter plugin to be used in a grid
     * @private
     */
    getFilterPlugin: function() {
        if (!this.filterPlugin) {
            this.filterPlugin = new Tine.widgets.tree.FilterPlugin({
                treePanel: this,
                field: 'path',
                nodeAttributeField: 'path',
                singleNodeOperator: 'in'
            });
        }
        
        return this.filterPlugin;
    },
    
    /**
     * convert containerPath to treePath
     * 
     * @param {String}  containerPath
     * @return {String} treePath
     */
    getTreePath: function(path) {
        return '/root' + path;
    },
    
    /**
     * @private
     * 
     * expand default account and select INBOX
     */
    afterRender: function() {
        Tine.Felamimail.TreePanel.superclass.afterRender.call(this);
        this.initToolTips();
        this.selectLastSelectedNode();
    },
    
    /**
     * select the last selected node
     * - expand the recorded expanded node from db
     */
    selectLastSelectedNode: async function () {
        if (!this.root.rendered) {
            return;
        }
        
        const state = Ext.state.Manager.get(this.stateId);
        this.applyingState = true;

        this.expandPortalColumn();

        try {
            await this.setExpandedPaths(state);
        } catch(error) {
            this.selectInbox();
            this.applyingState = false;
        }

        let node = this.getNodeById(_.get(state, 'selected'));

        if(!node) {
            this.selectInbox();
        } else {
            if (!node.isSelected()) {
                node.select();
            }
        }

        this.applyingState = false;
    },

    /**
     * expand portal column "Email Accounts"
     */
    expandPortalColumn: function () {
        Ext.each(this.app.getMainScreen().getWestPanel().getPortalColumn().items.items, function (item) {
            if (Ext.isFunction(item.expand) && item.recordClass) {
                item.expand();
            }
        });
    },

    /**
     * select inbox of account
     * @param {Record} account
     */
    selectInbox: function(account) {
        const accountId = (account) ? account.id : Tine.Felamimail.registry.get('preferences').get('defaultEmailAccount');

        this.expandPath('/root/' + accountId + '/', null, function(success, parentNode) {
            Ext.each(parentNode.childNodes, function(node) {
                if (Ext.util.Format.lowercase(node.attributes.localname) === 'inbox' && !node.isSelected()) {
                    node.select();
                    return false;
                }
            }, this);
        });
    },

    /**
     * return flag for applying state
     */
    saveStateIf() {
        return !this.applyingState;
    },
    
    /**
     * called when an account record updates
     * 
     * @param {Ext.data.JsonStore} store
     * @param {Tine.Felamimail.Model.Account} record
     * @param {String} action
     */
    onAccountUpdate: function(store, record, action) {
        if (action === Ext.data.Record.EDIT) {
            this.updateAccountStatus(record);
            this.selectLastSelectedNode();
        }
    },
    
    /**
     * on append node
     * 
     * render status box
     * 
     * @param {Tine.Felamimail.TreePanel} tree
     * @param {Ext.Tree.TreeNode} node
     * @param {Ext.Tree.TreeNode} appendedNode
     * @param {Number} index
     */
    onAppend: function(tree, node, appendedNode, index) {
        appendedNode.ui.render = appendedNode.ui.render.createSequence(function() {
            var app = Tine.Tinebase.appMgr.get('Felamimail'),
                folder = app.getFolderStore().getById(appendedNode.id);
                
            if (folder) {
                app.getMainScreen().getTreePanel().addStatusboxesToNodeUi(this);
                app.getMainScreen().getTreePanel().updateFolderStatus(folder);
            }
        }, appendedNode.ui);
    },
    
    /**
     * add status boxes
     * 
     * @param {Object} nodeUi
     */
    addStatusboxesToNodeUi: function(nodeUi) {
        if (nodeUi?.elNode?.lastChild) {
            if (nodeUi.elNode.lastChild?.className === 'felamimail-node-statusbox') {
                return;
            }
            Ext.DomHelper.insertAfter(nodeUi.elNode.lastChild, {
                tag: 'span', 'class': 'felamimail-node-statusbox', cn: [
                    {'tag': 'img', 'src': Ext.BLANK_IMAGE_URL, 'class': 'felamimail-node-statusbox-progress'},
                    {'tag': 'span', 'class': 'felamimail-node-statusbox-unread'}
                ]
            });
        }
    },
    
    /**
     * on before click handler
     * - accounts are not clickable because fetching all messages of account is too expensive
     * - skip event for folders that are not selectable
     * 
     * @param {Ext.tree.AsyncTreeNode} node
     */
    onBeforeClick: function(node) {
        const account = this.accountStore.getById(node.id);

        if (account || ! this.app.getFolderStore().getById(node.id).get('is_selectable')) {
            // account node should not trigger click event but still need to be expanded
            if (account && node.isExpandable()) {
                node.toggle();
            }
            return false;
        }
    },
    
    /**
     * on click handler
     * 
     * - expand node
     * - update filter toolbar of grid
     * - start check mails delayed task
     * 
     * @param {Ext.tree.AsyncTreeNode} node
     * @private
     */
    onClick: function(node) {
        if (node.isExpandable() && !node.expanded) {
            // NOTE: we don't expand here any longer - macos and win don't do it either
            // node.expand();
        }
        
        if (node.id && node.id !== '/' && node.attributes.globalname !== '') {
            const folder = this.app.getFolderStore().getById(node.id);
            if (folder) {
                if (folder.get('cache_status') === 'pending') {
                    this.app.checkMails(folder, Ext.emptyFn);
                }
                // lasy wait for selection change
                _.delay(() => {this.updateFolderStatus(folder);}, 100);
            }
        }
    },
    
    /**
     * show context menu for folder tree
     * 
     * items:
     * - create folder
     * - rename folder
     * - delete folder
     * - ...
     * 
     * @param {} node
     * @param {} event
     * @private
     */
    onContextMenu: function(node, event) {
        event.stopEvent();
        Tine.log.debug(node);
    
        // legacy for reload action
        this.ctxNode = node;
        
        const folder = this.app.getFolderStore().getById(node.attributes.id);
        this.ctxMenu = !folder ? this.accountMenu : this.folderMenu;
        this.actionUpdater.updateActions([node]);
        
        this.ctxMenu.showAt(event.getXY());
    },
    
    /**
     * mail(s) got dropped on node
     * 
     * @param {Object} dropEvent
     * @private
     */
    onBeforenodedrop: function(dropEvent) {
        var targetFolderId = dropEvent.target.attributes.folder_id,
            targetFolder = this.app.getFolderStore().getById(targetFolderId);
                
        this.app.getMainScreen().getCenterPanel().moveSelectedMessages(targetFolder, false);
        return true;
    },
    
    /**
     * cleanup on destruction
     */
    onDestroy: function() {
        this.folderStore.un('update', this.onUpdateFolderStore, this);
    },
    
    /**
     * folder store gets updated -> update tree nodes
     * 
     * @param {Tine.Felamimail.FolderStore} store
     * @param {Tine.Felamimail.Model.Folder} record
     * @param {String} operation
     */
    onUpdateFolderStore: function(store, record, operation) {
        if (operation === Ext.data.Record.EDIT) {
            this.updateFolderStatus(record);
        }
    },
    
    /**
     * add new folder to the store
     * 
     * @param {Object} folderData
     */
    onFolderAdd: function(folderData) {
        var recordData = Ext.copyTo({}, folderData, Tine.Felamimail.Model.Folder.getFieldNames());
        var newRecord = Tine.Felamimail.folderBackend.recordReader({responseText: Ext.util.JSON.encode(recordData)});

        this.ctxNode.expand();
        this.ctxNode.appendChild(this.loader.createNode(folderData));
        const parentRecord = this.folderStore.getById(this.ctxNode.id);
        if (parentRecord) parentRecord.set('has_children', true);
        this.folderStore.add([newRecord]);
        this.initNewFolderNode(newRecord);
    },
    
    /**
     * init new folder node
     * 
     * @param {Tine.Felamimail.Model.Folder} newRecord
     */
    initNewFolderNode: function(newRecord) {
        // update paths in node
        var appendedNode = this.getNodeById(newRecord.id);
        
        if (! appendedNode) {
            // node is not yet rendered -> reload parent
            var parentId = newRecord.get('parent_path').split('/').pop(),
                parentNode = this.getNodeById(parentId);

            if (Ext.isFunction(parentNode.reload)) {
                return parentNode.reload(function () {
                    this.initNewFolderNode(newRecord);
                }, this);
            } else {
                return;
            }
        }
        
        appendedNode.attributes.path = newRecord.get('path');
        appendedNode.attributes.parent_path = newRecord.get('parent_path');
        
        // add unreadcount/progress/tooltip
        this.addStatusboxesToNodeUi(appendedNode.ui);
        this.updateFolderStatus(newRecord);
    },

    /**
     * rename folder in the store
     * 
     * @param {Object} folderData
     */
    onFolderRename: function(folderData) {
        var record = this.folderStore.getById(folderData.id);
        record.set('globalname', folderData.globalname);
        record.set('localname', folderData.localname);
        
        Tine.log.debug('Renamed folder:' + record.get('globalname'));
    },
        
    /**
     * remove deleted folder from the store
     * 
     * @param {Object} folderData
     */
    onFolderDelete: function(folderData) {
        // if we deleted account, remove it from account store
        if (folderData.record && folderData.record.modelName === 'Account') {
            this.accountStore.remove(this.accountStore.getById(folderData.id));
        }
        
        this.folderStore.remove(this.folderStore.getById(folderData.id));
    },
    
    /**
     * returns tree node id the given el is child of
     * 
     * @param  {HTMLElement} el
     * @return {String}
     */
    getElsParentsNodeId: function(el) {
        return Ext.fly(el, '_treeEvents').up('div[class^=x-tree-node-el]').getAttribute('tree-node-id', 'ext');
    },
    
    /**
     * updates account status icon in this tree
     * 
     * @param {Tine.Felamimail.Model.Account} account
     */
    updateAccountStatus: function(account) {
        var imapStatus = account.get('imap_status'),
            node = this.getNodeById(account.id),
            ui = node ? node.getUI() : null,
            nodeEl = ui ? ui.getEl() : null;
            
        Tine.log.info('Account ' + account.get('name') + ' updated with imap_status: ' + imapStatus);
        if (node && node.ui.rendered) {
            var statusEl = Ext.get(Ext.DomQuery.selectNode('span[class=felamimail-node-accountfailure]', nodeEl));
            if (! statusEl) {
                // create statusEl on the fly
                statusEl = Ext.DomHelper.insertAfter(ui.elNode.lastChild, {'tag': 'span', 'class': 'felamimail-node-accountfailure'}, true);
                statusEl.on('click', function() {
                    Tine.Felamimail.folderBackend.handleRequestException(account.getLastIMAPException());
                }, this);
            }
            
            statusEl.setVisible(imapStatus === 'failure');
        }
    },
    
    /**
     * updates folder status icons/info in this tree
     * 
     * @param {Tine.Felamimail.Model.Folder} folder
     */
    updateFolderStatus: function(folder) {
        var unreadcount = folder.get('cache_unreadcount'),
            totalcount = folder.get('cache_totalcount'),
            progress    = Math.round(folder.get('cache_job_actions_done') / folder.get('cache_job_actions_est') * 10) * 10,
            node        = this.getNodeById(folder.id),
            ui = node ? node.getUI() : null,
            nodeEl = ui ? ui.getEl() : null,
            cacheStatus = folder.get('cache_status'),
            lastCacheStatus = folder.modified ? folder.modified.cache_status : null,
            isSelected = folder.isCurrentSelection(),
            account =  folder ? this.accountStore.getById(folder.get('account_id')) : this.accountStore.getById(node.id);

        this.setUnreadClass(folder.id);
            
        if (node && node.ui.rendered) {
            var domNode = Ext.DomQuery.selectNode('span[class=felamimail-node-statusbox-unread]', nodeEl);
            if (domNode) {
                
                //draft folder show totalcount instead  
                if(folder.get('globalname') === account.get('drafts_folder')) {
                    Ext.fly(domNode).update(totalcount).setVisible(totalcount > 0);
                } else {
                    // update unreadcount + visibity
                    Ext.fly(domNode).update(unreadcount).setVisible(unreadcount > 0);
                }

                // update progress
                var progressEl = Ext.get(Ext.DomQuery.selectNode('img[class^=felamimail-node-statusbox-progress]', nodeEl));
                progressEl.removeClass(['felamimail-node-statusbox-progress-pie', 'felamimail-node-statusbox-progress-loading']);
                if (! Ext.isNumber(progress)) {
                    progressEl.addClass('felamimail-node-statusbox-progress-loading');
                } else {
                    progressEl.addClass('felamimail-node-statusbox-progress-pie');
                    progressEl.addClass('felamimail-node-statusbox-progress-pie-' + progress);
                }
                progressEl.setVisible(isSelected && cacheStatus !== 'complete' && cacheStatus !== 'disconnect' && progress !== 100 && lastCacheStatus !== 'complete');
            }
        }
    },
    
    /**
     * set unread class of folder node and parents
     * 
     * @param {Tine.Felamimail.Model.Folder} folder
     * 
     * TODO make it work correctly for parents (use events) and activate again
     */
    setUnreadClass: function(folderId) {
        var folder              = this.app.getFolderStore().getById(folderId),
            node                = this.getNodeById(folderId),
            isUnread            = folder.get('cache_unreadcount') > 0,
            hasUnreadChildren   = folder.get('unread_children').length > 0;
            
        if (node && node.ui.rendered) {
            var ui = node.getUI();
            ui[(isUnread || hasUnreadChildren) ? 'addClass' : 'removeClass']('felamimail-node-unread');
        }
        
        // get parent, update and call recursivly
//        var parentFolder = this.app.getFolderStore().getParent(folder);
//        if (parentFolder) {
//            // need to create a copy of the array here (and make sure it is unique)
//            var unreadChildren = Ext.unique(parentFolder.get('unread_children'));
//                
//            if (isUnread || hasUnreadChildren) {
//                unreadChildren.push(folderId);
//            } else {
//                unreadChildren.remove(folderId);
//            }
//            parentFolder.set('unread_children', unreadChildren);
//            this.setUnreadClass(parentFolder.id);
//        }
    },
    
    /**
     * updates the given tip
     * @param {Ext.Tooltip} tip
     */
    updateFolderTip: function(tip) {
        var folderId = this.getElsParentsNodeId(tip.triggerElement),
            folder = this.app.getFolderStore().getById(folderId),
            account = this.accountStore.getById(folderId);
            
        if (folder && !this.isDropSensitive) {
            var info = [
                '<table>',
                    '<tr>',
                        '<td>', this.app.i18n._('Total Messages:'), '</td>',
                        '<td>', folder.get('cache_totalcount'), '</td>',
                    '</tr>',
                    '<tr>',
                        '<td>', this.app.i18n._('Unread Messages:'), '</td>',
                        '<td>', folder.get('cache_unreadcount'), '</td>',
                    '</tr>',
                    '<tr>',
                        '<td>', this.app.i18n._('Name on Server:'), '</td>',
                        '<td>', folder.get('globalname'), '</td>',
                    '</tr>',
                    '<tr>',
                        '<td>', this.app.i18n._('Last update:'), '</td>',
                        '<td>', Tine.Tinebase.common.dateTimeRenderer(folder.get('client_access_time')), '</td>',
                    '</tr>',
                '</table>'
            ];
            tip.body.dom.innerHTML = info.join('');
        } else {
            return false;
        }
    },
    
    /**
     * updates the given tip
     * @param {Ext.Tooltip} tip
     */
    updateProgressTip: function(tip) {
        var folderId = this.getElsParentsNodeId(tip.triggerElement),
            folder = this.app.getFolderStore().getById(folderId),
            progress = Math.round(folder.get('cache_job_actions_done') / folder.get('cache_job_actions_est') * 100);
        if (! this.isDropSensitive) {
            tip.body.dom.innerHTML = String.format(this.app.i18n._('Fetching messages... ({0} done)'), progress + '%');
        } else {
            return false;
        }
    },
    
    /**
     * updates the given tip
     * @param {Ext.Tooltip} tip
     */
    updateUnreadTip: function(tip) {
        var folderId = this.getElsParentsNodeId(tip.triggerElement),
            folder = this.app.getFolderStore().getById(folderId),
            count = folder.get('cache_unreadcount');
            
        if (! this.isDropSensitive) {
            tip.body.dom.innerHTML = String.format(this.app.i18n.ngettext('{0} unread message', '{0} unread messages', count), count);
        } else {
            return false;
        }
    },
    
    /**
     * decrement unread count of currently selected folder
     */
    decrementCurrentUnreadCount: function() {
        var store  = Tine.Tinebase.appMgr.get('Felamimail').getFolderStore(),
            node   = this.getSelectionModel().getSelectedNode(),
            folder = node ? store.getById(node.id) : null;
            
        if (folder) {
            folder.set('cache_unreadcount', parseInt(folder.get('cache_unreadcount'), 10) -1);
            folder.commit();
        }
    },
    
    /**
     * add account record to root node
     * 
     * @param {Tine.Felamimail.Model.Account} record
     */
    addAccountNode: function(record) {
        const node = new Ext.tree.AsyncTreeNode({
            id: record.data.id,
            path: '/' + record.data.id,
            record: record,
            globalname: '',
            draggable: false,
            allowDrop: false,
            expanded: false,
            text: Ext.util.Format.htmlEncode(record.get('name')),
            qtip: Ext.util.Format.htmlEncode(record.get('host')),
            leaf: false,
            cls: 'felamimail-node-account',
            delimiter: record.get('delimiter'),
            ns_personal: record.get('ns_personal'),
            account_id: record.data.id,
            listeners: {
                scope: this,
                load: function(node) {
                    var account = this.accountStore.getById(node.id);
                    this.updateAccountStatus(account);
                }
            }
        });
        
        // we don't want appending folder effects
        this.suspendEvents();
        this.root.appendChild(node);
        this.resumeEvents();

        _.defer(() => {
            this.app.getMainScreen().getCenterPanel().action_write.setDisabled(! this.app.getActiveAccount());
        });
        return node;
    },

    deleteAccount: function(store, account) {
        let accountNode = this.root.findChild('path', '/' + account.data.id);
        if (accountNode) {
            try {
                accountNode.remove(true);
            } catch (e) {}
        }
    },
    /**
     * get active account by checking selected node
     * @return Tine.Felamimail.Model.Account
     */
    getActiveAccount: function() {
        var result = null;
        var node = this.getSelectionModel().getSelectedNode();
        if (node) {
            var accountId = node.attributes.account_id;
            result = this.accountStore.getById(accountId);
        }
        
        return result;
    }
});

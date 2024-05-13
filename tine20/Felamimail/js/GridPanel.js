/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
Ext.namespace('Tine.Felamimail');

require('./MessageFileAction');

import keydown from 'keydown';

/**
 * Message grid panel
 * 
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.GridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Message Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Felamimail.GridPanel
 */
Tine.Felamimail.GridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    /**
     * record class
     * @cfg {Tine.Felamimail.Model.Message} recordClass
     */
    recordClass: Tine.Felamimail.Model.Message,
    
    /**
     * message detail panel
     * 
     * @type Tine.Felamimail.GridDetailsPanel
     * @property detailsPanel
     */
    detailsPanel: null,

    /**
     * default panel region
     *
     * @property detailsPanelRegion
     */
    detailsPanelRegion: 'south',

    /**
     * transaction id of current delete message request
     * @type Number
     */
    deleteTransactionId: null,
    
    /**
     * this is true if messages are moved/deleted
     * 
     * @type Boolean
     */
    movingOrDeleting: false,
    
    manualRefresh: false,
    
    /**
     * @private model cfg
     */
    evalGrants: false,
    filterSelectionDelete: true,
    // autoRefresh is done via onUpdateFolderStore
    autoRefreshInterval: false,

    // needed for refresh after file messages
    listenMessageBus: true,


    // NOTE: initial loading is done by tree selection
    initialLoadAfterRender: false,
    
    /**
     * @private grid cfg
     */
    defaultSortInfo: {field: 'sent', direction: 'DESC'},
    gridConfig: {
        autoExpandColumn: 'subject',
        // drag n dropfrom
        enableDragDrop: true,
        ddGroup: 'mailToTreeDDGroup',
    },
    // we don't want to update the preview panel on context menu
    updateDetailsPanelOnCtxMenu: false,

    /**
     * needed to apply second grid state for send folders
     */
    sendFolderGridStateId: null,

    sentFolderSelected: false,

    /**
     * Return CSS class to apply to rows depending upon flags
     * - checks Flagged, Deleted and Seen
     * 
     * @param {Tine.Felamimail.Model.Message} record
     * @param {Integer} index
     * @return {String}
     */
    getViewRowClass: function(record, index) {
        var className = '';
        
        if (record.hasFlag('\\Flagged')) {
            className += 'flag_flagged ';
        }
        if (record.hasFlag('\\Deleted')) {
            className += 'flag_deleted ';
        }
        if (! record.hasFlag('\\Seen')) {
            className += 'flag_unread ';
        }
        if (record.get('is_spam_suspicions')) {
            className += 'is_spam_suspicions ';
        }
        return className;
    },
    
    /**
     * init message grid
     * @private
     */
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('Felamimail');
        this.i18nEmptyText = this.app.i18n._('No Messages found.');
        this.recordProxy = Tine.Felamimail.messageBackend;
        this.gridConfig.cm = new Ext.grid.ColumnModel({
            defaults: {
                resizable: true
            },
            columns: this.getColumns()
        });
        this.gridConfig.columns = this.getColumns();
        
        this.initDetailsPanel();
        
        this.pagingConfig = {
            doRefresh: this.doRefresh.createDelegate(this)
        };

        if (!this.readOnly) {
            this.plugins.push({
                ptype: 'ux.browseplugin',
                multiple: true,
                scope: this,
                enableFileDialog: false,
                handler: this.onFilesSelect.createDelegate(this)
            });
        }
        
        Tine.Felamimail.GridPanel.superclass.initComponent.call(this);
        this.grid.getSelectionModel().on('rowselect', this.onRowSelection, this);
        this.app.getFolderStore().on('update', this.onUpdateFolderStore, this);
        this.contextMenu.on('beforeshow', this.onDisplaySpamActions, this);
        this.initPagingToolbar();
        
        this.sendFolderGridStateId = this.gridConfig.stateId + '-SendFolder';
    },
    
    /**
     * add quota bar to paging toolbar
     */
    initPagingToolbar: function() {
        Ext.QuickTips.init();
        
        this.quotaBar = new Ext.Component({
            displayPriority: 50,
            style: {
                margin: '3px 10px',
                width: '100px',
                height: '16px'
            }
        });
        this.pagingToolbar.insert(12, new Ext.Toolbar.Separator());
        this.pagingToolbar.insert(13, this.quotaBar);
    },
    
    /**
     * cleanup on destruction
     */
    onDestroy: function() {
        this.app.getFolderStore().un('update', this.onUpdateFolderStore, this);
    },
    
    /**
     * folder store gets updated -> refresh grid if new messages arrived or messages have been removed
     * 
     * @param {Tine.Felamimail.FolderStore} store
     * @param {Tine.Felamimail.Model.Folder} record
     * @param {String} operation
     */
    onUpdateFolderStore: function(store, record, operation) {
        if (operation === Ext.data.Record.EDIT && record.isModified('cache_totalcount')) {
            var tree = this.app.getMainScreen().getTreePanel(),
                selectedNodes = (tree) ? tree.getSelectionModel().getSelectedNodes() : [];
            
            // only refresh if 1 or no messages are selected
            if (this.getGrid().getSelectionModel().getCount() <= 1) {
                var refresh = false;
                for (var i = 0; i < selectedNodes.length; i++) {
                    if (selectedNodes[i].id == record.id) {
                        refresh = true;
                        break;
                    }
                }
                
                // check if folder is in filter or allinboxes are selected and updated folder is an inbox
                if (! refresh) {
                    var filters = this.filterToolbar.getValue();
                    filters = filters.filters ? filter.filters : filters;
                    
                    for (var i = 0; i < filters.length; i++) {
                        if (filters[i].field == 'path' && filters[i].operator == 'in') {
                            if (filters[i].value.indexOf(record.get('path')) !== -1 || (filters[i].value.indexOf('/allinboxes') !== -1 && record.isInbox())) {
                                refresh = true;
                                break;
                            }
                        }
                    }
                }
                
                if (refresh && this.noDeleteRequestInProgress()) {
                    Tine.log.debug('Refresh grid because of folder update.');
                    this.loadGridData({
                        removeStrategy: 'keepBuffered',
                        autoRefresh: true
                    });
                }
            }
        }
    },
    
    /**
     * init actions with actionToolbar, contextMenu and actionUpdater
     * 
     * @private
     */
    initActions: function() {
        // init felamimail specific actions
        this.action_spam = new Ext.Action({
            requiredGrant: 'editGrant',
            actionType: 'edit',
            text: this.app.i18n._('It is SPAM'),
            handler:this.processSpamStrategy.bind(this, 'spam'),
            iconCls: 'felamimail-action-spam',
            allowMultiple: true,
            hidden: !this.app.featureEnabled('featureSpamSuspicionStrategy')
        });

        this.action_ham = new Ext.Action({
            requiredGrant: 'editGrant',
            actionType: 'edit',
            text: this.app.i18n._('It is not SPAM'),
            handler: this.processSpamStrategy.bind(this, 'ham'),
            iconCls: 'felamimail-action-ham',
            allowMultiple: true,
            hidden: !this.app.featureEnabled('featureSpamSuspicionStrategy')
        });
        
        this.action_write = new Ext.Action({
            requiredGrant: 'addGrant',
            actionType: 'add',
            text: this.app.i18n._('Compose'),
            handler: this.onMessageCompose.createDelegate(this),
            // TODO reactivate when account becomes available as sometimes this stays deactivated
            disabled: ! this.app.getActiveAccount(),
            iconCls: this.app.appName + 'IconCls'
        });

        this.action_reply = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'reply',
            text: this.app.i18n._('Reply'),
            handler: this.onMessageReplyTo.createDelegate(this, [false]),
            iconCls: 'action_email_reply',
            disabled: true
        });

        this.action_replyAll = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'replyAll',
            text: this.app.i18n._('Reply To All'),
            handler: this.onMessageReplyTo.createDelegate(this, [true]),
            iconCls: 'action_email_replyAll',
            disabled: true
        });

        this.action_forward = new Ext.Action({
            requiredGrant: 'readGrant',
            actionType: 'forward',
            text: this.app.i18n._('Forward'),
            handler: this.onMessageForward.createDelegate(this),
            iconCls: 'action_email_forward',
            disabled: true
        });

        this.action_flag = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Toggle highlighting'),
            handler: this.toggleMessageFlagged,
            scope: this,
            iconCls: 'action_email_flag',
            allowMultiple: true,
            disabled: true
        });
        
        this.action_markUnread = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Mark read/unread'),
            handler: this.toggleMessageUnread,
            scope: this,
            iconCls: 'action_mark_read',
            allowMultiple: true,
            disabled: true
        });

        this.action_moveRecord = new Ext.Action({
            requiredGrant: 'editGrant',
            allowMultiple: true,
            text: this.app.i18n._('Move'),
            disabled: true,
            actionType: 'edit',
            handler: this.onMoveRecords,
            scope: this,
            iconCls: 'action_move'
        });

        this.action_copyRecord = new Ext.Action({
            requiredGrant: 'editGrant',
            allowMultiple: true,
            text: this.app.i18n._('Copy'),
            disabled: true,
            actionType: 'edit',
            handler: this.onCopyRecords,
            scope: this,
            iconCls: 'action_editcopy'
        });
        
        this.action_editRecord = new Ext.Action({
            requiredGrant: 'editGrant',
            allowMultiple: false,
            text: this.app.i18n._('Edit'),
            actionType: 'edit',
            handler: this.onRowDblClick,
            scope: this,
            iconCls: 'action_edit',
            actionUpdater: (action, grants, records, isFilterSelect, filteredContainers) => {
                let editable = false;
                if (this.sentFolderSelected && records[0]) {
                    const folder = this.app.getFolderStore().getById(records[0].get('folder_id'));
                    const account = this.app.getAccountStore().getById(folder.get('account_id'));
                    editable = folder.get('path').includes(account.getSpecialFolderId('drafts_folder'))
                        || folder.get('path').includes(account.getSpecialFolderId('templates_folder'));
                }
                action.setHidden(!editable);
            }
        });

        this.action_fileRecord = new Tine.Felamimail.MessageFileAction({});
        
        this.action_importRecords = new Ext.Action({
            requiredGrant: 'addGrant',
            actionType: 'add',
            text: this.app.i18n._('Import Messages'),
            handler: this.onFilesSelect,
            disabled: true,
            scope: this,
            plugins: [{
                ptype: 'ux.browseplugin',
                multiple: true,
                enableFileDrop: false,
                disable: true
            }],
            iconCls: 'action_import',
            actionUpdater: function(action, grants, records, isFilterSelect, filteredContainers) {
                action.setDisabled(! this.getCurrentFolderFromTree());
            }.createDelegate(this)
        });

        this.action_addAccount = new Ext.Action({
            text: this.app.i18n._('Add Account'),
            handler: this.onAddAccount,
            iconCls: 'action_add',
            scope: this,
            disabled: ! Tine.Tinebase.common.hasRight('add_accounts', 'Felamimail')
        });
        
        this.action_printPreview = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Print Preview'),
            handler: this.onPrintPreview.createDelegate(this, []),
            disabled:true,
            hidden: Ext.supportsPopupWindows,
            iconCls:'action_printPreview',
            scope:this
        });

        this.action_print = new Ext.Action({
            requiredGrant: 'readGrant',
            text: this.app.i18n._('Print Message'),
            handler: this.onPrint.createDelegate(this, []),
            disabled:true,
            iconCls:'action_print',
            scope:this,
            menu:{
                items:[
                    this.action_printPreview
                ]
            }
        });

        this.action_addTask = new Ext.Action({
            requiredGrant: 'readGrant',
            text: Tine.Tinebase.appMgr.isEnabled('Tasks') ? Tine.Tinebase.appMgr.get('Tasks').i18n._('Create New Task') : '',
            handler: this.onCreateTask,
            iconCls: 'action_addTask',
            scope: this,
            disabled: true,
            allowMultiple: false,
            hidden: !Tine.Tinebase.appMgr.isEnabled('Tasks')
        });

        // initial tagging actions from parent
        Tine.Felamimail.GridPanel.superclass.initActions.call(this);

        this.action_deleteRecord.setText(this.app.i18n._('Delete'));
        this.action_deleteRecord.initialConfig.translationObject = null;

        this.actionUpdater.addActions([
            this.action_editRecord,
            this.action_deleteRecord,
            this.action_reply,
            this.action_replyAll,
            this.action_forward,
            this.action_fileRecord,
            this.action_importRecords,
            this.action_flag,
            this.action_markUnread,
            this.action_addAccount,
            this.action_print,
            this.action_printPreview,
            this.action_copyRecord,
            this.action_moveRecord,
            this.action_addTask,
            this.action_spam,
            this.action_ham,
            this.action_tagsMassAttach,
            this.action_tagsMassDetach,
        ]);
        
        this.contextMenu = new Ext.menu.Menu({
            plugins: [{
                ptype: 'ux.itemregistry',
                key:   'Tinebase-MainContextMenu'
            }],
            items: [
                this.action_reply,
                this.action_replyAll,
                this.action_forward,
                this.action_editRecord,
                this.action_flag,
                this.action_markUnread,
                this.action_copyRecord,
                this.action_moveRecord,
                this.action_deleteRecord,
                this.action_fileRecord,
                this.action_addTask,
                this.action_spam,
                this.action_ham,
                this.action_tagsMassAttach,
                this.action_tagsMassDetach,
            ],
        });
    },

    // we don't delete messages, we move them to trash
    disableDeleteActionCheckServiceMap () {},

    /**
     * upload new file and add to store
     *
     * @param {ux.BrowsePlugin} fileSelector
     * @param {} e
     */
    onFilesSelect: async function (fileSelector, event) {
        const folder = this.getCurrentFolderFromTree();
        if (! folder) {
            return Ext.Msg.alert(this.app.i18n._('No target folder selected'), this.app.i18n._('You need to select a target folder for the messages.'));
        }
        
        const exts = _.uniq(_.map(fileSelector.files, file => {return file.name.split('.').pop()}));
        if (exts.length !== 1 || exts[0] !== 'eml') {
            return Ext.Msg.alert(this.app.i18n._('Wrong File Type'), this.app.i18n._('Files of type eml allowed only.'));
        }
        
        this.importMask = new Ext.LoadMask(this.grid.getEl(), { msg: i18n._('Importing Messages...') });
        this.importMask.show();
        
        const pms = _.map(fileSelector.files, (file) => {
            const upload = new Ext.ux.file.Upload({file: file});
            upload.upload();
            return upload.promise.then((upload) => {
                const tempFile = upload.fileRecord.get('tempFile');
                return Tine.Felamimail.importMessage(folder.id, tempFile.id);
            });
        });
        
        await Promise.allSettled(pms);
        this.doRefresh();
        this.importMask.hide();
    },
    
    /**
     * initializes the filterPanel, overwrites the superclass method
     */
    initFilterPanel: function() {
        this.defaultFilters = [
            {field: 'query', operator: 'contains', value: ''}
        ];
        this.filterToolbar = this.getFilterToolbar();
        this.filterToolbar.criteriaIgnores = [
            {field: 'query',     operator: 'contains',     value: ''},
            {field: 'id' },
            {field: 'path' }
        ];
        
        this.plugins = this.plugins || [];
        this.plugins.push(this.filterToolbar);
    },
    
    /**
     * the details panel (shows message content)
     * 
     * @private
     */
    initDetailsPanel: function() {
        this.detailsPanel = new Tine.Felamimail.GridDetailsPanel({
            gridpanel: this,
            grid: this,
            app: this.app,
            i18n: this.app.i18n
        });
    },
    
    /**
     * get action toolbar
     * 
     * @return {Ext.Toolbar}
     */
    getActionToolbar: function() {
        if (! this.actionToolbar) {
            this.actionToolbar = new Ext.Toolbar({
                defaults: {height: 55},
                items: [{
                    xtype: 'buttongroup',
                    layout: 'toolbar',
                    buttonAlign: 'left',
                    columns: 6,
                    items: [
                        Ext.apply(new Ext.SplitButton(this.action_write), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top',
                            arrowAlign:'right',
                            menu: new Ext.menu.Menu({
                                items: [],
                                plugins: [{
                                    ptype: 'ux.itemregistry',
                                    key:   'Tine.widgets.grid.GridPanel.addButton'
                                },{
                                    ptype: 'ux.itemregistry',
                                    key:   'Tinebase-MainContextMenu'
                                }]
                            })
                        }),
                        Ext.apply(new Ext.Button(this.action_deleteRecord), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        Ext.apply(new Ext.Button(this.action_reply), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        Ext.apply(new Ext.Button(this.action_replyAll), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        Ext.apply(new Ext.Button(this.action_forward), {
                            scale: 'medium',
                            rowspan: 2,
                            iconAlign: 'top'
                        }),
                        {
                            xtype: 'buttongroup',
                            buttonAlign: 'left',
                            columns: 3,
                            frame: false,
                            items: [
                                this.action_print,
                                this.action_markUnread,
                                this.action_addAccount,
                                this.action_fileRecord,
                                this.action_flag,
                                this.action_importRecords
                            ]
                        }
                    ]
                }, this.getActionToolbarItems()]
            });

            this.actionToolbar.on('resize', this.onActionToolbarResize, this, {buffer: 250});
            this.actionToolbar.on('show', this.onActionToolbarResize, this);

            if (this.filterToolbar && typeof this.filterToolbar.getQuickFilterField == 'function') {
                this.actionToolbar.add('->', this.filterToolbar.getQuickFilterField());
            }
        }
        
        return this.actionToolbar;
    },
    
    /**
     * returns cm
     * 
     * @private
     */
    getColumns: function(){
        return [{
            id: 'id',
            header: this.app.i18n._("Id"),
            width: 100,
            sortable: true,
            dataIndex: 'id',
            hidden: true
        }, {
            id: 'content_type',
            header: '<div class="action_attach tine-grid-row-action-icon"></div>',
            tooltip: this.app.i18n._("Attachments"),
            width: 12,
            sortable: true,
            dataIndex: 'has_attachment',
            renderer: this.attachmentRenderer
        }, {
            id: 'flags',
            header: this.app.i18n._("Flags"),
            width: 24,
            sortable: true,
            dataIndex: 'flags',
            align: 'center',
            renderer: this.flagRenderer
        }, {
            id: 'tags',
            header: this.app.i18n._("Tags"),
            width: 24,
            sortable: false,
            dataIndex: 'tags',
            align: 'center',
            renderer: Tine.Tinebase.common.tagsRenderer
        },{
            id: 'subject',
            header: this.app.i18n._("Subject"),
            width: 300,
            sortable: true,
            dataIndex: 'subject'
        },{
            id: 'from_email',
            header: this.app.i18n._("From (Email)"),
            width: 100,
            sortable: true,
            dataIndex: 'from_email'
        },{
            id: 'from_name',
            header: this.app.i18n._("From (Name)"),
            width: 100,
            sortable: true,
            dataIndex: 'from_name'
        },{
            id: 'sender',
            header: this.app.i18n._("Sender"),
            width: 100,
            sortable: true,
            dataIndex: 'sender',
            hidden: true
        },{
            id: 'to',
            header: this.app.i18n._("To"),
            width: 150,
            sortable: true,
            dataIndex: 'to',
            hidden: true,
            renderer: Tine.Tinebase.common.emailRenderer,
        },{
            id: 'sent',
            header: this.app.i18n._("Sent"),
            width: 100,
            sortable: true,
            dataIndex: 'sent',
            renderer: Tine.Tinebase.common.dateTimeRenderer
        },{
            id: 'received',
            header: this.app.i18n._("Received"),
            width: 100,
            sortable: true,
            dataIndex: 'received',
            hidden: true,
            renderer: Tine.Tinebase.common.dateTimeRenderer
        },{
            id: 'folder_id',
            header: this.app.i18n._("Folder"),
            width: 100,
            sortable: true,
            dataIndex: 'folder_id',
            hidden: true,
            renderer: this.accountAndFolderRenderer.createDelegate(this)
        },{
            id: 'size',
            header: this.app.i18n._("Size"),
            width: 80,
            sortable: true,
            dataIndex: 'size',
            hidden: true,
            renderer: Ext.util.Format.fileSize
        }];
    },
    
    /**
     * attachment column renderer
     * 
     * @param {String} value
     * @return {String}
     * @private
     */
    attachmentRenderer: function(value, metadata, record) {
        let result = '';
        
        if (value == 1) {
            result = '<div class="action_attach tine-grid-row-action-icon" />';
        }
        
        return result;
    },
    
    /**
     * get flag icon
     *
     * @private
     *
     * TODO  use spacer if first flag(s) is/are not set?
     * @param value
     * @param metadata
     * @param record
     * @param value
     * @param metadata
     * @param record
     */
    flagRenderer: function(value, metadata, record) {
        let   result = '';
        const i18n = Tine.Tinebase.appMgr.get('Felamimail').i18n;
        const icons = record.getFlagIcons();
        
        Ext.each(icons, function(icon) {
            result += '<img class="FelamimailFlagIcon ' + (icon.cls || "") + '" src="' + icon.src + '" ext:qtip="' + Ext.util.Format.htmlEncode(icon.qtip) + '">';
        }, this);

        let fileLocations = record.get('fileLocations');
        if (_.isArray(fileLocations) && fileLocations.length) {
            const fileLocationText = Tine.Felamimail.MessageFileAction.getFileLocationText(fileLocations, '<br>');

            result +=  fileLocationText ? ('<img class="FelamimailFlagIcon MessageFileIcon" src="images/icon-set/icon_download.svg" ' +
                'ext:qtitle="' + Ext.util.Format.htmlEncode(i18n._('Filed as:')) + '"' +
                'ext:qtip="' + Ext.util.Format.htmlEncode(fileLocationText) + '"' +
            '>') : '';
        }

        return result;
    },

    /**
     * returns account and folder globalname
     * 
     * @param {String} folderId
     * @param {Object} metadata
     * @param {Folder|Account} record
     * @return {String}
     */
    accountAndFolderRenderer: function(folderId, metadata, record) {
        var folderStore = this.app.getFolderStore(),
            account = this.app.getAccountStore().getById(record.get('account_id')),
            result = (account) ? account.get('name') : record.get('account_id'),
            folder = folderStore.getById(folderId);
        
        if (! folder) {
            folder = folderStore.getById(record.id);
            if (! folder) {
                // only account
                return (result) ? result : record.get('name');
            }
        }
        
        result += '/';
        if (folder) {
            result += folder.get('globalname');
        }

        return result;
    },
    
    /**
     * responsive content Renderer
     *
     * @param {String} folderId
     * @param {Object} metadata
     * @param {Folder|Account} record
     * @return {String}
     */
    responsiveRenderer: function(folderId, metadata, record) {
        const block = document.createElement('div');
        
        const flagIcons = record.getFlagIcons();
        const flagIconEls = flagIcons.map((icon) => {
            const iconEl = document.createElement('img');
            iconEl.id = icon.name;
            iconEl.className = 'felamimail-message-icon ' + (icon?.cls || "");
            iconEl.src = (icon?.src || '');
            iconEl.setAttribute('ext:qtip',  Ext.util.Format.htmlEncode(icon?.qtip || ''));
            if (icon?.visibility) iconEl.style.visibility = icon.visibility;
            return iconEl;
        });
        
        // unread icon
        let unreadIconEl = flagIconEls.find((item) => item.id === 'seen');
        if (!unreadIconEl) {
            unreadIconEl = document.createElement('img');
            unreadIconEl.id = 'empty';
            unreadIconEl.className = 'felamimail-message-icon ';
            unreadIconEl.src = 'images/icon-set/empty.svg';
        }
        
        // sender
        const senderEl = document.createElement('div');
        senderEl.innerHTML = Ext.util.Format.htmlEncode(record.data.from_name ?? record.data.from_email);
        senderEl.setAttribute('ext:qtip',  Ext.util.Format.htmlEncode(record.data.from_email));
        
        // recipient
        const recipientEl = document.createElement('div');
        const recipients = record.data.to.map((to) => { return to?.n_fileas || to?.email || to;});
        const extra = recipients.length > 2 ? ' ...' : '';
        recipientEl.innerHTML =  Ext.util.Format.htmlEncode(recipients.slice(0, 2).join(' & ') + extra);
        
        // attachment
        const attachmentEl = document.createElement('div');
        attachmentEl.innerHTML = '<div class="felamimail-message-icon action_attach tine-grid-row-action-icon"/>';
        attachmentEl.style.visibility = record?.data?.has_attachment ? 'visible' : 'hidden';
        
        // receivedDate
        const receivedDateEl = document.createElement('div');
        const date = record.data?.received ?? '';
        let formattedDate = date;
        let qtip = date;
        if (Ext.isFunction(date.format)) {
            const isToday = date.format('Y-m-d') === new Date().format('Y-m-d');
            const isThisWeek = date.between(new Date().add(Date.DAY, -7), new Date());
            const isThisYear = date.format('Y') === new Date().format('Y');
            formattedDate = date.format('l').substr(0,2) + Ext.util.Format.date(date, ' d/m');
            if (isThisWeek) formattedDate = date.format('l').substr(0,2) + Ext.util.Format.date(date, ' d/m H:i');
            if (isToday) formattedDate = Ext.util.Format.date(date, 'H:i');
            if (!isThisYear) formattedDate = Ext.util.Format.date(date, 'd.m.Y');
            qtip = date.format('H:i:s d/m/y');
        }
        receivedDateEl.innerHTML = formattedDate;
        receivedDateEl.setAttribute('ext:qtip',  Ext.util.Format.htmlEncode(qtip));
        
        // subject
        const subjectEl = document.createElement('div');
        subjectEl.innerHTML = Ext.util.Format.htmlEncode(record.data.subject);
        subjectEl.setAttribute('ext:qtip',  Ext.util.Format.htmlEncode(record.data.subject));
        
        
        // flags
        const displayIcons = ['answered', 'passed', 'spam', 'encrypted', 'tine20'];
        const flags = flagIconEls.filter((iconEl) => displayIcons.includes(iconEl.id));
        
        // tags
        const tagsEl = document.createElement('div');
        tagsEl.innerHTML =  Tine.Tinebase.common.tagsRenderer(record.data.tags);
        
        const row1 =  document.createElement('div');
        const row1Left = document.createElement('div');
        const row1Right =  document.createElement('div');
        row1.className = 'felamimail-message-title';
        row1Left.className = 'felamimail-message-title-row';
        row1Right.className = 'felamimail-message-title-row';
        senderEl.className = 'felamimail-message-title-text-small';
        recipientEl.className = 'felamimail-message-title-text-small';
        row1Left.appendChild(unreadIconEl);
        
        if (this.sentFolderSelected) {
            row1Left.appendChild(recipientEl);
        } else {
            row1Left.appendChild(senderEl);
        }
        row1Right.appendChild(receivedDateEl);
        
        const row2 = document.createElement('div');
        const row2Left = document.createElement('div');
        const row2Right =  document.createElement('div');
        row2.className = 'felamimail-message-title';
        row2Left.className = 'felamimail-message-title-row';
        row2Right.className = 'felamimail-message-title-row';
        row2Right.style.minWidth = '50px';
        subjectEl.className = 'felamimail-message-title-text-medium';
        
        row2Left.appendChild(attachmentEl);
        row2Left.appendChild(subjectEl);
        row2Right.appendChild(tagsEl);
        flags.forEach((flagEl) => row2Right.appendChild(flagEl));
        
        row1.appendChild(row1Left);
        row1.appendChild(row1Right);
        row2.appendChild(row2Left);
        row2.appendChild(row2Right);
        block.appendChild(row1);
        block.appendChild(row2);
        
        return  block.outerHTML;
    },

    /**
     * executed when user clicks refresh btn
     */
    doRefresh: function() {
        var folder = this.getCurrentFolderFromTree(),
            refresh = this.pagingToolbar.refresh;
            
        // refresh is explicit
        this.editBuffer = [];
        this.manualRefresh = true;
        
        if (folder) {
            refresh.disable();
            Tine.log.info('User forced mail check for folder "' + folder.get('localname') + '"');
            this.app.checkMails(folder, function() {
                refresh.enable();
                this.manualRefresh = false;
            });
        } else {
            this.filterToolbar.onFilterChange();
        }
    },
    
    /**
     * get currently selected folder from tree
     * @return {Tine.Felamimail.Model.Folder}
     */
    getCurrentFolderFromTree: function() {
        var tree = this.app.getMainScreen().getTreePanel(),
            node = tree ? tree.getSelectionModel().getSelectedNode() : null,
            folder = node ? this.app.getFolderStore().getById(node.id) : null;
        
        return folder;
    },
    
    /**
     * delete messages handler:
     * delete messages from trash/drafts folder, move messages to trash from other folders
     * 
     * @return {Boolean}
     */
    onDeleteRecords: function() {
        const account = this.app.getActiveAccount();
        const trashId = (account) ? account.getTrashFolderId() : null;
        const trash = trashId ? this.app.getFolderStore().getById(trashId) : null;
        const draftsFolderId = account.getSpecialFolderId('drafts_folder');
        const draftsFolder = draftsFolderId ? this.app.getFolderStore().getById(draftsFolderId) : null;

        let moveMessages;

        if (draftsFolder && draftsFolder.isCurrentSelection()) {
            moveMessages = false;
        } else {
            const trashConfigured = account.get('trash_folder');
            moveMessages = trash && ! trash.isCurrentSelection() || ! trash && trashConfigured;
        }

        return moveMessages
                ? this.moveSelectedMessages(trash, true, false)
                : this.deleteSelectedMessages();
    },

    /**
     * move messages handler
     *
     * @return {void}
     */
    onMoveRecords: function() {
        var selectPanel = Tine.Felamimail.FolderSelectPanel.openWindow({
            allAccounts: true,
            listeners: {
                scope: this,
                folderselect: function(node) {
                    var folder = new Tine.Felamimail.Model.Folder(node.attributes, node.attributes.id);
                    this.moveSelectedMessages(folder, false, false);
                    selectPanel.close();
                }
            }
        });
    },

    /**
     * copy messages handler
     *
     * @return {void}
     */
    onCopyRecords: function() {
        var selectPanel = Tine.Felamimail.FolderSelectPanel.openWindow({
            allAccounts: true,
            listeners: {
                scope: this,
                folderselect: function(node) {
                    var folder = new Tine.Felamimail.Model.Folder(node.attributes, node.attributes.id);
                    this.moveSelectedMessages(folder, false, true);
                    selectPanel.close();
                }
            }
        });
    },

    /**
     * file selected messages to Filemanager
     */
    onFileRecords: function() {
        var filePicker = new Tine.Filemanager.FilePickerDialog({
            windowTitle: this.app.i18n._('Select Message File Location'),
            singleSelect: true,
            requiredGrants: ['addGrant'],
            constraint: 'folder'
        });

        filePicker.on('selected', function (node) {
            this.fileRecords('Filemanager', node[0].path);
        }, this);

        filePicker.openWindow();
    },

    /**
     * file messages
     *
     * @param appName
     * @param path
     */
    fileRecords: function(appName, path) {
        var sm = this.getGrid().getSelectionModel(),
            filter = sm.getSelectionFilter();

        this.fileMessagesLoadMask = new Ext.LoadMask(Ext.getBody(), {msg: this.app.i18n._('Filing Messages')});
        this.fileMessagesLoadMask.show();
        Ext.Ajax.request({
            params: {
                method: 'Felamimail.fileMessages',
                filterData: filter,
                targetApp: appName,
                targetPath: path
            },
            timeout: 3600000, // 1 hour
            scope: this,
            success: function(result, request){
                this.afterFileRecords(result, request);
            },
            failure: function(response, request) {
                var responseText = Ext.util.JSON.decode(response.responseText),
                    exception = responseText.data;
                this.afterFileRecords(response, request, exception);
            }
        });
    },

    /**
     * show feedback when message filing has been (un)successful
     *
     * TODO reload grid when request returns?
     */
    afterFileRecords: function(result, request, error) {
        Tine.log.info('Tine.Felamimail.GridPanel::afterFileRecords');
        Tine.log.debug(result);

        this.fileMessagesLoadMask.hide();

        if (error) {
            Ext.Msg.show({
                title: this.app.i18n._('Error Filing Message'),
                msg: error.message ? error.message : this.app.i18n._('Could not file message.'),
                icon: Ext.MessageBox.ERROR,
                buttons: Ext.Msg.OK
            });
        }
    },

    /**
     * permanently delete selected messages
     */
    deleteSelectedMessages: function() {
        this.moveOrDeleteMessages(null);
    },
    
    /**
     * move selected messages to given folder
     * 
     * @param {Tine.Felamimail.Model.Folder} folder
     * @param {Boolean} toTrash
     * @param {Boolean} keepOriginalMessages
     */
    moveSelectedMessages: function(folder, toTrash, keepOriginalMessages) {
        if (folder && folder.isCurrentSelection()) {
            // nothing to do ;-)
            return;
        }
        
        this.moveOrDeleteMessages(folder, toTrash, keepOriginalMessages);
    },
    
    /**
     * move (folder !== null) or delete selected messages 
     * 
     * @param {Tine.Felamimail.Model.Folder} folder
     * @param {Boolean} toTrash
     * @param {Boolean} keepOriginalMessages
     */
    moveOrDeleteMessages: function(folder, toTrash, keepOriginalMessages) {
        
        // this is needed to prevent grid reloads while messages are moved or deleted
        this.movingOrDeleting = true;
        
        var sm = this.getGrid().getSelectionModel(),
            filter = sm.getSelectionFilter(),
            msgsIds = [];
        
        if (sm.isFilterSelect) {
            var msgs = this.getStore(),
                nextRecord = null;
        } else {
            var msgs = sm.getSelectionsCollection(),
                nextRecord = this.getNextMessage(msgs);
        }
        
        var increaseUnreadCountInTargetFolder = 0;
        msgs.each(function(msg) {
            var isSeen = msg.hasFlag('\\Seen'),
                currFolder = this.app.getFolderStore().getById(msg.get('folder_id')),
                diff = isSeen ? 0 : 1;
            
            if (currFolder) {
                currFolder.set('cache_unreadcount', currFolder.get('cache_unreadcount') - diff);
                currFolder.set('cache_totalcount', currFolder.get('cache_totalcount') - 1);
                currFolder.set('cache_status', 'pending');
                currFolder.commit();
            }
            increaseUnreadCountInTargetFolder += diff;
           
            msgsIds.push(msg.id);
            if (! keepOriginalMessages) {
                this.getStore().remove(msg);
            }
        },  this);
        
        if (folder) {
            // update unread count of target folder (only when moving)
            folder.set('cache_unreadcount', folder.get('cache_unreadcount') + increaseUnreadCountInTargetFolder);
            folder.set('cache_status', 'pending');
            folder.commit();
        }

        if (! keepOriginalMessages) {
            this.deleteQueue = this.deleteQueue.concat(msgsIds);
        }
        if (nextRecord !== null) {
            sm.selectRecords([nextRecord]);
        }
        
        this.app.checkMailsDelayedTask.delay(1000);
        
        var callbackFn = this.onAfterDelete.createDelegate(this, [msgsIds]);
        
        if (folder !== null || toTrash) {
            // move
            var targetFolderId = (toTrash) ? '_trash_' : folder.id;
            this.deleteTransactionId = Tine.Felamimail.messageBackend.moveMessages(filter, targetFolderId, keepOriginalMessages, {
                callback: callbackFn
            });
        } else {
            // delete
            this.deleteTransactionId = Tine.Felamimail.messageBackend.addFlags(filter, '\\Deleted', {
                callback: callbackFn
            });
        }
    },

    /**
     * get next message in grid
     * 
     * @param msgs
     * @return Tine.Felamimail.Model.Message
     */
    getNextMessage: function(msgs) {
        var nextRecord = null;
        
        if (msgs.getCount() >= 1 && this.getStore().getCount() > 1) {
            if (msgs.getCount() === 1) {
                // select next message (or previous if it was the last or BACKSPACE)
                var lastIdx = this.getStore().indexOf(msgs.last()),
                    direction = Ext.EventObject.getKey() == Ext.EventObject.BACKSPACE ? -1 : +1;

                nextRecord = this.getStore().getAt(lastIdx + 1 * direction);
                if (! nextRecord) {
                    nextRecord = this.getStore().getAt(lastIdx + (-1) * direction);
                }
            }
            
            if (msgs.getCount() > 1) {
                const sm = this.getGrid().getSelectionModel();
                msgs.each(function (msg) {
                    let idx = this.getStore().indexOfId(msg.id);
                    nextRecord = this.getStore().getAt(idx + 1);
                    
                    // return the first unselected row as nextRecord
                    if (!sm.isSelected(idx + 1)) {
                        return false;
                    }
                    
                    //select the previous row before the first selected one, if user select till the last row
                    if (idx === (this.getStore().getCount() - 1)) {
                        idx = this.getStore().indexOfId(msgs.items[0].id);
                        nextRecord = this.getStore().getAt(idx - 1) || null;
                        return false;
                    }
                }, this)
            }
        }
        
        return nextRecord;
    },
    
    /**
     * executed after a msg compose
     * 
     * @param {String} composedMsg
     * @param {String} action
     * @param {Array}  [affectedMsgs]  messages affected 
     * @param {String} [mode]
     */
    onAfterCompose: function(composedMsg, action, affectedMsgs, mode) {
        Tine.log.debug('Tine.Felamimail.GridPanel::onAfterCompose / arguments:');
        Tine.log.debug(arguments);

        // mark send folders cache status incomplete
        composedMsg = Ext.isString(composedMsg) ? new this.recordClass(Ext.decode(composedMsg)) : composedMsg;
        
        // NOTE: if affected messages is decoded, we need to fetch the originals out of our store
        if (Ext.isString(affectedMsgs)) {
            var msgs = [],
                store = this.getStore();
            Ext.each(Ext.decode(affectedMsgs), function(msgData) {
                var msg = store.getById(msgData.id);
                if (msg) {
                    msgs.push(msg);
                }
            }, this);
            affectedMsgs = msgs;
        }
        
        var composerAccount = this.app.getAccountStore().getById(composedMsg.get('account_id')),
            sendFolderId = composerAccount ? composerAccount.getSendFolderId() : null,
            sendFolder = sendFolderId ? this.app.getFolderStore().getById(sendFolderId) : null;
            
        if (sendFolder) {
            sendFolder.set('cache_status', 'incomplete');
        }
        
        if (Ext.isArray(affectedMsgs)) {
            Ext.each(affectedMsgs, function(msg) {
                if (['reply', 'forward'].indexOf(action) !== -1) {
                    msg.addFlag(action === 'reply' ? '\\Answered' : 'Passed');
                } else if (action === 'senddraft') {
                    this.deleteTransactionId = Tine.Felamimail.messageBackend.addFlags(msg.id, '\\Deleted', {
                        callback: this.onAfterDelete.createDelegate(this, [[msg.id]])
                    });
                }
            }, this);
        } 
    },
    
    /**
     * executed after msg delete
     * 
     * @param {Array} [ids]
     */
    onAfterDelete: function(ids) {
        this.deleteQueue = this.deleteQueue.diff(ids);
        this.editBuffer = this.editBuffer.diff(ids);
        
        this.movingOrDeleting = false;
        
        Tine.log.debug('Tine.Felamimail.GridPanel::onAfterDelete() -> Loading grid data after delete.');
        this.loadGridData({
            removeStrategy: 'keepBuffered',
            autoRefresh: true
        });
    },
    
    /**
     * check if delete/move action is running atm
     * 
     * @return {Boolean}
     */
    noDeleteRequestInProgress: function() {
        return (
            ! this.movingOrDeleting && 
            (! this.deleteTransactionId || ! Tine.Felamimail.messageBackend.isLoading(this.deleteTransactionId))
        );
    },
    
    /**
     * compose new message handler
     */
    @keydown(['ctrl+n', 'ctrl+m'])
    onMessageCompose: function() {
        var activeAccount = Tine.Tinebase.appMgr.get('Felamimail').getActiveAccount();
        
        var win = Tine.Felamimail.MessageEditDialog.openWindow({
            accountId: activeAccount ? activeAccount.id : null,
            listeners: {
                'update': this.onAfterCompose.createDelegate(this, ['compose', []], 1)
            }
        });
    },
    
    /**
     * forward message(s) handler
     */
    @keydown('ctrl+l')
    onMessageForward: function() {
        var sm = this.getGrid().getSelectionModel(),
            msgs = sm.getSelections(),
            msgsData = [];
            
        Ext.each(msgs, function(msg) {msgsData.push(msg.data)}, this);
        
        if (sm.getCount() > 0) {
            var win = Tine.Felamimail.MessageEditDialog.openWindow({
                forwardMsgs : Ext.encode(msgsData),
                listeners: {
                    'update': this.onAfterCompose.createDelegate(this, ['forward', msgs], 1)
                }
            });
        }
    },
    
    @keydown('ctrl+r')
    replyMessage() {
        return this.getGrid().getSelectionModel().getSelected() ? this.onMessageReplyTo(false) : false;
    },
    
    @keydown('ctrl+shift+r')
    replyMessageAll() {
        return this.getGrid().getSelectionModel().getSelected() ? this.onMessageReplyTo(true) : false;
    },
    
    @keydown(['a', 'ctrl+s'])
    fileMessage() {
        return this.getGrid().getSelectionModel().getSelected() ? this.action_fileRecord.handler() : false;
    },
    
    @keydown(['ctrl+o', 'enter'])
    openMessage() {
        return this.getGrid().getSelectionModel().getSelected() ? this.onRowDblClick(this.grid) : false;
    },
    
    @keydown('1')
    toggleMessageFlagged() {
        return this.getGrid().getSelectionModel().getSelected() ? this.onToggleFlag('\\Flagged') : false;
    },
    
    @keydown('m')
    toggleMessageUnread() {
        return this.getGrid().getSelectionModel().getSelected() ? this.onToggleFlag('\\Seen') : false;
    },
    
    @keydown('j')
    markMessagesSpam() {
        return this.getGrid().getSelectionModel().getSelected() ? this.processSpamStrategy('spam') : false;
    },
    
    @keydown('shift+j')
    markMessagesHam() {
        return this.getGrid().getSelectionModel().getSelected() ? this.processSpamStrategy('ham') : false;
    },
    
    getKeyBindingData() {
        const data = [
            'Ctrl+N / Ctrl+M : Compose message',
            'Ctrl+L : Forward message',
            'Ctrl+R : Reply message',
            'Ctrl+Shift+R : Reply to all',
            'A / Ctrl+S : File message',
            'Ctrl+O / Enter : Open message',
            '1 : Toggle highlighting',
            'M : Mark read/unread',
            'J : Mark message as SPAM',
            'Shift+J : Mark message as HAM',
        ];
        return data.map((item) => this.app.i18n._(item));
    },
    
    /**
     * reply message handler
     * 
     * @param {bool} toAll
     */
    onMessageReplyTo: function(toAll) {
        var sm = this.getGrid().getSelectionModel(),
            msg = sm.getSelected();
            
        if (! msg) return;
        
        Tine.Felamimail.MessageEditDialog.openWindow({
            replyTo : Ext.encode(msg.data),
            replyToAll: toAll,
            listeners: {
                'update': this.onAfterCompose.createDelegate(this, ['reply', [msg]], 1)
            }
        });
    },
    
    /**
     * called when a row gets selected
     * 
     * @param {SelectionModel} sm
     * @param {Number} rowIndex
     * @param {Tine.Felamimail.Model.Message} record
     * @param {Number} retryCount
     * 
     * TODO find a better way to check if body is fetched, this does not work correctly if a message is removed
     *       and the next one is selected automatically
     */
    onRowSelection: function(sm, rowIndex, record, retryCount) {
        if (sm.getCount() == 1 && (! retryCount || retryCount < 5) && ! record.bodyIsFetched()) {
            Tine.log.debug('Tine.Felamimail.GridPanel::onRowSelection() -> Deferring onRowSelection');
            retryCount = (retryCount) ? retryCount++ : 1;
            return this.onRowSelection.defer(250, this, [sm, rowIndex, record, retryCount+1]);
        }
        
        if (sm.getCount() == 1 && sm.isIdSelected(record.id) && !record.hasFlag('\\Seen')) {
            Tine.log.debug('Tine.Felamimail.GridPanel::onRowSelection() -> Selected unread message');
            Tine.log.debug(record);

            if (Tine.Felamimail.registry.get('preferences').get('markEmailRead') === 1) {
                setTimeout( ()=>  {
                    record.addFlag('\\Seen');
                    record.mtime = new Date().getTime();
                    Tine.Felamimail.messageBackend.addFlags(record.id, '\\Seen');
                    this.app.getMainScreen().getTreePanel().decrementCurrentUnreadCount();
                }, 2000);
            }
            
            if (record.get('headers')['disposition-notification-to']) {
                Ext.Msg.confirm(
                    this.app.i18n._('Send Reading Confirmation'),
                    this.app.i18n._('Do you want to send a reading confirmation message?'), 
                    function(btn) {
                        if (btn == 'yes'){
                            Tine.Felamimail.sendReadingConfirmation(record.id);
                        }
                    }, 
                    this
                );
            }
        }
    },

    /**
     * open first file location when file icon is clicked
     */
    onRowClick: function(grid, row, e) {
        if (e.getTarget('.MessageFileIcon')) {
            let record = this.getStore().getAt(row);
            let fileLocation = record.get('fileLocations')[0];
            Tine.Felamimail.MessageFileAction.locationClickHandler(fileLocation.model, fileLocation.record_id);

            e.stopEvent();
        } else if (e.getTarget('.action_attach')) {
            if (Tine.Tinebase.appMgr.isEnabled('Filemanager')) {
                const me = this;

                Tine.Filemanager.QuickLookPanel.openWindow({
                    record: this.getStore().getAt(row),
                    initialApp: this.app,
                    sm: grid.getSelectionModel(),
                    handleAttachments: Tine.Felamimail.MailDetailsPanel.prototype.quicklookHandleAttachments
                });
            }
        } else {
            Tine.Felamimail.GridPanel.superclass.onRowClick.apply(this, arguments);
        }
    },
    
    /**
     * row doubleclick handler
     * 
     * - opens message edit dialog (if draft/template)
     * - opens message display dialog (everything else)
     * 
     * @param {Tine.Felamimail.GridPanel} grid
     */
    onRowDblClick: function(grid) {
        
        var record = this.grid.getSelectionModel().getSelected(),
            folder = this.app.getFolderStore().getById(record.get('folder_id')),
            account = this.app.getAccountStore().getById(folder.get('account_id')),
            action = (folder.get('globalname') == account.get('drafts_folder')) ? 'senddraft' :
                      folder.get('globalname') == account.get('templates_folder') ? 'sendtemplate' : null,
            win;
        
        // check folder to determine if mail should be opened in compose dlg
        if (action !== null) {
            win = Tine.Felamimail.MessageEditDialog.openWindow({
                draftOrTemplate: Ext.encode(record.data),
                listeners: {
                    scope: this,
                    'update': this.onAfterCompose.createDelegate(this, [action, [record]], 1)
                }
            });
        } else {
            win = Tine.Felamimail.MessageDisplayDialog.openWindow({
                record: Ext.encode(record.data),
                listeners: {
                    scope: this,
                    'update': this.onAfterCompose.createDelegate(this, ['compose', []], 1),
                    'remove': this.onRemoveInDisplayDialog
                }
            });
        }
    },
    
    /**
     * message got removed in display dialog
     * 
     * @param {} msgData
     */
    onRemoveInDisplayDialog: function (msgData) {
        var msg = this.getStore().getById(Ext.decode(msgData).id),
            folderId = msg ? msg.get('folder_id') : null,
            folder = folderId ? this.app.getFolderStore().getById(folderId) : null,
            accountId = folder ? folder.get('account_id') : null,
            account = accountId ? this.app.getAccountStore().getById(accountId) : null;
            
        this.getStore().remove(msg);
        this.onAfterDelete(null);
    },    
    
    /**
     * called when the store gets updated
     * 
     * NOTE: we only allow updateing flags BUT the actual updating is done 
     *       directly from the UI fn's to support IMAP optimised bulk actions
     */
    onStoreUpdate: function(store, record, operation) {
        if (operation === Ext.data.Record.EDIT && record.isModified('flags')) {
            record.commit()
        }
    },

    /**
     * toggle flagged status of mail(s)
     * - Flagged/Seen
     *
     * @param {String} flag
     * @param {Boolean} flagged
     */
    onToggleFlag: function(flag, flagged) {
        var sm = this.getGrid().getSelectionModel(),
            filter = sm.getSelectionFilter(),
            msgs = sm.isFilterSelect ? this.getStore() : sm.getSelectionsCollection(),
            flagCount = 0;
            
        // switch all msgs to one state -> toogle most of them
        msgs.each(function(msg) {
            flagCount += msg.hasFlag(flag) ? 1 : 0;
        });
        var action = flagCount >= Math.round(msgs.getCount()/2) ? 'clear' : 'add';
        action = _.isBoolean(flagged) ? (flagged ? 'add' : 'clear') : action
        
        Tine.log.info('Tine.Felamimail.GridPanel::onToggleFlag - Toggle flag for ' + msgs.getCount() + ' message(s): ' + flag);
        
        // mark messages in UI and add to edit buffer
        msgs.each(function(msg) {
            // update unreadcount
            if (flag === '\\Seen') {
                var isSeen = msg.hasFlag('\\Seen'),
                    folder = this.app.getFolderStore().getById(msg.get('folder_id')),
                    diff = (action === 'clear' && isSeen) ? 1 :
                           (action === 'add' && ! isSeen) ? -1 : 0;
                
                if (folder) {
                    folder.set('cache_unreadcount', folder.get('cache_unreadcount') + diff);
                    if (sm.isFilterSelect && sm.getCount() > 50 && folder.get('cache_status') !== 'pending') {
                        Tine.log.debug('Tine.Felamimail.GridPanel::onToggleFlag - Set cache status to pending for folder ' + folder.get('globalname'));
                        folder.set('cache_status', 'pending');
                    }
                    folder.commit();
                }
            }
            
            msg[action + 'Flag'](flag);
            
            this.addToEditBuffer(msg);
        }, this);
        
        if (sm.isFilterSelect && sm.getCount() > 50) {
            Tine.log.debug('Tine.Felamimail.GridPanel::moveOrDeleteMessages - Update message cache for "pending" folders');
            this.app.checkMailsDelayedTask.delay(1000);
        }
        
        // do request
        Tine.Felamimail.messageBackend[action+ 'Flags'](filter, flag);
    },
    
    /**
     * called before store queries for data
     */
    onStoreBeforeload: function(store, options) {
        this.supr().onStoreBeforeload.call(this, store, options);

        if (! Ext.isEmpty(this.deleteQueue)) {
            options.params.filter.push({field: 'id', operator: 'notin', value: this.deleteQueue});
        }
        
        // make sure, our handler is called just before the request is sent
        this.onStoreBeforeLoadFolderChange(store, options);
    },

    onStoreBeforeLoadFolderChange: function (store, options) {
        this.updateGridState();
        this.updateDefaultfilter(options.params, this.sentFolderSelected);
    },
    
    resolveSendFolderPath: function (params) {
        const pathFilter = _.find(this.latestFilter, { field: 'path' });
        const operator = _.get(pathFilter, 'operator', '');
        const value = operator.match(/equals|in/) ? _.get(pathFilter, 'value', null) : null;
        const pathFilterValue =  value && _.isArray(value) ? value[0] : value;
        const isSendFolderPath = this.isSendFolderPath(pathFilterValue);
        if (isSendFolderPath && ! this.sentFolderSelected) {
            this.sentFolderSelected = true;
        } else if (! isSendFolderPath && this.sentFolderSelected) {
            this.sentFolderSelected = false;
        }
    },
    
    isSendFolderPath: function (pathFilterValue) {
        const pathParts = _.isString(pathFilterValue) ? pathFilterValue.split('/') : null;
        if (! pathParts || pathParts.length < 3) {
            return false;
        }
        const composerAccount = this.app.getAccountStore().getById(pathParts[1]);
        
        const sendFolderIds = composerAccount ? [
            composerAccount.getSendFolderId(),
            composerAccount.getSpecialFolderId('templates_folder'),
            composerAccount.getSpecialFolderId('drafts_folder')
        ]: null;
        
        return (-1 !== sendFolderIds.indexOf(pathParts[2]));
    },

    /**
     * TODO: make quick filter search "to" in be , and remove all the filter switch in fe
     * add custom filter before or after store load
     *
     * @param params
     * @param isSentFolder
     *
     */
    updateDefaultfilter: function (params, isSentFolder) {
        return; // not longer needed as the query filter now contains 'to' as well
        let targetFilters = params?.filter?.[0]?.filters?.[0]?.filters;
        if (!targetFilters) return;

        const defaultFilterField = isSentFolder ? 'to_list' : 'query';
        const existingDefaultFilter =  _.find(targetFilters, {field: defaultFilterField});
        
        if (!existingDefaultFilter) {
            targetFilters.push({ field: defaultFilterField, operator: 'contains', value: ''});
        }

        params.filter[0].filters[0].filters = targetFilters;
    },

    /**
     * // if send, draft, template folders are selected, do the following:
     * // - hide from email + name columns from grid
     * // - show to column in grid
     * // - save this state
     * // - if grid state is changed by user, do not change columns by user
     *
     * // if switched from send, draft, template to "normal" folder
     * // - switch to default state
     */
    updateGridState: function () {
        this.resolveSendFolderPath();
        let stateId = this.sentFolderSelected ? this.sendFolderGridStateId : this.gridConfig.stateId;
        const isEastLayout = this.detailsPanelRegion === 'east';
        if (isEastLayout && !stateId.includes('_DetailsPanel_East')) stateId = stateId + '_DetailsPanel_East';
        const isStateIdChanged = this.grid.stateId !== stateId;
        if (!Ext.state.Manager.get(stateId)) this.grid.saveState();
        this.grid.stateId = stateId;
        
        const stateIdDefault = isEastLayout ? stateId : this.gridConfig.stateId;
        const stateStored = Ext.state.Manager.get(stateIdDefault);
        const stateCurrent = this.grid.getState();
        let stateCloned = stateStored;
        if (!isStateIdChanged || !stateStored) stateCloned = stateCurrent;
        let stateClonedResolved = JSON.parse(JSON.stringify(stateCloned));
        
        if (stateClonedResolved?.columns && isEastLayout) {
            const stateClonedResolvedVisibleColumns = stateClonedResolved.columns.filter((col) => { return !col.hidden;});
            if (stateClonedResolvedVisibleColumns.length === 0) {
                const restoreStateId = stateId.replace('_DetailsPanel_East', '');
                const restoreState = Ext.state.Manager.get(restoreStateId);
                const restoreStateVisibleColumns = restoreState.columns.filter((col) => { return !col.hidden;});
                if (restoreStateVisibleColumns === 0) {
                    stateClonedResolved.columns.forEach((c) => {c.hidden = c.id === 'responsive';})
                } else {
                    stateClonedResolved.columns.forEach((c, idx) => {c.hidden = restoreState.columns[idx].hidden ?? false;})
                }
            }
        }
        
        if (stateId.includes(this.sendFolderGridStateId)) {
            let refState = Ext.state.Manager.get(stateId);
            if (refState) stateClonedResolved = JSON.parse(JSON.stringify(refState));
            
            // - hide from email + name columns from grid
            // - show to column in grid
            const customHideCols = {
                'from_email' : true,
                'from_name' : true,
                'to' : false,
            }
            //overwrite custom states
            _.each(customHideCols, (isHidden, colId) => {
                const idx = _.findIndex(stateClonedResolved.columns, {id: colId});
                isHidden = !refState ? isHidden : _.get(_.find(refState.columns, {id: colId}), 'hidden', false);
                
                if (idx > -1 && isHidden !== _.get(stateClonedResolved.columns[idx], 'hidden', false)) {
                    if (isHidden) {
                        stateClonedResolved.columns[idx].hidden = true;
                    } else {
                        delete stateClonedResolved.columns[idx].hidden;
                    }
                }
            })
        }

        if (!isStateIdChanged) {
            stateClonedResolved.sort = this.store.getSortState();
        }
        this.grid.applyState(stateClonedResolved);
        // save state
        this.grid.saveState();
        if (isStateIdChanged) this.getView().refresh(true);
    },

    /**
     *  called after a new set of Records has been loaded
     *  
     * @param  {Ext.data.Store} this.store
     * @param  {Array}          loaded records
     * @param  {Array}          load options
     * @return {Void}
     */
    onStoreLoad: function(store, records, options) {
        this.supr().onStoreLoad.apply(this, arguments);
        
        Tine.log.debug('Tine.Felamimail.GridPanel::onStoreLoad(): store loaded new records.');

        let folder = this.getCurrentFolderFromTree();
        if (folder && folder.get('imap_totalcount') !== folder.get('cache_totalcount')) {
            Tine.log.debug('Tine.Felamimail.GridPanel::onStoreLoad() - Count mismatch: got ' + folder.get('imap_totalcount') + ' records for folder ' + folder.get('globalname'));
            Tine.log.debug(folder);
            folder.set('cache_status', 'pending');
            folder.commit();
            this.app.checkMailsDelayedTask.delay(1000);
        }
        this.latestFilter = _.get(options.params, 'filter[0].filters[0].filters', {});
        this.updateDefaultfilter(options.params, this.sentFolderSelected);
        this.updateQuotaBar();
        this.updateGridState();
    },
    
    /**
     * update quotaBar / only do it if we have a path filter with a single account id
     * 
     * @param {Record} accountInbox
     */
    updateQuotaBar: function(accountInbox) {
        var accountId = this.extractAccountIdFromFilter();

        if (accountId === null) {
            Tine.log.debug('No or multiple account ids in filter. Resetting quota bar.');
            this.quotaBar.hide();
            return;
        }
            
        if (! accountInbox) {
            var accountInbox = this.app.getFolderStore().queryBy(function(folder) {
                return folder.isInbox() && (folder.get('account_id') == accountId);
            }, this).first();
        }
        if (accountInbox && parseInt(accountInbox.get('quota_limit'), 10) && accountId == accountInbox.get('account_id')) {
            Tine.log.debug('Showing quota info.');
            
            var limit = parseInt(accountInbox.get('quota_limit'), 10) / 1024,
                usage = parseInt(accountInbox.get('quota_usage'), 10) * 1024;
            
            this.quotaBar.show();
            this.quotaBar.update(Tine.widgets.grid.QuotaRenderer(usage, limit, /*use SoftQuota*/ false));
        } else {
            Tine.log.debug('No account inbox found or no quota info found.');
            this.quotaBar.hide();
        }
    },
    
    /**
     * get account id from filter (only returns the id if a single account id was found)
     * 
     * @param {Array} filter
     * @return {String}
     */
    extractAccountIdFromFilter: function(filter) {
        if (! filter) {
            filter = this.filterToolbar.getValue();
        }
        
        // use first OR panel in case of filterPanel
        Ext.each(filter, function(filterData) {
            if (filterData.condition && filterData.condition == 'OR') {
                filter = filterData.filters[0].filters;
                return false;
            }
        }, this);
        
        // condition from filterPanel
        while (filter.filters || (Ext.isArray(filter) && filter.length > 0 && filter[0].filters)) {
            filter = (filter.filters) ? filter.filters : filter[0].filters;
        }
        
        var accountId = null, 
            filterAccountId = null,
            accountIdMatch = null;

        for (var i = 0; i < filter.length; i++) {
            if (filter[i].field == 'path' && filter[i].operator == 'in') {
                for (var j = 0; j < filter[i].value.length; j++) {
                    accountIdMatch = filter[i].value[j].match(/^\/([a-z0-9]*)/i);
                    if (accountIdMatch) {
                        filterAccountId = accountIdMatch[1];
                        if (accountId && accountId != filterAccountId) {
                            // multiple different account ids found!
                            return null;
                        } else {
                            accountId = filterAccountId;
                        }
                    }
                }
            }
        }
        
        return accountId;
    },
    
    /**
     * add new account button
     * 
     * @param {Button} button
     * @param {Event} event
     */
    onAddAccount: function(button, event) {
        // it is only allowed to create user (external) accounts here
        var newAccount = new Tine.Felamimail.Model.Account({
            type: 'user'
        });
        // this is a little bit clunky but seems to be required to prevent record loading in AccountEditDialog
        newAccount.id = null;

        // make sure accountStore is initialised
        this.app.getAccountStore();

        var popupWindow = Tine.Felamimail.AccountEditDialog.openWindow({
            record: newAccount
        });
    },
    
    /**
     * create task handler
     *
     * @param {Button} button
     * @param {Event} event
     */
    onCreateTask: function(button, event) {
        const sm = this.getGrid().getSelectionModel();
        const msgs = sm.isFilterSelect ? this.getStore() : sm.getSelectionsCollection();

        if (msgs.length !== 1) {
            return ;
        }
        
        const msg = msgs.items[0];
        
        if (! msg?.data) {
            return ;
        }
        
        this.setIconClass('x-btn-wait');

        const popupWindow = Tine.Tasks.TaskEditDialog.openWindow({
            contentPanelConstructorInterceptor: async (config) => {
                const isTaskEnabled = Tine.Tinebase.appMgr.isEnabled('Tasks');
                const waitingText = isTaskEnabled ? Tine.Tinebase.appMgr.get('Tasks').i18n._('Creating new Task...') : 'Creating new Task...';
                const mask = await config.setWaitText(waitingText);
                
                const messageData = msg.data;
                const body = Tine.Tinebase.common.html2text(messageData?.body || '');
                const subject = messageData?.subject ? messageData.subject : isTaskEnabled ? Tine.Tinebase.appMgr.get('Tasks').i18n._('New Task') : 'New Task';
                config.record = Tine.Tinebase.data.Record.setFromJson(await Tine.Tasks.saveTask(Ext.apply(Tine.Tasks.Model.Task.getDefaultData(), {
                    summary: subject,
                    description: body
                })), Tine.Tasks.Model.Task);
    
                // a quick hack to prevent duplicated popupwindow
                config.window.manager.unregister(config.window);
                config.window.id = config.window.name = Tine.Tasks.TaskEditDialog.prototype.windowNamePrefix + config.record.id;
                config.window.manager.register(config.window);
                
                const messageFilter = [{
                    field: 'id',
                    operator: 'in',
                    value: [msg.data.id]
                }];

                const locations = [{
                    type: 'attachment',
                    model: 'Tasks_Model_Task',
                    record_id: config.record.data,
                    record_title: config.record.getTitle()
                }];
                
                await Tine.Felamimail.fileMessages(messageFilter, locations)
                    .catch((error) => {
                        win.Ext.Msg.show({
                            title: this.app.formatMessage('Error'),
                            msg: error.message,
                            buttons: Ext.MessageBox.OK,
                            icon: Ext.MessageBox.ERROR
                        });
                    })
    
                config.listeners = {
                    single: true,
                    load: function() {
                        mask.hide();
                    }
                };
            }
        });
    },
    
    /**
     * print handler
     * 
     * @todo move this to Ext.ux.Printer as iframe driver
     * @param {Tine.Felamimail.GridDetailsPanel} details panel [optional]
     */
    onPrint: function(detailsPanel) {
        var id = Ext.id(),
            doc = document,
            frame = doc.createElement('iframe');
            
        Ext.fly(frame).set({
            id: id,
            name: id,
            style: {
                position: 'absolute',
                width: '210mm',
                height: '297mm',
                top: '-10000px', 
                left: '-10000px'
            }
        });
        
        doc.body.appendChild(frame);

        Ext.fly(frame).set({
           src : Ext.SSL_SECURE_URL
        });

        var doc = frame.contentWindow.document || frame.contentDocument || WINDOW.frames[id].document,
            content = this.getDetailsPanelContentForPrinting(detailsPanel || this.detailsPanel);
            
        doc.open();
        doc.write(content);
        doc.close();
        
        frame.contentWindow.focus();
        frame.contentWindow.print();
    },
    
    /**
     * get detail panel content
     * 
     * @param {Tine.Felamimail.GridDetailsPanel} details panel
     * @return {String}
     */
    getDetailsPanelContentForPrinting: function(detailsPanel) {
        // TODO somehow we have two <div class="preview-panel-felamimail"> -> we need to fix that and get the first element found
        var detailsPanels = detailsPanel.getEl().query('.preview-panel-felamimail');
        var detailsPanelContent = (detailsPanels.length > 1) ? detailsPanels[1].innerHTML : detailsPanels[0].innerHTML;
        
        var buffer = '<html><head>';
        buffer += '<title>' + this.app.i18n._('Print Preview') + '</title>';
        buffer += '</head><body>';
        buffer += detailsPanelContent;
        buffer += '</body></html>';
        
        return buffer;
    },
    
    /**
     * print preview handler
     * 
     * @param {Tine.Felamimail.GridDetailsPanel} details panel [optional]
     */
    onPrintPreview: function(detailsPanel) {
        var content = this.getDetailsPanelContentForPrinting(detailsPanel || this.detailsPanel);
        
        var win = window.open('about:blank',this.app.i18n._('Print Preview'),'width=500,height=500,scrollbars=yes,toolbar=yes,status=yes,menubar=yes');
        win.document.open()
        win.document.write(content);
        win.document.close();
        win.focus();
    },
    
    /**
     * format headers
     * 
     * @param {Object} headers
     * @param {Bool} ellipsis
     * @param {Bool} onlyImportant
     * @return {String}
     */
    formatHeaders: function(headers, ellipsis, onlyImportant, plain) {
        let result = '';
        let header = '';
        for (header in headers) {
            if (headers.hasOwnProperty(header) && 
                    (! onlyImportant || header == 'from' || header == 'to' || header == 'cc' || header == 'subject' || header == 'date')) 
            {
                result += (plain ? (header + ': ') : ('<b>' + header + ':</b> '))
                    + Ext.util.Format.htmlEncode(
                        (ellipsis) 
                            ? Ext.util.Format.ellipsis(headers[header], 40)
                            : headers[header]
                    ) + (plain ? '\n' : '<br/>');
            }
        }
        return result;
    },

    /**
     * process spam strategy and refresh grid panel
     *
     * @param option
     */
    processSpamStrategy: async function (option) {
        const sm = this.getGrid().getSelectionModel();
        const msgs = sm.isFilterSelect ? this.getStore() : sm.getSelectionsCollection();
        const nextRecord = sm.isFilterSelect ? null : this.getNextMessage(msgs);
        const account = this.app.getActiveAccount();
        const msgsIds = [];
        
        try {
            const promises = [];
            let increaseUnreadCountInTargetFolder = 0;
            
            this.movingOrDeleting = true;
            
            msgs.each(function (msg) {
                const currFolder = this.app.getFolderStore().getById(msg.get('folder_id'));

                if (currFolder) {
                    if ('spam' === option) {
                        this.getStore().remove(msg);
                        this.deleteQueue.push(msg.id);
                        msgsIds.push(msg.id);
    
                        //spam strategy will execute move message , ham will remain in current folder
                        const isSeen = msg.hasFlag('\\Seen');
                        const diff = isSeen ? 0 : 1;
                        
                        currFolder.set('cache_unreadcount', currFolder.get('cache_unreadcount') - diff);
                        currFolder.set('cache_totalcount', currFolder.get('cache_totalcount') - 1);
                        currFolder.set('cache_status', 'pending');
                        currFolder.commit();
                        
                        increaseUnreadCountInTargetFolder += diff;
                    }
                    
                    if ('ham' === option) {
                        let subject = msg.get('subject').replace(/SPAM\? \(.+\) \*\*\* /, '');
                        msg.set('subject', subject);
                        msg.set('is_spam_suspicions', false);
                        msg.commit();
                        this.addToEditBuffer(msg);
                    }
                    
                }
                promises.push(Tine.Felamimail.processSpam(msg, option));

            }, this);
            
            // update unread count of trash folder (only needed for spam Strategy)
            if ('spam' === option) {
                const trashFolderId = account ? account.getTrashFolderId() : null;
                const targetFolder = trashFolderId ? this.app.getFolderStore().getById(trashFolderId) : null;
                
                if (targetFolder) {
                    targetFolder.set('cache_unreadcount', targetFolder.get('cache_unreadcount') + increaseUnreadCountInTargetFolder);
                    targetFolder.set('cache_status', 'pending');
                    targetFolder.commit();
                }
                
                if (nextRecord) {
                    sm.selectRecords([nextRecord]);
                }
            }
            
            if ('ham' === option ) {
                sm.selectRecords(msgs.items, true);
            }
            
            await Promise.allSettled(promises)
                .then(() => {
                    if ('spam' === option) {
                        this.onAfterDelete(msgsIds);
                    }
                    
                    this.doRefresh();
            });
            
        } catch (e) {
            this.doRefresh();
        }
    },

    /**
     * - hide spam actions if the current folder is trash/junk folder
     * - disable spam actions if the message has no spam flag
     */
    onDisplaySpamActions: function () {
        if (!this.app.featureEnabled('featureSpamSuspicionStrategy')) return;
        
        const folder = this.getCurrentFolderFromTree();
        const account = folder ? this.app.getAccountStore().getById(folder.get('account_id')) : null;

        this.action_spam.show();
        this.action_ham.show();
        
        if (!account || account.get('type') === 'user') {
            this.action_spam.hide();
            this.action_ham.hide();
        }
    }
});

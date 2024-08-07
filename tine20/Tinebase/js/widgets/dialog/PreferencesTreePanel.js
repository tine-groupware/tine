/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        create generic app tree panel?
 * @todo        add button: set default value(s)
 */

Ext.ns('Tine.widgets', 'Tine.widgets.dialog');

/**
 * preferences application tree panel
 * 
 * @namespace   Tine.widgets.dialog
 * @class       Tine.widgets.dialog.PreferencesTreePanel
 * @extends     Ext.tree.TreePanel
 */
Tine.widgets.dialog.PreferencesTreePanel = Ext.extend(Ext.tree.TreePanel, {

    /**
     * @cfg String  initialNodeId to select after render
     */
    initialNodeId: null,
    
    // presets
    iconCls: 'x-new-application',
    rootVisible: true,
    border: false,
    autoScroll: true,
    bodyStyle: 'background-color:white',
    
    /**
     * initComponent
     * 
     */
    initComponent: function(){
        
        Tine.widgets.dialog.PreferencesTreePanel.superclass.initComponent.call(this);
        
        this.initTreeNodes();
        this.initHandlers();
        this.selectInitialNode.defer(200, this);
    },

    /**
     * select initial node
     */
    selectInitialNode: function() {
        const initialNode = (this.initialNodeId !== null) ? this.getNodeById(this.initialNodeId) : this.getRootNode();
        this.fireEvent('click', initialNode);
    },
    
    /**
     * initTreeNodes with Tinebase and apps prefs
     * 
     * @private
     */
    initTreeNodes: function() {
        
        // general preferences are tree root
        const treeRoot = new Ext.tree.TreeNode({
            text: i18n._('All Settings'),
            id: 'All',
            draggable: false,
            allowDrop: false,
            expanded: true
        });
        const genericNode = new Ext.tree.TreeNode({
            text: i18n._('General Preferences'),
            id: 'Tinebase',
            draggable: false,
            allowDrop: false,
            expanded: true,
        });
        const applicationsNode = new Ext.tree.TreeNode({
            text: i18n._('Application Settings'),
            id: 'Applications',
            draggable: false,
            allowDrop: false,
            expanded: true,
            readyOnly: true,
        });
        
        this.setRootNode(treeRoot);
        treeRoot.appendChild(genericNode);
        treeRoot.appendChild(applicationsNode);
        
        // add all apps
        const allApps = Tine.Tinebase.appMgr.getAll();

        // add "My Profile"
        if (Tine.Tinebase.common.hasRight('manage_own_profile', 'Tinebase')) {
            const profileNode = new Ext.tree.TreeNode({
                text: i18n._('My Profile'),
                cls: 'file',
                iconCls: 'tinebase-accounttype-user',
                id: 'Tinebase.UserProfile',
                leaf: null
            });
            treeRoot.appendChild(profileNode);
        }
    
        // sort nodes by translated title (text property)
        this.treeSorter = new Ext.tree.TreeSorter(this, {
            dir: "asc",
            priorityProperty: 'id',
            priorityList: ['Tinebase', 'Tinebase.UserProfile', 'Applications'],
        });
        
        // console.log(allApps);
        allApps.each(function(app) {
            if (app && Ext.isFunction(app.getTitle)) {
                if (app.appName !== 'Tinebase') {
                    const node = new Ext.tree.TreeNode({
                        text: app.getTitle(),
                        cls: 'file',
                        id: app.appName,
                        iconCls: app.getIconCls('PreferencesTreePanel'),
                        leaf: null
                    });
    
                    applicationsNode.appendChild(node);
                }
            }
        }, this);
    },
    
    /**
     * initTreeNodes with Tinebase and apps prefs
     * 
     * @private
     */
    initHandlers: function() {
        this.on('click', function(node){
            // note: if node is clicked, it is not selected!
            if (node.id === 'Applications' || node.id === 'All') {
                return;
            }
            node.getOwnerTree().selectPath(node.getPath());
            node.expand();
            
            // get parent pref panel
            var parentPanel = this.findParentByType(Tine.widgets.dialog.Preferences);

            // add panel to card panel to show prefs for chosen app
            parentPanel.showPrefsForApp(node.id);
        }, this);
        
        this.on('beforeexpand', function(_panel) {
            if(_panel.getSelectionModel().getSelectedNode() === null) {
                _panel.expandPath('/Tinebase');
                _panel.selectPath('/Tinebase');
            }
            _panel.fireEvent('click', _panel.getSelectionModel().getSelectedNode());
        }, this);
    },

    /**
     * check grants for tree nodes / apps
     *
     * @param {Bool} adminMode
     * @param accountId
     */
    checkGrants: function(adminMode, accountId = '0') {
        const root = this.getRootNode();
        
        const validate = (node, hasRight) => {
            if (!hasRight && adminMode) {
                node.disable();
            } else {
                node.enable();
            }
        }
        
        root.eachChild((node) => {
            // enable or disable according to admin rights / admin mode
            switch (node.id) {
                case 'Tinebase':
                    validate(node, Tine.Tinebase.common.hasRight('admin', node.id));
                    break;
                case 'Tinebase.UserProfile':
                    const hasRight = (Tine.Tinebase.common.hasRight('manage_accounts', 'Admin') && accountId !== '0')
                     || accountId === _.get(Tine.Tinebase.registry.get('currentAccount'), 'accountId');
                    validate(node, hasRight);
                    break;
                case 'Applications':
                    _.each(node.childNodes, (appNode) => {
                        validate(appNode, Tine.Tinebase.common.hasRight('admin', appNode.id));
                    });
                    break;
            }
        });

    }
});

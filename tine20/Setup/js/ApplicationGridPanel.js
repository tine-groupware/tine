/*
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine', 'Tine.Setup');

/**
 * @namespace   Tine.Setup
 * @class       Tine.Setup.ApplicationGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Application Setup Grid Panel</p>
 * <p></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Setup.ApplicationGridPanel
 */
Tine.Setup.ApplicationGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {

    /**
     * @private
     */
    recordClass: Tine.Setup.Model.Application,
    recordProxy: Tine.Setup.ApplicationBackend,
    stateful: false,
    evalGrants: false,
    defaultSortInfo: {field: 'name', dir: 'ASC'},
    gridConfig: {
        autoExpandColumn: 'name'
    },
    
    /**
     * @private
     */
    initComponent: function() {
                
        this.gridConfig.columns = this.getColumns();
        
        Tine.Setup.ApplicationGridPanel.superclass.initComponent.call(this);
        
        // activate local sort
        this.store.remoteSort = false;
        
        // add selection of updateable apps after store load
        this.store.on('load', this.selectApps, this);
    },
    
    /**
     * @private
     */
    getColumns: function() {
        const columns = [
            { id: 'name', width: 350, header: this.app.i18n._("Name") },
            { id: 'status', width: 70, header: this.app.i18n._("Enabled"), renderer: this.enabledRenderer },
            { id: 'order', width: 50, header: this.app.i18n._("Order") },
            { id: 'version', width: 85, header: this.app.i18n._("Installed Version") },
            { id: 'current_version', width: 85, header: this.app.i18n._("Available Version") },
            { id: 'install_status', width: 70, header: this.app.i18n._("Status"), renderer: this.upgradeStatusRenderer.createDelegate(this) },
            { id: 'depends', width: 150, header: this.app.i18n._("Depends on") }
        ];
        return columns;
    },
    
    /**
     * @private
     */
    initActions: function() {
        this.action_installApplications = new Ext.Action({
            text: this.app.i18n._('Install application'),
            handler: this.onAlterApplications,
            actionType: 'install',
            iconCls: 'setup_action_install',
            disabled: true,
            scope: this
        });
        
        this.action_uninstallApplications = new Ext.Action({
            text: this.app.i18n._('Uninstall application'),
            handler: this.onAlterApplications,
            actionType: 'uninstall',
            iconCls: 'setup_action_uninstall',
            disabled: true,
            scope: this
        });
        
        this.action_updateApplications = new Ext.Action({
            text: this.app.i18n._('Update applications'),
            handler: this.onAlterApplications,
            actionType: 'update',
            iconCls: 'setup_action_update',
            scope: this
        });
        
        this.action_gotoLogin = new Ext.Action({
            text: String.format(this.app.i18n._('Go to {0} login'), Tine.title),
            handler: this.onGotoLogin,
            iconCls: 'action_login',
            scope: this
        });
        
        this.actions = [
            this.action_installApplications,
            this.action_uninstallApplications,
            this.action_updateApplications,
            '-',
            this.action_gotoLogin
        ];
        
        this.actionToolbar = new Ext.Toolbar({
            split: false,
            height: 26,
            items: this.actions
        });
        
        this.contextMenu = new Ext.menu.Menu({
            plugins: [{
                ptype: 'ux.itemregistry',
                key:   'Tinebase-MainContextMenu'
            }],
            items: this.actions
        });
    },
    
    /**
     * overwrite default - we do not have a filterpanel here
     */
    initFilterPanel: function() {},
    
    /**
     * @private
     */
    initGrid: function() {
        Tine.Setup.ApplicationGridPanel.superclass.initGrid.call(this);
        this.selectionModel.purgeListeners();
        
        this.selectionModel.on('selectionchange', this.onSelectionChange, this);
    },

    /**
     * @private
     */
    onSelectionChange: function(sm) {
        var apps = sm.getSelections();
        var disabled = sm.getCount() == 0;
        
        var nIn = disabled, nUp = disabled, nUn = disabled,        
            addressbook, admin, tinebase;
        
        for(var i=0; i<apps.length; i++) {
            var status = apps[i].get('install_status');
            nIn = nIn || status == 'uptodate' || status == 'updateable';
            nUp = nUp || status == 'uptodate' || status == 'uninstalled';
            nUn = nUn || status == 'uninstalled';
            if (apps[i].id == 'Addressbook') addressbook = true;
            else if (apps[i].id == 'Tinebase') tinebase = true;
            else if (apps[i].id == 'Admin') admin = true;
        }
        
        if(this.store.getById('Tinebase').get('install_status') == 'uninstalled') tinebase = false;
        if((addressbook || admin ) && !tinebase) nUn = true;
        
        this.action_installApplications.setDisabled(nIn);
        this.action_uninstallApplications.setDisabled(nUn);
    },
    
    /**
     * @private
     */
    onAlterApplications: function(btn, e) {

        if (btn.actionType == 'uninstall') {
            // get user confirmation before uninstall
            Ext.Msg.confirm(this.app.i18n._('uninstall'), this.app.i18n._('Do you really want to uninstall the application(s)?'), function(confirmbtn, value) {
                if (confirmbtn == 'yes') {
                    this.alterApps(btn.actionType);
                }
            }, this);
        } else {
            this.alterApps(btn.actionType);
        }
    },
    
    /**
     * goto tine 2.0 login screen
     * 
     * @param {Button} btn
     * @param {Event} e
     */
    onGotoLogin: function(btn, e) {
        window.location = window.location.href.replace(/setup(\.php)*/, '');
    },
    
    /**
     * select all installable or updateable apps
     * @private
     */
    selectApps: function() {
        
        var updateable = [];
        
        this.store.each(function(record) {
            if (record.get('install_status') == 'updateable') {
                updateable.push(record);
            }
        }, this);
        
        this.selectionModel.selectRecords(updateable);
    },
    
    /**
     * alter applications
     * 
     * @param {} type (uninstall/install/update)
     * @private
     */
    alterApps: function(type) {

        var appNames = [];
        var apps = this.selectionModel.getSelections();
        
        for(var i=0; i<apps.length; i++) {
            appNames.push(apps[i].get('name'));
        }

        this.sendAlterApplicationsRequest(type, appNames, null);
    },
    
    /**
     * @private
     */
    sendAlterApplicationsRequest: function(type, appNames, options) {
        var msg = this.app.i18n.n_('Updating application', 'Updating applications', appNames.length);
        if (appNames.length === 1) {
            msg = msg + ' "' + appNames[0] + '"';
        }
        msg = msg + ' - ' + this.app.i18n._('This may take a while');

        var longLoadMask = new Ext.LoadMask(this.grid.getEl(), {
            msg: msg,
            removeMask: true
        });
        longLoadMask.show();
        Ext.Ajax.request({
            scope: this,
            params: {
                method: 'Setup.' + type + 'Applications',
                applicationNames: appNames,
                options: options
            },
            success: function(response) {
                var regData = Ext.util.JSON.decode(response.responseText);
                // replace some registry data
                for (key in regData) {
                    if (key != 'status' && key != 'success') {
                        Tine.Setup.registry.replace(key, regData[key]);
                    }
                }
                this.store.load();
                longLoadMask.hide();
            },
            failure: function(exception) {
                longLoadMask.hide();
                
                var exception = Ext.util.JSON.decode(exception.responseText).data;
                
                switch (exception.code) {
                    //Dependency Exception
                    case 501:
                        Ext.MessageBox.show({
                            title: this.app.i18n._('Dependency Violation'), 
                            msg: exception.message,
                            buttons: Ext.Msg.OK,
                            icon: Ext.MessageBox.WARNING
                        });
                        this.store.load();
                        break;
                    case 901:
                        Ext.MessageBox.show({
                            title: this.app.i18n._('CLI Required'), 
                            msg: this.app.i18n._(exception.message),
                            buttons: Ext.Msg.OK,
                            icon: Ext.MessageBox.ERROR
                        });
                        break;
                    default:
                        Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
                }
            }
        });
    },
    
    /**
     * @private
     */
    enabledRenderer: function(value) {
        return Tine.Tinebase.common.booleanRenderer(value == 'enabled');
    },
    
    /**
     * @private
     */
    upgradeStatusRenderer: function(value) {
        return this.app.i18n._hidden(value);
    }
});

/*
 * Tine 2.0
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.namespace('Tine.Felamimail.sieve');

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.sieve.RulesDialog
 * @extends     Tine.widgets.dialog.EditDialog
 * 
 * <p>Sieve Filter Dialog</p>
 * <p>This dialog is for editing sieve filters (rules).</p>
 * <p>
 * </p>
 * 
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * 
 * @param       {Object} config
 * @constructor
 * Create a new RulesDialog
 */
Tine.Felamimail.sieve.RulesDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    /**
     * @cfg {Tine.Felamimail.Model.Account}
     */
    account: null,

    /**
     * @private
     */
    windowNamePrefix: 'RulesWindow_',
    appName: 'Felamimail',
//    loadRecord: false,
    mode: 'local',
    tbarItems: [],
    evalGrants: false,
    
    //private
    initComponent: function(){

        this.recordProxy = this.asAdminModule ? new Tine.Felamimail.RulesBackend({
            appName: 'Admin',
            modelName: 'SieveRule'
        }) : Tine.Felamimail.rulesBackend;

        Tine.Felamimail.sieve.RulesDialog.superclass.initComponent.call(this);
        
        this.i18nRecordName = this.app.i18n._('Sieve Filter Rules');
    },

    /**
     * returns canonical path part
     * @returns {string}
     */
    getCanonicalPathSegment: function () {
        return ['',
            this.appName,
            'EditDialog',
        ].join(Tine.Tinebase.CanonicalPath.separator);
    },

    /**
     * overwrite update toolbars function (we don't have record grants yet)
     * 
     * @private
     */
    updateToolbars: Ext.emptyFn,
    
    /**
     * init record to edit
     * -> we don't have a real record here
     */
    initRecord: function() {
        this.onRecordLoad();
    },
    
    /**
     * executed after record got updated from proxy
     * -> we don't have a real record here
     * 
     * @private
     */
    onRecordLoad: function() {
        // interrupt process flow till dialog is rendered
        if (! this.rendered) {
            this.onRecordLoad.defer(250, this);
            return;
        }
        
        var accountName = this.account ? this.account.get('name') : 'unknown',
            title = String.format(this.app.i18n._('Sieve Filter Rules for {0}'), accountName);
        this.window.setTitle(title);
        
        const hasRight = Tine.Felamimail.AccountEditDialog.prototype.checkAccountEditRight(this.account);
        this.action_saveAndClose.setDisabled(!hasRight);
        
        this.hideLoadMask();
    },
        
    /**
     * returns dialog
     * 
     * NOTE: when this method gets called, all initalisation is done.
     * 
     * @return {Object}
     * @private
     * 
     */
    getFormItems: function() {
        this.rulesGrid = new Tine.Felamimail.sieve.RulesGridPanel({
            account: this.account,
            recordProxy: this.recordProxy
        });
        
        return [this.rulesGrid];
    },
    
    /**
     * apply changes handler (get rules and send them to saveRules)
     */
    onApplyChanges: function(closeWindow) {
        var rules = [];
        this.rulesGrid.store.each(function(record) {
            rules.push(record.data);
        });
        
        this.loadMask.show();
        this.recordProxy.saveRules(this.account.id, rules, {
            scope: this,
            success: function(record) {
                if (closeWindow) {
                    this.purgeListeners();
                    this.window.close();
                }
            },
            failure: Tine.Felamimail.handleRequestException.createSequence(function() {
                this.hideLoadMask();
            }, this),
            timeout: 150000 // 3 minutes
        });
    }
});

/**
 * Felamimail Edit Popup
 * 
 * @param   {Object} config
 * @return  {Ext.ux.Window}
 */
Tine.Felamimail.sieve.RulesDialog.openWindow = function (config) {
    var window = Tine.WindowFactory.getWindow({
        width: 800,
        height: 400,
        name: Tine.Felamimail.sieve.RulesDialog.prototype.windowNamePrefix + Ext.id(),
        contentPanelConstructor: 'Tine.Felamimail.sieve.RulesDialog',
        contentPanelConstructorConfig: config
    });
    return window;
};

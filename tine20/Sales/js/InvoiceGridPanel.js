/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Sales');

/**
 * Invoice grid panel
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.InvoiceGridPanel
 * @extends     Tine.widgets.grid.GridPanel
 * 
 * <p>Invoice Grid Panel</p>
 * <p><pre>
 * </pre></p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>    
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.InvoiceGridPanel
 */
Tine.Sales.InvoiceGridPanel = Ext.extend(Tine.widgets.grid.GridPanel, {
    
    initComponent: function() {
        this.initDetailsPanel();
        Tine.Sales.InvoiceGridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * is called when the component is rendered
     * @param {} ct
     * @param {} position
     */
    onRender : function(ct, position) {
        this.billMask = new Ext.LoadMask(ct, {msg: this.app.i18n._('Rebilling Invoice...')});
        Tine.Sales.ContractGridPanel.superclass.onRender.call(this, ct, position);
    },
    
    /**
     * @todo: make this generally available (here its more general: Tine.HumanResources.EmployeeGridPanel)
     * 
     * returns additional toobar items
     * 
     * @return {Array} of Ext.Action
     */
    getActionToolbarItems: function() {
        this.actions_reversal = new Ext.Action({
            text: this.app.i18n._('Create Reversal Invoice'),
            iconCls: 'action_reversal',
            scope: this,
            disabled: true,
            allowMultiple: false,
            handler: this.onReverseInvoice,
            actionUpdater: function(action, grants, records) {
                if (records.length == 1 && records[0].get('type') == 'INVOICE' && records[0].get('number')) {
                    action.enable();
                } else {
                    action.disable();
                }
            }
        });

        var reversalButton = Ext.apply(new Ext.Button(this.actions_reversal), {
            scale: 'medium',
            rowspan: 2,
            iconAlign: 'top'
        });
        
        this.actions_rebill = new Ext.Action({
            text: this.app.i18n._('Rebill Invoice'),
            iconCls: 'action_rebill',
            scope: this,
            disabled: true,
            allowMultiple: false,
            handler: this.onRebillInvoice,
            actionUpdater: function(action, grants, records) {
                if (records.length == 1 && records[0].get('type') == 'INVOICE' && records[0].get('cleared') != 'CLEARED'  && records[0].get('is_auto')) {
                    action.enable();
                } else {
                    action.disable();
                }
            }
        });

        var rebillButton = Ext.apply(new Ext.Button(this.actions_rebill), {
            scale: 'medium',
            rowspan: 2,
            iconAlign: 'top'
        });
        
        this.actions_merge = new Ext.Action({
            text: this.app.i18n._('Merge Invoices'),
            iconCls: 'action_merge',
            scope: this,
            disabled: true,
            allowMultiple: false,
            handler: this.onMergeInvoice,
            actionUpdater: function(action, grants, records) {
                if (records.length == 1 && records[0].get('type') == 'INVOICE' && records[0].get('cleared') != 'CLEARED') {
                    action.enable();
                } else {
                    action.disable();
                }
            }
        });

        var mergeButton = Ext.apply(new Ext.Button(this.actions_merge), {
            scale: 'medium',
            rowspan: 2,
            iconAlign: 'top'
        });
        
        var additionalActions = [this.actions_reversal, this.actions_rebill, this.actions_merge];
        this.actionUpdater.addActions(additionalActions);
        return [reversalButton, rebillButton, mergeButton];
    },
    
    /**
     * is called on reversal invoice action
     * 
     * @param {Ext.Action} action
     * @param {Object} event
     */
    onReverseInvoice: function(action, event) {
        var rows = this.getGrid().getSelectionModel().getSelections();
        if (rows.length == 1) {
            var record = rows[0];
            
            var cfg = {
                record: record,
                createReversal: true,
                windowName: Tine.Sales.InvoiceEditDialog.prototype.windowNamePrefix + record.id + '-reversal'
            };
            
            Tine.Sales.InvoiceEditDialog.openWindow(cfg);
        }
    },
    
    /**
     * 
     */
    onRebillInvoice: function() {
        var rows = this.getGrid().getSelectionModel().getSelections();
        
        if (rows.length != 1) {
            return;
        }
        
        this.billMask.show();
        
        var that = this;
        
        var req = Ext.Ajax.request({
            url : 'index.php',
            params : { 
                method: 'Sales.rebillInvoice', 
                id:     rows[0].id 
            },
            success : function(result, request) {
                that.billMask.hide();
                that.getGrid().store.reload();
            },
            failure : function(exception) {
                that.billMask.hide();
                Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
            },
            scope: that
        });
    },
    
    onMergeInvoice: function(action, event) {
        var rows = this.getGrid().getSelectionModel().getSelections();
        
        if (rows.length != 1) {
            return;
        }
        
        this.billMask.show();
        
        var that = this;
        
        var req = Ext.Ajax.request({
            url : 'index.php',
            params : { 
                method: 'Sales.mergeInvoice', 
                id:     rows[0].id 
            },
            success : function(result, request) {
                that.billMask.hide();
                that.getGrid().store.reload();
            },
            failure : function(exception) {
                that.billMask.hide();
                Tine.Tinebase.ExceptionHandler.handleRequestException(exception);
            },
            scope: that
        });
    },
    
    /**
     * add custom items to context menu
     * 
     * @return {Array}
     */
    getContextMenuItems: function() {
        var items = [
            '-',
            this.actions_reversal,
            this.actions_rebill,
            this.actions_merge
            ];
        
        return items;
    },
    
    /**
     * @private
     */
    initDetailsPanel: function() {
        this.detailsPanel = new Tine.Sales.InvoiceDetailsPanel({
            grid: this,
            app: this.app
        });
    }
});

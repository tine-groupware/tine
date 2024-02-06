/*
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  widgets
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Ext.ux.grid');

/**
 * Paging toolbar with build in selection support
 *
 * @namespace   Ext.ux.grid
 * @class       Ext.ux.grid.PagingToolbar
 * @extends     Ext.PagingToolbar
 * @constructor
 * @param       {Object} config
 */
Ext.ux.grid.PagingToolbar = Ext.extend(Ext.PagingToolbar, {
    /**
     * @cfg {Bool} displayPageInfo 
     * True to display the displayMsg (defaults to false)
     */
    displayPageInfo: false,
    /**
     * @cfg {Bool} displaySelectionHelper
     * True to display the selectionMsg (defaults to false)
     */
    displaySelectionHelper: false,
    /**
     * @cfg {Ext.grid.AbstractSelectionModel}
     */
    sm: null,
    
    /**
     * if this paging toolbar belongs to a nested grid, this must set to true to disable the reload
     * 
     * @type Boolean
     */
    nested: false,
    
    /**
     * @cfg {Bool} disableSelectAllPages
     */
    disableSelectAllPages: false,

    canonicalName: 'PagingToolbar',
    
    /**
     * @private
     */
    initComponent: function() {
        // initialise i18n
        this.selHelperText = {
            'main'         : i18n._('{0} selected'),
            'deselect'     : i18n._('Unselect all'),
            'selectall'    : i18n._('Select all pages ({0} records)'),
            'toggle'       : i18n._('Toggle selection')
        };

        Ext.ux.grid.PagingToolbar.superclass.initComponent.call(this);
        // if the grid is nested in an editdialog, disable refresh
        // TODO: remove this when using memoryproxy
        if (this.nested) {
            this.refresh.disable();
            this.refresh.addClass('x-ux-pagingtb-refresh-disabled');
        }
        if (this.displaySelectionHelper) {
            this.renderSelHelper();
        }
    },
    
    /**
     * @private
     */
    renderSelHelper: function() {
        this.deselectBtn = new Ext.Action({
            iconCls: 'x-ux-pagingtb-deselect',
            text: this.getSelHelperText('deselect'),
            scope: this,
            handler: function() {this.sm.clearSelections();}
        });
        this.selectAllPages = new Ext.Action({
            iconCls: 'x-ux-pagingtb-selectall',
            text: this.getSelHelperText('selectall'),
            scope: this,
            disabled: this.disableSelectAllPages,
            handler: function() {this.sm.selectAll();}
        });
        this.toggleSelectionBtn = new Ext.Action({
            iconCls: 'x-ux-pagingtb-toggle',
            text: this.getSelHelperText('toggle'),
            scope: this,
            handler: function() {this.sm.toggleSelection();}
        });
        
        this.addSeparator();
        this.selHelperBtn = new Ext.Action({
            xtype: 'tbsplit',
            text: this.getSelHelperText('main'),
            iconCls: 'x-ux-pagingtb-main',
            displayPriority: 100,
            menu: new Ext.menu.Menu({
                items: [
                    this.deselectBtn,
                    this.selectAllPages,
                    this.toggleSelectionBtn
                ]
            })
        });
        
        this.add(this.selHelperBtn);
        
        // update buttons when data or selection changes
        this.sm.on('selectionchange', this.updateSelHelper, this);
        this.store.on('load', this.updateSelHelper, this);
    },
    
    /**
     * update all button descr.
     */
    updateSelHelper: function() {
        this.selHelperBtn.setText(this.getSelHelperText('main'));
        this.selectAllPages.setText(this.getSelHelperText('selectall'));
    },

    /**
     * get test for button
     * @param {String} domain 
     * @return {String}
     */
    getSelHelperText: function(domain) {
        var num;
        switch(domain) {
            case 'main':
                num = this.sm.getCount();
                break;
            case 'selectall':
                num = this.store.getTotalCount();
                break;
            default:
                return this.selHelperText[domain];
                break;
        }
        
        return String.format(this.selHelperText[domain], num);
    }    
});

/*
 * Tine 2.0
 * 
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3 
 * @author Alexander Stintzing <alex@stintzing.net>
 * @copyright Copyright (c) 2007-2009
 * Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * TODO use new filter syntax in onBeforeQuery when TagFilter is refactored and
 * extends Tinebase_Model_Filter_FilterGroup
 */

Ext.ns('Tine.widgets', 'Tine.widgets.tags');

/**
 * @namespace Tine.widgets.tags
 * @class Tine.widgets.tags.TagToggleBox
 * @extends Ext.tree.TreePanel
 */
Tine.widgets.tags.TagToggleBox = Ext.extend(Ext.form.FormPanel, {

    layout : 'fit',
    border : false,
    cls : 'tw-editdialog tw-tagtogglebox',
    store : null,
    buttonAlign : 'right',
    win: null,
    mode: null,
    
    anchor : '100% 100%',
    
    loadMask: null,

    initComponent : function() {
        
        this.addEvents('cancel', 'update', 'close');
        this.initStore();
        this.initTemplate();
        this.initView();

        this.initActions();

        this.initButtons();

        this.items = this.getFormItems();

        Tine.widgets.tags.TagToggleBox.superclass.initComponent.call(this);
    },
    
    initActions : function() {
        this.action_update = new Ext.Action({
                    text : (this.mode == 'detach') ? i18n._('Detach Tags') : i18n._('Attach Tags'),
                    minWidth : 70,
                    scope : this,
                    handler : this.onUpdate,
                    iconCls : 'action_saveAndClose'
                });
        this.action_cancel = new Ext.Action({
                    text : i18n._('Cancel'),
                    minWidth : 70,
                    scope : this,
                    handler : this.onCancel,
                    iconCls : 'action_cancel'
                });

    },

    initStore : function() {
            
        this.store = new Ext.data.JsonStore({
                    id : 'id',
                    root : 'results',
                    totalProperty : 'totalCount',
                    fields : Tine.Tinebase.Model.Tag,
                    baseParams : {
                        method : 'Tinebase.searchTagsByForeignFilter',
                        filterData: this.selectionModel.getSelectionFilter(),
                        filterName: this.recordClass.getMeta('appName') + '_Model_' +  this.recordClass.getMeta('modelName') + 'Filter'
                        }
                });
        
        this.store.load();
        
    },

    initButtons : function() {
        this.fbar = ['->', this.action_cancel, this.action_update];
    },
    
    getFormItems : function() {
        return {
            border : false,
            frame : true,
            layout : 'form',
            items : [{
                region : 'center',
                layout : {
                    align : 'stretch',
                    type : 'vbox'
                }
            }, this.view]
        };
    },
    
    initTemplate: function() {
        this.tpl = new Ext.XTemplate(
            '<tpl for=".">',
                '<div class="x-combo-list-item tw-tag-combo-item">',
                    '<input class="tagcheckel" type="checkbox" style="margin-right:5px" id="{values.id}" />',
                    '{[this.label(values)]}',
                '</div>',
            '</tpl>',
            {
                label: function(values) {
                    return Tine.widgets.tags.tagStyle.label(values, { count: values.selection_occurrence ? `(${values.selection_occurrence})` : '' });
                }
            }
        );
    },


    initView : function() {
        this.view = new Ext.DataView({
            anchor : '100% 100%',
            autoScroll : true,
            store : this.store,
            tpl : this.tpl,
            emptyText : i18n._('No Tags to detach found in the selected records'),
            loadingText : i18n._('Please Wait...'),
            style : 'background:#fff;'
        });
    },
    
    onCancel: function() {
        this.fireEvent('cancel');
    },
    // TODO: formitems could not be fetched by formpanel->getform->getvalues
    onUpdate: function() {
        
        this.loadMask = new Ext.LoadMask(this.getEl(), {msg: i18n._('Detaching Tags')});
        this.loadMask.show();
        
        var els = Ext.select('input.tagcheckel');
        var checked = [];
        els.each(function(el){
            if(el.dom.checked) checked.push(el.id);
        });

        var filter = this.selectionModel.getSelectionFilter();
        var filterModel = this.recordClass.getMeta('appName') + '_Model_' +  this.recordClass.getMeta('modelName') + 'Filter';
        
        Tine.Tinebase.detachTagsFromMultipleRecords(filter, filterModel, checked, this.onUpdated.createDelegate(this));

    },
    
    onUpdated: function() {
        this.loadMask.hide();
        this.fireEvent('updated');
    }
    

});

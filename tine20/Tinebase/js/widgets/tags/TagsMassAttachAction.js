/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Tine.widgets', 'Tine.widgets.tags');

/**
 * @namespace   Tine.widgets.tags
 * @class       Tine.widgets.tags.TagsMassAttachAction
 * @extends     Ext.Action
 */
Tine.widgets.tags.TagsMassAttachAction = function(config) {
    config.text = config.text ? config.text : i18n._('Add Tags');
    config.iconCls = 'action_tag';
    config.handler = config.handler ? config.handler : this.handleClick.createDelegate(this);
    config.scope = config.scope ? config.scope : this.handleClick.createDelegate(this);
    Ext.apply(this, config);

    Tine.widgets.tags.TagsMassAttachAction.superclass.constructor.call(this, config);
};

Ext.extend(Tine.widgets.tags.TagsMassAttachAction, Ext.Action, {
    
    /**
     * called when tags got updates
     * 
     * @type Function
     */
    updateHandler: Ext.emptyFn,
    
    /**
     * scope of update handler
     * 
     * @type Object
     */
    updateHandlerScope: null,
    
    loadMask: null,
    
    /**
     * @cfg {mixed} selectionModel
     * 
     * selection model (required)
     */
    selectionModel: null,
    
    /**
     * @cfg {function} recordClass
     * 
     * record class of records to filter for (required)
     */
    recordClass: null,
    
    getFormItems: function() {
        this.gridPanel = new Tine.widgets.grid.PickerGridPanel({
            height: 'auto',
            searchComboConfig: {app: this.app},
            recordClass: Tine.Tinebase.Model.Tag,
            store: new Ext.data.SimpleStore({
                fields: Tine.Tinebase.Model.Tag
            }),
            listeners: {
                change: (grid, value) => {
                    this.manageOkBtn();
                }
            },
            labelRenderer: Tine.Tinebase.common.tagRenderer
        });
        return this.gridPanel;
    },
    
    manageOkBtn: function() {
        if (this.okButton) {
            this.okButton.setDisabled(! this.gridPanel.store.getCount());
        }
    },
    
    handleClick: function() {
        this.win = Tine.WindowFactory.getWindow({
            layout: 'fit',
            width: 300,
            height: 300,
            modal: true,
            closeAction: 'hide', // mhh not working :-(
            title: i18n._('Select Tags'),
            items: [{
                xtype: 'form',
                buttonAlign: 'right',
                border: false,
                layout: 'fit',
                items: this.getFormItems(),
                buttons: [{
                    text: i18n._('Cancel'),
                    minWidth: 70,
                    scope: this,
                    handler: this.onCancel,
                    iconCls: 'action_cancel'
                }, this.okButton = new Ext.Button({
                    text: i18n._('Ok'),
                    disabled: this.gridPanel.store ? !this.gridPanel.store.getCount() : true,
                    minWidth: 70,
                    scope: this,
                    handler: this.onOk,
                    iconCls: 'action_saveAndClose',
                })]
            }]
        });
    },
    
    onCancel: function() {
        this.win.close();
    },
    
    onOk: function() {
        const tags = [];
        this.gridPanel.store.each(function(r) {
            tags.push(r.data);
        }, this);
        
        if (!tags.length) {
            this.win.close();
            return;
        }
        
        this.loadMask = new Ext.LoadMask(this.win.getEl(), {msg: i18n._('Attaching Tag')});
        this.loadMask.show();

        const filter = this.selectionModel.getSelectionFilter();
        const filterModel = this.recordClass.getMeta('appName') + '_Model_' + this.recordClass.getMeta('modelName') + 'Filter';

        // can't use Ext direct because the timeout is not configurable
        //Tine.Tinebase.attachTagToMultipleRecords(filter, filterModel, tag, this.onSuccess.createDelegate(this));
        Ext.Ajax.request({
            scope: this,
            timeout: 1800000, // 30 minutes
            success: this.onSuccess.createDelegate(this),
            params: {
                method: 'Tinebase.attachMultipleTagsToMultipleRecords',
                filterData: filter,
                filterName: filterModel,
                tags: tags
            },
            failure: function(response, options) {
                this.loadMask.hide();
                Tine.Tinebase.ExceptionHandler.handleRequestException(response, options);
            }
        });
    },
    
    onSuccess: function() {
        this.updateHandler.call(this.updateHandlerScope || this);
        this.loadMask.hide();
        this.win.close();
    }
});

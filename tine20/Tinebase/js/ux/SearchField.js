/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
import FieldClearerPlugin from "/ux/form/FieldClearerPlugin";

Ext.ns('Ext.ux');

/**
 * Generic widget for a twin triggerd search field
 * 
 * @namespace   Ext.ux
 * @class       Ext.ux.SearchField
 * @extends     Ext.form.TriggerField
 */
Ext.ux.SearchField = Ext.extend(Ext.form.TriggerField, {
    /**
     * @cfg {String} paramName
     */
    paramName : 'query',
    /**
     * @cfg {Bool} selectOnFocus
     */
    selectOnFocus : true,
    /**
     * @cfg {String} emptyText
     */
    emptyText: '',
    
    validationEvent:false,
    validateOnBlur:false,
    triggerClass:'x-form-search-trigger',
    hideTrigger1:true,
    width:180,
    hasSearch : false,
    /**
     * @private
     */
    initComponent : function(){
        this.clearer = new FieldClearerPlugin({
            onTriggerClick: this.onTrigger1Click.createDelegate(this),
        })
        this.plugins = this.plugins || [];
        this.plugins.unshift(this.clearer);

        this.emptyText = this.emptyText || i18n._('enter search filter');

        Ext.ux.SearchField.superclass.initComponent.call(this);

        this.on('specialkey', function(f, e){
            if (e.getKey() == e.ENTER){
                if (this.getValue() == '') {
                    this.onTrigger1Click();
                } else {
                    this.onTrigger2Click();
                }
            }
        }, this);
    },
    /**
     * @private
     */
    onTrigger1Click : function(){
        this.el.dom.value = '';
        if (this.hasSearch) {
            this.fireEvent('change', this, this.getRawValue(), this.startValue);
            this.startValue = this.getRawValue();
            this.hasSearch = false;
            this.clearer.assertState();
        }
    },
    /**
     * @private
     */
    onTrigger2Click : function(){
        var v = this.getRawValue();
        this.fireEvent('change', this, this.getRawValue(), this.startValue);
        this.startValue = this.getRawValue();
        this.hasSearch = true;
        this.clearer.assertState();
    },

    onTriggerClick : function(){
        this.onTrigger2Click();
    }
});

Ext.reg('ux-searchfield', Ext.ux.SearchField);

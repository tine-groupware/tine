/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Ext.ux');

/**
 * Generic widget for a twintriggerd search field
 * 
 * @namespace   Ext.ux
 * @class       Ext.ux.SearchField
 * @extends     Ext.form.TwinTriggerField
 */
Ext.ux.SearchField = Ext.extend(Ext.form.TwinTriggerField, {
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
    trigger1Class:'x-form-clear-trigger',
    trigger2Class:'x-form-search-trigger',
    hideTrigger1:true,
    width:180,
    hasSearch : false,
    /**
     * @private
     */
    initComponent : function(){
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
        if (this.hasSearch) {
            this.el.dom.value = '';
            this.fireEvent('change', this, this.getRawValue(), this.startValue);
            this.startValue = this.getRawValue();
            this.triggers[0].hide();
            this.hasSearch = false;
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
        this.triggers[0][v.length < 1 ? 'hide' : 'show']();
    }
});

Ext.reg('ux-searchfield', Ext.ux.SearchField);

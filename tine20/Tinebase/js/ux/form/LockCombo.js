/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        switch lock and trigger icons (because only the trigger icon has a round upper right corner)
 */
import FieldTriggerPlugin from "/ux/form/FieldTriggerPlugin";

Ext.ns('Ext.ux', 'Ext.ux.form');

/**
 * Generic widget for a twin trigger combo field
 *
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.LockCombo
 * @extends     Ext.form.ComboBox
 */
Ext.ux.form.LockCombo = Ext.extend(Ext.form.ComboBox, {
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
    emptyText: 'select entry...',
    
    hiddenFieldId: '',
    
    hiddenFieldData: '',
    
    validationEvent:false,
    validateOnBlur:false,
    trigger2ClassLocked:'x-form-locked-trigger',
    trigger2ClassUnlocked:'x-form-unlocked-trigger',
    hideTrigger1:false,
    width:180,
    hasSearch : false,
    /**
     * @private
     */
    initComponent : function(){
        Ext.ux.form.LockCombo.superclass.initComponent.call(this);

        if(!this.hiddenFieldData) {
            this.hiddenFieldData = '1';
        }

        this.plugins = this.plugins || [];
        this.plugins.push(new FieldTriggerPlugin({
            triggerClass: this.hiddenFieldData === '0' ? this.trigger2ClassUnlocked : this.trigger2ClassLocked,
            qtip: i18n._('Lock/Unlock') + ' ' + i18n._('When a preference is locked, a normal user cannot edit the preference anymore.'),
            onTriggerClick: this.onTrigger2Click.createDelegate(this),
        }));
    },

    onRender:function(ct, position) {
        Ext.ux.form.LockCombo.superclass.onRender.call(this, ct, position); // render the Ext.Button
        this.hiddenBox = ct.parent().createChild({tag:'input', type:'hidden', name: this.hiddenFieldId, id: this.hiddenFieldId, value: this.hiddenFieldData });
        Ext.ComponentMgr.register(this.hiddenBox);
    },


    onTrigger1Click: function(){
        if(this.disabled){
            return;
        }
        if(this.isExpanded()){
            this.collapse();
            this.el.focus();
        }else {
            this.onFocus({});
            if(this.triggerAction === 'all') {
                this.doQuery(this.allQuery, true);
            } else {
                this.doQuery(this.getRawValue());
            }
            this.el.focus();
        }
    },
    
    onTrigger2Click : function(p){
        const currentValue = Ext.getCmp(this.hiddenFieldId).getValue();

        Ext.getCmp(this.hiddenFieldId).dom.value = String(Number(!+currentValue));
        p.setTriggerClass(currentValue === '0' ? this.trigger2ClassUnlocked : this.trigger2ClassLocked);
    }    
});
Ext.reg('lockCombo', Ext.ux.form.LockCombo);

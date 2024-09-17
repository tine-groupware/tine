/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
 
Ext.ns('Ext.ux', 'Ext.ux.form');

/**
 * A ComboBox with a secondary trigger button that clears the contents of the ComboBox
 * 
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.ClearableComboBox
 * @extends     Ext.form.ComboBox
 */
Ext.ux.form.ClearableComboBox = Ext.extend(Ext.form.ComboBox, {     
    /**
     * @cfg {bool} disableClearer
     * disables the clearer
     */
    disableClearer: null,
    
    initComponent : function(){
        Ext.ux.form.ClearableComboBox.superclass.initComponent.call(this);
        this.triggerConfig = {
            tag: 'span', cls: 'x-form-twin-triggers',
            cn: [
                {tag: "img", src: Ext.BLANK_IMAGE_URL, cls: "x-form-trigger x-form-clear-trigger"},
                {tag: "img", src: Ext.BLANK_IMAGE_URL, cls: "x-form-trigger " + this.triggerClass}
            ]
        };
    },

    getTrigger: function (index) {
        return this.triggers[index];
    },

    initTrigger: function () {
        var ts = this.trigger.select('.x-form-trigger', true);
        this.wrap.setStyle('overflow', 'hidden');
        var triggerField = this;
        ts.each(function (t, all, index) {
            t.hide = function () {
                this.dom.style.display = 'none';
                triggerField.el?.setWidth(triggerField.wrap.getWidth() - 17);
            };
            t.show = function () {
                this.dom.style.display = '';
                triggerField.el.setWidth(triggerField.wrap.getWidth() - 34);
            };
            var triggerIndex = 'Trigger' + (index + 1);

            if (this['hide' + triggerIndex]) {
                t.dom.style.display = 'none';
            }
            
            t.on("click", this['on' + triggerIndex + 'Click'], this, {preventDefault: true});
            t.addClassOnOver('x-form-trigger-over');
            t.addClassOnClick('x-form-trigger-click');
        }, this);
        
        this.triggers = ts.elements;
        this.triggers[0].hide();
    },

    onRender : function(ct, position){
        Ext.ux.form.ClearableComboBox.superclass.onRender.apply(this, arguments);
        this.wrap.addClass('x-form-field-twin-trigger-wrap');
    },
    
    // clear contents of combobox
    onTrigger1Click: function () {
        if (this.disabled) {
           return;
        }
        this.clearValue();
    },
    
    // pass to original combobox trigger handler
    onTrigger2Click: function () {
        this.onTriggerClick();
    },
    
    /**
     * reset
     */
    reset: function () {
        Ext.ux.form.ClearableComboBox.superclass.reset.apply(this, arguments);
        if (this.triggers && this.disableClearer !== true) {
            this.triggers[0].hide();
        }
    },
    
    /**
     * clear value
     */
    clearValue: function () {
        const value = this.getValue();
        Ext.ux.form.ClearableComboBox.superclass.clearValue.apply(this, arguments);
        if (value) {
            this.fireEvent('select', this, '', this.startValue);
        }
        this.startValue = this.getRawValue();
        if (this.triggers && this.disableClearer !== true) {
            this.triggers[0].hide();
        }
    },
    
    // show clear trigger when item got selected
    onSelect: function (combo, record, index) {
        if (this.triggers && this.disableClearer !== true) {
            this.triggers[0].show();
        }
        Ext.ux.form.ClearableComboBox.superclass.onSelect.apply(this, arguments);
        this.startValue = this.getValue();
    },
    
    /**
     * @see Ext.form.ComboBox
     */
    setValue: function (value) {
        Ext.ux.form.ClearableComboBox.superclass.setValue.call(this, value);
        if (value && this.triggers && this.disableClearer !== true && !this.readOnly) {
            this.triggers[0].show();
        }
    },
    
    /**
     * @see Ext.form.ComboBox
     */
    assertValue : function(){
        
        var val = this.getRawValue(),
            rec;

        if (this.valueField && Ext.isDefined(this.value)){
            rec = this.findRecord(this.valueField, this.value);
        }
        
        if (val && (!rec || rec.get(this.displayField) != val)){
            rec = this.findRecord(this.displayField, val);
        }
        
        if (!rec && this.forceSelection){
            if (val.length > 0 && val != this.emptyText){
                this.el.dom.value = Ext.value(this.lastSelectionText, '');
                this.applyEmptyText();
            } else {
                this.clearValue();
            }
        } else {
            if (rec && this.valueField){
                // onSelect may have already set the value and by doing so
                // set the display field properly.  Let's not wipe out the
                // valueField here by just sending the displayField.
                if (this.value == val){
                    return;
                }
                val = rec.get(this.valueField || this.displayField);
            }
            
            this.selectedRecord = rec;
            
            // @see 0007622: losing non-contact recipient
            this.setRawValue(rec ? rec.get(this.displayField) : val);
        }
    }
});
Ext.reg('extuxclearablecombofield', Ext.ux.form.ClearableComboBox);

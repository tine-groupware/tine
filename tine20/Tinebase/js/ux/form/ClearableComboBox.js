/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
import FieldClearerPlugin from "/ux/form/FieldClearerPlugin";

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
        this.clearer = new FieldClearerPlugin()
        this.plugins = this.plugins || [];
        this.plugins.unshift(this.clearer);


        Ext.ux.form.ClearableComboBox.superclass.initComponent.call(this);
    },

    // clear contents of combobox
    onTrigger1Click: function () {
        if (this.disabled) {
           return;
        }
        this.clearer.onTriggerClick();
    },
    
    // pass to original combobox trigger handler
    onTrigger2Click: function () {
        this.onTriggerClick();
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
        this.clearer.setVisible(false);
    },
    
    // show clear trigger when item got selected
    onSelect: function (combo, record, index) {
        this.clearer.setVisible(this.disableClearer !== true && !this.readOnly);
        Ext.ux.form.ClearableComboBox.superclass.onSelect.apply(this, arguments);
        this.startValue = this.getValue();
    }
});
Ext.reg('extuxclearablecombofield', Ext.ux.form.ClearableComboBox);

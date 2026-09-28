/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
import FieldClearerPlugin from "/ux/form/FieldClearerPlugin";

Ext.ns('Ext.ux', 'Ext.ux.form');

/**
 * A DateField with a secondary trigger button that clears the contents of the DateField
 *
 * @namespace   Ext.ux.form
 * @class       Ext.ux.form.ClearableDateField
 * @extends     Ext.form.DateField
 */
Ext.ux.form.ClearableDateField = Ext.extend(Ext.form.DateField, {
    initComponent : function(){
        this.plugins = this.plugins || [];
        this.plugins.unshift(new FieldClearerPlugin());

        Ext.ux.form.ClearableDateField.superclass.initComponent.call(this);
    }
});
Ext.reg('extuxclearabledatefield', Ext.ux.form.ClearableDateField);

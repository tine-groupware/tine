/*
 * Tine 2.0
 *
 * @package     EventManager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.namespace('Tine.EventManager');

Tine.EventManager.CheckboxOptionEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {

    initComponent: function () {
        Tine.EventManager.CheckboxOptionEditDialog.superclass.initComponent.call(this);
        this.on('beforerender', this.onBeforeRender, this);
    },

    onBeforeRender: function () {
        this.setPlacesListener();
    },

    setPlacesListener: function () {
        this.form.findField('total_places').on('change', function () {
            this.form.findField('available_places').setValue(this.form.findField('total_places').getValue() - this.form.findField('booked_places').getValue());
        },this);
    },
});

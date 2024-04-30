/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.EventManager');

class OptionsPicker extends Tine.widgets.grid.PickerGridPanel {
    initComponent() {
        this.app = Tine.Tinebase.appMgr.get('EventManager');

        this.recordClass = 'EventManager.BookedOption';
        this.fieldLabel = this.app.i18n._('Booked Options');
        this.recordName = this.app.i18n._('Options');
        this.isMetadataModelFor = 'option';
        this.refIdField =  'record';
        this.searchComboConfig = {additionalFilters: [{field: 'eventId', operator: 'equals', value: this.editDialog.record.get('eventId')}]};

        this.hideLabel = true;
        this.isFormField = true;
        this.enableTbar = true;
        this.enableBbar = false;
        this.allowCreateNew = false;
        this.deleteOnServer = true;

        this.columns = ['option'];
        this.autoExpandColumn = 'option';

        this.editDialogConfig = this.editDialogConfig || {};

        super.initComponent();
    }
    setOwnerCt(ct) {
        this.ownerCt = ct;

        if (! this.editDialog) {
            this.editDialog = this.findParentBy(function (c) {
                return c instanceof Tine.widgets.dialog.EditDialog
            });
        }
    }

    onEditDialogRecordUpdate(updatedRecord) {
        super.onEditDialogRecordUpdate(updatedRecord);
        this.editDialog.loadRecord('remote');
    }

    onRowDblClick(grid, row, col) {
        //no edit here!
    }

}
Ext.reg('EventManager.OptionsPicker', OptionsPicker);

Tine.widgets.form.FieldManager.register('EventManager', 'Registration', 'options', {
    xtype: 'EventManager.OptionsPicker',
    height: 132
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);


export default OptionsPicker;

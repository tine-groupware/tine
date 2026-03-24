/*
 * tine Groupware
 *
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 */

Ext.ux.ItemRegistry.registerItem('Addressbook-List-EditDialog-TabPanel',  Ext.extend(Ext.Panel, {
    border: false,
    frame: true,
    requiredGrant: 'editGrant',
    layout: 'fit',

    initComponent: function() {

        this.recordForm = new Tine.widgets.form.RecordForm({
            recordClass: 'MatrixSynapseIntegrator.Room',
            // editDialog: Tine.widgets.dialog.EditDialog.getConstructor('MatrixSynapseIntegrator.Room'),
            editDialog: this,
        });

        this.app = Tine.Tinebase.appMgr.get('MatrixSynapseIntegrator');
        this.title = this.app.getTitle();

        this.supr().initComponent.call(this);
    },

    onRecordLoad: async function(editDialog, record) {
    },

    setReadOnly: function(readOnly) {
        this.readOnly = readOnly;
        // @TODO: set panel to readonly if user has no grants!
    },

    onRecordUpdate: function(editDialog, record) {
    },

    setOwnerCt: function(ct) {
        this.ownerCt = ct;

        if (! this.editDialog) {
            this.editDialog = this.findParentBy(function (c) {
                return c instanceof Tine.widgets.dialog.EditDialog
            });
        }

        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('recordUpdate', this.onRecordUpdate, this);

        // NOTE: in case record is already loaded
        if (! this.setOwnerCt.initialOnRecordLoad) {
            this.setOwnerCt.initialOnRecordLoad = true;
            this.onRecordLoad(this.editDialog, this.editDialog.record);
        }
    }

}), 2);

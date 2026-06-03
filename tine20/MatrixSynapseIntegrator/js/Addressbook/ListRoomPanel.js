/*
 * tine Groupware
 *
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 */
Promise.all([Tine.Tinebase.appMgr.isInitialised('MatrixSynapseIntegrator'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {

    if (!Tine.Tinebase.configManager.get('matrixDomain', 'MatrixSynapseIntegrator')) {
        Tine.log.debug('MatrixSynapseIntegrator: matrixDomain not configured - skipping list edit dialog hook');
        return;
    }

    Ext.ux.ItemRegistry.registerItem('Addressbook-List-EditDialog-TabPanel', Ext.extend(Ext.Panel, {
        border: false,
        frame: true,
        requiredGrant: 'editGrant',
        layout: 'fit',
        hideFields: ['list_id'],
        // TODO remove if not needed
//        isChatRoomActiveCheck: null,

        initComponent: function() {
            this.recordClass = Tine.MatrixSynapseIntegrator.Model.Room;
            this.recordForm = new Tine.widgets.form.RecordForm({
                recordClass: this.recordClass,
                editDialog: this
            });

            this.app = Tine.Tinebase.appMgr.get('MatrixSynapseIntegrator');

            // this.isChatRoomActiveCheck = new Ext.form.Checkbox({
            //     hideLabels: true,
            //     boxLabel: this.app.i18n._('This group has a chat room'),
            //     listeners: {
            //         scope: this,
            //         check: this.onChatRoomActiveCheck
            //     }
            // });

            this.title = this.app.i18n._('Chat Room');
            this.items = [
//                this.isChatRoomActiveCheck,
                this.recordForm
            ];

            this.supr().initComponent.call(this);
        },

        onRecordLoad: function(editDialog, record) {
            this.record = this.recordClass.setFromJson(record.get('room'))
            this.recordForm.getForm().loadRecord(this.record);
        },

        setReadOnly: function(readOnly) {
            // @TODO: use form stuff?
            this.readOnly = readOnly;
            this.recordForm.items.each(function(col) {
                if (col.items) {
                    col.items.each(function(row) {
                        if (row.items) {
                            row.items.each(function(field) {
                                if (Ext.isFunction(field.setReadOnly)) {
                                    field.setReadOnly(readOnly);
                                }
                            });
                        }
                    });
                }
            });
        },

        // onChatRoomActiveCheck: function(cb, checked) {
        //     this.setReadOnly(!checked);
        // },

        onRecordUpdate: function(editDialog, record) {
            this.recordForm.getForm().updateRecord(this.record);
            record.set('room', this.record.getData());
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
})

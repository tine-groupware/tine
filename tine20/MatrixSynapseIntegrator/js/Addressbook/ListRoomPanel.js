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

        initComponent: function() {
            this.recordForm = new Tine.widgets.form.RecordForm({
                recordClass: Tine.MatrixSynapseIntegrator.Model.Room,
                editDialog: this,
                // TODO is this needed?
                // editDialog: Tine.widgets.dialog.EditDialog.getConstructor('MatrixSynapseIntegrator.Room'),
            });

            this.app = Tine.Tinebase.appMgr.get('MatrixSynapseIntegrator');
            this.title = this.app.i18n._('Chat Room');
            this.items = [
                this.recordForm
            ];

            this.supr().initComponent.call(this);
        },

        onRecordLoad: function(editDialog, record) {
            // TODO why can't we do this.recordForm.getForm().setValues(record.get('room')); ?

            const room = record.get('room');
            if (room) {
                this.recordForm.items.each(function(col) {
                    if (col.items) {
                        col.items.each(function(row) {
                            if (row.items) {
                                row.items.each(function(field) {
                                    if (field.name && (room.hasOwnProperty(field.name) || (room.get && room.get(field.name) !== undefined))) {
                                        field.setValue(room.get ? room.get(field.name) : room[field.name], room);
                                    }
                                });
                            }
                        });
                    }
                });
            }
        },

        setReadOnly: function(readOnly) {
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

        onRecordUpdate: function(editDialog, record) {
            // TODO why can't we do record.set('room', this.recordForm.getForm().getValues()) ?

            const roomData = {};
            this.recordForm.items.each(function(col) {
                if (col.items) {
                    col.items.each(function(row) {
                        if (row.items) {
                            row.items.each(function(field) {
                                if (field.name && Ext.isFunction(field.getValue)) {
                                    roomData[field.name] = field.getValue();
                                }
                            });
                        }
                    });
                }
            });

            if (!roomData.name || roomData.name === '') {
                record.set('room', null);
            } else {
                let room = new Tine.MatrixSynapseIntegrator.Model.Room(roomData);
                record.set('room', room);
            }
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

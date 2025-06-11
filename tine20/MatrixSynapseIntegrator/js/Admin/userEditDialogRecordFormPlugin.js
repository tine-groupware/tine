Promise.all([Tine.Tinebase.appMgr.isInitialised('MatrixSynapseIntegrator'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {

    // TODO replace this with matrix account management in a separate tab in user edit dialog
    return;

    if (!Tine.Tinebase.configManager.get('matrixDomain', 'MatrixSynapseIntegrator')) {
        Tine.log.debug('MatrixSynapseIntegrator: matrixDomain not configured - skipping admin user edit dialog hook');
        return;
    }

    // add panel with matrixid field
    const mySubPanel = Ext.extend(Ext.form.FieldSet, {
        layout: 'fit',

        initComponent: function() {
            this.app = Tine.Tinebase.appMgr.get('MatrixSynapseIntegrator');
            this.title = this.app.i18n._('Matrix Integration');
            this.matrixIdField = new Ext.form.TextField({
                fieldLabel: this.app.i18n.gettext('Matrix ID'),
                name: 'matrixId',
            });
            this.matrixActiveField = new Ext.form.Checkbox({
                fieldLabel: this.app.i18n.gettext('Matrix Account Active'),
                name: 'matrixActive',
                listeners: {
                    'check': function(checkbox, value) {
                        if (value) {
                            if (!this.matrixIdField.getValue()) {
                                this.setDefaultMatrixId(this.editDialog.record);
                            }
                            this.matrixIdField.enable();
                        } else {
                            this.matrixIdField.disable();
                        }
                    },
                    scope: this
                }
            });

            this.items = [{
                    xtype: 'columnform',
                    labelAlign: 'top',
                    items: [[this.matrixActiveField, this.matrixIdField]]
                }];

            this.supr().initComponent.apply(this, arguments);
        },

        setDefaultMatrixId: function(record) {
            const matrixDomain = Tine.Tinebase.configManager.get('matrixDomain', 'MatrixSynapseIntegrator');
            const userId = record.phantom ? '@{user.id}' : record.id;
            this.matrixIdField.setValue(`${userId}:${matrixDomain}`);
        },

        onRecordLoad: async function(editDialog, record) {
            if (record.phantom) {
                // new record
                this.setDefaultMatrixId(record);
                this.matrixActiveField.setValue(true);
            } else {
                // get value from xprops
                let xprops = record.get('xprops');
                xprops = Ext.isObject(xprops) ? xprops : {};
                if (xprops.matrixId) {
                    this.matrixIdField.setValue(xprops.matrixId);
                }
                if (xprops.matrixActive) {
                    this.matrixActiveField.setValue(xprops.matrixActive);
                } else {
                    this.matrixIdField.disable();
                }
            }
        },

        onRecordUpdate: function(editDialog, record) {
            let xprops = record.get('xprops');
            xprops = Ext.isObject(xprops) ? xprops : {};
            xprops.matrixId = this.matrixIdField.getValue();
            xprops.matrixActive = this.matrixActiveField.getValue();
        },

        onRender: function() {
            this.supr().onRender.apply(this, arguments);

            if (!this.editDialog) {
                this.editDialog = this.findParentBy(function (c) {
                    return c instanceof Tine.widgets.dialog.EditDialog
                });
            }
            this.editDialog.on('load', this.onRecordLoad, this);
            this.editDialog.on('recordUpdate', this.onRecordUpdate, this);
        }
    })
    Ext.ux.ItemRegistry.registerItem('Admin-UserEditDialog-RecordForm', mySubPanel, 5);
})

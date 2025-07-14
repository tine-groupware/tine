Promise.all([Tine.Tinebase.appMgr.isInitialised('MatrixSynapseIntegrator'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {

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

            this.matrixAccountField = Ext.create({
                xtype: 'tw-recordEditField',
                appName: 'MatrixSynapseIntegrator',
                modelName: 'MatrixAccount',
                fieldName: 'matrix_account',
                fieldLabel: this.app.i18n._('Matrix Account'),
                editDialogConfig: {
                    // mode: 'load(remote):save(local)',
                    mode: 'local',
                    hideFields: [
                        'account_id'
                    ]
                },
                getRecordDefaults: () => {
                    const matrixDomain = Tine.Tinebase.configManager.get('matrixDomain', 'MatrixSynapseIntegrator');
                    const userId = this.editDialog.record.phantom ? '@{user.id}' : this.editDialog.record.id;
                    return {
                        matrix_id: `@${userId}:${matrixDomain}`
                    };
                }
            })

            this.items = [{
                    xtype: 'columnform',
                    labelAlign: 'top',
                    items: [[this.matrixAccountField]]
                }];

            this.supr().initComponent.apply(this, arguments);
        },

        onRecordLoad: async function(editDialog, record) {
            // TODO make this work
            const matrixAccount =
                Tine.Tinebase.data.Record.setFromJson(record.get('matrix_account_id'),
                    Tine.MatrixSynapseIntegrator.Model.MatrixAccount);
            this.matrixAccountField.setValue(matrixAccount);
        },

        onRecordUpdate: function(editDialog, record) {
            record.set('matrix_account_id', this.matrixAccountField.getValue());
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

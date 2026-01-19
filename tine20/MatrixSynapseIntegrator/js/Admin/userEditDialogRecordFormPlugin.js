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
                        matrix_id: `@${userId}:${matrixDomain}`,
                        account_id: userId
                    };
                },
                validateValue : function(value) {
                    return true;
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
            const matrixAccountData = record.get('matrix_account_id');
            if (_.isObject(matrixAccountData)) {
                const matrixAccount = Tine.Tinebase.data.Record.setFromJson(matrixAccountData, 'MatrixSynapseIntegrator.MatrixAccount');
                this.matrixAccountField.setValue(matrixAccount);
            } else {
                this.matrixAccountField.clearValue();
            }
        },

        onRecordUpdate: function(editDialog, record) {
            const currentMatrixAccountData = record.get('matrix_account_id');
            let matrixAccountData = this.matrixAccountField.getValue();
            if (currentMatrixAccountData.account_id) {
                matrixAccountData.account_id = currentMatrixAccountData.account_id;
            }
            record.set('matrix_account_id', matrixAccountData);
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

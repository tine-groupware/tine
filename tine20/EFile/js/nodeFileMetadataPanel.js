import getTierTypes from './tierTypes'

Ext.ux.ItemRegistry.registerItem('Filemanager-Node-EditDialog-TabPanel',  Ext.extend(Ext.Panel, {
    border: false,
    frame: true,
    requiredGrant: 'editGrant',
    
    initComponent: function() {
        this.app = Tine.Tinebase.appMgr.get('EFile');
        this.title = this.app.getTitle();
        this.recordClass = Tine.EFile.Model.FileMetadata;
        
        const metaDataFieldManager = _.bind(Tine.widgets.form.FieldManager.get,
            Tine.widgets.form.FieldManager, 'EFile', 'FileMetadata', _,
            Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);
        
        this.gotoFileButton = new Ext.Button({
            iconCls: 'efile-tiertype-file',
            text: this.app.i18n._('Jump to File'),
            handler: this.onGotoFileClick,
            scope: this,
            hidden: true
        });
        
        const mflds = this.metadataFields = {};
        _.each(Tine.widgets.form.RecordForm.getFieldDefinitions(this.recordClass), (fieldDefinition) => {
            const fieldName = fieldDefinition.fieldName
            const config = {};
            switch (fieldName) {
                case 'commissioned_office':
                    config.allowBlank = true;
                    config.checkState = function() {
                        this.allowBlank = this.inheritable;
                        this.validate();
                    }
                    break;
                case 'paper_file_location':
                    config.checkState = function() {
                        const checked = mflds.is_hybrid.getValue();
                        const value = this.getValue();
                        if (!checked && !this.inheritable && value) {
                            this.setValue('');
                            this.lastValue = value;
                        } else if (checked && !value && this.lastValue) {
                            this.setValue(this.lastValue);
                            this.lastValue = undefined;
                        }
                        this.setDisabled(!checked && !this.inheritable);
                    }
                    break;
                case 'final_decree_date':
                case 'final_decree_by':
                case 'retention_period':
                case 'retention_period_end_date':
                    config.checkState = function() {
                        const checked = mflds.is_closed.getValue();
                        this.setDisabled(!checked);
                        if (checked && fieldName === 'final_decree_date' && !this.getValue()) {
                            this.setValue(new Date().clearTime());
                        }
                        if (fieldName === 'retention_period_end_date') {
                            if (checked && !this.getValue()) {
                                mflds.retention_period_end_date.computeDate();
                            }
                            if (mflds.retention_period.getValue() === 'ETERNALLY') {
                                this.setDisabled(true);
                            }
                        }
                        if (fieldName === 'retention_period') {
                            this.setDisabled(!checked && !this.inheritable);
                        }
                    }
                    if (fieldName === 'retention_period') {
                        _.set(config, 'listeners.change', (field, newValue) => {
                            mflds.retention_period_end_date.computeDate();
                        });
                    }
                    if (fieldName === 'retention_period_end_date') {
                        config.computeDate = function () {
                            const finalDecreeDate = mflds.final_decree_date.getValue();
                            const retentionPeriod = parseInt(mflds.retention_period.getValue(), 10);
                            let date = '';
                            if (_.isDate(finalDecreeDate) && retentionPeriod) {
                                date = finalDecreeDate.add(Date.YEAR, retentionPeriod);
                                if (date.format('m-d') !== '01-01') {
                                    date = Date.parseDate((parseInt(date.format('Y'), 10) + 1)+ '-01-01', 'Y-m-d');
                                }
                            }

                            this.setValue(date);
                        }
                    }
                    break;
                case 'disposal_type':
                case 'disposal_date':
                case 'archive_name':
                    config.checkState = function() {
                        const checked = mflds.is_disposed.getValue();
                        this.setDisabled(!checked);
                        if (checked && fieldName === 'disposal_date' && !this.getValue()) {
                            this.setValue(new Date().clearTime());
                        }
                        if (fieldName === 'archive_name') {
                            const disposalType = mflds.disposal_type.getValue();
                            this[disposalType === 'QUASHED' ? 'hide' : 'show']();
                            if (disposalType === 'QUASHED') {
                                this.setValue('')
                            }
                        }
                        if (fieldName === 'disposal_type') {
                            this.setDisabled(!checked && !this.inheritable);
                        }
                    }
                    break;
            }

            this.metadataFields[fieldName] =  Ext.create(metaDataFieldManager(fieldName, config));
        });

        this.inheritableDescription = new Ext.form.Label({
            text: this.app.i18n._("Metadata entered here is automatically inherited by newly created eFiles.")
        });

        this.items = [{
            xtype: 'columnform',
            defaults: {columnWidth: 0.5},
            items: [
                [this.inheritableDescription],
                [mflds.duration_start, mflds.duration_end],
                [_.assign(mflds.commissioned_office, {columnWidth: 2/3})],
                [_.assign(mflds.is_hybrid, {columnWidth: 2/3})], 
                [_.assign(mflds.paper_file_location, {columnWidth: 2/3})],

                [_.assign(mflds.is_closed, {columnWidth: 2/3})],
                [mflds.final_decree_date, mflds.final_decree_by],
                [mflds.retention_period, mflds.retention_period_end_date],

                [_.assign(mflds.is_disposed, {columnWidth: 2/3})],
                [mflds.disposal_type, mflds.disposal_date],
                [_.assign(mflds.archive_name, {columnWidth: 2/3})],
            ]
        }];

        this.supr().initComponent.call(this);
    },

    onRecordLoad: async function(editDialog, record) {
        const mflds = this.metadataFields;
        const tierTypes = this.tierTypes = _.map(await getTierTypes(), 'tierType');

        const path = record.get('path');
        const basePaths = Array.from(Tine.Tinebase.configManager.get('basePath', 'EFile'));
        const tierType = record.get('efile_tier_type') || (basePaths.indexOf(path) > -1 && path !== '/shared/' ? 'masterPlan' : null);;
        const typeIsFileParent = tierType ? _.indexOf(tierTypes, tierType) < _.indexOf(tierTypes, 'file') : undefined;
        const typeIsFileChild = tierType && _.indexOf(tierTypes, tierType) > _.indexOf(tierTypes, 'file');

        // NOTE: have fast UI alignment (before async request starts)
        this.gotoFileButton[typeIsFileChild ? 'show' : 'hide']();
        this.inheritableDescription[typeIsFileParent ? 'show' : 'hide']();
        this.ownerCt[(tierType /*&& !typeIsFileParent*/ ? 'un' : '') +'hideTabStripItem'](this);

        this.fileData = null;
        if (typeIsFileChild) {
            this.fileData = await Tine.Filemanager.getParentNodeByFilter(record.id, [{
                field: 'efile_tier_type',
                operator: 'equals',
                value: 'file'
            }]);
        } else if (tierType) {
            this.fileData = record.data;
        }

        if (this.fileData) {
            const fileMetadata = _.get(this.fileData, 'efile_file_metadata.data', _.get(this.fileData, 'efile_file_metadata'));
            this.metadataRecord = Tine.Tinebase.data.Record.setFromJson(fileMetadata, this.recordClass);
            
            this.metadataRecord.fields.each((fieldDef) => {
                const field = mflds[fieldDef.name];
                if (field) {
                    if (!fileMetadata || !fileMetadata.hasOwnProperty(fieldDef.name)) {
                        // apply field default
                        _.set(this.metadataRecord, 'data.' + fieldDef.name, field.getValue());
                    } else {
                        // set from given value
                        field.setValue(this.metadataRecord.get(fieldDef.name));
                    }
                }
            });
            
        } else {
            this.metadataRecord = null;
        }
        
        _.each(mflds, (field) => {
            field.inheritable = typeIsFileParent && ['commissioned_office', 'is_hybrid', 'paper_file_location', 'retention_period', 'disposal_type'].indexOf(field.fieldName) >=0;

            field.setReadOnly(tierType !== 'file' && !field.inheritable);
            field[this.metadataRecord ? 'show' : 'hide']();
        });
    },

    setReadOnly: function(readOnly) {
        this.readOnly = readOnly;
        // @TODO: set panel to readonly if user has no grants!
    },

    onRecordUpdate: function(editDialog, record) {
        const mflds = this.metadataFields;
        const path = record.get('path');
        const basePaths = Array.from(Tine.Tinebase.configManager.get('basePath', 'EFile'));
        const tierType = record.get('efile_tier_type') || (basePaths.indexOf(path) > -1 && path !== '/shared/' ? 'masterPlan' : null);
        const typeIsFileParent = tierType ? _.indexOf(this.tierTypes, tierType) < _.indexOf(this.tierTypes, 'file') : undefined;
        
        if ((tierType === 'file' || typeIsFileParent) && this.metadataRecord) {
            _.each(mflds, (field, fieldName) => {
                this.metadataRecord.set(fieldName, field.getValue());
            });
            if (_.keys(this.metadataRecord.getChanges()).length) {
                record.set('efile_file_metadata', this.metadataRecord);
            }
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

        this.editDialog.getToolbar().add(this.gotoFileButton);
        
        // NOTE: in case record is already loaded
        if (! this.setOwnerCt.initialOnRecordLoad) {
            this.setOwnerCt.initialOnRecordLoad = true;
            this.onRecordLoad(this.editDialog, this.editDialog.record);
        }
        
    },

    onGotoFileClick: function() {
        const path = _.get(this.fileData, 'path');
        Tine.Tinebase.appMgr.get('Filemanager').showNode(path);
        this.editDialog.onCancel();
    }
    
}), 5);

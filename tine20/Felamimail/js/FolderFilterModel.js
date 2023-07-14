/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

Ext.ns('Tine.Felamimail');

/**
 * @namespace   Tine.widgets.container
 * @class       Tine.Felamimail.FolderFilterModel
 * @extends     Tine.widgets.grid.FilterModel
 * 
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */
Tine.Felamimail.FolderFilterModel = Ext.extend(Tine.widgets.grid.PickerFilter, {

    /**
     * @cfg 
     */
    operators: ['in', 'notin'],
    field: 'path',
    
    
    /**
     * @private
     */
    initComponent: function() {
        this.label = Tine.Felamimail.Model.Folder.getMeta('modelName');
        
        this.multiselectFieldConfig = {
            labelField: 'path',
            selectionWidget: new Tine.Felamimail.FolderSelectTriggerField({
                allAccounts: true
            }),
            recordClass: Tine.Felamimail.Model.Folder,
            valueStore: this.app.getFolderStore(),
            clicksToEdit: 2,
            minLayerWidth : 400,
            
            /**
             * functions
             */
            labelRenderer: Tine.Felamimail.GridPanel.prototype.accountAndFolderRenderer.createDelegate(this),
            initSelectionWidget: function() {
                this.selectionWidget.onSelectFolder = this.addRecord.createDelegate(this);
            },
            isSelectionVisible: function() {
                return this.selectionWidget.selectPanel && ! this.selectionWidget.selectPanel.isDestroyed        
            },
            /**
             * @return Ext.grid.ColumnModel
             */
            getColumnModel: function() {
                const labelColumn = {
                    id: this.labelField, 
                    header: String.format(i18n._('Selected  {0}'), i18n.n_(this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), 2)),
                    editable: true,
                    editor: new Ext.form.TextField({
                        allowBlank: false,
                        maxLength: 255,
                    }),
                    dataIndex: this.labelField,
                    renderer: Tine.Felamimail.GridPanel.prototype.accountAndFolderRenderer.createDelegate(this)
                };

                return new Ext.grid.ColumnModel({
                    defaults: {
                        sortable: false
                    },
                    columns:  [ labelColumn ]
                });
            },
            getRecordText(value) {
                const path = (Ext.isString(value)) ? value : (value.path) ? value.path : '/' + value.id;
                const index = this.valueStore.findExact('path', path);
                let record = this.valueStore.getAt(index);
                let text = path;
                
                if (! record) {
                    // try account
                    const accountId = path.substring(1, 40);
                    record = this.app.getAccountStore().getById(accountId);
                }
                if (record) {
                    this.currentValue.push(path);
                    // always copy/clone record because it can't exist in 2 different stores
                    this.store.add(record.copy());
                    text = this.labelRenderer(record.id, {}, record);
                } else {
                    if (!record) {
                        const data = {
                            path: path,
                            name: path
                        }
                        record = Tine.Tinebase.data.Record.setFromJson(data, Tine.Felamimail.Model.Folder);
                        this.store.add(record.copy());
                    }
                    this.currentValue.push(text);
                }
                return text;
            },
            
            pickerGridPanelConfigs: {
                clicksToEdit: 2,
                forceValidation: true,
                app: this.app,
                preEditValue : (record, field) => {
                    let value = this.multiselectFieldConfig.labelRenderer(record.id, {}, record);
                    if (!value.startsWith('/')) {
                        value = `/${value}`;
                    }
                    return value;
                },
                onBeforeEdit(o) {
                    const editor = this.colModel.getCellEditor(o.column, o.row);
                    if (!Ext.isFunction(editor.field.validator)) {
                        editor.field.validator = (value) => {
                            const paths = _.compact(value.split('/'));
                            const tips = [];
                            const errMsg = [];
                            let ignoreRest = false;
                            let accountRecords = [];
                            let folders = [];

                            paths.forEach((fieldText, idx) => {
                                const type = idx === 0 ? 'account' : 'folder';
                                const matchAllLevels = fieldText === '**';
                                const matchSameLevel = fieldText === '*';

                                if (ignoreRest) return;
                                if (!fieldText) errMsg.push('no path provided');
                                if (/[.,]$/.test(fieldText)) errMsg.push('path should not end with illegal char');

                                if (matchAllLevels) {
                                    tips.push(`search recursively, should ignore the rests`);
                                    ignoreRest = true;
                                    return;
                                }
                                
                                if (type === 'account') {
                                    if (matchSameLevel) tips.push(`search all accounts`);
                                    if (fieldText !== matchAllLevels && paths.length === 1) {
                                        //errMsg.push('account pattern is not recursive, should provide folderFieldText too!');
                                    }
                                    accountRecords = this.app.getAccountStore().queryBy((account) => {
                                        if (matchSameLevel) return true;
                                        if (account.data.name.includes(fieldText)) {
                                            tips.push(`${type}: ${account.data.name}`);
                                            return true;
                                        }
                                    });

                                    if (accountRecords.length === 0) errMsg.push(String.format(this.app.i18n._('{0} {1} is not found'), this.app.i18n._('Account'), fieldText));
                                    folders[idx] = {'globalname' : '', 'accountId' : ''};
                                }
                                if (type === 'folder') {
                                    const folderRecords = this.app.getFolderStore().queryBy((folder) => {
                                        const folderGlobalName = folder?.data?.globalname.toLowerCase();
                                        const folderLocalName = folder?.data?.localname.toLowerCase();
                                        
                                        const account = accountRecords.items.find((item) => {return item.id === folder.data.account_id});
                                        if (!account) return false;
                                        
                                        const levelDiff = folderGlobalName.split('.').length - fieldText.split('.').length;
                                        const folderName = levelDiff === 0 ? folderGlobalName : folderLocalName;
                                        const isParentMatch = folders[idx-1]['globalname'] === folder.data.parent.toLowerCase();

                                        if (!isParentMatch) return false;
                                        if (matchSameLevel) return true;
                                        if (folderName !== fieldText.toLowerCase()) {
                                            if (folderName.includes(fieldText.toLowerCase())) {
                                                //errMsg.push(`suggestion: ${folderName} from ${account.data.name}`);
                                            }
                                            return false;
                                        }
                                        tips.push(`${type}: ${folderGlobalName} from ${account.data.name}`);
                                        folders[idx] = {'globalname' : folderGlobalName, 'accountId' : account.id};
                                        return true;
                                    });
                                    if (folderRecords.length === 0) errMsg.push(String.format(this.app.i18n._('{0} {1} is not found'), this.app.i18n._('Folder'), fieldText));
                                    
                                    if (matchSameLevel && folders[idx-1]) {
                                        let text = folders[idx-1]['globalname'] === '' ? 'sub folders ' : ` sub folders from folder ${folders[idx-1]['globalname']} `;
                                        accountRecords.items.forEach((account) => {text += ` and from account ${account.data.name}`});
                                        tips.push(text);
                                    }
                                }
                            });
                            editor.field.clearInvalid();
                            //editor.field.el.dom.qtip = 'found filters : <br />' + tips.join('<br />');
                            return errMsg.length > 0 ? errMsg.join('<br />') : true;
                        }
                    }
                },
                // private
                postEditValue : function(value, originalValue, r, field){
                    if (value !== originalValue) {
                        r.id = '';
                        r.set('id', '');
                        r.set('account_id', '');
                        r.set('path', value);
                        r.set('name', value);
                    }
                    return  value;
                },
            }
        };

        Tine.Felamimail.FolderFilterModel.superclass.initComponent.call(this);
    },
});

Tine.widgets.grid.FilterToolbar.FILTERS['tine.felamimail.folder.filtermodel'] = Tine.Felamimail.FolderFilterModel;

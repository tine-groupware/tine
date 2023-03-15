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
            getRecordText: function(value) {
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
                    this.currentValue.push(text);
                }
                
                return text;
            }
        };

        Tine.Felamimail.FolderFilterModel.superclass.initComponent.call(this);
    }
});

Tine.widgets.grid.FilterToolbar.FILTERS['tine.felamimail.folder.filtermodel'] = Tine.Felamimail.FolderFilterModel;

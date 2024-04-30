/*
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * TODO         use Tine.widgets.grid.LinkGridPanel
 */
 
Ext.ns('Tine.Crm.Product');

/**
 * @namespace   Tine.Crm.Product
 * @class       Tine.Crm.Product.GridPanel
 * @extends     Ext.grid.EditorGridPanel
 * 
 * Lead Dialog Products Grid Panel
 * 
 * <p>
 * TODO         allow multiple relations with 1 product or add product quantity?
 * TODO         check if we need edit/add actions again
 * TODO         make resizing work correctly
 * </p>
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Tine.Crm.Product.GridPanel = Ext.extend(Ext.grid.EditorGridPanel, {
    /**
     * grid config
     * @private
     */
    autoExpandColumn: 'name',
    clicksToEdit: 1,
    
    /**
     * The record currently being edited
     * 
     * @type Tine.Crm.Model.Lead
     * @property record
     */
    record: null,
    
    /**
     * store to hold all contacts
     * 
     * @type Ext.data.Store
     * @property store
     */
    store: null,
    
    /**
     * @type Ext.Menu
     * @property contextMenu
     */
    contextMenu: null,

    /**
     * @type Array
     * @property otherActions
     */
    otherActions: null,
    
    /**
     * @type function
     * @property recordEditDialogOpener
     */
    recordEditDialogOpener: null,

    /**
     * record class
     * @cfg {Tine.Sales.Model.Product} recordClass
     */
    recordClass: null,
    
    /**
     * @private
     */
    initComponent: function() {
        // init properties
        this.app = this.app ? this.app : Tine.Tinebase.appMgr.get('Crm');
        this.title = this.app.i18n._('Products');
        this.recordEditDialogOpener = Ext.emptyFn;
        if (Tine.Sales && Tine.Tinebase.common.hasRight('run', 'Sales')) {
            this.recordEditDialogOpener = Tine.Sales.ProductEditDialog.openWindow;
        }
        this.recordClass = Tine.Sales.Model.Product;
        
        this.storeFields = Tine.Sales.Model.ProductArray;
        this.storeFields.push({name: 'relation'});   // the relation object           
        this.storeFields.push({name: 'relation_type'});
        this.storeFields.push({name: 'remark_price'});
        this.storeFields.push({name: 'remark_description'});
        this.storeFields.push({name: 'remark_quantity'});
        
        // create delegates
        this.initStore = Tine.Crm.LinkGridPanel.initStore.createDelegate(this);
        //this.initActions = Tine.Crm.LinkGridPanel.initActions.createDelegate(this);
        this.initGrid = Tine.Crm.LinkGridPanel.initGrid.createDelegate(this);
        //this.onUpdate = Tine.Crm.LinkGridPanel.onUpdate.createDelegate(this);
        this.onUpdate = Ext.emptyFn;

        // call delegates
        this.initStore();
        this.initActions();
        this.initGrid();
        
        // init store stuff
        this.store.setDefaultSort('name', 'asc');
        
        this.on('newentry', function(productData){
            // add new product to store
            var newProduct = [productData];
            this.store.loadData(newProduct, true);
            
            return true;
        }, this);
        
        Tine.Crm.Product.GridPanel.superclass.initComponent.call(this);
    },
    
    /**
     * @return Ext.grid.ColumnModel
     * @private
     */
    getColumnModel: function() {
        return new Ext.grid.ColumnModel({
            defaults: {
                sortable: true
            },
            columns: [
            {
                header: this.app.i18n._("Product"),
                id: 'name',
                dataIndex: 'name',
                renderer: Tine.widgets.grid.RendererManager.get('Sales','Product','name'),
                width: 150
            }, {
                header: this.app.i18n._("Description"),
                id: 'remark_description',
                dataIndex: 'remark_description',
                width: 150,
                editor: new Ext.form.TextField({
                })
            }, {
                header: this.app.i18n._("Price"),
                id: 'remark_price',
                dataIndex: 'remark_price',
                width: 150,
                editor: new Ext.form.NumberField({
                    allowBlank: false,
                    allowNegative: false,
                    // TODO hardcode separator or get it from locale?
                    decimalSeparator: ','
                }),
                renderer: Ext.util.Format.money
            }, {
                header: this.app.i18n._("Quantity"),
                id: 'remark_quantity',
                dataIndex: 'remark_quantity',
                width: 50,
                editor: new Ext.form.NumberField({
                    allowBlank: false,
                    allowNegative: false
                })
            }]
        });
    },
    
    /**
     * init actions and bars
     */
    initActions: function() {
        
        var app = Tine.Tinebase.appMgr.get(this.recordClass.getMeta('appName'));
        if (! app) {
            return;
        }        
        var recordName = app.i18n.n_(
            this.recordClass.getMeta('recordName'), this.recordClass.getMeta('recordsName'), 1
        );

        this.actionUnlink = new Ext.Action({
            requiredGrant: 'editGrant',
            text: String.format(this.app.i18n._('Unlink {0}'), recordName),
            tooltip: String.format(this.app.i18n._('Unlink selected {0}'), recordName),
            disabled: true,
            iconCls: 'action_remove',
            onlySingle: true,
            scope: this,
            handler: function(_button, _event) {
                var selectedRows = this.getSelectionModel().getSelections();
                for (var i = 0; i < selectedRows.length; ++i) {
                    this.store.remove(selectedRows[i]);
                }
            }
        });
        
        // init toolbars and ctx menut / add actions
        this.bbar = [                
            this.actionUnlink
        ];
        
        this.actions = [
            this.actionUnlink
        ];
        
        this.contextMenu = new Ext.menu.Menu({
            items: this.actions,
            plugins: [{
                ptype: 'ux.itemregistry',
                key:   'Tinebase-MainContextMenu'
            }]
        });

        this.ProductPickerCombo = new Tine.Crm.ProductPickerCombo({
            anchor: '90%',
            emptyText: this.app.i18n._('Search for Products to add ...'),
            productsStore: this.store,
            blurOnSelect: true,
            recordClass: Tine.Sales.Model.Product,
            sortBy: 'name',
            sortDir: 'ASC',
            getValue: function() {
                return this.selectedRecord ? this.selectedRecord.data : null;
            },
            onSelect: function(record){
                // check if already in?
                if (! this.productsStore.getById(record.id)) {
                    var newRecord = new Ext.data.Record({
                        salesprice: record.data.salesprice,
                        remark_price: record.data.salesprice,
                        remark_quantity: 1,
                        name: record.data.name,
                        relation_type: 'product',
                        related_id: record.id,
                        id: record.id
                    }, record.id);
                    this.productsStore.insert(0, newRecord);
                }

                this.collapse();
                this.clearValue();
            }
        })

        this.tbar = new Ext.Panel({
            layout: 'fit',
            items: [
                this.ProductPickerCombo
            ]
        });
    }    
});

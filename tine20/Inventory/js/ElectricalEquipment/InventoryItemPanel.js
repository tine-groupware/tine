/*
 * tine Groupware
 *
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 */

import './EditDialog';
import '../ElectricalSafetyTest/EditDialog';
import {get, bind, each, reduce, concat, find, findIndex, wrap} from "lodash";

Promise.all([Tine.Tinebase.appMgr.isInitialised('Inventory'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {

    Ext.ux.ItemRegistry.registerItem('Inventory-InventoryItem-EditDialog-TabPanel', Ext.extend(Ext.ux.form.ColumnFormPanel, {
        border: false,
        frame: true,
        requiredGrant: 'editGrant',

        initComponent: function() {
            this.app = Tine.Tinebase.appMgr.get('Inventory');

            this.iiRecordClass = Tine.Inventory.Model.InventoryItem;
            this.eeRecordClass = Tine.Inventory.Model.ElectricalEquipment;
            this.estRecordClass = Tine.Inventory.Model.ElectricalSafetyTest;

            this.iiFieldManager = bind(Tine.widgets.form.FieldManager.get,
                Tine.widgets.form.FieldManager, this.iiRecordClass.getMeta('appName'), this.iiRecordClass.getMeta('modelName'), _,
                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);
            this.eeFieldManager = bind(Tine.widgets.form.FieldManager.get,
                Tine.widgets.form.FieldManager, this.eeRecordClass.getMeta('appName'), this.eeRecordClass.getMeta('modelName'), _,
                Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);

            this.eeGrid = Ext.create(this.iiFieldManager('electrical_equipments', {
                height: 200,
                columns: ['inventory_id', 'name', 'protection_class', 'next_test_due'],
                getRecordDefaults: bind(this.getEEDefaults, this),
                editDialogConfig: {
                    hideFields: ['inventory_item_id'],
                }
            }));
            this.estGrid = Ext.create(this.eeFieldManager('electrical_safety_tests', {
                allowDelete: false,
                getRecordDefaults: bind(this.getEstDefaults, this),
                height: 200,
                editDialogConfig: {
                    getEEFilter: () => {
                        return [{ field: 'inventory_item_id', operator: 'equals', value: this.editDialog.record.id }]
                    }
                }
            }));
            const eeCol = find(this.estGrid.colModel.config, {id: 'equipment_id'});
            eeCol.renderer = wrap(eeCol.renderer, (func, ...args ) => {
                args[0] = this.eeGrid.store.getById(args[0])?.getData() || args[0];
                return func(...args);
            });

            this.title = this.app.i18n._('Electrical Equipment');
            this.items = [
                [this.eeGrid],
                [this.estGrid],
            ];

            this.supr().initComponent.call(this);
        },

        getEEDefaults: function() {
            const defaults = this.eeGrid.constructor.prototype.getRecordDefaults.call(this.eeGrid);

            defaults.name = this.editDialog.record.get('name');
            if (this.eeGrid.store.getCount() < 1) {
                defaults.inventory_id = this.editDialog.record.get('inventory_id');
            }
            return defaults;
        },

        getEstDefaults: function() {
            const defaults = this.eeGrid.constructor.prototype.getRecordDefaults.call(this.eeGrid);
            defaults.equipment_id = this.eeGrid.store.getCount() === 1 ? this.eeGrid.store.getAt(0).getData() : null;
            return defaults;
        },

        onRecordLoad: function(editDialog, record) {
            const ees = this.editDialog.record.get('electrical_equipments');
            const ests = reduce(ees, (accu, ee) => concat(accu, ee.electrical_safety_tests), []);

            this.estGrid.setValue(ests);
        },

        onRecordUpdate: function(editDialog, record) {
            each(this.estGrid.getValue(), (est) => {
                const ee = this.eeGrid.store.getById(get(est, 'equipment_id.id', est.equipment_id));
                const ests = ee.get('electrical_safety_tests');
                const idx = findIndex(ests, {id: est.id});
                ests[idx < 0 ? ests.length : idx] = est;
            })
            record.set('electrical_equipments', this.eeGrid.getValue());
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

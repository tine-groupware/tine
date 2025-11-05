/*
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
 
import EvaluationDimensionForm from "../../Tinebase/js/widgets/form/EvaluationDimensionForm";

Ext.namespace('Tine.Sales');

Tine.Sales.ProductEditDialog = Ext.extend(Tine.widgets.dialog.EditDialog, {
    windowWidth: 800,
    windowHeight: 600,

    responsiveBreakpointOverrides: [{level: 2, width: 700}],

    getRecordFormItems: function() {
        const fields = this.fields = Tine.widgets.form.RecordForm.getFormFields(this.recordClass, (fieldName, config, fieldDefinition) => {
            switch (fieldName) {
                case 'unfold_type':
                    config.checkState = function() {
                        const disabled = !(fields.subproducts.getValue() || []).length
                        this.setDisabled(disabled);
                        if (disabled) {
                            this.clearValue();
                        } else if (!this.getValue()) {
                            this.setValue('SET');
                        }
                    }

                    break;
            }
        });

        return [{
            region: 'center',
            xtype: 'columnform',
            items: [
                [fields.number, fields.gtin/*, fields.category*/],
                [fields.name, _.assign(fields.shortcut, {columnWidth: 1/3})],
                // [fields.description],
                [fields.manufacturer, _.assign(fields.purchaseprice, {columnWidth: 1/3})],
                [fields.unit, fields.salesprice_type, fields.salesprice, fields.salestaxrate],
                [fields.subproducts],
                [fields.unfold_type, fields.default_sorting, fields.default_grouping],
                [fields.lifespan_start, fields.lifespan_end],
                [fields.is_active, fields.is_salesproduct],
                [_.assign(fields.accountable, {columnWidth: 1/3})],
                [ new EvaluationDimensionForm({
                    maxItemsPerRow: 3,
                    recordClass: this.recordClass
                })]
            ]
        }];
    }
});

// @TODO worth an own file?
Tine.widgets.form.FieldManager.register('Sales', 'Product', 'accountable', {
    xtype: 'combo',
    name: 'accountable',
    allowBlank: false,
    forceSelection: true,
    value: 'Sales_Model_Product',
    displayField: 'modelName',
    valueField: 'key',
    mode: 'local',
    initComponent() {
        var data = [];
        var id = 0;
        this.app = Tine.Tinebase.appMgr.get('Sales');
        this.fieldLabel = this.app.i18n._('Accountable');
        
        Ext.each(Tine.Sales.AccountableRegistry.getArray(), function(rel) {
            const rc = Tine.Tinebase.data.RecordMgr.get(rel.appName, rel.modelName);
            const label = rc.getAppName() + ' ' + rc.getRecordsName();

            data.push([rc.getPhpClassName(), label]);
            id++;
        });
        this.store = new Ext.data.ArrayStore({
            fields: ['key', 'modelName'],
            data: data
        });
        this.supr().initComponent.call(this)
    }
}, Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);

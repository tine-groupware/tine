/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const EvaluationDimensionForm = Ext.extend(Ext.ux.form.ColumnFormPanel, {
    recordClass: null,

    maxItemsPerRow: 5,

    initComponent: function () {
        this.fieldManager = _.bind(Tine.widgets.form.FieldManager.get,
            Tine.widgets.form.FieldManager, this.recordClass.getMeta('appName'), this.recordClass.getMeta('modelName'), _,
            Tine.widgets.form.FieldManager.CATEGORY_EDITDIALOG);

        // @TODO exclude existing fields?
        const dimensionFields = _.sortBy(_.filter(this.recordClass.getModelConfiguration().fields, (field) => {
            return field.type === 'record' && _.get(field, 'config.recordClassName') === 'Tinebase_Model_EvaluationDimensionItem'
        }), (field) => {return _.get(field, 'uiconfig.sorting')});

        this.items = [];
        for (let rowIdx=0; rowIdx < Math.ceil(dimensionFields.length / this.maxItemsPerRow); rowIdx++) {
            let row = [];
            this.items.push(row);
            for (let colIdx=0; colIdx < this.maxItemsPerRow; colIdx++) {
                const field = dimensionFields[rowIdx*this.maxItemsPerRow + colIdx];

                row.push(Object.assign(field ? this.fieldManager(field.fieldName, {columnWidth: 1/this.maxItemsPerRow}) : { xtype: 'label', html: '&nbsp' }, {
                    columnWidth: 1/this.maxItemsPerRow
                }))
            }
        }
        this.supr().initComponent.call(this);
    }
});

Ext.reg('evaluationDimensionForm', EvaluationDimensionForm);

export default EvaluationDimensionForm
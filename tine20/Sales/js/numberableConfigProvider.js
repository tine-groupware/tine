/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

import NumberableField from "widgets/form/NumberableField";

Promise.all([Tine.Tinebase.appMgr.isInitialised('Sales'),
    Tine.Tinebase.ApplicationStarter.isInitialised()]).then(() => {

    NumberableField.registerConfigProvider('Sales', 'Debitor', 'number', (field, record) => {
        const configsAvailable = _.get(record.constructor.getField('number'), 'fieldDefinition.config.configsAvailable', []);
        const additional_key = `Division - ${record.get('division_id')}`;
        return _.find(configsAvailable, { additional_key });
    });

    ['Offer', 'Order', 'Delivery', 'Invoice'].forEach((type) => {
        ['document_number', 'document_proforma_number'].forEach((fieldName) => {
            NumberableField.registerConfigProvider('Sales', `Document_${type}`, fieldName, (field, record) => {
                const editDialog = field.this.findParentBy((c) => {return c instanceof Tine.widgets.dialog.EditDialog});
                const configsAvailable = _.get(record.constructor.getField(fieldName), 'fieldDefinition.config.configsAvailable', []);
                const division = _.get(editDialog.getForm().findField('document_category'), 'selectedRecord.data.division_id', '404');
                const additional_key = `Division - ${_.get(division, 'id', division)}`;
                return _.find(configsAvailable, { additional_key });
            })
        });
    });

});
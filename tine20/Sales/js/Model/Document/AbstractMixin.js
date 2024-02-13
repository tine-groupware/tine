/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const AbstractMixin = {
    statics: {
        getDefaultData() {
            return {
                credit_term: Tine.Tinebase.configManager.get('defaultPaymentTerms', 'Sales')
            }
        }
    }
}

Ext.ns('Tine.Sales.Model');
Tine.Sales.Model.Document_OfferMixin = AbstractMixin
Tine.Sales.Model.Document_OrderMixin = AbstractMixin
Tine.Sales.Model.Document_DeliveryMixin = AbstractMixin
Tine.Sales.Model.Document_InvoiceMixin = AbstractMixin

export default AbstractMixin

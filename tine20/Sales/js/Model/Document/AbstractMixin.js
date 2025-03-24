/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const AbstractMixin = {
    getAttachedDocument: function(type) {
        let latest = _.last(_.sortBy(_.filter(this.get('attached_documents'), {type: type}), 'created_for_seq'))
        if (!latest || latest.created_for_seq < this.get('document_seq')) return
        return _.find(this.get('attachments'), {id: latest.node_id})
    },

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
Tine.Sales.Model.Document_CustomerMixin = AbstractMixin
Tine.Sales.Model.InvoiceMixin = AbstractMixin
Tine.Sales.Model.CustomerMixin = AbstractMixin

export default AbstractMixin

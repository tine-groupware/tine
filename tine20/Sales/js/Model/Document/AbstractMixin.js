/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

const AbstractMixin = {
    getAttachedDocument: function(type) {
        let latest = _.last(_.sortBy(_.filter(this.get('attached_documents'), {type: type}), 'created_for_seq', (d) => d.last_modified_time || d.creation_time))
        if (!latest || latest.created_for_seq < this.get('document_seq')) return
        return _.find(this.get('attachments'), {id: latest.node_id})
    },

    statics: {
        getDefaultData() {
            return {
                credit_term: Tine.Tinebase.configManager.get('defaultPaymentTerms', 'Sales')
            }
        },
        /**
         * returns number rounded to given number of fractionDigits or input if it's not a Number
         *
         * NOTE: the default is our internal precision which might be subject to change
         *
         * @param number
         * @param fractionDigits
         * @returns {*}
         */
        toFixed(number, fractionDigits= 2) {
            if (!_.isNumber(fractionDigits) || !_.isNumber(number)) return number
            return 1 * number.toFixed(fractionDigits)
        }
    }
}

Ext.ns('Tine.Sales.Model');
Tine.Sales.Model.Document_OfferMixin = AbstractMixin
Tine.Sales.Model.Document_OrderMixin = AbstractMixin
Tine.Sales.Model.Document_DeliveryMixin = AbstractMixin
Tine.Sales.Model.Document_PurchaseInvoiceMixin = AbstractMixin
Tine.Sales.Model.Document_InvoiceMixin = AbstractMixin
Tine.Sales.Model.Document_CustomerMixin = AbstractMixin
Tine.Sales.Model.InvoiceMixin = AbstractMixin
Tine.Sales.Model.CustomerMixin = AbstractMixin

export default AbstractMixin

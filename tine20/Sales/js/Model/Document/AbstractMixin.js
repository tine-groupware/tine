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

    isBooked: function() {
        const statusFieldName = `${_.snakeCase(this.constructor.getMeta('modelName').replace(/^Document_/, ''))}_status`
        const statusDef = Tine.Tinebase.widgets.keyfield.getDefinitionFromMC(this.constructor, statusFieldName)
        return _.find(statusDef.records, {id: this.get(statusFieldName) })?.booked
    },

    statics: {
        /**
         * Rounds a number to a specified number of fractional digits using
         * "round half away from zero" semantics (i.e. symmetric rounding).
         *
         * Unlike Math.round(), which always rounds .5 cases toward +Infinity
         * (e.g. Math.round(-1.5) === -1), this function rounds symmetrically
         * for both positive and negative values (round(-1.5) === -2).
         *
         * Also applies a Number.EPSILON correction to mitigate floating-point
         * representation errors (e.g. 1.005 * 100 evaluating to 100.499...
         * instead of 100.5).
         *
         * @param {number} number - The number to round.
         * @param {number} [fractionDigits=2] - Number of digits after the
         *   decimal point to round to.
         * @returns {number} The rounded value.
         *
         * @example
         * round(1.005);        // 1.01
         * round(-1.005);        // -1.01
         * round(1.2345, 3);     // 1.235
         * round(-12.345);       // -12.35
         */
        toFixed(number, fractionDigits= 2) {
            if (!_.isNumber(fractionDigits) || !_.isNumber(number)) return number
            const factor = 10 ** fractionDigits;
            const sign = number < 0 ? -1 : 1;
            return sign * Math.round((Math.abs(number) + Number.EPSILON) * factor) / factor;
        }
    }
}

Ext.ns('Tine.Sales.Model');
Tine.Sales.Model.Document_OfferMixin = AbstractMixin
Tine.Sales.Model.Document_OrderMixin = AbstractMixin
Tine.Sales.Model.Document_DeliveryMixin = AbstractMixin
Tine.Sales.Model.Document_PurchaseInvoiceMixin = AbstractMixin
Tine.Sales.Model.Document_InvoiceMixin = AbstractMixin
Tine.Sales.Model.InvoiceMixin = AbstractMixin

export default AbstractMixin

/*
 * Tine 2.0
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.namespace('Tine.Sales');

Tine.Sales.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    appName: 'Sales',
    activeContentType: 'Product',
    contentTypes: [
        {modelName: 'Product', requiredRight: 'manage_products', singularContainerMode: true},
        {modelName: 'Contract', requiredRight: 'manage_contracts', singularContainerMode: true, genericCtxActions: ['grants']},
        {modelName: 'Customer', requiredRight: 'manage_customers', singularContainerMode: true},
        // {modelName: 'Supplier', requiredRight: 'manage_suppliers', singularContainerMode: true},
        // {modelName: 'PurchaseInvoice', requiredRight: 'manage_purchase_invoices', singularContainerMode: true},

        // deprecated documents
        // TODO add migration to new documents
        // {modelName: 'Invoice', requiredRight: 'manage_invoices', singularContainerMode: true},
        // {modelName: 'OrderConfirmation', requiredRight: 'manage_orderconfirmations', singularContainerMode: true},
        {modelName: 'Offer', requiredRight: 'manage_offers', singularContainerMode: true},

        // new documents
        {modelName: 'Document_Offer', requiredRight: 'manage_offers', singularContainerMode: true},
        {modelName: 'Document_Order', requiredRight: 'manage_orderconfirmations', singularContainerMode: true},
        {modelName: 'Document_Delivery', requiredRight: 'manage_orderconfirmations', singularContainerMode: true},
        {modelName: 'Document_Invoice', requiredRight: 'manage_invoices', singularContainerMode: true},

        // special/advanced lists
        {modelName: 'Debitor', requiredRight: 'manage_customers', singularContainerMode: true, group: 'Lists'},
        {modelName: 'DocumentPosition_Offer', requiredRight: 'manage_offers', singularContainerMode: true, group: 'Lists'},
        {modelName: 'DocumentPosition_Order', requiredRight: 'manage_orderconfirmations', singularContainerMode: true, group: 'Lists'},
        {modelName: 'DocumentPosition_Delivery', requiredRight: 'manage_orderconfirmations', singularContainerMode: true, group: 'Lists'},
        {modelName: 'DocumentPosition_Invoice', requiredRight: 'manage_invoices', singularContainerMode: true, group: 'Lists'},
    ]
});


/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Purchasing');

Tine.Purchasing.MainScreen = Ext.extend(Tine.widgets.MainScreen, {
    appName: 'Sales',
    activeContentType: 'Supplier',
    contentTypes: [
        {appName: 'Sales', modelName: 'Supplier', requiredRight: 'manage_suppliers', singularContainerMode: true},
        {appName: 'Sales', modelName: 'PurchaseInvoice', requiredRight: 'manage_purchase_invoices', singularContainerMode: true},
        {appName: 'Sales', modelName: 'Document_PurchaseInvoice', requiredRight: 'manage_purchase_invoices', singularContainerMode: true},
    ]
});
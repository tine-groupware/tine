/*
 * Tine 2.0
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

import '../styles/Sales.scss';
import './Model/Document/AbstractMixin';
import './Model/DocumentPosition/Offer';
import './Model/DocumentPosition/Order';
import './Model/DocumentPosition/Delivery';
import './Model/DocumentPosition/Invoice';
import './Document/OfferEditDialog';
import './Document/OrderEditDialog';
import './Document/DeliveryEditDialog';
import './Document/InvoiceEditDialog';
import './Document/BookDocumentAction';
import './Document/CopyDocumentAction';
import './Document/CreateFollowUpAction';
import './Document/CreatePaperSlipAction';
import './Document/DispatchDocumentAction';
// import './Document/AttachedDocument/ShowDispatchHistory';
// import './Document/AttachedDocument/ManageAttachmentAction';
import './Document/TrackAction';
import './Document/SendToDatevAction';
import './DocumentPosition/customerColumn';
import './EDocument/QuickLookPanel';
import './numberableStateProvider';
import './Document/PurchaseInvoice/importPurchaseInvoiceAction';
import './Document/PurchaseInvoice/EditDialog';
import './MainScreen'
import './AccountableRegistry'
import './InvoicePositionQuantityRendererRegistry'
import './ContractSearchCombo';
import './OfferSearchCombo';
import './InvoiceSearchCombo';
import './SupplierSearchCombo';
import './ContractProductFilterModel';
import './ContractGridPanel';
import './ContractEditDialog';
import './ProductGridPanel';
import './ProductEditDialog';
import './CustomerEditDialog';
import './SupplierEditDialog';
import './AddressEditDialog';
import './CustomerDetailsPanel';
import './SupplierDetailsPanel';
import './CustomerGridPanel';
import './SupplierGridPanel';
import './ExceptionHandler';
import './CopyAddressDialog';
import './OrderConfirmationEditDialog';
import './ContractFilterModel';
import './CustomerFilterModel';
import './SupplierFilterModel';
import './InvoiceEditDialog';
import './PurchaseInvoiceEditDialog';
import './InvoicePositionGridPanel';
import './InvoicePositionPanel';
import './InvoiceGridPanel';
import './PurchaseInvoiceGridPanel';
import './AddressSearchCombo';
import './OrderConfirmationSearchCombo';
import './InvoiceDetailsPanel';
import './PurchaseInvoiceDetailsPanel';
import './OrderConfirmationFilterModel';
import './BillingDateDialog';
import './OfferEditDialog';
import './PurchaseInvoiceApproverFilterModel';
import './ProductAggregateGridPanel';
    
Ext.namespace('Tine.Sales');

/**
 * opens the Copy Address Dialog and adds the rendered address
 *
 * @param {Tine.Tinebase.data.Record} record
 * @param {String} companyName
 */
Tine.Sales.addToClipboard = function(record, companyName) {
    var app = Tine.Tinebase.appMgr.get('Sales');

    Tine.Sales.CopyAddressDialog.openWindow({
        winTitle: 'Copy address to the clipboard',
        app: app,
        content: Tine.Sales.renderAddress(record, companyName)
    });
};
Tine.Tinebase.appMgr.isInitialised('Sales').then(() => {
    const app = Tine.Tinebase.appMgr.get('Sales');
    Ext.preg('sales.address.to-clipboard', Ext.extend(Ext.ux.grid.ActionColumnPlugin, {
        header: app.i18n._('Clipboard'),
        keepSelection: false,
        actions: [{
            name: 'clipboard',
            iconIndex: 'copy_clipboard',
            iconCls: 'clipboard',
            tooltip: app.i18n._('Copy address to the clipboard'),
            callback: function(rowIndex) {
                var record = this.store.getAt(rowIndex);
                var companyName =this.findParentBy(c => {
                    return c.recordClass?.getPhpClassName() === 'Sales_Model_Customer';
                })?.record.get('name');
                Tine.Sales.addToClipboard(record, companyName);
            }
        }]
    }));
});

// rendered sums registry for the invoiceposition grid panel
Tine.Sales.renderedSumsPerMonth = {};

/** @param {Tine.Tinebase.data.Record} record
 * @param {String} companyName
 *
 * @return {String}
 */
Tine.Sales.renderAddress = function(record, companyName) {
    // this is called either from the edit dialog or from the grid, so we have different record types
    var fieldPrefix = record.data.hasOwnProperty('bic') ? 'adr_' : '';

    companyName = companyName ? companyName : (record.get('name') ? record.get('name') : '');

    var lines = companyName + "\n";

    lines += (record.get((fieldPrefix + 'prefix1')) ? record.get((fieldPrefix + 'prefix1')) + "\n" : '');
    lines += (record.get((fieldPrefix + 'prefix2')) ? record.get((fieldPrefix + 'prefix2')) + "\n" : '');
    lines += (record.get((fieldPrefix + 'pobox')) ? (record.get(fieldPrefix + 'pobox') + "\n") : ((record.get(fieldPrefix + 'street') ? record.get(fieldPrefix + 'street') + "\n" : '')));
    lines += (record.get((fieldPrefix + 'postalcode')) ? (record.get((fieldPrefix + 'postalcode')) + ' ') : '') + (record.get((fieldPrefix + 'locality')) ? record.get((fieldPrefix + 'locality')) : '');

    if (record.get('countryname')) {
        lines += "\n" + Locale.getTranslationList('CountryList')[record.get('countryname')];
    }

    return lines;
};

Tine.Sales.renderSupplier = function(record) {
    const app = Tine.Tinebase.appMgr.get('Sales');
    const fields = [
        'bic',
        'iban',
    ]
    let info = '';
    fields.forEach((fieldName, idx) => {
        const field = record.fields.find((f) => f?.name === fieldName);
        if (idx > 0) info += '\n';
        if (field) info += `${app.i18n._(field.label)}: ${record.get(fieldName)}`;
    })
    return info;
};

Tine.Sales.renderAddressAsLine = function(values) {
    var ret = '';
    var app = Tine.Tinebase.appMgr.get('Sales');
    if (values.customer_id && values.customer_id.hasOwnProperty('name')) {
        ret += '<b>' + Ext.util.Format.htmlEncode(values.customer_id.name) + '</b> - ';
    }

    ret += Ext.util.Format.htmlEncode((values.postbox ? values.postbox : values.street));
    ret += ', ';
    ret += Ext.util.Format.htmlEncode(values.postalcode);
    ret += ' ';
    ret += Ext.util.Format.htmlEncode(values.locality);
    ret += ' (';
    ret += app.i18n._(values.type)

    if (values.type == 'billing') {
        ret += ' - ' + Ext.util.Format.htmlEncode(values.custom1);
    }

    ret += ')';

    return ret;
};

/**
 * register special renderer for invoice address_id
 */
Tine.widgets.grid.RendererManager.register('Sales', 'Invoice', 'address_id', Tine.Sales.renderAddressAsLine);

/**
 * renders the model of the invoice position
 *
 * @param {String} value
 * @param {Object} row
 * @param {Tine.Tinebase.data.Record} rec
 * @return {String}
 */
Tine.Sales.renderInvoicePositionModel = function(value, row, rec) {
    if (! value) {
        return '';
    }
    var split = value.split('_Model_');
    var model = Tine[split[0]].Model[split[1]];

    return '<span class="tine-recordclass-gridicon ' + model.getMeta('appName') + model.getMeta('modelName') + '">&nbsp;</span>' + model.getRecordName() + ' (' + model.getAppName() + ')';
};

/**
 * register special renderer for the invoice position
 */
Tine.widgets.grid.RendererManager.register('Sales', 'InvoicePosition', 'model', Tine.Sales.renderInvoicePositionModel);

/**
 * renders the unit of the invoice position
 *
 * @param {String} value
 * @param {Object} row
 * @param {Tine.Tinebase.data.Record} rec
 * @return {String}
 */
Tine.Sales.renderInvoicePositionUnit = function(value, row, rec) {

    if (! value) {
        return '';
    }

    var model = rec.get('model');
    var split = model.split('_Model_');

    var app = Tine.Tinebase.appMgr.get(split[0]);

    return app.i18n._(value);
};
/**
 * renders the unit of the invoice position
 * @param {} value
 * @param {} row
 * @param {} rec
 * @return {}
 */
Tine.Sales.renderInvoicePositionQuantity = function(value, row, rec) {
    var model = rec.data.model;
    if (Tine.Sales.InvoicePositionQuantityRendererRegistry.has(model, rec.data.unit)) {
        var renderer = Tine.Sales.InvoicePositionQuantityRendererRegistry.get(model, rec.data.unit);
        return renderer(value, row, rec);
    } else {
        return value;
    }
};

/**
 * register special renderer for the invoice position
 */
Tine.widgets.grid.RendererManager.register('Sales', 'InvoicePosition', 'unit', Tine.Sales.renderInvoicePositionUnit);
Tine.widgets.grid.RendererManager.register('Sales', 'InvoicePosition', 'quantity', Tine.Sales.renderInvoicePositionQuantity);


Tine.Sales.renderBillingPoint = function(v) {
    var app = Tine.Tinebase.appMgr.get('Sales');
    return v ? app.i18n._hidden(v) : '';
}

Tine.widgets.grid.RendererManager.register('Sales', 'Contract', 'billing_point', Tine.Sales.renderBillingPoint);

Tine.Sales.renderAccountable = function(values) {
    if (Ext.isEmpty(values)) {
        return '';
    }
    var split = values.split('_Model_');
    var ret = '';
    var app = Tine.Tinebase.appMgr.get(split[0]);

    return app ? app.i18n._(split[0] + split[1]) : null;
};

/**
 * register special renderer for invoice address_id
 */
Tine.widgets.grid.RendererManager.register('Sales', 'Product', 'accountable', Tine.Sales.renderAccountable);

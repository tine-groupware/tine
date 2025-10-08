<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Sales_Model_DocumentPosition_PurchaseInvoice as PIPosition;
use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * Purchase Invoice Document Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_PurchaseInvoice extends Sales_Model_Document_Abstract
{
    public const MODEL_NAME_PART = 'Document_PurchaseInvoice';
    public const TABLE_NAME = 'sales_document_purchase_invoice';

    public const STATUS_APPROVAL_REQUESTED = 'approvalRequested';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const FLD_PURCHASE_INVOICE_STATUS = 'purchase_invoice_status';
    public const FLD_SUPPLIER_ID = 'supplier_id';
    public const FLD_DIVISION_ID = 'division_id';

    public const FLD_DUE_AT = 'due_at';
    public const FLD_OVER_DUE_AT = 'over_due_at';
    public const FLD_PAY_AT = 'pay_at';
    public const FLD_PAID_AT = 'paid_at';
    public const FLD_DUNNINGS = 'dunnings'; // recordset mahnungen ?! TODO FIXME
    public const FLD_APPROVER = 'approver';
    public const FLD_DOCUMENT_CURRENCY = 'document_currency';

    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::CREATE_MODULE] = true;
        $_definition[self::RECORD_NAME] = 'Purchase Invoice'; // gettext('GENDER_Purchase Invoice')
        $_definition[self::RECORDS_NAME] = 'Purchase Invoices'; // ngettext('Purchase Invoice', 'Purchase Invoices', n)

        $_definition[self::VERSION] = 1;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::TABLE] = [
            self::NAME => self::TABLE_NAME,
        ];

        $_definition[self::FIELDS][self::FLD_DOCUMENT_NUMBER][self::NULLABLE] = true;
        $_definition[self::FIELDS][self::FLD_DOCUMENT_NUMBER][self::CONFIG][Tinebase_Numberable::BUCKETKEY] = self::class . '#' . self::FLD_DOCUMENT_NUMBER;

        $_definition[self::FIELDS][self::FLD_SUPPLIER_ID] = [
            self::LABEL             => 'Supplier', // _('Supplier')
            self::TYPE              => self::TYPE_RECORD,
            self::NULLABLE          => true,
            self::CONFIG            => [
                self::APP_NAME          => Sales_Config::APP_NAME,
                self::MODEL_NAME        => 'Supplier',
            ],
        ];
        $_definition[self::JSON_EXPANDER][Tinebase_Record_Expander::EXPANDER_PROPERTIES][self::FLD_SUPPLIER_ID] = [];

        $_definition[self::FIELDS][self::FLD_DUE_AT] = [
            self::LABEL             => 'Due at', // _('Due at')
            self::TYPE              => self::TYPE_DATE,
            self::NULLABLE          => true,
        ];
        $_definition[self::FIELDS][self::FLD_OVER_DUE_AT] = [
            self::LABEL             => 'Over due at', // _('Over due at')
            self::TYPE              => self::TYPE_DATE,
            self::NULLABLE          => true,
        ];
        $_definition[self::FIELDS][self::FLD_PAY_AT] = [
            self::LABEL             => 'Pay at', // _('Pay at')
            self::TYPE              => self::TYPE_DATE,
            self::NULLABLE          => true,
        ];
        $_definition[self::FIELDS][self::FLD_PAID_AT] = [
            self::LABEL             => 'Paid at', // _('Paid at')
            self::TYPE              => self::TYPE_DATE,
            self::NULLABLE          => true,
        ];
        //public const FLD_DUNNINGS = 'dunnings';  recordset mahnungen ?! TODO FIXME
        $_definition[self::FIELDS][self::FLD_APPROVER] = [
            self::LABEL             => 'Approver', // _('Approver')
            self::TYPE              => self::TYPE_RECORD,
            self::NULLABLE          => true,
            self::CONFIG            => [
                self::APP_NAME          => Addressbook_Config::APP_NAME,
                self::MODEL_NAME        => Addressbook_Model_Contact::MODEL_NAME_PART,
            ],
        ];

        unset($_definition[self::FILTER_MODEL][self::FLD_DIVISION_ID]);
        $_definition[self::DELEGATED_ACL_FIELD] = self::FLD_DIVISION_ID;
        $_definition[self::FIELDS][self::FLD_DIVISION_ID] = [
            self::LABEL             => 'Division', // _('Division')
            self::TYPE              => self::TYPE_RECORD,
            self::CONFIG            => [
                self::APP_NAME          => Sales_Config::APP_NAME,
                self::MODEL_NAME        => Sales_Model_Division::MODEL_NAME_PART,
            ],
        ];
        $_definition[self::JSON_EXPANDER][Tinebase_Record_Expander::EXPANDER_PROPERTIES][self::FLD_DIVISION_ID] = [];

        // invoice positions
        $_definition[self::FIELDS][self::FLD_POSITIONS][self::CONFIG][self::MODEL_NAME] =
            Sales_Model_DocumentPosition_PurchaseInvoice::MODEL_NAME_PART;

        // invoice status
        Tinebase_Helper::arrayInsertAfterKey($_definition[self::FIELDS], self::FLD_DOCUMENT_NUMBER, [
            self::FLD_PURCHASE_INVOICE_STATUS => [
                self::LABEL => 'Status', // _('Status')
                self::TYPE => self::TYPE_KEY_FIELD,
                self::NAME => Sales_Config::DOCUMENT_PURCHASE_INVOICE_STATUS,
                self::LENGTH => 255,
                self::NULLABLE => true,
            ]
        ]);

        $_definition[self::FIELDS][self::FLD_DOCUMENT_CURRENCY] = [
            self::LABEL             => 'Dcoument Currency', // _('Document Currency')
            self::TYPE              => self::TYPE_STRING,
            self::NULLABLE          => true,
        ];

        $_definition[self::FIELDS][self::FLD_PAYMENT_MEANS][self::CONFIG][self::MODEL_NAME] = Sales_Model_PurchasePaymentMeans::MODEL_NAME_PART;

        unset($_definition[self::FIELDS][self::FLD_DOCUMENT_LANGUAGE]);
        unset($_definition[self::FIELDS][self::FLD_DOCUMENT_CATEGORY]);
        unset($_definition[self::JSON_EXPANDER][Tinebase_Record_Expander::EXPANDER_PROPERTIES][self::FLD_DOCUMENT_CATEGORY]);
        unset($_definition[self::FIELDS][self::FLD_BOILERPLATES]);
        unset($_definition[self::JSON_EXPANDER][Tinebase_Record_Expander::EXPANDER_PROPERTIES][self::FLD_BOILERPLATES]);
        unset($_definition[self::FIELDS][self::FLD_CUSTOMER_ID]);
        unset($_definition[self::JSON_EXPANDER][Tinebase_Record_Expander::EXPANDER_PROPERTIES][self::FLD_CUSTOMER_ID]);
        unset($_definition[self::FIELDS][self::FLD_DEBITOR_ID]);
        unset($_definition[self::JSON_EXPANDER][Tinebase_Record_Expander::EXPANDER_PROPERTIES][self::FLD_DEBITOR_ID]);
        unset($_definition[self::FIELDS][self::FLD_RECIPIENT_ID]);
        unset($_definition[self::JSON_EXPANDER][Tinebase_Record_Expander::EXPANDER_PROPERTIES][self::FLD_RECIPIENT_ID]);

        // self::CONTACT_ID -> old model: relation, migrate

        unset($_definition[self::FIELDS][self::FLD_CONTRACT_ID]);

        // FLD_ATTACHED_DOCUMENTS
        // Zugferd -> paperslip
        // orginal document => edocument (auch für zugferd abspeichern)
        // validation document -> validation result

        /*unset($_definition[self::FIELDS][self::FLD_DISPATCH_HISTORY]);
        unset($_definition[self::JSON_EXPANDER][Tinebase_Record_Expander::EXPANDER_PROPERTIES][self::FLD_DISPATCH_HISTORY]);
        unset($_definition[self::FIELDS][self::FLD_DOCUMENT_SEQ]);
        dispatch to datev?
        */
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    protected static string $_statusField = self::FLD_PURCHASE_INVOICE_STATUS;
    protected static string $_statusConfigKey = Sales_Config::DOCUMENT_PURCHASE_INVOICE_STATUS;
    protected static string $_documentNumberPrefix = 'PI-'; // _('PI-')

    public function transitionFrom(Sales_Model_Document_Transition $transition)
    {
        throw new Tinebase_Exception_NotImplemented('PurchaseInvoices can only be imported, no other document can transition to a PurchaseInvoice');
    }

    public function calculatePricesIncludingPositions()
    {
        // nothing to do here
    }

    public function calculatePrices()
    {
        // nothing to do here
    }

    public static function fromXR(string $xml): static
    {
        $pInvoice = new static([
            self::FLD_DIVISION_ID => Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION},
            self::FLD_POSITIONS => new Tinebase_Record_RecordSet(Sales_Model_DocumentPosition_PurchaseInvoice::class),
        ], true);

        $xr = new SimpleXMLElement($xml, namespaceOrPrefix: 'urn:ce.eu:en16931:2017:xoev-de:kosit:standard:xrechnung-1');

        if ('' !== (string)$xr->Invoice_number) { // 1
            $pInvoice->{self::FLD_BUYER_REFERENCE} = (string)$xr->Invoice_number;
        }
        if ('' !== (string)$xr->Invoice_issue_date) { // 1
            $pInvoice->{self::FLD_DOCUMENT_DATE} = new Tinebase_DateTime((string)$xr->Invoice_issue_date);
        }
        //if ('' !== (string)$xr->Invoice_type_code) {} // 1
        if ('' !== (string)$xr->Invoice_currency_code) { // 1
            $pInvoice->{self::FLD_DOCUMENT_CURRENCY} = (string)$xr->Invoice_currency_code;
        }
        //if ('' !== (string)$xr->VAT_accounting_currency_code) {} // 0..1
        //if ('' !== (string)$xr->Value_added_tax_point_date) {} // 0..1
        //if ('' !== (string)$xr->Value_added_tax_point_date_code) {} // 0..1
        if ('' !== (string)$xr->Payment_due_date) {  // 0..1
            $pInvoice->{self::FLD_DUE_AT} = new Tinebase_DateTime((string)$xr->Payment_due_date);
        }
        if ('' !== (string)$xr->Buyer_reference) { // 1
            $pInvoice->{self::FLD_BUYER_REFERENCE} = (string)$xr->Buyer_reference;
        }
        //if ('' !== (string)$xr->Project_reference) {} // 0..1
        //if ('' !== (string)$xr->Contract_reference) {} // 0..1
        //if ('' !== (string)$xr->Purchase_order_reference) {} // 0..1
        //if ('' !== (string)$xr->Sales_order_reference) {} // 0..1
        //if ('' !== (string)$xr->Receiving_advice_reference) {} // 0..1
        //if ('' !== (string)$xr->Despatch_advice_reference) {} // 0..1
        //if ('' !== (string)$xr->Tender_or_lot_reference) {} // 0..1
        //if ('' !== (string)$xr->Invoiced_object_identifier) {} // 0..1
        //if ('' !== (string)$xr->Buyer_accounting_reference) {} // 0..1
        if ('' !== (string)$xr->Payment_terms) { // 0..1
            //$pInvoice->{self::FLD_PAYMENT_TERMS} = (string)$xr->Payment_terms; // TODO FIXME!!!
        }

        // 0..* INVOICE_NOTE
        /*
        foreach ($xr->INVOICE_NOTE as $note) {
            if ('' !== ($noteTxt = (string)$note->Invoice_note_subject_code)) { // 0..1
                $noteTxt .= PHP_EOL;
            }
            $noteTxt .= (string)$note->Invoice_note; // 1
            if (!$pInvoice->notes) {
                $pInvoice->notes = new Tinebase_Record_RecordSet(Tinebase_Model_Note::class);
            }
            $pInvoice->notes->addRecord(new Tinebase_Model_Note([
                Tinebase_Model_Note::FLD_NOTE => $noteTxt,
            ], true));
        }*/

        // 1 PROCESS_CONTROL
        // $xr->PROCESS_CONTROL->Business_process_type_identifier 1 e.g. urn:fdc:peppol.eu:2017:poacc:billing:01:1.0
        // $xr->PROCESS_CONTROL->Specification_identifier 1 e.g. urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0

        // 0..* PRECEDING_INVOICE_REFERENCE
        /*foreach ($xr->PRECEDING_INVOICE_REFERENCE as $precedingDocument) {
            $precedingDocument->Preceding_Invoice_reference // 1
            $precedingDocument->Preceding_Invoice_issue_date // 0..1
        }*/

        // 1 SELLER
        $seller = $xr->SELLER;
        if ('' !== (string)$seller->Seller_name) { // 1
            $supplier = Sales_Controller_Supplier::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Supplier::class, [
                [TMFA::FIELD => 'name', TMFA::OPERATOR => 'contains', TMFA::VALUE => (string)$seller->Seller_name],
            ]))->getFirstRecord();
            if (null !== $seller) {
                $pInvoice->{self::FLD_SUPPLIER_ID} = $supplier;
            }
        }
        $seller->Seller_trading_name; // 0..1
        foreach ($seller->Seller_identifier as $tmp) {}; // 0..*
        $seller->Seller_legal_registration_identifier; // 0..*
        $seller->Seller_VAT_identifier; // 0..1
        $seller->Seller_tax_registration_identifier; // 0..1
        $seller->Seller_additional_legal_information; // 0..1
        $seller->Seller_electronic_address; // 1
        
        // 1 SELLER_POSTAL_ADDRESS
        $sellerPostalAdr = $seller->SELLER_POSTAL_ADDRESS;
        $sellerPostalAdr->Seller_address_line_1; // 0..1
        $sellerPostalAdr->Seller_address_line_2; // 0..1
        $sellerPostalAdr->Seller_address_line_3; // 0..1
        $sellerPostalAdr->Seller_city; // 1
        $sellerPostalAdr->Seller_post_code; // 1
        $sellerPostalAdr->Seller_country_subdivision; // 0..1
        $sellerPostalAdr->Seller_country_code; // 1
        
        // 1 SELLER_CONTACT
        $sellerContact = $seller->SELLER_CONTACT;
        $sellerContact->Seller_contact_point; // 0..1
        $sellerContact->Seller_contact_telephone_number; // 0..1
        $sellerContact->Seller_contact_email_address; // 0..1

        // 1 BUYER
        $buyer = $xr->BUYER;
        $buyer->Buyer_name; // 1
        $buyer->Buyer_trading_name; // 0..1
        $buyer->Buyer_identifier; // 0..1
        $buyer->Buyer_legal_registration_identifier; // 0..1
        $buyer->Buyer_VAT_identifier; // 0..1
        $buyer->Buyer_electronic_address; // 1

        // 1 BUYER POSTAL ADDRESS
        $buyerPostalAdr = $buyer->BUYER_POSTAL_ADDRESS;
        $buyerPostalAdr->Buyer_address_line_1; // 0..1
        $buyerPostalAdr->Buyer_address_line_2; // 0..1
        $buyerPostalAdr->Buyer_address_line_3; // 0..1
        $buyerPostalAdr->Buyer_city; // 1
        $buyerPostalAdr->Buyer_post_code; // 1
        $buyerPostalAdr->Buyer_country_subdivision; // 0..1
        $buyerPostalAdr->Buyer_country_code; // 1

        // 0..1 BUYER CONTACT
        $buyerContact = $buyer->BUYER_CONTACT;
        $buyerContact->Buyer_contact_point; // 0..1
        $buyerContact->Buyer_contact_telephone_number; // 0..1
        $buyerContact->Buyer_contact_email_address; // 0..1

        // 0..1 PAYEE
        $payee = $xr->PAYEE;
        $payee->Payee_name; // 1
        $payee->Payee_identifier; // 0..1
        $payee->Payee_legal_registration_identifier; // 0..1

        // 0..1 SELLER_TAX_REPRESENTATIVE_PARTY
        $sellerTaxRep = $xr->SELLER_TAX_REPRESENTATIVE_PARTY;
        $sellerTaxRep->Seller_tax_representative_name; // 1
        $sellerTaxRep->Seller_tax_representative_VAT_identifier; // 1
        $sellerTaxRepAdr = $sellerTaxRep->SELLER_TAX_REPRESENTATIVE_POSTAL_ADDRESS; // 1
        $sellerTaxRepAdr?->Tax_representative_address_line_1; // 0..1
        $sellerTaxRepAdr?->Tax_representative_address_line_2; // 0..1
        $sellerTaxRepAdr?->Tax_representative_address_line_3; // 0..1
        $sellerTaxRepAdr?->Tax_representative_city; // 0..1
        $sellerTaxRepAdr?->Tax_representative_post_code; // 0..1
        $sellerTaxRepAdr?->Tax_representative_country_subdivision; // 0..1
        $sellerTaxRepAdr?->Tax_representative_country_code; // 1

        // 0..1 DELIVERY_INFORMATION
        $deliveryInfo = $xr->DELIVERY_INFORMATION;
        $deliveryInfo->Deliver_to_party_name; // 0..1
        $deliveryInfo->Deliver_to_location_identifier; // 0..1
        $deliveryInfo->Actual_delivery_date; // 0..1
        $deliveryToAdr = $deliveryInfo->DELIVER_TO_ADDRESS; // 0..1
        $deliveryToAdr?->Deliver_to_address_line_1; // 0..1
        $deliveryToAdr?->Deliver_to_address_line_2; // 0..1
        $deliveryToAdr?->Deliver_to_address_line_3; // 0..1
        $deliveryToAdr?->Deliver_to_city; // 1
        $deliveryToAdr?->Deliver_to_post_code; // 1
        $deliveryToAdr?->Deliver_to_country_subdivision; // 0..1
        $deliveryToAdr?->Deliver_to_country_code; // 1

        // 0..1 INVOICING_PERIOD
        $invoicingPeriod = $xr->INVOICING_PERIOD;
        if ('' !== (string)$invoicingPeriod->Invoicing_period_start_date) {  // 0..1
            $pInvoice->{self::FLD_SERVICE_PERIOD_START} = new Tinebase_DateTime((string)$invoicingPeriod->Invoicing_period_start_date);
        }
        if ('' !== (string)$invoicingPeriod->Invoicing_period_end_date) {  // 0..1
            $pInvoice->{self::FLD_SERVICE_PERIOD_END} = new Tinebase_DateTime((string)$invoicingPeriod->Invoicing_period_end_date);
        }

        $pInvoice->{self::FLD_PAYMENT_MEANS} = Sales_Model_PurchasePaymentMeans::fromXrXML($xr->PAYMENT_INSTRUCTIONS);
        // 0..1 PAYMENT_INSTRUCTIONS
        $paymentInstructions = $xr->PAYMENT_INSTRUCTIONS;
        $paymentInstructions->Payment_means_type_code; // 1 SaMoED_PMC TODO FIXME!!!
        $paymentInstructions->Payment_means_text; // 0..1
        $paymentInstructions->Remittance_information; // 0..1
        // 0..* CREDIT_TRANSFER
        foreach ($paymentInstructions->CREDIT_TRANSFER as $creditTransfer) {
            $creditTransfer->Payment_account_identifier; // 1
            $creditTransfer->Payment_account_name; // 0..1
            $creditTransfer->Payment_service_provider_identifier; // 0..1

        }
        // 0..* PAYMENT_CARD_INFORMATION
        foreach ($paymentInstructions->PAYMENT_CARD_INFORMATION as $paymentCardInfo) {
            $paymentCardInfo->Payment_card_primary_account_number; // 1
            $paymentCardInfo->Payment_card_holder_name; // 0..1

        }
        // 0..* DIRECT_DEBIT
        foreach ($paymentInstructions->DIRECT_DEBIT as $directDebit) {
            $directDebit->Bank_assigned_creditor_identifier; // 0..1
            $directDebit->Debited_account_identifier; // 0..1
        }


        // 0..* DOCUMENT_LEVEL_ALLOWANCES
        foreach ($xr->DOCUMENT_LEVEL_ALLOWANCES as $documentLevelAllowance) {
            $documentLevelAllowance->Document_level_allowance_amount; // 1
            $documentLevelAllowance->Document_level_allowance_base_amount; // 0..1
            $documentLevelAllowance->Document_level_allowance_percentage; // 0..1
            $documentLevelAllowance->Document_level_allowance_VAT_category_code; // 0..1
            $documentLevelAllowance->Document_level_allowance_VAT_rate; // 0..1
            $documentLevelAllowance->Document_level_allowance_reason; // 0..1
            $documentLevelAllowance->Document_level_allowance_reason_code; // 0..1
        }

        // 0..* DOCUMENT_LEVEL_CHARGES
        foreach ($xr->DOCUMENT_LEVEL_CHARGES as $documentLevelCharge) {
            $documentLevelCharge->Document_level_charge_amount; // 1
            $documentLevelCharge->Document_level_charge_base_amount; // 0..1
            $documentLevelCharge->Document_level_charge_percentage; // 0..1
            $documentLevelCharge->Document_level_charge_VAT_category_code; // 0..1
            $documentLevelCharge->Document_level_charge_VAT_rate; // 0..1
            $documentLevelCharge->Document_level_charge_reason; // 0..1
            $documentLevelCharge->Document_level_charge_reason_code; // 0..1
        }

        // 1 DOCUMENT_TOTALS
        $documentTotals = $xr->DOCUMENT_TOTALS;
        $pInvoice->{self::FLD_POSITIONS_NET_SUM} = floatval((string)$documentTotals->Sum_of_Invoice_line_net_amount); // 1
        if ('' !== (string)$documentTotals->Sum_of_allowances_on_document_level || '' !== (string)$documentTotals->Sum_of_charges_on_document_level) { // both 0..1
            $discount = floatval((string)$documentTotals->Sum_of_allowances_on_document_level) -
                floatval((string)$documentTotals->Sum_of_charges_on_document_level);
            $pInvoice->{self::FLD_INVOICE_DISCOUNT_SUM} = $discount;
            $pInvoice->{self::FLD_INVOICE_DISCOUNT_TYPE} = Sales_Config::INVOICE_DISCOUNT_SUM;
        }
        $pInvoice->{self::FLD_NET_SUM} = floatval((string)$documentTotals->Invoice_total_amount_without_VAT); // 1
        $pInvoice->{self::FLD_SALES_TAX} = floatval((string)$documentTotals->Invoice_total_VAT_amount); // 0..1
        $documentTotals->Invoice_total_VAT_amount_in_accounting_currency; // 0..1
        $pInvoice->{self::FLD_GROSS_SUM} = floatval((string)$documentTotals->Invoice_total_amount_with_VAT); // 1
        $documentTotals->Paid_amount; // 0..1 TODO FIXME
        $documentTotals->Rounding_amount; // 0..1
        $documentTotals->Amount_due_for_payment; // 0..1 TODO FIXME

        if (!$pInvoice->{self::FLD_SALES_TAX_BY_RATE} instanceof Tinebase_Record_RecordSet) {
            $pInvoice->{self::FLD_SALES_TAX_BY_RATE} = new Tinebase_Record_RecordSet(Sales_Model_Document_SalesTax::class);
        }
        // 1..* VAT_BREAKDOWN
        foreach ($xr->VAT_BREAKDOWN as $vatBreakdown) {
            $pInvoice->{self::FLD_SALES_TAX_BY_RATE}->addRecord(new Sales_Model_Document_SalesTax([
                Sales_Model_Document_SalesTax::FLD_NET_AMOUNT => floatval((string)$vatBreakdown->VAT_category_taxable_amount),
                Sales_Model_Document_SalesTax::FLD_TAX_AMOUNT => floatval((string)$vatBreakdown->VAT_category_tax_amount),
                Sales_Model_Document_SalesTax::FLD_TAX_RATE => floatval((string)$vatBreakdown->VAT_category_rate),
                Sales_Model_Document_SalesTax::FLD_GROSS_AMOUNT => floatval((string)$vatBreakdown->VAT_category_taxable_amount)
                    + floatval((string)$vatBreakdown->VAT_category_tax_amount),
            ], true));
            $vatBreakdown->VAT_category_code;
            $vatBreakdown->VAT_exemption_reason_text;
            $vatBreakdown->VAT_exemption_reason_code;
        }

        // 0..* ADDITIONAL_SUPPORTING_DOCUMENTS
        foreach ($xr->ADDITIONAL_SUPPORTING_DOCUMENTS as $addtionalSupportingDoc) {
            $addtionalSupportingDoc->Supporting_document_reference; // 1
            $addtionalSupportingDoc->Supporting_document_description; // 0..1
            $addtionalSupportingDoc->External_document_location; // 0..1
            $addtionalSupportingDoc->Attached_document; // 0..1
        }

        // 1..* INVOICE_LINE
        foreach ($xr->INVOICE_LINE as $invoiceLine) {
            $pInvoice->{self::FLD_POSITIONS}->addRecord(
                $pInvoiceLine = new PIPosition([
                    PIPosition::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_NET,
                ], true)
            );
            $pInvoiceLine->{PIPosition::FLD_TITLE} = (string)$invoiceLine->Invoice_line_identifier; // 1
            $invoiceLine->Invoice_line_note; // 0..1
            $invoiceLine->Invoice_line_object_identifier; // 0..1
            $pInvoiceLine->{PIPosition::FLD_QUANTITY} = floatval((string)$invoiceLine->Invoiced_quantity); // 0..1
            $invoiceLine->Invoiced_quantity_unit_of_measure_code; // 0..1
            $pInvoiceLine->{PIPosition::FLD_NET_PRICE} = floatval((string)$invoiceLine->Invoice_line_net_amount); // 0..1
            $invoiceLine->Referenced_purchase_order_line_reference; // 0..1
            $invoiceLine->Invoice_line_Buyer_accounting_reference; // 0..1
            // INVOICE_LINE_PERIOD 0..1
            if ('' !== (string)$invoiceLine->INVOICE_LINE_PERIOD->Invoice_line_period_start_date) {
                $pInvoiceLine->{PIPosition::FLD_SERVICE_PERIOD_START} = new Tinebase_DateTime((string)$invoiceLine->INVOICE_LINE_PERIOD->Invoice_line_period_start_date); // 0..1
            }
            if ('' !== (string)$invoiceLine->INVOICE_LINE_PERIOD->Invoice_line_period_end_date) {
                $pInvoiceLine->{PIPosition::FLD_SERVICE_PERIOD_END} = new Tinebase_DateTime((string)$invoiceLine->INVOICE_LINE_PERIOD->Invoice_line_period_end_date); // 0..1
            }
            $discount = 0.0;
            // INVOICE_LINE_ALLOWANCES 0..*
            foreach ($invoiceLine->INVOICE_LINE_ALLOWANCES as $lineAllowance) {
                $discount += floatval((string)$lineAllowance->Invoice_line_allowance_amount); // 1
                $lineAllowance->Invoice_line_allowance_base_amount; // 0..1
                $lineAllowance->Invoice_line_allowance_percentage; // 0..1
                $lineAllowance->Invoice_line_allowance_reason; // 0..1
                $lineAllowance->Invoice_line_allowance_reason_code; // 0..1
            }
            // INVOICE_LINE_CHARGES 0..*
            foreach ($invoiceLine->INVOICE_LINE_CHARGES as $lineCharges) {
                $discount -= floatval((string)$lineCharges->Invoice_line_charge_amount); // 1
                $lineCharges->Invoice_line_charge_base_amount; // 0..1
                $lineCharges->Invoice_line_charge_percentage; // 0..1
                $lineCharges->Invoice_line_charge_reason; // 0..1
                $lineCharges->Invoice_line_charge_reason_code; // 0..1
            }
            if (0.0 !== $discount) {
                $pInvoiceLine->{PIPosition::FLD_POSITION_DISCOUNT_SUM} = $discount;
                $pInvoiceLine->{PIPosition::FLD_POSITION_DISCOUNT_TYPE} = Sales_Config::INVOICE_DISCOUNT_SUM;
            }
            // PRICE_DETAILS 0..1
            if ('' !== (string)$invoiceLine->PRICE_DETAILS->Item_net_price) { // 1
                $pInvoiceLine->{PIPosition::FLD_UNIT_PRICE} = floatval((string)$invoiceLine->PRICE_DETAILS->Item_net_price);
            }
            $invoiceLine->PRICE_DETAILS->Item_price_discount; // 0..1
            $invoiceLine->PRICE_DETAILS->Item_gross_price; // 0..1
            $invoiceLine->PRICE_DETAILS->Item_price_base_quantity; // 0..1
            $invoiceLine->PRICE_DETAILS->Item_price_base_quantity_unit_of_measure; // 0..1
            // LINE_VAT_INFORMATION 0..*
            foreach ($invoiceLine->LINE_VAT_INFORMATION as $lineVatInfo) {
                $lineVatInfo->Invoiced_item_VAT_category_code; // 1
                if ('' !== (string)$invoiceLine->PRICE_DETAILS->Invoiced_item_VAT_rate) { // 0..1
                    $pInvoiceLine->{PIPosition::FLD_SALES_TAX_RATE} = floatval((string)$invoiceLine->PRICE_DETAILS->Invoiced_item_VAT_rate);
                }
            }
            // ITEM_INFORMATION 1
            $invoiceLine->ITEM_INFORMATION;
            $invoiceLine->ITEM_INFORMATION->Item_name; // 1
            $invoiceLine->ITEM_INFORMATION->Item_description; // 0..1
            $invoiceLine->ITEM_INFORMATION->Item_Sellers_identifier; // 0..1
            $invoiceLine->ITEM_INFORMATION->Item_Buyers_identifier; // 0..1
            $invoiceLine->ITEM_INFORMATION->Item_standard_identifier; // 0..1
            foreach ($invoiceLine->ITEM_INFORMATION->Item_classification_identifier as $classId) {// 0..*
            }
            $invoiceLine->ITEM_INFORMATION->Item_country_of_origin; // 0..1
            // 0..1 ITEM_ATTRIBUTES
            foreach ($invoiceLine->ITEM_INFORMATION->ITEM_ATTRIBUTES as $itemAttribute) {

            }

            // SUB_INVOICE_LINE 0..*
            foreach ($invoiceLine->SUB_INVOICE_LINE as $subLine) {
                // recursion!
            }
        }

        // 0..* THIRD_PARTY_PAYMENT
        foreach ($xr->THIRD_PARTY_PAYMENT as $thirdPartyPayment) {

        }


        return $pInvoice;
    }
}
<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

//use Tinebase_Model_Filter_Abstract as TMFA;
use Sales_Model_Document_Invoice as SMDI;
use Sales_Model_DocumentPosition_Invoice as SMDPI;

/**
 * Test class for UBL in Sales_Controller_Document_*
 */
class Sales_Document_UblTest extends Sales_Document_Abstract
{
    protected $oldPreviewSvc = null;
    protected $oldEDocSvc = null;

    public function setUp(): void
    {
        parent::setUp();

        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);

        $this->oldEDocSvc = null;
        if (!Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::VALIDATION_SVC}) {
            $this->oldEDocSvc = Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->toArray();

            Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::VALIDATION_SVC} = 'https://edocument-mw.mws-hosting.net/ubl';
            Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::EDOCUMENT_SVC_BASE_URL} = 'https://edocument-mw.mws-hosting.net';
            Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::VIEW_SVC} = 'https://edocument-mw.mws-hosting.net/ublView';
        }
    }

    public function tearDown(): void
    {
        if (null !== $this->oldEDocSvc) {
            Sales_Config::getInstance()->{Sales_Config::EDOCUMENT} = $this->oldEDocSvc;
        }
        parent::tearDown();
    }

    /**
     * TODO use \Sales_Document_Abstract::_createInvoice
     *
     * @param array $positions
     * @param array $invoiceData
     * @param Sales_Model_Customer|null $customer
     * @return Sales_Model_Document_Invoice
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _createUblInvoice(array $positions, array $invoiceData = [], ?Sales_Model_Customer $customer = null): SMDI
    {
        if (null === $customer) {
            $customer = $this->_createCustomer();
        }

        /** @var SMDI $invoice */
        $invoice = Sales_Controller_Document_Invoice::getInstance()->create(new SMDI(array_merge([
            SMDI::FLD_CUSTOMER_ID => $customer,
            SMDI::FLD_PAYMENT_TERMS => 10,
            SMDI::FLD_INVOICE_STATUS => SMDI::STATUS_PROFORMA,
            SMDI::FLD_DOCUMENT_DATE => Tinebase_DateTime::now(),
            SMDI::FLD_BUYER_REFERENCE => 'buy ref',
            SMDI::FLD_RECIPIENT_ID => $customer->{Sales_Model_Customer::FLD_DEBITORS}->getFirstRecord()->{Sales_Model_Debitor::FLD_BILLING}->getFirstRecord(),
            SMDI::FLD_POSITIONS => $positions,
        ], $invoiceData)));

        return $invoice;
    }

    protected function _assertUblXml(SMDI $invoice, float $taxExclValue, float $taxInclValue): SimpleXMLElement
    {
        Sales_Controller_Document_Invoice::getInstance()->createEDocument($invoice->getId());
        Tinebase_Record_Expander_DataRequest::clearCache();
        /** @var SMDI $invoice */
        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice->getId());

        $this->assertNotNull($node = $invoice->attachments->find(fn(Tinebase_Model_Tree_Node $attachment) => str_ends_with($attachment->name, '-xrechnung.xml'), null));
        //echo file_get_contents('tine20://' . Tinebase_FileSystem::getInstance()->getPathOfNode($node, true));
        $xml = new SimpleXMLElement(file_get_contents('tine20://' . Tinebase_FileSystem::getInstance()->getPathOfNode($node, true)));
        $xml->registerXPathNamespace('ubl', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');
        $xml->registerXPathNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xml->registerXPathNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $this->assertIsArray($taxExclAmount = $xml->xpath('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:TaxExclusiveAmount'));
        $this->assertSame($taxExclValue, (float)$taxExclAmount[0]);
        $this->assertIsArray($taxInclAmount = $xml->xpath('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:TaxInclusiveAmount'));
        $this->assertSame($taxInclValue, (float)$taxInclAmount[0]);

        return $xml;
    }

    public function testReadPdfInvoice(): void
    {
        //$zug = Sales_EDocument_ZUGFeRD::createFromString(file_get_contents(__DIR__ . '/files/XRECHNUNG_Einfach.pdf'));

        $path = Tinebase_FileSystem::getInstance()
                ->getApplicationBasePath(Filemanager_Config::APP_NAME, Tinebase_FileSystem::FOLDER_TYPE_SHARED) . '/unittest';
        Tinebase_FileSystem::getInstance()->mkdir($path);
        fwrite(
            $fh = Tinebase_FileSystem::getInstance()->fopen($path .  '/test.pdf', 'w'),
            file_get_contents(__DIR__ . '/files/XRECHNUNG_Einfach.pdf'));
        Tinebase_FileSystem::getInstance()->fclose($fh);

        $pInvoice = Sales_Controller_Document_PurchaseInvoice::getInstance()->importPurchaseInvoice(
            new Tinebase_Model_FileLocation([
                Tinebase_Model_FileLocation::FLD_LOCATION => new Tinebase_Model_JsonRecordWrapper([
                    Tinebase_Model_JsonRecordWrapper::FLD_MODEL_NAME => Filemanager_Model_FileLocation::class,
                    Tinebase_Model_JsonRecordWrapper::FLD_RECORD =>
                        new Filemanager_Model_FileLocation([
                            Filemanager_Model_FileLocation::FLD_FM_PATH => '/shared/unittest/test.pdf',
                        ]),
                ])
            ])
        );
        $this->assertNull($pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_DOCUMENT_NUMBER});
        $this->assertInstanceOf(Tinebase_DateTime::class, $pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_DUE_AT});
        $this->assertTrue((new Tinebase_DateTime('2025-12-15'))->equals($pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_DUE_AT}));
        $this->assertInstanceOf(Tinebase_Record_RecordSet::class, $pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_PAYMENT_MEANS});
        $this->assertSame(1, $pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_PAYMENT_MEANS}->count());
        $this->assertSame(Sales_Model_EDocument_PMC_PurchaseCreditTransfer::class, $pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_PAYMENT_MEANS}->getFirstRecord()->{Sales_Model_PurchasePaymentMeans::FLD_CONFIG_CLASS});
        $this->assertInstanceOf(Sales_Model_EDocument_PMC_PurchaseCreditTransfer::class, $pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_PAYMENT_MEANS}->getFirstRecord()->{Sales_Model_PurchasePaymentMeans::FLD_CONFIG});
        $this->assertSame('Kunden AG', $pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_PAYMENT_MEANS}->getFirstRecord()->{Sales_Model_PurchasePaymentMeans::FLD_CONFIG}->{Sales_Model_EDocument_PMC_PurchaseCreditTransfer::FLD_ACCOUNT_NAME});
        $this->assertSame('DE02120300000000202051', $pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_PAYMENT_MEANS}->getFirstRecord()->{Sales_Model_PurchasePaymentMeans::FLD_CONFIG}->{Sales_Model_EDocument_PMC_PurchaseCreditTransfer::FLD_ACCOUNT_IDENTIFIER});
        $this->assertSame('BYLADEM1001', $pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_PAYMENT_MEANS}->getFirstRecord()->{Sales_Model_PurchasePaymentMeans::FLD_CONFIG}->{Sales_Model_EDocument_PMC_PurchaseCreditTransfer::FLD_SERVICE_PROVIDER_IDENTIFIER});

        $pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_PURCHASE_INVOICE_STATUS} =  Sales_Model_Document_PurchaseInvoice::STATUS_APPROVED;
        $pInvoice = Sales_Controller_Document_PurchaseInvoice::getInstance()->update($pInvoice);
        $this->assertNotNull($pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_DOCUMENT_NUMBER});
    }

    public function testPurchaseInvoiceFromXr(): void
    {
        $xml = <<<EOSTR
<?xml version="1.0" encoding="UTF-8"?>
<xr:invoice xmlns:xr="urn:ce.eu:en16931:2017:xoev-de:kosit:standard:xrechnung-1">
    <xr:Invoice_number xr:id="BT-1" xr:src="/Invoice/cbc:ID">RE-0000001</xr:Invoice_number>
    <xr:Invoice_issue_date xr:id="BT-2" xr:src="/Invoice/cbc:IssueDate">2025-05-23</xr:Invoice_issue_date>
    <xr:Invoice_type_code xr:id="BT-3" xr:src="/Invoice/cbc:InvoiceTypeCode">380</xr:Invoice_type_code>
    <xr:Invoice_currency_code xr:id="BT-5" xr:src="/Invoice/cbc:DocumentCurrencyCode">EUR</xr:Invoice_currency_code>
    <xr:Payment_due_date xr:id="BT-9" xr:src="/Invoice/cbc:DueDate">2025-06-02</xr:Payment_due_date>
    <xr:Buyer_reference xr:id="BT-10" xr:src="/Invoice/cbc:BuyerReference">buy ref</xr:Buyer_reference>
    <xr:Payment_terms xr:id="BT-20" xr:src="/Invoice/cac:PaymentTerms/cbc:Note">Payable within 10 days without deduction.</xr:Payment_terms>
    <xr:PROCESS_CONTROL xr:id="BG-2" xr:src="/Invoice">
        <xr:Business_process_type_identifier xr:id="BT-23" xr:src="/Invoice">urn:fdc:peppol.eu:2017:poacc:billing:01:1.0</xr:Business_process_type_identifier>
        <xr:Specification_identifier xr:id="BT-24" xr:src="/Invoice">urn:cen.eu:en16931:2017#compliant#urn:xeinkauf.de:kosit:xrechnung_3.0</xr:Specification_identifier>
    </xr:PROCESS_CONTROL>
    <xr:SELLER xr:id="BG-4" xr:src="/Invoice/cac:AccountingSupplierParty">
        <xr:Seller_name xr:id="BT-27"
                        xr:src="/Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName">Mit Namen befüllen</xr:Seller_name>
        <xr:Seller_identifier xr:id="BT-29"
                              xr:src="/Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyIdentification/cbc:ID">1234567890</xr:Seller_identifier>
        <xr:Seller_VAT_identifier xr:id="BT-31"
                                  xr:src="/Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme[1]/cbc:CompanyID">DE1234567890</xr:Seller_VAT_identifier>
        <xr:Seller_tax_registration_identifier xr:id="BT-32"
                                               xr:src="/Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme[2]/cbc:CompanyID">1234567890</xr:Seller_tax_registration_identifier>
        <xr:Seller_electronic_address xr:id="BT-34"
                                      xr:src="/Invoice/cac:AccountingSupplierParty/cac:Party/cbc:EndpointID"
                                      scheme_identifier="9930">DE1234567890</xr:Seller_electronic_address>
        <xr:SELLER_POSTAL_ADDRESS xr:id="BG-5"
                                  xr:src="/Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress">
            <xr:Seller_address_line_3 xr:id="BT-162"
                                      xr:src="/Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cac:AddressLine/cbc:Line">Mit Adresse befüllen</xr:Seller_address_line_3>
            <xr:Seller_city xr:id="BT-37"
                            xr:src="/Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:CityName">Mit Stadt befüllen</xr:Seller_city>
            <xr:Seller_post_code xr:id="BT-38"
                                 xr:src="/Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:PostalZone">Mit Postleitzahl befüllen</xr:Seller_post_code>
            <xr:Seller_country_code xr:id="BT-40"
                                    xr:src="/Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cac:Country/cbc:IdentificationCode">DE</xr:Seller_country_code>
        </xr:SELLER_POSTAL_ADDRESS>
        <xr:SELLER_CONTACT xr:id="BG-6"
                           xr:src="/Invoice/cac:AccountingSupplierParty/cac:Party/cac:Contact">
            <xr:Seller_contact_point xr:id="BT-41"
                                     xr:src="/Invoice/cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:Name">Mit Kontakt-Namen befüllen</xr:Seller_contact_point>
            <xr:Seller_contact_telephone_number xr:id="BT-42"
                                                xr:src="/Invoice/cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:Telephone">123</xr:Seller_contact_telephone_number>
            <xr:Seller_contact_email_address xr:id="BT-43"
                                             xr:src="/Invoice/cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:ElectronicMail">test@foo.de</xr:Seller_contact_email_address>
        </xr:SELLER_CONTACT>
    </xr:SELLER>
    <xr:BUYER xr:id="BG-7" xr:src="/Invoice/cac:AccountingCustomerParty">
        <xr:Buyer_name xr:id="BT-44"
                       xr:src="/Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName">some billing address for 852a12a14fcd6dc28871eda64d3065d00200dcdf</xr:Buyer_name>
        <xr:Buyer_VAT_identifier xr:id="BT-48"
                                 xr:src="/Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID">DE0987654321</xr:Buyer_VAT_identifier>
        <xr:Buyer_electronic_address xr:id="BT-49"
                                     xr:src="/Invoice/cac:AccountingCustomerParty/cac:Party/cbc:EndpointID"
                                     scheme_identifier="9930">DE0987654321</xr:Buyer_electronic_address>
        <xr:BUYER_POSTAL_ADDRESS xr:id="BG-8"
                                 xr:src="/Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress">
            <xr:Buyer_city xr:id="BT-52"
                           xr:src="/Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:CityName">Neu Altdorf</xr:Buyer_city>
            <xr:Buyer_post_code xr:id="BT-53"
                                xr:src="/Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:PostalZone">12345</xr:Buyer_post_code>
            <xr:Buyer_country_code xr:id="BT-55"
                                   xr:src="/Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cac:Country/cbc:IdentificationCode">DE</xr:Buyer_country_code>
        </xr:BUYER_POSTAL_ADDRESS>
    </xr:BUYER>
    <xr:PAYMENT_INSTRUCTIONS xr:id="BG-16" xr:src="/Invoice">
        <xr:Payment_means_type_code xr:id="BT-81" xr:src="/Invoice/cac:PaymentMeans/cbc:PaymentMeansCode">58</xr:Payment_means_type_code>
        <xr:Payment_means_text xr:id="BT-82"
                               xr:src="/Invoice/cac:PaymentMeans/cbc:PaymentMeansCode/@name">SEPA credit transfer</xr:Payment_means_text>
        <xr:Remittance_information xr:id="BT-83" xr:src="/Invoice/cac:PaymentMeans/cbc:PaymentID">RE-0000001 DEB-1</xr:Remittance_information>
        <xr:CREDIT_TRANSFER xr:id="BG-17"
                            xr:src="/Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount">
            <xr:Payment_account_identifier xr:id="BT-84"
                                           xr:src="/Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:ID">DE02120300000000202051</xr:Payment_account_identifier>
            <xr:Payment_account_name xr:id="BT-85"
                                     xr:src="/Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:Name">Mit Namen befüllen</xr:Payment_account_name>
            <xr:Payment_service_provider_identifier xr:id="BT-86"
                                                    xr:src="/Invoice/cac:PaymentMeans/cac:PayeeFinancialAccount/cac:FinancialInstitutionBranch/cbc:ID">BYLADEM1001</xr:Payment_service_provider_identifier>
        </xr:CREDIT_TRANSFER>
    </xr:PAYMENT_INSTRUCTIONS>
    <xr:DOCUMENT_TOTALS xr:id="BG-22" xr:src="/Invoice/cac:LegalMonetaryTotal">
        <xr:Sum_of_Invoice_line_net_amount xr:id="BT-106"
                                           xr:src="/Invoice/cac:LegalMonetaryTotal/cbc:LineExtensionAmount">5.89</xr:Sum_of_Invoice_line_net_amount>
        <xr:Invoice_total_amount_without_VAT xr:id="BT-109"
                                             xr:src="/Invoice/cac:LegalMonetaryTotal/cbc:TaxExclusiveAmount">5.89</xr:Invoice_total_amount_without_VAT>
        <xr:Invoice_total_VAT_amount xr:id="BT-110" xr:src="/Invoice/cac:TaxTotal/cbc:TaxAmount">1.12</xr:Invoice_total_VAT_amount>
        <xr:Invoice_total_amount_with_VAT xr:id="BT-112"
                                          xr:src="/Invoice/cac:LegalMonetaryTotal/cbc:TaxInclusiveAmount">7.01</xr:Invoice_total_amount_with_VAT>
        <xr:Amount_due_for_payment xr:id="BT-115"
                                   xr:src="/Invoice/cac:LegalMonetaryTotal/cbc:PayableAmount">7.01</xr:Amount_due_for_payment>
    </xr:DOCUMENT_TOTALS>
    <xr:VAT_BREAKDOWN xr:id="BG-23" xr:src="/Invoice/cac:TaxTotal/cac:TaxSubtotal">
        <xr:VAT_category_taxable_amount xr:id="BT-116"
                                        xr:src="/Invoice/cac:TaxTotal/cac:TaxSubtotal/cbc:TaxableAmount">5.89</xr:VAT_category_taxable_amount>
        <xr:VAT_category_tax_amount xr:id="BT-117"
                                    xr:src="/Invoice/cac:TaxTotal/cac:TaxSubtotal/cbc:TaxAmount">1.12</xr:VAT_category_tax_amount>
        <xr:VAT_category_code xr:id="BT-118"
                              xr:src="/Invoice/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cbc:ID">S</xr:VAT_category_code>
        <xr:VAT_category_rate xr:id="BT-119"
                              xr:src="/Invoice/cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cbc:Percent">19.0</xr:VAT_category_rate>
    </xr:VAT_BREAKDOWN>
    <xr:INVOICE_LINE xr:id="BG-25" xr:src="/Invoice/cac:InvoiceLine[1]">
        <xr:Invoice_line_identifier xr:id="BT-126" xr:src="/Invoice/cac:InvoiceLine[1]/cbc:ID">1</xr:Invoice_line_identifier>
        <xr:Invoiced_quantity xr:id="BT-129"
                              xr:src="/Invoice/cac:InvoiceLine[1]/cbc:InvoicedQuantity">1.0</xr:Invoiced_quantity>
        <xr:Invoiced_quantity_unit_of_measure_code xr:id="BT-130"
                                                   xr:src="/Invoice/cac:InvoiceLine[1]/cbc:InvoicedQuantity/@unitCode">C62</xr:Invoiced_quantity_unit_of_measure_code>
        <xr:Invoice_line_net_amount xr:id="BT-131"
                                    xr:src="/Invoice/cac:InvoiceLine[1]/cbc:LineExtensionAmount">4.9</xr:Invoice_line_net_amount>
        <xr:INVOICE_LINE_ALLOWANCES xr:id="BG-27" xr:src="/Invoice/cac:InvoiceLine[1]/cac:AllowanceCharge">
            <xr:Invoice_line_allowance_amount xr:id="BT-136"
                                              xr:src="/Invoice/cac:InvoiceLine[1]/cac:AllowanceCharge/cbc:Amount">0.1</xr:Invoice_line_allowance_amount>
            <xr:Invoice_line_allowance_reason_code xr:id="BT-140"
                                                   xr:src="/Invoice/cac:InvoiceLine[1]/cac:AllowanceCharge/cbc:AllowanceChargeReasonCode">95</xr:Invoice_line_allowance_reason_code>
        </xr:INVOICE_LINE_ALLOWANCES>
        <xr:PRICE_DETAILS xr:id="BG-29" xr:src="/Invoice/cac:InvoiceLine[1]/cac:Price">
            <xr:Item_net_price xr:id="BT-146"
                               xr:src="/Invoice/cac:InvoiceLine[1]/cac:Price/cbc:PriceAmount">5.0</xr:Item_net_price>
        </xr:PRICE_DETAILS>
        <xr:LINE_VAT_INFORMATION xr:id="BG-30"
                                 xr:src="/Invoice/cac:InvoiceLine[1]/cac:Item/cac:ClassifiedTaxCategory">
            <xr:Invoiced_item_VAT_category_code xr:id="BT-151"
                                                xr:src="/Invoice/cac:InvoiceLine[1]/cac:Item/cac:ClassifiedTaxCategory/cbc:ID">S</xr:Invoiced_item_VAT_category_code>
            <xr:Invoiced_item_VAT_rate xr:id="BT-152"
                                       xr:src="/Invoice/cac:InvoiceLine[1]/cac:Item/cac:ClassifiedTaxCategory/cbc:Percent">19.0</xr:Invoiced_item_VAT_rate>
        </xr:LINE_VAT_INFORMATION>
        <xr:ITEM_INFORMATION xr:id="BG-31" xr:src="/Invoice/cac:InvoiceLine[1]/cac:Item">
            <xr:Item_name xr:id="BT-153" xr:src="/Invoice/cac:InvoiceLine[1]/cac:Item/cbc:Name">pos 2</xr:Item_name>
        </xr:ITEM_INFORMATION>
    </xr:INVOICE_LINE>
    <xr:INVOICE_LINE xr:id="BG-25" xr:src="/Invoice/cac:InvoiceLine[2]">
        <xr:Invoice_line_identifier xr:id="BT-126" xr:src="/Invoice/cac:InvoiceLine[2]/cbc:ID">2</xr:Invoice_line_identifier>
        <xr:Invoiced_quantity xr:id="BT-129"
                              xr:src="/Invoice/cac:InvoiceLine[2]/cbc:InvoicedQuantity">1.0</xr:Invoiced_quantity>
        <xr:Invoiced_quantity_unit_of_measure_code xr:id="BT-130"
                                                   xr:src="/Invoice/cac:InvoiceLine[2]/cbc:InvoicedQuantity/@unitCode">C62</xr:Invoiced_quantity_unit_of_measure_code>
        <xr:Invoice_line_net_amount xr:id="BT-131"
                                    xr:src="/Invoice/cac:InvoiceLine[2]/cbc:LineExtensionAmount">0.99</xr:Invoice_line_net_amount>
        <xr:INVOICE_LINE_ALLOWANCES xr:id="BG-27" xr:src="/Invoice/cac:InvoiceLine[2]/cac:AllowanceCharge">
            <xr:Invoice_line_allowance_amount xr:id="BT-136"
                                              xr:src="/Invoice/cac:InvoiceLine[2]/cac:AllowanceCharge/cbc:Amount">0.01</xr:Invoice_line_allowance_amount>
            <xr:Invoice_line_allowance_reason_code xr:id="BT-140"
                                                   xr:src="/Invoice/cac:InvoiceLine[2]/cac:AllowanceCharge/cbc:AllowanceChargeReasonCode">95</xr:Invoice_line_allowance_reason_code>
        </xr:INVOICE_LINE_ALLOWANCES>
        <xr:PRICE_DETAILS xr:id="BG-29" xr:src="/Invoice/cac:InvoiceLine[2]/cac:Price">
            <xr:Item_net_price xr:id="BT-146"
                               xr:src="/Invoice/cac:InvoiceLine[2]/cac:Price/cbc:PriceAmount">1.0</xr:Item_net_price>
        </xr:PRICE_DETAILS>
        <xr:LINE_VAT_INFORMATION xr:id="BG-30"
                                 xr:src="/Invoice/cac:InvoiceLine[2]/cac:Item/cac:ClassifiedTaxCategory">
            <xr:Invoiced_item_VAT_category_code xr:id="BT-151"
                                                xr:src="/Invoice/cac:InvoiceLine[2]/cac:Item/cac:ClassifiedTaxCategory/cbc:ID">S</xr:Invoiced_item_VAT_category_code>
            <xr:Invoiced_item_VAT_rate xr:id="BT-152"
                                       xr:src="/Invoice/cac:InvoiceLine[2]/cac:Item/cac:ClassifiedTaxCategory/cbc:Percent">19.0</xr:Invoiced_item_VAT_rate>
        </xr:LINE_VAT_INFORMATION>
        <xr:ITEM_INFORMATION xr:id="BG-31" xr:src="/Invoice/cac:InvoiceLine[2]/cac:Item">
            <xr:Item_name xr:id="BT-153" xr:src="/Invoice/cac:InvoiceLine[2]/cac:Item/cbc:Name">pos 1</xr:Item_name>
        </xr:ITEM_INFORMATION>
    </xr:INVOICE_LINE>
</xr:invoice>
EOSTR;

        $pInvoice = Sales_Model_Document_PurchaseInvoice::fromXR($xml);
    }

    public function testUblValidationFail(): void
    {
        if (!Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::VALIDATION_SVC}) {
            $this->markTestSkipped('validation service not configured');
        }

        $product1 = $this->_createProduct();

        $positions = [
            new SMDPI([
                SMDPI::FLD_TITLE => 'pos 1',
                SMDPI::FLD_PRODUCT_ID => $product1->getId(),
                SMDPI::FLD_QUANTITY => 1,
                SMDPI::FLD_UNIT_PRICE => 1,
                SMDPI::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_NET,
                SMDPI::FLD_SALES_TAX_RATE => Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX},
            ], true),
        ];
        $invoice = $this->_createUblInvoice($positions);

        $division = Sales_Controller_Division::getInstance()->get(Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION});
        $division->{Sales_Model_Division::FLD_VAT_NUMBER} = '';
        $division->{Sales_Model_Division::FLD_TAX_REGISTRATION_ID} = '';
        Sales_Controller_Division::getInstance()->update($division);
        Tinebase_Record_Expander_DataRequest::clearCache();

        $invoice->{SMDI::FLD_INVOICE_STATUS} = SMDI::STATUS_BOOKED;
        Sales_Controller_Document_Invoice::getInstance()->update($invoice);
        try {
            Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);
            Sales_Controller_Document_Invoice::getInstance()->createEDocument($invoice->getId());
            $this->fail('expect to throw ' . Tinebase_Exception_HtmlReport::class);
        } catch (Tinebase_Exception_HtmlReport $e) {
            $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice->getId());
            $this->assertSame(1, $invoice->attachments->count());
            $attachement = $invoice->attachments->getFirstRecord();
            $this->assertSame($invoice->{SMDI::FLD_DOCUMENT_NUMBER} . '-xrechnung.xml.validation.html', $attachement->name);
            $this->assertSame($e->getHtml(), file_get_contents('tine20://' . Tinebase_FileSystem::getInstance()->getPathOfNode($attachement, true)));
        }
    }

    public function testCustomerPercentageDiscount(): void
    {
        $product1 = $this->_createProduct();
        $positions = [
            new SMDPI([
                SMDPI::FLD_TITLE => 'pos 1',
                SMDPI::FLD_PRODUCT_ID => $product1->getId(),
                SMDPI::FLD_QUANTITY => 1,
                SMDPI::FLD_UNIT_PRICE => 1,
                SMDPI::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_NET,
                SMDPI::FLD_SALES_TAX_RATE => Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX},
            ], true),
        ];
        $customer = $this->_createCustomer(additionalCustomerData: ['discount' => 10]);

        $invoice = $this->_createUblInvoice($positions, customer: $customer);
        $invoice->{SMDI::FLD_INVOICE_STATUS} = SMDI::STATUS_BOOKED;
        /** @var SMDI $invoice */
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);
        $this->_assertUblXml($invoice, 0.90, round(0.9 * (1 + Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX} / 100), 2));
    }

    public function testPositionNetDiscount(): void
    {
        $product1 = $this->_createProduct();

        $positions = [
            new SMDPI([
                SMDPI::FLD_TITLE => 'pos 1',
                SMDPI::FLD_PRODUCT_ID => $product1->getId(),
                SMDPI::FLD_QUANTITY => 1,
                SMDPI::FLD_UNIT_PRICE => 1,
                SMDPI::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_NET,
                SMDPI::FLD_SALES_TAX_RATE => Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX},
                SMDPI::FLD_POSITION_DISCOUNT_TYPE => Sales_Config::INVOICE_DISCOUNT_SUM,
                SMDPI::FLD_POSITION_DISCOUNT_SUM => 0.01
            ], true),
            new SMDPI([
                SMDPI::FLD_TITLE => 'pos 2',
                SMDPI::FLD_PRODUCT_ID => $product1->getId(),
                SMDPI::FLD_QUANTITY => 1,
                SMDPI::FLD_UNIT_PRICE => 5,
                SMDPI::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_NET,
                SMDPI::FLD_SALES_TAX_RATE => Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX},
                SMDPI::FLD_POSITION_DISCOUNT_TYPE => Sales_Config::INVOICE_DISCOUNT_SUM,
                SMDPI::FLD_POSITION_DISCOUNT_SUM => 0.1
            ], true)
        ];
        $invoice = $this->_createUblInvoice($positions);
        $invoice->{SMDI::FLD_INVOICE_STATUS} = SMDI::STATUS_BOOKED;
        /** @var SMDI $invoice */
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);
        $this->_assertUblXml($invoice, 5.89, round(5.89 * (1 + Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX} / 100), 2));
    }

    public function testVatZeroReverse(): void
    {
        $product1 = $this->_createProduct();

        $positions = [
            new SMDPI([
                SMDPI::FLD_TITLE => 'pos 1',
                SMDPI::FLD_PRODUCT_ID => $product1->getId(),
                SMDPI::FLD_QUANTITY => 1,
                SMDPI::FLD_UNIT_PRICE => 1,
                SMDPI::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_NET,
                SMDPI::FLD_SALES_TAX_RATE => 0,
            ], true),
        ];

        $invoice = $this->_createUblInvoice($positions, [SMDI::FLD_VAT_PROCEDURE => Sales_Config::VAT_PROCEDURE_REVERSE_CHARGE]);
        $this->assertInstanceOf(Sales_Model_EDocument_VATEX::class, $invoice->{SMDI::FLD_VATEX_ID});
        $this->assertSame('vatex-eu-ae', $invoice->{SMDI::FLD_VATEX_ID}->{Sales_Model_EDocument_VATEX::FLD_CODE});
        $invoice->{SMDI::FLD_INVOICE_STATUS} = SMDI::STATUS_BOOKED;
        /** @var SMDI $invoice */
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);
        $this->_assertUblXml($invoice, 1, 1);
    }

    public function testVatZeroExport(): void
    {
        $product1 = $this->_createProduct();

        $positions = [
            new SMDPI([
                SMDPI::FLD_TITLE => 'pos 1',
                SMDPI::FLD_PRODUCT_ID => $product1->getId(),
                SMDPI::FLD_QUANTITY => 1,
                SMDPI::FLD_UNIT_PRICE => 1,
                SMDPI::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_NET,
                SMDPI::FLD_SALES_TAX_RATE => 0,
            ], true),
        ];
        $invoice = $this->_createUblInvoice($positions, [SMDI::FLD_VAT_PROCEDURE => Sales_Config::VAT_PROCEDURE_FREE_EXPORT_ITEM]);
        $this->assertInstanceOf(Sales_Model_EDocument_VATEX::class, $invoice->{SMDI::FLD_VATEX_ID});
        $this->assertSame('vatex-eu-g', $invoice->{SMDI::FLD_VATEX_ID}->{Sales_Model_EDocument_VATEX::FLD_CODE});
        $invoice->{SMDI::FLD_INVOICE_STATUS} = SMDI::STATUS_BOOKED;
        /** @var SMDI $invoice */
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);
        $this->_assertUblXml($invoice, 1, 1);
    }

    public function testVatZeroOutsideTaxScope(): void
    {
        $product1 = $this->_createProduct();

        $positions = [
            new SMDPI([
                SMDPI::FLD_TITLE => 'pos 1',
                SMDPI::FLD_PRODUCT_ID => $product1->getId(),
                SMDPI::FLD_QUANTITY => 1,
                SMDPI::FLD_UNIT_PRICE => 1,
                SMDPI::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_NET,
                SMDPI::FLD_SALES_TAX_RATE => 0,
            ], true),
        ];
        $invoice = $this->_createUblInvoice($positions, [
            SMDI::FLD_VAT_PROCEDURE => Sales_Config::VAT_PROCEDURE_OUTSIDE_TAX_SCOPE,
        ]);
        $this->assertInstanceOf(Sales_Model_EDocument_VATEX::class, $invoice->{SMDI::FLD_VATEX_ID});
        $this->assertSame('vatex-eu-o', $invoice->{SMDI::FLD_VATEX_ID}->{Sales_Model_EDocument_VATEX::FLD_CODE});
        $invoice->{SMDI::FLD_INVOICE_STATUS} = SMDI::STATUS_BOOKED;
        /** @var SMDI $invoice */
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);
        $this->_assertUblXml($invoice, 1, 1);
    }

    public function testVatZeroRatedGoods(): void
    {
        $product1 = $this->_createProduct();

        $positions = [
            new SMDPI([
                SMDPI::FLD_TITLE => 'pos 1',
                SMDPI::FLD_PRODUCT_ID => $product1->getId(),
                SMDPI::FLD_QUANTITY => 1,
                SMDPI::FLD_UNIT_PRICE => 1,
                SMDPI::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_NET,
                SMDPI::FLD_SALES_TAX_RATE => 0,
            ], true),
        ];
        $invoice = $this->_createUblInvoice($positions, [
            SMDI::FLD_VAT_PROCEDURE => Sales_Config::VAT_PROCEDURE_ZERO_RATED_GOODS,
        ]);
        $this->assertNull($invoice->{SMDI::FLD_VATEX_ID});
        $invoice->{SMDI::FLD_INVOICE_STATUS} = SMDI::STATUS_BOOKED;
        /** @var SMDI $invoice */
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);
        $this->_assertUblXml($invoice, 1, 1);
    }

    public function testPositionGrossDiscount(): void
    {
        $product1 = $this->_createProduct();

        $positions = [
            new SMDPI([
                SMDPI::FLD_TITLE => 'pos 1',
                SMDPI::FLD_PRODUCT_ID => $product1->getId(),
                SMDPI::FLD_QUANTITY => 1,
                SMDPI::FLD_UNIT_PRICE => 1,
                SMDPI::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_GROSS,
                SMDPI::FLD_POSITION_DISCOUNT_TYPE => Sales_Config::INVOICE_DISCOUNT_SUM,
                SMDPI::FLD_POSITION_DISCOUNT_SUM => 0.01,
                SMDPI::FLD_SALES_TAX_RATE => Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX},
            ], true),
            new SMDPI([
                SMDPI::FLD_TITLE => 'pos 2',
                SMDPI::FLD_PRODUCT_ID => $product1->getId(),
                SMDPI::FLD_QUANTITY => 1,
                SMDPI::FLD_UNIT_PRICE => 5,
                SMDPI::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_GROSS,
                SMDPI::FLD_POSITION_DISCOUNT_TYPE => Sales_Config::INVOICE_DISCOUNT_SUM,
                SMDPI::FLD_POSITION_DISCOUNT_SUM => 0.1,
                SMDPI::FLD_SALES_TAX_RATE => Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX},
            ], true)
        ];
        $invoice = $this->_createUblInvoice($positions);
        $invoice->{SMDI::FLD_INVOICE_STATUS} = SMDI::STATUS_BOOKED;
        /** @var SMDI $invoice */
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);
        $this->_assertUblXml($invoice, round(5.89/(1 + Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX} / 100), 2), 5.89);
    }

    public function testPositionGrossDiscountDocumentDiscount(): void
    {
        $product1 = $this->_createProduct();

        $positions = [
            new SMDPI([
                SMDPI::FLD_TITLE => 'pos 1',
                SMDPI::FLD_PRODUCT_ID => $product1->getId(),
                SMDPI::FLD_QUANTITY => 1,
                SMDPI::FLD_UNIT_PRICE => 1,
                SMDPI::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_GROSS,
                SMDPI::FLD_POSITION_DISCOUNT_TYPE => Sales_Config::INVOICE_DISCOUNT_SUM,
                SMDPI::FLD_POSITION_DISCOUNT_SUM => 0.01,
                SMDPI::FLD_SALES_TAX_RATE => Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX},
            ], true),
            new SMDPI([
                SMDPI::FLD_TITLE => 'pos 2',
                SMDPI::FLD_PRODUCT_ID => $product1->getId(),
                SMDPI::FLD_QUANTITY => 1,
                SMDPI::FLD_UNIT_PRICE => 5,
                SMDPI::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_GROSS,
                SMDPI::FLD_POSITION_DISCOUNT_TYPE => Sales_Config::INVOICE_DISCOUNT_SUM,
                SMDPI::FLD_POSITION_DISCOUNT_SUM => 0.01,
                SMDPI::FLD_SALES_TAX_RATE => Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX},
            ], true)
        ];
        $invoice = $this->_createUblInvoice($positions, [
            SMDI::FLD_INVOICE_DISCOUNT_TYPE => Sales_Config::INVOICE_DISCOUNT_SUM,
            SMDI::FLD_INVOICE_DISCOUNT_SUM => 0.01
        ]);
        $invoice->{SMDI::FLD_INVOICE_STATUS} = SMDI::STATUS_BOOKED;
        /** @var SMDI $invoice */
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);
        $this->_assertUblXml($invoice, round(5.97/(1 + Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX} / 100)-0.01, 2), 5.97);
    }

    public function testUblView(): void
    {
        if (!Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::VIEW_SVC}) {
            $this->markTestSkipped('no edocument view service configured, skipping');
        }
        $product1 = $this->_createProduct();
        $positions = [
            new SMDPI([
                SMDPI::FLD_TITLE => 'pos 1',
                SMDPI::FLD_PRODUCT_ID => $product1->getId(),
                SMDPI::FLD_QUANTITY => 1,
                SMDPI::FLD_UNIT_PRICE => 10,
                SMDPI::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_NET,
                SMDPI::FLD_SALES_TAX_RATE => Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX},
            ], true),
        ];
        $buyRef = 'buy refüÜß³';
        $invoice = $this->_createUblInvoice($positions, [
            SMDI::FLD_BUYER_REFERENCE => $buyRef,
        ]);
        $invoice->{SMDI::FLD_INVOICE_STATUS} = SMDI::STATUS_BOOKED;
        /** @var SMDI $invoice */
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);
        Sales_Controller_Document_Invoice::getInstance()->createEDocument($invoice->getId());
        Tinebase_Record_Expander_DataRequest::clearCache();
        /** @var SMDI $invoice */
        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoice->getId());

        $this->assertNotNull($node = $invoice->attachments->find(fn(Tinebase_Model_Tree_Node $attachment) => str_ends_with($attachment->name, '-xrechnung.xml'), null));
        ob_start();
        (new Sales_Frontend_Http)->getXRechnungView($node->getId());
        $html = ob_get_clean();
        $this->assertStringContainsString($buyRef, $html);
    }

    public function testPositionGrossDifferentTaxRates(): void
    {
        $product1 = $this->_createProduct();

        $positions = [
            new SMDPI([
                SMDPI::FLD_TITLE => 'pos 1',
                SMDPI::FLD_PRODUCT_ID => $product1->getId(),
                SMDPI::FLD_QUANTITY => 1,
                SMDPI::FLD_UNIT_PRICE => 10,
                SMDPI::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_GROSS,
                SMDPI::FLD_POSITION_DISCOUNT_TYPE => Sales_Config::INVOICE_DISCOUNT_SUM,
                SMDPI::FLD_POSITION_DISCOUNT_SUM => 1.01,
                SMDPI::FLD_SALES_TAX_RATE => Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX},
            ], true),
            new SMDPI([
                SMDPI::FLD_TITLE => 'pos 2',
                SMDPI::FLD_PRODUCT_ID => $product1->getId(),
                SMDPI::FLD_QUANTITY => 1,
                SMDPI::FLD_UNIT_PRICE => 5,
                SMDPI::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_GROSS,
                SMDPI::FLD_POSITION_DISCOUNT_TYPE => Sales_Config::INVOICE_DISCOUNT_SUM,
                SMDPI::FLD_POSITION_DISCOUNT_SUM => 0.5,
                SMDPI::FLD_SALES_TAX_RATE => 7.0,
                SMDPI::FLD_SALES_TAX => 4.5 - round(4.5/1.07, 2),
            ], true)
        ];
        $invoice = $this->_createUblInvoice($positions, [
            SMDI::FLD_INVOICE_DISCOUNT_TYPE => Sales_Config::INVOICE_DISCOUNT_SUM,
            SMDI::FLD_INVOICE_DISCOUNT_SUM => 0.01
        ]);
        $invoice->{SMDI::FLD_INVOICE_STATUS} = SMDI::STATUS_BOOKED;
        /** @var SMDI $invoice */
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);
        $this->_assertUblXml($invoice,
            round(8.99/(1 + Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX} / 100), 2)
            + round(4.5/1.07, 2) - 0.01
            , 13.48);
    }

    public function testPMCMandate(): void
    {
        $division = Sales_Controller_Division::getInstance()->get(Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION});
        $division->{Sales_Model_Division::FLD_SEPA_CREDITOR_ID} = Tinebase_Record_Abstract::generateUID();
        Sales_Controller_Division::getInstance()->update($division);

        $product1 = $this->_createProduct();
        $customer = $this->_createCustomer([
            Sales_Model_Address::FLD_PREFIX2 => 'pre2',
            Sales_Model_Address::FLD_PREFIX3 => 'pre3',
        ]);

        $positions = [
            new SMDPI([
                SMDPI::FLD_TITLE => 'pos 1',
                SMDPI::FLD_PRODUCT_ID => $product1->getId(),
                SMDPI::FLD_QUANTITY => 1,
                SMDPI::FLD_UNIT_PRICE => 10,
                SMDPI::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_NET,
                SMDPI::FLD_SALES_TAX_RATE => Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX},
            ], true),
        ];
        $invoice = $this->_createUblInvoice($positions, [
            Sales_Model_Document_Invoice::FLD_PAYMENT_MEANS => new Tinebase_Record_RecordSet(Sales_Model_PaymentMeans::class, [
                new Sales_Model_PaymentMeans([
                    Sales_Model_PaymentMeans::FLD_PAYMENT_MEANS_CODE => Sales_Controller_EDocument_PaymentMeansCode::getInstance()->getAll()->find(Sales_Model_EDocument_PaymentMeansCode::FLD_CODE, '59')->getId(),
                    Sales_Model_PaymentMeans::FLD_DEFAULT => true,
                    Sales_Model_PaymentMeans::FLD_CONFIG_CLASS => Sales_Model_EDocument_PMC_PaymentMandate::class,
                    Sales_Model_PaymentMeans::FLD_CONFIG => new Sales_Model_EDocument_PMC_PaymentMandate([
                        Sales_Model_EDocument_PMC_PaymentMandate::FLD_MANDATE_ID => 'foo',
                        Sales_Model_EDocument_PMC_PaymentMandate::FLD_PAYER_IBAN => 'DE02500105170137075030',
                    ]),
                ])
            ])
        ], $customer);
        $invoice->{SMDI::FLD_INVOICE_STATUS} = SMDI::STATUS_BOOKED;
        /** @var SMDI $invoice */
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);
        $xml = $this->_assertUblXml($invoice, 10, round(10 * (1 + Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX} / 100), 2));

        $this->assertIsArray($customerPartyName = $xml->xpath('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:Contact/cbc:Name'));
        $this->assertSame('pre3' . PHP_EOL . 'pre2', (string)$customerPartyName[0]);
    }
}
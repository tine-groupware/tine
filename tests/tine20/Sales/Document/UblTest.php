<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Tinebase_Model_Filter_Abstract as TMFA;
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
            Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::VIEW_CII_SVC} = 'https://edocument-mw.mws-hosting.net/ciiView';
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

    public function testPurchaseInvoiceFromNonEDocument(): void
    {
        $path = Tinebase_FileSystem::getInstance()
                ->getApplicationBasePath(Filemanager_Config::APP_NAME, Tinebase_FileSystem::FOLDER_TYPE_SHARED) . '/unittest';
        Tinebase_FileSystem::getInstance()->mkdir($path);
        fwrite(
            $fh = Tinebase_FileSystem::getInstance()->fopen($path .  '/test.pdf', 'w'),
                file_get_contents(__FILE__));
        Tinebase_FileSystem::getInstance()->fclose($fh);

        $pInvoice = Sales_Controller_Document_PurchaseInvoice::getInstance()->importPurchaseInvoice(
            new Tinebase_Model_FileLocation([
                Tinebase_Model_FileLocation::FLD_MODEL_NAME => Filemanager_Model_FileLocation::class,
                Tinebase_Model_FileLocation::FLD_LOCATION =>
                    new Filemanager_Model_FileLocation([
                        Filemanager_Model_FileLocation::FLD_FM_PATH => '/shared/unittest/test.pdf',
                    ]),
            ]), importNonEDocument: true
        );

        $this->assertNull($pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_DOCUMENT_NUMBER});
        $this->assertNull($pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_DUE_AT});
        $this->assertSame(1, $pInvoice->attachments->count());
    }

    public function testReadPdfInvoiceOldVersion(): void
    {
        $pi = $this->readPIFromPdf(__DIR__ . '/files/ZUGFeRD-Example.pdf');
        $this->assertSame('2021_10', $pi->{Sales_Model_Document_PurchaseInvoice::FLD_EXTERNAL_INVOICE_NUMBER});
    }

    protected function readPIFromPdf(string $filePath): Sales_Model_Document_PurchaseInvoice
    {
        $path = Tinebase_FileSystem::getInstance()
                ->getApplicationBasePath(Filemanager_Config::APP_NAME, Tinebase_FileSystem::FOLDER_TYPE_SHARED) . '/unittest';
        Tinebase_FileSystem::getInstance()->mkdir($path);
        fwrite(
            $fh = Tinebase_FileSystem::getInstance()->fopen($path .  '/test.pdf', 'w'),
            file_get_contents($filePath));
        Tinebase_FileSystem::getInstance()->fclose($fh);

        return Sales_Controller_Document_PurchaseInvoice::getInstance()->importPurchaseInvoice(
            new Tinebase_Model_FileLocation([
                Tinebase_Model_FileLocation::FLD_MODEL_NAME => Filemanager_Model_FileLocation::class,
                Tinebase_Model_FileLocation::FLD_LOCATION =>
                    new Filemanager_Model_FileLocation([
                        Filemanager_Model_FileLocation::FLD_FM_PATH => '/shared/unittest/test.pdf',
                    ]),
            ])
        );
    }

    public function testReadPdfInvoice(?Sales_Model_Supplier $existingSupplier = null): void
    {
        $pInvoice = $this->readPIFromPdf(__DIR__ . '/files/XRECHNUNG_Einfach.pdf');

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
        $this->assertNotNull($pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_SUPPLIER_ID});
        $this->assertInstanceOf(Sales_Model_Document_SupplierAddress::class, $pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_SUPPLIER_ID}->postal_id);
        $this->assertNotNull($pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_SUPPLIER_ID}->postal_id);
        $this->assertSame('Max@Mustermann.de', $pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_SUPPLIER_ID}->postal_id->{Sales_Model_Address::FLD_EMAIL});

        $pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_PURCHASE_INVOICE_STATUS} =  Sales_Model_Document_PurchaseInvoice::STATUS_APPROVED;
        $pInvoice = Sales_Controller_Document_PurchaseInvoice::getInstance()->update($pInvoice);
        $this->assertNotNull($pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_DOCUMENT_NUMBER});

        $this->assertNotNull($pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_SUPPLIER_ID});
        $this->assertInstanceOf(Sales_Model_Document_SupplierAddress::class, $pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_SUPPLIER_ID}->postal_id);
        $this->assertNotNull($pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_SUPPLIER_ID}->postal_id);
        $this->assertSame('Max@Mustermann.de', $pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_SUPPLIER_ID}->postal_id->{Sales_Model_Address::FLD_EMAIL});
        //$this->assertSame('Max@Mustermann.de', $pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_SUPPLIER_ID}->adr_email);
        if (null === $existingSupplier) {
            $this->assertNull($pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_SUPPLIER_ID}->{Sales_Model_Document_Supplier::FLD_ORIGINAL_ID});
            $this->assertTrue((bool)$pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_SUPPLIER_ID}->{Sales_Model_Document_Supplier::FLD_LOCALLY_CHANGED});
            $this->assertNull($pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_SUPPLIER_ID}->postal_id->{Sales_Model_Document_Supplier::FLD_ORIGINAL_ID});
        } else {
            $this->assertSame($existingSupplier->getId(), $pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_SUPPLIER_ID}->{Sales_Model_Document_Supplier::FLD_ORIGINAL_ID});
            $this->assertSame($existingSupplier->postal_id->getId(), $pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_SUPPLIER_ID}->postal_id->{Sales_Model_Document_Supplier::FLD_ORIGINAL_ID});
            $this->assertTrue((bool)$pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_SUPPLIER_ID}->{Sales_Model_Document_Supplier::FLD_LOCALLY_CHANGED});
        }
    }

    public function testReadPdfInvoiceSupplierVAT(): void
    {
        $this->testReadPdfInvoice(Sales_Controller_Supplier::getInstance()->create(new Sales_Model_Supplier([
            'name' => 'Test Supplier',
            'vatid' => 'DE123456789',
            'postal_id' => new Sales_Model_Address([
                'email' => 'a@b.c',
            ], true),
        ])));
    }

    public function testReadPdfInvoiceSupplierEmail(): void
    {
        $this->testReadPdfInvoice(Sales_Controller_Supplier::getInstance()->create(new Sales_Model_Supplier([
            'name' => 'Test Supplier',
            'postal_id' => new Sales_Model_Address([
                'email' => 'Max@Mustermann.de',
            ], true),
        ])));
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
        $this->assertNotNull($pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_SUPPLIER_ID});
        $this->assertNull($pInvoice->{Sales_Model_Document_PurchaseInvoice::FLD_SUPPLIER_ID}->getId());
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
        $this->assertSame(Sales_Config::INVOICE_DISCOUNT_PERCENTAGE, $invoice->{Sales_Model_Document_Abstract::FLD_INVOICE_DISCOUNT_TYPE}, $invoicePrintR = print_r($invoice->toArray(), true));
        $this->assertSame(10.0, $invoice->{Sales_Model_Document_Abstract::FLD_INVOICE_DISCOUNT_PERCENTAGE}, $invoicePrintR);
        $this->assertSame(0.9, $invoice->{Sales_Model_Document_Abstract::FLD_NET_SUM}, $invoicePrintR);
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

    public function testCustomerDebitorXROverwrite(): void
    {
        $division = $this->makeDefaultDivisonUblReady();

        $bt13 = Sales_Controller_EDocument_XRechnungElement::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_EDocument_XRechnungElement::class, [
                [TMFA::FIELD => Sales_Model_EDocument_XRechnungElement::FLD_BT_NUMBER, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => 'BT-13'],
            ])
        )->getFirstRecord();
        $bt14 = Sales_Controller_EDocument_XRechnungElement::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_EDocument_XRechnungElement::class, [
                [TMFA::FIELD => Sales_Model_EDocument_XRechnungElement::FLD_BT_NUMBER, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => 'BT-14'],
            ])
        )->getFirstRecord();

        $customer = $this->_createCustomer();
        $debitor = $customer->{Sales_Model_Customer::FLD_DEBITORS}->getFirstRecord();
        $debitor->{Sales_Model_Debitor::FLD_EINVOICE_TYPE} = Sales_Model_Einvoice_XRechnung::class;
        $debitor->{Sales_Model_Debitor::FLD_EINVOICE_CONFIG} = new Sales_Model_Einvoice_XRechnung([
            Sales_Model_Einvoice_XRechnung::FLD_OVERWRITES => new Tinebase_Record_RecordSet(Sales_Model_Einvoice_XRechnungOverwrite::class, [
                new Sales_Model_Einvoice_XRechnungOverwrite([
                    Sales_Model_Einvoice_XRechnungOverwrite::FLD_VALUE => 'A',
                    Sales_Model_Einvoice_XRechnungOverwrite::FLD_DESCRIPTION => 'Overwrite for BT-13',
                    Sales_Model_Einvoice_XRechnungOverwrite::FLD_ACTION => Sales_Model_Einvoice_XRechnungOverwrite::ACTION_STATIC,
                    Sales_Model_Einvoice_XRechnungOverwrite::FLD_XRECHNUNG_ELEMENT => $bt13->getId(),
                ]),
                new Sales_Model_Einvoice_XRechnungOverwrite([
                    Sales_Model_Einvoice_XRechnungOverwrite::FLD_VALUE => 'B',
                    Sales_Model_Einvoice_XRechnungOverwrite::FLD_DESCRIPTION => 'Overwrite for BT-14',
                    Sales_Model_Einvoice_XRechnungOverwrite::FLD_ACTION => Sales_Model_Einvoice_XRechnungOverwrite::ACTION_STATIC,
                    Sales_Model_Einvoice_XRechnungOverwrite::FLD_XRECHNUNG_ELEMENT => $bt14->getId(),
                ]),
            ]),
        ]);
        $customer = Sales_Controller_Customer::getInstance()->update($customer);

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

        $invoice = $this->_createUblInvoice($positions, invoiceData: [
            SMDI::FLD_SERVICE_PERIOD_START => Tinebase_DateTime::today()->subDay(5),
            SMDI::FLD_SERVICE_PERIOD_END   => Tinebase_DateTime::today()->subDay(1),
        ], customer: $customer);
        $invoice->{SMDI::FLD_INVOICE_STATUS} = SMDI::STATUS_BOOKED;

        // Update the invoice's debitor to change the BT-13 overwrite from 'A' to 'Z'
        /** @var Sales_Model_Document_Debitor $docDebitor */
        $docDebitor = $invoice->{Sales_Model_Document_Abstract::FLD_DEBITOR_ID};
        $this->assertSame(Sales_Model_Einvoice_XRechnung::class, $docDebitor->{Sales_Model_Debitor::FLD_EINVOICE_TYPE});
        $this->assertCount(2, $docDebitor->{Sales_Model_Debitor::FLD_EINVOICE_CONFIG}->{Sales_Model_Einvoice_XRechnung::FLD_OVERWRITES});
        $this->assertSame('A', $docDebitor->{Sales_Model_Debitor::FLD_EINVOICE_CONFIG}->{Sales_Model_Einvoice_XRechnung::FLD_OVERWRITES}
            ->find(Sales_Model_Einvoice_XRechnungOverwrite::FLD_XRECHNUNG_ELEMENT, $bt13->getId())
            ->{Sales_Model_Einvoice_XRechnungOverwrite::FLD_VALUE});
        $docDebitor->{Sales_Model_Debitor::FLD_EINVOICE_CONFIG}->{Sales_Model_Einvoice_XRechnung::FLD_OVERWRITES}
            ->find(Sales_Model_Einvoice_XRechnungOverwrite::FLD_XRECHNUNG_ELEMENT, $bt13->getId())
            ->{Sales_Model_Einvoice_XRechnungOverwrite::FLD_VALUE} = 'Z';

        $btElements = [
            'BT-3'  => ['label' => 'invoice_type_code', 'value' => 'overwrite-invoice_type_code'],
            'BT-7'  => ['label' => 'value_added_tax_point_date', 'value' => '2024-01-15'],
            'BT-8'  => ['label' => 'value_added_tax_point_date_code', 'value' => 'overwrite-value_added_tax_point_date_code'],
            'BT-10' => ['label' => 'buyer_reference', 'value' => 'overwrite-buyer_reference'],
            'BT-11' => ['label' => 'document_reference', 'value' => 'overwrite-document_reference'],
            'BT-12' => ['label' => 'document_reference', 'value' => 'overwrite-document_reference'],
            'BT-15' => ['label' => 'receiving_advice_reference', 'value' => 'overwrite-receiving_advice_reference'],
            'BT-16' => ['label' => 'despatch_advice_reference', 'value' => 'overwrite-despatch_advice_reference'],
            'BT-18' => ['label' => 'invoiced_object_identifier', 'value' => 'overwrite-invoiced_object_identifier'],
            'BT-19' => ['label' => 'buyer_accounting_reference', 'value' => 'overwrite-buyer_accounting_reference'],
            'BT-20' => ['label' => 'payment_terms', 'value' => 'overwrite-payment_terms'],
            'BT-25' => ['label' => 'document_reference', 'value' => 'overwrite-document_reference'],
            'BT-26' => ['label' => 'preceding_invoice_issue_date', 'value' => '2024-01-10'],
            'BT-27' => ['label' => 'seller_name', 'value' => 'overwrite-seller_name'],
            'BT-28' => ['label' => 'seller_trading_name', 'value' => 'overwrite-seller_trading_name'],
            'BT-33' => ['label' => 'seller_additional_legal_information', 'value' => 'overwrite-seller_additional_legal_information'],
            'BT-35' => ['label' => 'seller_address_line_1', 'value' => 'overwrite-seller_address_line_1'],
            'BT-36' => ['label' => 'seller_address_line_2', 'value' => 'overwrite-seller_address_line_2'],
            'BT-37' => ['label' => 'seller_city', 'value' => 'overwrite-seller_city'],
            'BT-38' => ['label' => 'seller_post_code', 'value' => 'overwrite-seller_post_code'],
            'BT-39' => ['label' => 'seller_country_subdivision', 'value' => 'overwrite-seller_country_subdivision'],
            'BT-40' => ['label' => 'seller_country_code', 'value' => 'overwrite-seller_country_code'],
            'BT-41' => ['label' => 'seller_contact_point', 'value' => 'overwrite-seller_contact_point'],
            'BT-42' => ['label' => 'seller_contact_telephone_number', 'value' => 'overwrite-seller_contact_telephone_number'],
            'BT-43' => ['label' => 'seller_contact_email_address', 'value' => 'overwrite-seller_contact_email_address'],
            'BT-44' => ['label' => 'buyer_name', 'value' => 'overwrite-buyer_name'],
            'BT-45' => ['label' => 'buyer_trading_name', 'value' => 'overwrite-buyer_trading_name'],
            'BT-46' => ['label' => 'buyer_identifier', 'value' => 'overwrite-buyer_identifier'],
            'BT-50' => ['label' => 'buyer_address_line_1', 'value' => 'overwrite-buyer_address_line_1'],
            'BT-51' => ['label' => 'buyer_address_line_2', 'value' => 'overwrite-buyer_address_line_2'],
            'BT-52' => ['label' => 'buyer_city', 'value' => 'overwrite-buyer_city'],
            'BT-53' => ['label' => 'buyer_post_code', 'value' => 'overwrite-buyer_post_code'],
            'BT-54' => ['label' => 'buyer_country_subdivision', 'value' => 'overwrite-buyer_country_subdivision'],
            'BT-55' => ['label' => 'buyer_country_code', 'value' => 'overwrite-buyer_country_code'],
            'BT-56' => ['label' => 'buyer_contact_point', 'value' => 'overwrite-buyer_contact_point'],
            'BT-57' => ['label' => 'buyer_contact_telephone_number', 'value' => 'overwrite-buyer_contact_telephone_number'],
            'BT-58' => ['label' => 'buyer_contact_email_address', 'value' => 'overwrite-buyer_contact_email_address'],
            'BT-59' => ['label' => 'payee_name', 'value' => 'overwrite-payee_name'],
            'BT-60' => ['label' => 'payee_identifier', 'value' => 'overwrite-payee_identifier'],
            'BT-64' => ['label' => 'tax_representative_address_line_1', 'value' => 'overwrite-tax_representative_address_line_1'],
            'BT-65' => ['label' => 'tax_representative_address_line_2', 'value' => 'overwrite-tax_representative_address_line_2'],
            'BT-66' => ['label' => 'tax_representative_city', 'value' => 'overwrite-tax_representative_city'],
            'BT-67' => ['label' => 'tax_representative_post_code', 'value' => 'overwrite-tax_representative_post_code'],
            'BT-68' => ['label' => 'tax_representative_country_subdivision', 'value' => 'overwrite-tax_representative_country_subdivision'],
            'BT-69' => ['label' => 'tax_representative_country_code', 'value' => 'overwrite-tax_representative_country_code'],
            'BT-70' => ['label' => 'deliver_to_party_name', 'value' => 'overwrite-deliver_to_party_name'],
            'BT-71' => ['label' => 'deliver_to_location_identifier', 'value' => 'overwrite-deliver_to_location_identifier'],
            'BT-72' => ['label' => 'actual_delivery_date', 'value' => '2024-02-01'],
            'BT-75' => ['label' => 'deliver_to_address_line_1', 'value' => 'overwrite-deliver_to_address_line_1'],
            'BT-76' => ['label' => 'deliver_to_address_line_2', 'value' => 'overwrite-deliver_to_address_line_2'],
            'BT-77' => ['label' => 'deliver_to_city', 'value' => 'overwrite-deliver_to_city'],
            'BT-78' => ['label' => 'deliver_to_post_code', 'value' => 'overwrite-deliver_to_post_code'],
            'BT-79' => ['label' => 'deliver_to_country_subdivision', 'value' => 'overwrite-deliver_to_country_subdivision'],
            'BT-80' => ['label' => 'deliver_to_country_code', 'value' => 'overwrite-deliver_to_country_code'],
            'BT-81' => ['label' => 'payment_means_type_code', 'value' => 'overwrite-payment_means_type_code'],
            'BT-82' => ['label' => 'payment_means_text', 'value' => 'overwrite-payment_means_text'],
            'BT-87' => ['label' => 'payment_card_primary_account_number', 'value' => 'overwrite-payment_card_primary_account_number'],
            'BT-88' => ['label' => 'payment_card_holder_name', 'value' => 'overwrite-payment_card_holder_name'],
            'BT-89' => ['label' => 'mandate_reference_identifier', 'value' => 'overwrite-mandate_reference_identifier'],
            'BT-90' => ['label' => 'bank_assigned_creditor_identifier', 'value' => 'overwrite-bank_assigned_creditor_identifier'],
            'BT-91' => ['label' => 'debited_account_identifier', 'value' => 'overwrite-debited_account_identifier'],
            'BT-113' => ['label' => 'paid_amount', 'value' => '113.0'],
            'BT-115' => ['label' => 'amount_due_for_payment', 'value' => '115.0'],
            'BT-162' => ['label' => 'seller_address_line_3', 'value' => 'overwrite-seller_address_line_3'],
            'BT-163' => ['label' => 'buyer_address_line_3', 'value' => 'overwrite-buyer_address_line_3'],
            'BT-164' => ['label' => 'tax_representative_address_line_3', 'value' => 'overwrite-tax_representative_address_line_3'],
            'BT-165' => ['label' => 'deliver_to_address_line_3', 'value' => 'overwrite-deliver_to_address_line_3'],
        ];

        $btIds = [];
        foreach ($btElements as $btNum => $btData) {
            $bt = Sales_Controller_EDocument_XRechnungElement::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_EDocument_XRechnungElement::class, [
                    [TMFA::FIELD => Sales_Model_EDocument_XRechnungElement::FLD_BT_NUMBER, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $btNum],
                ])
            )->getFirstRecord();
            $btIds[$btNum] = $bt->getId();

            $docDebitor->{Sales_Model_Debitor::FLD_EINVOICE_CONFIG}->{Sales_Model_Einvoice_XRechnung::FLD_OVERWRITES}
                ->addRecord(new Sales_Model_Einvoice_XRechnungOverwrite([
                    Sales_Model_Einvoice_XRechnungOverwrite::FLD_VALUE => $btData['value'],
                    Sales_Model_Einvoice_XRechnungOverwrite::FLD_DESCRIPTION => "Overwrite for {$btNum}",
                    Sales_Model_Einvoice_XRechnungOverwrite::FLD_ACTION => Sales_Model_Einvoice_XRechnungOverwrite::ACTION_STATIC,
                    Sales_Model_Einvoice_XRechnungOverwrite::FLD_XRECHNUNG_ELEMENT => $bt->getId(),
                ]));
        }

        $invoice->{Sales_Model_Document_Abstract::FLD_DEBITOR_ID} = $docDebitor;
        /** @var SMDI $invoice */
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);

        $xml = new SimpleXMLElement($invoice->toUbl());
        $xml->registerXPathNamespace('ubl', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');
        $xml->registerXPathNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xml->registerXPathNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        //$xml = $this->_assertUblXml($invoice, 10, round(10 * (1 + Tinebase_Config::getInstance()->{Tinebase_Config::SALES_TAX} / 100), 2));

        // BT-3
        $this->assertIsArray($invoiceTypeCode = $xml->xpath('/ubl:Invoice/cbc:InvoiceTypeCode'));
        $this->assertSame('overwrite-invoice_type_code', (string)$invoiceTypeCode[0]);

        // BT-7
        $this->assertIsArray($taxPointDate = $xml->xpath('/ubl:Invoice/cbc:TaxPointDate'));
        $this->assertSame('2024-01-15', (string)$taxPointDate[0]);

        // BT-8
        $this->assertIsArray($invoicePeriod = $xml->xpath('/ubl:Invoice/cac:InvoicePeriod/cbc:DescriptionCode'));
        $this->assertCount(1, $invoicePeriod);
        $this->assertSame('overwrite-value_added_tax_point_date_code', (string)$invoicePeriod[0]);

        // BT-10
        $this->assertIsArray($buyerRef = $xml->xpath('/ubl:Invoice/cbc:BuyerReference'));
        $this->assertSame('overwrite-buyer_reference', (string)$buyerRef[0]);

        // BT-11
        $this->assertIsArray($projectRef = $xml->xpath('/ubl:Invoice/cac:ProjectReference/cbc:ID'));
        $this->assertCount(1, $projectRef);
        $this->assertSame('overwrite-document_reference', (string)$projectRef[0]);

        // BT-12
        $this->assertIsArray($contractRef = $xml->xpath('/ubl:Invoice/cac:ContractDocumentReference/cbc:ID'));
        $this->assertCount(1, $contractRef);
        $this->assertSame('overwrite-document_reference', (string)$contractRef[0]);

        // BT-13
        $this->assertIsArray($orderRef = $xml->xpath('/ubl:Invoice/cac:OrderReference/cbc:ID'));
        $this->assertSame('Z', (string)$orderRef[0]);

        // BT-14
        $this->assertIsArray($orderRef = $xml->xpath('/ubl:Invoice/cac:OrderReference/cbc:SalesOrderID'));
        $this->assertSame('B', (string)$orderRef[0]);

        // BT-15
        $this->assertIsArray($receiptRef = $xml->xpath('/ubl:Invoice/cac:ReceiptDocumentReference/cbc:ID'));
        $this->assertCount(1, $receiptRef);
        $this->assertSame('overwrite-receiving_advice_reference', (string)$receiptRef[0]);

        // BT-16
        $this->assertIsArray($despatchRef = $xml->xpath('/ubl:Invoice/cac:DespatchDocumentReference/cbc:ID'));
        $this->assertCount(1, $despatchRef);
        $this->assertSame('overwrite-despatch_advice_reference', (string)$despatchRef[0]);

        // BT-18
        $this->assertIsArray($additionalDocRef = $xml->xpath('/ubl:Invoice/cac:AdditionalDocumentReference/cbc:ID'));
        $this->assertCount(1, $additionalDocRef);
        $this->assertSame('overwrite-invoiced_object_identifier', (string)$additionalDocRef[0]);

        // BT-19
        $this->assertIsArray($accountingCost = $xml->xpath('/ubl:Invoice/cbc:AccountingCost'));
        $this->assertSame('overwrite-buyer_accounting_reference', (string)$accountingCost[0]);

        // BT-20
        $this->assertIsArray($paymentTerms = $xml->xpath('/ubl:Invoice/cac:PaymentTerms/cbc:Note'));
        $this->assertCount(1, $paymentTerms);
        $this->assertSame('overwrite-payment_terms', (string)$paymentTerms[0]);

        // BT-25
        $this->assertIsArray($billingDocRef = $xml->xpath('/ubl:Invoice/cac:BillingReference/cac:InvoiceDocumentReference/cbc:ID'));
        $this->assertCount(1, $billingDocRef);
        $this->assertSame('overwrite-document_reference', (string)$billingDocRef[0]);

        // BT-26
        $this->assertIsArray($precedingDate = $xml->xpath('/ubl:Invoice/cac:BillingReference/cac:InvoiceDocumentReference/cbc:IssueDate'));
        $this->assertCount(1, $precedingDate);
        $this->assertSame('2024-01-10', (string)$precedingDate[0]);

        // BT-27
        $this->assertIsArray($sellerName = $xml->xpath('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName'));
        $this->assertSame('overwrite-seller_name', (string)$sellerName[0]);

        // BT-28
        $this->assertIsArray($sellerName = $xml->xpath('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyName/cbc:Name'));
        $this->assertSame('overwrite-seller_trading_name', (string)$sellerName[0]);

        // BT-33
        $this->assertIsArray($sellerLegalInfo = $xml->xpath('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:CompanyLegalForm'));
        $this->assertSame('overwrite-seller_additional_legal_information', (string)$sellerLegalInfo[0]);

        // BT-35
        $this->assertIsArray($sellerStreet = $xml->xpath('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:StreetName'));
        $this->assertSame('overwrite-seller_address_line_1', (string)$sellerStreet[0]);

        // BT-36
        $this->assertIsArray($sellerAddlStreet = $xml->xpath('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:AdditionalStreetName'));
        $this->assertSame('overwrite-seller_address_line_2', (string)$sellerAddlStreet[0]);

        // BT-37
        $this->assertIsArray($sellerCity = $xml->xpath('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:CityName'));
        $this->assertSame('overwrite-seller_city', (string)$sellerCity[0]);

        // BT-38
        $this->assertIsArray($sellerPostCode = $xml->xpath('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:PostalZone'));
        $this->assertSame('overwrite-seller_post_code', (string)$sellerPostCode[0]);

        // BT-39
        $this->assertIsArray($sellerSubdivision = $xml->xpath('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cbc:CountrySubentity'));
        $this->assertSame('overwrite-seller_country_subdivision', (string)$sellerSubdivision[0]);

        // BT-40
        $this->assertIsArray($sellerCountry = $xml->xpath('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cac:Country/cbc:IdentificationCode'));
        $this->assertCount(1, $sellerCountry);
        $this->assertSame('overwrite-seller_country_code', (string)$sellerCountry[0]);

        // BT-41
        $this->assertIsArray($sellerContact = $xml->xpath('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:Name'));
        $this->assertSame('overwrite-seller_contact_point', (string)$sellerContact[0]);

        // BT-42
        $this->assertIsArray($sellerTel = $xml->xpath('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:Telephone'));
        $this->assertSame('overwrite-seller_contact_telephone_number', (string)$sellerTel[0]);

        // BT-43
        $this->assertIsArray($sellerEmail = $xml->xpath('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:Contact/cbc:ElectronicMail'));
        $this->assertSame('overwrite-seller_contact_email_address', (string)$sellerEmail[0]);

        // BT-44
        $this->assertIsArray($buyerName = $xml->xpath('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName'));
        $this->assertSame('overwrite-buyer_name', (string)$buyerName[0]);

        // BT-45
        $this->assertIsArray($buyerName = $xml->xpath('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyName/cbc:Name'));
        $this->assertSame('overwrite-buyer_trading_name', (string)$buyerName[0]);

        // BT-46
        $this->assertIsArray($buyerName = $xml->xpath('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID'));
        $this->assertSame('overwrite-buyer_identifier', (string)$buyerName[0]);

        // BT-50
        $this->assertIsArray($buyerStreet = $xml->xpath('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:StreetName'));
        $this->assertSame('overwrite-buyer_address_line_1', (string)$buyerStreet[0]);

        // BT-51
        $this->assertIsArray($buyerAddlStreet = $xml->xpath('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:AdditionalStreetName'));
        $this->assertSame('overwrite-buyer_address_line_2', (string)$buyerAddlStreet[0]);

        // BT-52
        $this->assertIsArray($buyerCity = $xml->xpath('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:CityName'));
        $this->assertSame('overwrite-buyer_city', (string)$buyerCity[0]);

        // BT-53
        $this->assertIsArray($buyerPostCode = $xml->xpath('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:PostalZone'));
        $this->assertSame('overwrite-buyer_post_code', (string)$buyerPostCode[0]);

        // BT-54
        $this->assertIsArray($buyerSubdivision = $xml->xpath('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cbc:CountrySubentity'));
        $this->assertSame('overwrite-buyer_country_subdivision', (string)$buyerSubdivision[0]);

        // BT-55
        $this->assertIsArray($buyerCountry = $xml->xpath('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cac:Country/cbc:IdentificationCode'));
        $this->assertCount(1, $buyerCountry);
        $this->assertSame('overwrite-buyer_country_code', (string)$buyerCountry[0]);

        // BT-56
        $this->assertIsArray($value = $xml->xpath('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:Contact/cbc:Name'));
        $this->assertCount(1, $value);
        $this->assertSame('overwrite-buyer_contact_point', (string)$value[0]);

        // BT-57
        $this->assertIsArray($value = $xml->xpath('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:Contact/cbc:Telephone'));
        $this->assertCount(1, $value);
        $this->assertSame('overwrite-buyer_contact_telephone_number', (string)$value[0]);

        // BT-58
        $this->assertIsArray($value = $xml->xpath('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:Contact/cbc:ElectronicMail'));
        $this->assertCount(1, $value);
        $this->assertSame('overwrite-buyer_contact_email_address', (string)$value[0]);

        // BT-59
        $this->assertIsArray($value = $xml->xpath('/ubl:Invoice/cac:PayeeParty/cac:PartyName/cbc:Name'));
        $this->assertCount(1, $value);
        $this->assertSame('overwrite-payee_name', (string)$value[0]);

        // BT-60
        $this->assertIsArray($payeeId = $xml->xpath('/ubl:Invoice/cac:PayeeParty/cac:PartyIdentification/cbc:ID[not(@schemeID = \'SEPA\')]'));
        $this->assertCount(1, $payeeId);
        $this->assertSame('overwrite-payee_identifier', (string)$payeeId[0]);

        // BT-64
        $this->assertIsArray($taxRepStreet = $xml->xpath('/ubl:Invoice/cac:TaxRepresentativeParty/cac:PostalAddress/cbc:StreetName'));
        $this->assertSame('overwrite-tax_representative_address_line_1', (string)$taxRepStreet[0]);

        // BT-65
        $this->assertIsArray($taxRepAddlStreet = $xml->xpath('/ubl:Invoice/cac:TaxRepresentativeParty/cac:PostalAddress/cbc:AdditionalStreetName'));
        $this->assertSame('overwrite-tax_representative_address_line_2', (string)$taxRepAddlStreet[0]);

        // BT-66
        $this->assertIsArray($taxRepCity = $xml->xpath('/ubl:Invoice/cac:TaxRepresentativeParty/cac:PostalAddress/cbc:CityName'));
        $this->assertSame('overwrite-tax_representative_city', (string)$taxRepCity[0]);

        // BT-67
        $this->assertIsArray($taxRepPostCode = $xml->xpath('/ubl:Invoice/cac:TaxRepresentativeParty/cac:PostalAddress/cbc:PostalZone'));
        $this->assertSame('overwrite-tax_representative_post_code', (string)$taxRepPostCode[0]);

        // BT-68
        $this->assertIsArray($taxRepSubdivision = $xml->xpath('/ubl:Invoice/cac:TaxRepresentativeParty/cac:PostalAddress/cbc:CountrySubentity'));
        $this->assertSame('overwrite-tax_representative_country_subdivision', (string)$taxRepSubdivision[0]);

        // BT-69
        $this->assertIsArray($taxRepCountry = $xml->xpath('/ubl:Invoice/cac:TaxRepresentativeParty/cac:PostalAddress/cac:Country/cbc:IdentificationCode'));
        $this->assertCount(1, $taxRepCountry);
        $this->assertSame('overwrite-tax_representative_country_code', (string)$taxRepCountry[0]);

        // BT-70
        $this->assertIsArray($deliverName = $xml->xpath('/ubl:Invoice/cac:Delivery/cac:DeliveryParty/cac:PartyName/cbc:Name'));
        $this->assertCount(1, $deliverName);
        $this->assertSame('overwrite-deliver_to_party_name', (string)$deliverName[0]);

        // BT-71
        $this->assertIsArray($deliverLocId = $xml->xpath('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cbc:ID'));
        $this->assertCount(1, $deliverLocId);
        $this->assertSame('overwrite-deliver_to_location_identifier', (string)$deliverLocId[0]);

        // BT-72
        $this->assertIsArray($deliverDate = $xml->xpath('/ubl:Invoice/cac:Delivery/cbc:ActualDeliveryDate'));
        $this->assertCount(1, $deliverDate);
        $this->assertSame('2024-02-01', (string)$deliverDate[0]);

        // BT-75
        $this->assertIsArray($deliverStreet = $xml->xpath('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:StreetName'));
        $this->assertSame('overwrite-deliver_to_address_line_1', (string)$deliverStreet[0]);

        // BT-76
        $this->assertIsArray($deliverAddlStreet = $xml->xpath('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:AdditionalStreetName'));
        $this->assertSame('overwrite-deliver_to_address_line_2', (string)$deliverAddlStreet[0]);

        // BT-77
        $this->assertIsArray($deliverCity = $xml->xpath('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:CityName'));
        $this->assertSame('overwrite-deliver_to_city', (string)$deliverCity[0]);

        // BT-78
        $this->assertIsArray($deliverPostCode = $xml->xpath('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:PostalZone'));
        $this->assertSame('overwrite-deliver_to_post_code', (string)$deliverPostCode[0]);

        // BT-79
        $this->assertIsArray($deliverSubdivision = $xml->xpath('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cbc:CountrySubentity'));
        $this->assertSame('overwrite-deliver_to_country_subdivision', (string)$deliverSubdivision[0]);

        // BT-80
        $this->assertIsArray($deliverCountry = $xml->xpath('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cac:Country/cbc:IdentificationCode'));
        $this->assertCount(1, $deliverCountry);
        $this->assertSame('overwrite-deliver_to_country_code', (string)$deliverCountry[0]);

        // BT-81
        $this->assertIsArray($paymentMeansCode = $xml->xpath('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentMeansCode'));
        $this->assertSame('overwrite-payment_means_type_code', (string)$paymentMeansCode[0]);

        // BT-82
        $this->assertIsArray($paymentMeansName = $xml->xpath('/ubl:Invoice/cac:PaymentMeans/cbc:PaymentMeansCode/@name'));
        $this->assertSame('overwrite-payment_means_text', (string)$paymentMeansName[0]);

        // BT-87
        $this->assertIsArray($cardAcct = $xml->xpath('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:PrimaryAccountNumberID'));
        $this->assertSame('overwrite-payment_card_primary_account_number', (string)$cardAcct[0]);

        // BT-88
        $this->assertIsArray($cardHolder = $xml->xpath('/ubl:Invoice/cac:PaymentMeans/cac:CardAccount/cbc:HolderName'));
        $this->assertSame('overwrite-payment_card_holder_name', (string)$cardHolder[0]);

        // BT-89
        $this->assertIsArray($mandateId = $xml->xpath('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cbc:ID'));
        $this->assertCount(1, $mandateId);
        $this->assertSame('overwrite-mandate_reference_identifier', (string)$mandateId[0]);

        // BT-90
        $this->assertIsArray($creditorId = $xml->xpath('/ubl:Invoice/cac:PayeeParty/cac:PartyIdentification/cbc:ID[@schemeID="SEPA"]'));
        $this->assertCount(1, $creditorId);
        $this->assertSame('overwrite-bank_assigned_creditor_identifier', (string)$creditorId[0]);

        // BT-91
        $this->assertIsArray($debitedAcct = $xml->xpath('/ubl:Invoice/cac:PaymentMeans/cac:PaymentMandate/cac:PayerFinancialAccount/cbc:ID'));
        $this->assertCount(1, $debitedAcct);
        $this->assertSame('overwrite-debited_account_identifier', (string)$debitedAcct[0]);

        // BT-113
        $this->assertIsArray($prepaidAmt = $xml->xpath('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:PrepaidAmount'));
        $this->assertSame('113.0', (string)$prepaidAmt[0]);

        // BT-115
        $this->assertIsArray($payableAmt = $xml->xpath('/ubl:Invoice/cac:LegalMonetaryTotal/cbc:PayableAmount'));
        $this->assertSame('115.0', (string)$payableAmt[0]);

        // BT-162
        $this->assertIsArray($sellerAddrLine3 = $xml->xpath('/ubl:Invoice/cac:AccountingSupplierParty/cac:Party/cac:PostalAddress/cac:AddressLine/cbc:Line'));
        $this->assertCount(1, $sellerAddrLine3);
        $this->assertSame('overwrite-seller_address_line_3', (string)$sellerAddrLine3[0]);

        // BT-163
        $this->assertIsArray($buyerAddrLine3 = $xml->xpath('/ubl:Invoice/cac:AccountingCustomerParty/cac:Party/cac:PostalAddress/cac:AddressLine/cbc:Line'));
        $this->assertCount(1, $buyerAddrLine3);
        $this->assertSame('overwrite-buyer_address_line_3', (string)$buyerAddrLine3[0]);

        // BT-164
        $this->assertIsArray($taxRepAddrLine3 = $xml->xpath('/ubl:Invoice/cac:TaxRepresentativeParty/cac:PostalAddress/cac:AddressLine/cbc:Line'));
        $this->assertCount(1, $taxRepAddrLine3);
        $this->assertSame('overwrite-tax_representative_address_line_3', (string)$taxRepAddrLine3[0]);

        // BT-165
        $this->assertIsArray($deliverAddrLine3 = $xml->xpath('/ubl:Invoice/cac:Delivery/cac:DeliveryLocation/cac:Address/cac:AddressLine/cbc:Line'));
        $this->assertCount(1, $deliverAddrLine3);
        $this->assertSame('overwrite-deliver_to_address_line_3', (string)$deliverAddrLine3[0]);
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
        (new Sales_Frontend_Http)->getXRechnungView(json_encode((new Tinebase_Model_FileLocation([
            Tinebase_Model_FileLocation::FLD_LOCATION => new Tinebase_Model_FileLocation_RecordAttachment([
                Tinebase_Model_FileLocation_RecordAttachment::FLD_NAME => $node->name,
                Tinebase_Model_FileLocation_RecordAttachment::FLD_RECORD_ID => $invoice->getId(),
                Tinebase_Model_FileLocation_RecordAttachment::FLD_MODEL => SMDI::class,
            ]),
            Tinebase_Model_FileLocation::FLD_MODEL_NAME => Tinebase_Model_FileLocation_RecordAttachment::class,
        ]))->toArray()));
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

    public function testZugferdView(): void
    {
        if (!Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::VIEW_CII_SVC}) {
            $this->markTestSkipped('no edocument view service configured, skipping');
        }
        $path = Tinebase_FileSystem::getInstance()
                ->getApplicationBasePath(Filemanager_Config::APP_NAME, Tinebase_FileSystem::FOLDER_TYPE_SHARED) . '/unittest';
        Tinebase_FileSystem::getInstance()->mkdir($path);
        fwrite(
            $fh = Tinebase_FileSystem::getInstance()->fopen($path . '/zugferd-view-test.pdf', 'w'),
            file_get_contents(__DIR__ . '/files/ZUGFeRD-Example.pdf')
            );
        Tinebase_FileSystem::getInstance()->fclose($fh);

        $node = Tinebase_FileSystem::getInstance()->stat($path . '/zugferd-view-test.pdf');
        $this->assertNotNull($node);

        ob_start();
        (new Sales_Frontend_Http)->getXRechnungView(json_encode((new Tinebase_Model_FileLocation([
            Tinebase_Model_FileLocation::FLD_LOCATION => new Tinebase_Model_FileLocation_TreeNode([
                Tinebase_Model_FileLocation_TreeNode::FLD_NODE_ID => $node->getId(),
            ]),
            Tinebase_Model_FileLocation::FLD_MODEL_NAME => Tinebase_Model_FileLocation_TreeNode::class,
        ]))->toArray()));
        $html = ob_get_clean();
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('DOCTYPE', $html);
    }
}
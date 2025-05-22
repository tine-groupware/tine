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

    public function setUp(): void
    {
        parent::setUp();

        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);
    }

    protected function _createInvoice(array $positions, array $invoiceData = [], ?Sales_Model_Customer $customer = null): SMDI
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
            ], true),
        ];
        $invoice = $this->_createInvoice($positions);

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
            ], true),
        ];
        $customer = $this->_createCustomer(additionalCustomerData: ['discount' => 10]);

        $invoice = $this->_createInvoice($positions, customer: $customer);
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
                SMDPI::FLD_POSITION_DISCOUNT_TYPE => Sales_Config::INVOICE_DISCOUNT_SUM,
                SMDPI::FLD_POSITION_DISCOUNT_SUM => 0.01
            ], true),
            new SMDPI([
                SMDPI::FLD_TITLE => 'pos 2',
                SMDPI::FLD_PRODUCT_ID => $product1->getId(),
                SMDPI::FLD_QUANTITY => 1,
                SMDPI::FLD_UNIT_PRICE => 5,
                SMDPI::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_NET,
                SMDPI::FLD_POSITION_DISCOUNT_TYPE => Sales_Config::INVOICE_DISCOUNT_SUM,
                SMDPI::FLD_POSITION_DISCOUNT_SUM => 0.1
            ], true)
        ];
        $invoice = $this->_createInvoice($positions);
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

        $invoice = $this->_createInvoice($positions, [SMDI::FLD_VAT_PROCEDURE => Sales_Config::VAT_PROCEDURE_REVERSE_CHARGE]);
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
        $invoice = $this->_createInvoice($positions, [SMDI::FLD_VAT_PROCEDURE => Sales_Config::VAT_PROCEDURE_FREE_EXPORT_ITEM]);
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
        $invoice = $this->_createInvoice($positions, [
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
        $invoice = $this->_createInvoice($positions, [
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
                SMDPI::FLD_POSITION_DISCOUNT_SUM => 0.01
            ], true),
            new SMDPI([
                SMDPI::FLD_TITLE => 'pos 2',
                SMDPI::FLD_PRODUCT_ID => $product1->getId(),
                SMDPI::FLD_QUANTITY => 1,
                SMDPI::FLD_UNIT_PRICE => 5,
                SMDPI::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_GROSS,
                SMDPI::FLD_POSITION_DISCOUNT_TYPE => Sales_Config::INVOICE_DISCOUNT_SUM,
                SMDPI::FLD_POSITION_DISCOUNT_SUM => 0.1
            ], true)
        ];
        $invoice = $this->_createInvoice($positions);
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
                SMDPI::FLD_POSITION_DISCOUNT_SUM => 0.01
            ], true),
            new SMDPI([
                SMDPI::FLD_TITLE => 'pos 2',
                SMDPI::FLD_PRODUCT_ID => $product1->getId(),
                SMDPI::FLD_QUANTITY => 1,
                SMDPI::FLD_UNIT_PRICE => 5,
                SMDPI::FLD_UNIT_PRICE_TYPE => Sales_Config::PRICE_TYPE_GROSS,
                SMDPI::FLD_POSITION_DISCOUNT_TYPE => Sales_Config::INVOICE_DISCOUNT_SUM,
                SMDPI::FLD_POSITION_DISCOUNT_SUM => 0.01
            ], true)
        ];
        $invoice = $this->_createInvoice($positions, [
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
            ], true),
        ];
        $buyRef = 'buy refüÜß³';
        $invoice = $this->_createInvoice($positions, [
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
                SMDPI::FLD_POSITION_DISCOUNT_SUM => 1.01
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
        $invoice = $this->_createInvoice($positions, [
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
            ], true),
        ];
        $invoice = $this->_createInvoice($positions, [
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
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

    protected function _createInvoice(array $positions, array $invoiceData = []): SMDI
    {
        $customer = $this->_createCustomer();

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

    protected function _assertUblXml(SMDI $invoice, float $taxExclValue, float $taxInclValue): void
    {
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
}
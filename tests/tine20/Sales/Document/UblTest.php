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

    public function testA(): void
    {
        $customer = $this->_createCustomer();
        $product1 = $this->_createProduct();

        /** @var SMDI $invoice */
        $invoice = Sales_Controller_Document_Invoice::getInstance()->create(new SMDI([
            SMDI::FLD_CUSTOMER_ID => $customer,
            SMDI::FLD_PAYMENT_TERMS => 10,
            SMDI::FLD_INVOICE_STATUS => SMDI::STATUS_PROFORMA,
            SMDI::FLD_DOCUMENT_DATE => Tinebase_DateTime::now(),
            SMDI::FLD_BUYER_REFERENCE => 'buy ref',
            SMDI::FLD_RECIPIENT_ID => $customer->{Sales_Model_Customer::FLD_DEBITORS}->getFirstRecord()->{Sales_Model_Debitor::FLD_BILLING}->getFirstRecord(),
            SMDI::FLD_POSITIONS => [
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
                ], true)
            ],
        ]));

        $invoice->{SMDI::FLD_INVOICE_STATUS} = SMDI::STATUS_BOOKED;
        $invoice = Sales_Controller_Document_Invoice::getInstance()->update($invoice);

        $invoice->toUbl();
    }
}
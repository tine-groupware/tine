<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Sales_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 Sales All Tests');
        $suite->addTestSuite(Sales_Backend_ContractTest::class);
        $suite->addTestSuite(Sales_Backend_NumberTest::class);
        $suite->addTestSuite(Sales_BoilerplateControllerTest::class);
        $suite->addTestSuite(Sales_ControllerTest::class);
        $suite->addTestSuite(Sales_CustomersTest::class);
        $suite->addTestSuite(Sales_CustomFieldTest::class);
        $suite->addTestSuite(Sales_Document_ControllerTest::class);
        $suite->addTestSuite(Sales_Document_ExportTest::class);
        $suite->addTestSuite(Sales_Document_JsonTest::class);
        $suite->addTestSuite(Sales_Document_UblTest::class);
        $suite->addTestSuite(Sales_Export_DebitorTest::class);
        $suite->addTestSuite(Sales_Export_ProductTest::class);
        $suite->addTestSuite(Sales_Import_AllTests::class);
        $suite->addTestSuite(Sales_InvoiceControllerTests::class);
        $suite->addTestSuite(Sales_InvoiceExportTests::class);
        $suite->addTestSuite(Sales_InvoiceJsonTests::class);
        $suite->addTestSuite(Sales_JsonTest::class);
        $suite->addTestSuite(Sales_OfferControllerTests::class);
        $suite->addTestSuite(Sales_OrderConfirmationControllerTests::class);
        $suite->addTestSuite(Sales_PurchaseInvoiceTest::class);
        $suite->addTestSuite(Sales_SuppliersTest::class);

        return $suite;
    }

    public static function estimatedRunTime()
    {
        return 45;
    }
}

<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Admin_Frontend_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 Admin Frontend All Tests');
        $suite->addTestSuite(Admin_Frontend_CliTest::class);
        $suite->addTestSuite(Admin_Frontend_JsonTest::class);
        $suite->addTestSuite(Admin_Frontend_Json_EmailAccountTest::class);
        $suite->addTestSuite(Admin_Frontend_Json_UserTest::class);
        $suite->addTestSuite(Admin_Frontend_Json_QuotaTest::class);
        return $suite;
    }
}

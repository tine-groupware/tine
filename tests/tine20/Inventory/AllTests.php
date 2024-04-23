<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Inventory
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Michael Spahn <m.spahn@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Inventory_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 Inventory All Tests');
        
        $suite->addTestSuite('Inventory_JsonTest');
        $suite->addTestSuite('Inventory_ControllerTest');
        $suite->addTestSuite('Inventory_Import_AllTests');
        $suite->addTestSuite('Inventory_DoctrineModelTest');
        return $suite;
    }

    public static function estimatedRunTime()
    {
        return 3;
    }
}

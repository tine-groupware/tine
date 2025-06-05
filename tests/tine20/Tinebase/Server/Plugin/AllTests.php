<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2015-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * 
 * @package     Tinebase
 * @subpackage  Server
 *
 */
class Tinebase_Server_Plugin_AllTests
{
    public static function suite() 
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 Tinebase All Server Plugin Tests');
        $suite->addTestSuite(Tinebase_Server_Plugin_JsonTests::class);
        $suite->addTestSuite(Tinebase_Server_Plugin_WebDAVTests::class);
        $suite->addTestSuite(Tinebase_Server_Plugin_RoutingTests::class);
        
        return $suite;
    }
}

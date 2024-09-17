<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Addressbook_Frontend_WebDAV_AllTests
{
    public static function suite ()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 Addressbook All Frontend WebDAV Tests');
        $suite->addTestSuite(Addressbook_Frontend_WebDAV_ContactTest::class);
        $suite->addTestSuite(Addressbook_Frontend_WebDAV_ContainerTest::class);
        $suite->addTestSuite(Addressbook_Frontend_WebDAV_ServerTest::class);
        return $suite;
    }
}

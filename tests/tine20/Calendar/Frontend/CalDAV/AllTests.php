<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Calendar_Frontend_CalDAV_AllTests
{
    public static function suite()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 Calendar All Frontend CalDAV Tests');
        $suite->addTestSuite(Calendar_Frontend_CalDAV_FixMultiGet404PluginTest::class);
        $suite->addTestSuite(Calendar_Frontend_CalDAV_PluginDefaultAlarmsTest::class);
        $suite->addTestSuite(Calendar_Frontend_CalDAV_PluginManagedAttachmentsTest::class);
        $suite->addTestSuite(Calendar_Frontend_CalDAV_ProxyTest::class);
        $suite->addTestSuite(Calendar_Frontend_CalDAV_ScheduleOutboxTest::class);
        $suite->addTestSuite(Calendar_Frontend_CalDAV_SpeedUpPropfindPluginTest::class);
        return $suite;
    }
}

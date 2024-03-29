<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

class Calendar_Convert_Event_VCalendar_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 Calendar All Import Vcard Tests');
        $suite->addTestSuite('Calendar_Convert_Event_VCalendar_FactoryTest');
        $suite->addTestSuite('Calendar_Convert_Event_VCalendar_GenericTest');
        $suite->addTestSuite('Calendar_Convert_Event_VCalendar_MacOSXTest');
        $suite->addTestSuite('Calendar_Convert_Event_VCalendar_ThunderbirdTest');
        $suite->addTestSuite('Calendar_Convert_Event_VCalendar_EMClientTest');
        $suite->addTestSuite('Calendar_Convert_Event_VCalendar_DavDroidTest');

        return $suite;
    }
}

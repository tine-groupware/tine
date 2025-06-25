<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Addressbook_Export_AllTests
{
    public static function main ()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }
    
    public static function suite ()
    {
        $suite = new \PHPUnit\Framework\TestSuite('Tine 2.0 Addressbook All Export Tests');
        $suite->addTestSuite(Addressbook_Export_CsvTest::class);
        $suite->addTestSuite(Addressbook_Export_DocTest::class);
        $suite->addTestSuite(Addressbook_Export_VCardTest::class);
        $suite->addTestSuite(Addressbook_Export_VCardReportTest::class);
        $suite->addTestSuite(Addressbook_Export_XlsTest::class);
        $suite->addTestSuite(Addressbook_Export_YealinkXmlTest::class);
        return $suite;
    }
}

<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     CrewScheduling
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2017-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * All CrewScheduling tests
 * 
 * @package     CrewScheduling
 */
class CrewScheduling_AllTests
{
    public static function suite ()
    {
        $suite = new PHPUnit\Framework\TestSuite('All CrewScheduling tests');

        $suite->addTestSuite(CrewScheduling_ControllerTest::class);
        $suite->addTestSuite(CrewScheduling_Export_XlsxTest::class);
        $suite->addTestSuite(CrewScheduling_Export_PdfTest::class);
        $suite->addTestSuite(CrewScheduling_FrontendTest::class);

        return $suite;
    }
}

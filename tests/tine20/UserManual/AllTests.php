<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     UserManual
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2017-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * All UserManual tests
 * 
 * @package     UserManual
 */
class UserManual_AllTests
{
    public static function suite ()
    {
        $suite = new PHPUnit\Framework\TestSuite('All UserManual tests');

        $suite->addTestSuite(UserManual_Frontend_JsonTest::class);
        $suite->addTestSuite(UserManual_Frontend_CliTest::class);
        $suite->addTestSuite(UserManual_Frontend_HttpTest::class);
        return $suite;
    }
}

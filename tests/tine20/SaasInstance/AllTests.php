<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     SaasInstance
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * All SSO tests
 * 
 * @package     SSO
 */
class SaasInstance_AllTests
{
    public static function suite ()
    {
        $suite = new \PHPUnit\Framework\TestSuite('All SSO tests');
        $suite->addTestSuite(SaasInstance_ControllerTest::class);
        return $suite;
    }
}

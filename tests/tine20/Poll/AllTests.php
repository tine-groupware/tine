<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Poll
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2019-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl <c.feitl@metaways.de>
 */

/**
 * All Poll tests
 *
 * @package     Poll
 */
class Poll_AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit\Framework\TestSuite('All Poll tests');

        $suite->addTestSuite(Poll_JsonTest::class);
        return $suite;
    }
}

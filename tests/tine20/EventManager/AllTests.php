<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     EventManager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Tonia Wulff <t.leuschel@metaways.de>
 *
 */

class EventManager_AllTests
{
    public static function suite()
    {
        $suite = new \PHPUnit\Framework\TestSuite('EventManager All Tests');
        if (Tinebase_Application::getInstance()->isInstalled('EventManager')) {
            $suite->addTestSuite('EventManager_ControllerTest');
        }
        return $suite;
    }
}

<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

class Tinebase_Record_PersistentObserverTest extends TestCase
{
    public function testPersistentObserverRecordValidation(): void
    {
        $observer = new Tinebase_Model_PersistentObserver([
            'observable_model'      => Timetracker_Model_Timesheet::class,
            'observer_model'        => Timetracker_Controller_Timeaccount::class,
            'observer_identifier'   => 'calculateBudgetUpdate',
            'observed_event'        => Tinebase_Event_Record_Update::class,
        ]);
        $this->assertSame('', $observer->observable_identifier);

        $observer = new Tinebase_Model_PersistentObserver([
            'observable_model'      => Timetracker_Model_Timesheet::class,
            'observable_identifier' => null,
            'observer_model'        => Timetracker_Controller_Timeaccount::class,
            'observer_identifier'   => 'calculateBudgetUpdate',
            'observed_event'        => Tinebase_Event_Record_Update::class,
        ]);
        $this->assertSame('', $observer->observable_identifier);
    }
}

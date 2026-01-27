<?php declare(strict_types=1);
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */

/**
 * Task class with handle and run functions
 * 
 * @package     Calendar
 * @subpackage  Scheduler
 */
class Calendar_Scheduler_Task extends Tinebase_Scheduler_Task
{
    public const UPDATE_CONSTRAINTS_EXDATES = 'Calendar_Controller_Event::updateConstraintsExdates';
    public const SEND_TENTATIVE_NOTIFICATIONS = 'Calendar_Controller_Event::sendTentativeNotifications';

    /**
     * add update constraints exdates task to scheduler
     * 
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addUpdateConstraintsExdatesTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask(self::UPDATE_CONSTRAINTS_EXDATES)) {
            return;
        }

        $task = self::_getPreparedTask(self::UPDATE_CONSTRAINTS_EXDATES, self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => 'Calendar_Controller_Event',
            self::METHOD_NAME   => 'updateConstraintsExdates',
        ]]);
        $_scheduler->create($task);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task ' . self::UPDATE_CONSTRAINTS_EXDATES . ' in scheduler.');
    }

    public static function addTentativeNotificationTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask(self::SEND_TENTATIVE_NOTIFICATIONS)) {
            return;
        }

        $task = self::_getPreparedTask(self::SEND_TENTATIVE_NOTIFICATIONS, self::TASK_TYPE_DAILY,
            [[
                self::CONTROLLER    => 'Calendar_Controller_Event',
                self::METHOD_NAME   => 'sendTentativeNotifications',
        ]]);
        $_scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task ' . self::SEND_TENTATIVE_NOTIFICATIONS . ' in scheduler.');
    }
}

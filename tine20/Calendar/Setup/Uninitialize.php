<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Tinebase initialization
 *
 * @package     Sales
 */
class Calendar_Setup_Uninitialize extends Setup_Uninitialize
{
    protected function _uninitializeTasks(): void
    {
        $scheduler = Tinebase_Core::getScheduler();

        if ($scheduler->hasTask(Calendar_Scheduler_Task::UPDATE_CONSTRAINTS_EXDATES)) {
            $scheduler->removeTask(Calendar_Scheduler_Task::UPDATE_CONSTRAINTS_EXDATES);
        }
        if ($scheduler->hasTask(Calendar_Scheduler_Task::SEND_TENTATIVE_NOTIFICATIONS)) {
            $scheduler->removeTask(Calendar_Scheduler_Task::SEND_TENTATIVE_NOTIFICATIONS);
        }
    }
}
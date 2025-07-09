<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 */

/**
 * Task class with handle and run functions
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class OnlyOfficeIntegrator_Scheduler_Task extends Tinebase_Scheduler_Task
{
    /**
     * add delete expired Data task to scheduler
     * 
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addScheduleForceSavesTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            OnlyOfficeIntegrator_Controller_AccessToken::class,
            'scheduleForceSaves',
            Tinebase_Scheduler_Task::TASK_TYPE_MINUTELY,
            $_scheduler,
            'OOI_ForceSave'
        );
    }
}

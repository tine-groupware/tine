<?php
/**
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Admin_Scheduler_Task extends Tinebase_Scheduler_Task
{
    public static function addJWTAccessRoutesCleanUpTask(Tinebase_Scheduler $_scheduler): void
    {
        self::_addTaskIfItDoesNotExist(
            Admin_Controller_JWTAccessRoutes::class,
            'cleanTTL',
            self::TASK_TYPE_DAILY,
            $_scheduler,
        );
    }
}

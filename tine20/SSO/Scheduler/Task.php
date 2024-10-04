<?php declare(strict_types=1);
/**
 * Tine 2.0
 * 
 * @package     SSO
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class SSO_Scheduler_Task extends Tinebase_Scheduler_Task
{
    public const SSO_KEY_ROTATE = 'sso_key_rotate';

    public static function addKeyRotateTask(Tinebase_Scheduler $_scheduler): void
    {
        self::_addTaskIfItDoesNotExist(
            SSO_Controller::class,
            'keyRotate',
            self::TASK_TYPE_DAILY,
            $_scheduler,
            self::SSO_KEY_ROTATE
        );
    }

    public static function removeKeyRotateTask(Tinebase_Scheduler $_scheduler): void
    {
        $_scheduler->removeTask(self::SSO_KEY_ROTATE);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removed task ' . self::SSO_KEY_ROTATE . ' from scheduler.');
    }
}

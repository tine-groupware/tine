<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Task class with handle and run functions
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Felamimail_Scheduler_Task extends Tinebase_Scheduler_Task
{
    /**
     * add check expected answer task to scheduler
     * 
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addCheckExpectedAnswerTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Felamimail_Controller_MessageExpectedAnswer::checkExpectedAnswer')) {
            return;
        }

        $task = self::_getPreparedTask('Felamimail_Controller_MessageExpectedAnswer::checkExpectedAnswer', self::TASK_TYPE_HOURLY, [[
            self::CONTROLLER    => 'Felamimail_Controller_MessageExpectedAnswer',
            self::METHOD_NAME   => 'checkExpectedAnswer',
        ]]);
        $_scheduler->create($task);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Felamimail_Controller_MessageExpectedAnswer::checkExpectedAnswer in scheduler.');
    }

    /**
     * remove check expected answer task from scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function removeCheckExpectedAnswerTask(Tinebase_Scheduler $_scheduler)
    {
        $_scheduler->removeTask('Felamimail_Controller_MessageExpectedAnswer::checkExpectedAnswer');

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removed task Felamimail_Controller_MessageExpectedAnswer::checkExpectedAnswer from scheduler.');
    }

    public static function addPruneAttachmentCacheTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Felamimail_Controller_AttachmentCache::class,
            'checkTTL',
            Tinebase_Scheduler_Task::TASK_TYPE_HOURLY,
            $_scheduler,
            'FelamimailPruneAttachmentCache'
        );
    }
}

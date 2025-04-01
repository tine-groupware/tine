<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Scheduler Controller
 *
 * @package     Tinebase
 * @subpackage  Scheduler
 */
class Tinebase_Scheduler extends Tinebase_Controller_Record_Abstract
{
    /**
     * @var Tinebase_Backend_Scheduler
     */
    protected $_backend;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = 'Tinebase';
        $this->_backend = new Tinebase_Backend_Scheduler();
        $this->_modelName = 'Tinebase_Model_SchedulerTask';
        $this->_purgeRecords = false;
        $this->_omitModLog = false;
        $this->_doContainerACLChecks = false;
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }

    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Scheduler
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Tinebase_Scheduler
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * overwrite this function to check rights / don't forget to call parent
     *
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight(/** @noinspection PhpUnusedParameterInspection */$_action)
    {
        if (! $this->_doRightChecks) {
            return;
        }

        $this->checkRight(Tinebase_Acl_Rights::ADMIN);
    }

    /**
     * @return bool
     */
    public function run()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
            ' Scheduler started');
        $db = Tinebase_Core::getDb();
        $transactionManager = Tinebase_TransactionManager::getInstance();
        $this->_backend->cleanZombieTasks();
        $started = time();
        $tasks = [];
        $noop = fn() => true;

        do {
            $finallyFn = $noop;
            // first get a random task due to execute (without a transaction to avoid deadlocks!)
            if (null === ($dueTask = $this->_backend->getDueTask())) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' .
                    __LINE__ . ' no due task found, stopping...');
                // nothing to do, stop work
                break;
            }

            // in case a task fails quickly, we may run by here every few milli seconds... so better keep count
            // actually failed tasks are suspended for at least 5 minutes, so this is became redundant
            // also a minutely task may come by here twice legally
            if (!isset($tasks[$dueTask->getId()])) {
                $tasks[$dueTask->getId()] = 1;
            } else {
                if (++$tasks[$dueTask->getId()] > 4) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' .
                            __LINE__ . ' task ' . $dueTask->name . ' run to often, aborting');
                    }
                    // same task for the fifth time, we dont try again
                    return false;
                    
                } elseif ($tasks[$dueTask->getId()] > 2) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' .
                            __LINE__ . ' task ' . $dueTask->name . ' run to often, skipping');
                    }
                    // same task for the third+ time, we dont try again
                    continue;
                }
            }

            // now get the task transaction and deadlock safe and mark it running
            $transactionId = $transactionManager->startTransaction($db);

            if (null === ($task = $this->_backend->getTaskForUpdate($dueTask->getId()))) {
                // this may happen legally, though very unlikely, in case the task was deleted
                // in a parallel process after getting it in the first select statement
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::'
                    . __LINE__ . ' task ' . $dueTask->name . ' ' . $dueTask->getId() . ' disappeared');
                $transactionManager->commitTransaction($transactionId);
                continue;
            }

            if (!empty($task->lock_id)) {
                // somebody else snatched it away, no worry, just continue and maybe get another task
                $transactionManager->commitTransaction($transactionId);
                continue;
            }

            if (true !== $this->_backend->markTaskRunning($task)) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' failed to mark task running.');
                $transactionManager->rollBack();
                return false;
            }
            $lockId = $task->lock_id;
            try {
                $transactionManager->commitTransaction($transactionId);

                // then run the task
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' run task ' . $task->name);
                }
                $userRaii = null;
                try {
                    if ($task->{Tinebase_Model_SchedulerTask::FLD_ACCOUNT_ID}) {
                        $oldUser = Tinebase_Core::getUser();
                        $userRaii = new Tinebase_RAII(function() use($oldUser) {
                            Tinebase_Core::setUser($oldUser);
                        });
                        Tinebase_Core::setUser(Tinebase_User::getInstance()
                            ->getFullUserById($task->{Tinebase_Model_SchedulerTask::FLD_ACCOUNT_ID}));
                    }
                    if (true === $task->config->run()) {
                        $task->config->markSuccess($task);
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' .
                                __LINE__ . ' task ' . $task->name . ' succeeded');
                        }
                    } else {
                        $task->config->markFailed($task);
                        $finallyFn = fn() => $this->sendFailMail($task);
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' .
                                __LINE__ . ' task ' . $task->name . ' failed gracefully');
                        }
                    }
                } catch (Exception $e) {
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' task ' . $task->name .
                        ' failed with exception');
                    Tinebase_Exception::log($e);
                    $task->config->markFailed($task);
                    $finallyFn = fn() => $this->sendFailMail($task);
                }

                // clean up again
                unset($userRaii);
                $task->lock_id = null;
                $this->_backend->update($task);
            } finally {
                Tinebase_Core::releaseMultiServerLock($lockId);
                $finallyFn();
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' running task ' . $task->name . ' finished');

        } while (time() - $started < 90);

        return true;
    }

    protected function sendFailMail(Tinebase_Model_SchedulerTask $task): void
    {
        $maxFails = Tinebase_Config::getInstance()->{Tinebase_Config::SCHEDULER_USER_TASK_FAIL_NOTIFICATION_THRESHOLD};
        $accountId = $task->{Tinebase_Model_SchedulerTask::FLD_ACCOUNT_ID};
        if ($accountId !== null && !$task->{Tinebase_Model_SchedulerTask::FLD_IS_SYSTEM} && ($failCount = $task->config->getFailCountSinceLastSuccess($task)) >= $maxFails) {
            $translation = Tinebase_Translation::getTranslation('Admin', Tinebase_Core::getLocale());
            $subject =  $translation->_('Scheduler task failed');
            $messageBody = sprintf($translation->_('Your scheduler task %s has failed %d times.'), $task->name, $failCount);

            $user = Tinebase_User::getInstance()->getFullUserById($accountId);
            $contact = [$user->contact_id];
            Tinebase_Notification::getInstance()->send(Tinebase_Core::getUser(), $contact, $subject, $messageBody);
        }
    }

    /**
     * @param string $_name
     * @return bool
     */
    public function hasTask($_name)
    {
        return $this->_backend->hasTask($_name);
    }

    /**
     * @param $_name
     * @return bool
     */
    public function removeTask($_name)
    {
        return 1 === $this->_backend->deleteByProperty($_name, 'name');
    }

    /**
     * @return null|Tinebase_Model_SchedulerTask
     */
    public function getLastRun()
    {
        return $this->_backend->getLastRun();
    }

    public function spreadTasks(bool $hourly): void
    {
        $toUpdate = [];

        /** @var Tinebase_Model_SchedulerTask $task */
        foreach ($this->_backend->getAll('RAND()') as $task) {
            if (!$task->{Tinebase_Model_SchedulerTask::FLD_DISABLE_AUTO_SHUFFLE}
                    && preg_match($hourly ? '/^\d+( \* .*)$/' : '/^\d+ 0?[0-6]( .*)$/', $task->config->getCron(), $m)) {
                $toUpdate[] = [
                    'record'  => $task,
                    'matches' => $m
                ];
            }
        }

        if (($count = count($toUpdate)) < 2) return;
        $spread = ($hourly ? 60 : 6 * 60) / $count;
        $start = 0.0;
        $hour = 0;

        foreach ($toUpdate as $data) {
            $minute = floor($start);
            while ($minute > 59) {
                if ($hourly) {
                    $minute = 59;
                } else {
                    $minute -= 60;
                    $start -= 60;
                    if ($hour < 6) {
                        ++$hour;
                    }
                }
            }
            $data['record']->config->setCron($minute . ($hourly ? '' : ' ' . $hour) . $data['matches'][1]);
            $this->_backend->update($data['record']);
            $start += $spread;
        }
    }

    /**
     * inspect creation of one record (after create)
     *
     * @param   Tinebase_Record_Interface $_createdRecord
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        parent::_inspectAfterCreate($_createdRecord, $_record);

        if (!$_createdRecord->{Tinebase_Model_SchedulerTask::FLD_DISABLE_AUTO_SHUFFLE}) {
            $cron = $_createdRecord->config->getCron();
            if (preg_match('/^\d+( \* .*)$/', $cron, $m)) {
                $this->spreadTasks(true);
            } elseif (preg_match('/^\d+ 0?[0-6]( .*)$/', $cron, $m)) {
                $this->spreadTasks(false);
            }
        }
    }
}

<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Goekmen Ciyiltepe <g.ciyiltepe@metaways.de>
 */

/**
 * Task class with handle and run functions
 *
 * - methods called addXYZTask are called via \Tinebase_Setup_Initialize::addSchedulerTasks
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Scheduler_Task
{
    /**
     * minutely task
     * 
     * @var string
     */
    public const TASK_TYPE_MINUTELY = '* * * * *';
    
    /**
     * hourly task
     * 
     * @var string
     */
    public const TASK_TYPE_HOURLY = '0 * * * *';

    /**
     * daily task
     * 
     * @var string
     */
    public const TASK_TYPE_DAILY = '0 0 * * *';

    /**
     * weekly task (thursdays)
     *
     * @var string
     */
    public const TASK_TYPE_WEEKLY = '0 1 * * 4';

    /**
     * monthly task (first day of month at 2 am)
     */
    public const TASK_TYPE_MONTHLY = '0 2 1 * *';

    public const CLASS_NAME = 'class';
    public const CONTROLLER = 'controller';
    public const METHOD_NAME = 'method';
    public const ARGS = 'args';

    /**
     * measures the time spend in run() method in seconds
     *
     * @var int
     */
    protected $_runDuration = null;

    /**
     * the cron expression as string
     *
     * @var string
     */
    protected $_cron = null;

    /**
     * @var \Cron\CronExpression
     */
    protected $_cronObject = null;

    /**
     * @var array
     */
    protected $_callables = null;

    protected $_config = null;
    protected $_config_class = null;
    protected $_emails = null;
    protected $_name = null;

    /**
     * Tinebase_Scheduler_Task constructor.
     * @param array $options
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function __construct(array $options)
    {
        if (!isset($options['cron'])) {
            throw new Tinebase_Exception_InvalidArgument('options needs to contain key cron with a cron expression');
        }
        if (!isset($options['callables'])) {
            throw new Tinebase_Exception_InvalidArgument('options needs to contain callables');
        }
        foreach ($options['callables'] as $callable) {
            if (!isset($callable[self::CLASS_NAME]) && !isset($callable[self::CONTROLLER])) {
                throw new Tinebase_Exception_InvalidArgument('callables need to contain class oder controller');
            }
            if (!isset($callable[self::METHOD_NAME])) {
                throw new Tinebase_Exception_InvalidArgument('callables need to contain a method');
            }
        }
        $this->_cron = $options['cron'];
        $this->_cronObject = Cron\CronExpression::factory($this->_cron);
        $this->_callables = $options['callables'];
        $this->_config = $options['config'] ?? null;
        $this->_config_class = $options['config_class'] ?? null;
        $this->_emails = $options['emails'] ?? null;
        $this->_name = $options['name'] ?? null;
    }

    public function toArray()
    {
        return [
            'cron'              => $this->_cron,
            'callables'         => $this->_callables,
            'config'            => $this->_config,
            'config_class'      => $this->_config_class,
            'emails'            => $this->_emails,
            'name'              => $this->_name,
        ];
    }

    /**
     * @return string
     */
    public function getCron()
    {
        return $this->_cron;
    }

    public function setCron($cron)
    {
        $this->_cron = $cron;
        $this->_cronObject = Cron\CronExpression::factory($this->_cron);
    }

    /**
     * @param Tinebase_Model_SchedulerTask $task
     */
    public function markSuccess(Tinebase_Model_SchedulerTask $task)
    {
        $task->last_run = $task->server_time;
        $task->last_duration = $this->_runDuration;
        $task->next_run = $this->_cronObject->getNextRunDate($task->server_time->format('Y-m-d H:i:s'))
            ->format('Y-m-d H:i:s');
    }

    public function getFailCountSinceLastSuccess(Tinebase_Model_SchedulerTask $task): int
    {
        if (!$task->last_failure->equals($task->server_time)) {
            return 0;
        }
        if (null === $task->last_run) {
            return (int)$task->failure_count;
        }

        $failReRunInterval = $task->next_run->getTimestamp() - $task->server_time->getTimestamp();
        $timeBetweenLastRegularRunAndLastFail =
            $task->last_failure->getTimestamp() - $this->_cronObject->getNextRunDate($task->last_run->format('Y-m-d H:i:s'))->getTimestamp();

        return $timeBetweenLastRegularRunAndLastFail < 1 ? 0 : floor($timeBetweenLastRegularRunAndLastFail / $failReRunInterval);
    }

    /**
     * @param Tinebase_Model_SchedulerTask $task
     */
    public function markFailed(Tinebase_Model_SchedulerTask $task)
    {
        $task->last_failure = $task->server_time;
        $task->failure_count = $task->failure_count + 1;

        // if the next run is more than 1 hour away, set it to one hour
        // if the next run is less than 5 minutes away, set it to 5 minutes
        // otherwise accept the next run time
        $nextRun = $this->_cronObject->getNextRunDate($task->server_time->format('Y-m-d H:i:s'));
        $interval = $nextRun->diff($task->server_time);
        if ($interval->h > 0 || $interval->d > 0 || $interval->m > 0 || $interval->y > 0) {
            $task->next_run = clone $task->server_time->getClone()->addHour(1);
        } elseif ($interval->i < 5) {
            $task->next_run = clone $task->server_time->getClone()->addMinute(5);
        } else {
            $task->next_run = $nextRun->format('Y-m-d H:i:s');
        }
    }

    /**
     * @return bool
     */
    public function run()
    {
        $startTime = time();

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' starting .... ');
        }

        $sendNotification = ! empty($this->_emails);
        $aggResult = is_array($this->_callables) && count($this->_callables) > 0;
        foreach ($this->_callables as $callable) {
            try {
                if (isset($callable[self::CONTROLLER])) {
                    $class = $callable[self::CONTROLLER];
                } else {
                    $class = $callable[self::CLASS_NAME];
                }

                [$appName] = explode('_', (string) $class);
                if (true !== Tinebase_Application::getInstance()->isInstalled($appName)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::'
                        . __LINE__ . ' Application ' . $appName . ' is not installed for scheduler job');
                    $aggResult = false;
                    continue;
                }

                if (isset($callable[self::CONTROLLER])) {
                    $class = Tinebase_Controller_Abstract::getController($callable[self::CONTROLLER]);
                }
            } catch (Tinebase_Exception_AccessDenied $tead) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Could not get controller for scheduler job: ' . $tead->getMessage());
                $aggResult = false;
                continue;
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                    . ' Could not get controller for scheduler job: ' . $e->getMessage());
                Tinebase_Exception::log($e, false);
                $aggResult = false;
                continue;
            }

            $classMethod = [$class, $callable[self::METHOD_NAME]];
            if (! is_callable($classMethod)) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                    . ' Could not get callable for scheduler job');
                $aggResult = false;
            } else {
                $result = $this->_executeTaskMethod($classMethod, $callable, $sendNotification);
                $aggResult = $aggResult && $result;
            }
        }

        $this->_runDuration = time() - $startTime;
        if (0 === $this->_runDuration) {
            $this->_runDuration = 1;
        }

        return $aggResult;
    }

    protected function _executeTaskMethod(array $classMethod, array $callable, bool $sendNotification): bool
    {
        if ($sendNotification) {
            // use a temp file for the writer
            $tmpPath = tempnam(Tinebase_Core::getTempDir(), 'schedular_task');
            $writer = new Zend_Log_Writer_Stream($tmpPath);
            $priority = $this->_config['loglevel'] ?? 5;
            // Create a simple formatter that only outputs the message
            $formatter = new Zend_Log_Formatter_Simple('%message%' . PHP_EOL);
            $writer->setFormatter($formatter);
            $writer->addFilter(new Zend_Log_Filter_Priority($priority));
            Tinebase_Core::getLogger()->addWriter($writer);
        } else {
            $writer = null;
            $tmpPath = null;
        }

        try {
            $result = call_user_func_array($classMethod, $callable[self::ARGS] ?? []);
            if ($sendNotification) {
                $taskName = $this->_name ?? $callable[self::CONTROLLER] . '::' . $callable[self::METHOD_NAME];
                if ($result && Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' Task ' . $taskName . ' has been executed successfully.');
                } else if (! $result && Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                        . ' Task ' . $taskName . ' has failed.');
                };
                $notificationBody = file_get_contents($tmpPath);
                $purifiedNotificationBody = preg_replace('/\w+::\w+::\d+\s*/', '', $notificationBody);
                if (! empty($purifiedNotificationBody)) {
                    // send the file contents as notification to configured email
                    $this->_sendNotification($callable, $purifiedNotificationBody);
                }
            }
        } finally {
            if ($sendNotification) {
                Tinebase_Core::getLogger()->removeWriter($writer);
                unlink($tmpPath);
            }
        }

        if (! is_bool($result)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Task method did not return a boolean: ' . print_r($classMethod, true));
            };
            return false;
        }

        return $result;
    }

    /**
     * send notification to configured addresses
     *
     */
    protected function _sendNotification($callable, $infoData)
    {
        if (empty($infoData)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' No notification data is set');
            };
            return;
        }
        
        try {
            $taskName = $this->_name ?? $callable[self::CONTROLLER] . '::' . $callable[self::METHOD_NAME];
            $emails = is_array($this->_emails) ? $this->_emails : explode(',', (string) $this->_emails);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Sending notification to ' . print_r($emails, true));
            }
            
            $subject = $taskName . ' notification';
            $messageBody = "$subject: \n\n" . print_r($infoData, true);
            $messageBody = mb_substr($messageBody, 0, 1048576); // limit body size to 1 MB

            foreach ($emails as $recipient) {
                $contact = [new Addressbook_Model_Contact(['email' => $recipient], true)];
                Tinebase_Notification::getInstance()->send(Tinebase_Core::getUser(), $contact, $subject, $messageBody);
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Could not send notification :' . $tenf->getMessage());
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not send notification :' . $e);
        }
    }
    
    /**
     * @param string $name
     * @param string $cron
     * @param array $callAbles
     * @return Tinebase_Model_SchedulerTask
     */
    protected static function _getPreparedTask($name, $cron, array $callAbles)
    {
        $applicationId = null;

        foreach ($callAbles as $callable) {
            $class = $callable['controller'] ?? $callable['class'] ?? static::class ?? null;
            if ($class) {
                $parts = explode('_', $class);
                $applicationName = $parts[0];

                try {
                    $application = Tinebase_Application::getInstance()->getApplicationByName($applicationName);
                    $applicationId = $application ? $application->getId() : Tinebase_Application::getInstance()->getApplicationByName(Tinebase_Config::APP_NAME)->getId();
                    break;
                } catch (Exception $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " " . $e);
                }
            } else {
                $applicationId = Tinebase_Application::getInstance()->getApplicationByName(Tinebase_Config::APP_NAME)->getId();
            }
        }

        return new Tinebase_Model_SchedulerTask([
            'name'          => $name,
            'config'        => new Tinebase_Scheduler_Task([
                'cron'      => $cron,
                'callables' => $callAbles
            ]),
            // TODO think about this! daily jobs will be executed soon after...
            'next_run'      => new Tinebase_DateTime('2001-01-01 01:01:01'),
            Tinebase_Model_SchedulerTask::FLD_APPLICATION_ID    => $applicationId
        ]);
    }

    /**
     * add alarm task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    public static function addAlarmTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_Alarm::class,
            'sendPendingAlarms',
            self::TASK_TYPE_MINUTELY,
            $_scheduler,
            'Tinebase_Alarm'
        );
    }
    
    /**
     * add cache cleanup task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    public static function addCacheCleanupTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_Controller::class,
            'cleanupCache',
            self::TASK_TYPE_DAILY,
            $_scheduler,
            'Tinebase_CacheCleanup'
        );
    }
    
    /**
     * add sessions cleanup task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    public static function addSessionsCleanupTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_Controller::class,
            'cleanupSessions',
            self::TASK_TYPE_HOURLY,
            $_scheduler,
            'Tinebase_cleanupSessions'
        );
    }
    
    /**
     * add credential cache cleanup task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    public static function addCredentialCacheCleanupTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_Auth_CredentialCache::class,
            'clearCacheTable',
            self::TASK_TYPE_DAILY,
            $_scheduler,
            'Tinebase_CredentialCacheCleanup'
        );
    }
    
    /**
     * add temp_file table cleanup task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    public static function addTempFileCleanupTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_TempFile::class,
            'clearTableAndTempdir',
            self::TASK_TYPE_HOURLY,
            $_scheduler,
            'Tinebase_TempFileCleanup'
        );
    }
    
    /**
     * add deleted file cleanup task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    public static function addDeletedFileCleanupTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_FileSystem::class,
            'clearDeletedFiles',
            self::TASK_TYPE_DAILY,
            $_scheduler,
            'Tinebase_DeletedFileCleanup'
        );
    }

    /**
     * add access log cleanup task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    public static function addAccessLogCleanupTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_AccessLog::class,
            'clearTable',
            self::TASK_TYPE_DAILY,
            $_scheduler,
            'Tinebase_AccessLogCleanup'
        );
    }

    /**
     * @param Tinebase_Scheduler $_scheduler
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    public static function addAccountSyncTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_User/Group::syncUsers/Groups')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_User/Group::syncUsers/Groups', self::TASK_TYPE_HOURLY, [[
            self::CLASS_NAME    => 'Tinebase_User',
            self::METHOD_NAME   => 'syncUsers',
            self::ARGS          => [
                'options'           => [Tinebase_User::SYNC_WITH_CONFIG_OPTIONS => true],
            ]
        ],[
            self::CLASS_NAME    => 'Tinebase_Group',
            self::METHOD_NAME   => 'syncGroups',
        ]]);

        $_scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Tinebase_User/Group::syncUsers/Groups in scheduler.');
    }

    /**
     * @param Tinebase_Scheduler $_scheduler
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    public static function addReplicationTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_Timemachine_ModificationLog::class,
            'readModificationLogFromMaster',
            self::TASK_TYPE_HOURLY,
            $_scheduler,
            'readModificationLogFromMaster'
        );
    }

    /**
     * add file revision cleanup task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    public static function addFileRevisionCleanupTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_FileSystem::class,
            'clearFileRevisions',
            self::TASK_TYPE_DAILY,
            $_scheduler,
            'Tinebase_FileRevisionCleanup'
        );
    }

    /**
     * add file objects cleanup task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    public static function addFileObjectsCleanupTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_FileSystem::class,
            'clearFileObjects',
            self::TASK_TYPE_DAILY,
            $_scheduler
        );
    }

    /**
     * add file system index checking task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    public static function addFileSystemCheckIndexTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_FileSystem::class,
            'checkIndexing',
            self::TASK_TYPE_DAILY,
            $_scheduler,
            'Tinebase_FileSystemCheckIndex'
        );
    }

    /**
     * add file system preview checking task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    public static function addFileSystemSanitizePreviewsTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_FileSystem::class,
            'sanitizePreviews',
            self::TASK_TYPE_DAILY,
            $_scheduler,
            'Tinebase_FileSystemSanitizePreviews'
        );
    }

    /**
     * add file system index checking task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    public static function addFileSystemNotifyQuotaTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_FileSystem::class,
            'notifyQuota',
            self::TASK_TYPE_DAILY,
            $_scheduler,
            'Tinebase_FileSystemNotifyQuota'
        );
    }

    /**
     * @param Tinebase_Scheduler $_scheduler
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    public static function addFileSystemRepairDeleteTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_FileSystem::class,
            'repairTreeIsDeletedState',
            self::TASK_TYPE_DAILY,
            $_scheduler
        );
    }

    /**
     * add file system av scan task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    public static function addFileSystemAVScanTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_FileSystem::class,
            'avScan',
            self::TASK_TYPE_WEEKLY,
            $_scheduler
        );
    }

    /**
     * add file system size recalculation task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addFileSystemSizeRecalculation(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('Tinebase_FileSystemSizeRecalculation')) {
            return;
        }

        $task = self::_getPreparedTask('Tinebase_FileSystemSizeRecalculation', self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => 'Tinebase_FileSystem',
            self::METHOD_NAME   => 'recalculateRevisionSize',
        ],[
            self::CONTROLLER    => 'Tinebase_FileSystem',
            self::METHOD_NAME   => 'recalculateFolderSize',
        ]]);

        $_scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task Tinebase_FileSystem::recalculateRevisionSize and recalculateFolderSize in scheduler.');
    }

    /**
     * add acl tables cleanup task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addAclTableCleanupTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_Controller::class,
            'cleanAclTables',
            self::TASK_TYPE_DAILY,
            $_scheduler,
            'Tinebase_AclTablesCleanup'
        );
    }

    /**
     * add hourly action queue integrity check task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addActionQueueConsistencyCheckTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_Controller::class,
            'actionQueueConsistencyCheck',
            self::TASK_TYPE_HOURLY,
            $_scheduler,
            'Tinebase_ActionQueueConsistencyCheck'
        );
    }

    /**
     * add action queue constant monitoring task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addActionQueueMonitoringTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_Controller::class,
            'actionQueueActiveMonitoring',
            self::TASK_TYPE_MINUTELY,
            $_scheduler,
            'Tinebase_ActionQueueActiveMonitoring'
        );
    }

    /**
     * add filter sync token cleanup task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addFilterSyncTokenCleanUpTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_FilterSyncToken::class,
            'cleanUp',
            self::TASK_TYPE_DAILY,
            $_scheduler
        );
    }

    /**
     * add log entry cleanup task to scheduler
     *
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addLogEntryCleanUpTask(Tinebase_Scheduler $_scheduler)
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_Controller_LogEntry::class,
            'cleanUp',
            self::TASK_TYPE_WEEKLY,
            $_scheduler,
            'Tinebase_LogEntry::cleanup'
        );
    }

    /**
     * add purge db task to scheduler
     *
     * @param Tinebase_Scheduler $scheduler
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    public static function addRemoveObsoleteDataTask(Tinebase_Scheduler $scheduler): void
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_Controller::class,
            'removeObsoleteData',
            self::TASK_TYPE_MONTHLY,
            $scheduler
        );
    }

    public static function addFlySystemSyncTask(Tinebase_Scheduler $scheduler): void
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_FileSystem::class,
            'syncFlySystems',
            self::TASK_TYPE_HOURLY,
            $scheduler,
            'sync all fly system filesystems'
        );
    }

    public static function addCleanUpRelationTask(Tinebase_Scheduler $_scheduler): void
    {
        self::_addTaskIfItDoesNotExist(
            Tinebase_Relations::class,
            'cleanRelations',
            self::TASK_TYPE_MONTHLY,
            $_scheduler,
            'Clean up broken relations (monthly)'
        );
    }

    /**
     * @param string $taskController
     * @param string $taskMethod
     * @param string $cron
     * @param Tinebase_Scheduler $scheduler
     * @param string|null $name
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected static function _addTaskIfItDoesNotExist(
        string $taskController,
        string $taskMethod,
        string $cron,
        Tinebase_Scheduler $scheduler,
        ?string $name = null): void
    {
        $taskName = $name ?? $taskController . '::' . $taskMethod;
        if ($scheduler->hasTask($taskName)) {
            return;
        }

        $task = self::_getPreparedTask($taskName, $cron, [[
            self::CONTROLLER    => $taskController,
            self::METHOD_NAME   => $taskMethod,
        ]]);

        $scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__
            . ' Saved task ' . $taskName . ' in scheduler.');
    }
}

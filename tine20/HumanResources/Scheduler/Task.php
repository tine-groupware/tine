<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Task class with handle and run functions
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class HumanResources_Scheduler_Task extends Tinebase_Scheduler_Task 
{
    public const HR_AUTO_CREATE_ACCOUNTS = 'Human Resources Auto Create Employee Accounts';

    public static function addAutoCreateAccounts(Tinebase_Scheduler $_scheduler): void
    {
        if ($_scheduler->hasTask(self::HR_AUTO_CREATE_ACCOUNTS)) {
            return;
        }

        $task = self::_getPreparedTask(self::HR_AUTO_CREATE_ACCOUNTS, self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => HumanResources_Controller_Account::class,
            self::METHOD_NAME   => 'autoCreateAccounts',
        ]]);
        $_scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task ' . self::HR_AUTO_CREATE_ACCOUNTS . ' in scheduler.');
    }

    public static function removeAutoCreateAccounts(Tinebase_Scheduler $_scheduler): void
    {
        $_scheduler->removeTask(self::HR_AUTO_CREATE_ACCOUNTS);
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' removed task ' . self::HR_AUTO_CREATE_ACCOUNTS . ' from scheduler.');
    }

    /**
     * add CalculateDailyWorkingTimeReportsTask task to scheduler
     * 
     * @param Tinebase_Scheduler $_scheduler
     */
    public static function addCalculateDailyWorkingTimeReportsTask(Tinebase_Scheduler $_scheduler)
    {
        if ($_scheduler->hasTask('HumanResources_Controller_DailyWTReport::CalculateDailyWorkingTimeReportsTask')) {
            return;
        }

        $task = self::_getPreparedTask('HumanResources_Controller_DailyWTReport::CalculateDailyWorkingTimeReportsTask', self::TASK_TYPE_DAILY, [[
            self::CONTROLLER    => 'HumanResources_Controller_DailyWTReport',
            self::METHOD_NAME   => 'calculateAllReports',
        ]]);
        $_scheduler->create($task);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task HumanResources_Controller_DailyWTReport::CalculateDailyWorkingTimeReportsTask in scheduler.');
    }

    public static function removeCalculateDailyWorkingTimeReportsTask(Tinebase_Scheduler $_scheduler): void
    {
        $_scheduler->removeTask('HumanResources_Controller_DailyWTReport::CalculateDailyWorkingTimeReportsTask');
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' removed task HumanResources_Controller_DailyWTReport::CalculateDailyWorkingTimeReportsTask from scheduler.');
    }

    public static function addAttendanceRecorderRunBLTask(Tinebase_Scheduler $_scheduler): void
    {
        if ($_scheduler->hasTask('HumanResources_Controller_AttendanceRecorder::runBLPipes')) {
            return;
        }

        $task = self::_getPreparedTask('HumanResources_Controller_AttendanceRecorder::runBLPipes', self::TASK_TYPE_HOURLY, [[
            self::CONTROLLER    => HumanResources_Controller_AttendanceRecorder::class,
            self::METHOD_NAME   => 'runBLPipes',
        ]]);
        $_scheduler->create($task);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Saved task HumanResources_Controller_AttendanceRecorder::runBLPipes in scheduler.');
    }

    public static function removeAttendanceRecorderRunBLTask(Tinebase_Scheduler $_scheduler): void
    {
        $_scheduler->removeTask('HumanResources_Controller_AttendanceRecorder::runBLPipes');
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' removed task HumanResources_Controller_AttendanceRecorder::runBLPipes from scheduler.');
    }
}

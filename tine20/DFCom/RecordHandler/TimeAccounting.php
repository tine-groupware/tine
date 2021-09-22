<?php
/**
 * @package     DFCom
 * @subpackage  RecordHandler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * handler for device records of type timeaccounting
 *
 * @TODO: parts of this class might be moved to a generalized
 *        class in timetracker
 *
 * Class DFCom_RecordHandler_TimeAccounting
 */
class DFCom_RecordHandler_TimeAccounting
{
    const FUNCTION_KEY_INFO = 'INFO';
    const FUNCTION_KEY_CLOCKIN = 'CLIN';
    const FUNCTION_KEY_CLOCKOUT = 'CLOT';
    const FUNCTION_KEY_ABSENCE = 'ASCE';

    public function __construct($event)
    {
        /** @var DFCom_Model_Device $this->deviceRecord */
        $this->device = $event['device'];
        /** @var DFCom_Model_DeviceResponse $this->deviceRecord */
        $this->deviceResponse = $event['deviceResponse'];
        /** @var DFCom_Model_DeviceRecord $this->deviceRecord */
        $this->deviceRecord = $event['deviceRecord'];
        $this->deviceData = $this->deviceRecord->xprops('data');

        $this->employeeController = HumanResources_Controller_Employee::getInstance();
        $this->accountController = HumanResources_Controller_Account::getInstance();
//        $this->workingTimeSchemaController = HumanResources_Controller_WorkingTimeScheme::getInstance();
        $this->timeaccountController = Timetracker_Controller_Timeaccount::getInstance();
        $this->monthlyWTReportController = HumanResources_Controller_MonthlyWTReport::getInstance();
        $this->timesheetController = Timetracker_Controller_Timesheet::getInstance();

        $this->i18n = Tinebase_Translation::getTranslation('DFCom');
    }

    public function handle()
    {
        $assertEmployeeAclUsage = $this->employeeController->assertPublicUsage();
        $assertAccountAclUsage = $this->accountController->assertPublicUsage();
//        $assertWorkingTimeSchemaAclUsage = $this->workingTimeSchemaController->assertPublicUsage();
        $assertTimeaccountAclUsage = $this->timeaccountController->assertPublicUsage();
        $assertMonthlyWTReportControllerAclUsage = $this->monthlyWTReportController->assertPublicUsage();
        $assertTimesheetAclUsage = $this->timesheetController->assertPublicUsage();

        $dateTime = new Tinebase_DateTime($this->deviceData['dateTime'], $this->device->timezone);
        
        try {
            $this->employee = $this->employeeController->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Employee::class, [
                ['condition' => Tinebase_Model_Filter_FilterGroup::CONDITION_OR, 'filters' => [
                    ['field' => 'dfcom_id', 'operator' => 'equals', 'value' => (int)$this->deviceData['cardId']],
                    ['field' => 'dfcom_id', 'operator' => 'equals', 'value' => $this->deviceData['cardId']],
                ]]
            ]))->getFirstRecord();

            if (!$this->employee) {
                $this->deviceRecord->xprops()[self::XPROP_UNKNOWN_CARD_ID] =  $this->deviceData['cardId'];
                Tinebase_Core::getLogger()->WARN(__METHOD__ . '::' . __LINE__ . " unknown card_id '{$this->deviceData['cardId']}'");
                return;
            }

            // switch to current user identified by card
            $this->user = Tinebase_User::getInstance()->getUserById($this->employee->account_id, Tinebase_Model_FullUser::class);
            $this->currentUser = Tinebase_Core::setUser($this->user);

            switch ($this->deviceData['functionKey']) {
                case self::FUNCTION_KEY_INFO:
                    $employeeName = $this->user->accountDisplayName;
                    try {
                        $allRemainingVacationsDays = HumanResources_Controller_FreeTime::getInstance()
                            ->getRemainingVacationDays($this->employee);
                        $remainingVacations = "{$allRemainingVacationsDays} Tage";
                        
                        $monthlyWTR = $this->monthlyWTReportController->getByEmployeeMonth($this->employee);
                        $balanceTS = $monthlyWTR ? 
                            $monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_BALANCE} : 0;
                        $balanceTime = (string)round($balanceTS/3600) .':' .  
                            str_pad((string) abs($balanceTS/60)%60, 2, "0", STR_PAD_LEFT);
                        $balance = ($balanceTS >= 0 ? "+{$balanceTime} (haben)" : "{$balanceTime} (soll)");
                        
                        $message = "Zeitsaldo: {$balance}\n Resturlaub: {$remainingVacations}";
                    } catch (Exception $e) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                            __METHOD__ . '::' . __LINE__ . " " . $e->getMessage() . "\n" . $e->getTraceAsString());
                        $message = "Es liegen keine Informationen vor.";
                    }
                    $this->deviceResponse->displayMessage("{$employeeName}\n {$message}");
                    
                    return true;
                    break;
                case self::FUNCTION_KEY_CLOCKIN:
                case self::FUNCTION_KEY_CLOCKOUT:
                    // @TODO: do we need a field for project time here? (from terminal)
                    $sameDateOrphaned = $this->getOrphanedTimesheets($dateTime)
                        ->filter('start_date', $dateTime->format('Y-m-d') . ' 00:00:00')
                        ->sort('start_time', 'DESC')
                        ->getFirstRecord();

                    if ($sameDateOrphaned) {
                        if ($this->deviceData['functionKey'] == self::FUNCTION_KEY_CLOCKIN) {
                            // same date orphaned can occour when leave record from other terminal is
                            // processed before it's corresponding arive record

                            $sameDateOrphaned->need_for_clarification = true;
                            $this->timesheetController->update($sameDateOrphaned);

                            $timesheet = $this->createTimesheet($dateTime, $this->deviceData['functionKey']);
                        } else {
                            $sameDateOrphaned->need_for_clarification = false;
                            $timesheet = $this->endTimesheet($sameDateOrphaned, $dateTime);
                        }

                    } else {
                        $timesheet = $this->createTimesheet($dateTime, $this->deviceData['functionKey']);
                    }
                    break;

                default:
                    // @TODO implement me
                    // check absence reason
                    // end open timesheet (like leave)
                    // create new timesheet on absence timeaccount
                    // $this->endTimesheet $reason
                    // evalute special conditions like "till end of day"
//                Tinebase_Core::getLogger()->ERR(__METHOD__ . '::' . __LINE__ . " unknown function key '{$this->deviceData['functionKey']}'");

            }
        } finally {
            $assertTimesheetAclUsage();
            $assertMonthlyWTReportControllerAclUsage();
            $assertTimeaccountAclUsage();
//            $assertWorkingTimeSchemaAclUsage();
            $assertAccountAclUsage();
            $assertEmployeeAclUsage();
            Tinebase_Core::set(Tinebase_Core::USER, $this->currentUser);
        }
    }

    public function createTimesheet($date, $functionKey = self::FUNCTION_KEY_CLOCKIN)
    {
        return $this->timesheetController->create(new Timetracker_Model_Timesheet([
            'account_id' => $this->employee->account_id,
            'timeaccount_id' => HumanResources_Controller_WorkingTimeScheme::getInstance()->getWorkingTimeAccount($this->employee),
            'start_date' => $date,
            'start_time' => $date->format('H:i:s'),
            'end_time' => $date->format('H:i:s'),
            'duration' => 0,
            'description' => sprintf($functionKey === self::FUNCTION_KEY_CLOCKIN ? 
                $this->i18n->translate('Clock in: %1$s') : 
                $this->i18n->translate('Clock out: %1$s'), $date->format('H:i:s')),
        ]));
    }

    public function startTimesheet($timesheet, $start)
    {
        $timesheet->start_time = $start->format('H:i:s');
        $timesheet->description =
            sprintf($this->i18n->translate('Clock in: %1$s'), $start->format('H:i:s')) .
            ' ' . $timesheet->description;

        return $this->timesheetController->update($timesheet);
    }

    /**
     * @param $timesheet
     * @param $end
     * @param string|HumanResources_Model_FreeTimeType $reason
     * @return Timetracker_Model_Timesheet
     * @throws Tinebase_Exception_AccessDenied
     */
    public function endTimesheet($timesheet, $end, $reason='')
    {
        $timesheet->end_time = $end->format('H:i:s');
        $timesheet->{HumanResources_Model_FreeTimeType::TT_TS_SYSCF_CLOCK_OUT_REASON} = $reason;
        $timesheet->description = $timesheet->description . ' ' .
            sprintf($this->i18n->translate('Clock out: %1$s'), $end->format('H:i:s'));

        return $this->timesheetController->update($timesheet);
    }


    public function getOrphanedTimesheets($date)
    {
        $wtAccountId = HumanResources_Controller_WorkingTimeScheme::getInstance()
            ->getWorkingTimeAccount($this->employee)->getId();

        return Timetracker_Controller_Timesheet::getInstance()->search(new Timetracker_Model_TimesheetFilter([
            ['field' => 'account_id', 'operator' => 'equals', 'value' => $this->employee->account_id],
            ['field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $wtAccountId],
            ['field' => 'start_date', 'operator' => 'equals', 'value' => $date->format('Y-m-d')],
            ['field' => 'start_time', 'operator' => 'before', 'value' => $date->format('H:i:s')],
            ['field' => 'duration', 'operator' => 'equals', 'value' => 0],
        ]));
    }
}

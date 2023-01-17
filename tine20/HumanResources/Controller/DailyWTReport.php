<?php
/**
 * DailyWorkingTimeReport controller for HumanResources application
 * 
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * DailyWorkingTimeReport controller class for HumanResources application
 * 
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_DailyWTReport extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;
    use HumanResources_Controller_CheckFilterACLEmployeeTrait;

    const RC_ALLOW_IS_CLEARED = 'allowIsCleared';
    const RC_JSON_REQUEST = 'jsonRequest';

    /**
     * @var HumanResources_Model_Employee
     */
    protected $_employee = null;

    /**
     * @var Tinebase_DateTime
     */
    protected $_startDate = null;
    /**
     * @var Tinebase_DateTime
     */
    protected $_endDate = null;
    /**
     * @var Tinebase_DateTime
     */
    protected $_currentDate = null;
    /**
     * @var Tinebase_Record_RecordSet
     */
    protected $_oldReports = null;
    protected $_reportResult = null;

    protected $_wtsBLPipes = [];
    protected $_feastDays = [];

    protected $_monthlyWTR = [];

    public $lastReportCalculationResult = null;

    protected $_getMultipleGrant = [HumanResources_Model_DivisionGrants::READ_TIME_DATA];
    protected $_requiredFilterACLget = [HumanResources_Model_DivisionGrants::READ_TIME_DATA];
    protected $_requiredFilterACLupdate  = [HumanResources_Model_DivisionGrants::UPDATE_TIME_DATA];
    protected $_requiredFilterACLsync  = [HumanResources_Model_DivisionGrants::READ_TIME_DATA];
    protected $_requiredFilterACLexport  = [HumanResources_Model_DivisionGrants::READ_TIME_DATA];

    protected $allowCorrectionUpdate = false;

    public $iterationResult;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    protected function __construct()
    {
        $this->_applicationName = 'HumanResources';
        $this->_modelName = HumanResources_Model_DailyWTReport::class;
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName,
            'tableName' => 'humanresources_wt_dailyreport',
            'modlogActive' => true
        ));

        $this->_purgeRecords = false;
        $this->_resolveCustomFields = true;
        $this->_doContainerACLChecks = true;
        $this->_traitCheckFilterACLRight = HumanResources_Acl_Rights::MANAGE_WORKINGTIME;
    }

    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        if (!$this->_doContainerACLChecks) {
            return true;
        }

        // if we have manage_employee right, we have all grants
        if (Tinebase_Core::getUser()->hasRight(HumanResources_Config::APP_NAME, HumanResources_Acl_Rights::MANAGE_WORKINGTIME)) {
            return true;
        }

        switch ($_action) {
            case self::ACTION_GET:
                try {
                    HumanResources_Controller_Employee::getInstance()->get($_record->getIdFromProperty(HumanResources_Model_DailyWTReport::FLDS_EMPLOYEE_ID));
                } catch (Tinebase_Exception_AccessDenied $e) {
                    if ($_throw) {
                        throw new Tinebase_Exception_AccessDenied($_errorMessage);
                    } else {
                        return false;
                    }
                }
                return true;
            case self::ACTION_CREATE:
            case self::ACTION_UPDATE:
            case self::ACTION_DELETE:
                $_action = HumanResources_Model_DivisionGrants::UPDATE_TIME_DATA;
                break;
        }
        return parent::_checkGrant($_record, $_action, $_throw, $_errorMessage, $_oldRecord);
    }

    /**
     * DailyWorkingTimeReports are calculated once a day by a scheduler job. New
     *  reports are created and all reports from this and the last month which
     *  don't have their is_cleared flag set get updated. Older reports can be
     *  created/updated manually in the UI
     *
     * All employees that have employment_end IS NULL or emplyoment_end AFTER now() - 2 months will be included
     * in the calculation. Only days during which the employees have a valid contract will create a DailyWTReport
     *
     * @param bool $force
     * @return boolean
     */
    public function calculateAllReports($force = false)
    {
        if (! HumanResources_Config::getInstance()->featureEnabled(
            HumanResources_Config::FEATURE_CALCULATE_DAILY_REPORTS) &&
            ! HumanResources_Config::getInstance()->featureEnabled(
                HumanResources_Config::FEATURE_WORKING_TIME_ACCOUNTING)
        ) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' FEATURE_WORKING_TIME_ACCOUNTING/FEATURE_CALCULATE_DAILY_REPORTS disabled - Skipping.'
            );
            return true;
        }

        if (false === Tinebase_Core::acquireMultiServerLock(__METHOD__)) {
            return true;
        }

        try {
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Employee::class, [
                ['field' => 'employment_end', 'operator' => 'after', 'value' => Tinebase_DateTime::now()->subMonth(2)],
            ], '', [Tinebase_Model_Filter_Date::AFTER_OR_IS_NULL => true]);
            $containerFilter = new Tinebase_Model_Filter_DelegatedAcl('division_id', null, null, [
                'modelName' => HumanResources_Model_Employee::class
            ]);
            $containerFilter->setRequiredGrants([HumanResources_Model_DivisionGrants::READ_TIME_DATA]);
            $filter->addFilter($containerFilter);
            $oFilter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Employee::class, []);
            $oFilter->addFilterGroup($filter);
            $ids = array_merge(
                HumanResources_Controller_Employee::getInstance()->search($oFilter, null, false, true),
                HumanResources_Controller_Employee::getInstance()->search(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Employee::class, [
                        ['field' => 'account_id', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId()]
                    ]), null, false, true)
            );

            $iterator = new Tinebase_Record_Iterator(array(
                'iteratable' => $this,
                'controller' => HumanResources_Controller_Employee::getInstance(),
                'filter' => Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Employee::class, [
                    ['field' => 'id', 'operator' => 'in', 'value' => $ids]
                ]),
                'function' => 'calculateReportsForEmployees',
            ));
            $this->iterationResult = $iterator->iterate($force);
        } finally {
            Tinebase_Core::releaseMultiServerLock(__METHOD__);
        }

        return true;
    }

    /**
     * @param Tinebase_Record_RecordSet $_records
     * @param bool $force
     * @return array
     */
    public function calculateReportsForEmployees(Tinebase_Record_RecordSet $_records, $force = false)
    {
        $result = [];
        foreach ($_records as $employee) {
            $result[$employee->getId()] = $this->calculateReportsForEmployee($employee, null, null, $force);
        }

        $this->lastReportCalculationResult = $result;
        return $result;
    }

    /**
     * iterates over the dates provided or defaulting to beginning of last month until end of yesterday
     * gets the existing reports for each day. If the report of a day is_cleared, the day will be skipped
     * if the employee does not have a valid contract for that day, the day will be skipped
     * the BLPipe from the valid contracts working time scheme will be created and fed with data
     * if there was no existing report or if the report changed, it will be persisted
     *
     * @param HumanResources_Model_Employee $employee
     * @param null|Tinebase_DateTime $startDate
     * @param null|Tinebase_DateTime $endDate
     * @param bool $force
     * @return array
     *
     * @todo use an result object as return value?
     */
    public function calculateReportsForEmployee(
        HumanResources_Model_Employee $employee,
        Tinebase_DateTime $startDate = null,
        Tinebase_DateTime $endDate = null,
        bool $force = false
    ) {
        if (HumanResources_Controller_Employee::getInstance()->doContainerACLChecks()) {
            if ($employee->getIdFromProperty('account_id') !== Tinebase_Core::getUser()->getId() ||
                    !HumanResources_Controller_Employee::getInstance()->checkGrant($employee,
                        HumanResources_Model_DivisionGrants::READ_OWN_DATA, false)) {
                HumanResources_Controller_Employee::getInstance()->checkGrant($employee,
                    HumanResources_Model_DivisionGrants::READ_TIME_DATA);
            }
        }

        $lockId = __METHOD__ . $employee->getId();
        if (false === Tinebase_Core::acquireMultiServerLock($lockId)) {
            return [];
        }
        $multiServerLockRAII = new Tinebase_RAII(function() use($lockId) {
            Tinebase_Core::releaseMultiServerLock($lockId);
        });

        // we should never run in FE context, so we reset the RC and use RAII to restate it
        $oldRC = $this->_requestContext;
        $that = $this;
        $this->_requestContext = [];
        $rcRaii = new Tinebase_RAII(function() use ($oldRC, $that) {
            $that->_requestContext = $oldRC;
        });
        $oldAcl = $this->doContainerACLChecks(false);
        $oldMonthAcl = HumanResources_Controller_MonthlyWTReport::getInstance()->doContainerACLChecks(false);
        $aclRaii = new Tinebase_RAII(function() use($oldAcl, $oldMonthAcl) {
            HumanResources_Controller_DailyWTReport::getInstance()->doContainerACLChecks($oldAcl);
            HumanResources_Controller_MonthlyWTReport::getInstance()->doContainerACLChecks($oldMonthAcl);
        });

        // init some member vars
        $this->_monthlyWTR = [];
        $this->_employee = $employee;
        // since startDate / endDate are somewhat flexible, better not cache to much, they may differ for different employees
        $this->_feastDays = [];

        // first we get all data. We do this in a transaction to get a proper snapshot
        $dataReadTransaction = new Tinebase_TransactionManager_Handle();

        $rs = new Tinebase_Record_RecordSet(HumanResources_Model_Employee::class, [$employee]);
        Tinebase_ModelConfiguration::resolveRecordsPropertiesForRecordSet($rs,
            HumanResources_Model_Employee::getConfiguration());
        // expand required properties
        $expander = new Tinebase_Record_Expander(HumanResources_Model_Employee::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'contracts' => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        'working_time_scheme' => [Tinebase_Record_Expander::GET_DELETED => true]
                    ],
                ],
            ],
        ]);
        $expander->expand($rs);

        if ($startDate && $lastClearedReport = $this->_getLastClearedWTR()) {
            $lastClearedReport = (new Tinebase_DateTime($lastClearedReport->{HumanResources_Model_MonthlyWTReport::FLDS_MONTH}
                . '-01 00:00:00'))->addMonth(1);
            if ($lastClearedReport->isLater($startDate)) {
                $lastClearedReport->hasTime(false);
                $startDate = $lastClearedReport;
            }
        }
        $this->_startDate = $startDate ?: $this->_getStartDate($force);
        $this->_endDate = $endDate ?: $this->_getEndDate();
        $this->_reportResult = [
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
        ];;

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' Calculating Daily Reports for ' . $employee->getTitle()
            . ' (From: ' . $this->_startDate->toString()
            . ' Until: ' . $this->_endDate->toString() . ')'
        );

        $existingReports = $this->_getEmployeesReports();
        $timeSheets = $this->_getEmployeesTimesheets();
        $freeTimes = $this->_getEmployeesFreeTimes();

        $dataReadTransaction->commit();


        for ($this->_currentDate = clone $this->_startDate; $this->_endDate->isLaterOrEquals($this->_currentDate);
                $this->_currentDate->addDay(1)) {

            // we need those two also in an error case
            $dateStr = $this->_currentDate->format('Y-m-d');
            $monthlyWTR = null;

            // then we calculate each day in a transaction, see dailyTransaction
            try {
                $dailyTransaction = new Tinebase_TransactionManager_Handle();

                $monthlyWTR = $this->_getOrCreateMonthlyWTR();

                /** @var HumanResources_Model_DailyWTReport $oldReport */
                $oldReport = null;
                if (isset($existingReports[$dateStr])) {
                    $existingReports[$dateStr] = $this->get($existingReports[$dateStr]->getId());
                    $oldReport = $existingReports[$dateStr];
                    if ($oldReport->is_cleared) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' old report for day ' .
                                $this->_currentDate->toString() . ' is already cleared, skipping');
                        $dailyTransaction->commit();
                        continue;
                    }
                }

                if (null === ($contract = $this->_employee->getValidContract($this->_currentDate))) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                        Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . 'employee ' .
                            $employee->getId() . ' ' . $employee->getTitle() . ' has no valid contract at ' .
                            $this->_currentDate->toString());

                    if (isset($existingReports[$dateStr])) {
                        $oldReport = $existingReports[$dateStr]->getCleanClone();
                        $oldReport->calculation_failure = 1;
                        $oldReport->system_remark =
                            Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME)
                                ->_('No valid contract for this date');

                        $this->update($oldReport);
                        $this->_reportResult['errors'] += 1;
                    }

                    $dailyTransaction->commit();
                    continue;
                }

                if (false === ($blPipe = $this->_getBLPipe($contract->working_time_scheme))) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ .
                            '::' . __LINE__ . 'employees ' . $employee->getId() . ' ' . $employee->getTitle() .
                            ' contract has no valid working time scheme for dailyreporting at ' .
                            $this->_currentDate->toString());

                    if (isset($existingReports[$dateStr])) {
                        $oldReport = $existingReports[$dateStr]->getCleanClone();
                        $oldReport->calculation_failure = 1;
                        $oldReport->system_remark =
                            Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME)
                                ->_('No valid blpipe for the working time scheme of this contract for this date');

                        $this->update($oldReport);
                        $this->_reportResult['errors'] += 1;
                    }

                    $dailyTransaction->commit();
                    continue;
                }
                $blPipe->recycle();
                
                $blPipeData = new HumanResources_BL_DailyWTReport_Data();
                $blPipeData->workingTimeModel = $contract->working_time_scheme;
                $blPipeData->date = $this->_currentDate->getClone();
                if (isset($freeTimes[$dateStr])) {
                    $blPipeData->freeTimes = $freeTimes[$dateStr];
                }
                $blPipeData->feastTimes = $this->_getFeastTimes($dateStr, $contract->feast_calendar_id);

                $blPipeData->result = $oldReport ? $oldReport->getCleanClone() : /* @phpstan-ignore-line */
                    new HumanResources_Model_DailyWTReport([
                        'employee_id' => $employee,
                        'monthlywtreport' => $monthlyWTR->getId(),
                        'date' => clone $this->_currentDate,
                    ]);
                $blPipeData->allowTimesheetOverlap = true;
                if (null !== ($tsConvert = $blPipe
                        ->getFirstElementOfClass(HumanResources_BL_DailyWTReport_ConvertTsPtWtToTimeSlot::class))) {
                    /** @var HumanResources_BL_DailyWTReport_ConvertTsPtWtToTimeSlot $tsConvert */
                    $tsConvert->setTimeSheets(isset($timeSheets[$dateStr]) ? $timeSheets[$dateStr] : null);
                } elseif (isset($timeSheets[$dateStr])) {
                    if ($blPipe->hasInstanceOf(HumanResources_BL_DailyWTReport_BreakTime::class) ||
                        $blPipe->hasInstanceOf(HumanResources_BL_DailyWTReport_LimitWorkingTime::class)) {
                        $blPipeData->allowTimesheetOverlap = false;
                    }
                    $blPipeData->convertTimeSheetsToTimeSlots($timeSheets[$dateStr]);
                }

                $blPipe->execute($blPipeData, false);

                if (null === $oldReport) {
                    $this->create($blPipeData->result);
                    $dailyTransaction->commit();
                    $this->_reportResult['created'] += 1;
                } else {
                    if (!$blPipeData->result->diff($oldReport)->isEmpty()) {
                        $this->update($blPipeData->result);
                        $dailyTransaction->commit();
                        $this->_reportResult['updated'] += 1;
                    } else {
                        // just cleanup
                        $dailyTransaction->commit();
                    }
                }
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' could not create daily wt report for '
                    . $this->_currentDate->toString());
                $this->_reportResult['errors'] += 1;

                if (isset($existingReports[$dateStr])) {
                    $oldReport = $existingReports[$dateStr]->getCleanClone();
                    $oldReport->calculation_failure = 1;
                    $oldReport->system_remark =
                        Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME)
                            ->_('unexpected error: ') . $e->getMessage();

                    $this->update($oldReport);
                } else {
                    if (null !== $monthlyWTR) {
                        $this->create(new HumanResources_Model_DailyWTReport([
                            'employee_id' => $employee,
                            'monthlywtreport' => $monthlyWTR->getId(),
                            'date' => clone $this->_currentDate,
                            'calculation_failure' => 1,
                            'system_remark' =>
                                Tinebase_Translation::getTranslation(HumanResources_Config::APP_NAME)
                                    ->_('unexpected error: ') . $e->getMessage(),
                        ]));
                    } else {
                        Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' no monthly WTR available!');
                    }
                }

                $dailyTransaction->commit();
            }

            unset($dailyTransaction); // will trigger a rollback if not committed
        }

        if (count($this->_monthlyWTR) > 0) {
            // import sort first! we need to recalculate the oldest month (that will trigger recalculation of all following months!)
            ksort($this->_monthlyWTR);
            HumanResources_Controller_MonthlyWTReport::getInstance()->recalculateReport(current($this->_monthlyWTR));
        }

        // to satifisfy unused variable check
        unset($rcRaii);
        unset($multiServerLockRAII);
        unset($aclRaii);

        return $this->_reportResult;
    }

    /**
     * @param HumanResources_Model_WorkingTimeScheme $_wts
     * @return Tinebase_BL_Pipe | false
     */
    protected function _getBLPipe(HumanResources_Model_WorkingTimeScheme $_wts)
    {
        if (!isset($this->_wtsBLPipes[$_wts->getId()])) {
            if (! $_wts->blpipe instanceof Tinebase_Record_RecordSet) {
                $_wts->blpipe = new Tinebase_Record_RecordSet(HumanResources_Model_BLDailyWTReport_Config::class);
            }
            $rs = $_wts->blpipe->getClone(true);
            $record = new HumanResources_Model_BLDailyWTReport_Config([
                HumanResources_Model_BLDailyWTReport_Config::FLDS_CLASSNAME =>
                    HumanResources_Model_BLDailyWTReport_PopulateReportConfig::class,
                HumanResources_Model_BLDailyWTReport_Config::FLDS_CONFIG_RECORD => [],
            ]);
            $record->runConvertToRecord();
            $rs->addRecord($record);
            $this->_wtsBLPipes[$_wts->getId()] = new Tinebase_BL_Pipe($rs);
        }
        return $this->_wtsBLPipes[$_wts->getId()];
    }

    /**
     * @param string $dateStr
     * @param string $feastCalendarId
     * @return Tinebase_Record_RecordSet|null
     */
    protected function _getFeastTimes($dateStr, $feastCalendarId)
    {
        if (!isset($this->_feastDays[$feastCalendarId])) {
            $this->_feastDays[$feastCalendarId] = [];
            /** @var Calendar_Model_EventFilter $filter */
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Calendar_Model_Event::class, [
                ['field' => 'container_id', 'operator' => 'equals', 'value' => $feastCalendarId],
                ['field' => 'period', 'operator' => 'within', 'value' => [
                    'from' => $this->_startDate,
                    'until' => $this->_endDate,
                ]]
            ]);

            $events = Calendar_Controller_Event::getInstance()->search($filter);
            Calendar_Model_Rrule::mergeAndRemoveNonMatchingRecurrences($events, $filter);

            // turn off acl?
            /** @var Calendar_Model_Event $event */
            foreach ($events as $event) {
                $event->dtstart->setTimezone($event->originator_tz);
                $day = $event->dtstart->format('Y-m-d');
                if (!isset($this->_feastDays[$feastCalendarId][$day])) {
                    $this->_feastDays[$feastCalendarId][$day] =
                        new Tinebase_Record_RecordSet(Calendar_Model_Event::class);
                }
                $this->_feastDays[$feastCalendarId][$day]->addRecord($event);
            }
        }

        return isset($this->_feastDays[$feastCalendarId][$dateStr]) ? $this->_feastDays[$feastCalendarId][$dateStr]
            : null;
    }

    protected function _getLastClearedWTR()
    {
        return HumanResources_Controller_MonthlyWTReport::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_MonthlyWTReport::class, [
                ['field' => HumanResources_Model_MonthlyWTReport::FLDS_EMPLOYEE_ID, 'operator' => 'equals', 'value' => $this->_employee->getId()],
                ['field' => HumanResources_Model_MonthlyWTReport::FLDS_IS_CLEARED, 'operator' => 'equals', 'value' => true]
            ]), new Tinebase_Model_Pagination([
            'sort'  => HumanResources_Model_MonthlyWTReport::FLDS_MONTH,
            'dir'   => 'DESC',
            'limit' => 1
        ]))->getFirstRecord();
    }

    /**
     * @param bool $force
     * @return Tinebase_DateTime
     */
    protected function _getStartDate($force)
    {
        $default = Tinebase_Model_Filter_Date::getFirstDayOf(Tinebase_Model_Filter_Date::MONTH_LAST);
        if ($lastClearedReport = $this->_getLastClearedWTR()) {
            $start_date = (new Tinebase_DateTime($lastClearedReport->{HumanResources_Model_MonthlyWTReport::FLDS_MONTH}
                . '-01 00:00:00'))->addMonth(1);
        } elseif ($this->_employee->contracts instanceof Tinebase_Record_RecordSet && count($this->_employee->contracts) > 0) {
            $this->_employee->contracts->sort('start_date');
            if ($this->_employee->contracts->getFirstRecord()->start_date instanceof Tinebase_DateTime) {
                $start_date = clone $this->_employee->contracts->getFirstRecord()->start_date;
            } else {
                return $default;
            }
        } else {
            return $default;
        }

        if ($force) {
            return $start_date;
        }

        $lastReport = HumanResources_Controller_MonthlyWTReport::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_MonthlyWTReport::class, [
                ['field' => HumanResources_Model_MonthlyWTReport::FLDS_EMPLOYEE_ID, 'operator' => 'equals', 'value' => $this->_employee->getId()],
                ['field' => HumanResources_Model_MonthlyWTReport::FLDS_IS_CLEARED, 'operator' => 'equals', 'value' => false]
            ]), new Tinebase_Model_Pagination([
                'sort'  => HumanResources_Model_MonthlyWTReport::FLDS_MONTH,
                'dir'   => 'DESC',
                'limit' => 1
            ]))->getFirstRecord();
        if ($lastReport) {
            $time = ($lastReport->{HumanResources_Model_MonthlyWTReport::FLDS_LAST_CALCULATION} ?: $lastReport->creation_time)->getCLone()->subMinute(5);
            if ($this->_employee->contracts->filter(function(HumanResources_Model_Contract $c) use($time) {
                        if ($c->last_modified_time && $c->last_modified_time->isLater($time)) return true;
                        return false;
                    })->count() === 0) {
                // no contract changes
                // check TS
                $filterData = [
                    ['field' => 'account_id', 'operator' => 'equals', 'value' => $this->_employee->account_id],
                    ['field' => 'start_date', 'operator' => 'after_or_equals', 'value' => $start_date->format('Y-m-d')],
                    ['field' => 'start_date', 'operator' => 'before_or_equals', 'value' => $this->_getEndDate()->format('Y-m-d')],
                ];

                // fetch all timesheets of an employee that changed after last calculation and that start_date after time of interest
                $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                    Timetracker_Model_Timesheet::class,
                    $filterData
                );
                $filter->addFilterGroup(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                    Timetracker_Model_Timesheet::class,
                    [
                        ['field' => 'last_modified_time', 'operator' => 'after_or_equals', 'value' => $time],
                        ['field' => 'creation_time', 'operator' => 'after_or_equals', 'value' => $time],
                    ],
                    'OR'
                ));

                if (($firstTS = Timetracker_Controller_Timesheet::getInstance()->search($filter, new Tinebase_Model_Pagination([
                            'sort'  => 'start_date',
                            'dir'   => 'ASC',
                            'limit' => 1
                        ]))->getFirstRecord()) && $firstTS->start_date->isLater($start_date)) {
                    $chgDataDate = $firstTS->start_date;
                } else {
                    $chgDataDate = new Tinebase_DateTime($lastReport->month . '-01 00:00:00');
                }

                // check FreeTime
                $filterData = [
                    ['field' => 'employee_id', 'operator' => 'equals', 'value' => $this->_employee->getId()],
                    ['field' => 'lastday_date', 'operator' => 'after_or_equals', 'value' => $start_date->format('Y-m-d')],
                    ['field' => 'firstday_date', 'operator' => 'before_or_equals', 'value' => $this->_getEndDate()->format('Y-m-d')],
                ];
                // fetch all freetimes of an employee that changed after last calculation and that lastday_date after time of interest
                $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                    HumanResources_Model_FreeTime::class,
                    $filterData
                );
                $filter->addFilterGroup(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                    HumanResources_Model_FreeTime::class,
                    [
                        ['field' => 'last_modified_time', 'operator' => 'after_or_equals', 'value' => $time],
                        ['field' => 'creation_time', 'operator' => 'after_or_equals', 'value' => $time],
                    ],
                    'OR'
                ));

                if (($firstFT = HumanResources_Controller_FreeTime::getInstance()->search($filter, new Tinebase_Model_Pagination([
                            'sort'  => 'firstday_date',
                            'dir'   => 'ASC',
                            'limit' => 1
                        ]))->getFirstRecord()) && $firstFT->firstday_date->isEarlier($chgDataDate)) {
                    $chgDataDate = $firstFT->firstday_date;
                }

                $start_date = $chgDataDate;
            }
        }

        return $start_date;
    }

    /**
     * @return Tinebase_DateTime
     */
    protected function _getEndDate()
    {
        return Tinebase_DateTime::now()->setTime(23,59,59);
    }

    /**
     * @return HumanResources_Model_MonthlyWTReport
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _getOrCreateMonthlyWTR()
    {
        $month = $this->_currentDate->format('Y-m');

        if (!isset($this->_monthlyWTR[$month])) {
            $monthlyWTR = HumanResources_Controller_MonthlyWTReport::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_MonthlyWTReport::class, [
                    ['field' => HumanResources_Model_MonthlyWTReport::FLDS_EMPLOYEE_ID, 'operator' => 'equals',
                        'value' => $this->_employee->getId()],
                    ['field' => HumanResources_Model_MonthlyWTReport::FLDS_MONTH, 'operator' => 'equals',
                        'value' => $month],
                ]))->getFirstRecord();
            if (null === $monthlyWTR) {
                $monthlyWTR = HumanResources_Controller_MonthlyWTReport::getInstance()->create(
                    new HumanResources_Model_MonthlyWTReport([
                        HumanResources_Model_MonthlyWTReport::FLDS_EMPLOYEE_ID => $this->_employee->getId(),
                        HumanResources_Model_MonthlyWTReport::FLDS_MONTH => $month,
                    ]));
            }
            $this->_monthlyWTR[$month] = $monthlyWTR;
        }

        return $this->_monthlyWTR[$month];
    }

    /**
     * returns the employees DailyWTReports within interval as an array indexed by Y-m-d
     *
     * @return array
     */
    protected function _getEmployeesReports()
    {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_DailyWTReport::class, [
            ['field' => 'employee_id', 'operator' => 'AND', 'value' => [
                ['field' => ':id', 'operator' => 'equals', 'value' => $this->_employee->getId()]
            ]],
            ['field' => 'date', 'operator' => 'after_or_equals', 'value' => $this->_startDate->format('Y-m-d')],
            ['field' => 'date', 'operator' => 'before_or_equals', 'value' => $this->_endDate->format('Y-m-d')],
        ]);

        $result = [];
        /** @var HumanResources_Model_DailyWTReport $dwtr */
        foreach (HumanResources_Controller_DailyWTReport::getInstance()->search($filter, null, new Tinebase_Record_Expander(
                    HumanResources_Model_DailyWTReport::class, [
                        Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                            'employee_id' => [],
                        ]
                    ]
                )) as $dwtr) {
            $dwtr->relations = Tinebase_Relations::getInstance()->getRelations(
                HumanResources_Model_DailyWTReport::class,
                Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                $dwtr->getId());
            $result[$dwtr->date->format('Y-m-d')] = $dwtr;
        }
        return $result;
    }

    /**
     * returns the employees timesheets within interval as an array of RecordSets indexed by Y-m-d
     *
     * @return array
     */
    protected function _getEmployeesTimesheets()
    {
        $filterData = [
            ['field' => 'account_id', 'operator' => 'equals', 'value' => $this->_employee->account_id],
            ['field' => 'process_status', 'operator' => 'equals', 'value' => Timetracker_Config::TS_PROCESS_STATUS_ACCEPTED],
            ['field' => 'start_date', 'operator' => 'after_or_equals', 'value' => $this->_startDate->format('Y-m-d')],
            ['field' => 'start_date', 'operator' => 'before_or_equals', 'value' => $this->_endDate->format('Y-m-d')],
        ];

        // fetch all accepted timesheets of an employee within current start/end date
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Timetracker_Model_Timesheet::class,
            $filterData
        );

        $timeSheets = Timetracker_Controller_Timesheet::getInstance()->search($filter);
        (new Tinebase_Record_Expander(Timetracker_Model_Timesheet::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                HumanResources_Model_FreeTimeType::TT_TS_SYSCF_ABSENCE_REASON => [],
                HumanResources_Model_FreeTimeType::TT_TS_SYSCF_CLOCK_OUT_REASON => [],
            ],
        ]))->expand($timeSheets);
        // remove timesheets that have an absence reason with no wage_type assigned
        $timeSheets = $timeSheets->filter(function(Timetracker_Model_Timesheet $ts) {
            return empty($ts->{HumanResources_Model_FreeTimeType::TT_TS_SYSCF_ABSENCE_REASON}) ||
                !empty($ts->{HumanResources_Model_FreeTimeType::TT_TS_SYSCF_ABSENCE_REASON}->wage_type);
        });

        $result = [];
        /** @var Timetracker_Model_Timesheet $ts */
        foreach ($timeSheets as $ts) {
            $day = $ts->start_date->format('Y-m-d');
            if (!isset($result[$day])) {
                $result[$day] = new Tinebase_Record_RecordSet(Timetracker_Model_Timesheet::class, []);
            }
            $result[$day]->addRecord($ts);
        }
        return $result;
    }

    /**
     * returns the employees timesheets within interval as an array of RecordSets indexed by Y-m-d
     *
     * @return array
     */
    protected function _getEmployeesFreeTimes()
    {
        $start = $this->_startDate->format('Y-m-d');
        $end = $this->_endDate->format('Y-m-d');
        $filterData = [
            ['field' => 'employee_id', 'operator' => 'equals', 'value' => $this->_employee->getId()],
            ['field' => 'lastday_date', 'operator' => 'after_or_equals', 'value' => $start],
            ['field' => 'firstday_date', 'operator' => 'before_or_equals', 'value' => $end],
            ['field' => HumanResources_Model_FreeTime::FLD_PROCESS_STATUS, 'operator' => 'equals', 'value' => HumanResources_Config::FREE_TIME_PROCESS_STATUS_ACCEPTED],
        ];

        // fetch all freetime of an employee between start and end
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            HumanResources_Model_FreeTimeFilter::class,
            $filterData
        );
        $freeTimes = HumanResources_Controller_FreeTime::getInstance()->search($filter);
        if ($freeTimes->count() === 0) return [];

        Tinebase_ModelConfiguration::resolveRecordsPropertiesForRecordSet($freeTimes,
            HumanResources_Model_FreeTime::getConfiguration());
        // expand required properties
        $expander = new Tinebase_Record_Expander(HumanResources_Model_FreeTime::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'type' => [],
            ],
        ]);
        $expander->expand($freeTimes);

        $result = [];
        /** @var HumanResources_Model_FreeTime $ft */
        foreach ($freeTimes as $ft) {
            /** @var HumanResources_Model_FreeDay $fd */
            foreach ($ft->freedays as $fd) {
                $day = $fd->date->format('Y-m-d');
                if (!$fd->sickoverwrite && $day >= $start && $day <= $end) {
                    if (!isset($result[$day])) {
                        $result[$day] = new Tinebase_Record_RecordSet(HumanResources_Model_FreeTime::class, []);
                    }
                    $result[$day]->addRecord($ft);
                }
            }
        }
        return $result;
    }

    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        if (isset($this->_requestContext[self::RC_JSON_REQUEST])) {
            // _("daily wt reports can't be created")
            throw new Tinebase_Exception_SystemGeneric("daily wt reports can't be created", 600, HumanResources_Config::APP_NAME);
        }
    }

    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        if (isset($this->_requestContext[self::RC_JSON_REQUEST])) {
            $allowedProperties = [
                'evaluation_period_start_correction' => true,
                'evaluation_period_end_correction' => true,
                'working_time_target_correction' => true,
                'user_remark' => true,
            ];
            if ($this->allowCorrectionUpdate) {
                $allowedProperties[HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_CORRECTION] = true;
            }
            foreach ($_record->getFields() as $prop) {
                if (!isset($allowedProperties[$prop])) {
                    $_record->{$prop} = $_oldRecord->{$prop};
                }
            }
        }

        if (($_record->is_cleared || $_oldRecord->is_cleared) && (!isset($this->_requestContext[self::RC_ALLOW_IS_CLEARED]) ||
                !$this->_requestContext[self::RC_ALLOW_IS_CLEARED])) {
            // _('It is not allowed to update a cleared report')
            throw new Tinebase_Exception_SystemGeneric('It is not allowed to update a cleared report', 600, HumanResources_Config::APP_NAME);
        }
    }

    /**
     * inspect update of one record (after update)
     *
     * @param   Tinebase_Record_Interface $updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $record          the update record
     * @param   Tinebase_Record_Interface $currentRecord   the current record (before update)
     * @return  void
     */
    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        foreach (['evaluation_period_start_correction',
                  'evaluation_period_end_correction',
                  'working_time_correction',
                  'working_time_target_correction'] as $prop) {
            if ($currentRecord->{$prop} !== $updatedRecord->{$prop}) {
                $employee = HumanResources_Controller_Employee::getInstance()->get($updatedRecord->employee_id);
                $this->calculateReportsForEmployee($employee, $updatedRecord->date, $updatedRecord->date);
                break;
            }
        }
    }

    public function recalcCorrection(string $id)
    {
        $correction = 0;
        foreach (HumanResources_Controller_WTRCorrection::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_WTRCorrection::class,[
                ['field' => HumanResources_Model_WTRCorrection::FLD_WTR_DAILY, 'operator' => 'equals', 'value' => $id],
                ['field' => HumanResources_Model_WTRCorrection::FLD_STATUS, 'operator' => 'equals', 'value' => HumanResources_Config::WTR_CORRECTION_STATUS_ACCEPTED],
            ])) as $c) {
            $correction += intval($c->{HumanResources_Model_WTRCorrection::FLD_CORRECTION});
        }

        $oldAclVal = $this->doContainerACLChecks(false);
        $oldCorrectionVal = $this->allowCorrectionUpdate;
        $this->allowCorrectionUpdate = true;
        $raii = new Tinebase_RAII(function() use($oldAclVal, $oldCorrectionVal) {
            $this->doContainerACLChecks($oldAclVal);
            $this->allowCorrectionUpdate = $oldCorrectionVal;
        });

        $record = $this->get($id);
        if (intval($record->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_CORRECTION}) !== $correction) {
            $record->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_CORRECTION} = $correction;
            $this->update($record);
        }

        unset($raii);
    }

    /**
     * implement logic for each controller in this function
     *
     * @param Tinebase_Event_Abstract $_eventObject
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        switch (get_class($_eventObject)) {
            case Tinebase_Event_Record_Update::class:
                if ($_eventObject->observable instanceof Timetracker_Model_Timesheet) {
                    if (! HumanResources_Config::getInstance()->featureEnabled(
                            HumanResources_Config::FEATURE_CALCULATE_DAILY_REPORTS) &&
                        ! HumanResources_Config::getInstance()->featureEnabled(
                            HumanResources_Config::FEATURE_WORKING_TIME_ACCOUNTING)
                    ) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                            __METHOD__ . '::' . __LINE__ . ' FEATURE_WORKING_TIME_ACCOUNTING/FEATURE_CALCULATE_DAILY_REPORTS disabled - Skipping.'
                        );
                        break;
                    }

                    // context async / sync
                    $context = (array)HumanResources_Controller_DailyWTReport::getInstance()->getRequestContext();
                    if (isset($context['tsSyncron'])) {
                        $this->calculateReportsForAccountId($_eventObject->observable->account_id,
                            $_eventObject->observable->start_date);
                    } else {
                        Tinebase_ActionQueue::getInstance()->queueAction(HumanResources_Controller_DailyWTReport::class
                            . '.calculateReportsForAccountId', $_eventObject->observable->account_id,
                            $_eventObject->observable->start_date);
                    }

                } elseif ($_eventObject->observable instanceof HumanResources_Model_FreeTime) {
                    /* order matters start */
                    $tsRaii = new Tinebase_RAII(Timetracker_Controller_Timesheet::getInstance()->assertPublicUsage());
                    $mRaii = new Tinebase_RAII(HumanResources_Controller_MonthlyWTReport::getInstance()->assertPublicUsage());
                    $dRaii = new Tinebase_RAII($this->assertPublicUsage());
                    /* order matters end */

                    $this->calculateReportsForEmployee(HumanResources_Controller_Employee::getInstance()->get(
                        $_eventObject->observable->getIdFromProperty('employee_id')), $_eventObject->observable->start_date);

                    /* order matters start */
                    unset($dRaii);
                    unset($mRaii);
                    unset($tsRaii);
                    /* order matters end */
                }
                break;
        }
    }

    public function calculateReportsForAccountId(string $accountId, string $date): void
    {
        $oldUser = Tinebase_Core::getUser();
        if ($oldUser->accountLoginName !== Tinebase_User::SYSTEM_USER_CRON) {
            $oldUserRaii = new Tinebase_RAII(function() use($oldUser) {
                Tinebase_Core::setUser($oldUser);
            });
            $cronUser = Tinebase_User::createSystemUser(Tinebase_User::SYSTEM_USER_CRON);
            Tinebase_Core::setUser($cronUser);
        }

        /** @var HumanResources_Model_Employee $employee */
        $employee = HumanResources_Controller_Employee::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            HumanResources_Model_Employee::class, [
                ['field' => 'account_id', 'operator' => 'equals', 'value' => $accountId],
            ]
        ))->getFirstRecord();

        if ($employee) {
            $this->calculateReportsForEmployee($employee, new Tinebase_DateTime($date));
        }

        unset($oldUserRaii);
    }
}

<?php
/**
 * Timesheet controller for Timetracker application
 * 
 * @package     Timetracker
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * Timesheet controller class for Timetracker application
 * 
 * @package     Timetracker
 * @subpackage  Controller
 */
class Timetracker_Controller_Timesheet extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * should deadline be checked
     * 
     * @var boolean
     */
    protected $_doCheckDeadline = TRUE;

    /**
     * custom acl switch
     *
     * @var boolean
     */
    protected $_doTimesheetContainerACLChecks = TRUE;

    /**
     * check deadline or not
     * 
     * @return boolean
     */
    public function doCheckDeadLine()
    {
        $value = (func_num_args() === 1) ? (bool) func_get_arg(0) : NULL;
        return $this->_setBooleanMemberVar('_doCheckDeadline', $value);
    }
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    protected function __construct() {
        
        // config
        $this->_applicationName = Timetracker_Config::APP_NAME;
        $this->_backend = new Timetracker_Backend_Timesheet();
        $this->_modelName = Timetracker_Model_Timesheet::class;
        $this->_resolveCustomFields = TRUE;
        
        // disable container ACL checks as we don't init the 'Shared Timesheets' grants in the setup
        $this->_doContainerACLChecks = FALSE;
        
        // use modlog and don't completely delete records
        $this->_purgeRecords = FALSE;
    }
    
    /**
     * field grants for specific timesheet fields
     *
     * @var array
     */
    protected $_fieldGrants = array(
        'is_billable' => array('default' => 1,  'requiredGrant' => Timetracker_Model_TimeaccountGrants::MANAGE_BILLABLE),
        'billed_in'   => array('default' => '', 'requiredGrant' => Tinebase_Model_Grants::GRANT_ADMIN),
        'is_cleared'  => array('default' => 0,  'requiredGrant' => Tinebase_Model_Grants::GRANT_ADMIN),
    );

    /**
     * @deprecated use destroyInstance
     */
    public static function unsetInstance()
    {
        self::destroyInstance();
    }

    /****************************** functions ************************/

    /**
     * get all timesheets for a timeaccount
     *
     * @param string $_timeaccountId
     * @return Tinebase_Record_RecordSet of Timetracker_Model_Timesheet records
     */
    public function getTimesheetsByTimeaccountId($_timeaccountId)
    {
        $filter = new Timetracker_Model_TimesheetFilter(array(
            array('field' => 'timeaccount_id', 'operator' => 'AND', 'value' => array(
                array('field' => 'id', 'operator' => 'equals', 'value' => $_timeaccountId),
            ))
        ));
        
        $records = $this->search($filter);
        
        return $records;
    }
    
    /**
     * find timesheets by the given arguments. the result will be returned in an array
     *
     * @param string $timeaccountId
     * @param Tinebase_DateTime $startDate
     * @param Tinebase_DateTime $endDate
     * @param string $destination
     * @param string $taCostCenter
     * @param string $cacheId
     * @return array
     *
     * @deprecated can be removed?
     */
    public function findTimesheetsByTimeaccountAndPeriod($timeaccountId, $startDate, $endDate, $destination = NULL, $taCostCenter = NULL)
    {
        $filter = new Timetracker_Model_TimesheetFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $timeaccountId)));
        $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'start_date', 'operator' => 'before', 'value' => $endDate)));
        $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'start_date', 'operator' => 'after', 'value' => $startDate)));
        $filter->addFilter(new Tinebase_Model_Filter_Bool(array('field' => 'is_cleared', 'operator' => 'equals', 'value' => true)));
        
        $timesheets = $this->search($filter);
        
        $matrix = array();
        foreach ($timesheets as $ts) {
            $matrix[] = array(
                'userAccountId' => $ts->account_id,
                'amount' => ($ts->duration / 60),
                'destination' => $destination,
                'taCostCenter' => $taCostCenter
            );
        }
        
        return $matrix;
    }

    /**
     * calculates duration, start and end from given value
     *
     * @param Timetracker_Model_Timesheet $_record
     * @return void
     */
    protected function _calculateTimes(Timetracker_Model_Timesheet $_record)
    {
        $start_date = $_record->start_date instanceof Tinebase_DateTime ? $_record->start_date->format('Y-m-d') :
            substr((string)$_record->start_date, 0, 10);
        $duration = $_record->duration;
        $start = $_record->start_time;
        $end = $_record->end_time;

        // If start and end ist given calculate duration and overwrite default
        if (isset($start) && isset($end)){
            $start = new DateTime($start_date . ' ' . $start);
            $end = new DateTime($start_date . ' ' . $end);

            if ('00:00:00' === $_record->end_time || '00:00' === $_record->end_time || $end < $start) {
                $end->modify('+1 days');
            }

            $dtDiff = $end->diff($start);
            $_record->duration = $duration = ($dtDiff->d * 24 + $dtDiff->h) * 60 + $dtDiff->i;
        } else if (isset($duration) && isset($start)){
            // If duration and start is set calculate the end
            $start = new DateTime($start_date . ' ' . $start);
            
            $end = $start->modify('+' . $duration . ' minutes');
            $_record->end_time = $end->format('H:i:00');

        } else if (isset($duration) && isset($end)){
            // If start is not set but duration and end calculate start instead
            $end = new DateTime($start_date . ' ' . $end);
            if ('00:00:00' === $_record->end_time || '00:00' === $_record->end_time) {
                $end->modify('+1 days');
            }

            $start = $end->modify('-' . $duration . ' minutes');
            $_record->start_time = $start->format('H:i:00');
        }

        $_record->accounting_time = $duration = intval($duration);
        if ($duration > 0) {
            $factor = $_record->accounting_time_factor;
            if (null === $factor || '' === $factor) {
                $factor = $_record->accounting_time_factor = 1;
            }
            $method = Timetracker_Config::getInstance()->{Timetracker_Config::ACCOUNTING_TIME_ROUNDING_METHOD};
            if (!in_array($method, ['ceil', 'floor', 'round'])) {
                $method = 'ceil';
            }
            $minutes = intval(Timetracker_Config::getInstance()->{Timetracker_Config::ACCOUNTING_TIME_ROUNDING_MINUTES});
            if ($minutes < 1 || $minutes > 60) {
                $minutes = 15;
            }
            $_record->accounting_time = $method($duration * (float)$factor / $minutes) * $minutes;
        }
        
        if ($_record->is_billable === false || $_record->is_billable === 0) {
            $_record->accounting_time = 0;
        }
    }
    
    /**
     * checks deadline of record
     * 
     * @param Timetracker_Model_Timesheet $_record
     * @param boolean $_throwException
     * @return void
     * @throws Timetracker_Exception_Deadline
     */
    protected function _checkDeadline(Timetracker_Model_Timesheet $_record, $_throwException = TRUE)
    {
        if (! $this->_doCheckDeadline) {
            return;
        }
        
        // get timeaccount
        $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->get($_record->timeaccount_id);
        
        if (isset($timeaccount->deadline) && $timeaccount->deadline == Timetracker_Model_Timeaccount::DEADLINE_LASTWEEK) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Check if deadline is exceeded for timeaccount ' . $timeaccount->title);
            
            // it is only on monday allowed to add timesheets for last week
            $date = new Tinebase_DateTime();
            
            $date->setTime(0,0,0);
            $dayOfWeek = $date->get('w');
            
            if ($dayOfWeek >= 2) {
                // only allow to add ts for this week
                $date->sub($dayOfWeek-1, Tinebase_DateTime::MODIFIER_DAY);
            } else {
                // only allow to add ts for last week
                $date->sub($dayOfWeek+6, Tinebase_DateTime::MODIFIER_DAY);
            }
            
            // convert start date to Tinebase_DateTime
            $startDate = new Tinebase_DateTime($_record->start_date);
            if ($date->compare($startDate) >= 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Deadline exceeded: ' . $startDate . ' < ' . $date);
                
                if ($this->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE)
                     || Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, Tinebase_Model_Grants::GRANT_ADMIN)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                        . ' User with admin / manage all rights is allowed to save Timesheet even if it exceeds the deadline.'
                    );
                } else if ($_throwException) {
                    throw new Timetracker_Exception_Deadline();
                }
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Valid date: ' . $startDate . ' >= ' . $date);
            }
        }
    }
    
    /****************************** overwritten functions ************************/    

    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = self::ACTION_GET)
    {
        $result = parent::searchCount($_filter, $_action);

        if (class_exists('HumanResources_Config') && Tinebase_Application::getInstance()->isInstalled(HumanResources_Config::APP_NAME, true)
                    && ($periodFilter = $_filter->findFilterWithoutOr('start_date'))
                    && ($aFilter = $_filter->findFilterWithoutOr('account_id')) && $aFilter->getOperator() === TMFA::OP_EQUALS
                    && ($accountId = $aFilter->toArray()['value'] ?? null)) {
            $oldEmployeeAcl = HumanResources_Controller_Employee::getInstance()->doContainerACLChecks(false);
            try {
                $employee = HumanResources_Controller_Employee::getInstance()->search(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Employee::class, [
                        [TMFA::FIELD => 'account_id', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $accountId]
                    ]))->getFirstRecord();
            } finally {
                HumanResources_Controller_Employee::getInstance()->doContainerACLChecks($oldEmployeeAcl);
            }

            // ATTENTION employee has been retrieved without ACL check, this code here only used the employee id. Do not use employee data unless checking ACL first!
            if ($employee) {
                /** @var Tinebase_Model_Filter_Date $periodFilter */
                try {
                    $fromUntil = ['from' => $periodFilter->getStartOfPeriod(), 'until' => $periodFilter->getEndOfPeriod()];
                } catch (Tinebase_Exception_UnexpectedValue) {
                    return $result;
                }
                $from = $fromUntil['from'];
                $from->hasTime(false);
                $until = $fromUntil['until'];
                $until->hasTime(false);

                // get contracts
                $contracts = HumanResources_Controller_Contract::getInstance()->getValidContracts($fromUntil, $employee->getId());
                $turnOverGoal = 0;
                /** @var HumanResources_Model_Contract $contract */
                foreach ($contracts as $contract) {
                    if (0 === ($yGoal = (int)$contract->{HumanResources_Model_Contract::FLD_YEARLY_TURNOVER_GOAL})) {
                        continue;
                    }
                    $f = ($from->isLater($contract->start_date) ? $from : $contract->start_date)->getClone();
                    $u = (!$contract->end_date || $until->isEarlier($contract->end_date) ? $until : $contract->end_date)
                        ->getClone();

                    $multiplier = 0.0;
                    for (;(int)$f->format('Y') < (int)$u->format('Y'); $f->addYear(1)) {
                        $daysOfYear = $f->format('L') === '1' ? 366 : 365;
                        $multiplier += ($daysOfYear - (int)$f->format('z')) / $daysOfYear;
                        $f->setDate((int)$f->format('Y'), 1, 1);
                    }
                    $daysOfYear = $u->format('L') === '1' ? 366 : 365;
                    $multiplier += ((int)$u->format('z') - (int)$f->format('z') + 1) / $daysOfYear;

                    $turnOverGoal += round($yGoal * $multiplier, 2);
                }
                $result['turnOverGoal'] = $turnOverGoal;
                $result['workingTimeTarget'] = $this->_getDailyWorkingTimeTarget($employee, $from, $until);
            }
        }

        return $result;
    }

    protected function _getDailyWorkingTimeTarget($employee, $from, $until): int
    {
        $workingTarget = 0;
        /** @var HumanResources_Model_DailyWTReport $dailyWTR */
        foreach (HumanResources_Controller_DailyWTReport::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                HumanResources_Model_DailyWTReport::class, [
                [TMFA::FIELD => 'employee_id', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $employee->getId()],
                [TMFA::FIELD => 'date', TMFA::OPERATOR => 'within', TMFA::VALUE => ['from' => $from, 'until' => $until]],
            ]), _getRelations: new Tinebase_Record_Expander(HumanResources_Model_DailyWTReport::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                HumanResources_Model_DailyWTReport::FLDS_WORKING_TIMES => [],
            ],
        ])) as $dailyWTR) {
            $workingTarget += $dailyWTR->getShouldWorkingTime();
            if ($dailyWTR->{HumanResources_Model_DailyWTReport::FLDS_WORKING_TIMES}) {
                foreach ($dailyWTR->{HumanResources_Model_DailyWTReport::FLDS_WORKING_TIMES}->filter(
                    fn($rec) => HumanResources_Model_WageType::ID_SALARY !== $rec->getIdFromProperty(
                            HumanResources_Model_BLDailyWTReport_WorkingTime::FLDS_WAGE_TYPE
                        )
                ) as $wt) {
                    $workingTarget -= $wt->{HumanResources_Model_BLDailyWTReport_WorkingTime::FLDS_DURATION};
                }
            }
        }
        return $workingTarget;
    }

    /**
     * inspect creation of one record
     * 
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        parent::_inspectBeforeCreate($_record);

        /** @var Timetracker_Model_Timesheet $_record */
        $this->_checkDeadline($_record);
        $this->_calculateTimes($_record);
        $this->_calcAmounts($_record);
    }

    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        /** @var Timetracker_Model_Timesheet $_createdRecord */
        parent::_inspectAfterCreate($_createdRecord, $_record);

        $this->fireEvent($_createdRecord);
    }

    /**
     * inspect update of one record
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        /** @var Timetracker_Model_Timesheet $_record */
        $this->_checkDeadline($_record);
        $this->_calculateTimes($_record);
        $this->_calcAmounts($_record, $_oldRecord);

        if ($this->isTSDateChanged($_record, $_oldRecord) && $_record->is_cleared && !empty($_record->invoice_id)) {
            //reset invoicing related fields to find the invoice positions
            $_record->is_cleared = false;
            $_record->invoice_id = '';
        }
    }

    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        parent::_inspectAfterUpdate($updatedRecord, $record, $currentRecord);

        /** @var Timetracker_Model_Timesheet $updatedRecord */
        if ($this->isTSDateChanged($updatedRecord, $currentRecord)) {
            //need to clear timesheet after generate invoice position
            if (!$updatedRecord->is_cleared && empty($updatedRecord->invoice_id) && !empty($currentRecord->invoice_id)) {
                $result = Sales_Controller_Invoice::getInstance()->checkForUpdate($currentRecord->invoice_id);
                if (in_array($currentRecord->invoice_id, $result)) {
                    $updatedRecord->is_cleared = true;
                    $updatedRecord->invoice_id = $currentRecord->invoice_id;
                    $this->getBackend()->update($updatedRecord);
                }
            }
        }
        $this->fireEvent($updatedRecord, $currentRecord);
    }

    protected function _inspectDelete(array $_ids)
    {
        $invalidTS = [];
        $records = $this->getMultiple($_ids);
        foreach ($records as $record) {
            if ($record->is_cleared === 1 && !empty($record->invoice_id)) {
                $invalidTS[] = $record;
            }
        }
        if (count($invalidTS) > 0) {
            throw new Sales_Exception_InvoiceAlreadyClearedDelete();
        }
        $_ids = parent::_inspectDelete($_ids);
        return $_ids;
    }

    public function isTSDateChanged(Timetracker_Model_Timesheet $record, ?Timetracker_Model_Timesheet $oldRecord = null): bool
    {
        return $record->duration != $oldRecord->duration || $record->start_date != $oldRecord->start_date || $record->start_time != $oldRecord->start_time;
    }

    protected function _calcAmounts(Timetracker_Model_Timesheet $ts, ?Timetracker_Model_Timesheet $oldTs = null): void
    {
        if (!empty($delegator = Timetracker_Config::getInstance()->{Timetracker_Config::TS_CLEARED_AMOUNT_DELEGATOR})) {
            call_user_func($delegator, $ts, $oldTs);
            return;
        }

        $taCtrl = Timetracker_Controller_Timeaccount::getInstance();
        $oldAcl = $taCtrl->doContainerACLChecks(false);
        try {
            /** @var Timetracker_Model_Timeaccount $ta */
            $ta = Timetracker_Controller_Timeaccount::getInstance()->get($ts->getIdFromProperty('timeaccount_id'));
        } finally {
            $taCtrl->doContainerACLChecks($oldAcl);
        }
        $ts->{Timetracker_Model_Timesheet::FLD_RECORDED_AMOUNT} = round(($ts->accounting_time / 60) * (float)$ta->price, 2);

        if (!$ts->is_cleared) {
            $ts->{Timetracker_Model_Timesheet::FLD_CLEARED_AMOUNT} = null;
            return;
        }
        if ($oldTs?->is_cleared) {
            $ts->{Timetracker_Model_Timesheet::FLD_CLEARED_AMOUNT} = $oldTs?->{Timetracker_Model_Timesheet::FLD_CLEARED_AMOUNT};
            return;
        }

        $ts->{Timetracker_Model_Timesheet::FLD_CLEARED_AMOUNT} = round(($ts->accounting_time / 60) * (float)$ta->price, 2);
    }

    protected function fireEvent(Timetracker_Model_Timesheet $record, ?Timetracker_Model_Timesheet $oldRecord = null)
    {
        $event = new Tinebase_Event_Record_Update();
        $event->observable = $record;
        $event->oldRecord = $oldRecord;
        Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback(function() use($event) {
            Tinebase_Record_PersistentObserver::getInstance()->fireEvent($event);
        });
    }

    protected function _inspectAfterDelete(Tinebase_Record_Interface $record)
    {
        parent::_inspectAfterDelete($record);

        $event = new Tinebase_Event_Record_Delete();
        $event->observable = $record;
        Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback(function() use($event) {
            Tinebase_Record_PersistentObserver::getInstance()->fireEvent($event);
        });
    }

    /**
     * set/get checking ACL rights
     *
     * NOTE: as our business logic here needs $this->>_doContainerACLChecks to be turned off
     *       we introduce a new switch to turn off all grants checking here
     *
     * @param  boolean $setTo
     * @return boolean
     */
    public function doContainerACLChecks($setTo = NULL)
    {
        return $this->_setBooleanMemberVar('_doTimesheetContainerACLChecks', $setTo);
    }

    /**
     * check grant for action
     *
     * @param Timetracker_Model_Timeaccount $_record
     * @param string $_action
     * @param boolean $_throw
     * @param string $_errorMessage
     * @param Timetracker_Model_Timesheet $_oldRecord
     * @return boolean
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_Confirmation
     *
     * @todo think about just setting the default values when user
     *       hasn't the required grant to change the field (instead of throwing exception)
     */
    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        if (! $this->_doTimesheetContainerACLChecks) {
            return TRUE;
        }

        $isAdmin = false;
        // users with MANAGE_TIMEACCOUNTS have all grants here
        if ( $this->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE)
            || Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, Tinebase_Model_Grants::GRANT_ADMIN)) {
            $isAdmin = true;
        }

        // only TA managers are allowed to alter TS of closed TAs, but they have to confirm first that they really want to do it
        if ($_action != 'get') {
            if ($isAdmin && ($this->_requestContext['skipClosedCheck'] ?? false)) {
               return true;
            }

            $this->_validateRelations($_record, $_oldRecord);

            // check if timeaccount->is_billable is false => set default in fieldGrants to 0 and allow only managers to change it
            // if old record is billable, everybody can make it not billable
            $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->get($_record->timeaccount_id);

            if (!$timeaccount->is_billable && (!$_oldRecord || !$_oldRecord->is_billable)) {
                $this->_fieldGrants['is_billable']['default'] = 0;
                $this->_fieldGrants['is_billable']['requiredGrant'] = Tinebase_Model_Grants::GRANT_ADMIN;
            }
        }

        if ($isAdmin === true) {
            return true;
        }


        $hasGrant = FALSE;

        switch ($_action) {
            case 'get':
                $hasGrant = (
                    Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, array(
                        Timetracker_Model_TimeaccountGrants::VIEW_ALL,
                        Timetracker_Model_TimeaccountGrants::BOOK_ALL
                    ))
                    || ($_record->account_id == Tinebase_Core::getUser()->getId() && Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, [
                            Timetracker_Model_TimeaccountGrants::BOOK_OWN,
                            Timetracker_Model_TimeaccountGrants::READ_OWN,
                            Timetracker_Model_TimeaccountGrants::REQUEST_OWN,
                        ]))
                );
                break;
            case 'create':
                $hasGrant = (
                    ($_record->account_id == Tinebase_Core::getUser()->getId() && Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, array_merge([
                            Timetracker_Model_TimeaccountGrants::BOOK_OWN
                        ], Timetracker_Config::TS_PROCESS_STATUS_REQUESTED === $_record->process_status ? [
                            Timetracker_Model_TimeaccountGrants::REQUEST_OWN
                        ] : [])))
                    || Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_ALL)
                );

                if ($hasGrant) {
                    foreach ($this->_fieldGrants as $field => $config) {
                        $fieldValue = $_record->$field;
                        if (isset($fieldValue) && $fieldValue != $config['default']) {
                            $hasGrant &= Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, $config['requiredGrant']);
                        }
                    }
                }

                break;
            case 'update':
                $hasGrant = (
                    ($_record->account_id == Tinebase_Core::getUser()->getId() && Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, array_merge([
                            Timetracker_Model_TimeaccountGrants::BOOK_OWN
                        ], Timetracker_Config::TS_PROCESS_STATUS_REQUESTED === $_record->process_status && $_record->process_status === $_oldRecord->process_status ? [
                            Timetracker_Model_TimeaccountGrants::REQUEST_OWN
                        ] : [])))
                    || Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_ALL)
                );

                if ($hasGrant) {
                    foreach ($this->_fieldGrants as $field => $config) {
                        if (isset($_record->$field) && $_record->$field != $_oldRecord->$field) {
                            $hasGrant &= Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, $config['requiredGrant']);
                        }
                    }
                }

                break;
            case 'delete':
                $hasGrant = (
                    ($_record->account_id == Tinebase_Core::getUser()->getId() && Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_OWN))
                    || Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_ALL)
                );
                break;
        }

        if ($_throw && !$hasGrant) {
            throw new Tinebase_Exception_AccessDenied($_errorMessage);
        }

        return $hasGrant;
    }

    /**
     * Removes timeaccounts where current user has no access to
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        if (! $this->_doTimesheetContainerACLChecks) {
            $_filter->setRequiredGrants([]);
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Container ACL disabled for ' . $_filter->getModelName() . '.');
            return;
        }

        switch ($_action) {
            case 'get':
                $_filter->setRequiredGrants(array(
                    Timetracker_Model_TimeaccountGrants::BOOK_ALL,
                    Timetracker_Model_TimeaccountGrants::MANAGE_BILLABLE,
                    Timetracker_Model_TimeaccountGrants::VIEW_ALL,
                    Tinebase_Model_Grants::GRANT_ADMIN,
                ));
                break;
            case 'update':
                $_filter->setRequiredGrants(array(
                    Timetracker_Model_TimeaccountGrants::BOOK_OWN,
                    Timetracker_Model_TimeaccountGrants::BOOK_ALL,
                    Tinebase_Model_Grants::GRANT_ADMIN,
                ));
                break;
            case 'export':
                $_filter->setRequiredGrants(array(
                    Tinebase_Model_Grants::GRANT_EXPORT,
                    Tinebase_Model_Grants::GRANT_ADMIN,
                ));
                break;
            default:
                throw new Timetracker_Exception_UnexpectedValue('Unknown action: ' . $_action);
        }
    }

    /**
     * attach tags hook
     *
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    public function attachTagsHook($recordIds, $tagId)
    {
        $records = $this->getMultiple($recordIds);
        $tag = Tinebase_Tags::getInstance()->getTagById($tagId);

        foreach ($records as $record) {
            $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->get($record->timeaccount_id);
            if ($timeaccount && !$timeaccount->is_open && $tag['name'] === Sales_Export_TimesheetTimeaccount::TAG_SUM) {
                throw new Tinebase_Exception_SystemGeneric(
                    'Is is not allowed to update tag : ' . $tag['name'] . ', when the related timeaccount is closed'
                );
            }
        }
    }

    /**
     * detach tags hook
     *
     */
    public function detachTagsHook($recordIds, $tagId)
    {
        $records = $this->getMultiple($recordIds);
        $tag = Tinebase_Tags::getInstance()->getTagById($tagId);

        foreach ($records as $record) {
            $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->get($record->timeaccount_id);
            if ($timeaccount && !$timeaccount->is_open && $tag['name'] === Sales_Export_TimesheetTimeaccount::TAG_SUM) {
                throw new Tinebase_Exception_SystemGeneric(
                    'Is is not allowed to update tag : ' . $tag['name'] . ', when the related timeaccount is closed'
                );
            }
        }
    }

    protected function _validateRelations($_record, $_oldRecord)
    {
        $context = $this->getRequestContext();

        if ($context && is_array($context) &&
            (array_key_exists('clientData', $context) && array_key_exists('confirm', $context['clientData'])
                || array_key_exists('confirm', $context))) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Force updating the timesheet');
            return true;
        }

        $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->get($_record->timeaccount_id);
        $translation = Tinebase_Translation::getTranslation($this->_applicationName);
        $exception = null;

        if ($_oldRecord && $this->isTSDateChanged($_record, $_oldRecord) && $_record->is_cleared && !empty($_record->invoice_id)) {
            $exception = new Tinebase_Exception_Confirmation(
                $translation->_('The Invoice you tried to edit is cleared already, change date will rebill the invoice, do you still want to execute this action?')
            );
        }

        if (!$timeaccount->is_open) {
            $exception = new Tinebase_Exception_Confirmation(
                $translation->_('The related Timeaccount is already closed, do you still want to execute this action?')
            );
        }

        if ($exception) {
            throw $exception;
        }

        return true;
    }
}

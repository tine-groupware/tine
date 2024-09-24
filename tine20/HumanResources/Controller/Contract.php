<?php
/**
 * Contract controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * Contract controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_Contract extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;
    use HumanResources_Controller_CheckFilterACLEmployeeTrait;

    /**
     * true if sales is installed
     * 
     * @var boolean
     */
    protected $_useSales = NULL;

    protected $_getMultipleGrant = [HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA];
    protected $_requiredFilterACLget = [
        HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA,
        '|' . HumanResources_Model_DivisionGrants::READ_BASIC_EMPLOYEE_DATA
    ];
    protected $_requiredFilterACLupdate  = [HumanResources_Model_DivisionGrants::UPDATE_EMPLOYEE_DATA];
    protected $_requiredFilterACLsync  = [HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA];
    protected $_requiredFilterACLexport  = [HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA];
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = 'HumanResources';
        $this->_backend = new HumanResources_Backend_Contract();
        $this->_modelName = 'HumanResources_Model_Contract';
        $this->_purgeRecords = FALSE;
        $this->_useSales = Tinebase_Application::getInstance()->isInstalled('Sales', TRUE);
        // activate this if you want to use containers
        $this->_doContainerACLChecks = true;
    }

    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        if (!$this->_doContainerACLChecks) {
            return true;
        }

        // if we have manage_employee right, we have all grants
        if (Tinebase_Core::getUser()->hasRight(HumanResources_Config::APP_NAME, HumanResources_Acl_Rights::MANAGE_EMPLOYEE)) {
            return true;
        }

        switch ($_action) {
            case self::ACTION_GET:
                try {
                    HumanResources_Controller_Employee::getInstance()->get($_record->getIdFromProperty('employee_id'));
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
                $_action = HumanResources_Model_DivisionGrants::UPDATE_EMPLOYEE_DATA;
                break;
        }
        return parent::_checkGrant($_record, $_action, $_throw, $_errorMessage, $_oldRecord);
    }

    protected $_employee = null;
    public function setEmployee(HumanResources_Model_Employee $employee): void
    {
        $this->_employee = $employee;
    }

    protected function _inspectEmploymentEnd(HumanResources_Model_Contract $contract)
    {
        $employee = $contract->employee_id;
        if (is_string($employee)) {
            $employee = HumanResources_Controller_Employee::getInstance()->get($employee);
        }

        if ($employee->employment_end && (!$contract->end_date || $employee->employment_end->isEarlier($contract->end_date))) {
            $contract->end_date = clone $employee->employment_end;
        }
    }

    /**
     * inspect update of one record (before update)
     *
     * @param   HumanResources_Model_Contract $_record      the update record
     * @param   HumanResources_Model_Contract $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        $this->_inspectEmploymentEnd($_record);
        $this->_checkDates($_record);

        if (!empty($_record->{HumanResources_Model_Contract::FLD_WORKING_TIME_SCHEME})) {
            $oldWts = $_oldRecord->{HumanResources_Model_Contract::FLD_WORKING_TIME_SCHEME} ?: null;
            $this->_inspectWorkingTimeScheme($_record, $oldWts);
        }

        $this->_checkDateOverlap($_record);
    }

    protected function _inspectBookedResources(HumanResources_Model_Contract $contract): bool
    {
        $ftCtrl = HumanResources_Controller_FreeTime::getInstance();
        $ftRaii = new Tinebase_RAII($ftCtrl->assertPublicUsage());
        $dwtrCtrl = HumanResources_Controller_DailyWTReport::getInstance();
        $dwtrRaii = new Tinebase_RAII($dwtrCtrl->assertPublicUsage());

        /** @phpstan-ignore-next-line */
        if ($ftCtrl->searchCount(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                HumanResources_Model_FreeTime::class, array_merge([
                    ['field' => 'lastday_date', 'operator' => 'after_or_equals', 'value' => $contract->start_date],
                    ['field' => 'employee_id', 'operator' => 'equals', 'value' => $contract->employee_id],
                    ['field' => HumanResources_Model_FreeTime::FLD_PROCESS_STATUS, 'operator' => 'equals', 'value' => HumanResources_Config::FREE_TIME_PROCESS_STATUS_ACCEPTED],
                ], $contract->end_date ? [
                    ['field' => 'firstday_date', 'operator' => 'before_or_equals', 'value' => $contract->end_date],
                ] : []))) > 0) {
            return false;
        }

        /** @phpstan-ignore-next-line */
        if ($dwtrCtrl->searchCount(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                HumanResources_Model_DailyWTReport::class, array_merge([
                    ['field' => 'date', 'operator' => 'after_or_equals', 'value' => $contract->start_date],
                    ['field' => 'employee_id', 'operator' => 'equals', 'value' => $contract->employee_id],
                    ['field' => 'is_cleared', 'operator' => 'equals', 'value' => true],
                ], $contract->end_date ? [
                    ['field' => 'date', 'operator' => 'before_or_equals', 'value' => $contract->end_date],
                ] : []))) > 0) {
            return false;
        }

        unset($dwtrRaii);
        unset($ftRaii);

        return true;
    }

    public function getFreeTimes(HumanResources_Model_Contract $contract): Tinebase_Record_RecordSet
    {
        $freeTimeFilter = new HumanResources_Model_FreeTimeFilter(array(
            array('field' => 'lastday_date', 'operator' => 'after_or_equals', 'value' => $contract->start_date),
        ));
        
        if ($contract->end_date !== NULL) {
            $freeTimeFilter->addFilter(new Tinebase_Model_Filter_Date(
                array('field' => 'firstday_date', 'operator' => 'before_or_equals', 'value' => $contract->end_date)
            ));
        }
        
        $freeTimeFilter->addFilter(new Tinebase_Model_Filter_Text(
            array('field' => 'employee_id', 'operator' => 'equals', 'value' => $contract->employee_id)
        ));

        return HumanResources_Controller_FreeTime::getInstance()->search($freeTimeFilter);
    }

    /**
     * checks the start_date and end_date
     * 
     * @param Tinebase_Record_Interface $_record
     * @throws HumanResources_Exception_ContractDates
     */
    protected function _checkDates(Tinebase_Record_Interface $_record)
    {
        // if no end_date is given, no validation has to be done
        if (! $_record->end_date || ! ($_record->end_date instanceof Tinebase_DateTime)) {
            return;
        }
        
        if ($_record->end_date->isEarlier($_record->start_date)) {
            throw new HumanResources_Exception_ContractDates();
        }
    }
    
    /**
     * inspect creation of one record (before create)
     *
     * @param   HumanResources_Model_Contract $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $this->_inspectEmploymentEnd($_record);
        $this->_checkDates($_record);

        if (empty($_record->start_date)) {
            throw new Tinebase_Exception_Record_Validation('Contract needs a start date');
        }

        // if a contract before this exists without having an end_date, this is set here
        $filter = new HumanResources_Model_ContractFilter([
            ['field' => 'employee_id', 'operator' => 'equals', 'value' => $_record->employee_id],
            ['field' => 'start_date', 'operator' => 'before', 'value' => $_record->start_date],
            ['field' => 'end_date', 'operator' => 'isnull', 'value' => true],
        ]);

        $contracts = $this->search($filter);
        if ($contracts->count() > 1) {
            // well we are actually preventing this thing from happening nowadays, but did not in the past .... :-/
            throw new Tinebase_Exception_Record_Validation('There are more than 1 contracts before the new one without an end_date. Please terminate them before!');
        }
        $lastRecord = $contracts->getFirstRecord();
        
        // if there is a contract already
        if ($lastRecord) {
            // terminate last contract one day before the new contract starts
            $date = clone $_record->start_date;
            $lastRecord->end_date = $date->subDay(1);
            $this->update($lastRecord, FALSE);
        }

        $this->_checkDateOverlap($_record);

        if (!empty($_record->{HumanResources_Model_Contract::FLD_WORKING_TIME_SCHEME})) {
            $this->_inspectWorkingTimeScheme($_record);
        }
    }

    /**
     * @param HumanResources_Model_Contract $_record
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _checkDateOverlap(HumanResources_Model_Contract $_record)
    {
        $filter = new HumanResources_Model_ContractFilter(array_merge([
            ['field' => 'id', 'operator' => 'not', 'value' => $_record->getId()],
            ['field' => 'employee_id', 'operator' => 'equals', 'value' => $_record->employee_id],
            ['field' => 'end_date', 'operator' => 'after_or_equals', 'value' => $_record->start_date, 'options' => [Tinebase_Model_Filter_Date::AFTER_OR_IS_NULL => true]],
        ], empty($_record->end_date) ? [] : [
            ['field' => 'start_date', 'operator' => 'before_or_equals', 'value' => $_record->end_date],
        ]));

        /** @phpstan-ignore-next-line */
        if ($this->searchCount($filter) > 0) {
            $translation = Tinebase_Translation::getTranslation($this->_applicationName);
            throw new Tinebase_Exception_SystemGeneric($translation->_('Contracts may not overlap'));
        }
    }

    protected function _inspectDelete(array $_ids)
    {
        $_ids = parent::_inspectDelete($_ids);

        /** @var HumanResources_Model_Contract $contract */
        foreach ($this->getMultiple($_ids, true) as $contract) {
            if (!$this->_inspectBookedResources($contract)) {
                throw new HumanResources_Exception_ContractNotEditable('the contract ' . $contract->getTitle() . ' has booked freetimes or working time reports and can not be deleted');
            }
        }

        return $_ids;
    }

    protected function _inspectAfterDelete(Tinebase_Record_Interface $record)
    {
        parent::_inspectAfterDelete($record);

        if (!empty($wtsId = $record->getIdFromProperty(HumanResources_Model_Contract::FLD_WORKING_TIME_SCHEME))) {
            try {
                $wts = HumanResources_Controller_WorkingTimeScheme::getInstance()->get($wtsId);
                if (HumanResources_Model_WorkingTimeScheme::TYPES_INDIVIDUAL === $wts->{HumanResources_Model_WorkingTimeScheme::FLDS_TYPE}) {
                    HumanResources_Controller_WorkingTimeScheme::getInstance()->delete($wts);
                }
            } catch (Tinebase_Exception_NotFound $tenf) {}
        }
    }

    protected function _inspectWorkingTimeScheme(HumanResources_Model_Contract $_record, $_oldWTS = null)
    {
        /** @var HumanResources_Model_WorkingTimeScheme $recordWts */
        $recordWts = $_record->{HumanResources_Model_Contract::FLD_WORKING_TIME_SCHEME};
        $wtsId = null;
        if (is_array($recordWts)) {
            if (isset($recordWts['id'])) {
                $wtsId = $recordWts['id'];
            }
            $recordWts = new HumanResources_Model_WorkingTimeScheme($recordWts);
        } else {
            $wtsId = $recordWts instanceof Tinebase_Record_Interface ? $recordWts->getId() : $recordWts;
        }

        $wtsController = HumanResources_Controller_WorkingTimeScheme::getInstance();

        if (null !== $wtsId) {
            try {
                /** @var HumanResources_Model_WorkingTimeScheme $wts */
                $wts = $wtsController->get($wtsId);
                if (!$recordWts instanceof Tinebase_Record_Interface) {
                    $recordWts = $wts;
                }
                if ($wts->{HumanResources_Model_WorkingTimeScheme::FLDS_TYPE} ===
                    HumanResources_Model_WorkingTimeScheme::TYPES_SHARED) {
                    $_record->{HumanResources_Model_Contract::FLD_WORKING_TIME_SCHEME} = $wtsId;
                    $recordWts = null;
                } elseif ($wts->{HumanResources_Model_WorkingTimeScheme::FLDS_TYPE} ===
                    HumanResources_Model_WorkingTimeScheme::TYPES_TEMPLATE) {
                    $recordWts->setId(null);
                }
            } catch (Tinebase_Exception_NotFound $e) {
                // new inline defined schema
                $recordWts->setId(null);
            }
        }

        if (null !== $recordWts) {
            $recordWts->{HumanResources_Model_WorkingTimeScheme::FLDS_TYPE} =
                HumanResources_Model_WorkingTimeScheme::TYPES_INDIVIDUAL;

            if ($recordWts->getId()) {
                $wtsController->update($recordWts);
            } else {
                $employee = $_record->employee_id;
                if (is_string($employee)) {
                    $employee = HumanResources_Controller_Employee::getInstance()->get($employee);
                }
                $recordWts->{HumanResources_Model_WorkingTimeScheme::FLDS_TITLE} = $employee['number'] . ' ' .
                    $employee['n_fn'] . ' ' . $_record->start_date->getClone()
                        ->setTimezone(Tinebase_Core::getUserTimezone())->format('Y-m-d');

                for ($i = 1; $i <= 10; $i++ )
                {
                    try {
                        $recordWts = $wtsController->create($recordWts);
                        break;
                    } catch (Tinebase_Exception_Duplicate $ted) {
                        $recordWts->{HumanResources_Model_WorkingTimeScheme::FLDS_TITLE} = $recordWts->{HumanResources_Model_WorkingTimeScheme::FLDS_TITLE} . ' ' . $i;
                    }
                }

            }
            $_record->{HumanResources_Model_Contract::FLD_WORKING_TIME_SCHEME} = $recordWts->getId();
        }

        if (null !== $_oldWTS && $_oldWTS !== $_record->{HumanResources_Model_Contract::FLD_WORKING_TIME_SCHEME}) {
            $wts = $wtsController->get($_oldWTS);
            if ($wts->{HumanResources_Model_WorkingTimeScheme::FLDS_TYPE} ===
                    HumanResources_Model_WorkingTimeScheme::TYPES_INDIVIDUAL) {
                $wtsController->delete($_oldWTS);
            }
        }
    }
    
    /**
     * calculates the vacation days count of a contract for a period given by firstDate and lastDate. 
     * if the period exceeds the contracts' period, the contracts' period will be used
     * 
     * @param HumanResources_Model_Contract|Tinebase_Record_RecordSet $contracts
     * @param Tinebase_DateTime $firstDate
     * @param Tinebase_DateTime $lastDate
     * @return float
     */
    public function calculateVacationDays($contracts, Tinebase_DateTime $gFirstDate, Tinebase_DateTime $gLastDate)
    {
        $contracts = $this->_convertToRecordSet($contracts);
        
        $sum = 0;
        
        foreach($contracts as $contract) {
            $firstDate = $this->_getFirstDate($contract, $gFirstDate);
            $lastDate = $this->_getLastDate($contract, $gLastDate);
            
            // find out how many days the year does have
            $januaryFirst = Tinebase_DateTime::createFromFormat('Y-m-d H:i:s e', $firstDate->format('Y') . '-01-01 00:00:00 ' . Tinebase_Core::getUserTimezone());
            $decemberLast = Tinebase_DateTime::createFromFormat('Y-m-d H:i:s e', $firstDate->format('Y') . '-12-31 23:59:59 ' . Tinebase_Core::getUserTimezone());
            
            $daysOfTheYear = ($decemberLast->getTimestamp() - $januaryFirst->getTimestamp()) / 24 / 60 / 60;
            
            // find out how many days the contract does have
            $daysOfTheContract = ($lastDate->getTimestamp() - $firstDate->getTimestamp()) / 24 / 60 / 60;
            
            $correl = $daysOfTheContract / $daysOfTheYear;
            $sum = $sum + (($correl) * $contract->vacation_days);
        }
        
        return $sum;
    }
    
    /**
     * returns feast days as array containing Tinebase_DateTime objects
     * if the period exceeds the contracts' period(s), the contracts' period(s) will be used
     * 
     * @param HumanResources_Model_Contract|Tinebase_Record_RecordSet $contracts
     * @param Tinebase_DateTime $firstDate
     * @param Tinebase_DateTime $lastDate
     * @return array
     */
    public function getFeastDays($contracts, Tinebase_DateTime $firstDate, Tinebase_DateTime $lastDate)
    {
        $contracts = $this->_convertToRecordSet($contracts);

        if (empty($bankHolidayCalendarIds =
                array_filter(array_unique(array_values($contracts->getIdFromProperty('feast_calendar_id')))))) {
            return [];
        }

        $firstDate = $firstDate->getClone();
        $firstDate->hasTime(false);
        $lastDate = $lastDate->getClone();
        $lastDate->hasTime(false);

        $dates = [];
        foreach (array_unique(array_keys(Tinebase_Controller_BankHoliday::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_BankHoliday::class, [
                    [TMFA::FIELD => Tinebase_Model_BankHoliday::FLD_CALENDAR_ID, TMFA::OPERATOR => 'in', TMFA::VALUE => $bankHolidayCalendarIds],
                    [TMFA::FIELD => Tinebase_Model_BankHoliday::FLD_DATE, TMFA::OPERATOR => 'after_or_equals', TMFA::VALUE => $firstDate->toString()],
                    [TMFA::FIELD => Tinebase_Model_BankHoliday::FLD_DATE, TMFA::OPERATOR => 'before_or_equals', TMFA::VALUE => $lastDate->toString()],
                ]), new Tinebase_Model_Pagination(['sort' => Tinebase_Model_BankHoliday::FLD_DATE]), false,
                [Tinebase_Model_BankHoliday::FLD_DATE]))) as $date) {
            $date = new Tinebase_DateTime($date);
            $date->hasTime(false);
            $dates[] = $date;
        }

        return $dates;
    }
    
    /**
     * calculates the first date by date and contract. the contract date is used if the start date is earlier
     * 
     * @param HumanResources_Model_Contract $contract
     * @param Tinebase_DateTime $firstDate
     * @return Tinebase_DateTime
     */
    protected function _getFirstDate(HumanResources_Model_Contract $contract, Tinebase_DateTime $firstDate)
    {
        $date = $contract->start_date ? $firstDate < $contract->start_date ? $contract->start_date : $firstDate : $firstDate;
        return clone $date;
    }
    
    /**
     * calculates the last date by date and contract. the contract date is used if the end date is later
     * 
     * @param HumanResources_Model_Contract $contract
     * @param Tinebase_DateTime $lastDate
     * @return Tinebase_DateTime
     */
    protected function _getLastDate(HumanResources_Model_Contract $contract, Tinebase_DateTime $lastDate)
    {
        $date = $contract->end_date ? $lastDate > $contract->end_date ? $contract->end_date : $lastDate : $lastDate;
        $date->setTime(23, 59, 59);
        return clone $date;
    }
    
    
    /**
     * returns all dates the employee have to work on by contract. the feast days are removed already
     * if the period exceeds the contracts' period, the contracts' period will be used. 
     * freetimes are not respected here, if $respectTakenVacationDays is not set to TRUE
     * 
     * @param HumanResources_Model_Contract|Tinebase_Record_RecordSet $contracts
     * @param Tinebase_DateTime $firstDate
     * @param Tinebase_DateTime $lastDate
     * @param boolean $respectTakenVacationDays
     * 
     * @return array
     */
    public function getDatesToWorkOn($contracts, Tinebase_DateTime $firstDate, Tinebase_DateTime $lastDate, $respectTakenVacationDays = FALSE)
    {
        $contracts = $this->_convertToRecordSet($contracts);
        
        // find out feast days
        $feastDays = $this->getFeastDays($contracts, $firstDate, $lastDate);
        $freeDayStrings = array();
        
        foreach ($feastDays as $feastDay) {
            $freeDayStrings[] = $feastDay->format('Y-m-d');
        }
        
        if ($respectTakenVacationDays) {
            $vacationTimes = new Tinebase_Record_RecordSet('HumanResources_Model_FreeTime');
            
            foreach ($contracts as $contract) {
                $vacationTimes = $vacationTimes->merge($this->getFreeTimes($contract));
            }
            
            $filter = new HumanResources_Model_FreeDayFilter(array());
            $filter->addFilter(new Tinebase_Model_Filter_Text(
                array('field' => 'freetime_id','operator' => 'in', 'value' => $vacationTimes->id)
            ));
            $vacationDays = HumanResources_Controller_FreeDay::getInstance()->search($filter);
            foreach($vacationDays as $vDay) {
                $freeDayStrings[] = $vDay->date->format('Y-m-d');
            }
        }
        
        $results = array();
        $sumHours = 0;

        /** @var HumanResources_Model_Contract $contract */
        foreach ($contracts as $contract) {
            $firstDate = $this->_getFirstDate($contract, $firstDate);
            $lastDate = $this->_getLastDate($contract, $lastDate);
            
            $date = clone $firstDate;
            $json = $contract->getWorkingTimeJson();
            $weekdays = $json['days'];

            if ($weekdays) {
                // datetime format w uses day 0 as sunday
                $monday = array_pop($weekdays);
                array_unshift($weekdays, $monday);
                while ($date->isEarlier($lastDate)) {
                    // if calculated working day is not a feast day, add to days to work on
                    $ds = $date->format('Y-m-d');
                    $weekday = $date->format('w');
                    $hrs = $weekdays[$weekday];
                    if (!in_array($ds, $freeDayStrings) && $hrs > 0) {
                        $results[] = clone $date;
                        $sumHours += $hrs;
                    }
                    $date->addDay(1);
                }
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                    Tinebase_Core::getLogger()->warn(
                        __METHOD__ . '::' . __LINE__ . ' Contract ' . $contract->getId()
                        . ' has no weekdays config');
                }
            }
        }
        
        return array(
            'hours'   => $sumHours,
            'results' => $results
        );
    }
    
    /**
     * Get valid contracts for the period specified
     *
     * @param Tinebase_DateTime[] $period
     * @param mixed $employeeId
     */
    public function getValidContracts($period, $employeeId = NULL)
    {
        if (! ($period['from'] && $period['until'])) {
            throw new Tinebase_Exception_InvalidArgument('All params are needed!');
        }
        
        if (is_array($employeeId)) {
            $employeeId = $employeeId['id'];
        } elseif (is_object($employeeId) && get_class($employeeId) == 'HumanResources_Model_Employee') {
            $employeeId = $employeeId->getId();
        }

        $filter = new HumanResources_Model_ContractFilter(array(), 'AND');
        if ($employeeId) {
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $employeeId)));
        }
        $subFilter2 = new HumanResources_Model_ContractFilter(array(), 'OR');
        
        $subFilter21 = new HumanResources_Model_ContractFilter(array(), 'AND');
        $subFilter21->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'start_date', 'operator' => 'before_or_equals', 'value' => $period['until'])));
        $subFilter21->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'end_date', 'operator' => 'after_or_equals', 'value' =>  $period['from'])));
        $subFilter22 = new HumanResources_Model_ContractFilter(array(), 'AND');
        $subFilter22->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'start_date', 'operator' => 'before_or_equals', 'value' => $period['until'])));
        $subFilter22->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'end_date', 'operator' => 'isnull', 'value' => TRUE)));
        $subFilter2->addFilterGroup($subFilter21);
        $subFilter2->addFilterGroup($subFilter22);
        $filter->addFilterGroup($subFilter2);

        $contracts = $this->search($filter);
        $contracts->sort('start_date', 'ASC');
        return $contracts;
    }
    
    /**
     * returns the active contract for the given employee and date or now, when no date is given
     * 
     * @param string $_employeeId
     * @param Tinebase_DateTime $_firstDayDate
     * @return ?HumanResources_Model_Contract
     */
    public function getValidContract(string $_employeeId, ?Tinebase_DateTime $_firstDayDate = null): ?HumanResources_Model_Contract
    {
        $_firstDayDate = $_firstDayDate ?: Tinebase_DateTime::now();
        
        $filter = new HumanResources_Model_ContractFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'start_date', 'operator' => 'before_or_equals', 'value' => $_firstDayDate)));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_employeeId)));
        $endDate = new Tinebase_Model_Filter_FilterGroup(array(), 'OR');
        $endDate->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'end_date', 'operator' => 'after_or_equals', 'value' =>  $_firstDayDate)));
        $endDate->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'end_date', 'operator' => 'isnull', 'value' =>  true)));
        $filter->addFilterGroup($endDate);

        /** @var ?HumanResources_Model_Contract $result */
        $result = $this->search($filter)->getFirstRecord();
        return $result;
    }
    
    /**
     * returns the contracts for an employee sorted by the start_date
     * 
     * @param string $employeeId
     * @return Tinebase_Record_RecordSet
     */
    public function getContractsByEmployeeId($employeeId)
    {
        $filter = new HumanResources_Model_ContractFilter(array(
            array('field' => 'employee_id', 'operator' => 'equals', 'value' => $employeeId)
        ), 'AND');
        $pagination = new Tinebase_Model_Pagination(array('sort' => 'start_date'));
        $recs = $this->search($filter, $pagination);
        
        return $recs;
    }
}

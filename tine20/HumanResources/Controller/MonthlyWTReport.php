<?php
/**
 * MonthlyWorkingTimeReport controller for HumanResources application
 * 
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * MonthlyWorkingTimeReport controller class for HumanResources application
 * 
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_MonthlyWTReport extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;
    use HumanResources_Controller_CheckFilterACLEmployeeTrait;

    const RC_JSON_REQUEST = 'jsonRequest';

    protected $_getMultipleGrant = [HumanResources_Model_DivisionGrants::READ_TIME_DATA];
    protected $_requiredFilterACLget = [HumanResources_Model_DivisionGrants::READ_TIME_DATA];
    protected $_requiredFilterACLupdate  = [HumanResources_Model_DivisionGrants::UPDATE_TIME_DATA];
    protected $_requiredFilterACLsync  = [HumanResources_Model_DivisionGrants::READ_TIME_DATA];
    protected $_requiredFilterACLexport  = [HumanResources_Model_DivisionGrants::READ_TIME_DATA];

    protected $allowCorrectionUpdate = false;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    protected function __construct()
    {
        $this->_applicationName = HumanResources_Config::APP_NAME;
        $this->_modelName = HumanResources_Model_MonthlyWTReport::class;
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName,
            'tableName' => HumanResources_Model_MonthlyWTReport::TABLE_NAME,
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
                $_action = HumanResources_Model_DivisionGrants::UPDATE_TIME_DATA;
                break;
        }
        return parent::_checkGrant($_record, $_action, $_throw, $_errorMessage, $_oldRecord);
    }

    /**
     * @param string|HumanResources_Model_Employee $employeeId
     * @param string|DateTime $month default current year
     * @return HumanResources_Model_MonthlyWTReport|NULL
     * @throws Tinebase_Exception_InvalidArgument
     *
     * maybe @deprecated
     */
    public function getByEmployeeMonth($employeeId, $month = null)
    {
        $employeeId = !($employeeId instanceof HumanResources_Model_Employee) ?: $employeeId->getId();
        $month = $month ?: Tinebase_DateTime::now();
        $month = !($month instanceof DateTime) ?: $month->format('Y-m');

        // find account
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_MonthlyWTReport::class, [
            ['field' => HumanResources_Model_MonthlyWTReport::FLDS_MONTH,         'operator' => 'equals', 'value' => $month],
            ['field' => HumanResources_Model_MonthlyWTReport::FLDS_EMPLOYEE_ID ,  'operator' => 'equals', 'value' => $employeeId]
        ]);

        return $this->search($filter)->getFirstRecord();
    }
    
    /**
     * will recalculate the given monthly report and all reports that exist after the given one
     *
     * @param HumanResources_Model_MonthlyWTReport $_monthlyWTR
     */
    public function recalculateReport(HumanResources_Model_MonthlyWTReport $_monthlyWTR,
            HumanResources_Model_MonthlyWTReport $_previousMonthlyWTR = null)
    {
        $transaction = new Tinebase_TransactionManager_Handle();

        if (null === $_previousMonthlyWTR) {
            $_previousMonthlyWTR = $this->getPreviousMonthlyWTR($_monthlyWTR);
        }

        $rs = new Tinebase_Record_RecordSet(HumanResources_Model_MonthlyWTReport::class, [$_monthlyWTR]);
        Tinebase_ModelConfiguration::resolveRecordsPropertiesForRecordSet($rs,
            HumanResources_Model_MonthlyWTReport::getConfiguration());
        $_monthlyWTR->dailywtreports->sort('date');
        $currentRecord = clone $_monthlyWTR;

        $firstDay = $_monthlyWTR->dailywtreports->getFirstRecord();
        if (null !== $_previousMonthlyWTR) {
            $_monthlyWTR->working_time_balance_previous = $_previousMonthlyWTR->working_time_balance;
            if ($firstDay && (int)$firstDay->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_BALANCE_PREVIOUS} !==
                    (int)$_previousMonthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_BALANCE}) {
                $firstDay->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_BALANCE_PREVIOUS} =
                    (int)$_previousMonthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_BALANCE};
            }
        } else {
            $_monthlyWTR->working_time_balance_previous = 0;
            if ($firstDay && 0 !== (int)$firstDay->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_BALANCE_PREVIOUS}) {
                $firstDay->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_BALANCE_PREVIOUS} = 0;
            }
        }

        $isTime = 0;
        $shouldTime = 0;
        $prevDay = null;
        /** @var HumanResources_Model_DailyWTReport $dailyWTR */
        foreach ($_monthlyWTR->dailywtreports as $dailyWTR) {
            $is = $dailyWTR->getIsWorkingTime();
            $should = $dailyWTR->getShouldWorkingTime();
            if ($prevDay && (int)$dailyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_BALANCE_PREVIOUS}
                    !== (int)$prevDay->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_BALANCE}) {
                $dailyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_BALANCE_PREVIOUS} =
                    (int)$prevDay->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_BALANCE};
            }
            $balance = $is - $should +
                $dailyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_BALANCE_PREVIOUS};
            if ($balance !== (int)$dailyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_BALANCE}) {
                $dailyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_BALANCE} = $balance;
            }
            if ($dailyWTR->isDirty()) {
                HumanResources_Controller_DailyWTReport::getInstance()->update(clone $dailyWTR);
            }
            $isTime += $is;
            $shouldTime += $should;
            $prevDay = $dailyWTR;
        }

        $_monthlyWTR->working_time_actual = $isTime;
        $_monthlyWTR->working_time_target = $shouldTime;
        $_monthlyWTR->working_time_balance = $_monthlyWTR->working_time_balance_previous +
            $_monthlyWTR->working_time_actual - $_monthlyWTR->working_time_target +
            $_monthlyWTR->working_time_correction;

        if ($_monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_DAILY_WT_REPORTS}->getLastRecord() &&
                (int)$_monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_BALANCE} !==
                (int)$_monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_DAILY_WT_REPORTS}->getLastRecord()
                ->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_BALANCE}
                + (int)$_monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_CORRECTION}) {
            // well this is bad...
            Tinebase_Exception::log(new Tinebase_Exception_UnexpectedValue(
                'monthly balance mismatches last days balance for month ' .
                $_monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_MONTH} . ' for employee '
                . $_monthlyWTR->getIdFromProperty(HumanResources_Model_MonthlyWTReport::FLDS_EMPLOYEE_ID)));

            $_monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_SYSTEM_REMARK} = 'balance of last day mismatches monthly balance';
        } elseif (!empty($_monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_SYSTEM_REMARK})) {
            $_monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_SYSTEM_REMARK} =
                str_replace('balance of last day mismatches monthly balance', '',
                    $_monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_SYSTEM_REMARK});
        }

        if (!$currentRecord->diff($_monthlyWTR)->isEmpty()) {
            $_monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_LAST_CALCULATION} = Tinebase_DateTime::now();
            Tinebase_Timemachine_ModificationLog::getInstance()
                ->setRecordMetaData($_monthlyWTR, self::ACTION_UPDATE, $currentRecord);
            $currentMods = $this->_writeModLog($_monthlyWTR, $currentRecord);
            $this->_setSystemNotes($_monthlyWTR, Tinebase_Model_Note::SYSTEM_NOTE_NAME_CHANGED, $currentMods);

            $_monthlyWTR = $this->_backend->update($_monthlyWTR);
        }

        if (null !== ($_nextMonthlyWTR = $this->getNextMonthlyWTR($_monthlyWTR))) {
            $this->recalculateReport($_nextMonthlyWTR, $_monthlyWTR);
        }

        $transaction->commit();
    }

    /**
     * @param HumanResources_Model_MonthlyWTReport $_monthlyWTR
     * @return null|HumanResources_Model_MonthlyWTReport
     */
    public function getNextMonthlyWTR(HumanResources_Model_MonthlyWTReport $_monthlyWTR)
    {
        $date = new Tinebase_DateTime($_monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_MONTH}, 'UTC');
        $date->addMonth(1);

        return $this->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_MonthlyWTReport::class, [
                ['field' => HumanResources_Model_MonthlyWTReport::FLDS_EMPLOYEE_ID, 'operator' => 'equals',
                    'value' => $_monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_EMPLOYEE_ID}],
                ['field' => HumanResources_Model_MonthlyWTReport::FLDS_MONTH, 'operator' => 'equals',
                    'value' => $date->format('Y-m')],
            ]))->getFirstRecord();
    }

    /**
     * @param HumanResources_Model_MonthlyWTReport $_monthlyWTR
     * @return null|HumanResources_Model_MonthlyWTReport
     */
    public function getPreviousMonthlyWTR(HumanResources_Model_MonthlyWTReport $_monthlyWTR)
    {
        $date = new Tinebase_DateTime($_monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_MONTH}, 'UTC');
        $date->subMonth(1);

        return $this->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_MonthlyWTReport::class, [
                ['field' => HumanResources_Model_MonthlyWTReport::FLDS_EMPLOYEE_ID, 'operator' => 'equals',
                    'value' => $_monthlyWTR->{HumanResources_Model_MonthlyWTReport::FLDS_EMPLOYEE_ID}],
                ['field' => HumanResources_Model_MonthlyWTReport::FLDS_MONTH, 'operator' => 'equals',
                    'value' => $date->format('Y-m')],
            ]))->getFirstRecord();
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
            // _("monthly wt reports can't be created")
            throw new Tinebase_Exception_SystemGeneric("monthly wt reports can't be created", 600, HumanResources_Config::APP_NAME);
        }
    }

    /**
     * inspect update of one record (before update)
     *
     * @param   HumanResources_Model_MonthlyWTReport $_record      the update record
     * @param   HumanResources_Model_MonthlyWTReport $_oldRecord   the current persistent record
     * @return  void
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        if (isset($this->_requestContext[self::RC_JSON_REQUEST])) {
            $allowedProperties = [
                HumanResources_Model_MonthlyWTReport::FLDS_IS_CLEARED => true,
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

        if ($_record->is_cleared && $_oldRecord->is_cleared) {
            // _('It is not allowed to update a cleared report')
            throw new Tinebase_Exception_SystemGeneric('It is not allowed to update a cleared report', 600, HumanResources_Config::APP_NAME);
        }
    }

    /**
     * inspect update of one record (after update)
     *
     * @param   HumanResources_Model_MonthlyWTReport $updatedRecord   the just updated record
     * @param   HumanResources_Model_MonthlyWTReport $record          the update record
     * @param   HumanResources_Model_MonthlyWTReport $currentRecord   the current record (before update)
     * @return  void
     */
    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        foreach ([
                    HumanResources_Model_MonthlyWTReport::FLDS_WORKING_TIME_CORRECTION,
                 ] as $prop) {
            if ($currentRecord->{$prop} !== $updatedRecord->{$prop}) {
                $this->recalculateReport($updatedRecord);
                break;
            }
        }

        // set is_cleared
        if ($updatedRecord->is_cleared && !$currentRecord->is_cleared) {
            // previous month needs to be cleared first!
            if (null !== ($prev = $this->getPreviousMonthlyWTR($updatedRecord)) && !$prev->is_cleared) {
                // _('previous months need to be cleared first')
                throw new Tinebase_Exception_SystemGeneric('previous months need to be cleared first', 600, HumanResources_Config::APP_NAME);
            }

            $rs = new Tinebase_Record_RecordSet(HumanResources_Model_MonthlyWTReport::class, [$updatedRecord]);
            Tinebase_ModelConfiguration::resolveRecordsPropertiesForRecordSet($rs,
                HumanResources_Model_MonthlyWTReport::getConfiguration());

            $dailyCtrl = HumanResources_Controller_DailyWTReport::getInstance();
            $dailyCtrl->setRequestContext([HumanResources_Controller_DailyWTReport::RC_ALLOW_IS_CLEARED => true]);
            try {
                foreach ($updatedRecord->dailywtreports as $dailyReport) {
                    $dailyReport->is_cleared = true;
                    $dailyCtrl->update($dailyReport);
                }
            } finally {
                $dailyCtrl->setRequestContext([]);
            }

        // unset is_cleared
        } elseif (!$updatedRecord->is_cleared && $currentRecord->is_cleared) {
            // next month must not be cleared!
            if (null !== ($next = $this->getNextMonthlyWTR($updatedRecord)) && $next->is_cleared) {
                // _('following months need to be uncleared first')
                throw new Tinebase_Exception_SystemGeneric('following months need to be uncleared first', 600, HumanResources_Config::APP_NAME);
            }

            $rs = new Tinebase_Record_RecordSet(HumanResources_Model_MonthlyWTReport::class, [$updatedRecord]);
            Tinebase_ModelConfiguration::resolveRecordsPropertiesForRecordSet($rs,
                HumanResources_Model_MonthlyWTReport::getConfiguration());

            $dailyCtrl = HumanResources_Controller_DailyWTReport::getInstance();
            $dailyCtrl->setRequestContext([HumanResources_Controller_DailyWTReport::RC_ALLOW_IS_CLEARED => true]);
            try {
                foreach ($updatedRecord->dailywtreports as $dailyReport) {
                    $dailyReport->is_cleared = false;
                    $dailyCtrl->update($dailyReport);
                }
            } finally {
                $dailyCtrl->setRequestContext([]);
            }
        }
    }

    public function recalcCorrection(string $id)
    {
        $correction = 0;
        foreach (HumanResources_Controller_WTRCorrection::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_WTRCorrection::class,[
                ['field' => HumanResources_Model_WTRCorrection::FLD_WTR_MONTHLY, 'operator' => 'equals', 'value' => $id],
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
}

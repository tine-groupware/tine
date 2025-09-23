<?php
/**
 * Timeaccount controller for Timetracker application
 * 
 * @package     Timetracker
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Timeaccount controller class for Timetracker application
 * 
 * @package     Timetracker
 * @subpackage  Controller
 */
class Timetracker_Controller_Timeaccount extends Tinebase_Controller_Record_Container
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_applicationName = 'Timetracker';
        $this->_backend = new Timetracker_Backend_Timeaccount();
        $this->_modelName = 'Timetracker_Model_Timeaccount';
        $this->_grantsModel = 'Timetracker_Model_TimeaccountGrants';
        $this->_purgeRecords = FALSE;
        $this->_resolveCustomFields = TRUE;
        $this->_manageRight = Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS;
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
     * @var Timetracker_Controller_Timeaccount
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Timetracker_Controller_Timeaccount
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }        

    /**
     * delete linked objects / timesheets
     *
     * @param Tinebase_Record_Interface $_record
     */
    protected function _deleteLinkedObjects(Tinebase_Record_Interface $_record)
    {
        // delete linked timesheets
        $timesheets = Timetracker_Controller_Timesheet::getInstance()->getTimesheetsByTimeaccountId($_record->getId());
        Timetracker_Controller_Timesheet::getInstance()->delete($timesheets->getArrayOfIds());
        
        // delete other linked objects
        parent::_deleteLinkedObjects($_record);
    }

    /**
     * inspect update of one record
     * @param $_record
     * @param $_oldRecord
     * @return  void
     * @throws Tinebase_Exception_Confirmation
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        if ($_record['is_billable'] && $_record['is_billable'] != $_oldRecord['is_billable']) {
            $tsBackend = new Timetracker_Backend_Timesheet();
            $timesheets = $tsBackend->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Timetracker_Model_Timesheet::class, [
                ['field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $_record->getId()],
                ['field' => 'is_billable', 'operator' => 'equals', 'value' => false],
            ]));

            if ($timesheets->count() > 0) {
                $context = $this->getRequestContext();
                $confirmHeader = $context['confirm'] ?? $context['clientData']['confirm'] ?? null;

                if (!$confirmHeader) {
                    $translation = Tinebase_Translation::getTranslation($this->_applicationName);
                    $totalCount = 0;
                    $timesheetTitles = '<div style="max-height: 300px; overflow-y: auto; padding: 5px;">'; // Start with a container that has scrolling
                    $expander = new Tinebase_Record_Expander(Timetracker_Model_Timesheet::class, [
                        Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                            'account_id' => []
                        ]
                    ]);
                    $expander->expand(new Tinebase_Record_RecordSet(Timetracker_Model_Timesheet::class, $timesheets));

                    foreach ($timesheets as $timesheet) {
                        $title = $timesheet->getTitle();
                        $duration  = $timesheet->duration / 60;
                        $startDate = $timesheet->start_date->format('Y-m-d');
                        $accountName = $timesheet->account_id->accountDisplayName;
                        $timesheetTitles .= '<div style="display: block; margin: 5px;">
                                <div style="display: flex; flex-direction: row; justify-content: space-between;">
                                    <span style="text-align: left; min-width: 80px">' . $startDate . '</span>
                                    <span style="text-align: left; text-overflow: ellipsis; white-space: nowrap; overflow: hidden; width: 200px">' . $title . '</span>
                                    <span style="text-align: right;">' . $duration . '</span>
                                </div>
                                <div style="display: flex;flex-direction: row;justify-content: space-between;">
                                    <span style="text-align: left; max-width: 300px; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">' . $accountName . '</span>
                                </div>
                              </div>';
                        $totalCount += $duration;
                    }
                    $exception = new Tinebase_Exception_Confirmation(
                        sprintf($translation->_('There are %s hours that have not yet been billed. Do you want to make them billable?'), $totalCount)
                    );
                    $exception->setInfo($timesheetTitles);
                    //update timesheet should not interrupt the update process of timeaccount
                    $exception->setSendRequestOnRejection(true);
                    throw $exception;
                }

                if (filter_var($confirmHeader, FILTER_VALIDATE_BOOLEAN) === true) {
                    $tsBackend->updateMultiple($timesheets->getArrayOfIds(), [
                        'is_billable' => true,
                    ]);
                }
            }
        }

        if (!empty($_record->budget) && $_record->budget !== $_oldRecord->budget) {
            $_record->budget_booked_hours = Timetracker_Controller_Timeaccount::getInstance()->getBudgetBookedHoursByTimeaccountId($_record->getId());
            $_record->budget_filled_level = round(($_record->budget_booked_hours / $_record->budget), 2) * 100;
        }
    }

    /**
     * inspect update of one record
     * @param   Tinebase_Record_Interface $updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $record          the update record
     * @param   Tinebase_Record_Interface $currentRecord   the current record (before update)
     * @return  void
     */
    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        parent::_inspectAfterUpdate($updatedRecord, $record, $currentRecord);

        $this->_resolveTimesheets($updatedRecord, $currentRecord);
    }

    /**
     * inspects delete action
     *
     * @param array $_ids
     * @return array records to actually delete
     */
    protected function _inspectDelete(array $_ids): array
    {
        $inUseIds = [];

        foreach ($_ids as $id) {
            $timeSheets = Timetracker_Controller_Timesheet::getInstance()->getTimesheetsByTimeaccountId($id);
            if ($timeSheets->count() > 0) {
                array_push($inUseIds, $id);
            }
        }

        $timeAccounts = Timetracker_Controller_Timeaccount::getInstance()->getMultiple($inUseIds);

        if ($timeAccounts->count() > 0) {
            $context = $this->getRequestContext();
            $confirmHeader = $context['confirm'] ?? $context['clientData']['confirm'] ?? null;

            if (!$confirmHeader) {
                $translation = Tinebase_Translation::getTranslation($this->_applicationName);
                $exception = new Tinebase_Exception_Confirmation(
                    $translation->_('Time accounts are still in use! Are you sure you want to delete them?'));

                // todo: show more info about in used time accounts ?
//                $timeAccountTitles = null;
//                foreach ($timeAccounts as $timeaccount) {
//                    $timeAccountTitles .= '<br />' . $timeaccount->number . ', ' . $timeaccount->title;
//                }
//                $exception->setInfo($timeAccountTitles);

                throw $exception;
            }
        }

        return parent::_inspectDelete($_ids);
    }

    /**
     * check timeaccount rights
     * 
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight($_action)
    {
        if (! $this->_doRightChecks) {
            return;
        }

        // if get, right = true
        // else if manage_timeaccounts, right = true
        // else if create and add_timeaccounts, right = true
        if (!($hasRight = (self::ACTION_GET === $_action)) &&
                !($hasRight = $this->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE))) {
            if (self::ACTION_CREATE === $_action) {
                $hasRight = $this->checkRight(Timetracker_Acl_Rights::ADD_TIMEACCOUNTS, FALSE);
            }
        }
        
        if (! $hasRight) {
            throw new Tinebase_Exception_AccessDenied('You are not allowed to ' . $_action . ' timeaccounts.');
        }

        parent::_checkRight($_action);
    }
    
    /**
     * check grant for action (CRUD)
     *
     * @param Timetracker_Model_Timeaccount $_record
     * @param string $_action
     * @param boolean $_throw
     * @param string $_errorMessage
     * @param Timetracker_Model_Timeaccount $_oldRecord
     * @return boolean
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        if ($_action == 'create' || $this->_doGrantChecks == FALSE) {
            // no check here because the MANAGE_TIMEACCOUNTS right has been already checked before
            return TRUE;
        }
        
        $hasGrant = Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->getId(), Tinebase_Model_Grants::GRANT_ADMIN);
        
        switch ($_action) {
            case 'get':
                $hasGrant = (
                    $hasGrant
                    || Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->getId(), array(
                        Timetracker_Model_TimeaccountGrants::BOOK_ALL,
                        Timetracker_Model_TimeaccountGrants::BOOK_OWN,
                        Timetracker_Model_TimeaccountGrants::MANAGE_BILLABLE,
                        Timetracker_Model_TimeaccountGrants::READ_OWN,
                        Timetracker_Model_TimeaccountGrants::REQUEST_OWN,
                        Timetracker_Model_TimeaccountGrants::VIEW_ALL,
                    ))
                );
            case 'delete':
            case 'update':
                $hasGrant = (
                    $hasGrant
                    || $this->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE)
                );
                break;
        }
        
        if ($_throw && !$hasGrant) {
            throw new Tinebase_Exception_AccessDenied($_errorMessage);
        }
        
        return $hasGrant;
    }

    /**
     * Removes containers where current user has no access to
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action
     * @throws Timetracker_Exception_UnexpectedValue
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        if ($this->_doContainerACLChecks == FALSE) {
            $_filter->doIgnoreAcl(true);
            return TRUE;
        }
        switch ($_action) {
            case 'get':
                $_filter->setRequiredGrants(array(
                    Timetracker_Model_TimeaccountGrants::BOOK_ALL,
                    Timetracker_Model_TimeaccountGrants::BOOK_OWN,
                    Timetracker_Model_TimeaccountGrants::MANAGE_BILLABLE,
                    Timetracker_Model_TimeaccountGrants::READ_OWN,
                    Timetracker_Model_TimeaccountGrants::REQUEST_OWN,
                    Timetracker_Model_TimeaccountGrants::VIEW_ALL,
                    Tinebase_Model_Grants::GRANT_ADMIN,
                ));
                break;
            case 'update':
                $_filter->setRequiredGrants(array(
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
     *
     * @param Tinebase_Model_EvaluationDimensionItem|string $costCenterId
     * @return Tinebase_Record_RecordSet
     *
    public function getTimeaccountsByCostCenter($costCenterId)
    {
        $costCenterId = is_string($costCenterId) ? $costCenterId : $costCenterId->getId();
        
        $filter = new Tinebase_Model_RelationFilter(array(
            array('field' => 'related_model', 'operator' => 'equals',
                'value' => Tinebase_Model_EvaluationDimensionItem::class),
            array('field' => 'related_id', 'operator' => 'equals', 'value' => $costCenterId),
            array('field' => 'own_model', 'operator' => 'equals', 'value' => Timetracker_Model_Timeaccount::class),
            array('field' => 'type', 'operator' => 'equals', 'value' => 'COST_CENTER'),
        ), 'AND');
        
        return Timetracker_Controller_Timeaccount::getInstance()->getMultiple(Tinebase_Relations::getInstance()->search($filter)->own_id);
    }*/

    /**
     * @param Sales_Model_Contract $contractId
     * @return Tinebase_Record_RecordSet
     */
    public function getTimeaccountsBySalesContract($contractId)
    {
        $contractId = is_string($contractId) ? $contractId : $contractId->getId();
        
        $filter = new Tinebase_Model_RelationFilter(array(
            array('field' => 'related_model', 'operator' => 'equals', 'value' => 'Sales_Model_Contract'),
            array('field' => 'related_id', 'operator' => 'equals', 'value' => $contractId),
            array('field' => 'own_model', 'operator' => 'equals', 'value' => 'Timetracker_Model_Timeaccount'),
            array('field' => 'type', 'operator' => 'equals', 'value' => 'TIME_ACCOUNT'),
        ), 'AND');
        
        return Sales_Controller_Contract::getInstance()->getMultiple(Tinebase_Relations::getInstance()->search($filter)->own_id);
    }

    /**
     * @param Tinebase_Model_Container $_container
     * @param bool $_ignoreAcl
     * @param null $_filter
     */
    public function deleteContainerContents(Tinebase_Model_Container $_container, $_ignoreAcl = FALSE, $_filter = null)
    {
        // don't do anything here - timeaccount "contents" aka timesheets are deleted in _deleteLinkedObjects()
    }

    /**
     * resolve timesheets when timeaccount is cleared
     *
     * @param Tinebase_Record_Interface $updatedRecord the just updated record
     * @param $oldRecord
     * @throws Setup_Exception
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_Validation
     */
    private function _resolveTimesheets($updatedRecord, $oldRecord)
    {
        $tsBackend = new Timetracker_Backend_Timesheet();

        if ($oldRecord['status'] !== $updatedRecord['status'] && $updatedRecord['status'] === Timetracker_Model_Timeaccount::STATUS_BILLED) {
            $invoiceIdPresent = Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_INVOICES_MODULE);
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Timetracker_Model_Timesheet::class, [
                ['field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $updatedRecord->getId()],
                ['field' => 'is_billable', 'operator' => 'equals', 'value' => true],
            ]);
            $innerFilter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Timetracker_Model_Timesheet::class, [
                ['field'    => 'is_cleared', 'operator' => 'equals', 'value'    => false],
            ], Tinebase_Model_Filter_FilterGroup::CONDITION_OR);
            if ($invoiceIdPresent) {
                $innerFilter->addFilter($innerFilter->createFilter(
                    ['field' => 'invoice_id', 'operator' => 'equals', 'value' => null]
                ));
            }
            $filter->addFilterGroup($innerFilter);
            $timesheets = $tsBackend->search($filter);

            if ($timesheets->count() > 0) {
                $tsBackend->updateMultiple($timesheets->getArrayOfIds(), array_merge($invoiceIdPresent ? [
                    'invoice_id' => $updatedRecord['invoice_id'],
                ] : [], [
                    'is_cleared' => true,
                ]));
            }
        }
    }

    /**
     * implement logic for each controller in this function
     *
     * @param Tinebase_Event_Abstract $_eventObject
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        switch (get_class($_eventObject)) {
            case Tinebase_Event_Record_Delete::class:
            case Tinebase_Event_Record_Update::class:
                if ($_eventObject->observable instanceof Timetracker_Model_Timesheet) {
                    $newDuration = $_eventObject->observable->duration ?? 0;
                    $oldDuration = $_eventObject->oldRecord->duration ?? 0;
                    if ($newDuration !== $oldDuration) {
                        $timeaccountId = $_eventObject->observable->timeaccount_id['id'] ??  $_eventObject->observable->timeaccount_id;
                        $timeaccount = $this->get($timeaccountId);
                        if (empty($timeaccount->budget)) {
                            return;
                        }
                        if (empty($timeaccount->budget_booked_hours)) {
                            $timeaccount->budget_booked_hours = Timetracker_Controller_Timeaccount::getInstance()->getBudgetBookedHoursByTimeaccountId($timeaccount->getId());
                        } else {
                            if ($_eventObject->observable->is_deleted) {
                                $newDuration = -$newDuration;
                            }
                            $timeaccount->budget_booked_hours = $timeaccount->budget_booked_hours + (($newDuration - $oldDuration) / 60);
                        }
                        $timeaccount->budget_filled_level = round(($timeaccount->budget_booked_hours / $timeaccount->budget), 2) * 100;
                        $updatedTimeaccount = Timetracker_Controller_Timeaccount::getInstance()->update($timeaccount);
                    }
                }
                break;
        }
    }

    public static function calculateBudgetInHours()
    {
        try {
            $taController = Timetracker_Controller_Timeaccount::getInstance();
            $filterData = [
                'field' => 'budget',
                'operator' => 'greater',
                'value' => 0
            ];
            $timeaccounts = $taController->search(new Timetracker_Model_TimeaccountFilter([$filterData]));
            foreach ($timeaccounts as $timeaccount) {
                $timeaccount->budget_booked_hours = $taController->getBudgetBookedHoursByTimeaccountId($timeaccount->getId());
                $updatedTimeaccount = Timetracker_Controller_Timeaccount::getInstance()->update($timeaccount);
            }
        } catch (Exception $e) {
            Tinebase_Exception::class::log($e);
        } finally {
            return true;
        }
    }

    public static function getBudgetBookedHoursByTimeaccountId($timeaccountId)
    {
        $budgetBookedHours = 0;
        try {
            $filterData = [
                ['field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $timeaccountId],
            ];
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Timetracker_Model_Timesheet::class, $filterData);
            $timesheets = Timetracker_Controller_Timesheet::getInstance()->search($filter);

            foreach ($timesheets as $timesheet) {
                $budgetBookedHours += $timesheet->duration;
            }
        } catch (Exception $e) {
            Tinebase_Exception::class::log($e);
        } finally {
            return $budgetBookedHours / 60;
        }
    }
}

<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * class for HumanResources uninitialization
 *
 * @package     HumanResources
 */
class HumanResources_Setup_Uninitialize extends Setup_Uninitialize
{
    /**
     * uninit COR system customfields
     */
    protected function _uninitializeCORSystemCustomField()
    {
        try {
            $appId = Tinebase_Application::getInstance()->getApplicationByName(Timetracker_Config::APP_NAME)->getId();
            $customfield = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($appId,
                HumanResources_Model_FreeTimeType::TT_TS_SYSCF_CLOCK_OUT_REASON, Timetracker_Model_Timesheet::class, true);
            if ($customfield) {
                Tinebase_CustomField::getInstance()->deleteCustomField($customfield);
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
        } catch (Throwable $t) {
            // problem!
            Tinebase_Exception::log($t);
        }
    }

    protected function _uninitializeHolidayImport()
    {
        try {
            Admin_Controller_SchedulerTask::getInstance()->deleteByFilter(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(Admin_Model_SchedulerTask::class, [
                    [TMFA::FIELD => Admin_Model_SchedulerTask::FLD_NAME, TMFA::OPERATOR => 'startswith', TMFA::VALUE => 'HR Bank Holiday Import'],
                ]));
        } catch (Tinebase_Exception_NotFound $tenf) {
            // APP Admin already deleted
        } catch (Throwable $t) {
            // problem!
            Tinebase_Exception::log($t);
        }
    }

    protected function _uninitializePersistentObserver()
    {
        Tinebase_Record_PersistentObserver::getInstance()->removeObserverByIdentifier('wtreport');
        Tinebase_Record_PersistentObserver::getInstance()->removeObserverByIdentifier('wtreportFT');
    }

    protected function _uninitializeCostCenterCostBearer()
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }

        Tinebase_Controller_EvaluationDimension::removeModelsFromDimension(Tinebase_Model_EvaluationDimension::COST_CENTER, [
            HumanResources_Model_CostCenter::class,
        ]);
    }
}

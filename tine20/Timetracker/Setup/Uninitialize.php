<?php
/**
 * tine Groupware
 *
 * @package     Timetracker
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2025-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * class for Timetracker uninitialization
 *
 * @package     Timetracker
 */
class Timetracker_Setup_Uninitialize extends Setup_Uninitialize
{
    protected function _uninitializePersistentObserver()
    {
        Tinebase_Record_PersistentObserver::getInstance()->removeObserverByIdentifier('calculateBudgetUpdate');
        Tinebase_Record_PersistentObserver::getInstance()->removeObserverByIdentifier('calculateBudgetDelete');
    }

    /**
     * uninitialize custom fields
     *
     * @param Tinebase_Model_Application $_application
     * @param null $_options
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    protected function _uninitializeCustomFields(Tinebase_Model_Application $_application, $_options = null)
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }

        if (Tinebase_Application::getInstance()->isInstalled(Tasks_Config::APP_NAME)) {
            $cfc = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication(
                Tinebase_Application::getInstance()->getApplicationByName(Tasks_Config::APP_NAME)->getId(),
                Timetracker_Controller_Timeaccount::TASK_TIMEACCOUNT_CUSTOM_FIELD_NAME,
                Tasks_Model_Task::class, true);
            if (null !== $cfc) {
                Tinebase_CustomField::getInstance()->deleteCustomField($cfc);
            }
        }
    }
}

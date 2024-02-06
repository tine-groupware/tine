<?php declare(strict_types=1);
/**
 * Tine 2.0
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Projects uninitialization
 * 
 * @package     Setup
 */
class Crm_Setup_Uninitialize extends Setup_Uninitialize
{
    protected function _uninitializeTasksLooseCoupling(): void
    {
        if (!class_exists('Tasks_Config')) {
            return;
        }

        try {
            $app = Tinebase_Application::getInstance()->getApplicationByName(Tasks_Config::APP_NAME);
        } catch (Tinebase_Exception_NotFound $e) {
            return;
        }

        static::applicationUninstalled($app);
        Tasks_Model_Task::resetConfiguration();

        Tasks_Controller_Task::getInstance()->doContainerACLChecks(false);
        Tasks_Controller_Task::getInstance()->deleteByFilter(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Tasks_Model_Task::class, [
                ['field' => 'source_model', 'operator' => 'equals', 'value' => Crm_Model_Lead::class],
            ]
        ));
    }

    public static function applicationUninstalled(Tinebase_Model_Application $app): void
    {
        if (class_exists('Tasks_Config') && Tasks_Config::APP_NAME === $app->name) {
            $cfc = Tinebase_CustomField::getInstance()
                ->getCustomFieldByNameAndApplication($app, 'CrmTasksCoupling', Tasks_Model_Task::class, true);
            if (null !== $cfc) {
                Tinebase_CustomField::getInstance()->deleteCustomField($cfc);
            }
        }
    }
}

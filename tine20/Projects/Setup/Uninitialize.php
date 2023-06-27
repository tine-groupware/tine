<?php declare(strict_types=1);
/**
 * Tine 2.0
 * 
 * @package     Projects
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
class Projects_Setup_Uninitialize extends Setup_Uninitialize
{
    public static function applicationUninstalled(Tinebase_Model_Application $app): void
    {
        if (Tasks_Config::APP_NAME === $app->name) {
            $cfc = Tinebase_CustomField::getInstance()
                ->getCustomFieldByNameAndApplication($app, 'ProjectsTasksCoupling', Tasks_Model_Task::class, true);
            if (null !== $cfc) {
                Tinebase_CustomField::getInstance()->deleteCustomField($cfc);
            }
        }
    }
}

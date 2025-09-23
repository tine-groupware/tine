<?php
/**
 * Tine 2.0
 *
 * @package     CrewScheduling
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Class to handle application uninitialization
 *
 * @package     CrewScheduling
 * @subpackage  Setup
 */
class CrewScheduling_Setup_Uninitialize extends Setup_Uninitialize
{

    /**
     * uninitialize customfields
     *
     * @param Tinebase_Model_Application $_application
     * @param array | null $_options
     * @return void
     */
    protected function _uninitializeCustomFields(Tinebase_Model_Application $_application, $_options = null)
    {
        $uninstallCfs = CrewScheduling_Setup_Initialize::getInitialCustomFields();
        $uninstallCfs = array_merge($uninstallCfs, [
            ['app' => 'Calendar', 'model' => Calendar_Model_Attender::class, 'cfields' => [
                ['name' => CrewScheduling_Config::CREWSHEDULING_ROLES, 'is_system' => true],
            ]],
            ['app' => 'Calendar', 'model' => Calendar_Model_Event::class, 'cfields' => [
                ['name' => CrewScheduling_Config::EVENT_ROLES_CONFIGS, 'is_system' => true],
            ]],
            ['app' => 'Calendar', 'model' => Calendar_Model_EventType::class, 'cfields' => [
                ['name' => CrewScheduling_Config::CS_ROLE_CONFIGS, 'is_system' => true],
            ]],
        ]);
        $cfController = Tinebase_CustomField::getInstance();

        foreach ($uninstallCfs as $appModel) {
            // Tinebase_Application doesnt know about us anymore, we are basically already uninstalled
            if ('CrewScheduling' === $appModel['app']) {
                $appId = $_application->getId();
            } else {
                $appId = Tinebase_Application::getInstance()->getApplicationByName($appModel['app'])->getId();
            }

            foreach ($appModel['cfields'] as $customfield) {
                $filterData = [
                    ['field' => 'name', 'operator' => 'equals', 'value' => $customfield['name']],
                    ['field' => 'application_id', 'operator' => 'equals', 'value' => $appId],
                    ['field' => 'model', 'operator' => 'equals', 'value' => $appModel['model']],
                ];
                $filter = new Tinebase_Model_CustomField_ConfigFilter($filterData);
                $filter->customfieldACLChecks(false);
                if (isset($customfield['is_system'])) {
                    $cfController->getConfigBackend()->setAllCFs();
                }
                $customfields = $cfController->searchConfig($filter);

                foreach ($customfields as $cFConfig) {
                    $cfController->deleteCustomField($cFConfig);
                }
            }
        }
        $cfController->getConfigBackend()->setNoSystemCFs();
    }
}
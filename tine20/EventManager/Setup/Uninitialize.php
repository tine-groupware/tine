<?php
/**
 * Tine 2.0
 *
 * @package     EventManager
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Leuschel <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Class to handle application uninitialization
 *
 * @package     EventManager
 * @subpackage  Setup
 */
class EventManager_Setup_Uninitialize extends Setup_Uninitialize
{
    protected function _uninitializeDeleteEventContacts()
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }

        $container_id = EventManager_Config::getInstance()
            ->get(EventManager_Config::DEFAULT_CONTACT_EVENT_CONTAINER);
        if ($container_id) {
            Tinebase_Container::getInstance()->deleteContainer($container_id);
        }
    }

    protected function _uninitializeDeleteEventFolderFilemanager()
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }

        $prefix = Tinebase_FileSystem::getInstance()->getApplicationBasePath('Filemanager') . '/folders/';
        $path = EventManager_Config::getInstance()
            ->get(EventManager_Config::EVENT_FOLDER_FILEMANAGER_PATH);
        Filemanager_Controller_Node::getInstance()->deleteNodes([$prefix . $path]);
    }

    protected function _uninitializeCostCenterCostBearer()
    {
        // TODO: maybe this should be done in generic \Tinebase_Application::removeApplicationAuxiliaryData ?
        if (Tinebase_Core::isReplica()) {
            return;
        }

        Tinebase_Controller_EvaluationDimension::removeModelsFromDimension(
            Tinebase_Model_EvaluationDimension::COST_CENTER,
            [EventManager_Model_Event::class]
        );
        Tinebase_Controller_EvaluationDimension::removeModelsFromDimension(
            Tinebase_Model_EvaluationDimension::COST_BEARER,
            [EventManager_Model_Event::class]
        );
    }
}

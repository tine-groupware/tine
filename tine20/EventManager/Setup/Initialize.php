<?php

declare(strict_types=1);

/**
 * Tine 2.0
  *
 * @package     EventManager
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2025-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

/**
 * class for EventManager initialization
 *
 * @package EventManager
 */
class EventManager_Setup_Initialize extends Setup_Initialize
{
    /**
     * initialize folders for events, options, and registrations
     */
    public function _initializeEventFolders(Tinebase_Model_Application $_application, $_options = null)
    {
        self::createEventFolder();
    }

    /**
     * create the events folder
     */
    public static function createEventFolder()
    {
        $prefix = Tinebase_FileSystem::getInstance()->getApplicationBasePath('Filemanager') . '/folders/';
        $translation = Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME);
        $path = Tinebase_FileSystem::FOLDER_TYPE_SHARED . '/' . $translation->_('Events');

        EventManager_Config::getInstance()
            ->set(EventManager_Config::EVENT_FOLDER_FILEMANAGER_PATH, $path);

        if (Tinebase_Core::isReplica()) {
            return;
        }
        try {
            $nodeController = Filemanager_Controller_Node::getInstance();
            $basePath = EventManager_Config::getInstance()->get(EventManager_Config::EVENT_FOLDER_FILEMANAGER_PATH);
            if (!Tinebase_FileSystem::getInstance()->isDir($prefix . $basePath)) {
                $node = $nodeController->createNodes(
                    [$path],
                    Tinebase_Model_Tree_FileObject::TYPE_FOLDER
                )->getFirstRecord();
            } else {
                $node = Tinebase_FileSystem::getInstance()->stat($prefix . $basePath);
            }

            $grants = Tinebase_Tree_NodeGrants::getInstance()->getGrantsForRecord($node);
            $grants->addRecord(new Tinebase_Model_Grants([
                'account_id' => Tinebase_User::getInstance()
                    ->getFullUserByLoginName(Tinebase_User::SYSTEM_USER_ANONYMOUS)->getId(),
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Tinebase_Model_Grants::GRANT_READ => true,
                Tinebase_Model_Grants::GRANT_ADD => true,
            ]));
            Tinebase_FileSystem::getInstance()->setGrantsForNode($node, $grants);
        } catch (Filemanager_Exception_NodeExists $e) {
            // This is fine
        };
    }

    protected function _initializeCostCenterCostBearer()
    {
        self::initializeCostCenterCostBearer();
    }

    public static function initializeCostCenterCostBearer()
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }

        Tinebase_Controller_EvaluationDimension::addModelsToDimension(Tinebase_Model_EvaluationDimension::COST_CENTER, [
            EventManager_Model_Event::class,
        ]);
        Tinebase_Controller_EvaluationDimension::addModelsToDimension(Tinebase_Model_EvaluationDimension::COST_BEARER, [
            EventManager_Model_Event::class,
        ]);
    }
}

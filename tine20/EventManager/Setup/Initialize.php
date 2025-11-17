<?php
/**
 * Tine 2.0
  *
 * @package     EventManager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * initialize folders for events, options and registrations
     */
    public function _initializeEventFolders(Tinebase_Model_Application $_application, $_options = null)
    {
        self::createEventFolder();
    }

    /**
     * create event folder
     */
    public static function createEventFolder()
    {
        $prefix = Tinebase_FileSystem::getInstance()->getApplicationBasePath('Filemanager') . '/folders/';
        $translation = Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME);
        $path = '/' . Tinebase_FileSystem::FOLDER_TYPE_SHARED . '/' . $translation->_('Events');

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
    protected function _initializeDefaultContainer()
    {
        if (Tinebase_Core::isReplica()) {
            $this->getContactEventContainer();
            return;
        }

        $groupsBackend = Tinebase_Group::getInstance();
        $grants = new Tinebase_Record_RecordSet(Tinebase_Model_Grants::class, [
            [
                'account_id' => Tinebase_User::getInstance()
                    ->getFullUserByLoginName(Tinebase_User::SYSTEM_USER_ANONYMOUS)->getId(),
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Tinebase_Model_Grants::GRANT_READ => true,
                Tinebase_Model_Grants::GRANT_EDIT => true,
                Tinebase_Model_Grants::GRANT_ADD => true,
            ],
            [
                'account_id' => $groupsBackend->getDefaultAdminGroup()->getId(),
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
                Tinebase_Model_Grants::GRANT_ADD => true,
                Tinebase_Model_Grants::GRANT_READ => true,
                Tinebase_Model_Grants::GRANT_EDIT => true,
                Tinebase_Model_Grants::GRANT_DELETE => true,
                Tinebase_Model_Grants::GRANT_ADMIN => true
            ],
        ]);
        $systemContainer = Tinebase_Container::getInstance()->createSystemContainer(
            Addressbook_Config::APP_NAME,
            Addressbook_Model_Contact::class,
            'Event Contacts',
            grants: $grants
        );
        // config must be set after, since it belongs to EventManager and not Addressbook
        EventManager_Config::getInstance()
            ->set(EventManager_Config::DEFAULT_CONTACT_EVENT_CONTAINER, $systemContainer->getId());
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

    public function getContactEventContainer()
    {
        $configInstance = EventManager_Config::getInstance();
        $containerId = $configInstance->get(EventManager_Config::DEFAULT_CONTACT_EVENT_CONTAINER);

        if ($containerId) {
            return Tinebase_Container::getInstance()->getContainerById($containerId);
        }

        if (Tinebase_Core::isReplica()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
                    ' Replica: Syncing modification logs to get Event Contacts container');
            }
            Tinebase_Timemachine_ModificationLog::getInstance()->readModificationLogFromMaster(1000);
        }

        $containerBackend = Tinebase_Container::getInstance();
        $filters = new Tinebase_Model_ContainerFilter([
            ['field' => 'application_id', 'operator' => 'equals', 'value' => Tinebase_Application::getInstance()
                ->getApplicationByName(Addressbook_Config::APP_NAME)->getId()],
            ['field' => 'model', 'operator' => 'equals', 'value' => Addressbook_Model_Contact::class],
            ['field' => 'name', 'operator' => 'equals', 'value' => 'Event Contacts'],
            ['field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Container::TYPE_SHARED],
        ]);

        $containers = $containerBackend->search($filters);

        if ($containers->count() === 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ .
                    ' Event Contacts container not found');
            }
            return null;
        }

        $container = $containers->getFirstRecord();

        $configInstance->set(EventManager_Config::DEFAULT_CONTACT_EVENT_CONTAINER, $container->getId());

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
                ' Set DEFAULT_CONTACT_EVENT_CONTAINER config to: ' . $container->getId());
        }

        return $container;
    }
}

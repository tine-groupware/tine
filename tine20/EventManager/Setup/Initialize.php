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
        self::addPastoralUrl();
    }

    /**
     * create the events folder
     */
    public static function createEventFolder()
    {
        $prefix = Tinebase_FileSystem::getInstance()->getApplicationBasePath('Filemanager') . '/folders/';
        $translation = Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME);
        $path = Tinebase_FileSystem::FOLDER_TYPE_SHARED . '/' . $translation->_('Events');

        $folderEventExists = Tinebase_FileSystem::getInstance()->fileExists($prefix . $path);

        if ($folderEventExists) {
            $path = Tinebase_FileSystem::FOLDER_TYPE_SHARED . '/' . $translation->_('EventManager');
        }

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
            $grants->addRecord(new Tinebase_Model_Grants([
                'account_id' => Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
                Tinebase_Model_Grants::GRANT_READ => true,
                Tinebase_Model_Grants::GRANT_ADD => true,
            ]));
            Tinebase_FileSystem::getInstance()->setGrantsForNode($node, $grants);
        } catch (Filemanager_Exception_NodeExists $e) {
            // This is fine
        };
    }

    protected function _initializeContainer()
    {
        if (!Tinebase_Core::isReplica()) {
            $eventContainer = EventManager_Config::getInstance()->get(EventManager_Config::EVENT_SHARED_CONTAINER_NAME);
            $this->_getOrCreateSharedEventContainer($eventContainer);

            $eventTemplatesContainer = EventManager_Config::getInstance()->get(EventManager_Config::EVENT_TEMPLATES_CONTAINER_NAME);
            $this->_getOrCreateSharedEventContainer($eventTemplatesContainer);

            $eventCalendarContainer = EventManager_Config::getInstance()->get(EventManager_Config::EVENT_SHARED_CALENDAR_NAME);
            $this->_getORCreateSharedEventCalendar($eventCalendarContainer);
        }
    }

    public static function _getOrCreateSharedEventContainer($containerName)
    {
        try {
            $container = Tinebase_Container::getInstance()->getContainerByName(
                EventManager_Model_Event::class,
                $containerName,
                Tinebase_Model_Container::TYPE_SHARED,
            );
        } catch (Tinebase_Exception_NotFound $e) {
            $container = new Tinebase_Model_Container([
                'name'              => $containerName,
                'type'              => Tinebase_Model_Container::TYPE_SHARED,
                'owner_id'          => Tinebase_Core::getUser(),
                'backend'           => 'Sql',
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName(EventManager_Config::APP_NAME)->getId(),
                'model'             => Calendar_Model_Event::class
            ]);
            Tinebase_Container::getInstance()->addContainer($container);

            $grants = new Tinebase_Record_RecordSet(Tinebase_Model_Grants::class, [[
                'account_id'   => Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
                Tinebase_Model_Grants::GRANT_READ   => true,
                Tinebase_Model_Grants::GRANT_ADD    => true,
                Tinebase_Model_Grants::GRANT_EDIT   => true,
                Tinebase_Model_Grants::GRANT_DELETE => true,
            ], [
                'account_id'   => Tinebase_Group::getInstance()->getDefaultAdminGroup()->getId(),
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
                Tinebase_Model_Grants::GRANT_READ   => true,
                Tinebase_Model_Grants::GRANT_ADD    => true,
                Tinebase_Model_Grants::GRANT_EDIT   => true,
                Tinebase_Model_Grants::GRANT_DELETE => true,
                Tinebase_Model_Grants::GRANT_ADMIN => true,
            ]]);
            Tinebase_Container::getInstance()->setGrants($container->getId(), $grants, true, false);
        }
        return $container;
    }

    public static function _getOrCreateSharedEventCalendar($calendarName)
    {
        try {
            Tinebase_Container::getInstance()->getContainerByName(
                Calendar_Model_Event::class,
                $calendarName,
                Tinebase_Model_Container::TYPE_SHARED,
            );
        } catch (Tinebase_Exception_NotFound $e) {
            $container = new Tinebase_Model_Container([
                'name'              => $calendarName,
                'type'              => Tinebase_Model_Container::TYPE_SHARED,
                'owner_id'          => Tinebase_Core::getUser(),
                'backend'           => 'Sql',
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName(Calendar_Config::APP_NAME)->getId(),
                'model'             => Calendar_Model_Event::class
            ]);
            Tinebase_Container::getInstance()->addContainer($container);

            $grants = new Tinebase_Record_RecordSet(Tinebase_Model_Grants::class, [[
                'account_id'   => Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
                Tinebase_Model_Grants::GRANT_READ   => true,
                Tinebase_Model_Grants::GRANT_ADD    => true,
                Tinebase_Model_Grants::GRANT_EDIT   => true,
                Tinebase_Model_Grants::GRANT_DELETE => true,
                Tinebase_Model_Grants::GRANT_EXPORT => true,
            ], [
                'account_id'   => Tinebase_Group::getInstance()->getDefaultAdminGroup()->getId(),
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
                Tinebase_Model_Grants::GRANT_READ   => true,
                Tinebase_Model_Grants::GRANT_ADD    => true,
                Tinebase_Model_Grants::GRANT_EDIT   => true,
                Tinebase_Model_Grants::GRANT_DELETE => true,
                Tinebase_Model_Grants::GRANT_ADMIN => true,
                Tinebase_Model_Grants::GRANT_EXPORT => true,
            ]]);
            Tinebase_Container::getInstance()->setGrants($container->getId(), $grants, true, false);
        }
    }

    protected function _initializeFavorites()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();

        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName(EventManager_Config::APP_NAME)->getId(),
            'model'             => 'EventManager_Model_EventFilter',
        );

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, [
            'name'              => "Templates", // _("Templates")
            'description'       => "All templates for events", // _("All templates for events")
            'filters'           => [[
                'field'     => EventManager_Model_Event::FLD_IS_TEMPLATE,
                'operator'  => 'equals',
                'value'     => true,
            ]],
        ])));
    }

    protected function _initializeEventTemplates()
    {
        EventManager_Setup_EventTemplates::getInstance()->createTemplates();
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

        try {
            $def = Tinebase_ImportExportDefinition::getInstance()->getByName('tinebase_import_costcenter_csv');
            $importer = Tinebase_Import_Csv_Generic::createFromDefinition($def);
            $importer->importFile(__DIR__ . '/DemoData/files/costcenter.csv');
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Exception::log($tenf);
        }
    }

    /**
     * set url for events in pastoral Erzbistum Hamburg (Website)
     */
    public static function addPastoralUrl()
    {
        $url =  'https://pastoral-erzbistum-hamburg.de/' ; //todo change this to correct url
        $url = Tinebase_Core::getUrl() . '/EventManager/view/events'; // todo delete this when correct url is set

        EventManager_Config::getInstance()
            ->set(EventManager_Config::EVENT_PASTORAL_URL, $url);
    }
}

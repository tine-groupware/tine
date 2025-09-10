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
        if (Tinebase_Core::isReplica()) {
            return;
        }
        $nodeController = Filemanager_Controller_Node::getInstance();
        try {
            // TODO translate folder name
            $nodeController->createNodes('/' . Tinebase_FileSystem::FOLDER_TYPE_SHARED . '/Veranstaltungen',
                Tinebase_Model_Tree_FileObject::TYPE_FOLDER);
        } catch (Filemanager_Exception_NodeExists $e) {
            // This is fine
        };
    }

    protected function _initializeDefaultContainer()
    {
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
}

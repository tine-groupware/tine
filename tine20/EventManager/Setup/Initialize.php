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
        $nodeController = Filemanager_Controller_Node::getInstance();
        try {
            $nodeController->createNodes('/' . Tinebase_FileSystem::FOLDER_TYPE_SHARED . '/Veranstaltungen', Tinebase_Model_Tree_FileObject::TYPE_FOLDER);
        } catch (Filemanager_Exception_NodeExists $e) {
            // This is fine
        };
    }
}

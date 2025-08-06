<?php
/**
 * Event controller
 *
 * @package     EventManager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Event controller
 *
 * @package     EventManager
 * @subpackage  Controller
 */
class EventManager_Controller_Option extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = EventManager_Config::APP_NAME;
        $this->_modelName = EventManager_Model_Option::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => EventManager_Model_Option::class,
            Tinebase_Backend_Sql::TABLE_NAME    => EventManager_Model_Option::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => false
        ]);

        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    /**
     * inspect creation of one record (after create)
     *
     * @param   Tinebase_Record_Interface $_createdRecord
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        parent::_inspectAfterCreate($_createdRecord, $_record);

        $this->_handleFileUpload($_record);
    }

    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectAfterUpdate($_updatedRecord, $_record, $_oldRecord)
    {
        parent::_inspectAfterUpdate($_updatedRecord, $_record, $_oldRecord);
        $this->_handleFileUpload($_updatedRecord);
    }

    protected function _handleFileUpload(EventManager_Model_Option $_option)
    {
        if (!$_option->{EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS} === EventManager_Model_FileOption::class) {
            return;
        }

        // put uploaded tempfile into filemanager
        if (is_string(
            $_option->{EventManager_Model_Option::FLD_OPTION_CONFIG}->{EventManager_Model_FileOption::FLD_NODE_ID})) {
            try {
                $tempFile = Tinebase_TempFile::getInstance()->getTempFile(
                    $_option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
                        ->{EventManager_Model_FileOption::FLD_NODE_ID}
                );
                $event = EventManager_Controller_Event::getInstance()->get(
                    $_option->{EventManager_Model_Option::FLD_EVENT_ID}
                );
                $eventName = $event->{EventManager_Model_Event::FLD_NAME};
                $path = Tinebase_FileSystem::FOLDER_TYPE_SHARED . "/Veranstaltungen";
                $folders = ["/$eventName", "/Optionen"];
                $nodeController = Filemanager_Controller_Node::getInstance();
                $prefix = Tinebase_FileSystem::getInstance()->getApplicationBasePath('Filemanager') . '/folders/';

                foreach ($folders as $folder) {
                    $path = $path . $folder;
                    if (!Tinebase_FileSystem::getInstance()->isDir($prefix . $path)) {
                        $nodeController->createNodes(
                            [$path],
                            [Tinebase_Model_Tree_FileObject::TYPE_FOLDER]
                        );
                    }
                }
                $fileName = $path . "/" . $_option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
                        ->{EventManager_Model_FileOption::FLD_FILE_NAME};
                if (!Tinebase_FileSystem::getInstance()->fileExists($prefix . $fileName)) {
                    $node = $nodeController->createNodes(
                        [$fileName],
                        [Tinebase_Model_Tree_FileObject::TYPE_FILE],
                        [$tempFile->getId()]
                    )->getFirstRecord();
                    $_option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
                        ->{EventManager_Model_FileOption::FLD_NODE_ID} = $node->getId();
                    $this->update($_option);
                }
            } catch (Tinebase_Exception_NotFound) {}
        }
    }
}

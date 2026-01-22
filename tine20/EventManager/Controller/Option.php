<?php

declare(strict_types=1);

/**
 * Option controller
 *
 * @package     EventManager
 * @subpackage  Controller
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de> Tonia Wulff <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2020-2025 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

/**
 * Option controller
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

        $this->_handleOptionFileUpload($_record);
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
        $this->_handleOptionFileUpload($_updatedRecord);
    }

    protected function _handleOptionFileUpload(EventManager_Model_Option $_option)
    {
        if (!$_option->{EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS} === EventManager_Model_FileOption::class) {
            return;
        }

        $nodeId = $_option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
            ->{EventManager_Model_FileOption::FLD_NODE_ID};

        if (!is_string($nodeId)) {
            return;
        }

        $fileName = $_option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
            ->{EventManager_Model_FileOption::FLD_FILE_NAME};

        $eventId = $_option->{EventManager_Model_Option::FLD_EVENT_ID};

        $translation = Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME);
        $folderPath = ['/' . $translation->_('Options')];

        $updateCallback = function ($node) use ($_option) {
            $_option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
                ->{EventManager_Model_FileOption::FLD_NODE_ID} = $node->getId();
            $this->update($_option);
        };

        EventManager_Controller::processFileUpload($nodeId, $fileName, $eventId, $folderPath, $updateCallback);
    }
}

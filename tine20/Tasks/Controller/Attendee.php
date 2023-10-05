<?php declare(strict_types=1);

/**
 * SubProductMapping controller for Sales application
 *
 * @package     Tasks
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * TaskDependency controller class for Tasks application
 *
 * @package     Tasks
 * @subpackage  Controller
 */
class Tasks_Controller_Attendee extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Tasks_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Tasks_Model_Attendee::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Tasks_Model_Attendee::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Tasks_Model_Attendee::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    /**
     * inspect alarm and set time
     *
     * @param Tasks_Model_Attendee $_record
     * @param Tinebase_Model_Alarm $_alarm
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _inspectAlarmSet(Tinebase_Record_Interface $_record, Tinebase_Model_Alarm $_alarm)
    {
        $result = Tasks_Controller_Task::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tasks_Model_Task::class, [
            [TMFA::FIELD => 'id', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $_record->getIdFromProperty(Tasks_Model_Attendee::FLD_TASK_ID)],
        ]), null, false, ['id', Tasks_Model_Task::FLD_DUE]);

        if (count($result) !== 1) {
            throw new Tinebase_Exception('did not find task with id ' . $_record->getIdFromProperty(Tasks_Model_Attendee::FLD_TASK_ID) . ' for attendee ' . $_record->getId());
        }
        $_alarm->setTime(new Tinebase_DateTime(current($result)));
    }
}

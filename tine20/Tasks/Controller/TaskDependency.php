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

/**
 * TaskDependency controller class for Tasks application
 *
 * @package     Tasks
 * @subpackage  Controller
 */
class Tasks_Controller_TaskDependency extends Tinebase_Controller_Record_Abstract
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
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Tasks_Model_TaskDependency::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Tasks_Model_TaskDependency::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Tasks_Model_TaskDependency::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    /**
     * inspect creation of one record (before create)
     *
     * @param   Tasks_Model_TaskDependency $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {

        parent::_inspectBeforeCreate($_record);
    }

    /**
     * @param Tasks_Model_TaskDependency $_record
     * @param Tasks_Model_TaskDependency $_oldRecord
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        /** @phpstan-ignore-next-line */
        if (!$_record->diff($_oldRecord)->isEmpty()) {
            throw new Tinebase_Exception('no updates on n:m dependency table');
        }
    }
}

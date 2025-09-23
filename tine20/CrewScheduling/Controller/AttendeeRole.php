<?php
/**
 * Tine 2.0
 *
 * @package     CrewScheduling
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2017-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * AttendeeRole controller for CrewScheduling
 *
 * @package     CrewScheduling
 * @subpackage  Controller
 */
class CrewScheduling_Controller_AttendeeRole extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_doContainerACLChecks = false;
        $this->_applicationName = CrewScheduling_Config::APP_NAME;
        $this->_modelName = CrewScheduling_Model_AttendeeRole::class;
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName'     => CrewScheduling_Model_AttendeeRole::class,
            'tableName'     => CrewScheduling_Model_AttendeeRole::TABLE_NAME,
            'modlogActive'  => false
        ));
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }

    /**
     * holds the instance of the singleton
     *
     * @var CrewScheduling_Controller_SchedulingRole
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return CrewScheduling_Controller_SchedulingRole
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new CrewScheduling_Controller_AttendeeRole();
        }

        return self::$_instance;
    }

    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        parent::_inspectAfterCreate($_createdRecord, $_record);
    }

    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        parent::_inspectAfterUpdate($updatedRecord, $record, $currentRecord);
    }

    public static function modelConfigHook(array &$_fields, Tinebase_ModelConfiguration $mc): void
    {
        $expanderDef = $mc->jsonExpander;
        $expanderDef[Tinebase_Record_Expander::EXPANDER_PROPERTIES][CrewScheduling_Config::CREWSHEDULING_ROLES] = [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                CrewScheduling_Model_AttendeeRole::FLD_ROLE => [],
                CrewScheduling_Model_AttendeeRole::FLD_EVENT_TYPES => [],
            ],
        ];
        $mc->setJsonExpander($expanderDef);
    }
}

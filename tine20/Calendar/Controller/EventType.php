<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * EventType controller for Calendar
 *
 * @package     Calendar
 * @subpackage  Controller
 */
class Calendar_Controller_EventType extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_doContainerACLChecks = false;
        $this->_applicationName = 'Calendar';
        $this->_modelName = 'Calendar_Model_EventType';
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName'     => 'Calendar_Model_EventType',
            'tableName'     => 'cal_event_type',
            'modlogActive'  => true
        ));
        $this->_purgeRecords = FALSE;
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
     * @var Calendar_Controller_EventType
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Calendar_Controller_EventType
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Calendar_Controller_EventType();
        }
        
        return self::$_instance;
    }
}

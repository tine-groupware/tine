<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Poll
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl <c.feitl@metaways.de>
 */

/**
 * controller for Poll
 *
 * @package     Poll
 * @subpackage  Controller
 */
class Poll_Controller_Poll extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_doContainerACLChecks = false;
        $this->_applicationName = 'Poll';
        $this->_modelName = 'Poll_Model_Poll';
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Poll_Model_Poll',
            'tableName' => 'Poll_Poll',
            'modlogActive' => true
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
     * @var Poll_Controller_Poll
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Poll_Controller_Poll
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Poll_Controller_Poll();
        }

        return self::$_instance;
    }
}

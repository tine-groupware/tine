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
}

<?php declare(strict_types=1);

/**
 * OptionsRule controller for EventManager application
 *
 * @package     EventManager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * OptionsRule controller class for EventManager application
 *
 * @package     EventManager
 * @subpackage  Controller
 */
class EventManager_Controller_OptionsRule extends Tinebase_Controller_Record_Abstract
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
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => EventManager_Model_OptionsRule::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => EventManager_Model_OptionsRule::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => false,
        ]);
        $this->_modelName = EventManager_Model_OptionsRule::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }
}
<?php declare(strict_types=1);
/**
 * Contact Address controller for EventManager
 *
 * @package     EventManager
 * @subpackage  Controller
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * @extends Tinebase_Controller_Record_Abstract<EventManager_Model_Register_ContactPropertiesAddress>
 */
class EventManager_Controller_Register_ContactPropertiesAddress extends Tinebase_Controller_Record_Abstract
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
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => EventManager_Model_Register_ContactPropertiesAddress::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => EventManager_Model_Register_ContactPropertiesAddress::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = EventManager_Model_Register_ContactPropertiesAddress::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }
}

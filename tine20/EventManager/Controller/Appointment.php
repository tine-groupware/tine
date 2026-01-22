<?php

declare(strict_types=1);

/**
 * Appointment controller
 *
 * @package     EventManager
 * @subpackage  Controller
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.leuschel@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

/**
 * Appointment controller
 *
 * @package     EventManager
 * @subpackage  Controller
 */
class EventManager_Controller_Appointment extends Tinebase_Controller_Record_Abstract
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
        $this->_modelName = EventManager_Model_Appointment::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => EventManager_Model_Appointment::class,
            Tinebase_Backend_Sql::TABLE_NAME    => EventManager_Model_Appointment::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => false
        ]);

        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }
}

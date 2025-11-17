<?php declare(strict_types=1);

/**
 * PollEventType controller for CrewScheduling application
 *
 * @package     CrewScheduling
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.cweiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * PollEventType controller class for CrewScheduling application
 *
 * @package     CrewScheduling
 * @subpackage  Controller
 */
class CrewScheduling_Controller_PollEventType extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = CrewScheduling_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => CrewScheduling_Model_PollEventType::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => CrewScheduling_Model_PollEventType::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => false,
        ]);
        $this->_modelName = CrewScheduling_Model_PollEventType::class;
        $this->_purgeRecords = true;
        $this->_doContainerACLChecks = false;
    }
}

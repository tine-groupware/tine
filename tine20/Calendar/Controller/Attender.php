<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Calendar Attender FAKE Controller, used to set dependent records on attendees
 *
 * @package Calendar
 * @subpackage  Controller
 */
class Calendar_Controller_Attender extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    protected function __construct()
    {
        $this->_applicationName = Calendar_Config::APP_NAME;
        $this->_modelName = Calendar_Model_Attender::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME => $this->_modelName,
            Tinebase_Backend_Sql::TABLE_NAME => Calendar_Model_Attender::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => true,
        ]);
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
        $this->_doRightChecks = false;
    }
}
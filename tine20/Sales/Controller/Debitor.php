<?php declare(strict_types=1);
/**
 * Debitor controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Debitor controller class for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Debitor extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Sales_Config::APP_NAME;
        $this->_modelName = Sales_Model_Debitor::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => $this->_modelName,
            Tinebase_Backend_Sql::TABLE_NAME    => Sales_Model_Debitor::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => true,
        ]);

        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = true;
    }
}

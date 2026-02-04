<?php declare(strict_types=1);

/**
 * PurchaseInvoice DocumentPosition controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * PurchaseInvoice DocumentPosition controller class for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_DocumentPosition_PurchaseInvoice extends Sales_Controller_DocumentPosition_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Sales_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Sales_Model_DocumentPosition_PurchaseInvoice::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Sales_Model_DocumentPosition_PurchaseInvoice::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Sales_Model_DocumentPosition_PurchaseInvoice::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }
}

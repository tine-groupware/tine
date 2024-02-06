<?php declare(strict_types=1);
/**
 * DivisionEvalDimensionItem controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * DivisionEvalDimensionItem controller class for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_DivisionEvalDimensionItem extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Sales_Config::APP_NAME;
        $this->_modelName = Sales_Model_DivisionEvalDimensionItem::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => $this->_modelName,
            Tinebase_Backend_Sql::TABLE_NAME    => Sales_Model_DivisionEvalDimensionItem::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => true,
        ]);

        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }
}

<?php declare(strict_types=1);

/**
 * UnitOfMeasure controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * v controller class for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 *
 * @extends Tinebase_Controller_Record_Abstract<Sales_Model_EDocument_UnitOfMeasure>
 */
class Sales_Controller_EDocument_UnitOfMeasure extends Tinebase_Controller_Record_Abstract
{
    /** @use Tinebase_Controller_SingletonTrait<Sales_Controller_EDocument_UnitOfMeasure> */
    use Tinebase_Controller_SingletonTrait;

    /**
     * @throws Tinebase_Exception_Backend_Database
     */
    protected function __construct()
    {
        $this->_applicationName = Sales_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Sales_Model_EDocument_UnitOfMeasure::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Sales_Model_EDocument_UnitOfMeasure::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Sales_Model_EDocument_UnitOfMeasure::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    public function getByCode(string $code): ?Sales_Model_EDocument_UnitOfMeasure
    {
        return $this->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->_modelName, [
                [TMFA::FIELD => Sales_Model_EDocument_UnitOfMeasure::FLD_CODE, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $code],
            ]))->getFirstRecord();
    }
}

<?php declare(strict_types=1);

/**
 * Category controller for Sale Documents
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Sales Tax controller for Sale Documents
 *
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Document_SalesTax extends Tinebase_Controller_Record_Abstract
{
    /** @use Tinebase_Controller_SingletonTrait<Sales_Controller_Document_SalesTax> */
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
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Sales_Model_Document_SalesTax::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Sales_Model_Document_SalesTax::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Sales_Model_Document_SalesTax::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        /** @var Sales_Model_Document_SalesTax $_record */
        $this->_inspectVATConsistency($_record);
        parent::_inspectBeforeCreate($_record);
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        /** @var Sales_Model_Document_SalesTax $_record */
        $this->_inspectVATConsistency($_record);
        parent::_inspectBeforeUpdate($_record, $_oldRecord);
    }

    protected function _inspectVATConsistency(Sales_Model_Document_SalesTax $_record): void
    {
        $taxAmount = $_record->{Sales_Model_Document_SalesTax::FLD_NET_AMOUNT} * $_record->{Sales_Model_Document_SalesTax::FLD_TAX_RATE} / 100;
        if ((float)$_record->{Sales_Model_Document_SalesTax::FLD_GROSS_AMOUNT} !== (float)$_record->{Sales_Model_Document_SalesTax::FLD_NET_AMOUNT} + (float)$_record->{Sales_Model_Document_SalesTax::FLD_TAX_AMOUNT} ||
             $taxAmount < $_record->{Sales_Model_Document_SalesTax::FLD_TAX_AMOUNT} - 0.05 || $taxAmount > $_record->{Sales_Model_Document_SalesTax::FLD_TAX_AMOUNT} + 0.05) {
            throw new Tinebase_Exception_Record_Validation('tax amount, net amount, gros amount and tax rate are not coherent');
        }
    }
}

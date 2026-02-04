<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Sales_Model_Document_PurchaseInvoice as PurchaseInvoice;
use Tinebase_Model_Filter_Abstract as TMFA;

class Sales_Import_Document_PurchaseInvoice_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * additional config options
     *
     * @var array
     */
    protected $_additionalOptions = array(
        'container_id' => '',
        'dates'        => [],
    );

    public function __construct(array $_options = array())
    {
        $this->_additionalOptions['dates'] = array_merge(PurchaseInvoice::getConfiguration()->dateFields, PurchaseInvoice::getConfiguration()->datetimeFields);
        parent::__construct($_options);
    }

    /**
     * @param array $_data
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _doConversions($_data)
    {
        $result = parent::_doConversions($_data);
        $result = $this->_setCostCenter($result);
        $result = $this->_setContact($result);
        $result = $this->_setSupplier($result);

        if (!($result[PurchaseInvoice::FLD_DUE_AT] ?? null) instanceof Tinebase_DateTime &&
                ($result[PurchaseInvoice::FLD_DOCUMENT_DATE] ?? null) instanceof Tinebase_DateTime &&
                is_numeric($result[PurchaseInvoice::FLD_PAYMENT_TERMS] ?? null)) {
            $result[PurchaseInvoice::FLD_DUE_AT] = $result[PurchaseInvoice::FLD_DOCUMENT_DATE]->getClone()->addDay($result[PurchaseInvoice::FLD_PAYMENT_TERMS]);
        }

        if (is_numeric($result[PurchaseInvoice::FLD_NET_SUM])) {
            $result[PurchaseInvoice::FLD_POSITIONS_NET_SUM] = $result[PurchaseInvoice::FLD_NET_SUM];
        }
        if (is_numeric($result[PurchaseInvoice::FLD_SALES_TAX] ?? null) &&
                is_numeric($result[PurchaseInvoice::FLD_NET_SUM] ?? null) &&
                is_numeric($result['price_tax'] ?? null) &&
                is_numeric($result[PurchaseInvoice::FLD_POSITIONS_GROSS_SUM] ?? null)) {
            $result[PurchaseInvoice::FLD_SALES_TAX_BY_RATE] = [[
                Sales_Model_Document_SalesTax::FLD_TAX_RATE => $result[PurchaseInvoice::FLD_SALES_TAX],
                Sales_Model_Document_SalesTax::FLD_TAX_AMOUNT => $result['price_tax'],
                Sales_Model_Document_SalesTax::FLD_NET_AMOUNT => $result[PurchaseInvoice::FLD_NET_SUM],
                Sales_Model_Document_SalesTax::FLD_GROSS_AMOUNT => $result[PurchaseInvoice::FLD_POSITIONS_GROSS_SUM],
            ]];
        }
        unset($result['price_tax']);

        return $result;
    }

    protected function _setCostCenter(array $result): array
    {
        if (!empty($result['costcenter'])) {
            $result[PurchaseInvoice::FLD_EVAL_DIM_COST_CENTER] = Tinebase_Controller_EvaluationDimensionItem::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_EvaluationDimensionItem::class, [
                ['field' => Tinebase_Model_EvaluationDimensionItem::FLD_EVALUATION_DIMENSION_ID, 'operator' => 'definedBy', 'value' => [
                    ['field' => Tinebase_Model_EvaluationDimension::FLD_NAME, 'operator' => 'equals', 'value' => Tinebase_Model_EvaluationDimension::COST_CENTER],
                ]],
            ]))->getFirstRecord()?->getId();
        }
        return $result;
    }

    protected function _setContact(array $result): array
    {
        if (!empty($result['contact'])) {
            $result[PurchaseInvoice::FLD_APPROVER] = Addressbook_Controller_Contact::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Addressbook_Model_Contact::class, [
                [TMFA::FIELD => 'n_fileas', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $result['contact']],
            ]))->getFirstRecord()?->account_id ?: null;
        }
        return $result;
    }

    protected function _setSupplier(array $result): array
    {
        if (!empty($result['supplier'] ?? null)) {
            $result[PurchaseInvoice::FLD_SUPPLIER_ID] = Sales_Controller_Supplier::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Supplier::class, [
                    [TMFA::FIELD => 'name', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $result['supplier']],
                ]))->getFirstRecord()?->getId();
        }
        return $result;
    }

}

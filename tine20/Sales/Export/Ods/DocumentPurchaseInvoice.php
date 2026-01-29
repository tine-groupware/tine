<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Sales_Export_Ods_DocumentPurchaseInvoice extends Sales_Export_Ods_Abstract
{
    /**
     * default export definition name
     *
     * @var string
     */
    protected $_defaultExportname = 'document_purchaseinvoice_default_ods';

    /**
     * all addresses (Sales_Model_Address) needed for the export
     *
     * @var Tinebase_Record_RecordSet
     */
    protected $_specialFields = array();
    
    /**
     * @param Sales_Model_Document_PurchaseInvoice $record
     */
    public function processRecord($record, $idx)
    {
        $row = $this->_activeTable->appendRow();
        
        foreach ($this->_config->columns->column as $field) {
            // get type and value for cell
            $identifier = $field->identifier;
            switch ($identifier) {
                case Sales_Model_Document_PurchaseInvoice::FLD_APPROVER:
                    $cellType  = OpenDocument_SpreadSheet_Cell::TYPE_STRING;
                    $cellValue = $record->{Sales_Model_Document_PurchaseInvoice::FLD_APPROVER}?->getTitle();
                    break;
                    
                case Sales_Model_Document_PurchaseInvoice::FLD_SUPPLIER_ID:
                    $cellType  = OpenDocument_SpreadSheet_Cell::TYPE_STRING;
                    $cellValue = $record->{Sales_Model_Document_PurchaseInvoice::FLD_SUPPLIER_ID}?->getTitle();
                    break;

                case Sales_Model_Document_PurchaseInvoice::FLD_NET_SUM:
                case Sales_Model_Document_Abstract::FLD_SALES_TAX:
                case Sales_Model_Document_Abstract::FLD_GROSS_SUM:
                    $field->currenry = 'EUR';
                    $cellType  = OpenDocument_SpreadSheet_Cell::TYPE_CURRENCY;
                    $cellValue = $this->_getCellValue($field, $record, $cellType);
                    break;
                    
                default:
                    $cellType  = $this->_getCellType($field->type);
                    $cellValue = $this->_getCellValue($field, $record, $cellType);
                    break;
            }

            // create cell with type and value and add style
            $cell = $row->appendCell($cellValue, $cellType);
            
            if ($field->columnStyle) {
                $cell->setStyle((string) $field->columnStyle);
            }
            
            // add formula
            if ($field->formula) {
                $cell->setFormula($field->formula);
            }
        }
    }
}

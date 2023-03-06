<?php
/**
 * Sales InvoicePosition Ods generation class
 *
 * @package     Sales
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Sales InvoicePosition Ods generation class
 *
 * @package     Sales
 * @subpackage  Export
 *
 */
class Sales_Export_Ods_InvoicePosition extends Sales_Export_Ods_Abstract
{
    /**
     * default export definition name
     *
     * @var string
     */
    protected $_defaultExportname = 'invoiceposition_default_ods';

    /**
     * get record relations
     *
     * @var boolean
     */
    protected $_getRelations = FALSE;

    /**
     * all addresses (Sales_Model_Address) needed for the export
     *
     * @var Tinebase_Record_RecordSet
     */
    protected $_specialFields = array();

    /**
     * all contacts (Addressbook_Model_Contact) needed for the export
     *
     * @var Tinebase_Record_RecordSet
     */
    protected $_contacts = NULL;
    
    /**
     * add body rows
     * 
     * @alternate, kind of POC or VIP, overwrites the default one
     *
     * @param Tinebase_Record_RecordSet $records
     */
    public function processIteration($_records)
    {
        $json = new Tinebase_Convert_Json();
        
        $productAggregateIds = $_records->accountable_id;
        $paFilter = new Sales_Model_ProductAggregateFilter();
        $paFilter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'id', 'operator' => 'in', 'value' => $productAggregateIds)));
        $productAggregates = Sales_Controller_ProductAggregate::getInstance()->search($paFilter);
        
        $pFilter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Product::class);
        $pFilter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'id', 'operator' => 'in', 'value' => array_unique($productAggregates->product_id))));
        
        $products = Sales_Controller_Product::getInstance()->search($pFilter);
        
        $resolved = $json->fromTine20RecordSet($_records);

        foreach ($resolved as $record) {
        
            $record['accountable_id'] = $productAggregates->getById($record['accountable_id'])->toArray();
            $record['product_id']     = $products->getById($record['accountable_id']['product_id'])->toArray();
            
            $row = $this->_activeTable->appendRow();
            
            $i18n = $this->_translate->getAdapter();
            
            foreach ($this->_config->columns->column as $field) {
        
                $identifier = $field->identifier;
                
                // TODO: use ModelConfig here to get the POC
                // get type and value for cell
                $cellType = $this->_getCellType($field->type);
                
                switch ($identifier) {
                    case 'quantity':
                        $value = intval($record[$identifier]) * intval($record['accountable_id']['quantity']);
                        break;
                    case 'month':
                        $value = $record[$identifier];
                        break;
                    default:
                        $value = $record['product_id'][$identifier] ?? '';
                } 
                
                // create cell with type and value and add style
                $cell = $row->appendCell($value, $cellType);
                
                if ($field->customStyle) {
                    $cell->setStyle((string) $field->customStyle);
                }
            }
        }
    }
}

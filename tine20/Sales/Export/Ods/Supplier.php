<?php
/**
 * Sales Supplier Ods generation class
 *
 * @package     Sales
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Sales Supplier Ods generation class
 *
 * @package     Sales
 * @subpackage  Export
 *
 */
class Sales_Export_Ods_Supplier extends Sales_Export_Ods_Abstract
{
    /**
     * default export definition name
     *
     * @var string
     */
    protected $_defaultExportname = 'supplier_default_ods';

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
    protected $_addresses = NULL;
    protected $_supplierAddresses = array();
    protected $_specialFields = array('address', 'postal');

    /**
     * all contacts (Addressbook_Model_Contact) needed for the export
     *
     * @var Tinebase_Record_RecordSet
     */
    protected $_contacts = NULL;
    
    /**
     * constructor (adds more values with Crm_Export_Helper)
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Controller_Record_Interface $_controller
     * @param array $_additionalOptions
     * @return void
    */
    public function __construct(Tinebase_Model_Filter_FilterGroup $_filter, Tinebase_Controller_Record_Interface $_controller = NULL, $_additionalOptions = array())
    {
        $this->_resolveAddresses($_filter, $_controller);
        parent::__construct($_filter, $_controller, $_additionalOptions);
    }

    /**
     * get export config
     *
     * @param array $_additionalOptions additional options
     * @return Zend_Config_Xml
     * @throws Tinebase_Exception_NotFound
     */
    protected function _getExportConfig($_additionalOptions = array())
    {
        $config = parent::_getExportConfig($_additionalOptions);
        $count = $config->columns->column->count();

        foreach($this->_specialFieldDefinitions as $def) {
            $cfg = new Zend_Config(array('column' => array($count => $def)));
            $config->columns->merge($cfg);
            $count++;
        }
        
        $i18n = $this->_translate->getAdapter();
        
        // translate header
        foreach($config->columns->column as $index => $column) {
            $newConfig = $column->toArray();
            
            $newConfig['header'] = $i18n->translate($newConfig['header']);
            
            if (isset($newConfig['index']) && $newConfig['index'] > 0) {
                $newConfig['header'] .= ' (' . $newConfig['index'] . ')';
            }
            
            $cfg = new Zend_Config(array('column' => array($index => $newConfig)));
            $config->columns->merge($cfg);
        }

        return $config;
    }

    /**
     * resolve address records before setting headers, so we know how much addresses exist
     *
     * @param Sales_Model_SupplierFilter $filter
     * @param Sales_Controller_Supplier $controller
     */
    protected function _resolveAddresses($filter, $controller)
    {
        $suppliers   = $controller->search($filter);
        $supplierIds = $suppliers->id;
        $contactIds  = array_unique(array_merge($suppliers->cpextern_id, $suppliers->cpintern_id));
        
        unset($suppliers);

        $be = new Sales_Backend_Address();

        $this->_specialFieldDefinitions = array(array('header' => 'Postal Address', 'identifier' => 'postal_address', 'type' => 'postal'));

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Address::class, array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(
            array('field' => 'customer_id', 'operator' => 'in', 'value' => $supplierIds)
        ));
        
        $this->_addresses = $be->search($filter);
        
        $this->_contacts = Addressbook_Controller_Contact::getInstance()->getMultiple($contactIds);
    }

    /**
     * get special field value
     *
     * @param Tinebase_Record_Interface $_record
     * @param array $_param
     * @param string $_key
     * @param string $_cellType
     * @return string
     */
    protected function _getSpecialFieldValue(Tinebase_Record_Interface $_record, $_param, $_key = NULL, &$_cellType = NULL)
    {
        $supplierId = $_record->getId();
        
        if (! isset($this->_supplierAddresses[$supplierId])) {
            $all = $this->_addresses->filter('customer_id', $supplierId);
            $this->_addresses->removeRecords($all);
            $this->_supplierAddresses[$supplierId] = array(
                'postal'  => $all->filter('type', 'postal')->getFirstRecord(),
                'billing' => array('records' => $all->filter('type', 'billing'), 'index' => 0),
                'delivery' => array('records' => $all->filter('type', 'delivery'), 'index' => 0),
            );
        }

        switch ($_param['type']) {
            case 'postal':
                $address = $this->_supplierAddresses[$supplierId]['postal'];
                break;
            default:
                if (isset($this->_supplierAddresses[$supplierId][$_param['type']]['records'])) {
                    $address = $this->_supplierAddresses[$supplierId][$_param['type']]['records']->getByIndex($this->_supplierAddresses[$supplierId][$_param['type']]['index']);
                    $this->_supplierAddresses[$supplierId][$_param['type']]['index']++;
                }
        }

        return $address ? $this->_renderAddress($address, $_param['type']) : '';
    }

    /**
     * renders an address
     *
     * @param Sales_Model_Address $address
     * @param string $type
     */
    protected function _renderAddress($address, $type = NULL) {
        
        if (! $address) {
            return '';
        }
        
        $ret = array();

        foreach(array('prefix1', 'prefix2', 'street', 'postalcode', 'locality', 'region', 'countryname', 'pobox') as $prop) {
            if (isset($address->{$prop})) {
                $ret[] = $address->{$prop};
            }
        }
        
        return join(',', $ret);
    }
}

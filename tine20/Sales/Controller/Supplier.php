<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2015-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * supplier controller class for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Supplier extends Sales_Controller_NumberableAbstract
{
    /**
     * delete or just set is_delete=1 if record is going to be deleted
     * - legacy code -> remove that when all backends/applications are using the history logging
     *
     * @var boolean
     */
    protected $_purgeRecords = FALSE;
    
    /**
     * duplicate check fields / if this is NULL -> no duplicate check
     *
     * @var array
     */
    protected $_duplicateCheckFields = array(array('name'));
    
    protected $_applicationName      = 'Sales';
    protected $_modelName            = 'Sales_Model_Supplier';
    protected $_doContainerACLChecks = FALSE;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName'     => Sales_Model_Supplier::class,
            'tableName'     => Sales_Model_Supplier::TABLE_NAME,
            'modlogActive'  => true
        ));
        $this->_modelName = Sales_Model_Supplier::class;
        $this->_purgeRecords = false;        // TODO this should be done automatically if model has customfields (hasCustomFields)
        $this->_resolveCustomFields = true;
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var Sales_Controller_Supplier
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Sales_Controller_Supplier
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }

    /**
     * validates if the given code is a valid ISO 4217 code
     *
     * @param string $code
     * @throws Sales_Exception_UnknownCurrencyCode
     */
    public static function validateCurrencyCode($code)
    {
        try {
            $currency = new Zend_Currency($code, 'en_GB');
        } catch (Zend_Currency_Exception $e) {
            throw new Sales_Exception_UnknownCurrencyCode();
        }
    }
    
    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $this->resolvePostalAddress($_record);

        parent::_inspectBeforeCreate($_record);

        $this->_setNextNumber($_record);
        self::validateCurrencyCode($_record->currency);
    }
    
    /**
     * inspects delete action
     *
     * @param array $_ids
     * @return array of ids to actually delete
     */
    protected function _inspectDelete(array $_ids)
    {
        // TODO FIXME !!!! what about this?!?

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Address::class, array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'customer_id', 'operator' => 'in', 'value' => $_ids)));
        
        $addressController = Sales_Controller_Address::getInstance();
        $addressController->delete($addressController->search($filter, NULL, FALSE, TRUE));

        return $_ids;
    }

    /**
     * @param Sales_Model_Supplier $_record
     * @param class-string $model
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    protected function resolvePostalAddress(Sales_Model_Supplier $_record, string $model = Sales_Model_Address::class): void
    {
        $postalAddress = [];

        foreach($_record as $field => $value) {
            if (strpos($field, 'adr_') !== FALSE) {
                $postalAddress[substr($field, 4)] = $value;
            }
        }

        //its only for the occasion after resolveVirtualFields
        if (is_object($_record->postal_id)) {
            $postalAddress['seq'] = $_record->postal_id->seq;
            $postalAddress['id'] = $_record->postal_id->getId();
        } elseif ($_record->getId()) {
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($model, array(array('field' => 'type', 'operator' => 'equals', 'value' => 'postal')));
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'supplier_id', 'operator' => 'equals', 'value' => $_record->getId())));

            /** @phpstan-ignore-next-line */
            if ($postalAddressRecord = Tinebase_Core::getApplicationInstance($model)->search($filter)->getFirstRecord()) {
                $postalAddress['id'] = $postalAddressRecord->getId();
                $postalAddress['seq'] = $postalAddressRecord->seq;
            }
        }

        $postalAddress['supplier_id'] = $_record->getId();
        $postalAddress['type'] = 'postal';

        $_record['postal_id'] = new $model($postalAddress);
    }

    /**
     * resolves all virtual fields for the supplier
     *
     * @param array $supplier
     * @return array with property => value
     */
    public function resolveVirtualFields($supplier)
    {
        $mc = Sales_Model_Supplier::getConfiguration();
        if (null === ($supplier['postal_id'] ?? null)) {
            $addressController = Sales_Controller_Address::getInstance();
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Address::class, array(array('field' => 'type', 'operator' => 'equals', 'value' => 'postal')));
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'customer_id', 'operator' => 'equals', 'value' => $supplier['id'])));
            $postalAddressRecord = $addressController->search($filter)->getFirstRecord();
        } else {
            $postalAddressRecord = $supplier['postal_id'];
        }

        if ($postalAddressRecord) {
            if (is_object($postalAddressRecord)) {
                $supplier['postal_id'] = $postalAddressRecord->toArray();
            }
            foreach($supplier['postal_id'] as $field => $value) {
                if (in_array('adr_' . $field, $mc->fieldKeys)) {
                    $supplier[('adr_' . $field)] = $value;
                }
            }
        }
        return $supplier;
    }

    /**
     * @param array $resultSet
     *
     * @return array
     */
    public function resolveMultipleVirtualFields($resultSet)
    {
        foreach($resultSet as &$result) {
            $result = $this->resolveVirtualFields($result);
        }

        return $resultSet;
    }
    
    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     *
     * @todo $_record->contracts should be a Tinebase_Record_RecordSet
     * @todo use getMigration()
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        Sales_Controller_Customer::getInstance()->handleExternAndInternId($_record);
        $this->resolvePostalAddress($_record);

        self::validateCurrencyCode($_record->currency);
        
        if ($_record->number != $_oldRecord->number) {
            $this->_setNextNumber($_record, TRUE);
        }
    }
    
    /**
     * check if user has the right to manage invoices
     *
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight($_action)
    {
        switch ($_action) {
            case 'create':
            case 'update':
            case 'delete':
                if (! Tinebase_Core::getUser()->hasRight('Sales', Sales_Acl_Rights::MANAGE_SUPPLIERS)) {
                    throw new Tinebase_Exception_AccessDenied("You don't have the right to manage suppliers!");
                }
                break;
        }

        parent::_checkRight($_action);
    }
}

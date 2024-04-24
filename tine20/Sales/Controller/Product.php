<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Product controller class for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Product extends Sales_Controller_NumberableAbstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Sales';
    
    protected $_modelName = 'Sales_Model_Product';
    
    protected $_doContainerACLChecks = FALSE;
    
    /**
     * holds the instance of the singleton
     *
     * @var Sales_Controller_Product
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_backend = new Sales_Backend_Product();

        if (Tinebase_Application::getInstance()->isInstalled($this->_applicationName)) {
            $this->_numberPrefix = Sales_Config::getInstance()->get(Sales_Config::PRODUCT_NUMBER_PREFIX);
            $this->_numberZerofill = Sales_Config::getInstance()->get(Sales_Config::PRODUCT_NUMBER_ZEROFILL);
        }
        // TODO this should be done automatically if model has customfields (hasCustomFields)
        $this->_resolveCustomFields = true;
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
     
    /**
     * the singleton pattern
     *
     * @return Sales_Controller_Product
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }

    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   boolean $_duplicateCheck
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function create(Tinebase_Record_Interface $_record, $_duplicateCheck = true)
    {
        if (Sales_Config::getInstance()->get(Sales_Config::PRODUCT_NUMBER_GENERATION)
            === Sales_Config::PRODUCT_NUMBER_GENERATION_AUTO)
        {
            $this->_addNextNumber($_record);
        } else {
            $this->_checkNumberUniqueness($_record, false);
        }
        $this->_checkNumberType($_record);
        
        return parent::create($_record, $_duplicateCheck);
    }
    
    /**
     * Checks if number is unique if manual generated
     *
     * @param Tinebase_Record_Interface $r
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _checkNumberType($record)
    {
        $number = $record->number;
    
        if (empty($number)) {
            throw new Tinebase_Exception_Record_Validation('Please use a product number!');
        }
        
        if ((Sales_Config::getInstance()->get(Sales_Config::PRODUCT_NUMBER_VALIDATION) == 'integer') && (! is_numeric($number))) {
            throw new Tinebase_Exception_Record_Validation('Please use a decimal number as product number!');
        }
    }
    
    /**
     * check if user has the right to manage Products
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
                if (! Tinebase_Core::getUser()->hasRight('Sales', Sales_Acl_Rights::MANAGE_PRODUCTS)) {
                    throw new Tinebase_Exception_AccessDenied("You don't have the right to manage products!");
                }
                break;
            default;
               break;
        }

        parent::_checkRight($_action);
    }

    /**
     * updateProductLifespan (switch products active/inactive)
     *
     * @return bool
     */
    public function updateProductLifespan()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Updating product lifespans...');
        
        $productIdsToChangeToInactive = $this->_getProductIdsForLifespanUpdate(/* $setToActive = */ false);
        $productIdsToChangeToActive   = $this->_getProductIdsForLifespanUpdate(/* $setToActive = */ true);

        if (count($productIdsToChangeToInactive) > 0) {
            $this->_backend->updateMultiple($productIdsToChangeToInactive, array('is_active' => false));
        }
        if (count($productIdsToChangeToActive) > 0) {
            $this->_backend->updateMultiple($productIdsToChangeToActive, array('is_active' => true));
        }

        return true;
    }
    
    /**
     * helper function for updateProductLifespan
     * 
     * @param boolean $setToActive
     * return array of product ids
     */
    protected function _getProductIdsForLifespanUpdate($setToActive = true)
    {
        $now = Tinebase_DateTime::now();
        
        if ($setToActive) {
            // find all products that should be set to active
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Product::class, array(array(
                'field'    => 'is_active',
                'operator' => 'equals',
                'value'    => false
            ), array('condition' => 'OR', 'filters' => array(array(
                'field'    => 'lifespan_start',
                'operator' => 'before',
                'value'    => $now
            ), array(
                'field'    => 'lifespan_start',
                'operator' => 'isnull',
                'value'    => null
            ))), array('condition' => 'OR', 'filters' => array(array(
                'field'    => 'lifespan_end',
                'operator' => 'after',
                'value'    => $now
            ), array(
                'field'    => 'lifespan_end',
                'operator' => 'isnull',
                'value'    => null
            )))));
            
        } else {
            // find all products that should be set to inactive
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Product::class, array(array(
                'field'    => 'is_active',
                'operator' => 'equals',
                'value'    => true
            ), array('condition' => 'OR', 'filters' => array(array(
                'field'    => 'lifespan_start',
                'operator' => 'after',
                'value'    => $now
            ), array(
                'field'    => 'lifespan_end',
                'operator' => 'before',
                'value'    => $now
            )
            ))));
        }
        
        $productIdsToChange = $this->_backend->search($filter, null, Tinebase_Backend_Sql_Abstract::IDCOL);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Found ' . count($productIdsToChange) . ' to change to ' . ($setToActive ? 'active' : 'inactive'));
        
        return $productIdsToChange;
    }
}

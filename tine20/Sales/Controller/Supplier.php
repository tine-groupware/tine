<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2015-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;
use Tinebase_Model_Filter_Abstract as TMFA;

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
        parent::_inspectBeforeCreate($_record);

        $this->_setNextNumber($_record);
        self::validateCurrencyCode($_record->currency);
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

        self::validateCurrencyCode($_record->currency);
        
        if ($_record->number != $_oldRecord->number) {
            $this->_setNextNumber($_record, TRUE);
        }
    }

    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        parent::_inspectAfterUpdate($updatedRecord, $record, $currentRecord);

        $diff = $currentRecord->diff($updatedRecord, TMCC::$modLogProperties);
        if (!$diff->isEmpty()) {
            $fun = function(Tinebase_Model_Filter_FilterGroup $filter) {
                $orFilter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($filter->getModelName(), _condition: Tinebase_Model_Filter_FilterGroup::CONDITION_OR);
                $filter->addFilterGroup($orFilter);
                $orFilter->addFilterGroup(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel($filter->getModelName(), [
                        [TMFA::FIELD => Sales_Model_Document_Address::FLD_DOCUMENT_TYPE, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_Document_PurchaseInvoice::class],
                        [TMFA::FIELD => Sales_Model_Document_Address::FLD_DOCUMENT_ID, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                            [TMFA::FIELD => Sales_Model_Document_PurchaseInvoice::FLD_PURCHASE_INVOICE_STATUS, TMFA::OPERATOR => 'in', TMFA::VALUE => Sales_Config::getInstance()->{Sales_Config::DOCUMENT_PURCHASE_INVOICE_STATUS}->records->filter(Sales_Model_Document_Status::FLD_BOOKED, false)->id],
                        ]],
                    ], _options: [
                        TMCC::REF_MODEL_FIELD => Sales_Model_Document_Supplier::FLD_DOCUMENT_TYPE,
                        Tinebase_Model_Filter_ForeignIdDynamic::REF_MODEL_VALUE => Sales_Model_Document_PurchaseInvoice::class,
                    ])
                );
            };
            static::propagateUpdatesToDenormalizedRecords($diff, [Sales_Model_Document_Supplier::class => ['filterCallBack' => $fun]]);
            Tinebase_Event::fireEvent(new Tinebase_Event_Record_Update(['observable' => $updatedRecord, 'oldRecord' => $currentRecord]));
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

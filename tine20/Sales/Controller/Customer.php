<?php
/**
 * customer controller for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Sales_Model_Customer as SMC;
use Sales_Model_Debitor as SMDN;

/**
 * customer controller class for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Customer extends Tinebase_Controller_Record_Abstract
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

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = Sales_Config::APP_NAME;
        $this->_backend = new Sales_Backend_Customer();
        $this->_modelName = Sales_Model_Customer::class;
        // TODO this should be done automatically if model has customfields (hasCustomFields)
        $this->_resolveCustomFields = true;
    }

    /**
     * holds the instance of the singleton
     *
     * @var Sales_Controller_Customer
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Sales_Controller_Customer
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
     * @param Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        self::validateCurrencyCode($_record->currency);
    }

    /**
     * inspect creation of one record (after setReleatedData)
     *
     * @param Tinebase_Record_Interface $createdRecord the just updated record
     * @param Tinebase_Record_Interface $record the update record
     * @return  void
     */
    protected function _inspectAfterSetRelatedDataCreate($createdRecord, $record)
    {
        parent::_inspectAfterSetRelatedDataCreate($createdRecord, $record);
        $this->_resolveBillingAddress($createdRecord);
    }

    /**
     * inspect update of one record (before update)
     *
     * @param Tinebase_Record_Interface $_record the update record
     * @param Tinebase_Record_Interface $_oldRecord the current persistent record
     * @return  void
     *
     * @todo $_record->contracts should be a Tinebase_Record_RecordSet
     * @todo use getMigration()
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        $this->handleExternAndInternId($_record);

        self::validateCurrencyCode($_record->currency);
    }

    /**
     * inspect creation of one record (after create)
     *
     * @param Tinebase_Record_Interface $_createdRecord
     * @param Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        $rel = $_record->relations?->filter('type', 'CONTACTCUSTOMER')->getFirstRecord();
        if ($rel) {
            $contact = Addressbook_Controller_Contact::getInstance()->get($rel->related_id);
            $_record->postal->customer_id = $_createdRecord->getId();
            $_record->postal->setFromContact($contact);
        }


        // create default  if missing
        if (!$_record->{SMC::FLD_DEBITORS}) {
            $_record->{SMC::FLD_DEBITORS} = new Tinebase_Record_RecordSet(Sales_Model_Debitor::class);
        }
        if ($_record->{SMC::FLD_DEBITORS}->count() > 0) {
            return;
        }
        $_record->{SMC::FLD_DEBITORS}->addRecord(new Sales_Model_Debitor([
            Sales_Model_Debitor::FLD_CUSTOMER_ID => $_record->getId(),
            Sales_Model_Debitor::FLD_DIVISION_ID => Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION}
        ]));
    }

    /**
     * inspect update of one record (after update)
     *
     * @param Tinebase_Record_Interface $updatedRecord
     * @param Tinebase_Record_Interface $record
     * @param Tinebase_Record_Interface $currentRecord
     * @return  void
     */
    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        $this->handleExternAndInternId($record);
    }

    /**d
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
                if (!Tinebase_Core::getUser()->hasRight('Sales', Sales_Acl_Rights::MANAGE_CUSTOMERS)) {
                    throw new Tinebase_Exception_AccessDenied("You don't have the right to manage customers!");
                }
                break;
            default;
                break;
        }

        parent::_checkRight($_action);
    }

    /**
     * handleExternAndInternId
     *
     * @param Tinebase_Record_Interface $_record the record
     * @return  Tinebase_Record_Interface
     *
     */
    public function handleExternAndInternId($_record)
    {
        //its only for the occasion after resolveVirtualFields
        foreach (array('cpextern_id', 'cpintern_id') as $prop) {
            if (isset($_record[$prop]) && is_array($_record[$prop])) {
                $_record[$prop] = $_record[$prop]['id'];
            }
        }

        return $_record;
    }

    /**
     * create postal address record after creat customer
     *
     * - create billing address after postal address created
     * - billing address equal to postal address
     *
     * @param Tinebase_Record_Interface $_record
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     * @throws Tinebase_Exception_NotFound
     */
    protected function _resolveBillingAddress($_record)
    {
        $postalAddressRecord = null;
        foreach ($_record->{SMC::FLD_DEBITORS} ?? [] as $debitor) {
            if ($debitor->{SMDN::FLD_BILLING}?->count() > 0) {
                continue;
            }
            if (null === $postalAddressRecord) {
                $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Address::class, array(array('field' => 'type', 'operator' => 'equals', 'value' => 'postal')));
                $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'customer_id', 'operator' => 'equals', 'value' => $_record['id'])));

                $postalAddressRecord = Sales_Controller_Address::getInstance()->search($filter, null, true)->getFirstRecord();
            }

            if (null === $postalAddressRecord) {
                return;
            }

            $billingAddress = $postalAddressRecord->getData();
            unset($billingAddress['id']);
            unset($billingAddress[Sales_Model_Address::FLD_CUSTOMER_ID]);
            $billingAddress['type'] = Sales_Model_Address::TYPE_BILLING;
            $billingAddress[Sales_Model_Address::FLD_DEBITOR_ID] = $debitor->getId();

            $rel = $_record->relations->filter('type', 'CONTACTCUSTOMER')->getFirstRecord();
            if ($rel) {
                $billingAddress['relations'] = [array_merge($rel->getData(), [
                    'id' => null,
                    'type' => 'CONTACTADDRESS',
                    'own_id' => null,
                    'own_model' => 'Sales_Model_Address',
                ])];
            }

            $address = Sales_Controller_Address::getInstance()->create(new Sales_Model_Address($billingAddress));
            $debitor->{SMDN::FLD_BILLING}?->addRecord($address);
        }
    }
}

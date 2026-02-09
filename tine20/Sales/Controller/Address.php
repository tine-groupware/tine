<?php
/**
 * address controller for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * address controller class for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Address extends Tinebase_Controller_Record_Abstract
{
    /**
     * delete or just set is_delete=1 if record is going to be deleted
     * - legacy code -> remove that when all backends/applications are using the history logging
     *
     * @var boolean
     */
    protected $_purgeRecords = FALSE;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_applicationName = 'Sales';
        $this->_backend = new Sales_Backend_Address();
        $this->_modelName = 'Sales_Model_Address';
        $this->_doContainerACLChecks = FALSE;
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var Sales_Controller_Address
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Sales_Controller_Address
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    /**
     * resolves all virtual fields for the address
     *
     * @param array $address
     * @return array with property => value
     */
    public function resolveVirtualFields($address)
    {
        if (! isset($address['type'])) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' Invalid address for resolving: ' . print_r($address, true));
            
            return $address;
        }
        
        $ft = '';
        
        $i18n = Tinebase_Translation::getTranslation($this->_applicationName)->getAdapter();
        $type = $address['type'];
        
        $ft .= !empty($address['name_shorthand']) ? $address['name_shorthand'] : '';
        $ft .= !empty($address['name_shorthand']) ? ' => ' : '';
        $ft .= !empty($address['name']) ? $address['name'] : '';
        $ft .= !empty($address['name']) ? ' ' : '';
        $ft .= !empty($address['email']) ? $address['email'] : '';
        $ft .= !empty($address['email']) ? ' ' : '';
        $ft .= !empty($address['prefix1']) ? $address['prefix1'] : '';
        $ft .= !empty($address['prefix1']) && !empty($address['prefix2']) ? ' ' : '';
        $ft .= !empty($address['prefix2']) ? $address['prefix2'] : '';
        $ft .= !empty($address['prefix1']) || !empty($address['prefix2']) ? ', ' : '';
        
        $ft .= !empty($address['postbox']) ? $address['postbox'] : (!empty($address['street']) ? $address['street'] : '');
        $ft .= !empty($address['postbox']) || !empty($address['street']) ? ', ' : '';
        $ft .= !empty($address['postalcode']) ? $address['postalcode'] . ' ' : '';
        $ft .= !empty($address['locality']) ? $address['locality'] : '';
        $ft .= ' (';
        
        $ft .= $i18n->_($type);
        
        $ft .= ')';
        
        $address['fulltext'] = $ft;
        
        return $address;
    }
    
    /**
     * @todo make this better, faster
     *
     * @param array $resultSet
     *
     * @return array
     */
    public function resolveMultipleVirtualFields($resultSet)
    {
        foreach ($resultSet as &$result) {
            $result = $this->resolveVirtualFields($result);
        }
        
        return $resultSet;
    }
    
    /**
     * inspects delete action
     *
     * @param array $_ids
     * @return array of ids to actually delete
     * @throws Sales_Exception_DeleteUsedBillingAddress
     */
    protected function _inspectDelete(array $_ids)
    {
        $cc = Sales_Controller_Contract::getInstance();

        $filter = new Sales_Model_ContractFilter(array(array('field' => 'billing_address_id', 'operator' => 'in', 'value' => $_ids)));

        $contracts = $cc->search($filter);
    
        if ($contracts->count()) {
            $e = new Sales_Exception_DeleteUsedBillingAddress();
            $e->setContracts($contracts);
    
            throw $e;
        }
    
        return $_ids;
    }

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        /** @var Sales_Model_Address $_record */

        parent::_inspectBeforeCreate($_record);
        $this->_validateParentAndType($_record);
        $rel = $_record->relations?->filter('type', 'CONTACTADDRESS')->getFirstRecord();
        if ($rel) {
            $contact = Addressbook_Controller_Contact::getInstance()->get($rel->related_id);
            $_record->setFromContact( $contact );
        }
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);
        $this->_validateParentAndType($_record);

        //Do not update Address Records with a relation to a contact from type CONTACTADDRESS
        $relations = $_record->relations;

        if (!$relations) {
            return;
        }
        foreach ($relations as $relation) {
            if ($relation['type'] == 'CONTACTADDRESS') {
                throw new Tinebase_Exception_AccessDenied('It is not allowed to change an address that is linked to a contact. Please update the contact instead.');
            }
        }
    }

    protected function _validateParentAndType(Sales_Model_Address $address): void
    {
        if (!$address->{Sales_Model_Address::FLD_CUSTOMER_ID} && !$address->{Sales_Model_Address::FLD_DEBITOR_ID}) {
            throw new Tinebase_Exception_Record_Validation('either ' . Sales_Model_Address::FLD_CUSTOMER_ID . ' or '
                . Sales_Model_Address::FLD_DEBITOR_ID . ' need to be set');
        }
        if ($address->{Sales_Model_Address::FLD_CUSTOMER_ID} && $address->{Sales_Model_Address::FLD_DEBITOR_ID}) {
            throw new Tinebase_Exception_Record_Validation('only one of ' . Sales_Model_Address::FLD_CUSTOMER_ID
                . ' and ' . Sales_Model_Address::FLD_DEBITOR_ID . ' must be set');
        }
        if ($address->{Sales_Model_Address::FLD_CUSTOMER_ID} && $address->{Sales_Model_Address::FLD_TYPE} &&
                Sales_Model_Address::TYPE_POSTAL !== $address->{Sales_Model_Address::FLD_TYPE}) {
            throw new Tinebase_Exception_Record_Validation('if ' . Sales_Model_Address::FLD_CUSTOMER_ID . ' is set '
                . Sales_Model_Address::FLD_TYPE . ' needs to be ' . Sales_Model_Address::TYPE_POSTAL);
        }
    }


    /**
     * handle address
     * save properties in record
     * @param Tinebase_Record_Interface $_record the record
     * @return  Tinebase_Record_Interface
     *
     */
    public function resolvePostalAddress($_record) {
        $postalAddress = [];
        
        foreach( $_record as $field => $value) {
            if (strpos($field, 'adr_') !== FALSE) {
                $postalAddress[substr($field, 4)] = $value;
            }
        }

        //its only for the occasion after resolveVirtualFields
        if (!isset($postalAddress['seq']) && isset($_record['postal_id']) && isset($_record['postal_id']['seq'])) {
            $postalAddress['seq'] = $_record['postal_id']['seq'];
        }

        $postalAddress['customer_id'] = isset($_record['id']) ? $_record['id'] : $_record['name'];
        $postalAddress['type'] = 'postal';

        $_record['postal_id'] = $postalAddress;

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Address::class, array(array('field' => 'type', 'operator' => 'equals', 'value' => 'postal')));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'customer_id', 'operator' => 'equals', 'value' => $_record['id'])));

        $postalAddressRecord = Sales_Controller_Address::getInstance()->search($filter)->getFirstRecord();

        // create if none has been found
        if (! $postalAddressRecord) {
            $postalAddressRecord = Sales_Controller_Address::getInstance()->create(new Sales_Model_Address($_record['postal_id']));
        } else {
            // update if it has changed
            $recordData = $_record->toArray();
            foreach ($postalAddressRecord as $field => $value) {
                if (array_key_exists("adr_$field", $recordData)) {
                    $postalAddressRecord[$field] = $recordData["adr_$field"];
                }
            }

            $postalAddressRecord = Sales_Controller_Address::getInstance()->update($postalAddressRecord);
        }
        
        return $_record;
    }

    /**
     * Update a Sales Address with data from a Contact
     * 
     * @param Sales_Model_Address $address
     * @param Addressbook_Model_Contact $contact
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     */
    public function contactToCustomerAddress(Sales_Model_Address $address, Addressbook_Model_Contact $contact): Sales_Model_Address
    {
        //Update Address
        $oldAddress = clone $address;
        $address->setFromContact( $contact );

        /** @var Tinebase_Model_Diff $diff */
        $diff = $oldAddress->diff($address);
        if (!$diff->isEmpty()) {
            $address = Sales_Controller_Address::getInstance()->update($address);
        }
        return $address;
    }

    /**
     * @param Addressbook_Model_Contact $contact
     * @return string
     */
    public function getContactFullName(Addressbook_Model_Contact $contact, $language = 'en'): string
    {
        $fullName = $contact->n_given . ' ' . $contact->n_family;
        if ($contact->n_prefix) {
            $fullName = $contact->n_prefix . ' ' . $fullName;
        }
        if ($contact->salutation) {
            $locale = new Zend_Locale($language);
            $translation = Tinebase_Translation::getTranslation('Addressbook', $locale);
            $salutations = Addressbook_Config::getInstance()->get(Addressbook_Config::CONTACT_SALUTATION, NULL);
            if ($salutations && $salutations->records instanceof Tinebase_Record_RecordSet) {
                $salutationRecord = $salutations->records->getById($contact->salutation);
                if ($salutationRecord) {
                    $fullName = $translation->_($salutationRecord->value) . ' ' . $fullName;
                }
            }
        }
        return $fullName;
    }
}

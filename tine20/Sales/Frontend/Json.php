<?php
/**
 * Tine 2.0
 * @package     Sales
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 *
 * This class handles all Json requests for the Sales application
 *
 * @package     Sales
 * @subpackage  Frontend
 */
class Sales_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * @see Tinebase_Frontend_Json_Abstract
     * 
     * @var string
     */
    protected $_applicationName = 'Sales';

    /**
     * @see Tinebase_Frontend_Json_Abstract
     */
    protected $_relatableModels = array(
        'Sales_Model_Contract',
        'Sales_Model_Customer',
        'Sales_Model_Address',
        'Sales_Model_ProductAggregate',
    );

    /**
     * All configured models
     *
     * @var array
     */
    protected $_configuredModels = array(
        Sales_Model_Debitor::MODEL_NAME_PART,
        Sales_Model_Division::MODEL_NAME_PART,
        Sales_Model_DivisionBankAccount::MODEL_NAME_PART,
        Sales_Model_DivisionEvalDimensionItem::MODEL_NAME_PART,
        Sales_Model_DivisionGrants::MODEL_NAME_PART,
        Sales_Model_DocumentPosition_Credit::MODEL_NAME_PART,
        Sales_Model_DocumentPosition_Delivery::MODEL_NAME_PART,
        Sales_Model_DocumentPosition_Invoice::MODEL_NAME_PART,
        Sales_Model_DocumentPosition_Offer::MODEL_NAME_PART,
        Sales_Model_DocumentPosition_Order::MODEL_NAME_PART,
        Sales_Model_Document_Address::MODEL_NAME_PART,
        Sales_Model_Document_AttachedDocument::MODEL_NAME_PART,
        Sales_Model_Document_Boilerplate::MODEL_NAME_PART,
        Sales_Model_Document_Category::MODEL_NAME_PART,
        Sales_Model_Document_Credit::MODEL_NAME_PART,
        Sales_Model_Document_Customer::MODEL_NAME_PART,
        Sales_Model_Document_Debitor::MODEL_NAME_PART,
        Sales_Model_Document_Delivery::MODEL_NAME_PART,
        Sales_Model_Document_DispatchHistory::MODEL_NAME_PART,
        Sales_Model_Document_Invoice::MODEL_NAME_PART,
        Sales_Model_Document_Offer::MODEL_NAME_PART,
        Sales_Model_Document_Order::MODEL_NAME_PART,
        Sales_Model_Document_PaymentReminder::MODEL_NAME_PART,
        Sales_Model_Document_SalesTax::MODEL_NAME_PART,
        Sales_Model_Document_Supplier::MODEL_NAME_PART,
        Sales_Model_EDocument_Dispatch_Custom::MODEL_NAME_PART,
        Sales_Model_EDocument_Dispatch_DocumentType::MODEL_NAME_PART,
        Sales_Model_EDocument_Dispatch_DynamicConfig::MODEL_NAME_PART,
        Sales_Model_EDocument_Dispatch_Email::MODEL_NAME_PART,
        Sales_Model_EDocument_Dispatch_Manual::MODEL_NAME_PART,
        Sales_Model_EDocument_Dispatch_Upload::MODEL_NAME_PART,
        Sales_Model_EDocument_EAS::MODEL_NAME_PART,
        Sales_Model_EDocument_PMC_NoConfig::MODEL_NAME_PART,
        Sales_Model_EDocument_PMC_PayeeFinancialAccount::MODEL_NAME_PART,
        Sales_Model_EDocument_PMC_PaymentMandate::MODEL_NAME_PART,
        Sales_Model_EDocument_PaymentMeansCode::MODEL_NAME_PART,
        Sales_Model_EDocument_UnitOfMeasure::MODEL_NAME_PART,
        Sales_Model_EDocument_UnitOfMeasureLocalization::MODEL_NAME_PART,
        Sales_Model_EDocument_XRechnungElement::MODEL_NAME_PART,
        Sales_Model_Einvoice_XRechnung::MODEL_NAME_PART,
        Sales_Model_EDocument_XRechnungElement::MODEL_NAME_PART,
        Sales_Model_Einvoice_XRechnungOverwrite::MODEL_NAME_PART,
        Sales_Model_PaymentMeans::MODEL_NAME_PART,
        Sales_Model_Product::MODEL_NAME_PART,
        Sales_Model_ProductLocalization::MODEL_NAME_PART,
        Sales_Model_SubProductMapping::MODEL_NAME_PART,
//        'OrderConfirmation',
//        'PurchaseInvoice',
//        'Offer',
//        'Supplier',
        'Contract',
        'Customer',
        'Address',
        'ProductAggregate',
        'Boilerplate',
        'Invoice',
    );

    /**
     * the constructor
     */
    public function __construct()
    {
        if (Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_INVOICES_MODULE)) {
            $this->_relatableModels[]  = 'Sales_Model_Invoice';
            $this->_configuredModels[] = 'InvoicePosition';
            $this->_configuredModels[] = 'Invoice';
        } else if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Invoices module disabled');
        }
        if (Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_OFFERS_MODULE)) {
            if (Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_LEGACY_OFFERS)) {
                $this->_relatableModels[] = Sales_Model_Offer::class;
                $this->_configuredModels[] = 'Offer';
            } else {
                $this->_relatableModels[] = Sales_Model_Document_Offer::class;
                $this->_configuredModels[] = Sales_Model_Document_Offer::MODEL_NAME_PART;
            }
        }
        if (Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_SUPPLIERS_MODULE)) {
            $this->_relatableModels[]  = 'Sales_Model_Supplier';
            $this->_configuredModels[] = 'Supplier';
        }
        if (Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_PURCHASE_INVOICES_MODULE)) {
            $this->_relatableModels[]  = 'Sales_Model_PurchaseInvoice';
            $this->_configuredModels[] = 'PurchaseInvoice';
            $this->_configuredModels[] = Sales_Model_PurchasePaymentMeans::MODEL_NAME_PART;
            $this->_configuredModels[] = Sales_Model_DocumentPosition_PurchaseInvoice::MODEL_NAME_PART;
            $this->_configuredModels[] = Sales_Model_Document_PurchaseInvoice::MODEL_NAME_PART;
        }
        if (Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_ORDERCONFIRMATIONS_MODULE)) {
            $this->_relatableModels[]  = 'Sales_Model_OrderConfirmation';
            $this->_configuredModels[] = 'OrderConfirmation';
        }
    }

    /**
     * Returns registry data of the application.
     *
     * Each application has its own registry to supply static data to the client.
     * Registry data is queried only once per session from the client.
     *
     * This registry must not be used for rights or ACL purposes. Use the generic
     * rights and ACL mechanisms instead!
     *
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        $sharedContainer = Sales_Controller_Contract::getSharedContractsContainer();
        $sharedContainer->resolveGrantsAndPath();
        return array(
            'defaultContractContainer' => $sharedContainer->toArray(),
        );
    }

    /**
     * Sets the config for Sales
     * @param array $config
     */
    public function setConfig($config)
    {
        return Sales_Controller::getInstance()->setConfig($config);
    }

    /**
     * Get Config for Sales
     * @return array
     */
    public function getConfig()
    {
        return Sales_Controller::getInstance()->getConfig();
    }
    
    /*************************** contracts functions *****************************/


    /**
     * rebills an invoice
     *
     * @param string $id
     * @param string $date
     */
    public function billContract($id, $date)
    {
        $contract = Sales_Controller_Contract::getInstance()->get($id);
        
        $date = new Tinebase_DateTime($date, 'UTC');
    
        return Sales_Controller_Invoice::getInstance()->createAutoInvoices($date, $contract);
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchContracts($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_Contract::getInstance(), 'Sales_Model_ContractFilter',
            /* $_getRelations */ array('Sales_Model_Customer', 'Addressbook_Model_Contact', Tinebase_Model_EvaluationDimensionItem::class,));
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getContract($id)
    {
        $contract = $this->_get($id, Sales_Controller_Contract::getInstance());
        if (! empty($contract['billing_address_id'])) {
            $contract['billing_address_id'] = Sales_Controller_Address::getInstance()->resolveVirtualFields($contract['billing_address_id']);
        }
        // TODO: resolve this in controller
        if (! empty($contract['products']) && is_array($contract['products'])) {
            $prds = Sales_Controller_Product::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Product::class, array()));
            for ($i = 0; $i < count($contract['products']); $i++) {
                $prd = $prds->filter('id', $contract['products'][$i]['product_id'])->getFirstRecord();
                if ($prd) {
                    $contract['products'][$i]['product_id'] = $prd->toArray();
                }
                if (Tinebase_Application::getInstance()->isInstalled('WebAccounting')) {
                    if (isset($contract['products'][$i]['json_attributes']['assignedAccountables'])) {
                        $contract['products'][$i]['json_attributes']['assignedAccountables'] =
                            $this->_resolveAssignedAccountables(
                                $contract['products'][$i]['json_attributes']['assignedAccountables']);
                    }
                }
            }
        }
        
        return $contract;
    }

    /**
     * @param array $assignedAccountables
     * @return array
     *
     * TODO support other models + make this generic
     */
    //TODO: remove?
    protected function _resolveAssignedAccountables(&$assignedAccountables)
    {
        $assignedAccountableIds = [];
        foreach ($assignedAccountables as $accountable) {
            $assignedAccountableIds[] = $accountable['id'];
        }
        if (count($assignedAccountableIds) > 0
            && Tinebase_Application::getInstance()->isInstalled('WebAccounting', true)
            && class_exists('WebAccounting_Controller_ProxmoxVM')
        ) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' resolving accountables: '
                . print_r($assignedAccountableIds, true));
            $model = 'WebAccounting_Model_ProxmoxVM';
            $accountables = WebAccounting_Controller_ProxmoxVM::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel($model, [
                    ['field' => 'id', 'operator' => 'in', 'value' => $assignedAccountableIds]
                ]));
            foreach ($assignedAccountables as $key => $accountableArray) {
                $accountable = $accountables->getById($accountableArray['id']);
                if ($accountable) {
                    $assignedAccountables[$key]['id'] = $accountable->toArray();
                }
            }
        }
        return $assignedAccountables;
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     * 
     * @todo remove billing_address_id sanitizing (@see 0009906: generic solution for sanitizing ids by extracting id value from array)
     */
    public function saveContract($recordData)
    {
        if (isset($recordData['billing_address_id']) && is_array($recordData['billing_address_id'])) {
            $recordData['billing_address_id'] = $recordData['billing_address_id']['id'];
        }
        
        return $this->_save($recordData, Sales_Controller_Contract::getInstance(), 'Contract');
    }

    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteContracts($ids)
    {
        return $this->_delete($ids, Sales_Controller_Contract::getInstance());
    }

    /*************************** procuct aggregate functions *************************/

    public function searchProductAggregates($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_ProductAggregate::getInstance(), 'Sales_Model_ProductAggregateFilter');
    }

    // customer methods

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchCustomers($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Sales_Controller_Customer::getInstance(), 'Sales_Model_CustomerFilter');
        return $result;
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getCustomer($id)
    {
        return $this->_get($id, Sales_Controller_Customer::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @param  boolean $duplicateCheck
     *
     * @return array created/updated record
     * 
     * @todo move code to customer controller inspect functions
     */
    public function saveCustomer($recordData, $duplicateCheck = TRUE)
    {
        $ret = $this->_save($recordData, Sales_Controller_Customer::getInstance(), 'Customer', 'id', array($duplicateCheck));
        return $this->getCustomer($ret['id']);
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteCustomers($ids)
    {
        return $this->_delete($ids, Sales_Controller_Customer::getInstance());
    }
    
    
    /*************************** supplier functions *****************************/

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchSuppliers($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Sales_Controller_Supplier::getInstance(), 'Sales_Model_SupplierFilter');
        
        for ($i = 0; $i < count($result['results']); $i++) {
            if (isset($result['results'][$i]['postal_id'])) {
                $result['results'][$i]['postal_id'] = Sales_Controller_Address::getInstance()->resolveVirtualFields($result['results'][$i]['postal_id']);
            }
        }
        
        return $result;
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getSupplier($id)
    {
        return $this->_get($id, Sales_Controller_Supplier::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @param  boolean $duplicateCheck
     *
     * @return array created/updated record
     */
    public function saveSupplier($recordData, $duplicateCheck = TRUE)
    {
        $ret = $this->_save($recordData, Sales_Controller_Supplier::getInstance(), 'Sales_Model_Supplier', 'id', array($duplicateCheck));
        return $this->getSupplier($ret['id']);
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteSuppliers($ids)
    {
        return $this->_delete($ids, Sales_Controller_Supplier::getInstance());
    }
    
    /*************************** order confirmation functions *****************************/
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchOrderConfirmations($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_OrderConfirmation::getInstance(), 'Sales_Model_OrderConfirmationFilter', array('Sales_Model_Contract'));
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getOrderConfirmation($id)
    {
        return $this->_get($id, Sales_Controller_OrderConfirmation::getInstance());
    }
    
    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @param  boolean $duplicateCheck
     *
     * @return array created/updated record
     */
    public function saveOrderConfirmation($recordData, $duplicateCheck)
    {
        return $this->_save($recordData, Sales_Controller_OrderConfirmation::getInstance(), 'OrderConfirmation');
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteOrderConfirmations($ids)
    {
        return $this->_delete($ids, Sales_Controller_OrderConfirmation::getInstance());
    }
    
    // invoice methods
    
    /**
     * rebills an invoice
     * 
     * @param string $id
     */
    public function rebillInvoice($id)
    {
        $invoice = Sales_Controller_Invoice::getInstance()->get($id);
        $relation = Tinebase_Relations::getInstance()->getRelations('Sales_Model_Invoice', 'Sql', $id, 'sibling', array('CONTRACT'), 'Sales_Model_Contract')->getFirstRecord();
        $contract = Sales_Controller_Contract::getInstance()->get($relation->related_id);
        
        $date = clone $invoice->creation_time;
        
        Sales_Controller_Invoice::getInstance()->delete(array($id));
        
        return Sales_Controller_Invoice::getInstance()->createAutoInvoices($date, $contract);
    }
    
    /**
     * merge an invoice
     *
     * @param string $id
     */
    public function mergeInvoice($id)
    {
        $invoice = Sales_Controller_Invoice::getInstance()->get($id);
        $relation = Tinebase_Relations::getInstance()->getRelations('Sales_Model_Invoice', 'Sql', $id, 'sibling', array('CONTRACT'), 'Sales_Model_Contract')->getFirstRecord();
        $contract = Sales_Controller_Contract::getInstance()->get($relation->related_id);
    
        $date = clone $invoice->creation_time;
    
        Sales_Controller_Invoice::getInstance()->delete(array($id));
    
        return Sales_Controller_Invoice::getInstance()->createAutoInvoices($date, $contract, true);
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchInvoices($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_Invoice::getInstance(), 'Sales_Model_InvoiceFilter', array('Sales_Model_Customer', 'Sales_Model_Contract'));
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getInvoice($id)
    {
        $invoice =  $this->_get($id, Sales_Controller_Invoice::getInstance());
        $json = new Tinebase_Convert_Json();
        $resolvedProducts = new Tinebase_Record_RecordSet('Sales_Model_Product');
        $productController = Sales_Controller_Product::getInstance();
        
        foreach ($invoice['relations'] as &$relation) {
            if ($relation['related_model'] == "Sales_Model_ProductAggregate") {
                if (! $product = $resolvedProducts->getById($relation['related_record']['product_id'])) {
                    $product = $productController->get($relation['related_record']['product_id']);
                    $resolvedProducts->addRecord($product);
                }
                $relation['related_record']['product_id'] = $json->fromTine20Model($product);
            }
        }

//        // limit invoice positions to 500 to make sure browser storage quota is not exceeded
//        if (is_array($invoice['positions']) && count($invoice['positions']) > 500) {
//            // TODO add paging
//            $invoice['positions'] = array_slice($invoice['positions'], 0, 499);
//        }
        
        return $invoice;
    }
    
    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @param  boolean $duplicateCheck
     *
     * @return array created/updated record
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function saveInvoice($recordData, $duplicateCheck = TRUE)
    {
        // this may take longer
        $this->_longRunningRequest(60*60); // set to 1 hour

        // validate customer
        $foundCustomer = FALSE;
        $customerCalculated = FALSE;
        
        if (isset($recordData['relations']) && is_array($recordData['relations'])) {
            foreach($recordData['relations'] as $relation) {
                if ($relation['related_model'] == 'Sales_Model_Customer' && isset($relation['related_id'])) {
                    $foundCustomer = $relation['related_id'];
                    break;
                }
            }
        }
        // if no customer is set, try to find by contract
        if (isset($recordData['relations']) && is_array($recordData['relations']) && ! $foundCustomer) {
            foreach($recordData['relations'] as $relation) {
                if ($relation['related_model'] == 'Sales_Model_Contract') {
                    $foundContractRecord = Sales_Controller_Contract::getInstance()->get($relation['related_id']);
                    foreach($foundContractRecord->relations as $relation) {
                        if ($relation['related_model'] == 'Sales_Model_Customer') {
                            $foundCustomer = $relation['related_id'];
                            $customerCalculated = TRUE;
                            break 2;
                        }
                    }
                }
            }
        }
        
        if ($customerCalculated) {
            $recordData['relations'] = array_merge($recordData['relations'], array(array(
                "own_model"              => "Sales_Model_Invoice",
                "own_backend"            => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                'related_model'          => 'Sales_Model_Customer',
                'related_backend'        => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                'related_id'             => $foundCustomer,
                'type'                   => 'CUSTOMER'
            )));
        }
        
        if (! $foundCustomer) {
            $translation = Tinebase_Translation::getTranslation('Sales');
            throw new Tinebase_Exception_SystemGeneric($translation->_('You have to set a customer!'));
        }
        
        if (isset($recordData['address_id']) && is_array($recordData["address_id"])) {
            $recordData["address_id"] = $recordData["address_id"]['id'];
        }
        if (isset($recordData['eval_dim_cost_center']) && is_array($recordData["eval_dim_cost_center"])) {
            $recordData["eval_dim_cost_center"] = $recordData["eval_dim_cost_center"]['id'];
        }
        // sanitize product_id
        if (isset($recordData['positions']) && is_array($recordData['positions'])) {
            for ($i = 0; $i < count($recordData['positions']); $i++) {
                if (isset($recordData['positions'][$i]['product_id']) && is_array($recordData['positions'][$i]['product_id'])) {
                    $recordData['positions'][$i]['product_id'] = $recordData['positions'][$i]['product_id']['id'];
                }
            }
        }

        /*
        if (isset($recordData['relations']) && is_array($recordData['relations'])) {
            for ($i = 0; $i < count($recordData['relations']); $i++) {
                if (isset($recordData['relations'][$i]['related_record']['product_id'])) {
        
                    if (is_array($recordData['relations'][$i]['related_record']['product_id'])) {
                        $recordData['relations'][$i]['related_record']['product_id'] = $recordData['relations'][$i]['related_record']['product_id']['id'];
                    }
                } elseif ($recordData['relations'][$i]['related_model'] == 'Sales_Model_Invoice') {
                    if (is_array($recordData['relations'][$i]['related_record']['address_id'])) {
                        $recordData['relations'][$i]['related_record']['address_id'] = $recordData['relations'][$i]['related_record']['address_id']['id'];
                    }
                }
            }
        }*/

        return $this->_save($recordData, Sales_Controller_Invoice::getInstance(), 'Invoice', 'id', array($duplicateCheck));
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteInvoices($ids)
    {
        return $this->_delete($ids, Sales_Controller_Invoice::getInstance());
    }
    
    /*************************** purchase invoice functions *****************************/
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchPurchaseInvoices($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_PurchaseInvoice::getInstance(),
            'Sales_Model_PurchaseInvoiceFilter',
            ['Sales_Model_Supplier', Tinebase_Model_EvaluationDimensionItem::class, 'Addressbook_Model_Contact']);
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getPurchaseInvoice($id)
    {
        $invoice =  $this->_get($id, Sales_Controller_PurchaseInvoice::getInstance());
        
        return $invoice;
    }
    
    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @param  boolean $duplicateCheck
     *
     * @return array created/updated record
     */
    public function savePurchaseInvoice($recordData, $duplicateCheck = TRUE)
    {
        // validate supplier
        $foundSupplier = FALSE;
        
        if (is_array($recordData['relations'])) {
            foreach($recordData['relations'] as $relation) {
                if ($relation['related_model'] == 'Sales_Model_Supplier') {
                    $foundSupplier = $relation['related_id'];
                    break;
                }
            }
        }
        
        if (! $foundSupplier) {
            throw new Tinebase_Exception_Data('You have to set a customer!');
        }
        
        #if (is_array($recordData["costcenter_id"])) {
        #    $recordData["costcenter_id"] = $recordData["costcenter_id"]['id'];
        #}
        /*
        if (is_array($recordData['relations'])) {
            for ($i = 0; $i < count($recordData['relations']); $i++) {
                if (isset($recordData['relations'][$i]['related_record']['product_id'])) {
        
                    if (is_array($recordData['relations'][$i]['related_record']['product_id'])) {
                        $recordData['relations'][$i]['related_record']['product_id'] = $recordData['relations'][$i]['related_record']['product_id']['id'];
                    }
                } elseif ($recordData['relations'][$i]['related_model'] == 'Sales_Model_Invoice') {
                    if (is_array($recordData['relations'][$i]['related_record']['address_id'])) {
                        $recordData['relations'][$i]['related_record']['address_id'] = $recordData['relations'][$i]['related_record']['address_id']['id'];
                    }
                }
            }
        }*/

        return $this->_save($recordData, Sales_Controller_PurchaseInvoice::getInstance(), 'PurchaseInvoice', 'id', array($duplicateCheck));
    }

    public function contactToAdress($contact)
    {
        $contact = $this->_jsonToRecord($contact, Addressbook_Model_Contact::class);
        $address = new Sales_Model_Address([], true);
        $address->setFromContact($contact);

        return $this->_recordToJson($address);
    }
    /**
     * export purchase invoice to Datev email
     *
     * - support multiple invoices
     * - add action log
     *
     * @param string $modelName
     * @param $invoiceData
     * @return array
     * @throws Tinebase_Exception
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function exportInvoicesToDatevEmail(string $modelName, $invoiceData)
    {
        $senderConfig = Sales_Config::DATEV_SENDER_EMAIL_INVOICE;
        $recipientConfig = Sales_Config::DATEV_RECIPIENT_EMAILS_INVOICE;
        $numberProperty = 'number';

        $controller = null;
        switch ($modelName) {
            case 'PurchaseInvoice':
                $senderConfig = Sales_Config::DATEV_SENDER_EMAIL_PURCHASE_INVOICE;
                $recipientConfig = Sales_Config::DATEV_RECIPIENT_EMAILS_PURCHASE_INVOICE;
                $controller = Sales_Controller_PurchaseInvoice::getInstance();
                break;
            case Sales_Model_Document_Invoice::MODEL_NAME_PART:
                $controller = Sales_Controller_Document_Invoice::getInstance();
                $numberProperty = Sales_Model_Document_Invoice::FLD_DOCUMENT_NUMBER;
                break;
            case Sales_Model_Document_PurchaseInvoice::MODEL_NAME_PART:
                $senderConfig = Sales_Config::DATEV_SENDER_EMAIL_PURCHASE_INVOICE;
                $recipientConfig = Sales_Config::DATEV_RECIPIENT_EMAILS_PURCHASE_INVOICE;
                $controller = Sales_Controller_Document_PurchaseInvoice::getInstance();
                $numberProperty = Sales_Model_Document_PurchaseInvoice::FLD_EXTERNAL_INVOICE_NUMBER;
                break;
            case 'Invoice':
                $controller = Sales_Controller_Invoice::getInstance();
                break;
            default:
                break;
        }
        
        $invalidInvoiceIds = [];
        $validInvoiceIds = [];
        $errorMessage = null;

        if (empty($controller)) {
            throw new Tinebase_Exception_SystemGeneric('missing datev export controller');
        }
        
        $senderEmail = Sales_Config::getInstance()->get($senderConfig);
        $sender = !empty($senderEmail) ? Tinebase_User::getInstance()->getUserByProperty('accountEmailAddress', $senderEmail) 
            : Tinebase_Core::getUser();
        $recipientEmails = Sales_Config::getInstance()->get($recipientConfig);
        
        if (sizeof($recipientEmails) === 0) {
            throw new Tinebase_Exception_SystemGeneric('recipient email is not configured');
        }
        
        foreach ($invoiceData as $invoiceId => $attachmentIds) {
            if (sizeof($attachmentIds) === 0) {
                $invalidInvoiceIds[] = $invoiceId;
            } else {
                $validInvoiceIds[] = $invoiceId;
            }
        }
        
        if (sizeof($invalidInvoiceIds) > 0) {
            foreach ($controller->getMultiple($invalidInvoiceIds) as $invoice) {
                $title = $invoice->getTitle();
                $errorMessage .= PHP_EOL . $title . ' attachment size: ' . sizeof($invoiceData[$invoice->id]);
            }
            $exception = new Tinebase_Exception_SystemGeneric($errorMessage);
            $exception->setTitle('invoices with invalid attachments');
            throw $exception;
        } 
        
        $lastDatevSendTime = Tinebase_DateTime::now();
        $records = $controller->getMultiple($validInvoiceIds);
        Tinebase_FileSystem_RecordAttachments::getInstance()->getMultipleAttachmentsOfRecords($records);

        foreach ($records as $invoice) {
            $attachments = $invoice->attachments;
            $attachmentIds = $invoiceData[$invoice->id];
            if ($attachments) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Found ' .
                $attachments->count() . ' attachments for ' . $modelName . ' : ' . $invoice['name']);
            
            $selectedAttachments =  $attachments->filter(function(Tinebase_Model_Tree_Node $node) use ($attachmentIds) {
                return in_array($node->id, $attachmentIds);
            });
            
            $recipients = array_map(function ($recipientEmail) {return new Addressbook_Model_Contact(['email' => $recipientEmail], true);}, $recipientEmails);
            $messageBody = PHP_EOL . 'Model  : ' . $modelName . ', ID     : ' . $invoice['id']
                . PHP_EOL . 'Number : ' . $invoice[$numberProperty] . ', Title  : ' . $invoice->getTitle()
                . PHP_EOL . 'Datev Sent Date : ' . $lastDatevSendTime->toString();
            
            Tinebase_Notification::getInstance()->send($sender, $recipients, 'Datev notification', $messageBody, null,
                $selectedAttachments->asArray(), false, Tinebase_Model_ActionLog::TYPE_DATEV_EMAIL);
        }

        $model = Sales_Config::APP_NAME . '_Model_' . $modelName;
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($model, [
            ['field' => 'id', 'operator' => 'in', 'value' => $validInvoiceIds]
        ]);
        $result = [];
        if ($model === Sales_Model_PurchaseInvoice::class) {
            $result = $controller->updateMultiple($filter, ['last_datev_send_date' => $lastDatevSendTime->getIso()]);
            $result = $result['results'];
        }
        if ($model === Sales_Model_Document_Invoice::class || $model === Sales_Model_Invoice::class ||
                $model === Sales_Model_Document_PurchaseInvoice::class) {
            $expander = new Tinebase_Record_Expander($model, $model::getConfiguration()->jsonExpander);
            $expander->expand($records);
            foreach ($records as $validInvoice) {
                $validInvoice['last_datev_send_date'] = $lastDatevSendTime;
                $controller->update($validInvoice);
            }
            $result = $controller->getMultiple($validInvoiceIds);
        }
        
        return [
            'totalcount' => sizeof($result),
            'results' => $result->toArray(),
        ];
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deletePurchaseInvoices($ids)
    {
        return $this->_delete($ids, Sales_Controller_PurchaseInvoice::getInstance());
    }

    public function trackDocument(string $documentModel, string $documentId)
    {
        $resolvedIds = [];
        return Sales_Controller_Document_Abstract::createPrecursorTree($documentModel, [$documentId], $resolvedIds, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    Sales_Model_Document_Abstract::FLD_POSITIONS => [],
                    Sales_Model_Document_Abstract::FLD_CUSTOMER_ID => [],
                ],
            ])->toArray();
    }

    public function getApplicableBoilerplates(string $type, ?string $date = null, ?string $customerId = null, ?string $category = null, ?string $language = null, ?bool $isDefault = null)
    {
        if ($date) {
            $date = new Tinebase_DateTime($date);
        }
        $result = Sales_Controller_Boilerplate::getInstance()->getApplicableBoilerplates($type, $date, $customerId, $category, $language, $isDefault);
        return [
            'totalcount' => $result->count(),
            'results' => array_values($this->_multipleRecordsToJson($result)),
        ];
    }
    
    /*************************** offer functions *****************************/

    public function createEDocument(string $model, string $documentId): array
    {
        /** @var Tinebase_Record_Interface $model */
        $docCtrl = $model::getConfiguration()->getControllerInstance();
        if (!method_exists($docCtrl, 'createEDocument')) {
            throw new Tinebase_Exception_NotImplemented($model . ' does not support createEDocument yet');
        }

        $transaction = Tinebase_RAII::getTransactionManagerRAII();

        $result = $this->_recordToJson($docCtrl->createEDocument($documentId));

        $transaction->release();

        return $result;
    }

    public function getEDocumentSupplierData(string $purchaseInvoiceId): array
    {
        return $this->_recordToJson(
            Sales_Controller_Document_PurchaseInvoice::getInstance()->getEDocumentSupplier($purchaseInvoiceId)
        );
    }

    public function isEDocumentFile(array $fileLocation): bool
    {
        /** @var Tinebase_Model_FileLocation $fileLocation */
        $fileLocation = $this->_jsonToRecord($fileLocation, Tinebase_Model_FileLocation::class);
        return Sales_Controller_Document_PurchaseInvoice::getInstance()->isEDocumentFile($fileLocation);
    }

    public function importPurchaseInvoice(array $fileLocation, bool $importNonXR = false): array
    {
        /** @var Tinebase_Model_FileLocation $fileLocation */
        $fileLocation = $this->_jsonToRecord($fileLocation, Tinebase_Model_FileLocation::class);
        $purchaseInvoice = Sales_Controller_Document_PurchaseInvoice::getInstance()->importPurchaseInvoice($fileLocation, $importNonXR);
        return $this->_recordToJson($purchaseInvoice);
    }

    public function importFromPreviousInvoicePdf(string $invoiceId): array
    {
        $invoice = Sales_Controller_Document_Invoice::getInstance()->get($invoiceId);
        $prevInvoice = Sales_Controller_Document_Invoice::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Document_Invoice::class, [
            [TMFA::FIELD => Sales_Model_Document_Invoice::FLD_CONTRACT_ID, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $invoice->getIdFromProperty(Sales_Model_Document_Invoice::FLD_CONTRACT_ID)],
            [TMFA::FIELD => Sales_Model_Document_Invoice::FLD_DOCUMENT_DATE, TMFA::OPERATOR => 'before', TMFA::VALUE => $invoice->{Sales_Model_Document_Invoice::FLD_DOCUMENT_DATE}],
        ]), new Tinebase_Model_Pagination([
            Tinebase_Model_Pagination::FLD_SORT => Sales_Model_Document_Invoice::FLD_DOCUMENT_DATE,
            Tinebase_Model_Pagination::FLD_DIR => 'DESC',
            Tinebase_Model_Pagination::FLD_LIMIT => 1,
        ]))->getFirstRecord();

        if (null === $prevInvoice) {
            throw new Tinebase_Exception_NotFound('previous invoice not found');
        }

        Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($prevInvoice);
        $attachment = null;
        foreach ($prevInvoice->attachments as $tmp) {
            if (str_ends_with($tmp->name, '.pdf')) {
                $attachment = $tmp;
                break;
            }
        }

        if (null === $attachment) {
            throw new Tinebase_Exception_NotFound('previous invoice attachment not found');
        }
        //try {
            foreach ($positions = Sales_Model_Document_Invoice::createPositionsFromFileLocation(
                new Tinebase_Model_FileLocation([
                    Tinebase_Model_FileLocation::FLD_LOCATION => new Tinebase_Model_FileLocation_RecordAttachment(
                        [
                            Tinebase_Model_FileLocation_RecordAttachment::FLD_RECORD_ID => $prevInvoice->getId(),
                            Tinebase_Model_FileLocation_RecordAttachment::FLD_MODEL => Sales_Model_Document_Invoice::class,
                            Tinebase_Model_FileLocation_RecordAttachment::FLD_NAME => $attachment->name,
                        ]
                    ),
                    Tinebase_Model_FileLocation::FLD_MODEL_NAME => Tinebase_Model_FileLocation_RecordAttachment::class
                ])
            )->{Sales_Model_Document_Invoice::FLD_POSITIONS} as $position) {
                if (null !== $position->{Sales_Model_DocumentPosition_Invoice::FLD_POSITION_PRICE}) {
                    $position->{Sales_Model_DocumentPosition_Invoice::FLD_TYPE} = Sales_Model_DocumentPosition_Abstract::POS_TYPE_PRODUCT;
                }
                $position->{Sales_Model_DocumentPosition_Invoice::FLD_SALES_TAX_RATE} =
                    Sales_Config::VAT_PROCEDURE_STANDARD === $prevInvoice->{Sales_Model_Document_Invoice::FLD_VAT_PROCEDURE} ? 19 : 0;
                if (mb_strlen($position->title) > 255) {
                    $position->title = mb_substr($position->title, 0, 252) . '...';
                }
                if ($position->{Sales_Model_DocumentPosition_Invoice::FLD_SERVICE_PERIOD_END} && $position->{Sales_Model_DocumentPosition_Invoice::FLD_SERVICE_PERIOD_START}) {
                    $months = round(($position->{Sales_Model_DocumentPosition_Invoice::FLD_SERVICE_PERIOD_END}->getTimestamp() - $position->{Sales_Model_DocumentPosition_Invoice::FLD_SERVICE_PERIOD_START}->getTimestamp()) / (30 * 24 * 60 * 60));
                    $position->{Sales_Model_DocumentPosition_Invoice::FLD_SERVICE_PERIOD_START}->addMonth($months);
                    $position->{Sales_Model_DocumentPosition_Invoice::FLD_SERVICE_PERIOD_END} = $position->{Sales_Model_DocumentPosition_Invoice::FLD_SERVICE_PERIOD_START}->getClone()->addMonth($months)->subDay(1);
                }
            }

        /*} catch (Exception $e) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' reading positions from previous invoice pdf failed: ' . $e->getMessage());
            Tinebase_Exception::log($e);
        }*/

        $invoice->{Sales_Model_Document_Invoice::FLD_POSITIONS} = $positions;
        return $this->_recordToJson(Sales_Controller_Document_Invoice::getInstance()->update($invoice));
    }

    public function createInvoiceFromFileLocation(array $fileLocation): array
    {
        $fileLocation = $this->_jsonToRecord($fileLocation, Tinebase_Model_FileLocation::class);
        /** @var Tinebase_Model_FileLocation $fileLocation */
        $invoice = Sales_Model_Document_Invoice::createPositionsFromFileLocation($fileLocation);
        return $this->_recordToJson($invoice);
    }

    /**
     * @apiTimeout 60
     * @param string $model
     * @param string $documentId
     * @return array
     */
    public function createPaperSlip(string $model, string $documentId)
    {
        if ('0' === $documentId || '' === $documentId) {
            throw new Tinebase_Exception_SystemGeneric('documentId missing');
        }

        if (!($stream = fopen('php://memory', 'w+'))) {
            throw new Tinebase_Exception_Backend('could not create memory stream');
        }

        /** @var Tinebase_Record_Interface $model */
        $docCtrl = $model::getConfiguration()->getControllerInstance();
        /** @var Sales_Model_Document_Abstract $preExportDocument */
        $preExportDocument = $docCtrl->get($documentId);

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($model, [
            ['field' => 'id', 'operator' => 'equals', 'value' => $documentId]
        ]);

        $exportDef = Tinebase_ImportExportDefinition::getInstance()->getByName(
            'document_' . strtolower(preg_replace('/^Sales_Model_Document_/', '', $model) .'_pdf'));

        $doc = new ($exportDef->plugin)($filter, null, ['definitionId' => $exportDef->getId()]);
        $doc->generate();
        $doc->write($stream);
        rewind($stream);
        $model::resetConfiguration();

        $transaction = Tinebase_RAII::getTransactionManagerRAII();

        /** @var Sales_Model_Document_Abstract $document */
        $document = $docCtrl->get($documentId);
        Tinebase_Record_Expander::expandRecord($document);
        if ($preExportDocument->seq < $document->seq) {
            throw new Tinebase_Exception_ConcurrencyConflict('document seq increase during export, please try again');
        }

        $name = (new Tinebase_Twig(Tinebase_Core::getLocale(), Tinebase_Translation::getTranslation(Sales_Config::APP_NAME), [
            Tinebase_Twig::TWIG_LOADER =>
                new Tinebase_Twig_CallBackLoader(Sales_Config::INVOICE_PAPERSLIP_NAME_TMPL, time() - 1, fn() => Sales_Config::getInstance()->{Sales_Config::INVOICE_PAPERSLIP_NAME_TMPL}),
            Tinebase_Twig::TWIG_AUTOESCAPE => false,
        ]))->load(Sales_Config::INVOICE_PAPERSLIP_NAME_TMPL)->render([
            'document' => $document,
            'date' => Tinebase_DateTime::now()->format('Y-m-d'),
        ]);

        if ($node = $document->attachments->find('name', $name)) {
            $document->attachments->removeRecord($node);
            if (Sales_Config::getInstance()->{Sales_Config::INVOICE_PAPERSLIP_RENAME_TMPL}) {
                $replaceName = (new Tinebase_Twig(Tinebase_Core::getLocale(), Tinebase_Translation::getTranslation(Sales_Config::APP_NAME), [
                    Tinebase_Twig::TWIG_LOADER =>
                        new Tinebase_Twig_CallBackLoader(Sales_Config::INVOICE_PAPERSLIP_RENAME_TMPL, time() - 1, fn() => Sales_Config::getInstance()->{Sales_Config::INVOICE_PAPERSLIP_RENAME_TMPL}),
                    Tinebase_Twig::TWIG_AUTOESCAPE => false,
                ]))->load(Sales_Config::INVOICE_PAPERSLIP_RENAME_TMPL)->render([
                    'document' => $document,
                    'node' => $node,
                    'date' => Tinebase_DateTime::now()->format('Y-m-d'),
                ]);
                $path = Tinebase_FileSystem::getInstance()->getPathOfNode($node);
                array_walk($path, fn(&$path) => $path = $path['name']);
                $oldPath = '/' . implode('/', $path);
                array_pop($path);
                $document->attachments->addRecord(
                    Tinebase_FileSystem::getInstance()->rename($oldPath, '/'. join('/', $path) . '/' . $replaceName)
                );
            }
        }
        $document->attachments->addRecord(new Tinebase_Model_Tree_Node(['name' => $name, 'tempFile' => $stream], true));
        /** @var Sales_Model_Document_Abstract $document */
        $document = $docCtrl->update($document);
        $attachmentId = $document->attachments->find('name', $name)->getId();
        if (!$document->{Sales_Model_Document_Abstract::FLD_ATTACHED_DOCUMENTS}) {
            $document->{Sales_Model_Document_Abstract::FLD_ATTACHED_DOCUMENTS} = new Tinebase_Record_RecordSet(Sales_Model_Document_AttachedDocument::class, []);
        }
        if (($attachedDocument = $document->{Sales_Model_Document_Abstract::FLD_ATTACHED_DOCUMENTS}->find(Sales_Model_Document_AttachedDocument::FLD_NODE_ID, $attachmentId))) {
            $attachedDocument->{Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ} = $document->{Sales_Model_Document_Abstract::FLD_DOCUMENT_SEQ} + 1;
        } else {
            $document->{Sales_Model_Document_Abstract::FLD_ATTACHED_DOCUMENTS}->addRecord(new Sales_Model_Document_AttachedDocument([
                Sales_Model_Document_AttachedDocument::FLD_TYPE => Sales_Model_Document_AttachedDocument::TYPE_PAPERSLIP,
                Sales_Model_Document_AttachedDocument::FLD_NODE_ID => $attachmentId,
                Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ => $document->{Sales_Model_Document_Abstract::FLD_DOCUMENT_SEQ} + 1,
            ], true));
        }
        $document->getCurrentAttachedDocuments()
            ->filter(fn ($rec) => $rec->{Sales_Model_Document_AttachedDocument::FLD_TYPE} === Sales_Model_Document_AttachedDocument::TYPE_EDOCUMENT)
            ->{Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ} = $document->{Sales_Model_Document_Abstract::FLD_DOCUMENT_SEQ} + 1;
        $result = $this->_recordToJson($docCtrl->update($document));

        $transaction->release();
        return $result;
    }

    public function dispatchDocument(string $model, string $documentId, bool $redispatch = false): bool
    {
        /** @var Tinebase_Record_Interface $model */
        /** @var Sales_Controller_Document_Abstract $docCtrl */
        $docCtrl = $model::getConfiguration()->getControllerInstance();

        /** NO TRANSACTION ... we are dispatching, by mail etc. it might be very slow, we do not want to lock stuff */

        return $docCtrl::dispatchDocument($documentId, $redispatch);
    }

    public function createBatchJob(array $instructions, array $initialData): array
    {
        if (empty($instructions) || empty($initialData)) {
            throw new Tinebase_Exception_UnexpectedValue(__METHOD__ . ' requires instructions / initial data');
        }

        $recordSet = new Tinebase_Record_RecordSet(Tinebase_Model_BatchJobStep::class);
        $steps = $recordSet;
        $first = true;
        foreach ($instructions as $instruction) {
            switch ($instruction[0] ?? null) {
                case 'bookDocument':
                    if (!is_string($instruction[1] ?? null) || !is_subclass_of($instruction[1], Sales_Model_Document_Abstract::class)) {
                        throw new Tinebase_Exception_UnexpectedValue(($instruction[1] ?? '') . ' is not a subclass of Sales_Model_Document_Abstract');
                    }
                    $recordSet->addRecord($step = new Tinebase_Model_BatchJobStep([
                        Tinebase_Model_BatchJobStep::FLD_CALLABLES => new Tinebase_Record_RecordSet(Tinebase_Model_BatchJobCallable::class, [
                            new Tinebase_Model_BatchJobCallable([
                                Tinebase_Model_BatchJobCallable::FLD_CLASS => Sales_Controller::class,
                                Tinebase_Model_BatchJobCallable::FLD_METHOD => 'bookDocument',
                                Tinebase_Model_BatchJobCallable::FLD_STATIC => true,
                                Tinebase_Model_BatchJobCallable::FLD_APPEND_DATA => [$instruction[1]],
                            ]),
                        ]),
                        Tinebase_Model_BatchJobStep::FLD_NEXT_STEPS => ($nextSteps = new Tinebase_Record_RecordSet(Tinebase_Model_BatchJobStep::class)),
                    ]));
                    $recordSet = $nextSteps;
                    if ($first) {
                        $first = false;
                        $inData = [];
                        foreach ($initialData as $data) {
                            $inData['_' . $data] = json_encode([$data]);
                        }
                        $step->{Tinebase_Model_BatchJobStep::FLD_IN_DATA} = $inData;
                    }
                    break;

                case 'dispatchDocument':
                    if (!is_string($instruction[1] ?? null) || !is_subclass_of($instruction[1], Sales_Model_Document_Abstract::class)) {
                        throw new Tinebase_Exception_UnexpectedValue(($instruction[1] ?? '') . ' is not a subclass of Sales_Model_Document_Abstract');
                    }
                    $recordSet->addRecord($step = new Tinebase_Model_BatchJobStep([
                        Tinebase_Model_BatchJobStep::FLD_CALLABLES => new Tinebase_Record_RecordSet(Tinebase_Model_BatchJobCallable::class, [
                            new Tinebase_Model_BatchJobCallable([
                                Tinebase_Model_BatchJobCallable::FLD_CLASS => Sales_Controller::class,
                                Tinebase_Model_BatchJobCallable::FLD_METHOD => 'dispatchDocument',
                                Tinebase_Model_BatchJobCallable::FLD_STATIC => true,
                                Tinebase_Model_BatchJobCallable::FLD_APPEND_DATA => [$instruction[1]],
                            ]),
                        ]),
                        Tinebase_Model_BatchJobStep::FLD_NEXT_STEPS => ($nextSteps = new Tinebase_Record_RecordSet(Tinebase_Model_BatchJobStep::class)),
                    ]));
                    $recordSet = $nextSteps;
                    if ($first) {
                        $first = false;
                        $inData = [];
                        foreach ($initialData as $data) {
                            $inData['_' . $data] = json_encode([$data]);
                        }
                        $step->{Tinebase_Model_BatchJobStep::FLD_IN_DATA} = $inData;
                    }
                    break;

                case 'createFollowupDocument':
                    if (!is_string($instruction[1] ?? null) || !is_subclass_of($instruction[1], Sales_Model_Document_Abstract::class) ||
                            !is_string($instruction[2] ?? null) || !is_subclass_of($instruction[2], Sales_Model_Document_Abstract::class)) {
                        throw new Tinebase_Exception_UnexpectedValue(print_r($instruction, true) . ' is not an array of two subclass of Sales_Model_Document_Abstract');
                    }
                    $recordSet->addRecord($step = new Tinebase_Model_BatchJobStep([
                        Tinebase_Model_BatchJobStep::FLD_CALLABLES => ($callables = new Tinebase_Record_RecordSet(Tinebase_Model_BatchJobCallable::class)),
                        Tinebase_Model_BatchJobStep::FLD_NEXT_STEPS => ($nextSteps = new Tinebase_Record_RecordSet(Tinebase_Model_BatchJobStep::class)),
                    ]));
                    $recordSet = $nextSteps;
                    if ($first) {
                        $first = false;
                        $inData = [];
                        foreach($initialData as $data) {
                            $inData['_' . $data] = json_encode([[
                                Sales_Model_Document_Transition::FLD_TARGET_DOCUMENT_TYPE => $instruction[2],
                                Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS => [[
                                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL => $instruction[1],
                                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT => $data,
                                ]],
                            ]]);
                        }
                        $step->{Tinebase_Model_BatchJobStep::FLD_IN_DATA} = $inData;
                    } else {
                        $callables->addRecord(new Tinebase_Model_BatchJobCallable([
                            Tinebase_Model_BatchJobCallable::FLD_CLASS => Sales_Controller::class,
                            Tinebase_Model_BatchJobCallable::FLD_METHOD => 'wrapTransition',
                            Tinebase_Model_BatchJobCallable::FLD_STATIC => true,
                            Tinebase_Model_BatchJobCallable::FLD_APPEND_DATA => [[
                                Sales_Model_Document_Transition::FLD_TARGET_DOCUMENT_TYPE => $instruction[2],
                                Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS => [[
                                    Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL => $instruction[1],
                                ]],
                            ]],
                        ]));
                    }
                    $callables->addRecord(new Tinebase_Model_BatchJobCallable([
                        Tinebase_Model_BatchJobCallable::FLD_CLASS => Sales_Controller::class,
                        Tinebase_Model_BatchJobCallable::FLD_METHOD => 'createFollowupDocument',
                        Tinebase_Model_BatchJobCallable::FLD_STATIC => true,
                    ]));
                    break;

                default:
                    throw new Tinebase_Exception_UnexpectedValue(print_r($instruction, true) . ' is not a valid instruction');
            }
        }

        $batchJob = new Tinebase_Model_BatchJob([
            Tinebase_Model_BatchJob::FLD_TITLE      => 'Sales batch process "' . $instructions[0][0] . '" for ' . Tinebase_Core::getUser()->accountDisplayName . ' at ' . Tinebase_DateTime::now()->toString(),
            Tinebase_Model_BatchJob::FLD_ACCOUNT_ID => Tinebase_Core::getUser(),
            Tinebase_Model_BatchJob::FLD_STEPS => $steps,
        ]);

        $batchJob = Tinebase_Controller_BatchJob::getInstance()->create($batchJob);
        return $batchJob->toArray();
    }

    public function createFollowupDocument(array $documentTransition): array
    {
        $documentTransition = $this->_jsonToRecord($documentTransition, Sales_Model_Document_Transition::class);
        /** @var Sales_Model_Document_Transition $documentTransition */

        return $this->_recordToJson(
            Sales_Controller_Document_Abstract::executeTransition($documentTransition)
        );
    }

    public function getMatchingSharedOrderDocumentTransition(string $orderId, string $targetDocument): array
    {
        switch ($targetDocument) {
            case Sales_Model_Document_Invoice::class:
                $field = Sales_Model_Document_Order::FLD_SHARED_INVOICE;
                $followUpStatusFld = Sales_Model_Document_Order::FLD_FOLLOWUP_INVOICE_CREATED_STATUS;
                $recipientField = Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID;
                break;
            case Sales_Model_Document_Delivery::class:
                $field = Sales_Model_Document_Order::FLD_SHARED_DELIVERY;
                $followUpStatusFld = Sales_Model_Document_Order::FLD_FOLLOWUP_DELIVERY_CREATED_STATUS;
                $recipientField = Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID;
                break;
            default:
                throw new Tinebase_Exception_InvalidArgument('target document needs to be either invoice or delivery');
        }

        $order = Sales_Controller_Document_Order::getInstance()->get($orderId);
        $ft = $order->{$recipientField}?->{Sales_Model_Address::FLD_FULLTEXT};
        $contractId = $order->getIdFromProperty(Sales_Model_Document_Abstract::FLD_CONTRACT_ID);

        $orders = Sales_Controller_Document_Order::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Document_Order::class, array_merge([
                [TMFA::FIELD => Sales_Model_Document_Abstract::FLD_DOCUMENT_CATEGORY, TMFA::OPERATOR => 'equals', TMFA::VALUE => $order->getIdFromProperty(Sales_Model_Document_Abstract::FLD_DOCUMENT_CATEGORY)],
                [TMFA::FIELD => $field, TMFA::OPERATOR => 'equals', TMFA::VALUE => true],
                [TMFA::FIELD => Sales_Model_Document_Abstract::FLD_CONTRACT_ID, TMFA::OPERATOR => 'equals', TMFA::VALUE => $contractId],
                [TMFA::FIELD => $followUpStatusFld, TMFA::OPERATOR => 'not', TMFA::VALUE => Sales_Config::DOCUMENT_FOLLOWUP_STATUS_COMPLETED],
                [TMFA::FIELD => Sales_Model_Document_Order::FLD_ORDER_STATUS, TMFA::OPERATOR => 'equals', TMFA::VALUE => Sales_Model_Document_Order::STATUS_ACCEPTED],
            ], $order->{$recipientField} ? [
                [TMFA::FIELD => $recipientField, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                    [TMFA::FIELD => Tinebase_ModelConfiguration_Const::FLD_ORIGINAL_ID, TMFA::OPERATOR => 'equals', TMFA::VALUE => $order->{$recipientField}->getIdFromProperty(Tinebase_ModelConfiguration_Const::FLD_ORIGINAL_ID)]
                ]]] : [
                    [TMFA::FIELD => $recipientField, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => null],
                ]
            )), null, new Tinebase_Record_Expander(Sales_Model_Document_Order::class, [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [$recipientField => []],
            ]))->filter(fn ($rec) => $rec->{$recipientField}?->{Sales_Model_Address::FLD_FULLTEXT} === $ft);

        if ($orders->count() === 0) {
            return [];
        }

        $transitionSources = new Tinebase_Record_RecordSet(Sales_Model_Document_TransitionSource::class);
        foreach ($orders as $order) {
            $transitionSources->addRecord(new Sales_Model_Document_TransitionSource([
                Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT => $order,
                Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT_MODEL => Sales_Model_Document_Order::class,
            ]));
        }
        return $this->_recordToJson(new Sales_Model_Document_Transition([
            Sales_Model_Document_Transition::FLD_TARGET_DOCUMENT_TYPE => $targetDocument,
            Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS => $transitionSources,
        ]));
    }

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchOffers($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_Offer::getInstance(), 'Sales_Model_OfferFilter', array('Sales_Model_Customer'));
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getOffer($id)
    {
        return $this->_get($id, Sales_Controller_Offer::getInstance());
    }
    
    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @param  boolean $duplicateCheck
     *
     * @return array created/updated record
     */
    public function saveOffer($recordData, $duplicateCheck)
    {
        return $this->_save($recordData, Sales_Controller_Offer::getInstance(), 'Offer');
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteOffers($ids)
    {
        return $this->_delete($ids, Sales_Controller_Offer::getInstance());
    }

    /**
     * names of new uploads (temp files) the client marked as supporting documents in the current save, their node
     * ids exist only after the save (see _save)
     *
     * @var string[]
     */
    protected array $_pendingSupportingDocAttachmentNames = [];

    /**
     * supporting documents of new uploads: the client marks them with the id of their temp file, the attached
     * documents are added after the save created their nodes
     */
    protected function _save(
        $_recordData,
        Tinebase_Controller_Record_Interface $_controller,
        $_modelName,
        $_identifier = 'id',
        $_additionalArguments = array()
    ) {
        $modelClass = preg_match('/_Model_/', $_modelName) ? $_modelName :
            $this->_applicationName . '_Model_' . $_modelName;
        if (!is_a($modelClass, Sales_Model_Document_Abstract::class, true)) {
            return parent::_save($_recordData, $_controller, $_modelName, $_identifier, $_additionalArguments);
        }

        $this->_pendingSupportingDocAttachmentNames = [];
        $transaction = Tinebase_RAII::getTransactionManagerRAII();
        $result = parent::_save($_recordData, $_controller, $_modelName, $_identifier, $_additionalArguments);

        if ($names = $this->_pendingSupportingDocAttachmentNames) {
            $this->_pendingSupportingDocAttachmentNames = [];
            /** @var Sales_Model_Document_Abstract $document */
            $document = $_controller->get($result['id']);
            Tinebase_Record_Expander::expandRecord($document);
            $attachedDocs = $document->{Sales_Model_Document_Abstract::FLD_ATTACHED_DOCUMENTS};
            if (!$attachedDocs instanceof Tinebase_Record_RecordSet) {
                $attachedDocs = new Tinebase_Record_RecordSet(Sales_Model_Document_AttachedDocument::class);
                $document->{Sales_Model_Document_Abstract::FLD_ATTACHED_DOCUMENTS} = $attachedDocs;
            }
            $changed = false;
            foreach (array_unique($names) as $name) {
                $node = $document->attachments?->find('name', $name);
                if (!$node || $attachedDocs->find(Sales_Model_Document_AttachedDocument::FLD_NODE_ID, $node->getId())) {
                    continue;
                }
                $attachedDocs->addRecord(new Sales_Model_Document_AttachedDocument([
                    Sales_Model_Document_AttachedDocument::FLD_TYPE =>
                        Sales_Model_Document_AttachedDocument::TYPE_SUPPORTING_DOCUMENT,
                    Sales_Model_Document_AttachedDocument::FLD_NODE_ID => $node->getId(),
                    Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ =>
                        (int) $document->{Sales_Model_Document_Abstract::FLD_DOCUMENT_SEQ},
                ], true));
                $changed = true;
            }
            if ($changed) {
                $result = $this->_recordToJson($_controller->update($document));
            }
        }

        $transaction->release();
        return $result;
    }

    /**
     * @param Tinebase_Record_Interface $record
     */
    protected function _dependentRecordsFromJson(&$record)
    {
        parent::_dependentRecordsFromJson($record);

        if ($record instanceof Sales_Model_Document_Abstract) {
            $this->_sanitizeAttachedDocumentsFromJson($record);
        }
    }

    /**
     * the client may only mark / unmark attachments of the document as supporting documents. Paperslips and
     * eDocuments are managed by the server: they are taken from the stored document, whatever the client sends.
     * New uploads are marked with the id of their temp file (node_id), see _save.
     *
     * @param Sales_Model_Document_Abstract $record
     */
    protected function _sanitizeAttachedDocumentsFromJson(Sales_Model_Document_Abstract $record): void
    {
        $fld = Sales_Model_Document_Abstract::FLD_ATTACHED_DOCUMENTS;
        $currentRecord = null;
        if ($record->getId()) {
            try {
                $currentRecord = $record::getConfiguration()->getControllerInstance()->get($record->getId());
                Tinebase_Record_Expander::expandRecord($currentRecord);
            } catch (Tinebase_Exception_NotFound) {
            }
        }

        $currentAttachedDocs = $currentRecord?->{$fld} instanceof Tinebase_Record_RecordSet ? $currentRecord->{$fld} :
            new Tinebase_Record_RecordSet(Sales_Model_Document_AttachedDocument::class);
        $isSupportingDoc = fn (Sales_Model_Document_AttachedDocument $attachedDoc) =>
            Sales_Model_Document_AttachedDocument::TYPE_SUPPORTING_DOCUMENT ===
                $attachedDoc->{Sales_Model_Document_AttachedDocument::FLD_TYPE};
        $currentSupportingDocs = $currentAttachedDocs->filter($isSupportingDoc);

        // server managed attached documents: unchanged from the stored document
        $result = $currentAttachedDocs->filter(fn ($attachedDoc) => !$isSupportingDoc($attachedDoc));
        $serverManagedNodeIds = $result->{Sales_Model_Document_AttachedDocument::FLD_NODE_ID};

        // supporting documents requested by the client (no attached_documents sent: keep the stored ones)
        if (null === $record->{$fld}) {
            $requestedDocs = $currentAttachedDocs;
        } elseif ($record->{$fld} instanceof Tinebase_Record_RecordSet) {
            $requestedDocs = $record->{$fld};
        } else {
            $requestedDocs = new Tinebase_Record_RecordSet(Sales_Model_Document_AttachedDocument::class);
        }
        $requestedNodeIds = array_unique($requestedDocs->filter($isSupportingDoc)
            ->{Sales_Model_Document_AttachedDocument::FLD_NODE_ID});

        // only stored attachments the document keeps can be supporting documents
        $attachmentIds = $this->_getAttachmentIds($currentRecord?->attachments);
        if (null !== $record->attachments) {
            $attachmentIds = array_intersect($attachmentIds, $this->_getAttachmentIds($record->attachments));
        }

        // new uploads sent with this save: temp file id => name
        $tempFileNames = $this->_getTempFileAttachmentNames($record->attachments);

        foreach ($requestedNodeIds as $nodeId) {
            if (!$nodeId || in_array($nodeId, $serverManagedNodeIds)) {
                continue;
            }
            if (isset($tempFileNames[$nodeId])) {
                $this->_pendingSupportingDocAttachmentNames[] = $tempFileNames[$nodeId];
                continue;
            }
            if (!in_array($nodeId, $attachmentIds)) {
                continue;
            }
            $attachedDoc = $currentSupportingDocs->find(Sales_Model_Document_AttachedDocument::FLD_NODE_ID, $nodeId);
            if (!$attachedDoc) {
                $attachedDoc = new Sales_Model_Document_AttachedDocument([
                    Sales_Model_Document_AttachedDocument::FLD_TYPE =>
                        Sales_Model_Document_AttachedDocument::TYPE_SUPPORTING_DOCUMENT,
                    Sales_Model_Document_AttachedDocument::FLD_NODE_ID => $nodeId,
                    Sales_Model_Document_AttachedDocument::FLD_CREATED_FOR_SEQ =>
                        (int) $currentRecord->{Sales_Model_Document_Abstract::FLD_DOCUMENT_SEQ},
                ], true);
            }
            $result->addRecord($attachedDoc);
        }

        $record->{$fld} = $result;
    }

    /**
     * @param Tinebase_Record_RecordSet|array|null $attachments
     * @return array<string, string> temp file id => name of the new uploads
     */
    protected function _getTempFileAttachmentNames($attachments): array
    {
        $names = [];
        foreach ($attachments ?? [] as $attachment) {
            if ($attachment instanceof Tinebase_Record_Interface) {
                $tempFile = $attachment->tempFile;
                $name = $attachment->name;
            } else {
                $tempFile = $attachment['tempFile'] ?? null;
                $name = $attachment['name'] ?? null;
            }
            if ($tempFile instanceof Tinebase_Record_Interface) {
                $tempFileId = $tempFile->getId();
                $name = $name ?: $tempFile->name;
            } elseif (is_array($tempFile)) {
                $tempFileId = $tempFile['id'] ?? null;
                $name = $name ?: ($tempFile['name'] ?? null);
            } else {
                $tempFileId = $tempFile;
            }
            if ($tempFileId && is_string($tempFileId) && $name && is_string($name)) {
                $names[$tempFileId] = $name;
            }
        }
        return $names;
    }

    /**
     * @param Tinebase_Record_RecordSet|array|null $attachments
     * @return array ids of the stored attachments (new uploads have no id yet)
     */
    protected function _getAttachmentIds($attachments): array
    {
        $ids = [];
        foreach ($attachments ?? [] as $attachment) {
            $id = $attachment instanceof Tinebase_Record_Interface ? $attachment->getId() : ($attachment['id'] ?? null);
            if ($id && is_string($id)) {
                $ids[] = $id;
            }
        }
        return $ids;
    }
}

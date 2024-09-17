<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Christian Feitl<c.feitl@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * csv import class for the Sales
 *
 * @package     Sales
 * @subpackage  Import
 *
 */
class Sales_Import_Contract_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * additional config options
     *
     * @var array
     */
    protected $_additionalOptions = array(
        'container_id' => '',
        'dates' => array('start_date','end_date')
    );

    /**
     * add some more values (container id)
     *
     * @return array
     */
    protected function _addData()
    {
        $result['container_id'] = $this->_options['container_id'];
        return $result;
    }

    /**
     * @param array $_data
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _doConversions($_data)
    {
        $result = parent::_doConversions($_data);



        $result = $this->_setCustomers($result);
        $result = $this->_setCostCenter($result);
        $result = $this->_setContact($result);
        if (!empty(['product'])) {
            $products = explode(',', $result['product']);
            foreach ($products as $product) {
                $allProducts = Sales_Controller_Product::getInstance()->getAll();
                foreach ($allProducts as $oneProducts) {
                    if ($oneProducts['name'] == $product) {
                        $result['products'][] = array('product_id' => $oneProducts['id'], 'quantity' => '1');
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @param $result
     * @return mixed
     * @throws Tinebase_Exception_InvalidArgument
     * Resolve customers and set billing address
     */
    protected function _setCustomers($result)
    {
        if (!empty($result['customers'])) {
            $customers = Sales_Controller_Customer::getInstance()->getAll();
            foreach ($customers as $customer) {
                if ($customer['name'] == $result['customers']) {
                    $customer_id = $customer['id'];
                    $result['relations'] = array(
                        array(
                            'own_model' => 'Sales_Model_Contract',
                            'own_backend' => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                            'own_id' => NULL,
                            'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
                            'related_model' => 'Sales_Model_Customer',
                            'related_backend' => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                            'related_id' => $customer_id,
                            'type' => 'CUSTOMER'
                        ));
                    $addresses = Sales_Controller_Address::getInstance()->getAll();
                    foreach ($addresses as $address) {
                        if ($address['customer_id'] == $customer_id) {
                            $result['billing_address_id'] = $address['id'];
                            break;
                        }
                    }
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * @param $result
     * @return mixed
     * @throws Tinebase_Exception_InvalidArgument
     * Resolve CostCenter
     */
    protected function _setCostCenter($result)
    {
        if (!empty($result['costcenter'])) {
            $costCenters = Tinebase_Controller_EvaluationDimensionItem::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_EvaluationDimensionItem::class, [
                ['field' => Tinebase_Model_EvaluationDimensionItem::FLD_EVALUATION_DIMENSION_ID, 'operator' => 'definedBy', 'value' => [
                    ['field' => Tinebase_Model_EvaluationDimension::FLD_NAME, 'operator' => 'equals', 'value' => Tinebase_Model_EvaluationDimension::COST_CENTER],
                ]],
            ]));
            foreach ($costCenters as $costCenter) {
                if ($costCenter['name'] == $result['costcenter']) {
                    $result['eval_dim_cost_center'] = $costCenter['id'];
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * @param $result
     * @return mixed
     * @throws Tinebase_Exception_InvalidArgument
     * Set contract_in and contract_ex
     */
    protected function _setContact($result)
    {
        if (!empty($result['contract_ex']) or !empty($result['contract_in'])) {
            $users = Addressbook_Controller_Contact::getInstance()->getAll();
            foreach ($users as $user) {
                if ($user['n_fileas'] == $result['contract_ex']) {
                    $result['relations'][] = array(
                        'own_model' => 'Sales_Model_Contract',
                        'own_backend' => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                        'own_id' => NULL,
                        'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
                        'related_model' => 'Addressbook_Model_Contact',
                        'related_backend' => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                        'related_id' => $user['id'],
                        'type' => 'CUSTOMER'
                    );
                }
                if ($user['n_fileas'] == $result['contract_in']) {
                    $result['relations'][] = array(
                        'own_model' => 'Sales_Model_Contract',
                        'own_backend' => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                        'own_id' => NULL,
                        'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
                        'related_model' => 'Addressbook_Model_Contact',
                        'related_backend' => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                        'related_id' => $user['id'],
                        'type' => 'RESPONSIBLE'
                    );
                }
            }
        }
        return $result;
    }

}

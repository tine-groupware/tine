<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Sales_Frontend_Json
 */
class Sales_JsonTest extends TestCase
{
    /**
     * @var Sales_Frontend_Json
     */
    protected $_instance = null;

    protected $_deleteContracts = array();
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new \PHPUnit\Framework\TestSuite('Tine 2.0 Sales Json Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->_instance = new Sales_Frontend_Json();
        
        Sales_Controller_Contract::getInstance()->setNumberPrefix();
        Sales_Controller_Contract::getInstance()->setNumberZerofill();

        $this->_deleteContracts = array();
    }

    protected function tearDown(): void
    {
        Tinebase_Core::getPreference()->setValue(Tinebase_Preference::ADVANCED_SEARCH, false);

        parent::tearDown();

        if (count($this->_deleteContracts) > 0) {
            $this->_instance->deleteContracts($this->_deleteContracts);
        }
    }

    /**
     * try to add a contract
     * 
     * @return array
     */
    public function testAddContract()
    {
        $contract = $this->_getContract();
        $contractData = $this->_instance->saveContract($contract->toArray());
        
        // checks
        $this->assertGreaterThan(0, $contractData['number']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $contractData['created_by']['accountId'],
                'created_by not correct/resolved: ' . print_r($contractData, true));
        
        return $contractData;
    }

    /**
     * try to get a contract
     */
    public function testGetContract()
    {
        $contract = $this->_getContract();
        $contractData = $this->_instance->saveContract($contract->toArray());
        $contractData = $this->_instance->getContract($contractData['id']);

        // checks
        $this->assertGreaterThan(0, $contractData['number']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $contractData['created_by']['accountId']);
    }

    /**
     * Tests multiple update with relations
     */
    public function testUpdateMultipleWithRelations()
    {
        $contract1 = $this->_getContract('contract 1');
        $contract2 = $this->_getContract('contract 2');
        $contract1 = Sales_Controller_Contract::getInstance()->create($contract1);
        $contract2 = Sales_Controller_Contract::getInstance()->create($contract2);
        
        // peter, bob, laura, lisa
        list($contact1, $contact2, $contact3, $contact4) = $this->_createContacts();
        
        // add contact2 as customer relation to contract2
        $this->_setContractRelations($contract2, array($contact2), 'CUSTOMER');

        $contract1and2Ids = array($contract1->id, $contract2->id);

        $tbJson = new Tinebase_Frontend_Json();
        // add Responsible contact1 to both contracts
        $response = $tbJson->updateMultipleRecords('Sales', 'Contract',
            array(
                array('name' => '%RESPONSIBLE-Addressbook_Model_Contact', 'value' => $contact1->getId()),
                array('name' => '%add', 'value' => json_encode(array(
                    'own_model' => 'Sales_Model_Contract',
                    'own_backend' => 'Sql',
                    'related_degree' => 'sibling',
                    'related_model' => 'Addressbook_Model_Contact',
                    'related_backend' => 'Sql',
                    'related_id' => $contact1->getId(),
                    'type' => 'RESPONSIBLE',
                    // create relation failed if we set relation id here
                    //'id' => Tinebase_Record_Abstract::generateUID()
                )))
            ),
            array(array('field' => 'id', 'operator' => 'in', 'value' => $contract1and2Ids))
        );
        $this->assertEquals(2, count($response['results']));
        
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Tinebase_Model_Relation', [
            ['field' => 'own_model', 'operator' => 'equals', 'value' => 'Sales_Model_Contract'],
            ['field' => 'related_model', 'operator' => 'equals', 'value' => 'Addressbook_Model_Contact'],
            ['field' => 'type', 'operator' => 'equals', 'value' => 'RESPONSIBLE'],
        ]);
        $contractRelations = Tinebase_Relations::getInstance()->search($filter);
        $this->assertEquals(2, count($contractRelations));

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Tinebase_Model_Relation', [
            ['field' => 'own_model', 'operator' => 'equals', 'value' => 'Addressbook_Model_Contact'],
            ['field' => 'related_model', 'operator' => 'equals', 'value' => 'Sales_Model_Contract'],
            ['field' => 'type', 'operator' => 'equals', 'value' => 'RESPONSIBLE'],
        ]);
        $contactRelations = Tinebase_Relations::getInstance()->search($filter);
        $this->assertEquals(2, count($contactRelations));

        
        $contract1re = $this->_instance->getContract($contract1->getId());
        $contract2re = $this->_instance->getContract($contract2->getId());

        // only one CUSTOMER relation is allowed, contract2 still has related contact2
        $this->assertEquals(1, count($contract1re['relations']), 'contract1 relations count failed: ' . print_r($contract1re, true));
        $this->assertEquals(2, count($contract2re['relations']), 'contract2 relations count failed: ' . print_r($contract2re, true));

        $this->assertEquals($contact1->getId(), $contract1re['relations'][0]['related_id']);
        
        if ($contract2re['relations'][1]['related_id'] == $contact1->getId()) {
            $this->assertEquals($contact1->getId(), $contract2re['relations'][1]['related_id']);
            $this->assertEquals($contact2->getId(), $contract2re['relations'][0]['related_id']);
        } else {
            $this->assertEquals($contact2->getId(), $contract2re['relations'][1]['related_id']);
            $this->assertEquals($contact1->getId(), $contract2re['relations'][0]['related_id']);
        }
        
        // update customer to contact3 and add responsible to contact4, so contract1 and 2 will have 2 relations
        $response = $tbJson->updateMultipleRecords('Sales', 'Contract',
            array(
                array('name' => '%CUSTOMER-Addressbook_Model_Contact', 'value' => $contact3->getId()),
                array('name' => '%RESPONSIBLE-Addressbook_Model_Contact', 'value' => $contact4->getId()),
                ),
            array(array('field' => 'id', 'operator' => 'in', 'value' => $contract1and2Ids))
        );
        $this->assertEquals(count($response['results']), 2);
        
        $contract1re = $this->_instance->getContract($contract1->getId());
        $contract2re = $this->_instance->getContract($contract2->getId());
        
        $this->assertEquals(2, count($contract1re['relations']));
        $this->assertEquals(2, count($contract2re['relations']));
        
        // remove customer
        $response = $tbJson->updateMultipleRecords('Sales', 'Contract',
            array(array('name' => '%CUSTOMER-Addressbook_Model_Contact', 'value' => '')),
            array(array('field' => 'id', 'operator' => 'in', 'value' => $contract1and2Ids))
        );
        
        $this->assertEquals(2, count($response['results']));
        
        $contract1res = $this->_instance->getContract($contract1->getId());
        $contract2res = $this->_instance->getContract($contract2->getId());
        
        $this->assertEquals(1, count($contract1res['relations']));
        $this->assertEquals(1, count($contract2res['relations']));
    }
    
    /**
     * create some contacts
     * 
     * @param integer $number 
     * @return array
     */
    protected function _createContacts($number = 4)
    {
        $contact1 = new Addressbook_Model_Contact(array(
           'n_given' => 'peter', 'n_family' => 'wolf',
        ));
        
        for ($i = 0; $i < $number; $i++) {
            $contact = clone $contact1;
            switch ($i) {
                case 0:
                    break;
                case 1:
                    $contact->n_given = 'bob';
                    break;
                case 2:
                    $contact->n_given = 'laura';
                    break;
                case 3:
                    $contact->n_given = 'lisa';
                    break;
                default:
                    $contact->n_given = Tinebase_Record_Abstract::generateUID(20);
            }
            $contact = Addressbook_Controller_Contact::getInstance()->create($contact, false);
            $result[] = $contact;
        }
        
        return $result;
    }
    
    /**
     * set relations for contract
     * 
     * @param array|Sales_Model_Contract $contract
     * @param array $contacts
     * @param string $type
     */
    protected function _setContractRelations($contract, $contacts, $type = 'PARTNER')
    {
        $relationData = array();
        foreach ($contacts as $contact) {
            $relationData[] = array(
                'related_degree' => 'sibling',
                'related_degree' => 'sibling',
                'related_model' => 'Addressbook_Model_Contact',
                'related_backend' => 'Sql',
                'related_id' => $contact->getId(),
                'type' => $type
            );
        }
        $contractId = ($contract instanceof Sales_Model_Contract) ? $contract->getId() : $contract['id'];
        Tinebase_Relations::getInstance()->setRelations('Sales_Model_Contract', 'Sql', $contractId, $relationData);
    }

    /**
     * try to get an empty contract
     */
    public function testGetEmptyContract()
    {
        $contractData = $this->_instance->getContract(0);

        $this->assertEquals(Sales_Controller_Contract::getSharedContractsContainer()->getId(), $contractData['container_id']['id']);
    }

    /**
     * try to get a contract
     */
    public function testSearchContracts()
    {
        Tinebase_Config::getInstance()->get(Tinebase_Config::FULLTEXT)->{Tinebase_Config::FULLTEXT_QUERY_FILTER} = true;

        // create
        $contract = $this->_getContract();
        $contractData = $this->_instance->saveContract($contract->toArray());

        $this->_deleteContracts = array($contractData['id']);
        Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        // search & check
        $search = $this->_instance->searchContracts($this->_getFilter(), $this->_getPaging());
        self::assertGreaterThan(0, $search['totalcount']);
        self::assertEquals($contract->title, $search['results'][0]['title']);
        self::assertEquals(1, $search['totalcount']);
    }

    /**
     * try to get a contract by product
     */
    public function testSearchContractsByProduct()
    {
        // create & add aggregate
        $prodTest = new Sales_ProductControllerTest();
        $product = $prodTest->testCreateProduct();

        $contract = $this->_getContract();
        $contract->products = array(
            array(
                'product_id' => $product->getId(),
                'quantity' => 1,
                'interval' => 1,
                'billing_point' => 1,
            ),
        );
        $contractData = $this->_instance->saveContract($contract->toArray());

        // search & check
        $filter = $this->_getFilter($contract->title);
        $filter[] = array('field' => 'products', 'operator' => 'contains', 'value' => 'product');
        $search = $this->_instance->searchContracts($filter, $this->_getPaging());
        $this->assertEquals($contract->title, $search['results'][0]['title']);
        $this->assertEquals(1, $search['totalcount']);

        $result = $this->_instance->searchProductAggregates([], null);
        $this->assertGreaterThan(0, count($result['results']));
        $this->assertArrayHasKey('product_id', $result['results'][0]);
        $this->assertIsArray($result['results'][0]['product_id']);
        $this->assertArrayHasKey('name', $result['results'][0]['product_id']);
        $this->assertIsArray($result['results'][0]['product_id']['name']);
    }

    /**
     * testSearchActiveAndInactiveContracts
     */
    public function testSearchActiveAndInactiveContracts()
    {
        $contract = $this->_getContract('phpunit contract', 'blabla', [
            // inactive contract
            'end_date' => Tinebase_DateTime::now()->subDay(1)->toString()
        ]);
        $this->_instance->saveContract($contract->toArray());

        // search & check
        $filter = $this->_getFilter($contract->title);
        $filter[] = array('field' => 'end_date', 'operator' => 'before', 'value' => Tinebase_Model_Filter_Date::DAY_THIS);
        $search = $this->_instance->searchContracts($filter, $this->_getPaging());
        $this->assertEquals(1, $search['totalcount'], 'inactive contract should be found');
        $this->assertEquals($contract->title, $search['results'][0]['title']);

        $filter = $this->_getFilter($contract->title);
        $filter[] = array('field' => 'end_date', 'operator' => 'after', 'value' => Tinebase_Model_Filter_Date::DAY_LAST);
        $search = $this->_instance->searchContracts($filter, $this->_getPaging());
        $this->assertEquals(0, $search['totalcount'], 'inactive contract should not be found');
    }

    /**
     * assert products filter
     */
    public function testContractFilterModel()
    {
        $config = Sales_Model_Contract::getConfiguration();
        $filterModel = $config->getFilterModel();
        $this->assertTrue(isset($filterModel['_filterModel']['customer']), 'customer not found in filter model: ' . print_r($filterModel, true));
        $this->assertTrue(isset($filterModel['_filterModel']['products']), 'products not found in filter model: ' . print_r($filterModel, true));
    }

    public function testSearchEmptyDateTimeFilter()
    {
        Tinebase_Config::getInstance()->get(Tinebase_Config::FULLTEXT)->{Tinebase_Config::FULLTEXT_QUERY_FILTER} = true;

        // create
        $contract = $this->_getContract();
        $contractData = $this->_instance->saveContract($contract->toArray());

        $this->_deleteContracts = array($contractData['id']);
        Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        $filter = $this->_getFilter();
        $filter[] = array('field' => 'end_date', 'operator' => 'equals', 'value' => '');

        $result = $this->_instance->searchContracts($filter, $this->_getPaging());

        $this->assertEquals(1, $result['totalcount']);
        $this->assertEquals('', $result['filter'][1]['value']);
    }

    /**
     * testSearchCustomers
     *
     * search and resolve
     */
    public function testSearchCustomers()
    {
        $customerController = Sales_Controller_Customer::getInstance();
        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($this->_personas['sclever']->getId());
        $i = 0;
        
        while ($i < 104) {
            $customerController->create(new Sales_Model_Customer([
                'name' => Tinebase_Record_Abstract::generateUID(),
                'cpextern_id' => $contact->getId(),
                'bic' => 'SOMEBIC',
            ]));
            $i++;
        }
        $result = $this->_instance->searchCustomers([
            ['field' => 'bic', 'operator' => 'equals', 'value' => 'SOMEBIC']
        ], array('limit' => 50));
        
        $this->assertEquals(50, count($result['results']));
        $this->assertEquals(104, $result['totalcount']);
        $firstcustomer = $result['results'][0];
        $this->assertTrue(is_array($firstcustomer['created_by']), 'creator not resolved: '
            . print_r($firstcustomer, true));
        $this->assertTrue(is_array($firstcustomer['cpextern_id']), 'cpextern_id not resolved: '
            . print_r($firstcustomer, true));
        $this->assertTrue(
            $firstcustomer['cpextern_id']['created_by'] === null
                || is_array($firstcustomer['cpextern_id']['created_by']),
            'cpextern_id creator not resolved: ' . print_r($firstcustomer, true));
    }
    
    /**
     * test product json api
     */
    public function testAddGetSearchDeleteProduct()
    {
        $product = $this->_testSimpleRecordApi('Product', null, null, true, [
            'name' => [[
                Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                Sales_Model_ProductLocalization::FLD_TEXT => 'test name',
            ]],
            'description' => [[
                Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                Sales_Model_ProductLocalization::FLD_TEXT => 'test desc',
            ]],
            'price' => 10000,
            'default_sorting' => '',
        ], false);
        $this->assertArrayHasKey(Sales_Model_ProductLocalization::FLD_RECORD_ID, $product['name'][0]);
        $this->assertSame($product['id'], $product['name'][0][Sales_Model_ProductLocalization::FLD_RECORD_ID]);
    }
    
    /**
     * save product with note
     * 
     * @return array
     */
    protected function _addProduct($withNote = TRUE)
    {
        $product    = $this->_getProduct();
        $productData = $product->toArray();
        if ($withNote) {
            $note = array(
                'note' => 'phpunit test note',
            );
            $productData['notes'] = array($note);
        }
        $savedProduct = $this->_instance->saveProduct($productData);
        
        return $savedProduct;
    }

    /**
     * testGetRegistryData (shared contracts container)
     */
    public function testGetRegistryData()
    {
        $data = $this->_instance->getRegistryData();

        $this->assertTrue(isset($data['defaultContractContainer']));
        $this->assertTrue(isset($data['defaultContractContainer']['path']));
        $this->assertTrue(isset($data['defaultContractContainer']['account_grants']));
        $this->assertTrue(is_array($data['defaultContractContainer']['account_grants']));
    }

    public function testSMD()
    {
        $this->markTestSkipped('doesnt work running with all tests, locally it works');
        unset($_REQUEST['method']);
        $smd = Tinebase_Frontend_Http::getServiceMap();
        $this->assertArrayHasKey('services', $smd);
        $this->assertArrayHasKey('Sales.getDocument_Offer', $smd['services'], print_r($smd['services'], true));
    }

    /**
     * testNoteConcurrencyManagement
     * 
     * @see 0006278: concurrency conflict when saving product
     */
    public function testNoteConcurrencyManagement()
    {
        $savedProduct = $this->_addProduct(FALSE);
        $savedProduct['notes'][] = array(
            'note' => 'another phpunit test note',
        );
        $savedProduct = $this->_instance->saveProduct($savedProduct);
        
        $savedProduct['name'][0][Sales_Model_ProductLocalization::FLD_TEXT] = 'changed name';
        $savedProductNameChanged = $this->_instance->saveProduct($savedProduct);
        
        $savedProductNameChanged['name'][0][Sales_Model_ProductLocalization::FLD_TEXT] = 'PHPUnit test product';
        $savedProductNameChangedAgain = $this->_instance->saveProduct($savedProductNameChanged);
        
        $this->assertEquals('PHPUnit test product', $savedProductNameChangedAgain['name'][0][Sales_Model_ProductLocalization::FLD_TEXT]);
    }

    public function testSubProducts()
    {
        $product = $this->_getProduct();
        $subProduct1 = $this->_getProduct();
        $subProduct1->name[0][Sales_Model_ProductLocalization::FLD_TEXT] = 'sub1';
        $savedSubProduct1 = $this->_instance->saveProduct($subProduct1->toArray());
        $subMapping1 = new Sales_Model_SubProductMapping([
            Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $savedSubProduct1,
            Sales_Model_SubProductMapping::FLD_SHORTCUT => 'shoo',
            Sales_Model_SubProductMapping::FLD_QUANTITY => 1,
        ], true);
        $subProduct2 = $this->_getProduct();
        $subProduct2->name[0][Sales_Model_ProductLocalization::FLD_TEXT] = 'sub2';
        $savedSubProduct2 = $this->_instance->saveProduct($subProduct2->toArray());
        $subMapping2 = new Sales_Model_SubProductMapping([
            Sales_Model_SubProductMapping::FLD_PRODUCT_ID => $savedSubProduct2,
            Sales_Model_SubProductMapping::FLD_SHORTCUT => 'lorem',
            Sales_Model_SubProductMapping::FLD_QUANTITY => 2,
        ], true);

        $product->{Sales_Model_Product::FLD_SUBPRODUCTS} = [
            $subMapping1->toArray(),
            $subMapping2->toArray(),
        ];

        $savedProduct = $this->_instance->saveProduct($product->toArray());
        $this->assertArrayHasKey(Sales_Model_Product::FLD_SUBPRODUCTS, $savedProduct);
        $this->assertCount(2, $savedProduct[Sales_Model_Product::FLD_SUBPRODUCTS]);
        $this->assertArrayHasKey(Sales_Model_SubProductMapping::FLD_PRODUCT_ID, $savedProduct[Sales_Model_Product::FLD_SUBPRODUCTS][0]);
        $this->assertArrayHasKey(Sales_Model_Product::FLD_NAME, $savedProduct[Sales_Model_Product::FLD_SUBPRODUCTS][0][Sales_Model_SubProductMapping::FLD_PRODUCT_ID]);
        $this->assertCount(1, $savedProduct[Sales_Model_Product::FLD_SUBPRODUCTS][0][Sales_Model_SubProductMapping::FLD_PRODUCT_ID][Sales_Model_Product::FLD_NAME]);
    }
    
    /**
     * testCustomerConcurrencyManagement
     */
    public function testCustomerConcurrencyManagement()
    {
        $customer = $this->_instance->saveCustomer(array(
            'name'      => Tinebase_Record_Abstract::generateUID(),
        ));
        
        $customer['postal']['prefix1'] = 'test';
        $customer = $this->_instance->saveCustomer($customer);
        $this->assertEquals(1, $customer['postal']['seq']);
        
        $customer['postal']['prefix1'] = 'test test';
        // js client does not send adr_seq
        unset($customer['postal']['seq']);
        $updatedCustomer = $this->_instance->saveCustomer($customer);
        $this->assertEquals('test test', $updatedCustomer['postal']['prefix1']);
        $this->assertEquals(2, $updatedCustomer['postal']['seq']);
    }

    public function testSaveCustomerAndCreateInvoiceAddress()
    {
        $customer = $this->_instance->saveCustomer(array(
            'name'      => Tinebase_Record_Abstract::generateUID(),
            'postal' => [
                'street' => '11212stree',
                'postalcode' => '1111',
                'locality' => '1dscscsd',
            ]
        ));

        // assert invoice address (same as postal address)
        self::assertTrue(is_array($customer['billing']), print_r($customer, true));
        self::assertCount(1, $customer['billing'], print_r($customer, true));
    }
    
    /**
     * testSaveContractWithManyRelations
     * 
     * @group longrunning
     * 
     * @see 0008586: when saving record with too many relations, modlog breaks
     * @see 0008712: testSaveContractWithManyRelations test lasts too long
     * @see 0009152: saving of record fails because of too many relations
     */
    public function testSaveContractWithManyRelations()
    {
        $contractArray = $this->testAddContract();
        $contacts = $this->_createContacts(500);
        $this->_setContractRelations($contractArray, $contacts);
        
        sleep(1);
        $updatedContractArray = $this->_instance->getContract($contractArray['id']);
        $updatedAgainContractArray = $this->_instance->saveContract($updatedContractArray);
        
        $this->assertEquals(500, count($updatedAgainContractArray['relations']));
        $this->assertTrue(empty($updatedAgainContractArray['relations'][0]['last_modified_time']), 'relation changed');
        
        $updatedAgainContractArray['relations'][0]['related_backend'] = 'sql';
        $updatedAgainContractArray['relations'][0]['related_record']['adr_one_locality'] = 'örtchen';
        $updatedAgainContractArray = $this->_instance->saveContract($updatedAgainContractArray);
        $this->assertEquals(500, count($updatedAgainContractArray['relations']));
    }

    public function testApplicableBoilerplates()
    {
        $customer = $this->_instance->saveCustomer(array(
            'name'      => Tinebase_Record_Abstract::generateUID(),
        ));

        $ctrl = Sales_Controller_Boilerplate::getInstance();

        $boilerDefault = $ctrl->create(Sales_BoilerplateControllerTest::getBoilerplate());

        $boilerDate = clone $boilerDefault;
        $boilerDate->setId(null);
        $boilerDate->{Sales_Model_Boilerplate::FLD_FROM} = Tinebase_DateTime::now()->addDay(1);
        $boilerDate = $ctrl->create($boilerDate);

        $boilerCustomer = clone $boilerDefault;
        $boilerCustomer->setId(null);
        $boilerCustomer->{Sales_Model_Boilerplate::FLD_CUSTOMER} = $customer['id'];
        $boilerCustomer = $ctrl->create($boilerCustomer);

        $result = $this->_instance->getApplicableBoilerplates(Sales_Model_Document_Offer::class);
        $this->assertCount(1, $result['results']);
        $this->assertSame($boilerDefault->getId(), $result['results'][0]['id']);

        $result = $this->_instance->getApplicableBoilerplates(Sales_Model_Document_Offer::class, null, $customer['id']);
        $this->assertCount(1, $result['results']);
        $this->assertSame($boilerCustomer->getId(), $result['results'][0]['id']);

        $result = $this->_instance->getApplicableBoilerplates(Sales_Model_Document_Offer::class, Tinebase_DateTime::now()->addDay(5));
        $this->assertCount(1, $result['results']);
        $this->assertSame($boilerDate->getId(), $result['results'][0]['id']);
    }
    
    /************ protected helper funcs *************/

    /**
     * get contract
     *
     * @param $title
     * @param $desc
     * @param $additionaldata
     * @return Sales_Model_Contract
     */
    protected function _getContract($title = 'phpunit contract', $desc = 'blabla', $additionaldata = [])
    {
        return new Sales_Model_Contract(array_merge([
            'title'         => $title,
            'description'   => $desc,
        ], $additionaldata), TRUE);
    }

    /**
     * get paging
     *
     * @return array
     */
    protected function _getPaging()
    {
        return array(
            'start' => 0,
            'limit' => 50,
            'sort' => 'number',
            'dir' => 'ASC',
        );
    }

    /**
     * get filter
     *
     * @param string $search
     * @return array
     */
    protected function _getFilter($search ='blabla' )
    {
        return array(
            array('field' => 'query', 'operator' => 'contains', 'value' => $search),
        );
    }

    /**
     * get product
     *
     * @return Sales_Model_Product
     */
    protected function _getProduct()
    {
        return new Sales_Model_Product(array(
            'name'          => [[
                Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                Sales_Model_ProductLocalization::FLD_TEXT => 'PHPUnit test product',
            ]],
            'price'         => 10000,
            'description'   => [[
                Sales_Model_ProductLocalization::FLD_LANGUAGE => 'en',
                Sales_Model_ProductLocalization::FLD_TEXT => 'test product description',
            ]],
        ));
    }

    /**
     * get product filter
     *
     * @return array
     */
    protected function _getProductFilter()
    {
        return array(
            array('field' => 'query',           'operator' => 'contains',       'value' => 'PHPUnit'),
        );
    }
    
    /**
     * tests CostCenter CRUD Methods
     */
    public function testAllCostCenterMethods()
    {
        $name = Tinebase_Record_Abstract::generateUID(10);
        $number = Tinebase_DateTime::now()->getTimestamp();

        $this->_instance = new Tinebase_Frontend_Json();
        $cc = $this->_instance->saveCostCenter(
            array('number' => $number, 'name' => $name)
        );
        
        $this->assertEquals(40, strlen($cc['id']));
        
        $cc = $this->_instance->getCostCenter($cc['id']);
        
        $this->assertEquals($number, $cc['number']);
        $this->assertEquals($name, $cc['name']);
        
        $cc['name'] = $cc['name'] . '_unittest';
        $cc['number'] = $number - 5000;
        
        $cc = $this->_instance->saveCostCenter($cc);
        
        $this->assertEquals($name . '_unittest', $cc['name']);
        $this->assertEquals($number - 5000, $cc['number']);
        
        $accountId = Tinebase_Core::getUser()->getId();

        $this->assertEquals($accountId, $cc['created_by']['accountId']);
        $this->assertEquals($accountId, $cc['last_modified_by']['accountId']);
        $this->assertEquals(NULL, $cc['deleted_by']);
        $this->assertEquals(NULL, $cc['deleted_time']);
        $this->assertEquals(2, $cc['seq']);
        $this->assertEquals(0, $cc['is_deleted']);
        
        $ccs = $this->_instance->searchCostCenters(array(array('field' => 'name', 'operator' => 'equals', 'value' => $name . '_unittest')), array());
        
        $this->assertEquals(1, $ccs['totalcount']);
        $this->assertEquals($name . '_unittest', $ccs['results'][0]['name']);
        
        $this->_instance->deleteCostCenters($cc['id']);
        
        $ccs = $this->_instance->searchCostCenters(array(array('field' => 'number', 'operator' => 'equals', 'value' => $number - 5000)), array());
        
        $this->assertEquals(0, $ccs['totalcount']);
        
        $be = Tinebase_Controller_CostCenter::getInstance()->getBackend();
        $be->setModlogActive(FALSE);

        $result = $be->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_CostCenter::class, [
            ['field' => 'number', 'operator' => 'equals', 'value' => $number - 5000]
        ]));
        
        $this->assertEquals(1, $result->count());
        $this->assertEquals(1, $result->getFirstRecord()->is_deleted);
    }
    
    /**
     * @see https://forge.tine20.org/mantisbt/view.php?id=8840
     */
    public function testRelationConstraintsOwnSide()
    {
        $contract = Sales_Controller_Contract::getInstance()->create($this->_getContract());
    
        list($contact1, $contact2, $contact3, $contact4) = $this->_createContacts();
        
        $this->expectException('Tinebase_Exception_InvalidRelationConstraints');
        
        $this->_setContractRelations($contract, array($contact1, $contact2), 'CUSTOMER');
    }
    
    /**
     * @see https://forge.tine20.org/mantisbt/view.php?id=8840
     */
    public function testRelationConstraintsOtherSide()
    {
        $contract = Sales_Controller_Contract::getInstance()->create($this->_getContract());
        
        list($contact1, $contact2, $contact3, $contact4) = $this->_createContacts(4);
        
        $this->_setContractRelations($contract, array($contact1), 'RESPONSIBLE');

        unset($contact1->relations);
        Addressbook_Controller_Contact::getInstance()->update($contact1);
        $contact1 = Addressbook_Controller_Contact::getInstance()->get($contact1->getId(), NULL, TRUE);
        $this->assertEquals(1, count($contact1->relations), 'did not find expected relation in contact');
        
        // a partner may be added
        $relation = new Tinebase_Model_Relation(array(
            'related_degree' => 'sibling',
            'own_model'  => 'Addressbook_Model_Contact',
            'own_backend' => 'Sql',
            'own_id' => $contact2->getId(),
            'related_degree' => 'sibling',
            'related_model' => 'Sales_Model_Contract',
            'related_backend' => 'Sql',
            'related_id' => $contract->getId(),
            'type' => 'PARTNER'
        ));
        
        $contact2->relations = array($relation);

        $contact2 = Addressbook_Controller_Contact::getInstance()->update($contact2);
        $contact2 = Addressbook_Controller_Contact::getInstance()->get($contact2->getId(), NULL, TRUE);
        $this->assertEquals(1, count($contact2->relations));
        
        // a second partner may be added also
        $relation = new Tinebase_Model_Relation(array(
            'related_degree' => 'sibling',
            'own_model'  => 'Addressbook_Model_Contact',
            'own_backend' => 'Sql',
            'own_id' => $contact3->getId(),
            'related_degree' => 'sibling',
            'related_model' => 'Sales_Model_Contract',
            'related_backend' => 'Sql',
            'related_id' => $contract->getId(),
            'type' => 'PARTNER'
        ));
        
        $contact3->relations = array($relation);
        Addressbook_Controller_Contact::getInstance()->update($contact3);
        $contact3 = Addressbook_Controller_Contact::getInstance()->get($contact3->getId(), NULL, TRUE);
        $this->assertEquals(1, count($contact3->relations));
        
        $contract = Sales_Controller_Contract::getInstance()->get($contract->getId(), NULL, TRUE);
        $this->assertEquals(3, count($contract->relations));

        // but a second responsible must not be added
        $relation = new Tinebase_Model_Relation(array(
            'related_degree' => 'sibling',
            'own_model'  => 'Addressbook_Model_Contact',
            'own_backend' => 'Sql',
            'own_id' => $contact4->getId(),
            'related_degree' => 'sibling',
            'related_model' => 'Sales_Model_Contract',
            'related_backend' => 'Sql',
            'related_id' => $contract->getId(),
            'type' => 'RESPONSIBLE'
        ));
        
        $contact4->relations = array($relation);
        
        $this->expectException('Tinebase_Exception_InvalidRelationConstraints');
        $contact4 = Addressbook_Controller_Contact::getInstance()->update($contact4);
    }
    
    /**
     * tests if a product interval of 36 is possible and if an empty string gets converted to null
     */
    public function testContract()
    {
        $product = Sales_Controller_Product::getInstance()->create($this->_getProduct());
        $contract = array('id' => NULL, 'number' => 1, 'title' => 'test123', 'products' => array(array(
            'product_id' => $product->getId(),
            'interval' => 36,
            'billing_point' => 'begin',
            'quantity' => ''
        )));
        
        $contract = $this->_instance->saveContract($contract);
        
        $this->assertEquals(NULL, $contract['products'][0]['quantity']);
        $this->assertEquals(36, $contract['products'][0]['interval']);
    }

    /**
     * @see 0011494: activate advanced search for contracts (customers, ...)
     */
    public function testAdvancedContractsSearch()
    {
        Tinebase_Config::getInstance()->get(Tinebase_Config::FULLTEXT)->{Tinebase_Config::FULLTEXT_QUERY_FILTER} = true;

        $title = Tinebase_Record_Abstract::generateUID(20);
        Tinebase_Core::getPreference()->setValue(Tinebase_Preference::ADVANCED_SEARCH, true);
        $contract = Sales_Controller_Contract::getInstance()->create($this->_getContract($title));
        list($contact1) = $this->_createContacts(1);
        $this->_setContractRelations($contract, array($contact1), 'RESPONSIBLE');
        $result = $this->_instance->searchContracts($this->_getFilter('wolf'), array());

        $this->assertEquals(1, $result['totalcount'], 'should find contract of customer person Peter Wolf');

        // test notcontains
        $contract2 = Sales_Controller_Contract::getInstance()->create($this->_getContract($title));

        $this->_deleteContracts = array($contract->getId(), $contract2->getId());

        Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);

        $result = $this->_instance->searchContracts($this->_getFilter(), array());
        $this->assertEquals(2, $result['totalcount'], 'should find 2 contracts');

        // search with notcontains
        $search = $this->_instance->searchContracts(array(
            array('field' => 'query', 'operator' => 'notcontains', 'value' => 'wolf'),
            array('field' => 'title', 'operator' => 'equals', 'value' => $title),
        ), $this->_getPaging());

        $this->assertEquals(1, $search['totalcount']);
        $this->assertEquals($contract2->title, $search['results'][0]['title']);

        // search with notcontains
        $search = $this->_instance->searchContracts(array(
            array('field' => 'query', 'operator' => 'notcontains', 'value' => 'sheeeeep'),
            array('field' => 'title', 'operator' => 'equals', 'value' => $title),
        ), $this->_getPaging());
        
        $this->assertEquals(2, $search['totalcount']);
    }

    public function testExportPurchaseInvoiceToDatev()
    {
        Sales_Config::getInstance()->set(Sales_Config::DATEV_RECIPIENT_EMAILS_PURCHASE_INVOICE, [Tinebase_Core::getUser()->accountEmailAddress]);
        
        $pit = new Sales_PurchaseInvoiceTest();
        $pit->setUp();
        $invoice = $pit->createPurchaseInvoice();
        //insert attachment to invoice
        $path = Tinebase_TempFile::getTempPath();
        file_put_contents($path, 'testAttachmentData');
        $invoice = Sales_Controller_PurchaseInvoice::getInstance()->get($invoice['id']);
        $invoice->attachments = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', [
            [
                'name'      => 'testAttachmentData.txt',
                'tempFile'  => Tinebase_TempFile::getInstance()->createTempFile($path),
            ]
        ], true);
        $invoice = Sales_Controller_PurchaseInvoice::getInstance()->update($invoice);
        //feed test invoice data
        Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($invoice);
        $attachments = $invoice->attachments;
        $invoiceData[$invoice['id']] = $attachments->id;
        $this->_instance->exportInvoicesToDatevEmail('PurchaseInvoice', $invoiceData);
        //assert acitionLog and decode data
        $actionLog = Tinebase_ControllerTest::assertActionLogEntry(Tinebase_Model_ActionLog::TYPE_DATEV_EMAIL, 1);
        $actionLog = $actionLog->getFirstRecord();
        $data = json_decode($actionLog->data, true);
        
        $invoice = Sales_Controller_PurchaseInvoice::getInstance()->get($invoice['id']);
        $this->assertEquals($invoice['last_datev_send_date'], $actionLog->datetime, 'invoice datev sent date should be the same as action log datetime');
        $this->assertStringContainsString('testAttachmentData.txt', $data['attachments'][0], 'attachement is invalid');
    }
}

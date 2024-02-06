<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Sales_CustomersTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new \PHPUnit\Framework\TestSuite('Tine 2.0 Sales Controller Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }
    
    protected $_contactController;
    protected $_contractController;
    protected $_json;
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        $this->_contactController = Addressbook_Controller_Contact::getInstance();
        $this->_contractController = Sales_Controller_Contract::getInstance();
        $this->_json = new Sales_Frontend_Json();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
{
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
    
    /*
     * returns relation
    */
    protected function _getRelation($contract, $ipnet) {
        $r = new Tinebase_Model_Relation();
        $ra = array(
            'own_model' => 'Sales_Model_Coustomer',
            'own_backend' => 'Sql',
            'own_id' => $ipnet->getId(),
            'related_degree' => 'sibling',
            'name' => 'phpunit test',
            'related_model' => 'Sales_Model_Contract',
            'related_backend' => 'Sql',
            'related_id' => $contract->getId(),
            'type' => 'CONTRACT');
        $r->setFromArray($ra);
        return $r;
    }
    
    /**
     * @param Tinebase_Model_Container|null $adbContainer
     * @return array
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _createCustomer(?Tinebase_Model_Container $adbContainer = null): array
    {
        if (! $adbContainer) {
            $adbContainer = Tinebase_Container::getInstance()->getSharedContainer(
                Tinebase_Core::getUser()->getId(),
                Addressbook_Model_Contact::class,
                'WRITE'
            )->getFirstRecord();
        }
        
        $containerContracts = Tinebase_Container::getInstance()->getSharedContainer(
            Tinebase_Core::getUser()->getId(),
            Sales_Model_Contract::class,
            'WRITE'
        );
        
        $contact1 = $this->_contactController->create(new Addressbook_Model_Contact(
            array('n_given' => 'Yiting', 'n_family' => 'Huang', 'container_id' => $adbContainer->getId()))
        );
        $contact2 = $this->_contactController->create(new Addressbook_Model_Contact(
            array('n_given' => 'Hans Friedrich', 'n_family' => 'Ochs', 'container_id' => $adbContainer->getId()))
        );
        
        $contract = $this->_contractController->create(new Sales_Model_Contract(
            array('number' => '123', 'title' => 'Testing', 'description' => 'test123', 'container_id' => $containerContracts->getId())
        ));
        
        $customerData = array(
            'name' => 'Worldwide Electronics International',
            'cpextern_id' => $contact1->getId(),
            'cpintern_id' => $contact2->getId(),
            'number'      => 4294967,
        
            'iban'        => 'CN09234098324098234598',
            'bic'         => '0239580429570923432444',
            'url'         => 'http://wwei.cn',
            'vatid'       => '239rc9mwqe9c2q',
            'credit_term' => '30',
            'currency'    => 'EUR',
            'curreny_trans_rate' => 7.034,
            'discount'    => 12.5,
        
            'postal' => [
                'prefix1' => 'no prefix 1',
                'prefix2' => 'no prefix 2',
                'street' => 'Mao st. 2000',
                'postalcode' => '1',
                'locality' => 'Shanghai',
                'region' => 'Shanghai',
                'countryname' => 'China',
                'pobox'   => '7777777',
            ],
        
            'billing' => array(array(
                // setting id here (id is needed in fe store)
                'id' => '1406708670499',
                'prefix1' => 'no prefix1',
                'prefix2' => 'no prefix2',
                'street' => 'Mao st. 1',
                'postalcode' => '2345425',
                'locality' => 'Shenzen',
                'region' => 'Sichuan',
                'countryname' => 'China',
                'pobox'   => '999999999',
                'type' => 'billing',
                'relations' => array(
                    array(
                        'own_model' => 'Sales_Model_Address',
                        'own_backend' => 'Sql',
                        'related_degree' => 'sibling',
                        'remark' => 'phpunit test',
                        'related_model' => 'Sales_Model_Contract',
                        'related_backend' => 'Sql',
                        'related_id' => $contract->getId(),
                        'type' => 'CONTRACT'
                    )
                )
            )),
            'delivery' => array(array(
                'id' => '1406708670491',
                'prefix1' => 'no prefix 1',
                'prefix2' => 'no prefix 2',
                'street' => 'Mao st. 2',
                'postalcode' => '1',
                'locality' => 'Peking',
                'region' => 'Peking',
                'countryname' => 'China',
                'pobox'   => '888888888',
                'type' => 'delivery'
            ))
        );
        
        return $this->_json->saveCustomer($customerData);
    }
    
    public function testLifecycleCustomer()
    {
        $retVal = $this->_createCustomer();
        
        $this->assertEquals(4294967, $retVal["number"]);
        $this->assertEquals("Worldwide Electronics International", $retVal["name"]);
        $this->assertEquals("http://wwei.cn", $retVal["url"]);
        $this->assertEquals(NULL, $retVal['description']);
        
        $this->assertEquals('Yiting', $retVal['cpextern_id']['n_given']);
        $this->assertEquals('Huang', $retVal['cpextern_id']['n_family']);
        
        $this->assertEquals('Hans Friedrich', $retVal['cpintern_id']['n_given']);
        $this->assertEquals('Ochs', $retVal['cpintern_id']['n_family']);

        // @see: 0009378: create a test for resolving dependent records recursively
        $this->assertEquals('Sales_Model_Contract', $retVal['billing'][0]['relations'][0]['related_model']);
        $this->assertEquals('Testing', $retVal['billing'][0]['relations'][0]['related_record']['title']);

        $this->assertArrayHasKey('fulltext', $retVal['billing'][0]);
        
        // @see: 0009378: create a test for resolving dependent records recursively
        $this->assertEquals('Sales_Model_Contract', $retVal['billing'][0]['relations'][0]['related_model']);
        $this->assertEquals('Testing', $retVal['billing'][0]['relations'][0]['related_record']['title']);
        
        // test billing and delivery addresses get resolved
        $this->assertTrue(is_array($retVal['delivery']));
        $this->assertEquals(1, count($retVal['delivery']));
        $this->assertEquals('Peking', $retVal['delivery'][0]['locality']);
        $this->assertEquals('China', $retVal['delivery'][0]['countryname']);
        
        $this->assertTrue(is_array($retVal['billing']));
        $this->assertEquals(1, count($retVal['billing']));
        $this->assertEquals('Shenzen', $retVal['billing'][0]['locality']);
        $this->assertEquals('China', $retVal['billing'][0]['countryname']);
        
        // delete record (set deleted=1) of customer and assigned addresses
        $this->_json->deleteCustomers(array($retVal['id']));
        
        $customerBackend = new Sales_Backend_Customer();
        $deletedCustomer = $customerBackend->get($retVal['id'], TRUE);
        $this->assertEquals(1, $deletedCustomer->is_deleted);
        
        $addressBackend = new Sales_Backend_Address();
        $deletedAddresses = $addressBackend->getMultipleByProperty($retVal['id'], 'customer_id', TRUE);

        $this->assertEquals(3, $deletedAddresses->count());
        
        foreach($deletedAddresses as $address) {
            $this->assertEquals(1, $address->is_deleted);
        }
        $this->expectException('Tinebase_Exception_NotFound');
        
        return $this->_json->getCustomer($retVal['id']);
    }
    
    /**
     * checks if the number is always set to the correct value
     */
    public function testNumberable()
    {
        $controller = Sales_Controller_Customer::getInstance();
    
        $record = $controller->create(new Sales_Model_Customer(array('name' => 'auto1')));
    
        $this->assertEquals(1, $record->number);
    
        $record = $controller->create(new Sales_Model_Customer(array('name' => 'auto2')));
    
        $this->assertEquals(2, $record->number);
    
        // set number to 4, should return the formatted number
        $record = $controller->create(new Sales_Model_Customer(array('name' => 'manu1', 'number' => 4)));
        $this->assertEquals(4, $record->number);
    
        // the next number should be a number after the manual number
        $record = $controller->create(new Sales_Model_Customer(array('name' => 'auto3')));
        $this->assertEquals(5, $record->number);
    }
    
    /**
     * on search we need the billing address, but not the delivery address
     * used in search combo of the contract edit dialog
     */
    public function testResolvingBillingAddressesOnSearch()
    {
        $customer = $this->_createCustomer();
        
        $this->assertEquals(1, count($customer['billing']));
        $this->assertEquals(1, count($customer['delivery']));
        
        $customers = $this->_json->searchCustomers(array(), array());
        
        $this->assertEquals(1, count($customers['results'][0]['billing']));
        $this->assertFalse(isset($customers['results'][0]['delivery']));
    }
    
    /**
     * tests if the last billing address gets deleted
     */
    public function testDeleteLastBillingAddress()
    {
        $customer = $this->_createCustomer();
        
        $this->assertEquals(1, count($customer['billing']));
        $this->assertEquals(1, count($customer['delivery']));
        
        $customer['billing'] = array();
        $this->_json->saveCustomer($customer);
        
        $customer = $this->_json->getCustomer($customer['id']);
        
        $this->assertTrue(empty($customer['billing']));
    }
    
    /**
     * tests if an exception gets thrown if a address is used as a billing address
     */
    public function testDeleteUsedBillingAddress()
    {
        $customer = $this->_createCustomer();
        
        $containerContracts = Tinebase_Container::getInstance()->getSharedContainer(
            Tinebase_Core::getUser()->getId(),
            Sales_Model_Contract::class,
            'WRITE'
        );
        
        $contract = $this->_contractController->create(new Sales_Model_Contract(array(
            'number' => '123', 
            'title' => 'Testing', 
            'description' => 'test123', 
            'container_id' => $containerContracts->getId(),
            'start_date' => Tinebase_DateTime::now(),
            'billing_address_id' => $customer['billing'][0]['id'],
        )));

        // if the property is set to null, no handling of this dependent records must be done
        $customer['billing'] = NULL;
        $customer = $this->_json->saveCustomer($customer);
        $this->assertEquals(1, count($customer['billing']));
        
        // if the property is set to an empty array, all (in this case the last) dependent record(s) should 
        // be deleted (in this case deleting should fail, because the billing address is used in a contract)
        
        $this->expectException('Sales_Exception_DeleteUsedBillingAddress');
        
        $customer['billing'] = array();
        $this->_json->saveCustomer($customer);
    }

    /**
     * tests setting a debitor number of a billing address
     */
    public function testChangeDebitorNumber()
    {
        $customer = $this->_createCustomer();
        $customer['delivery'] = array();
        $customer = $this->_json->saveCustomer($customer);
        
        $this->assertEquals(1406708670499, $customer['billing'][0]['id']);
        $this->assertEquals(1, count($customer['billing']));
        $this->assertEquals(0, count($customer['delivery']));
        
        $customer = $this->_json->getCustomer($customer['id']);
        $customer['billing'][0]['custom1'] = '4219832435';
        $customer['delivery'] = NULL;
        $customer = $this->_json->saveCustomer($customer);
    
        $this->assertEquals(1406708670499, $customer['billing'][0]['id']);
        $this->assertEquals('4219832435', $customer['billing'][0]['custom1']);
        $this->assertEquals(0, count($customer['delivery']));
    }

    public function testAutoCustomerContactRelation()
    {
        // create contact (in container with $container->xprops()[Sales_Config::XPROP_CUSTOMER_ADDRESSBOOK]
        // create customer
        $container = Tinebase_Container::getInstance()->getSharedContainer(
            Tinebase_Core::getUser()->getId(),
            Addressbook_Model_Contact::class,
            'WRITE'
        )->getFirstRecord();
        $container->xprops()[Sales_Config::XPROP_CUSTOMER_ADDRESSBOOK] = true;
        Tinebase_Container::getInstance()->update($container);
        $customer = $this->_createCustomer();

        // TODO assert special relation (TYPE CONTACTCUSTOMER - see \Sales_Controller::createUpdatePostalAddress)
        self::assertCount(1, $customer['relations']);
    }
}

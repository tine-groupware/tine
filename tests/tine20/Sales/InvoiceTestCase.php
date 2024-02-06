<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 */

/**
 * Base test class for Sales-Invoice
 */
class Sales_InvoiceTestCase extends TestCase
{
    /**
     * holds created customers
     * 
     * @var Tinebase_Record_RecordSet
     */
    protected $_customerRecords = NULL;
    
    /**
    * holds created timesheets
    *
    * @var Tinebase_Record_RecordSet
    */
    protected $_timesheetRecords = NULL;
    
    /**
     * holds created addresses (Sales_Model_Address)
     * 
     * @var Tinebase_Record_RecordSet
     */
    protected $_addressRecords = NULL;
    
    /**
     * holds created costcenters
     * 
     * @var Tinebase_Record_RecordSet
     */
    protected $_costcenterRecords = NULL;
    
    /**
     * holds created contracts
     * 
     * @var Tinebase_Record_RecordSet
     */
    protected $_contractRecords = NULL;
    
    /**
     * holds created products
     * 
     * @var Tinebase_Record_RecordSet
     */
    protected $_productRecords = NULL;
    
    /**
     * holds created timeaccounts
     * 
     * @var Tinebase_Record_RecordSet
     */
    protected $_timeaccountRecords = NULL;
    
    /**
     * holds created timeaccounts
     *
     * @var Tinebase_Record_RecordSet
     */
    protected $_contactRecords = NULL;
    
    /**
     * the referrence date all tests should depend on
     * 
     * @var Tinebase_DateTime
     */
    protected $_referenceDate = NULL;
    
    /**
     * the year part of the reference date
     * 
     * @var string
     */
    protected $_referenceYear = NULL;
    
    /**
     * this is true, if the year of the reference date is a leap year
     * 
     * @var bool
     */
    protected $_isLeapYear = FALSE;
    
    /**
     * holds the last day of each month (january is on the 0 index!)
     * 
     * @var array
     */
    protected $_lastMonthDays = NULL;
    /**
     * 
     * @var Addressbook_Controller_Contact
     */
    protected $_contactController = NULL;
    
    /**
     * @var Sales_Controller_Customer
     */
    protected $_customerController = NULL;
    
    /**
     * @var Sales_Controller_Address
     */
    protected $_addressController = NULL;
    
    /**
     * 
     * @var Tinebase_Controller_CostCenter
     */
    protected $_costcenterController = NULL;
    
    /**
     * 
     * @var Timetracker_Controller_Timesheet
     */
    protected $_timesheetController = NULL;
    
    /**
     *
     * @var Timetracker_Controller_Timeaccount
     */
    protected $_timeaccountController = NULL;
    
    /**
     *
     * @var Sales_Controller_Invoice
     */
    protected $_invoiceController = NULL;
    
    /**
     * 
     * @var Sales_Controller_Contract
     */
    protected $_contractController = NULL;
    
    protected $_sharedContractsContainerId = NULL;
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        Sales_Controller_Contract::getInstance()->deleteByFilter(new Sales_Model_ContractFilter(array()));
        
        $cfg = Sales_Config::getInstance();
        $cfg->set(Sales_Config::AUTO_INVOICE_CONTRACT_INTERVAL, 12);
        
        $this->_invoiceController = Sales_Controller_Invoice::getInstance();
        $this->_invoiceController->deleteByFilter(new Sales_Model_InvoiceFilter(array()));
        
        parent::setUp();
        
        Sales_Controller_Contract::getInstance()->setNumberPrefix();
        Sales_Controller_Contract::getInstance()->setNumberZerofill();
        
        $this->_setReferenceDate();
    }
    
    /**
     * (non-PHPdoc)
     * @see TestCase::tearDown()
     */
    protected function tearDown(): void
{
        parent::tearDown();
        $this->_removeFixtures();
    }
    
    protected function _removeFixtures()
    {
        if ($this->_customerRecords) {
            Sales_Controller_Customer::getInstance()->delete($this->_customerRecords->getId());
        }
        
        if ($this->_contactRecords) {
            $this->_contactController->delete($this->_contactRecords->getId());
        }
        
        if ($this->_addressRecords) {
            $this->_addressController->delete($this->_addressRecords->getId());
        }
        
        if ($this->_contractRecords) {
            Sales_Controller_Contract::getInstance()->delete($this->_contractRecords->getId());
        }
        
        $paController = Sales_Controller_ProductAggregate::getInstance();
        $paController->deleteByFilter(new Sales_Model_ProductAggregateFilter(array()));
        
        $this->_invoiceController = Sales_Controller_Invoice::getInstance();
        $this->_invoiceController->deleteByFilter(new Sales_Model_InvoiceFilter(array()));

        if ($this->_sharedContractsContainerId) {
            try {
                Tinebase_Container::getInstance()->deleteContainer($this->_sharedContractsContainerId, true);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // already removed
            }
        }
    }
    
    protected function _setReferenceDate()
    {
        // set reference date to the 1st january of last year
        $this->_referenceDate = Tinebase_DateTime::now();
        $this->_referenceDate->setTimezone(Tinebase_Core::getUserTimezone());
        $this->_referenceDate->subYear(1);
        $this->_referenceDate->setDate($this->_referenceDate->format('Y'), 1 ,1);
        $this->_referenceDate->setTime(0,0,0);
        $this->_referenceDate->hasTime(false);
        
        $this->_referenceYear = $this->_referenceDate->format('Y');
        $this->_lastMonthDays = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        
        // find out if year is a leap year
        if ((bool)$this->_referenceDate->format('L')) {
            $this->_isLeapYear = TRUE;
            $this->_lastMonthDays[1] = 29;
        }
    }
    
    protected function _createContacts($count = 20)
    {
        // addresses
        $csvFile = dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'tine20'  . DIRECTORY_SEPARATOR . 'Addressbook' . DIRECTORY_SEPARATOR . 'Setup' . DIRECTORY_SEPARATOR . 'DemoData' . DIRECTORY_SEPARATOR . 'out1000.csv';
        
        if (! file_exists($csvFile)) {
            throw new Tinebase_Exception_NotFound('File does not exist: ' . $csvFile);
        }
        $fhcsv = fopen($csvFile, 'r');
        
        $i = 0;
        
        $indexes = fgetcsv($fhcsv);
        
        $this->_contactController = Addressbook_Controller_Contact::getInstance();
        
        $addresses = array();
        
        while ($row = fgetcsv($fhcsv)) {
            if ($i >= $count) {
                break;
            }
        
            foreach($row as $index => $field) {
                if ($indexes[$index] == 'gender') {
                    if ($field == 'male') {
                        $addresses[$i]['salutation'] = 'MR';
                    } else {
                        $addresses[$i]['salutation'] = 'MRS';
                    }
                } else {
                    $addresses[$i][$indexes[$index]] = $field;
                }
            }
        
            $i++;
        }
        fclose($fhcsv);

        $containerName = 'TEST SALES INVOICES';
        $model = Addressbook_Model_Contact::class;
        $type = Tinebase_Model_Container::TYPE_SHARED;
        try {
            $container = Tinebase_Container::getInstance()->getContainerByName($model, $containerName, $type);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $container = Tinebase_Container::getInstance()->addContainer(
                new Tinebase_Model_Container(
                    array(
                        'name' => $containerName,
                        'type' => $type,
                        'backend' => 'SQL',
                        'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
                        'model' => $model,
                        'color' => '#000000'
                    )), NULL, TRUE
            );
        }
        
        $this->_sharedContractsContainerId = $container->getId();
        
        $this->_contactRecords = new Tinebase_Record_RecordSet('Addressbook_Model_Contact');
        
        foreach($addresses as $address) {
            $data = array_merge($address, array('container_id' => $this->_sharedContractsContainerId));
            $this->_contactRecords->addRecord($this->_contactController->create(new Addressbook_Model_Contact($data, TRUE), FALSE));
        }
    }
    
    protected function _createCostCenters()
    {
        $this->_costcenterController = Tinebase_Controller_CostCenter::getInstance();
        
        $this->_costcenterRecords = new Tinebase_Record_RecordSet(Tinebase_Model_CostCenter::class);
        $ccs = array('unittest1', 'unittest2', 'unittest3', 'unittest4');
        
        $id = 1;
        
        $allCC = $this->_costcenterController->getAll();
        
        foreach($ccs as $title) {
            $cc = new Tinebase_Model_CostCenter(
                array('name' => $title, 'number' => $id)
            );
            
            try {
                $this->_costcenterRecords->addRecord($this->_costcenterController->create($cc));
            } catch (Tinebase_Exception_Duplicate $e) {
                $this->_costcenterRecords->addRecord($e->getClientRecord());
            } catch (Zend_Db_Statement_Exception $e) {
                $this->_costcenterRecords->addRecord($allCC->filter('number', $id)->getFirstRecord());
            }
            
            $id++;
        }
    }
    
    /**
     * create customers and their addresses
     * 
     * @param number $count
     * @return Tinebase_Record_RecordSet
     */
    protected function _createCustomers($count = 4)
    {
        if (! $this->_contactRecords) {
            // each customer may have 5 contacts
            $this->_createContacts($count * 5);
        }
        $this->_customerController = Sales_Controller_Customer::getInstance();
        $this->_customerRecords = new Tinebase_Record_RecordSet('Sales_Model_Customer');
        $this->_addressController = Sales_Controller_Address::getInstance();
        $this->_addressRecords = new Tinebase_Record_RecordSet('Sales_Model_Address');
        
        $countAll = 0;
        
        // customers
        $customers = array(
            array(
                'name' => 'Customer1',
                'url' => 'www.customer1.de',
                'discount' => 0
            ),
            array(
                'name' => 'Customer2',
                'url' => 'www.customer2.de',
                'discount' => 5
            ),
            array(
                'name' => 'Customer3',
                'url' => 'www.customer3.de',
                'discount' => 10
            ),
            array(
                'name' => 'Customer4',
                'url' => 'www.customer4.de',
                'discount' => 0
            )
        );
        
        $i = 0;
        
        foreach ($customers as $customer) {
            
            if ($countAll == $count) {
                break;
            }
            
            $countAll++;
            
            $customer['cpextern_id'] = $this->_contactRecords->getByIndex($i)->getId();
            $i++;
            $customer['cpintern_id'] = $this->_contactRecords->getByIndex($i)->getId();
            $i++;
            $customer['iban'] = Tinebase_Record_Abstract::generateUID(20);
            $customer['bic'] = Tinebase_Record_Abstract::generateUID(12);
            $customer['credit_term'] = 30;
            $customer['currency'] = 'EUR';
            $customer['currency_trans_rate'] = 1;

            //add default necessary postal data
            $customer['adr_street'] = 'test street';
            $customer['adr_pobox'] = 'test pobox';
            $customer['adr_postalcode'] = 'test postalcode';
            $customer['adr_locality'] ='test locality';
        
            $this->_customerRecords->addRecord($this->_customerController->create(new Sales_Model_Customer($customer)));
        }
        
        foreach($this->_customerRecords as $customer) {
            foreach(array('postal', 'billing', 'delivery') as $type) {
                $caddress = $this->_contactRecords->getByIndex($i);
                $address = new Sales_Model_Address(array(
                    'customer_id' => $customer->getId(),
                    'type'        => $type,
                    'prefix1'     => $caddress->title,
                    'prefix2'     => $caddress->n_fn,
                    'street'      => $caddress->adr_two_street,
                    'postalcode'  => $caddress->adr_two_postalcode,
                    'locality'    => $caddress->adr_two_locality,
                    'region'      => $caddress->adr_two_region,
                    'countryname' => $caddress->adr_two_countryname,
                    'custom1'     => ($type == 'billing') ? Tinebase_Record_Abstract::generateUID(5) : NULL
                ));
        
                $this->_addressRecords->addRecord($this->_addressController->create($address));
        
                $i++;
            }
        }
        
        $this->_customerRecords->sort('name', 'ASC');
        
        return $this->_customerRecords;
    }
    
    /**
     * creates products
     * 
     * @param string $productArray
     */
    protected function _createProducts($productArray = NULL)
    {
        // 1.1.20xx
        $startDate = clone $this->_referenceDate;
        $endDate   = clone $startDate;
        // 1.8.20xx
        $endDate->addMonth(7);
        
        if (! $productArray) $productArray = array(
            array(
                'name' => 'billhalfyearly',
                'description' => 'bill this 2 times a year',
                'price' => '102',
                'accountable' => 'Sales_Model_Product'
            ),
            array(
                'name' => 'billeachquarter',
                'description' => 'bill this each quarter',
                'price' => '102',
                'accountable' => 'Sales_Model_Product'
            ),
            array(
                'name' => 'Hours',
                'description' => 'timesheets',
                'price' => '100',
                'accountable' => 'Timetracker_Model_Timeaccount'
            )
        );
        
        $default = array(
            'manufacturer' => 'Unittest',
            'category' => 'Tine'
        );
        
        $productController = Sales_Controller_Product::getInstance();
        $this->_productRecords = new Tinebase_Record_RecordSet('Sales_Model_Product');
        
        foreach($productArray as $product) {
            if (!is_array($product['name'])) {
                $product['name'] = [['language' => 'en', 'text' => $product['name']]];
            }
            if (!is_array($product['description'])) {
                $product['description'] = [['language' => 'en', 'text' => $product['description']]];
            }
            $p = new Sales_Model_Product(array_merge($product, $default));
            $p->setTimezone('UTC');
            $this->_productRecords->addRecord($productController->create($p));
        }
        (new Tinebase_Record_Expander(Sales_Model_Product::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                Sales_Model_Product::FLD_NAME => [],
                Sales_Model_Product::FLD_DESCRIPTION => [],
            ],
        ]))->expand($this->_productRecords);
    }
    
    /**
     * create contracts, auto add timeaccounts if there are any
     * 
     * @param array $contractData
     * @return Tinebase_Record_RecordSet
     */
    protected function _createContracts($contractData = NULL)
    {
        // 1.1.20xx
        $startDate = clone $this->_referenceDate;
        $endDate   = clone $startDate;
        // 1.8.20xx
        $endDate->addMonth(7);
        
        $this->_contractController = Sales_Controller_Contract::getInstance();
        $container = $this->_contractController->getSharedContractsContainer();
        $this->_sharedContractsContainerId = $container->getId();
        
        if (! $contractData) {
            
            if (! $this->_costcenterRecords) {
                $this->_createCostCenters();
            }
            
            if (! $this->_productRecords) {
                $this->_createProducts();
            }
            
            if (! $this->_customerRecords) {
                $this->_createCustomers();
            }
            if (! $this->_timesheetRecords) {
                $this->_createTimesheets();
            }

            $hoursId = $this->_productRecords->find(function($val) {
                return null !== $val->name->find('text', 'Hours');
            }, null)->getId();

            $contractData = array(
                // 1 invoice should be created from 1.2.2013 - 28.2.2013
                array(
                    'number'       => 1,
                    'title'        => Tinebase_Record_Abstract::generateUID(),
                    'description'  => '1 unittest begin',
                    'container_id' => $this->_sharedContractsContainerId,
                    'billing_address_id' => $this->_addressRecords->filter(
                        'customer_id', $this->_customerRecords->filter(
                            'name', 'Customer1')->getFirstRecord()->getId())->filter(
                                'type', 'billing')->getFirstRecord()->getId(),
                    
                    'start_date' => clone $startDate,
                    'end_date' => NULL,
                    'products' => array(
                        array('start_date' => $startDate, 'end_date' => NULL, 'quantity' => 1, 'interval' => 1, 'billing_point' => 'begin', 'product_id' => $hoursId),
                    )
                ),
            
                // 1 invoice should be created on 1.5 for interval 1.1. - 30.4
                array(
                    'number'       => 2,
                    'title'        => Tinebase_Record_Abstract::generateUID(),
                    'description'  => '2 unittest end',
                    'container_id' => $this->_sharedContractsContainerId,
                    'billing_address_id' => $this->_addressRecords->filter('customer_id', $this->_customerRecords->filter('name', 'Customer2')->getFirstRecord()->getId())->filter('type', 'billing')->getFirstRecord()->getId(),
                    'start_date' => clone $startDate,
                    'end_date' => clone $endDate,
                    'products' => array(
                        array('start_date' => clone $startDate, 'end_date' => clone $endDate, 'quantity' => 1, 'interval' => 4, 'billing_point' => 'end', 'product_id' => $hoursId),
                    )
                ),
                // 2 invoices should be created on 1.5.2013 and 1.10.2013
                array(
                    'number'       => 3,
                    'title'        => Tinebase_Record_Abstract::generateUID(),
                    'description'  => '3 unittest end',
                    'container_id' => $this->_sharedContractsContainerId,
                    'billing_address_id' => $this->_addressRecords->filter('customer_id', $this->_customerRecords->filter('name', 'Customer3')->getFirstRecord()->getId())->filter('type', 'billing')->getFirstRecord()->getId(),
                    'start_date' => clone $startDate,
                    'end_date' => NULL,
                    'products' => array(
                            array('start_date' => clone $startDate, 'end_date' => NULL, 'quantity' => 1, 'interval' => 3, 'billing_point' => 'end', 'product_id' => $hoursId),
                    )
                ),
                // 4 invoices should be created on 1.1.2013, 1.4.2013, 1.7.2013 and 1.12.2014
                array(
                    'number'       => 4,
                    'title'        => Tinebase_Record_Abstract::generateUID(),
                    'description'  => '4 unittest products',
                    'container_id' => $this->_sharedContractsContainerId,
                    'billing_address_id' => $this->_addressRecords->filter('customer_id', $this->_customerRecords->filter('name', 'Customer4')->getFirstRecord()->getId())->filter('type', 'billing')->getFirstRecord()->getId(),
                    // this has an interval of 1 month, but there will be 2 products (6,3 months), so we need 5 invoices (4 in the first year, 1 for the beginning of the second year)
                    'start_date' => clone $startDate,
                    'end_date' => NULL,
                    'products' => array(
                        array('start_date' => clone $startDate, 'end_date' => NULL, 'quantity' => 1, 'interval' => 6, 'billing_point' => 'begin', 'product_id' => $this->_productRecords->find(function($val) {
                            return null !== $val->name->find('text', 'billhalfyearly');
                        }, null)->getId()),
                        array('start_date' => clone $startDate, 'end_date' => NULL, 'quantity' => 1, 'interval' => 3, 'billing_point' => 'begin', 'product_id' => $this->_productRecords->find(function($val) {
                            return null !== $val->name->find('text', 'billeachquarter');
                        }, null)->getId()),
//                         array('quantity' => 1, 'interval' => 1, 'billing_point' => 'begin', 'product_id' => $this->_productRecords->filter('name', 'Hours')->getFirstRecord()->getId()),
                    )
                )
            );
        }
        
        $this->_contractRecords = new Tinebase_Record_RecordSet('Sales_Model_Contract');
        
        $i = 0;
        
        foreach ($contractData as $cd) {
            $costcenter = $this->_costcenterRecords->getByIndex($i);
            $customer   = $this->_customerRecords->getByIndex($i);
            
            if ($this->_timeaccountRecords) {
                $timeaccount = $this->_timeaccountRecords->getByIndex($i);
            }
            
            $i++;
            $contract = new Sales_Model_Contract($cd);
            $contract->setTimezone('UTC');
            
            $contract->relations = array(
                array(
                    'own_model'              => Sales_Model_Contract::class,
                    'own_backend'            => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                    'own_id'                 => NULL,
                    'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                    'related_model'          => Tinebase_Model_CostCenter::class,
                    'related_backend'        => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                    'related_id'             => $costcenter->getId(),
                    'type'                   => 'LEAD_COST_CENTER'
                ),
                array(
                    'own_model'              => Sales_Model_Contract::class,
                    'own_backend'            => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                    'own_id'                 => NULL,
                    'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                    'related_model'          => Sales_Model_Customer::class,
                    'related_backend'        => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                    'related_id'             => $customer->getId(),
                    'type'                   => 'CUSTOMER'
                ),
            );

            if ($this->_timeaccountRecords) {
                $contract->relations = array_merge($contract->relations, array(array(
                    'own_model'              => 'Sales_Model_Contract',
                    'own_backend'            => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                    'own_id'                 => NULL,
                    'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                    'related_model'          => 'Timetracker_Model_Timeaccount',
                    'related_backend'        => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
                    'related_id'             => $timeaccount->getId(),
                    'type'                   => 'TIME_ACCOUNT'
                )));
            }
            
            $this->_contractRecords->addRecord($this->_contractController->create($contract));
        }
        
        return $this->_contractRecords;
    }
    
    /**
     * 
     * @param array $recordData
     * @return Tinebase_Record_RecordSet
     */
    protected function _createTimeaccounts($recordData = NULL)
    {
        $this->_timeaccountRecords = new Tinebase_Record_RecordSet('Timetracker_Model_Timeaccount');
        $this->_timeaccountController = Timetracker_Controller_Timeaccount::getInstance();
        
        if (! $recordData) {
            // ta for customer 1 and 2 is budgeted AND to bill
            foreach($this->_customerRecords as $customer) {
                $this->_timeaccountRecords->addRecord($this->_timeaccountController->create(new Timetracker_Model_Timeaccount(array(
                    'title'         => 'TA-for-' . $customer->name,
                    'description'   => 'blabla',
                    'is_open'       => 1,
                    'status'        => $customer->name == 'Customer4' ? 'billed' : 'to bill',
                    'budget' => $customer->name == 'Customer3' ? null : 100
                ), TRUE)));
            }
        } else {
            foreach($recordData as $taArray) {
                $this->_timeaccountRecords->addRecord($this->_timeaccountController->create(new Timetracker_Model_Timeaccount($taArray, TRUE)));
            }
        }
        
        return $this->_timeaccountRecords;
    }
    
    /**
     * 
     * @param array $recordData
     * @return Tinebase_Record_RecordSet
     */
    protected function _createTimesheets($recordData = NULL)
    {
        $this->_timesheetController = Timetracker_Controller_Timesheet::getInstance();
        
        if (! $this->_timesheetRecords) {
            $this->_timesheetRecords = new Tinebase_Record_RecordSet('Timetracker_Model_Timesheet');
        }
        
        if (! $recordData) {
            if (! $this->_timeaccountRecords) {
                $this->_createTimeaccounts();
            }
            
            $tsDate = clone $this->_referenceDate;
            $tsDate->addMonth(4)->addDay(5);
            
            // this is a ts on 20xx-05-06
            $timesheet = new Timetracker_Model_Timesheet(array(
                'account_id' => Tinebase_Core::getUser()->getId(),
                'timeaccount_id' => $this->_timeaccountRecords->filter('title', 'TA-for-Customer3')->getFirstRecord()->getId(),
                'start_date' => $tsDate,
                'duration' => 105,
                'description' => 'ts from ' . (string) $tsDate,
            ));
            
            $this->_timesheetRecords->addRecord($this->_timesheetController->create($timesheet));
            
            // this is a ts on 20xx-05-07
            $timesheet->id = NULL;
            $timesheet->start_date = $tsDate->addDay(1);
            $timesheet->description = 'ts from ' . (string) $tsDate;
            
            $this->_timesheetRecords->addRecord($this->_timesheetController->create($timesheet));
            
            // this is a ts on 20xx-09-07
            $timesheet->id = NULL;
            $timesheet->start_date = $tsDate->addMonth(4);
            $timesheet->description = 'ts from ' . (string) $tsDate;
            
            $this->_timesheetRecords->addRecord($this->_timesheetController->create($timesheet));
            
            // this is a ts on 20xx-09-08
            $timesheet->id = NULL;
            $timesheet->start_date = $tsDate->addDay(1);
            $timesheet->description = 'ts from ' . (string) $tsDate;
            
            $this->_timesheetRecords->addRecord($this->_timesheetController->create($timesheet));
        } else {
            foreach($recordData as $tsData) {
                $timesheet = new Timetracker_Model_Timesheet($tsData);
                $this->_timesheetRecords->addRecord($this->_timesheetController->create($timesheet));
            }
        }
        
        return $this->_timesheetRecords;
    }
    
    protected function _createFullFixtures()
    {
        $this->_createContacts();
        $this->_createContracts();
        
        $this->_testFullFixtures();
    }
    
    protected function _testFullFixtures()
    {
        $this->assertEquals(4, $this->_customerRecords->count());
        $this->assertEquals(12, $this->_addressRecords->count());
        $this->assertEquals(4, $this->_contractRecords->count());
        $this->assertEquals(20, $this->_contactRecords->count());
    }
}

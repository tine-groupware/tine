<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * 
 */

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Test class for Sales Invoice Controller
 */
class Sales_InvoiceControllerTests extends Sales_InvoiceTestCase
{
    protected $_testUser = NULL;

    protected $sharedTimesheet = null;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        if ($this->_dbIsPgsql() || ! Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_INVOICES_MODULE)) {
            $this->markTestSkipped('0011670: fix Sales_Invoices Tests with postgresql backend');
        }

        parent::setUp();
    }

    /**
     * (non-PHPdoc)
     * @see TestCase::tearDown()
     */
    protected function tearDown(): void
{
        // switch back to admin user
        if ($this->_testUser) {
            Tinebase_Core::set(Tinebase_Core::USER, $this->_testUser);
        }
        
        parent::tearDown();
        
    }
    
    protected function _createFailingContracts()
    {
        // add contract not to bill
        $this->_contractRecords->addRecord($this->_contractController->create(new Sales_Model_Contract(array(
            'number'       => 5,
            'title'        => Tinebase_Record_Abstract::generateUID(),
            'description'  => '5 unittest no auto',
            'container_id' => $this->_sharedContractsContainerId,
            'billing_address_id' => $this->_addressRecords->filter('customer_id', $this->_customerRecords->filter('name', 'Customer3')->getFirstRecord()->getId())->filter('type', 'billing')->getFirstRecord()->getId(),
            'start_date' => $this->_referenceDate,
            'end_date' => NULL,
        ))));
        
        // add contract without customer
        $contract = new Sales_Model_Contract(array(
            'number'       => 6,
            'title'        => Tinebase_Record_Abstract::generateUID(),
            'description'  => '6 unittest auto not possible',
            'container_id' => $this->_sharedContractsContainerId,
            'start_date' => $this->_referenceDate,
            'end_date' => NULL,
            'billing_address_id' => $this->_addressRecords->filter('customer_id', $this->_customerRecords->filter('name', 'Customer3')->getFirstRecord()->getId())->filter('type', 'billing')->getFirstRecord()->getId(),
            'eval_dim_cost_center' => $this->_costcenterRecords->getFirstRecord()->getId(),
        ));
        
        $this->_contractRecords->addRecord($this->_contractController->create($contract));
        
        // add contract without address
        $contract = new Sales_Model_Contract(array(
            'number'       => 7,
            'title'        => Tinebase_Record_Abstract::generateUID(),
            'description'  => '7 unittest auto not possible',
            'container_id' => $this->_sharedContractsContainerId,
            'start_date' => $this->_referenceDate,
            'end_date' => NULL,
        ));
        
        $this->_contractRecords->addRecord($this->_contractController->create($contract));
    }
    
    /**
     * tests auto invoice creation
     */
    public function testFullAutoInvoice()
    {
        $this->markTestSkipped('0010492: fix failing invoices and timetracker tests');
        $this->_createFullFixtures();
        $this->_createFailingContracts();
        
        $this->assertEquals(7, $this->_contractRecords->count());
        
        $date = clone $this->_referenceDate;
        
        $i = 0;
        
        // the whole year, 12 months
        while ($i < 12) {
            $result = $this->_invoiceController->createAutoInvoices($date);
            $date->addMonth(1);
            $i++;
        }
        
        $this->assertEquals(6, count($result['failures']));
        
        $failures = '';
        foreach($result['failures'] as $failure) {
            $failures .= $failure;
        }
        
        $this->assertTrue(strstr($failures, 'no customer') !== FALSE);
        $this->assertTrue(strstr($failures, 'no billing') !== FALSE);
        $this->assertTrue(strstr($failures, 'no costcenter') !== FALSE);
        
        // also add an hour to get the last end
        $date->addHour(1);
        $this->_invoiceController->createAutoInvoices($date);
        $date->addHour(1);
        $this->_invoiceController->createAutoInvoices($date);
        
        $all = $this->_invoiceController->getAll();

        $cc1 = $this->_costcenterRecords->filter('name', 'unittest1')->getFirstRecord();
        $cc2 = $this->_costcenterRecords->filter('name', 'unittest2')->getFirstRecord();
        $cc3 = $this->_costcenterRecords->filter('name', 'unittest3')->getFirstRecord();
        $cc4 = $this->_costcenterRecords->filter('name', 'unittest4')->getFirstRecord();
        
        $all->setTimezone(Tinebase_Core::getUserTimezone());
        
        $customer1Invoices = $all->filter('costcenter_id', $cc1->getId())->sort('start_date');
        $customer2Invoices = $all->filter('costcenter_id', $cc2->getId())->sort('start_date');
        $customer3Invoices = $all->filter('costcenter_id', $cc3->getId())->sort('start_date');
        $customer4Invoices = $all->filter('costcenter_id', $cc4->getId())->sort('start_date');
        
        // customer 1 must have one invoice (timeaccount with budget has been billed the first month)
        $this->assertEquals(1, $customer1Invoices->count(), 'Customer 1 must have 1 invoice!');
        
        // customer 2 must have one invoice (timeaccount with budget has been billed the first time)
        $this->assertEquals(1, $customer2Invoices->count(), 'Customer 2 must have 1 invoice!');
        
        // there are timesheets in 2 intervals, so no empty invoice should be generated
        $this->assertEquals(2, $customer3Invoices->count(), 'Customer 3 must have 2 invoices!');
        
        // there are 2 products, interval 3,6 -> so every quarter in this year and the first of next year must be found
        $this->assertEquals(5, $customer4Invoices->count(), 'Customer 4 must have 5 invoices!');
        
        // test invoice positions
        $allInvoicePositions = Sales_Controller_InvoicePosition::getInstance()->getAll();

        $this->assertEquals(1, $allInvoicePositions->filter('invoice_id', $customer1Invoices->getFirstRecord()->getId())->count());
        $this->assertEquals(1, $allInvoicePositions->filter('invoice_id', $customer2Invoices->getFirstRecord()->getId())->count());
        
        // each invoice should contain 1 timeaccount
        foreach($customer3Invoices as $ci) {
            $this->assertEquals(1, $allInvoicePositions->filter('invoice_id', $ci->getId())->count());
        }
        
        // we need 9,3,9,3,9 invoice positions
        $i = 1;
        foreach($customer4Invoices as $ci) {
            $ip = $allInvoicePositions->filter('invoice_id', $ci->getId());
            $this->assertEquals(($i % 2 == 1) ? 9 : 3, $ip->count());
            $i++;
        }
        
        // contract 1 gets billed at the begin of the period
        $c1IArray = $customer1Invoices->start_date;
        
        $this->assertEquals($this->_referenceYear . '-01-01 00:00:00', $c1IArray[0]->toString());
        
        $c1IArray = $customer1Invoices->end_date;
        $this->assertEquals($this->_referenceYear . '-01-31 23:59:59', $c1IArray[0]->toString());
        
        // contract 2 gets billed at the end of the period, and the second period ends at 1.8.20xx
        $c2IsArray = $customer2Invoices->start_date;
        $c2IeArray = $customer2Invoices->end_date;
        
        $this->assertEquals($this->_referenceYear . '-05-01 00:00:00', $c2IsArray[0]->toString());
        $this->assertEquals($this->_referenceYear . '-05-31 23:59:59', $c2IeArray[0]->toString());
        
        // test correct timesheet handling of customer 3
        $c3IsArray = $customer3Invoices->start_date;
        $c3IeArray = $customer3Invoices->end_date;
        
        $this->assertEquals($this->_referenceYear . '-05-01 00:00:00', $c3IsArray[0]->toString());
        $this->assertEquals($this->_referenceYear . '-05-31 23:59:59', $c3IeArray[0]->toString());
        
        $this->assertEquals($this->_referenceYear . '-09-01 00:00:00', $c3IsArray[1]->toString());
        $this->assertEquals($this->_referenceYear . '-09-30 23:59:59', $c3IeArray[1]->toString());
        
        // test customer 4 having products only
        $c4IsArray = $customer4Invoices->start_date;
        $c4IeArray = $customer4Invoices->end_date;
        
        // should contain billeachquarter & billhalfyearly
        $this->assertEquals($this->_referenceYear . '-01-01 00:00:00', $c4IsArray[0]->toString());
        $this->assertEquals($this->_referenceYear . '-06-30 23:59:59', $c4IeArray[0]->toString());
        
        // should contain billeachquarter
        $this->assertEquals($this->_referenceYear . '-04-01 00:00:00', $c4IsArray[1]->toString());
        $this->assertEquals($this->_referenceYear . '-06-30 23:59:59', $c4IeArray[1]->toString());
        
        // should contain billeachquarter & billhalfyearly
        $this->assertEquals($this->_referenceYear . '-07-01 00:00:00', $c4IsArray[2]->toString());
        $this->assertEquals($this->_referenceYear . '-12-31 23:59:59', $c4IeArray[2]->toString());
        
        // should contain billeachquarter
        $this->assertEquals($this->_referenceYear . '-10-01 00:00:00', $c4IsArray[3]->toString());
        $this->assertEquals($this->_referenceYear . '-12-31 23:59:59', $c4IeArray[3]->toString());
        
        // look if hours of timesheets gets calculated properly
        $c3Invoice = $customer3Invoices->getFirstRecord();
        $filter = new Sales_Model_InvoicePositionFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $c3Invoice->getId())));
        $c3InvoicePositions = Sales_Controller_InvoicePosition::getInstance()->search($filter);
        
        $this->assertEquals(1, $c3InvoicePositions->count());
        $this->assertEquals(3.5, $c3InvoicePositions->getFirstRecord()->quantity);
        
        $invoice = $customer1Invoices->getFirstRecord();
        self::assertEquals(Tinebase_Config::getInstance()->get(Tinebase_Config::SALES_TAX), $invoice->sales_tax, 'invoice sales_tax mismatch');

        $invoice->relations = Tinebase_Relations::getInstance()->getRelations('Sales_Model_Invoice', 'Sql', $invoice->getId())->toArray();
        
        $filter = new Sales_Model_InvoicePositionFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoice['id'])));
        $invoice->positions = Sales_Controller_InvoicePosition::getInstance()->search($filter);
        
        $invoice->cleared = 'CLEARED';
        $invoice = $this->_invoiceController->update($invoice);
        
        // check correct number generation
        $this->assertEquals("R-00001", $invoice->number);
        
        $invoice = $customer2Invoices->getFirstRecord();
        $invoice->relations = Tinebase_Relations::getInstance()->getRelations('Sales_Model_Invoice', 'Sql', $invoice->getId())->toArray();
        $filter = new Sales_Model_InvoicePositionFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoice['id'])));
        $invoice->positions = Sales_Controller_InvoicePosition::getInstance()->search($filter);
        
        $invoice->cleared = 'CLEARED';
        $invoice = $this->_invoiceController->update($invoice);
        
        $this->assertEquals("R-00002", $invoice->number);
        
        // check disallow editing invoice after clearing
        $invoice->credit_term = 20;
        $this->expectException('Sales_Exception_InvoiceAlreadyClearedEdit');
        
        $this->_invoiceController->update($invoice);
    }

    public function testXRechnungAttachment()
    {
        $this->_createFullFixtures();

        $this->_invoiceController->createAutoInvoices($this->_referenceDate);

        $allInvoices = $this->_invoiceController->getAll('start_date', 'DESC');
        echo $allInvoices->count() . PHP_EOL;

        /** @var Sales_Model_Invoice $invoice */
        $invoice = $allInvoices->getFirstRecord();
        $invoice->cleared = 'CLEARED';

//        $invoice->relations = Tinebase_Relations::getInstance()->getRelations(Sales_Model_Invoice::class, 'Sql', $invoice->getId());
        $division = Sales_Controller_Division::getInstance()->get(Sales_Config::getInstance()->{Sales_Config::DEFAULT_DIVISION});
        if ($division->{Sales_Model_Division::FLD_BANK_ACCOUNTS}->count() === 0) {
            $bankAccounts = Tinebase_Controller_BankAccount::getInstance()->getAll();
            if ($bankAccounts->count() === 0) {
                $bankAccounts->addRecord(Tinebase_Controller_BankAccount::getInstance()->create(new Tinebase_Model_BankAccount([
                    Tinebase_Model_BankAccount::FLD_NAME => 'unittest',
                    Tinebase_Model_BankAccount::FLD_BIC => 'unittest',
                    Tinebase_Model_BankAccount::FLD_IBAN => 'unittest',
                ])));
            }
            $division->{Sales_Model_Division::FLD_BANK_ACCOUNTS}->addRecord(new Sales_Model_DivisionBankAccount([
                Sales_Model_DivisionBankAccount::FLD_BANK_ACCOUNT => $bankAccounts->getFirstRecord(),
            ], true));
            Sales_Controller_Division::getInstance()->update($division);
        }

        //Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::VALIDATION_SVC} = 'http://172.118.0.1:3000/ubl';
        Sales_Config::getInstance()->{Sales_Config::EDOCUMENT}->{Sales_Config::VALIDATION_SVC} = 'http://unittest:3000/ubl';

        Sales_EDocument_Service_Validate::$zendHttpClientAdapter = new Zend_Http_Client_Adapter_Test();
        Sales_EDocument_Service_Validate::$zendHttpClientAdapter->setResponse(new Zend_Http_Response(200, [],
            '<?xml version="1.0" encoding="UTF-8"?>
<svrl:schematron-output xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:cn="urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2" xmlns:error="https://doi.org/10.5281/zenodo.1495494#error" xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:sch="http://purl.oclc.org/dsdl/schematron" xmlns:schxslt-api="https://doi.org/10.5281/zenodo.1495494#api" xmlns:schxslt="https://doi.org/10.5281/zenodo.1495494" xmlns:svrl="http://purl.oclc.org/dsdl/svrl" xmlns:ubl-creditnote="urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2" xmlns:ubl-invoice="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" xmlns:ubl="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" xmlns:xs="http://www.w3.org/2001/XMLSchema" title="Schematron Version @xr-schematron.version.full@ - XRechnung @xrechnung.version@ compatible - UBL - Invoice / Creditnote">
   <svrl:ns-prefix-in-attribute-values prefix="cbc" uri="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"/>
</svrl:schematron-output>'));

        try {
            $this->_invoiceController->update($invoice);
        } finally {
            Sales_EDocument_Service_Validate::$zendHttpClientAdapter = null;
        }
    }

    public function testTimeAccountBudget()
    {
        $this->_createFullFixtures();

        $ta1 = $this->_timeaccountController->create(new Timetracker_Model_Timeaccount(array(
            'title'         => 'TA-for-budget1',
            'description'   => 'blabla',
            'is_open'       => 1,
            'status'        => Timetracker_Model_Timeaccount::STATUS_TO_BILL,
            'budget' => 100
        )));
        $ta2 = $this->_timeaccountController->create(new Timetracker_Model_Timeaccount(array(
            'title'         => 'TA-for-budget2',
            'description'   => 'blabla',
            'is_open'       => 1,
            'status'        => Timetracker_Model_Timeaccount::STATUS_NOT_YET_BILLED,
            'budget' => 100
        )));
        $contract = $this->_contractRecords->getFirstRecord();
        $contract->relations = array_merge($contract->relations->toArray(), [[
            'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model'          => 'Timetracker_Model_Timeaccount',
            'related_backend'        => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
            'related_id'             => $ta1->getId(),
            'type'                   => 'TIME_ACCOUNT'
        ],[
        'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model'          => 'Timetracker_Model_Timeaccount',
            'related_backend'        => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
            'related_id'             => $ta2->getId(),
            'type'                   => 'TIME_ACCOUNT'
        ]]);
        $this->_contractController->update($contract);

        $date = clone $this->_referenceDate;
        $date->addMonth(1);

        $this->_invoiceController->createAutoInvoices($date);

        $allInvoices = $this->_invoiceController->getAll('start_date', 'DESC');
        $this->assertEquals(2, $allInvoices->count(), print_r($allInvoices->toArray(), true));
        $invoice = $allInvoices->filter('description', $contract->title . ' (' .
            $this->_referenceDate->format('Y-m-d H:i:s') . ')');
        static::assertEquals(1, $invoice->count(), 'did not find contracts invoice');
        $invoice = $invoice->getFirstRecord();

        $filterArr = [
            ['field' => 'customer', 'operator' => 'AND', 'value' => [
                ['field' => ':id', 'operator' => 'equals', 'value' => null]
            ]]
        ];
        $tmp = Sales_Controller_Invoice::getInstance()->search($filter= new Sales_Model_InvoiceFilter($filterArr));
        static::assertSame(0, $tmp->count());
        static::assertSame($filterArr, $filter->toArray(true));

        $ip = Sales_Controller_InvoicePosition::getInstance()->search(new Sales_Model_InvoicePositionFilter([
            ['field' => 'invoice_id', 'operator' => 'AND', 'value' => [
                ['field' => 'id', 'operator' => 'equals', 'value' => $invoice->getId()]
            ]]
        ]));
        static::assertEquals(2, $ip->count(), 'invoice should have only two positions');
        static::assertEquals(0, $ip->filter('accountable_id', $ta2->getId())->count());
        static::assertEquals(1, $ip->filter('accountable_id', $ta1->getId())->count());
        $updatedTa1 = $this->_timeaccountController->get($ta1->getId());
        $updatedTa2 = $this->_timeaccountController->get($ta2->getId());
        static::assertEquals($ta2->seq, $updatedTa2->seq);
        static::assertNotEquals($ta1->seq, $updatedTa1->seq);

        $invoice->relations = Tinebase_Relations::getInstance()->getRelations(get_class($invoice), 'Sql', $invoice->getId());
        static::assertNotNull($customer = $invoice->relations->find('type', 'CUSTOMER'));
        static::assertSame(1, ($searchResult = $this->_invoiceController->search(new Sales_Model_InvoiceFilter([
            ['field' => 'customer', 'operator' => 'notDefinedBy:AND', 'value' => [
                ['field' => ':id', 'operator' => 'equals', 'value' => $customer->related_id]
            ]]
        ])))->count());
        static::assertNotSame($searchResult->getFirstRecord()->getId(), $invoice->getId());

        static::assertSame(1, ($searchResult = $this->_invoiceController->search(new Sales_Model_InvoiceFilter([
            ['field' => 'customer', 'operator' => 'AND', 'value' => [
                ['field' => ':id', 'operator' => 'equals', 'value' => $customer->related_id]
            ]]
        ])))->count());
        static::assertSame($searchResult->getFirstRecord()->getId(), $invoice->getId());
    }

    public function testDeleteInvoice()
    {
        $this->_createFullFixtures();
        
        $date = clone $this->_referenceDate;
        $date->addMonth(12);
        
        $this->_invoiceController->createAutoInvoices($date);
        
        $paController = Sales_Controller_ProductAggregate::getInstance();
        $productAggregates = $paController->getAll();
        $contracts = $this->_contractController->getAll();
        $contracts->sort('id', 'DESC');
        
        $c1 = $contracts->getFirstRecord();
        
        $this->assertEquals(5, $productAggregates->count());
        
        $taController = Timetracker_Controller_Timeaccount::getInstance();
        $tsController = Timetracker_Controller_Timesheet::getInstance();
        
        $allTimesheets = $tsController->getAll();
        $allTimeaccounts = $taController->getAll();
        
        foreach($allTimesheets as $ts) {
            $this->assertTrue($ts->invoice_id != NULL);
        }
        
        foreach($allTimeaccounts as $ta) {
            if (intval($ta->budget) == 0) {
                $this->assertTrue($ta->invoice_id == NULL);
            }
        }

        $allInvoices = $this->_invoiceController->getAll('start_date', 'DESC');
        $this->assertEquals(9, $allInvoices->count(), print_r($allInvoices->toArray(), 1));
        
        foreach($allInvoices as $invoice) {
            $this->_invoiceController->delete($invoice);
        }
        
        $allTimesheets = $tsController->getAll();
        $allTimeaccounts = $taController->getAll();
        
        foreach($allTimeaccounts as $ta) {
            if (intval($ta->budget) == 0) {
                $this->assertTrue($ta->invoice_id == NULL, print_r($ta->toArray(), 1));
            }
        }
        
        foreach($allTimesheets as $ts) {
            $this->assertTrue($ts->invoice_id == NULL, print_r($ts->toArray(), 1));
        }
    }

    protected function _createInvoiceUpdateRecreationFixtures($createTimesheet = true)
    {
        $this->_createFullFixtures();

        // we dont want this contract 1 to be part of the runs below, move it out of the way
        $contract = $this->_contractRecords->getByIndex(0);
        $contract->start_date->addMonth(12);
        Sales_Controller_Contract::getInstance()->update($contract);

        $date = clone $this->_referenceDate;
        $customer4Timeaccount = $this->_timeaccountRecords->filter('title', 'TA-for-Customer4')->getFirstRecord();
        $customer4Timeaccount->status = 'to bill';
        $customer4Timeaccount->budget = NULL;

        if (null === $this->_timesheetController)
            $this->_timesheetController = Timetracker_Controller_Timesheet::getInstance();
        if (null === $this->_timeaccountController)
            $this->_timeaccountController = Timetracker_Controller_Timeaccount::getInstance();
        // don't update relations
        unset($customer4Timeaccount->relations);
        $this->_timeaccountController->update($customer4Timeaccount);

        // this is a ts on 20xx-03-18
        $this->sharedTimesheet = new Timetracker_Model_Timesheet(array(
            'account_id' => Tinebase_Core::getUser()->getId(),
            'timeaccount_id' => $customer4Timeaccount->getId(),
            'start_date' => $date->addMonth(2)->addDay(17),
            'duration' => 120,
            'description' => 'ts from ' . (string) $date,
        ));
        if (true === $createTimesheet)
            $this->_timesheetController->create($this->sharedTimesheet);

        //run autoinvoicing with 20xx-04-01
        $date = clone $this->_referenceDate;
        $date->addMonth(3);
        $result = $this->_invoiceController->createAutoInvoices($date);
        $this->assertEquals(2, count($result['created']));

        return $result;
    }

    public function testInvoiceRecreation()
    {
        self::markTestSkipped('FIXME: this fails at random :(');

        $result = $this->_createInvoiceUpdateRecreationFixtures();

        $oldInvoiceId0 = $result['created'][0];
        $ipc = Sales_Controller_InvoicePosition::getInstance();
        $f = new Sales_Model_InvoicePositionFilter(array(
            array('field' => 'invoice_id', 'operator' => 'AND', 'value' => array(
                array('field' => 'id', 'operator' => 'equals', 'value' => $oldInvoiceId0),
            )),
        ));
        $positions = $ipc->search($f);
        $this->assertEquals(9, $positions->count());

        $oldInvoiceId1 = $result['created'][1];
        $ipc = Sales_Controller_InvoicePosition::getInstance();
        $f = new Sales_Model_InvoicePositionFilter(array(
            array('field' => 'invoice_id', 'operator' => 'AND', 'value' => array(
                array('field' => 'id', 'operator' => 'equals', 'value' => $oldInvoiceId1),
            )),
        ));
        $positions = $ipc->search($f);
        $this->assertEquals(4, $positions->count());

        $contract4 = $this->_contractRecords->getByIndex(3);
        $contract4->products->find('interval', 3)->interval = 4;
        $contract4->title = $contract4->getTitle() . ' changed';
        // don't update relations
        unset($contract4->relations);

        sleep(1);
        $this->_contractController->update($contract4);

        $this->sharedTimesheet->id = NULL;
        $this->_timesheetController->create($this->sharedTimesheet);

        $result = $this->_invoiceController->checkForContractOrInvoiceUpdates();

        $this->assertGreaterThanOrEqual(2, count($result));

        $mapping = $this->_invoiceController->getAutoInvoiceRecreationResults();
        $this->assertEquals(true, isset($mapping[$oldInvoiceId0]), print_r($mapping, true));
        $this->assertEquals(true, isset($mapping[$oldInvoiceId1]), print_r($mapping, true));
        $newInvoiceId0 = $mapping[$oldInvoiceId0];
        $newInvoiceId1 = $mapping[$oldInvoiceId1];
        $this->assertNotEquals($oldInvoiceId0, $newInvoiceId0);
        $this->assertNotEquals($oldInvoiceId1, $newInvoiceId1);

        $this->_checkInvoiceUpdateExistingTimeaccount($newInvoiceId1);

        $f = new Sales_Model_InvoicePositionFilter(array(
            array('field' => 'invoice_id', 'operator' => 'AND', 'value' => array(
                array('field' => 'id', 'operator' => 'equals', 'value' => $newInvoiceId0),
            )),
        ));
        $positions = $ipc->search($f);
        $this->assertEquals(10, $positions->count());

        $f = new Sales_Model_InvoicePositionFilter(array(
            array('field' => 'invoice_id', 'operator' => 'AND', 'value' => array(
                array('field' => 'id', 'operator' => 'equals', 'value' => $newInvoiceId1),
            )),
        ));
        $positions = $ipc->search($f);
        $this->assertEquals(1, $positions->count());
    }

    public function testInvoiceUpdateAddTimesheet()
    {
        $result = $this->_createInvoiceUpdateRecreationFixtures();

        sleep(1);

        $this->sharedTimesheet->id = NULL;
        $this->_timesheetController->create($this->sharedTimesheet);

        $result = $this->_invoiceController->checkForUpdate($result['created'][1]);
        $this->assertArrayHasKey(0, $result);

        sleep(1);

        $this->assertEmpty($this->_invoiceController->checkForUpdate($result[0]));
    }

    public function testCheckForContractOrInvoiceUpdatesExistingTimeaccount()
    {
        $this->_createInvoiceUpdateRecreationFixtures();

        sleep(1);

        $this->sharedTimesheet->id = NULL;
        $this->_timesheetController->create($this->sharedTimesheet);

        $result = $this->_invoiceController->checkForContractOrInvoiceUpdates();
        $this->assertArrayHasKey(0, $result);

        sleep(1);

        $this->assertEmpty($this->_invoiceController->checkForUpdate($result[0]));
    }

    protected function _checkInvoiceUpdateExistingTimeaccount($invoiceId, $result = 4)
    {
        $ipc = Sales_Controller_InvoicePosition::getInstance();
        $f = new Sales_Model_InvoicePositionFilter(array(
            array('field' => 'model', 'operator' => 'equals', 'value' => 'Timetracker_Model_Timeaccount'),
            array('field' => 'invoice_id', 'operator' => 'AND', 'value' => array(
                array('field' => 'id', 'operator' => 'equals', 'value' => $invoiceId),
            )),
        ));
        $positions = $ipc->search($f);
        $this->assertEquals(1, $positions->count(), 'no invoice position found');
        $this->assertEquals($result, $positions->getFirstRecord()->quantity);
    }

    public function testCheckForContractOrInvoiceUpdatesWithUpdatedTimesheet()
    {
        $this->_createInvoiceUpdateRecreationFixtures();

        sleep(1);

        $oldTimesheet = $this->sharedTimesheet;
        $this->sharedTimesheet->id = NULL;
        $this->sharedTimesheet = $this->_timesheetController->create($this->sharedTimesheet);

        $result = $this->_invoiceController->checkForContractOrInvoiceUpdates();
        $this->assertArrayHasKey(0, $result);

        sleep(1);

        $oldTimesheet->duration = 180;
        $this->sharedTimesheet = $this->_timesheetController->update($oldTimesheet);

        $result = $this->_invoiceController->checkForContractOrInvoiceUpdates();
        $this->assertArrayHasKey(0, $result);

        $this->_checkInvoiceUpdateExistingTimeaccount($result[0], 5);

        $oldTimesheet->accounting_time_factor = 0;
        $filter = new Timetracker_Model_TimesheetFilter(array(
            array('field' => 'id', 'operator' => 'equals', 'value' => $oldTimesheet->getId()),
        ));
        $this->_timesheetController->update($oldTimesheet);
        $this->assertEquals(0.0, $oldTimesheet->accounting_time_factor, 'accounting_time_factor should not be changed');

        $result = $this->_timesheetController->updateMultiple($filter, array('invoice_id' => null));
        $record = $result['results']->getFirstRecord();
        $this->assertEquals(0.0, $record->accounting_time_factor, 'accounting_time_factor should not be changed');
    }

    protected function _checkInvoiceUpdateWithNewTimeaccount($invoiceId)
    {
        $ipc = Sales_Controller_InvoicePosition::getInstance();
        $f = new Sales_Model_InvoicePositionFilter(array(
            array('field' => 'model', 'operator' => 'equals', 'value' => 'Timetracker_Model_Timeaccount'),
            array('field' => 'invoice_id', 'operator' => 'AND', 'value' => array(
                array('field' => 'id', 'operator' => 'equals', 'value' => $invoiceId),
            )),
        ));
        $positions = $ipc->search($f);
        $this->assertEquals(1, $positions->count());
        $this->assertEquals(2, $positions->getFirstRecord()->quantity);
    }
    /**
     *
     */
    public function testInvoiceUpdateWithNewTimeaccount()
    {
        $result = $this->_createInvoiceUpdateRecreationFixtures(false);

        $this->_timesheetController->create($this->sharedTimesheet);

        $result = $this->_invoiceController->checkForUpdate($result['created'][1]);
        $this->assertArrayHasKey(0, $result);

        $this->_checkInvoiceUpdateWithNewTimeaccount($result[0]);

        //check that the same update run doesnt do anything anymore
        $this->assertEmpty($this->_invoiceController->checkForUpdate($result[0]));
    }

    public function testCheckForContractOrInvoiceUpdatesWithNewTimeaccount()
    {
        $this->_createInvoiceUpdateRecreationFixtures(false);

        $this->_timesheetController->create($this->sharedTimesheet);

        $result = $this->_invoiceController->checkForContractOrInvoiceUpdates();
        $this->assertArrayHasKey(0, $result);
        $this->_checkInvoiceUpdateWithNewTimeaccount($result[0]);

        $this->assertEmpty($this->_invoiceController->checkForContractOrInvoiceUpdates());
    }

    /**
     * @see: rt127444
     * 
     * make sure timeaccounts won't be billed if they shouldn't
     */
    public function testBudgetTimeaccountBilled()
    {
        $this->_createFullFixtures();
        
        $date = clone $this->_referenceDate;
        $i = 0;
        
        // do not set to bill, this ta has a budget
        $customer1Timeaccount = $this->_timeaccountRecords->filter('title', 'TA-for-Customer1')->getFirstRecord();
        $customer1Timeaccount->status = 'not yet billed';
        
        $tsController = Timetracker_Controller_Timesheet::getInstance();
        $taController = Timetracker_Controller_Timeaccount::getInstance();
        unset($customer1Timeaccount->relations);
        $taController->update($customer1Timeaccount);
        
        // this is a ts on 20xx-01-18
        $timesheet = new Timetracker_Model_Timesheet(array(
            'account_id' => Tinebase_Core::getUser()->getId(),
            'timeaccount_id' => $customer1Timeaccount->getId(),
            'start_date' => $date->addDay(17),
            'duration' => 120,
            'description' => 'ts from ' . (string) $date,
        ));
        
        $tsController->create($timesheet);
        
        // this is a ts on 20xx-02-03
        $timesheet->id = NULL;
        $timesheet->start_date  = $date->addDay(17);
        $timesheet->description = 'ts from ' . (string) $date;
        
        $tsController->create($timesheet);
        
        $date = clone $this->_referenceDate;
        $date->addMonth(1);
        
        $result = $this->_invoiceController->createAutoInvoices($date);
        
        $this->assertEquals(1, count($result['created']));
        
        $customer1Timeaccount->status = 'to bill';
        unset($customer1Timeaccount->relations);
        $taController->update($customer1Timeaccount);
        
        $date->addSecond(1);
        
        $result = $this->_invoiceController->createAutoInvoices($date);
        $this->assertEquals(1, count($result['created']));
        
        $invoiceId = $result['created'][0];
        $invoice = $this->_invoiceController->get($invoiceId);
        $found = FALSE;
        
        foreach($invoice->relations as $relation) {
            if ($relation->related_model == 'Timetracker_Model_Timeaccount') {
                $this->assertEquals('TA-for-Customer1',     $relation->related_record->title);
                $found = TRUE;
            }
        }

        $this->assertTrue($found, 'the timeaccount could not be found in the invoice!');
    }
    
    public function testCreateClearedInvoice()
    {
        $this->_createCustomers();
        $this->_createCostCenters();

        $customer = $this->_customerRecords->filter('name', 'Customer1')->getFirstRecord();
        $invoice = $this->_invoiceController->create(new Sales_Model_Invoice(array(
            'customer_id' => $customer->getId(),
            'description' => 'Cleared',
            'address_id' => $this->_addressRecords->filter('customer_id', $customer->getId())->getFirstRecord()->getId(),
            'costcenter_id' => $this->_costcenterRecords->getFirstRecord()->getId(),
            'cleared'    => 'CLEARED',
        )));

        $this->assertNotNull($invoice->cleared, 'New created invoice with cleared status should generate the invoice number automatically.');
    }
    
    /**
     * tests if the rights work: Sales_Acl_Rights::SET_INVOICE_NUMBER, Sales_Acl_Rights::MANAGE_INVOICES
     */
    public function testSetManualNumberRight()
    {
        $this->_createCustomers();
        $this->_createCostCenters();
        
        $customer = $this->_customerRecords->filter('name', 'Customer1')->getFirstRecord();
        $invoice = $this->_invoiceController->create(new Sales_Model_Invoice(array(
            'number' => 'R-3000',
            'customer_id' => $customer->getId(),
            'description' => 'Manual',
            'address_id' => $this->_addressRecords->filter('customer_id', $customer->getId())->getFirstRecord()->getId(),
            'costcenter_id' => $this->_costcenterRecords->getFirstRecord()->getId()
        )));
        
        // fetch user group
        $group   = Tinebase_Group::getInstance()->getDefaultGroup();
        $groupId = $group->getId();
        
        // create new user
        $user = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => 'testuser',
            'accountPrimaryGroup'   => $groupId,
            'accountDisplayName'    => 'Test User',
            'accountLastName'       => 'User',
            'accountFirstName'      => 'Test',
            'accountFullName'       => 'Test User',
            'accountEmailAddress'   => 'unittestx8@' . TestServer::getPrimaryMailDomain(),
        ));
        
        $user = Admin_Controller_User::getInstance()->create($user, 'pw5823H132', 'pw5823H132');
        $this->_testUser = Tinebase_Core::getUser();

        Tinebase_Core::set(Tinebase_Core::USER, $user);
        
        $e = new Exception('No Message');
        
        try {
            $invoice = $this->_invoiceController->create(new Sales_Model_Invoice(array(
                'number' => 'R-3001',
                'customer_id' => $customer->getId(),
                'description' => 'Manual Forbidden',
                'address_id' => $this->_addressRecords->filter('customer_id', $customer->getId())->getFirstRecord()->getId(),
                'costcenter_id' => $this->_costcenterRecords->getFirstRecord()->getId()
            )));
        } catch (Exception $e) {
        }
        
        $this->assertTrue(get_class($e) == 'Tinebase_Exception_AccessDenied');
        $this->assertTrue($e->getMessage() == 'You don\'t have the right to manage invoices!');
        
        Tinebase_Core::set(Tinebase_Core::USER, $this->_testUser);
        
        $fe = new Admin_Frontend_Json();
        $userRoles = $fe->getRoles('user', [], 'ASC', 0, 1);
        $userRole = $fe->getRole($userRoles['results'][0]['id']);
        
        $roleRights = $fe->getRoleRights($userRole['id']);
        $roleMembers = $fe->getRoleMembers($userRole['id']);
        $roleMembers['results'][] = array('name' => 'testuser', 'type' => 'user', 'id' => $user->accountId);
        
        $app = Tinebase_Application::getInstance()->getApplicationByName('Sales');
        
        $roleRights['results'][] = array('application_id' => $app->getId(), 'right' => Sales_Acl_Rights::MANAGE_INVOICES);
        $fe->saveRole($userRole, $roleMembers['results'], $roleRights['results']);
        
        Tinebase_Core::set(Tinebase_Core::USER, $user);
        
        $e = new Exception('No Message');
        
        try {
            $invoice = $this->_invoiceController->create(new Sales_Model_Invoice(array(
                'number' => 'R-3001',
                'customer_id' => $customer->getId(),
                'description' => 'Manual Forbidden',
                'address_id' => $this->_addressRecords->filter('customer_id', $customer->getId())->getFirstRecord()->getId(),
                'costcenter_id' => $this->_costcenterRecords->getFirstRecord()->getId()
            )));
        } catch (Exception $e) {
        }
        
        $this->assertEquals('Tinebase_Exception_AccessDenied', get_class($e));
        $this->assertEquals('You have no right to set the invoice number!', $e->getMessage());
    }
    
    /**
     * tests invoice merging
     */
    public function testMergingInvoices()
    {
        $startDate = clone $this->_referenceDate;
        
        $this->_createProducts();
        
        $this->_createCustomers(1);
        $this->_createCostCenters();
        
        $monthBack = clone $this->_referenceDate;
        $monthBack->subMonth(1);
        $addressId = $this->_addressRecords->filter(
                'customer_id', $this->_customerRecords->filter(
                    'name', 'Customer1')->getFirstRecord()->getId())->filter(
                        'type', 'billing')->getFirstRecord()->getId();
        
        $this->assertTrue($addressId !== NULL);
        
        // this contract begins 6 months before the first invoice will be created
        $this->_createContracts(array(array(
            'number'       => 100,
            'title'        => 'MyContract',
            'description'  => 'unittest',
            'container_id' => $this->_sharedContractsContainerId,
            'billing_point' => 'begin',
            'billing_address_id' => $addressId,
            
            'interval' => 1,
            'start_date' => $startDate->subMonth(6),
            'last_autobill' => clone $this->_referenceDate,
            'end_date' => NULL,
            'products' => array(
                array('product_id' => $this->_productRecords->getByIndex(0)->getId(), 'quantity' => 1, 'interval' => 1, 'last_autobill' => $monthBack),
            )
        )));
        
        $startDate = clone $this->_referenceDate;
        $startDate->addDay(5);
        $startDate->addMonth(24);
        
        $result = $this->_invoiceController->createAutoInvoices($startDate, null, true);
        $this->assertEquals(1, $result['created_count']);
    }
    
    /**
     * tests if a product aggregate gets billed in the correct periods
     */
    public function testOneProductContractInterval()
    {
        $startDate = clone $this->_referenceDate;
        
        $this->_createProducts();
        
        $this->_createCustomers(1);
        $this->_createCostCenters();
        
        $monthBack = clone $this->_referenceDate;
        $monthBack->subMonth(1);
        $addressId = $this->_addressRecords->filter(
                'customer_id', $this->_customerRecords->filter(
                    'name', 'Customer1')->getFirstRecord()->getId())->filter(
                        'type', 'billing')->getFirstRecord()->getId();
        
        $this->assertTrue($addressId !== NULL);
        
        // this contract begins 6 months before the first invoice will be created
        $this->_createContracts(array(array(
            'number'       => 100,
            'title'        => 'MyContract',
            'description'  => 'unittest',
            'container_id' => $this->_sharedContractsContainerId,
            'billing_point' => 'begin',
            'billing_address_id' => $addressId,
            
            'interval' => 1,
            'start_date' => $startDate->subMonth(6),
            'last_autobill' => clone $this->_referenceDate,
            'end_date' => NULL,
            'products' => array(
                array('product_id' => $this->_productRecords->getByIndex(0)->getId(), 'quantity' => 1, 'interval' => 1, 'last_autobill' => $monthBack),
            )
        )));
        
        $startDate = clone $this->_referenceDate;
        $startDate->addDay(5);
        $startDate->addMonth(24);
        
        $result = $this->_invoiceController->createAutoInvoices($startDate);
        $this->assertEquals(25, $result['created_count']);
        
        $invoices = $this->_invoiceController->getAll('start_date');
        $firstInvoice = $invoices->getFirstRecord();
        $this->assertInstanceOf('Tinebase_DateTime', $firstInvoice->start_date);
        $this->assertEquals('0101', $firstInvoice->start_date->format('md'));
        
        $this->assertEquals(25, $invoices->count());
        
        $filter = new Sales_Model_InvoicePositionFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'in', 'value' => $invoices->getArrayOfIds())));
        
        $pagination = new Tinebase_Model_Pagination(array('sort' => 'month', 'dir' => 'ASC'));
        
        $invoicePositions = Sales_Controller_InvoicePosition::getInstance()->search($filter, $pagination);
        
        // get sure each invoice positions has the same month as the invoice and the start_date is the first
        foreach($invoices as $invoice) {
            $month = (int) $invoice->start_date->format('n');
            $index = $month - 1;
            
            $this->assertEquals('01', $invoice->start_date->format('d'));
            $this->assertEquals($invoice->end_date->format('t'), $invoice->end_date->format('d'), print_r($invoice->toArray(), 1));
            
            $this->assertEquals(1, $invoice->start_date->format('d'));
            
            $pos = $invoicePositions->filter('invoice_id', $invoice->getId())->getFirstRecord();
            $this->assertEquals($invoice->start_date->format('Y-m'), $pos->month);
            $this->assertEquals($invoice->end_date->format('Y-m'), $pos->month);
        }
        
        $this->assertEquals(25, $invoicePositions->count());
        
        $this->assertEquals($this->_referenceYear . '-01', $invoicePositions->getFirstRecord()->month);
        
        $invoicePositions->sort('month', 'DESC');
        
        $this->assertEquals($this->_referenceYear + 2 . '-01', $invoicePositions->getFirstRecord()->month);
    }
    
    /**
     * test product only contract setting last_autobill and resetting last_autobill on delete
     */
    public function testLastAutobillAfterDeleteInvoice()
    {
        if (PHP_VERSION_ID >= 70400) {
            self::markTestSkipped('FIXME not working with php7.4');
        }

        $startDate = clone $this->_referenceDate;
        $lab = clone $this->_referenceDate;
        $lab->subMonth(1);
        $this->_createProducts(array(array(
            'name' => 'Hours',
            'description' => 'timesheets',
            'price' => '100',
            'accountable' => 'Timetracker_Model_Timeaccount'
        )));
        
        $this->_createCustomers(1);
        $this->_createCostCenters();
        
        // has budget, is to bill
        $ta = $this->_createTimeaccounts(array(array(
            'title'         => 'Tacss',
            'description'   => 'blabla',
            'is_open'       => 1,
            'budget'        => NULL,
            
        )))->getFirstRecord();
        
        // has timeaccount without budget, must be billed at end of the period (each month has at least one timesheet)
        $this->_createContracts(array(array(
            'number'       => 100,
            'title'        => 'MyContract',
            'description'  => 'unittest',
            'container_id' => $this->_sharedContractsContainerId,
            'billing_address_id' => $this->_addressRecords->filter(
                'customer_id', $this->_customerRecords->filter(
                    'name', 'Customer1')->getFirstRecord()->getId())->filter(
                        'type', 'billing')->getFirstRecord()->getId(),
        
            'start_date' => $startDate,
            'last_autobill' => NULL,
            'end_date' => NULL,
            'products' => array(
                array('product_id' => $this->_productRecords->getByIndex(0)->getId(), 
                    'quantity' => 1, 'interval' => 1, 'billing_point' => 'end'),
            )
        )));
        
        // create timesheets
        $tsDate = clone $this->_referenceDate;
        $tsDate->addDay(10);
        
        $i = 0;
        while($i < 12) {
            $this->_createTimesheets(array(array(
                'account_id' => Tinebase_Core::getUser()->getId(),
                'timeaccount_id' => $ta->getId(),
                'start_date' => $tsDate,
                'duration' => 105,
                'description' => 'ts from ' . (string) $tsDate,
            )));
            $tsDate->addMonth(1);
            $i++;
        }
        
        
        $contract = $this->_contractController->getAll()->getFirstRecord();
        $this->assertEquals($startDate->__toString(), $contract->start_date->__toString());
        
        // find product aggregate
        $paController = Sales_Controller_ProductAggregate::getInstance();
        $productAggregate = $paController->getAll()->getFirstRecord();
        $productAggregate->setTimezone(Tinebase_Core::getUserTimezone());
        
        $this->assertEquals(NULL, $productAggregate->last_autobill);
        
        // create 6 invoices - each month one invoice - last autobill must be increased each month
        for ($i = 1; $i < 7; $i++) {
            $myDate = clone $this->_referenceDate;
            $myDate->addMonth($i)->addHour(3);
            
            $testDate = clone $this->_referenceDate;
            $testDate->addMonth($i);
            
            $result = $this->_invoiceController->createAutoInvoices($myDate);
            $this->assertEquals(1, $result['created_count']);
            
            $productAggregate = $paController->get($productAggregate->getId());
            $productAggregate->setTimezone(Tinebase_Core::getUserTimezone());
            $this->assertEquals($testDate, $productAggregate->last_autobill);
        }
        
        $testDate = clone $this->_referenceDate;
        $testDate->addMonth(6);
        $this->assertEquals($testDate, $productAggregate->last_autobill);
        
        // delete all created invoices again
        $allInvoices = $this->_invoiceController->getAll('start_date', 'DESC');
        $lastInvoice = $allInvoices->getLastRecord();
        $allInvoices->removeRecord($lastInvoice);
        
        foreach($allInvoices as $invoice) {
            $this->_invoiceController->delete($invoice);
        }
        
        $productAggregate = $paController->get($productAggregate->getId());
        $productAggregate->setTimezone(Tinebase_Core::getUserTimezone());

        $this->assertEquals($this->_referenceDate->getClone()->addMonth(1), $productAggregate->last_autobill);

        $this->_invoiceController->delete($lastInvoice);
        $productAggregate = $paController->get($productAggregate->getId());
        $productAggregate->setTimezone(Tinebase_Core::getUserTimezone());
        static::assertNull($productAggregate->last_autobill);
        
        // create 6 invoices again - each month one invoice - last autobill must be increased each month
        for ($i = 1; $i < 7; $i++) {
            $myDate = clone $this->_referenceDate;
            $myDate->addMonth($i)->addHour(3);
        
            $testDate = clone $this->_referenceDate;
            $testDate->addMonth($i);
        
            $result = $this->_invoiceController->createAutoInvoices($myDate);
            $this->assertEquals(1, $result['created_count']);
        
            $productAggregate = $paController->get($productAggregate->getId());
            $productAggregate->setTimezone(Tinebase_Core::getUserTimezone());
            $this->assertEquals($testDate, $productAggregate->last_autobill);
        }
        
        $testDate = clone $this->_referenceDate;
        $testDate->addMonth(6);
        $this->assertEquals($testDate, $productAggregate->last_autobill);
        
    }
    
    /**
     * @see step2 FS0012
     * 
     * products must be billed on the beginning of a period
     * the first dates must fit
     */
    public function testProductWithOneYearInterval()
    {
        // last year, the 1.1 in usertimezone
        $date      = clone $this->_referenceDate;
        
        $startDateContract = clone $this->_referenceDate;
        $startDateContract->subMonth(13);
        
        // this has been billed for the last year.
        $startDateProduct = clone $this->_referenceDate;
        $startDateProduct->subMonth(12);
        
        $this->_createProducts();
        
        $this->_createCustomers(1);
        $this->_createCostCenters();
        
        $addressId = $this->_addressRecords->filter(
                'customer_id', $this->_customerRecords->filter(
                    'name', 'Customer1')->getFirstRecord()->getId())->filter(
                        'type', 'billing')->getFirstRecord()->getId();
        
        // the contract has an interval of 0, but it has to be billed
        $this->_createContracts(array(array(
            'number'       => 100,
            'title'        => 'MyContract',
            'description'  => 'unittest',
            'container_id' => $this->_sharedContractsContainerId,
            'billing_point' => 'begin',
            'billing_address_id' => $addressId,
            'interval' => 0,
            'start_date' => $startDateContract,
            'last_autobill' => clone $this->_referenceDate,
            'end_date' => NULL,
            'products' => array(
                array('product_id' => $this->_productRecords->getByIndex(0)->getId(),
                    'quantity' => 1, 'interval' => 12, 'last_autobill' => $startDateProduct),
            )
        )));
        
        $startDate = clone $this->_referenceDate;
        $startDate->addHour(3);
        
        $i=0;
        
        $result = $this->_invoiceController->createAutoInvoices($startDate);
        $this->assertEquals(1, $result['created_count']);
        
        $invoice = $this->_invoiceController->get($result['created'][0]);
        
        $invoicePositions = Sales_Controller_InvoicePosition::getInstance()->getAll('month')->filter('invoice_id', $result['created'][0]);
        $this->assertEquals(12, $invoicePositions->count());
        
        $i = 1;
        
        foreach($invoicePositions as $ipo) {
            $this->assertEquals($this->_referenceYear . '-' . ($i > 9 ? '' : '0') . $i, $ipo->month, print_r($invoicePositions->toArray(), 1));
            $this->assertEquals($ipo->invoice_id, $invoice->getId());
            $i++;
        }
    }
    
    /**
     * make sure that timesheets get created for the right month
     */
    public function testTimesheetOnMonthEndAndBegin()
    {
        $dates = array(
            clone $this->_referenceDate,
            clone $this->_referenceDate,
            clone $this->_referenceDate,
            clone $this->_referenceDate
        );
        // 0: 1.1.xxxx, 1: 31.1.xxxx, 2: 1.2.xxxx, 3: 28/29.2.xxxx
        $dates[1]->addMonth(1)->subDay(1);
        $dates[2]->addMonth(1);
        $dates[3]->addMonth(2)->subDay(1);
        
        $customer = $this->_createCustomers(1)->getFirstRecord();
        $this->_createCostCenters();
        
        // has no budget
        $ta = $this->_createTimeaccounts(array(array(
            'title'         => 'TaTest',
            'description'   => 'blabla',
            'is_open'       => 1,
            'status'        => 'not yet billed',
            'budget'        => null
        )))->getFirstRecord();
        
        foreach($dates as $date) {
            $this->_createTimesheets(array(
                array(
                    'account_id' => Tinebase_Core::getUser()->getId(),
                    'timeaccount_id' => $ta->getId(),
                    'start_date' => $date,
                    'duration' => 105,
                    'description' => 'ts from ' . (string) $date,
                ))
            );
        }
        
        $this->assertEquals(4, $this->_timesheetRecords->count());
        
        $csDate = clone $this->_referenceDate;
        $csDate->subMonth(10);
        
        $lab = clone $this->_referenceDate;
        // set start position (must be manually set on introducing the invoice module)
        $lab->subMonth(1);
        $this->_createProducts();
        $this->_createContracts(array(array(
            'number'       => 100,
            'title'        => 'MyContract',
            'description'  => 'unittest',
            'container_id' => $this->_sharedContractsContainerId,
            'billing_point' => 'begin',
            'billing_address_id' => $this->_addressRecords->filter(
                'customer_id', $customer->getId())->filter(
                        'type', 'billing')->getFirstRecord()->getId(),
        
            'start_date' => $csDate,
            'end_date' => NULL,
            'products' => array(
                    array('start_date' => $csDate, 'end_date' => NULL, 'quantity' => 1, 'interval' => 1, 'billing_point' => 'end', 'product_id' => $this->_productRecords->find(function($val) {
                        return null !== $val->name->find('text', 'Hours');
                    }, null)->getId()),
            )
        )));
        
        $json = new Sales_Frontend_Json();

        $date = clone $this->_referenceDate;
        // this is set by cli if called by cli
        $date->setTime(3,0,0);
        
        $result = $this->_invoiceController->createAutoInvoices($date);
        $this->assertEquals(0, $result['created_count'], (string) $date);
        sleep(1);
        $date->addMonth(1);
        $result = $this->_invoiceController->createAutoInvoices($date);
        $this->assertEquals(1, $result['created_count'], (string) $date);
        $invoice1Id = $result['created'][0];
        $invoice = $json->getInvoice($invoice1Id);
        $this->assertEquals(1, count($invoice['positions']), print_r($invoice['positions'], 1));
        sleep(1);
        $date->addMonth(1);
        $result = $this->_invoiceController->createAutoInvoices($date);
        $this->assertEquals(1, $result['created_count'], (string) $date);
        $invoice2Id = $result['created'][0];
        $invoice = $json->getInvoice($invoice2Id);
        $this->assertEquals(1, count($invoice['positions']));
        sleep(1);
        $date->addMonth(1);
        $result = $this->_invoiceController->createAutoInvoices($date);
        $this->assertEquals(0, $result['created_count'], (string) $date);
        
        $filter = new Timetracker_Model_TimesheetFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoice1Id)));
        $timesheets = $this->_timesheetController->search($filter);
        $this->assertEquals(2, $timesheets->count());
        
        $filter = new Timetracker_Model_TimesheetFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoice2Id)));
        $timesheets = $this->_timesheetController->search($filter);
        $this->assertEquals(2, $timesheets->count());
        
        // now try to delete the first invoice, which is not allowed
        $this->expectException('Sales_Exception_DeletePreviousInvoice');
        
        $this->_invoiceController->delete(array($invoice1Id));
    }
    
    /**
     * make sure that timesheets get created for the right month
     */
    public function test2MonthIntervalTimesheetOnMonthEndAndBegin()
    {
        $dates = array(
            clone $this->_referenceDate,
            clone $this->_referenceDate,
            clone $this->_referenceDate,
            clone $this->_referenceDate
        );
        // 0: 1.1.xxxx, 1: 31.1.xxxx, 2: 1.2.xxxx, 3: 28/29.2.xxxx
        $dates[1]->addMonth(1)->subDay(1);
        $dates[2]->addMonth(1);
        $dates[3]->addMonth(2)->subDay(1);
    
        // create much more timesheets
        $dt = clone $this->_referenceDate;
        for ($i = 0; $i < 80; $i++) {
            $dt->addHour(12);
            $dates[] = clone $dt;
        }

        $customer = $this->_createCustomers(1)->getFirstRecord();
        $this->_createCostCenters();
    
        // has no budget
        $ta = $this->_createTimeaccounts(array(array(
            'title'         => 'TaTest',
            'description'   => 'blabla',
            'is_open'       => 1,
            'status'        => 'not yet billed',
            'budget'        => null
        )))->getFirstRecord();
    
        foreach($dates as $date) {
            $this->_createTimesheets(array(
                array(
                    'account_id' => Tinebase_Core::getUser()->getId(),
                    'timeaccount_id' => $ta->getId(),
                    'start_date' => $date,
                    'duration' => 105,
                    'description' => 'ts from ' . (string) $date,
                ))
            );
        }
    
        $this->assertEquals(84, $this->_timesheetRecords->count());
        $this->_createProducts();
        $csDate = clone $this->_referenceDate;
        $csDate->subMonth(10);
    
        $lab = clone $this->_referenceDate;
        $this->_createContracts(array(array(
            'number'       => 100,
            'title'        => 'MyContract',
            'description'  => 'unittest',
            'container_id' => $this->_sharedContractsContainerId,
            'billing_point' => 'begin',
            'billing_address_id' => $this->_addressRecords->filter(
                'customer_id', $customer->getId())->filter(
                    'type', 'billing')->getFirstRecord()->getId(),
    
            'start_date' => $csDate,
            'end_date' => NULL,
            'products' => array(
                array('start_date' => $csDate, 'end_date' => NULL, 'quantity' => 1, 'interval' => 1, 'billing_point' => 'end', 'product_id' => $this->_productRecords->find(function($val) {
                    return null !== $val->name->find('text', 'Hours');
                }, null)->getId())
            )
        )));
    
        $json = new Sales_Frontend_Json();
    
        $date = clone $this->_referenceDate;
        // this is set by cli if called by cli
        $date->setTime(3,0,0);
    
        $result = $this->_invoiceController->createAutoInvoices($date);
        $this->assertEquals(0, $result['created_count']);
    
        $date->addMonth(1);
        $result = $this->_invoiceController->createAutoInvoices($date);
        $this->assertEquals(1, $result['created_count']);
        
        $invoice1Id = $result['created'][0];
        $filter = new Timetracker_Model_TimesheetFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoice1Id)));
        $timesheets = $this->_timesheetController->search($filter);
        $this->assertEquals(63, $timesheets->count());
        
        $date->addMonth(1);
        $result = $this->_invoiceController->createAutoInvoices($date);
        $this->assertEquals(1, $result['created_count'], (string) $date);
        $invoice2Id = $result['created'][0];
        $invoice = $json->getInvoice($invoice2Id);
        $this->assertEquals(1, count($invoice['positions']));
    
        $date->addMonth(1);
        $result = $this->_invoiceController->createAutoInvoices($date);
        $this->assertEquals(0, $result['created_count']);
    
        $filter = new Timetracker_Model_TimesheetFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoice2Id)));
        $timesheets = $this->_timesheetController->search($filter);
        $this->assertEquals(21, $timesheets->count());
    }
    
    /**
     * tests new fields
     */
    public function testManualInvoice()
    {
        $customer = $this->_createCustomers(1)->getFirstRecord();
        $this->_createCostCenters();
        
        $invoice = $this->_invoiceController->create(new Sales_Model_Invoice(array(
            'number' => 100,
            'description' => 'test',
            'address_id' => $this->_addressRecords->getFirstRecord()->getId(),
            'costcenter_id' => $this->_costcenterRecords->getFirstRecord()->getId(),
            'is_auto' => TRUE,
            'price_net' => 200.20,
            'price_gross' => 238.45,
            'sales_tax' => 19.5
        )));
        
        $this->assertEquals(19.5, $invoice->sales_tax);
        $this->assertEquals(200.20, $invoice->price_net);
        $this->assertEquals(238.45, $invoice->price_gross);
    }
    
    /**
     * tests if timesheets get resetted properly after deleting the invoice
     * and recreate the same invoice again containing the same timesheets
     */
    public function testDeleteAndRunAgainInvoice()
    {
        if (PHP_VERSION_ID >= 70400) {
            self::markTestSkipped('FIXME not working with php7.4');
        }

        $this->_createFullFixtures();
    
        $date = clone $this->_referenceDate;
        $date->addMonth(8);
        $i = 0;
    
        $result = $this->_invoiceController->createAutoInvoices($date);
    
        $this->assertEquals(6, count($result['created']));
        
        $tsController = Timetracker_Controller_Timesheet::getInstance();
    
        // get first valid invoice id from all timesheets
        $tsInvoiceIds = array_unique($tsController->getAll()->invoice_id);
        sort($tsInvoiceIds);
        $tsInvoiceIds = array_reverse($tsInvoiceIds);
        $this->assertTrue(! empty($tsInvoiceIds[0]));
        $myInvoice = $this->_invoiceController->get($tsInvoiceIds[0]);

        $f = new Timetracker_Model_TimesheetFilter(array());
        $f->addFilter(new Tinebase_Model_Filter_Text(
                array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $myInvoice->getId())
        ));
        $myTimesheets = $tsController->search($f);
        $this->assertEquals(2, $myTimesheets->count(), 'timesheets not found for invoice ' . $myInvoice->getId());
        
        $this->_invoiceController->delete(array($myInvoice->getId()));
        $allTimesheets = $tsController->getAll();
        foreach($allTimesheets as $ts) {
            $this->assertSame(NULL, $ts->invoice_id, 'invoice id should be reset');
        }
        
        $this->_invoiceController->createAutoInvoices($date);
        
        $tsId = $myTimesheets->getFirstRecord()->getId();
        
        $myTimesheet = $tsController->get($tsId);
        $f = new Timetracker_Model_TimesheetFilter(array());
        $f->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $myTimesheet->invoice_id)));
        
        $myTimesheets = $tsController->search($f);
        $this->assertEquals(2, $myTimesheets->count());
        
        foreach($myTimesheets as $ts) {
            $this->assertEquals(40, strlen($ts->invoice_id));
        }
    }
    
    public function testInterval12LastAutobill()
    {
        $startDate = clone $this->_referenceDate;
        $startDate->subYear(1);
        
        $this->_createProducts(array(
                array('name' => 'bill yearly',
                'description' => 'bill every year',
                'price' => '1002','accountable' => 'Sales_Model_Product')
        ));
        $this->_createCustomers(1);
        $this->_createCostCenters();
        $addressId = $this->_addressRecords->filter(
                        'customer_id', $this->_customerRecords->filter(
                                'name', 'Customer1')->getFirstRecord()->getId())->filter(
                                        'type', 'billing')->getFirstRecord()->getId();
        
        // this contract begins 6 months before the first invoice will be created
        $this->_createContracts(array(array(
                'number'       => 100,
                'title'        => 'MyContract',
                'description'  => 'unittest',
                'container_id' => $this->_sharedContractsContainerId,
                'billing_point' => 'begin',
                'billing_address_id' => $addressId,
                'interval' => 12,
                'start_date' => $startDate,
                'last_autobill' => $startDate,
                'end_date' => NULL,
                'products' => array(
                        array('product_id' => $this->_productRecords->getByIndex(0)->getId(), 'quantity' => 1, 'interval' => 12, 'last_autobill' => $startDate),
                )
        )));
        
        $startDate = clone $this->_referenceDate;
        $startDate->subMonth(1);
        
        $startDate = clone $this->_referenceDate;
        $startDate->addDay(5);
        $result = $this->_invoiceController->createAutoInvoices($startDate);
        
        $this->assertEquals(1, $result['created_count']);
        
        $invoices = $this->_invoiceController->getAll();
        $firstInvoice = $invoices->getFirstRecord();
        $this->assertEquals(1, $invoices->count());
        
        $filter = new Sales_Model_InvoicePositionFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'in', 'value' => $invoices->getArrayOfIds())));
        
        $invoicePositions = Sales_Controller_InvoicePosition::getInstance()->search($filter);
        
        $this->assertEquals(12, $invoicePositions->count());
        
        $contract = $this->_contractRecords->getFirstRecord();
        $contract->setTimezone(Tinebase_Core::getUserTimezone());
        
        $autobillDate = clone $this->_referenceDate;
        
        for ($i = 0; $i < 8; $i++) {
            $startDate->addDay(1);
            $result = $this->_invoiceController->createAutoInvoices($startDate);
            $this->assertEquals(0, $result['created_count']);
        }
        
        $productAggregate = Sales_Controller_ProductAggregate::getInstance()->getAll()->getFirstRecord();
        $productAggregate->setTimezone(Tinebase_Core::getUserTimezone());
        $this->assertEquals($autobillDate, $productAggregate->last_autobill);
    }
    
    /**
     * tests if uncleared invoices gets deleted
     */
    public function testUnclearedDeletion()
    {
        $this->_createFullFixtures();
    
        $date = clone $this->_referenceDate;
        $date->addMonth(8);
        $i = 0;
    
        $result = $this->_invoiceController->createAutoInvoices($date);
    
        $this->assertEquals(6, count($result['created']));
        
        $invoice = $this->_invoiceController->get($result['created'][0]);
        $invoice->cleared = 'CLEARED';
        $this->_invoiceController->update($invoice);
        
        $cli = new Sales_Frontend_Cli();
        $cli->removeUnbilledAutoInvoices();
        
        $invoices = $this->_invoiceController->getAll();
        
        $this->assertEquals(1, $invoices->count());
    }
    
    /**
     * if no productaggregates are defined for a contract, but 
     * accountables are related, use default billing Info from accountable
     * (product will be created if it does not exist - is needed in the invoice position)
     */
    public function testDefaultAutobillInterval()
    {
        $startDate = clone $this->_referenceDate;
        $startDate->subYear(1);
        
        $this->_createCustomers(1);
        $this->_createCostCenters();
    
        $this->_createTimeaccounts(array(array(
                'title'         => 'TA',
                'description'   => 'blabla',
                'is_open'       => 1,
                'status'        => 'to bill',
                'budget'        => 100
        )));
        
        $addressId = $this->_addressRecords->filter(
                        'customer_id', $this->_customerRecords->filter(
                                'name', 'Customer1')->getFirstRecord()->getId())->filter(
                                        'type', 'billing')->getFirstRecord()->getId();
        
        // this contract begins 6 months before the first invoice will be created
        $this->_createContracts(array(array(
                'number'       => 100,
                'title'        => 'MyContract',
                'description'  => 'unittest',
                'container_id' => $this->_sharedContractsContainerId,
                'billing_point' => 'begin',
                'billing_address_id' => $addressId,
    
                'start_date' => $startDate,
                'end_date' => NULL,
        )));
    
        $startDate = clone $this->_referenceDate;
        $startDate->subMonth(1);
    
        $startDate = clone $this->_referenceDate;
        $startDate->addDay(5);
        $result = $this->_invoiceController->createAutoInvoices($startDate);
    
        $this->assertEquals(1, $result['created_count'], 'no invoice was created');
        
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Product::class, array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'accountable', 'operator' => 'equals', 'value' => 'Timetracker_Model_Timeaccount')));
        
        $products = Sales_Controller_Product::getInstance()->search($filter);
        $this->assertEquals(1, $products->count());
        
        
        $this->assertEquals('Timetracker_Model_Timeaccount', $products->getFirstRecord()->accountable);
        
        $filter = new Sales_Model_InvoicePositionFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $result['created'][0])));
        
        $invoicePositions = Sales_Controller_InvoicePosition::getInstance()->search($filter);
        
        $this->assertEquals(1, $invoicePositions->count());
    }

    /**
     *
     * @throws Exception
     * @throws Tinebase_Exception
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function testGenerateTimesheet()
    {
        $fsConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::FILESYSTEM);
        if (!$fsConfig || !$fsConfig->{Tinebase_Config::FILESYSTEM_CREATE_PREVIEWS}) {
            $this->markTestSkipped('PreviewService not configured.');
        }
        
        if ($this->_addressRecords === null) {
            $this->_createCustomers(1);
        }
        
        $costcenter = new Tinebase_Model_EvaluationDimensionItem([], true);
        $costcenter->number = 1337;
        $costcenter->name = 'Foobar Costcenter';
        $cc = Tinebase_Controller_EvaluationDimension::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_EvaluationDimension::class, [
            ['field' => Tinebase_Model_EvaluationDimension::FLD_NAME, 'operator' => 'equals', 'value' => Tinebase_Model_EvaluationDimension::COST_CENTER],
        ]), null, new Tinebase_Record_Expander(Tinebase_Model_EvaluationDimension::class, Tinebase_Model_EvaluationDimension::getConfiguration()->jsonExpander))->getFirstRecord();
        $cc->{Tinebase_Model_EvaluationDimension::FLD_ITEMS}->addRecord($costcenter);
        $cc = Tinebase_Controller_EvaluationDimension::getInstance()->update($cc);
        $costcenter = $cc->{Tinebase_Model_EvaluationDimension::FLD_ITEMS}->find(Tinebase_Model_EvaluationDimensionItem::FLD_NUMBER, $costcenter->number);
        
        $invoice = new Sales_Model_Invoice();
        $invoice->description = 'Foobar Rechnung';
        $invoice->start_date = (new Tinebase_DateTime())->subMonth(1);
        $invoice->end_date = new Tinebase_DateTime();
        $invoice->costcenter_id = $costcenter->getId();
        $invoice->address_id = $this->_addressRecords->getFirstRecord()->getId();
        
        /* @var $invoice Sales_Model_Invoice */
        $invoice = Sales_Controller_Invoice::getInstance()->create($invoice);

        $customer = new Sales_Model_Customer(['name' => 'Test Customer',
            Sales_Model_Customer::FLD_DEBITORS => [[
                Sales_Model_Debitor::FLD_DIVISION_ID => Sales_Controller_Division::getInstance()->getAll()->getFirstRecord()->getId(),
            ]],
        ]);
        $customer = Sales_Controller_Customer::getInstance()->create($customer);
        
        Tinebase_Relations::getInstance()->setRelations(
            Sales_Model_Invoice::class,
            'Sql',
            $invoice->getId(),
            [
                [
                    'related_degree' => 'sibling',
                    'related_model' => Sales_Model_Customer::class,
                    'related_backend' => 'Sql',
                    'related_id' => $customer->getId(),
                    'type' => 'CUSTOMER'
                ]
            ]
        );
        
        $timeaccount1 = new Timetracker_Model_Timeaccount();
        $timeaccount1->title = 'Foobar 1';
        $timeaccount1->is_billable = true;
        $timeaccount1 = Timetracker_Controller_Timeaccount::getInstance()->create($timeaccount1);
        
        $timeaccount2 = new Timetracker_Model_Timeaccount();
        $timeaccount2->title = 'Foobar 2';      
        $timeaccount2->is_billable = true;
        $timeaccount2 = Timetracker_Controller_Timeaccount::getInstance()->create($timeaccount2);

        $bereitschaftTag = new Tinebase_Model_Tag(array(
            'type'  => Tinebase_Model_Tag::TYPE_SHARED,
            'name'  => 'Bereitschaft',
            'description' => 'Bereitschaft für Admins',
            'color' => '#009B31',
        ));
        $bereitschaftTag = Tinebase_Tags::getInstance()->createTag($bereitschaftTag);

        $right = new Tinebase_Model_TagRight([
            'tag_id'        => $bereitschaftTag->getId(),
            'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'view_right'    => true,
            'use_right'     => true
        ]);
        Tinebase_Tags::getInstance()->setRights($right);
        
        $timesheets = [];
        
        for($i = 0; $i < 16; $i++) {
            $timesheet = new Timetracker_Model_Timesheet();
            $timesheet->timeaccount_id = ($i % 2) ? $timeaccount1->getId() : $timeaccount2->getId();
            $timesheet->is_billable = true;
            $timesheet->description = $i . ' Test Task';
            $timesheet->account_id = Tinebase_Core::getUser()->getId();
            $timesheet->start_date = (clone $invoice->start_date)->addDay($i);
            $timesheet->duration = 30;
            
            // @FIXME: not sure about this one??? when is it filled in real world data
            $timesheet->invoice_id = $invoice->getId();
            
            $timesheet = Timetracker_Controller_Timesheet::getInstance()->create($timesheet);
            $timesheets[] = $timesheet;

            if ($i % 5) {
                $filter = new Timetracker_Model_TimesheetFilter([
                    ['field' => 'id', 'operator' => 'in', 'value' => [$timesheet->getId()]]
                ]);
                Tinebase_Tags::getInstance()->attachTagToMultipleRecords($filter, $bereitschaftTag);
            }
        }
     
        $productAggregateTimeaccount1 = new Sales_Model_InvoicePosition();
        $productAggregateTimeaccount1->model = Timetracker_Model_Timeaccount::class;
        $productAggregateTimeaccount1->invoice_id = $invoice->getId();
        $productAggregateTimeaccount1->accountable_id = $timeaccount1->getId();
        $productAggregateTimeaccount1->title = $timeaccount1->title;
        $productAggregateTimeaccount1->unit = 'hour';
        $productAggregateTimeaccount1->month = '';
        $productAggregateTimeaccount1 = Sales_Controller_InvoicePosition::getInstance()->create($productAggregateTimeaccount1);

        $productAggregateTimeaccount2 = new Sales_Model_InvoicePosition();
        $productAggregateTimeaccount2->model = Timetracker_Model_Timeaccount::class;
        $productAggregateTimeaccount2->invoice_id = $invoice->getId();
        $productAggregateTimeaccount2->accountable_id = $timeaccount2->getId();
        $productAggregateTimeaccount2->title = $timeaccount2->title;
        $productAggregateTimeaccount2->unit = 'hour';
        $productAggregateTimeaccount2->month = '';
        $productAggregateTimeaccount2 = Sales_Controller_InvoicePosition::getInstance()->create($productAggregateTimeaccount2);
        
        // watch out, this call requires that ghostscript is installed on the machine and available in path through "gs"!
        $invoice = Sales_Controller_Invoice::getInstance()->createTimesheetFor($invoice->getId());
     
        static::assertInstanceOf(Sales_Model_Invoice::class, $invoice);
        static::assertEquals(3, Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachments($invoice)->count());
    }

    /**
     *
     * @throws Exception
     * @throws Tinebase_Exception
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function testGenerateTimesheetForAutoInvoice()
    {
        $this->_createFullFixtures();
        $date = clone $this->_referenceDate;
        $date->addMonth(4);
        $from = $date->setTime(0,0,0)->toString();
        $until = $date->addMonth(1)->toString();
        $dateFilter = ['field' => 'start_date', 'operator' => 'within', 'value' => [
            'from' => $from,
            'until' => $until,
        ]];
        
        $customer3Timeaccount = $this->_timeaccountRecords->filter('title', 'TA-for-Customer3')->getFirstRecord();
        $timeaccountFilter = ['field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $customer3Timeaccount->getId()];
        $tsFilter = new Timetracker_Model_TimesheetFilter([$dateFilter, $timeaccountFilter]);
        $timesheets = $this->_timesheetController->search($tsFilter);
        $bereitschaftTag = new Tinebase_Model_Tag(array(
            'type'  => Tinebase_Model_Tag::TYPE_SHARED,
            'name'  => 'Bereitschaft',
            'description' => 'Bereitschaft für Admins',
            'color' => '#009B31',
        ));
        $bereitschaftTag = Tinebase_Tags::getInstance()->createTag($bereitschaftTag);

        $right = new Tinebase_Model_TagRight([
            'tag_id'        => $bereitschaftTag->getId(),
            'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'view_right'    => true,
            'use_right'     => true
        ]);
        Tinebase_Tags::getInstance()->setRights($right);
        
        foreach ($timesheets as $idx => $timeSheet) {
            $timeSheet['is_billable'] = $idx % 2;
            
            if ($timeSheet['is_billable']) {
                $timeSheet->description = 'ts without tag';
                $timeSheet->start_time = '10:20:00';
                $timeSheet->end_time = '12:20:00';
                if ($idx === 1) {
                    $filter = new Timetracker_Model_TimesheetFilter([
                        ['field' => 'id', 'operator' => 'in', 'value' => [$timeSheet->getId()]]
                    ]);
                    Tinebase_Tags::getInstance()->attachTagToMultipleRecords($filter, $bereitschaftTag);
                    $timeSheet->description = 'ts with tag';
                }
            }
            $this->_timesheetController->update($timeSheet);
        }
        
        $i = 0;
        // the whole year, 12 months
        $date2 = clone $this->_referenceDate;
        while ($i < 12) {
            $this->_invoiceController->createAutoInvoices($date2);
            $date2->addMonth(1);
            $i++;
        }
        
        $timesheets = $this->_timesheetController->search($tsFilter);
        static::assertEquals(4, $timesheets->count());
        $billableTimesheets = $timesheets->filter('is_billable', 1);
        static::assertEquals(2, $billableTimesheets->count());
        $billableTimesheet = $billableTimesheets->getFirstRecord();
        
        $invoice = $this->_invoiceController->get($billableTimesheet['invoice_id']);

        $definition = Tinebase_ImportExportDefinition::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, [
            'model' => Sales_Model_Invoice::class,
            'name' => 'invoice_timesheet_xlsx'
        ]))->getFirstRecord()->getId();

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Invoice::class,[
            ['field' => 'id', 'operator' => 'equals', 'value' => $invoice->getId()]
        ]);
        $export = new Sales_Export_TimesheetTimeaccount($filter, null, [
            'definitionId' => $definition, 'timeaccount' => $customer3Timeaccount, 'invoice' => $invoice
        ]);
        
        try {
            // @todo caching breaks it all, we should fix caching instead of busting it
            $export->registerTwigExtension(new Tinebase_Export_TwigExtensionCacheBust(
                Tinebase_Record_Abstract::generateUID()));
            $export->generate();
            $xlsx = Tinebase_TempFile::getTempPath();
            $export->save($xlsx);
            $reader = IOFactory::createReader('Xlsx');
            $doc = $reader->load($xlsx);
            $arrayData = $doc->getActiveSheet()->rangeToArray('A1:H10');

            // search invoice positions
            $filter = new Sales_Model_InvoicePositionFilter(array());
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'title', 'operator' => 'contains', 'value' => 'TA-for-Customer3')));
            $invoicePositions = Sales_Controller_InvoicePosition::getInstance()->search($filter);
            static::assertEquals(2, count($invoicePositions));

            static::assertEquals($customer3Timeaccount['title'], $arrayData[0][2]);
            static::assertEquals($billableTimesheet['description'], $arrayData[4][1]);
            // only export start time when tag Bereitschaft is set
            $array = array_filter($arrayData, function ($data) {
                return $data[1] === 'ts with tag';
            });
            $tsWithTag = array_pop($array);
            static::assertNotNull($tsWithTag[6]);
            static::assertEquals('Bereitschaft', $tsWithTag[7]);
            $array1 = array_filter($arrayData, function ($data) {
                return $data[1] === 'ts without tag';
            });
            $tsWithoutTag = array_pop($array1);
            static::assertNull($tsWithoutTag[6]);
            static::assertNull($tsWithoutTag[7]);
        } finally {
            @unlink($xlsx);
        }
    }

    public function testExportInvoicePositionsOds()
    {
        $fsConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::FILESYSTEM);
        if (!$fsConfig || !$fsConfig->{Tinebase_Config::FILESYSTEM_CREATE_PREVIEWS}) {
            $this->markTestSkipped('PreviewService not configured.');
        }
        
        if ($this->_addressRecords === null) {
            $this->_createCustomers(1);
        }

        $bereitschaftTag = new Tinebase_Model_Tag(array(
            'type'  => Tinebase_Model_Tag::TYPE_SHARED,
            'name'  => 'Bereitschaft',
            'description' => 'Bereitschaft für Admins',
            'color' => '#009B31',
        ));
        $bereitschaftTag = Tinebase_Tags::getInstance()->createTag($bereitschaftTag);

        $right = new Tinebase_Model_TagRight([
            'tag_id'        => $bereitschaftTag->getId(),
            'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'view_right'    => true,
            'use_right'     => true
        ]);
        Tinebase_Tags::getInstance()->setRights($right);

        $costcenter = new Tinebase_Model_EvaluationDimensionItem([], true);
        $costcenter->number = 1337;
        $costcenter->name = 'Foobar Costcenter';
        $cc = Tinebase_Controller_EvaluationDimension::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_EvaluationDimension::class, [
            ['field' => Tinebase_Model_EvaluationDimension::FLD_NAME, 'operator' => 'equals', 'value' => Tinebase_Model_EvaluationDimension::COST_CENTER],
        ]), null, new Tinebase_Record_Expander(Tinebase_Model_EvaluationDimension::class, Tinebase_Model_EvaluationDimension::getConfiguration()->jsonExpander))->getFirstRecord();
        $cc->{Tinebase_Model_EvaluationDimension::FLD_ITEMS}->addRecord($costcenter);
        $cc = Tinebase_Controller_EvaluationDimension::getInstance()->update($cc);
        $costcenter = $cc->{Tinebase_Model_EvaluationDimension::FLD_ITEMS}->find(Tinebase_Model_EvaluationDimensionItem::FLD_NUMBER, $costcenter->number);

        $invoice = new Sales_Model_Invoice();
        $invoice->description = 'Foobar Rechnung';
        $invoice->start_date = (new Tinebase_DateTime())->subMonth(1);
        $invoice->end_date = new Tinebase_DateTime();
        $invoice->{Sales_Model_Invoice::FLD_DEBITOR_ID} =  $costcenter->getId();
        $invoice->address_id = $this->_addressRecords->getFirstRecord()->getId();

        /* @var $invoice Sales_Model_Invoice */
        $invoice = Sales_Controller_Invoice::getInstance()->create($invoice);

        $customer = new Sales_Model_Customer(['name' => 'Test Customer',
            Sales_Model_Customer::FLD_DEBITORS => [[
                Sales_Model_Debitor::FLD_DIVISION_ID => Sales_Controller_Division::getInstance()->getAll()->getFirstRecord()->getId(),
            ]],
        ]);
        $customer = Sales_Controller_Customer::getInstance()->create($customer);

        Tinebase_Relations::getInstance()->setRelations(
            Sales_Model_Invoice::class,
            'Sql',
            $invoice->getId(),
            [
                [
                    'related_degree' => 'sibling',
                    'related_model' => Sales_Model_Customer::class,
                    'related_backend' => 'Sql',
                    'related_id' => $customer->getId(),
                    'type' => 'CUSTOMER'
                ]
            ]
        );

        $timeaccount1 = new Timetracker_Model_Timeaccount();
        $timeaccount1->title = 'Foobar 1';
        $timeaccount1->is_billable = true;
        $timeaccount1 = Timetracker_Controller_Timeaccount::getInstance()->create($timeaccount1);

        $bereitschaftTag = new Tinebase_Model_Tag(array(
            'type'  => Tinebase_Model_Tag::TYPE_SHARED,
            'name'  => 'Bereitschaft',
            'description' => 'Bereitschaft für Admins',
            'color' => '#009B31',
        ));
        $bereitschaftTag = Tinebase_Tags::getInstance()->createTag($bereitschaftTag);

        $right = new Tinebase_Model_TagRight([
            'tag_id'        => $bereitschaftTag->getId(),
            'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'view_right'    => true,
            'use_right'     => true
        ]);
        Tinebase_Tags::getInstance()->setRights($right);
        
        $timesheet = new Timetracker_Model_Timesheet();
        $timesheet->timeaccount_id = $timeaccount1->getId();
        $timesheet->is_billable = true;
        $timesheet->description = 'ts without tag';
        $timesheet->account_id = Tinebase_Core::getUser()->getId();
        $timesheet->start_date = (clone $invoice->start_date)->addDay(0);
        $timesheet->start_time = '05:00:00';
        $timesheet->duration = 30;

        // @FIXME: not sure about this one??? when is it filled in real world data
        $timesheet->invoice_id = $invoice->getId();
        
        $timesheet1 = Timetracker_Controller_Timesheet::getInstance()->create(clone($timesheet));
        
        $timesheet->description = 'ts with tag';
        $timesheet->start_time = '06:00:00';
        $timesheet2 = Timetracker_Controller_Timesheet::getInstance()->create($timesheet);

        $filter = new Timetracker_Model_TimesheetFilter([
            ['field' => 'id', 'operator' => 'in', 'value' => [$timesheet2->getId()]]
        ]);
        Tinebase_Tags::getInstance()->attachTagToMultipleRecords($filter, $bereitschaftTag);

        $productAggregateTimeaccount1 = new Sales_Model_InvoicePosition();
        $productAggregateTimeaccount1->model = Timetracker_Model_Timeaccount::class;
        $productAggregateTimeaccount1->invoice_id = $invoice->getId();
        $productAggregateTimeaccount1->accountable_id = $timeaccount1->getId();
        $productAggregateTimeaccount1->title = $timeaccount1->title;
        $productAggregateTimeaccount1->unit = 'hour';
        $productAggregateTimeaccount1->month = '';
        $productAggregateTimeaccount1 = Sales_Controller_InvoicePosition::getInstance()->create($productAggregateTimeaccount1);
        
        $filter = new Timetracker_Model_TimesheetFilter(array(
            array('field' => 'timeaccount_id', 'operator' => 'AND', 'value' => array(
                array('condition' => 'OR', 'filters' => array(
                    array('field' => 'budget', 'operator' => 'equals', 'value' => 0),
                    array('field' => 'budget', 'operator' => 'equals', 'value' => NULL),
                )),
                array('field' => 'is_billable', 'operator' => 'equals', 'value' => TRUE),
            )),
        ));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoice->getId())));

        // test export with test template
        $config = [
            'definition' => __DIR__ . '/Export/definitions/rechnung_bereitschaft.xml',
            'app' => 'Timetracker'
        ];
        $app = Tinebase_Application::getInstance()->getApplicationByName($config['app']);
        $definition = Tinebase_ImportExportDefinition::getInstance()
            ->updateOrCreateFromFilename($config['definition'], $app);
        
        $export = Tinebase_Export::factory($filter, [
            'format' => 'ods',
            'definitionId' => $definition,
            ], Timetracker_Controller_Timesheet::getInstance());
        
        $result = $export->generate();
        $xmlBody = $export->getDocument()->asXML();
        $this->assertTrue(file_exists($result));

        $this->assertStringContainsString('ts without tag', $xmlBody, 'timesheet name is not found');
        $this->assertStringNotContainsString('05:00:00', $xmlBody, 'timesheet start time should not be set');

        $this->assertStringContainsString('ts with tag', $xmlBody, 'timesheet name is not found');
        $this->assertStringContainsString('06:00:00', $xmlBody, 'start time with tag should be set');
        // cleanup / delete file
        unlink($result);
    }
}

<?php
/**
 * Tine 2.0 - http://www.tine20.com
 *
 * Test class for Tinebase_ModelConfiguration, using the test class from hr
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */
class Tinebase_ModelConfigurationTest extends TestCase
{
    protected function tearDown(): void
{
        parent::tearDown();

        // reset mc config to prevent problems with following tests
        $customer = new Timetracker_Model_Timeaccount([], true);
        $customer->resetConfiguration();
    }

    /**
     * tests if the modelconfiguration gets created for the traditional models
     */
    public function testModelCreationTraditional()
    {
        $contact = new Addressbook_Model_Industry([], true);
        $cObj = $contact->getConfiguration();

        // at first this is just null
        $this->assertNull($cObj);
    }

    /**
     * tests if the modelconfiguration is created for foreign record keys that are disabled by a feature switch
     */
    public function testModelConfigWithDisabledForeignRecords()
    {
        // TODO disable feature first
        if (Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_INVOICES_MODULE)) {
            $this->markTestSkipped('only testable when disabled');
        }

        $timesheet = new Timetracker_Model_Timesheet(array(), true);
        $mcFields = $timesheet->getConfiguration()->getFields();
        $this->assertEquals('string', $mcFields['invoice_id']['type']);
        $this->assertEquals(null, $mcFields['invoice_id']['label']);
    }

    /**
     * testModelConfigWithDisabledRelationApp
     */
    public function testModelConfigWithDisabledRelationApp()
    {
        $sales = Tinebase_Application::getInstance()->getApplicationByName('Sales');
        Tinebase_Application::getInstance()->setApplicationStatus($sales->getId(),
            Tinebase_Application::DISABLED);
        $customer = new Timetracker_Model_Timeaccount([], true);
        $cObj = $customer->getConfiguration();
        $fields = $cObj->getFields();
        $found = false;
        foreach ($fields as $name => $field) {
            if ($name === 'contract') {
                self::assertTrue(!isset($field['label']) || $field['label'] === null,
                    'contract field should have no label: ' . print_r($field, true));
                $found = true;
            }
        }
        self::assertTrue($found);
        $filterModel = $cObj->getFilterModel();
        self::assertGreaterThan(10, count($filterModel['_filterModel']));
        foreach ($filterModel['_filterModel'] as $name => $filter) {
            if ($name === 'contract') {
                self::fail('filter model should not contain contract filter: ' . print_r($filter, true));
            }
        }
    }

    /**
     * testRelationCopyOmit
     */
    public function testRelationCopyOmit()
    {
        $timeaccount = new Timetracker_Model_Timeaccount([], true);
        $cObj = $timeaccount->getConfiguration();
        $fields = $cObj->getFields();
        foreach ($fields as $name => $fieldconfig) {
            if ($name === 'relations') {
                self::assertTrue($fieldconfig['copyOmit'],
                    'TA relations should be omitted on copy: ' . print_r($fieldconfig, true));
            }
        }

        $task = new Tasks_Model_Task([], true);
        $cObj = $task->getConfiguration();
        $fields = $cObj->getFields();
        foreach ($fields as $name => $fieldconfig) {
            if ($name === 'relations') {
                self::assertFalse($fieldconfig['copyOmit'],
                    'Tasks_Model_Task relations should not be omitted on copy: ' . print_r($fieldconfig, true));
            }
        }
    }

    /**
     * assert virtual field filters in model config
     */
    public function testVirtualFieldFilter()
    {
        $timesheet = new Timetracker_Model_Timesheet([], true);
        $cObj = $timesheet->getConfiguration();

        $filterModel = $cObj->getFilterModel();
        self::assertTrue(isset($filterModel['_filterModel']['is_billable_combined']));
        $fields = $cObj->getFields();
        self::assertTrue(isset($fields['is_billable_combined']));
        self::assertTrue(isset($fields['is_billable_combined']['config']['label']));
        self::assertTrue(isset($fields['is_billable_combined']['filterDefinition']['options']));
    }

    public function testRelationFilter()
    {
        // do not have generic "Relation" filter
        $record = new Sales_Model_OrderConfirmation([], true);
        $cObj = $record->getConfiguration();

        $filterModel = $cObj->getFilterModel();
        self::assertFalse(isset($filterModel['_filterModel']['relations']), print_r($filterModel, true));
    }

    /**
     * test config value as default
     */
    public function testConfigViaDefault()
    {
        $invoice = new Sales_Model_Invoice([], true);
        $cObj = $invoice->getConfiguration();
        $fields = $cObj->getFields();
        self::assertTrue(isset($fields['sales_tax']['default']), print_r($fields['sales_tax'], true));
        self::assertEquals(19.0, $fields['sales_tax']['default'], print_r($fields['sales_tax'], true));
    }
}

<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl<c.feitl@metawas.de>
 */

/**
 * Test class for Sales
 */
class Sales_Import_ContractTest extends TestCase
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_importContainer = null;

    protected function tearDown(): void
    {
        parent::tearDown();
        self::clear('Sales', 'Contract');
        self::clear('Sales', 'Boilerplate', [['field' => 'is_default', 'operator' => 'equals', 'value' => false]]);
    }

    public function testImportDemoData()
    {
        self::clear('Sales', 'Contract');
        $now = Tinebase_DateTime::now();
        $this->_importContainer = $this->_getTestContainer('Sales', 'Sales_Model_Contract');
        $importer = new Tinebase_Setup_DemoData_ImportSet('Sales', [
            'container_id' => $this->_importContainer->getId(),
            'files' => array('Sales.yml')
        ]);

        $importer->importDemodata();
        $filter = Sales_Model_ContractFilter::getFilterForModel('Sales_Model_Contract', [
            ['field' => 'creation_time', 'operator' => 'after_or_equals', 'value' => $now]
        ]);
        $result = Sales_Controller_Contract::getInstance()->search($filter);
        if (count($result) === 0) {
            // FIXME: test artifacts make this test fail sometimes
            self::markTestSkipped('FIXME: this fails sometimes with '
                . '"SQLSTATE[HY000]: General error: 1364 Field costcenter_id doesn\'t have a default value"');
        }
        self::assertEquals(3, count($result));
    }
}

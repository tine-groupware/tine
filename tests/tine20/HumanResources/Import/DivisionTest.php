<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl<c.feitl@metawas.de>
 */

/**
 * Test class for HumanResources
 */
class HumanResources_Import_DivisionTest extends TestCase
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_importContainer = null;

    protected function tearDown(): void
    {
        parent::tearDown();
        self::clear(HumanResources_Config::APP_NAME, HumanResources_Model_Division::MODEL_NAME_PART);
    }

    public function testImportDemoData()
    {
        try {
            $this->clear(HumanResources_Config::APP_NAME, HumanResources_Model_Division::MODEL_NAME_PART);
        } catch (Tinebase_Exception_AccessDenied $tead) {
            Tinebase_Exception::log($tead);
            self::markTestSkipped('could not clear existing data');
        }
        $now = Tinebase_DateTime::now();
        $importer = new Tinebase_Setup_DemoData_Import(HumanResources_Model_Division::class, [
            'definition' => 'hr_import_division_csv',
            'file' => 'division.csv'
        ]);
        $importer->importDemodata();
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(HumanResources_Model_Division::class, [
            ['field' => 'creation_time', 'operator' => 'after_or_equals', 'value' => $now]
        ]);
        $result = HumanResources_Controller_Division::getInstance()->search($filter);
        self::assertEquals(3, count($result));
    }
}

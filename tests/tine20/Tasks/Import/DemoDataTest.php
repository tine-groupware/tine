<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Inventory
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Inventory
 */
class Tasks_Import_DemoDataTest extends TestCase
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_importContainer = null;

    protected function tearDown(): void
{
        Tasks_Controller_Task::getInstance()->deleteByFilter(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel('Tasks_Model_Task', [
                    ['field' => 'creation_time', 'operator' => 'within', 'value' => 'dayThis']
                ]
            ));

        parent::tearDown();
    }

    public function testImportDemoData()
    {
        $this->_importContainer = $this->_getTestContainer('Tasks', 'Tasks_Model_Task');
        $importer = new Tinebase_Setup_DemoData_Import('Tasks_Model_Task', [
            'container_id' => $this->_importContainer->getId(),
            'definition' => 'tasks_import_csv',
            'file' => 'task.csv'
        ]);
        $importer->importDemodata();

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Tasks_Model_Task', [
            ['field' => 'container_id', 'operator' => 'equals', 'value' => $this->_importContainer->getId()]
        ]);
        $result = Tasks_Controller_Task::getInstance()->search($filter);
        self::assertGreaterThanOrEqual(2, count($result), print_r($result->toArray(), true));
        $abgabe = $result->filter('summary', 'Abgabe Zulassung')->getFirstRecord();
        self::assertNotNull($abgabe);
        $abgabe = Tasks_Controller_Task::getInstance()->get($abgabe);
        self::assertNotEmpty($abgabe->alarms);
        self::assertEquals(15, $abgabe->alarms[0]->minutes_before);
        $now = Tinebase_DateTime::now();
        self::assertEquals($now->get('Y-m-d'), $abgabe->due->get('Y-m-d'));
        $einkauf = $result->filter('summary', 'Einkauf Bürobedarf')->getFirstRecord();
        self::assertNotNull($einkauf);
        $nextMonday = new Tinebase_DateTime(strtotime('monday'));
        self::assertEquals($nextMonday->get('Y-m-d'), $einkauf->due->get('Y-m-d'));
    }
}

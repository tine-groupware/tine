<?php
/**
 * tine Groupware - https://www.tine-groupware.de/
 *
 * @package     Timetracker
 * @license     https://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Christian Feitl <c.feitl@metaways.de>
 */

class Timetracker_Import_TimesheetTest extends ImportTestCase
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_importContainer = null;

    public function testImportDemoData($options = [])
    {
        $tat = new Timetracker_Import_TimeaccountTest();
        $tat->importDemoData();

        // NOTE: needs timeaccount-demodata!
        if (Tinebase_DateTime::now()->setTimezone('UTC')->format('d') !== Tinebase_DateTime::now()->setTimezone(
                Tinebase_Core::getUserTimezone())->format('d')) {
            static::markTestSkipped('utc / usertimezone have a different date, test would fail');
        }

        $importer_timesheet = new Tinebase_Setup_DemoData_Import('Timetracker_Model_Timesheet', array_merge([
            'definition' => 'time_import_timesheet_csv',
            'file' => 'timesheet.csv',
        ], $options));

        $importer_timesheet->importDemodata();

        $filter_timesheet = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Timetracker_Model_Timesheet', [
            ['field' => 'creation_time', 'operator' => 'within', 'value' => 'dayThis']
        ]);
        $result_timesheet = Timetracker_Controller_TimeSheet::getInstance()->search($filter_timesheet);

        self::assertEquals(4, count($result_timesheet));
        return $result_timesheet;
    }

    public function testImportCsv()
    {
        $this->_filename = __DIR__ . '/../../../../tine20/Timetracker/Import/examples/import_timesheet_example.csv';
        $this->_deleteImportFile = false;
        $this->testImportDemoData();
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('time_import_timesheet_csv');
        $result = $this->_doImport([], $definition);
        self::assertEquals(8, $result['totalcount']);
        $importedRecord = $result['results']->filter('duration', 120)->getFirstRecord();
        self::assertNotNull($importedRecord);
        self::assertNotEquals(Tinebase_Core::getUser()->getId(), $importedRecord->account_id);
        self::assertEquals('EO-232', $importedRecord->timeaccount_id->number);
    }
}

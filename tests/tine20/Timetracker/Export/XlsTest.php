<?php
/**
 * @package     Timetracker
 * @license     https://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Timetracker Xls generation class tests
 *
 * @package     Timetracker
 * @subpackage  Export
 */
class Timetracker_Export_XlsTest extends Timetracker_AbstractTest
{
    use GetProtectedMethodTrait;

    public function testXlsxExport()
    {
        $timesheet = $this->_getTimesheet(_forceCreation: true);
        Tinebase_Core::setupUserLocale('en');
        $doc = $this->_doXlsExport(model: Timetracker_Model_Timesheet::class, definition: 'ts_overview_xls', testRecord: $timesheet);

        $arrayData = $doc->getActiveSheet()->rangeToArray('A5:E6');

        foreach ([
            'Account' => Tinebase_Core::getUser()->accountDisplayName,
            'Time Account' => 'ABCDE-1234',
            'Description' => 'blabla',
            'Duration' => '30',
             ] as $key => $value
        ) {
            $positionIndex = array_search($key, $arrayData[0], true);
            static::assertNotFalse($positionIndex, 'can\'t find ' . $key
                . ' in: ' . print_r($arrayData[0], true));
            static::assertEquals($value, $arrayData[1][$positionIndex],
                $positionIndex . ' ' . print_r($arrayData[1], true));
        }
    }

    public function testStartDateFilterInContext()
    {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Timetracker_Model_Timesheet::class, [
            ['field' => 'start_date', 'operator' => 'within', 'value' => Tinebase_Model_Filter_Date::WEEK_THIS],
        ]);
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName('ts_overview_xls');
        $export = Tinebase_Export::factory($filter, [
            'definitionId' => $definition->getId(),
        ]);

        $reflectionMethod = $this->getProtectedMethod(Timetracker_Export_Xls::class, '_getStartDateFilterForContext');
        $result = $reflectionMethod->invokeArgs($export, []);
        self::assertArrayHasKey('start', $result);
        self::assertArrayHasKey('end', $result);
        self::assertEquals(1, preg_match('/\d{4}-\d{2}-\d{2}/', $result['start']),
            'start is no date: ' . $result['start']);
    }
}

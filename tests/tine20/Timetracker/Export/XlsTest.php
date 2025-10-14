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
}

<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Calendar_Export_VCalendar
 */
class Calendar_Export_VCalendarTest extends Calendar_TestCase
{
    /**
     * @throws Tinebase_Exception_NotFound
     * @group nodockerci
     */
    public function testExportPersonalContainer()
    {
        $this->_testNeedsTransaction();

        $this->_importDemoData(
            'Calendar',
            Calendar_Model_Event::class, [
                'definition' => 'cal_import_event_csv',
                'file' => 'event.csv'
            ], $this->_getTestCalendar()
        );
        $result = $this->_export('stdout=1');

        self::assertStringContainsString('Anforderungsanalyse', $result);
        self::assertStringContainsString('BEGIN:VCALENDAR', $result);
        self::assertStringContainsString('BEGIN:VTIMEZONE', $result);
        // 4 events + 1 time in header
        self::assertEquals(5, substr_count($result, 'X-CALENDARSERVER-ACCESS:PUBLIC'),
            'X-CALENDARSERVER-ACCESS:PUBLIC should appear once in header');
    }

    protected function _export($params = '', $addContainerid = true)
    {
        $cmd = realpath(__DIR__ . "/../../../../tine20/tine20.php") . ' --method Calendar.exportVCalendar';
        $args = $addContainerid ? 'container_id=' .
            $this->_getTestCalendar()->getId() : '';
        if (! empty($params)) {
            $args .= ' ' . $params;
        }
        $cmd = TestServer::assembleCliCommand($cmd, TRUE,  $args);
        exec($cmd, $output);
        return implode(',', $output);
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_Record_Validation
     * @group nodockerci
     */
    public function testExportRecurEvent()
    {
        $this->_testNeedsTransaction();

        $event = $this->_getRecurEvent();
        Calendar_Controller_Event::getInstance()->create($event);

        $result = $this->_export('stdout=1');

        self::assertStringContainsString('hard working man needs some silence', $result);
        self::assertStringContainsString('RRULE:FREQ=DAILY', $result);
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_ConcurrencyConflict
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_Validation
     * @group nodockerci
     */
    public function testExportRecurEventWithException()
    {
        $this->_testNeedsTransaction();

        $event = $this->_getRecurEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1';

        $persistentEvent = Calendar_Controller_Event::getInstance()->create($event);
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $nextOccurance = Calendar_Model_Rrule::computeNextOccurrence($persistentEvent, $exceptions, Tinebase_DateTime::now());
        $nextOccurance->summary = 'hard working woman needs some silence';
        Calendar_Controller_Event::getInstance()->createRecurException($nextOccurance);

        $result = $this->_export('stdout=1');

        self::assertStringContainsString('hard working man needs some silence', $result);
        self::assertStringContainsString('hard working woman needs some silence', $result);
        self::assertStringContainsString('RRULE:FREQ=DAILY', $result);
        self::assertStringContainsString('RECURRENCE-ID', $result);
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_ConcurrencyConflict
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_Validation
     * @group nodockerci
     */
    public function testExportIndividualEventWithException()
    {
        $this->_testNeedsTransaction();

        $event = $this->_getRecurEvent();
        $event->rrule = 'FREQ=INDIVIDUAL;INTERVAL=1;COUNT=3';

        $persistentEvent = Calendar_Controller_Event::getInstance()->create($event);
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $nextOccurance = Calendar_Model_Rrule::computeNextOccurrence($persistentEvent, $exceptions, $event->dtstart);
        $nextOccurance->summary = 'hard working woman needs some silence';
        Calendar_Controller_Event::getInstance()->createRecurException($nextOccurance);

        $result = $this->_export('stdout=1');

        self::assertStringContainsString('hard working man needs some silence', $result);
        self::assertStringContainsString('hard working woman needs some silence', $result);
        self::assertStringContainsString('RRULE:FREQ=DAILY', $result);
        self::assertStringContainsString('RECURRENCE-ID', $result);
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     * @group nodockerci
     */
    public function testExportEventWithAlarm()
    {
        $this->_testNeedsTransaction();

        $event = $this->_getEvent();
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 30
            ), TRUE)
        ));
        Calendar_Controller_Event::getInstance()->create($event);

        $result = $this->_export('stdout=1');

        self::assertStringContainsString('Early to bed and early to rise', $result);
        self::assertStringContainsString('VALARM', $result);
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_Duplicate
     * @throws Tinebase_Exception_Record_Validation
     * @group nodockerci
     */
    public function testExportEventWithAttachment()
    {
        $this->_testNeedsTransaction();

        $event = $this->_getEvent();
        Calendar_Controller_Event::getInstance()->create($event);
        $tempFile = $this->_getTempFile();
        Tinebase_FileSystem_RecordAttachments::getInstance()->addRecordAttachment(
            $event, $tempFile->name, $tempFile);

        $result = $this->_export('stdout=1');

        self::assertStringContainsString('Early to bed and early to rise', $result);
        self::assertStringContainsString('ATTACH;ENCODING=BASE64;VALUE=BINARY;FILENAME=test.txt', $result);
        self::assertStringContainsString('APPLE-FILENAME=test.txt;FMTTYPE=text/plain:dGVzdCBmaWxlIGNvbn', $result);
    }

    /**
     * @throws Tinebase_Exception_NotFound
     * @group nodockerci
     */
    public function testExportIntoFile()
    {
        $this->_testNeedsTransaction();

        $this->_importDemoData(
            'Calendar',
            Calendar_Model_Event::class, [
                'definition' => 'cal_import_event_csv'
            ], $this->_getTestCalendar()
        );
        $filename = '/tmp/export.ics';
        $this->_export('filename=' . $filename);
        self::assertTrue(file_exists($filename), 'export file does not exist');
        $result = file_get_contents($filename);
        unlink($filename);
        self::assertStringContainsString('Anforderungsanalyse', $result);
        self::assertStringContainsString('SUMMARY:Mittag', $result);
        self::assertStringContainsString('BEGIN:VCALENDAR', $result);
        self::assertStringContainsString('BEGIN:VTIMEZONE', $result);
        self::assertStringContainsString('END:VCALENDAR', $result);
    }

    /**
     * @throws Tinebase_Exception_NotFound
     * @group nodockerci
     */
    public function testExportAllCalendars()
    {
        $this->_testNeedsTransaction();

        $this->_importDemoData(
            'Calendar',
            Calendar_Model_Event::class, [
                'definition' => 'cal_import_event_csv'
            ], $this->_getTestCalendar()
        );

        $path = Tinebase_Core::getTempDir() . DIRECTORY_SEPARATOR . 'tine20_export_' . Tinebase_Record_Abstract::generateUID(8);
        mkdir($path);
        $output = $this->_export('path=' . $path . ' type=personal', false);

        self::assertStringContainsString('Exported into file', $output);

        // loop files in export dir
        $exportFilesFound = 0;
        $fh = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($fh as $splFileInfo) {
            /** @var SplFileInfo $splFileInfo */
            $filename = $splFileInfo->getFilename();
            if ($filename === '.' || $filename === '..') {
                continue;
            }
            self::assertStringContainsString(Tinebase_Core::getUser()->accountLoginName, $filename);
            $result = file_get_contents($splFileInfo->getPathname());
            self::assertStringContainsString('END:VCALENDAR', $result);
            $exportFilesFound++;
            unlink($splFileInfo->getPathname());
        }
        self::assertGreaterThan(0, $exportFilesFound);

        rmdir($path);
    }
}

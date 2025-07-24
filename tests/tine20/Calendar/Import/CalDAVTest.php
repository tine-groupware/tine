<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test class for Calendar_Import_CalDAV
 */
class Calendar_Import_CalDAVTest extends Calendar_TestCase
{
    /**
     * unit in test
     *
     * @var Calendar_Import_CalDAV_ClientMock
     */
    protected $_uit = null;
    
    /**
     * lazy init of uit
     *
     * @return Calendar_Import_CalDAV_ClientMock
     */
    protected function _getUit()
    {
        if ($this->_uit === null) {
            $this->setUit();
        }
        
        return $this->_uit;
    }

    protected function setUit()
    {
        $testCredentials = TestServer::getInstance()->getTestCredentials();
        $caldavClientOptions = array(
            'baseUri' => 'localhost',
            'userName' => Tinebase_Core::getUser()->accountLoginName,
            'password' => $testCredentials['password'],
        );
        $this->_uit = new Calendar_Import_CalDAV_ClientMock($caldavClientOptions, 'Generic');
        $this->_uit->setVerifyPeer(false);
    }
    
    /**
     * test import of a single container/calendar of current user
     */
    public function testImportCalendar($sharedContainer = true)
    {
        $importCalendar = $this->_getTestContainer('Calendar', Calendar_Model_Event::class, $sharedContainer);

        $this->_getUit()->syncCalendarEvents('/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar/', $importCalendar);

        $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter([
            ['field' => 'container_id', 'operator' => 'in', 'value' => [$importCalendar->getId()]],
        ]));
        $this->assertSame(3, count($events));
        $etags = $events->etag;
        sort($etags);
        $this->assertSame([
                '"0b3621a20e9045d8679075db57e881dd"',
                '"8b89914690ad7290fa9a2dc1da490489"',
                '"bcc36c611f0b60bfee64b4d42e44aa1d"',
            ], $etags);
        $event = $events->getFirstRecord();
        $this->assertEmpty($event->organizer);
        $this->assertNotEmpty($event->organizer_email);
        $this->assertNotEmpty($event->external_seq);
        $this->assertSame(1, $event->attendee->count());
        $attendees = $event->attendee->filter('user_email', 'klaustu@test.net');
        $this->assertSame(1, $attendees->count());
        $this->assertEmpty($attendees->getFirstRecord()->user_id);
        $this->assertSame(Calendar_Model_Attender::USERTYPE_EMAIL, $attendees->getFirstRecord()->user_type);

        $this->_getUit()->updateServerEvents();

        $this->_getUit()->syncCalendarEvents('/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar/', $importCalendar);

        $updatedEvents = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter([
            ['field' => 'container_id', 'operator' => 'in', 'value' => [$importCalendar->getId()]],
        ]));
        $this->assertSame(3, count($updatedEvents));
        $etags = $updatedEvents->etag;
        sort($etags);
        $this->assertSame([
                '"-1030341843%40citrixonlinecom"',
                '"aa3621a20e9045d8679075db57e881dd"',
                '"bcc36c611f0b60bfee64b4d42e44aa1d"',
            ], $etags);

        $oldIds = $events->getArrayOfIds();
        sort($oldIds);
        $newIds = $updatedEvents->getArrayOfIds();
        sort($newIds);
        $this->assertNotSame($oldIds, $newIds);

        $this->assertSame('test update',
            $updatedEvents->find('etag', '"aa3621a20e9045d8679075db57e881dd"')->summary);
    }

    public function testImportCalendarPersonal()
    {
        $this->testImportCalendar(sharedContainer: false);
    }

    public function testImportCalendarTwice()
    {
        $this->testImportCalendar();
        $this->setUit();
        $this->testImportCalendar(sharedContainer: false);
    }
    
    /**
     * fetch import calendar
     * 
     * @return Tinebase_Model_Container
     */
    protected function _getImportCalendar()
    {
        $calendarUuid = sha1('/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar/');
        return Tinebase_Container::getInstance()->getByProperty($calendarUuid, 'uuid');
    }
}

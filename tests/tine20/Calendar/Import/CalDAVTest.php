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

    protected function setUit(array $options = [])
    {
        $testCredentials = TestServer::getInstance()->getTestCredentials();
        $caldavClientOptions = array_merge([
                'baseUri' => 'localhost',
                'userName' => Tinebase_Core::getUser()->accountLoginName,
                'password' => $testCredentials['password'],
                Calendar_Import_CalDAV_ClientMock::OPT_DISABLE_EXTERNAL_ORGANIZER_CALENDAR => true,
            ], $options);
        $this->_uit = new Calendar_Import_CalDAV_ClientMock($caldavClientOptions, 'Generic', $this->_personas['sclever']->accountEmailAddress);
        $this->_uit->setVerifyPeer(false);
        $this->_uit->getDecorator()->initCalendarImport($caldavClientOptions);
    }

    public function testImportWithGroupMatching(): void
    {
        $oldImapValue = Tinebase_Config::getInstance()->{Tinebase_Config::IMAP}->{Tinebase_Config::IMAP_USE_SYSTEM_ACCOUNT};
        Tinebase_Config::getInstance()->{Tinebase_Config::IMAP}->{Tinebase_Config::IMAP_USE_SYSTEM_ACCOUNT} = false;
        $imapRaii = new Tinebase_RAII(fn() => Tinebase_Config::getInstance()->{Tinebase_Config::IMAP}->{Tinebase_Config::IMAP_USE_SYSTEM_ACCOUNT} = $oldImapValue);

        $list = Addressbook_Controller_List::getInstance()->get(Tinebase_Group::getInstance()->getDefaultGroup()->list_id);
        $list->email = 'klaustu@test.net';
        Addressbook_Controller_List::getInstance()->update($list);

        unset($imapRaii);

        $this->setUit([
            Calendar_Import_CalDav_Client::OPT_SKIP_INTERNAL_OTHER_ORGANIZER => true,
            Calendar_Import_Abstract::OPTION_MATCH_ORGANIZER => true,
            Calendar_Import_Abstract::OPTION_MATCH_ATTENDEES => true,
        ]);

        $importCalendar = $this->_getTestContainer('Calendar', Calendar_Model_Event::class, true);

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

        $event = $events->find('external_id', '88F077A1-6F5B-4C6C-8D73-94C1F0127492');
        $this->assertEmpty($event->organizer);
        $this->assertNotEmpty($event->organizer_email);
        $this->assertNotEmpty($event->external_seq);
        $this->assertSame(count(Tinebase_Group::getInstance()->getGroupMembers($list->group_id)) + 1, $event->attendee->count());
        $attendees = $event->attendee->filter('user_email', 'klaustu@test.net');
        $this->assertSame(1, $attendees->count());
        $this->assertNotEmpty($attendees->getFirstRecord()->user_id);
        $this->assertSame(Calendar_Model_Attender::USERTYPE_GROUP, $attendees->getFirstRecord()->user_type);
    }

    public function testImportSkipInternalOtherOrganizer(): void
    {
        $this->setUit([
            Calendar_Import_CalDav_Client::OPT_SKIP_INTERNAL_OTHER_ORGANIZER => true,
            Calendar_Import_Abstract::OPTION_MATCH_ORGANIZER => true,
            Calendar_Import_Abstract::OPTION_MATCH_ATTENDEES => true,
        ]);
        $uitRaii = new Tinebase_RAII(fn() => $this->_uit = null);

        $importCalendar = $this->_getTestContainer('Calendar', Calendar_Model_Event::class, true);

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

        unset($uitRaii);
    }

    public function testImportSkipInternalOtherOrganizerWithStatusUpdate(): void
    {
        $calCtrl = Calendar_Controller_Event::getInstance();
        $this->setUit([
            Calendar_Import_CalDav_Client::OPT_SKIP_INTERNAL_OTHER_ORGANIZER => true,
            Calendar_Import_CalDav_Client::OPT_USE_OWN_ATTENDEE_FOR_SKIP_INTERNAL_OTHER_ORGANIZER_EVENTS => true,
            Calendar_Import_Abstract::OPTION_MATCH_ORGANIZER => true,
            Calendar_Import_Abstract::OPTION_MATCH_ATTENDEES => true,
        ]);
        $uitRaii = new Tinebase_RAII(fn() => $this->_uit = null);

        Tinebase_Core::setUser($this->_personas['sclever']);
        $event = $this->_getEvent();
        $event->container_id = $this->_getTestContainer('Calendar', Calendar_Model_Event::class);
        $event->organizer = $this->_personas['sclever']->contact_id;
        $event->external_id = '20E3E0E4-762D-42D6-A563-206161A9F1CF';
        $createdEvent = $calCtrl->create($event);

        Tinebase_Core::setUser($this->_originalTestUser);
        $importCalendar = $this->_getTestContainer('Calendar', Calendar_Model_Event::class, true);

        $this->assertSame(Calendar_Model_Attender::STATUS_NEEDSACTION, Calendar_Model_Attender::getOwnAttender($createdEvent->attendee)->status);

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

        $updatedEvent = $calCtrl->get($createdEvent->getId());
        $this->assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, Calendar_Model_Attender::getOwnAttender($updatedEvent->attendee)->status);

        unset($uitRaii);
    }

    public function testImportSkipInternalOtherOrganizerWithPartyCrush(): void
    {
        $calCtrl = Calendar_Controller_Event::getInstance();
        $this->setUit([
            Calendar_Import_CalDav_Client::OPT_SKIP_INTERNAL_OTHER_ORGANIZER => true,
            Calendar_Import_CalDav_Client::OPT_USE_OWN_ATTENDEE_FOR_SKIP_INTERNAL_OTHER_ORGANIZER_EVENTS => true,
            Calendar_Import_CalDav_Client::OPT_ALLOW_PARTY_CRUSH_FOR_SKIP_INTERNAL_OTHER_ORGANIZER_EVENTS => true,
            Calendar_Import_Abstract::OPTION_MATCH_ORGANIZER => true,
            Calendar_Import_Abstract::OPTION_MATCH_ATTENDEES => true,
        ]);
        $uitRaii = new Tinebase_RAII(fn() => $this->_uit = null);

        Tinebase_Core::setUser($this->_personas['sclever']);
        $event = $this->_getEvent();
        $event->container_id = $this->_getTestContainer('Calendar', Calendar_Model_Event::class);
        $event->organizer = $this->_personas['sclever']->contact_id;
        $event->external_id = '20E3E0E4-762D-42D6-A563-206161A9F1CF';
        $event->attendee = null;
        $createdEvent = $calCtrl->create($event);

        Tinebase_Core::setUser($this->_originalTestUser);
        $importCalendar = $this->_getTestContainer('Calendar', Calendar_Model_Event::class, true);

        $this->assertNull(Calendar_Model_Attender::getOwnAttender($createdEvent->attendee));

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

        $updatedEvent = $calCtrl->get($createdEvent->getId());
        $this->assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, Calendar_Model_Attender::getOwnAttender($updatedEvent->attendee)->status);

        unset($uitRaii);
    }

    /**
     * test import of a single container/calendar of current user
     */
    public function testImportCalendar($sharedContainer = true): void
    {
        $importCalendar = $this->_getTestContainer('Calendar', Calendar_Model_Event::class, $sharedContainer);

        $this->_getUit()->syncCalendarEvents('/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar/', $importCalendar);

        $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter([
            ['field' => 'container_id', 'operator' => 'in', 'value' => [$importCalendar->getId()]],
        ]));
        $this->assertSame(5, count($events));
        $etags = $events->etag;
        sort($etags);
        $this->assertSame([
                '"0b3621a20e9045d8679075db57e881dd"',
                '"8b89914690ad7290fa9a2dc1da490489"',
                '"bcc36c611f0b60bfee64b4d42e44aa1d"',
                '"bcc36c611f0b60bfee64b4d42e44bb1d"',
                '"bcc36c611f0b60bfee64b4d42e44bb1d"',
            ], $etags);
        $event = $events->find('external_id', '88F077A1-6F5B-4C6C-8D73-94C1F0127492');
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
        $this->assertSame(5, count($updatedEvents));
        $etags = $updatedEvents->etag;
        sort($etags);
        $this->assertSame([
                '"-1030341843%40citrixonlinecom"',
                '"aa3621a20e9045d8679075db57e881dd"',
                '"bcc36c611f0b60bfee64b4d42e44aa1d"',
                '"bcc36c611f0b60bfee64b4d42e44bb1d"',
                '"bcc36c611f0b60bfee64b4d42e44bb1d"',
            ], $etags);

        $oldIds = $events->getArrayOfIds();
        sort($oldIds);
        $newIds = $updatedEvents->getArrayOfIds();
        sort($newIds);
        $this->assertNotSame($oldIds, $newIds);

        $this->assertSame('test update',
            $updatedEvents->find('etag', '"aa3621a20e9045d8679075db57e881dd"')->summary);
    }

    public function testImportCalendarPersonal(): void
    {
        $this->testImportCalendar(sharedContainer: false);
    }

    public function testImportCalendarTwice(): void
    {
        $this->testImportCalendar();
        $this->setUit();
        $this->testImportCalendar(sharedContainer: false);
    }
    
    /**
     * fetch import calendar
     */
    protected function _getImportCalendar(): Tinebase_Model_Container
    {
        $calendarUuid = sha1('/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar/');
        return Tinebase_Container::getInstance()->getByProperty($calendarUuid, 'uuid');
    }
}

<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * Test class for Calendar_Import_CalDAV
 */
class Calendar_Import_CalDAVTest extends Calendar_TestCase
{
    /**
     * unit in test
     *
     * @var Calendar_Backend_CalDav_ClientMock
     */
    protected $_uit = null;

    public function setUp(): void
    {
        parent::setUp();

        /** @var \Laminas\Http\Headers $headers */
        $headers = Tinebase_Core::getRequest()->getHeaders();
        $headers->addHeader(new \Laminas\Http\Header\IfMatch('123'));

        $this->_uit = null;
    }
    /**
     * lazy init of uit
     *
     * @return Calendar_Backend_CalDav_ClientMock
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
                Calendar_Backend_CalDav_Client::OPT_DISABLE_EXTERNAL_ORGANIZER_CALENDAR => true,
                Calendar_Backend_CalDav_Client::OPT_NO_CACHE => true,
            ], $options);
        $this->_uit = new Calendar_Backend_CalDav_ClientMock($caldavClientOptions, 'Generic', $this->_personas['sclever']->accountEmailAddress);
        $this->_uit->setVerifyPeer(false);
        $this->_uit->getDecorator()->initCalendarImport($caldavClientOptions);
    }

    public function testGetAllCalendars(): void
    {
        $result = $this->_getUit()->findAllCollections();
        $this->assertSame(2, $result->count());
    }

    public function testSyncContainerCreateLocal(): void
    {
        $calDavClientRaii = new Tinebase_RAII(fn() => Tinebase_Model_CloudAccount_CalDAV::$_unittestCalDavClient = null);
        Tinebase_Model_CloudAccount_CalDAV::$_unittestCalDavClient = $mockClient = new Calendar_Backend_CalDav_GenericClientMock([
            'baseUri' => '/',
            Calendar_Backend_CalDav_Client::OPT_DISABLE_EXTERNAL_ORGANIZER_CALENDAR => true,
            Calendar_Backend_CalDav_Client::OPT_NO_CACHE => true,
        ], 'Generic');
        $eventCalendarData = null;
        $mockClient->multiStatusRequestDelegator = function(string $method, string $uri, string $body, int $depth) use (&$eventCalendarData) {
            static $count = 0;
            return match($count++) {
                // 01 => \Calendar_Backend_CalDav_Client::getCollectionInfos() => no proper answer required
                0 => [],
                // 03 => \Calendar_Model_SyncContainerConfig::readObjectsInCollectionFromRemote => no proper answer required
                1 => [],
                // 04 => \Calendar_Model_SyncContainerConfig::readObjectsInCollectionFromRemote => no proper answer required
                2 => [],
                // 06 => \readEventFromRemote => return event data
                3 => ['/calendars/unittest/c8e8141adf7b259b9abc72b5d06750f6656cc0b2.ics' => [
                    '{urn:ietf:params:xml:ns:caldav}calendar-data' => $eventCalendarData,
                    '{DAV:}getetag' => 'someETag',
                ]],
                // 08 => \readEventFromRemote => return event data
                4 => ['/calendars/unittest/c8e8141adf7b259b9abc72b5d06750f6656cc0b2.ics' => [
                    '{urn:ietf:params:xml:ns:caldav}calendar-data' => $eventCalendarData,
                    '{DAV:}getetag' => 'someETag1',
                ]],
                // 09 => \Calendar_Backend_CalDav_Client::getCollectionInfos() => no proper answer required
                5 => [],
                // 11 => \Calendar_Model_SyncContainerConfig::readObjectsInCollectionFromRemote => \Calendar_Backend_CalDav_Client::_fetchServerEtags
                6 => ['/calendars/unittest/c8e8141adf7b259b9abc72b5d06750f6656cc0b2.ics' => [
                    '{DAV:}getetag' => 'someETag1',
                ]],
                // 12 => \Calendar_Model_SyncContainerConfig::readObjectsInCollectionFromRemote
                7 => ['/calendars/unittest/c8e8141adf7b259b9abc72b5d06750f6656cc0b2.ics' => [
                    '{urn:ietf:params:xml:ns:caldav}calendar-data' => $eventCalendarData,
                    '{DAV:}getetag' => 'someETag1',
                ]],
                default => (function() {
                    return [];
                })(),
            };
        };
        $mockClient->propFindDelegator = function($url, $properties, $depth) {
            static $count = 0;
            return match($count++) {
                // 02 => \Calendar_Model_SyncContainerConfig::readObjectsInCollectionFromRemote ask for synctoken => no proper answer required
                // 10 => \Calendar_Model_SyncContainerConfig::readObjectsInCollectionFromRemote ask for synctoken => no proper answer required
                default => (function() {
                    return [];
                })(),
            };
        };
        $mockClient->requestDelegator = function($method, $url, $body, $headers) use (&$eventCalendarData) {
            static $count = 0;
            return match($count++) {
                // 05 => \Calendar_Backend_CalDav_Client::writeEventRemotelyStoreLocally => PUT request
                0 => ['statusCode' => ($eventCalendarData = $body) ? 201 : 201],
                // 07 => \Calendar_Backend_CalDav_Client::writeEventRemotelyStoreLocally => PUT request
                1 => ['statusCode' => ($eventCalendarData = $body) ? 201 : 201],
                default => (function() {
                    return [];
                })(),
            };
        };

        $cloudAccount = Tinebase_Controller_CloudAccount::getInstance()->create(new Tinebase_Model_CloudAccount([
            Tinebase_Model_CloudAccount::FLD_NAME => 'unittest',
            Tinebase_Model_CloudAccount::FLD_TYPE => Tinebase_Model_CloudAccount_CalDAV::class,
            Tinebase_Model_CloudAccount::FLD_OWNER_ID => Tinebase_Core::getUser()->getId(),
            Tinebase_Model_CloudAccount::FLD_CONFIG => new Tinebase_Model_CloudAccount_CalDAV([
                Tinebase_Model_CloudAccount_CalDAV::FLD_URL => 'http://localhost/unittest',
                Tinebase_Model_CloudAccount_CalDAV::FLD_USERNAME => 'unittest',
                Tinebase_Model_CloudAccount_CalDAV::FLD_PWD => 'unittest',
            ]),
        ], true));
        $container = $this->_getTestContainer(Calendar_Config::APP_NAME, Calendar_Model_Event::class, additionalData: [
            'xprops' => [
                Calendar_Controller_Event::SYNC_CONTAINER => (new Calendar_Model_SyncContainerConfig([
                    Calendar_Model_SyncContainerConfig::FLD_CLOUD_ACCOUNT_ID => $cloudAccount,
                    Calendar_Model_SyncContainerConfig::FLD_CALENDAR_PATH => '/calendars/unittest',
                ]))->dehydrate(),
            ],
        ]);

        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);

        $createdEvent = Calendar_Controller_Event::getInstance()->create($eventToCreate = new Calendar_Model_Event([
            'container_id' => $container,
            'uid' => Tinebase_Record_Abstract::generateUID(),
            'dtstart' => Tinebase_DateTime::now()->addHour(1),
            'dtend' => Tinebase_DateTime::now()->addHour(2),
            'summary' => 'test'
        ], true));

        $this->assertSame($container->getId(), $createdEvent->getIdFromProperty('container_id'));
        $this->assertSame($container->getId(), Calendar_Controller_Event::getInstance()->getBackend()->get($createdEvent->getId())->getIdFromProperty('container_id'));

        $createdEvent->summary = 'unittest';
        $updatedEvent = Calendar_Controller_Event::getInstance()->update($createdEvent);
        $this->assertNotSame($eventToCreate->summary, $updatedEvent->summary);
        $this->assertSame($container->getId(), $updatedEvent->getIdFromProperty('container_id'));
        $this->assertSame($createdEvent->getId(), $updatedEvent->getId());

        // test same "event / uid" in different container
        $container1 = $this->_getTestContainer(Calendar_Config::APP_NAME, Calendar_Model_Event::class, additionalData: [
            'xprops' => [
                Calendar_Controller_Event::SYNC_CONTAINER => (new Calendar_Model_SyncContainerConfig([
                    Calendar_Model_SyncContainerConfig::FLD_CLOUD_ACCOUNT_ID => $cloudAccount,
                    Calendar_Model_SyncContainerConfig::FLD_CALENDAR_PATH => '/calendars/unittest',
                ]))->dehydrate(),
            ],
        ]);

        $syncedEvents = Calendar_Controller_Event::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Calendar_Model_Event::class, [
            [TMFA::FIELD => 'container_id', TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $container1->getId()],
        ]));
        $this->assertSame(1, $syncedEvents->count());
        $syncedEvent = $syncedEvents->getFirstRecord();
        $this->assertSame($syncedEvent->uid, $updatedEvent->uid);
        $this->assertSame($syncedEvent->external_id, $updatedEvent->external_id);
        $this->assertNotSame($syncedEvent->getId(), $updatedEvent->getId());

        unset($calDavClientRaii);
    }

    public function testVTodoImportCreateSharedContainer(): void
    {
        $importCalendar = $this->_getTestContainer('Calendar', Calendar_Model_Event::class, true);

        $import = new Calendar_Import_CalDAV([
            'container_id' => $importCalendar->getId(),
            'url' => 'https://some.domain.invalidTld/foo/fee/fum',
            Calendar_Import_Abstract::OPTION_IMPORT_VTODOS => true,
            'calDavRequestTries' => 1,
        ]);

        try {
            $import->import();
        } catch (\Sabre\HTTP\ClientException){}

        $taskContainer = Tinebase_Container::getInstance()
            ->getContainerByName(Tasks_Model_Task::class, $importCalendar->name, $importCalendar->type);
        $tasks = Tasks_Controller_Task::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tasks_Model_Task::class, [
            ['field' => 'container_id', 'operator' => 'equals', 'value' => $taskContainer->getId()],
        ]));
        $this->assertSame(0, $tasks->count());
    }

    public function testVTodoImportTask(): void
    {
        $this->setUit([
            Calendar_Backend_CalDav_Client::OPT_SKIP_INTERNAL_OTHER_ORGANIZER => true,
            Calendar_Import_Abstract::OPTION_MATCH_ORGANIZER => true,
            Calendar_Import_Abstract::OPTION_MATCH_ATTENDEES => true,
            Calendar_Import_Abstract::OPTION_IMPORT_VTODOS => true,
        ]);

        $importCalendar = $this->_getTestContainer('Calendar', Calendar_Model_Event::class, true);

        $this->_getUit()->syncCalendarEvents('/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar/', $importCalendar);

        $container = Tinebase_Container::getInstance()->getDefaultContainer(Tasks_Model_Task::class);
        $tasks = Tasks_Controller_Task::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tasks_Model_Task::class, [
            ['field' => 'container_id', 'operator' => 'equals', 'value' => $container->getId()],
        ]));
        $this->assertSame(1, $tasks->count());
    }

    public function testImportWithGroupMatching(): void
    {
        $this->_skipIfLDAPBackend('Zend_Ldap_Exception: 0x44 (Already exists): adding: cn=unittest,ou=groups,dc=tine,dc=test');

        $oldImapValue = Tinebase_Config::getInstance()->{Tinebase_Config::IMAP}->{Tinebase_Config::IMAP_USE_SYSTEM_ACCOUNT};
        Tinebase_Config::getInstance()->{Tinebase_Config::IMAP}->{Tinebase_Config::IMAP_USE_SYSTEM_ACCOUNT} = false;
        $imapRaii = new Tinebase_RAII(fn() => Tinebase_Config::getInstance()->{Tinebase_Config::IMAP}->{Tinebase_Config::IMAP_USE_SYSTEM_ACCOUNT} = $oldImapValue);

        $group = Admin_Controller_Group::getInstance()->create(new Tinebase_Model_Group([
            'name' => 'unittest',
            'email' => 'klaustu@test.net',
        ]));

        Admin_Controller_Group::getInstance()->addGroupMember($group->getId(), $this->_personas['sclever']);
        Admin_Controller_Group::getInstance()->addGroupMember($group->getId(), $this->_personas['jmcblack']);

        unset($imapRaii);

        $this->setUit([
            Calendar_Backend_CalDav_Client::OPT_SKIP_INTERNAL_OTHER_ORGANIZER => true,
            Calendar_Import_Abstract::OPTION_MATCH_ORGANIZER => true,
            Calendar_Import_Abstract::OPTION_MATCH_ATTENDEES => true,
        ]);

        $importCalendar = $this->_getTestContainer('Calendar', Calendar_Model_Event::class, true);

        /*$syncState = Calendar_Backend_CalDav_SyncState::getSyncStateFromContainer($importCalendar, 'default');
        $syncState->setSyncTokenSupport(false);
        $syncState->storeInContainer($importCalendar);*/

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
        $this->assertSame(count(Tinebase_Group::getInstance()->getGroupMembers($group->getId())) + 1, $event->attendee->count());
        $attendees = $event->attendee->filter('user_email', 'klaustu@test.net');
        $this->assertSame(1, $attendees->count());
        $this->assertSame(Addressbook_Controller_List::getInstance()->getBackend()->getByGroupName($group->name, null)?->getId(), $attendees->getFirstRecord()->user_id);
        $this->assertSame(Calendar_Model_Attender::USERTYPE_GROUP, $attendees->getFirstRecord()->user_type);
    }

    public function testImportSkipInternalOtherOrganizer(): void
    {
        $this->setUit([
            Calendar_Backend_CalDav_Client::OPT_SKIP_INTERNAL_OTHER_ORGANIZER => true,
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
            Calendar_Backend_CalDav_Client::OPT_SKIP_INTERNAL_OTHER_ORGANIZER => true,
            Calendar_Backend_CalDav_Client::OPT_USE_OWN_ATTENDEE_FOR_SKIP_INTERNAL_OTHER_ORGANIZER_EVENTS => true,
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
            Calendar_Backend_CalDav_Client::OPT_SKIP_INTERNAL_OTHER_ORGANIZER => true,
            Calendar_Backend_CalDav_Client::OPT_USE_OWN_ATTENDEE_FOR_SKIP_INTERNAL_OTHER_ORGANIZER_EVENTS => true,
            Calendar_Backend_CalDav_Client::OPT_ALLOW_PARTY_CRUSH_FOR_SKIP_INTERNAL_OTHER_ORGANIZER_EVENTS => true,
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

    public function testImportCalendarTwiceNoRecreate(): void
    {
        $this->testImportCalendar();

        $this->setUit([
            Calendar_Import_Abstract::OPTION_ENFORCE_RECREATE_IN_TARGET_CONTAINER => false,
        ]);
        $importCalendar = $this->_getTestContainer('Calendar', Calendar_Model_Event::class, false);
        $this->_getUit()->syncCalendarEvents('/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar/', $importCalendar);
        $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter([
            ['field' => 'container_id', 'operator' => 'in', 'value' => [$importCalendar->getId()]],
        ]));
        $this->assertSame(0, count($events));
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

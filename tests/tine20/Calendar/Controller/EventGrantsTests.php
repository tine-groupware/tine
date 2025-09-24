<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Calendar_Controller_Event
 * 
 * @todo:
 *  - add free/busy cleanup tests
 * 
 * @package     Calendar
 */
class Calendar_Controller_EventGrantsTests extends Calendar_TestCase
{
    
    /**
     * @var Calendar_Controller_Event controller unter test
     */
    protected $_uit;
    
    public function setUp(): void
{
        parent::setUp();
        
        /**
         * set up personas personal container grants:
         * 
         *  jsmith:    anyone freebusyGrant, readGrant, addGrant, editGrant, deleteGrant
         *  pwulf:     anyone readGrant, syncGrant, sclever addGrant, readGrant, editGrant, deleteGrant, privateGrant
         *  sclever:   testuser addGrant, readGrant, editGrant, deleteGrant, privateGrant
         *  jmcblack:  prim group of testuser readGrant, testuser privateGrant
         *  rwright:   testuser freebusyGrant, sclever has readGrant and editGrant
         */
        $this->_setupTestCalendars();
        
        $this->_uit = Calendar_Controller_Event::getInstance();
        $this->_uit->doContainerACLChecks(true);
    }
    
    public function tearDown(): void
{
        parent::tearDown();
        
        if (! $this->_transactionId) {
            $this->cleanupTestCalendars();
        }
    }
    
    /**
     * a new personal container schould give free/busy to anyone
     */
    public function testAddPersonalCalendarGrants()
    {
        $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($this->_getTestCalendar(), TRUE);
        $anyoneIdx = array_search(Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE, $grants->account_type);
        $this->assertTrue($anyoneIdx !== false, 'anyone has no grant entry');
        $this->assertTrue($grants[$anyoneIdx]->{Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY}, 'anyone has not freebusy grant');
    }
    
    /**
     * reads an event of the personal calendar of jsmith
     *  -> anyone has readGrant, editGrant and deleteGrant
     */
    public function testGrantsByContainerAnyone()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('jsmith', 'jsmith', 'jsmith');
        
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
        $this->assertEquals($persistentEvent->summary, $loadedEvent->summary);
        $this->assertTrue((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_EDIT});
        $this->assertTrue((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_DELETE});
    }
    
    /**
     * reads an event of the personal calendar of sclever
     *  -> test user has readGrant, editGrant and deleteGrant
     */
    public function testGrantsByContainerUser()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('sclever', 'sclever', 'sclever');
        
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
        $this->assertEquals($persistentEvent->summary, $loadedEvent->summary);
        $this->assertTrue((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_EDIT});
        $this->assertTrue((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_DELETE});
    }
    
    /**
     * reads an event of the personal calendar of jmcblack
     *  -> default group of testuser has readGrant
     */
    public function testGrantsByContainerGroup()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('jmcblack', 'jmcblack', 'jmcblack');
        
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
        $this->assertEquals($persistentEvent->summary, $loadedEvent->summary);
        $this->assertFalse((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_EDIT});
        $this->assertFalse((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_DELETE});
    }
    
    /**
     * try to read an event of the personal calendar of rwright
     *  -> no access
     */
    public function testReadGrantByContainerFail()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('rwright', 'rwright', 'rwright');
        
        $this->expectException('Tinebase_Exception_AccessDenied');
        $this->_uit->get($persistentEvent->getId());
    }

    /**
     * set role grant for "user" to rwright calendar
     * try to read an event of the personal calendar of rwright
     *  -> access because of the role
     */
    public function testReadGrantByContainerForRoleRight()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('rwright', 'rwright', 'rwright');

        $calendar  = $this->_getPersonasDefaultCals('rwright');

        $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($calendar, true);
        $grantsClass = $grants->getRecordClassName();
        $grants->addRecord(new $grantsClass(array(
                'account_id'    => Tinebase_Acl_Roles::getInstance()->getRoleByName('user role')->getId(),
                'account_type'  => 'role',
                Tinebase_Model_Grants::GRANT_READ     => true,
                Tinebase_Model_Grants::GRANT_ADD      => true,
                Tinebase_Model_Grants::GRANT_EDIT     => true,
                Tinebase_Model_Grants::GRANT_DELETE   => true,
                Calendar_Model_EventPersonalGrants::GRANT_PRIVATE => true,
                Tinebase_Model_Grants::GRANT_ADMIN    => true,
                Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY => true,
        )));
        Tinebase_Container::getInstance()->setGrants($calendar, $grants, true);

        $event = $this->_uit->get($persistentEvent->getId());
        $event->summary = 'role update';
        $updatedEvent = $this->_uit->update($event);

        static::assertEquals($event->summary, $updatedEvent->summary);
    }
    
    /**
     * reads an event of the personal calendar of rwight
     *  -> test user is attender with implicit readGrant
     *  -> test user can update his status
     *  -> test user can delete
     */
    public function testGrantsByAttender()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('rwright', 'rwright', NULL);
        
        // try read
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
        $this->assertEquals($persistentEvent->summary, $loadedEvent->summary);
        $this->assertFalse((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_EDIT});
        $this->assertFalse((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_DELETE});
        
        // try status update
        $loadedEvent->attendee[0]->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        $this->_uit->update($loadedEvent);
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $loadedEvent->attendee[0]->status);
        
        // try delete (implicit DECLINE atm.
        $this->_uit->delete($persistentEvent->getId());
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
        $this->assertEquals(Calendar_Model_Attender::STATUS_DECLINED, $loadedEvent->attendee[0]->status);
    }
    
    /**
     * reads an event of the personal calendar of rwright
     *  -> set testuser to organizer! -> implicit readGrand and editGrant
     */
    public function testGrantsByOrganizer()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('rwright', NULL, 'rwright');
        
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
        $this->assertEquals($persistentEvent->summary, $loadedEvent->summary);
        $this->assertTrue((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_EDIT}, Tinebase_Model_Grants::GRANT_EDIT);
        $this->assertTrue((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_DELETE}, Tinebase_Model_Grants::GRANT_DELETE);
    }
    
    /**
     * reads an event of the personal calendar of rwright
     *  -> sclever is attender -> testuser has readGrant for scelver
     */
    public function testGrantsByInheritedAttendeeContainerGrantsGet()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('rwright', 'rwright', 'sclever');
        
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
        
        $this->assertEquals($persistentEvent->summary, $loadedEvent->summary);
    }
    
    /**
     * searches an event of the personal calendar of rwright
     *  -> sclever is attender -> testuser has readGrant for scelver
     */
    public function testGrantsByInheritedAttendeeContainerGrantsSearch()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('rwright', 'rwright', 'sclever');

        $events = $this->_uit->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => "/personal/{$this->_getPersona('sclever')->getId()}"),
            array('field' => 'id', 'operator' => 'equals', 'value' => $persistentEvent->getId())
        )));

        $this->assertEquals(1, count($events), 'event not found with search action!');
        $loadedEvent = $events->getFirstRecord();
        $this->assertTrue((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_READ}, 'event not readable');
        $this->assertEquals($persistentEvent->summary, $loadedEvent->summary);
    }
    
    /**
     * try to get/search event of rwright
     *  -> testuser has no Grants, but freebusy
     */
    public function testGrantsByFreeBusy()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('rwright', 'rwright', 'rwright');
        
        $events = $this->_uit->search(new Calendar_Model_EventFilter(array(
            array('field' => 'id', 'operator' => 'equals', 'value' => $persistentEvent->getId())
        )));
        
        $event = $events->getFirstRecord();
        
        $this->assertFalse(empty($event), 'no event found, but freebusy info should be');
        $this->assertTrue(empty($event->summary), 'event with freebusy only is not cleaned up');
        $this->assertFalse((bool)$event->{Tinebase_Model_Grants::GRANT_READ}, Tinebase_Model_Grants::GRANT_READ);
        $this->assertFalse((bool)$event->{Tinebase_Model_Grants::GRANT_EDIT}, Tinebase_Model_Grants::GRANT_EDIT);
        $this->assertFalse((bool)$event->{Tinebase_Model_Grants::GRANT_DELETE}, Tinebase_Model_Grants::GRANT_DELETE);
        
        // direct get of freebusy only events is not allowed
        $this->expectException('Tinebase_Exception_AccessDenied');
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
    }
    
    /**
     * reads an private event of jmcblack
     *  -> test user has private grant
     */
    public function testPrivateViaContainer()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('jmcblack', 'jmcblack', 'jmcblack', Calendar_Model_Event::CLASS_PRIVATE);
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
        $this->assertEquals($persistentEvent->summary, $loadedEvent->summary);
        $this->assertTrue((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_READ}, Tinebase_Model_Grants::GRANT_READ);
        $this->assertFalse((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_DELETE}, Tinebase_Model_Grants::GRANT_DELETE);
    }
    
    /**
     * attempt to read an private event of pwulf
     *  -> test user has no private grant
     */
    public function testPrivateViaContainerFail()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('pwulf', 'pwulf', 'pwulf', Calendar_Model_Event::CLASS_PRIVATE);
        
        $this->expectException('Tinebase_Exception_AccessDenied');
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
    }

    /**
     * attempt to search an private event of pwulf
     *  -> test user has no private grant
     */
    public function testPrivateSearchViaContainerFail()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('pwulf', 'pwulf', 'pwulf', Calendar_Model_Event::CLASS_PRIVATE);

        $result = $this->_uit->search(new Calendar_Model_EventFilter([[
            'field' => 'id', 'operator' => 'equals', 'value' => $persistentEvent->getId()
        ]]));
        static::assertSame(1, $result->count(), 'did not find created event, expect freebusy cleaned up event');
        /** @var Calendar_Model_Event $event */
        $event = $result->getFirstRecord();
        static::assertEmpty($event->summary);
    }
    
    /**
     * reads an private event of rwright with testuser as attender
     *  -> test user should have implicit read grant
     */
    public function testPrivateViaAttendee()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('rwright', 'rwright', NULL, Calendar_Model_Event::CLASS_PRIVATE);
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
        $this->assertEquals($persistentEvent->summary, $loadedEvent->summary);
        $this->assertTrue((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_READ});
        $this->assertFalse((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_EDIT});
    }
    
    /**
     * reads an private event of rwright with testuser as organizer
     *  -> test user should have implicit read+edit grant
     */
    public function testPrivateViaOrganizer()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('rwright', NULL, 'rwright', Calendar_Model_Event::CLASS_PRIVATE);
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
        $this->assertEquals($persistentEvent->summary, $loadedEvent->summary);
        $this->assertTrue((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_READ}, Tinebase_Model_Grants::GRANT_READ);
        $this->assertTrue((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_EDIT}, Tinebase_Model_Grants::GRANT_EDIT);
        $this->assertTrue((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_DELETE}, Tinebase_Model_Grants::GRANT_DELETE);
    }
    
    /**
     * reads an private event of pwulf and sclever
     *  -> test user has private grant for sclever
     */
    public function testPrivateViaAttendeeInherritance()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('pwulf', 'pwulf', 'sclever', Calendar_Model_Event::CLASS_PRIVATE);
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
        $this->assertEquals($persistentEvent->summary, $loadedEvent->summary);
        $this->assertTrue((bool)$loadedEvent->{Tinebase_Model_Grants::GRANT_READ});
    }
    
    /**
     * search an private event of pwulf
     *  -> test user has no private grant -> must be freebusy cleaned!
     */
    public function testPrivateCleanup()
    {
        $this->_assertPrivateEvent();
    }
    
    /**
     * assert private event
     * 
     * @param string $searchMethod
     * @throws InvalidArgumentException
     */
    protected function _assertPrivateEvent($searchMethod = 'search', $action = 'get', $expectResult = true)
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('pwulf', 'pwulf', 'pwulf', Calendar_Model_Event::CLASS_PRIVATE);
        
        if ($searchMethod === 'search') {
            $filterData = array(
                array('field' => 'id', 'operator' => 'equals', 'value' => $persistentEvent->getId())
            );
            $events = $this->_uit->search(new Calendar_Model_EventFilter($filterData), _action: $action);

            if ('get' === $action) {
                // assert json fe does not add history
                $json = new Calendar_Frontend_Json();
                $resolvedEvents = $json->searchEvents($filterData, array());
                $this->assertTrue(empty($resolvedEvents['results'][0]['notes']));
            }
            
        } else if ($searchMethod === 'getMultiple') {
            $events = $this->_uit->getMultiple(array($persistentEvent->getId()));
        } else {
            throw new InvalidArgumentException('unknown search method: ' . $searchMethod);
        }

        if ($expectResult) {
            $this->assertSame(1, $events->count());
            $this->assertTrue($events[0]->summary == '');
        } else {
            $this->assertSame(0, $events->count());
        }
    }

    public function testSyncCleanUp(): void
    {
        $this->_assertPrivateEvent(action: 'sync');
    }

    public function testSyncCleanUpNoResults(): void
    {
        $preferenceRaii = new Tinebase_RAII(fn() => Tinebase_Core::getPreference(Calendar_Config::APP_NAME)->setValue(Calendar_Preference::SYNC_FREE_BUSY_EVENTS, true));
        Tinebase_Core::getPreference(Calendar_Config::APP_NAME)->setValue(Calendar_Preference::SYNC_FREE_BUSY_EVENTS, false);
        $this->_assertPrivateEvent(action: 'sync', expectResult: false);
        unset($preferenceRaii);
    }
    
    /**
     * testPrivateCleanupGetMultiple
     * 
     * @see 0005400: private must lever out admin grant on get/Multiple in controller
     */
    public function testPrivateCleanupGetMultiple()
    {
        $this->_assertPrivateEvent('getMultiple');
    }
    
    /**
     * jmcblack organises with rwright
     *  => testuser shuld see freebusy of rwright
     *  
     *  @see #6388: freebusy info missing if user has only access to display calendar
     */
    public function testFreeBusyViaAttendee()
    {
        // wipe grants from jmcblack
        $cal = $this->_getPersonasDefaultCals('jmcblack');
        Tinebase_Container::getInstance()->setGrants($cal, new Tinebase_Record_RecordSet($cal->getGrantClass(), array(array(
            'account_id'    => $this->_getPersona('jmcblack')->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Calendar_Model_EventPersonalGrants::GRANT_PRIVATE => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => true,
            Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY => true,
        ))), TRUE);
        
        $persistentEvent = $this->_createEventInPersonasCalendar('jmcblack', 'jmcblack', 'rwright');
        
        $events = $this->_uit->search(new Calendar_Model_EventFilter(array(
            array('condition' => 'OR', 'filters' => array(
                array('condition' => 'AND', 'filters' =>
                    array(
                        array('field' => 'id', 'operator' => 'equals', 'value' => $persistentEvent->getId()),
                        array('field' => 'attender', 'operator' => 'in', 'value' => array(array(
                            'user_type' => 'user',
                            'user_id'   => $this->_getPersona('rwright')->contact_id,
                        ))),
                    )
                )
            ))
        )), NULL, FALSE, FALSE);
        
        $this->assertEquals(1, count($events), 'failed to search fb event');
    }
//    /**
//     * search for an attender we have no cal grants for
//     * 
//     */
//    public function testSearchGrantsByAttender()
//    {
//        // make sure we have no grants on sclevers calendar @ all
//        $this->cleanupTestCalendars();
//        
//        $persistentEvent = $this->_createEventInPersonasCalendar('rwright', 'rwright', 'rwright');
//        
//        $events = $this->_uit->search(new Calendar_Model_EventFilter(array(
//            array('field' => 'attender', 'operator' => 'in', 'value' => array(
//                array(
//                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
//                    'user_id'   => $this->_getPersonasContacts('rwright')->getId()
//                )
//            ))
//        )), NULL, FALSE, FALSE);
//        
//        print_r($events->toArray());
//    }

    /**
     * tests if search deals with record based grants correctly for 'get' action
     * 
     *  -> test user is attendee -> implicit READ GRANT
     *  
     */
    public function testSearchGrantsActionGet()
    {
        $events = $this->_testSearchGrantsActionForAction('get');
        
        $this->assertEquals(1, count($events), 'testuser has implicit readGrant, but serach for action get found no event');
    }
    
    /**
     * tests if search deals with record based grants correctly for 'get' action
     * 
     *  -> test user is attendee -> implicit SYNC GRANT
     *  
     */
    public function testSearchGrantsActionSync()
    {
        $events = $this->_testSearchGrantsActionForAction('sync');
        
        $this->assertEquals(1, count($events), 'testuser has implicit syncGrant, but serach for action sync found no event');
    }
    
    protected function _testSearchGrantsActionForAction($_action)
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('rwright', 'rwright');
        
        // for shure, this is esoteric, but it enshures that record GRANTS are in charge
        $cal = $this->_getTestCalendar();
        Tinebase_Container::getInstance()->setGrants($cal, new Tinebase_Record_RecordSet($cal->getGrantClass(), array(array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY => FALSE,
            Tinebase_Model_Grants::GRANT_READ     => FALSE,
            Tinebase_Model_Grants::GRANT_ADD      => FALSE,
            Tinebase_Model_Grants::GRANT_EDIT     => FALSE,
            Tinebase_Model_Grants::GRANT_DELETE   => FALSE,
            Calendar_Model_EventPersonalGrants::GRANT_PRIVATE => FALSE,
            Tinebase_Model_Grants::GRANT_SYNC     => FALSE,
            Tinebase_Model_Grants::GRANT_EXPORT   => FALSE,
            Tinebase_Model_Grants::GRANT_ADMIN    => TRUE,
        ))), TRUE);
        
        $events = $this->_uit->search(new Calendar_Model_EventFilter(array(
            array('field' => 'id', 'operator' => 'equals', 'value' => $persistentEvent->getId())
        )), NULL, FALSE, FALSE, $_action);
        
        return $events;
    }
    
    /**
     * tests if search deals with record based grants correctly for 'update' action
     * 
     *  -> test user is attendee -> implicit READ GRANT
     *  
     */
    public function testSearchGrantsActionUpdate()
    {
        $persistentEvent = $this->_createEventInPersonasCalendar('rwright', 'rwright');
        
        $events = $this->_uit->search(new Calendar_Model_EventFilter(array(
            array('field' => 'id', 'operator' => 'equals', 'value' => $persistentEvent->getId())
        )), NULL, FALSE, FALSE, 'update');
        
        // the admin grant of testuser of his displaycalendar let this test fail...
        // in summaray record grants are not taken into account...
        $this->assertEquals(0, count($events), 'testuser has not edit grant, but serach for action update found the event');
    }
    
    public function testCreateRecurExceptionWithEditGrantOnly()
    {
        $this->markTestIncomplete('temporarily disabled until fixed');
        
        // set testuser to have editgrant for sclever
        $cal = $this->_getPersonasDefaultCals('sclever');
        Tinebase_Container::getInstance()->setGrants($cal, new Tinebase_Record_RecordSet($cal->getGrantClass(), array(array(
            'account_id'    => $this->_getPersona('sclever')->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Calendar_Model_EventPersonalGrants::GRANT_PRIVATE => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => true,
            Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY => true,
        ), array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => false,
            Tinebase_Model_Grants::GRANT_ADD      => false,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => false,
            Calendar_Model_EventPersonalGrants::GRANT_PRIVATE => false,
            Tinebase_Model_Grants::GRANT_ADMIN    => false,
            Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY => false,
        ))), TRUE);
        
        $persistentEvent = $this->_createEventInPersonasCalendar('sclever', 'sclever');
        $persistentEvent->rrule = 'FREQ=DAILY;INTERVAL=1';
        $updatedEvent = $this->_uit->update($persistentEvent);
        
        $events = $this->_uit->search(new Calendar_Model_EventFilter(array(
            array('field' => 'id', 'operator' => 'equals', 'value' => $persistentEvent->getId()),
        )), NULL, FALSE, FALSE);
        
        $this->assertEquals(1, count($events), 'failed to search fb event');
        
        Calendar_Model_Rrule::mergeRecurrenceSet($events, $updatedEvent->dtstart, $updatedEvent->dtstart->getClone()->addDay(7));
        
        $this->assertEquals(8, count($events), 'failed to merge recurrence set');
        
        $events[3]->summary = 'exception';
        $exception = $this->_uit->createRecurException($events[3]);
        
        $this->assertEquals('exception', $exception->summary);
    }
    
    protected function _createEventInPersonasCalendar($_calendarPersona, $_organizerPersona = NULL, $_attenderPersona = NULL, $_classification = Calendar_Model_Event::CLASS_PUBLIC)
    {
        $calendarId  = $this->_getPersonasDefaultCals($_calendarPersona)->getId();
        $organizerId = $_organizerPersona ? $this->_getPersonasContacts($_organizerPersona)->getId() : $this->_getTestUserContact()->getId();
        $attenderId  = $_attenderPersona ? $this->_getPersonasContacts($_attenderPersona)->getId() : $this->_getTestUserContact()->getId();
        
        $event = $this->_getEvent();
        $event->class = $_classification;
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_id'        => $attenderId,
                'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
                'status_authkey' => Tinebase_Record_Abstract::generateUID(),
            )
        ));
        $persistentEvent = $this->_uit->create($event);
        
        // we need to adopt conainer through backend, to bypass rights control
        $persistentEvent->container_id = $calendarId;
        $persistentEvent->organizer = $organizerId;
        $this->_backend->update($persistentEvent);
        
        return $persistentEvent;
    }
    
    /**
     * set up personas personal container grants:
     * 
     *  jsmith:    anyone freebusyGrant, readGrant, addGrant, editGrant, deleteGrant
     *  pwulf:     anyone readGrant, sclever addGrant, readGrant, editGrant, deleteGrant, privateGrant
     *  sclever:   testuser addGrant, readGrant, editGrant, deleteGrant, privateGrant
     *  jmcblack:  prim group of testuser readGrant, testuser privateGrant
     *  rwright:   testuser freebusyGrant, sclever has readGrant and editGrant
     */
    protected function _setupTestCalendars()
    {
        // jsmith:     anyone freebusyGrant, readGrant, addGrant, editGrant, deleteGrant
        $cal = $this->_getPersonasDefaultCals('jsmith');
        Tinebase_Container::getInstance()->setGrants($cal, new Tinebase_Record_RecordSet($cal->getGrantClass(), array(array(
            'account_id'    => $this->_getPersona('jsmith')->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Calendar_Model_EventPersonalGrants::GRANT_PRIVATE => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => true,
            Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY => true,
        ), array(
            'account_id'    => 0,
            'account_type'  => 'anyone',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => false,
        ))), true);
        
        // pwulf:      anyone readGrant, syncGrant, sclever addGrant, readGrant, editGrant, deleteGrant, privateGrant
        $cal = $this->_getPersonasDefaultCals('pwulf');
        Tinebase_Container::getInstance()->setGrants($cal, new Tinebase_Record_RecordSet($cal->getGrantClass(), array(array(
            'account_id'    => $this->_getPersona('pwulf')->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Calendar_Model_EventPersonalGrants::GRANT_PRIVATE => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => true,
            Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY => true,
        ), array(
            'account_id'    => 0,
            'account_type'  => 'anyone',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_SYNC     => true,
            Tinebase_Model_Grants::GRANT_ADD      => false,
            Tinebase_Model_Grants::GRANT_EDIT     => false,
            Tinebase_Model_Grants::GRANT_DELETE   => false,
            Tinebase_Model_Grants::GRANT_ADMIN    => false,
        ), array(
            'account_id'    => $this->_getPersona('sclever')->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Calendar_Model_EventPersonalGrants::GRANT_PRIVATE => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => false,
        ))), true);
        
        // sclever:   testuser addGrant, readGrant, editGrant, deleteGrant, privateGrant
        $cal = $this->_getPersonasDefaultCals('sclever');
        Tinebase_Container::getInstance()->setGrants($cal, new Tinebase_Record_RecordSet($cal->getGrantClass(), array(array(
            'account_id'    => $this->_getPersona('sclever')->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Calendar_Model_EventPersonalGrants::GRANT_PRIVATE => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => true,
            Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY => true,
        ),array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Calendar_Model_EventPersonalGrants::GRANT_PRIVATE => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => false,
        ))), true);
        
        // jmacblack: prim group of testuser readGrant, testuser privateGrant
        $cal = $this->_getPersonasDefaultCals('jmcblack');
        Tinebase_Container::getInstance()->setGrants($cal, new Tinebase_Record_RecordSet($cal->getGrantClass(), array(array(
            'account_id'    => $this->_getPersona('jmcblack')->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Calendar_Model_EventPersonalGrants::GRANT_PRIVATE => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => true,
            Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY => true,
        ),array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => false,
            Tinebase_Model_Grants::GRANT_EDIT     => false,
            Tinebase_Model_Grants::GRANT_DELETE   => false,
            Calendar_Model_EventPersonalGrants::GRANT_PRIVATE => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => false,
        ),array(
            'account_id'    => Tinebase_Core::getUser()->accountPrimaryGroup,
            'account_type'  => 'group',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => false,
            Tinebase_Model_Grants::GRANT_EDIT     => false,
            Tinebase_Model_Grants::GRANT_DELETE   => false,
            Tinebase_Model_Grants::GRANT_ADMIN    => false,
        ))), true);
        
        // rwright:   testuser freebusyGrant, sclever has readGrant and editGrant
        $cal = $this->_getPersonasDefaultCals('rwright');
        Tinebase_Container::getInstance()->setGrants($cal, new Tinebase_Record_RecordSet($cal->getGrantClass(), array(array(
            'account_id'    => $this->_getPersona('rwright')->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Calendar_Model_EventPersonalGrants::GRANT_PRIVATE => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => true,
            Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY => true,
        ),array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => false,
            Tinebase_Model_Grants::GRANT_ADD      => false,
            Tinebase_Model_Grants::GRANT_EDIT     => false,
            Tinebase_Model_Grants::GRANT_DELETE   => false,
            Calendar_Model_EventPersonalGrants::GRANT_PRIVATE => false,
            Tinebase_Model_Grants::GRANT_ADMIN    => false,
            Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY => true,
        ), array(
            'account_id'    => $this->_getPersona('sclever')->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => false,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => false,
            Tinebase_Model_Grants::GRANT_ADMIN    => false,
        ))), true);
    }

    public function testCreateEventWithConflictToPrivateEvent()
    {
        $this->_testNeedsTransaction();

        $event = $this->_getEvent();
        $event->class = Calendar_Model_Event::CLASS_PRIVATE;

        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('rwright')->getId()),
        ));
        $event->organizer = $this->_getPersonasContacts('rwright')->getId();
        $this->_uit->doContainerACLChecks(false);
        $this->_uit->create($event);
        $this->_uit->doContainerACLChecks(true);

        $conflictEvent = $this->_getEvent();
        $conflictEvent->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('rwright')->getId()),
        ));

        try {
            $exectionRaised = FALSE;
            $this->_uit->create($conflictEvent, TRUE);
        } catch (Calendar_Exception_AttendeeBusy $busyException) {
            $fbData = $busyException->toArray();
            $this->assertGreaterThanOrEqual(1, count($fbData['freebusyinfo']));
            $this->assertArrayNotHasKey("description", $fbData['freebusyinfo'][0]['event'], 'testuser must not have access to event details');
            $exectionRaised = TRUE;
        }
        if (! $exectionRaised) {
            $this->fail('An expected exception has not been raised.');
        }
        $persitentConflictEvent = $this->_uit->create($conflictEvent, FALSE);

        return $persitentConflictEvent;
    }

    /**
     * resets all grants of personas calendars and deletes events from it
     */
    protected function cleanupTestCalendars()
    {
        /** @var Tinebase_Model_Container $calendar */
        foreach ($this->_getAllPersonasDefaultCals() as $loginName => $calendar) {
            Tinebase_Container::getInstance()->setGrants($calendar, new Tinebase_Record_RecordSet($calendar->getGrantClass(), array(array(
                'account_id'    => $this->_getPersona($loginName)->getId(),
                'account_type'  => 'user',
                Tinebase_Model_Grants::GRANT_READ     => true,
                Tinebase_Model_Grants::GRANT_ADD      => true,
                Tinebase_Model_Grants::GRANT_EDIT     => true,
                Tinebase_Model_Grants::GRANT_DELETE   => true,
                Calendar_Model_EventPersonalGrants::GRANT_PRIVATE => true,
                Tinebase_Model_Grants::GRANT_ADMIN    => true,
            ))), true);
            
            $events = $this->_backend->search(new Calendar_Model_EventFilter(array(
                array('field' => 'container_id', 'operator' => 'equals', 'value' => $calendar->getId()),
            )), new Tinebase_Model_Pagination(array()));
            
            // delete alarms
            Tinebase_Alarm::getInstance()->deleteAlarmsOfRecord('Calendar_Model_Event', $events->getArrayOfIds());
            
            // delete events
            foreach ($events as $event) {
                $this->_backend->delete($event->getId());
            }
        }
    }
}

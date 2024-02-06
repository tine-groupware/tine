<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test class for Calendar_Controller_Event
 * 
 * @package     Calendar
 */
class Calendar_Controller_EventTests extends Calendar_TestCase
{
    /**
     * @var Calendar_Controller_Event
     */
    protected $_controller;

    protected $_oldFileSystemConfig = null;

    /**
     * (non-PHPdoc)
     * @see Calendar_TestCase::setUp()
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->_controller = Calendar_Controller_Event::getInstance();
        $this->_oldFileSystemConfig = clone Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM};
    }

    public function tearDown(): void
    {
        Calendar_Controller_Event::unsetInstance();
        Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM} = $this->_oldFileSystemConfig;
        parent::tearDown();
    }
    
    /**
     * testCreateEvent
     * 
     * @return Calendar_Model_Event
     */
    public function testCreateEvent()
    {
        $event = $this->_getEvent();
        $persistentEvent = $this->_controller->create($event);
        
        $this->assertEquals($event->description, $persistentEvent->description);
        $this->assertTrue($event->dtstart->equals($persistentEvent->dtstart));
        $this->assertEquals(Tinebase_Core::getUserTimezone(), $persistentEvent->originator_tz);
        
        return $persistentEvent;
    }

    /**
     * testCreateEvent
     *
     * @return Calendar_Model_Event
     */
    public function testCreateEventWithBadTZ()
    {
        $event = $this->_getEvent();
        $event->originator_tz = 'BRT';
        static::expectException(Tinebase_Exception_Record_Validation::class);
        $this->_controller->create($event);
    }
    
    /**
     * testCreateAlldayEventWithoutDtend
     */
    public function testCreateAlldayEventWithoutDtend()
    {
        $event = $this->_getEvent();
        $event->is_all_day_event = true;
        $event->dtend = null;
        
        $persistentEvent = $this->_controller->create($event);
        $persistentEvent->setTimezone(Tinebase_Core::getUserTimezone());
        $this->assertEquals('2009-04-06 23:59:59', $persistentEvent->dtend->toString());
    }
    
    /**
     * testGetEvent
     */
    public function testGetEvent()
    {
        $persistentEvent = $this->testCreateEvent();
        $this->assertTrue((bool) $persistentEvent->{Tinebase_Model_Grants::GRANT_READ});
        $this->assertTrue((bool) $persistentEvent->{Tinebase_Model_Grants::GRANT_EDIT});
        $this->assertTrue((bool) $persistentEvent->{Tinebase_Model_Grants::GRANT_DELETE});
        
        $loadedEvent = $this->_controller->get($persistentEvent->getId());
        $this->assertTrue((bool) $loadedEvent->{Tinebase_Model_Grants::GRANT_READ});
        $this->assertTrue((bool) $loadedEvent->{Tinebase_Model_Grants::GRANT_EDIT});
        $this->assertTrue((bool) $loadedEvent->{Tinebase_Model_Grants::GRANT_DELETE});
    }

    public function testRelationToFakeId()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1';

        $persistentEvent = $this->_controller->create($event);
        $nextOccurance = Calendar_Model_Rrule::computeNextOccurrence($persistentEvent,
            new Tinebase_Record_RecordSet(Calendar_Model_Event::class), Tinebase_DateTime::now());
        $this->assertStringStartsWith('fakeid', $nextOccurance->getId());

        $contact = $this->_getPersonasContacts('sclever');
        $contact->relations = [
            [
                'related_id' => $nextOccurance->getId(),
                'related_model' => Calendar_Model_Event::class,
                'related_degree' => 'unittest',
                'type' => 'unittest',
            ]
        ];
        $contact = Addressbook_Controller_Contact::getInstance()->update($contact);
        /** @var Calendar_Model_Event $relatedEvent */
        $relatedEvent = $contact->relations->find('related_model', Calendar_Model_Event::class);
        $this->assertNotNull($relatedEvent);
        $this->assertSame($event->getId(), $relatedEvent->related_record->base_event_id);
    }

    public function testGetRecurInstance()
    {
        // create event and invite admin group
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1';

        $persistentEvent = $this->_controller->create($event);
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $nextOccurance = Calendar_Model_Rrule::computeNextOccurrence($persistentEvent, $exceptions, Tinebase_DateTime::now());

        $deepLink = $nextOccurance->getDeepLink();
        preg_match('/fakeid.*$/', $deepLink, $matches);
        $id = $matches[0];

        $recurInstance = $this->_controller->get($id);
        $this->assertTrue($recurInstance->isRecurInstance());
        $this->assertEquals($nextOccurance->getId(), $recurInstance->getId());

        // create recur exception in the meantime
        $nextOccurance->summary = 'exception';
        $this->_controller->createRecurException($nextOccurance);
        $recurInstance = $this->_controller->get($id);
//        print_r($recurInstance->toArray());
        $this->assertFalse($recurInstance->isRecurInstance());
        $this->assertEquals($nextOccurance->summary, $recurInstance->summary);
    }

    /**
     * testUpdateEvent
     */
    public function testUpdateEvent()
    {
        $persistentEvent = $this->testCreateEvent();
        
        $currentTz = Tinebase_Core::getUserTimezone();
        Tinebase_Core::set(Tinebase_Core::USERTIMEZONE, 'Asia/Tokyo');
        
        $persistentEvent->summary = 'Lunchtime';
        $updatedEvent = $this->_controller->update($persistentEvent);
        $this->assertEquals($persistentEvent->summary, $updatedEvent->summary);
        $this->assertEquals($currentTz, $updatedEvent->originator_tz, 'originator_tz must not be touched if dtsart is not updated!');
        
        $updatedEvent->dtstart->addHour(1);
        $updatedEvent->dtend->addHour(1);
        $secondUpdatedEvent = $this->_controller->update($updatedEvent);
        $this->assertEquals(Tinebase_Core::getUserTimezone(), $secondUpdatedEvent->originator_tz, 'originator_tz must be adopted if dtsart is updatet!');
    
        Tinebase_Core::set(Tinebase_Core::USERTIMEZONE, $currentTz);
    }

    /**
     * testConcurrentUpdate
     */
    public function testConcurrentUpdate()
    {
        $event = $this->testCreateEvent();
        
        sleep(1);
        $resolvableConcurrentEvent1 = clone $event;
        $resolvableConcurrentEvent1->dtstart = $resolvableConcurrentEvent1->dtstart->addMonth(1);
        $resolvableConcurrentEvent1->dtend = $resolvableConcurrentEvent1->dtend->addMonth(1);
        $resolvableConcurrentEvent1->rrule = new Calendar_Model_Rrule([
            'freq' => 'WEEKLY',
            'interval' => 1,
            'wkst' => 'MO',
            'byday' => 'TU',
        ]);
        $resolvableConcurrentEvent1Update = $this->_controller->update($resolvableConcurrentEvent1);
        
        sleep(1);
        $resolvableConcurrentEvent2 = clone $event;
        $resolvableConcurrentEvent2->summary = 'Updated Event';
        $resolvableConcurrentEvent2Update = $this->_controller->update($resolvableConcurrentEvent2);
        
        $this->assertEquals($resolvableConcurrentEvent1Update->dtstart, $resolvableConcurrentEvent2Update->dtstart);
        $this->assertEquals((string) $resolvableConcurrentEvent1Update->rrule, (string) $resolvableConcurrentEvent2Update->rrule);
    }
    
    public function testUpdateAttendeeStatus()
    {
        $this->_controller->setCalendarUser(new Calendar_Model_Attender([
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id' => Tinebase_Core::getUser()->contact_id,
        ]));
        
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        $event->attendee[1] = new Calendar_Model_Attender(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => $this->_getPersonasContacts('pwulf')->getId(),
        ));
        
        $persistentEvent = $this->_controller->create($event);
        
        foreach ($persistentEvent->attendee as $attender) {
            $attender->status = Calendar_Model_Attender::STATUS_DECLINED;
            $this->_controller->attenderStatusUpdate($persistentEvent, $attender, $attender->status_authkey);
        }
        
        
        $persistentEvent->last_modified_time = $this->_controller->get($persistentEvent->getId())->last_modified_time;
        
        // update time
        $persistentEvent->dtstart->addHour(2);
        $persistentEvent->dtend->addHour(2);
        // NOTE: in normal operations the status authkey is removed by resolveAttendee
        //       we simulate this here by removeing the keys per hand. (also note that current user does not need an authkey)
        $persistentEvent->attendee->status_authkey = null;
        $updatedEvent = $this->_controller->update($persistentEvent);

        $currentUser = $updatedEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_USER)
            ->filter('user_id', Tinebase_Core::getUser()->contact_id)
            ->getFirstRecord();
            
        $pwulf = $updatedEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_USER)
            ->filter('user_id', $this->_getPersonasContacts('pwulf')->getId())
            ->getFirstRecord();

        $this->assertEquals(Calendar_Model_Attender::STATUS_DECLINED, $currentUser->status, 'current users status must not be touched');
        $this->assertEquals(Calendar_Model_Attender::STATUS_NEEDSACTION, $pwulf->status, 'pwulfs status must be reset');
    }
    
    public function testUpdateMultiple()
    {
        $persistentEvent = $this->testCreateEvent();
        
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($persistentEvent->getId()))
        ));
        
        $data = array(
            'summary' => 'multipleTest'
        );
        
        $this->_controller->updateMultiple($filter, $data);
        
        $updatedEvent = $this->_controller->get($persistentEvent->getId());
        $this->assertEquals('multipleTest', $updatedEvent->summary);
    }
    
    public function testAttendeeBasics()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        $event->attendee[1] = new Calendar_Model_Attender(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => $this->_getPersonasContacts('pwulf')->getId()
        ));
        
        $persistendEvent = $this->_controller->create($event);
        $this->assertEquals(2, count($persistendEvent->attendee));
        
        unset($persistendEvent->attendee[0]);
        $updatedEvent = $this->_controller->update($persistendEvent);
        $this->assertEquals(1, count($updatedEvent->attendee));
        
        $updatedEvent->attendee->getFirstRecord()->role = Calendar_Model_Attender::ROLE_OPTIONAL;
        $updatedEvent->attendee->getFirstRecord()->transp = Calendar_Model_Event::TRANSP_TRANSP;
        
        $secondUpdatedEvent = $this->_controller->update($updatedEvent);
        $this->assertEquals(1, count($secondUpdatedEvent->attendee));
        $this->assertEquals(Calendar_Model_Attender::ROLE_OPTIONAL, $secondUpdatedEvent->attendee->getFirstRecord()->role);
        $this->assertEquals(Calendar_Model_Event::TRANSP_TRANSP, $secondUpdatedEvent->attendee->getFirstRecord()->transp);
    }

    public function testAttendeeFilter()
    {
        $event1 = $this->_getEvent();
        $event1->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_id' => Tinebase_Core::getUser()->contact_id),
            array('user_id' => $this->_getPersonasContacts('pwulf')->getId())
        ));
        $this->_controller->create($event1);
        
        $event2 = $this->_getEvent();
        $event2->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_id' => Tinebase_Core::getUser()->contact_id),
            array('user_id' => $this->_getPersonasContacts('sclever')->getId()),
        ));
        $this->_controller->create($event2);
        
        $event3 = $this->_getEvent();
        $event3->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_id' => Tinebase_Core::getUser()->contact_id),
            array('user_id' => $this->_getPersonasContacts('sclever')->getId()),
        ));
        $this->_controller->create($event3);
        
        // test sclever
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
            array('field' => 'attender'    , 'operator' => 'equals', 'value' => array(
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'   => $this->_getPersonasContacts('sclever')->getId()
            )),
        ));
        $eventsFound = $this->_controller->search($filter, new Tinebase_Model_Pagination());
        $this->assertEquals(2, count($eventsFound), 'sclever attends to two events');
        
        // test pwulf
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
            array('field' => 'attender'    , 'operator' => 'equals', 'value' => array(
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'   => $this->_getPersonasContacts('pwulf')->getId()
            )),
        ));
        $eventsFound = $this->_controller->search($filter, new Tinebase_Model_Pagination());
        $this->assertEquals(1, count($eventsFound), 'pwulf attends to one events');
        
        // test sclever OR pwulf
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
            array('field' => 'attender'    , 'operator' => 'in',     'value' => array(
                array(
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'user_id'   => $this->_getPersonasContacts('sclever')->getId()
                ),
                array (
                    'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                    'user_id'   => $this->_getPersonasContacts('pwulf')->getId()
                )
            )),
        ));
        $eventsFound = $this->_controller->search($filter, new Tinebase_Model_Pagination());
        $this->assertEquals(3, count($eventsFound), 'sclever OR pwulf attends to tree events');
    }
    
    public function testAttendeeGroupFilter()
    {
        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_id' => Tinebase_Core::getUser()->contact_id),
            array('user_id' => $this->_getPersonasContacts('sclever')->getId())
        ));
        $this->_controller->create($event);
        
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
            array('field' => 'attender'    , 'operator' => 'in',     'value' => array(
                array(
                    'user_type' => Calendar_Model_AttenderFilter::USERTYPE_MEMBEROF,
                    'user_id'   => $this->_getPersona('sclever')->accountPrimaryGroup
                )
            )),
        ));
        $eventsFound = $this->_controller->search($filter, new Tinebase_Model_Pagination());
        $this->assertEquals(1, count($eventsFound), 'sclever is groupmember');
    }

    /**
     * @see 0006702: CalDAV: single event appears in personal and shared calendar
     *
     * TODO fix for pgsql: Failed asserting that 0 matches expected 1.
     *
     */
    public function testAttendeeNotInFilter()
    {
        if ($this->_dbIsPgsql()) {
            $this->markTestSkipped('0011674: problem with Attendee "NotIn" Filter (pgsql)');
        }

        foreach(array(Tinebase_Core::getUser()->contact_id, $this->_personasContacts['sclever']->getId()) as $attId) {
            $event = $this->_getEvent();
            $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
                    array('user_id' => $attId),
            ));
            $this->_controller->create($event);
        }
    
        $filter = new Calendar_Model_EventFilter(array(
                array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
                array('field' => 'attender'    , 'operator' => 'notin',  'value' => array(
                        array(
                                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                                'user_id'   => $this->_personasContacts['sclever']->getId()
                        )
                )),
        ));
        $eventsFound = $this->_controller->search($filter, new Tinebase_Model_Pagination());
        $this->assertEquals(1, count($eventsFound), 'should be exactly one event');
        $this->assertEquals(
                Tinebase_Core::getUser()->contact_id, 
                $eventsFound->getFirstRecord()->attendee->getFirstRecord()->user_id,
                'sclevers event should not be found');
    }
    
    /**
     * test get free busy info with single event
     * 
     * @return Calendar_Model_Event
     */
    public function testGetFreeBusyInfo()
    {
        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_id' => $this->_getPersonasContacts('sclever')->getId()),
            array('user_id' => $this->_getPersonasContacts('pwulf')->getId())
        ));
        $persistentEvent = $this->_controller->create($event);

        $period = new Calendar_Model_EventFilter(array(array(
            'field'     => 'period',
            'operator'  => 'within',
            'value'     => array(
                'from'      => $persistentEvent->dtstart,
                'until'     => $persistentEvent->dtend
            ),
        )));
        $fbinfo = $this->_controller->getFreeBusyInfo($period, $persistentEvent->attendee);
       
        $this->assertGreaterThanOrEqual(2, count($fbinfo));
        
        return $persistentEvent;
    }

    /**
     * test get free busy info with single event
     *
     * @return Calendar_Model_Event
     */
    public function testGetFreeBusyInfoWithGroup()
    {
        $event = $this->_getEvent();
        $attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_id' => Tinebase_Group::getInstance()->getDefaultGroup(),
                'user_type' => Calendar_Model_Attender::USERTYPE_GROUP,
                'role' => Calendar_Model_Attender::ROLE_OPTIONAL,
            ),
        ));
        $event->attendee = clone $attendee;
        $persistentEvent = $this->_controller->create($event);

        $this->assertSame(Tinebase_Group::getInstance()->getDefaultGroup()->list_id, $persistentEvent->attendee->find(
            'user_type', Calendar_Model_Attender::USERTYPE_GROUP
        )->user_id, 'group attendee should be stored by its list id, not its group id!');

        $period = new Calendar_Model_EventFilter(array(array(
            'field'     => 'period',
            'operator'  => 'within',
            'value'     => array(
                'from'      => $persistentEvent->dtstart,
                'until'     => $persistentEvent->dtend
            ),
        )));
        $fbinfo = $this->_controller->getFreeBusyInfo($period, $attendee);

        $this->assertGreaterThanOrEqual(1, count($fbinfo));

        return $persistentEvent;
    }

    public function testSearchFreeTime()
    {
        static::markTestSkipped('rrules are disabled for search free time');

        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_id' => $this->_getPersonasContacts('sclever')->getId(), 'user_type' => Calendar_Model_Attender::USERTYPE_USER),
            array('user_id' => $this->_getPersonasContacts('pwulf')->getId(), 'user_type' => Calendar_Model_Attender::USERTYPE_USER)
        ));

        $tmp = clone $event;
        $this->_controller->create($tmp);

        $tmp = clone $event;
        $tmp->dtstart->setHour(8);
        $tmp->dtend->setHour(8);
        $tmp->attendee->removeFirst();
        $this->_controller->create($tmp);

        $tmp = clone $event;
        $tmp->dtstart->addDay(1);
        $tmp->dtend->addDay(1);
        $tmp->attendee->removeRecord($tmp->attendee->getByIndex(1));
        $this->_controller->create($tmp);

        $tmp = clone $event;
        $tmp->dtstart->addDay(2);
        $tmp->dtend->addDay(2);
        $tmp->attendee->removeRecord($tmp->attendee->getByIndex(1));
        $this->_controller->create($tmp);

        $options = array(
            'from'        => $event->dtstart->getClone()->setHour(6),
            'constraints' => array(array(
                'dtstart'   => $event->dtstart->getClone()->setHour(6),
                'dtend'     => $event->dtstart->getClone()->setHour(22),
                'rrule'     => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO,TU,WE,TH,FR'
            )),
        );

        $result = $this->_controller->searchFreeTime($event, $options);
        static::assertEquals(1, $result->count());
        static::assertEquals($options['from'], $result->getFirstRecord()->dtstart);

        $options['from'] = $event->dtstart->getClone()->setHour(8);
        $result = $this->_controller->searchFreeTime($event, $options);
        static::assertEquals(1, $result->count());
        static::assertEquals($options['from']->addMinute(30), $result->getFirstRecord()->dtstart);

        $options['from'] = $event->dtstart->getClone()->addDay(1);
        $result = $this->_controller->searchFreeTime($event, $options);
        static::assertEquals(1, $result->count());
        static::assertEquals($options['from']->addMinute(30), $result->getFirstRecord()->dtstart);

        $options['from'] = $event->dtstart->getClone()->addDay(2);
        $result = $this->_controller->searchFreeTime($event, $options);
        static::assertEquals(1, $result->count());
        static::assertEquals($options['from']->addMinute(30), $result->getFirstRecord()->dtstart);

        $options['from'] = $event->dtstart->getClone()->addDay(2)->addHour(1);
        $result = $this->_controller->searchFreeTime($event, $options);
        static::assertEquals(1, $result->count());
        static::assertEquals($options['from'], $result->getFirstRecord()->dtstart);
    }

    public function testSearchFreeTimeRule()
    {
        static::markTestSkipped('rrules are disabled for search free time');

        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_id' => $this->_getPersonasContacts('sclever')->getId(), 'user_type' => Calendar_Model_Attender::USERTYPE_USER),
            array('user_id' => $this->_getPersonasContacts('pwulf')->getId(), 'user_type' => Calendar_Model_Attender::USERTYPE_USER)
        ));
        $event->rrule = 'FREQ=WEEKLY;INTERVAL=1;BYDAY=TU,FR';

        $options = array(
            'constraints' => array(array(
                'dtstart'   => $event->dtstart->getClone()->setHour(6),
                'dtend'     => $event->dtstart->getClone()->setHour(22),
                'rrule'     => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO,TU,WE,TH,FR'
            )),
        );

        $result = $this->_controller->searchFreeTime($event, $options);
        static::assertEquals(1, $result->count());
        /** @var Calendar_Model_Event $suggestedEvent */
        $suggestedEvent = $result->getFirstRecord();
        $dtstartExpected = $event->dtstart->getClone()->addDay(1)->setHour(6); // '2009-04-07 06:00:00'
        $dtendExpected = $event->dtend->getClone()->addDay(1)->setHour(6); // '2009-04-07 06:30:00'
        static::assertEquals($dtstartExpected, $suggestedEvent->dtstart);
        static::assertEquals($dtendExpected, $suggestedEvent->dtend);

        $newEvent = clone $event;
        $newEvent->rrule = null;
        $newEvent->dtstart = $dtstartExpected->getClone()->addMinute(29); // '2009-04-07 06:29:00'
        $newEvent->dtend = $dtendExpected->getClone(); // '2009-04-07 06:30:00'
        $this->_controller->create($newEvent);
        $newEvent->setId(null);
        $newEvent->uid = null;
        $newEvent->attendee->id = null;
        $newEvent->attendee->cal_event_id = null;
        $newEvent->dtstart->addDay(7); // '2009-04-14 06:29:00'
        $newEvent->dtend->addDay(7); // '2009-04-14 06:30:00'
        $this->_controller->create($newEvent);

        $result = $this->_controller->searchFreeTime($event, $options);
        static::assertEquals(1, $result->count());
        /** @var Calendar_Model_Event $suggestedEvent */
        $suggestedEvent = $result->getFirstRecord();
        static::assertEquals($dtstartExpected->addMinute(30), $suggestedEvent->dtstart); // '2009-04-07 06:30:00'
        static::assertEquals($dtendExpected->addMinute(30), $suggestedEvent->dtend); // '2009-04-07 07:00:00'

        $newEvent->setId(null);
        $newEvent->uid = null;
        $newEvent->attendee->id = null;
        $newEvent->attendee->cal_event_id = null;
        $newEvent->dtstart->addMinute(1); // '2009-04-14 06:30:00'
        $newEvent->dtend->addMinute(1); // '2009-04-14 06:31:00'
        $this->_controller->create($newEvent);

        $result = $this->_controller->searchFreeTime($event, $options);
        static::assertEquals(1, $result->count());
        /** @var Calendar_Model_Event $suggestedEvent */
        $suggestedEvent = $result->getFirstRecord();
        static::assertEquals($dtstartExpected->addMinute(15), $suggestedEvent->dtstart); // '2009-04-07 06:45:00'
        static::assertEquals($dtendExpected->addMinute(15), $suggestedEvent->dtend); // '2009-04-07 07:15:00'
    }
    
    /**
     * events from deleted calendars should not be shown
     */
    public function testSearchEventFromDeletedCalendar() {
        $testCal = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => 'PHPUnit test calendar',
            'type'           => Tinebase_Model_Container::TYPE_PERSONAL,
            'owner_id'       => Tinebase_Core::getUser(),
            'backend'        => $this->_backend->getType(),
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'          => Calendar_Model_Event::class,
        ), true));
        
        $this->_getTestCalendars()->addRecord($testCal);
        
        // create event in testcal
        $event = $this->_getEvent();
        $event->container_id = $testCal->getId();
        $event->attendee = $this->_getAttendee();
        $persistentEvent = $this->_controller->create($event);

        // delete testcal
        Tinebase_Container::getInstance()->deleteContainer($testCal, TRUE);
        
        // search by attendee
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'attender', 'operator' => 'equals', 'value' => array(
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'   => $this->_getTestUserContact()->getId()
            ))
        )), NULL, FALSE, FALSE);
        
        $this->assertFalse(in_array($persistentEvent->getId(), $events->getId()), 'event in deleted (display) container shuld not be found');
    }
    
    public function testCreateEventWithConflict()
    {
        $this->_testNeedsTransaction();
        
        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('sclever')->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('pwulf')->getId())
        ));
        $this->_controller->create($event);
        
        $conflictEvent = $this->_getEvent();
        $conflictEvent->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('sclever')->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('pwulf')->getId())
        ));
        
        try {
            $exectionRaised = FALSE;
            $this->_controller->create($conflictEvent, TRUE);
        } catch (Calendar_Exception_AttendeeBusy $busyException) {
            $fbData = $busyException->toArray();
            $this->assertGreaterThanOrEqual(2, count($fbData['freebusyinfo']));
            $exectionRaised = TRUE;
        }
        if (! $exectionRaised) {
            $this->fail('An expected exception has not been raised.');
        }
        $persitentConflictEvent = $this->_controller->create($conflictEvent, FALSE);
        
        return $persitentConflictEvent;
    }
    
    public function testCreateEventWithConflictFromGroupMember()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        $this->_controller->create($event);
        
        $conflictEvent = $this->_getEvent();
        $conflictEvent->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('sclever')->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('pwulf')->getId())
        ));
        
        try {
            $this->_controller->create($conflictEvent, TRUE);
            $this->assertTrue(false, 'Failed to detect conflict from groupmember');
        } catch (Calendar_Exception_AttendeeBusy $busyException) {
            $fbData = $busyException->toArray();
            $this->assertGreaterThanOrEqual(2, count($fbData['freebusyinfo']));
            return;
        }
        
        $this->fail('An expected exception has not been raised.');
    }
    
    public function testCreateTransparentEventNoConflict()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        $this->_controller->create($event);
        
        $nonConflictEvent = $this->_getEvent();
        $nonConflictEvent->transp = Calendar_Model_Event::TRANSP_TRANSP;
        $nonConflictEvent->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('sclever')->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('pwulf')->getId())
        ));
        
        $this->_controller->create($nonConflictEvent, TRUE);
    }
    
    public function testCreateNoConflictParallelTrasparentEvent()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        $event->transp = Calendar_Model_Event::TRANSP_TRANSP;
        $this->_controller->create($event);
        
        $nonConflictEvent = $this->_getEvent();
        $nonConflictEvent->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('sclever')->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('pwulf')->getId())
        ));
        
        $this->_controller->create($nonConflictEvent, TRUE);
    }
    
    public function testCreateNoConflictParallelAtendeeTrasparentEvent()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        unset ($event->attendee[1]); // no group here
        $event->attendee->transp = Calendar_Model_Event::TRANSP_TRANSP;
        $this->_controller->create($event);
        
        $nonConflictEvent = $this->_getEvent();
        $nonConflictEvent->attendee = $this->_getAttendee();
        
        $this->_controller->create($nonConflictEvent, TRUE);
    }

    public function testCreateConflictResourceUnavailable()
    {
        $event = $this->_getEvent();

        // create & add resource
        $rt = new Calendar_Controller_ResourceTest();
        $rt->setUp();
        $resource = $rt->testCreateResource();
        $resource->busy_type = Calendar_Model_FreeBusy::FREEBUSY_BUSY_UNAVAILABLE;
        Calendar_Controller_Resource::getInstance()->update($resource);

        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(new Calendar_Model_Attender(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_RESOURCE,
            'user_id'   => $resource->getId()
        ))));

        $conflictEvent = clone $event;
        $this->_controller->create($event);
        try {
            $this->_controller->create($conflictEvent, TRUE);
            $this->fail('Calendar_Exception_AttendeeBusy was not thrown');
        } catch (Calendar_Exception_AttendeeBusy $abe) {
            $fb = $abe->getFreeBusyInfo();
            $this->assertEquals(Calendar_Model_FreeBusy::FREEBUSY_BUSY_UNAVAILABLE, $fb[0]->type);
        }

    }

    public function testUpdateWithConflictNoTimechange()
    {
        $persitentConflictEvent = $this->testCreateEventWithConflict();
        $persitentConflictEvent->summary = 'only time updates should recheck free/busy';
        
        $this->_controller->update($persitentConflictEvent, TRUE);
    }
    
    public function testUpdateWithConflictAttendeeChange()
    {
        $persitentConflictEvent = $this->testCreateEventWithConflict();
        $persitentConflictEvent->summary = 'attendee adds should recheck free/busy';
        
        $defaultUserGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        $persitentConflictEvent->attendee->addRecord(new Calendar_Model_Attender(array(
            'user_id'   => $defaultUserGroup->getId(),
            'user_type' => Calendar_Model_Attender::USERTYPE_GROUP,
            'role'      => Calendar_Model_Attender::ROLE_REQUIRED
        )));
        
        $this->expectException('Calendar_Exception_AttendeeBusy');
        $this->_controller->update($persitentConflictEvent, TRUE);
    }
    
    public function testUpdateWithConflictWithTimechange()
    {
        $persitentConflictEvent = $this->testCreateEventWithConflict();
        $persitentConflictEvent->summary = 'time updates should recheck free/busy';
        $persitentConflictEvent->dtend->addHour(1);
        
        $this->expectException('Calendar_Exception_AttendeeBusy');
        $this->_controller->update($persitentConflictEvent, TRUE);
    }

    public function testAttendeeAuthKeyPreserv()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        
        $persistendEvent = $this->_controller->create($event);
        $newAuthKey = Tinebase_Record_Abstract::generateUID();
        $persistendEvent->attendee->status_authkey = $newAuthKey;
        
        $updatedEvent = $this->_controller->update($persistendEvent);
        foreach ($updatedEvent->attendee as $attender) {
            $this->assertNotEquals($newAuthKey, $attender->status_authkey);
        }
    }
    
    public function testAttendeeStatusPreservViaSave()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        $event->attendee[0]->user_id = Tinebase_User::getInstance()->getUserByLoginName('sclever')->contact_id;
        $event->attendee[0]->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        unset($event->attendee[1]);
        
        $persistendEvent = $this->_controller->create($event);
        $this->assertEquals(Calendar_Model_Attender::STATUS_NEEDSACTION, $persistendEvent->attendee[0]->status, 'creation of other attedee must not set status');
        
        $persistendEvent->attendee[0]->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        $persistendEvent->attendee[0]->status_authkey = NULL;
        $updatedEvent = $this->_controller->update($persistendEvent);
        $this->assertEquals(Calendar_Model_Attender::STATUS_NEEDSACTION, $updatedEvent->attendee[0]->status, 'updateing of other attedee must not set status');
    }

    /**
     * @group nodockerci
     */
    public function testGroupMembershipChangeReflectsInAttendeeList()
    {
        $admGrpCtrl = Admin_Controller_Group::getInstance();
        $group = $admGrpCtrl->create(new Tinebase_Model_Group(['name' => 'unittest']));
        $admGrpCtrl->addGroupMember($group->getId(), $this->_personas['sclever']->getId());

        $event = $this->_getEvent(true);
        $event->attendee = new Tinebase_Record_RecordSet(Calendar_Model_Attender::class, [[
                'user_id'   => $group->list_id,
                'user_type' => Calendar_Model_Attender::USERTYPE_GROUP,
                'role'      => Calendar_Model_Attender::ROLE_REQUIRED
            ]]);
        $event = $this->_controller->create($event);
        static::assertSame(2, $event->attendee->count(), 'expect 2 attendees on event');

        $admGrpCtrl->addGroupMember($group->getId(), $this->_personas['pwulf']->getId());
        Calendar_Model_Attender::clearCache();
        $event = $this->_controller->get($event->getId());
        static::assertSame(3, $event->attendee->count(), 'expect 3 attendees on event');
    }

    public function testAttendeeSetStatus()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        unset($event->attendee[1]);
        
        $persistendEvent = $this->_controller->create($event);
        $attendee = $persistendEvent->attendee[0];
        
        $attendee->status = Calendar_Model_Attender::STATUS_DECLINED;
        $this->_controller->attenderStatusUpdate($persistendEvent, $attendee, $attendee->status_authkey);
        
        $loadedEvent = $this->_controller->get($persistendEvent->getId());
        $this->assertEquals(Calendar_Model_Attender::STATUS_DECLINED, $loadedEvent->attendee[0]->status, 'status not set');
        
    }
    
    public function testAttendeeStatusFilter()
    {
        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        unset($event->attendee[1]);
        
        $persistentEvent = $this->_controller->create($event);
        
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'uid',             'operator' => 'equals', 'value' => $persistentEvent->uid),
            array('field' => 'attender_status', 'operator' => 'not',    'value' => Calendar_Model_Attender::STATUS_DECLINED),
        ));
        
        $events = $this->_controller->search($filter);
        $this->assertEquals(1, count($events), 'event should be found, but is not');
        
        $attender = $persistentEvent->attendee[0];
        $attender->status = Calendar_Model_Attender::STATUS_DECLINED;
        $this->_controller->update($persistentEvent);
        
        $events = $this->_controller->search($filter);
        $this->assertEquals(0, count($events), 'event should _not_ be found, but is');
        
    }
    
    public function testAttendeeDisplaycontainerContact()
    {
        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
           'n_given'  => 'phpunit',
           'n_family' => 'cal attender'
        )));
         
        $event = $this->_getEvent();
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'   => $contact->getId(),
                'role'      => Calendar_Model_Attender::ROLE_REQUIRED
            ),
        ));
        $persistentEvent = $this->_controller->create($event);
        $attender = $persistentEvent->attendee[0];
        
        $this->assertTrue(empty($attender->displaycontainer_id), 'displaycontainer_id must not be set for contacts');
    }
    
    public function testAttendeeGroupMembersResolving()
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY) {
            $this->markTestSkipped('only working in non-AD setups');
        }

        $defaultUserGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        $defaultUserGroupMembers = Tinebase_Group::getInstance()->getGroupMembers($defaultUserGroup->getId());

        $event = $this->_getEvent();
        $event->attendee = $this->_getAttendee();
        $event->attendee[1] = new Calendar_Model_Attender(array(
            'user_id'   => $defaultUserGroup->getId(),
            'user_type' => Calendar_Model_Attender::USERTYPE_GROUP,
            'role'      => Calendar_Model_Attender::ROLE_REQUIRED
        ));
        
        $persistentEvent = $this->_controller->create($event);

        // user as attender + group + all members
        $expectedAttendeeCount = 1 + 1 + count($defaultUserGroupMembers);
        if (in_array(Tinebase_Core::getUser()->getId(), $defaultUserGroupMembers)) {
            // remove suppressed user (only if user is member of default group)
            $expectedAttendeeCount--;
        }
        $this->assertGreaterThanOrEqual($expectedAttendeeCount, count($persistentEvent->attendee),
            'attendee: ' . print_r($persistentEvent->attendee->toArray(), true));
        
        $groupAttender = $persistentEvent->attendee->find('user_type', Calendar_Model_Attender::USERTYPE_GROUP);
        $persistentEvent->attendee->removeRecord($groupAttender);
        
        $updatedPersistentEvent = $this->_controller->update($persistentEvent);
        $this->assertEquals(1, count($updatedPersistentEvent->attendee));
    }

    /**
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     * @throws Zend_Exception
     * @group nodockerci
     *        fails with:
     * Tinebase_Exception_NotFound: Tinebase_Model_Tree_Node record with id = b4ab92dd51c4c7ff7efdbd4cf86d1efe935c3309 not found!
     * (Tinebase_ActionQueue::getInstance()->processQueue(10000);)
     */
    public function testAttendeeGroupMembersChange()
    {
        $defaultAdminGroup = Tinebase_Group::getInstance()->getDefaultAdminGroup();
        
        // create event and invite admin group
        $event = $this->_getEvent();
        
        // only events in future will be changed!
        $event->dtstart = Tinebase_DateTime::now()->addHour(1);
        $event->dtend = Tinebase_DateTime::now()->addHour(2);
        
        $event->attendee = $this->_getAttendee();
        $event->attendee[1] = new Calendar_Model_Attender(array(
            'user_id'   => $defaultAdminGroup->getId(),
            'user_type' => Calendar_Model_Attender::USERTYPE_GROUP,
            'role'      => Calendar_Model_Attender::ROLE_REQUIRED
        ));
        $persistentEvent = $this->_controller->create($event);
        
        // assert test condition
        $pwulf = $persistentEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $this->_getPersonasContacts('pwulf')->getId());
        $this->assertEquals(0, count($pwulf), 'invalid test condition, pwulf should not be member or admin group');
        
        Admin_Controller_Group::getInstance()->addGroupMember($defaultAdminGroup->getId(), $this->_getPersonasContacts('pwulf')->account_id);
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        
        $loadedEvent = $this->_controller->get($persistentEvent->getId());
        // assert pwulf is in
        $pwulf = $loadedEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $this->_getPersonasContacts('pwulf')->getId());
        $this->assertEquals(1, count($pwulf), 'pwulf is not attender of event, but should be');
        
        
        Admin_Controller_Group::getInstance()->removeGroupMember($defaultAdminGroup->getId(), $this->_getPersonasContacts('pwulf')->account_id);
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        
        $loadedEvent = $this->_controller->get($persistentEvent->getId());
        // assert pwulf is missing
        $pwulf = $loadedEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $this->_getPersonasContacts('pwulf')->getId());
        $this->assertEquals(0, count($pwulf), 'pwulf is attender of event, but not should be');
        
        // Test the same with update
        $group = Admin_Controller_Group::getInstance()->get($defaultAdminGroup->getId());
        $group->members = array_merge(Admin_Controller_Group::getInstance()->getGroupMembers($defaultAdminGroup->getId()), array(Tinebase_Helper::array_value('pwulf', Zend_Registry::get('personas'))->getId()));
        Admin_Controller_Group::getInstance()->update($group);
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        
        // assert pwulf is in
        $loadedEvent = $this->_controller->get($persistentEvent->getId());
        $pwulf = $loadedEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $this->_getPersonasContacts('pwulf')->getId());
        $this->assertEquals(1, count($pwulf), 'pwulf is not attender of event, but should be (via update)');
        
        $group->members = array_diff(Admin_Controller_Group::getInstance()->getGroupMembers($defaultAdminGroup->getId()), array(Tinebase_Helper::array_value('pwulf', Zend_Registry::get('personas'))->getId()));
        Admin_Controller_Group::getInstance()->update($group);
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        // assert pwulf is missing
        $loadedEvent = $this->_controller->get($persistentEvent->getId());
        $pwulf = $loadedEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $this->_getPersonasContacts('pwulf')->getId());
        $this->assertEquals(0, count($pwulf), 'pwulf is attender of event, but not should be');
    }

    /**
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_Confirmation
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Tinebase_Exception_Record_Validation
     * @group nodockerci
     *        fails with:
     * Tinebase_Exception_NotFound: Tinebase_Model_Tree_Node record with id = b4ab92dd51c4c7ff7efdbd4cf86d1efe935c3309 not found!
     * (Tinebase_ActionQueue::getInstance()->processQueue(10000);)
     */
    public function testAttendeeGroupMembersAddUser()
    {
        try {
            // clean up if exists
            $cleanupUser = Tinebase_User::getInstance()->getFullUserByLoginName('testAttendeeGroupMembersAddUser');
            Tinebase_User::getInstance()->deleteUser($cleanupUser);
        } catch (Exception $e) {
            // do nothing
        }
        
        
        $defaultGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        
        // create event and invite admin group
        $event = $this->_getEvent();
        
        // only events in future will be changed!
        $event->dtstart = Tinebase_DateTime::now()->addHour(1);
        $event->dtend = Tinebase_DateTime::now()->addHour(2);
        
        $event->attendee = $this->_getAttendee();
        $event->attendee[1] = new Calendar_Model_Attender(array(
            'user_id'   => $defaultGroup->getId(),
            'user_type' => Calendar_Model_Attender::USERTYPE_GROUP,
            'role'      => Calendar_Model_Attender::ROLE_REQUIRED
        ));
        $persistentEvent = $this->_controller->create($event);
        
        $newUser = $this->_createNewUser();
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        
        // check if this user was added to event
        $loadedEvent = $this->_controller->get($persistentEvent->getId());
        $user = $loadedEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $newUser->contact_id);
        $this->assertEquals(1, count($user), 'added user is not attender of event, but should be. user: ' . print_r($newUser->toArray(), TRUE));
        
        // cleanup user
        // user deletion need the confirmation header
        Admin_Controller_User::getInstance()->setRequestContext(['confirm' => true]);
        Admin_Controller_User::getInstance()->delete($newUser->getId());
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        
        // check if user was removed from event
        $loadedEvent = $this->_controller->get($persistentEvent->getId());
        $user = $loadedEvent->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $newUser->contact_id);
        $this->assertEquals(0, count($user), 'added user is attender of event, but should be (after deleting user)');
    }
    
    /**
     * testAttendeeGroupMembersRecurringAddUser
     * 
     * FIXME 0007352: fix Calendar_Controller_EventTests::testAttendeeGroupMembersRecurringAddUser
     */
    public function testAttendeeGroupMembersRecurringAddUser()
    {
        $this->markTestIncomplete('test fails sometimes / needs fixing');
        
        try {
            // cleanup if exists
            $cleanupUser = Tinebase_User::getInstance()->getFullUserByLoginName('testAttendeeGroupMembersAddUser');
            Tinebase_User::getInstance()->deleteUser($cleanupUser);
        } catch (Exception $e) {
            // do nothing
        }
        
        $defaultGroup = Tinebase_Group::getInstance()->getDefaultGroup();
        
        // create event and invite admin group
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1';
        
        $event->attendee = $this->_getAttendee();
        $event->attendee[1] = new Calendar_Model_Attender(array(
            'user_id'   => $defaultGroup->getId(),
            'user_type' => Calendar_Model_Attender::USERTYPE_GROUP,
            'role'      => Calendar_Model_Attender::ROLE_REQUIRED
        ));
        $persistentEvent = $this->_controller->create($event);

        $newUser = $this->_createNewUser();
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        
        $events = $this->_backend->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'in', 'value' => $this->_getTestCalendars()->getId()),
        )), new Tinebase_Model_Pagination(array()));
        
        $oldSeries = $events->filter('rrule_until', '/.+/', TRUE)->getFirstRecord();
        $newSeries = $events->filter('rrule_until', '/^$/', TRUE)->getFirstRecord();
        
        $this->assertEquals(2, $events->count(), 'recur event must be splitted '. print_r($events->toArray(), TRUE));
        // check if this user was added to event
        $this->_controller->get($persistentEvent->getId());
        $user = $oldSeries->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $newUser->contact_id);
        $this->assertEquals(0, count($user), 'added user is attender of old event, but should not be');
        $user = $newSeries->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $newUser->contact_id);
        $this->assertEquals(1, count($user), 'added user is not attender of new event, but should be');
        
        // cleanup user
        // user deletion need the confirmation header
        Admin_Controller_User::getInstance()->setRequestContext(['confirm' => true]);
        Admin_Controller_User::getInstance()->delete($newUser->getId());
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        
        $events = $this->_backend->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'in', 'value' => $this->_getTestCalendars()->getId()),
        )), new Tinebase_Model_Pagination(array()));
        
        $newSeries = $events->filter('rrule_until', '/^$/', TRUE)->getFirstRecord();
        
        // check if this user was deleted from event
        $user = $newSeries->attendee
            ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
            ->filter('user_id', $newUser->contact_id);
        $this->assertEquals(0, count($user), 'deleted user is attender of new event, but should not be');
    }

    protected function _createNewUser()
    {
        $pw = Tinebase_Record_Abstract::generateUID(10) . '*A53x';
        $newUser = Admin_Controller_User::getInstance()->create(new Tinebase_Model_FullUser(array(
            'accountLoginName'      => 'testAttendeeGroupMembersAddUser',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit',
            'accountEmailAddress'   => 'phpunit@' . TestServer::getPrimaryMailDomain(),
        )), $pw, $pw);
        return $newUser;
    }

    public function testRruleUntil()
    {
        $event = $this->_getEvent();
        
        $event->rrule_until = Tinebase_DateTime::now();
        $persistentEvent = $this->_controller->create($event);
        $this->assertNull($persistentEvent->rrule_until, 'rrul_until is not unset');
        
        $persistentEvent->rrule = 'FREQ=YEARLY;INTERVAL=1;BYMONTH=2;UNTIL=2010-04-01 21:59:59';
        $updatedEvent = $this->_controller->update($persistentEvent);
        $this->assertEquals('2010-04-01 21:59:59', $updatedEvent->rrule_until->get(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    public function testUpdateRecuingDtstart()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-30 21:59:59';
        $event->exdate = array(new Tinebase_DateTime('2009-04-07 13:00:00'));
        $persistentEvent = $this->_controller->create($event);

        $this->assertEquals('2009-04-30 21:59:59', $persistentEvent->rrule->until->toString(), 'rrule is not adapted');

        $exception = clone $persistentEvent;
        $exception->dtstart->addDay(2);
        $exception->dtend->addDay(2);

        $exception->setId(NULL);
        unset($exception->rrule);
        unset($exception->exdate);
        $exception->setRecurId($event->getId());
        $persistentException = $this->_controller->createRecurException($exception);

        $loadedEvent = $this->_controller->get($persistentEvent->getId());
        $loadedEvent->dtstart->addHour(5);
        $loadedEvent->dtend->addHour(5);

        $updatedEvent = $this->_controller->update($loadedEvent);

        $updatedException = $this->_controller->get($persistentException->getId());
        $exdates = array_map(function($date) {return $date->format(Tinebase_Record_Abstract::ISO8601LONG);}, $updatedEvent->exdate);

        $this->assertEquals(2, count($updatedEvent->exdate), 'failed to reset exdate');
        $this->assertTrue(in_array('2009-04-07 18:00:00', $exdates, 'fallout exception not exdate not adopted'));
        $this->assertTrue(in_array('2009-04-08 18:00:00', $exdates, 'persistend exception not exdate not adopted'));
        $this->assertEquals('2009-04-08 18:00:00', substr($updatedException->recurid, -19), 'failed to update persistent exception');
        $this->assertEquals('2009-04-30 02:59:59', Calendar_Model_Rrule::getRruleFromString($updatedEvent->rrule)->until->get(Tinebase_Record_Abstract::ISO8601LONG), 'until not changed');
        $this->assertEquals('2009-04-30 02:59:59', $updatedEvent->rrule_until->get(Tinebase_Record_Abstract::ISO8601LONG), 'rrule_until not changed');

        $updatedEvent->dtstart->subHour(5);
        $updatedEvent->dtend->subHour(5);
        $secondUpdatedEvent = $this->_controller->update($updatedEvent);
        $secondUpdatedException = $this->_controller->get($persistentException->getId());
        $exdates = array_map(function($date) {return $date->format(Tinebase_Record_Abstract::ISO8601LONG);}, $updatedEvent->exdate);

        $this->assertTrue(in_array('2009-04-07 13:00:00', $exdates, 'fallout exception not exdate not adopted'));
        $this->assertTrue(in_array('2009-04-08 13:00:00', $exdates, 'persistend exception not exdate not adopted'));

        $this->assertEquals('2009-04-30 21:59:59', Calendar_Model_Rrule::getRruleFromString($updatedEvent->rrule)->until->get(Tinebase_Record_Abstract::ISO8601LONG), 'until not changed');
        $this->assertEquals('2009-04-30 21:59:59', $updatedEvent->rrule_until->get(Tinebase_Record_Abstract::ISO8601LONG), 'rrule_until not changed');
    }

    /**
     * testUpdateRecurDtstartOverDst
     */
    public function testUpdateRecurDtstartOverDst()
    {
        // note: 2009-03-29 Europe/Berlin switched to DST
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'Abendessen',
            'dtstart'       => '2009-03-25 18:00:00',
            'dtend'         => '2009-03-25 18:30:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-02 21:59:59',
            'exdate'        => '2009-03-27 18:00:00,2009-03-31 17:00:00',
            'container_id'  => $this->_getTestCalendar()->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $persistentEvent = $this->_controller->create($event);
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $from = new Tinebase_DateTime('2009-03-26 00:00:00');
        $until = new Tinebase_DateTime('2009-04-03 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent, $exceptions, $from, $until); // 9 days
        
        // skip 27(exception), 31(exception), 03(until)
        $this->assertEquals(6, count($recurSet));
        
        $exceptionBeforeDstBoundary = clone $recurSet[1]; // 28. 
        $persistentExceptionBeforeDstBoundary = $this->_controller->createRecurException($exceptionBeforeDstBoundary);
        
        $updatedBaseEvent = $this->_controller->getRecurBaseEvent($recurSet[5]);
        $recurSet[5]->last_modified_time = $updatedBaseEvent->last_modified_time;
        $exceptionAfterDstBoundary = clone $recurSet[5]; // 02.
        $persistentExceptionAfterDstBoundary = $this->_controller->createRecurException($exceptionAfterDstBoundary);

        $persistentEvent = $this->_controller->getRecurBaseEvent($recurSet[5]);
        $persistentEvent->dtstart
            ->setTimezone($persistentEvent->originator_tz)
            ->addDay(5)
            ->subHour(4)
            ->setTimezone('UTC'); //30.
        $persistentEvent->dtend
            ->setTimezone($persistentEvent->originator_tz)
            ->addDay(5)
            ->subHour(4)
            ->setTimezone('UTC');
        $from->addDay(5); //31
        $until->addDay(5); //08
        
//        $this->_controller->get($persistentEvent);
        $persistentEvent->seq = 3; // satisfy modlog
        $updatedPersistenEvent = $this->_controller->update($persistentEvent);
        
        $persistentEvents = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'period', 'operator' => 'within', 'value' => array('from' => $from, 'until' => $until)),
            array('field' => 'uid', 'operator' => 'equals', 'value' => $persistentEvent->uid)
        )));
        
        // we don't 'see' the persistent exception from 28/
        $this->assertEquals(2, count($persistentEvents));
                
        $exceptions = $persistentEvents->filter('recurid', "/^{$persistentEvent->uid}-.*/", TRUE);
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($updatedPersistenEvent, $exceptions, $from, $until);

        // 2009-04-01
        $this->assertEquals(1, count($recurSet));

        $this->assertEquals("FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-02 17:59:59",
            (string) $updatedPersistenEvent->rrule);
    }
    
    public function testDeleteImplicitDeleteRcuringExceptions()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-30 13:30:00';
        $event->exdate = array(new Tinebase_DateTime('2009-04-07 13:00:00'));
        $persistentEvent = $this->_controller->create($event);
        
        $exception = clone $persistentEvent;
        $exception->dtstart->addDay(2);
        $exception->dtend->addDay(2);
        $exception->setId(NULL);
        unset($exception->rrule);
        unset($exception->exdate);
        $exception->setRecurId($persistentEvent->getId());
        $persistentException = $this->_controller->create($exception);
        
        unset($persistentEvent->rrule);
        $this->_controller->delete($persistentEvent);
        $this->expectException('Tinebase_Exception_NotFound');
        $this->_controller->get($persistentException->getId());
    }
    
    /**
     * test delete event
     * - check here if content sequence of container has been increased
     */
    public function testDeleteEvent()
    {
        $event = $this->_getEvent();
        $persistentEvent = $this->_controller->create($event);
        
        $this->_controller->delete($persistentEvent->getId());
        
        $contentSeq = Tinebase_Container::getInstance()->getContentSequence($this->_getTestCalendar());
        $this->assertEquals(3, $contentSeq, 'container content seq should be increased 3 times!');
        
        $this->expectException('Tinebase_Exception_NotFound');
        $this->_controller->get($persistentEvent->getId());
    }

    public function testGetChangesForRecurEventsForOneself()
    {
        Tinebase_Core::setUser($this->_personas['sclever']);

        $event = $this->_getEvent(true);
        $event->container_id = $this->_getPersonasDefaultCals('sclever')->id;
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=' . $event->dtend->getClone()->addDay(3)->toString();
        $persistentEvent = $this->_controller->create($event);
        $exception = clone $persistentEvent;
        $exception->dtstart->addDay(1);
        $exception->dtend->addDay(1);
        $exception->setId(NULL);
        unset($exception->rrule);
        unset($exception->exdate);
        $exception->setRecurId($persistentEvent->getId());
        $this->_controller->create($exception);

        $_SERVER['HTTP_USER_AGENT'] = 'Mac_OS_X/10.9 (13A603) CalendarAgent/174';

        $collection = new Calendar_Frontend_WebDAV(\Tine20\CalDAV\Plugin::CALENDAR_ROOT . '/' . $this->_personas['sclever']->contact_id, true);

        $changes = $collection->getChild($this->_getPersonasDefaultCals('sclever')->id)->getChanges(-1);

        $this->assertArrayHasKey('create', $changes);
        $this->assertArrayHasKey($persistentEvent->getId(), $changes['create']);
        $this->assertSame($persistentEvent->getId() . '.ics', $changes['create'][$persistentEvent->getId()]);
        $this->assertCount(1, $changes['create']);
    }

    public function testGetChangesForRecurEventsAsAttendee()
    {
        $event = $this->_getEvent(true);
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=' . $event->dtend->getClone()->addDay(3)->toString();
        $event->attendee = [[
                'user_id'   => $this->_personas['sclever']->contact_id,
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
        ]];
        $persistentEvent = $this->_controller->create($event);
        $exception = clone $persistentEvent;
        $exception->dtstart->addDay(1);
        $exception->dtend->addDay(1);
        $exception->setId(NULL);
        unset($exception->rrule);
        unset($exception->exdate);
        $exception->setRecurId($persistentEvent->getId());
        $this->_controller->create($exception);

        Tinebase_Core::setUser($this->_personas['sclever']);
        $_SERVER['HTTP_USER_AGENT'] = 'Mac_OS_X/10.9 (13A603) CalendarAgent/174';

        $collection = new Calendar_Frontend_WebDAV(\Tine20\CalDAV\Plugin::CALENDAR_ROOT . '/' . $this->_personas['sclever']->contact_id, true);

        $changes = $collection->getChild($this->_getPersonasDefaultCals('sclever')->id)->getChanges(-1);

        $this->assertArrayHasKey('create', $changes);
        $this->assertArrayHasKey($persistentEvent->getId(), $changes['create']);
        $this->assertSame($persistentEvent->getId() . '.ics', $changes['create'][$persistentEvent->getId()]);
        $this->assertCount(1, $changes['create']);
    }

    public function testIMIPtbWebDAVAllInternalRecurEventAsAttendee()
    {
        $event = $this->_getEvent(true);
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=' . $event->dtend->getClone()->addDay(3)->toString();
        $event->attendee = [[
            'user_id'   => $this->_personas['sclever']->contact_id,
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
        ]];
        $persistentEvent = $this->_controller->create($event);

        $this->assertSame(Calendar_Model_Attender::STATUS_NEEDSACTION,
            $persistentEvent->attendee->find('user_id', $this->_personas['sclever']->contact_id)->status);

        $exception = clone $persistentEvent;
        $exception->dtstart->addDay(1);
        $exception->dtend->addDay(1);
        $exception->setId(NULL);
        unset($exception->rrule);
        unset($exception->exdate);
        $exception->setRecurId($persistentEvent->getId());
        $this->_controller->create($exception);

        Tinebase_Core::setUser($this->_personas['sclever']);
        $_SERVER['HTTP_USER_AGENT'] = 'Mac_OS_X/10.9 (13A603) CalendarAgent/174';

        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        $vevent = $converter->fromTine20Model($persistentEvent);
        $vevent->METHOD = 'REQUEST';
        $vevent = str_replace('PARTSTAT=NEEDS-ACTION', 'PARTSTAT=ACCEPTED', $vevent->serialize());

        Calendar_Frontend_WebDAV_Event::create($this->_getPersonasDefaultCals('sclever'),
            $persistentEvent->getId() . '.ics', $vevent);

        $persistentEvent = $this->_controller->get($persistentEvent->getId());
        $this->assertSame(Calendar_Model_Attender::STATUS_ACCEPTED,
            $persistentEvent->attendee->find('user_id', $this->_personas['sclever']->contact_id)->status);
    }

    public function testIMIPtbWebDAVAllInternalRecurEventExceptionAsAttendee()
    {
        $event = $this->_getEvent(true);
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=' . $event->dtend->getClone()->addDay(3)->toString();
        $event->attendee = [[
            'user_id'   => $this->_personas['sclever']->contact_id,
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
        ]];
        $persistentEvent = $this->_controller->create($event);
        $exception = clone $persistentEvent;
        $exception->dtstart->addDay(1);
        $exception->dtend->addDay(1);
        $exception->setId(NULL);
        unset($exception->rrule);
        unset($exception->exdate);
        $exception->setRecurId($persistentEvent->getId());
        $persistentEventException = $this->_controller->create($exception);

        $this->assertSame(Calendar_Model_Attender::STATUS_NEEDSACTION,
            $persistentEventException->attendee->find('user_id', $this->_personas['sclever']->contact_id)->status);

        Tinebase_Core::setUser($this->_personas['sclever']);
        $_SERVER['HTTP_USER_AGENT'] = 'Mac_OS_X/10.9 (13A603) CalendarAgent/174';

        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        $vevent = $converter->fromTine20Model($persistentEventException);
        $vevent->METHOD = 'REQUEST';
        $vevent = str_replace('PARTSTAT=NEEDS-ACTION', 'PARTSTAT=ACCEPTED', $vevent->serialize());

        Calendar_Frontend_WebDAV_Event::create($this->_getPersonasDefaultCals('sclever'),
            $persistentEventException->getId() . '.ics', $vevent);

        $persistentEventException = $this->_controller->get($persistentEventException->getId());
        $this->assertSame(Calendar_Model_Attender::STATUS_ACCEPTED,
            $persistentEventException->attendee->find('user_id', $this->_personas['sclever']->contact_id)->status);
    }

    /**
     * @todo use exception api once we have it!
     *
     */
    public function testDeleteRecurExceptions()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-30 13:30:00';
        $event->exdate = array(new Tinebase_DateTime('2009-04-07 13:00:00'));
        $persistentEvent = $this->_controller->create($event);
        
        $exception = clone $persistentEvent;
        $exception->dtstart->addDay(2);
        $exception->dtend->addDay(2);
        $exception->setId(NULL);
        unset($exception->rrule);
        unset($exception->exdate);
        $exception->setRecurId($persistentEvent->getId());
        $persistentException = $this->_controller->create($exception);
        
        $this->_controller->delete($persistentEvent->getId());
        $this->expectException('Tinebase_Exception_NotFound');
        $this->_controller->get($persistentException->getId());
    }
    
    public function testDeleteNonPersistentRecurException()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-30 13:30:00';
        $persistentEvent = $this->_controller->create($event);
        
        // create an exception (a fallout)
        $exception = clone $persistentEvent;
        $exception->dtstart->addDay(3);
        $exception->dtend->addDay(3);
        $exception->summary = 'Abendbrot';
        $exception->recurid = $exception->uid . '-' . $exception->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
        $persistentEventWithExdate = $this->_controller->createRecurException($exception, true);
        
        $persistentEvent = $this->_controller->get($persistentEvent->getId());
        $this->assertEquals('Tinebase_DateTime', get_class($persistentEventWithExdate->exdate[0]));
        $this->assertEquals($persistentEventWithExdate->exdate[0]->format('c'), $persistentEvent->exdate[0]->format('c'));
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid',     'operator' => 'equals', 'value' => $persistentEvent->uid),
        )));
        $this->assertEquals(1, count($events));
    }
    
    public function testDeletePersistentRecurException()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-04-30 13:30:00';
        $persistentEvent = $this->_controller->create($event);
        
        $exception = clone $persistentEvent;
        $exception->dtstart->addDay(3);
        $exception->dtend->addDay(3);
        $exception->summary = 'Abendbrot';
        $exception->recurid = $exception->uid . '-' . $exception->dtstart->get(Tinebase_Record_Abstract::ISO8601LONG);
        $persistentException = $this->_controller->createRecurException($exception);
        
        $this->_controller->delete($persistentException->getId());
        
        $persistentEvent = $this->_controller->get($persistentEvent->getId());
        
        $this->assertEquals('Tinebase_DateTime', get_class($persistentEvent->exdate[0]));
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid',     'operator' => 'equals', 'value' => $persistentEvent->uid),
        )));
        $this->assertEquals(1, count($events));
    }
    
    public function testSetAlarm()
    {
        $event = $this->_getEvent();
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 30
            ), TRUE)
        ));
        $persistentEvent = $this->_controller->create($event);
        $alarmTime = clone $persistentEvent->dtstart;
        $alarmTime->subMinute(30);
        $firstAlarm = $persistentEvent->alarms->getFirstRecord();
        self::assertTrue(is_object($firstAlarm), 'did not find any alarm');
        self::assertTrue($alarmTime->equals($firstAlarm->alarm_time), 'initial alarm is not at expected time');

        $persistentEvent->dtstart->addHour(5);
        $persistentEvent->dtend->addHour(5);
        $updatedEvent = $this->_controller->update($persistentEvent);
        $alarmTime = clone $updatedEvent->dtstart;
        $alarmTime->subMinute(30);
        self::assertTrue($alarmTime->equals($updatedEvent->alarms->getFirstRecord()->alarm_time), 'alarm of updated event is not adjusted');
    }
    
    /**
     * testSetAlarmOfRecurSeries
     */
    public function testSetAlarmOfRecurSeries()
    {
        $event = $this->_getEvent();
        $event->dtstart = Tinebase_DateTime::now()->addHour(1);
        $event->dtend = Tinebase_DateTime::now()->addHour(2);
        
        $event->rrule = 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR,SA,SU;INTERVAL=1';
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 30
            ), TRUE)
        ));
        $persistentEvent = $this->_controller->create($event);
        $alarm = $persistentEvent->alarms->getFirstRecord();
        $this->assertEquals($event->dtstart->subMinute(30)->toString(), $alarm->alarm_time->toString(),
            'inital alarm fails: ' . print_r($alarm->toArray(), TRUE));
        
        // move whole series
        $persistentEvent->dtstart->addHour(5);
        $persistentEvent->dtend->addHour(5);
        $updatedEvent = $this->_controller->update($persistentEvent);
        $this->assertEquals($persistentEvent->dtstart->subMinute(30)->toString(), $updatedEvent->alarms->getFirstRecord()->alarm_time->toString(),
            'update alarm fails');
    }
    
    /**
     * testSetAlarmOfRecurSeriesException
     */
    public function testSetAlarmOfRecurSeriesException()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;INTERVAL=1';
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 30
            ), TRUE)
        ));
        $persistentEvent = $this->_controller->create($event);
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $exception = Calendar_Model_Rrule::computeNextOccurrence($persistentEvent, $exceptions, new Tinebase_DateTime());
        $exception->dtstart->subHour(6);
        $exception->dtend->subHour(6);
        $persistentException = $this->_controller->createRecurException($exception);
        
        $baseEvent = $this->_controller->getRecurBaseEvent($persistentException);
        $this->_controller->getAlarms($baseEvent);
        
        $exceptions = $this->_controller->getRecurExceptions($persistentException);
        $nextOccurance = Calendar_Model_Rrule::computeNextOccurrence($baseEvent, $exceptions, Tinebase_DateTime::now());
        
        $nextAlarmEventStart = new Tinebase_DateTime(substr($baseEvent->alarms->getFirstRecord()->getOption('recurid'), -19));
        
        $this->assertTrue($nextOccurance->dtstart->equals($nextAlarmEventStart), 'next alarm got not adjusted');
        
        $alarmTime = clone $persistentException->dtstart;
        $alarmTime->subMinute(30);
        $this->assertTrue($alarmTime->equals($persistentException->alarms->getFirstRecord()->alarm_time), 'alarmtime of persistent exception is not correnct/set');
    }
    
    /**
     * testAdoptAlarmTimeOfYearlyEvent
     * 
     * @see 0009320: Wrong notification on first occurrence exceptions
     */
    public function testAdoptAlarmTimeOfYearlyEvent()
    {
        $event = $this->_getEvent();
        $event->dtstart = new Tinebase_DateTime('2012-10-26 22:00:00');
        $event->dtend = new Tinebase_DateTime('2012-10-27 21:59:00');
        $event->is_all_day_event = 1;
        $event->rrule = 'FREQ=YEARLY;BYMONTH=10;BYMONTHDAY=27;INTERVAL=1';
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            new Tinebase_Model_Alarm(array(
                'minutes_before' => 2880
            ), TRUE)
        ));
        $persistentEvent = $this->_controller->create($event);
        $alarm = $persistentEvent->alarms->getFirstRecord();
        $this->_controller->adoptAlarmTime($persistentEvent, $alarm);
        
        $now = Tinebase_DateTime::now();
        $year = $now->get('Y');
        if ($now->isLater(new Tinebase_DateTime($year . '-10-27'))) {
            $year++;
        }
        // might be at 22:00 or 23.00 (daylight saving ...)
        // TODO verify that (@see 0011404: fix failing testAdoptAlarmTimeOfYearlyEvent)
        $expectedAlarmTimes = array($year . '-10-24 22:00:00', $year . '-10-24 23:00:00');
        $this->assertTrue(in_array($alarm->alarm_time->toString(), $expectedAlarmTimes),
            'alarm time mismatch:' . print_r($alarm->toArray(), true)
            . ' expected: ' . print_r($expectedAlarmTimes, true));

        if ($now->isLater(new Tinebase_DateTime($year . '-10-24'))) {
            // FIXME test fails if current date is between 10-24 and 10-27
            // @see 0011404: fix failing testAdoptAlarmTimeOfYearlyEvent
            return;
        }
        
        // mock send alarm and check next occurrence
        $alarm->sent_status = Tinebase_Model_Alarm::STATUS_PENDING;
        $alarm->sent_time = new Tinebase_DateTime('2012-10-24 22:01:03');
        $alarm->alarm_time = new Tinebase_DateTime('2013-10-24 22:00:00');
        $alarm->options = '{"custom":false,"minutes_before":2880,"recurid":"' . $persistentEvent->uid . '-2013-10-26 22:00:00"}';
        $alarmBackend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Alarm', 
            'tableName' => 'alarm',
        ));
        
        $updatedAlarm = $alarmBackend->update($alarm);
        
        $updatedAlarm->sent_time = Tinebase_DateTime::now();
        $updatedAlarm->sent_status = Tinebase_Model_Alarm::STATUS_SUCCESS;
        $updatedAlarm->minutes_before = 2880;
        
        $this->_controller->adoptAlarmTime($persistentEvent, $updatedAlarm, 'instance');
        $this->assertTrue(in_array($updatedAlarm->alarm_time->toString(), $expectedAlarmTimes),
            'alarm time mismatch:' . print_r($updatedAlarm->toArray(), true)
            . ' expected: ' . print_r($expectedAlarmTimes, true));
    }
    
    public function testPeriodFilter()
    {
        $this->testCreateEvent();
        
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
            array('field' => 'period', 'operator' => 'within', 'value' => array(
                'from'  => '2009-04-07',
                'until' => '2010-04-07'
            ))
        )), NULL, FALSE, FALSE);
        
        $this->assertEquals(0, count($events));

        // test period filter with time interval
        $this->_controller->create($this->_getEvent(true));

        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
            array('field' => 'period', 'operator' => 'within', 'value' => array(
                'from'  => 'P',
                'until' => 'PT10M'
            ))
        )), NULL, FALSE, FALSE);
        $this->assertEquals(1, count($events));

        // now is now, no matter which timezone is set
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
            array('field' => 'period', 'operator' => 'within', 'value' => array(
                'from'  => 'P',
                'until' => 'PT10M'
            ))
        ), null, ['timezone' => 'Europe/Berlin']), NULL, FALSE, FALSE);
        $this->assertEquals(1, count($events));

        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
            array('field' => 'period', 'operator' => 'within', 'value' => array(
                'from'  => 'PT-1H',
                'until' => 'PT10M'
            ))
        )));
        $this->assertEquals(1, count($events));

        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
            array('field' => 'period', 'operator' => 'within', 'value' => array(
                'from'  => 'PT-1H',
                'until' => 'PT-10M'
            ))
        )), NULL, FALSE, FALSE);
        $this->assertEquals(0, count($events));
    }
    
    /**
     * returns a simple event
     *
     * @return Calendar_Model_Event
     * @param bool $_now
     * @param bool $mute
     * @todo replace with TestCase::_getEvent
     */
    public function _getEvent($_now = FALSE, $mute = NULL)
    {
        $event = new Calendar_Model_Event(array(
            'summary'     => 'Mittagspause',
            'dtstart'     => '2009-04-06 13:00:00',
            'dtend'       => '2009-04-06 13:30:00',
            'description' => 'Wieslaw Brudzinski: Das Gesetz garantiert zwar die Mittagspause, aber nicht das Mittagessen...',
        
            'container_id' => $this->_getTestCalendar()->getId(),
            Tinebase_Model_Grants::GRANT_EDIT    => true,
        ));
        
        if ($_now) {
            $event->dtstart = Tinebase_DateTime::now();
            $event->dtend = Tinebase_DateTime::now()->addMinute(15);
        }
        
        return $event;
    }
    
    /**
     * (non-PHPdoc)
     * @see Calendar_TestCase::_getAttendee()
     */
    protected function _getAttendee()
    {
        return new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_id'   => Tinebase_Core::getUser()->contact_id,
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'role'      => Calendar_Model_Attender::ROLE_REQUIRED
            ),
            array(
                'user_id'   => Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
                'user_type' => Calendar_Model_Attender::USERTYPE_GROUP,
                'role'      => Calendar_Model_Attender::ROLE_REQUIRED
            )
        ));
    }
    
    /**
     * tests if customfields gets saved properly
     */
    public function testCustomFields()
    {
        $cfData = new Tinebase_Model_CustomField_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'name'              => 'unittest',
            'model'             => 'Calendar_Model_Event',
            'definition'        => array(
                'label' => Tinebase_Record_Abstract::generateUID(),
                'type'  => 'string',
                'uiconfig' => array(
                    'xtype'  => Tinebase_Record_Abstract::generateUID(),
                    'length' => 10,
                    'group'  => 'unittest',
                    'order'  => 100,
                )
            )
        ));
    
        try {
            Tinebase_CustomField::getInstance()->addCustomField($cfData);
        } catch (Zend_Db_Statement_Exception $zdse) {
            // custom field already exists
        }
    
        $event = new Calendar_Model_Event(array(
            'summary'     => 'Abendessen',
            'dtstart'     => '2014-04-06 18:00:00',
            'dtend'       => '2014-04-06 19:00:00',
            'description' => 'Guten Appetit',
            
            'container_id' => $this->_getTestCalendar()->getId(),
            Tinebase_Model_Grants::GRANT_EDIT    => true,
            'customfields' => array('unittest' => 'Hello')
        ));
        
        $event = $this->_controller->create($event);
        
        $this->assertEquals('Hello', $event->customfields['unittest']);
    }

    /**
     * @see 0010454: cli script for comparing calendars
     */
    public function testCompareCalendars()
    {
        $cal1 = $this->_testCalendar;
        $cal2 = $this->_getTestContainer('Calendar', Calendar_Model_Event::class);
        
        $this->_buildCompareCalendarsFixture($cal1, $cal2);
        
        $from = Tinebase_DateTime::now()->subDay(1);
        $until = Tinebase_DateTime::now()->addWeek(2);
        $result = Calendar_Controller_Event::getInstance()->compareCalendars($cal1->getId(), $cal2->getId(), $from, $until);
        
        $this->assertEquals(2, count($result['matching']), 'events 3+4 / 8+9 should have matched: ' . print_r($result['matching']->toArray(), true));
        $this->assertEquals(1, count($result['changed']), 'event 5 should appear in changed: ' . print_r($result['changed']->toArray(), true));
        $this->assertEquals(1, count($result['missingInCal1']), 'event 2 should miss from cal1: ' . print_r($result['missingInCal1']->toArray(), true));
        $this->assertEquals(1, count($result['missingInCal2']), 'event 6 should miss from cal2 ' . print_r($result['missingInCal2']->toArray(), true));
    }
    
    /**
     *  create some events to compare
     *  
     * - event1: in calendar 1
     * - event2: only in calendar 2
     * - event3+4: in both calendars
     * - event5: slightly different from event1 (same summary) / in cal2
     * - event6: only in cal1 (next week)
     * - event7: only in displaycontainer
     * - event8+9: in both calendars (whole day)
     * 
     * @param Tinebase_Model_Container $cal1
     * @param Tinebase_Model_Container $cal2
     */
    protected function _buildCompareCalendarsFixture($cal1, $cal2)
    {
        $event1 = $this->_getEvent(true);
        $event1->summary = 'event 1';
        $this->_controller->create($event1);
        
        $event2 =  $this->_getEvent(true);
        $event2->dtstart->addDay(1);
        $event2->dtend->addDay(1);
        $event2->summary = 'event 2';
        $event2->container_id = $cal2->getId();
        $this->_controller->create($event2);
        
        $event3 = $this->_getEvent(true);
        $event3->dtstart->addDay(2);
        $event3->dtend->addDay(2);
        $event3->summary = 'event 3';
        $event4 = clone $event3;
        $this->_controller->create($event3);
        
        $event4->container_id = $cal2->getId();
        $this->_controller->create($event4);
        
        $event5 = $this->_getEvent(true);
        $event5->summary = 'event 1';
        $event5->dtstart->addMinute(30);
        $event5->container_id = $cal2->getId();
        $this->_controller->create($event5);
        
        // this tests weekly processing, too
        $event6 = $this->_getEvent(true);
        $event6->summary = 'event 6';
        $event6->dtstart->addDay(8);
        $event6->dtend->addDay(8);
        $this->_controller->create($event6);
        
        // add event that is only in displaycontainer (should not appear in report)
        $currentDefault = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, Tinebase_Core::getUser()->getId());
        Tinebase_Core::getPreference('Calendar')->setValue(Calendar_Preference::DEFAULTCALENDAR, $cal1->getId());
        $event7 = $this->_getEvent(true);
        $event7->summary = 'event 7';
        $event7->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_id'   => Tinebase_Core::getUser()->contact_id,
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'role'      => Calendar_Model_Attender::ROLE_REQUIRED,
            ),
        ));
        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        $cal3 = $this->_getTestContainer('Calendar', Calendar_Model_Event::class);
        $event7->container_id = $cal3->getId();
        $this->_controller->create($event7);
        Tinebase_Core::set(Tinebase_Core::USER, $this->_originalTestUser);
        Tinebase_Core::getPreference('Calendar')->setValue(Calendar_Preference::DEFAULTCALENDAR, $currentDefault);
        
        $event8 = $this->_getEvent(true);
        $event8->summary = 'event 8';
        $event8->is_all_day_event = true;
        $event9 = clone $event8;
        $this->_controller->create($event8);
        
        $event9->container_id = $cal2->getId();
        $this->_controller->create($event9);
    }
    
    public function testRepairAttendee()
    {
        $event = $this->_getEvent(true);
        $event->attendee = null;
        $persistentEvent = $this->_controller->create($event);
        
        $result = $this->_controller->repairAttendee($persistentEvent->container_id, Tinebase_DateTime::now()->subDay(1), Tinebase_DateTime::now());
        
        $this->assertEquals(1, $result, 'should repair 1 event');
        
        $repairedEvent = $this->_controller->get($persistentEvent->getId());
        $this->assertEquals(1, count($repairedEvent->attendee));
        $ownAttender = Calendar_Model_Attender::getOwnAttender($repairedEvent->attendee);
        $this->assertTrue($ownAttender !== null);
    }

    /**
     * @see 0011130: handle bad originator timzone in VCALENDAR converter
     */
    public function testBrokenTimezoneInEvent()
    {
        $event = $this->_getEvent(true);
        $event->originator_tz = 'AWSTTTT';
        try {
            $event = $this->_controller->create($event);
            $this->fail('should throw Tinebase_Exception_Record_Validation because of bad TZ: ' . print_r($event->toArray(), true));
        } catch (Tinebase_Exception_Record_Validation $terv) {
            $this->assertEquals('Bad Timezone: AWSTTTT', $terv->getMessage());
        }
    }

    /**
     * @group longrunning
     * @group nodockerci
     */
    public function testRruleModLogUndo()
    {
        if (Tinebase_Core::getDb() instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            static::markTestSkipped('pgsql will be dropped, roll back of data not supported on pgsql');
        }

        if (Tinebase_Core::getUser()->accountLoginName === 'github') {
            static::markTestSkipped('FIXME on github-ci');
        }

        $instanceSeq = Tinebase_Timemachine_ModificationLog::getInstance()->getMaxInstanceSeq();

        $ownContactId = Tinebase_Core::getUser()->contact_id;
        $scleverContactId = $this->_personas['sclever']->contact_id;

        // create event with 2 attendees, no rrule
        $event1 = $this->_getEvent(true);
        $event1->summary = 'event 1';
        $event1->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', [
            [
                'user_id'   => $ownContactId,
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'role'      => Calendar_Model_Attender::ROLE_REQUIRED,
                'status'    => Calendar_Model_Attender::STATUS_DECLINED
            ],[
                'user_id'   => $scleverContactId,
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'role'      => Calendar_Model_Attender::ROLE_REQUIRED,
                'status'    => Calendar_Model_Attender::STATUS_DECLINED
            ],
        ]);

        $createdEvent = $this->_controller->create($event1);
        static::assertEquals(Calendar_Model_Attender::STATUS_DECLINED, $createdEvent->attendee->filter('user_id',
            $ownContactId)->getFirstRecord()->status);
        static::assertEquals(Calendar_Model_Attender::STATUS_NEEDSACTION, $createdEvent->attendee->filter('user_id',
            $scleverContactId)->getFirstRecord()->status);

        // update attendee status
        /** @var Calendar_Model_Attender $attender */
        $attender = $createdEvent->attendee->filter('user_id', $scleverContactId)->getFirstRecord();
        $attender->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        $this->_controller->attenderStatusUpdate($createdEvent, $attender, $attender->status_authkey);
        $updatedEvent = $this->_controller->get($createdEvent->getId());
        static::assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $updatedEvent->attendee->filter('user_id',
            $scleverContactId)->getFirstRecord()->status);
        static::assertEquals(Calendar_Model_Attender::STATUS_DECLINED, $updatedEvent->attendee->filter('user_id',
            $ownContactId)->getFirstRecord()->status);

        // now make it a recurring event
        $updatedEvent->rrule = 'FREQ=DAILY;INTERVAL=1';
        $updatedEvent = $this->_controller->update($updatedEvent);
        static::assertNull($updatedEvent->exdate);
        static::assertEquals(Calendar_Model_Attender::STATUS_NEEDSACTION, $updatedEvent->attendee->filter('user_id',
            $scleverContactId)->getFirstRecord()->status);
        static::assertEquals(Calendar_Model_Attender::STATUS_DECLINED, $updatedEvent->attendee->filter('user_id',
            $ownContactId)->getFirstRecord()->status);
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $nextOccurance = Calendar_Model_Rrule::computeNextOccurrence($updatedEvent, $exceptions, $updatedEvent->dtend);

        $deepLink = $nextOccurance->getDeepLink();
        preg_match('/fakeid.*$/', $deepLink, $matches);
        $id = $matches[0];

        $recurInstance = $this->_controller->get($id);
        static::assertTrue($recurInstance->isRecurInstance());
        static::assertEquals($nextOccurance->getId(), $recurInstance->getId());
        static::assertEquals(Calendar_Model_Attender::STATUS_DECLINED, $nextOccurance->attendee->filter('user_id',
            $ownContactId)->getFirstRecord()->status);

        // create recur exception
        $nextOccurance->summary = 'exception';
        $this->_controller->createRecurException($nextOccurance);
        $recurException = $this->_controller->get($id);
        static::assertFalse($recurException->isRecurInstance());
        static::assertEquals($nextOccurance->summary, $recurException->summary);
        static::assertEquals(Calendar_Model_Attender::STATUS_DECLINED, $recurException->attendee->filter('user_id',
            $ownContactId)->getFirstRecord()->status);
        static::assertEquals(Calendar_Model_Attender::STATUS_NEEDSACTION, $recurException->attendee->filter('user_id',
            $scleverContactId)->getFirstRecord()->status);
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid',     'operator' => 'equals', 'value' => $createdEvent->uid),
        )));
        static::assertEquals(2, count($events));
        /** @var Calendar_Model_Event $event */
        foreach ($events as $event) {
            if ($event->isRecurException()) continue;
            static::assertEquals('Tinebase_DateTime', get_class($event->exdate[0]));
            static::assertEquals($recurException->dtstart->format('c'), $event->exdate[0]->format('c'));
        }

        // update recur exception
        $updatedException = clone $recurException;
        $updatedException->dtstart->addMinute(1);
        $updatedException = $this->_controller->update($updatedException);
        static::assertEquals(1, $updatedException->dtstart->compare($recurException->dtstart));
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid',     'operator' => 'equals', 'value' => $createdEvent->uid),
        )));
        static::assertEquals(2, count($events));
        $updatedEvent = $this->_controller->get($updatedEvent->getId());
        static::assertEquals('Tinebase_DateTime', get_class($updatedEvent->exdate[0]));
        static::assertCount(1, $updatedEvent->exdate);
        static::assertEquals($recurException->dtstart->format('c'), $updatedEvent->exdate[0]->format('c'));

        // create a fallout exception
        $fallout = Calendar_Model_Rrule::computeNextOccurrence($updatedEvent, $exceptions, $updatedException->dtend);
        $fallout->summary = 'Abendbrot';
        $fallout->last_modified_time = clone $updatedEvent->last_modified_time;
        $persistentEventWithExdate = $this->_controller->createRecurException($fallout, true);
        $persistentEventWithExdate->sortExdates();
        $updatedEvent = $this->_controller->get($updatedEvent->getId());
        $updatedEvent->sortExdates();
        static::assertEquals('Tinebase_DateTime', get_class($updatedEvent->exdate[0]));
        static::assertEquals($recurException->dtstart->format('c'), $updatedEvent->exdate[0]->format('c'));
        static::assertEquals('Tinebase_DateTime', get_class($updatedEvent->exdate[1]));
        static::assertEquals($fallout->dtstart->format('c'), $updatedEvent->exdate[1]->format('c'));
        static::assertEquals('Tinebase_DateTime', get_class($persistentEventWithExdate->exdate[0]));
        static::assertEquals($recurException->dtstart->format('c'), $persistentEventWithExdate->exdate[0]->format('c'));
        static::assertEquals('Tinebase_DateTime', get_class($persistentEventWithExdate->exdate[1]));
        static::assertEquals($fallout->dtstart->format('c'), $persistentEventWithExdate->exdate[1]->format('c'));
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid',     'operator' => 'equals', 'value' => $createdEvent->uid),
        )));
        $this->assertEquals(2, count($events));

        // change rrule
        $rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=' . $updatedEvent->dtend->getClone()->addDay(10)->format('Y-m-d H:i:s');
        $updatedEvent->rrule = $rrule;
        $updatedEvent = $this->_controller->update($updatedEvent);
        $updatedEvent->sortExdates();
        static::assertEquals(substr($rrule, 0, -8), substr((string)($updatedEvent->rrule), 0 , -8));
        static::assertEquals('Tinebase_DateTime', get_class($persistentEventWithExdate->exdate[0]));
        static::assertEquals($recurException->dtstart->format('c'), $updatedEvent->exdate[0]->format('c'));
        static::assertEquals('Tinebase_DateTime', get_class($persistentEventWithExdate->exdate[1]));
        static::assertEquals($fallout->dtstart->format('c'), $updatedEvent->exdate[1]->format('c'));

        // just testing
        $this->_controller->get($updatedException->getId());
        $this->_controller->get($fallout->getId());

        // delete it
        $this->_controller->delete($updatedEvent->getId());
        try {
            $this->_controller->get($updatedEvent->getId());
            static::fail('delete did not work');
        } catch (Tinebase_Exception_NotFound $tenf) {}
        try {
            $this->_controller->get($updatedException->getId());
            static::fail('delete did not work');
        } catch (Tinebase_Exception_NotFound $tenf) {}
        try {
            $this->_controller->get($fallout->getId());
            static::fail('delete did not work');
        } catch (Tinebase_Exception_NotFound $tenf) {}

        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()->getModifications('Calendar', null,
            Calendar_Model_Event::class, null, null, null, null, $instanceSeq + 1);
        static::assertEquals(9, $modifications->count());

        // undelete it
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        $undidEvent = $this->_controller->get($updatedEvent->getId());
        $undidEvent->sortExdates();
        static::assertCount(2, $undidEvent->exdate);
        static::assertEquals(substr($rrule, 0, -8), substr((string)($undidEvent->rrule), 0 , -8));
        static::assertEquals('Tinebase_DateTime', get_class($undidEvent->exdate[0]));
        static::assertEquals($updatedEvent->exdate[0]->format('c'), $undidEvent->exdate[0]->format('c'));
        static::assertEquals('Tinebase_DateTime', get_class($undidEvent->exdate[1]));
        static::assertEquals($updatedEvent->exdate[1]->format('c'), $undidEvent->exdate[1]->format('c'));
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid',     'operator' => 'equals', 'value' => $createdEvent->uid),
        )));
        $this->assertEquals(2, count($events));
        static::assertFalse($this->_controller->get($updatedException->getId())->isRecurInstance());
        static::assertTrue($this->_controller->get($fallout->getId())->isRecurInstance());

        // undo rrule change
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        $undidEvent = $this->_controller->get($updatedEvent->getId());
        $rrule = 'FREQ=DAILY;INTERVAL=1';
        static::assertEquals($rrule, (string)($undidEvent->rrule));

        // remove fall out exception
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        $undidEvent = $this->_controller->get($updatedEvent->getId());
        static::assertEquals(1, count($undidEvent->exdate));
        static::assertEquals($undidEvent->exdate[0]->format('c'), $updatedEvent->exdate[0]->format('c'));

        // undo update recur exception
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        $undidEvent = $this->_controller->get($updatedEvent->getId());
        $undidException = $this->_controller->get($updatedException->getId());
        static::assertEquals($undidEvent->exdate[0]->format('c'), $undidException->dtstart->format('c'));
        static::assertEquals($undidException->dtstart->format('c'), $updatedException->dtstart->getClone()->subMinute(1)->format('c'));
        static::assertEquals(Calendar_Model_Attender::STATUS_DECLINED, $undidException->attendee->filter('user_id',
            $ownContactId)->getFirstRecord()->status);
        static::assertEquals(Calendar_Model_Attender::STATUS_NEEDSACTION, $undidException->attendee->filter('user_id',
            $scleverContactId)->getFirstRecord()->status);

        // delete recur exception
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        $undidEvent = $this->_controller->get($updatedEvent->getId());
        static::assertEmpty($undidEvent->exdate);
        try {
            $this->_controller->get($updatedException->getId());
            static::fail('delete did not work');
        } catch (Tinebase_Exception_NotFound $tenf) {}


        // undo make it a recurring event
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        $undidEvent = $this->_controller->get($updatedEvent->getId());
        static::assertTrue(empty($undidEvent->rrule));
        static::assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $undidEvent->attendee->filter('user_id',
            $scleverContactId)->getFirstRecord()->status);

        // undo create
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        try {
            $this->_controller->get($updatedEvent->getId());
            static::fail('delete did not work');
        } catch (Tinebase_Exception_NotFound $tenf) {}
    }

    public function testAlarmReSendOnReSchedule()
    {
        $event = $this->_getEvent();
        $event->alarms = new Tinebase_Record_RecordSet(Tinebase_Model_Alarm::class, [
            new Tinebase_Model_Alarm([
                'minutes_before' => 15,

                // this is a shortcut, which may and should fail in future, thats why we assert it below
                // change this test once it starts failing
                'sent_status' => Tinebase_Model_Alarm::STATUS_SUCCESS,
                'sent_time' => $event->dtstart->getClone()->subMinute(5)
            ], TRUE)
        ]);

        $createdEvent = $this->_controller->create($event);
        static::assertInstanceOf(Tinebase_Record_RecordSet::class, $createdEvent->alarms);
        static::assertNotNull($alarm = $createdEvent->alarms->getFirstRecord());
        static::assertSame(Tinebase_Model_Alarm::STATUS_SUCCESS, $alarm->sent_status);
        static::assertSame((string)$event->dtstart->getClone()->subMinute(5), (string)$alarm->sent_time);

        $createdEvent->dtstart->addMinute(12);
        $createdEvent->dtend->addMinute(12);
        $updatedEvent = $this->_controller->update($createdEvent);

        static::assertInstanceOf(Tinebase_Record_RecordSet::class, $updatedEvent->alarms);
        static::assertNotNull($alarm = $updatedEvent->alarms->getFirstRecord());
        static::assertSame(Tinebase_Model_Alarm::STATUS_PENDING, $alarm->sent_status);
        static::assertNull($alarm->sent_time);
    }

    public function testModLogUndo()
    {
        // activate ModLog in FileSystem!
        Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}
            ->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE} = true;
        $filesystem = Tinebase_FileSystem::getInstance();
        $filesystem->resetBackends();
        Tinebase_Core::clearAppInstanceCache();
        $ownContactId = Tinebase_Core::getUser()->contact_id;
        $scleverContactId = $this->_personas['sclever']->contact_id;

        $cField1 = Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config(array(
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'name' => Tinebase_Record_Abstract::generateUID(),
            'model' => Calendar_Model_Event::class,
            'definition' => [
                'label' => Tinebase_Record_Abstract::generateUID(),
                'type' => 'string',
                'uiconfig' => [
                    'xtype' => Tinebase_Record_Abstract::generateUID(),
                    'length' => 10,
                    'group' => 'unittest',
                    'order' => 100,
                ]
            ]
        )));
        $cField2 = Tinebase_CustomField::getInstance()->addCustomField(new Tinebase_Model_CustomField_Config(array(
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'name' => Tinebase_Record_Abstract::generateUID(),
            'model' => Calendar_Model_Event::class,
            'definition' => [
                'label' => Tinebase_Record_Abstract::generateUID(),
                'type' => 'string',
                'uiconfig' => [
                    'xtype' => Tinebase_Record_Abstract::generateUID(),
                    'length' => 10,
                    'group' => 'unittest',
                    'order' => 100,
                ]
            ]
        )));

        // create event with notes, relations, tags, attachments, customfield
        $event1 = $this->_getEvent(true);
        $event1->summary = 'event 1';
        $event1->notes = [new Tinebase_Model_Note([
            'note_type_id'      => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE,
            'note'              => 'phpunit test note',
        ])];
        $event1->relations = [[
            'related_id'        => $ownContactId,
            'related_model'     => 'Addressbook_Model_Contact',
            'related_degree'    => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_backend'   => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
            'type'              => 'foo'
        ]];
        $event1->tags = [['name' => 'testtag1']];
        $path = Tinebase_TempFile::getTempPath();
        file_put_contents($path, 'testAttachmentData');
        $event1->attachments = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', [
            [
                'name'      => 'testAttachmentData.txt',
                'tempFile'  => Tinebase_TempFile::getInstance()->createTempFile($path)
            ]
        ], true);
        $event1->customfields = [
            $cField1->name => 'test field1'
        ];
        $event1->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', [
            [
                'user_id'   => $ownContactId,
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'role'      => Calendar_Model_Attender::ROLE_REQUIRED,
                'status'    => Calendar_Model_Attender::STATUS_DECLINED
            ],
        ]);
        $event1->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', [
            new Tinebase_Model_Alarm([
                'minutes_before' => 2880
            ], TRUE)
        ]);

        $createdEvent = $this->_controller->create($event1);
        static::assertEquals(Calendar_Model_Attender::STATUS_DECLINED, $createdEvent->attendee->getFirstRecord()
            ->status);

        // update event, add more notes, relations, tags, attachments, customfields, alarms, attendees
        $updateEvent = clone $createdEvent;
        $notes = $updateEvent->notes->toArray();
        $notes[] = [
            'note_type_id'      => Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE,
            'note'              => 'phpunit test note 2',
        ];
        $updateEvent->notes = $notes;
        $relations = $updateEvent->relations->toArray();
        $relations[] = [
            'related_id'        => $ownContactId,
            'related_model'     => 'Addressbook_Model_Contact',
            'related_degree'    => Tinebase_Model_Relation::DEGREE_CHILD,
            'related_backend'   => Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND,
            'type'              => 'bar'
        ];
        $updateEvent->relations = $relations;
        $updateEvent->tags = clone $createdEvent->tags;
        $updateEvent->tags->addRecord(new Tinebase_Model_Tag(['name' => 'testtag2'], true));
        $updateEvent->attachments = clone $createdEvent->attachments;
        $path = Tinebase_TempFile::getTempPath();
        file_put_contents($path, 'moreTestAttachmentData');
        $updateEvent->attachments->addRecord(new Tinebase_Model_Tree_Node([
            'name'      => 'moreTestAttachmentData.txt',
            'tempFile'  => Tinebase_TempFile::getInstance()->createTempFile($path)
        ], true));
        $updateEvent->xprops('customfields')[$cField2->name] = 'test field2';
        $updateEvent->attendee->addRecord(new Calendar_Model_Attender([
                'user_id'   => $scleverContactId,
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'role'      => Calendar_Model_Attender::ROLE_REQUIRED,
                'status'    => Calendar_Model_Attender::STATUS_ACCEPTED
        ]));
        $updateEvent->alarms->addRecord(new Tinebase_Model_Alarm([
            'minutes_before' => 2880
        ], TRUE));

        $updatedEvent = $this->_controller->update($updateEvent);
        static::assertEquals(2, $updatedEvent->attendee->count());
        static::assertEquals(Calendar_Model_Attender::STATUS_DECLINED, $updatedEvent->attendee->filter('user_id',
            $ownContactId)->getFirstRecord()->status);
        static::assertEquals(Calendar_Model_Attender::STATUS_NEEDSACTION, $updatedEvent->attendee->filter('user_id',
            $scleverContactId)->getFirstRecord()->status);
        /** @var Calendar_Model_Attender $attender */
        $attender = $updatedEvent->attendee->filter('user_id', $ownContactId)->getFirstRecord();
        $attender->status = Calendar_Model_Attender::STATUS_TENTATIVE;
        $this->_controller->attenderStatusUpdate($updatedEvent, $attender, $attender->status_authkey);
        $attender = $updatedEvent->attendee->filter('user_id', $scleverContactId)->getFirstRecord();
        $attender->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        $this->_controller->attenderStatusUpdate($updatedEvent, $attender, $attender->status_authkey);
        $updatedEvent = $this->_controller->get($updateEvent->getId());
        static::assertEquals(Calendar_Model_Attender::STATUS_TENTATIVE, $updatedEvent->attendee->filter('user_id',
            $ownContactId)->getFirstRecord()->status);
        static::assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $updatedEvent->attendee->filter('user_id',
            $scleverContactId)->getFirstRecord()->status);

        // update event, remove one note, relation, tag, attachment, customfield, alarm, attendee
        $updateEvent = clone $updatedEvent;
        $notes = $updateEvent->notes->toArray();
        array_pop($notes);
        $updateEvent->notes = $notes;
        $relations = $updateEvent->relations->toArray();
        array_pop($relations);
        $updateEvent->relations = $relations;
        $updateEvent->tags->removeFirst();
        $updateEvent->attachments->removeFirst();
        $updateEvent->xprops('customfields')[$cField2->name] = null;
        $updateEvent->attendee->removeRecord($updateEvent->attendee->filter('user_id', $ownContactId)->getFirstRecord());
        $updateEvent->alarms->removeFirst();

        $updatedEvent = $this->_controller->update($updateEvent);
        static::assertEquals(1, $updatedEvent->notes->count());
        static::assertEquals(1, $updatedEvent->relations->count());
        static::assertEquals(1, $updatedEvent->tags->count());
        static::assertEquals(1, $updatedEvent->attachments->count());
        static::assertEquals(1, count($updatedEvent->customfields));
        static::assertEquals(1, $updatedEvent->attendee->count());
        static::assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $updatedEvent->attendee->getFirstRecord()->status);
        static::assertEquals(1, $updatedEvent->alarms->count());

        // update event, only add attendee
        $updatedEvent->attendee->addRecord(new Calendar_Model_Attender([
            'user_id'   => $ownContactId,
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'role'      => Calendar_Model_Attender::ROLE_REQUIRED,
            'status'    => Calendar_Model_Attender::STATUS_TENTATIVE
        ]));
        $updatedEvent = $this->_controller->update($updatedEvent);
        static::assertEquals(2, $updatedEvent->attendee->count());
        static::assertEquals(Calendar_Model_Attender::STATUS_TENTATIVE, $updatedEvent->attendee->filter('user_id', $ownContactId)->getFirstRecord()->status);
        static::assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $updatedEvent->attendee->filter('user_id', $scleverContactId)->getFirstRecord()->status);

        // update event, only change attendee status
        $updatedEvent->attendee->filter('user_id', $ownContactId)->getFirstRecord()->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        $updatedEvent = $this->_controller->update($updatedEvent);
        static::assertEquals(2, $updatedEvent->attendee->count());
        static::assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $updatedEvent->attendee->filter('user_id', $ownContactId)->getFirstRecord()->status);
        static::assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $updatedEvent->attendee->filter('user_id', $scleverContactId)->getFirstRecord()->status);

        // reschedule the event
        $updateEvent = clone $updatedEvent;
        $updateEvent->dtstart->addDay(1);
        $updateEvent->dtend->addDay(1);
        $updatedEvent = $this->_controller->update($updateEvent);
        static::assertEquals(Calendar_Model_Attender::STATUS_NEEDSACTION, $updatedEvent->attendee->filter('user_id', $scleverContactId)->getFirstRecord()->status);

        // update the event, not the attendees
        $updatedEvent->summary = 'event 2';
        $updatedEvent = $this->_controller->update($updatedEvent);
        static::assertSame('event 2', $updatedEvent->summary);

        $event = clone $updatedEvent;
        // delete it
        $this->_controller->delete($event->getId());
        try {
            $this->_controller->get($event->getId());
            static::fail('delete did not work');
        } catch (Tinebase_Exception_NotFound $tenf) {}


        $event->seq = 0;
        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()->getModificationsBySeq(
            Tinebase_Application::getInstance()->getApplicationById('Calendar')->getId(), $event, 10000);
        static::assertEquals(10, $modifications->count());

        // undelete it
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        $undeletedEvent = $this->_controller->get($event->getId());
        static::assertEquals(1, $undeletedEvent->notes->count());
        static::assertEquals(1, $undeletedEvent->relations->count());
        static::assertEquals(1, $undeletedEvent->tags->count());
        static::assertEquals(1, $undeletedEvent->attachments->count());
        static::assertEquals(1, count($undeletedEvent->customfields));
        static::assertEquals(2, $undeletedEvent->attendee->count());
        static::assertEquals(Calendar_Model_Attender::STATUS_NEEDSACTION, $undeletedEvent->attendee->filter('user_id', $scleverContactId)->getFirstRecord()
            ->status);
        static::assertEquals(1, $undeletedEvent->alarms->count());
        static::assertSame('event 2', $undeletedEvent->summary);

        // undo the summary change
        $mod = $modifications->getLastRecord();
        static::assertStringNotContainsString(Calendar_Model_Attender::class, $mod->new_value);
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        $undeletedEvent = $this->_controller->get($event->getId());
        static::assertSame('event 1', $undeletedEvent->summary);

        // undo the reschedule
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        $unrescheduledEvent = $this->_controller->get($event->getId());
        static::assertEquals(2, $unrescheduledEvent->attendee->count());
        static::assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $unrescheduledEvent->attendee->getFirstRecord()
            ->status);
        static::assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $unrescheduledEvent->attendee->getLastRecord()
            ->status);

        // undo update event, only change attendee status
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        $undidEvent = $this->_controller->get($event->getId());
        static::assertEquals(2, $undidEvent->attendee->count());
        static::assertEquals(Calendar_Model_Attender::STATUS_TENTATIVE, $undidEvent->attendee->filter('user_id', $ownContactId)->getFirstRecord()->status);
        static::assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $undidEvent->attendee->filter('user_id', $scleverContactId)->getFirstRecord()->status);

        // undo update event, only add attendee
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        $undidEvent = $this->_controller->get($event->getId());
        static::assertEquals(1, $undidEvent->attendee->count());
        static::assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $undidEvent->attendee->filter('user_id', $scleverContactId)->getFirstRecord()->status);

        // undelete the removed related data
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        $undeletedEvent = $this->_controller->get($event->getId());
        static::assertEquals(2, $undeletedEvent->notes->count());
        static::assertEquals(2, $undeletedEvent->relations->count());
        static::assertEquals(2, $undeletedEvent->tags->count());
        static::assertEquals(2, $undeletedEvent->attachments->count());
        static::assertEquals(2, count($undeletedEvent->customfields));
        static::assertEquals(2, $undeletedEvent->attendee->count());
        static::assertEquals(Calendar_Model_Attender::STATUS_TENTATIVE, $undeletedEvent->attendee->filter('user_id',
            $ownContactId)->getFirstRecord()->status);
        // this tests the special event undo mechanism to allow to set attendee status!
        static::assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $undeletedEvent->attendee->filter('user_id',
            $scleverContactId)->getFirstRecord()->status);
        static::assertEquals(2, $undeletedEvent->alarms->count());

        // remove the added related data
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        $mod1 = $modifications->getLastRecord();
        $modifications->removeRecord($mod1);
        $mod2 = $modifications->getLastRecord();
        $modifications->removeRecord($mod2);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter([
            ['field' => 'id', 'operator' => 'in', 'value' => [$mod->getId(), $mod1->getId(), $mod2->getId()]]
        ]));
        $undeletedEvent = $this->_controller->get($event->getId());
        static::assertEquals(1, $undeletedEvent->notes->count());
        static::assertEquals(1, $undeletedEvent->relations->count());
        static::assertEquals(1, $undeletedEvent->tags->count());
        static::assertEquals(1, $undeletedEvent->attachments->count());
        static::assertEquals(1, count($undeletedEvent->customfields));
        static::assertEquals(1, $undeletedEvent->attendee->count());
        static::assertEquals(Calendar_Model_Attender::STATUS_DECLINED, $undeletedEvent->attendee->filter('user_id',
            $ownContactId)->getFirstRecord()->status);
        static::assertEquals(1, $undeletedEvent->alarms->count());

        // remove the created event
        $mod = $modifications->getLastRecord();
        $modifications->removeRecord($mod);
        Tinebase_Timemachine_ModificationLog::getInstance()->undo(new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'id', 'operator' => 'in', 'value' => array($mod->getId()))
        )));
        try {
            $this->_controller->get($event->getId());
            static::fail('event should not be found, it should have been deleted');
        } catch (Tinebase_Exception_NotFound $tenf) {}
    }

    public function testRoleRights()
    {
        $newRole = Tinebase_Role::getInstance()->create(new Tinebase_Model_Role([
            'name' => 'unittest',
        ]));
        $newRole->members = [[
            'id' => '',
            'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
            'account_id' => Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
        ]];
        Tinebase_Role::getInstance()->update($newRole);
        Tinebase_Acl_Roles::getInstance()->resetClassCache();

        $sharedContainer = $this->_getTestContainer('Calendar', Calendar_Model_Event::class, true);
        $event = $this->_getEvent();
        $event->container_id = $sharedContainer->getId();
        $createdEvent = $this->_controller->create($event);

        Tinebase_Container::getInstance()->setGrants($sharedContainer, new Tinebase_Record_RecordSet(
            Tinebase_Model_Grants::class, [[
            'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_ROLE,
            'account_id' => $newRole->getId(),
            //'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
            //'account_id' => Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
            Tinebase_Model_Grants::GRANT_READ => true,
            Tinebase_Model_Grants::GRANT_EDIT => true,
            Tinebase_Model_Grants::GRANT_ADMIN => true,
            Tinebase_Model_Grants::GRANT_ADD => true,
        ]]), true, false);

        Tinebase_Core::setUser($this->_getPersona('sclever'));
        $scleverEvent = $this->_controller->get($createdEvent->getId());
        $this->assertTrue($scleverEvent->{Tinebase_Model_Grants::GRANT_DELETE});
        $scleverEvent = $this->_controller->search(new Calendar_Model_EventFilter([['field' => 'id', 'operator' => 'equals', 'value' => $createdEvent->getId()]]))->getFirstRecord();
        $this->assertNotNull($scleverEvent);
        $this->assertTrue($scleverEvent->{Tinebase_Model_Grants::GRANT_DELETE});
        $scleverEvent = $this->_controller->getMultiple([$createdEvent->getId()])->getFirstRecord();
        $this->assertNotNull($scleverEvent);
        $this->assertTrue($scleverEvent->{Tinebase_Model_Grants::GRANT_DELETE});
    }

    public function testGetPrivateEventInSharedContainer()
    {
        $sharedContainer = $this->_getTestContainer('Calendar', Calendar_Model_Event::class, true);
        Tinebase_Container::getInstance()->setGrants($sharedContainer, new Tinebase_Record_RecordSet(
            Tinebase_Model_Grants::class, [[
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Tinebase_Model_Grants::GRANT_READ => true,
                Tinebase_Model_Grants::GRANT_EDIT => true,
                Tinebase_Model_Grants::GRANT_ADMIN => true,
                Tinebase_Model_Grants::GRANT_ADD => true,
            ]]), true, false);

        // create private sclever event
        Tinebase_Core::set(Tinebase_Core::USER, $this->_getPersona('sclever'));
        $event = $this->_getEvent();
        $event->class = Calendar_Model_Event::CLASS_PRIVATE;
        $event->container_id = $sharedContainer->getId();
        $createdEvent = $this->_controller->create($event);

        // try to access it as original test user
        Tinebase_Core::set(Tinebase_Core::USER, $this->_originalTestUser);
        try {
            $this->_controller->get($createdEvent->getId());
            static::fail('expect access denied to private event');
        } catch (Tinebase_Exception_AccessDenied $e) {}
    }

    public function testSearchPrivateEventInSharedContainer()
    {
        $sharedContainer = $this->_getTestContainer('Calendar', Calendar_Model_Event::class, true);
        Tinebase_Container::getInstance()->setGrants($sharedContainer, new Tinebase_Record_RecordSet(
            Tinebase_Model_Grants::class, [[
            'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
            Tinebase_Model_Grants::GRANT_READ => true,
            Tinebase_Model_Grants::GRANT_EDIT => true,
            Tinebase_Model_Grants::GRANT_ADMIN => true,
            Tinebase_Model_Grants::GRANT_ADD => true,
        ]]), true, false);

        // create private sclever event
        Tinebase_Core::set(Tinebase_Core::USER, $this->_getPersona('sclever'));
        $event = $this->_getEvent();
        $event->class = Calendar_Model_Event::CLASS_PRIVATE;
        $event->container_id = $sharedContainer->getId();
        $createdEvent = $this->_controller->create($event);

        // try to access it as original test user
        Tinebase_Core::set(Tinebase_Core::USER, $this->_originalTestUser);
        $result = $this->_controller->search(new Calendar_Model_EventFilter([[
            'field' => 'id', 'operator' => 'equals', 'value' => $createdEvent->getId()
        ]]));
        static::assertSame(1, $result->count(), 'did not find created event, expect freebusy cleaned up event');
        /** @var Calendar_Model_Event $event */
        $event = $result->getFirstRecord();
        static::assertEmpty($event->summary);
    }

    public function testHandleRemoveGroup()
    {
        $group = Admin_Controller_Group::getInstance()->create(new Tinebase_Model_Group([
            'name'          => 'tine20phpunitgroup' . Tinebase_Record_Abstract::generateUID(6),
            'description'   => 'unittest group',
            'members'       => [],
        ]));

        $event = $this->_getEvent();
        $now = Tinebase_DateTime::now()->setTimezone(Tinebase_Core::getUserTimezone())->setTime(0,0,0);
        $event->dtstart = $now;
        $event->dtend = $now->addHour(1);
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
        array(
            'user_id'   => $group->list_id,
            'user_type' => Calendar_Model_Attender::USERTYPE_GROUP,
            'role'      => Calendar_Model_Attender::ROLE_REQUIRED
        )));
        $event = $this->_controller->create($event);

        $this->assertEquals(1, count($event->attendee->_listOfRecords));

        Admin_Controller_Group::getInstance()->delete([$group->getID()]);

        $event = $this->_controller->get($event->getId());

        $this->assertEquals(0, count($event->attendee->_listOfRecords));
    }

    public function testEventTypeFilter()
    {
        $eventType1 = Calendar_Controller_EventType::getInstance()->create(new Calendar_Model_EventType(['name' => 'foo']));
        $eventType2 = Calendar_Controller_EventType::getInstance()->create(new Calendar_Model_EventType(['name' => 'bar']));

        $filter = new Calendar_Model_EventTypeFilter([
            ['field' => 'name', 'operator' => 'equals', 'value' => 'foo']
        ]);

        $eventTypeFound = Calendar_Controller_EventType::getInstance()->search($filter);
        $this->assertEquals(1, count($eventTypeFound), 'no EventType "foo" was found');

        $event = $this->_getEvent();
        $event = $this->_controller->create($event);

        $event->event_types = new Tinebase_Record_RecordSet('Calendar_Model_EventTypes', array(
            array('record' => $event->getId(), 'eventType' => $eventType1->getId()),
            array('record' => $event->getId(), 'eventType' => $eventType2->getId()),
        ));

        $event = $this->_controller->update($event);

        self::assertCount(2, $event->event_types);

        $filter = new Calendar_Model_EventFilter([
            ['field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()],
            ['field' => 'event_types', 'operator' => 'definedBy', 'value' => [
                ['field' => Calendar_Model_EventTypes::FLD_EVENT_TYPE, 'operator' => 'in', 'value' => [$eventType1->getId()]],
            ]]
        ]);
        $eventsFound = $this->_controller->search($filter);
        $this->assertEquals(1, count($eventsFound), 'no Event with the EventType "foo" was found');
    }
}

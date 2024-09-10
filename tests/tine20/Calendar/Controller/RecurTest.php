<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Goekmen Ciyiltepe <g.ciyiltepe@metaways.de>
 */

/**
 * Test class for Calendar_Controller_Event
 * 
 * @package     Calendar
 */
class Calendar_Controller_RecurTest extends Calendar_TestCase
{
    /**
     * @var Calendar_Controller_Event controller
     */
    protected $_controller;
    
    public function setUp(): void
{
        parent::setUp();
        $this->_controller = Calendar_Controller_Event::getInstance();
    }
    
    public function testInvalidRruleUntil()
    {
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'Abendessen',
            'dtstart'       => '2012-06-01 18:00:00',
            'dtend'         => '2012-06-01 18:30:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=DAILY;INTERVAL=1;UNTIL=2011-05-31 17:30:00',
            'container_id'  => $this->_getTestCalendar()->getId(),
        ));
        
        $this->expectException(Tinebase_Exception_SystemGeneric::class);
        $this->_controller->create($event);
    }
    
    /**
     * imcomplete rrule clauses should be filled in automatically
     */
    public function testIncompleteRrule()
    {
        $event = $this->_getRecurEvent();
        
        $event->rrule = 'FREQ=WEEKLY';
        $persistentEvent = $this->_controller->create(clone $event);
        $this->assertEquals(Calendar_Model_Rrule::getWeekStart(), $persistentEvent->rrule->wkst, 'wkst not normalized');
        $this->assertEquals('TH', $persistentEvent->rrule->byday, 'byday not normalized');
        
        $rrule = Calendar_Model_Rrule::getRruleFromString('FREQ=MONTHLY');
        $rrule->normalize($event);
        $this->assertEquals(20, $rrule->bymonthday, 'bymonthday not normalized');
        
        $rrule = Calendar_Model_Rrule::getRruleFromString('FREQ=MONTHLY;BYDAY=1TH');
        $rrule->normalize($event);
        $this->assertEquals(NULL, $rrule->bymonthday, 'bymonthday must not be added');
        
        $rrule = Calendar_Model_Rrule::getRruleFromString('FREQ=YEARLY');
        $rrule->normalize($event);
        $this->assertEquals(5, $rrule->bymonth, 'bymonth not normalized');
        $this->assertEquals(20, $rrule->bymonthday, 'bymonthday not normalized');
        
        $rrule = Calendar_Model_Rrule::getRruleFromString('FREQ=YEARLY;BYDAY=1TH');
        $rrule->normalize($event);
        $this->assertEquals(5, $rrule->bymonth, 'bymonth not normalized');
        $this->assertEquals(NULL, $rrule->bymonthday, 'bymonthday must not be added');
    }

    public function testRescheduleAllFutureEvents()
    {
        $from = new Tinebase_DateTime('2011-04-18 00:00:00');
        $until = new Tinebase_DateTime('2011-05-05 23:59:59');

        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'Abendessen',
            'dtstart'       => '2011-04-19 14:00:00', // Tuesday
            'dtend'         => '2011-04-19 15:30:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=DAILY;INTERVAL=1;COUNT=10',
            'container_id'  => $this->_getTestCalendar()->getId(),
            'attendee'      => [$this->_createAttender($this->_personas['pwulf']->contact_id)],
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));

        $persistentEvent = $this->_controller->create($event);
        $persistentEvent->attendee->status = 'CONFIRMED';
        $persistentEvent = $this->_controller->update($persistentEvent);
        $events = new Tinebase_Record_RecordSet(Calendar_Model_Event::class, [$persistentEvent]);

        Calendar_Model_Rrule::mergeRecurrenceSet($events, $from, $until);
        static::assertEquals(10, count($events), 'there should be 10 events in the set');

        $events[5]->dtstart->addHour(1);
        $events[5]->dtend->addHour(1);
        $exception = $this->_controller->createRecurException($events[5], false, true);

        $baseEvent = $this->_controller->get($persistentEvent->getId());
        static::assertSame('CONFIRMED', $baseEvent->attendee->getFirstRecord()->status);
        static::assertSame('NEEDS-ACTION', $exception->attendee->getFirstRecord()->status);
    }

    public function testDailyCountOneEvent()
    {
        $from = new Tinebase_DateTime('2011-04-18 00:00:00');
        $until = new Tinebase_DateTime('2011-04-24 23:59:59');

        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'Abendessen',
            'dtstart'       => '2011-04-19 14:00:00', // Tuesday
            'dtend'         => '2011-04-19 15:30:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=DAILY;INTERVAL=1;COUNT=1',
            'container_id'  => $this->_getTestCalendar()->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));

        $persistentEvent = $this->_controller->create($event);
        static::assertSame($persistentEvent->dtend->toString(), $persistentEvent->rrule_until->toString());
        $events = new Tinebase_Record_RecordSet(Calendar_Model_Event::class, [$persistentEvent]);

        Calendar_Model_Rrule::mergeRecurrenceSet($events, $from, $until);
        static::assertEquals(1, count($events), 'there should only be 1 events in the set');
    }

    public function testWeeklyTwiceCountOneEvent()
    {
        $from = new Tinebase_DateTime('2011-04-18 00:00:00');
        $until = new Tinebase_DateTime('2011-04-24 23:59:59');

        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'Abendessen',
            'dtstart'       => '2011-04-19 14:00:00', // Tuesday
            'dtend'         => '2011-04-19 15:30:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=WEEKLY;INTERVAL=1;WKST=SU;BYDAY=TU,TH;COUNT=1',
            'container_id'  => $this->_getTestCalendar()->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));

        $persistentEvent = $this->_controller->create($event);
        $events = new Tinebase_Record_RecordSet(Calendar_Model_Event::class, [$persistentEvent]);

        Calendar_Model_Rrule::mergeRecurrenceSet($events, $from, $until);
        $this->assertEquals(1, count($events), 'there should only be 1 events in the set');
    }

    public function testFirstInstanceException()
    {
        $from = new Tinebase_DateTime('2011-04-18 00:00:00');
        $until = new Tinebase_DateTime('2011-04-24 23:59:59');
        
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'Abendessen',
            'dtstart'       => '2011-04-20 14:00:00',
            'dtend'         => '2011-04-20 15:30:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=WEEKLY;INTERVAL=3;WKST=SU;BYDAY=TU,TH',
            'container_id'  => $this->_getTestCalendar()->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $persistentEvent = $this->_controller->create($event);
        
        $eventException = clone $persistentEvent;
        $eventException->summary = 'Dinner';
        $eventException->dtstart->subHour(2);
        $eventException->dtend->subHour(2);
        $persistentEventException = $this->_controller->createRecurException($eventException);
        
        $weekviewEvents = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
        )));
        
        Calendar_Model_Rrule::mergeRecurrenceSet($weekviewEvents, $from, $until);
        $this->assertEquals(2, count($weekviewEvents), 'there should only be 2 events in the set');
        $this->assertFalse(in_array($persistentEvent->getId(), $weekviewEvents->getId()), 'baseEvent should not be in the set!');
        
        return $weekviewEvents;
    }
    
    /**
     * @see 8618: delete exdate / range this and future fails
     */
    public function testFirstInstanceExceptionDeleteRangeThisAndFuture()
    {
        $events = $this->testFirstInstanceException();
        $firstInstanceException = $events->getFirstRecord();
        
        $this->_controller->delete($firstInstanceException->getId(), Calendar_Model_Event::RANGE_THISANDFUTURE);
        
        $this->expectException('Tinebase_Exception_NotFound');
        $this->_controller->get($firstInstanceException->getId());
    }
    
    /**
     * @see 8618: delete exdate / range this and future fails
     */
    public function testFirstInstanceExceptionUpdateRangeThisAndFuture()
    {
        $events = $this->testFirstInstanceException();
        $firstInstanceException = $events->getFirstRecord();
        $location = 'At Home';
        $firstInstanceException->location = $location;
    
        $result = $this->_controller->update($firstInstanceException, FALSE, Calendar_Model_Event::RANGE_THISANDFUTURE);
        $this->assertEquals($location, $result->location);
    }

    /**
     * testFirstInstanceExceptionUpdateRangeAll
     * 
     * @see 0008826: update range:all does not work on first occurrence exception
     */
    public function testFirstInstanceExceptionUpdateRangeAll()
    {
        $events = $this->testFirstInstanceException();
        $firstInstanceException = $events->getFirstRecord();
        $location = 'At Home';
        $firstInstanceException->location = $location;
    
        $result = $this->_controller->update($firstInstanceException, FALSE, Calendar_Model_Event::RANGE_ALL);
        $this->assertEquals($result->location, $location);
        
        // @todo check other instances?
    }
    
    /**
     * @see #5802: moving last event of a recurring set with count part creates a instance a day later
     */
    public function testLastInstanceException()
    {
        $from = new Tinebase_DateTime('2012-02-20 00:00:00');
        $until = new Tinebase_DateTime('2012-02-26 23:59:59');
        
        $event = new Calendar_Model_Event(array(
                'uid'           => Tinebase_Record_Abstract::generateUID(),
                'summary'       => 'Abendessen',
                'dtstart'       => '2012-02-22 14:00:00',
                'dtend'         => '2012-02-22 15:30:00',
                'originator_tz' => 'Europe/Berlin',
                'rrule'         => 'FREQ=DAILY;COUNT=3',
                'container_id'  => $this->_getTestCalendar()->getId(),
        ));
        
        $persistentEvent = $this->_controller->create($event);
        
        // create exception
        $weekviewEvents = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
        )));
        Calendar_Model_Rrule::mergeRecurrenceSet($weekviewEvents, $from, $until);
        $weekviewEvents[2]->dtstart->subHour(5);
        $weekviewEvents[2]->dtend->subHour(5);
        $this->_controller->createRecurException($weekviewEvents[2]);
        
        // load series
        $weekviewEvents = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
        )));
        Calendar_Model_Rrule::mergeRecurrenceSet($weekviewEvents, $from, $until);
        $weekviewEvents->sort('dtstart', 'ASC');
        
        $this->assertEquals(3, count($weekviewEvents), 'wrong count');
        $this->assertEquals('2012-02-24 09:00:00', $weekviewEvents[2]->dtstart->toString());
    }
    
    /**
     * http://forge.tine20.org/mantisbt/view.php?id=4810
     */
    public function testWeeklyException()
    {
        $from = new Tinebase_DateTime('2011-09-01 00:00:00');
        $until = new Tinebase_DateTime('2011-09-30 23:59:59');
        
        $event = new Calendar_Model_Event(array(
            'uid'               => Tinebase_Record_Abstract::generateUID(),
            'summary'           => 'weekly',
            'dtstart'           => '2011-09-11 22:00:00',
            'dtend'             => '2011-09-12 21:59:59',
            'is_all_day_event'  => true,
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO,TU,WE,TH',
            'container_id'  => $this->_getTestCalendar()->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        
        $persistentEvent = $this->_controller->create($event);
        
        $weekviewEvents = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
        )));
        
        Calendar_Model_Rrule::mergeRecurrenceSet($weekviewEvents, $from, $until);
        $this->assertEquals(12, count($weekviewEvents), 'there should be 12 events in the set');
        
        // delte one instance
        $exception = $weekviewEvents->filter('dtstart', new Tinebase_DateTime('2011-09-19 22:00:00'))->getFirstRecord();
        $persistentEventException = $this->_controller->createRecurException($exception, TRUE);
        
        $weekviewEvents = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
        )));
        
        Calendar_Model_Rrule::mergeRecurrenceSet($weekviewEvents, $from, $until);
        $this->assertEquals(11, count($weekviewEvents), 'there should be 11 events in the set');
        
        $exception = $weekviewEvents->filter('dtstart', new Tinebase_DateTime('2011-09-19 22:00:00'))->getFirstRecord();
        $this->assertTrue(!$exception, 'exception must not be in eventset');
    }
    
    public function testAttendeeSetStatusRecurException()
    {
        // note: 2009-03-29 Europe/Berlin switched to DST
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'Abendessen',
            'dtstart'       => '2009-03-25 18:00:00',
            'dtend'         => '2009-03-25 18:30:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=DAILY;INTERVAL=1;UNTIL=2009-03-31 17:30:00',
            'exdate'        => '2009-03-27 18:00:00,2009-03-29 17:00:00',
            'container_id'  => $this->_getTestCalendar()->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        $event->attendee = $this->_getAttendee();
        unset($event->attendee[1]);
        
        $persistentEvent = $this->_controller->create($event);
        $attendee = $persistentEvent->attendee[0];
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $from = new Tinebase_DateTime('2009-03-26 00:00:00');
        $until = new Tinebase_DateTime('2009-04-01 23:59:59');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent, $exceptions, $from, $until);
        
        $exception = $recurSet->getFirstRecord();
        $attendee = $exception->attendee[0];
        $attendee->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        
        $this->_controller->attenderStatusCreateRecurException($exception, $attendee, $attendee->status_authkey);
        
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'period', 'operator' => 'within', 'value' => array('from' => $from, 'until' => $until)),
            array('field' => 'uid', 'operator' => 'equals', 'value' => $persistentEvent->uid)
        )));
        
        $recurid = array_values(array_filter($events->recurid));
        $this->assertEquals(1, count($recurid), 'only recur instance must have a recurid');
        $this->assertEquals('2009-03-26 18:00:00', substr($recurid[0], -19));
        $this->assertEquals(2, count($events));
    }
    
    public function testFirstInstanceAttendeeSetStatusRecurException()
    {
        $from = new Tinebase_DateTime('2011-04-18 00:00:00');
        $until = new Tinebase_DateTime('2011-04-24 23:59:59');
        
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'Abendessen',
            'dtstart'       => '2011-04-20 14:00:00',
            'dtend'         => '2011-04-20 15:30:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=WEEKLY;INTERVAL=3;WKST=SU;BYDAY=TU,TH',
            'container_id'  => $this->_getTestCalendar()->getId(),
            Tinebase_Model_Grants::GRANT_EDIT     => true,
        ));
        $event->attendee = $this->_getAttendee();
        unset($event->attendee[1]);
        
        $persistentEvent = $this->_controller->create($event);
        $attendee = $persistentEvent->attendee[0];
        $attendee->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        
        $this->_controller->attenderStatusCreateRecurException(clone $persistentEvent, $attendee, $attendee->status_authkey);
        
        $weekviewEvents = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
        )));
        
        Calendar_Model_Rrule::mergeRecurrenceSet($weekviewEvents, $from, $until);
        
        $this->assertEquals(2, count($weekviewEvents), 'there should only be 2 events in the set');
        $this->assertFalse(in_array($persistentEvent->getId(), $weekviewEvents->getId()), 'baseEvent should not be in the set!');
    }
    
    /**
     * Conflict between an existing and recurring event when create the event
     */
    public function testCreateConflictBetweenRecurAndExistEvent()
    {
        $event = $this->_getEvent();
        $event->dtstart = '2010-05-20 06:00:00';
        $event->dtend = '2010-05-20 06:15:00';
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('sclever')->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('pwulf')->getId())
        ));
        $this->_controller->create($event);

        $event1 = $this->_getRecurEvent();
        $event1->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('sclever')->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('pwulf')->getId())
        ));
        
        $this->expectException('Calendar_Exception_AttendeeBusy');
        $this->_controller->create($event1, TRUE);
    }
    
    /**
     * Conflict between an existing and recurring event when update the event
     */
    public function testUpdateConflictBetweenRecurAndExistEvent()
    {
        $event = $this->_getEvent();
        $event->dtstart = '2010-05-20 06:00:00';
        $event->dtend = '2010-05-20 06:15:00';
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('sclever')->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('pwulf')->getId())
        ));
        $this->_controller->create($event);

        $event1 = $this->_getRecurEvent();
        $event1->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('sclever')->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('pwulf')->getId())
        ));
        
        $event1 = $this->_controller->create($event1);
        $event1->rrule = "FREQ=DAILY;INTERVAL=2";
        
        $this->expectException('Calendar_Exception_AttendeeBusy');
        $this->_controller->update($event1, TRUE);
    }
    
    /**
     * check that fake clones of dates of persistent exceptions are left out in recur set calculation
     */
    public function testRecurSetCalcLeafOutPersistentExceptionDates()
    {
        // month 
        $from = new Tinebase_DateTime('2010-06-01 00:00:00');
        $until = new Tinebase_DateTime('2010-06-31 23:59:59');
        
        $event = $this->_getRecurEvent();
        $event->rrule = "FREQ=MONTHLY;INTERVAL=1;BYDAY=3TH";
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('sclever')->getId()),
            array('user_type' => Calendar_Model_Attender::USERTYPE_USER, 'user_id' => $this->_getPersonasContacts('pwulf')->getId())
        ));
        
        $persistentRecurEvent = $this->_controller->create($event);
        
        // get first recurrance
        $eventSet = new Tinebase_Record_RecordSet('Calendar_Model_Event', array($persistentRecurEvent));
        Calendar_Model_Rrule::mergeRecurrenceSet($eventSet, 
            new Tinebase_DateTime('2010-06-01 00:00:00'),
            new Tinebase_DateTime('2010-06-31 23:59:59')
        );
        $firstRecurrance = $eventSet[1];
        
        // create exception of this first occurance: 17.6. -> 24.06.
        $firstRecurrance->dtstart->add(1, Tinebase_DateTime::MODIFIER_WEEK);
        $firstRecurrance->dtend->add(1, Tinebase_DateTime::MODIFIER_WEEK);
        $this->_controller->createRecurException($firstRecurrance);
        
        // fetch weekview 14.06 - 20.06.
        $from = new Tinebase_DateTime('2010-06-14 00:00:00');
        $until = new Tinebase_DateTime('2010-06-20 23:59:59');
        $weekviewEvents = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid', 'operator' => 'equals', 'value' => $persistentRecurEvent->uid),
            array('field' => 'period', 'operator' => 'within', 'value' => array('from' => $from, 'until' => $until),
        ))));
        Calendar_Model_Rrule::mergeRecurrenceSet($weekviewEvents, $from, $until);
        
        // make sure the 17.6. is not in the set
        $this->assertEquals(1, count($weekviewEvents),
                '17.6. is an exception date and must not be part of this weekview: '
                . print_r($weekviewEvents->toArray(), true));
    }
    
    public function testCreateRecurExceptionPreserveAttendeeStatus()
    {
        $from = new Tinebase_DateTime('2012-03-01 00:00:00');
        $until = new Tinebase_DateTime('2012-03-31 23:59:59');
        
        $event = new Calendar_Model_Event(array(
                'summary'       => 'Some Daily Event',
                'dtstart'       => '2012-03-13 09:00:00',
                'dtend'         => '2012-03-13 10:00:00',
                'rrule'         => 'FREQ=DAILY;INTERVAL=1',
                'container_id'  => $this->_getTestCalendar()->getId(),
                'attendee'      => $this->_getAttendee(),
        ));
        
        $persistentEvent = $this->_controller->create($event);
        $persistentSClever = Calendar_Model_Attender::getAttendee($persistentEvent->attendee, $event->attendee[1]);
        
        // accept series for sclever
        $persistentSClever->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        $this->_controller->attenderStatusUpdate($persistentEvent, $persistentSClever, $persistentSClever->status_authkey);
        
        // create recur exception w.o. scheduling change
        $persistentEvent = $this->_controller->get($persistentEvent->getId());
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent, $exceptions, $from, $until);
        
        $recurSet[5]->description = 'From now on, everything will be better'; //2012-03-19
        $updatedPersistentEvent = $this->_controller->createRecurException($recurSet[5], FALSE, FALSE);
        
        $updatedPersistentSClever = Calendar_Model_Attender::getAttendee($updatedPersistentEvent->attendee, $event->attendee[1]);
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $updatedPersistentSClever->status, 'status must not change');
        
        
        // create recur exception with scheduling change
        $updatedBaseEvent = $this->_controller->getRecurBaseEvent($recurSet[6]);
        $recurSet[6]->last_modified_time = $updatedBaseEvent->last_modified_time;
        $recurSet[6]->dtstart->addHour(2);
        $recurSet[6]->dtend->addHour(2);
        $updatedPersistentEvent = $this->_controller->createRecurException($recurSet[6], FALSE, FALSE);
        
        $updatedPersistentSClever = Calendar_Model_Attender::getAttendee($updatedPersistentEvent->attendee, $event->attendee[1]);
        $this->assertEquals(Calendar_Model_Attender::STATUS_NEEDSACTION, $updatedPersistentSClever->status, 'status must change');
    }

    protected function countTestCalendarEvents($from, $until, $expectedCount, $expectedRecurExceptions = null)
    {
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
            array('field' => 'period', 'operator' => 'within', 'value' => array('from' => $from, 'until' => $until),
            ))));

        Calendar_Model_Rrule::mergeRecurrenceSet($events, $from, $until);
        $dates = [];
        $foundRecurExceptions = 0;
        /** @var Calendar_Model_Event $event */
        foreach ($events as $event) {
            if ($event->isRecurException() && strpos($event->getId(), 'fakeid') !== 0) {
                ++$foundRecurExceptions;
                $date = $event->getOriginalDtStart()->format('Y-m-d');
            } else {
                $date = $event->dtstart->format('Y-m-d');
            }
            if (!isset($dates[$date])) {
                $dates[$date] = [];
            }
            $dates[$date][] = [$event->dtstart->toString(), $event->uid];
        }
        ksort($dates);
        static::assertSame($expectedCount, count($events), 'there should be exactly ' . $expectedCount . ' events' . print_r($dates, true));
        if (null !== $expectedRecurExceptions) {
            static::assertSame($expectedRecurExceptions, $foundRecurExceptions, print_r($dates, true));
        }

        return $events;
    }

    public function testCreateRecurExceptionAllFollowingGeneral()
    {
        $from = new Tinebase_DateTime('2011-04-21 00:00:00');
        $until = new Tinebase_DateTime('2011-04-28 23:59:59');
        
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'Latte bei Schweinske',
            'dtstart'       => '2011-04-21 10:00:00',
            'dtend'         => '2011-04-21 12:00:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=DAILY;INTERVAL=1;UNTIL=2011-04-28 21:59:59',
            'container_id'  => $this->_getTestCalendar()->getId()
        ));
        
        $persistentEvent = $this->_controller->create($event);
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent, $exceptions, $from, $until);
        
        // create exceptions
        $recurSet->summary = 'Limo bei Schweinske';
        $recurSet[5]->dtstart->addHour(2);
        $recurSet[5]->dtend->addHour(2);

        $recurSet[6]->dtstart->subDay(6);
        $recurSet[6]->dtend->subDay(6);

        $this->countTestCalendarEvents($from, $until, 8);
        $this->_controller->createRecurException($recurSet[1], TRUE);  // (23) delete instance
        $this->countTestCalendarEvents($from, $until, 7);
        
        $updatedBaseEvent = $this->_controller->getRecurBaseEvent($recurSet[2]);
        static::assertCount(1, $updatedBaseEvent->exdate);
        $recurSet[2]->last_modified_time = $updatedBaseEvent->last_modified_time;
        $this->_controller->createRecurException($recurSet[2], FALSE); // (24) only summary update
        
        $updatedBaseEvent = $this->_controller->getRecurBaseEvent($recurSet[4]);
        static::assertCount(2, $updatedBaseEvent->exdate);
        $recurSet[4]->last_modified_time = $updatedBaseEvent->last_modified_time;
        $this->_controller->createRecurException($recurSet[4], TRUE);  // (26) delete instance
        $this->countTestCalendarEvents($from, $until, 6);
        
        $updatedBaseEvent = $this->_controller->getRecurBaseEvent($recurSet[5]);
        static::assertCount(3, $updatedBaseEvent->exdate);
        $recurSet[5]->last_modified_time = $updatedBaseEvent->last_modified_time;
        $this->_controller->createRecurException($recurSet[5], FALSE); // (27) move instance

        $updatedBaseEvent = $this->_controller->getRecurBaseEvent($recurSet[6]);
        static::assertCount(4, $updatedBaseEvent->exdate);
        $recurSet[6]->last_modified_time = $updatedBaseEvent->last_modified_time;
        $this->_controller->createRecurException($recurSet[6], FALSE); // (28) move instance to 22
        $this->countTestCalendarEvents($from, $until, 6);
        
        // now test update allfollowing
        $recurSet[3]->summary = 'Spezi bei Schwinske';
        $recurSet[3]->dtstart->addHour(4);
        $recurSet[3]->dtend->addHour(4);
        
        $updatedBaseEvent = $this->_controller->getRecurBaseEvent($recurSet[3]);
        static::assertCount(5, $updatedBaseEvent->exdate);
        $recurSet[3]->last_modified_time = $updatedBaseEvent->last_modified_time;
        $newBaseEvent = $this->_controller->createRecurException($recurSet[3], FALSE, TRUE); // split at 25
        $updatedBaseEvent = $this->_controller->get($updatedBaseEvent->getId());
        static::assertCount(2, $updatedBaseEvent->exdate);
        $events = $this->countTestCalendarEvents($from, $until, 6);
        
        $oldSeries = $events->filter('uid', $persistentEvent->uid);
        $newSeries = $events->filter('uid', $newBaseEvent->uid);
        $this->assertEquals(3, count($oldSeries), 'there should be exactly 3 events with old uid');
        $this->assertEquals(3, count($newSeries), 'there should be exactly 3 events with new uid');
        
        $this->assertEquals(1, count($oldSeries->filter('recurid', "/^$/", TRUE)), 'there should be exactly one old base event');
        $this->assertEquals(1, count($newSeries->filter('recurid', "/^$/", TRUE)), 'there should be exactly one new base event');
        
        $this->assertEquals(1, count($oldSeries->filter('recurid', "/^.+/", TRUE)->filter('rrule', '/^$/', TRUE)), 'there should be exactly one old persitent event exception');
        $this->assertEquals(2, count($newSeries->filter('recurid', "/^.+/", TRUE)->filter('rrule', '/^$/', TRUE)), 'there should be exactly one new persitent event exception');
        
        $this->assertEquals(1, count($oldSeries->filter('id', "/^fake.*/", TRUE)), 'there should be exactly one old fake event');
        $this->assertEquals(0, count($newSeries->filter('id', "/^fake.*/", TRUE)), 'there should be exactly one new fake event'); //26 (reset)
        
        $oldBaseEvent = $oldSeries->filter('recurid', "/^$/", TRUE)->getFirstRecord();
        $newBaseEvent = $newSeries->filter('recurid', "/^$/", TRUE)->getFirstRecord();
        
        $this->assertFalse(!!array_diff($oldBaseEvent->exdate, array(
            new Tinebase_DateTime('2011-04-23 10:00:00'),
            new Tinebase_DateTime('2011-04-24 10:00:00'),
        )), 'exdate of old series');

        $this->assertFalse(!!array_diff($newBaseEvent->exdate, array(
            new Tinebase_DateTime('2011-04-26 14:00:00'),
            new Tinebase_DateTime('2011-04-27 14:00:00'),
            new Tinebase_DateTime('2011-04-28 14:00:00'),
        )), 'exdate of new series');
        
        $this->assertFalse(!!array_diff($oldSeries->dtstart, array(
            new Tinebase_DateTime('2011-04-21 10:00:00'),
            new Tinebase_DateTime('2011-04-22 10:00:00'),
            new Tinebase_DateTime('2011-04-24 10:00:00'),
        )), 'dtstart of old series');

        $this->assertFalse(!!array_diff($newSeries->dtstart, array(
            new Tinebase_DateTime('2011-04-25 14:00:00'),
            new Tinebase_DateTime('2011-04-26 14:00:00'),
            new Tinebase_DateTime('2011-04-27 12:00:00'),
            new Tinebase_DateTime('2011-04-22 10:00:00'),
        )), 'dtstart of new series');
    }

    public function testCreateRecurExceptionAllFollowingRruleChange()
    {
        // Wednesday
        $from = new Tinebase_DateTime('2015-07-01 00:00:00');
        $until = new Tinebase_DateTime('2015-09-29 23:59:59');

        $event = new Calendar_Model_Event(array(
            'summary'           => 'Mettwoch',
            'dtstart'           => '2015-02-10 12:00:00',
            'dtend'             => '2015-02-10 13:00:00',
            'description'       => '2 Pfund Mett. 15 Brotchen. 1ne Zwiebel',
            'rrule'             => 'FREQ=WEEKLY;INTERVAL=1;WKST=MO;BYDAY=TU,FR',
            'container_id'      => method_exists($this, '_getTestCalendar') ?
                $this->_getTestCalendar()->getId() :
                $this->_testCalendar->getId(),
            'attendee'          => $this->_getAttendee(),
        ));

        $persistentEvent = $this->_controller->create($event);

        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        // Friday, 03. 07. 2015 - Tuesday, 07. 07. 2015
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent, $exceptions, $from, $until);
        static::assertEquals('2015-07-03 11:00:00', $recurSet->getFirstRecord()->dtstart);

        // delete Friday 10.07.2015
        $this->_controller->createRecurException($recurSet[2], true);
        $updatedBaseEvent = $this->_controller->get($persistentEvent->getId());
        static::assertCount(1, $updatedBaseEvent->exdate);

        // delete Tuesday 14.07.2015
        $recurSet[3]->last_modified_time = $updatedBaseEvent->last_modified_time;
        $this->_controller->createRecurException($recurSet[3], true);
        $updatedBaseEvent = $this->_controller->get($persistentEvent->getId());
        static::assertCount(2, $updatedBaseEvent->exdate);

        // alter Friday 17.07.2015
        $recurSet[4]->last_modified_time = $updatedBaseEvent->last_modified_time;
        $recurSet[4]->dtstart->addMinute(1);
        $this->_controller->createRecurException($recurSet[4]);
        $updatedBaseEvent = $this->_controller->get($persistentEvent->getId());
        static::assertCount(3, $updatedBaseEvent->exdate);

        // alter Tuesday 21.07.2015
        $recurSet[5]->last_modified_time = $updatedBaseEvent->last_modified_time;
        $recurSet[5]->dtstart->addMinute(2);
        $this->_controller->createRecurException($recurSet[5]);
        $updatedBaseEvent = $this->_controller->get($persistentEvent->getId());
        static::assertCount(4, $updatedBaseEvent->exdate);

        $recurSet[1]->last_modified_time = $updatedBaseEvent->last_modified_time;
        $recurSet[1]->dtstart->addDay(1);
        $recurSet[1]->dtend->addDay(1);
        $recurSet[1]->exdate = $updatedBaseEvent->exdate;
        $newBaseEvent = $this->_controller->createRecurException($recurSet[1], FALSE, TRUE);
        static::assertEquals('2015-07-08 11:00:00', $newBaseEvent->dtstart);
        static::assertEquals('WE,FR', $newBaseEvent->rrule->byday);
        static::assertCount(2, $newBaseEvent->exdate);
        $this->countTestCalendarEvents($newBaseEvent->dtstart, new Tinebase_DateTime('2015-07-22 14:00:00'), 4, 1);

        $oldBaseEvent = $this->_controller->get($persistentEvent->getId());
        static::assertEquals('2015-07-07 10:59:59', $oldBaseEvent->rrule->until);
        static::assertEmpty($oldBaseEvent->exdate);
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $oldRecurSet = Calendar_Model_Rrule::computeRecurrenceSet($oldBaseEvent, $exceptions, $from, $until);

        $this->assertCount(1, $oldRecurSet, 'one event should be left in given period ');
        $this->assertEquals('2015-07-03 11:00:00', $oldRecurSet[0]->dtstart);
    }

    public function testCreateRecurExceptionAllFollowingAllDay()
    {
        $from = new Tinebase_DateTime('2015-07-01 00:00:00');
        $until = new Tinebase_DateTime('2015-09-29 23:59:59');

        $event = new Calendar_Model_Event(array(
            'summary'           => 'Mettwoch',
            'dtstart'           => '2015-02-10 23:00:00',
            'dtend'             => '2015-02-11 22:59:59',
            'is_all_day_event'  => 1,
            'description'       => '2 Pfund Mett. 15 Brotchen. 1ne Zwiebel',
            'rrule'             => 'FREQ=MONTHLY;INTERVAL=1;BYDAY=2WE',
            'container_id'      => method_exists($this, '_getTestCalendar') ?
                $this->_getTestCalendar()->getId() :
                $this->_testCalendar->getId(),
            'attendee'          => $this->_getAttendee(),
        ));

        $persistentEvent = $this->_controller->create($event);

        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent, $exceptions, $from, $until);

        $recurSet[1]->description = '4 Pfund Mett. 15 Brotchen. 2 Zwiebeln';
        $newBaseEvent = $this->_controller->createRecurException($recurSet[1], FALSE, TRUE);

        $oldBaseEvent = $this->_controller->get($persistentEvent->getId());
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $oldRecurSet = Calendar_Model_Rrule::computeRecurrenceSet($oldBaseEvent, $exceptions, $from, $until);

        $this->assertCount(1, $oldRecurSet, 'one event should be left in given period ');
        $this->assertEquals('2015-07-07 22:00:00', $oldRecurSet[0]->dtstart);
    }

    /**
     * if not resheduled, attendee status must be preserved
     */
    public function testCreateRecurExceptionAllFollowingPreserveAttendeeStatus()
    {
        $from = new Tinebase_DateTime('2012-02-01 00:00:00');
        $until = new Tinebase_DateTime('2012-02-29 23:59:59');
        
        $event = new Calendar_Model_Event(array(
            'summary'       => 'Some Daily Event',
            'dtstart'       => '2012-02-03 09:00:00',
            'dtend'         => '2012-02-03 10:00:00',
            'rrule'         => 'FREQ=DAILY;INTERVAL=1',
            'container_id'  => $this->_getTestCalendar()->getId(),
            'attendee'      => $this->_getAttendee(),
        ));
        
        $persistentEvent = $this->_controller->create($event);
        $persistentSClever = Calendar_Model_Attender::getAttendee($persistentEvent->attendee, $event->attendee[1]);
        
        // accept series for sclever
        $persistentSClever->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        $this->_controller->attenderStatusUpdate($persistentEvent, $persistentSClever, $persistentSClever->status_authkey);
        
        // update "allfollowing" w.o. scheduling change
        $persistentEvent = $this->_controller->get($persistentEvent->getId());
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent, $exceptions, $from, $until);
        
        $recurSet[5]->description = 'From now on, everything will be better'; //2012-02-09 
        $updatedPersistentEvent = $this->_controller->createRecurException($recurSet[5], FALSE, TRUE);
        
        $updatedPersistentSClever = Calendar_Model_Attender::getAttendee($updatedPersistentEvent->attendee, $event->attendee[1]);
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $updatedPersistentSClever->status, 'status must not change');
    }
    
    /**
     * @see https://forge.tine20.org/mantisbt/view.php?id=6548
     */
    public function testCreateRecurExceptionsConcurrently()
    {
        $from = new Tinebase_DateTime('2012-06-01 00:00:00');
        $until = new Tinebase_DateTime('2012-06-30 23:59:59');
        
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'Concurrent Recur updates',
            'dtstart'       => '2012-06-01 10:00:00',
            'dtend'         => '2012-06-01 12:00:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=WEEKLY;INTERVAL=1',
            'container_id'  => $this->_getTestCalendar()->getId()
        ));
        
        $persistentEvent = $this->_controller->create($event);
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent, $exceptions, $from, $until);
        
        // create all following exception with first session
        $firstSessionExdate = clone $recurSet[1];
        $firstSessionExdate->summary = 'all following update';
        $this->_controller->createRecurException($firstSessionExdate, FALSE, TRUE);
        
        // try to update exception concurrently
        $this->expectException('Tinebase_Exception_ConcurrencyConflict');
        $secondSessionExdate = clone $recurSet[1];
        $secondSessionExdate->summary = 'just an update';
        $this->_controller->createRecurException($secondSessionExdate, FALSE, TRUE);
    }
    
    /**
     * test implicit recur (exception) series creation for attendee status only
     */
    public function testAttendeeSetStatusRecurExceptionAllFollowing()
    {
        $from = new Tinebase_DateTime('2012-02-01 00:00:00');
        $until = new Tinebase_DateTime('2012-02-29 23:59:59');
        
        $event = new Calendar_Model_Event(array(
            'summary'       => 'Some Daily Event',
            'dtstart'       => '2012-02-03 09:00:00',
            'dtend'         => '2012-02-03 10:00:00',
            'rrule'         => 'FREQ=DAILY;INTERVAL=1',
            'container_id'  => $this->_getTestCalendar()->getId(),
            'attendee'      => $this->_getAttendee(),
        ));
        
        $persistentEvent = $this->_controller->create($event);
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent, $exceptions, $from, $until);
        
        // accept for sclever thisandfuture
        $start = $recurSet[10];
        $sclever = Calendar_Model_Attender::getAttendee($start->attendee, $event->attendee[1]);
        $sclever->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        $this->_controller->attenderStatusCreateRecurException($start, $sclever, $sclever->status_authkey, TRUE);
        
        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId())
        )))->sort('dtstart', 'ASC');
        
        // assert two baseEvents
        $this->assertTrue($events[0]->rrule_until instanceof Tinebase_DateTime, 'rrule_until of first baseEvent is not set');
        $this->assertTrue($events[0]->rrule_until < new Tinebase_DateTime('2012-02-14 09:00:00'), 'rrule_until of first baseEvent is not adopted properly');
        $this->assertEquals(Calendar_Model_Attender::STATUS_NEEDSACTION, Calendar_Model_Attender::getAttendee($events[0]->attendee, $event->attendee[1])->status, 'first baseEvent status must not be touched');
        
        $this->assertEquals($events[1]->dtstart, new Tinebase_DateTime('2012-02-14 09:00:00'), 'start of second baseEvent is wrong');
        $this->assertTrue(empty($events[1]->recurid), 'second baseEvent is not a baseEvent');
        $this->assertEquals((string) $event->rrule, (string) $events[1]->rrule, 'rrule of second baseEvent must be set');
        $this->assertFalse($events[1]->rrule_until instanceof Tinebase_DateTime, 'rrule_until of second baseEvent must not be set');
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, Calendar_Model_Attender::getAttendee($events[1]->attendee, $event->attendee[1])->status, 'second baseEvent status is not touched');
    }

    public function testCreateRecurException()
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

        $persistentEvent = $this->_controller->get($persistentEvent->getId());
        $this->assertEquals(1, count($persistentEvent->exdate));

        $events = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'uid',     'operator' => 'equals', 'value' => $persistentEvent->uid),
        )));
        $this->assertEquals(2, count($events));

        return $persistentException;
    }

    public function testGetRecurExceptions()
    {
        $persistentException = $this->testCreateRecurException();

        $baseEvent = $this->_controller->getRecurBaseEvent($persistentException);

        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $nextOccurance = Calendar_Model_Rrule::computeNextOccurrence($baseEvent, $exceptions, $baseEvent->dtend);
        $this->_controller->createRecurException($nextOccurance, TRUE);

        $exceptions = $this->_controller->getRecurExceptions($persistentException, TRUE);
        $dtstarts = $exceptions->dtstart;

        $this->assertTrue(in_array($nextOccurance->dtstart, $dtstarts), 'deleted instance missing');
        $this->assertTrue(in_array($persistentException->dtstart, $dtstarts), 'exception instance missing');
    }

    /**
     * testUpdateEventWithRruleAndRecurId
     *
     * @see 0008696: do not allow both rrule and recurId in event
     */
    public function testUpdateEventWithRruleAndRecurId()
    {
        $persistentRecurEvent = $this->testCreateRecurException();
        $persistentRecurEvent->rrule = 'FREQ=DAILY;INTERVAL=1';

        $updatedEvent = $this->_controller->update($persistentRecurEvent);

        $this->assertEquals(NULL, $updatedEvent->rrule);
    }

   /**
    * @see {http://forge.tine20.org/mantisbt/view.php?id=5686}
    */
    public function testCreateRecurExceptionAllFollowingAttendeeAdd()
    {
        $from = new Tinebase_DateTime('2012-02-01 00:00:00');
        $until = new Tinebase_DateTime('2012-02-29 23:59:59');
        
        $persistentEvent = $this->_getDailyEvent(new Tinebase_DateTime('2012-02-03 09:00:00'));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent, $exceptions, $from, $until);

        $pwulf = new Calendar_Model_Attender(array(
            'user_type'   => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'     => $this->_getPersonasContacts('pwulf')->getId()
        ));
        $recurSet[5]->attendee->addRecord($pwulf);
        
        $updatedPersistentEvent = $this->_controller->createRecurException($recurSet[5], FALSE, TRUE);
        
        $this->assertEquals(3, count($updatedPersistentEvent->attendee));

        $persistentPwulf = Calendar_Model_Attender::getAttendee($updatedPersistentEvent->attendee, $pwulf);
        $this->assertNotNull($persistentPwulf->displaycontainer_id);
    }
    
    /**
     * Events don't show up in attendees personal calendar
     */
    public function testCreateRecurExceptionAllFollowingAttendeeAdd2()
    {
        $from = new Tinebase_DateTime('2014-04-01 00:00:00');
        $until = new Tinebase_DateTime('2014-04-29 23:59:59');
        
        $persistentEvent = $this->_getDailyEvent(new Tinebase_DateTime('2014-04-03 09:00:00'));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent, $exceptions, $from, $until);
        
        $recurSet[5]->attendee->addRecord(new Calendar_Model_Attender(array(
                'user_type'   => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'     => $this->_getPersonasContacts('pwulf')->getId()
        )));
        
        $updatedPersistentEvent = $this->_controller->createRecurException($recurSet[5], FALSE, TRUE);
        $this->assertEquals(3, count($updatedPersistentEvent->attendee));
        
        $filter = new Calendar_Model_EventFilter(array(
                array('field' => 'container_id',             'operator' => 'equals', 'value' => $this->_personasDefaultCals['pwulf']->id),
                array('field' => 'attender_status', 'operator' => 'not',    'value' => Calendar_Model_Attender::STATUS_DECLINED),
        ));
        
        $events = $this->_controller->search($filter);
        $this->assertEquals(1, count($events), 'event should be found, but is not');
    }
    
    /**
     * create daily recur series
     * 
     * @param Tinebase_DateTime $dtstart
     * @return Calendar_Model_Event
     */
    protected function _getDailyEvent(Tinebase_DateTime $dtstart)
    {
        $event = new Calendar_Model_Event(array(
            'summary'       => 'Some Daily Event',
            'dtstart'       => $dtstart->toString(),
            'dtend'         => $dtstart->addHour(1)->toString(),
            'rrule'         => 'FREQ=DAILY;INTERVAL=1',
            'container_id'  => $this->_getTestCalendar()->getId(),
            'attendee'      => $this->_getAttendee(),
        ));
        return $this->_controller->create($event);
    }
    
   /**
    * @see #5806: thisandfuture range updates with count part fail
    */
    public function testCreateRecurExceptionAllFollowingWithCount()
    {
        $from = new Tinebase_DateTime('2012-02-20 00:00:00');
        $until = new Tinebase_DateTime('2012-02-26 23:59:59');
        
        $event = new Calendar_Model_Event(array(
            'uid'           => Tinebase_Record_Abstract::generateUID(),
            'summary'       => 'Abendessen',
            'dtstart'       => '2012-02-21 14:00:00',
            'dtend'         => '2012-02-21 15:30:00',
            'originator_tz' => 'Europe/Berlin',
            'rrule'         => 'FREQ=DAILY;COUNT=5',
            'container_id'  => $this->_getTestCalendar()->getId(),
        ));
        
        $persistentEvent = $this->_controller->create($event);
        
        // create exception
        $weekviewEvents = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
        )));
        Calendar_Model_Rrule::mergeRecurrenceSet($weekviewEvents, $from, $until);
        $weekviewEvents[2]->dtstart->subHour(5);
        $weekviewEvents[2]->dtend->subHour(5);
        $this->_controller->createRecurException($weekviewEvents[2], FALSE, TRUE);
        
        // load events
        $weekviewEvents = $this->_controller->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_getTestCalendar()->getId()),
        )));
        Calendar_Model_Rrule::mergeRecurrenceSet($weekviewEvents, $from, $until);
        $weekviewEvents->sort('dtstart', 'ASC');
        
        $this->assertEquals(2, count($weekviewEvents->filter('uid', $weekviewEvents[0]->uid)), 'shorten failed');
        $this->assertEquals(5, count($weekviewEvents), 'wrong total count');
    }

    public function testCreateRecurExceptionAllFollowingContainerMove()
    {
        $this->markTestSkipped('exdate container move not yet forbidden');
        $exception = $this->testCreateRecurException();
        $baseEvent = $this->_controller->getRecurBaseEvent($exception);

        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $from = $baseEvent->dtstart;
        $until = $baseEvent->dtstart->getClone()->addDay(1);
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($baseEvent, $exceptions, $from, $until);

        $recurSet->getFirstRecord()->container_id = $this->_getTestContainer('Calendar', Calendar_Model_Event::class)->getId();
        $newSeries = $this->_controller->createRecurException($recurSet->getFirstRecord(), false, true);
        $newExceptions = $this->_controller->getRecurExceptions($newSeries);

//        print_r($newSeries->toArray());
//        print_r($newExceptions->toArray());
    }

    /**
     * testMoveRecurException
     * 
     * @see 0008704: moving a recur exception twice creates concurrency exception
     * - this has been a client problem, server did everything right
     */
    public function testMoveRecurException()
    {
        $from = new Tinebase_DateTime('2012-02-01 00:00:00');
        $until = new Tinebase_DateTime('2012-02-29 23:59:59');
        
        $persistentEvent1 = $this->_getDailyEvent(new Tinebase_DateTime('2012-02-03 09:00:00'));
        $persistentEvent2 = $this->_getDailyEvent(new Tinebase_DateTime('2012-02-03 13:00:00'));
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($persistentEvent1, $exceptions, $from, $until);
        
        $recurSet[5]->dtstart = new Tinebase_DateTime('2012-02-09 13:00:00');
        $recurSet[5]->dtend = new Tinebase_DateTime('2012-02-09 14:00:00');
        
        $updatedPersistentEvent = $this->_controller->createRecurException($recurSet[5]);
        
        $updatedPersistentEvent->dtstart = new Tinebase_DateTime('2012-03-09 13:00:00');
        $updatedPersistentEvent->dtend = new Tinebase_DateTime('2012-03-09 14:00:00');
        
        $this->expectException('Tinebase_Exception_ConcurrencyConflict');
        $updatedPersistentEvent = $this->_controller->createRecurException($updatedPersistentEvent);
    }

    public function testExdateContainerMoveCreateException()
    {
        $this->markTestSkipped('exdate container move not yet forbidden');
        $event = $this->_getDailyEvent(new Tinebase_DateTime('2014-02-03 09:00:00'));

        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $from = new Tinebase_DateTime('2014-02-01 00:00:00');
        $until = new Tinebase_DateTime('2014-02-29 23:59:59');
        
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);

        $this->expectException('Calendar_Exception_ExdateContainer');

        $recurSet[2]->container_id = $this->_getTestContainer('Calendar', Calendar_Model_Event::class)->getId();
        $this->_controller->createRecurException($recurSet[2]);
    }

    public function testExdateContainerMoveUpdateException()
    {
        $this->markTestSkipped('exdate container move not yet forbidden');
        $event = $this->_getDailyEvent(new Tinebase_DateTime('2014-02-03 09:00:00'));

        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');

        $from = new Tinebase_DateTime('2014-02-01 00:00:00');
        $until = new Tinebase_DateTime('2014-02-29 23:59:59');

        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $from, $until);

        $recurSet[2]->summary = 'exdate';

        $updatedPersistentEvent = $this->_controller->createRecurException($recurSet[2]);

        $this->expectException('Calendar_Exception_ExdateContainer');

        $updatedPersistentEvent->container_id = $this->_getTestContainer('Calendar', Calendar_Model_Event::class)->getId();
        $this->_controller->update($updatedPersistentEvent);

    }

    /**
     * test get free busy info with recurring event and dst
     *
     * @see 0009558: sometimes free/busy conflicts are not detected
     */
    public function testFreeBusyWithRecurSeriesAndRessourceInDST()
    {
        $event = $this->_getEvent();
        $resource = Calendar_Controller_Resource::getInstance()->create($this->_getResource());
        $event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(array(
            'user_id'   => $resource->getId(),
            'user_type' => Calendar_Model_Attender::USERTYPE_RESOURCE,
        )));
        $event->dtstart = new Tinebase_DateTime('2013-10-14 10:30:00'); // this is UTC
        $event->dtend = new Tinebase_DateTime('2013-10-14 11:45:00');
        $event->rrule = 'FREQ=WEEKLY;INTERVAL=1;WKST=SU;BYDAY=MO';
        $persistentEvent = Calendar_Controller_Event::getInstance()->create($event);

        // check free busy in DST
        $newEvent = $this->_getEvent();
        $newEvent->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(array(
            'user_id'   => $resource->getId(),
            'user_type' => Calendar_Model_Attender::USERTYPE_RESOURCE,
        )));
        $newEvent->dtstart = new Tinebase_DateTime('2014-01-20 12:30:00');
        $newEvent->dtend = new Tinebase_DateTime('2014-01-20 13:30:00');

        $this->expectException('Calendar_Exception_AttendeeBusy');
        $savedEvent = Calendar_Controller_Event::getInstance()->create($newEvent, /* $checkBusyConflicts = */ true);
    }

    public function testRecurEventWithConstrainsBackgroundComputation()
    {
        $constrainEvent = $this->_getRecurEvent();
        $constrainEvent->rrule_constraints = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'in', 'value' => array($constrainEvent['container_id'])),
        ));
        $constrainEvent = Calendar_Controller_Event::getInstance()->create($constrainEvent);

        // create conflicting event
        $conflictEvent = $this->_getRecurEvent();
        $conflictEvent->rrule->until = $conflictEvent->dtstart->getClone()->addDay(5);
        $conflictEvent = Calendar_Controller_Event::getInstance()->create($conflictEvent);

        // run background job
        Calendar_Controller_Event::getInstance()->updateConstraintsExdates();

        // check exdates
        $constrainEvent = Calendar_Controller_Event::getInstance()->get($constrainEvent->getId());
        $this->assertCount(6, $constrainEvent->exdate);
    }

    public function testRecurIdWithSpecialChar()
    {
        $constraintEventWithBadUid = $this->_getRecurEvent();
        $constraintEventWithBadUid->uid = 'MS-OS:[11242,123213,5234';
        $constraintEventWithBadUid->recurid = $constraintEventWithBadUid->uid . '-';
        $events = new Tinebase_Record_RecordSet(Calendar_Model_Event::class);
        $events->addRecord($constraintEventWithBadUid);
        $filteredEvents = Calendar_Model_Rrule::getExceptionsByCandidate($events, $constraintEventWithBadUid);
        self::assertEquals(1, count($filteredEvents));
    }

    /**
     * returns a simple recure event
     *
     * @return Calendar_Model_Event
     */
    protected function _getRecurEvent()
    {
        return new Calendar_Model_Event(array(
            'summary'     => 'Breakfast',
            'dtstart'     => '2010-05-20 06:00:00',
            'dtend'       => '2010-05-20 06:15:00',
            'description' => 'Breakfast',
            'rrule'       => 'FREQ=DAILY;INTERVAL=1',    
            'container_id' => $this->_getTestCalendar()->getId(),
            Tinebase_Model_Grants::GRANT_EDIT    => true,
        ));
    }
}

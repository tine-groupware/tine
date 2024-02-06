<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test class for Calendar_Controller_MSEventFacade
 * 
 * @package     Calendar
 */
class Calendar_Controller_MSEventFacadeTest extends Calendar_TestCase
{
    public function setUp(): void
{
        parent::setUp();
        
        Calendar_Controller_Event::getInstance()->doContainerACLChecks(true);
        
        $this->_uit = Calendar_Controller_MSEventFacade::getInstance();
        $this->_uit->setEventFilter(new Calendar_Model_EventFilter(array(
            array('field' => 'attender', 'operator' => 'equals', 'value' => array(
                'user_type'    => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'      => Tinebase_Core::getUser()->contact_id,
            )),
            array(
                'field' => 'attender_status', 'operator' => 'notin', 'value' => array(
                    Calendar_Model_Attender::STATUS_DECLINED
                )
        ))));
    }
    
    public function getTestEvent()
    {
        $event = $this->_getEvent();
        $event->rrule = 'FREQ=DAILY;INTERVAL=1';
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        $event->attendee[1]->transp = Calendar_Model_Event::TRANSP_TRANSP;
        $event->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            array('minutes_before' => 15),
            array('minutes_before' => 30),
            array('minutes_before' => 'custom', 'alarm_time' => '2009-03-25 04:33:00'),
            array('minutes_before' => 60),
            array('minutes_before' => 90),
        ), TRUE);
        $event->alarms[0]->setOption('skip', array(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => $this->_getTestUserContact()->getId(),
            'organizer' => Tinebase_Core::getUser()->contact_id
        )));
        $event->alarms[1]->setOption('attendee', array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => $this->_getTestUserContact()->getId(),
            'organizer' => Tinebase_Core::getUser()->contact_id
        ));
        $event->alarms[2]->setOption('skip', array(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => $this->_getPersonasContacts('sclever')->getId(),
            'organizer' => Tinebase_Core::getUser()->contact_id
        )));
        $event->alarms[3]->setOption('attendee', array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => $this->_getPersonasContacts('sclever')->getId(),
            'organizer' => Tinebase_Core::getUser()->contact_id
        ));
        
        $persistentException = clone $event;
        $persistentException->recurid = clone $persistentException->dtstart;
        $persistentException->recurid->addDay(1);
        $persistentException->dtstart->addDay(1)->addHour(2);
        $persistentException->dtend->addDay(1)->addHour(2);
        $persistentException->summary = 'exception';
        $exceptions->addRecord($persistentException);
        
        $deletedInstance = clone $event;
        $deletedInstance->dtstart->addDay(2);
        $deletedInstance->dtend->addDay(2);
        $deletedInstance->recurid = clone $deletedInstance->dtstart;
        $deletedInstance->is_deleted = TRUE;
        $exceptions->addRecord($deletedInstance);
        
        $event->exdate = $exceptions;
        return $event;
    }
    
    public function testCreate()
    {
        $event = $this->getTestEvent();
        
        $persistentEvent = $this->_uit->create($event);
        Tinebase_FileSystem_RecordAttachments::getInstance()->addRecordAttachment($persistentEvent, 'agenda.txt', fopen('php://temp', 'rw'));
        Tinebase_FileSystem_RecordAttachments::getInstance()->addRecordAttachment($persistentEvent->exdate[0], 'exception.txt', fopen('php://temp', 'rw'));

        Tinebase_FileSystem_RecordAttachments::getInstance()->getMultipleAttachmentsOfRecords($persistentEvent);
        Tinebase_FileSystem_RecordAttachments::getInstance()->getMultipleAttachmentsOfRecords($persistentEvent->exdate[0]);

        $this->_assertTestEvent($persistentEvent);
        
        return $persistentEvent;
    }
    
    public function testGet()
    {
        $event = $this->testCreate();

        $event = $this->_uit->get($event->getId());
        
        $this->_assertTestEvent($event);
    }
    
    public function testDelete()
    {
        $event = $this->getTestEvent();
        
        $persistentEvent = $this->_uit->create($event);
        $persistentEvent = $this->_uit->delete($event->getId());
    }
    
    public function testSearch()
    {
        $this->testCreate();
        
        $events = $this->_uit->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'in', 'value' => $this->_getTestCalendars()->getId()),
        )));

        $this->assertEquals(1, $events->count());
        $this->_assertTestEvent($events->getFirstRecord());
    }
    
    public function testSearchBaselessExceptions()
    {
        $event = $this->testCreate();
        
        // move baseEvent out of scope
        $cbe = new Calendar_Backend_Sql();
        $cbe->delete($event->getId());
        
        $events = $this->_uit->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'in', 'value' => $this->_getTestCalendars()->getId()),
        )));
        
        $this->assertEquals(1, $events->count());
        $this->assertEquals('exception', $events->getFirstRecord()->summary);
        
    }
    
    /**
     * test organizer based updates 
     */
    public function testUpdateFromOrganizer()
    {
        $event = $this->testCreate();
        
        // fake what the client does
        $event->alarms->setId(NULL);
        foreach ($event->exdate->alarms as $alarms) {
            $alarms->setId(NULL);
        }
        
        $this->_fixConcurrency($event);
        $event = $this->_uit->update($event);
        $this->_assertTestEvent($event);
        
        $sclever = new Calendar_Model_Attender(array(
            'user_id'        => $this->_getPersonasContacts('sclever')->getId(),
            'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
        ));
        
        $currUser = $this->_uit->setCalendarUser($sclever);
        $event = $this->_uit->get($event->getId());
        $this->_uit->setCalendarUser($currUser);
        
        $this->assertEquals(Calendar_Model_Event::TRANSP_TRANSP, $event->transp, 'transp not from perspective');
        $this->assertEquals(3, $event->alarms->count(), 'alarms for 15, 60, 90 should be present for sclever'); 
        $this->assertEquals(1, $event->alarms->filter('minutes_before', 15)->count(), '15 min. before is not present');
        $this->assertEquals(1, $event->alarms->filter('minutes_before', 60)->count(), '60 min. before is not present');
        $this->assertEquals(1, $event->alarms->filter('minutes_before', 90)->count(), '90 min. before is not present');
    }
    
    /**
     * adjusts seq for event to prevent concurrency errors
     * 
     * @param Calendar_Model_Event $event
     */
    protected function _fixConcurrency($event)
    {
        $event->seq = 3;
    }
    
    /**
     * test attendee based updates 
     */
    public function testUpdateFromAttendee()
    {
        $event = $this->testCreate();
        
        $sclever = new Calendar_Model_Attender(array(
            'user_id'        => $this->_getPersonasContacts('sclever')->getId(),
            'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
        ));
        $currUser = $this->_uit->setCalendarUser($sclever);
        $event = $this->_uit->get($event->getId());
        
        // fake what the client does
        $event->alarms->setId(NULL);
        $event->alarms->addRecord(new Tinebase_Model_Alarm(array(
            'minutes_before' => 5,
        ), TRUE));
        $event = $this->_uit->update($event);
        $this->_uit->setCalendarUser($currUser);
        
        $this->assertEquals(Calendar_Model_Event::TRANSP_TRANSP, $event->transp, 'transp not from perspective');
        $this->assertEquals(4, $event->alarms->count(), 'alarms for 5, 15, 60, 90 should be present for sclever'); 
        $this->assertEquals(1, $event->alarms->filter('minutes_before', 5)->count(), '5 min. before is not present');
    }
    
    public function testUpdateRemoveExceptions()
    {
        $event = $this->testCreate();

        $this->_fixConcurrency($event);
        $event->exdate = NULL;
        $updatedEvent = $this->_uit->update($event);
        
        $this->assertEquals(0, $updatedEvent->exdate->count());
    }
    
    public function testUpdateCreateExceptions()
    {
        $event = $this->testCreate();
        
        $newPersistentException = clone $event->exdate->filter('is_deleted', 0)->getFirstRecord();
        $newPersistentException->recurid = clone $event->dtstart;
        $newPersistentException->recurid->addDay(3);
        $newPersistentException->dtstart->addDay(2)->addHour(2);
        $newPersistentException->dtend->addDay(2)->addHour(2);
        $newPersistentException->summary = 'new exception';
        $newPersistentException->setId(null);
        $event->exdate->addRecord($newPersistentException);
        
        $newDeletedInstance = clone $event->exdate->filter('is_deleted', 1)->getFirstRecord();
        $newDeletedInstance->dtstart->addDay(2);
        $newDeletedInstance->dtend->addDay(2);
        $newDeletedInstance->recurid = clone $newDeletedInstance->dtstart;
        $newDeletedInstance->is_deleted = TRUE;
        $newPersistentException->setId(null);
        $event->exdate->addRecord($newDeletedInstance);
        
        $this->_fixConcurrency($event);
        $updatedEvent = $this->_uit->update($event);
        
        $this->assertEquals(4, $updatedEvent->exdate->count());
    }
    
    public function testUpdateUpdateExceptions()
    {
        $event = $this->testCreate();
        
        $persistentException = $event->exdate->filter('is_deleted', 0)->getFirstRecord();
        $persistentException->dtstart->addHour(2);
        $persistentException->dtend->addHour(2);
        $persistentException->summary = 'updated exception';
        
        $this->_fixConcurrency($event);
        $updatedEvent = $this->_uit->update($event);
        
        $this->assertEquals(2, $updatedEvent->exdate->count());
        $updatedPersistentException = $updatedEvent->exdate->filter('is_deleted', 0)->getFirstRecord();
        $this->assertEquals('updated exception', $updatedPersistentException->summary);
        $this->assertEquals('2009-03-26 10:00:00', $updatedPersistentException->dtstart->format(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    /**
     * testUpdatePreserveAlarmProperties
     * 
     * @see #7430: Calendar sends too much alarms for recurring events
     *
     * @group nodockerci
     *        fails with:
     * Tinebase_Exception_NotFound: Tinebase_Model_Tree_Node record with id = b4ab92dd51c4c7ff7efdbd4cf86d1efe935c3309 not found!
     * (flushMailer / Tinebase_ActionQueue::getInstance()->processQueue();)
     */
    public function testUpdatePreserveAlarmProperties()
    {
        $alarm30 = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array(
            array('minutes_before' => 30),
        ), TRUE);
        
        $event = $this->_getEvent();
        $event->dtstart = Tinebase_DateTime::now()->subDay(1)->addMinute(15);
        $event->dtend = clone $event->dtstart;
        $event->dtend->addHour(2);
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;COUNT=3';
        $event->alarms = clone $alarm30;
        $event->organizer = Tinebase_Core::getUser()->contact_id;
        $event = Calendar_Controller_Event::getInstance()->create($event);
        
        $exceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        $recurSet = Calendar_Model_Rrule::computeRecurrenceSet($event, $exceptions, $event->dtstart, Tinebase_DateTime::now()->addDay(1));
        $exceptionEvent = Calendar_Controller_Event::getInstance()->createRecurException($recurSet->getFirstRecord());
        
        Tinebase_Alarm::getInstance()->sendPendingAlarms("Tinebase_Event_Async_Minutely");
        Calendar_Controller_EventNotificationsTests::flushMailer();
        
        $event = $this->_uit->get($event->getId());
        $persistentAlarm = $event->exdate[0]->alarms->getFirstRecord();
        $event->alarms = $event->alarms = clone $alarm30;
        Calendar_Controller_Alarm::setAcknowledgeTime($event->alarms, Tinebase_DateTime::now());
        foreach ($event->exdate as $exdate) {
            $exdate->alarms = clone $alarm30;
        }
        $updatedEvent = $this->_uit->update($event);
        $updatedAlarm = $updatedEvent->exdate[0]->alarms->getFirstRecord();
        
        $this->assertNotNull($persistentAlarm);
        $diff = $persistentAlarm->diff($updatedAlarm);
        $this->assertTrue($diff->isEmpty(), 'no diff');
        $this->assertTrue(Calendar_Controller_Alarm::getAcknowledgeTime($updatedEvent->alarms->getFirstRecord()) instanceof Tinebase_DateTime, 'ack time missing');
    }
    
    /**
     * testAttendeeStatusUpdate
     */
    public function testAttendeeStatusUpdate()
    {
        $event = $this->testCreate();
        
        $testAttendee = new Calendar_Model_Attender(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => Tinebase_Core::getUser()->contact_id,
            'organizer' => Tinebase_Core::getUser()->contact_id
        ));
        
        // update base events status
        $testAttendee = Calendar_Model_Attender::getAttendee($event->attendee, $testAttendee);
        $testAttendee->status = Calendar_Model_Attender::STATUS_TENTATIVE;
        $updatedEvent = $this->_uit->attenderStatusUpdate($event, $testAttendee);
        
        $this->assertEquals(2, count($updatedEvent->exdate), 'num exdate mismatch');
        $this->assertEquals(Calendar_Model_Attender::STATUS_TENTATIVE, Calendar_Model_Attender::getAttendee($updatedEvent->attendee, $testAttendee)->status, 'status of baseevent was not updated');
        $this->assertEquals(Calendar_Model_Attender::STATUS_NEEDSACTION, Calendar_Model_Attender::getAttendee($updatedEvent->exdate->filter('is_deleted', 0)->getFirstRecord()->attendee, $testAttendee)->status, 'status of exdate must not be updated');
        
        // update exiting persistent exception
        Calendar_Model_Attender::getAttendee($updatedEvent->exdate->filter('is_deleted', 0)->getFirstRecord()->attendee, $testAttendee)->status = Calendar_Model_Attender::STATUS_ACCEPTED;
        $updatedEvent = $this->_uit->attenderStatusUpdate($updatedEvent, $testAttendee);
        
        $this->assertEquals(2, count($updatedEvent->exdate), 'persistent exdate num exdate mismatch');
        $this->assertEquals(Calendar_Model_Attender::STATUS_TENTATIVE, Calendar_Model_Attender::getAttendee($updatedEvent->attendee, $testAttendee)->status, 'persistent exdate status of baseevent was not updated');
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, Calendar_Model_Attender::getAttendee($updatedEvent->exdate->filter('is_deleted', 0)->getFirstRecord()->attendee, $testAttendee)->status, 'persistent exdate status of exdate must not be updated');
        
        $newException = $this->_createEventException($event);
        $updatedEvent->exdate->addRecord($newException);
        
        Calendar_Model_Attender::getAttendee($newException->attendee, $testAttendee)->status = Calendar_Model_Attender::STATUS_DECLINED;
        $updatedEvent = $this->_uit->attenderStatusUpdate($updatedEvent, $testAttendee);
        
        $this->assertEquals(3, count($updatedEvent->exdate), 'new exdate num exdate mismatch');
    }
    
    /**
     * create event exception
     * 
     * @param Calendar_Model_Event $event
     * @return Calendar_Model_Event
     */
    protected function _createEventException($event)
    {
        $newException = clone $event;
        $newException->id = NULL;
        $newException->base_event_id = $event->getId();
        $newException->recurid = clone $newException->dtstart;
        $newException->recurid->addDay(3);
        $newException->dtstart->addDay(3)->addHour(2);
        $newException->dtend->addDay(3)->addHour(2);
        $newException->summary = 'new exception';
        $newException->exdate = NULL;
        
        return $newException;
    }
    
    /**
     * testAlarmAckInRecurException
     * 
     * @see 0009396: alarm_ack_time and alarm_snooze_time are not updated
     */
    public function testAlarmAckInRecurException()
    {
        $event = $this->testCreate();
        
        // save event as sclever to ack sclevers alarm
        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        
        $exdateAlarms = $event->exdate->getFirstRecord()->alarms;
        $ackTime = Tinebase_DateTime::now();
        $scleverAlarm = new Tinebase_Model_Alarm(array(
            'model'          => 'Calendar_Model_Event',
            'alarm_time'     => $ackTime,
            'minutes_before' => 90
        ));
        $ackAlarm = Calendar_Controller_Alarm::getMatchingAlarm($exdateAlarms, $scleverAlarm);
        Calendar_Controller_Alarm::setAcknowledgeTime($ackAlarm, $ackTime);
        $updatedEvent = $this->_uit->update($event);
        
        $this->assertEquals(3, count($updatedEvent->alarms));
        $updatedAlarm = Calendar_Controller_Alarm::getMatchingAlarm($updatedEvent->exdate->getFirstRecord()->alarms, $scleverAlarm);
        $this->assertEquals($ackTime, Calendar_Controller_Alarm::getAcknowledgeTime($updatedAlarm));
        
        // check alarm ack client + ip
        $accessLog = Tinebase_Core::get(Tinebase_Core::USERACCESSLOG);
        $this->assertEquals($accessLog->ip, $updatedAlarm->getOption(Tinebase_Model_Alarm::OPTION_ACK_IP), 'ip not found in options: ' . print_r($updatedAlarm->toArray(), true));
        $expectedClient = 'type: ' . $accessLog->clienttype . '|useragent: ' . $_SERVER['HTTP_USER_AGENT'];
        $this->assertEquals($expectedClient, $updatedAlarm->getOption(Tinebase_Model_Alarm::OPTION_ACK_CLIENT), 'clienttype not found in options: ' . print_r($updatedAlarm->toArray(), true));
    }
    
    /**
     * sclever declines event exception.
     * => from her iTIP perspective, with the filter, this is an fallout
     */
    public function testPerspectiveExceptionFallout()
    {
        $event = $this->testCreate();
        
        $persistentException = $event->exdate->filter('is_deleted', 0)->getFirstRecord();
        
        $persistentSClever = $this->_getAttenderFromAttendeeSet($persistentException->attendee, 'sclever');
        $persistentException->attendee->removeRecord($persistentSClever);
        
        $currUser = $this->_uit->setCalendarUser($persistentSClever);
        $this->_uit->setEventFilter(new Calendar_Model_EventFilter(array(
            array('field' => 'attender', 'operator' => 'equals', 'value' => array(
                'user_type'    => Calendar_Model_Attender::USERTYPE_USER,
                'user_id'      => $this->_getPersonasContacts('sclever')->getId(),
            )),
            array(
                'field' => 'attender_status', 'operator' => 'notin', 'value' => array(
                    Calendar_Model_Attender::STATUS_DECLINED
                )
        ))));
        
        $this->_fixConcurrency($event);
        $event = $this->_uit->update($event);
        
        $event = $this->_uit->get($event->getId());
        $this->_uit->setCalendarUser($currUser);
        
        $persistentException = $event->exdate->filter('is_deleted', 0)->getFirstRecord();
        $this->assertNull($persistentException, 'exdate without sclever should be marked as deleted: ' . (($persistentException) ? print_r($persistentException->toArray()) : ''));
    }
    
    public function testMissingEmailAttendee()
    {
        $noMailContact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'org_name' => 'nomail'
        )));
        
        $noMailAttendee = new Calendar_Model_Attender(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'   => $noMailContact->getId(),
            'organizer' => Tinebase_Core::getUser()->contact_id
        ));
        
        $event = $this->_getEvent();
        $event->attendee->addRecord($noMailAttendee);
        
        $persistentEvent = Calendar_Controller_Event::getInstance()->create($event);
        $loadedEvent = $this->_uit->get($persistentEvent->getId());
        
        $this->assertTrue((bool) Calendar_Model_Attender::getAttendee($persistentEvent->attendee, $noMailAttendee));
        $this->assertFalse((bool) Calendar_Model_Attender::getAttendee($loadedEvent->attendee, $noMailAttendee));
        
        $loadedEvent->summary = 'update';
        $update = $this->_uit->update($loadedEvent);
        
        $loadedEvent = Calendar_Controller_Event::getInstance()->get($persistentEvent->getId());
        
        $this->assertEquals('update', $loadedEvent->summary);
        $this->assertTrue((bool) Calendar_Model_Attender::getAttendee($loadedEvent->attendee, $noMailAttendee));
    }
    
    /**
     * asserts tested event
     * 
     * @param Calendar_Model_Event $persistentEvent
     */
    protected function _assertTestEvent($persistentEvent)
    {
        $this->assertEquals(2, $persistentEvent->exdate->count());
        
        $this->assertEquals(Calendar_Model_Event::TRANSP_OPAQUE, $persistentEvent->transp, 'base transp from perspective');
        $this->assertEquals(3, count($persistentEvent->alarms), 'base alarms not from perspective');
        $this->assertEquals(0, count($persistentEvent->alarms->filter('minutes_before', 15)), '15 min. before is not skipped');
        $this->assertEquals(0, count($persistentEvent->alarms->filter('minutes_before', 60)), '60 min. before is not for test CU');
        $this->assertEquals(1, count($persistentEvent->attachments), 'base attachment missing');
        $this->assertEquals('agenda.txt', $persistentEvent->attachments[0]->name, 'base attachment wrong name');

        $persistException = $persistentEvent->exdate->filter('is_deleted', 0)->getFirstRecord();
        $this->assertEquals('2009-03-26 08:00:00', $persistException->dtstart->format(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('2009-03-26 06:00:00', $persistException->getOriginalDtStart()->format(Tinebase_Record_Abstract::ISO8601LONG));
        $this->assertEquals('exception', $persistException->summary);
        $this->assertEquals(Calendar_Model_Event::TRANSP_OPAQUE, $persistException->transp, 'recur transp from perspective');
        $this->assertEquals(3, count($persistException->alarms), 'exception alarms not from perspective');
        $this->assertEquals(0, count($persistException->alarms->filter('minutes_before', 15)), '15 min. before is not skipped');
        $this->assertEquals(0, count($persistException->alarms->filter('minutes_before', 60)), '60 min. before is not for test CU');
        $this->assertEquals(1, count($persistException->attachments), 'exception attachment missing');
        $this->assertEquals('exception.txt', $persistException->attachments[0]->name, 'exception attachment wrong name');

        $deletedInstance = $persistentEvent->exdate->filter('is_deleted', 1)->getFirstRecord();
        $this->assertEquals('2009-03-27 06:00:00', $deletedInstance->dtstart->format(Tinebase_Record_Abstract::ISO8601LONG));
    }
}

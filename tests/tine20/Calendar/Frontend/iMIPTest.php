<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2011-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @todo        add test testOrganizerSendBy
 * @todo extend Calendar_TestCase
 */

/**
 * Test class for Calendar_Frontend_iMIP
 */
class Calendar_Frontend_iMIPTest extends TestCase
{
    /**
     * iMIP frontent to be tested
     * 
     * @var Calendar_Frontend_iMIP
     */
    protected $_iMIPFrontend = NULL;
    
    /**
    * email test class
    *
    * @var Felamimail_Controller_MessageTest
    */
    protected $_emailTestClass;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY) {
            // account email addresses are empty with AD backend
            $this->markTestSkipped('skipped for ad backend');
        }

        Calendar_Controller_Event::getInstance()->sendNotifications(true);
        
        Calendar_Config::getInstance()->set(Calendar_Config::DISABLE_EXTERNAL_IMIP, false);

        $this->_iMIPFrontend = new Calendar_Frontend_iMIP();
        Calendar_Frontend_iMIP::$doIMIPSpoofProtection = false;
        
        try {
            $this->_emailTestClass = new Felamimail_Controller_MessageTest();
            $this->_emailTestClass->setup();
        } catch (Exception $e) {
            // do nothing
        }

        parent::setUp();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
    {
        Calendar_Frontend_iMIP::$doIMIPSpoofProtection = true;

        Calendar_Controller_Event::getInstance()->sendNotifications(false);
        
        if ($this->_emailTestClass instanceof Felamimail_Controller_MessageTest) {
            $this->_emailTestClass->tearDown();
        }

        parent::tearDown();

        // remove instance to prevent acl pollution
        Admin_Controller_EmailAccount::destroyInstance();
        Calendar_Controller_Event::unsetInstance();
    }

    public function testInviteExceptionThenSeries()
    {
        $sharedContainer = $this->_getTestContainer('Calendar', Calendar_Model_Event::class, true);
        $event = $this->_getEvent();
        $event->organizer = null;
        $event->organizer_email = 'l.kneschke@caldav.org';
        $event->organizer_type = Calendar_Model_Event::ORGANIZER_TYPE_EMAIL;
        $event->dtstart = Tinebase_DateTime::now()->setTime(10, 0);
        $event->dtend = $event->dtstart->getClone()->setTime(11, 0);
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=' . $event->dtend->getClone()->addDay(5)->toString();
        $event->container_id = $sharedContainer;
        $event->attendee->removeRecord($event->attendee->find('user_id', Tinebase_Core::getUser()->contact_id));
        $attendee = $event->attendee->find('user_id', $this->_personas['sclever']->contact_id);
        $event->attendee->removeRecord($attendee);

        $calCtrl = Calendar_Controller_Event::getInstance();
        $calCtrl->sendNotifications(false);
        $createdEvent = $calCtrl->create($event);

        $exceptions = new Tinebase_Record_RecordSet(Calendar_Model_Event::class);
        $nextOccurance = Calendar_Model_Rrule::computeNextOccurrence($createdEvent, $exceptions, $createdEvent->dtend->getClone());
        $nextOccurance->dtstart->addMinute(30);
        $nextOccurance->dtend->addMinute(30);
        $nextOccurance->attendee->addRecord($attendee);
        $exception1 = $calCtrl->createRecurException($nextOccurance);
        $exceptions->addRecord($exception1);

        $refMeth = new ReflectionMethod(Calendar_Controller_EventNotifications::class, '_createVCalendar');
        $refMeth->setAccessible(true);
        // be aware of $component->{'ORGANIZER'}->add('SENT-BY', 'mailto:' . $updater->accountEmailAddress); .... relevant?
        // exception1
        $vcalendar = $refMeth->invoke(Calendar_Controller_EventNotifications::getInstance(), $calCtrl->get($exception1->getId()), Calendar_Model_iMIP::METHOD_REQUEST, Tinebase_Core::getUser(), $attendee)->serialize();

        $createdEvent = $calCtrl->get($createdEvent->getId());
        $nextOccurance = Calendar_Model_Rrule::computeNextOccurrence($createdEvent, $exceptions, $exception1->dtend->getClone());
        $nextOccurance->dtstart->addMinute(45);
        $nextOccurance->dtend->addMinute(45);
        $nextOccurance->attendee->addRecord($attendee);
        $exception2 = $calCtrl->createRecurException($nextOccurance);

        // exception2
        $vcalendar1 = $refMeth->invoke(Calendar_Controller_EventNotifications::getInstance(), $calCtrl->get($exception2->getId()), Calendar_Model_iMIP::METHOD_REQUEST, Tinebase_Core::getUser(), $attendee)->serialize();

        $createdEvent->attendee->addRecord($attendee);
        $createdEvent = $calCtrl->update($createdEvent);
        // whole series
        $vcalendar2 = $refMeth->invoke(Calendar_Controller_EventNotifications::getInstance(), $createdEvent, Calendar_Model_iMIP::METHOD_REQUEST, Tinebase_Core::getUser(), $attendee)->serialize();

        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(false);
        Tinebase_TransactionManager::getInstance()->rollBack();
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        $iMIP = new Calendar_Model_iMIP([
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'ics'            => (string)$vcalendar,
            'method'         => 'REQUEST',
            'originator'     => 'l.kneschke@caldav.org',
        ]);
        $this->_iMIPFrontend->process($iMIP);
        $firstEvent = $calCtrl->get($iMIP->events->getFirstRecord()->getId());

        $this->assertSame($exception1->dtstart->toString(), $firstEvent->dtstart->toString());
        $this->assertTrue($firstEvent->isRecurException());
        $this->assertSame($exception1->recurid, $firstEvent->recurid);
        $this->assertSame($exception1->uid, $firstEvent->uid);
        $this->assertNull($firstEvent->base_event_id);

        $iMIP = new Calendar_Model_iMIP([
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'ics'            => (string)$vcalendar1,
            'method'         => 'REQUEST',
            'originator'     => 'l.kneschke@caldav.org',
        ]);
        $this->_iMIPFrontend->process($iMIP);
        $secondEvent = $calCtrl->get($iMIP->events->getFirstRecord()->getId());

        $this->assertSame($exception2->dtstart->toString(), $secondEvent->dtstart->toString());
        $this->assertSame($exception2->recurid, $secondEvent->recurid);
        $this->assertSame($exception2->uid, $secondEvent->uid);
        $this->assertTrue($secondEvent->isRecurException());
        $this->assertNull($secondEvent->base_event_id);


        $firstEvent = $calCtrl->get($firstEvent->getId());
        $this->assertSame($secondEvent->uid, $firstEvent->uid);
        $this->assertNotSame($secondEvent->getId(), $firstEvent->getId());
        $this->assertNotSame($secondEvent->dtstart->toString(), $firstEvent->dtstart->toString());
        $this->assertNotSame($secondEvent->recurid, $firstEvent->recurid);


        $iMIP = new Calendar_Model_iMIP([
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'ics'            => (string)$vcalendar2,
            'method'         => 'REQUEST',
            'originator'     => 'l.kneschke@caldav.org',
        ]);
        $this->_iMIPFrontend->process($iMIP);
        $this->assertSame(1, $iMIP->events->count());
        $iMIPEvent = $iMIP->getExistingEvent($iMIP->getEvents()->getFirstRecord());
        $this->assertSame(2, $iMIPEvent->exdate->count());
        $iMIPException1 = $calCtrl->get($iMIPEvent->exdate->getFirstRecord()->getId());
        $iMIPException2 = $calCtrl->get($iMIPEvent->exdate->getLastRecord()->getId());

        $this->assertSame($createdEvent->dtstart->toString(), $iMIPEvent->dtstart->toString());
        $this->assertSame($firstEvent->dtstart->toString(), $iMIPException1->dtstart->toString());
        $this->assertSame($firstEvent->getId(), $iMIPException1->getId());
        $this->assertSame($secondEvent->dtstart->toString(), $iMIPException2->dtstart->toString());
        $this->assertSame($secondEvent->getId(), $iMIPException2->getId());
    }

    public function testExternalIMIPViews()
    {
        $externalContact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'email' => 'external@domain.tld',
        ]));
        $externalContact1 = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact([
            'email' => 'external1@domain.tld',
        ]));
        $sharedContainer = $this->_getTestContainer('Calendar', Calendar_Model_Event::class, true);
        $event = $this->_getEvent();
        $event->dtstart = Tinebase_DateTime::now()->setTime(10, 0);
        $event->dtend = $event->dtstart->getClone()->setTime(11, 0);
        $event->rrule = 'FREQ=DAILY;INTERVAL=1;UNTIL=' . $event->dtend->getClone()->addDay(5)->toString();
        $event->container_id = $sharedContainer;
        $event->attendee->addRecord(new Calendar_Model_Attender([
            'user_id'        => $externalContact1->getId(),
            'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
            'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
        ]));

        $calCtrl = Calendar_Controller_Event::getInstance();
        $calCtrl->sendNotifications(false);
        $createdEvent = $calCtrl->create($event);
        $attendee = $createdEvent->attendee->find('user_id', $externalContact1->getId());

        $exceptions = new Tinebase_Record_RecordSet(Calendar_Model_Event::class);
        $nextOccurance = Calendar_Model_Rrule::computeNextOccurrence($createdEvent, $exceptions, $createdEvent->dtend->getClone());
        $nextOccurance->attendee->addRecord(new Calendar_Model_Attender([
            'user_id'        => $externalContact->getId(),
            'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
            'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
        ]));
        $exception1 = $calCtrl->createRecurException($nextOccurance);
        $exceptions->addRecord($exception1);

        $nextOccurance = Calendar_Model_Rrule::computeNextOccurrence($calCtrl->get($createdEvent->getId()), $exceptions, $nextOccurance->dtend->getClone());
        $nextOccurance->attendee->addRecord(new Calendar_Model_Attender([
            'user_id'        => $externalContact->getId(),
            'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
            'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
        ]));
        $exception2 = $calCtrl->createRecurException($nextOccurance);

        $refMeth = new ReflectionMethod(Calendar_Controller_EventNotifications::class, '_createVCalendar');
        $refMeth->setAccessible(true);
        $vcalendar = $refMeth->invoke(Calendar_Controller_EventNotifications::getInstance(), $calCtrl->get($createdEvent->getId()), Calendar_Model_iMIP::METHOD_REQUEST, Tinebase_Core::getUser(), $attendee)->serialize();

        $converter = new Calendar_Convert_Event_VCalendar2_Abstract();
        $imipEvent = $converter->toTine20Models($vcalendar)->getFirstRecord();
        $this->assertSame($createdEvent->uid, $imipEvent->uid);
        $this->assertSame($createdEvent->dtstart->toString(), $imipEvent->dtstart->toString());
        $this->assertSame(2, $imipEvent->exdate->count());

        $exception1Attendee = $exception1->attendee->find('user_id', $externalContact->getId());
        $vcalendarEx1 = $refMeth->invoke(Calendar_Controller_EventNotifications::getInstance(),$exception1, Calendar_Model_iMIP::METHOD_REQUEST, Tinebase_Core::getUser(), $exception1Attendee)->serialize();

        $imipEvent = $converter->toTine20Models($vcalendarEx1)->getFirstRecord();
        $this->assertSame($exception1->uid, $imipEvent->uid);
        $this->assertSame($exception1->dtstart->toString(), $imipEvent->dtstart->toString());
        $this->assertEmpty($imipEvent->exdate);

        $exception2Attendee = $exception2->attendee->find('user_id', $externalContact->getId());
        $vcalendarEx2 = $refMeth->invoke(Calendar_Controller_EventNotifications::getInstance(),$exception2, Calendar_Model_iMIP::METHOD_REQUEST, Tinebase_Core::getUser(), $exception2Attendee)->serialize();

        $imipEvent = $converter->toTine20Models($vcalendarEx2)->getFirstRecord();
        $this->assertSame($exception2->uid, $imipEvent->uid);
        $this->assertSame($exception2->dtstart->toString(), $imipEvent->dtstart->toString());
        $this->assertEmpty($imipEvent->exdate);
    }

    protected function _prepCounterIMIP(?string $externalEmail, ?Calendar_Model_Event &$event = null): Calendar_Model_iMIP
    {
        if (null === $event) {
            $event = $this->_getEvent();
            $event->attendee->addRecord(new Calendar_Model_Attender([
                'user_email' => $externalEmail,
                'user_type' => Calendar_Model_Attender::USERTYPE_EMAIL,
                'role' => Calendar_Model_Attender::ROLE_REQUIRED,
            ]));

            $oldSend = Calendar_Controller_Event::getInstance()->sendNotifications(false);
            try {
                $event = Calendar_Controller_Event::getInstance()->create($event);
            } finally {
                Calendar_Controller_Event::getInstance()->sendNotifications($oldSend);
            }
        }

        $counterEvent = clone $event;
        $counterEvent->summary = 'counter!';
        $counterEvent->dtstart->addHour(1);
        $counterEvent->dtend->addHour(1);

        return new Calendar_Model_iMIP(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'ics'            => (new Calendar_Convert_Event_VCalendar_Generic)->fromTine20Model($counterEvent)->serialize(),
            'method'         => 'COUNTER',
            'originator'     => $externalEmail,
        ));
    }

    public function testAcceptCounter(): void
    {
        $event = null;
        $iMIP = $this->_prepCounterIMIP($externalEmail = 'a@bcd.ef', $event);
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $updatedEvent = Calendar_Controller_Event::getInstance()->get($event->getId());

        $this->assertSame($updatedEvent->dtstart->toString(), $event->dtstart->getClone()->addHour(1)->toString());
    }

    public function testDeclineCounter(): void
    {
        $iMIP = $this->_prepCounterIMIP($externalEmail = 'a@bcd.ef');

        Calendar_Controller_EventNotificationsTests::flushMailer();
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_DECLINED);

        $msgs = Calendar_Controller_EventNotificationsTests::getMessages();
        $this->assertCount(1, $msgs);
        /** @var Tinebase_Mail $msg */
        $msg = $msgs[0];
        $this->assertCount(1, $msg->getRecipients());
        $this->assertSame($externalEmail, current($msg->getRecipients()));
        $this->assertStringContainsString('Proposed changes for event',  $msg->getSubject());
        $this->assertStringContainsString('declined',  $msg->getSubject());
        $this->assertStringContainsString('METHOD:DECLINECOUNTER',  $msg->getPartContent(0));
    }

    public function testAutoProcessCounter(): void
    {
        $iMIP = $this->_prepCounterIMIP($externalEmail = 'a@bcd.ef');

        $this->_iMIPFrontend->prepareComponent($iMIP);
        $this->assertArrayHasKey('counterDiff', $iMIP->xprops());
        $counterDiff = $iMIP->xprops()['counterDiff'];
        sort($counterDiff);
        $counterDiffLog = print_r($counterDiff, true);
        $this->assertCount(3, $iMIP->xprops()['counterDiff'], $counterDiffLog);
        $this->assertSame(['dtend', 'dtstart', 'summary'], $counterDiff, $counterDiffLog);
    }

    public function testExternalInvitationToOneOfARecurSeries()
    {
        $ics = Calendar_Frontend_WebDAV_EventTest::getVCalendar(dirname(__FILE__) .
            '/files/exchange_external_reoccuring_onlyone.ics');
        $iMIP = new Calendar_Model_iMIP(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'ics'            => $ics,
            'method'         => 'REQUEST',
            'originator'     => 'l.kneschke@caldav.org',
        ));

        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        $this->_iMIPFrontend->process($iMIP);

        static::assertTrue($iMIP->events->getFirstRecord() instanceof Calendar_Model_Event, 'imips event not set');
        static::assertEquals(1, count($iMIP->events->getFirstRecord()->attendee));
        static::assertEquals('Daily Call', $iMIP->events->getFirstRecord()->summary);
        static::assertEquals('RECURRENCE-ID;TZID=W. Europe Standard Time:20180906T110000',
            $iMIP->events->getFirstRecord()->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['RECURRENCE-ID']);
        static::assertEquals('X-MICROSOFT-CDO-OWNERAPPTID:1983350753',
            $iMIP->events->getFirstRecord()->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['X-MICROSOFT-CDO-OWNERAPPTID']);
        $this->assertSame('X-MICROSOFT-SKYPETEAMSPROPERTIES:{"cid":"19:meeting_YmYwMzdhNTktOTQxMy00MmN' . "\r\n" .
' kLWE1ZDAtOTliYmIwZDQxMDIz@thread.v2"\,"private":true\,"type":0\,"mid":0\,"' . "\r\n" .
' rid":0\,"uid":null}',
            $iMIP->events->getFirstRecord()->xprops()[Calendar_Model_Event::XPROPS_IMIP_PROPERTIES]['X-MICROSOFT-SKYPETEAMSPROPERTIES']);

        // TODO test that msg send to external server contains proper recurid
        $iMIP->preconditionsChecked = [$iMIP->events->getFirstRecord()->getRecurIdOrUid() => true];
        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var \Tine20\VObject\Component\VCalendar $vcalendar */
        $vcalendar = Calendar_Convert_Event_VCalendar_Factory::factory('')->fromTine20Model($iMIP->getEvents()->getFirstRecord());
        $vCalBlob = $vcalendar->serialize();
        static::assertStringContainsString('RECURRENCE-ID;TZID=W. Europe Standard Time:20180906T110000', $vCalBlob);
        static::assertStringContainsString('X-MICROSOFT-CDO-OWNERAPPTID:1983350753', $vCalBlob);
        static::assertStringContainsString('X-MICROSOFT-SKYPETEAMSPROPERTIES:{"cid":"19:meeting_YmYwMzdhNTktOTQxMy00MmN', $vCalBlob);
    }

    /**
     * testExternalInvitationRequestAutoProcess
     */
    public function testExternalInvitationRequestAutoProcess($_doAssertation = true, $_doAutoProcess = true)
    {
        return $this->_testExternalImap('invitation_request_external.ics', 5, 'test mit extern', $_doAssertation,
            $_doAutoProcess);
    }

    /**
     * testExternalInvitationRequestAutoProcess
     */
    public function testBadTZ()
    {
        $iMIP = $this->_testExternalImap('invitation_bad_tz.ics', 2, 'test');
        $event = $iMIP->getEvents()->getFirstRecord();

        // VCalendar will default to UTC on broken / bad TZ (like BRT), that them somehow defaults to UserTZ!
        // if that is such a good idea?
        static::assertEquals(Tinebase_Core::getUserTimezone(), $event->originator_tz);
    }

    public function testExternalInvitationRequestMultiImport()
    {
        if (Tinebase_Core::getUser()->accountLoginName === 'github') {
            static::markTestSkipped('FIXME on github-ci');
        }

        $firstIMIP = $this->testExternalInvitationRequestAutoProcess();
        $this->_iMIPFrontend->process($firstIMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        Calendar_Model_Attender::clearCache();

        $secondIMIP = $this->testExternalInvitationRequestAutoProcess(false, false);

        static::assertIsArray($secondIMIP->preconditionsChecked, 'preconditions have not been checked');
        static::assertTrue(current($secondIMIP->preconditionsChecked), 'preconditions have not been checked');
        static::assertSame(1, count($secondIMIP->existing_events), 'there should be an existing event');
        static::assertEmpty($secondIMIP->preconditions, 'no preconditions should be raised');
        static::assertEquals($secondIMIP->event->organizer_email, $secondIMIP->existing_event->organizer_email,
            'organizer mismatch');
        static::assertEquals(4, count($secondIMIP->events->getFirstRecord()->attendee));
        static::assertEquals(5, count(current($secondIMIP->existing_events)->attendee));
        static::assertEquals(2, current($secondIMIP->existing_events)->attendee->filter('status',
            Calendar_Model_Attender::STATUS_ACCEPTED)->count(), 'organizer and vagrant should have accepted');
        $this->_iMIPFrontend->process($secondIMIP, Calendar_Model_Attender::STATUS_ACCEPTED);
        $event = Calendar_Controller_Event::getInstance()->get($firstIMIP->events->getFirstRecord()->getId());
        static::assertEquals(3, $event->attendee->filter('status', Calendar_Model_Attender::STATUS_ACCEPTED)->count(),
            'organizer, vagrant and sclever should have accepted');
    }

    /**
     * @param $icsFilename
     * @param $numAttendee
     * @param $summary
     * @param bool $_doAssertation
     * @param bool $_doAutoProcess
     * @return Calendar_Model_iMIP
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     * @throws Zend_Db_Statement_Exception
     */
    protected function _testExternalImap($icsFilename, $numAttendee, $summary, $_doAssertation = true,
        $_doAutoProcess = true)
    {
        $ics = Calendar_Frontend_WebDAV_EventTest::getVCalendar(dirname(__FILE__) . '/files/' . $icsFilename);
        $ics = preg_replace('#\d{8}T#', Tinebase_DateTime::now()->addDay(1)->format('Ymd') . 'T', $ics);

        $iMIP = new Calendar_Model_iMIP(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'ics'            => $ics,
            'method'         => 'REQUEST',
            'originator'     => 'l.Kneschke@caldav.org',
        ));

        if ($_doAutoProcess) {
            $this->_iMIPFrontend->autoProcess($iMIP);
        }
        $this->_iMIPFrontend->prepareComponent($iMIP);

        if ($_doAssertation) {
            $this->assertEmpty($iMIP->preconditions, 'no preconditions should be raised');
            $this->assertEquals($numAttendee, count($iMIP->events->getFirstRecord()->attendee));
            $this->assertEquals($summary, $iMIP->events->getFirstRecord()->summary);
        }

        return $iMIP;
    }

    /**
     * testExternalInvitationRequestAutoProcessMozilla
     */
    public function testExternalInvitationRequestAutoProcessMozilla()
    {
        $this->_testExternalImap('invitation_request_external_mozilla.ics', 2, 'Input Plakat für Veranstaltung am 19.10.');
    }

    /**
     * testSearchSharedCalendarsForExternalEvents
     *
     * @see 0011024: don't show external imip events in shared calendars
     */
    public function testSearchSharedCalendarsForExternalEvents()
    {
        $iMIP = $this->testExternalInvitationRequestAutoProcess();
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => '/shared')
        ));
        $eventsInShared = Calendar_Controller_Event::getInstance()->search($filter);

        $this->assertFalse(in_array($iMIP->events->getFirstRecord()->getId(), $eventsInShared->getArrayOfIds()),
            'found event in shared calendar: ' . print_r($iMIP->events->toArray(), true));
    }

    /**
    * testSupportedPrecondition
    */
    public function testUnsupportedPrecondition()
    {
        $iMIP = $this->_getiMIP('PUBLISH');
            
        $this->_iMIPFrontend->prepareComponent($iMIP);
        $key = $iMIP->getEvents()->getFirstRecord()->getRecurIdOrUid();
    
        $this->assertEquals(1, count($iMIP->preconditions));
        $this->assertEquals('processing published events is not supported yet', $iMIP->preconditions[$key][Calendar_Model_iMIP::PRECONDITION_SUPPORTED][0]['message']);
        $this->assertFalse($iMIP->preconditions[$key][Calendar_Model_iMIP::PRECONDITION_SUPPORTED][0]['check']);
    }
    
    /**
     * get iMIP record from internal event
     * 
     * @param string $_method
     * @param boolean $_addEventToiMIP
     * @return Calendar_Model_iMIP
     */
    protected function _getiMIP($_method, $_addEventToiMIP = FALSE, $_testEmptyMethod = FALSE)
    {
        $email = $this->_getEmailAddress();
        
        $event = $this->_getEvent();
        $event = Calendar_Controller_Event::getInstance()->create($event);
        
        if ($_method == 'REPLY') {
            $personas = Zend_Registry::get('personas');
            $sclever = $personas['sclever'];
            
            $scleverAttendee = $event->attendee
                ->filter('status', Calendar_Model_Attender::STATUS_NEEDSACTION)
                ->getFirstRecord();
            
            $scleverAttendee->status = Calendar_Model_Attender::STATUS_ACCEPTED;
            Calendar_Controller_Event::getInstance()->attenderStatusUpdate($event, $scleverAttendee, $scleverAttendee->status_authkey);
            $event = Calendar_Controller_Event::getInstance()->get($event->getId());
            $email = $sclever->accountEmailAddress;
        }
        
        // get iMIP invitation for event
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        $vevent = $converter->fromTine20Model($event);
        $vevent->METHOD = $_method;
        $ics = $vevent->serialize();
        
        $iMIP = new Calendar_Model_iMIP(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'ics'            => $ics,
            'method'         => ($_testEmptyMethod) ? NULL : $_method,
            'originator'     => $email,
        ));
        
        if ($_addEventToiMIP) {
            $iMIP->events = new Tinebase_Record_RecordSet(Calendar_Model_Event::class, [$event]);
        }
        
        return $iMIP;
    }
    
    /**
     * testInternalInvitationRequestAutoProcess
     */
    public function testInternalInvitationRequestAutoProcess()
    {
        $iMIP = $this->_getiMIP('REQUEST');

        Tinebase_Core::setUser($this->_personas['sclever']);

        $this->_iMIPFrontend->autoProcess($iMIP);
        $this->_iMIPFrontend->prepareComponent($iMIP);
        
        $this->assertEquals(2, count($iMIP->events->getFirstRecord()->attendee), 'expected 2 attendee');
        $this->assertEquals('Sleep very long', $iMIP->events->getFirstRecord()->summary);
        $this->assertTrue(empty($iMIP->preconditions));
    }

    /**
    * testInternalInvitationRequestAutoProcessOwnStatusAlreadySet
    */
    public function testInternalInvitationRequestPreconditionOwnStatusAlreadySet()
    {
        $iMIP = $this->_getiMIP('REQUEST', TRUE);

        Tinebase_Core::setUser($this->_personas['sclever']);
        
        // set own status
        $ownAttender = Calendar_Model_Attender::getOwnAttender($iMIP->getEvents()->getFirstRecord()->attendee);
        $ownAttender->status = Calendar_Model_Attender::STATUS_TENTATIVE;
        Calendar_Controller_Event::getInstance()->attenderStatusUpdate($iMIP->getEvents()->getFirstRecord(), $ownAttender, $ownAttender->status_authkey);
        
        $this->_iMIPFrontend->prepareComponent($iMIP);
        $this->assertTrue(empty($iMIP->preconditions), "it's ok to reanswer without reschedule!");

        Tinebase_Core::setUser($this->_originalTestUser);

        // reschedule
        $event = Calendar_Controller_Event::getInstance()->get(current($iMIP->existing_events)->getId());
        $event->dtstart->addHour(2);
        $event->dtend->addHour(2);
        Calendar_Controller_Event::getInstance()->update($event, false);

        Tinebase_Core::setUser($this->_personas['sclever']);

        $iMIP->getExistingEvent($iMIP->getEvents()->getFirstRecord(), true);
        $iMIP->preconditionsChecked = [];
        $this->_iMIPFrontend->prepareComponent($iMIP);
        $key = $iMIP->events->getFirstRecord()->getRecurIdOrUid();
        
        $this->assertFalse(empty($iMIP->preconditions), 'do not accept this iMIP after reshedule');
        $this->assertTrue(isset($iMIP->preconditions[$key][Calendar_Model_iMIP::PRECONDITION_RECENT]));
    }
    
    /**
     * returns a simple event
     *
     * @return Calendar_Model_Event
     * @param bool $_now
     * @param bool $mute
     * @todo replace with TestCase::_getEvent
     */
    protected function _getEvent($now = FALSE, $mute = NULL)
    {
        return new Calendar_Model_Event(array(
            'summary'     => 'Sleep very long',
            'dtstart'     => '2012-03-25 01:00:00',
            'dtend'       => '2012-03-25 11:15:00',
            'description' => 'Early to bed and early to rise, makes a men healthy, wealthy and wise ... not.',
            'attendee'    => $this->_getAttendee(),
            'organizer'   => Tinebase_Core::getUser()->contact_id,
            'uid'         => Calendar_Model_Event::generateUID(),
        ));
    }
    
    /**
     * get test attendee
     *
     * @return Tinebase_Record_RecordSet
     */
    protected function _getAttendee()
    {
        $personas = Zend_Registry::get('personas');
        $sclever = $personas['sclever'];
        
        return new Tinebase_Record_RecordSet('Calendar_Model_Attender', array(
            array(
                'user_id'        => Tinebase_Core::getUser()->contact_id,
                'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
                'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
                'status'         => Calendar_Model_Attender::STATUS_ACCEPTED,
                'status_authkey' => Tinebase_Record_Abstract::generateUID(),
            ),
            array(
                'user_id'        => $sclever->contact_id,
                'user_type'      => Calendar_Model_Attender::USERTYPE_USER,
                'role'           => Calendar_Model_Attender::ROLE_REQUIRED,
                'status_authkey' => Tinebase_Record_Abstract::generateUID(),
            ),
        ));
    }

    /**
     * testExternalInvitationRequestProcess
     */
    public function testExternalInvitationRequestProcess()
    {
        // TODO should not depend on IMAP/SMTP config ...
        $this->_checkIMAPConfig();

        $ics = Calendar_Frontend_WebDAV_EventTest::getVCalendar(dirname(__FILE__) . '/files/invitation_request_external.ics' );
        $ics = preg_replace('#DTSTART;VALUE=DATE-TIME;TZID=Europe/Berlin:20111121T130000#', 'DTSTART;VALUE=DATE-TIME;TZID=Europe/Berlin:' . Tinebase_DateTime::now()->addHour(1)->format('Ymd\THis'), $ics);
        $ics = preg_replace('#DTEND;VALUE=DATE-TIME;TZID=Europe/Berlin:20111121T140000#', 'DTEND;VALUE=DATE-TIME;TZID=Europe/Berlin:' . Tinebase_DateTime::now()->addHour(2)->format('Ymd\THis'), $ics);
        
        $iMIP = new Calendar_Model_iMIP(array(
                'id'             => Tinebase_Record_Abstract::generateUID(),
                'ics'            => $ics,
                'method'         => 'REQUEST',
                'originator'     => 'l.Kneschke@caldav.org',
        ));
        
        Calendar_Controller_EventNotificationsTests::flushMailer();
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);
        
        $event = $iMIP->getExistingEvent($iMIP->getEvents()->getFirstRecord(), true);
        // assert external organizer
        $this->assertEquals('l.kneschke@caldav.org', $event->organizer_email, 'wrong organizer');
        $this->assertTrue(empty($event->organizer->account_id), 'organizer must not have an account');
        
        // assert attendee
        $ownAttendee = Calendar_Model_Attender::getOwnAttender($event->attendee);
        $this->assertTrue(!! $ownAttendee, 'own attendee missing');
        $this->assertEquals(5, count($event->attendee), 'all attendee must be keeped');
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttendee->status, 'must be ACCEPTED');

        // assert no status authkey for external attendee
        foreach($event->attendee as $attendee) {
            if (!$attendee->user_id?->account_id) {
                $this->assertFalse(!!$attendee->user_id?->status_authkey, 'authkey should be skipped');
            }
        }
        
        // assert REPLY message to organizer only
        $messages = Calendar_Controller_EventNotificationsTests::getMessages();
        $this->assertEquals(1, count($messages), 'exactly one mail should be send');
        $this->assertTrue(in_array('l.kneschke@caldav.org', $messages[0]->getRecipients()), 'organizer is not a receipient');
        $this->assertStringContainsString('accepted', $messages[0]->getSubject(), 'wrong subject');
        $this->assertStringContainsString('METHOD:REPLY', var_export($messages[0], TRUE), 'method missing');
        $this->assertStringContainsString('SEQUENCE:0', var_export($messages[0], TRUE), 'external sequence has not been keepted');
    }
    
    /**
     * external organizer container should not be visible
     */
    public function testExternalContactContainer()
    {
        $this->testExternalInvitationRequestProcess();
        $containerFrontend = new Tinebase_Frontend_Json_Container();
        $result = $containerFrontend->getContainer(Calendar_Model_Event::class, Tinebase_Model_Container::TYPE_SHARED, null, null);
        
        foreach ($result as $container) {
            if ($container['name'] === 'l.kneschke@caldav.org') {
                $this->fail('found external organizer container: ' . print_r($container, true));
            }
        }
    }
    
    /**
     * adds new imip message to Felamimail cache
     * 
     * @return Felamimail_Model_Message
     */
    protected function _addImipMessageToEmailCache()
    {
        $this->_checkIMAPConfig();
        
        // handle message with fmail (add to cache)
        $message = $this->_emailTestClass->messageTestHelper('calendar_request.eml', NULL, NULL, array('unittest@tine20.org', $this->_getEmailAddress()));
        return Felamimail_Controller_Message::getInstance()->getCompleteMessage($message);
    }
    
    /**
     * testDisabledExternalImip
     */
    public function testDisabledExternalImip()
    {
        Calendar_Config::getInstance()->set(Calendar_Config::DISABLE_EXTERNAL_IMIP, true);
        $complete = $this->_addImipMessageToEmailCache();
        $fmailJson = new Felamimail_Frontend_Json();
        $jsonMessage = $fmailJson->getMessage($complete->getId());
        Calendar_Config::getInstance()->set(Calendar_Config::DISABLE_EXTERNAL_IMIP, false);
        $this->assertFalse(empty($jsonMessage['preparedParts']));
        static::assertTrue(isset($jsonMessage['preparedParts'][0]['preparedData']['preconditions']) &&
            !empty($jsonMessage['preparedParts'][0]['preparedData']['preconditions']));
    }

    /**
     * check IMAP config and marks test as skipped if no IMAP backend is configured
     */
    protected function _checkIMAPConfig()
    {
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP);
        if (! $imapConfig || ! isset($imapConfig->useSystemAccount)
            || $imapConfig->useSystemAccount != TRUE
            || ! $this->_emailTestClass instanceof Felamimail_Controller_MessageTest
        ) {
            $this->markTestSkipped('IMAP backend not configured');
        }
    }

    /**
     * testExternalPublishProcess
     * - uses felamimail to cache external publish message
     * 
     * NOTE: meetup sends REQUEST w.o. attendee. We might think of autoconvert this to PUBLISH
     */
    public function testExternalPublishProcess()
    {
        $this->_checkIMAPConfig();
        
        // handle message with fmail (add to cache)
        $message = $this->_emailTestClass->messageTestHelper('meetup.eml');
        $complete = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message);

        self::assertGreaterThan(0, count($complete->preparedParts), 'no prepared parts found');
        $iMIP = $complete->preparedParts->getFirstRecord()->preparedData;
        
        $this->expectException('Calendar_Exception_iMIP');
        $this->expectExceptionMessageMatches('/iMIP preconditions failed: SUPPORTED/');
        $this->_iMIPFrontend->process($iMIP);
    }

    /**
     * testInternalInvitationRequestProcess
     */
    public function testInternalInvitationRequestProcess()
    {
        $iMIP = $this->_getiMIP('REQUEST');

        Tinebase_Core::setUser($this->_personas['sclever']);

        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_TENTATIVE);
        
        $event = $iMIP->getExistingEvent($iMIP->getEvents()->getFirstRecord(), true);

        $attender = Calendar_Model_Attender::getOwnAttender($event->attendee);
        $this->assertEquals(Calendar_Model_Attender::STATUS_TENTATIVE, $attender->status);
    }

    /**
     * testEmptyMethod
     */
    public function testEmptyMethod()
    {
        $iMIP = $this->_getiMIP('REQUEST', FALSE, TRUE);
        
        $this->assertEquals('REQUEST', $iMIP->method);
    }
    
    /**
     * testInternalInvitationReplyPreconditions
     * 
     * an internal reply does not need to be processed of course
     */
    public function testInternalInvitationReplyPreconditions()
    {
        $iMIP = $this->_getiMIP('REPLY');
        $this->_iMIPFrontend->prepareComponent($iMIP);
        $key = $iMIP->events->getFirstRecord()->getRecurIdOrUid();
        
        $this->assertFalse(empty($iMIP->preconditions), 'empty preconditions');
        $this->assertTrue(isset($iMIP->preconditions[$key][Calendar_Model_iMIP::PRECONDITION_TOPROCESS]), 'missing PRECONDITION_TOPROCESS');
    }
    
    /**
     * testInternalInvitationReplyAutoProcess
     * 
     * an internal reply does not need to be processed of course
     * @group nodockerci
     *        fails with:
     * Tinebase_Exception_NotFound: Tinebase_Model_Tree_Node record with id = b4ab92dd51c4c7ff7efdbd4cf86d1efe935c3309 not found!
     */
    public function testInternalInvitationReplyAutoProcess()
    {
        // flush mailer
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue(10000);
        }
        Tinebase_Smtp::getDefaultTransport()->flush();
        
        $iMIP = $this->_getiMIP('REPLY', TRUE);
        $event = $iMIP->getEvents()->getFirstRecord();
        
        try {
            $this->_iMIPFrontend->autoProcess($iMIP);
        } catch (Exception $e) {
            $this->assertStringContainsString('TOPROCESS', $e->getMessage());
            return;
        }
        
        $this->fail("autoProcess did not throw TOPROCESS Exception");
    }

    public function testGoogleExternalInviteAddAttenderAutoProcess()
    {
        // test external invite
        $iMIP = $this->_createiMIPFromFile('google_external_invite.ics');
        $iMIP->originator = $iMIP->getEvents()->getFirstRecord()->organizer_email;
        $iMIP->method = 'REQUEST';
        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var Calendar_Model_iMIP $processedIMIP */
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $createdEvent = Calendar_Controller_Event::getInstance()->get($iMIP->getEvents()->getFirstRecord()->getId());
        static::assertSame('test', $createdEvent->summary);
        $unitAttender = Calendar_Model_Attender::getOwnAttender($createdEvent->attendee);
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $unitAttender->status);


        // test external invite update
        $iMIP = $this->_createiMIPFromFile('google_external_invite_addAttender.ics');

        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        Calendar_Controller_MSEventFacade::unsetInstance();
        
        $iMIP->originator = $iMIP->getEvents()->getFirstRecord()->organizer_email;
        $iMIP->method = 'REQUEST';

        $this->_iMIPFrontend->autoProcess($iMIP);
        $notUpdatedEvent = Calendar_Controller_Event::getInstance()->get($createdEvent);
        static::assertSame($createdEvent->seq, $notUpdatedEvent->seq);
    }

    protected function _smtpConfigReadyForIMIPTest()
    {
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        if (!isset($smtpConfig['primarydomain'])) {
            return false;
        }
        return true;
    }

    public function testGoogleExternalInviteAddAttenderConcurrencyHandlingWebDAV()
    {
        // test external invite
        $iMIP = $this->_createiMIPFromFile('google_external_invite.ics');
        $iMIP->originator = $iMIP->getEvents()->getFirstRecord()->organizer_email;
        $iMIP->method = 'REQUEST';
        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var Calendar_Model_iMIP $processedIMIP */
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $createdEvent = Calendar_Controller_Event::getInstance()->get($iMIP->getEvents()->getFirstRecord()->getId());
        static::assertSame('test', $createdEvent->summary);
        $unitAttender = Calendar_Model_Attender::getOwnAttender($createdEvent->attendee);
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $unitAttender->status);

        $createdEvent->summary = 'shooohoho';
        Calendar_Controller_Event::getInstance()->update($createdEvent);


        $oldHTTPAgent = $_SERVER['HTTP_USER_AGENT'];
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';

        try {
            $vcalendar = Calendar_Frontend_WebDAV_EventTest::getVCalendar(dirname(__FILE__) .
                '/files/google_external_invite_addAttender.ics');

            Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
            Calendar_Controller_MSEventFacade::unsetInstance();

            $id = '3evgs2i0jdkofmibc9u5cah0a9@googlePcomffffffffffffffff';
            $event = Calendar_Frontend_WebDAV_Event::create(Tinebase_Container::getInstance()->getPersonalContainer(
                $this->_personas['sclever'], Calendar_Model_Event::class,
                $this->_personas['sclever'])->getFirstRecord(),
                "$id.ics", $vcalendar);
            $record = $event->getRecord();

            static::assertSame($createdEvent->uid, $record->uid, 'uid does not match');
            static::assertSame($createdEvent->getId(), $record->getId());

            Tinebase_Core::set(Tinebase_Core::USER, $this->_originalTestUser);

            $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter([
                ['field' => 'dtstart', 'operator' => 'equals', 'value' => $createdEvent->dtstart]
            ]));

            static::assertEquals(1, $events->count());
            $ownAttender = Calendar_Model_Attender::getOwnAttender($events->getFirstRecord()->attendee);
            static::assertNotNull($ownAttender, 'lost own attendee');
            static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);
        } finally {
            $_SERVER['HTTP_USER_AGENT'] = $oldHTTPAgent;
        }
    }

    public function testGoogleExternalInviteMultipleAttendeeConcurrencyHandlingWebDAV()
    {
        // test external invite
        $iMIP = $this->_createiMIPFromFile('google_external_invite_addAttender.ics');
        $iMIP->originator = $iMIP->getEvents()->getFirstRecord()->organizer_email;
        $iMIP->method = 'REQUEST';
        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var Calendar_Model_iMIP $processedIMIP */
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $createdEvent = Calendar_Controller_Event::getInstance()->get($iMIP->getEvents()->getFirstRecord()->getId());
        $unitAttender = Calendar_Model_Attender::getOwnAttender($createdEvent->attendee);
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $unitAttender->status);

        $personalCal = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(),
            Calendar_Model_Event::class, Tinebase_Core::getUser())->getFirstRecord();
        Tinebase_Container::getInstance()->addGrants($personalCal, Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            $this->_personas['sclever'], [Tinebase_Model_Grants::GRANT_EDIT]);

        $oldHTTPAgent = $_SERVER['HTTP_USER_AGENT'];
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';

        try {
            $vcalendar = Calendar_Frontend_WebDAV_EventTest::getVCalendar(dirname(__FILE__) .
                '/files/google_external_invite_addAttender.ics');
            $vcalendar = str_replace('PARTSTAT=NEEDS-ACTION;RSVP=
  TRUE;CN=sclever', 'PARTSTAT=ACCEPTED;RSVP=
  TRUE;CN=sclever', $vcalendar);

            Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
            Calendar_Controller_MSEventFacade::unsetInstance();

            $id = '3evgs2i0jdkofmibc9u5cah0a9@googlePcomffffffffffffffff';
            $event = Calendar_Frontend_WebDAV_Event::create(Tinebase_Container::getInstance()->getPersonalContainer(
                $this->_personas['sclever'], Calendar_Model_Event::class,
                $this->_personas['sclever'])->getFirstRecord(),
                "$id.ics", $vcalendar);
            $record = $event->getRecord();

            static::assertSame($createdEvent->uid, $record->uid, 'uid does not match');
            static::assertSame($createdEvent->getId(), $record->getId());
            $scleverAttender = Calendar_Model_Attender::getOwnAttender($record->attendee);
            static::assertNotNull($scleverAttender, 'lost own attendee');
            static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $scleverAttender->status);

            Tinebase_Core::set(Tinebase_Core::USER, $this->_originalTestUser);

            $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter([
                ['field' => 'dtstart', 'operator' => 'equals', 'value' => $createdEvent->dtstart]
            ]));

            static::assertEquals(1, $events->count());
            $ownAttender = Calendar_Model_Attender::getOwnAttender($events->getFirstRecord()->attendee);
            static::assertNotNull($ownAttender, 'lost own attendee');
            static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);
            $scleverAttender = $events->getFirstRecord()->attendee->find('user_id', $scleverAttender->user_id);
            static::assertNotNull($scleverAttender, 'lost sclever attendee');
            static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $scleverAttender->status);
        } finally {
            $_SERVER['HTTP_USER_AGENT'] = $oldHTTPAgent;
        }
    }

    public function testGoogleExternalInviteMultipleAttendeeConcurrencyHandling()
    {
        // test external invite
        $iMIP = $this->_createiMIPFromFile('google_external_invite_addAttender.ics');
        $iMIP->originator = $iMIP->getEvents()->getFirstRecord()->organizer_email;
        $iMIP->method = 'REQUEST';
        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var Calendar_Model_iMIP $processedIMIP */
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $createdEvent = Calendar_Controller_Event::getInstance()->get($iMIP->getEvents()->getFirstRecord()->getId());
        $unitAttender = Calendar_Model_Attender::getOwnAttender($createdEvent->attendee);
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $unitAttender->status);

        // test external invite 2nd user
        $iMIP = $this->_createiMIPFromFile('google_external_invite_addAttender.ics');

        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        Calendar_Controller_MSEventFacade::unsetInstance();

        $iMIP->originator = $iMIP->getEvents()->getFirstRecord()->organizer_email;
        $iMIP->method = 'REQUEST';
        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var Calendar_Model_iMIP $processedIMIP */
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $updatedEvent = Calendar_Controller_Event::getInstance()->get($createdEvent->getId());
        static::assertSame(3, $updatedEvent->attendee->count(), 'attendee count mismatch');
        $scleverAttender = Calendar_Model_Attender::getOwnAttender($updatedEvent->attendee);
        static::assertNotNull($scleverAttender, 'sclever attender not found');
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $scleverAttender->status);
        $unitAttender = $updatedEvent->attendee->find('user_id', $unitAttender->user_id);
        static::assertNotNull($unitAttender, 'unit attender not found');
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $unitAttender->status);

        Tinebase_Core::set(Tinebase_Core::USER, $this->_originalTestUser);
        $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter([
            ['field' => 'dtstart', 'operator' => 'equals', 'value' => $createdEvent->dtstart]
        ]));

        static::assertEquals(1, $events->count());
        $ownAttender = Calendar_Model_Attender::getOwnAttender($events->getFirstRecord()->attendee);
        static::assertNotNull($ownAttender, 'lost own attendee');
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);
    }

    public function testGoogleExternalInviteAddAttender()
    {

        // test external invite
        $iMIP = $this->_createiMIPFromFile('google_external_invite.ics');
        $iMIP->originator = $iMIP->getEvents()->getFirstRecord()->organizer_email;
        $iMIP->method = 'REQUEST';

        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var Calendar_Model_iMIP $processedIMIP */
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $createdEvent = Calendar_Controller_Event::getInstance()->get($iMIP->getEvents()->getFirstRecord()->getId());
        static::assertSame('test', $createdEvent->summary);
        $ownAttender = Calendar_Model_Attender::getOwnAttender($createdEvent->attendee);
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);


        // test external invite update
        $iMIP = $this->_createiMIPFromFile('google_external_invite_addAttender.ics');

        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        Calendar_Controller_MSEventFacade::unsetInstance();

        $iMIP->originator = $iMIP->getEvents()->getFirstRecord()->organizer_email;
        $iMIP->method = 'REQUEST';
        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var Calendar_Model_iMIP $processedIMIP */
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $updatedEvent = Calendar_Controller_Event::getInstance()->get($createdEvent->getId());
        static::assertSame('test update', $updatedEvent->summary);
        $ownAttender = Calendar_Model_Attender::getOwnAttender($updatedEvent->attendee);
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);


        Tinebase_Core::set(Tinebase_Core::USER, $this->_originalTestUser);
        $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter([
            ['field' => 'dtstart', 'operator' => 'equals', 'value' => $updatedEvent->dtstart]
        ]));

        static::assertEquals(1, $events->count());
        $ownAttender = Calendar_Model_Attender::getOwnAttender($events->getFirstRecord()->attendee);
        static::assertNotNull($ownAttender, 'lost own attendee');
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);
    }

    public function testInvitationSeriesExceptionForDifferentAttendees(): void
    {
        Tinebase_Core::setUser($this->_personas['rwright']);
        $iMIP = $this->_createiMIPFromFile('invitationICALseriesNoException.ics');
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $eventSeries = $iMIP->getExistingEvent($iMIP->getEvents()->getFirstRecord());
        $this->assertNull($eventSeries->organizer);
        $this->assertNotNull($eventSeries->organizer_email);
        $this->assertSame(Calendar_Model_Event::ORGANIZER_TYPE_EMAIL, $eventSeries->organizer_type);
        $this->assertSame(Calendar_Controller::getInstance()->getInvitationContainer(null, $eventSeries->organizer_email)->getId(),
            $eventSeries->container_id);
        $this->assertNotNull($rwrightAttendee = Calendar_Model_Attender::getOwnAttender($eventSeries->attendee));
        $this->assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $rwrightAttendee->status);

        Tinebase_Core::setUser($this->_personas['pwulf']);
        Calendar_Controller_MSEventFacade::unsetInstance();
        Calendar_Controller_Event::unsetInstance();

        $iMIP = $this->_createiMIPFromFile('invitationICALexception1.ics');
        $iMIP->ics = str_replace('CREATED:20240613T160230Z', 'ATTENDEE;CN=Paul Mehrer;CUTYPE=INDIVIDUAL;EMAIL=' . $this->_personas['rwright']->accountEmailAddress . ';PART
 STAT=NEEDS-ACTION:mailto:' . $this->_personas['rwright']->accountEmailAddress . '
CREATED:20240613T160230Z', $iMIP->ics);
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);
        $exception = $iMIP->getExistingEvent($iMIP->getEvents()->getFirstRecord());
        $this->assertNull($exception->organizer);
        $this->assertNotNull($exception->organizer_email);
        $this->assertSame(Calendar_Model_Event::ORGANIZER_TYPE_EMAIL, $exception->organizer_type);
        $this->assertSame($eventSeries->container_id, $exception->container_id);
        $this->assertNotNull($pwulfAttendee = Calendar_Model_Attender::getOwnAttender($exception->attendee));
        $this->assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $pwulfAttendee->status);

        $this->assertSame($eventSeries->getId(), $exception->base_event_id);
        $this->assertNotNull($rwrightExAttendee = $exception->attendee->find('user_id', $rwrightAttendee->user_id));
        $this->assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $rwrightExAttendee->status);
    }

    public function testInvitationSeriesExceptionRescheduledForDifferentAttendees(): void
    {
        Tinebase_Core::setUser($this->_personas['rwright']);
        $iMIP = $this->_createiMIPFromFile('invitationICALseriesNoException.ics');
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $eventSeries = $iMIP->getExistingEvent($iMIP->getEvents()->getFirstRecord());
        $this->assertNull($eventSeries->organizer);
        $this->assertNotNull($eventSeries->organizer_email);
        $this->assertSame(Calendar_Model_Event::ORGANIZER_TYPE_EMAIL, $eventSeries->organizer_type);
        $this->assertSame(Calendar_Controller::getInstance()->getInvitationContainer(null, $eventSeries->organizer_email)->getId(),
            $eventSeries->container_id);
        $this->assertNotNull($rwrightAttendee = Calendar_Model_Attender::getOwnAttender($eventSeries->attendee));
        $this->assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $rwrightAttendee->status);

        Tinebase_Core::setUser($this->_personas['pwulf']);
        Calendar_Controller_MSEventFacade::unsetInstance();
        Calendar_Controller_Event::unsetInstance();

        $iMIP = $this->_createiMIPFromFile('invitationICALexception1.ics');
        $iMIP->ics = str_replace([
                'CREATED:20240613T160230Z',
                'DTEND;TZID=Europe/Berlin:20240613T084500',
            ], [
                'ATTENDEE;CN=Paul Mehrer;CUTYPE=INDIVIDUAL;EMAIL=' . $this->_personas['rwright']->accountEmailAddress . ';PART
 STAT=NEEDS-ACTION:mailto:' . $this->_personas['rwright']->accountEmailAddress . '
CREATED:20240613T160230Z',
            'DTEND;TZID=Europe/Berlin:20240613T084400',
            ], $iMIP->ics);
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);
        $exception = $iMIP->getExistingEvent($iMIP->getEvents()->getFirstRecord());
        $this->assertNull($exception->organizer);
        $this->assertNotNull($exception->organizer_email);
        $this->assertSame(Calendar_Model_Event::ORGANIZER_TYPE_EMAIL, $exception->organizer_type);
        $this->assertSame($eventSeries->container_id, $exception->container_id);
        $this->assertNotNull($pwulfAttendee = Calendar_Model_Attender::getOwnAttender($exception->attendee));
        $this->assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $pwulfAttendee->status);

        $this->assertSame($eventSeries->getId(), $exception->base_event_id);
        $this->assertNotNull($rwrightExAttendee = $exception->attendee->find('user_id', $rwrightAttendee->user_id));
        $this->assertSame(Calendar_Model_Attender::STATUS_NEEDSACTION, $rwrightExAttendee->status);
    }

    public function testInvitationExceptionSeriesForDifferentAttendees(): void
    {
        Tinebase_Core::setUser($this->_personas['pwulf']);
        $iMIP = $this->_createiMIPFromFile('invitationICALexception1.ics');
        $iMIP->ics = str_replace('CREATED:20240613T160230Z', 'ATTENDEE;CN=Paul Mehrer;CUTYPE=INDIVIDUAL;EMAIL=' . $this->_personas['rwright']->accountEmailAddress . ';PART
 STAT=NEEDS-ACTION:mailto:' . $this->_personas['rwright']->accountEmailAddress . '
CREATED:20240613T160230Z', $iMIP->ics);
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);
        $exception = $iMIP->getExistingEvent($iMIP->getEvents()->getFirstRecord());
        $this->assertNull($exception->organizer);
        $this->assertNotNull($exception->organizer_email);
        $this->assertSame(Calendar_Model_Event::ORGANIZER_TYPE_EMAIL, $exception->organizer_type);
        $this->assertNotNull($pwulfAttendee = Calendar_Model_Attender::getOwnAttender($exception->attendee));
        $this->assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $pwulfAttendee->status);

        Tinebase_Core::setUser($this->_personas['rwright']);
        Calendar_Controller_MSEventFacade::unsetInstance();
        Calendar_Controller_Event::unsetInstance();

        $iMIP = $this->_createiMIPFromFile('invitationICALseriesNoException.ics');
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $eventSeries = $iMIP->getExistingEvent($iMIP->getEvents()->getFirstRecord());
        $exception = Calendar_Controller_MSEventFacade::getInstance()->get($exception->getId());
        $this->assertNull($eventSeries->organizer);
        $this->assertNotNull($eventSeries->organizer_email);
        $this->assertSame(Calendar_Model_Event::ORGANIZER_TYPE_EMAIL, $eventSeries->organizer_type);
        $this->assertSame(Calendar_Controller::getInstance()->getInvitationContainer(null, $eventSeries->organizer_email)->getId(),
            $eventSeries->container_id);
        $this->assertNotNull($rwrightAttendee = Calendar_Model_Attender::getOwnAttender($eventSeries->attendee));
        $this->assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $rwrightAttendee->status);
        $this->assertSame($eventSeries->container_id, $exception->container_id);
        $this->assertSame($eventSeries->getId(), $exception->base_event_id);
        $this->assertNull($exception->attendee->find('user_id', $rwrightAttendee->user_id));
    }

    public function testInvitationExternalReplyRecurInstance(): void
    {
        // force creation of external attendee
        $externalAttendee = new Calendar_Model_Attender(array(
            'user_type' => Calendar_Model_Attender::USERTYPE_EMAIL,
            'user_email' => 'mail@corneliusweiss.de',
            'status' => Calendar_Model_Attender::STATUS_NEEDSACTION,
        ));

        // create matching event
        $event = new Calendar_Model_Event(array(
            'summary' => 'TEST7',
            'dtstart' => '2011-11-30 14:00:00',
            'dtend' => '2011-11-30 15:00:00',
            'originator_tz' => 'Europe/Berlin',
            'description' => 'Early to bed and early to rise, makes a men healthy, wealthy and wise ...',
            'attendee' => $this->_getAttendee(),
            'organizer' => Tinebase_Core::getUser()->contact_id,
            'uid' => 'a8d10369e051094ae9322bd65e8afecac010bfc8',
            'rrule' => 'FREQ=DAILY;INTERVAL=1;COUNT=3',
        ));
        $event->attendee->addRecord($externalAttendee);
        $event = Calendar_Controller_Event::getInstance()->create($event);

        $iMIP = $this->_createiMIPFromFile('invitation_reply_external_accepted.ics');
        $iMIP->ics = str_replace([
            'BEGIN:VEVENT',
            'DTEND;TZID=Europe/Berlin:20111130T160000',
            'DTSTART;TZID=Europe/Berlin:20111130T150000',
        ], [
            "BEGIN:VEVENT\nRECURRENCE-ID;TZID=Europe/Berlin:20111201T150000",
            'DTEND;TZID=Europe/Berlin:20111201T160000',
            'DTSTART;TZID=Europe/Berlin:20111201T150000',
        ], $iMIP->ics);

        // TEST NORMAL REPLY
        try {
            //$this->_iMIPFrontend->autoProcess($iMIP);
            $this->_iMIPFrontend->process($iMIP);
        } catch (Exception $e) {
            $this->fail('TEST NORMAL REPLY autoProcess throws Exception: ' . $e);
        }

        $newEvent = $iMIP->getExistingEvent($iMIP->getEvents()->getFirstRecord());
        $this->assertSame($event->getId(), $newEvent->base_event_id);
    }

    public function testInvitationRecureExceptionThenSeries(): void
    {
        $iMIP = $this->_createiMIPFromFile('invitationICALexception1.ics');
        $iMIP->originator = $iMIP->getEvents()->getFirstRecord()->organizer_email;
        $iMIP->method = 'REQUEST';

        $this->_iMIPFrontend->prepareComponent($iMIP);
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $createdException1 = Calendar_Controller_Event::getInstance()->get($iMIP->getEvents()->getFirstRecord()->getId());
        $this->assertSame('TEST', $createdException1->summary);
        $this->assertSame('13', $createdException1->dtstart->format('d'));
        $ownAttender = Calendar_Model_Attender::getOwnAttender($createdException1->attendee);
        $this->assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);


        $iMIP = $this->_createiMIPFromFile('invitationICALexception2.ics');
        $iMIP->originator = $iMIP->getEvents()->getFirstRecord()->organizer_email;
        $iMIP->method = 'REQUEST';

        $this->_iMIPFrontend->prepareComponent($iMIP);
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $createdException2 = Calendar_Controller_Event::getInstance()->get($iMIP->getEvents()->getLastRecord()->getId());
        $this->assertSame('TEST', $createdException2->summary);
        $this->assertSame('14', $createdException2->dtstart->format('d'));
        $ownAttender = Calendar_Model_Attender::getOwnAttender($createdException2->attendee);
        $this->assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);


        $iMIP = $this->_createiMIPFromFile('invitationICALseries.ics');
        $iMIP->originator = $iMIP->getEvents()->getFirstRecord()->organizer_email;
        $iMIP->method = 'REQUEST';

        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var Calendar_Model_iMIP $processedIMIP */
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $createdSeries = Calendar_Controller_MSEventFacade::getInstance()->get($iMIP->getEvents()->getFirstRecord()->getId());
        $this->assertSame('TEST', $createdSeries->summary);
        $this->assertSame('12', $createdSeries->dtstart->format('d'));
        $this->assertFalse($createdSeries->isRecurException());
        $ownAttender = Calendar_Model_Attender::getOwnAttender($createdSeries->attendee);
        $this->assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);
        $this->assertNotFalse($createdSeries->exdate->getById($createdException1->getId()));
        $this->assertNotFalse($createdSeries->exdate->getById($createdException2->getId()));

        $updateException1 = Calendar_Controller_Event::getInstance()->get($createdException1->getId());
        $updateException2 = Calendar_Controller_Event::getInstance()->get($createdException2->getId());
        $this->assertSame($createdSeries->getId(), $updateException1->base_event_id);
        $this->assertSame($createdSeries->getId(), $updateException2->base_event_id);
        $this->assertSame($createdSeries->uid, $updateException1->uid);
        $this->assertSame($createdSeries->uid, $updateException2->uid);
    }

    /**
     * testInvitationExternalReply
     */
    public function testInvitationExternalReply()
    {
        $iMIP = $this->_createiMIPFromFile('invitation_reply_external_accepted.ics');

        // force creation of external attendee
        $externalAttendee = new Calendar_Model_Attender(array(
            'user_type'     => Calendar_Model_Attender::USERTYPE_EMAIL,
            'user_email'    => $iMIP->getEvents()->getFirstRecord()->attendee->getFirstRecord()->user_email,
            'status'        => Calendar_Model_Attender::STATUS_NEEDSACTION
        ));

        // create matching event
        $event = new Calendar_Model_Event(array(
            'summary'     => 'TEST7',
            'dtstart'     => '2011-11-30 14:00:00',
            'dtend'       => '2011-11-30 15:00:00',
            'description' => 'Early to bed and early to rise, makes a men healthy, wealthy and wise ...',
            'attendee'    => $this->_getAttendee(),
            'organizer'   => Tinebase_Core::getUser()->contact_id,
            'uid'         => 'a8d10369e051094ae9322bd65e8afecac010bfc8',
        ));
        $event->attendee->addRecord($externalAttendee);
        $event = Calendar_Controller_Event::getInstance()->create($event);
        
        // TEST NORMAL REPLY
        try {
            $this->_iMIPFrontend->autoProcess($iMIP);
        } catch (Exception $e) {
            $this->fail('TEST NORMAL REPLY autoProcess throws Exception: ' . $e);
        }
        unset($iMIP->existing_events);
        
        $updatedEvent = Calendar_Controller_Event::getInstance()->get($event->getId());
        $updatedExternalAttendee = Calendar_Model_Attender::getAttendee($updatedEvent->attendee, $externalAttendee);
        
        $this->assertEquals(3, count($updatedEvent->attendee));
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $updatedExternalAttendee->status, 'status not updated');
        $this->assertTrue(isset($updatedExternalAttendee->xprops()[Calendar_Model_Attender::XPROP_REPLY_DTSTAMP]) &&
            isset($updatedExternalAttendee->xprops()[Calendar_Model_Attender::XPROP_REPLY_SEQUENCE]),
            'xprops of attender not properly set: ' . print_r($updatedExternalAttendee->xprops(), true));
        $this->assertEquals($iMIP->getEvents()->getFirstRecord()->seq, $updatedExternalAttendee->xprops()[Calendar_Model_Attender::XPROP_REPLY_SEQUENCE]);
        $this->assertEquals($iMIP->getEvents()->getFirstRecord()->last_modified_time, $updatedExternalAttendee->xprops()[Calendar_Model_Attender::XPROP_REPLY_DTSTAMP]);
        
        // TEST NORMAL REPLY
        $updatedExternalAttendee->status = Calendar_Model_Attender::STATUS_NEEDSACTION;
        Calendar_Controller_Event::getInstance()->attenderStatusUpdate($updatedEvent, $updatedExternalAttendee, $updatedExternalAttendee->status_authkey);
        try {
            $iMIP->getEvents()->getFirstRecord()->seq = $iMIP->getEvents()->getFirstRecord()->seq + 1;
            $iMIP->preconditionsChecked = [];
            $this->_iMIPFrontend->autoProcess($iMIP);
        } catch (Exception $e) {
            $this->fail('TEST NORMAL REPLY autoProcess throws Exception: ' . $e);
        }
        unset($iMIP->existing_events);
        
        $updatedEvent = Calendar_Controller_Event::getInstance()->get($event->getId());
        $updatedExternalAttendee = Calendar_Model_Attender::getAttendee($updatedEvent->attendee, $externalAttendee);
        
        $this->assertEquals(3, count($updatedEvent->attendee));
        $this->assertEquals(Calendar_Model_Attender::STATUS_ACCEPTED, $updatedExternalAttendee->status, 'status not updated');
        $this->assertEquals($iMIP->getEvents()->getFirstRecord()->seq, $updatedExternalAttendee->xprops()[Calendar_Model_Attender::XPROP_REPLY_SEQUENCE]);
        $this->assertEquals($iMIP->getEvents()->getFirstRecord()->last_modified_time, $updatedExternalAttendee->xprops()[Calendar_Model_Attender::XPROP_REPLY_DTSTAMP]);
        
        // check if attendee are resolved
        $iMIP->getExistingEvent($iMIP->getEvents()->getFirstRecord(), true);
        $this->assertTrue(current($iMIP->existing_events)->attendee instanceof Tinebase_Record_RecordSet);
        $this->assertEquals(3, count(current($iMIP->existing_events)->attendee));
        
        // TEST NON RECENT REPLY (seq is the same as before)
        $iMIP->preconditionsChecked = [];
        try {
            $this->_iMIPFrontend->autoProcess($iMIP);
            $this->fail('autoProcess should throw Calendar_Exception_iMIP');
        } catch (Calendar_Exception_iMIP $cei) {
            $this->assertStringContainsString('iMIP preconditions failed: RECENT', $cei->getMessage());
        }
    }

    protected function _createiMIPFromFile($_filename): Calendar_Model_iMIP
    {
        $email = $this->_getEmailAddress();

        $ics = file_get_contents(dirname(__FILE__) . '/files/' . $_filename);
        $ics = preg_replace('/unittest@tine20\.org/', $email, $ics);
        $ics = preg_replace('/@tine20\.org/', '@' . TestServer::getPrimaryMailDomain(), $ics);

        $iMIP = new Calendar_Model_iMIP(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'ics'            => $ics,
            'method'         => 'REPLY',
            'originator'     => 'mail@Corneliusweiss.de',
        ));

        return $iMIP;
    }

    public function testExternalReplyFromGoogle()
    {
        $iMIP = $this->_createiMIPFromFile('google_confirm.ics');
        // force creation of external attendee
        $externalAttendee = new Calendar_Model_Attender(array(
            'user_type'     => Calendar_Model_Attender::USERTYPE_USER,
            'user_id'       => 'mail@cOrneliusweiss.de',
            'status'        => Calendar_Model_Attender::STATUS_NEEDSACTION
        ));

        // create matching event
        $event = new Calendar_Model_Event(array(
            'summary'     => 'testtermin google confirm',
            'dtstart'     => '2017-11-16 10:30:00',
            'dtend'       => '2017-11-16 11:30:00',
            'attendee'    => $this->_getAttendee(),
            'organizer'   => Tinebase_Core::getUser()->contact_id,
            'uid'         => '62050f080e53ca8e00353ff0a89c6c6aa4af3dec',
        ));
        $event->attendee->addRecord($externalAttendee);
        Calendar_Controller_Event::getInstance()->create($event);

        // TEST NORMAL REPLY
        try {
            $this->_iMIPFrontend->autoProcess($iMIP);
        } catch (Exception $e) {
            $this->fail('TEST NORMAL REPLY autoProcess throws Exception: ' . $e);
        }
    }

    /**
     * testExternalInvitationCancelProcessEvent
     *
     */
    public function testExternalInvitationCancelProcessEvent()
    {
        $iMIP = $this->testExternalInvitationRequestAutoProcess();
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);
        $existingEvent = $iMIP->getExistingEvent($iMIP->getEvents()->getFirstRecord(), true);
        $this->assertSame(Calendar_Model_Event::STATUS_CONFIRMED, $existingEvent->status);

        $ics = file_get_contents(dirname(__FILE__) . '/files/invitation_cancel.ics' );

        $iMIP = new Calendar_Model_iMIP(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'ics'            => $ics,
            'method'         => 'CANCEL',
            'originator'     => 'l.kneschke@calDav.org',
        ));

        // TEST CANCEL
        try {
            $this->_iMIPFrontend->process($iMIP);
        } catch (Exception $e) {
            $this->fail('TEST NORMAL CANCEL autoProcess throws Exception: ' . $e);
        }

        $existingEvent = $iMIP->getExistingEvent($iMIP->getEvents()->getFirstRecord(), true);
        $this->assertNotNull($existingEvent, 'event must not be deleted');

        $ownAttender = Calendar_Model_Attender::getOwnAttender($existingEvent->attendee);
        static::assertNotNull($ownAttender, 'own attender must not be null');
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);
        $this->assertSame(Calendar_Model_Event::STATUS_CANCELED, $existingEvent->status);
    }

    /**
     * testExternalInvitationCancelProcessAttendee
     *
     */
    public function testExternalInvitationCancelProcessAttendee()
    {
        $iMIP = $this->testExternalInvitationRequestAutoProcess();
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $ics = file_get_contents(dirname(__FILE__) . '/files/invitation_cancel.ics' );
        // set status to not cancelled, so that only attendees are removed from the event
        $ics = preg_replace('#STATUS:CANCELLED#', 'STATUS:CONFIRMED', $ics);

        $iMIP = new Calendar_Model_iMIP(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'ics'            => $ics,
            'method'         => 'CANCEL',
            'originator'     => 'l.kneschke@caldav.Org',
        ));

        // TEST CANCEL
        try {
            $this->_iMIPFrontend->process($iMIP);
        } catch (Exception $e) {
            $this->fail('TEST NORMAL CANCEL autoProcess throws Exception: ' . $e);
        }

        $updatedEvent = $iMIP->getExistingEvent($iMIP->getEvents()->getFirstRecord(), _refetch: true);
        $updatedEvent = Calendar_Controller_Event::getInstance()->get($updatedEvent->getId());
        $this->assertEquals(4, count($updatedEvent->attendee), 'attendee count must be 4');
    }

    /**
      * testInvitationCancel
      * 
      * @todo implement
      */
     public function testOrganizerSendBy()
     {
         $this->markTestIncomplete('implement me');
     }

    /**
     * testExternalInvitationRescheduleOutlook
     */
    public function testExternalInvitationRescheduleOutlook()
    {
        // TODO should not depend on IMAP/SMTP config ...
        $this->_checkIMAPConfig();

        // initial invitation
        $iMIP = $this->_testExternalImap('outlook_invitation.ics',
            3, 'Metaways Folgetermin ');
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        // reschedule/reply first user
        Calendar_Controller_EventNotificationsTests::flushMailer();
        $ics = Calendar_Frontend_WebDAV_EventTest::getVCalendar(dirname(__FILE__) . '/files/outlook_reschedule.ics');
        $ics = preg_replace('/20170816/', Tinebase_DateTime::now()->addDay(2)->format('Ymd'), $ics);
        $iMIP = new Calendar_Model_iMIP(array(
            'id' => Tinebase_Record_Abstract::generateUID(),
            'ics' => $ics,
            'method' => 'REQUEST',
            'originator' => 'l.kneschkE@caldav.org',
        ));
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_TENTATIVE);

        $updatedEvent = $iMIP->getExistingEvent($iMIP->getEvents()->getFirstRecord(), true);
        $this->assertEquals(Tinebase_DateTime::now()->addDay(2)->format('Y-m-d') . ' 11:00:00',
            $updatedEvent->dtstart->setTimezone($updatedEvent->originator_tz)->toString());
        
        $messages = Calendar_Controller_EventNotificationsTests::getMessages();
        $subject = quoted_printable_decode($messages[0]->getSubject());
        
        $this->assertEquals(1, count($messages), 'exactly one mail should be send');
        $this->assertTrue(in_array('l.kneschke@caldav.org', $messages[0]->getRecipients()), 'organizer is not a receipient');
        $this->assertStringContainsString('Tentative response', $subject, 'wrong subject');
        $this->assertStringContainsString('METHOD:REPLY', var_export($messages[0], TRUE), 'method missing');
        $this->assertStringContainsString('SEQUENCE:4', var_export($messages[0], TRUE), 'external sequence has not been keepted');


        // reply from second internal attendee
        Calendar_Controller_EventNotificationsTests::flushMailer();
        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);
        Calendar_Model_Attender::clearCache();
        $iMIP = new Calendar_Model_iMIP(array(
            'id' => Tinebase_Record_Abstract::generateUID(),
            'ics' => $ics,
            'method' => 'REQUEST',
            'originator' => 'l.kNeschke@caldav.org',
        ));
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_DECLINED);
        $messages = Calendar_Controller_EventNotificationsTests::getMessages();
        $this->assertEquals(1, count($messages), 'exactly one mail should be send');
        $this->assertStringContainsString('Susan Clever declined event', $messages[0]->getSubject(), 'wrong subject');
        $this->assertStringContainsString('SEQUENCE:4', var_export($messages[0], TRUE), 'external sequence has not been keepted');

        // try outdated imip
        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['pwulf']);
        try {
            $iMIP = $this->_testExternalImap('outlook_invitation.ics',
                3, 'Metaways Folgetermin ', false, false);
        } catch (Calendar_Exception_iMIP $preconditionException) {}
        $this->assertIsArray($iMIP->preconditions);
        $this->assertIsArray(current($iMIP->preconditions));
        $this->assertArrayHasKey('RECENT', current($iMIP->preconditions));
    }

    public function testGoogleExternalInviteLongUID()
    {
        if (! $this->_smtpConfigReadyForIMIPTest()) {
            $this->markTestSkipped('smtp config not ready for test');
        }

        // test external invite
        $iMIP = $this->_createiMIPFromFile('google_external_inviteLongUID.ics');
        $iMIP->originator = $iMIP->getEvents()->getFirstRecord()->organizer_email;
        $iMIP->method = 'REQUEST';
        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var Calendar_Model_iMIP $processedIMIP */
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $createdEvent = Calendar_Controller_Event::getInstance()->get($iMIP->getEvents()->getFirstRecord()->getId());
        static::assertSame('test', $createdEvent->summary);
        static::assertSame('3evgs2i0jdkofmibc9u5cah0a9@googlePcomffffffffffffffff', $createdEvent->uid);
        $ownAttender = Calendar_Model_Attender::getOwnAttender($createdEvent->attendee);
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);

        // test external invite update
        $iMIP = $this->_createiMIPFromFile('google_external_inviteLongUID.ics');

        Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);

        $iMIP->originator = $iMIP->getEvents()->getFirstRecord()->organizer_email;
        $iMIP->method = 'REQUEST';

        $existingEvent = $iMIP->getExistingEvent($iMIP->getEvents()->getFirstRecord(), true);
        static::assertNotNull($existingEvent, 'can\'t get existing event');
        static::assertSame($createdEvent->uid, $existingEvent->uid, 'uid does not match');
        static::assertSame($createdEvent->getId(), $existingEvent->getId());

        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var Calendar_Model_iMIP $processedIMIP */
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $existingEvent = $iMIP->getExistingEvent($iMIP->getEvents()->getFirstRecord(), true);
        static::assertSame($createdEvent->uid, $existingEvent->uid, 'uid does not match');
        static::assertSame($createdEvent->getId(), $existingEvent->getId());
        $ownAttender = Calendar_Model_Attender::getOwnAttender($existingEvent->attendee);
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);

        Tinebase_Core::set(Tinebase_Core::USER, $this->_originalTestUser);
        $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter([
            ['field' => 'dtstart', 'operator' => 'equals', 'value' => $createdEvent->dtstart]
        ]));

        static::assertEquals(1, $events->count());
        $ownAttender = Calendar_Model_Attender::getOwnAttender($events->getFirstRecord()->attendee);
        static::assertNotNull($ownAttender, 'lost own attendee');
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);
    }

    /**
     * @return void
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_NotDefined
     * @throws \Sabre\DAV\Exception\PreconditionFailed
     * @group nodockerci
     *        fails with:
     * Failed asserting that two strings are identical.
     * --- Expected
     * +++ Actual
     * @@ @@
     * -'a67943e2822b3efce064e4ae329409b0e9315c7a'
     * +'a39d587f3d28d79e78ac9e7387eb19c59470b1df'
     * /usr/share/tests/tine20/Calendar/Frontend/iMIPTest.php:1375
     * (static::assertSame($createdEvent->getId(), $record->getId());)
     *
     */
    public function testGoogleExternalInviteLongUIDWebDAV()
    {
        // test external invite
        $iMIP = $this->_createiMIPFromFile('google_external_inviteLongUID.ics');
        $iMIP->originator = $iMIP->getEvents()->getFirstRecord()->organizer_email;
        $iMIP->method = 'REQUEST';
        $this->_iMIPFrontend->prepareComponent($iMIP);
        /** @var Calendar_Model_iMIP $processedIMIP */
        $this->_iMIPFrontend->process($iMIP, Calendar_Model_Attender::STATUS_ACCEPTED);

        $createdEvent = Calendar_Controller_Event::getInstance()->get($iMIP->getEvents()->getFirstRecord()->getId());
        static::assertSame('test', $createdEvent->summary);
        $ownAttender = Calendar_Model_Attender::getOwnAttender($createdEvent->attendee);
        static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);


        $oldHTTPAgent = $_SERVER['HTTP_USER_AGENT'];
        $_SERVER['HTTP_USER_AGENT'] = 'CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)';

        try {
            $vcalendar = Calendar_Frontend_WebDAV_EventTest::getVCalendar(dirname(__FILE__) .
                '/files/google_external_inviteLongUID.ics');

            Tinebase_Core::set(Tinebase_Core::USER, $this->_personas['sclever']);

            Tinebase_Container::getInstance()->resetClassCache();
            $id = '3evgs2i0jdkofmibc9u5cah0a9@googlePcomffffffffffffffff';
            $event = Calendar_Frontend_WebDAV_Event::create(Tinebase_Container::getInstance()->getPersonalContainer(
                $this->_personas['sclever'], Calendar_Model_Event::class,
                $this->_personas['sclever'])->getFirstRecord(),
                "$id.ics", $vcalendar);
            $record = $event->getRecord();

            static::assertSame($createdEvent->uid, $record->uid, 'uid does not match');
            static::assertSame($createdEvent->getId(), $record->getId());

            Tinebase_Core::set(Tinebase_Core::USER, $this->_originalTestUser);

            $events = Calendar_Controller_Event::getInstance()->search(new Calendar_Model_EventFilter([
                ['field' => 'dtstart', 'operator' => 'equals', 'value' => $createdEvent->dtstart]
            ]));

            static::assertEquals(1, $events->count());
            $ownAttender = Calendar_Model_Attender::getOwnAttender($events->getFirstRecord()->attendee);
            static::assertNotNull($ownAttender, 'lost own attendee');
            static::assertSame(Calendar_Model_Attender::STATUS_ACCEPTED, $ownAttender->status);
        } finally {
            $_SERVER['HTTP_USER_AGENT'] = $oldHTTPAgent;
        }
    }
}

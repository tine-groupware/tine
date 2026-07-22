<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     EventManagers
 * @license     https://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Tonia Wulff <t.leuschel@metaways.de>
 *
 */

/**
 * Test class for EventManagers_Json
 */
class EventManager_ControllerTest extends TestCase
{
    /**
     * set up tests
     */
    protected function setUp(): void
    {
        if (!Tinebase_Application::getInstance()->isInstalled('EventManager')) {
            self::markTestSkipped('App is not installed');
        }
        parent::setUp();
    }

    /**
     * try to add an event
     *
     */
    public function testAddEvent()
    {
        $event = $this->_getEvent();
        $this->_createSharedEventContainer();
        EventManager_Controller_Event::getInstance()->create($event);
        self::assertEquals('phpunit event', $event['name'][0]['text']);
    }

    /**
     * try to add an option to an event
     */
    public function testAddOptionToEvent()
    {
        $event = $this->_getEvent();
        $this->_createSharedEventContainer();
        EventManager_Controller_Event::getInstance()->create($event);
        $option = $this->_getOption($event->getId());
        $created_option = EventManager_Controller_Option::getInstance()->create($option);
        self::assertEquals(
            $option->{EventManager_Model_Option::FLD_EVENT_ID},
            $created_option->{EventManager_Model_Option::FLD_EVENT_ID}
        );
    }

    /**
     * try to add a registration to an event
     */
    public function testAddRegistrationToEvent()
    {
        $event = $this->_getEvent();
        $this->_createSharedEventContainer();
        EventManager_Controller_Event::getInstance()->create($event);
        $registration = $this->_getRegistration($event->getId());
        $created_registration = EventManager_Controller_Registration::getInstance()->create($registration);
        self::assertEquals(
            $registration->{EventManager_Model_Registration::FLD_EVENT_ID},
            $created_registration->{EventManager_Model_Registration::FLD_EVENT_ID}
        );
        $event = EventManager_Controller_Event::getInstance()->get($event->getId());
        self::assertCount(1, $event->{EventManager_Model_Event::FLD_REGISTRATIONS});

        $jsonEvent = (new EventManager_Frontend_Json)->getEvent($event->getId());
        $this->assertArrayHasKey('adr_one', $jsonEvent[EventManager_Model_Event::FLD_REGISTRATIONS][0][EventManager_Model_Registration::FLD_PARTICIPANT] ?? [], print_r($jsonEvent, true));
    }

    /**
     * try to add a registration to an event updating the event
     */
    public function testAddRegistrationToEventWithUpdate()
    {
        $event = $this->_getEvent();
        $this->_createSharedEventContainer();
        EventManager_Controller_Event::getInstance()->create($event);
        $registration = $this->_getRegistration($event->getId());
        $event->{EventManager_Model_Event::FLD_REGISTRATIONS} =
            new Tinebase_Record_RecordSet(EventManager_Model_Registration::class, [$registration]);
        $event = EventManager_Controller_Event::getInstance()->update($event);
        $updatedEvent = EventManager_Controller_Event::getInstance()->get($event->getId());
        self::assertCount(1, $updatedEvent->{EventManager_Model_Event::FLD_REGISTRATIONS});
        $registration = $updatedEvent->{EventManager_Model_Event::FLD_REGISTRATIONS}->getFirstRecord();
        self::assertNotNull($registration->{EventManager_Model_Registration::FLD_REGISTRANT});
    }

    /**
     * try to add a registrant to a registration different from participant
     */
    public function testAddRegistrantToRegistration()
    {
        $event = $this->_getEvent();
        $this->_createSharedEventContainer();
        EventManager_Controller_Event::getInstance()->create($event);
        $registration = $this->_getRegistration($event->getId(), null, true);
        $event->{EventManager_Model_Event::FLD_REGISTRATIONS} =
            new Tinebase_Record_RecordSet(EventManager_Model_Registration::class, [$registration]);
        self::assertNotEquals(
            $registration->{EventManager_Model_Registration::FLD_PARTICIPANT}->n_family,
            $registration->{EventManager_Model_Registration::FLD_REGISTRANT}->n_family
        );
        $event = EventManager_Controller_Event::getInstance()->update($event);
        $registration = $event->{EventManager_Model_Event::FLD_REGISTRATIONS}[0];
        self::assertNotEquals(
            $registration->{EventManager_Model_Registration::FLD_PARTICIPANT}->n_family,
            $registration->{EventManager_Model_Registration::FLD_REGISTRANT}->n_family
        );
    }

    /**
     * delete an option from event
     */
    public function testDeleteOptionFromEvent()
    {
        $event = $this->_getEvent();
        $this->_createSharedEventContainer();
        EventManager_Controller_Event::getInstance()->create($event);
        $option = $this->_getOption($event->getId());
        $created_option = EventManager_Controller_Option::getInstance()->create($option);
        $registration = $this->_getRegistration($event->getId(), $created_option);
        $created_registration = EventManager_Controller_Registration::getInstance()->create($registration);
        self::assertEquals(
            $created_registration->{EventManager_Model_Registration::FLD_EVENT_ID},
            $registration->{EventManager_Model_Registration::FLD_EVENT_ID}
        );
        self::assertEquals(1, count($created_registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS}));

        $event = EventManager_Controller_Event::getInstance()->get($event->getId());
        $event->{EventManager_Model_Event::FLD_OPTIONS}->removeById($created_option->getId());
        EventManager_Controller_Event::getInstance()->update($event);
        try {
            EventManager_Controller_Option::getInstance()->get($created_option->getId());
            self::fail('option should be deleted');
        } catch (Tinebase_Exception_NotFound $tenf) {}
        // check if is also deleted in registrations
        $registration_without_booked_option = EventManager_Controller_Registration::getInstance()
            ->get($registration->getId());
        Tinebase_Record_Expander::expandRecord($registration_without_booked_option);
        self::assertEquals(
            null,
            $registration_without_booked_option->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS}->getFirstRecord()
        );
    }

    /**
     * delete a booked option from registration
     */
    public function testAddAndDeleteBookedOptionFromRegistration()
    {
        $event = $this->_getEvent();
        $this->_createSharedEventContainer();
        EventManager_Controller_Event::getInstance()->create($event);
        $option = $this->_getOption($event->getId());
        $created_option = EventManager_Controller_Option::getInstance()->create($option);
        $registration = $this->_getRegistration($event->getId(), $created_option);
        $created_registration = EventManager_Controller_Registration::getInstance()->create($registration);

        // check if available places have been reduced by 1 and booked places add 1
        $updated_option = EventManager_Controller_Option::getInstance()->get($created_option->getId());
        self::assertEquals(
            $created_option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
                ->{EventManager_Model_CheckboxOption::FLD_AVAILABLE_PLACES} - 1,
            $updated_option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
                ->{EventManager_Model_CheckboxOption::FLD_AVAILABLE_PLACES}
        );
        self::assertEquals(
            $created_option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
                ->{EventManager_Model_CheckboxOption::FLD_BOOKED_PLACES} + 1,
            $updated_option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
                ->{EventManager_Model_CheckboxOption::FLD_BOOKED_PLACES}
        );

        // delete booked_options from registration
        $created_registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS} = [];
        EventManager_Controller_Registration::getInstance()->update($created_registration);

        $option_after_delete = EventManager_Controller_Option::getInstance()->get($option->getId());
        // check if available places and booked places back to start
        self::assertEquals(
            $created_option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
            ->{EventManager_Model_CheckboxOption::FLD_AVAILABLE_PLACES},
            $option_after_delete->{EventManager_Model_Option::FLD_OPTION_CONFIG}
            ->{EventManager_Model_CheckboxOption::FLD_AVAILABLE_PLACES}
        );
        self::assertEquals(
            $created_option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
            ->{EventManager_Model_CheckboxOption::FLD_BOOKED_PLACES},
            $option_after_delete->{EventManager_Model_Option::FLD_OPTION_CONFIG}
            ->{EventManager_Model_CheckboxOption::FLD_BOOKED_PLACES}
        );
    }

    /**
     * update a booked option from registration
     */
    public function testUpdateBookedOptionFromRegistration()
    {
        $event = $this->_getEvent();
        $this->_createSharedEventContainer();
        EventManager_Controller_Event::getInstance()->create($event);
        $option = $this->_getOption($event->getId());
        $created_option = EventManager_Controller_Option::getInstance()->create($option);
        $registration = $this->_getRegistration($event->getId(), $created_option);
        $created_registration = EventManager_Controller_Registration::getInstance()->create($registration);

        $created_registration->booked_options[0]->selection_config->booked = false;
        EventManager_Controller_Registration::getInstance()->update($created_registration);

        // check if available places have been increased again by 1, since value changed from true -> false
        $updated_option = EventManager_Controller_Option::getInstance()->get($option->getId());
        self::assertEquals(
            $created_option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
                ->{EventManager_Model_CheckboxOption::FLD_AVAILABLE_PLACES},
            $updated_option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
                ->{EventManager_Model_CheckboxOption::FLD_AVAILABLE_PLACES}
        );
        self::assertEquals(
            $created_option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
                ->{EventManager_Model_CheckboxOption::FLD_BOOKED_PLACES},
            $updated_option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
                ->{EventManager_Model_CheckboxOption::FLD_BOOKED_PLACES}
        );
    }

    public function testFileOptionFileUpload()
    {
        $event = $this->_getEvent();
        $this->_createSharedEventContainer();
        EventManager_Controller_Event::getInstance()->create($event);
        $option = $this->_getFileOption($event->getId());
        $tempf_id = $option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
            ->{EventManager_Model_FileOption::FLD_NODE_ID};
        $created_option = EventManager_Controller_Option::getInstance()->create($option);
        $event = EventManager_Controller_Event::getInstance()->get($event->getId());

        //check that file node is returned
        $node_id = $event->{EventManager_Model_Event::FLD_OPTIONS}->getFirstRecord()
            ->{EventManager_Model_Option::FLD_OPTION_CONFIG}
            ->{EventManager_Model_FileOption::FLD_NODE_ID};
        self::assertNotEquals($tempf_id, $node_id);
    }

    public function testFileUploadToRegistration()
    {
        $event = $this->_getEvent();
        $this->_createSharedEventContainer();
        EventManager_Controller_Event::getInstance()->create($event);
        $option = $this->_getFileOption($event->getId());
        $tempf_id = $option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
            ->{EventManager_Model_FileOption::FLD_NODE_ID};
        $created_option = EventManager_Controller_Option::getInstance()->create($option);
        $registration = $this->_getRegistration($event->getId(), $created_option);
        $created_registration = EventManager_Controller_Registration::getInstance()->create($registration);
        $event = EventManager_Controller_Event::getInstance()->get($event->getId());

        //check that file node is returned
        $node_id = $event->{EventManager_Model_Event::FLD_REGISTRATIONS}->getFirstRecord()
            ->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS}->getFirstRecord()
            ->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}
            ->{EventManager_Model_Selections_File::FLD_NODE_ID};
        self::assertNotEquals($tempf_id, $node_id);
    }

    public function testFileUploadToRegistrationAnonymousUser()
    {
        $event = $this->_getEvent();
        $this->_createSharedEventContainer();
        EventManager_Controller_Event::getInstance()->create($event);
        $option = $this->_getFileOption($event->getId());
        $tempf_id = $option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
            ->{EventManager_Model_FileOption::FLD_NODE_ID};
        $created_option = EventManager_Controller_Option::getInstance()->create($option);
        $registration = $this->_getRegistration($event->getId(), $created_option);
        //anonymous user
        $user = Tinebase_User::createSystemUser(Tinebase_User::SYSTEM_USER_ANONYMOUS);
        Tinebase_Core::setUser($user);

        $created_registration = EventManager_Controller_Registration::getInstance()->create($registration);
        $event = EventManager_Controller_Event::getInstance()->get($event->getId());

        //check that file node is returned
        $node_id = $event->{EventManager_Model_Event::FLD_REGISTRATIONS}->getFirstRecord()
            ->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS}->getFirstRecord()
            ->{EventManager_Model_BookedOption::FLD_SELECTION_CONFIG}
            ->{EventManager_Model_Selections_File::FLD_NODE_ID};
        self::assertNotEquals($tempf_id, $node_id);
    }

    public function testMoreThanOneBookedOptionTypeToRegistration()
    {
        $event = $this->_getEvent();
        $this->_createSharedEventContainer();
        EventManager_Controller_Event::getInstance()->create($event);
        $option1 = $this->_getOption($event->getId());
        $created_option1 = EventManager_Controller_Option::getInstance()->create($option1);
        $option2 = $this->_getFileOption($event->getId());
        $created_option2 = EventManager_Controller_Option::getInstance()->create($option2);
        $booked_options = [$created_option1, $created_option2];
        $registration = $this->_getRegistration($event->getId(), $booked_options);
        $created_registration = EventManager_Controller_Registration::getInstance()->create($registration);
        self::assertEquals(
            count($created_registration->{EventManager_Model_Registration::FLD_BOOKED_OPTIONS}),
            count($booked_options)
        );
    }


    public function testAddAndDeleteRegistration()
    {
        $this->_testNeedsTransaction(); //registerOnCommitCallback

        $event = $this->_getEvent();
        $this->_createSharedEventContainer();
        $createdEvent = EventManager_Controller_Event::getInstance()->create($event);
        $booked_places = $createdEvent->{EventManager_Model_Event::FLD_BOOKED_PLACES};
        $available_places = $createdEvent->{EventManager_Model_Event::FLD_AVAILABLE_PLACES};
        $registration = $this->_getRegistration($event->getId());
        $created_registration = EventManager_Controller_Registration::getInstance()->create($registration);
        $createdEvent->{EventManager_Model_Event::FLD_REGISTRATIONS}->addRecord($created_registration);
        $updated_event = EventManager_Controller_Event::getInstance()->update($createdEvent);
        self::assertEquals(1, count($updated_event->{EventManager_Model_Event::FLD_REGISTRATIONS}));
        self::assertEquals($booked_places + 1, $updated_event->{EventManager_Model_Event::FLD_BOOKED_PLACES});
        self::assertEquals($available_places - 1, $updated_event->{EventManager_Model_Event::FLD_AVAILABLE_PLACES});

        $updated_event->{EventManager_Model_Event::FLD_REGISTRATIONS}->removeById($registration->getId());
        EventManager_Controller_Event::getInstance()->update($updated_event);
        $updated_event = EventManager_Controller_Event::getInstance()->get($updated_event->getId());
        self::assertEquals(0, count($updated_event->{EventManager_Model_Event::FLD_REGISTRATIONS}));
        self::assertEquals($booked_places, $updated_event->{EventManager_Model_Event::FLD_BOOKED_PLACES});
        self::assertEquals($available_places, $updated_event->{EventManager_Model_Event::FLD_AVAILABLE_PLACES});

        // Make sure that event is deleted because we don't use the unittest transaction
        EventManager_Controller_Event::getInstance()->delete($updated_event);
    }

    /**
     * try to update the address of a participant from a registration
     */
    public function testUpdateParticipantFromRegistration()
    {
        $event = $this->_getEvent();
        $this->_createSharedEventContainer();
        EventManager_Controller_Event::getInstance()->create($event);
        $registration = $this->_getRegistration($event->getId(), null, true);
        $event->{EventManager_Model_Event::FLD_REGISTRATIONS} =
            new Tinebase_Record_RecordSet(EventManager_Model_Registration::class, [$registration]);
        $event = EventManager_Controller_Event::getInstance()->update($event);
        $testAddress = $event->{EventManager_Model_Event::FLD_REGISTRATIONS}[0]->participant->adr_one_locality;

        $event->{EventManager_Model_Event::FLD_REGISTRATIONS}[0]->participant->adr_one_locality = 'New Test Street';
        $event = EventManager_Controller_Event::getInstance()->update($event);
        $newTestAddress = $event->{EventManager_Model_Event::FLD_REGISTRATIONS}[0]->participant->adr_one_locality;

        self::assertNotEquals($testAddress, $newTestAddress);
        $this->assertSame('New Test Street', $newTestAddress);
    }

    /**
     * creating an event (without appointments) should create a single,
     * tagged calendar event mirroring name/start/end
     */
    public function testCreateEventCreatesCalendarEvent()
    {
        $event = $this->_getEvent();
        $this->_createSharedEventContainer();
        $createdEvent = EventManager_Controller_Event::getInstance()->create($event);

        $relation = $this->_getCalendarEventRelation($createdEvent);
        self::assertNotNull($relation, 'no calendar event relation found');

        $calendarEvent = Calendar_Controller_Event::getInstance()->get($relation->related_id);
        $eventName = EventManager_Controller_Event::getInstance()->getEventName($createdEvent);
        self::assertEquals($eventName, $calendarEvent->summary);

        // check the calendar event is tagged as automatically created
        $isTagged = false;
        foreach ($calendarEvent->tags as $tag) {
            if ($tag->name === 'automatic EventManager') {
                $isTagged = true;
            }
        }
        self::assertTrue($isTagged, 'calendar event should be tagged as automatic EventManager');
    }

    /**
     * creating an event with appointments should create one calendar event
     * per appointment (and no general calendar event)
     */
    public function testCreateEventWithAppointmentsCreatesCalendarEventPerAppointment()
    {
        $event = $this->_getEvent();
        $appointment1 = $this->_getAppointment($event->getId(), '2026-06-01', 1, '09:00:00', '10:00:00');
        $appointment2 = $this->_getAppointment($event->getId(), '2026-06-02', 2, '11:00:00', '12:00:00');
        $event->{EventManager_Model_Event::FLD_APPOINTMENTS} = new Tinebase_Record_RecordSet(
            EventManager_Model_Appointment::class,
            [$appointment1, $appointment2]
        );
        $this->_createSharedEventContainer();
        $createdEvent = EventManager_Controller_Event::getInstance()->create($event);

        foreach ($createdEvent->{EventManager_Model_Event::FLD_APPOINTMENTS} as $appointment) {
            $relation = $this->_getCalendarEventRelation($createdEvent, $appointment->getId());
            self::assertNotNull($relation, 'no calendar event relation found for appointment');
            $calendarEvent = Calendar_Controller_Event::getInstance()->get($relation->related_id);
            $eventName = EventManager_Controller_Event::getInstance()->getEventName($createdEvent);
            $translate = Tinebase_Translation::getTranslation(EventManager_Config::APP_NAME);
            self::assertEquals(
                $eventName . ' ' .
                $translate->_('Session') . ' ' .
                $appointment->{EventManager_Model_Appointment::FLD_SESSION_NUMBER},
                $calendarEvent->summary
            );
        }

        $generalRelation = $this->_getCalendarEventRelation($createdEvent);
        self::assertNull($generalRelation, 'no general calendar event should exist when appointments are used');
    }

    /**
     * changing the event name should update the linked calendar event's summary
     */
    public function testUpdateEventNameUpdatesCalendarEventSummary()
    {
        $event = $this->_getEvent();
        $this->_createSharedEventContainer();
        $createdEvent = EventManager_Controller_Event::getInstance()->create($event);
        $createdEvent->{EventManager_Model_Event::FLD_NAME}[0]['text'] = 'updated phpunit event';
        $updated_event = EventManager_Controller_Event::getInstance()->update($createdEvent);

        $relation = $this->_getCalendarEventRelation($updated_event);
        self::assertNotNull($relation);
        $calendarEvent = Calendar_Controller_Event::getInstance()->get($relation->related_id);
        self::assertEquals('updated phpunit event', $calendarEvent->summary);
    }

    /**
     * changing the event start/end should update the linked calendar event's dtstart/dtend
     */
    public function testUpdateEventStartEndUpdatesCalendarEvent()
    {
        $event = $this->_getEvent();
        $this->_createSharedEventContainer();
        $createdEvent = EventManager_Controller_Event::getInstance()->create($event);
        $newStart = new Tinebase_DateTime('2026-06-10 08:00:00');
        $newEnd = new Tinebase_DateTime('2026-06-12 18:00:00');
        $createdEvent->{EventManager_Model_Event::FLD_START} = $newStart;
        $createdEvent->{EventManager_Model_Event::FLD_END} = $newEnd;
        $updated_event = EventManager_Controller_Event::getInstance()->update($createdEvent);

        $relation = $this->_getCalendarEventRelation($updated_event);
        self::assertNotNull($relation);
    }

    /**
     * adding appointments to an event that only had a general calendar event
     * should remove the general one and create per-appointment calendar events
     */
    public function testAddAppointmentReplacesGeneralCalendarEvent()
    {
        $event = $this->_getEvent();
        $this->_createSharedEventContainer();
        $createdEvent = EventManager_Controller_Event::getInstance()->create($event);

        // general calendar event exists before adding appointments
        $generalRelationBefore = $this->_getCalendarEventRelation($createdEvent);
        self::assertNotNull($generalRelationBefore);

        $appointment = $this->_getAppointment($createdEvent->getId(), '2026-06-15', 1, '09:00:00', '10:00:00');
        $createdEvent->{EventManager_Model_Event::FLD_APPOINTMENTS} = new Tinebase_Record_RecordSet(
            EventManager_Model_Appointment::class,
            [$appointment]
        );
        $updated_event = EventManager_Controller_Event::getInstance()->update($createdEvent);

        // general calendar event should be gone
        try {
            Calendar_Controller_Event::getInstance()->get($generalRelationBefore->related_id);
            self::fail('general calendar event should have been deleted');
        } catch (Tinebase_Exception_NotFound $tenf) {}

        // a new calendar event for the appointment should exist
        $newAppointment = $updated_event->{EventManager_Model_Event::FLD_APPOINTMENTS}->getFirstRecord();
        $appointmentRelation = $this->_getCalendarEventRelation($updated_event, $newAppointment->getId());
        self::assertNotNull($appointmentRelation);
    }

    /**
     * modifying an appointment's start time should update its linked calendar event
     */
    public function testModifyAppointmentUpdatesCalendarEvent()
    {
        $event = $this->_getEvent();
        $appointment = $this->_getAppointment($event->getId(), '2026-06-20', 1, '09:00:00', '10:00:00');
        $event->{EventManager_Model_Event::FLD_APPOINTMENTS} = new Tinebase_Record_RecordSet(
            EventManager_Model_Appointment::class,
            [$appointment]
        );
        $this->_createSharedEventContainer();
        $createdEvent = EventManager_Controller_Event::getInstance()->create($event);
        $createdAppointment = $createdEvent->{EventManager_Model_Event::FLD_APPOINTMENTS}->getFirstRecord();

        $createdAppointment->{EventManager_Model_Appointment::FLD_START_TIME} = '14:00:00';
        $updated_event = EventManager_Controller_Event::getInstance()->update($createdEvent);

        $relation = $this->_getCalendarEventRelation($updated_event, $createdAppointment->getId());
        self::assertNotNull($relation);
        $calendarEvent = Calendar_Controller_Event::getInstance()->get($relation->related_id);
        self::assertEquals('14:00:00', $calendarEvent->dtstart->format('H:i:s'));
    }

    /**
     * removing an appointment from an event should delete its linked calendar event
     */
    public function testRemoveAppointmentDeletesCalendarEvent()
    {
        $event = $this->_getEvent();
        $appointment = $this->_getAppointment($event->getId(), '2026-06-25', 1, '09:00:00', '10:00:00');
        $event->{EventManager_Model_Event::FLD_APPOINTMENTS} = new Tinebase_Record_RecordSet(
            EventManager_Model_Appointment::class,
            [$appointment]
        );
        $this->_createSharedEventContainer();
        $createdEvent = EventManager_Controller_Event::getInstance()->create($event);
        $createdAppointment = $createdEvent->{EventManager_Model_Event::FLD_APPOINTMENTS}->getFirstRecord();
        $relation = $this->_getCalendarEventRelation($createdEvent, $createdAppointment->getId());
        self::assertNotNull($relation);

        $createdEvent->{EventManager_Model_Event::FLD_APPOINTMENTS}->removeRecord($createdAppointment);
        EventManager_Controller_Event::getInstance()->update($createdEvent);

        try {
            Calendar_Controller_Event::getInstance()->get($relation->related_id);
            self::fail('calendar event for removed appointment should have been deleted');
        } catch (Tinebase_Exception_NotFound $tenf) {}
    }

    /**
     * deleting an event should delete its linked calendar event(s) as well
     */
    public function testDeleteEventDeletesCalendarEvent()
    {
        $event = $this->_getEvent();
        $this->_createSharedEventContainer();
        $createdEvent = EventManager_Controller_Event::getInstance()->create($event);
        $relation = $this->_getCalendarEventRelation($createdEvent);
        self::assertNotNull($relation);

        EventManager_Controller_Event::getInstance()->delete($createdEvent);

        try {
            Calendar_Controller_Event::getInstance()->get($relation->related_id);
            self::fail('calendar event should have been deleted along with the event');
        } catch (Tinebase_Exception_NotFound $tenf) {}
    }

    /************ protected helper funcs *************/

    /**
     * get event
     *
     * @param $name
     * @return EventManager_Model_Event
     */
    protected function _getEvent(): EventManager_Model_Event
    {
        $adb_controller = Addressbook_Controller_Contact::getInstance();

        $contact = $adb_controller->create(new Addressbook_Model_Contact([
            'n_family' => 'test contact',
            'adr_one_street' => 'test Str. 1',
            'adr_one_postalcode' => '1234',
            'adr_one_locality' => 'Test City'
        ]));

        $event_type = EventManager_Config::getInstance()->get(EventManager_Config::EVENT_TYPE)->records->getById('1');
        $event_status = EventManager_Config::getInstance()->get(EventManager_Config::EVENT_STATUS)->records->getById('1');

        return new EventManager_Model_Event([
            'name'                          => [[
                GDPR_Model_DataIntendedPurposeLocalization::FLD_LANGUAGE => 'de',
                GDPR_Model_DataIntendedPurposeLocalization::FLD_TEXT => 'phpunit event'
            ]],
            'start'                         => new Tinebase_DateTime("2025-05-28 17:00:00"),
            'end'                           => new Tinebase_DateTime("2025-05-31 20:30:00"),
            'location'                      => $contact,
            'type'                          => $event_type,
            'status'                        => $event_status,
            'fee'                           => 0,
            'total_places'                  => 50,
            'booked_places'                 => 0,
            'available_places'              => 50,
            'double_opt_in'                 => false,
            'options'                       => [],
            'registrations'                 => [],
            'appointments'                  => [],
            'description'                   => [[
                GDPR_Model_DataIntendedPurposeLocalization::FLD_LANGUAGE => 'de',
                GDPR_Model_DataIntendedPurposeLocalization::FLD_TEXT => 'description test phpunit event'
            ]],
            'is_live'                       => true,
            'registraion_possible_until'    => new Tinebase_DateTime("2025-05-27"),
        ], true);
    }

    /**
     * get option
     *
     * @return EventManager_Model_Option
     */
    protected function _getOption($event_id): EventManager_Model_Option
    {
        $option_config_checkbox = new EventManager_Model_CheckboxOption([
            'price' => 0,
            'total_places' => 10,
            'booked_places' => 0,
            'available_places' => 10,
            'description' => 'description checkbox phpunit',
        ]);
        $display = EventManager_Config::getInstance()->get(EventManager_Config::DISPLAY_TYPE)->records->getById('1');
        $option_required = EventManager_Config::getInstance()->get(EventManager_Config::OPTION_REQUIRED_TYPE)
            ->records->getById('2');
        $level = EventManager_Config::getInstance()->get(EventManager_Config::OPTION_LEVEL)->records->getById('1');
        $rule_type = EventManager_Config::getInstance()->get(EventManager_Config::RULE_TYPE)->records->getById('1');

        return new EventManager_Model_Option([
            'event_id'                  => $event_id,
            'name_option'               => 'phpunit checkbox option',
            'option_config'             => $option_config_checkbox,
            'option_config_class'       => EventManager_Model_CheckboxOption::class,
            'display'                   => $display,
            'option_required'           => $option_required,
            'group'                     => 'test phpunit group',
            'level'                     => $level,
            'sorting'                   => 1,
            'option_rule'               => [],
            'rule_type'                 => $rule_type,
        ], true);
    }

    /**
     * get option
     *
     * @return EventManager_Model_Option
     */
    protected function _getFileOption($event_id): EventManager_Model_Option
    {
        $tempfile = $this->_getTempFile();
        $option_config_fileoption = new EventManager_Model_FileOption([
            'node_id' => $tempfile->getId(),
            'file_name' => $tempfile->name,
            'file_type' => $tempfile->type,
            'file_size' => $tempfile->size,
        ]);
        $display = EventManager_Config::getInstance()->get(EventManager_Config::DISPLAY_TYPE);
        $display = $display->records->getById('1');
        $option_required = EventManager_Config::getInstance()->get(EventManager_Config::OPTION_REQUIRED_TYPE);
        $option_required = $option_required->records->getById('2');
        $level = EventManager_Config::getInstance()->get(EventManager_Config::OPTION_LEVEL);
        $level = $level->records->getById('1');
        $rule_type = EventManager_Config::getInstance()->get(EventManager_Config::RULE_TYPE);
        $rule_type = $rule_type->records->getById('1');

        return new EventManager_Model_Option([
            'event_id'                  => $event_id,
            'name_option'               => 'phpunit file option',
            'option_config'             => $option_config_fileoption,
            'option_config_class'       => EventManager_Model_FileOption::class,
            'display'                   => $display,
            'option_required'           => $option_required,
            'group'                     => 'test phpunit group',
            'level'                     => $level,
            'sorting'                   => 1,
            'option_rule'               => [],
            'rule_type'                 => $rule_type,
        ],true);
    }

    /**
     * get appointment
     *
     * @return EventManager_Model_Appointment
     */
    protected function _getAppointment($event_id, $session_date, $session_number, $start_time = null, $end_time = null): EventManager_Model_Appointment
    {
        return new EventManager_Model_Appointment([
            EventManager_Model_Appointment::FLD_EVENT_ID       => $event_id,
            EventManager_Model_Appointment::FLD_SESSION_DATE   => new Tinebase_DateTime($session_date),
            EventManager_Model_Appointment::FLD_SESSION_NUMBER => $session_number,
            EventManager_Model_Appointment::FLD_START_TIME     => $start_time,
            EventManager_Model_Appointment::FLD_END_TIME       => $end_time,
        ], true);
    }

    /**
     * get registration
     *
     * @return EventManager_Model_Registration
     */
    protected function _getRegistration($event_id, $options = null, $has_other_registrant = false): EventManager_Model_Registration
    {
        $adb_controller = Addressbook_Controller_Contact::getInstance();
        $participant = $adb_controller->create(new Addressbook_Model_Contact([
            'n_family' => 'participant test',
            'adr_one_street' => 'test Str. 1',
            'adr_one_postalcode' => '1234',
            'adr_one_locality' => 'Test City',
        ]));
        if ($has_other_registrant) {
            $registrant = $adb_controller->create(new Addressbook_Model_Contact([
                'n_family' => 'registrant test',
                'adr_one_street' => 'test Str. 2',
                'adr_one_postalcode' => '5678',
                'adr_one_locality' => 'Test City',
            ]));
        } else {
            $registrant = $participant;
        }
        $default_values = EventManager_Controller_Registration::getInstance()->getDefaultRegistrationKeyFields();

        if (is_array($options)) {
            $booked_option = [];
            foreach ($options as $option) {
                $booked_option[] = $this->_getBookedOption($event_id, $option);
            }
            return new EventManager_Model_Registration([
                'event_id'               => $event_id,
                'participant'            => $participant,
                'registrant'             => $registrant,
                'function'               => $default_values['function'],
                'source'                 => $default_values['source'],
                'status'                 => $default_values['status'],
                'booked_options'         => $booked_option,
                'description'            => 'description test phpunit registration',
            ], true);
        } elseif ($options) {
            $booked_option = $this->_getBookedOption($event_id, $options);
            return new EventManager_Model_Registration([
                'event_id'               => $event_id,
                'participant'            => $participant,
                'registrant'             => $registrant,
                'function'               => $default_values['function'],
                'source'                 => $default_values['source'],
                'status'                 => $default_values['status'],
                'booked_options'         => [$booked_option],
                'description'            => 'description test phpunit registration',
            ], true);
        } else {
            return new EventManager_Model_Registration([
                'event_id'               => $event_id,
                'participant'            => $participant,
                'registrant'             => $registrant,
                'function'               => $default_values['function'],
                'source'                 => $default_values['source'],
                'status'                 => $default_values['status'],
                'booked_options'         => [],
                'description'            => 'description test phpunit registration',
            ], true);
        }
    }

    protected function _getBookedOption($event_id, $option, $value = true): EventManager_Model_BookedOption
    {
        if ($option->option_config_class === EventManager_Model_FileOption::class) {
            $selection_config = new EventManager_Model_Selections_File([
                'node_id'   => $option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
                    ->{EventManager_Model_FileOption::FLD_NODE_ID},
                'file_name' => $option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
                    ->{EventManager_Model_FileOption::FLD_FILE_NAME},
                'file_type' => $option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
                    ->{EventManager_Model_FileOption::FLD_FILE_TYPE},
                'file_size' => $option->{EventManager_Model_Option::FLD_OPTION_CONFIG}
                    ->{EventManager_Model_FileOption::FLD_FILE_SIZE},
            ], true);
            return new EventManager_Model_BookedOption([
                'event_id' => $event_id,
                'option' => $option->getId(),
                'selection_config' => $selection_config,
                'selection_config_class' => EventManager_Model_Selections_File::class,
            ]);
        } else {
            $selection_config = new EventManager_Model_Selections_Checkbox([
                'booked' => $value,
            ], true);
            return new EventManager_Model_BookedOption([
                'event_id' => $event_id,
                'option' => $option->getId(),
                'selection_config' => $selection_config,
                'selection_config_class' => EventManager_Model_Selections_Checkbox::class,
            ]);
        }
    }

    /**
     * fetch the calendar event relation for an EventManager event.
     * when $appointmentId is null, returns the "general" (no remark) calendar event relation
     * otherwise returns the relation whose remark matches the appointment id.
     *
     * @return Tinebase_Model_Relation|null
     */
    protected function _getCalendarEventRelation($event, $appointmentId = null)
    {
        $fetchedEvent = EventManager_Controller_Event::getInstance()->get($event->getId());
        foreach ($fetchedEvent->relations as $relation) {
            if ($relation->related_model !== Calendar_Model_Event::class) {
                continue;
            }
            if ($appointmentId === null && empty($relation->remark)) {
                return $relation;
            }
            if ($appointmentId !== null && $relation->remark === $appointmentId) {
                return $relation;
            }
        }
        return null;
    }

    protected function _createSharedEventContainer()
    {
        try {
            $calendarName = EventManager_Config::getInstance()->get(EventManager_Config::EVENT_SHARED_CALENDAR_NAME);
            Tinebase_Container::getInstance()->getContainerByName(
                Calendar_Model_Event::class,
                $calendarName,
                Tinebase_Model_Container::TYPE_SHARED,
            );
        } catch (Tinebase_Exception_NotFound $e) {
            $container = new Tinebase_Model_Container([
                'name'              => $calendarName,
                'type'              => Tinebase_Model_Container::TYPE_SHARED,
                'owner_id'          => Tinebase_Core::getUser(),
                'backend'           => 'Sql',
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName(Calendar_Config::APP_NAME)->getId(),
                'model'             => Calendar_Model_Event::class
            ]);
            Tinebase_Container::getInstance()->addContainer($container);
        }
    }
}

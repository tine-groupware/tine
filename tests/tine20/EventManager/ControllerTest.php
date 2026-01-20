<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     EventManagers
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
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
        EventManager_Controller_Event::getInstance()->create($event);
        self::assertEquals('phpunit event', $event['name']);
    }

    /**
     * try to add an option to an event
     */
    public function testAddOptionToEvent()
    {
        $event = $this->_getEvent();
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
        EventManager_Controller_Event::getInstance()->create($event);
        $registration = $this->_getRegistration($event->getId());
        $created_registration = EventManager_Controller_Registration::getInstance()->create($registration);
        self::assertEquals(
            $registration->{EventManager_Model_Registration::FLD_EVENT_ID},
            $created_registration->{EventManager_Model_Registration::FLD_EVENT_ID}
        );
        $event = EventManager_Controller_Event::getInstance()->get($event->getId());
        self::assertCount(1, $event->{EventManager_Model_Event::FLD_REGISTRATIONS});
    }

    /**
     * try to add a registration to an event updating the event
     */
    public function testAddRegistrationToEventWithUpdate()
    {
        $event = $this->_getEvent();
        EventManager_Controller_Event::getInstance()->create($event);
        $registration = $this->_getRegistration($event->getId());
        $event->{EventManager_Model_Event::FLD_REGISTRATIONS} =
            new Tinebase_Record_RecordSet(EventManager_Model_Registration::class, [$registration]);
        $event = EventManager_Controller_Event::getInstance()->update($event);
        $updatedEvent = EventManager_Controller_Event::getInstance()->get($event->getId());
        self::assertCount(1, $updatedEvent->{EventManager_Model_Event::FLD_REGISTRATIONS});
        $registration = $updatedEvent->{EventManager_Model_Event::FLD_REGISTRATIONS}->getFirstRecord();
        self::assertNotNull($registration->{EventManager_Model_Registration::FLD_REGISTRATOR});
    }

    /**
     * try to add a registrator to a registration different from participant
     */
    public function testAddRegistratorToRegistration()
    {
        $event = $this->_getEvent();
        EventManager_Controller_Event::getInstance()->create($event);
        $registration = $this->_getRegistration($event->getId(), null, true);
        $event->{EventManager_Model_Event::FLD_REGISTRATIONS} =
            new Tinebase_Record_RecordSet(EventManager_Model_Registration::class, [$registration]);
        self::assertNotEquals(
            $registration->{EventManager_Model_Registration::FLD_PARTICIPANT}->n_family,
            $registration->{EventManager_Model_Registration::FLD_REGISTRATOR}->n_family
        );
        $event = EventManager_Controller_Event::getInstance()->update($event);
        $registration = $event->{EventManager_Model_Event::FLD_REGISTRATIONS}[0];
        self::assertNotEquals(
            $registration->{EventManager_Model_Registration::FLD_PARTICIPANT}->n_family,
            $registration->{EventManager_Model_Registration::FLD_REGISTRATOR}->n_family
        );
    }

    /**
     * delete an option from event
     */
    public function testDeleteOptionFromEvent()
    {
        $event = $this->_getEvent();
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
        $created_event = EventManager_Controller_Event::getInstance()->create($event);
        $booked_places = $created_event->{EventManager_Model_Event::FLD_BOOKED_PLACES};
        $available_places = $created_event->{EventManager_Model_Event::FLD_AVAILABLE_PLACES};
        $registration = $this->_getRegistration($event->getId());
        $created_registration = EventManager_Controller_Registration::getInstance()->create($registration);
        $created_event->{EventManager_Model_Event::FLD_REGISTRATIONS}->addRecord($created_registration);
        $updated_event = EventManager_Controller_Event::getInstance()->update($created_event);
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
            'name'                          => 'phpunit event',
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
            'description'                   => 'description test phpunit event',
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
        $option_required = EventManager_Config::getInstance()->get(EventManager_Config::DISPLAY_TYPE)
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
            'group_sorting'             => 1,
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
        $option_required = EventManager_Config::getInstance()->get(EventManager_Config::DISPLAY_TYPE);
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
            'group_sorting'             => 1,
            'level'                     => $level,
            'sorting'                   => 1,
            'option_rule'               => [],
            'rule_type'                 => $rule_type,
        ],true);
    }

    /**
     * get registration
     *
     * @return EventManager_Model_Registration
     */
    protected function _getRegistration($event_id, $options = null, $has_other_registrator = false): EventManager_Model_Registration
    {
        $container_id = EventManager_Setup_Initialize::getContactEventContainer()->getId();
        $adb_controller = Addressbook_Controller_Contact::getInstance();
        $participant = $adb_controller->create(new Addressbook_Model_Contact([
            'n_family' => 'participant test',
            'adr_one_street' => 'test Str. 1',
            'adr_one_postalcode' => '1234',
            'adr_one_locality' => 'Test City',
            'container_id' => $container_id,
        ]));
        if ($has_other_registrator) {
            $registrator = $adb_controller->create(new Addressbook_Model_Contact([
                'n_family' => 'registrator test',
                'adr_one_street' => 'test Str. 2',
                'adr_one_postalcode' => '5678',
                'adr_one_locality' => 'Test City',
                'container_id' => $container_id,
            ]));
        } else {
            $registrator = $participant;
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
                'registrator'            => $registrator,
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
                'registrator'            => $registrator,
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
                'registrator'            => $registrator,
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
}

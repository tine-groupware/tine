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
        $createdOption = EventManager_Controller_Option::getInstance()->create($option);
        self::assertEquals($option->event_id, $createdOption->event_id);
    }

    /**
     * try to add a registration to an event
     */
    public function testAddRegistrationToEvent()
    {
        $event = $this->_getEvent();
        EventManager_Controller_Event::getInstance()->create($event);
        $registration = $this->_getRegistration($event->getId());
        $createdRegistration = EventManager_Controller_Registration::getInstance()->create($registration);
        self::assertEquals($registration->event_id, $createdRegistration->event_id);
    }

    /**
     * delete an option from event
     */
    public function testDeleteOptionFromEvent()
    {
        $event = $this->_getEvent();
        EventManager_Controller_Event::getInstance()->create($event);
        $option = $this->_getOption($event->getId());
        $createdOption = EventManager_Controller_Option::getInstance()->create($option);
        $registration = $this->_getRegistration($event->getId(), $createdOption);
        $createdRegistration = EventManager_Controller_Registration::getInstance()->create($registration);
        self::assertEquals($createdRegistration->event_id, $registration->event_id);
        self::assertEquals(1, count($createdRegistration->booked_options));

        $event = EventManager_Controller_Event::getInstance()->get($event->getId());
        $event->{EventManager_Model_Event::FLD_OPTIONS}->removeById($createdOption->getId());
        EventManager_Controller_Event::getInstance()->update($event);
        try {
            EventManager_Controller_Option::getInstance()->get($createdOption->getId());
            self::fail('option should be deleted');
        } catch (Tinebase_Exception_NotFound $tenf) {}
        // check if is also deleted in registrations
        $registrationWithoutBookedOption = EventManager_Controller_Registration::getInstance()->get($registration->getId());
        Tinebase_Record_Expander::expandRecord($registrationWithoutBookedOption);
        self::assertEquals(null, $registrationWithoutBookedOption->booked_options->getFirstRecord());
    }

    /**
     * delete a booked option from registration
     */
    public function testAddAndDeleteBookedOptionFromRegistration()
    {
        $event = $this->_getEvent();
        EventManager_Controller_Event::getInstance()->create($event);
        $option = $this->_getOption($event->getId());
        $createdOption = EventManager_Controller_Option::getInstance()->create($option);
        $registration = $this->_getRegistration($event->getId(), $createdOption);
        $createdRegistration = EventManager_Controller_Registration::getInstance()->create($registration);

        // check if available places have been reduced by 1 and booked places add 1
        $updatedOption = EventManager_Controller_Option::getInstance()->get($createdOption->getId());
        self::assertEquals( $createdOption->option_config->available_places - 1, $updatedOption->option_config->available_places);
        self::assertEquals( $createdOption->option_config->booked_places + 1, $updatedOption->option_config->booked_places);

        // delete booked_options from registration
        $createdRegistration->booked_options = [];
        EventManager_Controller_Registration::getInstance()->update($createdRegistration);

        $optionAfterDelete = EventManager_Controller_Option::getInstance()->get($option->getId());
        // check if available places and booked places back to start
        self::assertEquals($createdOption->option_config->available_places, $optionAfterDelete->option_config->available_places);
        self::assertEquals($createdOption->option_config->booked_places, $optionAfterDelete->option_config->booked_places);
    }

    /**
     * update a booked option from registration
     */
    public function testUpdateBookedOptionFromRegistration()
    {
        $event = $this->_getEvent();
        EventManager_Controller_Event::getInstance()->create($event);
        $option = $this->_getOption($event->getId());
        $createdOption = EventManager_Controller_Option::getInstance()->create($option);
        $registration = $this->_getRegistration($event->getId(), $createdOption);
        $createdRegistration = EventManager_Controller_Registration::getInstance()->create($registration);

        $createdRegistration->booked_options[0]->selection_config->booked = false;
        EventManager_Controller_Registration::getInstance()->update($createdRegistration);

        // check if available places have been increased again by 1, since value changed from true -> false
        $updatedOption = EventManager_Controller_Option::getInstance()->get($option->getId());
        self::assertEquals($createdOption->option_config->available_places, $updatedOption->option_config->available_places);
        self::assertEquals($createdOption->option_config->booked_places, $updatedOption->option_config->booked_places);
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
        $adbController = Addressbook_Controller_Contact::getInstance();

        $contact = $adbController->create(new Addressbook_Model_Contact([
            'n_family' => 'test contact',
            'adr_one_street' => 'test Str. 1',
            'adr_one_postalcode' => '1234',
            'adr_one_locality' => 'Test City'
        ]));

        return new EventManager_Model_Event([
            'name'                          => 'phpunit event',
            'start'                         => new Tinebase_DateTime("2025-05-28"),
            'end'                           => new Tinebase_DateTime("2025-05-31"),
            'location'                      => $contact,
            'type'                          => EventManager_Config::getInstance()->get(EventManager_Config::EVENT_TYPE, 'Main Event'),
            'status'                        => EventManager_Config::getInstance()->get(EventManager_Config::EVENT_STATUS, 'Open'),
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

        return new EventManager_Model_Option([
            'event_id'                  => $event_id,
            'name_option'               => 'phpunit option',
            'option_config'             => $option_config_checkbox,
            'option_config_class'       => EventManager_Model_CheckboxOption::class,
            'display'                   => EventManager_Config::getInstance()->get(EventManager_Config::DISPLAY_TYPE, 'Always'),
            'option_required'           => EventManager_Config::getInstance()->get(EventManager_Config::OPTION_REQUIRED_TYPE, 'No'),
            'group'                     => 'test phpunit group',
            'group_sorting'             => 1,
            'level'                     => EventManager_Config::getInstance()->get(EventManager_Config::OPTION_LEVEL, 'Level 1'),
            'sorting'                   => 1,
            'option_rule'               => [],
            'rule_type'                 => EventManager_Config::getInstance()->get(EventManager_Config::RULE_TYPE, 'One or more conditions are fulfilled'),
        ],true);
    }

    /**
     * get registration
     *
     * @return EventManager_Model_Registration
     */
    protected function _getRegistration($event_id, $option = null): EventManager_Model_Registration
    {
        if ($option) {
            $booked_option = $this->_getBookedOption($event_id, $option);
            return new EventManager_Model_Registration([
                'event_id'               => $event_id,
                'name'                   => 'phpunit registration',
                'function'               => EventManager_Config::getInstance()->get(EventManager_Config::REGISTRATION_FUNCTION, 'Attendee'),
                'source'                 => EventManager_Config::getInstance()->get(EventManager_Config::REGISTRATION_SOURCE, 'Online'),
                'status'                 => EventManager_Config::getInstance()->get(EventManager_Config::REGISTRATION_STATUS, 'Waiting list'),
                'booked_options'         => [$booked_option],
                'description'            => 'description test phpunit registration',
            ], true);
        } else {
            return new EventManager_Model_Registration([
                'event_id'               => $event_id,
                'name'                   => 'phpunit registration',
                'function'               => EventManager_Config::getInstance()->get(EventManager_Config::REGISTRATION_FUNCTION, 'Attendee'),
                'source'                 => EventManager_Config::getInstance()->get(EventManager_Config::REGISTRATION_SOURCE, 'Online'),
                'status'                 => EventManager_Config::getInstance()->get(EventManager_Config::REGISTRATION_STATUS, 'Waiting list'),
                'booked_options'         => [],
                'description'            => 'description test phpunit registration',
            ], true);
        }
    }

    protected function _getBookedOption($event_id, $option, $value = true): EventManager_Model_BookedOption
    {
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

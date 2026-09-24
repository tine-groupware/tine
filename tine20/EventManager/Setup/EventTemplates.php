<?php

declare(strict_types=1);

/**
 * tine Groupware
 *
 * @package     EventManager
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Tonia Wulff <t.wulff@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 *
 */

/**
 * class for EventManager Templates initialization
 *
 * @package     Setup
 */
class EventManager_Setup_EventTemplates extends EventManager_Setup_DemoData
{
    /**
     * holds the instance of the singleton
     *
     * @var EventManager_Setup_EventTemplates
     */
    private static $_instance = null;

    /**
     * the application name to work on
     *
     * @var string
     */
    protected $_appName = EventManager_Config::APP_NAME;

    /**
     * required apps
     *
     * @var array
     */
    protected static array $_requiredApplications = ['Admin','Addressbook'];

    /**
     * models to work on
     * @var array
     */
    protected $_models = [
        EventManager_Model_Event::MODEL_NAME_PART,
        EventManager_Model_Option::MODEL_NAME_PART,
        EventManager_Model_Registration::MODEL_NAME_PART,
        EventManager_Model_Appointment::MODEL_NAME_PART,
        EventManager_Model_Selection::MODEL_NAME_PART,
    ];

    /**
     * the constructor
     *
     */
    public function __construct()
    {
    }

    /**
     * this is required for other applications needing demo data of this application
     * if this returns true, this demodata has been run already
     *
     * @return boolean
     */
    public static function hasBeenRun()
    {
        try {
            $containerName = EventManager_Config::getInstance()
                ->get(EventManager_Config::EVENT_TEMPLATES_CONTAINER_NAME);
            $container = Tinebase_Container::getInstance()->getContainerByName(
                EventManager_Model_Event::class,
                $containerName,
                Tinebase_Model_Container::TYPE_SHARED
            );

            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(EventManager_Model_Event::class, [
                ['field' => 'container_id', 'operator' => 'equals', 'value' => $container->getId()],
            ]);

            return EventManager_Controller_Event::getInstance()->search($filter)->count() > 0;
        } catch (Tinebase_Exception_NotFound) {
            return false;
        }
    }

    /**
     * the singleton pattern
     *
     * @return EventManager_Setup_EventTemplates
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * unsets the instance to save memory, be aware that hasBeenRun still needs to work after unsetting!
     *
     */
    public function unsetInstance()
    {
        if (self::$_instance !== null) {
            self::$_instance = null;
        }
    }

    /**
     * @see Tinebase_Setup_DemoData_Abstract
     */
    protected function _onCreate()
    {
        $this->createTemplates();
    }

    public function createTemplates()
    {
        if (self::hasBeenRun()) {
            return;
        }

        $eventContainerName = EventManager_Config::getInstance()
            ->get(EventManager_Config::EVENT_TEMPLATES_CONTAINER_NAME);
        $container_id = EventManager_Setup_Initialize::_getOrCreateSharedEventContainer($eventContainerName)->getId();

        EventManager_Config::getInstance()
            ->set(EventManager_Config::JWT_SECRET, 'jwtSecretCreatedFromEventManagerTemplates');

        $event_type = EventManager_Config::getInstance()->get(EventManager_Config::EVENT_TYPE)->records->getById('1');
        $event_status = EventManager_Config::getInstance()->get(EventManager_Config::EVENT_STATUS)
            ->records->getById('2');
        $option_not_required = EventManager_Config::getInstance()->get(EventManager_Config::OPTION_REQUIRED_TYPE)
            ->records->getById('2');
        $option_required_if = EventManager_Config::getInstance()->get(EventManager_Config::OPTION_REQUIRED_TYPE)
            ->records->getById('3');

        $option_display_if = EventManager_Config::getInstance()->get(EventManager_Config::DISPLAY_TYPE)
            ->records->getById('2');

        //contact_fields
        $defaultContactFields = [
            'n_given'               => true,
            'n_middle'              => true,
            'n_family'              => true,
            'bday'                  => true,
            'email'                 => true,
            'tel_cell'              => true,
            'tel_work'              => true,
            'adr_one_street'        => true,
            'adr_one_street2'       => true,
            'adr_one_postalcode'    => true,
            'adr_one_locality'      => true,
            'adr_one_region'        => true,
            'adr_one_countryname'   => true,
        ];

        $templates = [];

        // template 1
        $template1 = EventManager_Controller_Event::getInstance()->create(new EventManager_Model_Event([
            EventManager_Model_Event::FLD_CONTAINER_ID                  => $container_id,
            EventManager_Model_Event::FLD_NAME                          => [[
                EventManager_Model_EventLocalization::FLD_LANGUAGE => 'de',
                EventManager_Model_EventLocalization::FLD_TEXT => 'Erstkommunion'
            ]],
            EventManager_Model_Event::FLD_START                         => '',
            EventManager_Model_Event::FLD_END                           => '',
            EventManager_Model_Event::FLD_REGISTRATION_POSSIBLE_UNTIL   => '',
            EventManager_Model_Event::FLD_LOCATION_RECORD               => '',
            EventManager_Model_Event::FLD_TYPE                          => $event_type,
            EventManager_Model_Event::FLD_STATUS                        => $event_status,
            EventManager_Model_Event::FLD_FEE                           => '',
            EventManager_Model_Event::FLD_TOTAL_PLACES                  => '',
            EventManager_Model_Event::FLD_BOOKED_PLACES                 => '',
            EventManager_Model_Event::FLD_AVAILABLE_PLACES              => '',
            EventManager_Model_Event::FLD_PARTICIPANT_CONTACT_FIELDS    => $defaultContactFields,
            EventManager_Model_Event::FLD_REGISTRANT_CONTACT_FIELDS     => $defaultContactFields,
            EventManager_Model_Event::FLD_IS_TEMPLATE                   => true,
            EventManager_Model_Event::FLD_OPTIONS                       => [
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Hl. Familie',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Hier mit melde ich mein Kind zur Erstkommunionsvorbereitung in folgender Gemeinde an:',
                    EventManager_Model_Option::FLD_SORTING => 1,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'St. Hedwig',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Hier mit melde ich mein Kind zur Erstkommunionsvorbereitung in folgender Gemeinde an:',
                    EventManager_Model_Option::FLD_SORTING => 2,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'St. Annen',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Hier mit melde ich mein Kind zur Erstkommunionsvorbereitung in folgender Gemeinde an:',
                    EventManager_Model_Option::FLD_SORTING => 3,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Mein Kind wurde innerhalb der Pfarrei XXX getauft.',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Taufe',
                    EventManager_Model_Option::FLD_SORTING => 10,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Taufdatum',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextInputDemoData(false),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextInputOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Taufe',
                    EventManager_Model_Option::FLD_SORTING => 11,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Taufort',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextInputDemoData(false),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextInputOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Taufe',
                    EventManager_Model_Option::FLD_SORTING => 12,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Taufkirche',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextInputDemoData(false),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextInputOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Taufe',
                    EventManager_Model_Option::FLD_SORTING => 13,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Name der Mutter',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextInputDemoData(false),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextInputOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Taufe',
                    EventManager_Model_Option::FLD_SORTING => 14,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Name des vaters',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextInputDemoData(false),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextInputOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Taufe',
                    EventManager_Model_Option::FLD_SORTING => 15,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Kopie der Taufurkunde (wenn vorhanden)',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigFileDemoData(false),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_FileOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Taufe',
                    EventManager_Model_Option::FLD_SORTING => 16,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Ich bin damit einverstanden, dass mein Kind:',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextOption::class,
                    EventManager_Model_Option::FLD_GROUP => '',
                    EventManager_Model_Option::FLD_SORTING => 20,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'mit Vornamen auf dem Liederzettel des Erstkommunionsgottesdienstes genannt wird',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => '',
                    EventManager_Model_Option::FLD_SORTING => 21,
                    EventManager_Model_Option::FLD_OPTION_REQUIRED => $option_not_required,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'mit Namen und Foto auf Aushängen in der Kirche zu sehen ist',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => '',
                    EventManager_Model_Option::FLD_SORTING => 22,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Datenschutzerklärung',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigFileDemoData(true),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_FileOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Datenschutzinformation',
                    EventManager_Model_Option::FLD_SORTING => 30,
                ],
            ],
            EventManager_Model_Event::FLD_REGISTRATIONS                 => [],
            EventManager_Model_Event::FLD_APPOINTMENTS                  => [],
            EventManager_Model_Event::FLD_DESCRIPTION                   => [[
                EventManager_Model_EventLocalization::FLD_LANGUAGE => 'de',
                EventManager_Model_EventLocalization::FLD_TEXT => 'BESCHREIBUNG HIER'
            ]],
        ]));
        $templates[] = $template1;

        // template 2
        $template2 = EventManager_Controller_Event::getInstance()->create(new EventManager_Model_Event([
            EventManager_Model_Event::FLD_CONTAINER_ID                  => $container_id,
            EventManager_Model_Event::FLD_NAME                          => [[
                EventManager_Model_EventLocalization::FLD_LANGUAGE => 'de',
                EventManager_Model_EventLocalization::FLD_TEXT => 'Kinderbibeltag'
            ]],
            EventManager_Model_Event::FLD_START                         => '',
            EventManager_Model_Event::FLD_END                           => '',
            EventManager_Model_Event::FLD_REGISTRATION_POSSIBLE_UNTIL   => '',
            EventManager_Model_Event::FLD_LOCATION_RECORD               => '',
            EventManager_Model_Event::FLD_TYPE                          => $event_type,
            EventManager_Model_Event::FLD_STATUS                        => $event_status,
            EventManager_Model_Event::FLD_FEE                           => '',
            EventManager_Model_Event::FLD_TOTAL_PLACES                  => '',
            EventManager_Model_Event::FLD_BOOKED_PLACES                 => '',
            EventManager_Model_Event::FLD_AVAILABLE_PLACES              => '',
            EventManager_Model_Event::FLD_PARTICIPANT_CONTACT_FIELDS    => $defaultContactFields,
            EventManager_Model_Event::FLD_REGISTRANT_CONTACT_FIELDS     => $defaultContactFields,
            EventManager_Model_Event::FLD_IS_TEMPLATE                   => true,
            EventManager_Model_Event::FLD_OPTIONS                       => [
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Vegetarisch',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Verpflegung',
                    EventManager_Model_Option::FLD_SORTING => 1,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Mit Fleisch',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Verpflegung',
                    EventManager_Model_Option::FLD_SORTING => 2,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Allergien/Unverträglichkeiten',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextInputDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextInputOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Verpflegung',
                    EventManager_Model_Option::FLD_SORTING => 3,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Heilige Familie',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Aus welcher Gemeinde kommen Sie?',
                    EventManager_Model_Option::FLD_SORTING => 10,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'St. Hedwig',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Aus welcher Gemeinde kommen Sie?',
                    EventManager_Model_Option::FLD_SORTING => 11,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'St. Annen',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Aus welcher Gemeinde kommen Sie?',
                    EventManager_Model_Option::FLD_SORTING => 12,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Andere Gemeinde:',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextInputDemoData(false),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextInputOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Aus welcher Gemeinde kommen Sie?',
                    EventManager_Model_Option::FLD_SORTING => 13,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => '(optional)',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextInputDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextInputOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Ihre Nachricht an uns',
                    EventManager_Model_Option::FLD_SORTING => 14,
                    EventManager_Model_Option::FLD_OPTION_REQUIRED => $option_not_required,
                ],
            ],
            EventManager_Model_Event::FLD_REGISTRATIONS                 => [],
            EventManager_Model_Event::FLD_APPOINTMENTS                  => [],
            EventManager_Model_Event::FLD_DESCRIPTION                   => [[
                EventManager_Model_EventLocalization::FLD_LANGUAGE => 'de',
                EventManager_Model_EventLocalization::FLD_TEXT => 'Onkel Quentin wird uns am 19.09.2026 zwischen 10:00 und 16:00 Uhr mit auf seine Entdeckungsreise in die Geschichte von Petrus in seinem geheimnisvollen Buch nehmen. 
Wir freuen uns, dass Du beim Kinderbibeltag dabei sein möchtest!'
            ]],
        ]));
        $templates[] = $template2;

        // template 3
        $template3 = EventManager_Controller_Event::getInstance()->create(new EventManager_Model_Event([
            EventManager_Model_Event::FLD_CONTAINER_ID                  => $container_id,
            EventManager_Model_Event::FLD_NAME                          => [[
                EventManager_Model_EventLocalization::FLD_LANGUAGE => 'de',
                EventManager_Model_EventLocalization::FLD_TEXT => 'Zeltlager'
            ]],
            EventManager_Model_Event::FLD_START                         => '',
            EventManager_Model_Event::FLD_END                           => '',
            EventManager_Model_Event::FLD_REGISTRATION_POSSIBLE_UNTIL   => '',
            EventManager_Model_Event::FLD_LOCATION_RECORD               => '',
            EventManager_Model_Event::FLD_TYPE                          => $event_type,
            EventManager_Model_Event::FLD_STATUS                        => $event_status,
            EventManager_Model_Event::FLD_FEE                           => '',
            EventManager_Model_Event::FLD_TOTAL_PLACES                  => '',
            EventManager_Model_Event::FLD_BOOKED_PLACES                 => '',
            EventManager_Model_Event::FLD_AVAILABLE_PLACES              => '',
            EventManager_Model_Event::FLD_PARTICIPANT_CONTACT_FIELDS    => $defaultContactFields,
            EventManager_Model_Event::FLD_REGISTRANT_CONTACT_FIELDS     => $defaultContactFields,
            EventManager_Model_Event::FLD_IS_TEMPLATE                   => true,
            EventManager_Model_Event::FLD_OPTIONS                       => [
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Jahre',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextInputDemoData(false),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextInputOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Alter im Zeltlager:',
                    EventManager_Model_Option::FLD_SORTING => 1,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Name',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextInputDemoData(false),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextInputOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Notfallkontakt in der Zeit des Zeltlagers:',
                    EventManager_Model_Option::FLD_SORTING => 10,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Telefonnummer',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextInputDemoData(false),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextInputOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Notfallkontakt in der Zeit des Zeltlagers:',
                    EventManager_Model_Option::FLD_SORTING => 11,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'E-Mailadresse',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextInputDemoData(false),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextInputOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Notfallkontakt in der Zeit des Zeltlagers:',
                    EventManager_Model_Option::FLD_SORTING => 12,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Wir benötigen die nachfolgenden Angaben, um Ihr Kind während der Fahrt vor gesundheitlichen Gefahren bewahren und in Notfallsituationen richtig handeln zu können!',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextOption::class,
                    EventManager_Model_Option::FLD_GROUP => '',
                    EventManager_Model_Option::FLD_SORTING => 20,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Unser/Mein Kind muss während der Fahrt Medikamente einnehmen',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => '',
                    EventManager_Model_Option::FLD_SORTING => 21,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Arzneimittel / Einnahmeturnus / Menge:',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextInputDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextInputOption::class,
                    EventManager_Model_Option::FLD_GROUP => '',
                    EventManager_Model_Option::FLD_SORTING => 22,
                    EventManager_Model_Option::FLD_OPTION_REQUIRED => $option_required_if,
                    EventManager_Model_Option::FLD_DISPLAY => $option_display_if,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Es sind Allergien/Unverträglichkeiten zu beachten',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => '',
                    EventManager_Model_Option::FLD_SORTING => 23,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Unser/Mein Kind leidet unter folgenden Allergien/Unverträglichkeiten:',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextInputDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextInputOption::class,
                    EventManager_Model_Option::FLD_GROUP => '',
                    EventManager_Model_Option::FLD_SORTING => 24,
                    EventManager_Model_Option::FLD_OPTION_REQUIRED => $option_required_if,
                    EventManager_Model_Option::FLD_DISPLAY => $option_display_if,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Nahrungsgewohnheiten',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextInputDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextInputOption::class,
                    EventManager_Model_Option::FLD_GROUP => '',
                    EventManager_Model_Option::FLD_SORTING => 25,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Unser/Mein Kind ist Vergetarier/in:',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => '',
                    EventManager_Model_Option::FLD_SORTING => 26,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Ich bin damit einverstanden, dass...',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextOption::class,
                    EventManager_Model_Option::FLD_GROUP => '',
                    EventManager_Model_Option::FLD_SORTING => 27,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'mein Kind für bestimmte Unternehmungen und im begrenztem Umfang (z.B. Stadtbesichtigung, Einkäufe ect.) in Kleingruppen (mind. 3 Personen) ohne Aufsichtsperson unterwegs sein darf.',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => '',
                    EventManager_Model_Option::FLD_SORTING => 28,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'mein Kind an Badeausflügen unter Aufsicht teilnehmen kann.',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => '',
                    EventManager_Model_Option::FLD_SORTING => 29,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Mein Kind ist Nichtschwimmer',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Schwimmkenntnisse des Kindes',
                    EventManager_Model_Option::FLD_SORTING => 30,
                    EventManager_Model_Option::FLD_OPTION_REQUIRED => $option_required_if,
                    EventManager_Model_Option::FLD_DISPLAY => $option_display_if,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Mein Kind ist Schwimmer/Schwimmerin',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Schwimmkenntnisse des Kindes',
                    EventManager_Model_Option::FLD_SORTING => 31,
                    EventManager_Model_Option::FLD_OPTION_REQUIRED => $option_required_if,
                    EventManager_Model_Option::FLD_DISPLAY => $option_display_if,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Mein Kind hat folgendes Schwimmabzeichen',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextInputDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextInputOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Schwimmkenntnisse des Kindes',
                    EventManager_Model_Option::FLD_SORTING => 32,
                    EventManager_Model_Option::FLD_OPTION_REQUIRED => $option_required_if,
                    EventManager_Model_Option::FLD_DISPLAY => $option_display_if,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'mein Kind in PKW/Kleinbus mit Leitenden als Fahrer/Fahrerin mitfahren darf.',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => '',
                    EventManager_Model_Option::FLD_SORTING => 50,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'mein Kind im Falle einer kleineren Verletzung (z.B. Schnitt, Schürfwunde, ect.) ein handelsübliches Pflaster erhält.',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => '',
                    EventManager_Model_Option::FLD_SORTING => 51,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'mein Kind bei einem Zeckenbiss die Zecke von einer Betreuungsperson entfernt bekommt.',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => '',
                    EventManager_Model_Option::FLD_SORTING => 52,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'mein Kind, wenn es ständig oder in einer schwerwiegenden Sache gegen die Anordnung der GruppenleiterInnen verstößt, nach Rücksprache mit Frau Ann-Kathrin Berndmeyer - pastorale Mitarbeiterin - und mir auf eigene Kosten und Verantwortung vorzeitig nach Hause geschickt oder von mir abgeholt werden kann.',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => '',
                    EventManager_Model_Option::FLD_SORTING => 53,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Sonstige Dinge, die beachtet werden müssen oder die Sie uns mitteilen möchten:',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextInputDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextInputOption::class,
                    EventManager_Model_Option::FLD_GROUP => '',
                    EventManager_Model_Option::FLD_SORTING => 60,
                    EventManager_Model_Option::FLD_OPTION_REQUIRED => $option_not_required,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Für uns gehört es zu einer guten Vorbereitung, auch alle rechtlichen Fragen vorher genau zu klären.
Da unsere Leiter ehrenamtlich tätig sind, können wir ihnen keine weitgehende persönliche Haftung
auferlegen. Unsere Haftung ist daher wie folgt auf die Versicherungsleistung beschränkt:',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigTextDemoData('• Die persönliche Haftung der Leiter ist über die Leistung der vorhandenen Versicherung hinaus
ausgeschlossen, soweit dies gesetzlich zulässig ist, also auch im Falle einer Fahrlässigkeit. Dies gilt
insbesondere, wenn ein Teilnehmer einen Schaden erleidet oder verursacht, nachdem er eine
Weisung der Leitung unbeachtet gelassen oder sich unerlaubt von der Gemeinschaft entfernt hat.

• Verursacht ein Teilnehmer einen Schaden, für welchen ein Leiter in Anspruch genommen wird, so ist
dieser verpflichtet, den in Anspruch Genommenen von der Haftung freizustellen, soweit keine
Versicherung den Schaden übernimmt.

Ich wurde über das Programm des Zeltlagers informiert und weiß, dass u. A. folgende
Programmpunkte durchgeführt werden: Stadttag, Nachtspiele, Sternlauf, Sauerei, Postenläufe,
Waldspiele, Schwimmen, Küchen- & Klodienst'),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_TextOption::class,
                    EventManager_Model_Option::FLD_GROUP => '',
                    EventManager_Model_Option::FLD_SORTING => 70,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Hiermit erkläre ich/wir mich/uns einverstanden, dass ein geringfügiger Überschuss nicht an mich zurückgezahlt werden muss, sondern für die Zeltlager der folgenden Jahre verwendet werden darf.',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigCheckboxDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_CheckboxOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Verzicht auf Rückerstattung Überschüsse',
                    EventManager_Model_Option::FLD_SORTING => 80,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Ich/Wir haben die "Belehrung für Eltern und Sonstige Sorgeberechtigte gem. §34 Abs. 5, S.2 Infektionsschutzgesetz (IfSG)" zur Kenntnis genommen.',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigFileDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_FileOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Belehrung nach Infektionsschutzgesetz',
                    EventManager_Model_Option::FLD_SORTING => 90,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Einwilligungserklärung über die Erstellung von Foto und Videoaufnahmen meines Kindes während der Veranstaltung. (Bitte die Einwilligung ausfüllen und unterschrieben hier wieder hochladen)',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigFileDemoData(false),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_FileOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Foto und Videoaufnahmen',
                    EventManager_Model_Option::FLD_SORTING => 110,
                ],
                [
                    EventManager_Model_Option::FLD_NAME_OPTION => 'Datenschutz Information für das Zeltlager',
                    EventManager_Model_Option::FLD_OPTION_CONFIG => $this->setOptionConfigFileDemoData(),
                    EventManager_Model_Option::FLD_OPTION_CONFIG_CLASS => EventManager_Model_FileOption::class,
                    EventManager_Model_Option::FLD_GROUP => 'Datenschutzhinweis',
                    EventManager_Model_Option::FLD_SORTING => 200,
                ],
            ],
            EventManager_Model_Event::FLD_REGISTRATIONS                 => [],
            EventManager_Model_Event::FLD_APPOINTMENTS                  => [],
            EventManager_Model_Event::FLD_DESCRIPTION                   => [[
                EventManager_Model_EventLocalization::FLD_LANGUAGE => 'de',
                EventManager_Model_EventLocalization::FLD_TEXT => 'BESCHREIBUNG HIER'
            ]],
        ]));
        $templates[] = $template3;

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Event Templates were created'
            . EventManager_Model_Event::MODEL_NAME_PART);


        // dependent option name => trigger option name it depends on
        $rulesMap = [
            'Arzneimittel / Einnahmeturnus / Menge:' =>
                'Unser/Mein Kind muss während der Fahrt Medikamente einnehmen',
            'Unser/Mein Kind leidet unter folgenden Allergien/Unverträglichkeiten:' =>
                'Es sind Allergien/Unverträglichkeiten zu beachten',
            'Mein Kind ist Nichtschwimmer' =>
            'mein Kind an Badeausflügen unter Aufsicht teilnehmen kann.',
            'Mein Kind ist Schwimmer/Schwimmerin' =>
                'mein Kind an Badeausflügen unter Aufsicht teilnehmen kann.',
            'Mein Kind hat folgendes Schwimmabzeichen' =>
                'Mein Kind ist Schwimmer/Schwimmerin',
        ];

        foreach ($templates as $template) {
            $options = $template->{EventManager_Model_Event::FLD_OPTIONS};

            $byName = [];
            foreach ($options as $opt) {
                $byName[$opt->{EventManager_Model_Option::FLD_NAME_OPTION}] = $opt;
            }

            $changed = false;
            foreach ($rulesMap as $dependentName => $triggerName) {
                if (!isset($byName[$dependentName], $byName[$triggerName])) {
                    continue;
                }

                $dependent = $byName[$dependentName];
                $trigger   = $byName[$triggerName];

                $dependent->{EventManager_Model_Option::FLD_OPTION_RULE} = [
                    $this->setOptionsRuleConfigDemoData($trigger->getId(), 1, ''),
                ];
                $changed = true;
            }

            if ($changed) {
                EventManager_Controller_Event::getInstance()->update($template);
            }
        }
    }
}

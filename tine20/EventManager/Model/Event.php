<?php
/**
 * Tine 2.0
 *
 * @package     EventManager
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Model for metadata of files
 *
 * @package     EventManager
 * @subpackage  Model
 */
class EventManager_Model_Event extends Tinebase_Record_NewAbstract
{
    const FLD_NAME = 'name';
    const FLD_START = 'start';
    const FLD_END = 'end';
    const FLD_LOCATION = 'location';
    const FLD_TYPE = 'type';
    const FLD_STATUS = 'status';
    const FLD_FEE = 'fee';
    const FLD_TOTAL_PLACES = 'totalPlaces';
    const FLD_BOOKED_PLACES = 'bookedPlaces';
    const FLD_AVAILABLE_PLACES = 'availablePlaces';
    const FLD_DOUBLE_OPT_IN = 'doubleOptIn';
    const FLD_OPTIONS = 'options';
    const FLD_REGISTRATIONS = 'registrations';
    const FLD_DESCRIPTION = 'description';
    const FLD_IS_LIVE = 'isLive';
    const FLD_REGISTRATION_POSSIBLE_UNTIL = 'registrationPossibleUntil';
    const FLD_REQUIRED_CONTACT_FIELDS = 'requiredContactFields';
    const FLD_AUTO_TAG = 'autoTag';

    const MODEL_NAME_PART = 'Event';
    const TABLE_NAME = 'eventmanager_event';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::RECORD_NAME               => 'Event',
        self::RECORDS_NAME              => 'Events', // ngettext('Event', 'Events', n)
        self::TITLE_PROPERTY            => 'name',
        self::HAS_RELATIONS             => true,
        self::HAS_CUSTOM_FIELDS         => true,
        self::HAS_SYSTEM_CUSTOM_FIELDS  => true,
        self::HAS_NOTES                 => true,
        self::HAS_TAGS                  => true,
        self::MODLOG_ACTIVE             => true,
        self::HAS_ATTACHMENTS           => true,

        self::CREATE_MODULE             => true,

        self::EXPOSE_HTTP_API           => true,
        self::EXPOSE_JSON_API           => true,

        self::APP_NAME                  => EventManager_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::INDEXES => [

            ],
        ],

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_REGISTRATIONS => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        'name'      => [],
                        'options'   => [Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                            'option'      => [Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                                'name'      => [],
                            ]],
                            'record'   => []
                        ]]
                    ],
                ],
                self::FLD_OPTIONS => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        'name'      => [],
                    ],
                ]
            ]
        ],

        self::LANGUAGES_AVAILABLE => [
            self::TYPE => self::TYPE_KEY_FIELD,
            self::NAME => EventManager_Config::LANGUAGES_AVAILABLE,
            self::CONFIG => [
                self::APP_NAME => EventManager_Config::APP_NAME,
            ],
        ],

        self::FIELDS => [
            self::FLD_NAME => [
                self::TYPE => self::TYPE_LOCALIZED_STRING,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::CONFIG => [
                    self::TYPE => self::TYPE_STRING,
                    self::LENGTH => 255,
                ],
                self::LABEL => 'Name', // _('Name')
            ],
            self::FLD_START => [
                self::TYPE => self::TYPE_DATETIME,
                self::LABEL => 'Event start', // _('Event start')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
            ],
            self::FLD_END => [
                self::TYPE => self::TYPE_DATETIME,
                self::LABEL => 'Event end', // _('Event end')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
            ],
            self::FLD_REGISTRATION_POSSIBLE_UNTIL => [
                self::TYPE => self::TYPE_DATETIME,
                self::LABEL => 'Registration possible until', // _('Registration possible until')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::ALLOW_CAMEL_CASE => true,
            ],
            self::FLD_LOCATION => [
                self::TYPE => self::TYPE_RECORD,
                self::QUERY_FILTER => true,
                self::LABEL => 'Location', // _('Location')
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::CONFIG => array(
                    self::APP_NAME     => 'Addressbook',
                    self::MODEL_NAME   => 'Contact',
                    'idProperty'  => 'id'
                )
            ],
            self::FLD_TYPE => [
                self::TYPE => self::TYPE_KEY_FIELD,
                self::LABEL => 'Type', // _('Type')
                self::DEFAULT_VAL => 1,
                self::NAME => EventManager_Config::EVENT_TYPE,
                self::NULLABLE => true,
            ],
            self::FLD_STATUS => [
                self::TYPE => self::TYPE_KEY_FIELD,
                self::LABEL => 'Status', // _('Status')
                self::DEFAULT_VAL => 1,
                self::NAME => EventManager_Config::EVENT_STATUS,
                self::NULLABLE => true,
            ],
            self::FLD_FEE => [
                self::TYPE => self::TYPE_MONEY,
                self::LABEL => 'Fee', // _('Fee')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
            ],
            self::FLD_TOTAL_PLACES => [
                self::TYPE => self::TYPE_INTEGER,
                self::LABEL => 'Total places', // _('Total places')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::ALLOW_CAMEL_CASE      => true,
            ],
            self::FLD_BOOKED_PLACES => [
                self::TYPE => self::TYPE_INTEGER,
                self::LABEL => 'Booked places', // _('Booked places')
                self::NULLABLE => true,
                self::READ_ONLY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::ALLOW_CAMEL_CASE      => true,
            ],
            self::FLD_AVAILABLE_PLACES => [
                self::TYPE => self::TYPE_INTEGER,
                self::LABEL => 'Available places', // _('Available places')
                self::NULLABLE => true,
                self::READ_ONLY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::ALLOW_CAMEL_CASE      => true,
            ],
            self::FLD_DOUBLE_OPT_IN => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::READ_ONLY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::LABEL => 'Double Opt in', // _('Double Opt in')
                self::ALLOW_CAMEL_CASE      => true,
            ],
            self::FLD_OPTIONS => [
                self::TYPE => self::TYPE_RECORDS,
                self::QUERY_FILTER => true,
                self::LABEL => 'Event Options', // _('Event Options')
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::UI_CONFIG                     => [
                    self::COLUMNS                     => [
                        EventManager_Model_Option::FLD_NAME,
                        EventManager_Model_Option::FLD_PRICE,
                        EventManager_Model_Option::FLD_BOOKED_PLACES,
                        EventManager_Model_Option::FLD_AVAILABLE_PLACES,
                        EventManager_Model_Option::FLD_DESCRIPTION,
                    ],
                ],
                self::CONFIG => array(
                    self::DEPENDENT_RECORDS         => true,
                    self::REF_ID_FIELD              => EventManager_Model_Option::FLD_EVENT_ID,
                    self::APP_NAME                  => EventManager_Config::APP_NAME,
                    self::MODEL_NAME                => EventManager_Model_Option::MODEL_NAME_PART,
                )
            ],
            self::FLD_REGISTRATIONS => [
                self::TYPE => self::TYPE_RECORDS,
                self::QUERY_FILTER => true,
                self::LABEL => 'Event Registrations', // _('Event Registrations')
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::UI_CONFIG                     => [
                    self::COLUMNS                     => [
                        EventManager_Model_Registration::FLD_NAME,
                        EventManager_Model_Registration::FLD_FUNCTION,
                        EventManager_Model_Registration::FLD_STATUS,
                        EventManager_Model_Registration::FLD_SOURCE,
                        EventManager_Model_Registration::FLD_MEMBER_STATUS,
                        EventManager_Model_Registration::FLD_OPTIONS,
                        EventManager_Model_Registration::FLD_DESCRIPTION,
                    ],
                ],
                self::CONFIG => array(
                    self::DEPENDENT_RECORDS         => true,
                    self::REF_ID_FIELD              => EventManager_Model_Registration::FLD_EVENT_ID,
                    self::APP_NAME                  => EventManager_Config::APP_NAME,
                    self::MODEL_NAME                => EventManager_Model_Registration::MODEL_NAME_PART,
                )
            ],
            self::FLD_DESCRIPTION        => [
                self::LABEL             => 'Description', //_('Description')
                self::TYPE          => self::TYPE_LOCALIZED_STRING,
                self::CONFIG => [
                    self::TYPE => self::TYPE_FULLTEXT,
                ],
                self::NULLABLE      => true,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS     => [Zend_Filter_Empty::class => null],
            ],
            self::FLD_IS_LIVE => [
                self::TYPE => self::TYPE_BOOLEAN,
                self::LABEL => 'Event is live', // _('Event is live')
                self::NULLABLE => true,
                self::DEFAULT_VAL => false,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::ALLOW_CAMEL_CASE => true,
            ],
            self::FLD_REQUIRED_CONTACT_FIELDS => [
                self::TYPE => self::TYPE_JSON,
                self::LABEL => 'Required Contact fields', // _('Required Contact fields')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::ALLOW_CAMEL_CASE => true,
            ],
            self::FLD_AUTO_TAG => [
                self::TYPE => self::TYPE_JSON,
                self::LABEL => 'Contact auto Tags', // _('Contact auto Tags')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::ALLOW_CAMEL_CASE => true,
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}

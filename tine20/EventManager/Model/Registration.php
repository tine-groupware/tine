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
 * Model
 *
 * @package     EventManager
 * @subpackage  Model
 */
class EventManager_Model_Registration extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Registration';
    public const TABLE_NAME = 'eventmanager_registration';
    public const FLD_EVENT_ID = 'event_id';
    public const FLD_PARTICIPANT = 'participant';
    public const FLD_REGISTRATOR = 'registrator';
    public const FLD_FUNCTION = 'function';
    public const FLD_SOURCE = 'source';
    public const FLD_STATUS = 'status';
    public const FLD_REASON_WAITING = 'reason_waiting_list';
    public const FLD_BOOKED_OPTIONS = 'booked_options';
    public const FLD_DESCRIPTION = 'description';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 2,
        self::RECORD_NAME               => 'Registration', // gettext('GENDER_Registration')
        self::RECORDS_NAME              => 'Registrations', // ngettext('Registration', 'Registrations', n)
        self::DEFAULT_SORT_INFO         =>  ['field' => 'particpant'],
        self::TITLE_PROPERTY            => '{{participant.n_fn}}',
        self::IS_METADATA_MODEL_FOR     => self::FLD_PARTICIPANT,
        self::IS_DEPENDENT              => true,
        self::HAS_RELATIONS             => false,
        self::HAS_CUSTOM_FIELDS         => false,
        self::HAS_SYSTEM_CUSTOM_FIELDS  => true,
        self::HAS_NOTES                 => false,
        self::HAS_TAGS                  => false,
        self::MODLOG_ACTIVE             => true,
        self::HAS_ATTACHMENTS           => false,

        self::CREATE_MODULE             => false,

        self::EXPOSE_HTTP_API           => true,
        self::EXPOSE_JSON_API           => true,

        self::APP_NAME                  => EventManager_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
        ],

        self::JSON_EXPANDER => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_PARTICIPANT => [],
                self::FLD_BOOKED_OPTIONS => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        EventManager_Model_BookedOption::FLD_OPTION => [],
                        EventManager_Model_BookedOption::FLD_SELECTION_CONFIG => [],
                    ],
                ],
            ],
        ],

        self::FIELDS => [
            self::FLD_EVENT_ID      => [
                self::TYPE              => self::TYPE_RECORD,
                self::VALIDATORS        => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::CONFIG            => [
                    self::APP_NAME                  => EventManager_Config::APP_NAME,
                    self::MODEL_NAME                => EventManager_Model_Event::MODEL_NAME_PART,
                ],
                self::DISABLED          => true,
                self::ALLOW_CAMEL_CASE  => true,
                self::NULLABLE          => true,
            ],
            self::FLD_PARTICIPANT          => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::QUERY_FILTER      => true,
                self::LABEL             => 'Participant', // _('Participant')
                self::NULLABLE          => true,
                self::CONFIG            => [ //TODO Denormalization
                    self::APP_NAME          => Addressbook_Config::APP_NAME, // EventManager_Modal_Contact
                    self::MODEL_NAME        => Addressbook_Model_Contact::MODEL_NAME_PART,
                ],
                self::VALIDATORS        => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_REGISTRATOR          => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::QUERY_FILTER      => true,
                self::LABEL             => 'Registrator', // _('Registrator')
                self::NULLABLE          => true,
                self::CONFIG            => [ //TODO Denormalization
                    self::APP_NAME          => Addressbook_Config::APP_NAME, // EventManager_Modal_Contact
                    self::MODEL_NAME        => Addressbook_Model_Contact::MODEL_NAME_PART,
                ],
            ],
            self::FLD_FUNCTION      => [
                self::TYPE              => self::TYPE_KEY_FIELD,
                self::LABEL             => 'Function', // _('Function')
                self::DEFAULT_VAL       => 1,
                self::NAME              => EventManager_Config::REGISTRATION_FUNCTION,
                self::NULLABLE          => true,
            ],
            self::FLD_SOURCE        => [
                self::TYPE              => self::TYPE_KEY_FIELD,
                self::LABEL             => 'Source', // _('Source')
                self::DEFAULT_VAL       => 1,
                self::NAME              => EventManager_Config::REGISTRATION_SOURCE,
                self::NULLABLE          => true,
            ],
            self::FLD_STATUS        => [
                self::TYPE              => self::TYPE_KEY_FIELD,
                self::LABEL             => 'Status', // _('Status')
                self::DEFAULT_VAL       => 1,
                self::NAME              => EventManager_Config::REGISTRATION_STATUS,
                self::NULLABLE          => true,
                self::DESCRIPTION       => 'If the event is full it will not be possible to selected confirmed',
                // _('If the event is full it will not be possible to selected confirmed')
            ],
            self::FLD_REASON_WAITING        => [
                self::TYPE              => self::TYPE_KEY_FIELD,
                self::LABEL             => 'Reason waiting list', // _('Reason waiting list')
                self::DEFAULT_VAL       => 3,
                self::NAME              => EventManager_Config::REGISTRATION_WAITING_LIST,
                self::NULLABLE          => true,
            ],
            self::FLD_BOOKED_OPTIONS    => [
                self::TYPE                  => self::TYPE_RECORDS,
                self::LABEL                 => 'Booked options', // _('Booked options')
                self::CONFIG                => [
                    self::APP_NAME              => EventManager_Config::APP_NAME,
                    self::MODEL_NAME            => EventManager_Model_BookedOption::MODEL_NAME_PART,
                    self::STORAGE               => self::TYPE_JSON,
                ],
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::NULLABLE              => true,
                self::UI_CONFIG             => [
                    self::COLUMNS               => [
                        EventManager_Model_BookedOption::FLD_OPTION,
                        EventManager_Model_BookedOption::FLD_SELECTION_CONFIG,
                    ],
                    'copyMetadataForProps'      => [
                        EventManager_Model_Selection::FLD_SELECTION_CONFIG_CLASS
                    ],
                    'height'                    => 300,
                ],
                self::ALLOW_CAMEL_CASE      => true,
            ],
            self::FLD_DESCRIPTION       => [
                self::LABEL                 => 'Description', //_('Description')
                self::SHY                   => true,
                self::TYPE                  => self::TYPE_TEXT,
                self::LENGTH                => \Doctrine\DBAL\Platforms\MySqlPlatform::LENGTH_LIMIT_MEDIUMTEXT,
                self::NULLABLE              => true,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS         => [Zend_Filter_Empty::class => null],
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

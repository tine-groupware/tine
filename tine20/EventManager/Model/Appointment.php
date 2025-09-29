<?php
/**
 * Tine 2.0
 *
 * @package     EventManager
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
 */

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Model
 *
 * @package     EventManager
 * @subpackage  Model
 */
class EventManager_Model_Appointment extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Appointment';
    public const TABLE_NAME = 'eventmanager_appointment';
    public const FLD_EVENT_ID = 'event_id';
    public const FLD_SESSION_NUMBER = 'session_number';
    public const FLD_SESSION_DATE = 'session_date';
    public const FLD_START_TIME = 'start_time';
    public const FLD_END_TIME = 'end_time';

    public const FLD_STATUS = 'status';
    public const FLD_DESCRIPTION = 'description';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 1,
        self::RECORD_NAME               => 'Appointment', // gettext('GENDER_Appointment')
        self::RECORDS_NAME              => 'Appointments', // ngettext('Appointment', 'Appointments', n)
        self::DEFAULT_SORT_INFO         =>  ['field' => 'session_number', 'direction' => 'DESC'],
        self::TITLE_PROPERTY            => self::FLD_EVENT_ID,
        self::IS_DEPENDENT              => true,
        self::HAS_RELATIONS             => false,
        self::HAS_CUSTOM_FIELDS         => false,
        self::HAS_SYSTEM_CUSTOM_FIELDS  => false,
        self::HAS_NOTES                 => true,
        self::HAS_TAGS                  => false,
        self::MODLOG_ACTIVE             => false,
        self::HAS_ATTACHMENTS           => false,

        self::CREATE_MODULE             => false,

        self::EXPOSE_HTTP_API           => true,
        self::EXPOSE_JSON_API           => true,

        self::APP_NAME                  => EventManager_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::INDEXES => [

            ],
        ],

        self::FIELDS => [
            self::FLD_EVENT_ID         => [
                self::TYPE                  => self::TYPE_RECORD,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::DISABLED              => true,
                self::CONFIG                => [
                    self::APP_NAME              => EventManager_Config::APP_NAME,
                    self::MODEL_NAME            => EventManager_Model_Event::MODEL_NAME_PART,
                ],
                self::ALLOW_CAMEL_CASE      => true,
                self::NULLABLE              => true,
            ],
            self::FLD_SESSION_NUMBER    => [
                self::TYPE                  => self::TYPE_INTEGER,
                self::LABEL                 => 'Session number', // _('Session number')
                self::NULLABLE              => true,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::DEFAULT_VAL           => 1,
            ],
            self::FLD_SESSION_DATE      => [
                self::TYPE                  => self::TYPE_DATE,
                self::LABEL                 => 'Date', // _('Date')
                self::NULLABLE              => true,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS         => [Zend_Filter_Empty::class => null],
            ],
            self::FLD_START_TIME        => [
                self::TYPE                  => self::TYPE_TIME,
                self::LABEL                 => 'Start Time', // _('Start Time')
                self::NULLABLE              => true,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS         => [Zend_Filter_Empty::class => null],
            ],
            self::FLD_END_TIME          => [
                self::TYPE                  => self::TYPE_TIME,
                self::LABEL                 => 'End Time', // _('End Time')
                self::NULLABLE              => true,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS         => [Zend_Filter_Empty::class => null],
            ],
            self::FLD_STATUS            => [
                self::TYPE                  => self::TYPE_KEY_FIELD,
                self::LABEL                 => 'Status', // _('Status')
                self::DEFAULT_VAL           => 1,
                self::NAME                  => EventManager_Config::APPOINTMENT_STATUS,
                self::NULLABLE              => true,
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

<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2020-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Model
 *
 * @package     Calendar
 * @subpackage  Model
 */
class Calendar_Model_EventTypes extends Tinebase_Record_NewAbstract
{
    const FLD_EVENT_TYPE = 'event_type';
    const FLD_RECORD = 'record';

    const MODEL_NAME_PART = 'EventTypes';
    const TABLE_NAME = 'cal_event_types';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::RECORD_NAME               => 'Event Type',
        self::RECORDS_NAME              => 'Event Types', // ngettext('Event Type', 'Event Types', n)
        self::TITLE_PROPERTY            => '{{ event_type.name }}',
        self::DEFAULT_SORT_INFO         => [self::FIELD => self::FLD_EVENT_TYPE],
        self::HAS_RELATIONS             => false,
        self::HAS_CUSTOM_FIELDS         => false,
        self::HAS_SYSTEM_CUSTOM_FIELDS  => false,
        self::HAS_NOTES                 => false,
        self::HAS_TAGS                  => false,
        self::MODLOG_ACTIVE             => true,
        self::HAS_ATTACHMENTS           => false,

        self::CREATE_MODULE             => false,

        self::EXPOSE_HTTP_API           => true,
        self::EXPOSE_JSON_API           => true,

        self::APP_NAME                  => Calendar_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME      => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS   => [
                self::FLD_EVENT_TYPE       => [
                    self::COLUMNS           => [self::FLD_EVENT_TYPE, self::FLD_RECORD],
                ],
                self::FLD_RECORD                => [
                    self::COLUMNS           => [self::FLD_RECORD, self::FLD_EVENT_TYPE],
                ],
            ]
        ],

        self::ASSOCIATIONS => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'group_fk' => [
                    'targetEntity' => Calendar_Model_EventType::class,
                    'fieldName' => self::FLD_EVENT_TYPE,
                    'joinColumns' => [[
                        'name' => self::FLD_EVENT_TYPE,
                        'referencedColumnName'  => 'id'
                    ]],
                ],
            ],
        ],

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                Calendar_Model_EventTypes::FLD_EVENT_TYPE      => [],
                Calendar_Model_EventTypes::FLD_RECORD          => []
            ],
        ],

        self::FIELDS => [
            self::FLD_EVENT_TYPE      => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::CONFIG            => [
                    self::APP_NAME          => Calendar_Config::APP_NAME,
                    self::MODEL_NAME        => Calendar_Model_EventType::MODEL_NAME_PART,
                ],
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Event Type', // _('Event Type')
                self::QUERY_FILTER      => true,
                self::ALLOW_CAMEL_CASE  => true,
            ],
            self::FLD_RECORD            => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::CONFIG            => [
                    self::APP_NAME          => Calendar_Config::APP_NAME,
                    self::MODEL_NAME        => 'Event',
                ],
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Event', // _('Event')
                self::OMIT_MOD_LOG      => true,
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
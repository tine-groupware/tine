<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     CrewScheduling
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2020-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * calendar attendee crew scheduling role config
 *
 * @package     CrewScheduling
 * @subpackage  Model
 */
class CrewScheduling_Model_AttendeeRole extends Tinebase_Record_NewAbstract
{
    const FLD_ATTENDEE = 'attendee';
    const FLD_ROLE = 'role';
    const FLD_EVENT_TYPES = 'event_types';
    const MODEL_NAME_PART = 'AttendeeRole';
    const TABLE_NAME = 'cs_attendee_roles';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::RECORD_NAME               => 'Attendee Role',  // gettext('GENDER_Attendee Role')
        self::RECORDS_NAME              => 'Attendee Roles', // ngettext('Attendee Role', 'Attendee Roles', n)
        self::TITLE_PROPERTY            => "{{ role.name }}",
        self::HAS_RELATIONS             => false,
        self::HAS_CUSTOM_FIELDS         => false,
        self::HAS_SYSTEM_CUSTOM_FIELDS  => false,
        self::HAS_NOTES                 => false,
        self::HAS_TAGS                  => false,
        self::MODLOG_ACTIVE             => false,
        self::HAS_ATTACHMENTS           => false,

        self::CREATE_MODULE             => false,

        self::EXPOSE_HTTP_API           => true,
        self::EXPOSE_JSON_API           => true,

        self::APP_NAME                  => CrewScheduling_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::DEFAULT_SORT_INFO         => [self::FIELD => self::FLD_ROLE],

        self::TABLE => [
            self::NAME      => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS   => [
                self::FLD_ROLE       => [
                    self::COLUMNS           => [self::FLD_ROLE, self::FLD_ATTENDEE],
                ],
                self::FLD_ATTENDEE                => [
                    self::COLUMNS           => [self::FLD_ATTENDEE, self::FLD_ROLE],
                ],
            ]
        ],

        self::ASSOCIATIONS => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'role_fk' => [
                    'targetEntity' => CrewScheduling_Model_SchedulingRole::class,
                    'fieldName' => self::FLD_ROLE,
                    'joinColumns' => [[
                        'name' => self::FLD_ROLE,
                        'referencedColumnName'  => 'id'
                    ]],
                ],
            ],
        ],

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_ROLE     => [],
                self::FLD_EVENT_TYPES       => [],
                self::FLD_ATTENDEE       => []
            ],
        ],

        self::FIELDS => [
            self::FLD_ATTENDEE            => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::CONFIG            => [
                    self::APP_NAME          => Calendar_Config::APP_NAME,
                    self::MODEL_NAME        => Calendar_Model_Attender::MODEL_NAME_PART,
                ],
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Attendee', // _('Attendee')
                self::DISABLED          => true,
            ],
            self::FLD_ROLE      => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::CONFIG            => [
                    self::APP_NAME          => CrewScheduling_Config::APP_NAME,
                    self::MODEL_NAME        => CrewScheduling_Model_SchedulingRole::MODEL_NAME_PART,
                ],
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Scheduling Role', // _('Scheduling Role')
                self::QUERY_FILTER      => true,
            ],
            self::FLD_EVENT_TYPES            => [
                self::LABEL             => 'Event Types', // _('Event Types') // null means all!
                self::TYPE              => self::TYPE_RECORDS,
                self::NULLABLE          => true,
                self::CONFIG            => [
                    self::APP_NAME          => Calendar_Config::APP_NAME,
                    self::MODEL_NAME        => Calendar_Model_EventType::MODEL_NAME_PART,
                    self::STORAGE           => self::TYPE_JSON_REFID,
                ],
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public function getRoleTypesKey()
    {
        $expander = new Tinebase_Record_Expander(CrewScheduling_Model_AttendeeRole::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                CrewScheduling_Model_AttendeeRole::FLD_ROLE => [],
                CrewScheduling_Model_AttendeeRole::FLD_EVENT_TYPES => []
            ]
        ]);
        $expander->expandRecord($this);



        return $this->{CrewScheduling_Model_AttendeeRole::FLD_ROLE}->{CrewScheduling_Model_SchedulingRole::FLD_KEY} .
        ':' . implode('&', array_unique($this->{CrewScheduling_Model_AttendeeRole::FLD_EVENT_TYPES}?->sort('short_name')->short_name ?? []));
    }
}

<?php
/**
 * Tine 2.0
 *
 * @package     CrewScheduling
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * event type specific role config override
 *
 * @package     CrewScheduling
 * @subpackage  Model
 */
class CrewScheduling_Model_EventTypeConfig extends Tinebase_Record_NewAbstract
{
    const FLD_SCHEDULING_ROLE = 'scheduling_role';
    const FLD_EVENT_TYPE = 'event_type';
    const FLD_SHORTFALL_ACTION = 'shortfall_action';
    const FLD_EXCEEDANCE_ACTION = 'exceedance_action';
    const FLD_NUM_REQUIRED_ROLE_ATTENDEE = 'num_required_role_attendee';
    const FLD_ROLE_ATTENDEE_REQUIRED_GROUPS = 'role_attendee_required_groups';
    const FLD_ROLE_ATTENDEE_REQUIRED_GROUPS_OPERATOR = 'role_attendee_required_groups_operator';
    const FLD_SAME_ROLE_SAME_ATTENDEE = 'same_role_same_attendee';
    const FLD_OTHER_ROLE_SAME_ATTENDEE = 'other_role_same_attendee';

    const MODEL_NAME_PART = 'EventTypeConfig';
    const TABLE_NAME = 'cs_role_configs';


    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::RECORD_NAME               => 'Scheduling Role Config',  // gettext('GENDER_Scheduling Role Config')
        self::RECORDS_NAME              => 'Scheduling Role Configs', // ngettext('Scheduling Role Config', 'Scheduling Role Configs', n)
        self::TITLE_PROPERTY            => '{{ scheduling_role.name }}',
        self::HAS_RELATIONS             => false,
        self::HAS_CUSTOM_FIELDS         => false,
        self::HAS_SYSTEM_CUSTOM_FIELDS  => false,
        self::HAS_NOTES                 => false,
        self::HAS_TAGS                  => false,
        self::MODLOG_ACTIVE             => true,
        self::HAS_ATTACHMENTS           => false,
        self::IS_DEPENDENT              => true,

        self::CREATE_MODULE             => false,

        self::EXPOSE_HTTP_API           => true,
        self::EXPOSE_JSON_API           => true,

        self::APP_NAME                  => CrewScheduling_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::DEFAULT_SORT_INFO         => [self::FIELD => self::FLD_SCHEDULING_ROLE],


        self::TABLE => [
            self::NAME      => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS   => [
                self::FLD_SCHEDULING_ROLE       => [
                    self::COLUMNS           => [self::FLD_SCHEDULING_ROLE, self::FLD_EVENT_TYPE],
                ],
                self::FLD_EVENT_TYPE                => [
                    self::COLUMNS           => [self::FLD_EVENT_TYPE, self::FLD_SCHEDULING_ROLE],
                ],
            ]
        ],

        self::ASSOCIATIONS => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'sheduling_role_fk' => [
                    'targetEntity' => CrewScheduling_Model_SchedulingRole::class,
                    'fieldName' => self::FLD_SCHEDULING_ROLE,
                    'joinColumns' => [[
                        'name' => self::FLD_SCHEDULING_ROLE,
                        'referencedColumnName'  => 'id'
                    ]],
                ],
            ],
        ],

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_SCHEDULING_ROLE      => [],
                self::FLD_EVENT_TYPE       => [],
                self::FLD_ROLE_ATTENDEE_REQUIRED_GROUPS       => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        'group'      => [],
                    ],
                ]
            ],
        ],

        self::FIELDS => [
            self::FLD_SCHEDULING_ROLE      => [
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
            self::FLD_EVENT_TYPE            => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::CONFIG            => [
                    self::APP_NAME          => Calendar_Config::APP_NAME,
                    self::MODEL_NAME        => Calendar_Model_EventType::MODEL_NAME_PART,
                    self::IS_PARENT         => true,
                ],
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Event Type', // _('Event Type')
            ],
            self::FLD_NUM_REQUIRED_ROLE_ATTENDEE => [
                self::TYPE         => self::TYPE_INTEGER,
                self::NULLABLE     => true,
                self::VALIDATORS   => [Zend_Filter_Input::ALLOW_EMPTY => TRUE],
                self::LABEL        => 'Default Minimal Attendee Role Count', // _('Default Minimal Attendee Role Count')
                self::INPUT_FILTERS => ['Zend_Filter_Empty' => NULL],
                self::UI_CONFIG     => [
                    'emptyText'         => 'If not set value from role is taken per default' // _('If not set value from role is taken per default')
                ],
            ],
            self::FLD_SHORTFALL_ACTION => [
                self::TYPE         => self::TYPE_KEY_FIELD,
                self::NULLABLE     => true,
                self::DEFAULT_VAL  => null,
                self::VALIDATORS   => [
                    Zend_Filter_Input::ALLOW_EMPTY => TRUE,
                    Zend_Filter_Input::DEFAULT_VALUE => null,
                ],
                self::INPUT_FILTERS => [
                    Zend_Filter_Empty::class => null,
                ],
                self::LABEL        => 'Shortfall Action', // _('Shortfall Action')
                self::NAME         => CrewScheduling_Config::SHORTFALL_ACTIONS,
                self::UI_CONFIG     => [
                    'emptyText'         => 'If not set value from role is taken per default' // _('If not set value from role is taken per default')
                ],
            ],
            self::FLD_EXCEEDANCE_ACTION => [
                self::TYPE         => self::TYPE_KEY_FIELD,
                self::NULLABLE     => true,
                self::DEFAULT_VAL  => null,
                self::VALIDATORS   => [
                    Zend_Filter_Input::ALLOW_EMPTY => TRUE,
                    Zend_Filter_Input::DEFAULT_VALUE => null,
                ],
                self::INPUT_FILTERS => [
                    Zend_Filter_Empty::class => null,
                ],
                self::LABEL        => 'Exceedance Action', // _('Exceedance Action')
                self::NAME         => CrewScheduling_Config::EXCEEDANCE_ACTIONS,
                self::UI_CONFIG     => [
                    'emptyText'         => 'If not set value from role is taken per default' // _('If not set value from role is taken per default')
                ],
            ],
            self::FLD_ROLE_ATTENDEE_REQUIRED_GROUPS => [
                self::TYPE       => self::TYPE_RECORDS,
                self::NULLABLE   => true,
                self::LABEL     => 'Required Attendee Groups for role', // _('Required Attendee Groups for role')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::DEFAULT_VAL  => null,
                self::UI_CONFIG                     => [
                    self::COLUMNS                     => [CrewScheduling_Model_RequiredGroups::FLD_GROUP],
                ],
                self::CONFIG => [
                    self::APP_NAME                  => CrewScheduling_Config::APP_NAME,
                    self::MODEL_NAME                => CrewScheduling_Model_RequiredGroups::MODEL_NAME_PART,
                    self::REF_ID_FIELD              => CrewScheduling_Model_RequiredGroups::FLD_RECORD,
                    self::DEPENDENT_RECORDS         => true,
                ]
            ],
            self::FLD_ROLE_ATTENDEE_REQUIRED_GROUPS_OPERATOR => [
                self::TYPE         => self::TYPE_KEY_FIELD,
                self::NULLABLE     => true,
                self::VALIDATORS   => [Zend_Filter_Input::ALLOW_EMPTY => TRUE],
                self::LABEL       => 'Required Groups Behaviour', // _('Required Groups Behaviour')
                self::NAME         => CrewScheduling_Config::GROUP_OPERATORS,
                self::DEFAULT_VAL  => null,
//                self::DEFAULT_VAL  => CrewScheduling_Config::OPERATOR_ONE_OF,
            ],
            self::FLD_SAME_ROLE_SAME_ATTENDEE => [
                self::TYPE         => self::TYPE_KEY_FIELD,
                self::NULLABLE     => true,
                self::VALIDATORS   => [Zend_Filter_Input::ALLOW_EMPTY => TRUE],
                self::LABEL       => 'Behavior if this role is also required from other event types', // _('Behavior if this role is also required from other event types')
                self::INPUT_FILTERS => ['Zend_Filter_Empty' => NULL],
                self::NAME         => CrewScheduling_Config::SAME_ROLE_SAME_ATTENDEE,
            ],
            self::FLD_OTHER_ROLE_SAME_ATTENDEE => [
                self::TYPE          => self::TYPE_BOOLEAN,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => 0,
                self::LABEL       => 'Participants who occupy this role may also take on other roles in the event.', // _('Participants who occupy this role may also take on other roles in the event.')
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

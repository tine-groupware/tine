<?php
/**
 * Tine 2.0
 *
 * @package     CrewScheduling
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2016-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */
class CrewScheduling_Model_SchedulingRole extends Tinebase_Record_Abstract
{
    const FLD_KEY = 'key';
    const FLD_ORDER = 'order';
    const FLD_NAME = 'name';
    const FLD_COLOR = 'color';
    const FLD_SHORTFALL_ACTION = 'shortfall_action';
    const FLD_EXCEEDANCE_ACTION = 'exceedance_action';
    const FLD_LEADTIME = 'leadtime';
    const FLD_NUM_REQUIRED_ROLE_ATTENDEE = 'num_required_role_attendee';
    const FLD_ROLE_ATTENDEE_REQUIRED_GROUPS = 'role_attendee_required_groups';
    const FLD_ROLE_ATTENDEE_REQUIRED_GROUPS_OPERATOR = 'role_attendee_required_groups_operator';
    const FLD_DESCRIPTION = 'description';

    const MODEL_NAME_PART = 'SchedulingRole';
    const TABLE_NAME = 'cs_scheduling_role';

    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = CrewScheduling_Config::APP_NAME;

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION           => 9,
        self::RECORD_NAME       => 'Scheduling Role',   // gettext('GENDER_Scheduling Role')
        self::RECORDS_NAME       => 'Scheduling Roles', // ngettext('Scheduling Role', 'Scheduling Roles', n)
        self::CONTAINER_NAME            => 'Scheduling Role',
        self::CONTAINERS_NAME           => 'Scheduling Roles',
        self::TITLE_PROPERTY     => self::FLD_NAME,
        self::HAS_RELATIONS      => FALSE,
        self::HAS_CUSTOM_FIELDS   => true,
        self::HAS_NOTES          => FALSE,
        self::HAS_TAGS          => TRUE,
        self::MODLOG_ACTIVE      => TRUE,
        self::HAS_DELETED_TIME_UNIQUE => true,
        self::HAS_ATTACHMENTS   => FALSE,

        self::CREATE_MODULE      => FALSE,

        self::EXPOSE_HTTP_API     => true,
        self::EXPOSE_JSON_API     => true,

        self::APP_NAME           => CrewScheduling_Config::APP_NAME,
        self::MODEL_NAME         => self::MODEL_NAME_PART,
        self::GRANTS_MODEL       => CrewScheduling_Model_SchedulingRoleGrants::class,
        self::EXTENDS_CONTAINER  => self::FLD_CONTAINER_ID,

        self::UI_CONFIG         => [
            'searchComboConfig'     => [
                'useEditPlugin'         => false,
            ],
        ],

        self::TABLE            => [
            self::NAME    => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS     => [
                self::FLD_NAME                  => [
                    self::COLUMNS               => [self::FLD_NAME, self::FLD_DELETED_TIME]
                ],
                self::FLD_KEY                  => [
                    self::COLUMNS               => [self::FLD_KEY, self::FLD_DELETED_TIME]
                ]
            ]
        ],

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_ROLE_ATTENDEE_REQUIRED_GROUPS   => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        'group'      => [],
                        'record'   => []
                    ],
                ],
            ],
            Tinebase_Record_Expander::EXPANDER_PROPERTY_CLASSES => [
                Tinebase_Record_Expander::PROPERTY_CLASS_GRANTS         => [],
                Tinebase_Record_Expander::PROPERTY_CLASS_ACCOUNT_GRANTS => [],
            ],
        ],

        self::FIELDS          => [
            self::FLD_KEY => [
                self::TYPE       => self::TYPE_STRING,
                self::LENGTH     => 10,
                self::NULLABLE   => false,
                self::VALIDATORS  => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL      => 'Key', // _('Key')
                self::QUERY_FILTER => TRUE
            ],
            self::FLD_ORDER => [
                self::TYPE         => self::TYPE_INTEGER,
                self::NULLABLE     => true,
                self::VALIDATORS   => [Zend_Filter_Input::ALLOW_EMPTY => TRUE],
                self::LABEL       => 'Sorting Order', // _('Sorting Order')
                self::INPUT_FILTERS => ['Zend_Filter_Empty' => NULL],
                self::DEFAULT_VAL      => 10,
            ],
            self::FLD_NAME => [
                self::TYPE       => self::TYPE_STRING,
                self::LENGTH     => 255,
                self::NULLABLE   => false,
                self::VALIDATORS  => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL      => 'Name', // _('Name')
                self::QUERY_FILTER => TRUE
            ],
            self::FLD_COLOR => [
                self::TYPE       => self::TYPE_HEX_COLOR,
                self::LENGTH     => 7,
                self::NULLABLE     => true,
                self::VALIDATORS   => [Zend_Filter_Input::ALLOW_EMPTY => TRUE],
                self::LABEL       => 'Color', // _('Color')
                self::INPUT_FILTERS => ['Zend_Filter_Empty' => NULL],
                self::DEFAULT_VAL      => '#00FF00',
            ],
            self::FLD_NUM_REQUIRED_ROLE_ATTENDEE => [
                self::TYPE         => self::TYPE_INTEGER,
                self::NULLABLE     => true,
                self::VALIDATORS   => [Zend_Filter_Input::ALLOW_EMPTY => TRUE],
                self::LABEL       => 'Default Minimal Attendee Role Count', // _('Default Minimal Attendee Role Count')
                self::INPUT_FILTERS => ['Zend_Filter_Empty' => NULL],
                self::DEFAULT_VAL      => 1,
            ],
            self::FLD_SHORTFALL_ACTION => [
                self::TYPE         => self::TYPE_KEY_FIELD,
                self::NULLABLE     => false,
                self::VALIDATORS   => [Zend_Filter_Input::ALLOW_EMPTY => TRUE],
                self::LABEL        => 'Shortfall Action', // _('Shortfall Action')
                self::NAME         => CrewScheduling_Config::SHORTFALL_ACTIONS,
                self::DEFAULT_VAL  => CrewScheduling_Config::ACTION_NONE,
            ],
            self::FLD_EXCEEDANCE_ACTION => [
                self::TYPE         => self::TYPE_KEY_FIELD,
                self::NULLABLE     => false,
                self::VALIDATORS   => [Zend_Filter_Input::ALLOW_EMPTY => TRUE],
                self::LABEL       => 'Exceedance Action', // _('Exceedance Action')
                self::NAME         => CrewScheduling_Config::EXCEEDANCE_ACTIONS,
                self::DEFAULT_VAL  => CrewScheduling_Config::ACTION_NONE,
            ],
            self::FLD_LEADTIME => [
                self::TYPE          => self::TYPE_INTEGER,
                self::NULLABLE      => false,
                self::LABEL         => 'Leadtime', // _('Leadtime')
                self::DESCRIPTION   => 'Number of days before the actions are finally applied. On the one hand, this is the automatic cancellation and on the other hand the notifications (see grants tab). If appointments are are canceled, the participants are automatically informed of the cancellation.', // _('Number of days before the actions are finally applied. On the one hand, this is the automatic cancellation and on the other hand the notifications (see grants tab). If appointments are are canceled, the participants are automatically informed of the cancellation.')
                self::DEFAULT_VAL   => 7,
                self::VALIDATORS    => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                    Zend_Filter_Input::DEFAULT_VALUE=> 7,
                ]
            ],
            self::FLD_ROLE_ATTENDEE_REQUIRED_GROUPS => [
                self::TYPE       => self::TYPE_RECORDS,
                self::NULLABLE   => true,
                self::LABEL     => 'Required Attendee Groups for role', // _('Required Attendee Groups for role')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
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
                self::NULLABLE     => false,
                self::VALIDATORS   => [Zend_Filter_Input::ALLOW_EMPTY => TRUE],
                self::LABEL       => 'Required Groups Behaviour', // _('Required Groups Behaviour')
                self::NAME         => CrewScheduling_Config::GROUP_OPERATORS,
                self::DEFAULT_VAL  => CrewScheduling_Config::OPERATOR_ONE_OF,
            ],
            self::FLD_DESCRIPTION => [
                self::TYPE       => self::TYPE_FULLTEXT,
                self::NULLABLE   => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => TRUE],
                self::LABEL     => 'Description', // _('Description')
            ],
        ]
    ];

    public function isReplicable()
    {
        return true;
    }
}

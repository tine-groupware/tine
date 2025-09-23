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
 * event specific crew scheduling config
 *
 * @package     CrewScheduling
 * @subpackage  Model
 */
class CrewScheduling_Model_EventRoleConfig extends Tinebase_Record_NewAbstract
{
    const FLD_CAL_EVENT = 'cal_event';
    const FLD_ROLE = 'role';
    const FLD_EVENT_TYPES = 'event_types';
    const FLD_SHORTFALL_ACTION = 'shortfall_action';
    const FLD_EXCEEDANCE_ACTION = 'exceedance_action';
    const FLD_NUM_REQUIRED_ROLE_ATTENDEE = 'num_required_role_attendee';
    const FLD_SAME_ROLE_SAME_ATTENDEE = 'same_role_same_attendee';
    const FLD_OTHER_ROLE_SAME_ATTENDEE = 'other_role_same_attendee';

    const MODEL_NAME_PART = 'EventRoleConfig';
    const TABLE_NAME = 'cs_event_role_cfg';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::RECORD_NAME               => "Role Configuration",  // gettext('GENDER_Role Configuration')
        self::RECORDS_NAME              => "Role Configurations", // ngettext("Role Configuration", "Role Configurations", n)
        self::TITLE_PROPERTY            => self::FLD_ROLE,
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

        self::APP_NAME                  => CrewScheduling_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::IS_METADATA_MODEL_FOR     => self::FLD_ROLE,
        self::IS_DEPENDENT              => true,

        self::TABLE => [
            self::NAME      => self::TABLE_NAME,
        ],

        self::ASSOCIATIONS => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'role_config_fk' => [
                    'targetEntity' => Calendar_Model_Event::class,
                    'fieldName' => self::FLD_CAL_EVENT,
                    'joinColumns' => [[
                        'name' => self::FLD_CAL_EVENT,
                        'referencedColumnName'  => self::ID,
                    ]],
                ],
            ],
        ],

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_EVENT_TYPES       => [],
                self::FLD_ROLE     => [],
                self::FLD_CAL_EVENT       => []
            ],
        ],


        self::FIELDS => [
            self::FLD_CAL_EVENT            => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::CONFIG            => [
                    self::APP_NAME          => Calendar_Config::APP_NAME,
                    self::MODEL_NAME        => Calendar_Model_Event::MODEL_NAME_PART,
                    self::IS_PARENT         => true,
                ],
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Event', // _('Event')
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
                self::TYPE              => self::TYPE_RECORDS,
                self::NULLABLE          => true,
                self::CONFIG            => [
                    self::APP_NAME          => Calendar_Config::APP_NAME,
                    self::MODEL_NAME        => Calendar_Model_EventType::MODEL_NAME_PART,
                    self::STORAGE           => self::TYPE_JSON_REFID,
                ],
//                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Event Types', // _('Event Types')
                self::UI_CONFIG         => [
                    'hasGridColumn'         => true,
                ],
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
            self::FLD_SAME_ROLE_SAME_ATTENDEE => [
                self::TYPE          => self::TYPE_KEY_FIELD,
                self::NULLABLE      => true,
                self::VALIDATORS    => [Zend_Filter_Input::ALLOW_EMPTY => TRUE],
                self::LABEL         => 'Other event types?', // _('Other event types?')
                self::DESCRIPTION   => 'Behavior if this role is also required from other event types', // _('Behavior if this role is also required from other event types')
                self::INPUT_FILTERS => ['Zend_Filter_Empty' => NULL],
                self::NAME          => CrewScheduling_Config::SAME_ROLE_SAME_ATTENDEE,
                self::SHY           => true,
            ],
            self::FLD_OTHER_ROLE_SAME_ATTENDEE => [
                self::TYPE          => self::TYPE_BOOLEAN,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => 0,
                self::UI_CONFIG     => [
                    'boxLabel'          => 'Participants who occupy this role may also take on other roles in the event.', // _('Participants who occupy this role may also take on other roles in the event.')
                ],
                self::LABEL         => 'Allow other roles?', // _('Allow other roles?')
                self::SHY           => true,
            ],
            self::FLD_NUM_REQUIRED_ROLE_ATTENDEE => [
                self::TYPE         => self::TYPE_INTEGER,
                self::LABEL        => 'Number of required attendee', // _('Number of required attendee')
                self::VALIDATORS   => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::DEFAULT_VAL   => 1,
                self::UI_CONFIG     => [
                    'emptyText'         => 'If not set value from role is taken per default' // _('If not set value from role is taken per default')
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
}

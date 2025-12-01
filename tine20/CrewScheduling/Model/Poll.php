<?php declare(strict_types=1);
/**
 * class to handle grants
 *
 * @package     CrewScheduling
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.cweiss@metaways.de>
 */

class CrewScheduling_Model_Poll extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART    = 'Poll';
    public const TABLE_NAME         = 'cs_poll';

    public const FLD_SCHEDULING_ROLE  = 'scheduling_role';
    public const FLD_FROM = 'from';
    public const FLD_UNTIL = 'until';
    public const FLD_DESCRIPTION = 'description';
    public const FLD_SITES = 'sites';
    public const FLD_EVENT_TYPES = 'event_types';
    public const FLD_DEADLINE = 'deadline';
    public const FLD_IS_CLOSED = 'is_closed';
    public const FLD_REMINDERS = 'reminders';
    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 1,
        self::APP_NAME                  => CrewScheduling_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::MODLOG_ACTIVE             => true,
        self::HAS_DELETED_TIME_UNIQUE   => true,
        self::CONTAINER_PROPERTY        => null,
        self::DELEGATED_ACL_FIELD       => self::FLD_SCHEDULING_ROLE,
        self::EXPOSE_JSON_API           => true,
        self::EXPOSE_HTTP_API           => true,
        self::RECORD_NAME               => 'Poll',  // gettext('GENDER_Poll')
        self::RECORDS_NAME              => 'Polls', // ngettext('Poll', 'Polls', n)
        self::TITLE_PROPERTY            => self::FLD_DESCRIPTION,
        self::DEFAULT_SORT_INFO         => [self::FIELD => self::FLD_DEADLINE],
        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_SCHEDULING_ROLE       => [
                    self::COLUMNS                   => [self::FLD_SCHEDULING_ROLE],
                ],
            ],
        ],

        self::ASSOCIATIONS              => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                self::FLD_SCHEDULING_ROLE              => [
                    self::TARGET_ENTITY             => CrewScheduling_Model_SchedulingRole::class,
                    self::FIELD_NAME                => self::FLD_SCHEDULING_ROLE,
                    self::JOIN_COLUMNS                  => [[
                        self::NAME                          => self::FLD_SCHEDULING_ROLE,
                        self::REFERENCED_COLUMN_NAME        => self::ID,
                        self::ON_DELETE                     => self::CASCADE,
                    ]],
                ],
            ],
        ],

        self::JSON_EXPANDER => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_SCHEDULING_ROLE => [],
                self::FLD_SITES => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        CrewScheduling_Model_PollSite::FLD_SITE => [],
                    ],
                ],
                self::FLD_EVENT_TYPES => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        CrewScheduling_Model_PollEventType::FLD_EVENT_TYPE => [],
                    ],
                ],
                self::FLD_PARTICIPANTS => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        CrewScheduling_Model_PollParticipant::FLD_CONTACT => [],
                        CrewScheduling_Model_PollParticipant::FLD_POLL_REPLIES => [],
                    ],
                ],
            ],
        ],

        self::FIELDS                    => [
            self::FLD_SCHEDULING_ROLE        => [
                self::LABEL                     => 'Scheduling Role', // _('Scheduling Role')
                self::TYPE                      => self::TYPE_RECORD,
                self::LENGTH                    => 40,
                self::QUERY_FILTER              => true,
                self::CONFIG                    => [
                    self::APP_NAME                  => CrewScheduling_Config::APP_NAME,
                    self::MODEL_NAME                => CrewScheduling_Model_SchedulingRole::MODEL_NAME_PART,
                ],
                self::VALIDATORS        => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::UI_CONFIG                 => [
                    self::FIELDS_CONFIG             => [
                        'xtype'                         => 'cs-poll-schedulingrolefield',
                        'fixedIf'                       => '!phantom',
                    ],
                ],
            ],
            self::FLD_FROM                  => [
                self::LABEL                     => 'From', // _('From')
                self::DESCRIPTION               => 'Calendar events from this date on are included in poll.', // _('Calendar events from this date on are included in poll.')
                self::TYPE                      => self::TYPE_DATE,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_UNTIL                 => [
                self::LABEL                     => 'Until', // _('Until')
                self::DESCRIPTION               => 'Calendar events until this date on are included in poll.', // _('Calendar events until this date on are included in poll.')
                self::TYPE                      => self::TYPE_DATE,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_DESCRIPTION            => [
                self::LABEL                     => 'Description', // _('Description')
                self::TYPE                      => self::TYPE_FULLTEXT,
                self::QUERY_FILTER              => true,
                self::NULLABLE                  => true,
                self::SHY                       => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => true,
                ],
            ],
            self::FLD_DEADLINE              => [
                self::LABEL                     => 'Deadline', // _('Deadline')
                self::DESCRIPTION               => 'Once given date is passed poll is closed and attendee cannot replay any longer.', // _('Once given date is passed poll is closed and attendee cannot replay any longer.')
                self::TYPE                      => self::TYPE_DATE,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_SITES                   => [
                self::LABEL                     => 'Sites', // _('Sites')
                self::TYPE                      => self::TYPE_RECORDS,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => true,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_OPTIONAL
                ],
                self::CONFIG                    => [
                    self::DEPENDENT_RECORDS         => true,
                    self::APP_NAME                  => CrewScheduling_Config::APP_NAME,
                    self::MODEL_NAME                => CrewScheduling_Model_PollSite::MODEL_NAME_PART,
                    self::REF_ID_FIELD              => CrewScheduling_Model_PollSite::FLD_POLL,
                ],
                self::UI_CONFIG     => [
                    'xtype' => 'tinerecordspickercombobox',
                    'fixedIf' => '!phantom',
                    'filterOptions' => [
                        'jsConfig'              => ['filtertype' => 'tinebase.site'],
                    ],
                    'searchComboConfig' => [
                        'useEditPlugin' => false,
                        'emptyText' => 'Search for sites ... (leave empty for all sites)', // _('Search for sites ... (leave empty for all sites)')
                    ],
                    'additionalFilterSpec' => [
                        'config' => [
                            'name' => Tinebase_Config::SITE_FILTER,
                            'appName' => Tinebase_Config::APP_NAME,
                        ],
                    ],
                    self::UI_CONFIG_FEATURE     => [
                        self::APP_NAME              => Tinebase_Config::APP_NAME,
                        self::UI_CONFIG_FEATURE     => Tinebase_Config::FEATURE_SITE
                    ],
                ],
            ],
            self::FLD_EVENT_TYPES            => [
                self::LABEL                     => 'Event Types', // _('Event Types')
                self::TYPE                      => self::TYPE_RECORDS,
                self::DEFAULT_VAL               => null,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => true,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_OPTIONAL
                ],
                self::CONFIG                    => [
                    self::DEPENDENT_RECORDS         => true,
                    self::APP_NAME                  => CrewScheduling_Config::APP_NAME,
                    self::MODEL_NAME                => CrewScheduling_Model_PollEventType::MODEL_NAME_PART,
                    self::REF_ID_FIELD              => CrewScheduling_Model_PollEventType::FLD_POLL,
                ],
                self::UI_CONFIG         => [
                    'xtype' => 'tinerecordspickercombobox',
                    'fixedIf' => '!phantom',
                    'searchComboConfig'     => [
                        'useEditPlugin'         => false,
                    ],
                ],
            ],
            self::FLD_IS_CLOSED              => [
                self::LABEL                     => 'Is Closed',  // _('Is Closed')
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::DEFAULT_VAL               => 0,
            ],
//            self::FLD_REMINDERS              => [
//                self::LABEL                     => 'Reminders', // _('Reminders')
//                self::DESCRIPTION               => 'Remind participants not having replied when given dates have passed.',
//                self::TYPE                      => self::TYPE_RECORD, // Array of Dates?
//                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
//                self::CONFIG                    => [
//
//                ],
//            ],
            self::FLD_PARTICIPANTS            => [
                self::LABEL                     => 'Participants', // _('Participants')
                self::TYPE                      => self::TYPE_RECORDS,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => true,
                ],
                self::CONFIG                    => [
                    self::APP_NAME                  => CrewScheduling_Config::APP_NAME,
                    self::MODEL_NAME                => CrewScheduling_Model_PollParticipant::MODEL_NAME_PART,
                    self::REF_ID_FIELD              => CrewScheduling_Model_PollParticipant::FLD_POLL,
                    self::DEPENDENT_RECORDS         => true,
                ],
                self::UI_CONFIG                 => [
                    self::READ_ONLY                 => true, // managed by code
                    self::FIELDS_CONFIG             => [
                        'xtype'                         => 'cs-poll-participantsfield',
                        'height'                        => 300,
                    ],
                ]
            ],
        ],
    ];

    public const FLD_PARTICIPANTS = 'participants';

    public function getUrl($participant=null) : string
    {
        $publicUrl = Tinebase_Core::getUrl() . '/CrewScheduling/view/Poll/' . $this->getId();

        return $publicUrl . ($participant ? (
                '/' . ($participant instanceof CrewScheduling_Model_PollParticipant ? $participant->getId() : $participant)
            ) : '');
    }
    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}

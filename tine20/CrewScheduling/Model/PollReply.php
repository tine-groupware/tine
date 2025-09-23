<?php declare(strict_types=1);
/**
 * class to handle grants
 *
 * @package     CrewScheduling
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class CrewScheduling_Model_PollReply extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART    = 'PollReply';
    public const TABLE_NAME         = 'cs_poll_reply';

    public const FLD_POLL_PARTICIPANT_ID = 'poll_participant_id';
    public const FLD_EVENT_REF = 'event_ref'; // => @see self::getEventRef($event)
    public const FLD_STATUS = 'status';

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
        self::DELEGATED_ACL_FIELD       => self::FLD_POLL_PARTICIPANT_ID,
        self::RECORD_NAME               => 'Poll Reply',  // gettext('GENDER_Poll Reply')
        self::RECORDS_NAME              => 'Poll Replies', // ngettext('Poll Reply', 'Poll Replies', n)
        self::IS_DEPENDENT              => true,
        self::EXPOSE_JSON_API           => true,
        self::IS_METADATA_MODEL_FOR     => self::FLD_EVENT_REF,

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS        => [
                self::FLD_POLL_PARTICIPANT_ID . self::FLD_EVENT_REF  => [
                    self::COLUMNS => [
                        self::FLD_POLL_PARTICIPANT_ID,
                        self::FLD_EVENT_REF,
                        self::FLD_DELETED_TIME,
                    ],
                ],
            ],
            self::INDEXES                   => [
                self::FLD_POLL_PARTICIPANT_ID   => [
                    self::COLUMNS                   => [self::FLD_POLL_PARTICIPANT_ID],
                ],
            ],
        ],

        self::ASSOCIATIONS              => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                self::FLD_POLL_PARTICIPANT_ID   => [
                    self::TARGET_ENTITY             => CrewScheduling_Model_PollParticipant::class,
                    self::FIELD_NAME                => self::FLD_POLL_PARTICIPANT_ID,
                    self::JOIN_COLUMNS                  => [[
                        self::NAME                          => self::FLD_POLL_PARTICIPANT_ID,
                        self::REFERENCED_COLUMN_NAME        => self::ID,
                        self::ON_DELETE                     => self::CASCADE,
                    ]],
                ],
            ],
        ],
        self::JSON_EXPANDER => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_POLL_PARTICIPANT_ID => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        CrewScheduling_Model_PollParticipant::FLD_POLL => [],
                    ],
                ],
            ],
        ],

        self::FIELDS                    => [
            self::FLD_POLL_PARTICIPANT_ID   => [
                self::LABEL                     => 'Poll Participant', // _('Poll Participant')
                self::TYPE                      => self::TYPE_RECORD,
                self::LENGTH                    => 40,
                self::QUERY_FILTER              => true,
                self::CONFIG                    => [
                    self::APP_NAME                  => CrewScheduling_Config::APP_NAME,
                    self::MODEL_NAME                => CrewScheduling_Model_PollParticipant::MODEL_NAME_PART,
                    self::IS_PARENT                 => true,
                ],
                self::VALIDATORS        => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_EVENT_REF              => [
                self::LABEL                     => 'Event', // _('Event')
                self::TYPE                      => self::TYPE_RECORD,
                self::CONFIG                    => [
                    self::APP_NAME                  => Calendar_Config::APP_NAME,
                    self::MODEL_NAME                => Calendar_Model_Event::MODEL_NAME_PART,
                ],
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_STATUS                => [
                self::LABEL                     => 'Status', // _('Status')
                self::NAME                      => Calendar_Config::ATTENDEE_STATUS,
                self::TYPE                      => self::TYPE_KEY_FIELD,
                self::CONFIG                    => [
                    self::APPLICATION               => Calendar_Config::APP_NAME,
                ],
                self::LENGTH                    => 40,
                self::DEFAULT_VAL               => Calendar_Model_Attender::STATUS_NEEDSACTION,
                self::INPUT_FILTERS             => [
                    Zend_Filter_Empty::class        => [Calendar_Model_Attender::STATUS_NEEDSACTION],
                ],
                self::VALIDATORS                => [
                    Zend_Filter_Input::DEFAULT_VALUE => Calendar_Model_Attender::STATUS_NEEDSACTION,
                    [Zend_Validate_InArray::class, [
                        Calendar_Model_Attender::STATUS_NEEDSACTION,
                        Calendar_Model_Attender::STATUS_ACCEPTED,
                        Calendar_Model_Attender::STATUS_DECLINED,
                        Calendar_Model_Attender::STATUS_TENTATIVE,
                    ]]
                ],
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    /**
     * Generates an event_ref from an event
     *
     * @param Calendar_Model_Event $event
     * @return string
     */
    public static function getEventRef(Calendar_Model_Event $event): string
    {
        return $event->recur_id ? $event->base_event_id . '/' . $event->recur_id : $event->id;
    }
}

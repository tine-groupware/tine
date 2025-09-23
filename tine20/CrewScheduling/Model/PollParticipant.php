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

class CrewScheduling_Model_PollParticipant extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART    = 'PollParticipant';
    public const TABLE_NAME         = 'cs_poll_participant';

    public const FLD_POLL  = 'poll_id';
    public const FLD_CONTACT = 'contact_id';
    public const FLD_LAST_RESPONSE_TIME = 'last_response_time';
    public const FLD_POLL_REPLIES = 'poll_replies';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 1,
        self::APP_NAME                  => CrewScheduling_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::CONTAINER_PROPERTY        => null,
        self::DELEGATED_ACL_FIELD       => self::FLD_POLL,
        self::MODLOG_ACTIVE             => true,
        self::HAS_DELETED_TIME_UNIQUE   => true,
        self::RECORD_NAME               => 'Participant',  // gettext('GENDER_Participant')
        self::RECORDS_NAME              => 'Participants', // ngettext('Participant', 'Participants', n)
        self::IS_METADATA_MODEL_FOR     => self::FLD_CONTACT,
        self::TITLE_PROPERTY            => '{{ renderTitle(contact_id, "Addressbook_Model_Contact") }}',
        self::DEFAULT_SORT_INFO         => [self::FIELD => self::FLD_POLL],

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_POLL                  => [
                    self::COLUMNS                   => [self::FLD_POLL],
                ],
                self::FLD_CONTACT               => [
                    self::COLUMNS                   => [self::FLD_CONTACT],
                ],
            ],
        ],

        self::ASSOCIATIONS              => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                self::FLD_CONTACT              => [
                    self::TARGET_ENTITY             => Addressbook_Model_Contact::class,
                    self::FIELD_NAME                => self::FLD_CONTACT,
                    self::JOIN_COLUMNS                  => [[
                        self::NAME                          => self::FLD_CONTACT,
                        self::REFERENCED_COLUMN_NAME        => self::ID,
                        self::ON_DELETE                     => self::CASCADE,
                    ]],
                ],
                self::FLD_POLL                  => [
                    self::TARGET_ENTITY             => CrewScheduling_Model_Poll::class,
                    self::FIELD_NAME                => self::FLD_POLL,
                    self::JOIN_COLUMNS                  => [[
                        self::NAME                          => self::FLD_POLL,
                        self::REFERENCED_COLUMN_NAME        => self::ID,
                        self::ON_DELETE                     => self::CASCADE,
                    ]],
                ],
            ],
        ],

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_CONTACT               => [],
                self::FLD_POLL_REPLIES          => [],
            ],
        ],

        self::FIELDS                    => [
            self::FLD_POLL                  => [
                self::LABEL                     => 'Poll', // _('Poll')
                self::TYPE                      => self::TYPE_RECORD,
                self::QUERY_FILTER              => true,
                self::CONFIG                    => [
                    self::APP_NAME                  => CrewScheduling_Config::APP_NAME,
                    self::MODEL_NAME                => CrewScheduling_Model_Poll::MODEL_NAME_PART,
                ],
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_CONTACT               => [
                self::LABEL                     => 'Participant', // _('Participant')
                self::TYPE                      => self::TYPE_RECORD,
                self::QUERY_FILTER              => true,
                self::CONFIG                    => [
                    self::APP_NAME                  => Addressbook_Config::APP_NAME,
                    self::MODEL_NAME                => Addressbook_Model_Contact::MODEL_NAME_PART,
                ],
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_LAST_RESPONSE_TIME    => [
                self::LABEL                     => 'Response Date', // _('Response Date')
                self::TYPE                      => self::TYPE_DATETIME,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => true,
                ],
            ],
            self::FLD_NOTES                 => [
                self::LABEL                     => 'Notes', // _('Notes')
                self::TYPE                      => self::TYPE_FULLTEXT,
                self::QUERY_FILTER              => true,
                self::NULLABLE                  => true,
                self::SHY                       => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => true,
                ],
            ],
            self::FLD_POLL_REPLIES            => [
                self::LABEL                     => 'Replies', // _('Replies')
                self::TYPE                      => self::TYPE_RECORDS,
                self::CONFIG                    => [
                    self::DEPENDENT_RECORDS         => true,
                    self::APP_NAME                  => CrewScheduling_Config::APP_NAME,
                    self::MODEL_NAME                => CrewScheduling_Model_PollReply::MODEL_NAME_PART,
                    self::REF_ID_FIELD              => CrewScheduling_Model_PollReply::FLD_POLL_PARTICIPANT_ID,
                ],
                self::UI_CONFIG                 => [
                    self::READ_ONLY                 => true,
                    self::FIELDS_CONFIG             => [
                        'xtype'                         => 'cs-poll-participant-repliesfield',
                        'height'                        => 500,
                    ],
                ]
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}

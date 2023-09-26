<?php
/**
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Model of an attendee
 *
 * @package Tasks
 * @property Tinebase_DateTime $alarm_ack_time
 * @property Tinebase_DateTime $alarm_snooze_time
 * @property string $transp
 * @property string $user_id
 * @property string $status
 * @property string $status_authkey
 * @property string $user_type
 * @property string $displaycontainer_id
 */
class Tasks_Model_Attendee extends Tinebase_Record_NewAbstract
{
    /**
     * supported status
     */
    const STATUS_NEEDSACTION   = 'NEEDS-ACTION';
    const STATUS_ACCEPTED      = 'ACCEPTED';
    const STATUS_DECLINED      = 'DECLINED';
    const STATUS_TENTATIVE     = 'TENTATIVE';

    const FLD_TASK_ID = 'task_id';
    const FLD_USER_ID = 'user_id';
    const FLD_STATUS = 'status';

    const MODEL_NAME_PART = 'Attendee';
    const TABLE_NAME = 'tasks_attendee';

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
        self::VERSION           => 1,
        self::IS_DEPENDENT      => true,
        self::RECORD_NAME       => 'Collaborator',
        self::RECORDS_NAME      => 'Collaborators', // ngettext('Collaborator', 'Collaborators', n)
        self::TITLE_PROPERTY    => self::FLD_USER_ID,
        self::MODLOG_ACTIVE     => true,
        self::HAS_ALARMS        => true,
        self::IS_METADATA_MODEL_FOR => self::FLD_USER_ID,

        self::APP_NAME           => Tasks_Config::APP_NAME,
        self::MODEL_NAME         => self::MODEL_NAME_PART,

        self::TABLE            => [
            self::NAME    => self::TABLE_NAME,
            self::INDEXES       => [
                self::FLD_TASK_ID             => [
                    self::COLUMNS               => [self::FLD_TASK_ID],
                ],
            ],
        ],

        'associations' => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'tasks_attendee::task_id-tasks::id' => [
                    'targetEntity' => Tasks_Model_Task::class,
                    'fieldName' => self::FLD_TASK_ID,
                    'joinColumns' => [[
                        'name' => self::FLD_TASK_ID,
                        'referencedColumnName'  => 'id',
                        self::ON_DELETE                 => 'CASCADE',
                    ]],
                ],
            ],
        ],


        self::FIELDS          => [
            self::FLD_USER_ID => [
                self::TYPE       => self::TYPE_RECORD,
                self::LENGTH     => 40,
                self::VALIDATORS  => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL      => 'Collaborator', // _('Collaborator')
                self::CONFIG                => [
                    self::APP_NAME              => Addressbook_Config::APP_NAME,
                    self::MODEL_NAME            => Addressbook_Model_Contact::MODEL_PART_NAME,
                ],
                self::QUERY_FILTER => TRUE
            ],
            self::FLD_TASK_ID => [
                self::TYPE       => self::TYPE_RECORD,
                self::LENGTH     => 40,
                self::VALIDATORS  => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL      => 'Task', // _('Task')
                self::QUERY_FILTER => TRUE,
                self::CONFIG        => [
                    self::APP_NAME          => Tasks_Config::APP_NAME,
                    self::MODEL_NAME        => Tasks_Model_Task::MODEL_NAME_PART,
                    self::FOREIGN_FIELD     => Tasks_Model_Task::FLD_ATTENDEES,
                ],
                self::UI_CONFIG                     => [
                    self::DISABLED                      => true,
                ],
            ],
            self::FLD_STATUS => [
                self::LABEL      => 'Status', // _('Status')
                self::TYPE       => self::TYPE_KEY_FIELD,
                self::NAME       => Tasks_Config::ATTENDEE_STATUS,
                self::LENGTH     => 40,
                self::DEFAULT_VAL   => self::STATUS_NEEDSACTION,
                self::VALIDATORS    => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => self::STATUS_NEEDSACTION
                ],
            ],
        ]
    ];
}

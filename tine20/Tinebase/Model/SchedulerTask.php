<?php

/**
 * tine Groupware
 *
 * @package     Tinebase
 * @subpackage  Scheduler
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Scheduler Task Model
 *
 * @package     Tinebase
 * @subpackage  Scheduler
 *
 * @property bool $active
 * @property string $name
 * @property Tinebase_Scheduler_Task $config
 * @property bool $is_system
 * @property Tinebase_DateTime $last_run
 * @property int $last_duration
 * @property string $lock_id
 * @property Tinebase_DateTime $next_run
 * @property Tinebase_DateTime $last_failure
 * @property int $failure_count
 * @property Tinebase_DateTime $server_time
 */

class Tinebase_Model_SchedulerTask extends Tinebase_Record_NewAbstract
{
    public const FLD_ACCOUNT_ID = 'account_id';
    public const FLD_APPLICATION_ID = 'application_id';
    public const FLD_CONFIG = 'config';
    public const FLD_IS_SYSTEM = 'is_system';
    public const FLD_NAME = 'name';
    public const FLD_NEXT_RUN = 'next_run';
    public const FLD_DISABLE_AUTO_SHUFFLE = 'disable_auto_shuffle';
    public const FLD_ACTIVE = 'active';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        'version'           => 5,
        'recordName'        => 'Scheduler task',
        'recordsName'       => 'Scheduler tasks', // ngettext('Scheduler task', 'Scheduler tasks', n)
        //'containerProperty' => 'container_id',
        'titleProperty'     => 'name',
        //'containerName'     => 'Inventory item list',
        //'containersName'    => 'Inventory item lists', // xnxgettext('Inventory item list', 'Inventory item lists', n)
        'hasRelations'      => false,
        'hasCustomFields'   => false,
        'hasNotes'          => false,
        'hasTags'           => false,
        'modlogActive'      => true,
        self::HAS_DELETED_TIME_UNIQUE => true,
        'hasAttachments'    => false,
        'exposeJsonApi'     => false,

        'createModule'      => false,

        'appName'           => 'Tinebase',
        'modelName'         => 'SchedulerTask',

        'table'             => [
            'name'    => Tinebase_Backend_Scheduler::TABLE_NAME,
            'indexes' => [
                'next_run' => [
                    'columns' => ['next_run']
                ]
            ],
            'uniqueConstraints' => [
                'name' => [
                    'columns' => ['name', self::FLD_DELETED_TIME]
                ]
            ]
        ],

        self::FILTER_MODEL => [
            self::FLD_APPLICATION_ID => [
                'filter' => Tinebase_Model_Filter_Text::class,
                'label' => 'Application', // _('Application')
                'jsConfig' => array('filtertype' => 'admin.application'),
            ],
        ],

        'fields'            => [
            self::FLD_NAME => [
                'type'          => 'string',
                'length'        => 255,
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                'label'         => 'Name', // _('Name')
                'queryFilter'   => true
            ],
            self::FLD_CONFIG => [
                'type'          => 'text',
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                'label'         => 'Configuration', // _('Configuration')
                'converters'    => [Tinebase_Scheduler_TaskConverter::class],
                'inputFilters'  => []
            ],
            self::FLD_DISABLE_AUTO_SHUFFLE => [
                self::TYPE      => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL => false,
                self::LABEL     => 'Disable automatic time shuffle', // _('Disable automatic time shuffle')
            ],
            'last_run' => [
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label'         => 'Last run', // _('Last run')
                'default'       => null,
                'type'          => 'datetime',
                'nullable'      => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            'last_duration' => [
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label'         => 'Last run duration', // _('Last run duration')
                'default'       => null,
                'type'          => 'integer',
                'nullable'      => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            'lock_id' => [
                'type'          => 'string',
                'length'        => 255,
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label'         => 'Lock ID', // _('Lock ID')
                'default'       => null,
                'nullable'      => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_NEXT_RUN => [
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                'label'         => 'Next run', // _('Next run')
                'type'          => 'datetime',
            ],
            'last_failure' => [
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label'         => 'Last failure', // _('Last failure')
                'default'       => null,
                'type'          => 'datetime',
                'nullable'      => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            'failure_count' => [
                'validators'    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'label'         => 'Failure count', // _('Failure count')
                'default'       => 0,
                'type'          => 'integer',
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            'server_time' => [
                'type'          => 'virtual',
                'config'        => ['type' => 'datetime'],
                self::CONVERTERS => [Tinebase_Model_Converter_DateTime::class],
                self::UI_CONFIG                     => [
                    self::DISABLED                      => true,
                ],
            ],
            self::FLD_ACTIVE => [
                self::LABEL => 'Active', // _('Active')
                self::TYPE => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL => true,
            ],
            self::FLD_IS_SYSTEM => [
                self::TYPE => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL => false,
                self::UI_CONFIG => [
                    self::READ_ONLY => true,
                ],
            ],
            self::FLD_ACCOUNT_ID => [
                self::TYPE => self::TYPE_USER,
                self::NULLABLE => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_APPLICATION_ID => [
                self::TYPE => self::TYPE_STRING,
                self::SPECIAL_TYPE => 'application',
                self::LENGTH => 255,
                'nullable' => false,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => false,   'presence' => 'required'],
                self::LABEL => 'Application', // _('Application')
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    /**
     * @return bool
     */
    public function run()
    {
        return $this->config->run();
    }
}

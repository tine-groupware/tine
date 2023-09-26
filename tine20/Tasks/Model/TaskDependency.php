<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tasks
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold task dependency mapping
 *
 * @package     Sales
 * @subpackage  Model
 */
class Tasks_Model_TaskDependency extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'TaskDependency';
    public const TABLE_NAME = 'tasks_dependency';

    public const FLD_TASK_ID = 'task_id';
    public const FLD_DEPENDS_ON = 'depends_on';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 1,
        self::MODLOG_ACTIVE             => true,
        self::IS_DEPENDENT              => true,

        self::APP_NAME                  => Tasks_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::RECORD_NAME               => 'Task Dependency', // gettext('GENDER_Task Dependency')
        self::RECORDS_NAME              => 'Task Dependencies', // ngettext('Task Dependency', 'Task Dependencies', n)

        self::HAS_DELETED_TIME_UNIQUE   => true,

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_TASK_ID               => [
                    self::COLUMNS                   => [self::FLD_TASK_ID, self::FLD_DEPENDS_ON, self::FLD_DELETED_TIME],
                ],
            ],
            self::UNIQUE_CONSTRAINTS        => [
                self::FLD_DEPENDS_ON               => [
                    self::COLUMNS                   => [self::FLD_DEPENDS_ON, self::FLD_TASK_ID, self::FLD_DELETED_TIME],
                ],
            ],
        ],

        self::ASSOCIATIONS              => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                self::FLD_TASK_ID         => [
                    self::TARGET_ENTITY         => Tasks_Model_Task::class,
                    self::FIELD_NAME            => self::FLD_TASK_ID,
                    self::JOIN_COLUMNS          => [[
                        self::NAME                  => self::FLD_TASK_ID,
                        self::REFERENCED_COLUMN_NAME=> 'id',
                        self::ON_DELETE             => self::CASCADE,
                    ]],
                ],
                self::FLD_DEPENDS_ON        => [
                    self::TARGET_ENTITY         => Tasks_Model_Task::class,
                    self::FIELD_NAME            => self::FLD_DEPENDS_ON,
                    self::JOIN_COLUMNS          => [[
                        self::NAME                  => self::FLD_DEPENDS_ON,
                        self::REFERENCED_COLUMN_NAME=> 'id',
                        self::ON_DELETE             => self::CASCADE,
                    ]],
                ],
            ],
        ],

        self::FIELDS                    => [
            self::FLD_TASK_ID               => [
                self::LABEL                     => 'Task', // _('Task')
                self::TYPE                      => self::TYPE_RECORD,
                self::CONFIG => [
                    self::APP_NAME              => Tasks_Config::APP_NAME,
                    self::MODEL_NAME            => Tasks_Model_Task::MODEL_NAME_PART,
                ],
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::UI_CONFIG                 => [
                    'xtype' => 'tasks.dependency'
//                    'columns'   => 'summary, status'
                ],
            ],
            self::FLD_DEPENDS_ON            => [
                self::LABEL                     => 'Depends on Task', // _('Depends on Task')
                self::TYPE                      => self::TYPE_RECORD,
                self::CONFIG => [
                    self::APP_NAME              => Tasks_Config::APP_NAME,
                    self::MODEL_NAME            => Tasks_Model_Task::MODEL_NAME_PART,
                ],
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::UI_CONFIG                 => [
//                    'columns'   => 'summary, status'
                ],
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}

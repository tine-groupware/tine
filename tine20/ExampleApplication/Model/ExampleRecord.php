<?php
/**
 * class to hold ExampleRecord data
 * 
 * @package     ExampleApplication
 * @subpackage  Model
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold ExampleRecord data
 * 
 * @package     ExampleApplication
 * @subpackage  Model
 * @property Tinebase_DateTime datetime
 */
class ExampleApplication_Model_ExampleRecord extends Tinebase_Record_NewAbstract implements Tinebase_Record_PerspectiveInterface
{
    use Tinebase_Record_PerspectiveTrait;

    public const FLD_CONTAINER_ID = 'container_id';
    public const FLD_DATETIME = 'datetime';
    public const FLD_DESCRIPTION = 'description';
    public const FLD_NAME = 'name';
    public const FLD_NUMBER_INT = 'number_int';
    public const FLD_NUMBER_STR = 'number_str';
    public const FLD_ONE_TO_ONE = 'one_to_one';
    public const FLD_PERSPECTIVE = 'perspective';
    public const FLD_PERSP_DT = 'persp_dt';
    public const FLD_REASON = 'reason';
    public const FLD_STATUS = 'status';

    public const MODEL_NAME_PART = 'ExampleRecord';
    public const TABLE_NAME = 'example_application_record';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
    
    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 1,
        self::APP_NAME                  => ExampleApplication_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::RECORD_NAME               => 'example record', // _('example record') ngettext('example record', 'example records', n)
        self::RECORDS_NAME              => 'example records', // _('example records')
        self::CONTAINER_PROPERTY        => self::FLD_CONTAINER_ID,
        self::TITLE_PROPERTY            => self::FLD_NAME,
        self::CONTAINER_NAME            => 'example record list', // _('example record list')
        self::CONTAINERS_NAME           => 'example record lists', // _('example record lists')

        // none of these values needs to set to false, they all default to false, just remove them as needed
        self::HAS_RELATIONS             => true,
        self::HAS_CUSTOM_FIELDS         => true,
        self::HAS_SYSTEM_CUSTOM_FIELDS  => true,
        self::HAS_NOTES                 => true,
        self::HAS_TAGS                  => true,
        self::MODLOG_ACTIVE             => true,
        self::HAS_ATTACHMENTS           => true,

        self::CREATE_MODULE             => true,
        self::EXPOSE_HTTP_API           => true,
        self::EXPOSE_JSON_API           => true,

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_CONTAINER_ID          => [
                    self::COLUMNS                   => [self::FLD_CONTAINER_ID]
                ],
                self::FLD_DESCRIPTION           => [
                    self::COLUMNS                   => [self::FLD_DESCRIPTION],
                    self::FLAGS                     => [self::TYPE_FULLTEXT],
                ]
            ],
        ],

        self::EXPORT                    => [
            self::SUPPORTED_FORMATS         => ['csv'],
        ],

        self::FIELDS                    => [
            self::FLD_NAME                  => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::LABEL                     => 'Name', // _('Name')
                self::QUERY_FILTER              => true,
            ],
            self::FLD_DESCRIPTION           => [
                self::TYPE                      => self::TYPE_FULLTEXT,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                     => 'Description', // _('Description')
                self::QUERY_FILTER              => true,
            ],
            self::FLD_STATUS                => [
                self::TYPE                      => self::TYPE_KEY_FIELD,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                     => 'Status', // _('Status')
                self::NAME                      => 'exampleStatus',
                self::DEFAULT_VAL               => 'IN-PROCESS',
            ],
            self::FLD_REASON                => [
                self::TYPE                      => self::TYPE_KEY_FIELD,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                     => 'Reason', // _('Reason')
                self::NAME                      => 'exampleReason',
                self::NULLABLE                  => true,
            ],
            self::FLD_NUMBER_STR            => [
                self::TYPE                      => self::TYPE_NUMBERABLE_STRING,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                     => 'Number', // _('Number')
                self::QUERY_FILTER              => true,
                self::CONFIG                    => [
                    Tinebase_Numberable::STEPSIZE          => 1,
                    Tinebase_Numberable_String::PREFIX     => 'ER-',
                    Tinebase_Numberable_String::ZEROFILL   => 0,
                    // TODO implement that
//                    'filters' => '', // group/filters - use to link with container for example
//                    'allowClientSet' => '', // force?
//                    'allowDuplicate' => '',
//                    'duplicateResolve' => array(
//                        'inc/2 (recursive)' => '',
//                        'next free' => '',
//                        'exception' => '',
//                    ),
                ]
            ],
            self::FLD_NUMBER_INT            => [
                self::TYPE                      => self::TYPE_NUMBERABLE_INT,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                     => 'Number', // _('Number')
                self::QUERY_FILTER              => true,
                self::CONFIG                    => [
                    Tinebase_Numberable::STEPSIZE => 1,
                    Tinebase_Numberable::CONFIG_OVERRIDE => 'Tinebase_Container::getNumberableConfig',
                ]
            ],
            self::FLD_DATETIME             => [
                self::TYPE                      => self::TYPE_DATETIME,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                     => 'datetime', // _('datetime')
                self::NULLABLE                  => true,
                self::FILTER_DEFINITION         => [
                    self::FILTER                    => Tinebase_Model_Filter_DateTime::class,
                    self::OPTIONS                   => [
                        Tinebase_Model_Filter_Date::BEFORE_OR_IS_NULL => true,
                        Tinebase_Model_Filter_Date::AFTER_OR_IS_NULL  => true,
                    ]
                ]
            ],
            self::FLD_ONE_TO_ONE            => [
                self::TYPE                      => self::TYPE_RECORD,
                self::DOCTRINE_IGNORE           => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true,],
                self::NULLABLE                  => true,
                self::QUERY_FILTER              => true,
                self::CONFIG                    => [
                    self::APP_NAME                  => ExampleApplication_Config::APP_NAME,
                    self::MODEL_NAME                => ExampleApplication_Model_OneToOne::MODEL_NAME_PART,
                    self::REF_ID_FIELD              => ExampleApplication_Model_OneToOne::FLD_EXAMPLE_RECORD,
                    self::DEPENDENT_RECORDS         => true,
                ],
            ],
            self::FLD_PERSPECTIVE           => [
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::IS_PERSPECTIVE            => true,
                self::PERSPECTIVE_DEFAULT       => true,
            ],
            self::FLD_PERSP_DT              => [
                self::TYPE                      => self::TYPE_DATETIME,
                self::IS_PERSPECTIVE            => true,
            ],
        ]
    ];
}

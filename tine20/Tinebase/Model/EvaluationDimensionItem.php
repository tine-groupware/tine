<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * EvaluationDimensionItem Model
 *
 * @package     Tinebase
 * @subpackage  Model
 */

class Tinebase_Model_EvaluationDimensionItem extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'EvaluationDimensionItem';
    public const TABLE_NAME = 'evaluation_dimension_item';

    public const FLD_EVALUATION_DIMENSION_ID = 'evaluation_dimension_id';
    public const FLD_NAME = self::NAME;
    public const FLD_NUMBER = 'number';
    public const FLD_DESCRIPTION = 'description';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 1,
        self::APP_NAME                  => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::MODLOG_ACTIVE             => true,
        self::HAS_DELETED_TIME_UNIQUE   => true,
        self::IS_DEPENDENT              => true,
        self::RECORD_NAME               => 'Item', // gettext('GENDER_Item')
        self::RECORDS_NAME              => 'Items', // ngettext('Item', 'Items', n)
        self::TITLE_PROPERTY            => '{{ number }} - {{ name }}',
        self::EXPOSE_JSON_API           => true,
        self::HAS_SYSTEM_CUSTOM_FIELDS  => true,

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS        => [
                self::NAME            => [
                    self::COLUMNS                   => [
                        self::FLD_EVALUATION_DIMENSION_ID,
                        self::FLD_NUMBER,
                        self::FLD_NAME,
                        self::FLD_DELETED_TIME
                    ],
                ],
            ],
        ],

        self::FIELDS                    => [
            self::FLD_EVALUATION_DIMENSION_ID => [
                self::TYPE                      => self::TYPE_RECORD,
                self::CONFIG                    => [
                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                => Tinebase_Model_EvaluationDimension::MODEL_NAME_PART,
                    self::IS_PARENT                 => true,
                ],
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::DISABLED                  => true,
            ],
            self::FLD_NUMBER                => [
                self::LABEL                     => 'Number', //_('Number')
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 64,
                self::QUERY_FILTER              => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_NAME                  => [
                self::LABEL                     => 'Name', // _('Name')
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::QUERY_FILTER              => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_DESCRIPTION           => [
                self::LABEL                     => 'Description', // _('Description')
                self::TYPE                      => self::TYPE_FULLTEXT,
                self::NULLABLE                  => true,
                self::QUERY_FILTER              => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => TRUE],
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

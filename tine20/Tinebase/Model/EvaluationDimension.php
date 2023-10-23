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
 * EvaluationDimension Model
 *
 * @package     Tinebase
 * @subpackage  Model
 */

class Tinebase_Model_EvaluationDimension extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'EvaluationDimension';
    public const TABLE_NAME = 'evaluation_dimension';

    public const FLD_DEPENDS_ON = 'depends_on';
    public const FLD_ITEMS = 'items';
    public const FLD_MODELS = 'models';
    public const FLD_NAME = self::NAME;

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

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS        => [
                self::NAME            => [
                    self::COLUMNS                   => [self::FLD_NAME, self::FLD_DELETED_TIME],
                ],
            ],
        ],

        self::FIELDS                    => [
            self::FLD_NAME                  => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_ITEMS                 => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::CONFIG                    => [
                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                => Tinebase_Model_EvaluationDimensionItem::MODEL_NAME_PART,
                    self::IS_DEPENDENT              => true,
                ],
            ],
            self::FLD_MODELS                => [
                self::TYPE                      => self::TYPE_JSON,
                self::NULLABLE                  => true,
            ],
            self::FLD_DEPENDS_ON            => [
                self::TYPE                      => self::TYPE_RECORD,
                self::NULLABLE                  => true,
                self::CONFIG                    => [
                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                => Tinebase_Model_EvaluationDimension::MODEL_NAME_PART,
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

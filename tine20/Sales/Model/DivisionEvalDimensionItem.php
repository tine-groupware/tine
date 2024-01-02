<?php declare(strict_types=1);
/**
 * class to hold DivisionEvalDimensionItem data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold DivisionEvalDimensionItem data
 *
 * @package     Sales
 */
class Sales_Model_DivisionEvalDimensionItem extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART    = 'DivisionEvalDimensionItem';
    public const TABLE_NAME         = 'sales_division_eval_dimension_item';

    public const FLD_DIVISION_ID = 'division_id';
    public const FLD_EVAL_DIMENSION_ITEM_ID = 'eval_dimension_item_id';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 1,
        self::APP_NAME                  => Sales_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::MODLOG_ACTIVE             => true,

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_DIVISION_ID => [],
                self::FLD_EVAL_DIMENSION_ITEM_ID => [],
            ],
        ],

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_EVAL_DIMENSION_ITEM_ID => [
                    self::COLUMNS                   => [self::FLD_EVAL_DIMENSION_ITEM_ID, self::FLD_DIVISION_ID],
                ],
            ],
            self::UNIQUE_CONSTRAINTS        => [
                self::FLD_DIVISION_ID                 => [
                    self::COLUMNS                   => [self::FLD_DIVISION_ID, self::FLD_EVAL_DIMENSION_ITEM_ID],
                ],
            ],
        ],

        self::FIELDS                    => [
            self::FLD_DIVISION_ID           => [
                self::TYPE                      => self::TYPE_RECORD,
                self::CONFIG                    => [
                    self::APP_NAME                  => Sales_Config::APP_NAME,
                    self::MODEL_NAME                => Sales_Model_Division::MODEL_NAME_PART,
                ],
            ],
            self::FLD_EVAL_DIMENSION_ITEM_ID=> [
                self::TYPE                      => self::TYPE_RECORD,
                self::CONFIG                    => [
                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                => Tinebase_Model_EvaluationDimensionItem::MODEL_NAME_PART,
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

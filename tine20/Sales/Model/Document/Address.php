<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Address Model for Documents (is a snapshot / copy of normal Model_Address record)
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_Address extends Sales_Model_Address
{
    public const MODEL_NAME_PART = 'Document_Address';
    public const TABLE_NAME = 'sales_document_address';

    public const FLD_DOCUMENT_ID = 'document_id';
    public const FLD_DOCUMENT_FIELD = 'document_field';
    public const FLD_DOCUMENT_TYPE = 'document_type';

    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::VERSION] = 5;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::TABLE][self::NAME] = self::TABLE_NAME;
        $_definition[self::TABLE][self::INDEXES] = [
            self::FLD_DOCUMENT_ID => [
                self::COLUMNS => [self::FLD_DOCUMENT_ID],
            ],
        ];
        $_definition[self::EXPOSE_JSON_API] = false;
        $_definition[self::DENORMALIZATION_OF] = Sales_Model_Address::class;

        $_definition[self::FIELDS][self::FLD_DOCUMENT_ID] = [
            self::TYPE                  => self::TYPE_DYNAMIC_RECORD,
            self::LENGTH                => 40,
            self::CONFIG                => [
                self::REF_MODEL_FIELD       => self::FLD_DOCUMENT_TYPE,
                self::PERSISTENT            => Tinebase_Model_Converter_DynamicRecord::REFID,
                self::IS_PARENT             => true,
                self::FIXED_LENGTH          => true,
            ],
            self::FILTER_DEFINITION     => [
                self::FILTER                => Tinebase_Model_Filter_ForeignIdDynamic::class,
                self::OPTIONS               => [
                    self::REF_MODEL_FIELD       => self::FLD_DOCUMENT_TYPE,
                ],
            ],
        ];
        $_definition[self::FIELDS][self::FLD_DOCUMENT_FIELD] = [
            self::TYPE                  => self::TYPE_STRING,
            self::LENGTH                => 255,
            self::NULLABLE              => true,
        ];
        $_definition[self::FIELDS][self::FLD_DOCUMENT_TYPE] = [
            self::TYPE                  => self::TYPE_MODEL,
            self::CONFIG                => [
                self::AVAILABLE_MODELS      => [
                    Sales_Model_Document_Delivery::class,
                    Sales_Model_Document_Invoice::class,
                    Sales_Model_Document_Offer::class,
                    Sales_Model_Document_Order::class,
                    Sales_Model_Document_PurchaseInvoice::class,
                ],
            ],
            self::LENGTH                => 255,
        ];
        $_definition[self::FIELDS][self::FLD_CUSTOMER_ID][self::TYPE] = self::TYPE_STRING;
        unset($_definition[self::FIELDS][self::FLD_CUSTOMER_ID][self::LABEL]);
        unset($_definition[self::FIELDS][self::FLD_CUSTOMER_ID][self::VALIDATORS]);
        unset($_definition[self::FIELDS][self::FLD_CUSTOMER_ID][self::CONFIG]);

        $_definition[self::FIELDS][self::FLD_DEBITOR_ID][self::CONFIG][self::MODEL_NAME] = Sales_Model_Document_Debitor::MODEL_NAME_PART;
        $_definition[self::FIELDS][self::FLD_DEBITOR_ID][self::CONFIG][self::DENORMALIZATION_OF] = null;
        unset($_definition[self::FIELDS][self::FLD_DEBITOR_ID][self::CONFIG][self::IS_PARENT]);

        $_definition[self::FIELDS][self::FLD_SUPPLIER_ID][self::CONFIG][self::MODEL_NAME] = Sales_Model_Document_Supplier::MODEL_NAME_PART;
        $_definition[self::FIELDS][self::FLD_SUPPLIER_ID][self::CONFIG][self::DENORMALIZATION_OF] = null;
        unset($_definition[self::FIELDS][self::FLD_SUPPLIER_ID][self::CONFIG][self::IS_PARENT]);
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}

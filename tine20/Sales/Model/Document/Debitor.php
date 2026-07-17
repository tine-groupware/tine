<?php declare(strict_types=1);
/**
 * class to hold Document Debitor Number data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Document Debitor Number data
 *
 * @package     Sales
 */
class Sales_Model_Document_Debitor extends Sales_Model_Debitor
{
    public const MODEL_NAME_PART    = 'Document_Debitor';
    public const TABLE_NAME         = 'sales_document_debitor';

    public const FLD_DOCUMENT_ID = 'document_id';
    public const FLD_DOCUMENT_TYPE = 'document_type';

    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::VERSION] = 1;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::TABLE] = [
            self::NAME      => self::TABLE_NAME,
            self::INDEXES   => [
                self::FLD_DOCUMENT_ID => [
                    self::COLUMNS   => [self::FLD_DOCUMENT_ID],
                ],
                self::FLD_ORIGINAL_ID => [
                    self::COLUMNS   => [self::FLD_ORIGINAL_ID],
                ],
            ],
        ];
        $_definition[self::EXPOSE_JSON_API] = false;
        $_definition[self::EXPOSE_HTTP_API] = false;

        unset($_definition[self::FIELDS][self::FLD_DELIVERY]);
        unset($_definition[self::JSON_EXPANDER][Tinebase_Record_Expander::EXPANDER_PROPERTIES][self::FLD_DELIVERY]);
        unset($_definition[self::FIELDS][self::FLD_BILLING]);
        unset($_definition[self::JSON_EXPANDER][Tinebase_Record_Expander::EXPANDER_PROPERTIES][self::FLD_BILLING]);
        unset($_definition[self::FIELDS][self::FLD_CUSTOMER_ID]);
        unset($_definition[self::JSON_EXPANDER][Tinebase_Record_Expander::EXPANDER_PROPERTIES][self::FLD_CUSTOMER_ID]);


        $_definition[self::FIELDS][self::FLD_NUMBER][self::TYPE] = self::TYPE_STRING;
        unset($_definition[self::FIELDS][self::FLD_NUMBER][self::CONFIG]);

        $_definition[self::DENORMALIZATION_OF] = Sales_Model_Debitor::class;
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
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}

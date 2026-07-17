<?php declare(strict_types=1);
/**
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Sales_Model_Document_Supplier extends Sales_Model_Supplier
{
    public const MODEL_NAME_PART    = 'Document_Supplier';
    public const TABLE_NAME         = 'sales_document_supplier';

    public const FLD_DOCUMENT_ID = 'document_id';
    public const FLD_DOCUMENT_TYPE = 'document_type';

    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::VERSION] = 3;
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

        $_definition[self::DENORMALIZATION_OF] = Sales_Model_Supplier::class;
        $_definition[self::DENORMALIZATION_CONFIG] = [
            self::TRACK_CHANGES         => true,
            self::DENORMALIZATION_DIFF_OMIT_FIELDS => [
                'fulltext',
            ]
        ];
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
        $_definition[self::FIELDS]['postal_id'][self::CONFIG][self::MODEL_NAME] = Sales_Model_Document_SupplierAddress::MODEL_NAME_PART;
        unset($_definition[self::FIELDS]['postal_id'][self::CONFIG][self::DEPENDENT_RECORDS]);
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}

<?php declare(strict_types=1);
/**
 * class to hold Document Debitor Number data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023-2024 Metaways Infosystems GmbH (http://www.metaways.de)
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

    public static string $documentIdModel = Sales_Model_Document_Offer::MODEL_NAME_PART;
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
            self::TYPE                  => self::TYPE_RECORD,
            self::NORESOLVE             => true,
            self::CONFIG                => [
                self::APP_NAME              => Sales_Config::APP_NAME,
                self::MODEL_NAME            => self::$documentIdModel, // TODO not nice, it can be any document really...
            ],
        ];
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}

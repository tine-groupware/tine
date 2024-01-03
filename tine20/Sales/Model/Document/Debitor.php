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
                self::FLD_CUSTOMER_ID => [
                    self::COLUMNS   => [self::FLD_CUSTOMER_ID],
                ],
                self::FLD_ORIGINAL_ID => [
                    self::COLUMNS   => [self::FLD_ORIGINAL_ID],
                ],
            ],
        ];
        $_definition[self::EXPOSE_JSON_API] = true;

        $_definition[self::FIELDS][self::FLD_DELIVERY][self::CONFIG][self::MODEL_NAME] =
            Sales_Model_Document_Address::MODEL_NAME_PART;
        $_definition[self::FIELDS][self::FLD_BILLING][self::CONFIG][self::MODEL_NAME] =
            Sales_Model_Document_Address::MODEL_NAME_PART;
        $_definition[self::FIELDS][self::FLD_NUMBER][self::TYPE] = self::TYPE_STRING;
        unset($_definition[self::FIELDS][self::FLD_NUMBER][self::CONFIG]);


        if (!isset($_definition[self::ASSOCIATIONS])) {
            $_definition[self::ASSOCIATIONS] = [];
        }
        if (!isset($_definition[self::ASSOCIATIONS][\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE])) {
            $_definition[self::ASSOCIATIONS][\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE] = [];
        }
        $_definition[self::ASSOCIATIONS][\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE][self::FLD_CUSTOMER_ID] =
            [
                self::TARGET_ENTITY             => Sales_Model_Document_Customer::class,
                self::FIELD_NAME                => self::FLD_CUSTOMER_ID,
                self::JOIN_COLUMNS              => [[
                    self::NAME                      => self::FLD_CUSTOMER_ID,
                    self::REFERENCED_COLUMN_NAME    => Sales_Model_Document_Customer::ID,
                ]],
            ];

        $_definition[self::DENORMALIZATION_OF] = Sales_Model_Debitor::class;
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}

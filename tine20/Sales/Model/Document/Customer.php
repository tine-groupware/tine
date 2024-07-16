<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Customer Model for Documents (is a snapshot / copy of normal Model_Customer record)
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_Customer extends Sales_Model_Customer
{
    public const MODEL_NAME_PART = 'Document_Customer';
    public const TABLE_NAME = 'sales_document_customer';

    public const FLD_DOCUMENT_ID = 'document_id';

    public static string $documentIdModel = Sales_Model_Document_Offer::MODEL_NAME_PART;
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
                'description'   => [
                    self::COLUMNS   => ['description'],
                    self::FLAGS     => [self::TYPE_FULLTEXT],
                ],
                self::FLD_DOCUMENT_ID => [
                    self::COLUMNS   => [self::FLD_DOCUMENT_ID],
                ],
                self::FLD_ORIGINAL_ID => [
                    self::COLUMNS   => [self::FLD_ORIGINAL_ID],
                ],
            ],
        ];
        $_definition[self::EXPOSE_JSON_API] = true;

        $_definition[self::DELEGATED_ACL_FIELD] = self::FLD_DOCUMENT_ID;

        $_definition[self::HAS_RELATIONS] = false;

        unset($_definition[self::FIELDS][self::FLD_DEBITORS]);
        unset($_definition[self::JSON_EXPANDER][Tinebase_Record_Expander::EXPANDER_PROPERTIES][self::FLD_DEBITORS]);
        $_definition[self::JSON_EXPANDER][Tinebase_Record_Expander::EXPANDER_PROPERTY_CLASSES] = null;
        unset($_definition[self::FIELDS]['postal']);
        unset($_definition[self::JSON_EXPANDER][Tinebase_Record_Expander::EXPANDER_PROPERTIES]['postal']);

        unset($_definition[self::FIELDS]['number'][self::CONFIG]);
        $_definition[self::FIELDS]['number'][self::TYPE] = self::TYPE_STRING;
        $_definition[self::FIELDS]['number'][self::LENGTH] = 255;

        if (!isset($_definition[self::ASSOCIATIONS])) {
            $_definition[self::ASSOCIATIONS] = [];
        }
        if (!isset($_definition[self::ASSOCIATIONS][\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE])) {
            $_definition[self::ASSOCIATIONS][\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE] = [];
        }
        /* sadly not possible
         * $_definition[self::ASSOCIATIONS][\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE][self::FLD_DOCUMENT_ID] =
            [
                self::TARGET_ENTITY             => Sales_Model_Document_Offer::class,
                self::FIELD_NAME                => self::FLD_DOCUMENT_ID,
                self::JOIN_COLUMNS              => [[
                    self::NAME                      => self::FLD_DOCUMENT_ID,
                    self::REFERENCED_COLUMN_NAME    => Sales_Model_Document_Offer::ID,
                ]],
            ];*/

        $_definition[self::DENORMALIZATION_OF] = Sales_Model_Customer::class;
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
    protected static $_configurationObject = NULL;

    public function setFromArray(array &$_data)
    {
        unset($_data['relations']);
        parent::setFromArray($_data);
    }
}

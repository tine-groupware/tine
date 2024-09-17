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
 * Offer Document Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_Offer extends Sales_Model_Document_Abstract
{
    public const MODEL_NAME_PART = 'Document_Offer';
    public const TABLE_NAME = 'sales_document_offer';

    public const FLD_ORDER_ID = 'order_id';
    public const FLD_OFFER_STATUS = 'offer_status';
    public const FLD_FOLLOWUP_ORDER_CREATED_STATUS = 'followup_order_created_status';
    public const FLD_FOLLOWUP_ORDER_BOOKED_STATUS = 'followup_order_booked_status';

    /**
     * offer status
     */
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_RELEASED = 'RELEASED';
    public const STATUS_ORDERED = 'ORDERED';
    public const STATUS_REJECTED = 'REJECTED';

    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::CREATE_MODULE] = true;
        $_definition[self::RECORD_NAME] = 'Offer'; // gettext('GENDER_Offer')
        $_definition[self::RECORDS_NAME] = 'Offers'; // ngettext('Offer', 'Offers', n)
        
        $_definition[self::VERSION] = 4;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::TABLE] = [
            self::NAME                      => self::TABLE_NAME,
            /*self::INDEXES                   => [
                self::FLD_...            => [
                    self::COLUMNS                   => [self::FLD_...],
                ],
            ]*/
        ];

        if (!isset($_definition[self::ASSOCIATIONS][\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE])) {
            $_definition[self::ASSOCIATIONS][\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE] = [];
        }
        $_definition[self::ASSOCIATIONS][\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE][self::FLD_ORDER_ID] = [
            self::TARGET_ENTITY             => Sales_Model_Document_Order::class,
            self::FIELD_NAME                => self::FLD_ORDER_ID,
            self::JOIN_COLUMNS              => [[
                self::NAME                      => self::FLD_ORDER_ID,
                self::REFERENCED_COLUMN_NAME    => Sales_Model_Document_Order::ID,
            ]],
        ];

        self::_adaptFields($_definition);
    }

    /**
     * @param array $_definition
     * @return void
     */
    protected static function _adaptFields(array &$_definition)
    {
        // offer customers are optional
        unset($_definition[self::FIELDS][self::FLD_CUSTOMER_ID][self::VALIDATORS]);

        $_definition[self::FIELDS][self::FLD_POSITIONS][self::CONFIG][self::MODEL_NAME] =
            Sales_Model_DocumentPosition_Offer::MODEL_NAME_PART;

        // OFFER_STATUS keyfield: In Bearbeitung(ungebucht, offen), Zugestellt(gebucht, offen),
        //                        Beauftragt(gebucht, offen), Abgelehnt(gebucht, geschlossen)
        Tinebase_Helper::arrayInsertAfterKey($_definition[self::FIELDS], self::FLD_DOCUMENT_NUMBER, [
            self::FLD_OFFER_STATUS => [
                self::LABEL => 'Status', // _('Status')
                self::TYPE => self::TYPE_KEY_FIELD,
                self::NAME => Sales_Config::DOCUMENT_OFFER_STATUS,
                self::LENGTH => 255,
                self::NULLABLE => true,
            ],
        ]);

        $_definition[self::FIELDS] = array_merge($_definition[self::FIELDS], [
            self::FLD_ORDER_ID => [
                self::TYPE => self::TYPE_RECORD,
                self::DISABLED => true,
                self::CONFIG => [
                    self::APP_NAME => Sales_Config::APP_NAME,
                    self::MODEL_NAME => Sales_Model_Document_Order::MODEL_NAME_PART,
                ],
                self::NULLABLE => true,
            ],
            self::FLD_FOLLOWUP_ORDER_CREATED_STATUS     => [
                self::LABEL                         => 'Order Created', // _('Order Created')
                self::TYPE                          => self::TYPE_KEY_FIELD,
                self::NAME                          => Sales_Config::DOCUMENT_FOLLOWUP_STATUS,
                self::SHY                           => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_FOLLOWUP_ORDER_BOOKED_STATUS     => [
                self::LABEL                         => 'Order Booked', // _('Order Booked')
                self::TYPE                          => self::TYPE_KEY_FIELD,
                self::NAME                          => Sales_Config::DOCUMENT_FOLLOWUP_STATUS,
                self::SHY                           => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
        ]);
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    protected static string $_statusField = self::FLD_OFFER_STATUS;
    protected static string $_statusConfigKey = Sales_Config::DOCUMENT_OFFER_STATUS;
    protected static string $_documentNumberPrefix = 'OF-'; // _('OF-')
    protected static array $_followupCreatedStatusFields = [
        self::FLD_FOLLOWUP_ORDER_CREATED_STATUS => [
            self::MODEL_NAME => Sales_Model_Document_Order::class,
        ],
    ];
    protected static array $_followupBookedStatusFields = [
        self::FLD_FOLLOWUP_ORDER_BOOKED_STATUS => [
            self::MODEL_NAME => Sales_Model_Document_Order::class,
        ],
    ];
}

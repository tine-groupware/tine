<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Attached Document Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_AttachedDocument extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Document_AttachedDocument';
    public const TABLE_NAME = 'sales_document_attached_document';

    public const TYPE_PAPERSLIP = 'paperslip';
    public const TYPE_UBL = 'ubl';
    public const TYPE_SUPPORTING_DOCUMENT = 'supporting_document';


    public const FLD_DELIVERY_ID = 'delivery_id';
    public const FLD_INVOICE_ID = 'invoice_id';
    public const FLD_OFFER_ID = 'offer_id';
    public const FLD_ORDER_ID = 'order_id';
    public const FLD_TYPE = 'type';
    public const FLD_NODE_ID = 'node_id';
    public const FLD_CREATED_FOR_SEQ = 'created_for_seq';
    public const FLD_DISPATCH_HISTORY = 'dispatch_history';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                       => 1,
        self::MODLOG_ACTIVE                 => true,
        self::IS_DEPENDENT                  => true,

        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::RECORD_NAME => 'Attached Document', // gettext('GENDER_Attached Document')
        self::RECORDS_NAME => 'Attached Documents', // ngettext('Attached Document', 'Attached Documents', n)

        self::TITLE_PROPERTY                => self::FLD_TYPE,

        self::EXPOSE_JSON_API               => true,

        self::TABLE                         => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_DELIVERY_ID           => [
                    self::COLUMNS                   => [self::FLD_DELIVERY_ID],
                ],
                self::FLD_INVOICE_ID            => [
                    self::COLUMNS                   => [self::FLD_INVOICE_ID],
                ],
                self::FLD_OFFER_ID              => [
                    self::COLUMNS                   => [self::FLD_OFFER_ID],
                ],
                self::FLD_ORDER_ID              => [
                    self::COLUMNS                   => [self::FLD_ORDER_ID],
                ],
                self::FLD_NODE_ID               => [
                    self::COLUMNS                   => [self::FLD_NODE_ID],
                ],
            ],
        ],

        self::FIELDS                        => [
            self::FLD_TYPE                      => [
                self::LABEL                         => 'Type', // _('Type')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
            ],
            self::FLD_NODE_ID                   => [
                self::LENGTH                        => 40,
                self::TYPE                          => self::TYPE_STRING, /*self::TYPE_RECORD,
                self::CONFIG                        => [
                    self::APP_NAME                      => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                    => Tinebase_Model_Tree_Node::MODEL_NAME_PART,
                ],*/
                self::UI_CONFIG                 => [
                    self::DISABLED                  => true,
                ],
            ],
            self::FLD_CREATED_FOR_SEQ           => [
                self::LABEL                         => 'Created for Version', // _('Created for Version')
                self::TYPE                          => self::TYPE_INTEGER,
            ],
            self::FLD_DELIVERY_ID               => [
                self::TYPE                          => self::TYPE_RECORD,
                self::NULLABLE                      => true,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_Document_Delivery::MODEL_NAME_PART,
                    self::IS_PARENT                     => true,
                ],
                self::UI_CONFIG                 => [
                    self::DISABLED                  => true,
                ],
            ],
            self::FLD_INVOICE_ID                => [
                self::TYPE                          => self::TYPE_RECORD,
                self::NULLABLE                      => true,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_Document_Invoice::MODEL_NAME_PART,
                    self::IS_PARENT                     => true,
                ],
                self::UI_CONFIG                 => [
                    self::DISABLED                  => true,
                ],
            ],
            self::FLD_OFFER_ID                  => [
                self::TYPE                          => self::TYPE_RECORD,
                self::NULLABLE                      => true,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_Document_Offer::MODEL_NAME_PART,
                    self::IS_PARENT                     => true,
                ],
                self::UI_CONFIG                 => [
                    self::DISABLED                  => true,
                ],
            ],
            self::FLD_ORDER_ID                  => [
                self::TYPE                          => self::TYPE_RECORD,
                self::NULLABLE                      => true,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_Document_Order::MODEL_NAME_PART,
                    self::IS_PARENT                     => true,
                ],
                self::UI_CONFIG                 => [
                    self::DISABLED                  => true,
                ],
            ],
            self::FLD_DISPATCH_HISTORY          => [
                self::LABEL                         => 'Dispatch History', // _('Dispatch History')
                self::TYPE                          => self::TYPE_RECORDS,
                self::CONFIG                        => [
                    self::DEPENDENT_RECORDS             => true,
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_Document_DispatchHistory::MODEL_NAME_PART,
                    self::REF_ID_FIELD                  => Sales_Model_Document_DispatchHistory::FLD_ATTACHED_DOCUMENT_ID,
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

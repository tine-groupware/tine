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
    public const TYPE_EDOCUMENT = 'edocument';
    public const TYPE_SUPPORTING_DOCUMENT = 'supporting_document';


    public const FLD_DOCUMENT_ID = 'document_id';
    public const FLD_DOCUMENT_TYPE = 'document_type';
    public const FLD_TYPE = 'type';
    public const FLD_NODE_ID = 'node_id';
    public const FLD_CREATED_FOR_SEQ = 'created_for_seq';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                       => 2,
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
                self::FLD_DOCUMENT_ID           => [
                    self::COLUMNS                   => [self::FLD_DOCUMENT_ID],
                ],
            ],
        ],

        self::FIELDS                        => [
            self::FLD_TYPE                      => [
                self::LABEL                         => 'Type', // _('Type')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_NODE_ID                   => [
                self::LENGTH                        => 40,
                self::TYPE                          => self::TYPE_STRING, /*self::TYPE_RECORD,
                self::CONFIG                        => [
                    self::APP_NAME                      => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                    => Tinebase_Model_Tree_Node::MODEL_NAME_PART,
                ],*/
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::UI_CONFIG                     => [
                    self::DISABLED                      => true,
                ],
            ],
            self::FLD_CREATED_FOR_SEQ           => [
                self::LABEL                         => 'Created for Version', // _('Created for Version')
                self::TYPE                          => self::TYPE_INTEGER,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_DOCUMENT_ID               => [
                self::TYPE                          => self::TYPE_DYNAMIC_RECORD,
                self::LENGTH                        => 40,
                self::CONFIG                        => [
                    self::REF_MODEL_FIELD               => self::FLD_DOCUMENT_TYPE,
                    self::PERSISTENT                    => Tinebase_Model_Converter_DynamicRecord::REFID,
                    self::IS_PARENT                     => true,
                ],
                self::FILTER_DEFINITION             => [
                    self::FILTER                        => Tinebase_Model_Filter_Id::class,
                ],
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::UI_CONFIG                     => [
                    self::DISABLED                      => true,
                ],
            ],
            self::FLD_DOCUMENT_TYPE             => [
                self::TYPE                          => self::TYPE_MODEL,
                self::CONFIG                        => [
                    self::AVAILABLE_MODELS              => [
                        Sales_Model_Document_Delivery::class,
                        Sales_Model_Document_Invoice::class,
                        Sales_Model_Document_Offer::class,
                        Sales_Model_Document_Order::class,
                    ],
                ],
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                    [Zend_Validate_InArray::class, [
                        Sales_Model_Document_Delivery::class,
                        Sales_Model_Document_Invoice::class,
                        Sales_Model_Document_Offer::class,
                        Sales_Model_Document_Order::class,
                    ]],
                ],
                self::UI_CONFIG                     => [
                    self::DISABLED                      => true,
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

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
 * DispatchHistory Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_DispatchHistory extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Document_DispatchHistory';
    public const TABLE_NAME = 'sales_document_dispatch_history';

    public const DH_TYPE_START = 'start';
    public const DH_TYPE_WAIT_FOR_FEEDBACK = 'waitForFeedback';
    public const DH_TYPE_SUCCESS = 'success';
    public const DH_TYPE_FAIL = 'fail';

    public const FLD_DISPATCH_ID = 'dispatch_id';
    public const FLD_PARENT_DISPATCH_ID = 'parent_dispatch_id';
    public const FLD_DISPATCH_PROCESS = 'dispatch_process';
    public const FLD_TYPE = 'type';
    public const FLD_DOCUMENT_ID = 'document_id';
    public const FLD_DOCUMENT_TYPE = 'document_type';
    public const FLD_DISPATCH_DATE = 'dispatch_date';
    public const FLD_DISPATCH_TRANSPORT = 'dispatch_transport';
    public const FLD_DISPATCH_REPORT = 'dispatch_report';
    public const FLD_DISPATCH_CONFIG = 'dispatch_config';
    public const FLD_FEEDBACK_RECEIVED = 'feedback_received';


    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                       => 6,
        self::MODLOG_ACTIVE                 => true,
        self::IS_DEPENDENT                  => true,
        self::HAS_ATTACHMENTS               => true,
        self::HAS_XPROPS                    => true,
        self::EXPOSE_JSON_API               => true,
        self::CREATE_MODULE                 => true,

        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::TITLE_PROPERTY                => "{{ dispatch_date |localizeddate('short', 'none', app.request.locale) }} - {{ dispatch_transport }}",
        self::DEFAULT_SORT_INFO             => [self::FIELD => self::FLD_DISPATCH_DATE],

        self::RECORD_NAME => 'Dispatch Report', // gettext('GENDER_Dispatch Report')
        self::RECORDS_NAME => 'Dispatch Reports', // ngettext('Dispatch Report', 'Dispatch Reports', n)

        self::TABLE                         => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_DOCUMENT_ID           => [
                    self::COLUMNS                   => [self::FLD_DOCUMENT_ID],
                ],
                self::FLD_TYPE                  => [
                    self::COLUMNS                   => [self::FLD_TYPE, self::FLD_FEEDBACK_RECEIVED],
                ],
            ],
        ],

        self::FIELDS                        => [
            self::FLD_DISPATCH_PROCESS          => [
                self::TYPE                          => self::TYPE_VIRTUAL,
                self::DOCTRINE_IGNORE               => true,
                self::CONFIG                        => [
                    self::TYPE                          => self::TYPE_STRING,
                    self::LABEL                         => 'Dispatch Process', // _('Dispatch Process')
                ],
                self::UI_CONFIG                     => [
                    self::DISABLED                      => true,
                ],

            ],
            self::FLD_DISPATCH_DATE             => [
                self::LABEL                         => 'Date', //_('Date')
                self::TYPE                          => self::TYPE_DATETIME,
                self::NULLABLE                      => false,
                self::UI_CONFIG => [
                    self::READ_ONLY                     => true,
                    'format'                            => ['medium'],
                ],

            ],
            self::FLD_DISPATCH_TRANSPORT        => [
                self::TYPE                          => self::TYPE_MODEL,
                self::LABEL                         => 'Transport Method', // _('Transport Method')
                self::CONFIG                        => [
                    self::AVAILABLE_MODELS              => [
                        Sales_Model_EDocument_Dispatch_Custom::class,
                        Sales_Model_EDocument_Dispatch_Email::class,
                        Sales_Model_EDocument_Dispatch_Manual::class,
                        Sales_Model_EDocument_Dispatch_Upload::class,
                    ],
                ],
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_DISPATCH_REPORT           => [
                self::LABEL                         => 'Report', //_('Report')
                self::TYPE                          => self::TYPE_TEXT,
                self::NULLABLE                      => true,
                self::QUERY_FILTER                  => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
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
            self::FLD_TYPE                      => [
                self::LABEL                         => 'Process Step', //_('Process Step')
                self::TYPE                          => self::TYPE_KEY_FIELD,
                self::NAME                          => Sales_Config::DISPATCH_HISTORY_TYPES,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                    [Zend_Validate_InArray::class, [
                        self::DH_TYPE_START,
                        self::DH_TYPE_WAIT_FOR_FEEDBACK,
                        self::DH_TYPE_SUCCESS,
                        self::DH_TYPE_FAIL,
                    ]],
                ],
                self::UI_CONFIG                     => [
                    self::READ_ONLY                      => true,
                ],
            ],
            self::FLD_DISPATCH_ID               => [
                self::TYPE                          => self::TYPE_STRING,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::UI_CONFIG                     => [
                    self::DISABLED                      => true,
                ],
            ],
            self::FLD_PARENT_DISPATCH_ID        => [
                self::TYPE                          => self::TYPE_STRING,
                self::NULLABLE                      => true,
                self::UI_CONFIG                     => [
                    self::DISABLED                      => true,
                ],
            ],
            self::FLD_DISPATCH_CONFIG           => [
                self::LABEL                         => 'Electronic Document Transport Config', // _('Electronic Document Transport Config')
                self::TYPE                          => self::TYPE_DYNAMIC_RECORD,
                self::NULLABLE                      => true,
                self::CONFIG                        => [
                    self::REF_MODEL_FIELD               => self::FLD_DISPATCH_TRANSPORT,
                    self::PERSISTENT                    => true,
                ],
            ],
            self::FLD_FEEDBACK_RECEIVED         => [
                self::LABEL                         => 'Feedback received', // _('Feedback received')
                self::TYPE                          => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL                   => false,
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

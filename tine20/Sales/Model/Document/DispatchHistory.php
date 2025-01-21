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


    public const FLD_ATTACHED_DOCUMENT_ID = 'attached_document_id';
    public const FLD_DISPATCH_DATE = 'dispatch_date';
    public const FLD_DISPATCH_TRANSPORT = 'dispatch_transport';
    public const FLD_DISPATCH_REPORT = 'dispatch_report';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                       => 2,
        self::MODLOG_ACTIVE                 => true,
        self::IS_DEPENDENT                  => true,
        self::HAS_ATTACHMENTS               => true,

        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::TITLE_PROPERTY                => "{{ dispatch_date |localizeddate('short', 'none', app.request.locale) }} - {{ dispatch_transport }}",
        self::DEFAULT_SORT_INFO             => [self::FIELD => self::FLD_DISPATCH_DATE],

        self::RECORD_NAME => 'Dispatch Report', // gettext('GENDER_Dispatch Report')
        self::RECORDS_NAME => 'Dispatch Reports', // ngettext('Dispatch Report', 'Dispatch Reports', n)

        self::TABLE                         => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_ATTACHED_DOCUMENT_ID  => [
                    self::COLUMNS                   => [self::FLD_ATTACHED_DOCUMENT_ID],
                ],
            ],
        ],

        self::FIELDS                        => [
            self::FLD_DISPATCH_DATE             => [
                self::LABEL                         => 'Dispatch Date', //_('Dispatch Date')
                self::TYPE                          => self::TYPE_DATE,
                self::NULLABLE                      => false,
                self::UI_CONFIG => [
                    'format' => ['medium'],
                ],
            ],
            self::FLD_DISPATCH_TRANSPORT        => [
                self::TYPE                      => self::TYPE_MODEL,
                self::LABEL                     => 'Dispatch Transport Method', // _('Dispatch Transport Method')
                self::CONFIG                    => [
                    self::AVAILABLE_MODELS          => [
                        Sales_Model_EDocument_Dispatch_Custom::class,
                        Sales_Model_EDocument_Dispatch_Email::class,
                        Sales_Model_EDocument_Dispatch_Manual::class,
                        Sales_Model_EDocument_Dispatch_Upload::class,
                    ],
                ],
            ],
            self::FLD_DISPATCH_REPORT           => [
                self::LABEL                         => 'Dispatch Report', //_('Dispatch Report')
                self::TYPE                          => self::TYPE_TEXT,
                self::NULLABLE                      => true,
                self::QUERY_FILTER                  => true,
            ],
            self::FLD_ATTACHED_DOCUMENT_ID      => [
                self::TYPE                          => self::TYPE_RECORD,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_Document_AttachedDocument::MODEL_NAME_PART,
                    self::IS_PARENT                     => true,
                ],
                self::UI_CONFIG                 => [
                    self::DISABLED                  => true,
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

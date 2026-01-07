<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * PaymentReminder Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_PaymentReminder extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Document_PaymentReminder';
    public const TABLE_NAME = 'sales_document_payment_reminder';

    public const FLD_DOCUMENT_ID = 'document_id';
    public const FLD_DOCUMENT_TYPE = 'document_type';
    public const FLD_DATE = 'date';
    public const FLD_FEE = 'fee';
    public const FLD_OUTSTANDING_AMOUNT = 'outstanding_amount';
    public const FLD_LEVEL = 'level';


    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                       => 1,
        self::MODLOG_ACTIVE                 => true,
        self::IS_DEPENDENT                  => true,

        self::TITLE_PROPERTY                => "{{ date |localizeddate('short', 'none', app.request.locale) }} - {{ outstanding_amount }}",

        //self::EXPOSE_JSON_API               => true,
        //self::CREATE_MODULE                 => true,

        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::RECORD_NAME                   => 'Payment Reminder', // gettext('GENDER_Payment Reminder')
        self::RECORDS_NAME                  => 'Payment Reminders', // ngettext('Payment Reminder', 'Payment Reminders', n)

        self::TABLE                         => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_DOCUMENT_ID           => [
                    self::COLUMNS                   => [self::FLD_DOCUMENT_ID],
                ],
            ],
        ],

        self::FIELDS                        => [
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
                        Sales_Model_Document_Invoice::class,
                        Sales_Model_Document_PurchaseInvoice::class,
                    ],
                ],
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                    [Zend_Validate_InArray::class, [
                        Sales_Model_Document_Invoice::class,
                        Sales_Model_Document_PurchaseInvoice::class,
                    ]],
                ],
                self::UI_CONFIG                     => [
                    self::DISABLED                      => true,
                ],
            ],
            self::FLD_DATE                      => [
                self::TYPE                          => self::TYPE_DATE,
                self::LABEL                         => 'Date', // _('Date')
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_FEE                       => [
                self::TYPE                          => self::TYPE_MONEY,
                self::LABEL                         => 'Fee', // _('Fee')
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_LEVEL                     => [
                self::TYPE                          => self::TYPE_KEY_FIELD,
                self::LABEL                         => 'Level', // _('Level')
                self::NAME                          => Sales_Config::PAYMENT_REMINDER_LEVEL,
            ],
            self::FLD_OUTSTANDING_AMOUNT        => [
                self::TYPE                          => self::TYPE_MONEY,
                self::LABEL                         => 'Outstanding amount', // _('Outstanding amount')
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
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

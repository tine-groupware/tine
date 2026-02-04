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
 * SalesTax Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_SalesTax extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Document_SalesTax';
    public const TABLE_NAME = 'sales_document_sales_tax';

    public const FLD_DOCUMENT_ID = 'document_id';
    public const FLD_DOCUMENT_TYPE = 'document_type';
    public const FLD_GROSS_AMOUNT = 'gross_amount';
    public const FLD_NET_AMOUNT = 'net_amount';
    public const FLD_TAX_AMOUNT = 'tax_amount';
    public const FLD_TAX_RATE = 'tax_rate';



    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                       => 1,
        self::MODLOG_ACTIVE                 => true,
        self::IS_DEPENDENT                  => true,
        self::HAS_DELETED_TIME_UNIQUE       => true,

        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::RECORD_NAME                   => 'Sales Tax', // gettext('GENDER_Sales Tax')
        self::RECORDS_NAME                  => 'Sales Taxes', // ngettext('Sales Tax', 'Sales Taxes', n)

        self::TABLE                         => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_DOCUMENT_ID           => [
                    self::COLUMNS                   => [self::FLD_DOCUMENT_ID],
                ],
            ],
            self::UNIQUE_CONSTRAINTS        => [
                self::FLD_TAX_RATE              => [
                    self::COLUMNS                   => [self::FLD_DOCUMENT_TYPE, self::FLD_DOCUMENT_ID, self::FLD_TAX_RATE, self::FLD_DELETED_TIME],
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
                        Sales_Model_Document_Offer::class,
                        Sales_Model_Document_Order::class,
                        Sales_Model_Document_PurchaseInvoice::class,
                    ],
                ],
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                    [Zend_Validate_InArray::class, [
                        Sales_Model_Document_Invoice::class,
                        Sales_Model_Document_Offer::class,
                        Sales_Model_Document_Order::class,
                        Sales_Model_Document_PurchaseInvoice::class,
                    ]],
                ],
                self::UI_CONFIG                     => [
                    self::DISABLED                      => true,
                ],
            ],
            self::FLD_GROSS_AMOUNT              => [
                self::LABEL                         => 'Gross amount', // _('Gross amount')
                self::TYPE                          => self::TYPE_MONEY,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::UI_CONFIG                     => [
//                    self::READ_ONLY                     => true,
                    self::COLUMN_CONFIG                 => [
                        'width'                             => 100
                    ],
                ],
            ],
            self::FLD_NET_AMOUNT                => [
                self::LABEL                         => 'Net amount', // _('Net amount')
                self::TYPE                          => self::TYPE_MONEY,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::UI_CONFIG                     => [
//                    self::READ_ONLY                     => true,
                    self::COLUMN_CONFIG                 => [
                        'width'                             => 100
                    ],
                ],
            ],
            self::FLD_TAX_AMOUNT                => [
                self::LABEL                         => 'Tax', // _('Tax')
                self::TYPE                          => self::TYPE_MONEY,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::UI_CONFIG                     => [
//                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_TAX_RATE                  => [
                self::LABEL                         => 'Tax Rate', // _('Tax Rate')
                self::TYPE                          => self::TYPE_FLOAT,
                self::SPECIAL_TYPE                  => self::SPECIAL_TYPE_PERCENT,
                self::INPUT_FILTERS                 => [
                    Tinebase_Record_Filter_NumericFloat::class => [-1]
                ],
                self::VALIDATORS                    => [
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                    [Tinebase_Record_Validator_GreaterOrEqualThan::class, 0.0],
                ],
                self::UI_CONFIG                     => [
//                    self::READ_ONLY                     => true,
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

    protected static function _getFilter($field = null)
    {
        if (null === $field || self::FLD_TAX_RATE === $field) {
            $mc = self::getConfiguration();
            $orgValidators = $validators = $mc->validators;
            $refProp = new ReflectionProperty($mc, '_validators');
            $refProp->setAccessible(true);
            $validators[self::FLD_TAX_RATE][] = new Zend_Validate_NotEmpty(0);
            try {
                $refProp->setValue($mc, $validators);
                return parent::_getFilter($field);
            } finally {
                $refProp->setValue($mc, $orgValidators);
            }
        }

        return parent::_getFilter($field);
    }
}
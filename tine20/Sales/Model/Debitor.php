<?php declare(strict_types=1);
/**
 * class to hold Debitor Number data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * class to hold Debitor Number data
 *
 * @package     Sales
 */
class Sales_Model_Debitor extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART    = 'Debitor';
    public const TABLE_NAME         = 'sales_debitor';

    public const FLD_BILLING        = 'billing';

    public const FLD_BUYER_LEGAL_REGISTRATION_IDENTIFIER = 'buyer_legal_registration_identifier';
    public const FLD_VAT_IDENTIFIER = 'buyer_vat_identifier';
    public const FLD_BUYER_REFERENCE = 'buyer_reference';
    public const FLD_SELLER_IDENTIFIER = 'seller_identifier';

    public const FLD_CUSTOMER_ID    = 'customer_id';
    public const FLD_DELIVERY       = 'delivery';
    public const FLD_DIVISION_ID    = 'division_id';
    public const FLD_NUMBER         = 'number';
    public const FLD_NAME           = 'name';
    public const FLD_DESCRIPTION    = 'description';
    public const FLD_EINVOICE_TYPE  = 'einvoice_type';
    public const FLD_EINVOICE_CONFIG= 'einvoice_config';
    public const FLD_EAS_ID = 'eas_id';
    public const FLD_ELECTRONIC_ADDRESS = 'electronic_address';
    public const FLD_EDOCUMENT_DISPATCH_TYPE = 'edocument_dispatch_type';
    public const FLD_EDOCUMENT_DISPATCH_CONFIG = 'edocument_dispatch_config';

    public const FLD_PAYMENT_MEANS = 'payment_means';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 5,
        self::APP_NAME                  => Sales_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::RECORD_NAME               => 'Debitor', // gettext('GENDER_Debitor')
        self::RECORDS_NAME              => 'Debitors', // ngettext('Debitor', 'Debitors', n)
        self::MODLOG_ACTIVE             => true,
        self::HAS_DELETED_TIME_UNIQUE   => true,
        self::CREATE_MODULE             => false,
        self::EXPOSE_JSON_API           => true,
        self::EXPOSE_HTTP_API           => true,
        self::IS_DEPENDENT              => true,
        self::TITLE_PROPERTY            => "{{ number }} {{ name }}{% if division_id.title %} ({{ division_id.title }}){% endif %}",
        self::DEFAULT_SORT_INFO         => [self::FIELD => self::FLD_NUMBER],
        self::CONTAINER_PROPERTY        => null,
        self::DELEGATED_ACL_FIELD       => self::FLD_DIVISION_ID,
//        generic export is not so useful atm.
//        self::EXPORT                    => [
//            self::SUPPORTED_FORMATS         => ['csv'],
//        ],

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_CUSTOMER_ID           => [
                    self::COLUMNS                   => [self::FLD_CUSTOMER_ID],
                ]
            ],
            self::UNIQUE_CONSTRAINTS        => [
                self::FLD_NUMBER                 => [
                    self::COLUMNS                   => [self::FLD_DIVISION_ID, self::FLD_NUMBER, self::FLD_DELETED_TIME],
                ],
            ],
        ],

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_CUSTOMER_ID   => [],
                self::FLD_DELIVERY      => [],
                self::FLD_BILLING       => [],
                self::FLD_PAYMENT_MEANS => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Sales_Model_PaymentMeans::FLD_PAYMENT_MEANS_CODE => [],
                    ],
                ],
            ],
        ],

        self::FIELDS                    => [
            self::FLD_NUMBER                 => [
                self::LABEL                     => 'Number', // _('Number')
                self::TYPE                      => self::TYPE_NUMBERABLE_STRING,
                self::QUERY_FILTER              => true,
                self::CONFIG                    => [
                    Tinebase_Numberable::STEPSIZE          => 1,
                    Tinebase_Numberable_String::PREFIX     => 'DEB-',
                    Tinebase_Numberable_String::ZEROFILL   => 0,
                    Tinebase_Model_NumberableConfig::NO_AUTOCREATE => true,
                    Tinebase_Numberable::CONFIG_OVERRIDE   => Sales_Controller_Debitor::class . '::numberConfigOverride',
                ]
            ],
            self::FLD_NAME              => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::QUERY_FILTER          => true,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => true,
                    Zend_Filter_Input::DEFAULT_VALUE => '-',
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::LABEL                 => 'Name', // _('Name')
            ],
            self::FLD_DESCRIPTION               => [
                self::LABEL                         => 'Description', // _('Description')
                self::TYPE                          => self::TYPE_FULLTEXT,
                self::NULLABLE                      => true,
                self::SHY                           => true,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                ],
            ],
            self::FLD_CUSTOMER_ID           => [
                self::TYPE                      => self::TYPE_RECORD,
                self::LABEL                     => 'Customer', // _('Customer')
                self::CONFIG                    => [
                    self::APP_NAME                  => Sales_Config::APP_NAME,
                    self::MODEL_NAME                => Sales_Model_Customer::MODEL_NAME_PART,
                    self::IS_PARENT                 => true,
                ],
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_DIVISION_ID            => [
                self::TYPE                      => self::TYPE_RECORD,
                self::LABEL                     => 'Division', // _('Division')
                self::CONFIG                    => [
                    self::APP_NAME                  => Sales_Config::APP_NAME,
                    self::MODEL_NAME                => Sales_Model_Division::MODEL_NAME_PART,
                ],
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_DELIVERY              => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::LABEL                     => 'Delivery Addresses', // _('Delivery Addresses')
                self::CONFIG                    => [
                    self::APP_NAME                  => Sales_Config::APP_NAME,
                    self::MODEL_NAME                => Sales_Model_Address::MODEL_NAME_PART,
                    self::REF_ID_FIELD              => Sales_Model_Address::FLD_DEBITOR_ID,
                    self::ADD_FILTERS               => [[TMFA::FIELD => Sales_Model_Address::FLD_TYPE, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_Address::TYPE_DELIVERY]],
                    self::PAGING                    => [Tinebase_Model_Pagination::FLD_SORT => Sales_Model_Address::FLD_LOCALITY, Tinebase_Model_Pagination::FLD_DIR => 'ASC'],
                    self::DEPENDENT_RECORDS         => true,
                    self::FORCE_VALUES              => [
                        Sales_Model_Address::FLD_TYPE                   => Sales_Model_Address::TYPE_DELIVERY,
                        Sales_Model_Address::FLD_CUSTOMER_ID            => null,
                    ],
                ],
                self::UI_CONFIG                     => [
                    'plugins'                           => ['sales.address.to-clipboard'],
                    'editDialogConfig'              => [
                        'fixedFields'                   => [
                            Sales_Model_Address::FLD_TYPE   => Sales_Model_Address::TYPE_DELIVERY,
                        ]
                    ],
                ],
            ],
            self::FLD_BILLING               => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::LABEL                     => 'Billing Addresses', // _('Billing Addresses')
                self::CONFIG                    => [
                    self::APP_NAME                  => Sales_Config::APP_NAME,
                    self::MODEL_NAME                => Sales_Model_Address::MODEL_NAME_PART,
                    self::REF_ID_FIELD              => Sales_Model_Address::FLD_DEBITOR_ID,
                    self::ADD_FILTERS               => [[TMFA::FIELD => Sales_Model_Address::FLD_TYPE, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => Sales_Model_Address::TYPE_BILLING]],
                    self::PAGING                    => [Tinebase_Model_Pagination::FLD_SORT => Sales_Model_Address::FLD_LOCALITY, Tinebase_Model_Pagination::FLD_DIR => 'ASC'],
                    self::DEPENDENT_RECORDS         => true,
                    self::FORCE_VALUES              => [
                        Sales_Model_Address::FLD_TYPE                   => Sales_Model_Address::TYPE_BILLING,
                        Sales_Model_Address::FLD_CUSTOMER_ID            => null,
                    ],
                    // we need the billing address on search in the contract-customer combo to automatically set the first billing address
                    'omitOnSearch'     => false, // is that a thing?
                ],
                self::UI_CONFIG                     => [
                    'plugins'                           => ['sales.address.to-clipboard'],
                    'editDialogConfig'              => [
                        'fixedFields'                   => [
                            Sales_Model_Address::FLD_TYPE   => Sales_Model_Address::TYPE_BILLING,
                        ]
                    ],
                ],
            ],
            self::FLD_BUYER_LEGAL_REGISTRATION_IDENTIFIER  => [
                self::TYPE                      => self::TYPE_STRING,
                self::LABEL                     => 'Buyer legal registration identifier', // _('Buyer legal registration identifier')
                self::DESCRIPTION               => 'An identifier issued by an official registrar that identifies the acquirer as a legal entity or legal person. E.g. commercial register entry, register of associations, etc. (BT-47 [EN 16931]).', // _('An identifier issued by an official registrar that identifies the acquirer as a legal entity or legal person. E.g. commercial register entry, register of associations, etc. (BT-47 [EN 16931]).')
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
            ],
            self::FLD_VAT_IDENTIFIER        => [
                self::TYPE                      => self::TYPE_STRING,
                self::LABEL                     => 'Buyer VAT identifier', // _('Buyer VAT identifier')
                self::DESCRIPTION               => 'The VAT identification number preceded by a country prefix (BT-48 [EN 16931]).', // _('The VAT identification number preceded by a country prefix (BT-48 [EN 16931]).')
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
            ],
            self::FLD_BUYER_REFERENCE       => [
                self::TYPE                      => self::TYPE_STRING,
                self::LABEL                     => 'Buyer Reference', // _('Buyer Reference')
                self::DESCRIPTION               => 'An identifier assigned by the acquirer and used for internal control purposes (BT-10 [EN 16931]).', // _('An identifier assigned by the acquirer and used for internal control purposes (BT-10 [EN 16931]).')
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
            ],
            self::FLD_SELLER_IDENTIFIER       => [
                self::TYPE                      => self::TYPE_STRING,
                self::LABEL                     => 'Seller identifier', // _('Seller identifier')
                self::DESCRIPTION               => 'An identifier (usually assigned by the purchaser) of the seller, such as the vendor number for the funds management procedure or the supplier number for the ordering system (BT-29 [EN 16931]).', //_('An identifier (usually assigned by the purchaser) of the seller, such as the vendor number for the funds management procedure or the supplier number for the ordering system (BT-29 [EN 16931]).')
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
            ],
            self::FLD_EINVOICE_TYPE         => [
                self::LABEL                     => 'Electronic Invoice Type', // _('Electronic Invoice Type')
                self::TYPE                      => self::TYPE_MODEL,
                self::DEFAULT_VAL               => Sales_Model_Einvoice_XRechnung::class,
                self::CONFIG                    => [
                    self::AVAILABLE_MODELS          => [
                        Sales_Model_Einvoice_XRechnung::class,
                    ],
                ],
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Empty::class => Sales_Model_Einvoice_XRechnung::class,
                    Zend_Filter_Input::DEFAULT_VALUE => Sales_Model_Einvoice_XRechnung::class,
                    [Zend_Validate_InArray::class, [
                        Sales_Model_Einvoice_XRechnung::class,
                    ]],
                ],
            ],
            self::FLD_EINVOICE_CONFIG       => [
                self::LABEL                     => 'Electronic Invoice Config', // _('Electronic Invoice Config')
                self::TYPE                      => self::TYPE_DYNAMIC_RECORD,
                self::DEFAULT_VAL               => '[]',
                self::CONFIG                    => [
                    self::REF_MODEL_FIELD           => self::FLD_EINVOICE_TYPE,
                    self::PERSISTENT                => true,
                ],
                self::INPUT_FILTERS         => [
                    Zend_Filter_Empty::class => [[]],
                ],
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => [[]],
                    [Tinebase_Record_Validator_SubValidate::class, [Tinebase_Record_Validator_SubValidate::IGNORE_VALUE => []]],
                ],
            ],
            self::FLD_EAS_ID                => [
                self::TYPE                      => self::TYPE_RECORD,
                self::LABEL                     => 'Electronic Address Schema', // _('Electronic Address Schema')
                self::DESCRIPTION               => "The pattern for 'Buyer electronic address (BT-49 [EN 16931]).", //_("The pattern for 'Buyer electronic address (BT-49 [EN 16931]).")
                self::NULLABLE                  => true,
                self::CONFIG                    => [
                    self::APP_NAME                  => Sales_Config::APP_NAME,
                    self::MODEL_NAME                => Sales_Model_EDocument_EAS::MODEL_NAME_PART,
                ],
            ],
            self::FLD_ELECTRONIC_ADDRESS    => [
                self::TYPE                      => self::TYPE_STRING,
                self::LABEL                     => 'Electronic Address', // _('Electronic Address')
                self::DESCRIPTION               => 'Specifies an electronic address of the purchaser to which an invoice should be sent (BT-49 [EN 16931]).', //_('Specifies an electronic address of the purchaser to which an invoice should be sent (BT-49 [EN 16931]).')
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
            ],
            self::FLD_EDOCUMENT_DISPATCH_TYPE => [
                self::TYPE                      => self::TYPE_MODEL,
                self::LABEL                     => 'Electronic Document Transport Method', // _('Electronic Document Transport Method')
                self::DEFAULT_VAL               => Sales_Model_EDocument_Dispatch_Email::class,
                self::CONFIG                    => [
                    self::DEFAULT_FROM_CONFIG       => [
                        self::APP_NAME                  => Sales_Config::APP_NAME,
                        self::CONFIG                    => Sales_Config::DEFAULT_DEBITOR_EDOCUMENT_DISPATCH_TYPE,
                    ],
                    self::AVAILABLE_MODELS          => [
                        Sales_Model_EDocument_Dispatch_Custom::class,
                        Sales_Model_EDocument_Dispatch_Email::class,
                        Sales_Model_EDocument_Dispatch_Manual::class,
                        Sales_Model_EDocument_Dispatch_Upload::class,
                    ],
                ],
                self::VALIDATORS                => [
                    [Zend_Validate_InArray::class, [
                        Sales_Model_EDocument_Dispatch_Custom::class,
                        Sales_Model_EDocument_Dispatch_Email::class,
                        Sales_Model_EDocument_Dispatch_Manual::class,
                        Sales_Model_EDocument_Dispatch_Upload::class,
                    ]],
                ],
                self::UI_CONFIG                     => [
                    'includeAppName'                    => false,
                    'useRecordName'                     => true,
                ],
            ],
            self::FLD_EDOCUMENT_DISPATCH_CONFIG=> [
                self::LABEL                     => 'Electronic Document Transport Config', // _('Electronic Document Transport Config')
                self::TYPE                      => self::TYPE_DYNAMIC_RECORD,
                self::CONFIG                    => [
                    self::REF_MODEL_FIELD           => self::FLD_EDOCUMENT_DISPATCH_TYPE,
                    self::PERSISTENT                => true,
                    self::SET_DEFAULT_INSTANCE      => true,
                ],
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    [Tinebase_Record_Validator_SubValidate::class],
                ],
            ],
            self::FLD_PAYMENT_MEANS         => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::LABEL                     => 'Payment Means', // _('Payment Means')
                self::CONFIG                    => [
                    self::APP_NAME                  => Sales_Config::APP_NAME,
                    self::MODEL_NAME                => Sales_Model_PaymentMeans::MODEL_NAME_PART,
                    self::STORAGE                   => self::TYPE_JSON,
                ],
                self::VALIDATORS                => [
                    [Sales_Model_Validator_PaymentMeansOneDefault::class],
                ],
                self::UI_CONFIG                 => [
                    'allowDuplicatePicks'           => true,
                    'allowMetadataForEditing'       => false,
                    'searchComboConfig'             => [
                        'useEditPlugin'                 => false,
                    ],
                    'columns'                       => [
                        Sales_Model_PaymentMeans::FLD_PAYMENT_MEANS_CODE,
                        Sales_Model_PaymentMeans::FLD_CONFIG,
                        Sales_Model_PaymentMeans::FLD_DEFAULT,
                    ],
                    // @TODO: define at metadata model????
                    'copyMetadataForProps'          => [
                        Sales_Model_EDocument_PaymentMeansCode::FLD_CONFIG_CLASS,
                    ],
                    self::FIELDS_CONFIG             => [
                        'plugins'                       => [[
                            'ptype'                         => 'tb-grid-one-is-true',
                            'field'                         => Sales_Model_PaymentMeans::FLD_DEFAULT,
                        ]]
                    ]
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

    public function setFromArray(array &$_data)
    {
        if (!isset($_data[self::FLD_PAYMENT_MEANS]) || (is_array($_data[self::FLD_PAYMENT_MEANS]) && empty($_data[self::FLD_PAYMENT_MEANS]))) {
            $pmc = Sales_Controller_EDocument_PaymentMeansCode::getInstance()->get(Sales_Config::getInstance()->{Sales_Config::DEBITOR_DEFAULT_PAYMENT_MEANS});
            if (empty($model = $pmc->{Sales_Model_EDocument_PaymentMeansCode::FLD_CONFIG_CLASS})) {
                throw new Tinebase_Exception_UnexpectedValue('default payment means code configuration needs to have a config class configured');
            }
            $_data[self::FLD_PAYMENT_MEANS] = new Tinebase_Record_RecordSet(Sales_Model_PaymentMeans::class, [
                new Sales_Model_PaymentMeans([
                    Sales_Model_PaymentMeans::FLD_PAYMENT_MEANS_CODE => Sales_Config::getInstance()->{Sales_Config::DEBITOR_DEFAULT_PAYMENT_MEANS},
                    Sales_Model_PaymentMeans::FLD_DEFAULT => true,
                    Sales_Model_PaymentMeans::FLD_CONFIG_CLASS => $model,
                    Sales_Model_PaymentMeans::FLD_CONFIG => new $model,
                ])
            ]);
        } else {
            if (is_array($_data[self::FLD_PAYMENT_MEANS])
                && count($_data[self::FLD_PAYMENT_MEANS]) === 1
                && !$_data[self::FLD_PAYMENT_MEANS][0][Sales_Model_PaymentMeans::FLD_DEFAULT]
            ) {
                $_data[self::FLD_PAYMENT_MEANS][0][Sales_Model_PaymentMeans::FLD_DEFAULT] = true;
            }
        }

        parent::setFromArray($_data);
    }
}

<?php declare(strict_types=1);
/**
 * class to hold Debitor Number data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
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
    public const FLD_CUSTOMER_ID    = 'customer_id';
    public const FLD_DELIVERY       = 'delivery';
    public const FLD_DIVISION_ID    = 'division_id';
    public const FLD_NUMBER         = 'number';
    public const FLD_NAME           = 'name';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 1,
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
            ],
        ],

        self::FIELDS                    => [
            // after creating, the number can't be changed anymore!!!
            self::FLD_NUMBER                 => [
                self::LABEL                     => 'Number', // _('Number')
                self::TYPE                      => self::TYPE_NUMBERABLE_STRING,
                self::QUERY_FILTER              => true,
                self::CONFIG                    => [
                    Tinebase_Numberable::STEPSIZE          => 1,
                    Tinebase_Numberable::BUCKETKEY         => self::class . '#' . self::FLD_NUMBER,
                    Tinebase_Numberable_String::PREFIX     => 'DEB-',
                    Tinebase_Numberable_String::ZEROFILL   => 0,
                    Tinebase_Model_NumberableConfig::NO_AUTOCREATE => true,
                    Tinebase_Numberable::CONFIG_OVERRIDE   => Sales_Controller_Debitor::class . '::numberConfigOverride',
                ],
                self::UI_CONFIG                 => [
                    self::READ_ONLY                         => true,
                ],
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
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}

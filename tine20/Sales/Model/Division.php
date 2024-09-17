<?php declare(strict_types=1);
/**
 * class to hold Division data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Division data
 *
 * @package     Sales
 */
class Sales_Model_Division extends Tinebase_Record_NewAbstract implements Tinebase_Container_NotReplicable
{
    public const MODEL_NAME_PART    = 'Division';
    public const TABLE_NAME         = 'sales_division';

    public const FLD_TITLE          = 'title';
    public const FLD_NAME           = 'name';
    public const FLD_ADDR_PREFIX1   = 'addr_prefix1';
    public const FLD_ADDR_PREFIX2   = 'addr_prefix2';
    public const FLD_ADDR_PREFIX3   = 'addr_prefix3';
    public const FLD_ADDR_POSTAL    = 'addr_postal';
    public const FLD_ADDR_REGION    = 'addr_region';
    public const FLD_ADDR_LOCALITY  = 'addr_locality';
    public const FLD_ADDR_COUNTRY   = 'addr_country';
    public const FLD_CONTACT_NAME   = 'contact_name';
    public const FLD_CONTACT_EMAIL  = 'contact_email';
    public const FLD_CONTACT_PHONE  = 'contact_phone';
    public const FLD_TAX_REGISTRATION_ID = 'tax_registration_id';
    public const FLD_VAT_NUMBER     = 'vat_number';
    public const FLD_BANK_ACCOUNTS  = 'bank_accounts';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 1,
        self::APP_NAME                  => Sales_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::RECORD_NAME               => 'Division', // gettext('GENDER_Division')
        self::RECORDS_NAME              => 'Divisions', // ngettext('Division', 'Divisions', n)
        self::CONTAINER_NAME            => 'Division',
        self::CONTAINERS_NAME           => 'Divisions',
        self::HAS_RELATIONS             => true,
        self::MODLOG_ACTIVE             => true,
        self::HAS_DELETED_TIME_UNIQUE   => true,
        self::CREATE_MODULE             => true,
        self::EXPOSE_JSON_API           => true,
        self::TITLE_PROPERTY            => self::FLD_TITLE,
        self::EXTENDS_CONTAINER         => self::FLD_CONTAINER_ID,
        self::GRANTS_MODEL              => Sales_Model_DivisionGrants::class,

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_BANK_ACCOUNTS => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Sales_Model_DivisionBankAccount::FLD_BANK_ACCOUNT => [],
                    ],
                ],
            ],
            Tinebase_Record_Expander::EXPANDER_PROPERTY_CLASSES => [
                Tinebase_Record_Expander::PROPERTY_CLASS_GRANTS         => [],
                Tinebase_Record_Expander::PROPERTY_CLASS_ACCOUNT_GRANTS => [],
            ]
        ],

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS        => [
                self::FLD_TITLE                 => [
                    self::COLUMNS                   => [self::FLD_TITLE, self::FLD_DELETED_TIME],
                ],
            ],
        ],

        self::FIELDS                    => [
            self::FLD_TITLE                 => [
                self::LABEL                     => 'Title', // _('Title')
                self::TYPE                      => self::TYPE_STRING,
                self::QUERY_FILTER              => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_NAME                  => [
                self::LABEL                     => 'Name', // _('Name')
                self::TYPE                      => self::TYPE_STRING,
                self::QUERY_FILTER              => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_ADDR_PREFIX1          => [
                self::LABEL                     => 'Address Prefix 1', // _('Address Prefix 1')
                self::TYPE                      => self::TYPE_STRING,
                self::QUERY_FILTER              => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_ADDR_PREFIX2          => [
                self::LABEL                     => 'Address Prefix 2', // _('Address Prefix 2')
                self::TYPE                      => self::TYPE_STRING,
                self::NULLABLE                  => true,
                self::QUERY_FILTER              => true,
            ],
            self::FLD_ADDR_PREFIX3          => [
                self::LABEL                     => 'Address Prefix 3', // _('Address Prefix 3')
                self::TYPE                      => self::TYPE_STRING,
                self::NULLABLE                  => true,
                self::QUERY_FILTER              => true,
            ],
            self::FLD_ADDR_POSTAL           => [
                self::LABEL                     => 'Address Postal Code', // _('Address Postal Code')
                self::TYPE                      => self::TYPE_STRING,
                self::QUERY_FILTER              => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_ADDR_REGION           => [
                self::LABEL                     => 'Address Region', // _('Address Region')
                self::TYPE                      => self::TYPE_STRING,
                self::NULLABLE                  => true,
                self::QUERY_FILTER              => true,
            ],
            self::FLD_ADDR_LOCALITY         => [
                self::LABEL                     => 'Address Locality', // _('Address Locality')
                self::TYPE                      => self::TYPE_STRING,
                self::QUERY_FILTER              => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_ADDR_COUNTRY          => [
                self::LABEL                     => 'Address Country', // _('Address Country')
                self::TYPE                      => self::TYPE_STRING,
                self::QUERY_FILTER              => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_CONTACT_NAME          => [
                self::LABEL                     => 'Contact Name', // _('Contact Name')
                self::TYPE                      => self::TYPE_STRING,
                self::QUERY_FILTER              => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_CONTACT_EMAIL         => [
                self::LABEL                     => 'Contact EMail', // _('Contact EMail')
                self::TYPE                      => self::TYPE_STRING,
                self::QUERY_FILTER              => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_CONTACT_PHONE         => [
                self::LABEL                     => 'Contact Phone', // _('Contact Phone')
                self::TYPE                      => self::TYPE_STRING,
                self::QUERY_FILTER              => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_TAX_REGISTRATION_ID   => [
                self::LABEL                     => 'Tax Registration Id', // _('Tax Registration Id')
                self::TYPE                      => self::TYPE_STRING,
                self::NULLABLE                  => true,
                self::QUERY_FILTER              => true,
            ],
            self::FLD_VAT_NUMBER            => [
                self::LABEL                     => 'VAT Number', // _('VAT Number')
                self::TYPE                      => self::TYPE_STRING,
                self::QUERY_FILTER              => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_BANK_ACCOUNTS         => [
                self::LABEL                     => 'Bank Accounts', // _('Bank Accounts')
                self::TYPE                      => self::TYPE_RECORDS,
                self::CONFIG                    => [
                    self::APP_NAME                  => Sales_Config::APP_NAME,
                    self::MODEL_NAME                => Sales_Model_DivisionBankAccount::MODEL_NAME_PART,
                    self::DEPENDENT_RECORDS         => true,
                    self::REF_ID_FIELD              => \Sales_Model_DivisionBankAccount::FLD_DIVISION,
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

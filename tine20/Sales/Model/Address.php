<?php
/**
 * Tine 2.0

 * @package     Sales
 * @subpackage  Address
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Address data
 *
 * @package     Sales
 * @subpackage  Address
 */
class Sales_Model_Address extends Tinebase_Record_NewAbstract
{
    /**
     * holds the configuration object (must be set in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject;

    public const FLD_CUSTOMER_ID = 'customer_id';
    public const FLD_DEBITOR_ID = 'debitor_id';

    public const FLD_SHORTHAND = 'name_shorthand';
    public const FLD_LANGUAGE = 'language';
    public const FLD_NAME = 'name';
    public const FLD_PREFIX1 = 'prefix1';
    public const FLD_PREFIX2 = 'prefix2';
    public const FLD_PREFIX3 = 'prefix3';
    public const FLD_EMAIL = 'email';
    public const FLD_STREET = 'street';
    public const FLD_POBOX = 'pobox';
    public const FLD_POSTALCODE = 'postalcode';
    public const FLD_LOCALITY = 'locality';
    public const FLD_REGION = 'region';
    public const FLD_COUNTRYNAME = 'countryname';
    public const FLD_CUSTOM1 = 'custom1'; // debit nr - WTF?
    public const FLD_TYPE = 'type';
    public const FLD_FULLTEXT = 'fulltext';
    
    public const TYPE_POSTAL = 'postal';
    public const TYPE_BILLING = 'billing';
    public const TYPE_DELIVERY = 'delivery';
    
    public const MODEL_NAME_PART = 'Address';
    public const TABLE_NAME = 'sales_addresses';
                        
    
    /**
     * Holds the model configuration
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION   => 6,
        self::APP_NAME => Sales_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,
        self::RECORD_NAME   => 'Address', // ngettext('Address', 'Addresses', n)
        self::RECORDS_NAME  => 'Addresses', // gettext('GENDER_Address')
        self::HAS_RELATIONS => true,
        self::HAS_CUSTOM_FIELDS => true,
        self::MODLOG_ACTIVE => true,
        self::CREATE_MODULE => false,
        self::IS_DEPENDENT  => true,
        self::TITLE_PROPERTY => "{% if debitor_id.number %}{{ debitor_id.number }} {% endif %}{% if name_shorthand %}'{{ name_shorthand }}' {% endif %}{% if name %}{{ name }} {% endif %}{% if email %}{{ email }} {% endif %}{% if prefix1 %}{{ prefix1 }}{% if prefix2 %} {% else %}, {% endif %}{% endif %}{% if prefix2 %}{{ prefix2 }}, {% endif %}{% if postbox %}{{ postbox }}, {% elseif street %}{{ street }}, {% endif %}{% if postalcode %}{{ postalcode }} {% endif %}{% if locality %}{{ locality }} {% endif %}({{ type }})",
        self::DEFAULT_SORT_INFO => [self::FIELD => self::FLD_NAME],
        self::EXPOSE_JSON_API => true,
        'resolveRelated'  => TRUE,
        'defaultFilter'   => 'query',
        'resolveVFGlobally' => TRUE,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::INDEXES   => [
                self::FLD_CUSTOMER_ID   => [
                    self::COLUMNS   => [self::FLD_CUSTOMER_ID],
                ],
                self::FLD_DEBITOR_ID   => [
                    self::COLUMNS   => [self::FLD_DEBITOR_ID],
                ],
            ],
        ],

        self::FIELDS          => [
            self::FLD_CUSTOMER_ID       => [
                self::LABEL         => 'Customer',    // _('Customer')
                self::TYPE          => self::TYPE_RECORD,
                'sortable'          => false,
                self::CONFIG        => [
                    self::APP_NAME     => Sales_Config::APP_NAME,
                    self::MODEL_NAME   => Sales_Model_Customer::MODEL_NAME_PART,
                    self::IS_PARENT => true,
                ],
                self::QUERY_FILTER  => true,
                self::NULLABLE      => true,
            ],
            self::FLD_DEBITOR_ID       => [
                self::LABEL         => 'Debitor Number',    // _('Debitor Number')
                self::DESCRIPTION   => 'A buyer identifier (usually assigned by the seller), such as the customer number for accounting or the customer number for order management. Note: No standardized scheme is required for the creation of the buyer identifier. (BT-64 [EN 16931]).', // _('A buyer identifier (usually assigned by the seller), such as the customer number for accounting or the customer number for order management. Note: No standardized scheme is required for the creation of the buyer identifier. (BT-64 [EN 16931]).')
                self::TYPE          => self::TYPE_RECORD,
                'sortable'          => false,
                self::CONFIG => [
                    self::APP_NAME     => Sales_Config::APP_NAME,
                    self::MODEL_NAME   => Sales_Model_Debitor::MODEL_NAME_PART,
                    self::IS_PARENT => true,
                ],
                self::NULLABLE => true,
            ],
            self::FLD_SHORTHAND => [
                self::LABEL      => 'Name shorthand', // _('Name shorthand')
                self::NULLABLE => true,
                self::TYPE => self::TYPE_STRING,
                self::QUERY_FILTER => true,
            ],
            self::FLD_LANGUAGE => [
                self::LABEL         => 'Language', // _('Language')
                self::DESCRIPTION   => 'Defines translation of the "Postbox" prefix in BT-50 [EN 16931].', // _('Defines translation of the "Postbox" prefix in BT-50 [EN 16931].')
                self::TYPE          => self::TYPE_KEY_FIELD,
                self::NAME          => Sales_Config::LANGUAGES_AVAILABLE,
                self::NULLABLE      => true,
            ],
            self::FLD_EMAIL => [
                self::LABEL         => 'Email', // _('Email')
                self::DESCRIPTION   => 'An email address of the contact point (BT-58 [EN 16931]).', // _('An e-mail address of the contact point (BT-58 [EN 16931]).')
                self::NULLABLE      => true,
                self::TYPE          => self::TYPE_STRING,
                self::QUERY_FILTER  => true,
            ],
            self::FLD_NAME => [
                self::LABEL         => 'Customer Name', // _('Customer Name')
                self::DESCRIPTION   => 'The full name of the purchaser (default value if left blank is the "name" property of the corresponding customer) (BT-44 [EN 16931]).', // _('The full name of the purchaser (default value if left blank is the "name" property of the corresponding customer) (BT-44 [EN 16931]).')
                self::NULLABLE      => true,
                self::TYPE          => self::TYPE_STRING,
                self::QUERY_FILTER  => true,
            ],
            self::FLD_PREFIX1 => [
                self::LABEL         => 'Company / Organisation (Prefix 1)', //_('Company / Organisation (Prefix 1)')
                self::DESCRIPTION   => 'A name by which the acquirer is known, if different from the acquirer\'s name (BT-45 [EN 16931]).', // _('A name by which the acquirer is known, if different from the acquirer\'s name (BT-45 [EN 16931]).')
                self::NULLABLE      => TRUE,
                self::TYPE          => self::TYPE_STRING,
                self::QUERY_FILTER  => TRUE,
            ],
            self::FLD_PREFIX2 => [
                self::LABEL         => 'Unit (Prefix 2)', //_('Unit (Prefix 2)')
                self::DESCRIPTION   => 'Contact person or contact point at the acquirer (e.g. name of a person, department or office name) - supplementary entry - (BT-56 [EN 16931]).', // _('Contact person or contact point at the acquirer (e.g. name of a person, department or office name) - supplementary entry - (BT-56 [EN 16931]).')
                self::NULLABLE      => TRUE,
                self::TYPE          => self::TYPE_STRING,
                self::QUERY_FILTER  => TRUE,
            ],
            self::FLD_PREFIX3 => [
                self::LABEL         => 'Recipient Name (Prefix 3)', //_('Recipient Name (Prefix 3)')
                self::DESCRIPTION   => 'Contact person or contact point at the acquirer (e.g. name of a person, department or office name) (BT-56 [EN 16931]).', // _('Contact person or contact point at the acquirer (e.g. name of a person, department or office name) (BT-56 [EN 16931]).')
                self::NULLABLE      => TRUE,
                self::TYPE          => self::TYPE_STRING,
                self::QUERY_FILTER  => TRUE,
            ],
            self::FLD_STREET => [
                self::TYPE          => self::TYPE_STRING,
                self::LABEL         => 'Street', //_('Street')
                self::DESCRIPTION   => 'The main line of an address. This is usually either the street and house number (BT-50 if no PO box is specified, otherwise BT-51 [EN 16931]).', // _('The main line of an address. This is usually either the street and house number (BT-50 if no PO box is specified, otherwise BT-51 [EN 16931]).')
                self::QUERY_FILTER  => TRUE,
                self::NULLABLE      => TRUE,
            ],
            self::FLD_POBOX => [
                self::TYPE          => self::TYPE_STRING,
                self::LABEL         => 'Postbox', //_('Postbox')
                self::DESCRIPTION   => 'Becomes the text "Postbox" followed by the mailbox number specified here in BT-50 [EN 16931].', // _('Becomes the text "Postbox" followed by the mailbox number specified here in BT-50 [EN 16931].')
                self::QUERY_FILTER  => TRUE,
                self::NULLABLE      => TRUE,
            ],
            self::FLD_POSTALCODE => [
                self::TYPE          => self::TYPE_STRING,
                self::LABEL         => 'Postal Code', //_('Postal Code')
                self::DESCRIPTION   => 'The zip code (BT-53 [EN 16931]).', // ('The zip code (BT-53 [EN 16931]).')
                self::QUERY_FILTER  => TRUE,
                self::NULLABLE      => TRUE,
            ],
            self::FLD_LOCALITY => [
                self::TYPE          => self::TYPE_STRING,
                self::LABEL         => 'Locality', //_('Locality')
                self::DESCRIPTION   => 'The name of the city or municipality in which the purchaser\'s address is located (BT-52 [EN 16931]).', // _('The name of the city or municipality in which the purchaser\'s address is located (BT-52 [EN 16931]).')
                self::QUERY_FILTER  => TRUE,
                self::NULLABLE      => TRUE,
            ],
            self::FLD_REGION => [
                self::TYPE          => self::TYPE_STRING,
                self::DESCRIPTION   => 'The subdivision of a country (such as region, state, province, etc.) (BT-54 [EN 16931]).', // _('The subdivision of a country (such as region, state, province, etc.) (BT-54 [EN 16931]).')
                self::LABEL         => 'Region', //_('Region')
                self::QUERY_FILTER  => TRUE,
                self::NULLABLE      => TRUE,
            ],
            self::FLD_COUNTRYNAME => [
                self::TYPE          => self::TYPE_STRING,
                self::SPECIAL_TYPE  => self::SPECIAL_TYPE_COUNTRY,
                self::LABEL         => 'Country', //_('Country')
                self::DESCRIPTION   => 'BT-55',
                self::DEFAULT_VAL   => 'DE',
                self::QUERY_FILTER  => TRUE,
                self::NULLABLE      => TRUE,
            ],
            // we should drop this column, be aware of upgrade path though!
            // if you remove it here, Setup/Update/17.php ::update001 will remove it! though it is accessed by later update functions!!!
            self::FLD_CUSTOM1 => [
                self::TYPE      => self::TYPE_STRING,
                self::NULLABLE  => TRUE,
            ],
            self::FLD_TYPE => [
                self::TYPE          => self::TYPE_STRING,
                self::LABEL         => NULL,
                self::DEFAULT_VAL   => self::TYPE_POSTAL,
                self::VALIDATORS    => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    [Zend_Validate_InArray::class, [
                        self::TYPE_BILLING,
                        self::TYPE_DELIVERY,
                        self::TYPE_POSTAL,
                    ]]
                ],
                self::QUERY_FILTER  => TRUE
            ],
            self::FLD_FULLTEXT => [
                'config' => [
                    'duplicateOmit' => TRUE,
                    self::LABEL     => NULL
                ],
                self::TYPE => self::TYPE_VIRTUAL,
            ],
        ]
    ];

    public function hydrateFromBackend(array &$data)
    {
        $data = Sales_Controller_Address::getInstance()->resolveVirtualFields($data);

        parent::hydrateFromBackend($data);
    }

    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        array('relatedApp' => 'Addressbook', 'relatedModel' => 'Contact', 'config' => array(
            array('type' => 'CONTACTADDRESS', 'degree' => 'sibling', 'text' => 'Contact Address', 'max' => '0:0'), // _('Invoice Item')
        ), 'defaultType' => 'CONTACTADDRESS'
        ),
    );
}

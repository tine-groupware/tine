<?php

/**
 * Tine 2.0
 *
 * class to hold contact data
 *
 * @package     Addressbook
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Addressbook_Model_ContactProperties_Definition as AMCPD;

/**
 * @property    string $account_id                 id of associated user
 * @property    string $adr_one_countryname        name of the country the contact lives in
 * @property    string $adr_one_locality           locality of the contact
 * @property    string $adr_one_postalcode         postalcode belonging to the locality
 * @property    string $adr_one_region             region the contact lives in
 * @property    string $adr_one_street             street where the contact lives
 * @property    string $adr_one_street2            street2 where contact lives
 * @property    string $adr_one_lon
 * @property    string $adr_one_lat
 * @property    string $adr_two_countryname        second home/country where the contact lives
 * @property    string $adr_two_locality           second locality of the contact
 * @property    string $adr_two_postalcode         postalcode belonging to second locality
 * @property    string $adr_two_region             second region the contact lives in
 * @property    string $adr_two_street             second street where the contact lives
 * @property    string $adr_two_street2            second street2 where the contact lives
 * @property    string $adr_two_lon
 * @property    string $adr_two_lat
 * @property    string $assistent                  name of the assistent of the contact
 * @property    datetime $bday                     date of birth of contact
 * @property    string $color                      contact color (hex value)
 * @property    string $calendar_uri               calendar uri
 * @property    string $freebusy_uri               freebusy uri
 * @property    integer $container_id              id of container
 * @property    string $email                      the email address of the contact
 * @property    string $email_home                 the private email address of the contact
 * @property    string $jpegphoto                  photo of the contact
 * @property    string $geo
 * @property    string $groups                     virtual field with array of group memberships
 * @property    string $industry                   industry record id
 * @property    string $n_family                   surname of the contact
 * @property    string $language                   language
 * @property    string $n_fileas                   display surname, name
 * @property    string $n_fn                       the full name
 * @property    string $n_given                    forename of the contact
 * @property    string $n_middle                   middle name of the contact
 * @property    string $note                       notes of the contact
 * @property    string $n_prefix                   name prefix
 * @property    string $n_suffix                   name suffix
 * @property    string $n_short                    short name
 * @property    string $org_name                   name of the company/organisation the contact works at
 * @property    string $org_unit                   company/organisation unit
 * @property    string $pubkey                     public key
 * @property    string $role                       type of role of the contact
 * @property    string $room                       room of the contact
 * @property    string $tel_assistent              phone number of the assistant
 * @property    string $tel_car                    cat phone number
 * @property    string $tel_cell                   mobile phone number
 * @property    string $tel_cell_private           private mobile number
 * @property    string $tel_fax                    number for calling the fax
 * @property    string $tel_fax_home               private fax number
 * @property    string $tel_home                   telephone number of contact's home
 * @property    string $tel_pager                  contact's pager number
 * @property    string $tel_work                   contact's office phone number
 * @property    string $tel_other                  other phone number
 * @property    string $tel_prefer                 preferred phone number
 * @property    string $tel_assistent_normalized   phone number of the assistant (normalized)
 * @property    string $tel_car_normalized         car phone number (normalized)
 * @property    string $tel_cell_normalized        mobile phone number (normalized)
 * @property    string $tel_cell_private_normalized private mobile number (normalized)
 * @property    string $tel_fax_normalized         number for calling the fax (normalized)
 * @property    string $tel_fax_home_normalized    private fax number (normalized)
 * @property    string $tel_home_normalized        telephone number of contact's home (normalized)
 * @property    string $tel_pager_normalized       contact's pager number (normalized)
 * @property    string $tel_work_normalized        contact's office phone number (normalized)
 * @property    string $tel_other_normalized       other phone number (normalized)
 * @property    string $tel_prefer_normalized      preferred phone number (normalized)
 * @property    string $title                      special title of the contact
 * @property    string $type                       type of contact
 * @property    string $tz                         time zone
 * @property    string $url                        url/website of the contact
 * @property    string $salutation                 Salutation
 * @property    string $url_home                   private url of the contact
 * @property    string $preferred_address          defines which is the preferred address of a contact
 * @property    string $preferred_email            defines which is the preferred email of a contact
 */
class Addressbook_Model_Contact extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'Contact';

    /**
     * const to describe contact of current account id independent
     *
     * @var string
     */
    const CURRENTCONTACT = 'currentContact';

    /**
     * contact type: contact
     *
     * @var string
     */
    const CONTACTTYPE_CONTACT = 'contact';

    /**
     * contact type: user
     *
     * @var string
     */
    const CONTACTTYPE_USER = 'user';

    /**
     * contact type: email_account
     *
     * @var string
     */
    const CONTACTTYPE_EMAIL_ACCOUNT = 'email_account';

    /**
     * small contact photo size
     *
     * @var integer
     */
    const SMALL_PHOTO_SIZE = 36000;

    const XPROP_NO_GEODATA_UPDATE = 'noGeodataUpdate';


    public const TABLE_NAME = 'addressbook';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    public static $doResolveAttenderCleanUp = true;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION       => 27,
        'containerName'     => 'Addressbook', // gettext('GENDER_Addressbook')
        'containersName'    => 'Addressbooks', // ngettext('Addressbook', 'Addressbooks', n)
        'recordName'        => self::MODEL_NAME_PART, // gettext('GENDER_Contact')
        'recordsName'       => 'Contacts', // ngettext('Contact', 'Contacts', n)
        'hasRelations'      => true,
        'copyRelations'     => false,
        'hasCustomFields'   => true,
        'hasSystemCustomFields' => true,
        'hasNotes'          => true,
        'hasTags'           => true,
        'modlogActive'      => true,
        'hasAttachments'    => true,
        'createModule'      => true,
        'exposeHttpApi'     => true,
        'exposeJsonApi'     => true,
        'containerProperty' => 'container_id',
        'multipleEdit'      => true,
        self::HAS_XPROPS    => true,

        'titleProperty'     => 'n_fileas',
        'appName'           => 'Addressbook',
        'modelName'         => self::MODEL_NAME_PART, // _('GENDER_Contact')
        self::TABLE         => [
            self::NAME          => self::TABLE_NAME,
            self::INDEXES       => [
                'cat_id'                    => [
                    self::COLUMNS               => ['cat_id'],
                ],
                'container_id_index'        => [
                    self::COLUMNS               => ['container_id'],
                ],
                'type'                      => [
                    self::COLUMNS               => ['type'],
                ],
                'n_given_n_family'          => [
                    self::COLUMNS               => ['n_given', 'n_family'],
                ],
                'n_fileas'                  => [
                    self::COLUMNS               => ['n_fileas'],
                ],
                'n_family_n_given'          => [
                    self::COLUMNS               => ['n_family', 'n_given'],
                ],
                'note'                      => [
                    self::COLUMNS               => ['note'],
                    self::FLAGS                 => ['fulltext'],
                ],
            ],
        ],

        'associations' => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'container_id_fk' => [
                    'targetEntity' => Tinebase_Model_Container::class,
                    'fieldName' => 'container_id',
                    'joinColumns' => [[
                        'name' => 'container_id',
                        'referencedColumnName'  => 'id'
                    ]],
                ],
            ],
        ],

        self::JSON_EXPANDER => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'container_id' => [],
                'sites'      => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Addressbook_Model_ContactSite::FLD_SITE => [],
                    ],
                ],
            ],
        ],

        'filterModel'       => [
            'id'                => [
                'filter'            => Addressbook_Model_ContactIdFilter::class,
                'options'           => [
                    'idProperty'        => 'id',
                    'modelName'         => 'Addressbook_Model_Contact'
                ]
            ],
            'showDisabled'      => [
                'filter'            => Addressbook_Model_ContactHiddenFilter::class,
                // @TODO: do we want to have this filter in UI?
//                'label'             => 'Show Disabled', // _('Show Disabled')
                'options'           => [
                    'requiredCols'      => ['account_id' => 'accounts.id'],
                ],
                'jsConfig'          => ['valueType' => 'bool']
            ],
            'path'              => [
                'filter'            => Tinebase_Model_Filter_Path::class,
                'options'           => [],
            ],
            'list'              => [
                'filter'            => Addressbook_Model_ListMemberFilter::class,
                'label'             => 'Group', // _('Group')
                'options'           => [],
                'jsConfig'          => [
                    'filtertype' => 'foreignrecord',
                    'linkType' => 'foreignId',
                    'foreignRecordClass' => 'Tine.Addressbook.Model.List',
                    'multipleForeignRecords' => true,
                    'ownField' => 'list'
                ]
            ],
            'list_role_id'      => [
                'filter'            => Addressbook_Model_ListRoleMemberFilter::class,
                'label'             => 'Group Function', // _('Group Function')
                'options'           => [],
                'jsConfig'          => [
                    'filtertype' => 'foreignrecord',
                    'linkType' => 'foreignId',
                    'foreignRecordClass' => 'Tine.Addressbook.Model.ListRole',
                    'multipleForeignRecords' => true,
                    'ownField' => 'list_role_id'
                ]
            ],
            'telephone'         => [
                'filter'            => Tinebase_Model_Filter_Query::class,
                'label'             => 'Phone Numbers', // _('Phone Numbers')
                'options'           => [
                    'fields'            => [
                        'tel_assistent',
                        'tel_car',
                        'tel_other',
                        'tel_pager',
                        'tel_prefer',
                    ],
                    'modelName' => self::class,
                ],
            ],
            'telephone_normalized' => [
                'filter'            => Tinebase_Model_Filter_Query::class,
//                'label'             => 'not in ui yet',
                'options'           => [
                    'fields'            => [
                        'tel_assistent_normalized',
                        'tel_car_normalized',
                        'tel_other_normalized',
                        'tel_pager_normalized',
                        'tel_prefer_normalized',
                    ],
                    'modelName' => self::class,
                ],
            ],
            'email_query'       => [
                'filter'            => Tinebase_Model_Filter_Query::class,
                'label'             => 'Emails', // _('Emails')
                'options'           => [
                    'fields'            => [
                    ],
                    'modelName' => self::class,
                ],
            ],
            'name_email_query'       => [
                'filter'            => Tinebase_Model_Filter_Query::class,
                'label'             => 'Name/Emails', // _('Name/Emails')
                'options'           => [
                    'fields'            => [
                        'n_family',
                        'n_given',
                        'n_middle',
                        'org_name',
                    ],
                    'modelName' => self::class,
                ],
            ],
            'adr_one_countryname' => [
                'filter'            => Tinebase_Model_Filter_Country::class,
            ],
            'adr_two_countryname' => [
                'filter'            => Tinebase_Model_Filter_Country::class,
            ],
        ],

        self::FIELDS        => [
            'account_id'                    => [
                self::TYPE                      => self::TYPE_STRING, // self::TYPE_USER....
                self::IS_VIRTUAL                => true,
                self::COPY_OMIT                 => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                ],
                self::UI_CONFIG                 => [
                    'omitDuplicateResolving'        => true,
                ],
            ],
            'adr_one_countryname'           => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Country', // _('Country')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [Zend_Filter_StringTrim::class, Zend_Filter_StringToUpper::class],
                self::UI_CONFIG                 => [
                    'group'                         => 'Company Address',
                ],
            ],
            'adr_one_locality'              => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'City', // _('City')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
                self::UI_CONFIG                 => [
                    'group'                         => 'Company Address',
                ],
            ],
            'adr_one_postalcode'            => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Postalcode', // _('Postalcode')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'group'                         => 'Company Address',
                ],
            ],
            'adr_one_region'                => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Region', // _('Region')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'group'                         => 'Company Address',
                ],
            ],
            'adr_one_street'                => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Street', // _('Street')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'group'                         => 'Company Address',
                ],
            ],
            'adr_one_street2'               => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Street 2', // _('Street 2')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'group'                         => 'Company Address',
                ],
            ],
            'adr_one_lon'                   => [
                self::TYPE                      => self::TYPE_FLOAT,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Longitude', // _('Longitude')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [Zend_Filter_Empty::class => null],
                self::UI_CONFIG                 => [
                    'group'                         => 'Company Address',
                    'omitDuplicateResolving'        => true,
                ],
            ],
            'adr_one_lat'                   => [
                self::TYPE                      => self::TYPE_FLOAT,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Latitude', // _('Latitude')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [Zend_Filter_Empty::class => null],
                self::UI_CONFIG                 => [
                    'group'                         => 'Company Address',
                    'omitDuplicateResolving'        => true,
                ],
            ],
            'adr_two_countryname'           => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Country (private)', // _('Country (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [Zend_Filter_StringTrim::class, Zend_Filter_StringToUpper::class],
                self::UI_CONFIG                 => [
                    'group'                         => 'Private Address',
                ],
            ],
            'adr_two_locality'              => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'City (private)', // _('City (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'group'                         => 'Private Address',
                ],
            ],
            'adr_two_postalcode'            => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Postalcode (private)', // _('Postalcode (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'group'                         => 'Private Address',
                ],
            ],
            'adr_two_region'                => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Region (private)', // _('Region (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'group'                         => 'Private Address',
                ],
            ],
            'adr_two_street'                => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Street (private)', // _('Street (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'group'                         => 'Private Address',
                ],
            ],
            'adr_two_street2'               => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Street 2 (private)', // _('Street 2 (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'group'                         => 'Private Address',
                ],
            ],
            'adr_two_lon'                   => [
                self::TYPE                      => self::TYPE_FLOAT,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Longitude (private)', // _('Longitude (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [Zend_Filter_Empty::class => null],
                self::UI_CONFIG                 => [
                    'group'                         => 'Private Address',
                    'omitDuplicateResolving'        => true,
                ],
            ],
            'adr_two_lat'                   => [
                self::TYPE                      => self::TYPE_FLOAT,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Latitude (private)', // _('Latitude (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [Zend_Filter_Empty::class => null],
                self::UI_CONFIG                 => [
                    'group'                         => 'Private Address',
                    'omitDuplicateResolving'        => true,
                ],
            ],
            'assistent'                     => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Assistent', // _('Assistent')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'group'                         => 'Company',
                    'omitDuplicateResolving'        => true,
                ],
            ],
            'bday'                          => [
                self::TYPE                      => 'datetime',
                self::NULLABLE                  => true,
                self::LABEL                     => 'Birthday', // _('Birthday')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::REQUIRED_GRANTS           => [Addressbook_Model_ContactGrants::GRANT_PRIVATE_DATA],
            ],
            'color'                         => [
                self::TYPE                      => self::TYPE_HEX_COLOR,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Color', // _('Color')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'calendar_uri'                  => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 128,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Calendar URI', // _('Calendar URI')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'omitDuplicateResolving'        => true,
                ],
            ],
            'freebusy_uri'                  => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 128,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Free/Busy URI', // _('Free/Busy URI')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'omitDuplicateResolving'        => true,
                ],
            ],
            'geo'                           => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 32,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Geo', // _('Geo')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'groups'                        => [
                self::TYPE                      => 'virtual',
                self::LABEL                     => 'Groups', // _('Groups')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::OMIT_MOD_LOG              => true,
            ],
            'industry'                      => [
                self::TYPE                      => self::TYPE_STRING, // TODO make a record out of it?
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Industry', // _('Industry')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::FILTER_DEFINITION         => [
                    self::FILTER                    => Tinebase_Model_Filter_ForeignId::class,
                    self::OPTIONS                   => [
                        self::FILTER_GROUP              => Addressbook_Model_IndustryFilter::class,
                        self::CONTROLLER                => Addressbook_Controller_Industry::class
                    ]
                ]
            ],
            'jpegphoto'                     => [
                self::TYPE                      => self::TYPE_VIRTUAL,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                ],
                self::OMIT_MOD_LOG              => true,
                self::SYSTEM                    => true,
                self::UI_CONFIG                 => [
                    'omitDuplicateResolving'        => true,
                ],
            ],
            'language'           => [
                self::TYPE                      => self::TYPE_LANGUAGE,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Language', // _('Language')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'group'                         => 'Company Communication',
                ],
            ],
            'note'                          => [
                self::TYPE                      => self::TYPE_FULLTEXT,
                self::LENGTH                    => 2147483647, // mysql longtext, really?!?
                self::NULLABLE                  => true,
                self::LABEL                     => 'Note', // _('Note')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
            ],
            'n_family'                      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Last Name', // _('Last Name')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
                self::UI_CONFIG                 => [
                    'group'                         => 'Name',
                ],
            ],
            'n_fileas'                      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Display Name', // _('Display Name')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'omitDuplicateResolving'        => true,
                ],
            ],
            'n_fn'                          => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Full Name', // _('Full Name')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'omitDuplicateResolving'        => true,
                ],
            ],
            'n_given'                       => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'First Name', // _('First Name')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
                self::UI_CONFIG                 => [
                    'group'                         => 'Name',
                ],
            ],
            'n_middle'                      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Middle Name', // _('Middle Name')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'group'                         => 'Name',
                ],
            ],
            'n_prefix'                      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Title', // _('Title')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'group'                         => 'Name',
                ],
            ],
            'n_suffix'                      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Suffix', // _('Suffix')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'group'                         => 'Name',
                ],
            ],
            'n_short'                      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Short Name', // _('Short Name')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::COPY_OMIT                 => true,
                self::UI_CONFIG                 => [
                    'group'                         => 'Name',
                ],
            ],
            'org_name'                      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Company / Organisation', // _('Company / Organisation')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
                self::UI_CONFIG                 => [
                    'group'                         => 'Company',
                ],
            ],
            'org_unit'                      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Unit', // _('Unit')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
                self::UI_CONFIG                 => [
                    'group'                         => 'Company',
                ],
            ],
            'paths'                         => [
                'type'                          => 'records',
                self::IS_VIRTUAL                => true,
                'noResolve'                     => true,
                'config'                        => [
                    'appName' => 'Tinebase',
                    'modelName' => 'Path',
                    'recordClassName' => Tinebase_Model_Path::class,
                    'controllerClassName' => Tinebase_Record_Path::class,
                    'filterClassName' => Tinebase_Model_PathFilter::class,
                ],
                'label'                         => 'Paths', // _('Paths')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'omitDuplicateResolving'        => true,
                ],
            ],
            'preferred_address'             => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::DEFAULT_VAL               => 'adr_one',
                self::LABEL                     => 'Preferred Address', // _('Preferred Address')
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                    Zend_Filter_Input::DEFAULT_VALUE    => 'adr_one',
                ],
                self::INPUT_FILTERS         => [Zend_Filter_Empty::class => 'adr_one'],
                self::UI_CONFIG                 => [
                    'omitDuplicateResolving'        => true,
                ],
            ],
            'preferred_email'             => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::DEFAULT_VAL               => 'email',
                self::LABEL                     => 'Preferred Email', // _('Preferred Email')
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                    Zend_Filter_Input::DEFAULT_VALUE    => 'email',
                ],
                self::INPUT_FILTERS         => [Zend_Filter_Empty::class => 'email'],
                self::UI_CONFIG                 => [
                    'omitDuplicateResolving'        => true,
                ],
            ],
            'pubkey'                        => [
                self::TYPE                      => self::TYPE_TEXT,
                self::LENGTH                    => 2147483647, // mysql longtext, really?!?
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [],
                self::UI_CONFIG                 => [
                    'omitDuplicateResolving'        => true,
                ],
            ],
            'role'                          => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Job Role', // _('Job Role')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'group'                         => 'Company',
                ],
            ],
            'room'                          => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Room', // _('Room')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'group'                         => 'Company',
                ],
            ],
            'salutation'                    => [
                self::TYPE                      => self::TYPE_KEY_FIELD,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Salutation', // _('Salutation')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::NAME                      => Addressbook_Config::CONTACT_SALUTATION,
                self::UI_CONFIG                 => [
                    'group'                         => 'Name',
                ],
            ],
            'syncBackendIds'                => [
                self::TYPE                      => self::TYPE_TEXT,
                self::LENGTH                    => 16000,
                self::NULLABLE                  => true,
                self::LABEL                     => 'syncBackendIds', // _('syncBackendIds')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [],
                self::SYSTEM                    => true,
                self::DISABLED                  => true,
                self::ALLOW_CAMEL_CASE          => true,
            ],
            'tel_assistent'                 => [ // not in UI atm.
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::SYSTEM                    => true,
                self::DISABLED                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'group'                         => 'Contact Information',
                ],
            ],
            'tel_car'                       => [ // not in UI atm.
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Car phone', // _('Car phone')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'group'                         => 'Contact Information',
                ],
                self::SYSTEM                    => true,
                self::DISABLED                  => true,
            ],
            'tel_pager'                     => [ // not in UI atm.
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Pager', // _('Pager')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::SYSTEM                    => true,
                self::DISABLED                  => true,
            ],
            'tel_other'                     => [ // not in UI atm.
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::SYSTEM                    => true,
                self::DISABLED                  => true,
                self::UI_CONFIG                 => [
                    'group'                         => 'Contact Information',
                ],
            ],
            'tel_prefer'                    => [ // not in UI atm.
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::SYSTEM                    => true,
                self::DISABLED                  => true,
                self::UI_CONFIG                 => [
                    'group'                         => 'Contact Information',
                ],
            ],
            'tel_assistent_normalized'      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::SYSTEM                    => true,
                self::DISABLED                  => true,
            ],
            'tel_car_normalized'            => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::SYSTEM                    => true,
                self::DISABLED                  => true,
            ],
            'tel_pager_normalized'          => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::SYSTEM                    => true,
                self::DISABLED                  => true,
            ],
            'tel_other_normalized'          => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::SYSTEM                    => true,
                self::DISABLED                  => true,
            ],
            'tel_prefer_normalized'         => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::SYSTEM                    => true,
                self::DISABLED                  => true,
            ],
            'title'                         => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Job Title', // _('Job Title')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'group'                         => 'Company',
                ],
            ],
            'type'                          => [
                self::TYPE                      => self::TYPE_KEY_FIELD,
                self::NAME                      => Addressbook_Config::CONTACT_TYPES,
                self::LENGTH                    => 128,
                self::LABEL                     => 'Type', // _('Type')
                self::SYSTEM                    => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                    Zend_Filter_Input::DEFAULT_VALUE    => self::CONTACTTYPE_CONTACT,
                    //export key field resolving has a problem with that validation as a key field record, not a string will be set
                    //['InArray', [self::CONTACTTYPE_USER, self::CONTACTTYPE_CONTACT, self::CONTACTTYPE_EMAIL_ACCOUNT]]
                ],
                self::DEFAULT_VAL               => self::CONTACTTYPE_CONTACT,
                self::COPY_OMIT                 => true,
            ],
            'tz'                            => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 8,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Timezone', // _('Timezone')
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    ['StringLength', ['max' => 8]],
                ],
                self::UI_CONFIG                 => [
                    'omitDuplicateResolving'        => true,
                ],
            ],

            // just a placeholder, mainly required for install
            'email' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
            ],
            // just placeholders, do not edit them, required for update path, can be removed if update path can be broken
            'email_home' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
            ],
            'tel_work' => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
            ],
            'tel_work_normalized' => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
            ],
            'tel_home' => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
            ],
            'tel_home_normalized' => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
            ],
            'tel_cell' => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
            ],
            'tel_cell_normalized' => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
            ],
            'tel_cell_private' => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
            ],
            'tel_cell_private_normalized' => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
            ],
            'tel_fax' => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
            ],
            'tel_fax_normalized' => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
            ],
            'tel_fax_home' => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
            ],
            'tel_fax_home_normalized' => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
            ],
            'url' => [
                self::TYPE                      => self::TYPE_TEXT,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
            ],
            'url_home' => [
                self::TYPE                      => self::TYPE_TEXT,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
            ],
            // placeholders end

            // do we want to remove those?
            'label'                         => [
                self::TYPE                      => self::TYPE_TEXT,
                self::LENGTH                    => 2147483647, // mysql longtext, really?!?
                self::NULLABLE                  => true,
                self::LABEL                     => 'Label', // _('Label')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'omitDuplicateResolving'        => true,
                ],
            ],
            'cat_id'                        => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG                 => [
                    'omitDuplicateResolving'        => true,
                ],
            ],
            'sites' => [
                self::TYPE                  => self::TYPE_RECORDS,
                self::LABEL                 => 'Sites', // _('Sites')
                self::CONFIG                => [
                    self::DEPENDENT_RECORDS     => true,
                    self::APP_NAME              => Addressbook_Config::APP_NAME,
                    self::MODEL_NAME            => Addressbook_Model_ContactSite::MODEL_NAME_PART,
                    self::REF_ID_FIELD          => Addressbook_Model_ContactSite::FLD_CONTACT,
                ],
                self::UI_CONFIG             => [
                    'filterOptions' => [
                        'jsConfig' => ['filtertype' => 'tinebase.site']
                    ],
                    self::UI_CONFIG_FEATURE     => [
                        self::APP_NAME              => Tinebase_Config::APP_NAME,
                        self::UI_CONFIG_FEATURE     => Tinebase_Config::FEATURE_SITE
                    ],
                ]
            ],

        ],

        self::DB_COLUMNS                => [
            'tid'                           => [
                'fieldName'                     => 'tid',
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 1,
                self::NULLABLE                  => true,
                self::DEFAULT_VAL               => 'n',
            ],
            'private'                       => [
                'fieldName'                     => 'private',
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::LENGTH                    => 1,
                self::NULLABLE                  => true,
                self::DEFAULT_VAL               => 0,
            ],
        ],
    ];


    /**
     * if foreign Id fields should be resolved on search and get from json
     * should have this format:
     *     array('Calendar_Model_Contact' => 'contact_id', ...)
     * or for more fields:
     *     array('Calendar_Model_Contact' => array('contact_id', 'customer_id), ...)
     * (e.g. resolves contact_id with the corresponding Model)
     *
     * @var array
     */
    protected static $_resolveForeignIdFields = array(
        'Tinebase_Model_User'        => array('created_by', 'last_modified_by'),
        'Addressbook_Model_Industry' => array('industry'),
        'recursive'                  => array('attachments' => 'Tinebase_Model_Tree_Node'),
        'Addressbook_Model_List' => array('groups'),
    );

    /**
     * name of fields which require manage accounts to be updated
     *
     * @var array list of fields which require manage accounts to be updated
     */
    protected static $_manageAccountsFields = array(
        'email',
        'n_fileas',
        'n_fn',
        'n_given',
        'n_family',
    );

    protected static $_telFields = [];

    protected static $_emailFields = [];

    protected static $_additionalAdrFields = [];

    static public function getAdditionalAddressFields(): array
    {
        return static::$_additionalAdrFields;
    }

    static public function getTelefoneFields(): array
    {
        return static::$_telFields;
    }

    static public function getEmailFields(): array
    {
        if (count(static::$_emailFields) === 0) {
            return [
                'email' => 'email',
                'email_home' => 'email_home'
            ];
        }
        return static::$_emailFields;
    }

    static public function setAdditionalAddressFields(array $fields): void
    {
        static::$_additionalAdrFields = $fields;
    }

    static public function setTelefoneFields(array $fields): void
    {
        static::$_telFields = $fields;
    }

    static public function setEmailFields(array $fields): void
    {
        static::$_emailFields = $fields;
    }

    public static function resetConfiguration()
    {
        static::$_emailFields = [];
        static::$_telFields = [];
        static::$_additionalAdrFields = [];

        parent::resetConfiguration();
    }

    /**
     * @return array
     */
    static public function getManageAccountFields()
    {
        return self::$_manageAccountsFields;
    }

    /**
     * returns preferred email address of given contact
     *
     * - if preferred email is null , use the first none empty email field
     *
     * @return string|null
     */
    public function getPreferredEmailAddress(): ?string
    {
        // prefer work mail over private mail till we have prefs for this
        $fields = array_keys(self::getEmailFields());
        $preferredEmail = null;

        if ($this->preferred_email && in_array($this->preferred_email, $fields)) {
            $preferredEmail = $this->{$this->preferred_email};
        }
        if (empty($preferredEmail)) {
            foreach ($fields as $field) {
                if (!empty($this->{$field})) {
                    $preferredEmail = $this->{$field};
                    break;
                }
            }
        }
        return $preferredEmail;
    }

    /**
     * check if the email require private grant in addressbook contact property
     *
     * @param $email
     */
    public function isPrivateEmail($email, $emailField): bool
    {
        $fields = self::getEmailFields();
        $result = false;

        $match = !empty($this->{$emailField}) && $email === $this->{$emailField};
        if ($match && is_array($fields[$emailField]['grant_matrix']) && in_array(Addressbook_Model_ContactGrants::GRANT_PRIVATE_DATA, $fields[$emailField]['grant_matrix'])) {
            $result = true;
        }
        return $result;
    }

    /**
     * @see Tinebase_Record_Abstract::setFromArray
     *
     * @param array $_data            the new data to set
     */
    public function setFromArray(array &$_data)
    {
        $this->_resolveAutoValues($_data);
        parent::setFromArray($_data);
    }

    public function hydrateFromBackend(array &$_data)
    {
        $this->_resolveAutoValues($_data);
        parent::hydrateFromBackend($_data);
    }
    /**
     * Resolves the auto values n_fn and n_fileas
     * @param array $_data
     */
    protected function _resolveAutoValues(array &$_data)
    {
        if (! (isset($_data['org_name']) || array_key_exists('org_name', $_data))) {
            // we might want to set it to null instead?
            $_data['org_name'] = '';
        }

        // try to guess name from n_fileas
        if (empty($_data['org_name']) && empty($_data['n_family']) && empty($_data['n_given'])) {
            if (! empty($_data['n_fn'])) {
                $names = preg_split('/\s* \s*/', $_data['n_fn'], 2);
                if (isset($names[0])) {
                    $_data['n_given'] = $names[0];
                }
                if (isset($names[1])) {
                    $_data['n_family'] = $names[1];
                }
            } elseif (! empty($_data['n_fileas'])) {
                $names = preg_split('/\s*,\s*/', $_data['n_fileas'], 2);
                if (isset($names[0])) {
                    $_data['n_family'] = $names[0];
                }
                if (isset($names[1])) {
                    $_data['n_given'] = $names[1];
                }
            }
        }

        if (empty($_data['n_fileas'])) {
            $name = 'n_fileas';
            $template = Addressbook_Config::getInstance()->{Addressbook_Config::FILE_AS_TEMPLATE};

            $locale = Tinebase_Core::getLocale();
            if (! $locale) {
                $locale = Tinebase_Translation::getLocale();
            }
            $twig = new Tinebase_Twig($locale, Tinebase_Translation::getTranslation(), [
                Tinebase_Twig::TWIG_LOADER =>
                    new Tinebase_Twig_CallBackLoader(__METHOD__ . $name, time() - 1, function () use ($template) {
                        return $template;
                    })
            ]);
            $_data['n_fileas'] = $twig->load(__METHOD__ . $name)->render($_data);
        }

        // always update fn
        if (!empty($_data['n_given'])) {
            $_data['n_fn'] = $_data['n_given'] . (!empty($_data['n_family']) ? ' ' . $_data['n_family'] : '');
        } else {
            $_data['n_fn'] = (!empty($_data['n_family'])) ? $_data['n_family']
                : ((! empty($_data['org_name'])) ? $_data['org_name']
                    : ((isset($_data['n_fn'])) ? $_data['n_fn'] : ''));
        }

        // truncate some values if too long
        // TODO add generic code for this? maybe it should be configurable
        foreach (
            [
            'n_fn' => 255,
            'n_family' => 255,
            'n_fileas' => 255,
            'org_name' => 255,
            'n_given' => 86,
            'n_middle' => 86,
            'n_prefix' => 86,
            'n_suffix' => 86,
            'n_short' => 86,
            ] as $field => $allowedLength
        ) {
            if (isset($_data[$field]) && mb_strlen((string)$_data[$field]) > $allowedLength) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Field has been truncated: '
                        . $field . ' original data: ' . $_data[$field]);
                }
                $_data[$field] = mb_substr($_data[$field], 0, $allowedLength);
            }
        }
    }

    /**
     * Overwrites the __set Method from Tinebase_Record_Abstract
     * Also sets n_fn and n_fileas when org_name, n_given or n_family should be set
     * @see Tinebase_Record_Abstract::__set()
     * @param string $_name of property
     * @param mixed $_value of property
     */
    public function __set($_name, $_value)
    {

        switch ($_name) {
            case 'n_given':
                $resolved = array('n_given' => $_value, 'n_family' => $this->__get('n_family'), 'org_name' => $this->__get('org_name'));
                $this->_resolveAutoValues($resolved);
                parent::__set('n_fn', $resolved['n_fn']);
                break;
            case 'n_family':
                $resolved = array('n_family' => $_value, 'n_given' => $this->__get('n_given'), 'org_name' => $this->__get('org_name'));
                $this->_resolveAutoValues($resolved);
                parent::__set('n_fn', $resolved['n_fn']);
                break;
            case 'org_name':
                $resolved = array('org_name' => $_value, 'n_given' => $this->__get('n_given'), 'n_family' => $this->__get('n_family'));
                $this->_resolveAutoValues($resolved);
                parent::__set('n_fn', $resolved['n_fn']);
                break;
            default:
                // normalize telephone numbers
                if (isset(static::$_telFields[$_name])) {
                    parent::__set($_name . '_normalized', (empty($_value) ? $_value : static::normalizeTelephoneNum($_value)));
                }
                break;
        }

        parent::__set($_name, $_value);
    }

    /**
     * normalizes telephone numbers and eventually adds missing country part (using configured default country code)
     * result will be of format +y[y][y]xxxxxxx (only digits, y country code)
     *
     * @param  string $telNumber
     * @return string|null
     */
    public static function normalizeTelephoneNum($telNumber, $additionalAllowedChars = '')
    {
        $telNumber = preg_replace('/[^\d\+()' . $additionalAllowedChars . ']/u', '', $telNumber);

        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        try {
            $numberFormat = $phoneUtil->parse($telNumber, Addressbook_Config::getInstance()
                ->{Addressbook_Config::DEFAULT_TEL_COUNTRY_CODE});
            return $phoneUtil->format($numberFormat, \libphonenumber\PhoneNumberFormat::E164);
        } catch (Exception $e) {
        }

        return null;
    }

    public function getPreferredAddressObject(): ?Addressbook_Model_ContactProperties_Address
    {
        if (!$this->preferred_address || !$this->has($this->preferred_address)) {
            return null;
        }
        if ($this->{$this->preferred_address} instanceof Addressbook_Model_ContactProperties_Address) {
            return $this->{$this->preferred_address};
        }

        if (
            ($fieldDef = static::getConfiguration()->getJsonFacadeFields()[$this->preferred_address] ?? null) &&
            Addressbook_Model_ContactProperties_Address::class === $fieldDef[Tinebase_ModelConfiguration_Const::CONFIG][Tinebase_ModelConfiguration_Const::RECORD_CLASS_NAME]
        ) {
            Addressbook_Model_ContactProperties_Address::jsonFacadeToJson($this, $this->preferred_address, $fieldDef);
            return $this->{$this->preferred_address};
        }
        return null;
    }

    public function getMsisdn()
    {
        foreach (['tel_cell_normalized', 'tel_cell_private_normalized','tel_car_normalized'] as $property) {
            if (strlen((string)$this->{$property}) > 0) {
                return substr(ltrim(ltrim($this->{$property}, '0'), '+'), 0, 15);
            }
        }
    }

    /**
     * fills a contact from json data
     *
     * @param array $_data record data
     * @return void
     *
     * @todo timezone conversion for birthdays?
     * @todo move this to Addressbook_Convert_Contact_Json
     */
    protected function _setFromJson(array &$_data)
    {
        $this->_setContactImage($_data);

        foreach (static::$_telFields as $field) {
            unset($_data[$field . '_normalized']);
        }
    }

    /**
     * set contact image
     *
     * @param array $_data
     */
    protected function _setContactImage(&$_data)
    {
        if (! isset($_data['jpegphoto']) || $_data['jpegphoto'] === '') {
            return;
        }

        $imageParams = Tinebase_ImageHelper::parseImageLink($_data['jpegphoto']);
        if ($imageParams['isNewImage']) {
            try {
                $_data['jpegphoto'] = Tinebase_ImageHelper::getImageData($imageParams);
            } catch (Tinebase_Exception_UnexpectedValue $teuv) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not add contact image: ' . $teuv->getMessage());
                unset($_data['jpegphoto']);
            }
        } else {
            unset($_data['jpegphoto']);
        }
    }

    /**
     * set small contact image
     *
     * @param $newPhotoBlob
     * @param $maxSize
     */
    public function setSmallContactImage($newPhotoBlob, $maxSize = self::SMALL_PHOTO_SIZE)
    {
        if ($this->getId()) {
            try {
                $currentPhoto = Tinebase_Controller::getInstance()->getImage('Addressbook', $this->getId())->getBlob('image/jpeg', $maxSize);
            } catch (Exception $e) {
                // no current photo
            }
        }

        if (isset($currentPhoto) && $currentPhoto == $newPhotoBlob) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__
                . " Photo did not change -> preserving current photo");
            }
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__
                . " Setting new contact photo (" . strlen((string)$newPhotoBlob) . "KB)");
            }
            $this->jpegphoto = $newPhotoBlob;
        }
    }

    /**
     * return small contact image for sync
     *
     * @param $maxSize
     *
     * @return string
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function getSmallContactImage($maxSize = self::SMALL_PHOTO_SIZE)
    {
        $image = Tinebase_Controller::getInstance()->getImage('Addressbook', $this->getId());
        return $image->getBlob('image/jpeg', $maxSize);
    }

    /**
     * get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->n_fn;
    }

    /**
     * returns an array containing the parent neighbours relation objects or record(s) (ids) in the key 'parents'
     * and containing the children neighbours in the key 'children'
     *
     * @return array
     */
    public function getPathNeighbours()
    {
        $listController = Addressbook_Controller_List::getInstance();
        $oldAclCheck = $listController->doContainerACLChecks(false);

        $lists = $listController->search(new Addressbook_Model_ListFilter(array(
            array('field' => 'contact',     'operator' => 'equals', 'value' => $this->getId())
        )));

        $listMemberRoles = $listController->getMemberRolesBackend()->search(new Addressbook_Model_ListMemberRoleFilter(array(
            array('field' => 'contact_id',  'operator' => 'equals', 'value' => $this->getId())
        )));

        /** @var Addressbook_Model_ListMemberRole $listMemberRole */
        foreach ($listMemberRoles as $listMemberRole) {
            $lists->removeById($listMemberRole->list_id);
        }

        $result = parent::getPathNeighbours();
        $result['parents'] = array_merge($result['parents'], $lists->asArray(), $listMemberRoles->asArray());

        $listController->doContainerACLChecks($oldAclCheck);

        return $result;
    }

    /**
     * @return bool
     */
    public static function generatesPaths()
    {
        return true;
    }

    /**
     * moved here from vevent converter -> @TODO improve me
     *
     * @param $fullName
     * @return array
     */
    public static function splitName($fullName)
    {
        if (preg_match('/(?P<firstName>\S*) (?P<lastNameName>\S*)/', $fullName, $matches)) {
            $firstName = $matches['firstName'];
            $lastName  = $matches['lastNameName'];
        } else {
            $firstName = null;
            $lastName  = $fullName;
        }

        return [
            'n_given' => $firstName,
            'n_family' => $lastName
        ];
    }

    public function resolveAttenderCleanUp()
    {
        if (!static::$doResolveAttenderCleanUp) {
            return;
        }

        $this->_data = array_intersect_key($this->_data, [
            'id'          => true,
            'note'        => true,
            'email'       => true,
            'email_home'  => true,
            'salutation'  => true,
            'n_family'    => true,
            'n_given'     => true,
            'n_fileas'    => true,
            'n_fn'        => true,
            'n_short'     => true,
            'account_id'  => true,
            'org_name'    => true,
        ]);
    }

    public function unsetFieldsBeforeConvertingToJson()
    {
        parent::unsetFieldsBeforeConvertingToJson();

        unset($this->jpegphoto);
    }


    /**
     * get contacts recipient token
     *
     * - if preferred email is not set , return the first none empty email field
     *
     * @todo: which email field to return if we have multiple custom emails?
     * @param bool $preferredEmailOnly
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function getRecipientTokens(bool $preferredEmailOnly = false): array
    {
        $possibleAddresses = [];

        if (class_exists('GDPR_Controller_DataIntendedPurposeRecord')
            && Tinebase_Application::getInstance()->isInstalled('GDPR', checkEnabled: true)
        ) {
            $expander = new Tinebase_Record_Expander(Addressbook_Model_Contact::class, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    GDPR_Controller_DataIntendedPurposeRecord::ADB_CONTACT_CUSTOM_FIELD_NAME => [
                        Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                            GDPR_Model_DataIntendedPurposeRecord::FLD_INTENDEDPURPOSE => [
                                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                                    GDPR_Model_DataIntendedPurpose::FLD_NAME => [],
                                    GDPR_Model_DataIntendedPurpose::FLD_DESCRIPTION => [],
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
            $expander->expand(new Tinebase_Record_RecordSet(Addressbook_Model_Contact::class, [$this]));
        }
        $emailFields = array_keys(self::getEmailFields());
        foreach ($emailFields as $emailField) {
            if (empty($this[$emailField]) || ($preferredEmailOnly && $emailField !== $this->preferred_email && count($possibleAddresses) > 0)) {
                continue;
            }
            $possibleAddresses[] = [
                "n_fileas" => $this->n_fileas ?? '',
                "name" => $this->n_fn ?? '',
                "type" =>  $this->type ?? '',
                "email" => $this[$emailField],
                "email_type_field" => $emailField,
                "contact_record" => $this->toArray(),
                "is_private" => $this->isPrivateEmail($this[$emailField], $emailField),
            ];
        }

        return  $possibleAddresses;
    }
}

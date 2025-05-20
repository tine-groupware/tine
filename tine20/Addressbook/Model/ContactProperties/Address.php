<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Contact Property Address Model
 *
 * @package     Addressbook
 * @subpackage  Model
 */
class Addressbook_Model_ContactProperties_Address extends Tinebase_Record_NewAbstract
    implements Addressbook_Model_ContactProperties_Interface, Tinebase_Record_JsonFacadeInterface
{
    public const FLD_CONTACT_ID = 'contact_id';
    public const FLD_COUNTRYNAME = 'countryname';
    public const FLD_LAT = 'lat';
    public const FLD_LOCALITY = 'locality';
    public const FLD_LON = 'lon';
    public const FLD_POSTALCODE = 'postalcode';
    public const FLD_REGION = 'region';
    public const FLD_STREET = 'street';
    public const FLD_STREET2 = 'street2';

    public const MODEL_NAME_PART = 'ContactProperties_Address';
    public const TABLE_NAME = 'addressbook_address';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::MODLOG_ACTIVE => true,
        self::IS_DEPENDENT => true,
        self::DELEGATED_ACL_FIELD => self::FLD_CONTACT_ID,
        self::JSON_EXPANDER => null,

        self::APP_NAME => Addressbook_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::RECORD_NAME => 'Address', // gettext('GENDER_Address')
        self::RECORDS_NAME => 'Addresses', // ngettext('Address', 'Addresses', n)

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
        ],

        self::ASSOCIATIONS                  => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                self::FLD_CONTACT_ID       => [
                    self::TARGET_ENTITY             => Addressbook_Model_Contact::class,
                    self::FIELD_NAME                => self::FLD_CONTACT_ID,
                    self::JOIN_COLUMNS                  => [[
                        self::NAME                          => self::FLD_CONTACT_ID,
                        self::REFERENCED_COLUMN_NAME        => self::ID,
                        self::ON_DELETE                     => self::CASCADE,
                    ]],
                ],
            ],
        ],

        self::FIELDS                    => [
            self::FLD_CONTACT_ID            => [
                self::TYPE                      => self::TYPE_RECORD,
                self::CONFIG                    => [
                    self::APP_NAME                  => Addressbook_Config::APP_NAME,
                    self::MODEL_NAME                => Addressbook_Model_Contact::MODEL_NAME_PART,
                ],
                self::DISABLED => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_TYPE                  => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
            ],
            self::FLD_COUNTRYNAME           => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Country', // _('Country')
                self::INPUT_FILTERS             => [Zend_Filter_StringTrim::class, Zend_Filter_StringToUpper::class],
            ],
            self::FLD_LOCALITY              => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'City', // _('City')
                self::QUERY_FILTER              => true,
            ],
            self::FLD_POSTALCODE            => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Postalcode', // _('Postalcode')
            ],
            self::FLD_REGION                => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Region', // _('Region')
            ],
            self::FLD_STREET                => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Street', // _('Street')
            ],
            self::FLD_STREET2               => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Street 2', // _('Street 2')
            ],
            self::FLD_LON                   => [
                self::TYPE                      => self::TYPE_FLOAT,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Longitude', // _('Longitude')
                self::INPUT_FILTERS             => [Zend_Filter_Empty::class => null],
                self::UI_CONFIG                 => [
                    'omitDuplicateResolving'        => true,
                ],
            ],
            self::FLD_LAT                   => [
                self::TYPE                      => self::TYPE_FLOAT,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Latitude', // _('Latitude')
                self::INPUT_FILTERS             => [Zend_Filter_Empty::class => null],
                self::UI_CONFIG                 => [
                    'omitDuplicateResolving'        => true,
                ],
            ],
        ],
    ];

    static public function updateCustomFieldConfig(Tinebase_Model_CustomField_Config $cfc,
                                                   Addressbook_Model_ContactProperties_Definition $def): void
    {
        switch ($def->{Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE}) {
            case Addressbook_Model_ContactProperties_Definition::LINK_TYPE_RECORD:
                /*$cfc->xprops('definition')[Tinebase_Model_CustomField_Config::CONTROLLER_HOOKS] = [
                    '_jsonExpander' => [
                        Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                            $def->{Addressbook_Model_ContactProperties_Definition::FLD_NAME} => [],
                        ],
                    ],
                ];*/
                $cfc->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD] = [
                    self::TYPE => self::TYPE_RECORD,
                    self::LABEL => $def->{Addressbook_Model_ContactProperties_Definition::FLD_LABEL},
                    self::NULLABLE => true,
                    self::DOCTRINE_IGNORE => true,
                    self::CONFIG => [
                        self::APP_NAME => Addressbook_Config::APP_NAME,
                        self::MODEL_NAME => Addressbook_Model_ContactProperties_Address::MODEL_NAME_PART,
                        self::DEPENDENT_RECORDS => true,
                        self::REF_ID_FIELD => self::FLD_CONTACT_ID,
                        self::FORCE_VALUES              => [
                            Addressbook_Model_ContactProperties_Address::FLD_TYPE => $def->{Addressbook_Model_ContactProperties_Definition::FLD_NAME},
                        ],
                        self::ADD_FILTERS               => [
                            ['field' => Addressbook_Model_ContactProperties_Address::FLD_TYPE, 'operator' => 'equals', 'value' => $def->{Addressbook_Model_ContactProperties_Definition::FLD_NAME}],
                        ],
                    ],
                ];
                $grants = $def->{Addressbook_Model_ContactProperties_Definition::FLD_GRANT_MATRIX};
                if (!empty($grants)) {
                    $cfc->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD][self::REQUIRED_GRANTS] = $grants;
                }
                break;
            case Addressbook_Model_ContactProperties_Definition::LINK_TYPE_RECORDS:
            default:
                throw new Tinebase_Exception_NotImplemented(
                    $def->{Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE} . ' is not implemented');
        }
    }

    public static function applyJsonFacadeMC(array &$fields, Addressbook_Model_ContactProperties_Definition $def): void
    {
        if (!isset($fields[$def->{Addressbook_Model_ContactProperties_Definition::FLD_NAME}][self::CONFIG][self::JSON_FACADE]) ||
                empty($grants = $def->{Addressbook_Model_ContactProperties_Definition::FLD_GRANT_MATRIX})) {
            return;
        }
        $prefix = $fields[$def->{Addressbook_Model_ContactProperties_Definition::FLD_NAME}][self::CONFIG][self::JSON_FACADE];
        foreach (self::$_facadeFields as $field) {
            $fields[$prefix . $field][self::REQUIRED_GRANTS] = $grants;
        }
    }

    public static function jsonFacadeToJson(Tinebase_Record_Interface $record, string $fieldKey, array $def): void
    {
        if (!isset($def[self::CONFIG][self::JSON_FACADE])) {
            return;
        }
        $prefix = $def[self::CONFIG][self::JSON_FACADE];
        $self = new self([
            self::FLD_CONTACT_ID => $record->getId(),
        ], true);

        foreach (self::$_facadeFields as $field) {
            $self->{$field} = $record->{$prefix . $field};
        }

        $record->{$fieldKey} = $self;
    }

    public function jsonFacadeFromJson(Tinebase_Record_Interface $record, array $def): void
    {
        if (!isset($def[self::CONFIG][self::JSON_FACADE])) {
            return;
        }
        $prefix = $def[self::CONFIG][self::JSON_FACADE];
        foreach (self::$_facadeFields as $field) {
            $record->{$prefix . $field} = $this->{$field};
        }
        $record->{$def[Tinebase_ModelConfiguration_Const::FIELD_NAME]} = null;
    }

    protected static array $_facadeFields = [
        self::FLD_COUNTRYNAME,
        self::FLD_LAT,
        self::FLD_LOCALITY,
        self::FLD_LON,
        self::FLD_POSTALCODE,
        self::FLD_REGION,
        self::FLD_STREET,
        self::FLD_STREET2,
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}

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
 * Contact Property InstantMessenger Model
 *
 * @package     Addressbook
 * @subpackage  Model
 */
class Addressbook_Model_ContactProperties_InstantMessenger extends Tinebase_Record_NewAbstract
    implements Addressbook_Model_ContactProperties_Interface, Tinebase_Record_JsonFacadeInterface
{
    /*public const FLD_CONTACT_ID = 'contact_id';
    public const FLD_URL = 'url';*/

    public const MODEL_NAME_PART = 'ContactProperties_InstantMessenger';
    //public const TABLE_NAME = 'addressbook_im';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        /*self::VERSION => 1,
        self::MODLOG_ACTIVE => true,
        self::IS_DEPENDENT => true,
        self::DELEGATED_ACL_FIELD => self::FLD_CONTACT_ID,*/

        self::APP_NAME => Addressbook_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::RECORD_NAME => 'Instant Messenger', // gettext('GENDER_Instant Messenger')
        self::RECORDS_NAME => 'Instant Messengers', // ngettext('Instant Messenger', 'Instant Messengers', n)

        /*self::TABLE => [
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
        ],*/

        self::FIELDS                    => [
            /*self::FLD_CONTACT_ID            => [
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
            self::FLD_EMAIL                 => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Email', // _('Email')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [Zend_Filter_StringTrim::class, Zend_Filter_StringToLower::class],
                self::QUERY_FILTER              => true,
            ],*/
        ],
    ];

    static public function updateCustomFieldConfig(Tinebase_Model_CustomField_Config $cfc,
                                                   Addressbook_Model_ContactProperties_Definition $def): void
    {
        switch ($def->{Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE}) {
            case Addressbook_Model_ContactProperties_Definition::LINK_TYPE_INLINE:
                $cfc->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD] = [
                    self::TYPE                      => self::TYPE_TEXT,
                    self::LENGTH                    => 255,
                    self::NULLABLE                  => true,
                    self::LABEL                     => $def->{Addressbook_Model_ContactProperties_Definition::FLD_LABEL},
                    self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                    self::INPUT_FILTERS             => [Zend_Filter_StringTrim::class],
                ];
                $grants = $def->{Addressbook_Model_ContactProperties_Definition::FLD_GRANT_MATRIX};
                if (!empty($grants)) {
                    $cfc->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD][self::REQUIRED_GRANTS] = $grants;
                }
                break;
            case Addressbook_Model_ContactProperties_Definition::LINK_TYPE_RECORDS:
            case Addressbook_Model_ContactProperties_Definition::LINK_TYPE_RECORD:
            default:
                throw new Tinebase_Exception_NotImplemented(
                    $def->{Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE} . ' is not implemented');
        }
    }

    public static function applyJsonFacadeMC(array &$fields, Addressbook_Model_ContactProperties_Definition $def): void
    {
    }

    public static function jsonFacadeToJson(Tinebase_Record_Interface $record, string $fieldKey, array $def): void
    {
    }

    public function jsonFacadeFromJson(Tinebase_Record_Interface $record, array $def): void
    {
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}

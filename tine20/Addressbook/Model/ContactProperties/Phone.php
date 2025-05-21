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
 * Contact Property Phone Model
 *
 * @package     Addressbook
 * @subpackage  Model
 */
class Addressbook_Model_ContactProperties_Phone extends Tinebase_Record_NewAbstract
    implements Addressbook_Model_ContactProperties_Interface, Tinebase_Record_JsonFacadeInterface
{
    public const FLD_CONTACT_ID = 'contact_id';
    public const FLD_NUMBER = 'number';
    public const FLD_NUMBER_NORMALIZED = 'number_normalized';

    public const MODEL_NAME_PART = 'ContactProperties_Phone';
    //public const TABLE_NAME = 'addressbook_phone';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        //self::VERSION => 1,
        self::MODLOG_ACTIVE => true,
        self::IS_DEPENDENT => true,
        self::DELEGATED_ACL_FIELD => self::FLD_CONTACT_ID,

        self::APP_NAME => Addressbook_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::RECORD_NAME => 'Phone', // gettext('GENDER_Phone')
        self::RECORDS_NAME => 'Phone', // ngettext('Phone', 'Phones', n)

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
            self::FLD_NUMBER                => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
            ],
            self::FLD_NUMBER_NORMALIZED     => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
            ],*/
        ],
    ];

    static public function updateCustomFieldConfig(Tinebase_Model_CustomField_Config $cfc,
                                                   Addressbook_Model_ContactProperties_Definition $def): void
    {
        switch ($def->{Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE}) {
            case Addressbook_Model_ContactProperties_Definition::LINK_TYPE_INLINE:
                $cfc->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD] = [
                    self::TYPE                      => self::TYPE_STRING,
                    self::LENGTH                    => 86,
                    self::LABEL                     => $def->{Addressbook_Model_ContactProperties_Definition::FLD_LABEL},
                    self::NULLABLE                  => true,
                ];
                $grants = $def->{Addressbook_Model_ContactProperties_Definition::FLD_GRANT_MATRIX};
                if (!empty($grants)) {
                    $cfc->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD][self::REQUIRED_GRANTS] = $grants;
                }

                $cfCtrl = Tinebase_CustomField::getInstance();
                $nameNorm = $cfc->name . '_normalized';
                $cfcNorm = $cfCtrl->getCustomFieldByNameAndApplication($cfc->application_id, $nameNorm,
                    Addressbook_Model_Contact::class, true);
                if (null === $cfcNorm) {
                    $cfcNorm = new Tinebase_Model_CustomField_Config([
                        'is_system' => true,
                        'application_id' => $cfc->application_id,
                        'model' => Addressbook_Model_Contact::class,
                        'name' => $nameNorm,
                    ], true);
                }
                $cfcNorm->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD] = [
                    self::TYPE                      => self::TYPE_STRING,
                    self::LENGTH                    => 86,
                    self::NULLABLE                  => true,
                    self::SYSTEM                    => true,
                    self::DISABLED                  => true,
                ];
                if (!empty($grants)) {
                    $cfcNorm->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD][self::REQUIRED_GRANTS] = $grants;
                }
                $cfCtrl->addCustomField($cfcNorm);

                break;
            case Addressbook_Model_ContactProperties_Definition::LINK_TYPE_RECORDS:
                /*$cfc->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD] = [
                    self::TYPE => self::TYPE_RECORD,
                    self::CONFIG => [
                        self::APP_NAME => Addressbook_Config::APP_NAME,
                        self::MODEL_NAME => Addressbook_Model_ContactProperties_Address::MODEL_NAME_PART,
                    ],
                ];
                $grants = $def->{Addressbook_Model_ContactProperties_Definition::FLD_GRANT_MATRIX};
                if (!empty($grants)) {
                    $cfc->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD][self::REQUIRED_GRANTS] = $grants;
                }
                break;*/
            case Addressbook_Model_ContactProperties_Definition::LINK_TYPE_RECORD:
            default:
                throw new Tinebase_Exception_NotImplemented(
                    $def->{Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE} . ' is not implemented');
        }
    }

    public static function applyJsonFacadeMC(array &$fields, Addressbook_Model_ContactProperties_Definition $def): void
    {
        $grants = $def->{Addressbook_Model_ContactProperties_Definition::FLD_GRANT_MATRIX};
        if (empty($grants)) {
            return;
        }
        $fields[$def->{Addressbook_Model_ContactProperties_Definition::FLD_NAME} . '_normalized']
            [self::REQUIRED_GRANTS] = $grants;
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

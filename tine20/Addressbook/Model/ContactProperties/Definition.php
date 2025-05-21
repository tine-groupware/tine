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
class Addressbook_Model_ContactProperties_Definition extends Tinebase_Record_NewAbstract
{
    public const FLD_ACTIVE_SYNC_MAP = 'active_sync_map';
    public const FLD_GRANT_MATRIX = 'grant_matrix';
    public const FLD_IS_APPLIED = 'is_applied';
    public const FLD_IS_SYSTEM = 'is_system';
    public const FLD_LAST_ERROR = 'last_error';
    public const FLD_LINK_TYPE = 'link_type';
    public const FLD_MODEL = 'model';
    public const FLD_NAME = 'name';
    public const FLD_LABEL = 'label';
    public const FLD_GROUPING = 'grouping';
    public const FLD_SORTING = 'sorting';
    public const FLD_VCARD_MAP = 'vcard_map';

    public const LINK_TYPE_INLINE = 'inline';
    public const LINK_TYPE_RECORD = self::TYPE_RECORD;
    public const LINK_TYPE_RECORDS = self::TYPE_RECORDS;

    public const MODEL_NAME_PART = 'ContactProperties_Definition';
    public const TABLE_NAME = 'addressbook_definition';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::MODLOG_ACTIVE => true,
        self::IS_DEPENDENT => true,

        self::APP_NAME => Addressbook_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::EXPOSE_JSON_API => true,

        self::RECORD_NAME => 'Property definition', // gettext('GENDER_Property definition')
        self::RECORDS_NAME => 'Property definitions', // ngettext('Property definition', 'Property definitions', n)

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
        ],

        // TODO FIXME make name unique

        self::FIELDS                        => [
            self::FLD_NAME                      => [
                self::LABEL                         => 'Name', // _('Name')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::QUERY_FILTER                  => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_LABEL                     => [
                self::LABEL                         => 'Label', // _('Label')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::QUERY_FILTER                  => true,
                self::NULLABLE                      => true,
            ],
            self::FLD_GROUPING                  => [
                self::LABEL                         => 'Grouping', // _('Grouping')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
            ],
            self::FLD_SORTING                   => [
                self::LABEL                         => 'Sorting', // _('Sorting')
                self::TYPE                          => self::TYPE_INTEGER,
                self::NULLABLE                      => true,
            ],
            self::FLD_IS_SYSTEM                => [
                self::LABEL                         => 'System', // _('System')
                self::TYPE                          => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL                   => false,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_IS_APPLIED               => [
                self::TYPE                          => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL                   => false,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
            self::FLD_MODEL                     => [
                self::LABEL                         => 'Model', // _('Model')
                self::TYPE                          => self::TYPE_MODEL,
                self::QUERY_FILTER                  => true,
                self::CONFIG                        => [
                    self::AVAILABLE_MODELS              => [
                        Addressbook_Model_ContactProperties_Address::class,
                        Addressbook_Model_ContactProperties_Email::class,
                        Addressbook_Model_ContactProperties_Phone::class,
                        Addressbook_Model_ContactProperties_Url::class,
                        Addressbook_Model_ContactProperties_InstantMessenger::class,
                    ],
                ],
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                    ['InArray', [
                        Addressbook_Model_ContactProperties_Address::class,
                        Addressbook_Model_ContactProperties_Email::class,
                        Addressbook_Model_ContactProperties_Phone::class,
                        Addressbook_Model_ContactProperties_Url::class,
                        Addressbook_Model_ContactProperties_InstantMessenger::class,
                    ]],
                ],
            ],
            self::FLD_LINK_TYPE                 => [
                self::LABEL                         => 'Link Type', // _('Link Type')
                self::TYPE                          => self::TYPE_KEY_FIELD,
                self::NAME                          => Addressbook_Config::CONTACT_PROPERTY_LINK_TYPES,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                    [Zend_Validate_InArray::class, [self::LINK_TYPE_INLINE, self::LINK_TYPE_RECORD, self::LINK_TYPE_RECORDS]],

                ],
                self::SHY                           => true,
            ],
            self::FLD_GRANT_MATRIX              => [
                self::LABEL                         => 'Grant Matrix', // _('Grant Matrix')
                self::TYPE                          => self::TYPE_JSON, // Tinebase_Model_GrantContext => keyfield in der config
                self::NULLABLE                      => true,
                self::SHY                           => true,
            ],
            self::FLD_ACTIVE_SYNC_MAP           => [
                self::LABEL                         => 'ActiveSync Map', // _('ActiveSync Map')
                self::TYPE                          => self::TYPE_JSON,
                self::NULLABLE                      => true,
                self::SHY                           => true,
            ],
            self::FLD_VCARD_MAP                 => [
                self::LABEL                         => 'VCard Map', // _('VCard Map')
                self::TYPE                          => self::TYPE_JSON,
                self::NULLABLE                      => true,
                self::SHY                           => true,
            ],
            self::FLD_LAST_ERROR                => [
                self::LABEL                         => 'Last Error', // _('Last Error')
                self::TYPE                          => self::TYPE_TEXT,
                self::NULLABLE                      => true,
                self::SHY                           => true,
                self::UI_CONFIG                     => [
                    self::READ_ONLY                     => true,
                ],
            ],
        ],
    ];

    public static $doNotApplyToContactModel = false;

    public function removeFromContactModel(): void
    {
        if ($this->{self::FLD_IS_SYSTEM}) {
            throw new Tinebase_Exception_AccessDenied('system definitions can not be removed');
        }

        $appId = Tinebase_Application::getInstance()->getApplicationByName(Addressbook_Config::APP_NAME)->getId();
        $cfCtrl = Tinebase_CustomField::getInstance();

        if ($this->{self::FLD_LINK_TYPE} !== self::LINK_TYPE_INLINE) {
            /** @var Tinebase_Controller_Record_Abstract $modelCtrl */
            $modelCtrl = Tinebase_Core::getApplicationInstance($this->{self::FLD_MODEL});
            $modelCtrl->deleteByFilter(Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->{self::FLD_MODEL}, [
                [
                    Tinebase_Model_Filter_Abstract::FIELD => Addressbook_Model_ContactProperties_Interface::FLD_TYPE,
                    Tinebase_Model_Filter_Abstract::OPERATOR => 'equals',
                    Tinebase_Model_Filter_Abstract::VALUE => $this->{Addressbook_Model_ContactProperties_Definition::FLD_NAME},
                ],
            ]));
        }

        if (null !== $cfCtrl->getCustomFieldByNameAndApplication($appId,
                $this->{Addressbook_Model_ContactProperties_Definition::FLD_NAME}, Addressbook_Model_Contact::class)) {
            // LOG ERROR
            return;
        }

        if (null === ($cfc = $cfCtrl->getCustomFieldByNameAndApplication($appId,
                $this->{Addressbook_Model_ContactProperties_Definition::FLD_NAME}, Addressbook_Model_Contact::class, true))) {
            // LOG ERROR
            return;
        }

        $cfCtrl->deleteCustomField($cfc);
    }

    public function applyToContactModel(): void
    {
        if (static::$doNotApplyToContactModel) {
            return;
        }
        
        $appId = Tinebase_Application::getInstance()->getApplicationByName(Addressbook_Config::APP_NAME)->getId();
        $cfCtrl = Tinebase_CustomField::getInstance();

        if (null !== $cfCtrl->getCustomFieldByNameAndApplication($appId,
                $this->{Addressbook_Model_ContactProperties_Definition::FLD_NAME}, Addressbook_Model_Contact::class)) {
            $this->{self::FLD_LAST_ERROR} = 'cf already exists as non-system';
            return;
        }
        $this->{self::FLD_LAST_ERROR} = null;

        $cfc = $cfCtrl->getCustomFieldByNameAndApplication($appId,
            $this->{Addressbook_Model_ContactProperties_Definition::FLD_NAME}, Addressbook_Model_Contact::class, true);

        if (null === $cfc) {
            $cfc = new Tinebase_Model_CustomField_Config([
                'is_system' => true,
                'application_id' => $appId,
                'model' => Addressbook_Model_Contact::class,
            ], true);
        }
        $cfc->name = $this->{self::FLD_NAME}; // we need it here, so the model knows the name, actually not, go an refactor it

        /** @var Addressbook_Model_ContactProperties_Interface $model */
        $model = $this->{self::FLD_MODEL};
        $model::updateCustomFieldConfig($cfc, $this);

        $cfc->name = $this->{self::FLD_NAME}; // we enforce it here again, why not
        $cfc->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD][self::SPECIAL_TYPE] = $model;
        $cfc->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD][self::UI_CONFIG]['order'] =
            $this->{self::FLD_SORTING};
        $cfc->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD][self::UI_CONFIG]['group'] =
            $this->{self::FLD_GROUPING};

        if ($cfc->getId()) {
            $cfCtrl->updateCustomField($cfc);
        } else {
            $cfCtrl->addCustomField($cfc);
        }

        $this->{self::FLD_IS_APPLIED} = true;
        Addressbook_Controller_ContactProperties_Definition::getInstance()->getBackend()->update($this);
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public function isReplicable()
    {
        return true;
    }
}

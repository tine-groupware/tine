<?php declare(strict_types=1);

/**
 * class to hold External Idp data
 *
 * @package     SSO
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold External Idp data
 *
 * @package     SSO
 * @subpackage  Model
 *
 * @property ?SSO_ExIdpConfigInterface $config
 */
class SSO_Model_ExternalIdp extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'ExternalIdp';
    public const TABLE_NAME = 'sso_external_idp';

    public const SESSION_KEY = 'externalAuthIdpUsed';

    public const FLD_CONFIG = 'config';
    public const FLD_CONFIG_CLASS = 'config_class';
    public const FLD_DESCRIPTION = 'description';
    public const FLD_NAME = 'name';
    public const FLD_DOMAINS = 'domains';
    public const FLD_SHOW_AS_LOGIN_OPTION = 'show_login_option';
    public const FLD_LABEL = 'label';
    public const FLD_LOGO_DARK = 'logo_dark';
    public const FLD_LOGO_LIGHT = 'logo_light';
    public const FLD_ALLOW_EXISTING_LOCAL_ACCOUNT = 'allow_existing_local_account';
    public const FLD_ALLOW_REASSIGN_LOCAL_ACCOUNT = 'allow_reassign_local_account';
    public const FLD_ALLOW_CREATE_LOCAL_ACCOUNT = 'allow_create_local_account';
    public const FLD_USERNAME_CLAIM = 'username_claim';
    public const FLD_ALLOW_LOCAL_LOGIN = 'allow_local_logiin';
    public const FLD_REQUIRE_LOCAL_MFA = 'require_local_mfa';
    public const FLD_UPDATE_LOCAL_PROPERTIES = 'update_local_properties';
    public const FLD_PRIMARY_GROUP_NEW_ACCOUNT = 'primary_group_new_account';
    public const FLD_GROUPS_NEW_ACCOUNT = 'groups_new_account';
    public const FLD_DENY_GROUPS = 'deny_groups';
    public const FLD_DENY_ROLES = 'deny_roles';
    public const FLD_REQUIRED_GROUP_CLAIMS = 'required_group_claims';
    public const FLD_GROUPS_CLAIM_NAME = 'groups_claim_name';
    public const FLD_CREATE_GROUPS = 'create_groups';
    public const FLD_ASSIGN_GROUPS = 'assign_groups';
    public const FLD_ACCOUNT_PREFIX = 'account_prefix';
    public const FLD_ACCOUNT_DISPLAY_NAME_PREFIX = 'account_display_name_prefix';
    public const FLD_GROUP_PREFIX = 'group_prefix';
    public const FLD_ADDRESSBOOK = 'addressbook';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 7,
        self::RECORD_NAME => 'External Identity Provider',
        self::RECORDS_NAME => 'External Identity Providers', // ngettext('External Identity Provider', 'External Identity Providers', n)
        self::TITLE_PROPERTY => self::FLD_NAME,
        self::MODLOG_ACTIVE => true,
        self::HAS_DELETED_TIME_UNIQUE => true,
        self::EXPOSE_JSON_API => true,
        self::CREATE_MODULE => true,

        self::APP_NAME => SSO_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS => [
                self::FLD_NAME => [
                    self::COLUMNS => [self::FLD_NAME, self::FLD_DELETED_TIME]
                ],
            ],
        ],

        self::SKIP_LEGACY_JSON_CONVERT => true,
        self::JSON_EXPANDER => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_DOMAINS => [],
                self::FLD_GROUPS_NEW_ACCOUNT => [],
                self::FLD_PRIMARY_GROUP_NEW_ACCOUNT => [],
                self::FLD_DENY_GROUPS => [],
                self::FLD_DENY_ROLES => [],
            ],
        ],

        self::FIELDS => [
            self::FLD_NAME              => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::QUERY_FILTER          => true,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL                 => 'Name', // _('Name')
            ],
            self::FLD_DESCRIPTION       => [
                self::TYPE                  => self::TYPE_TEXT,
                self::QUERY_FILTER          => true,
                self::NULLABLE              => true,
                self::LABEL                 => 'Description', // _('Description')
            ],
            self::FLD_CONFIG_CLASS      => [
                self::TYPE                  => self::TYPE_MODEL,
                self::LABEL                 => 'Specific Type', // _('Specific Type')
                self::FILTER_DEFINITION     => [self::FILTER => Tinebase_Model_Filter_Text::class],
                self::CONFIG                    => [
                    self::AVAILABLE_MODELS              => [
                        SSO_Model_ExIdp_OIdConfig::class,
                    ],
                ],
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                    [Zend_Validate_InArray::class, [
                        SSO_Model_ExIdp_OIdConfig::class,
                    ]],
                ],
            ],
            self::FLD_CONFIG            => [
                self::TYPE                  => self::TYPE_DYNAMIC_RECORD,
                self::LABEL                 => 'Specific Configuration', // _('Specific Configuration')
                self::CONFIG                => [
                    self::REF_MODEL_FIELD       => self::FLD_CONFIG_CLASS,
                    self::PERSISTENT            => true,
                ],
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                    [Tinebase_Record_Validator_SubValidate::class],
                ],
            ],
            self::FLD_DOMAINS           => [
                self::LABEL                 => 'Domains', // _('Domains')
                self::TYPE                  => self::TYPE_RECORDS,
                self::CONFIG                => [
                    self::DEPENDENT_RECORDS     => true,
                    self::APP_NAME              => SSO_Config::APP_NAME,
                    self::MODEL_NAME            => SSO_Model_ExIdpDomain::MODEL_NAME_PART,
                    self::REF_ID_FIELD          => SSO_Model_ExIdpDomain::FLD_EX_IPD_ID,
                ],
            ],
            self::FLD_SHOW_AS_LOGIN_OPTION => [
                self::LABEL                 => 'Show on Login Screen', // _('Show on Login Screen')
                self::TYPE                  => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL           => false,
            ],
            self::FLD_LABEL             => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::QUERY_FILTER          => true,
                self::NULLABLE              => true,
                self::LABEL                 => 'Label', // _('Label')
            ],
            self::FLD_LOGO_LIGHT        => [
                self::TYPE                  => self::TYPE_BLOB,
                self::NULLABLE              => true,
                self::LABEL                 => 'Logo Light', // _('Logo Light')
            ],
            self::FLD_LOGO_DARK         => [
                self::TYPE                  => self::TYPE_BLOB,
                self::NULLABLE              => true,
                self::LABEL                 => 'Logo Dark', // _('Logo Dark')
            ],
            self::FLD_ALLOW_EXISTING_LOCAL_ACCOUNT => [
                self::TYPE                  => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL           => false,
                self::LABEL                 => 'Allow assignment of existing local accounts', // _('Allow assignment of existing local accounts')
            ],
            self::FLD_ALLOW_REASSIGN_LOCAL_ACCOUNT => [
                self::TYPE                  => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL           => false,
                self::LABEL                 => 'Allow reassignment of existing local accounts', // _('Allow reassignment of existing local accounts')
            ],
            self::FLD_ALLOW_CREATE_LOCAL_ACCOUNT => [
                self::TYPE                  => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL           => true,
                self::LABEL                 => 'Allow creation of new local accounts', // _('Allow creation of new local accounts')
            ],
            self::FLD_USERNAME_CLAIM        => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::DEFAULT_VAL           => 'preferred_username',
                self::LABEL                  => 'Username claim', // _('Username claim')
                self::DESCRIPTION            => 'preferred_username, email, email:local_part, ...',
            ],
            self::FLD_ALLOW_LOCAL_LOGIN => [
                self::TYPE                  => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL           => false,
                self::LABEL                 => 'Allow local login of assigned accounts', // _('Allow local login of assigned accounts')
            ],
            self::FLD_REQUIRE_LOCAL_MFA => [
                self::TYPE                  => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL           => false,
                self::LABEL                 => 'Require local MFA', // _('Require local MFA')
            ],
            self::FLD_UPDATE_LOCAL_PROPERTIES => [
                self::TYPE                  => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL           => true,
                self::LABEL                 => 'Update local properties', // _('Update local properties')
            ],
            self::FLD_PRIMARY_GROUP_NEW_ACCOUNT => [
                self::TYPE                  => self::TYPE_RECORD,
                self::NULLABLE              => true,
                self::CONFIG                    => [
                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                => 'Group',
                ],
                self::LABEL                 => 'Primary group for created local accounts', // _('Primary group for created local accounts')
            ],
            self::FLD_GROUPS_NEW_ACCOUNT => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::NULLABLE                  => true,
                self::CONFIG                    => [
                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                => 'Group',
                    self::STORAGE                   => self::TYPE_JSON_REFID,
                    self::REF_ID_FIELD              => self::ID,
                ],
                self::LABEL                     => 'Groups to be set for created local accounts', // _('Groups to be set for created local accounts')
            ],
            self::FLD_DENY_GROUPS => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::NULLABLE                  => true,
                self::CONFIG                    => [
                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                => 'Group',
                    self::STORAGE                   => self::TYPE_JSON_REFID,
                    self::REF_ID_FIELD              => self::ID,
                ],
                self::LABEL                     => 'Groups that deny login for existing local accounts', // _('Groups that deny login for existing local accounts')
            ],
            self::FLD_DENY_ROLES => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::NULLABLE                  => true,
                self::CONFIG                    => [
                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                => 'Role',
                    self::STORAGE                   => self::TYPE_JSON_REFID,
                ],
                self::LABEL                     => 'Roles that deny login for existing local accounts', // _('Roles that deny login for existing local accounts')
            ],
            self::FLD_REQUIRED_GROUP_CLAIMS => [
                self::TYPE                      => self::TYPE_JSON,
                self::NULLABLE                  => true,
                self::LABEL                     => 'One of these group claims needs to be present', // _('One of these group claims needs to be present')
            ],
            self::FLD_GROUPS_CLAIM_NAME     => [
                self::TYPE                      => self::TYPE_STRING,
                self::NULLABLE                  => true,
                self::LABEL                     => 'The name of the "groups" claim, defaults to "groups"', // _('The name of the "groups" claim, defaults to "groups"')
            ],
            self::FLD_CREATE_GROUPS         => [
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL               => false,
                self::LABEL                     => 'Create groups locally', // _('Create groups locally')
            ],
            self::FLD_ASSIGN_GROUPS         => [
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL               => false,
                self::LABEL                     => 'Assign groups locally', // _('Assign groups locally')
            ],
            self::FLD_ACCOUNT_PREFIX        => [
                self::TYPE                      => self::TYPE_STRING,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Account Name Prefix', // _('Account Name Prefix')
                self::DESCRIPTION               => 'String prefixed to local account names when created from this IDP', // _('String prefixed to local account names when created from this IDP')
            ],
            self::FLD_GROUP_PREFIX          => [
                self::TYPE                      => self::TYPE_STRING,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Group Name Prefix', // _('Group Name Prefix')
                self::DESCRIPTION               => 'String prefixed to local group names when created from this IDP', // _('String prefixed to local group names when created from this IDP')
            ],
            self::FLD_ACCOUNT_DISPLAY_NAME_PREFIX => [
                self::TYPE                      => self::TYPE_STRING,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Account Display Name Prefix', // _('Account Display Name Prefix')
                self::DESCRIPTION               => 'String prefixed to local account display names when created from this IDP', // _('String prefixed to local account display names when created from this IDP')
            ],
            self::FLD_ADDRESSBOOK           => [
                self::TYPE                      => self::TYPE_CONTAINER,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Addressbook Container', // _('Addressbook Container')
                self::DESCRIPTION               => 'Addressbook container to store newly created account contacts and lists', // _('Addressbook container to store newly created account contacts and lists')
                self::FILTER_DEFINITION         => [
                    self::FILTER                    => Tinebase_Model_Filter_Container::class,
                    self::OPTIONS                   => [
                        self::MODEL_NAME                => Addressbook_Model_Contact::class,
                    ],
                ],
            ],
        ],
    ];

    public function toUserArray(): array
    {
        $result = $this->toArray();
        unset($result[self::FLD_CONFIG]);
        unset($result[self::FLD_DOMAINS]);
        unset($result[self::FLD_DESCRIPTION]);
        return $result;
    }
}

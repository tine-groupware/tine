<?php declare(strict_types=1);

/**
 * class to hold External Idp data
 *
 * @package     SSO
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
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

    public const FLD_CONFIG = 'config';
    public const FLD_CONFIG_CLASS = 'config_class';
    public const FLD_DESCRIPTION = 'description';
    public const FLD_NAME = 'name';
    public const FLD_DOMAINS = 'domains';

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
        self::VERSION => 1,
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

        self::JSON_EXPANDER => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_DOMAINS => [],
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
                self::TYPE                  => self::TYPE_RECORDS,
                self::CONFIG                => [
                    self::DEPENDENT_RECORDS     => true,
                    self::APP_NAME              => SSO_Config::APP_NAME,
                    self::MODEL_NAME            => SSO_Model_ExIdpDomain::MODEL_NAME_PART,
                    self::REF_ID_FIELD          => SSO_Model_ExIdpDomain::FLD_EX_IPD_ID,
                ],
            ],
        ]
    ];
}

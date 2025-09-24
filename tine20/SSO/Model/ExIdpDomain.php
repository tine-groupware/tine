<?php declare(strict_types=1);

/**
 * class to hold External Idp Domain data
 *
 * @package     SSO
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold External Idp Domain data
 *
 * @package     SSO
 * @subpackage  Model
 */
class SSO_Model_ExIdpDomain extends Tinebase_Record_Abstract
{
    public const MODEL_NAME_PART = 'ExIdpDomain';
    public const TABLE_NAME = 'sso_external_idp_domain';

    public const FLD_EX_IPD_ID = 'ex_idp_id';
    public const FLD_DOMAIN = 'domain';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::RECORD_NAME => 'External Identity Provider Domain',
        self::RECORDS_NAME => 'External Identity Provider Domains', // ngettext('External Identity Provider Domain', 'External Identity Provider Domains', n)
        self::TITLE_PROPERTY => self::FLD_DOMAIN,
        self::MODLOG_ACTIVE => true,
        self::HAS_DELETED_TIME_UNIQUE => true,
        self::IS_DEPENDENT => true,
        // TODO FIXME this should not expose json api! its required for the FE to get to know the model
        self::EXPOSE_JSON_API => true,

        self::CREATE_MODULE => false,
        self::APP_NAME => SSO_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS => [
                self::FLD_DOMAIN => [
                    self::COLUMNS => [self::FLD_DOMAIN, self::FLD_EX_IPD_ID, self::FLD_DELETED_TIME]
                ]
            ]
        ],

        self::FIELDS => [
            self::FLD_DOMAIN              => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::QUERY_FILTER          => true,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::FILTER_DEFINITION     => [
                    self::FILTER                => Tinebase_Model_Filter_Text::class,
                    self::OPTIONS               => [
                        Tinebase_Model_Filter_Text::CASE_SENSITIVE => false,
                    ],
                ],
                self::LABEL                 => 'Name', // _('Name')
            ],
            self::FLD_EX_IPD_ID         => [
                self::TYPE                  => self::TYPE_RECORD,
                self::CONFIG                => [
                    self::APP_NAME              => SSO_Config::APP_NAME,
                    self::MODEL_NAME            => SSO_Model_ExternalIdp::MODEL_NAME_PART,
                    self::IS_PARENT             => true,
                ],
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::UI_CONFIG             => [
                    self::DISABLED              => true,
                ],
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}

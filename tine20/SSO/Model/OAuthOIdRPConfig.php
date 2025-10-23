<?php declare(strict_types=1);

/**
 * class to hold RelyingParty (SP) config for oauth2 open id connect
 *
 * @package     SSO
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold RelyingParty data
 *
 * @package     SSO
 * @subpackage  Model
 */
class SSO_Model_OAuthOIdRPConfig extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'OAuthOIdRPConfig';

    public const FLD_REDIRECT_URLS = 'redirect_urls';
    public const FLD_SECRET = 'secret';
    public const FLD_IS_CONFIDENTIAL = 'is_confidential';
    public const FLD_OAUTH2_GRANTS = 'oauth2_grants';

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
        self::CREATE_MODULE => false,
        self::APP_NAME => SSO_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,
        self::RECORD_NAME => 'OAuth2 Relying Party Config',
        self::RECORDS_NAME => 'OAuth2 Relying Party Configs', // ngettext('OAuth2 Relying Party Config', 'OAuth2 Relying Party Configs', n)

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_OAUTH2_GRANTS
            ]
        ],

        self::FIELDS => [
            self::FLD_REDIRECT_URLS     => [
                self::TYPE                  => self::TYPE_JSON,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL                 => 'Redirect URLs', // _('Redirect URLs')
            ],
            self::FLD_SECRET            => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL                 => 'Secret', // _('Secret')
            ],
            self::FLD_IS_CONFIDENTIAL   => [
                self::TYPE                  => self::TYPE_BOOLEAN,
                self::INPUT_FILTERS         => [
                    Tinebase_Record_Filter_NotSetDefault::class => true,
                ],
                self::LABEL                 => 'Confidential', // _('Confidential')
                self::DESCRIPTION           => 'Secret will only be validated for confidential relying parties', // _('Secret will only be validated for confidential relying parties')
            ],
            self::FLD_OAUTH2_GRANTS     => [
                self::TYPE                  => self::TYPE_RECORDS,
                self::VALIDATORS            => [
                    Zend_Filter_Input::DEFAULT_VALUE =>  [[Tinebase_Core::class, 'createInstance'], Tinebase_Record_RecordSet::class, SSO_Model_OAuthGrant::class, [
                        [SSO_Model_OAuthGrant::FLD_GRANT => SSO_Config::OAUTH2_GRANTS_AUTHORIZATION_CODE],
                    ]],
                    [Tinebase_Record_Validator_SubValidate::class],
                ],
                self::CONFIG                => [
                    self::APP_NAME                  => SSO_Config::APP_NAME,
                    self::MODEL_NAME                => SSO_Model_OAuthGrant::MODEL_NAME_PART,
                    self::STORAGE                   => self::TYPE_JSON,
                ],
                self::UI_CONFIG             => [
                    ''
                ],
            ],
        ]
    ];
}

<?php declare(strict_types=1);

/**
 * class to hold OAuth Grant data for oauth2 open id connect
 *
 * @package     SSO
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold OAuth Grant data
 *
 * @package     SSO
 * @subpackage  Model
 */
class SSO_Model_OAuthGrant extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'OAuthGrant';

    public const FLD_GRANT = 'grant';

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
        self::RECORD_NAME => 'OAuth2 Grant',
        self::RECORDS_NAME => 'OAuth2 Grants', // ngettext('OAuth2 Grant', 'OAuth2 Grants', n)
        self::TITLE_PROPERTY => self::FLD_GRANT,
        self::IS_METADATA_MODEL_FOR => self::FLD_GRANT,

        self::FIELDS => [
            self::ID                        => [
                self::TYPE                      => self::TYPE_STRING,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => true,
                    Zend_Filter_Input::DEFAULT_VALUE => [[Tinebase_Record_Abstract::class, 'generateUID']],
                ],
            ],
            self::FLD_GRANT             => [
                self::TYPE                  => self::TYPE_KEY_FIELD,
                self::NAME                  => SSO_Config::OAUTH2_GRANTS,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL                 => 'OAuth Grant', // _('OAuth Grant')
                self::CONFIG                => [
                    self::VALIDATE              => true,
                ],
            ],
        ]
    ];
}

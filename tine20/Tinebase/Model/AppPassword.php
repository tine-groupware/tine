<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * AppPassword Model
 *
 * @package     Tinebase
 * @subpackage  Model
 */

class Tinebase_Model_AppPassword extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'AppPassword';
    public const TABLE_NAME = 'app_pwd';

    public const FLD_AUTH_TOKEN = 'auth_token';
    public const FLD_ACCOUNT_ID = 'account_id';
    public const FLD_CHANNELS = 'channels';
    public const FLD_VALID_UNTIL = 'valid_until';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION               => 1,
        self::APP_NAME              => Tinebase_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,
        self::MODLOG_ACTIVE         => false,
        self::EXPOSE_JSON_API       => true,

        self::TABLE                 => [
            self::NAME                  => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS    => [
                self::FLD_ACCOUNT_ID       => [
                    self::COLUMNS               => [self::FLD_ACCOUNT_ID, self::FLD_AUTH_TOKEN]
                ],
            ],
        ],

        self::FIELDS                => [
            self::FLD_AUTH_TOKEN       => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_ACCOUNT_ID       => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 40,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_VALID_UNTIL      => [
                self::TYPE                  => self::TYPE_DATETIME,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_CHANNELS         => [
                self::TYPE                  => self::TYPE_JSON,
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                    Tinebase_Record_Validator_Json::class,
                ],
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public function notifyBroadcastHub(): bool
    {
        return false;
    }
}

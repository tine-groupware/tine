<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * AuthGenericSmsMFAAdapterConfig Model
 *
 * @package     Tinebase
 * @subpackage  Auth
 */
class Tinebase_Model_MFA_GenericSmsConfig extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'MFA_GenericSmsConfig';

    public const FLD_SYSTEM_SMS_NAME = 'system_sms';
    public const FLD_BODY = 'body';
    public const FLD_HEADERS = 'headers';
    public const FLD_METHOD = 'method';
    public const FLD_PIN_LENGTH = 'pin_length';
    public const FLD_PIN_TTL = 'pin_ttl';
    public const FLD_URL = 'url';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::FIELDS                        => [
            self::FLD_SYSTEM_SMS_NAME           => [
                self::TYPE                          => self::TYPE_TEXT,
            ],
            self::FLD_BODY                      => [
                self::TYPE                          => self::TYPE_TEXT,
            ],
            self::FLD_HEADERS                   => [
                self::TYPE                          => self::TYPE_JSON,
            ],
            self::FLD_METHOD                    => [
                self::TYPE                          => self::TYPE_STRING,
            ],
            // length of the generated pin
            self::FLD_PIN_LENGTH                => [
                self::TYPE                          => self::TYPE_INTEGER,
            ],
            // time the generated pins lives
            self::FLD_PIN_TTL                   => [
                self::TYPE                          => self::TYPE_INTEGER,
            ],
            self::FLD_URL                       => [
                self::TYPE                          => self::TYPE_TEXT,
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}

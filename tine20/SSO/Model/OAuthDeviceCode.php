<?php declare(strict_types=1);

/**
 * class to hold OAuth Device Code data
 *
 * @package     SSO
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold OAuth Device Code data
 *
 * @package     SSO
 * @subpackage  Model
 */
class SSO_Model_OAuthDeviceCode extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'OAuthDeviceCode';
    public const TABLE_NAME = 'sso_oauth_device_code';

    public const FLD_USER_CODE = 'user_code';
    public const FLD_VALID_UNTIL = 'valid_until';
    public const FLD_RELYING_PARTY_ID = 'relying_party_id';
    public const FLD_APPROVED_BY = 'approved_by';

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
        self::RECORD_NAME => 'OAuth Device Code',
        self::RECORDS_NAME => 'OAuth Device Codes', // ngettext('OAuth Device Code', 'OAuth Device Codes', n)
        self::TITLE_PROPERTY => self::FLD_USER_CODE,

        self::APP_NAME => SSO_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::INDEXES => [
                self::FLD_RELYING_PARTY_ID => [
                    self::COLUMNS => [self::FLD_RELYING_PARTY_ID],
                ]
            ],
            self::UNIQUE_CONSTRAINTS => [
                self::FLD_USER_CODE => [
                    self::COLUMNS => [self::FLD_USER_CODE]
                ],
            ],
        ],

        self::ASSOCIATIONS => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                self::FLD_RELYING_PARTY_ID => [
                    'targetEntity' => SSO_Model_RelyingParty::class,
                    'fieldName' => self::FLD_RELYING_PARTY_ID,
                    'joinColumns' => [[
                        'name' => self::FLD_RELYING_PARTY_ID,
                        'referencedColumnName'  => 'id'
                    ]],
                ],
            ],
        ],

        self::FIELDS => [
            self::FLD_RELYING_PARTY_ID         => [
                self::TYPE                  => self::TYPE_RECORD,
                self::LENGTH                => 40,
                self::CONFIG                => [
                    self::APP_NAME              => SSO_Config::APP_NAME,
                    self::MODEL_NAME            => SSO_Model_RelyingParty::MODEL_NAME_PART,
                    self::FIXED_LENGTH          => true,
                ],
            ],
            self::FLD_USER_CODE         => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 11,
                self::CONFIG                => [
                    self::FIXED_LENGTH          => true,
                ],
            ],
            self::FLD_VALID_UNTIL       => [
                self::TYPE                  => self::TYPE_DATETIME,
            ],
            self::FLD_APPROVED_BY          => [
                self::TYPE                  => self::TYPE_USER,
                self::NULLABLE              => true,
                self::DEFAULT_VAL           => null,
                self::CONFIG                => [
                    self::FIXED_LENGTH          => true,
                ],
            ],
        ],
    ];

    public static function modelConfigHook(array &$_definition)
    {
        parent::modelConfigHook($_definition);

        $_definition[self::ID][self::CONFIG][self::FIXED_LENGTH] = true;
    }
}

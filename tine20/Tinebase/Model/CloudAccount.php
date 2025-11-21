<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */


class Tinebase_Model_CloudAccount extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'CloudAccount';
    public const TABLE_NAME = 'cloud_account';

    public const FLD_OWNER_ID = 'owner_id';
    public const FLD_TYPE = 'type';
    public const FLD_CONFIG = 'config';
    public const FLD_NAME = 'name';
    public const FLD_DESCRIPTION = 'description';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 1,
        self::EXPOSE_JSON_API           => true,
        self::MODLOG_ACTIVE             => true,
        self::HAS_DELETED_TIME_UNIQUE   => true,

        self::RECORD_NAME               => 'Cloud Account', // _('GENDER_Cloud Account')
        self::RECORDS_NAME              => 'Cloud Accounts', // ngettext('Cloud Account', 'Cloud Accounts', n)
        self::TITLE_PROPERTY            => self::FLD_NAME,

        self::APP_NAME                  => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS        => [
                self::FLD_NAME                  => [
                    self::COLUMNS                   => [self::FLD_NAME, self::FLD_DELETED_TIME],
                ],
            ],
        ],

        self::FIELDS                    => [
            self::FLD_NAME                  => [
                self::TYPE                      => self::TYPE_STRING,
                self::LABEL                     => 'Name', // _('Name')
                self::LENGTH                    => 255,
                self::QUERY_FILTER              => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_DESCRIPTION           => [
                self::TYPE                      => self::TYPE_TEXT,
                self::LABEL                     => 'Description', // _('Description')
                self::NULLABLE                  => true,
            ],
            self::FLD_OWNER_ID              => [
                self::TYPE                      => self::TYPE_USER,
                self::LABEL                     => 'Owner', // _('Owner')
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_TYPE                  => [
                self::TYPE                      => self::TYPE_MODEL,
                self::LABEL                     => 'Cloud Account Type', // _('Cloud Account Type')
                self::DEFAULT_VAL               => Tinebase_Model_CloudAccount_CalDAV::class,
                self::CONFIG                    => [
                    self::AVAILABLE_MODELS          => [
                        Tinebase_Model_CloudAccount_CalDAV::class,
                    ],
                ],
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                    [Zend_Validate_InArray::class, [
                        Tinebase_Model_CloudAccount_CalDAV::class,
                    ]],
                ],
            ],
            self::FLD_CONFIG                => [
                self::TYPE                      => self::TYPE_DYNAMIC_RECORD,
                self::CONFIG                    => [
                    self::REF_MODEL_FIELD           => self::FLD_TYPE,
                    self::PERSISTENT                => true,
                ],
                self::VALIDATORS            => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                    [Tinebase_Record_Validator_SubValidate::class],
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
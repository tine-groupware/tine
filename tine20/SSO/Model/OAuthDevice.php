<?php declare(strict_types=1);

/**
 * class to hold OAuth Device data
 *
 * @package     SSO
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold OAuth Device data
 *
 * @package     SSO
 * @subpackage  Model
 */
class SSO_Model_OAuthDevice extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'OAuthDevice';
    public const TABLE_NAME = 'sso_oauth_device';

    public const FLD_DESCRIPTION = 'description';
    public const FLD_NAME = 'name';
    public const FLD_LABEL = 'label';
    public const FLD_LOGO_DARK = 'logo_dark';
    public const FLD_LOGO_LIGHT = 'logo_light';

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
        self::RECORD_NAME => 'OAuth Device',
        self::RECORDS_NAME => 'OAuth Devices', // ngettext('OAuth Device', 'OAuth Devices', n)
        self::TITLE_PROPERTY => self::FLD_NAME,
        self::MODLOG_ACTIVE => true,
        self::HAS_DELETED_TIME_UNIQUE => true,
        self::EXPOSE_JSON_API => true,

        self::CREATE_MODULE => false,
        self::APP_NAME => SSO_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS => [
                'name' => [
                    self::COLUMNS => ['name', 'deleted_time']
                ]
            ]
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
            self::FLD_LABEL             => [
                self::TYPE                  => self::TYPE_STRING,
                self::LENGTH                => 255,
                self::QUERY_FILTER          => true,
                self::NULLABLE              => true,
                self::LABEL                 => 'Label', // _('Label')
            ],
            self::FLD_DESCRIPTION       => [
                self::TYPE                  => self::TYPE_TEXT,
                self::QUERY_FILTER          => true,
                self::NULLABLE              => true,
                self::LABEL                 => 'Description', // _('Description')
            ],
            self::FLD_LOGO_DARK         => [
                self::TYPE                  => self::TYPE_BLOB,
                self::NULLABLE              => true,
                self::LABEL                 => 'Logo Dark', // _('Logo Dark')
            ],
            self::FLD_LOGO_LIGHT              => [
                self::TYPE                  => self::TYPE_BLOB,
                self::NULLABLE              => true,
                self::LABEL                 => 'Logo', // _('Logo')
            ],
        ],
    ];
}

<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Tinebase_Model_Filter_Abstract as TMFA;

class Sales_Model_EDocument_VATEX extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'EDocument_VATEX';
    public const TABLE_NAME = 'edocument_vatex';

    public const FLD_NAME = 'name';
    public const FLD_CODE = 'code';
    public const FLD_REMARK = 'remark';
    public const FLD_DESCRIPTION = 'description';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                       => 1,
        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'VAT exemption reason code', // ngettext('VAT exemption reason code', 'VAT exemption reason codes', n)
        self::RECORDS_NAME                  => 'VAT exemption reason codes', // gettext('GENDER_VAT exemption reason code')
        self::TITLE_PROPERTY                => '{{ name }} - {{ description }}',
        self::DEFAULT_SORT_INFO             => [self::FIELD => self::FLD_NAME],
        self::MODLOG_ACTIVE                 => true,
        self::EXPOSE_JSON_API               => true,
        self::HAS_DELETED_TIME_UNIQUE       => true,

        self::TABLE                         => [
            self::NAME                          => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS            => [
                self::FLD_CODE                      => [
                    self::COLUMNS                       => [self::FLD_CODE, self::FLD_DELETED_TIME]
                ],
            ]
        ],

        self::LANGUAGES_AVAILABLE           => [
            self::TYPE                          => self::TYPE_KEY_FIELD,
            self::NAME                          => Sales_Config::LANGUAGES_AVAILABLE,
            self::CONFIG                        => [
                self::APP_NAME                      => Sales_Config::APP_NAME,
            ],
        ],

        self::FIELDS                        => [
            self::FLD_CODE                      => [
                self::LABEL                         => 'Code', // _('Code')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::QUERY_FILTER                  => true,
            ],
            self::FLD_NAME                      => [
                self::LABEL                         => 'Name', // _('Name')
                self::TYPE                          => self::TYPE_LOCALIZED_STRING,
                self::QUERY_FILTER                  => true,
            ],
            self::FLD_DESCRIPTION               => [
                self::LABEL                         => 'Description', // _('Description')
                self::TYPE                          => self::TYPE_LOCALIZED_STRING,
                self::QUERY_FILTER                  => true,
            ],
            self::FLD_REMARK                      => [
                self::LABEL                         => 'Remark', // _('Remark')
                self::TYPE                          => self::TYPE_LOCALIZED_STRING,
                self::QUERY_FILTER                  => true,
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

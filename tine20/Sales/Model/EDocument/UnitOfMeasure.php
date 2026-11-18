<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Tinebase_Model_Filter_Abstract as TMFA;

class Sales_Model_EDocument_UnitOfMeasure extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'EDocument_UnitOfMeasure';
    public const TABLE_NAME = 'edocument_unitofmeasure';

    public const FLD_NAME = 'name';
    public const FLD_CODE = 'code';
    public const FLD_ACTIVE = 'active';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                       => 1,
        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'Unit of measure', // ngettext('Unit of measure', 'Units of measure', n)
        self::RECORDS_NAME                  => 'Units of measure', // gettext('GENDER_Unit of measure')
        self::TITLE_PROPERTY                => self::FLD_NAME,
        self::DEFAULT_SORT_INFO             => [self::FIELD => self::FLD_CODE],
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
            self::FLD_ACTIVE                    => [
                self::LABEL                         => 'Active', // _('Active')
                self::TYPE                          => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL                   => false,
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

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

class Sales_Model_EDocument_XRechnungElement extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'EDocument_XRechnungElement';
    public const TABLE_NAME = 'edocument_xrechnung_element';

    public const FLD_PARENT_ID = 'parent_id';
    public const FLD_NAME = 'name';
    public const FLD_BT_NUMBER = 'bt_number';
    public const FLD_DESCRIPTION = 'description';
    public const FLD_TYPE = 'type';
    public const FLD_IS_OVERRIDEABLE = 'is_overrideable';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                       => 1,
        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'XRechnung Element', // ngettext('XRechnung Element', 'XRechnung Elements', n)
        self::RECORDS_NAME                  => 'XRechnung Elements', // gettext('XRechnung Elements')
        self::TITLE_PROPERTY                => '{{ name }} ({{ bt_number }})',
        self::DEFAULT_SORT_INFO             => [self::FIELD => self::FLD_NAME],
        self::MODLOG_ACTIVE                 => true,
        self::EXPOSE_JSON_API               => true,

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
        ],

        self::UI_CONFIG => [
            self::READ_ONLY => true,
        ],
        self::FIELDS                        => [
            self::FLD_PARENT_ID                   => [
                self::LABEL                         => 'Parent', // _('Parent')
                self::TYPE                          => self::TYPE_RECORD,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => self::MODEL_NAME_PART,
                    self::IS_PARENT                     => true,
                ],
                self::NULLABLE                      => true,
            ],
            self::FLD_NAME                        => [
                self::LABEL                         => 'Name', // _('Name')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::QUERY_FILTER                  => true,
            ],
            self::FLD_BT_NUMBER                   => [
                self::LABEL                         => 'BT Number', // _('BT Number')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::QUERY_FILTER                  => true,
            ],
            self::FLD_DESCRIPTION                 => [
                self::LABEL                         => 'Description', // _('Description')
                self::TYPE                          => self::TYPE_TEXT,
                self::QUERY_FILTER                  => true,
            ],
            self::FLD_TYPE                        => [
                self::LABEL                         => 'Type', // _('Type')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::QUERY_FILTER                  => true,
            ],
            self::FLD_IS_OVERRIDEABLE             => [
                self::LABEL                         => 'Is Overrideable', // _('Is Overrideable')
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

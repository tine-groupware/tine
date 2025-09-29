<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     EventManager
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Tonia Leuschel <t.leuschel@metaways.de>
 */

class EventManager_Model_Selection extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Selection';
    public const TABLE_NAME = 'eventmanager_selection';
    public const FLD_SELECTION_CONFIG_CLASS = 'selection_config_class';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                       => 1,
        self::APP_NAME                      => EventManager_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,
        self::RECORD_NAME                   => 'Selection' , // gettext('GENDER_Selection')
        self::RECORDS_NAME                  => 'Selections', // ngettext('Selection', 'Selections', n)
        self::MODLOG_ACTIVE                 => true,
        self::EXPOSE_JSON_API               => true,

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
        ],

        self::FIELDS                        => [
            self::FLD_SELECTION_CONFIG_CLASS    => [
                self::LABEL                         => 'Selection Config Class', // _('Selection Config Class')
                self::TYPE                          => self::TYPE_MODEL,
                self::NULLABLE                      => true,
                self::VALIDATORS                    => [Zend_Filter_Input::ALLOW_EMPTY      => true],
                self::ALLOW_CAMEL_CASE              => true,
                self::CONFIG                        => [
                    self::AVAILABLE_MODELS              => [
                        EventManager_Model_Selections_Checkbox::class,
                        EventManager_Model_Selections_File::class,
                    ],
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
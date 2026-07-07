<?php declare(strict_types=1);
/**
 * @package     Inventory
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2026-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

class Inventory_Model_ElectricalSafetyTest extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'ElectricalSafetyTest';
    public const TABLE_NAME = 'inventory_electrical_safety_test';

    public const FLD_EQUIPMENT_ID = 'equipment_id';
    public const FLD_TEST_DATE = 'test_date';
    public const FLD_PROTECTIVE_CONDUCTOR_RESISTANCE = 'protective_conductor_resistance';
    public const FLD_INSULATION_RESISTANCE = 'insulation_resistance';
    public const FLD_PROTECTIVE_CONDUCTOR_CURRENT = 'protective_conductor_current';
    public const FLD_TOUCH_CURRENT = 'touch_current';
    public const FLD_TEST_PASSED = 'test_passed';
    public const FLD_INSPECTOR = 'inspector';
    
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
        self::VERSION                   => 1,
        self::RECORD_NAME               => 'Electrical Safety Test',
        self::RECORDS_NAME              => 'Electrical Safety Tests', // ngettext('Electrical Safety Test', 'Electrical Safety Tests', n)
        self::TITLE_PROPERTY            => self::FLD_TEST_DATE,
        self::DEFAULT_SORT_INFO         => [self::FIELD => self::FLD_TEST_DATE],
        self::MODLOG_ACTIVE             => true,
        self::IS_DEPENDENT              => true,

        self::EXPOSE_JSON_API           => true,

        self::APP_NAME                  => Inventory_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_EQUIPMENT_ID     => [
                    self::COLUMNS                   => [self::FLD_EQUIPMENT_ID, self::FLD_TEST_DATE],
                ],
            ]
        ],

        self::FIELDS                    => [
            self::FLD_EQUIPMENT_ID          => [
                self::TYPE                      => self::TYPE_RECORD,
                self::LENGTH                    => 40,
                self::CONFIG                    => [
                    self::APP_NAME                  => Inventory_Config::APP_NAME,
                    self::MODEL_NAME                => Inventory_Model_ElectricalEquipment::MODEL_NAME_PART,
                    self::IS_PARENT                 => true,
                ],
            ],
            self::FLD_TEST_DATE             => [
                self::LABEL                     => 'Test Date', // _('Test Date')
                self::TYPE                      => self::TYPE_DATE,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::QUERY_FILTER              => true,
            ],
            self::FLD_PROTECTIVE_CONDUCTOR_RESISTANCE => [
                self::LABEL                     => 'Protective conductor resistance', // _('Protective conductor resistance')
                self::TYPE                      => self::TYPE_FLOAT,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_INSULATION_RESISTANCE => [
                self::LABEL                     => 'Insulation resistance', // _('Insulation resistance')
                self::TYPE                      => self::TYPE_FLOAT,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_PROTECTIVE_CONDUCTOR_CURRENT => [
                self::LABEL                     => 'Protective conductor current', // _('Protective conductor current')
                self::TYPE                      => self::TYPE_FLOAT,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_TOUCH_CURRENT         => [
                self::LABEL                     => 'Touch current', // _('Touch current')
                self::TYPE                      => self::TYPE_FLOAT,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_TEST_PASSED           => [
                self::LABEL                     => 'Test passed', // _('Test passed')
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_INSPECTOR             => [
                self::LABEL                     => 'Inspector', // _('Inspector')
                self::TYPE                      => self::TYPE_USER,
            ],
        ],
    ];
}

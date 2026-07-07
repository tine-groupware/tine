<?php declare(strict_types=1);
/**
 * @package     Inventory
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2026-2026 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

class Inventory_Model_ElectricalEquipment extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'ElectricalEquipment';
    public const TABLE_NAME = 'inventory_electrical_equipment';

    public const FLD_NAME = 'name';
    public const FLD_INVENTORY_ID = 'inventory_id';
    public const FLD_INVENTORY_ITEM_ID = 'inventory_item_id';
    public const FLD_INSPECTION_INSTRUCTIONS = 'inspection_instructions';
    public const FLD_NEXT_TEST_DUE = 'next_test_due';
    public const FLD_PROTECTION_CLASS = 'protection_class';
    public const FLD_ELECTRICAL_SAFETY_TESTS = 'electrical_safety_tests';
    
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
        self::RECORD_NAME               => 'Electrical Equipment',
        self::RECORDS_NAME              => 'Electrical Equipments', // ngettext('Electrical Equipment', 'Electrical Equipments', n)
        self::TITLE_PROPERTY            => self::FLD_NAME,
        self::DEFAULT_SORT_INFO         => [self::FIELD => self::FLD_NAME],
        self::MODLOG_ACTIVE             => true,
        self::IS_DEPENDENT              => true,

        self::EXPOSE_JSON_API           => true,

        self::APP_NAME                  => Inventory_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_INVENTORY_ITEM_ID     => [
                    self::COLUMNS                   => [self::FLD_INVENTORY_ITEM_ID],
                ],
            ]
        ],

        self::FIELDS                    => [
            self::FLD_INVENTORY_ITEM_ID     => [
                self::TYPE                      => self::TYPE_RECORD,
                self::LENGTH                    => 40,
                self::CONFIG                    => [
                    self::APP_NAME                  => Inventory_Config::APP_NAME,
                    self::MODEL_NAME                => Inventory_Model_InventoryItem::MODEL_NAME_PART,
                    self::IS_PARENT                 => true,
                ],
            ],
            self::FLD_NAME                  => [
                self::LABEL                     => 'Name', // _('Name')
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::QUERY_FILTER              => true,
            ],
            self::FLD_INVENTORY_ID          => [
                self::LABEL                     => 'Inventory Id', // _('Inventory Id')
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::QUERY_FILTER              => true,
            ],
            self::FLD_PROTECTION_CLASS      => [
                self::LABEL                     => 'Protection Class', // _('Protection Class')
                self::TYPE                      => self::TYPE_KEY_FIELD,
                self::NAME                      => Inventory_Config::PROTECTION_CLASS,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_INSPECTION_INSTRUCTIONS => [
                self::LABEL                     => 'Inspection Instructions', // _('Inspection Instructions')
                self::TYPE                      => self::TYPE_TEXT,
                self::NULLABLE                  => true,
            ],
            self::FLD_NEXT_TEST_DUE         => [
                self::LABEL                     => 'Next test due', // _('Next test due')
                self::TYPE                      => self::TYPE_DATE,
            ],
            self::FLD_ELECTRICAL_SAFETY_TESTS => [
                self::LABEL                     => 'Electrical safety tests',
                self::TYPE                      => self::TYPE_RECORDS,
                self::CONFIG                    => [
                    self::APP_NAME                  => Inventory_Config::APP_NAME,
                    self::MODEL_NAME                => Inventory_Model_ElectricalSafetyTest::MODEL_NAME_PART,
                    self::DEPENDENT_RECORDS         => true,
                    self::REF_ID_FIELD              => Inventory_Model_ElectricalSafetyTest::FLD_EQUIPMENT_ID,
                ],
            ],
        ],
    ];
}

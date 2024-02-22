<?php
/**
 * @package     Inventory
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2011-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Inventory config class
 * 
 * @package     Inventory
 * @subpackage  Config
 */
class Inventory_Config extends Tinebase_Config_Abstract
{
    public const APP_NAME = 'Inventory';

    /**
     * Inventory Status
     * 
     * @var string
     */
    const INVENTORY_STATUS = 'inventoryStatus';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = [
        self::INVENTORY_STATUS => [
            //_('Inventory Status Available')
            self::LABEL                 => 'Inventory Status Available',
            self::DESCRIPTION           => 'Possible status.', //_('Possible status.')
            self::TYPE                  => self::TYPE_KEYFIELD_CONFIG,
            self::OPTIONS               => [self::RECORD_MODEL => Inventory_Model_Status::class],
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::DEFAULT_STR           => [
                self::RECORDS => [
                    ['id' => Inventory_Model_Status::ORDERED,       'value' => 'Ordered'        ], //_('Ordered')
                    ['id' => Inventory_Model_Status::AVAILABLE,     'value' => 'Available'      ], //_('Available')
                    ['id' => Inventory_Model_Status::DEFECT,        'value' => 'Defect'         ], //_('Defect')
                    ['id' => Inventory_Model_Status::MISSING,       'value' => 'Missing'        ], //_('Missing')
                    ['id' => Inventory_Model_Status::REMOVED,       'value' => 'Removed'        ], //_('Removed')
                    ['id' => Inventory_Model_Status::UNKNOWN,       'value' => 'Unknown'        ], //_('Unknown')
                    ['id' => Inventory_Model_Status::STORED,        'value' => 'Stored'         ], //_('Stored')
                    ['id' => Inventory_Model_Status::SOLD,          'value' => 'Sold'           ], //_('Sold')
                    ['id' => Inventory_Model_Status::DESTROYED,     'value' => 'Destroyed'      ], //_('Destroyed')
                ],
                self::DEFAULT_STR => Inventory_Model_Status::AVAILABLE,
            ]
        ],
    ];
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Inventory';
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Config
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __construct() {}
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __clone() {}
    
    /**
     * Returns instance of Tinebase_Config
     *
     * @return Tinebase_Config
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
}

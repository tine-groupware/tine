<?php declare(strict_types=1);
/**
 * class to hold InventoryItem data
 * 
 * @package     Inventory
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold InventoryItem data
 * 
 * @package     Inventory
 * @subpackage  Model
 *
 */
class Inventory_Model_InventoryItem extends Tinebase_Record_Abstract
{
    public const MODEL_NAME_PART = 'InventoryItem';
    public const TABLE_NAME = 'inventory_item';
    public const FLD_NAME = 'name';
    public const FLD_STATUS = 'status';
    public const FLD_INVENTORY_ID = 'inventory_id';
    public const FLD_SERIAL_NUMBER = 'serial_number';
    public const FLD_DESCRIPTION = 'description';
    public const FLD_LOCATION = 'location';
    public const FLD_INVOICE_DATE = 'invoice_date';
    public const FLD_TOTAL_NUMBER = 'total_number';
    public const FLD_ACTIVE_NUMBER = 'active_number';
    public const FLD_TYPE = 'type';
    public const FLD_INVOICE = 'invoice';
    public const FLD_PRICE = 'price';
    public const FLD_WARRANTY = 'warranty';
    public const FLD_ADDED_DATE = 'added_date';
    public const FLD_REMOVED_DATE = 'removed_date';
    public const FLD_DEPRECATED_STATUS = 'deprecated_status';
    public const FLD_IMAGE = 'image';
    public const FLD_EMPLOYEE = 'employee';
    
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
        self::VERSION           => 13,
        self::RECORD_NAME        => 'Inventory item',
        self::RECORDS_NAME       => 'Inventory items', // ngettext('Inventory item', 'Inventory items', n)
        self::CONTAINER_PROPERTY => self::FLD_CONTAINER_ID,
        self::TITLE_PROPERTY     => "{{ name }} {% if serial_number %} ({{ serial_number }}){% endif %}",
        self::DEFAULT_SORT_INFO  => [self::FIELD => self::FLD_NAME],
        self::CONTAINER_NAME     => 'Inventory item list',
        self::CONTAINERS_NAME    => 'Inventory item lists', // ngettext('Inventory item list', 'Inventory item lists', n)
        self::HAS_RELATIONS      => true,
        self::HAS_CUSTOM_FIELDS   => true,
        self::HAS_NOTES          => true,
        self::HAS_TAGS           => true,
        self::MODLOG_ACTIVE      => true,
        self::HAS_ATTACHMENTS    => true,
        'copyNoAppendTitle' => true,
        self::HAS_SYSTEM_CUSTOM_FIELDS => true,
        
        self::EXPOSE_JSON_API     => true,

        self::CREATE_MODULE      => true,

        self::APP_NAME           => Inventory_Config::APP_NAME,
        self::MODEL_NAME         => self::MODEL_NAME_PART,

        self::TABLE             => [
            self::NAME    => self::TABLE_NAME,
            self::INDEXES => [
                self::FLD_CONTAINER_ID => [
                    self::COLUMNS => [self::FLD_CONTAINER_ID]
                ],
                self::FLD_DESCRIPTION => [
                    self::COLUMNS       => [self::FLD_DESCRIPTION],
                    self::FLAGS         => [self::TYPE_FULLTEXT],
                ],
            ]
        ],

        'import'            => [
            'defaultImportContainerRegistryKey' => 'defaultInventoryItemContainer',
        ],
        self::EXPORT            => [
            self::SUPPORTED_FORMATS => ['csv', 'ods'],
        ],

        self::FILTER_MODEL =>   [
            self::FLD_EMPLOYEE  => [
                self::FILTER    => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                self::LABEL => 'Employee', // _('Employee')
                self::OPTIONS   => [
                    self::CONTROLLER    => HumanResources_Controller_Employee::class,
                    self::FILTER_GROUP  => HumanResources_Model_EmployeeFilter::class,
                    'own_filtergroup' => 'Inventory_Model_InventoryItemFilter',
                    'own_controller' => 'Inventory_Controller_InventoryItem',
                    'related_model' => HumanResources_Model_Employee::class,
                ]
            ]
        ],

        self::JSON_EXPANDER => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_INVOICE => [],
            ],
        ],

        self::FIELDS            => [
            self::FLD_NAME => [
                self::TYPE        => self::TYPE_STRING,
                self::LENGTH      => 255,
                self::VALIDATORS  => [
                    Zend_Filter_Input::ALLOW_EMPTY => false, 
                    'presence' => 'required'
                ],
                self::LABEL       => 'Name', // _('Name')
                self::QUERY_FILTER => true,
            ],
            self::FLD_STATUS => [
                self::LABEL => 'Status', // _('Status')
                self::TYPE => self::TYPE_KEY_FIELD,
                self::NAME => Inventory_Config::INVENTORY_STATUS,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::NULLABLE  => true,
            ],
            self::FLD_SERIAL_NUMBER   => [
                self::TYPE => self::TYPE_STRING,
                self::LABEL => 'Serial Number', // _('Serial Number')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER  => true,
                self::NULLABLE              => true,
            ],
            self::FLD_INVENTORY_ID => [
                self::TYPE       => 'string',
                self::LENGTH     => 100,
                self::NULLABLE    => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL      => 'Inventory ID' // _('Inventory ID')
            ],
            self::FLD_DESCRIPTION       => [
                self::LABEL                 => 'Description', // _('Description')
                self::TYPE                  => self::TYPE_FULLTEXT,
                self::NULLABLE              => true,
                self::QUERY_FILTER          => true,
            ],
            self::FLD_LOCATION => [
                self::TYPE       => self::TYPE_STRING,
                self::LENGTH     => 255,
                self::NULLABLE    => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL      => 'Location', // _('Location')
                self::QUERY_FILTER          => true,
            ],
            self::FLD_INVOICE_DATE => [
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL      => 'Invoice date', // _('Invoice date')
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::DEFAULT_VAL    => null,
                self::TYPE       => self::TYPE_DATETIME,
                self::NULLABLE      => true,
                'hidden'     => true,
            ],
            self::FLD_TOTAL_NUMBER => [
                self::TYPE         => self::TYPE_INTEGER,
                self::NULLABLE      => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL        => null,
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::DEFAULT_VAL      => 1,
            ],
            self::FLD_ACTIVE_NUMBER => [
                self::TYPE         => self::TYPE_INTEGER,
                self::NULLABLE      => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL        => 'Available number', // _(Available number)
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::DEFAULT_VAL      => 1,
            ],
            self::FLD_TYPE       => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::CONFIG            => [
                    self::APP_NAME          => Inventory_Config::APP_NAME,
                    self::MODEL_NAME        => Inventory_Model_Type::MODEL_NAME_PART,
                ],
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL             => 'Inventory Type', // _('Inventory Type')
                self::NULLABLE          => true,
            ],
            self::FLD_INVOICE => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::CONFIG            => [
                    self::APP_NAME          => Sales_Config::APP_NAME,
                    self::MODEL_NAME        => Sales_Model_PurchaseInvoice::MODEL_NAME_PART,
                ],
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL             => 'Purchase Invoice', // _('Purchase Invoice')
                self::NULLABLE          => true,
                self::QUERY_FILTER      => true,
            ],
            self::FLD_PRICE => [
                self::TYPE         => self::TYPE_MONEY,
                self::NULLABLE      => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true
                ],
                self::LABEL        => 'Price', // _('Price')
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                'hidden'       => true,
            ],
            self::FLD_WARRANTY => [
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL      => 'Warranty', // _('Warranty')
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::TYPE       => self::TYPE_DATETIME,
                self::NULLABLE    => true,
                'hidden'     => true,
            ],
            self::FLD_ADDED_DATE => [
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL      => 'Added', // _('Added')
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::TYPE       => self::TYPE_DATETIME,
                self::NULLABLE    => true,
                'hidden'     => true,
            ],
            self::FLD_REMOVED_DATE => [
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true
                ],                self::LABEL      => 'Removed', // _('Removed')
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::TYPE       => self::TYPE_DATETIME,
                self::NULLABLE    => true,
                'hidden'     => true,
            ],
            self::FLD_DEPRECATED_STATUS => [
                self::TYPE => self::TYPE_BOOLEAN,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true, 
                    Zend_Filter_Input::DEFAULT_VALUE => 0,
                ],
                self::LABEL => null,
                self::DEFAULT_VAL => 0,
                //self::LABEL        => 'Depreciated', // _('Depreciated')
            ],
            self::FLD_IMAGE => [
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                // is saved in vfs, only image files allowed
                self::TYPE => 'image',
            ],
            self::FLD_EMPLOYEE => array(
                self::TYPE => self::TYPE_VIRTUAL,
                self::CONFIG => [
                self::TYPE =>   self::TYPE_RELATION,
                    self::LABEL => 'Employee',    // _('Employee')
                    self::CONFIG => [
                        self::APP_NAME          => HumanResources_Config::APP_NAME,
                        self::MODEL_NAME        => HumanResources_Model_Employee::MODEL_NAME_PART,
                        self::TYPE => 'EMPLOYEE',
                    ]
                ],
            ),
        ]
    ];
}

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
 * @property    string $name
 */
class Inventory_Model_InventoryItem extends Tinebase_Record_Abstract
{
    public const TABLE_NAME = 'inventory_item';
    
    const FLD_STATUS = 'status';

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
    protected static $_modelConfiguration = array(
        'version'           => 13,
        'recordName'        => 'Inventory item',
        'recordsName'       => 'Inventory items', // ngettext('Inventory item', 'Inventory items', n)
        'containerProperty' => 'container_id',
        'titleProperty'     => 'name',
        'containerName'     => 'Inventory item list',
        'containersName'    => 'Inventory item lists', // ngettext('Inventory item list', 'Inventory item lists', n)
        'hasRelations'      => true,
        'hasCustomFields'   => true,
        'hasNotes'          => true,
        'hasTags'           => true,
        'modlogActive'      => true,
        'hasAttachments'    => true,
        'copyNoAppendTitle' => true,
        self::HAS_SYSTEM_CUSTOM_FIELDS => true,
        
        'exposeJsonApi'     => true,

        'createModule'      => true,

        'appName'           => 'Inventory',
        'modelName'         => 'InventoryItem',

        'table'             => array(
            'name'    => self::TABLE_NAME,
            'indexes' => array(
                'container_id' => array(
                    'columns' => array('container_id')
                )
            )
        ),

        'import'            => array(
            'defaultImportContainerRegistryKey' => 'defaultInventoryItemContainer',
        ),
        'export'            => array(
            'supportedFormats' => array('csv', 'ods'),
        ),

        'fields'            => array(
            'name' => array(
                'type'        => 'string',
                'length'      => 255,
                'validators'  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
                'label'       => 'Name', // _('Name')
                'queryFilter' => true,
            ),
            self::FLD_STATUS => [
                self::LABEL => 'Status', // _('Status')
                self::TYPE => self::TYPE_KEY_FIELD,
                self::NAME => Inventory_Config::INVENTORY_STATUS,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::NULLABLE  => true,
            ],
            'inventory_id' => array(
                'type'       => 'string',
                'length'     => 100,
                'nullable'   => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Inventory ID' // _('Inventory ID')
            ),
            'description' => array(
                // TODO convert to fulltext
                'type'       => 'text',
                'nullable'   => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      =>'Description' // _('Description')
            ),
            'location' => array(
                'type'       => 'string',
                'length'     => 255,
                'nullable'   => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Location', // _('Location')
                'queryFilter' => true,
            ),
            'invoice_date' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Invoice date', // _('Invoice date')
                'inputFilters' => array('Zend_Filter_Empty' => null),
                'hidden'     => true,
                'default'    => null,
                'type'       => 'datetime',
                'nullable'     => true,
            ),
            'total_number' => array(
                'type'         => 'integer',
                'nullable'     => true,
                'validators'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'        => null,
                'inputFilters' => array('Zend_Filter_Empty' => null),
                'default'      => 1,
            ),
            'active_number' => array(
                'type'         => 'integer',
                'nullable'     => true,
                'validators'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'        => 'Available number', // _(Available number)
                'inputFilters' => array('Zend_Filter_Empty' => null),
                'default'      => 1,
            ),
            'invoice' => array(
                'type'       => 'string',
                'nullable'   => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Invoice', // _('Invoice')
                'hidden'     => true
            ),
            'price' => array(
                'type'         => 'money',
                'nullable'     => true,
                'validators'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'        => 'Price', // _('Price')
                'hidden'       => true,
                'inputFilters' => array('Zend_Filter_Empty' => null),
            ),
            'warranty' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Warranty', // _('Warranty')
                'hidden'     => true,
                'inputFilters' => array('Zend_Filter_Empty' => null),
                'type'       => 'datetime',
                'nullable'   => true,
            ),
            'added_date' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Added', // _('Added')
                'hidden'     => true,
                'inputFilters' => array('Zend_Filter_Empty' => null),
                'type'       => 'datetime',
                'nullable'   => true,
            ),
            'removed_date' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label'      => 'Removed', // _('Removed')
                'hidden'     => true,
                'inputFilters' => array('Zend_Filter_Empty' => null),
                'type'       => 'datetime',
                'nullable'   => true,
            ),
            'deprecated_status' => array(
                self::TYPE => self::TYPE_BOOLEAN,
                self::VALIDATORS => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
                self::LABEL => null,
                self::DEFAULT_VAL => 0,
                //'label'        => 'Depreciated', // _('Depreciated')
            ),
            'image' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'inputFilters' => array('Zend_Filter_Empty' => null),
                // is saved in vfs, only image files allowed
                'type' => 'image'
            ),
        )
    );
}

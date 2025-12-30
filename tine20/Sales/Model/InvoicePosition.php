<?php
/**
 * class to hold Division data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold InvoicePosition data
 *
 * @package     Sales
 */
class Sales_Model_InvoicePosition extends Tinebase_Record_Abstract
{
    public const MODEL_NAME_PART = 'InvoicePosition';
    public const TABLE_NAME = 'sales_invoice_positions';

    const TYPE_TOTAL = 'total';
    const TYPE_INCLUSIVE = 'inclusive';
    const TYPE_EXCEEDING = 'exceeding';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
    
    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        self::VERSION       => 3,
        self::APP_NAME      => Sales_Config::APP_NAME,
        self::MODEL_NAME    => self::MODEL_NAME_PART,

        'recordName'        => 'Invoice Position',
        'recordsName'       => 'Invoice Positions', // ngettext('Invoice Position', 'Invoice Positions', n)
        'titleProperty'     => 'title',

        self::HAS_SYSTEM_CUSTOM_FIELDS => true,

        self::TABLE         => [
            self::NAME          => self::TABLE_NAME,
            self::INDEXES       => [
                'invoice_id' => [
                    self::COLUMNS       => ['invoice_id'],
                ],
            ]
        ],

        'fields'            => array(
            'invoice_id' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'label' => NULL,
                'type'  => 'record',
                self::LENGTH => 40,
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'Invoice',
                    'idProperty'  => 'id',
                    'isParent'    => true
                )
            ),
            'accountable_id' => array(
                'label'   => NULL,
                'type'    => 'string',
                self::LENGTH => 40,
            ),
            'model' => array(
                'label'   => 'Type', // _('Type')
                'type'    => 'string',
            ),
            'type' => array(
                //'label'   => 'Type', // _('Type')
                'type'    => 'string',
                'validators' => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => '',
                    [Zend_Validate_InArray::class, [
                        self::TYPE_EXCEEDING,
                        self::TYPE_INCLUSIVE,
                        self::TYPE_TOTAL,
                    ]],
                ],
            ),
            'title' => array(
                'label'   => 'Title', // _('Title')
                'type'    => 'string',
                'queryFilter' => true,
            ),
            'month' => array(
                'label'   => 'Month', // _('Month')
                'type'    => self::TYPE_STRING,
                self::LENGTH => 7,
            ),
            'unit' => array(
                'label'   => 'Unit', // _('Unit')
                'type'    => 'string',
                self::LENGTH => 128,
            ),
            'quantity' => array(
                'label' => 'Quantity', //_('Quantity')
                'type'  => 'float',
                'summaryType' => 'sum',
                self::DEFAULT_VAL => 1.0,
                self::UNSIGNED => true,
            ),
        )
    );
}

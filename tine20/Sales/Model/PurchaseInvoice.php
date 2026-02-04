<?php
/**
 * Tine 2.0

 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2015-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Purchase Invoice data
 *
 * @package     Sales
 * @subpackage  Model
 */

class Sales_Model_PurchaseInvoice extends Tinebase_Record_Abstract
{
    public const MODEL_NAME_PART = 'PurchaseInvoice';
    public const TABLE_NAME = 'sales_purchase_invoices';
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
        self::VERSION       => 7,
        'recordName'        => 'Legacy Purchase Invoice',
        'recordsName'       => 'Legacy Purchase Invoices', // ngettext('Legacy Purchase Invoice', 'Legacy Purchase Invoices', n)
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        self::HAS_SYSTEM_CUSTOM_FIELDS => true,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,
        'createModule'      => TRUE,
        'containerProperty' => NULL,
        'titleProperty'     => '{{number}} - {{supplier.name}}',
        'appName'           => 'Sales',
        'modelName'         => self::MODEL_NAME_PART,

        'exposeHttpApi'     => true,
        self::EXPOSE_JSON_API   => true,

        'defaultSortInfo'   => ['field' => 'number', 'direction' => 'DESC'],

        self::TABLE         => [
            self::NAME          => self::TABLE_NAME,
            self::INDEXES       => [
                'description'       => [
                    self::COLUMNS       => ['description'],
                    self::FLAGS         => [self::TYPE_FULLTEXT],
                ],
            ],
        ],
        
        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'supplier'       => [],
            ]
        ],
        
        'filterModel' => array(
            'supplier' => array(
                'filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'label' => 'Supplier', // _('Supplier')
                'options' => array(
                    'controller'      => 'Sales_Controller_Supplier',
                    'filtergroup'     => 'Sales_Model_SupplierFilter',
                    'own_filtergroup' => 'Sales_Model_PurchaseInvoiceFilter',
                    'own_controller'  => 'Sales_Controller_PurchaseInvoice',
                    'related_model'   => 'Sales_Model_Supplier',
                ),
                'jsConfig' => array('filtertype' => 'sales.supplier')
            ),
            'approver' => array(
                'filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'label' => 'Approver', // _('Approver')
                'options' => array(
                    'controller'      => 'Addressbook_Controller_Contact',
                    'filtergroup'     => 'Addressbook_Model_ContactFilter',
                    'own_filtergroup' => 'Sales_Model_PurchaseInvoiceFilter',
                    'own_controller'  => 'Sales_Controller_PurchaseInvoice',
                    'related_model'   => 'Addressbook_Model_Contact',
                ),
                'jsConfig' => array('filtertype' => 'sales.purchaseinvoice_approver')
            )
        ),
        
        'fields'            => array(
            'number' => array(
                'label' => 'Invoice Number',    // _('Invoice Number')
                self::TYPE  => self::TYPE_STRING,
                self::LENGTH => 64,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'queryFilter' => TRUE,
            ),
            'description' => array(
                self::LABEL         => 'Description', // _('Description')
                self::TYPE          => self::TYPE_STRICTFULLTEXT,
                self::VALIDATORS    => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                self::QUERY_FILTER  => TRUE,
                self::NULLABLE      => true,
            ),
            'date' => array(
                'type'  => 'date',
                'label' => 'Date of invoice',   // _('Date of invoice')
                self::UI_CONFIG => [
                    'format' => ['medium'],
                ],
                self::NULLABLE      => true,
            ),
            'due_in' => array(
                'title' => 'Due in',            // _('Due in')
                'type'  => 'integer',
                'label' => 'Due in',            // _('Due in')
                'shy' => TRUE,
                self::UNSIGNED => true,
            ),
            'due_at' => array(
                'type'  => 'date',
                'label' => 'Due at',            // _('Due at')
                self::UI_CONFIG => [
                    'format' => ['medium'],
                ],
            ),
            'pay_at' => array(
                'type'  => 'date',
                'label' => 'Pay at',            // _('Pay at')
                self::NULLABLE      => true,
            ),
            'overdue_at' => array(
                'type'  => 'date',
                'label' => 'Overdue at',            // _('Overdue at')
                self::NULLABLE      => true,
            ),
            'is_payed' => array(
                'type'  => self::TYPE_BOOLEAN,
                'label' => 'Is payed',            // _('Is payed')
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => 0,
                self::UNSIGNED => true,
            ),
            'payed_at' => array(
                'type'  => 'date',
                'label' => 'Payed at',          // _('Payed at')
                self::UI_CONFIG => [
                    'format' => ['medium'],
                ],
                self::NULLABLE      => true,
            ),
            'dunned_at' => array(
                'type'  => 'date',
                'label' => 'Dunned at',          // _('Dunned at')
                self::UI_CONFIG => [
                    'format' => ['medium'],
                ],
                self::NULLABLE      => true,
            ),
            'payment_method' => array(
                'label'   => 'Payment Method', //_('Payment Method')
                'default' => null, //'BANK TRANSFER',
                'type'    => 'keyfield',
                'name'    => Sales_Config::PAYMENT_METHODS,
                'shy'     => TRUE,
                self::NULLABLE      => true,
            ),
            'discount' => array(
                'label'   => 'Discount (%)', // _('Discount (%)')
                'type'    => self::TYPE_INTEGER,
                self::UNSIGNED => true,
                'specialType' => 'percent',
                'inputFilters' => array('Zend_Filter_Empty' => 0),
                'shy'     => TRUE,
            ),
            'discount_until' => array(
                'type'  => 'date',
                'label' => 'Discount until',    // _('Discount until')
                'shy' => TRUE,
                self::NULLABLE      => true,
            ),
            'is_approved' => array(
                'type'  => self::TYPE_BOOLEAN,
                'label' => 'Is approved',            // _('Is approved')
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => 0,
                self::UNSIGNED => true,
            ),
            'price_net' => array(
                'label' => 'Price Net', // _('Price Net')
                'type'  => 'money',
                'inputFilters' => array('Zend_Filter_Empty' => 0),
                'shy' => TRUE,
                self::NULLABLE      => true,
            ),
            'price_gross' => array(
                'label' => 'Price Gross', // _('Price Gross')
                'type'  => 'money',
                'inputFilters' => array('Zend_Filter_Empty' => 0),
                'shy' => TRUE,
                self::NULLABLE      => true,
            ),
            'price_gross2' => array(
                'label' => 'Additional Price Gross', // _('Additional Price Gross')
                'type'  => 'money',
                'inputFilters' => array('Zend_Filter_Empty' => 0),
                'shy' => TRUE,
                self::NULLABLE      => true,
            ),
            'price_tax' => array(
                'label' => 'Taxes (VAT)', // _('Taxes (VAT)')
                'type'  => 'money',
                'inputFilters' => array('Zend_Filter_Empty' => 0),
                'shy' => TRUE,
                self::NULLABLE      => true,
            ),
            'sales_tax' => array(
                'label' => 'Sales Tax', // _('Sales Tax')
                'type'  => 'float',
                'specialType' => 'percent',
                self::UNSIGNED      => true,
                self::NULLABLE => true,
                /*self::DEFAULT_VAL_CONFIG => [
                    self::APP_NAME  => Tinebase_Config::APP_NAME,
                    self::CONFIG => Tinebase_Config::SALES_TAX
                ],*/
                'shy' => TRUE,
            ),
            'price_total' => array(
                'label' => 'Total Price', // _('Total Price')
                'type'  => 'money',
                self::NULLABLE => true,
                'inputFilters' => array('Zend_Filter_Empty' => 0),
            ),
            'approver' => array(
                'type'   => 'virtual',
                'config' => array(
                    'type'   => 'relation',
                    'label'  => 'Approver',    // _('Approver')
                    'config' => array(
                        'appName'   => 'Addressbook',
                        'modelName' => 'Contact',
                        'type'      => 'APPROVER'
                    )
                )
            ),
            'supplier' => array(
                'type'   => 'virtual',
                'config' => array(
                    'type'   => 'relation',
                    'label'  => 'Supplier',    // _('Supplier')
                    'config' => array(
                        'appName'   => 'Sales',
                        'modelName' => 'Supplier',
                        'type'      => 'SUPPLIER'
                    )
                )
            ),
            'last_datev_send_date'       => [
                self::LABEL                 => 'Last Datev send date', // _('Last Datev send date')
                self::TYPE                  => self::TYPE_DATETIME,
                self::VALIDATORS            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                self::NULLABLE              => true,
                self::SHY                   => true,
            ],
        )
    );
    
    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        array(
            'relatedApp'   => 'Sales',
            'relatedModel' => 'Supplier',
            'config'       => array(
                array('type' => 'SUPPLIER', 'degree' => 'sibling', 'text' => 'Supplier', 'max' => '1:0'), // _('Supplier')
            ),
            'defaultType'  => 'SUPPLIER'
        ),
        array(
            'relatedApp'   => 'Addressbook',
            'relatedModel' => 'Contact',
            'config' => array(
                array('type' => 'APPROVER', 'degree' => 'sibling', 'text' => 'Approver', 'max' => '1:0'), // _('Approver')
            ),
            'defaultType'  => 'APPROVER'
        ),
    );
}

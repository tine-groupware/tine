<?php
/**
 * Tine 2.0

 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Invoice data
 *
 * @package     Sales
 * @subpackage  Model
 * 
 * @property $number
 * @property $description
 * @property $address_id
 * @property $fixed_address
 * @property $date
 * @property Tinebase_DateTime $start_date
 * @property $end_date
 * @property $credit_term
 * @property $costcenter_id
 * @property $cleared
 * @property $type
 * @property $is_auto
 * @property $price_net
 * @property $price_tax
 * @property $price_gross
 * @property $sales_tax
 * @property $inventory_change
 * @property $positions
 * @property $contract
 * @property $customer
 * @property $fulltext
 */
class Sales_Model_Invoice extends Tinebase_Record_Abstract
{
    public const MODEL_NAME_PART = 'Invoice';
    public const TABLE_NAME = 'sales_sales_invoices';

    public const FLD_DEBITOR_ID = 'debitor_id';
    public const FLD_LAST_DATEV_SEND_DATE = 'last_datev_send_date';

    public const FLD_BUYER_REFERENCE = 'buyer_reference'; // varchar 255
    public const FLD_PURCHASE_ORDER_REFERENCE = 'purchase_order_reference';
    public const FLD_PROJECT_REFERENCE = 'project_reference';
    public const FLD_PAYMENT_MEANS = 'payment_means';


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
        self::VERSION       => 13,
        'recordName'        => 'Invoice', // gettext('GENDER_Invoice')
        'recordsName'       => 'Invoices', // ngettext('Invoice', 'Invoices', n)
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,
        'createModule'      => TRUE,
        'containerProperty' => NULL,
        self::HAS_SYSTEM_CUSTOM_FIELDS => true,

        'titleProperty'     => 'fulltext', //array('%s - %s', array('number', 'title')),

        'appName'           => 'Sales',
        'modelName'         => 'Invoice',

        'exposeHttpApi'     => true,

        'defaultSortInfo'   => ['field' => 'number', 'direction' => 'DESC'],

        self::TABLE         => [
            self::NAME          => self::TABLE_NAME,
            self::INDEXES       => [
                'description' => [
                    self::COLUMNS       => ['description'],
                    self::FLAGS         => [self::TYPE_FULLTEXT],
                ],
            ]
        ],

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'address_id'                => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Sales_Model_Address::FLD_DEBITOR_ID => [
                            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                                Sales_Model_Debitor::FLD_PAYMENT_MEANS => [
                                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                                        Sales_Model_PaymentMeans::FLD_PAYMENT_MEANS_CODE => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                self::FLD_PAYMENT_MEANS     => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Sales_Model_PaymentMeans::FLD_PAYMENT_MEANS_CODE => [],
                    ],
                ],
            ],
        ],
        'filterModel' => array(
            'contract' => array(
                'filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'label' => 'Contract', // _('Contract')
                'options' => array(
                    'controller' => 'Sales_Controller_Contract',
                    'filtergroup' => 'Sales_Model_ContractFilter',
                    'own_filtergroup' => 'Sales_Model_InvoiceFilter',
                    'own_controller' => 'Sales_Controller_Invoice',
                    'related_model' => 'Sales_Model_Contract',
                ),
                'jsConfig' => array('filtertype' => 'sales.invoicecontract')
            ),
            'customer' => array(
                'filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'label' => 'Customer', // _('Customer')
                'options' => array(
                    'controller' => 'Sales_Controller_Customer',
                    'filtergroup' => 'Sales_Model_CustomerFilter',
                    'own_filtergroup' => 'Sales_Model_InvoiceFilter',
                    'own_controller' => 'Sales_Controller_Invoice',
                    'related_model' => 'Sales_Model_Customer',
                ),
                'jsConfig' => array('filtertype' => 'sales.invoicecustomer')
            ),
        ),
        
        'fields'            => array(
            'number' => array(
                'label' => 'Invoice Number', //_('Invoice Number')
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 64,
                self::NULLABLE => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'queryFilter' => TRUE,
            ),
            'description' => array(
                'label'   => 'Description', // _('Description')
                self::TYPE => self::TYPE_FULLTEXT,
                self::LENGTH => 255,
                'queryFilter' => TRUE,
            ),
            self::FLD_DEBITOR_ID                => [
                self::TYPE                          => self::TYPE_RECORD,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_Debitor::MODEL_NAME_PART,
                ],
                self::NULLABLE                      => true,
            ],
            'address_id'       => array(
                'label'      => 'Address',    // _('Address')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
                'type'       => 'record',
                'shy' => TRUE,
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'Address',
                    'idProperty'  => 'id',
                )
            ),
            'fixed_address' => array(
                'label'      => 'Address',    // _('Address')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label' => NULL,
                self::TYPE => self::TYPE_TEXT,
                self::NULLABLE => true,
            ),
            'is_auto' => array(
                'type' => self::TYPE_BOOLEAN,
                'label' => NULL,
                self::DEFAULT_VAL => 0,
                self::NULLABLE                      => true,
                self::UNSIGNED => true,
            ),
            'date' => array(
                'type' => 'date',
                'label' => 'Date',    // _('Date')
                'uiconfig'  => [
                    'format' => ['medium'],
                ],
                self::NULLABLE                      => true,
            ),
            'credit_term' => array(
                'title' => 'Credit Term', // _('Credit Term')
                'type'  => 'integer',
                self::NULLABLE => true,
                self::UNSIGNED => true,
            ),
            'price_net' => array(
                'label' => 'Price Net', // _('Price Net')
                'type'  => 'money',
                'inputFilters' => array('Zend_Filter_Empty' => 0.0),
                'shy' => TRUE,
                self::NULLABLE => true,
            ),
            'price_tax' => array(
                'label' => 'Taxes (VAT)', // _('Taxes (VAT)')
                'type'  => 'money',
                self::NULLABLE => true,
                'inputFilters' => array('Zend_Filter_Empty' => 0.0),
                'shy' => TRUE,
            ),
            'price_gross' => array(
                'label' => 'Price Gross', // _('Price Gross')
                'type'  => 'money',
                self::NULLABLE => true,
                'inputFilters' => array('Zend_Filter_Empty' => 0.0),
                'shy' => TRUE,
            ),
            'sales_tax' => array(
                'label' => 'Sales Tax', // _('Sales Tax')
                'type'  => 'float',
                'specialType' => 'percent',
                self::DEFAULT_VAL => 19.0,
                'inputFilters' => array('Zend_Filter_Empty' => 0.0),
                'shy' => TRUE,
                self::NULLABLE => true,
                self::UNSIGNED => true,
            ),
            'inventory_change' => array(
                'label' => 'Inventory Change', // _('Inventory Change')
                'type'  => 'money',
                'default' => 0.0,
                'inputFilters' => array('Zend_Filter_Empty' => 0.0),
                'shy' => TRUE,
                self::NULLABLE => true,
            ),
            'cleared' => array(
                'label' => 'Cleared', //_('Cleared')
                'default' => 'TO_CLEAR',
                'type' => 'keyfield',
                'name' => Sales_Config::INVOICE_CLEARED,
                self::NULLABLE => true,
            ),
            'type' => array(
                'label' => 'Type', //_('Type')
                'default' => null,
                'type' => 'keyfield',
                'name' => Sales_Config::INVOICE_TYPE,
                self::NULLABLE => true,
            ),
            'start_date' => array(
                'type' => 'date',
                'label'      => 'Interval Begins',    // _('Interval Begins')
                'uiconfig'  => [
                    'format' => ['medium'],
                ],
                self::NULLABLE => true,
            ),
            'end_date' => array(
                'type' => 'date',
                'label'      => 'Interval Ends',    // _('Interval Ends')
                'uiconfig'  => [
                    'format' => ['medium'],
                ],
                self::NULLABLE => true,
            ),
            'positions' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Positions', // _('Positions')
                'type'       => 'records',
                'config'     => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'InvoicePosition',
                    'refIdField'  => 'invoice_id',
                    'paging'      => array('sort' => 'month', 'dir' => 'ASC'),
                    'dependentRecords' => TRUE
                ),
            ),
            'contract' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'relation',
                    'label' => 'Contract',    // _('Contract')
                    'config' => array(
                        'appName'   => 'Sales',
                        'modelName' => 'Contract',
                        'type' => 'CONTRACT'
                    )
                )
            ),
            'customer' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'relation',
                    'label' => 'Customer',    // _('Customer')
                    'config' => array(
                        'appName'   => 'Sales',
                        'modelName' => 'Customer',
                        'type' => 'CUSTOMER'
                    )
                )
            ),
            'fulltext' => array(
                'type'   => 'virtual',
                'config' => array(
                    'sortable' => false,
                    'type'   => 'string'
                )
            ),
            self::FLD_LAST_DATEV_SEND_DATE       => [
                self::LABEL                 => 'Last Datev send date', // _('Last Datev send date')
                self::TYPE                  => self::TYPE_DATETIME,
                self::VALIDATORS            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                self::NULLABLE              => true,
                self::SHY                   => true,
            ],
            self::FLD_BUYER_REFERENCE        => [
                self::LABEL                         => 'Buyer Reference', //_('Buyer Reference')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
                self::QUERY_FILTER                  => true,
                self::SHY                           => true,
            ],
            self::FLD_PURCHASE_ORDER_REFERENCE  => [
                self::LABEL                         => 'Purchase Order Reference', // _('Purchase Order Reference')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
                self::QUERY_FILTER                  => true,
            ],
            self::FLD_PROJECT_REFERENCE         => [
                self::LABEL                         => 'Project Reference', // _('Project Reference')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
                self::QUERY_FILTER                  => true,
            ],
            self::FLD_PAYMENT_MEANS             => [
                self::LABEL                         => 'Payment Means', // _('Payment Means')
                self::TYPE                          => self::TYPE_RECORDS,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_PaymentMeans::MODEL_NAME_PART,
                    self::STORAGE                       => self::TYPE_JSON,
                ],
                self::NULLABLE                      => true,
            ],
        )
    );

    /**
     * sets the record related properties from user generated input.
     *
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data            the new data to set
     * @throws Tinebase_Exception_Record_Validation when content contains invalid or missing data
     **/
    public function setFromArray(array &$_data)
    {
        parent::setFromArray($_data);
        $this->fulltext = $this->number . ' - ' . $this->description;
    }

    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        array('relatedApp' => 'Sales', 'relatedModel' => 'Contract', 'config' => array(
            array('type' => 'CONTRACT', 'degree' => 'sibling', 'text' => 'Contract', 'max' => '0:0'), // _('Contract')
            ), 'defaultType' => 'CONTRACT'
        ),
        array('relatedApp' => 'Sales', 'relatedModel' => 'Customer', 'config' => array(
            array('type' => 'CUSTOMER', 'degree' => 'sibling', 'text' => 'Customer', 'max' => '0:0'), // _('Customer')
        ), 'defaultType' => 'CUSTOMER'
            ),
        array('relatedApp' => 'Sales', 'relatedModel' => 'Invoice', 'config' => array(
            array('type' => 'REVERSAL', 'degree' => 'sibling', 'text' => 'Reversal Invoice', 'max' => '1:1'), // _('Reversal Invoice')
            ), 'defaultType' => 'REVERSAL'
        ),
        array('relatedApp' => 'Timetracker', 'relatedModel' => 'Timeaccount', 'config' => array(
            array('type' => 'INVOICE_ITEM', 'degree' => 'sibling', 'text' => 'Invoice Item', 'max' => '0:0'), // _('Invoice Item')
            ), 'defaultType' => 'INVOICE_ITEM'
        ),
        array('relatedApp' => 'WebAccounting', 'relatedModel' => 'IPNet', 'config' => array(
            array('type' => 'INVOICE_ITEM', 'degree' => 'sibling', 'text' => 'Invoice Item', 'max' => '0:0'), // _('Invoice Item')
            ), 'defaultType' => 'INVOICE_ITEM'
        ),
        array('relatedApp' => 'WebAccounting', 'relatedModel' => 'StoragePath', 'config' => array(
            array('type' => 'INVOICE_ITEM', 'degree' => 'sibling', 'text' => 'Invoice Item', 'max' => '0:0'), // _('Invoice Item')
            ), 'defaultType' => 'INVOICE_ITEM'
        ),
        array('relatedApp' => 'WebAccounting', 'relatedModel' => 'BackupPath', 'config' => array(
            array('type' => 'INVOICE_ITEM', 'degree' => 'sibling', 'text' => 'Invoice Item', 'max' => '0:0'), // _('Invoice Item')
            ), 'defaultType' => 'INVOICE_ITEM'
        ),
        array('relatedApp' => 'WebAccounting', 'relatedModel' => 'CertificateDomain', 'config' => array(
            array('type' => 'INVOICE_ITEM', 'degree' => 'sibling', 'text' => 'Invoice Item', 'max' => '0:0'), // _('Invoice Item')
            ), 'defaultType' => 'INVOICE_ITEM'
        ),
        array('relatedApp' => 'WebAccounting', 'relatedModel' => 'DReg', 'config' => array(
            array('type' => 'INVOICE_ITEM', 'degree' => 'sibling', 'text' => 'Invoice Item', 'max' => '0:0'), // _('Invoice Item')
            ), 'defaultType' => 'INVOICE_ITEM'
        ),
        array('relatedApp' => 'WebAccounting', 'relatedModel' => 'MailAccount', 'config' => array(
            array('type' => 'INVOICE_ITEM', 'degree' => 'sibling', 'text' => 'Invoice Item', 'max' => '0:0'), // _('Invoice Item')
            ), 'defaultType' => 'INVOICE_ITEM'
        ),
    );
}

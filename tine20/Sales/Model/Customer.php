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
 * class to hold Customer data
 *
 * @package     Sales
 * @subpackage  Model
 *
 * @property string $name_shorthand
 * @property string $cpextern_id
 * @property ?Sales_Model_Address $postal
 */
class Sales_Model_Customer extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Customer';
    public const TABLE_NAME = 'sales_customers';

    public const FLD_DEBITORS = 'debitors';
    public const FLD_VAT_PROCEDURE = 'vat_procedure';


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
        self::VERSION                   => 6,
        self::APP_NAME                  => Sales_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::RECORD_NAME               => 'Customer', // gettext('GENDER_Customer')
        self::RECORDS_NAME              => 'Customers', // ngettext('Customer', 'Customers', n)
        self::TITLE_PROPERTY            => 'name',

        self::HAS_RELATIONS             => true,
        self::HAS_CUSTOM_FIELDS         => true,
        self::HAS_NOTES                 => true,
        self::HAS_TAGS                  => true,
        self::HAS_ATTACHMENTS           => true,

        self::MODLOG_ACTIVE             => true,
        self::CONTAINER_PROPERTY        => null,
        self::DELEGATED_ACL_FIELD       => self::FLD_DEBITORS,

        self::CREATE_MODULE             => true,
        self::EXPOSE_HTTP_API           => true,

        self::DEFAULT_SORT_INFO         => ['field' => 'number', 'direction' => 'DESC'],

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                'description'                   => [
                    self::COLUMNS                   => ['description'],
                    self::FLAGS                     => [self::TYPE_FULLTEXT],
                ],
            ],
        ],

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_DEBITORS => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTY_CLASSES => [
                        Tinebase_Record_Expander::PROPERTY_CLASS_ACCOUNT_GRANTS => [],
                    ],
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        Sales_Model_Debitor::FLD_DIVISION_ID  => [],
                        Sales_Model_Debitor::FLD_BILLING      => [
                            Tinebase_Record_Expander::EXPANDER_PROPERTIES => ['relations' => []],
                        ],
                        Sales_Model_Debitor::FLD_DELIVERY     => [
                            Tinebase_Record_Expander::EXPANDER_PROPERTIES => ['relations' => []],
                        ],
                        Sales_Model_Debitor::FLD_EAS_ID       => [],
                        Sales_Model_Debitor::FLD_PAYMENT_MEANS=> [
                            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                                Sales_Model_PaymentMeans::FLD_PAYMENT_MEANS_CODE => [],
                            ],
                        ],
                    ],
                ],
                'postal'        => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => ['relations' => []],
                ],
                'cpextern_id'   => [],
                'cpintern_id'   => [],
            ],
        ],
        self::FILTER_MODEL => [
            'document_offer' => [
                self::LABEL => 'Has Offers', // _('Has Offers')
                self::FILTER => Sales_Model_Document_CustomerDocumentFilter::class,
                self::OPTIONS => [
                    self::MODEL_NAME    => Sales_Model_Document_Offer::MODEL_NAME_PART,
                ],
                'jsConfig'          => [
                    'filtertype' => 'foreignrecord',
                    'linkType' => 'foreignId',
                    'foreignRecordClass' => Sales_Model_Document_Offer::class,
                    'multipleForeignRecords' => true,
                    'defaultOperator' => 'definedBy'
                ],
            ],
            'document_order' => [
                self::LABEL => 'Has Orders', // _('Has Orders')
                self::FILTER => Sales_Model_Document_CustomerDocumentFilter::class,
                self::OPTIONS => [
                    self::MODEL_NAME    => Sales_Model_Document_Order::MODEL_NAME_PART,
                ],
                'jsConfig'          => [
                    'filtertype' => 'foreignrecord',
                    'linkType' => 'foreignId',
                    'foreignRecordClass' => Sales_Model_Document_Order::class,
                    'multipleForeignRecords' => true,
                    'defaultOperator' => 'definedBy'
                ],
            ],
            'document_invoice' => [
                self::LABEL => 'Has Invoices', // _('Has Invoices')
                self::FILTER => Sales_Model_Document_CustomerDocumentFilter::class,
                self::OPTIONS => [
                    self::MODEL_NAME    => Sales_Model_Document_Invoice::MODEL_NAME_PART,
                ],
                'jsConfig'          => [
                    'filtertype' => 'foreignrecord',
                    'linkType' => 'foreignId',
                    'foreignRecordClass' => Sales_Model_Document_Invoice::class,
                    'multipleForeignRecords' => true,
                    'defaultOperator' => 'definedBy'
                ],
            ],
            'document_delivery' => [
                self::LABEL => 'Has Deliveries', // _('Has Deliveries')
                self::FILTER => Sales_Model_Document_CustomerDocumentFilter::class,
                self::OPTIONS => [
                    self::MODEL_NAME    => Sales_Model_Document_Delivery::MODEL_NAME_PART,
                ],
                'jsConfig'          => [
                    'filtertype' => 'foreignrecord',
                    'linkType' => 'foreignId',
                    'foreignRecordClass' => Sales_Model_Document_Delivery::class,
                    'multipleForeignRecords' => true,
                    'defaultOperator' => 'definedBy'
                ],
            ],
        ],

        'fields'            => array(
            'number' => array(
                'label'       => 'Customer Number', //_('Customer Number')
                'group'       => 'core',
                'queryFilter' => true,
                self::TYPE    => self::TYPE_NUMBERABLE_STRING,
                self::CONFIG  => [
                    Tinebase_Numberable_String::ZEROFILL => 6,
                ],
            ),
            'name' => array(
                'label'       => 'Name', // _('Name')
                'type'        => 'text',
                'duplicateCheckGroup' => 'name',
                'group'       => 'core',
                'queryFilter' => TRUE,
            ),
            'name_shorthand' => array(
                'label'       => 'Name shorthand', // _('Name shorthand')
                'type'        => 'text',
                'duplicateCheckGroup' => 'name',
                'group'       => 'accounting',
                'queryFilter' => TRUE,
                self::NULLABLE => true,
            ),
            'url' => array(
                'label'       => 'Web', // _('Web')
                'type'        => 'text',
                'group'       => 'misc',
                'shy'         => TRUE,
                self::NULLABLE => true,
            ),
            'description' => array(
                'label'       => 'Description', // _('Description')
                'group'       => 'core',
                'type'        => 'fulltext',
                'queryFilter' => TRUE,
                'shy'         => TRUE,
                self::NULLABLE => true,
            ),
            'cpextern_id'       => array(
                'label'   => 'Contact Person (external)',    // _('Contact Person (external)')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type'    => 'record',
                'group'   => 'core',
                'config'  => array(
                    'appName'     => 'Addressbook',
                    'modelName'   => 'Contact',
                    'idProperty'  => 'id',
                ),
                'recursiveResolving' => true,
                self::NULLABLE => true,
            ),
            'cpintern_id'    => array(
                'label'      => 'Contact Person (internal)',    // _('Contact Person (internal)')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type'       => 'record',
                'group'      => 'core',
                'config' => array(
                    'appName'     => 'Addressbook',
                    'modelName'   => 'Contact',
                    'idProperty'  => 'id',
                ),
                'recursiveResolving' => true,
                self::NULLABLE => true,
            ),
            'vatid' => array (
                'label'   => 'VAT ID', // _('VAT ID')
                'type'    => 'text',
                'group'   => 'accounting',
                'shy'     => TRUE,
                self::NULLABLE => true,
            ),
            self::FLD_VAT_PROCEDURE => [
                self::LABEL => 'VAT Procedure', // _('VAT Procedure')
                self::TYPE => self::TYPE_KEY_FIELD,
                self::NAME => Sales_Config::VAT_PROCEDURES,
            ],
            'iban' => array (
                'label'   => 'IBAN',
                self::TYPE => self::TYPE_TEXT,
                'group'   => 'accounting',
                'shy'     => TRUE,
                self::NULLABLE => true,
            ),
            'bic' => array (
                'label'   => 'BIC',
                self::TYPE => self::TYPE_TEXT,
                'group'   => 'accounting',
                'shy'     => TRUE,
                self::NULLABLE => true,
            ),
            'credit_term' => array (
                'label'   => 'Credit Term (days)', // _('Credit Term (days)')
                'type'    => 'integer',
                'group'   => 'accounting',
                self::UNSIGNED => true,
                'shy'     => TRUE,
                self::NULLABLE => true,
                'inputFilters' => array('Zend_Filter_Empty' => null),
            ),
            'currency' => array (
                'label'   => 'Currency', // _('Currency')
                'type'    => self::TYPE_STRING,
                self::SPECIAL_TYPE    => self::SPECIAL_TYPE_CURRENCY,
                'group'   => 'accounting',
                self::NULLABLE => true,
                self::LENGTH => 4,
            ),
            'currency_trans_rate' => array (
                'label'   => 'Currency Translation Rate', // _('Currency Translation Rate')
                'type'    => 'float',
                'group'   => 'accounting',
                'shy'     => TRUE,
                self::NULLABLE => true,
                self::UNSIGNED => true,
                'inputFilters' => array('Zend_Filter_Empty' => null),
            ),
            'discount' => array (
                'label'   => 'Discount (%)', // _('Discount (%)')
                'type'    => 'float',
                'group'   => 'accounting',
                self::NULLABLE => true,
                self::UNSIGNED => true, // TODO FIXME doesnt work?!
                'inputFilters' => array('Zend_Filter_Empty' => null),
            ),
            'postal' => [
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => TRUE],
                self::TYPE              => self::TYPE_RECORD,
                self::DOCTRINE_IGNORE   => true,
                self::CONFIG            => [
                    self::APP_NAME          => Sales_Config::APP_NAME,
                    self::MODEL_NAME        => Sales_Model_Address::MODEL_NAME_PART,
                    self::ADD_FILTERS       =>[['field' => 'type', 'operator' => 'equals', 'value' => 'postal']],
                    self::REF_ID_FIELD      => 'customer_id',
                    self::DEPENDENT_RECORDS => true,
                    self::FORCE_VALUES      => [
                        'type'                  => 'postal',
                        Sales_Model_Address::FLD_DEBITOR_ID => null,
                    ],
                ]
            ],
            self::FLD_DEBITORS   => [
                self::TYPE              => self::TYPE_RECORDS,
                self::LABEL             => 'Debitors', // _('Debitors')
                self::CONFIG            => [
                    self::APP_NAME          => Sales_Config::APP_NAME,
                    self::MODEL_NAME        => Sales_Model_Debitor::MODEL_NAME_PART,
                    self::REF_ID_FIELD      => Sales_Model_Debitor::FLD_CUSTOMER_ID,
                    self::DEPENDENT_RECORDS => true,
                ],
                self::VALIDATORS        => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::UI_CONFIG         => [
                    'columns'               => [ Sales_Model_Debitor::FLD_NUMBER, Sales_Model_Debitor::FLD_NAME, Sales_Model_Debitor::FLD_DIVISION_ID ],
                    'editDialogConfig'      => ['fieldsToExclude' => [Sales_Model_Debitor::FLD_CUSTOMER_ID]]
                ],
            ],
            'fulltext' => array(
                'type'   => 'virtual',
                'config' => array(
                    'sortable' => false,
                    'type'   => 'string'
                )
            ),
        )
    );

    public function hydrateFromBackend(array &$data)
    {
        parent::hydrateFromBackend($data);
        $this->fulltext = $this->number . ' - ' . $this->name;
    }
    
    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        array('relatedApp' => 'Addressbook', 'relatedModel' => 'Contact', 'config' => array(
            array('type' => 'CUSTOMER', 'degree' => 'sibling', 'text' => 'Customer', 'max' => '0:0'), // _('Customer')
        ), 'defaultType' => 'CUSTOMER'
        )
    );
}

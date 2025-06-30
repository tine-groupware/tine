<?php
/**
 * class to hold contract data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold contract data
 *
 * @package     Sales
 *
 * @property Tinebase_DateTime      $end_date
 */
class Sales_Model_Contract extends Tinebase_Record_Abstract
{
    public const MODEL_NAME_PART = 'Contract';
    public const TABLE_NAME = 'sales_contracts';

    /**
     * relation type: customer
     *
     */
    const RELATION_TYPE_CUSTOMER = 'CUSTOMER';
    
    /**
     * relation type: responsible
     *
     */
    const RELATION_TYPE_RESPONSIBLE = 'RESPONSIBLE';

    public const FLD_BUYER_REFERENCE = 'buyer_reference'; // varchar 255
    public const FLD_PURCHASE_ORDER_REFERENCE = 'purchase_order_reference';
    public const FLD_PROJECT_REFERENCE = 'project_reference';

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
        self::VERSION       => 12,
        'recordName'        => 'Contract', // gettext('GENDER_Contract')
        'recordsName'       => 'Contracts', // ngettext('Contract', 'Contracts', n)
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,
        'createModule'      => TRUE,
        self::HAS_SYSTEM_CUSTOM_FIELDS => true,
        
        'containerProperty' => self::FLD_CONTAINER_ID,

        'containerName'    => 'Contracts',
        'containersName'    => 'Contracts',
        'containerUsesFilter' => FALSE,

        'defaultSortInfo'   => ['field' => 'number', 'direction' => 'DESC'],
        
        'titleProperty'     => 'fulltext',//array('%s - %s', array('number', 'title')),
        'appName'           => Sales_Config::APP_NAME,
        'modelName'         => self::MODEL_NAME_PART,

        self::TABLE         => [
            self::NAME          => self::TABLE_NAME,
            self::INDEXES       => [
                'description'       => [
                    self::COLUMNS       => ['description'],
                    self::FLAGS         => [self::TYPE_FULLTEXT],
                ],
            ],
        ],

        self::ASSOCIATIONS => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                self::FLD_CONTAINER_ID       => [
                    self::TARGET_ENTITY             => Tinebase_Model_Container::class,
                    self::FIELD_NAME                => self::FLD_CONTAINER_ID,
                    self::JOIN_COLUMNS                  => [[
                        self::NAME                          => self::FLD_CONTAINER_ID,
                        self::REFERENCED_COLUMN_NAME        => self::ID,
                        self::ON_DELETE                     => self::CASCADE,
                    ]],
                ],
            ],
        ],

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'billing_address_id' => [],
                'products'       => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        'product_id' => [
                            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                                Sales_Model_Product::FLD_NAME => [],
                                Sales_Model_Product::FLD_DESCRIPTION => [],
                            ],
                        ],
                    ],
                ],
            ]
        ],
        
        'filterModel' => array(
            'contact_internal' => array(
                'filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'label' => 'Contact Person (internal)', // _('Contact Person (internal)')
                'options' => array(
                    'controller' => 'Addressbook_Controller_Contact',
                    'filtergroup' => 'Addressbook_Model_ContactFilter',
                    'own_filtergroup' => 'Sales_Model_ContractFilter',
                    'own_controller' => 'Sales_Controller_Contract',
                    'related_model' => 'Addressbook_Model_Contact',
                ),
            ),
            'contact_external' => array(
                'filter' => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'label' => 'Contact Person (external)', // _('Contact Person (external)')
                'options' => array(
                    'controller' => 'Addressbook_Controller_Contact',
                    'filtergroup' => 'Addressbook_Model_ContactFilter',
                    'own_filtergroup' => 'Sales_Model_ContractFilter',
                    'own_controller' => 'Sales_Controller_Contract',
                    'related_model' => 'Addressbook_Model_Contact',
                ),
            ),
            'products' => array(
                // TODO generalize this for "records" type (Tinebase_Model_Filter_ForeignRecords?)
                'filter' => 'Sales_Model_Filter_ContractProductAggregateFilter',
                'label' => 'Products', // _('Products')
                'jsConfig' => array('filtertype' => 'sales.contract-product')
            ),
        ),
        
        'fields'            => array(
            'parent_id'       => array(
                'label'      => NULL,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type'       => 'record',
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'Contract',
                    'idProperty'  => 'id',
                ),
                self::NULLABLE  => true,
            ),
            'number' => array(
                'label' => 'Number', //_('Number')
                'type'  => 'string',
                self::LENGTH => 64,
                'queryFilter' => TRUE,
            ),
            'title' => array(
                'label'   => 'Title', // _('Title')
                'type'    => 'string',
                'queryFilter' => TRUE,
            ),
            'description' => array(
                'label'   => 'Description', // _('Description')
                'type'    => 'fulltext',
                'queryFilter' => TRUE,
                self::NULLABLE => true,
            ),
            'start_date' => array(
                'type' => 'date',
                'label' => 'Start Date',    // _('Start Date')
                self::UI_CONFIG => [
                    'format' => ['medium'],
                ],
                self::NULLABLE => true,
            ),
            'end_date' => array(
                'type' => 'date',
                'label' => 'End Date',    // _('End Date')
                self::FILTER_DEFINITION         => [
                    self::FILTER                    => Tinebase_Model_Filter_Date::class,
                    self::OPTIONS                   => [
                        Tinebase_Model_Filter_Date::BEFORE_OR_IS_NULL => false,
                        Tinebase_Model_Filter_Date::AFTER_OR_IS_NULL  => true,
                    ]
                ],
                self::UI_CONFIG => [
                    'format' => ['medium'],
                ],
                self::NULLABLE => true,
            ),
            'billing_address_id' => array(
                'label'      => 'Billing Address', // _('Billing Address')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type'       => 'record',
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'Address',
                    'idProperty'  => 'id',
                ),
                self::NULLABLE => true,
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
            self::FLD_BUYER_REFERENCE        => [
                self::LABEL                         => 'Buyer Reference', //_('Buyer Reference')
                self::DESCRIPTION                   => 'An identifier assigned by the acquirer and used for internal control purposes (BT-10 [EN 16931]).', // _('An identifier assigned by the acquirer and used for internal control purposes (BT-10 [EN 16931]).')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
                self::QUERY_FILTER                  => true,
                self::SHY                           => true,
            ],
            self::FLD_PURCHASE_ORDER_REFERENCE  => [
                self::LABEL                         => 'Purchase Order Reference', // _('Purchase Order Reference')
                self::DESCRIPTION                   => 'An identifier issued by the purchaser for a referenced order (BT-13 [EN 16931]).', // _('An identifier issued by the purchaser for a referenced order (BT-13 [EN 16931]).')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
                self::QUERY_FILTER                  => true,
            ],
            self::FLD_PROJECT_REFERENCE         => [
                self::LABEL                         => 'Project Reference', // _('Project Reference')
                self::DESCRIPTION                   => 'The identifier of a project to which the invoice refers (BT-11 [EN 16931]).', // _('The identifier of a project to which the invoice refers (BT-11 [EN 16931]).')
                self::TYPE                          => self::TYPE_STRING,
                self::LENGTH                        => 255,
                self::NULLABLE                      => true,
                self::QUERY_FILTER                  => true,
            ],
            'contact_external' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'relation',
                    'label' => 'Contact Person (external)',    // _('Contact Person (external)')
                    'config' => array(
                        'appName'   => 'Addressbook',
                        'modelName' => 'Contact',
                        'type' => 'CUSTOMER' // yes, it's the same name of type, but another model than the field before
                    )
                )
            ),
            'contact_internal' => array(
                'type' => 'virtual',
                'config' => array(
                    'type' => 'relation',
                    'label' => 'Contact Person (internal)',    // _('Contact Person (internal)')
                    'config' => array(
                        'appName'   => 'Addressbook',
                        'modelName' => 'Contact',
                        'type' => 'RESPONSIBLE'
                    )
                )
            ),
            'products' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'      => 'Products', // _('Products')
                'type'       => 'records', // be careful: records type has no automatic filter definition!
                'config'     => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'ProductAggregate',
                    'refIdField'  => 'contract_id',
                    'dependentRecords' => TRUE
                ),
            ),
            'fulltext' => array(
                'type'   => 'virtual',
                'config' => array(
                    'type'   => 'string',
                    'sortable' => false
                )            
            ),
        )
    );

    /**
     * sets the record related properties from user generated input.
     *
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data            the new data to set
     * @throws Tinebase_Exception_Record_Validation when content contains invalid or missing data
     *
     * @todo remove custom fields handling (use Tinebase_Record_RecordSet for them)
     */
    public function setFromArray(array &$_data)
    {
        parent::setFromArray($_data);
        $this->fulltext = $this->number . ' - ' . $this->title;
    }
    
    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        // a contract may have one responsible and one customer but many partners
        array('relatedApp' => 'Addressbook', 'relatedModel' => 'Contact', 'config' => array(
            array('type' => 'RESPONSIBLE', 'degree' => 'sibling', 'text' => 'Responsible', 'max' => '1:0'), // _('Responsible')
            array('type' => 'CUSTOMER', 'degree' => 'sibling', 'text' => 'Customer', 'max' => '1:0'),  // _('Customer')
            array('type' => 'PARTNER', 'degree' => 'sibling', 'text' => 'Partner', 'max' => '0:0'),  // _('Partner')
            ), 'defaultType' => ''
        ),
        array('relatedApp' => 'Tasks', 'relatedModel' => 'Task', 'config' => array(
            array('type' => 'TASK', 'degree' => 'sibling', 'text' => 'Task', 'max' => '0:0'),
            ), 'defaultType' => ''
        ),
        array('relatedApp' => 'Sales', 'relatedModel' => 'Product', 'config' => array(
            array('type' => 'PRODUCT', 'degree' => 'sibling', 'text' => 'Product', 'max' => '0:0'),
            ), 'defaultType' => ''
        ),
        array('relatedApp' => 'Timetracker', 'relatedModel' => 'Timeaccount', 'config' => array(
            array('type' => 'TIME_ACCOUNT', 'degree' => 'sibling', 'text' => 'Time Account', 'max' => '0:1'), // _('Time Account')
            ), 'defaultType' => ''
        ),
        array('relatedApp' => 'Sales', 'relatedModel' => 'Customer', 'config' => array(
            array('type' => 'CUSTOMER', 'degree' => 'sibling', 'text' => 'Customer', 'max' => '1:0'), // _('Customer')
            ), 'defaultType' => ''
        ),
    );
    
    /**
     * returns the product aggregate for a given accountable
     * 
     * @param Sales_Model_Accountable_Interface $record
     */
    public function findProductAggregate(Sales_Model_Accountable_Interface $record) {
        
        $accountableClassName = get_class($record);
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Product::class, array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'accountable', 'operator' => 'equals', 'value' => $accountableClassName)));
        $products = Sales_Controller_Product::getInstance()->search($filter);
        
        $filter = new Sales_Model_ProductAggregateFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'product_id', 'operator' => 'in', 'value' => $products->getArrayOfIds())));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'contract_id', 'operator' => 'equals', 'value' => $this->getId())));

        $pas = Sales_Controller_ProductAggregate::getInstance()->search($filter);
        
        if ($pas->count() < 1) {
            throw new Tinebase_Exception_Data('A contract aggregate could not be found!');
        } elseif ($pas->count() > 1) {
            throw new Tinebase_Exception_Data('At the moment a contract may have only one product aggregate for the same product, not more!');
        }
        
        return $pas->getFirstRecord();
    }

    public static function touchOnRelated(Tinebase_Model_Relation $relation): bool
    {
        if (Sales_Model_Invoice::class === $relation->own_model) {
            return false;
        }
        return true;
    }
}

<?php
/**
 * Tine 2.0

 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Supplier data
 *
 * @package     Sales
 * @subpackage  Model
 */

class Sales_Model_Supplier extends Tinebase_Record_NewAbstract
{
    public const TABLE_NAME = 'sales_suppliers';

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
        self::VERSION       => 5,
        'recordName'        => 'Supplier', // gettext('GENDER_Supplier')
        'recordsName'       => 'Suppliers', // ngettext('Supplier', 'Suppliers', n)
        'hasRelations'      => TRUE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,
        'createModule'      => TRUE,
        'containerProperty' => NULL,
        'resolveVFGlobally' => TRUE,
        
        'titleProperty'     => '{{number}} - {{name}}',
        'appName'           => 'Sales',
        'modelName'         => 'Supplier',

        'exposeHttpApi'     => true,
        self::EXPOSE_JSON_API   => true,

        'defaultSortInfo'   => ['field' => 'number', 'direction' => 'DESC'],

        self::TABLE             => [
            self::NAME    => self::TABLE_NAME,
            self::INDEXES => [
                'description' => [
                    self::COLUMNS       => ['description'],
                    self::FLAGS         => [self::TYPE_FULLTEXT],
                ],
            ]
        ],

        'fields'            => array(
            'number' => array(
                'label'       => 'Supplier Number', //_('Supplier Number')
                'group'       => 'core',
                'queryFilter' => TRUE,
                'type'        => 'integer'
            ),
            'name' => array(
                'label'       => 'Name', // _('Name')
                'type'        => 'text',
                'duplicateCheckGroup' => 'name',
                'group'       => 'core',
                'queryFilter' => TRUE,
                'nullable'   => false,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
            ),
            'url' => array(
                'label'       => 'Web', // _('Web')
                'type'        => 'text',
                'group'       => 'misc',
                'shy'         => TRUE,
                'nullable'   => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
            ),
            'description' => array(
                'label'       => 'Description', // _('Description')
                self::TYPE                      => self::TYPE_FULLTEXT,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => TRUE],
                self::QUERY_FILTER              => true,
                'group'       => 'core',
                'shy'         => TRUE,
            ),
            'cpextern_id'       => array(
                'label'   => 'Contact Person (external)',    // _('Contact Person (external)')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                self::NULLABLE => true,
                'type'    => 'record',
                'group'   => 'core',
                'config'  => array(
                    'appName'     => 'Addressbook',
                    'modelName'   => 'Contact',
                    'idProperty'  => 'id',
                )
            ),
            'cpintern_id'    => array(
                'label'      => 'Contact Person (internal)',    // _('Contact Person (internal)')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                self::NULLABLE => true,
                'type'       => 'record',
                'group'      => 'core',
                'config' => array(
                    'appName'     => 'Addressbook',
                    'modelName'   => 'Contact',
                    'idProperty'  => 'id',
                )
            ),
            'vatid' => array (
                'label'   => 'VAT ID', // _('VAT ID')
                'type'    => 'text',
                'group'   => 'accounting',
                'shy'     => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                self::NULLABLE => true,
            ),
            'vat_procedure' => [
                'label' => 'VAT Procedure', // _('VAT Procedure')
                'type' => 'keyfield',
                'name' => 'vatProcedures',
            ],
            'credit_term' => array (
                'label'   => 'Credit Term (days)', // _('Credit Term (days)')
                'type'    => 'integer',
                'group'   => 'accounting',
                'default' => 10,
                'nullable'   => true,
                'shy'     => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'currency' => array (
                'label'   => 'Currency', // _('Currency')
                'type'    => 'text',
                'group'   => 'accounting',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                self::NULLABLE => true,
                self::SPECIAL_TYPE    => self::SPECIAL_TYPE_CURRENCY,
            ),
            'currency_trans_rate' => array (
                'label'   => 'Currency Translation Rate', // _('Currency Translation Rate')
                'type'    => 'float',
                'group'   => 'accounting',
                'default' => 1,
                'inputFilters' => ['Zend_Filter_Empty' => 1],
                'shy'     => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'iban' => array (
                'label'   => 'IBAN',
                'group'   => 'accounting',
                'shy'     => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                self::NULLABLE => true,
            ),
            'bic' => array (
                'label'   => 'BIC',
                'group'   => 'accounting',
                'shy'     => true,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                self::NULLABLE => true,
            ),
            #'discount' => array(
            #    'label'   => 'Discount (%)', // _('Discount (%)')
            #    'type'  => 'float',
            #    'specialType' => 'percent',
            #    'default' => 0,
            #    //'inputFilters' => array('Zend_Filter_Empty' => 0),
            #    //'shy' => TRUE,
            #),
            // the postal address
            'postal_id' => array(
                'type' => 'virtual',
                'config' => array(
                    'duplicateOmit' => TRUE,
                    'label'         => NULL,
                )
            ),
            'adr_prefix1' => array(
                'config' => array(
                    'duplicateOmit' => TRUE,
                    'label'         => 'Prefix', //_('Prefix')
                    'shy'           => TRUE
                ),
                'type'   => 'virtual',
            ),
            'adr_prefix2' => array(
                'config' => array(
                    'duplicateOmit' => TRUE,
                    'label'         => 'Additional Prefix', //_('Additional Prefix')
                    'shy'           => TRUE
                ),
                'type'   => 'virtual',
            ),
            'adr_name' => [
                'config' => [
                    'duplicateOmit' => TRUE,
                    'label'         => 'Name', //_('Name')
                    'shy'           => TRUE
                ],
                'type'   => 'virtual',
            ],
            'adr_email' => [
                'config' => [
                    'duplicateOmit' => TRUE,
                    'label'         => 'Email', //_('Name')
                    'shy'           => TRUE
                ],
                'type'   => 'virtual',
            ],
            'adr_street' => array(
                'config' => array(
                    'duplicateOmit' => TRUE,
                    'label'         => 'Street', //_('Street')
                    'shy'           => TRUE
                ),
                'type' => 'virtual',
            ),
            'adr_postalcode' => array(
                'type' => 'virtual',
                'config' => array(
                    'duplicateOmit' => TRUE,
                    'label'         => 'Postal Code', //_('Postal Code')
                    'shy'           => TRUE
                ),
            ),
            'adr_locality' => array(
                'type' => 'virtual',
                'config' => array(
                    'duplicateOmit' => TRUE,
                    'label'         => 'Locality', //_('Locality')
                    'shy'           => TRUE
                ),
            ),
            'adr_region' => array(
                'type' => 'virtual',
                'config' => array(
                    'duplicateOmit' => TRUE,
                    'label'         => 'Region', //_('Region')
                    'shy'           => TRUE
                ),
            ),
            'adr_countryname' => array(
                'type' => 'virtual',
                'config' => array(
                    'duplicateOmit' => TRUE,
                    'label'         => 'Country', //_('Country')
                    'shy'           => TRUE,
                    'default'       => 'DE'
                ),
            ),
            'adr_pobox' => array(
                'type' => 'virtual',
                'config' => array(
                    'duplicateOmit' => TRUE,
                    'label'         => 'Postbox', //_('Postbox')
                    'shy'           => TRUE
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
        $this->fulltext = $this->number . ' - ' . $this->name;
    }
    
    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        array(
            'relatedApp'   => 'Addressbook',
            'relatedModel' => 'Contact',
            'config'       => array(
                array('type' => 'SUPPLIER', 'degree' => 'sibling', 'text' => 'Supplier', 'max' => '0:0'), // _('Supplier')
            ),
            'defaultType'  => 'SUPPLIER'
        )
    );
}

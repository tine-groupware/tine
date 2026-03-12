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
    public const MODEL_NAME_PART = 'Supplier';

    public const FLD_EAS_ID = 'eas_id';
    public const FLD_ELECTRONIC_ADDRESS = 'electronic_address';

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
        self::VERSION       => 6,
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

        self::JSON_EXPANDER     => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                'postal_id' => [],
            ],
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
            'postal_id' => [
                self::TYPE          => self::TYPE_RECORD,
                self::CONFIG        => [
                    self::APP_NAME      => Sales_Config::APP_NAME,
                    self::MODEL_NAME    => Sales_Model_Address::MODEL_NAME_PART,
                    self::REF_ID_FIELD  => Sales_Model_Address::FLD_SUPPLIER_ID,
                    self::DEPENDENT_RECORDS => true,
                ],
            ],
            'fulltext' => array(
                'type'   => 'virtual',
                'config' => array(
                    'type'   => 'string',
                    'sortable' => false
                )           
            ),
            self::FLD_EAS_ID                => [
                self::TYPE                      => self::TYPE_RECORD,
                self::LABEL                     => 'Electronic Address Schema', // _('Electronic Address Schema')
                self::DESCRIPTION               => "The pattern for 'Seller electronic address (BT-34 [EN 16931]).", //_("The pattern for 'Seller electronic address (BT-34 [EN 16931]).")
                self::NULLABLE                  => true,
                self::CONFIG                    => [
                    self::APP_NAME                  => Sales_Config::APP_NAME,
                    self::MODEL_NAME                => Sales_Model_EDocument_EAS::MODEL_NAME_PART,
                ],
            ],
            self::FLD_ELECTRONIC_ADDRESS    => [
                self::TYPE                      => self::TYPE_STRING,
                self::LABEL                     => 'Electronic Address', // _('Electronic Address')
                self::DESCRIPTION               => 'Specifies the electronic address of the vendor to which the application level response to an invoice can be sent (BT-34 [EN 16931]).', //_('Specifies the electronic address of the vendor to which the application level response to an invoice can be sent (BT-34 [EN 16931]).')
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
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

    public function fromXRXml(SimpleXMLElement $xr): static
    {
        $this->name = (string)$xr->SELLER->Seller_name; // 1 (BT-27)
//            'url' => ?
        $this->vat_procedure = Sales_Config::getInstance()->{Sales_Config::VAT_PROCEDURES}->records
            ->find(Sales_Model_EDocument_VATProcedure::FLD_UNTDID_5305, $xr->VAT_BREAKDOWN->VAT_category_code)?->id
                ?? Sales_Config::VAT_PROCEDURE_STANDARD; // 1
        $this->currency = (string)$xr->Invoice_currency_code; // 1
        $this->{self::FLD_EAS_ID} = Sales_Controller_EDocument_EAS::getInstance()
            ->getByCode((string)$xr->SELLER->Seller_electronic_address->attributes()->scheme_identifier); // 1
        $this->{self::FLD_ELECTRONIC_ADDRESS} = (string)$xr->SELLER->Seller_electronic_address; // 1

        if((string)$xr->PAYMENT_INSTRUCTIONS->Payment_means_type_code === "58") {
            $this->iban = (string)$xr->PAYMENT_INSTRUCTIONS->CREDIT_TRANSFER[0]->Payment_account_identifier;
            $this->bic = (string)$xr->PAYMENT_INSTRUCTIONS->CREDIT_TRANSFER[0]->Payment_service_provider_identifier;
        }

//        foreach ($xr->SELLER->Seller_identifier as $tmp) {}; // 0..*
//        $xr->Seller_legal_registration_identifier; // 0..*
        if ($vatId = (string)$xr->SELLER->Seller_VAT_identifier) { // 0..1
            $this->vatid = $vatId;
        }
//        $xr->SELLER->Seller_tax_registration_identifier; // 0..1
//        $xr->SELLER->Seller_additional_legal_information; // 0..1

        // 1 SELLER_POSTAL_ADDRESS
        $sellerPostalAdr = $xr->SELLER->SELLER_POSTAL_ADDRESS;
        if (!$this->postal_id instanceof Sales_Model_Address) {
            $this->postal_id = new Sales_Model_Address([], true);
        }
        $this->postal_id->prefix1 = (string)$sellerPostalAdr->Seller_address_line_1 /* 0..1 */ ?: null;
        $this->postal_id->prefix2 = (string)$sellerPostalAdr->Seller_address_line_2 /* 0..1 */ ?: null;
        $this->postal_id->name = (string)$xr->SELLER->Seller_trading_name /* 0..1 (BT-28) */ ?: (string)$xr->SELLER->Seller_name /* 1 (BT-27) */;
        $this->postal_id->email = (string)$xr->SELLER->SELLER_CONTACT->Seller_contact_email_address ?: null;
        $this->postal_id->street = (string)$sellerPostalAdr->Seller_address_line_3 /* 0..1 */ ?: null;
        $this->postal_id->postalcode = (string)$sellerPostalAdr->Seller_post_code /* 1 */;
        $this->postal_id->locality = (string)$sellerPostalAdr->Seller_city /* 1 */;
        $this->postal_id->region = (string)$sellerPostalAdr->Seller_country_subdivision /* 0..1 */ ?: null;
        $this->postal_id->countryname  = (string)$sellerPostalAdr->Seller_country_code /* 1 */;
//        $this->postal_id->pobox;

        // 1 SELLER_CONTACT
//        $sellerContact = $xr->SELLER->SELLER_CONTACT;
//        $sellerContact->Seller_contact_point; // 0..1
//        $sellerContact->Seller_contact_telephone_number; // 0..1
//        $sellerContact->Seller_contact_email_address; // 0..1

        return $this;
    }
}

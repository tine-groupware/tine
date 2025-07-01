<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class documentation
 *
 * @package     Addressbook
 * @subpackage  Frontend
 */
class Addressbook_Frontend_ActiveSync extends ActiveSync_Frontend_Abstract implements Syncroton_Data_IDataSearch
{
    protected $_mapping = array(
        #'Anniversary'           => 'anniversary',
        #'AssistantName'         => 'assistantname',
        'assistantPhoneNumber'  => 'tel_assistent',
        'birthday'              => 'bday',
        'body'                  => 'note',
        #'Business2PhoneNumber'  => 'business2phonenumber',
        'businessAddressCity'          => 'adr_one_locality',
        'businessAddressCountry'       => 'adr_one_countryname',
        'businessAddressPostalCode'    => 'adr_one_postalcode',
        'businessAddressState'         => 'adr_one_region',
        'businessAddressStreet'        => 'adr_one_street',
        'businessFaxNumber'     => 'tel_fax',
        'businessPhoneNumber'   => 'tel_work',
        #'CarPhoneNumber'        => 'carphonenumber',
        #'Categories'            => 'categories',
        #'Category'              => 'category',
        #'Children'              => 'children',
        #'Child'                 => 'child',
        'companyName'           => 'org_name',
        'department'            => 'org_unit',
        'email1Address'         => 'email',
        'email2Address'         => 'email_home',
        #'Email3Address'         => 'email3address',
        'fileAs'                => 'n_fileas',
        'firstName'             => 'n_given',
        'home2PhoneNumber'      => 'tel_cell_private',
        'homeAddressCity'       => 'adr_two_locality',
        'homeAddressCountry'    => 'adr_two_countryname',
        'homeAddressPostalCode' => 'adr_two_postalcode',
        'homeAddressState'      => 'adr_two_region',
        'homeAddressStreet'     => 'adr_two_street',
        'homeFaxNumber'         => 'tel_fax_home',
        'homePhoneNumber'       => 'tel_home',
        'jobTitle'              => 'title', 
        'lastName'              => 'n_family',
        'middleName'            => 'n_middle',
        'mobilePhoneNumber'     => 'tel_cell',
        'officeLocation'        => 'room',
        #'OtherCity'             => 'adr_one_locality',
        #'OtherCountry'          => 'adr_one_countryname',
        #'OtherPostalCode'       => 'adr_one_postalcode',
        #'OtherState'            => 'adr_one_region',
        #'OtherStreet'           => 'adr_one_street',
        'pagerNumber'           => 'tel_pager',
        #'RadioPhoneNumber'      => 'radiophonenumber',
        #'Spouse'                => 'spouse',
        'suffix'                => 'n_prefix',
        #'Title'                 => '', //salutation
        'webPage'               => 'url',
        #'YomiCompanyName'       => 'yomicompanyname',
        #'YomiFirstName'         => 'yomifirstname',
        #'YomiLastName'          => 'yomilastname',
        #'Rtf'                   => 'rtf',
        'picture'               => 'jpegphoto'
    );
        
    /**
     * name of Tine 2.0 backend application
     * 
     * @var string
     */
    protected $_applicationName     = 'Addressbook';
    
    /**
     * name of Tine 2.0 model to use
     * 
     * @var string
     */
    protected $_modelName           = 'Contact';
    
    /**
     * type of the default folder
     *
     * @var int
     */
    protected $_defaultFolderType   = Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT;
    
    /**
     * default container for new entries
     * 
     * @var string
     */
    protected $_defaultFolder       = ActiveSync_Preference::DEFAULTADDRESSBOOK;
    
    /**
     * type of user created folders
     *
     * @var int
     */
    protected $_folderType          = Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT_USER_CREATED;

    /**
     * name of property which defines the filterid for different content classes
     * 
     * @var string
     */
    protected $_filterProperty = 'contactsfilterId';
    
    /**
     * field to sort search results by
     * 
     * @var string
     */
    protected $_sortField = 'n_fileas';
    
    protected $_defaultContainerPreferenceName = Addressbook_Preference::DEFAULTADDRESSBOOK;

    /**
     * the constructor
     *
     * @param Tinebase_DateTime $_syncTimeStamp
     */
    public function __construct(Syncroton_Model_IDevice $_device, DateTime $_syncTimeStamp)
    {
        parent::__construct($_device, $_syncTimeStamp);

        foreach (Addressbook_Controller_ContactProperties_Definition::getInstance()->getAll() as $cpDef) {
            if (isset($cpDef->{Addressbook_Model_ContactProperties_Definition::FLD_ACTIVE_SYNC_MAP})) {
                foreach ($cpDef->{Addressbook_Model_ContactProperties_Definition::FLD_ACTIVE_SYNC_MAP} as $key => $val) {
                    $this->_mapping[$key] = $val;
                }
            }
        }
    }

    /**
     * Search command handler
     * 
     * the search command is only a stub to make the AS Search command happy
     * Tine 2.0 sync's the GAL entries as normal adddressbooks 
     *
     * @param Syncroton_Model_StoreRequest $store   Search query parameters
     * @return Syncroton_Model_StoreResponse
     */
    public function search(Syncroton_Model_StoreRequest $store)
    {
        $storeResponse = new Syncroton_Model_StoreResponse();
        $storeResponse->total = 0;
        
        return $storeResponse;
    }
    
    /**
     * @see ActiveSync_Frontend_Abstract::toSyncrotonModel()
     *
     * @param Addressbook_Model_Contact $entry
     * @param array $options
     * @return Syncroton_Model_Contact
     */
    public function toSyncrotonModel($entry, array $options = array())
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
            __METHOD__ . '::' . __LINE__ . " contact data " . print_r($entry->toArray(), TRUE));
        
        $syncrotonContact = new Syncroton_Model_Contact();

        foreach ($entry::getConfiguration()->getJsonFacadeFields() as $fieldKey => $def) {
            /** @var Tinebase_Record_JsonFacadeInterface $model */
            $model = $def[Tinebase_ModelConfiguration_Const::CONFIG][Tinebase_ModelConfiguration_Const::RECORD_CLASS_NAME];
            $model::jsonFacadeToJson($entry, $fieldKey, $def);
        }

        foreach ($this->_mapping as $syncrotonProperty => $tine20Property) {
            $val = $entry;
            foreach (explode('.', $tine20Property) as $pathPart) {
                $val = $val->{$pathPart};
                if (!is_object($val)) {
                    break;
                }
            }

            if ($this->_isEmptyValue($val)) {
                // skip empty values
                continue;
            }
            
            switch($syncrotonProperty) {
                case 'businessAddressCountry':
                case 'homeAddressCountry':
                    $syncrotonContact->$syncrotonProperty = Tinebase_Translation::getCountryNameByRegionCode($val);
                    
                    break;
                    
                case 'birthday':
                    $syncrotonContact->$syncrotonProperty = $entry->$tine20Property;
                    
                   if ($this->_device->devicetype == Syncroton_Model_Device::TYPE_BLACKBERRY && version_compare($this->_device->getMajorVersion(), '10', '>=')) {
                        // BB 10+ expects birthday to be at noon
                        $syncrotonContact->$syncrotonProperty->addHour(12);
                    }
                    
                    break;
                    
                case 'body':
                    $syncrotonContact->$syncrotonProperty = new Syncroton_Model_EmailBody(array(
                        'type' => Syncroton_Model_EmailBody::TYPE_PLAINTEXT,
                        'data' => $entry->$tine20Property
                    ));
                    
                    break;
                    
                case 'picture':
                    try {
                        $syncrotonContact->$syncrotonProperty = $entry->getSmallContactImage();
                    } catch (Exception $e) {
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Image for contact {$entry->getId()} not found or invalid");
                    }
                    
                    break;
                    
                default:
                    $syncrotonContact->$syncrotonProperty = $val;
                    
                    break;
            }
        }
        
        return $syncrotonContact;
    }
    
    /**
     * convert contact from xml to Addressbook_Model_Contact
     *
     * @param Syncroton_Model_IEntry $data
     * @param $entry
     * @return Addressbook_Model_Contact
     */
    public function toTineModel(Syncroton_Model_IEntry $data, $entry = null)
    {
        if ($entry instanceof Addressbook_Model_Contact) {
            $contact = $entry;
        } else {
            $contact = new Addressbook_Model_Contact(null, true);
        }
        unset($contact->jpegphoto);
        $mc = Addressbook_Model_Contact::getConfiguration();
        $contactFields = $mc->getFields();

        foreach ($this->_mapping as $fieldName => $value) {

            $propPart2 = null;
            @[$propPart1, $propPart2] = explode('.', $value);
            if (null !== $propPart2) {
                $property = $propPart2;
                if (null === ($obj = $contact->$propPart1)) {
                    if (Tinebase_ModelConfiguration_Const::TYPE_RECORD !== $contactFields[$propPart1][Tinebase_ModelConfiguration_Const::TYPE]) {
                        throw new Tinebase_Exception_NotImplemented('field ' . $propPart1 . ' is not of type record');
                    }
                    $obj = new $contactFields[$propPart1][Tinebase_ModelConfiguration_Const::CONFIG][Tinebase_ModelConfiguration_Const::RECORD_CLASS_NAME]([], true);
                    $contact->$propPart1 = $obj;
                }
                $fields = $obj::getConfiguration()->getFields();
            } else {
                $obj = $contact;
                $property = $propPart1;
                $fields = $contactFields;
            }

            if (!isset($data->$fieldName)) {
                $obj->$property = null;
                continue;
            }

            $maxLength = $fields[$property]['length'] ?? null;

            switch ($fieldName) {
                case 'picture':
                    if(!empty($data->$fieldName)) {
                        $devicePhoto = $data->$fieldName;
                        $contact->setSmallContactImage($devicePhoto);
                    } else if ($entry && ! empty($entry->jpegphoto)) {
                        $contact->jpegphoto = '';
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                            . ' Deleting contact photo on device request (contact id: ' . $contact->getId() . ')');
                    }
                    
                    break;
                    
                case 'birthday':
                    $obj->$property = new Tinebase_DateTime($data->$fieldName);
                    
                    if ($this->_device->devicetype == Syncroton_Model_Device::TYPE_IPHONE && $this->_device->getMajorVersion() < 800) {
                        // iOS < 4 & webos < 2.1 send birthdays to the entered date, but the time the birthday got entered on the device
                        // actually iOS < 4 sometimes sends the bday at noon but the timezone is not clear
                        // -> we don't trust the time part and set the birthdays timezone to the timezone the user has set in tine
                        $userTimezone = Tinebase_Core::getUserTimezone();
                        $obj->$property = new Tinebase_DateTime($obj->$property->setTime(0,0,0)->format(Tinebase_Record_Abstract::ISO8601LONG), $userTimezone);
                        $obj->$property->setTimezone('UTC');
                    } elseif ($this->_device->devicetype == Syncroton_Model_Device::TYPE_BLACKBERRY && version_compare($this->_device->getMajorVersion(), '10', '>=')) {
                        // BB 10+ expects birthday to be at noon
                        $obj->$property->subHour(12);
                    }
                    
                    break;
                    
                case 'businessAddressCountry':
                case 'homeAddressCountry':
                    $obj->$property = Tinebase_Translation::getRegionCodeByCountryName($data->$fieldName);
                    break;
                    
                case 'email1Address':
                case 'email2Address':
                case 'email3Address':
                    // android sends email address as
                    // Lars Kneschke <l.kneschke@metaways.de>
                    if (preg_match('/(.*)<(.+@[^@]+)>/', $data->$fieldName, $matches)) {
                        $obj->$property = trim($matches[2]);
                    } else {
                        $obj->$property = $data->$fieldName;
                    }
                    
                    break;
                
                case 'body':
                    // @todo check $data->$fieldName->Type and convert to/from HTML if needed
                    if ($data->$fieldName instanceof Syncroton_Model_EmailBody) {
                        $obj->$property = $data->$fieldName->data;
                    } else {
                        $obj->$property = null;
                    }
                    
                    break;
                    
                case 'webPage':
                    // remove facebook urls
                    if (! preg_match('/^fb:\/\//', $data->$fieldName)) {
                        $obj->$property = $data->$fieldName;
                    }
                    
                    break;
                    
                default:
                    $this->_truncateField($obj, $property, $data->$fieldName, $maxLength);
                    break;
            }

            if (in_array($fields[$property]['type'], [
                Tinebase_Record_NewAbstract::TYPE_STRING,
                Tinebase_Record_NewAbstract::TYPE_STRING_AUTOCOMPLETE,
                Tinebase_Record_NewAbstract::TYPE_FULLTEXT,
                Tinebase_Record_NewAbstract::TYPE_STRICTFULLTEXT,
                Tinebase_Record_NewAbstract::TYPE_TEXT,
            ])) {
                $obj->$property = Tinebase_Core::filterInputForDatabase($obj->$property);
            }
        }

        $data = array(
            'n_given'   => $contact->n_given,
            'n_family'  => $contact->n_family,
            'org_name'  => $contact->org_name
        );
        // force update of n_fileas and n_fn
        $contact->setFromArray($data);

        // contact should be valid now
        $contact->isValid();
        
        return $contact;
    }
    
    /**
     * get devices with multiple folders
     * 
     * @return array
     */
    protected function _getDevicesWithMultipleFolders()
    {
        // outlook currently (Microsoft.Outlook.15) does not support mutliple addressbooks
        // @see 0009184: Only Admin Contact Data is synced (Outlook 2013)
        $doesNotSupportMultipleFolders = array('windowsoutlook15');
        $result = array_diff(parent::_getDevicesWithMultipleFolders(), $doesNotSupportMultipleFolders);
        
        return $result;
    }
}

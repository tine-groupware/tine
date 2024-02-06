<?php
/**
 * Tine 2.0
 * 
 * MAIN controller for addressbook, does event and container handling
 *
 * @package     Addressbook
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * main controller for Addressbook
 *
 * @package     Addressbook
 * @subpackage  Controller
 */
class Addressbook_Controller extends Tinebase_Controller_Event implements Tinebase_Application_Container_Interface
{
    /**
     * holds the instance of the singleton
     *
     * @var Addressbook_Controller
     */
    private static $_instance = NULL;

    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'Addressbook_Model_Contact';

    /**
     * Models of this application that make use of Tinebase_Record_Path
     *
     * @var array|null
     */
    protected $_modelsUsingPath = array(
        Addressbook_Model_Contact::class,
        Addressbook_Model_List::class,
        // don't ever add listrole or listmemberrole here! why? read the code and until you do, dont touch paths at all
    );
    
    /**
     * constructor (get current user)
     */
    private function __construct() {
        $this->_applicationName = 'Addressbook';
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * the singleton pattern
     *
     * @return Addressbook_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Addressbook_Controller;
        }
        
        return self::$_instance;
    }

    /**
     * event handler function
     * 
     * all events get routed through this function
     *
     * @param Tinebase_Event_Abstract $_eventObject the eventObject
     * 
     * @todo    write test
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        switch (get_class($_eventObject)) {
            case 'Admin_Event_AddAccount':
                $this->createPersonalFolder($_eventObject->account);
                break;
            case 'Tinebase_Event_User_DeleteAccount':
                /**
                 * @var Tinebase_Event_User_DeleteAccount $_eventObject
                 */
                if ($_eventObject->deletePersonalContainers()) {
                    $this->deletePersonalFolder($_eventObject->account, Addressbook_Model_Contact::class);
                }

                $oldAcl = Addressbook_Controller_Contact::getInstance()->doContainerACLChecks(false);
                $oldRight = Addressbook_Controller_Contact::getInstance()->doRightChecks(false);
                $oldAreaLock = Addressbook_Controller_Contact::getInstance()->doAreaLockCheck(false);
                $raii = new Tinebase_RAII(function() use ($oldAcl, $oldAreaLock, $oldRight) {
                    Addressbook_Controller_Contact::getInstance()->doContainerACLChecks($oldAcl);
                    Addressbook_Controller_Contact::getInstance()->doRightChecks($oldRight);
                    Addressbook_Controller_Contact::getInstance()->doAreaLockCheck($oldAreaLock);
                });

                // make to be deleted accounts (user) contact a normal contact
                if ($_eventObject->keepAsContact()) {
                    $contact = Addressbook_Controller_Contact::getInstance()->get($_eventObject->account->contact_id);
                    $contact->type = Addressbook_Model_Contact::CONTACTTYPE_CONTACT;
                    Addressbook_Controller_Contact::getInstance()->update($contact);

                } else {
                    // or just delete it
                    Addressbook_Controller_Contact::getInstance()->delete([$_eventObject->account->contact_id]);
                }

                unset($raii);
                break;
            case 'Tinebase_Event_Container_BeforeCreate':
                $this->_handleContainerBeforeCreateEvent($_eventObject);
                break;
            case 'Admin_Event_DeleteMailingList':
                $list = Addressbook_Controller_List::getInstance()->get($_eventObject->listId);
                $list->email = null;
                $list->type = Addressbook_Model_List::LISTTYPE_GROUP;
                $list->xprops()[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST] = false;
                Addressbook_Controller_List::getInstance()->update($list);
                break;
        }
    }

    /**
     * creates the initial folder for new accounts
     *
     * @param mixed|Tinebase_Model_User $_account
     * @return Tinebase_Record_RecordSet of subtype Tinebase_Model_Container
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    public function createPersonalFolder($_account): Tinebase_Record_RecordSet
    {
        $result = new Tinebase_Record_RecordSet(Tinebase_Model_Container::class);
        $translation = Tinebase_Translation::getTranslation($this->_applicationName);
        $account = Tinebase_User::getInstance()->getUserById($_account);
        $name = sprintf($translation->_("%s's personal addressbook"), $account->accountFullName);
        $container = Tinebase_Container::getInstance()->createDefaultContainer(
            Addressbook_Model_Contact::class,
            $this->_applicationName,
            $account,
            $name
        );
        $result->addRecord($container);
        return $result;
    }

    protected function _handleContainerBeforeCreateEvent(Tinebase_Event_Container_BeforeCreate $_eventObject)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
            . ' about to handle Tinebase_Event_Container_BeforeCreate' );

        $this->_addDefaultPersonalGrantsToContainer(
            $_eventObject->container,
            'Addressbook',
            $_eventObject->grants,
            $_eventObject->accountId,
            [
                Addressbook_Model_Contact::class,
                Addressbook_Model_List::class,
            ],
            Addressbook_Model_ContactGrants::class
        );
    }

    /**
     * returns contact image
     * 
     * @param   string $_identifier record identifier
     * @param   string $_location not used, required by interface
     * @return  Tinebase_Model_Image
     * @throws  Addressbook_Exception_NotFound if no image found
     */
    public function getImage($_identifier, $_location = '')
    {
        // get contact to ensure user has read rights
        $image = Addressbook_Controller_Contact::getInstance()->getImage($_identifier);
        
        if (empty($image)) {
            throw new Addressbook_Exception_NotFound('Contact has no image.');
        }
        $imageInfo = Tinebase_ImageHelper::getImageInfoFromBlob($image);

        return new Tinebase_Model_Image($imageInfo + array(
            'id'           => sha1($image),
            'application'  => $this->_applicationName,
            'data'         => $image
        ));
    }

    /**
     * get core data for this application
     *
     * @return Tinebase_Record_RecordSet
     */
    public function getCoreDataForApplication()
    {
        $result = parent::getCoreDataForApplication();

        $application = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);

        if (Tinebase_Core::getUser()->hasRight($application, Addressbook_Acl_Rights::MANAGE_CORE_DATA_LISTS)) {
            $result->addRecord(new CoreData_Model_CoreData(array(
                'id' => 'adb_lists',
                'application_id' => $application,
                'model' => 'Addressbook_Model_List',
                'label' => 'Groups' // _('Groups')
            )));
        }

        if (Tinebase_Core::getUser()->hasRight($application, Addressbook_Acl_Rights::MANAGE_CORE_DATA_LIST_ROLES)) {
            $result->addRecord(new CoreData_Model_CoreData(array(
                'id' => 'adb_list_roles',
                'application_id' => $application,
                'model' => 'Addressbook_Model_ListRole',
                'label' => 'Group Functions' // _('Group Functions')
            )));
        }

        if (Addressbook_Config::getInstance()->featureEnabled(Addressbook_Config::FEATURE_INDUSTRY)) {
            $result->addRecord(new CoreData_Model_CoreData(array(
                    'id' => 'adb_industries',
                    'application_id' => $application,
                    'model' => 'Addressbook_Model_Industry',
                    'label' => 'Industries' // _('Industries')
            )));
        }

        return $result;
    }

    /**
     * get default internal adb id
     *
     * @return string
     */
    public static function getDefaultInternalAddressbook()
    {
        $appConfigDefaults = Admin_Controller::getInstance()->getConfigSettings();
        $result = (isset($appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK])) ?
            $appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK] : NULL;

        if (empty($result)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::'
                . __LINE__ . ' Default internal addressbook not found. Creating new config setting.');
            $result = Addressbook_Setup_Initialize::setDefaultInternalAddressbook()->getId();
        }
        return $result;
    }
}

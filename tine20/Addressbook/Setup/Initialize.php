<?php
/**
 * Tine 2.0
  * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Addressbook initialization
 * 
 * @todo move {@see _createInitialAdminAccount} to a better place (resolve dependency from addressbook)
 * 
 * @package Addressbook
 */
class Addressbook_Setup_Initialize extends Setup_Initialize
{
    /**
     * addressbook for internal contacts/groups
     * 
     * @var Tinebase_Model_Container
     */
    protected $_internalAddressbook = NULL;
    
    /**
     * Override method: Setup needs additional initialisation
     *
     * @param Tinebase_Model_Application $_application
     * @param array $_options
     *
     * @see tine20/Setup/Setup_Initialize#_initialize($_application)
     */
    protected function _initialize(Tinebase_Model_Application $_application, $_options = null)
    {
        parent::createInitialRights($_application);

        $initialAdminUserOptions = $this->_parseInitialAdminUserOptions($_options);

        $groupController = Tinebase_Group::getInstance();
        $userController = Tinebase_User::getInstance();
        $oldGroupValue = $groupController->modlogActive(false);
        $oldUserValue = $userController->modlogActive(false);

        $initialUserName = $initialAdminUserOptions['adminLoginName'];

        // make sure we have a setup user:
        Tinebase_Model_User::resetConfiguration();
        Tinebase_Model_FullUser::resetConfiguration();
        $setupUser = Setup_Update_Abstract::getSetupFromConfigOrCreateOnTheFly();
        if (! Tinebase_Core::getUser() instanceof Tinebase_Model_User && $setupUser) {
            Tinebase_Core::set(Tinebase_Core::USER, $setupUser);
        }

        // in case we have an ldap setup, we sync the users from the ldap before creating the initial accounts
        Tinebase_User::syncUsers([Tinebase_User::SYNC_WITH_CONFIG_OPTIONS => true]);
        Tinebase_User::createInitialAccounts($initialAdminUserOptions);
        $initialUser = $userController->getUserByProperty('accountLoginName', $initialUserName);
        
        Tinebase_Core::set(Tinebase_Core::USER, $initialUser);

        $groupController->modlogActive($oldGroupValue);
        $userController->modlogActive($oldUserValue);
        
        parent::_initialize($_application, $_options);

        $this->_setLicense($_options);
    }

    /**
     * create inital rights
     *
     * @param Tinebase_Application $application
     * @return void
     */
    public static function createInitialRights(Tinebase_Model_Application $_application)
    {
        // we do nothing to work our way through the jungle here
        // we call parent::createInitialRights at the time we like it in _initialize
    }

    /**
     * returns internal addressbook
     * 
     * @return Tinebase_Model_Container
     */
    protected function _getInternalAddressbook()
    {
        if ($this->_internalAddressbook === NULL) {
            $this->_internalAddressbook = Tinebase_Container::getInstance()->getContainerById(
                Admin_Controller_User::getDefaultInternalAddressbook()
            );
        }
        
        return $this->_internalAddressbook;
    }
    
    /**
     * create group lists
     */
    protected function _initializeGroupLists()
    {
        Tinebase_Core::getCache()->clean();
        Tinebase_Group::getInstance()->resetClassCache();
        Addressbook_Controller_List::getInstance()->doContainerACLChecks(false);
        foreach (Tinebase_Group::getInstance()->getGroups() as $group) {
            $group->members = Tinebase_Group::getInstance()->getGroupMembers($group);
            $group->container_id = $this->_getInternalAddressbook()->getId();
            $group->visibility = Tinebase_Model_Group::VISIBILITY_DISPLAYED;
            Admin_Controller_Group::getInstance()->createOrUpdateList($group);

            Tinebase_Group::getInstance()->updateGroup($group);
        }
    }
    
    /**
     * create favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
            'model'             => 'Addressbook_Model_ContactFilter',
        );
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => Addressbook_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => "All contacts I have read grants for", // _("All contacts I have read grants for")
            'filters'           => array(),
        ))));
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My company", // _("My company")
            'description'       => "All coworkers in my company", // _("All coworkers in my company")
            'filters'           => array(array(
                'field'     => 'container_id',
                'operator'  => 'in',
                'value'     => array(
                    'id'    => $this->_getInternalAddressbook()->getId(),
                    // @todo use placeholder here (as this can change later)?
                    'path'  => '/shared/' . $this->_getInternalAddressbook()->getId(),
                )
            )),
        ))));
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My contacts", // _("My contacts")
            'description'       => "All contacts in my Addressbooks", // _("All contacts in my Addressbooks")
            'filters'           => array(array(
                'field'     => 'container_id',
                'operator'  => 'in',
                'value'     => array(
                    'id'    => 'personal',
                    'path'  => '/personal/' . Tinebase_Model_User::CURRENTACCOUNT,
                )
            )),
        ))));
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Last modified by me", // _("Last modified by me")
            'description'       => "All contacts that I have last modified", // _("All contacts that I have last modified")
            'filters'           => array(array(
                'field'     => 'last_modified_by',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_User::CURRENTACCOUNT,
            )),
        ))));
    }

    /**
     * init grants of internal addressbook
     *
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _initializeInternalAddressbook()
    {
        $internalAddressbook = $this->_getInternalAddressbook();
        self::setGrantsForInternalAddressbook($internalAddressbook);
    }

    /**
     * give anyone read rights to the internal addressbook
     * give Administrators group read/edit/admin rights to the internal addressbook
     *
     * @param $internalAddressbook
     */
    public static function setGrantsForInternalAddressbook($internalAddressbook)
    {
        $adminRole = Tinebase_Acl_Roles::getInstance()->getDefaultAdminRole();

        Tinebase_Container::getInstance()->addGrants($internalAddressbook,
            Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE, '0', array(
            Tinebase_Model_Grants::GRANT_READ
        ), TRUE);
        Tinebase_Container::getInstance()->addGrants($internalAddressbook,
            Tinebase_Acl_Rights::ACCOUNT_TYPE_ROLE, $adminRole->getId(), array(
            Tinebase_Model_Grants::GRANT_READ,
            Tinebase_Model_Grants::GRANT_EDIT,
            Tinebase_Model_Grants::GRANT_ADMIN
        ), TRUE);
    }
    
    /**
     * Extract default group name settings from {@param $_options}
     * 
     * @todo the initial admin user options get set for the sql backend only. They should be set independed of the backend selected
     * @param array $_options
     * @return array
     */
    protected function _parseInitialAdminUserOptions($_options)
    {
        $result = array();
        $accounts = isset($_options['authenticationData']['authentication'][Tinebase_User::SQL]) ? $_options['authenticationData']['authentication'][Tinebase_User::SQL] : array();
        $keys = array('adminLoginName', 'adminPassword', 'adminEmailAddress');
        foreach ($keys as $key) {
            if (isset($_options[$key])) {
                $result[$key] = $_options[$key];
            } elseif (isset($accounts[$key])) {
                $result[$key] = $accounts[$key];
            }
        }
        
        if (! isset($result['adminLoginName']) || ! isset($result['adminPassword'])) {
            $loginConfig = Tinebase_Config::getInstance()->get('login');
            if ($loginConfig) {
                $result = array(
                    'adminLoginName' => $loginConfig->username,
                    'adminPassword' => $loginConfig->password,
                );
            } else {
                throw new Setup_Exception('Inital admin username and password are required');
            }
        }
        
        return $result;
    }
    
    /**
     * init config settings
     * - add internal addressbook config setting
     */
    protected function _initializeConfig()
    {
        self::setDefaultInternalAddressbook($this->_getInternalAddressbook());
    }

    protected function _initializeImportExportDefinitionContainer()
    {
        Tinebase_ImportExportDefinition::getDefaultImportExportContainer();
    }

    protected function _initializeContactProperties()
    {
        static::createInitialContactProperties();
    }

    public static function createInitialContactProperties()
    {
        if (Tinebase_Core::isReplica()) {
            return;
        }

        $ctrl = Addressbook_Controller_ContactProperties_Definition::getInstance();
        $oldAcl = $ctrl->doContainerACLChecks(false);
        $raii = new Tinebase_RAII(function() use($oldAcl, $ctrl) {
            $ctrl->doContainerACLChecks($oldAcl);
        });

        $ctrl->create(new Addressbook_Model_ContactProperties_Definition([
            Addressbook_Model_ContactProperties_Definition::FLD_IS_SYSTEM => true,
            Addressbook_Model_ContactProperties_Definition::FLD_NAME => 'adr_one',
            Addressbook_Model_ContactProperties_Definition::FLD_LABEL => 'Company Address', // _('Company Address')
            Addressbook_Model_ContactProperties_Definition::FLD_SORTING => 1,
            Addressbook_Model_ContactProperties_Definition::FLD_MODEL => Addressbook_Model_ContactProperties_Address::class,
            Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE => Addressbook_Model_ContactProperties_Definition::LINK_TYPE_RECORD,
            Addressbook_Model_ContactProperties_Definition::FLD_VCARD_MAP => ['TYPE' => 'WORK'],
            Addressbook_Model_ContactProperties_Definition::FLD_ACTIVE_SYNC_MAP => [
                'businessAddressCity' => 'adr_one.locality',
                'businessAddressCountry' => 'adr_one.countryname',
                'businessAddressPostalCode' => 'adr_one.postalcode',
                'businessAddressState' => 'adr_one.region',
                'businessAddressStreet' => 'adr_one.street',
            ],
        ]));

        $adbAppId = Tinebase_Application::getInstance()->getApplicationByName(Addressbook_Config::APP_NAME)->getId();
        $cfCfg = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($adbAppId, 'adr_one',
            Addressbook_Model_Contact::class, true, true);
        $cfCfg->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD]
            [Tinebase_ModelConfiguration_Const::CONFIG][Tinebase_ModelConfiguration_Const::JSON_FACADE] = 'adr_one_';
        $cfCfg->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_HOOK] = [
            [Addressbook_Controller_Contact::class, 'modelConfigHook'],
        ];
        Tinebase_CustomField::getInstance()->updateCustomField($cfCfg);

        $ctrl->create(new Addressbook_Model_ContactProperties_Definition([
            Addressbook_Model_ContactProperties_Definition::FLD_IS_SYSTEM => true,
            Addressbook_Model_ContactProperties_Definition::FLD_NAME => 'adr_two',
            Addressbook_Model_ContactProperties_Definition::FLD_LABEL => 'Private Address', // _('Private Address')
            Addressbook_Model_ContactProperties_Definition::FLD_SORTING => 2,
            Addressbook_Model_ContactProperties_Definition::FLD_MODEL => Addressbook_Model_ContactProperties_Address::class,
            Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE => Addressbook_Model_ContactProperties_Definition::LINK_TYPE_RECORD,
            Addressbook_Model_ContactProperties_Definition::FLD_GRANT_MATRIX => [Addressbook_Model_ContactGrants::GRANT_PRIVATE_DATA],
            Addressbook_Model_ContactProperties_Definition::FLD_VCARD_MAP => ['TYPE' => 'HOME'],
            Addressbook_Model_ContactProperties_Definition::FLD_ACTIVE_SYNC_MAP => [
                'homeAddressCity' => 'adr_two.locality',
                'homeAddressCountry' => 'adr_two.countryname',
                'homeAddressPostalCode' => 'adr_two.postalcode',
                'homeAddressState' => 'adr_two.region',
                'homeAddressStreet' => 'adr_two.street',
            ],
        ]));

        $cfCfg = Tinebase_CustomField::getInstance()->getCustomFieldByNameAndApplication($adbAppId, 'adr_two',
            Addressbook_Model_Contact::class, true, true);
        $cfCfg->xprops('definition')[Tinebase_Model_CustomField_Config::DEF_FIELD]
            [Tinebase_ModelConfiguration_Const::CONFIG][Tinebase_ModelConfiguration_Const::JSON_FACADE] = 'adr_two_';
        Tinebase_CustomField::getInstance()->updateCustomField($cfCfg);

        $ctrl->create(new Addressbook_Model_ContactProperties_Definition([
            Addressbook_Model_ContactProperties_Definition::FLD_IS_SYSTEM => true,
            Addressbook_Model_ContactProperties_Definition::FLD_NAME => 'email',
            Addressbook_Model_ContactProperties_Definition::FLD_LABEL => 'E-Mail', // _('E-Mail')
            Addressbook_Model_ContactProperties_Definition::FLD_GROUPING => 'Company Communication', // _('Company Communication')
            Addressbook_Model_ContactProperties_Definition::FLD_SORTING => 10,
            Addressbook_Model_ContactProperties_Definition::FLD_MODEL => Addressbook_Model_ContactProperties_Email::class,
            Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE => Addressbook_Model_ContactProperties_Definition::LINK_TYPE_INLINE,
            Addressbook_Model_ContactProperties_Definition::FLD_VCARD_MAP => ['TYPE' => 'WORK'],
            Addressbook_Model_ContactProperties_Definition::FLD_ACTIVE_SYNC_MAP => [
                'email1Address' => 'email',
            ],
        ]));

        $ctrl->create(new Addressbook_Model_ContactProperties_Definition([
            Addressbook_Model_ContactProperties_Definition::FLD_IS_SYSTEM => true,
            Addressbook_Model_ContactProperties_Definition::FLD_NAME => 'email_home',
            Addressbook_Model_ContactProperties_Definition::FLD_LABEL => 'E-Mail (private)', // _('E-Mail (private)')
            Addressbook_Model_ContactProperties_Definition::FLD_GROUPING => 'Private Communication', // _('Private Communication')
            Addressbook_Model_ContactProperties_Definition::FLD_SORTING => 15,
            Addressbook_Model_ContactProperties_Definition::FLD_MODEL => Addressbook_Model_ContactProperties_Email::class,
            Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE => Addressbook_Model_ContactProperties_Definition::LINK_TYPE_INLINE,
            Addressbook_Model_ContactProperties_Definition::FLD_GRANT_MATRIX => [Addressbook_Model_ContactGrants::GRANT_PRIVATE_DATA],
            Addressbook_Model_ContactProperties_Definition::FLD_VCARD_MAP => ['TYPE' => 'HOME'],
            Addressbook_Model_ContactProperties_Definition::FLD_ACTIVE_SYNC_MAP => [
                'email2Address' => 'email_home',
            ],
        ]));

        $ctrl->create(new Addressbook_Model_ContactProperties_Definition([
            Addressbook_Model_ContactProperties_Definition::FLD_IS_SYSTEM => true,
            Addressbook_Model_ContactProperties_Definition::FLD_NAME => 'tel_work',
            Addressbook_Model_ContactProperties_Definition::FLD_GROUPING => 'Company Communication', // _('Company Communication')
            Addressbook_Model_ContactProperties_Definition::FLD_SORTING => 30,
            Addressbook_Model_ContactProperties_Definition::FLD_LABEL => 'Phone', // _('Phone')
            Addressbook_Model_ContactProperties_Definition::FLD_MODEL => Addressbook_Model_ContactProperties_Phone::class,
            Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE => Addressbook_Model_ContactProperties_Definition::LINK_TYPE_INLINE,
            Addressbook_Model_ContactProperties_Definition::FLD_VCARD_MAP => ['TYPE' => 'WORK'],
        ]));

        $ctrl->create(new Addressbook_Model_ContactProperties_Definition([
            Addressbook_Model_ContactProperties_Definition::FLD_IS_SYSTEM => true,
            Addressbook_Model_ContactProperties_Definition::FLD_NAME => 'tel_home',
            Addressbook_Model_ContactProperties_Definition::FLD_LABEL => 'Phone (private)', // _('Phone (private)')
            Addressbook_Model_ContactProperties_Definition::FLD_GROUPING => 'Private Communication', // _('Private Communication')
            Addressbook_Model_ContactProperties_Definition::FLD_SORTING => 35,
            Addressbook_Model_ContactProperties_Definition::FLD_MODEL => Addressbook_Model_ContactProperties_Phone::class,
            Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE => Addressbook_Model_ContactProperties_Definition::LINK_TYPE_INLINE,
            Addressbook_Model_ContactProperties_Definition::FLD_GRANT_MATRIX => [Addressbook_Model_ContactGrants::GRANT_PRIVATE_DATA],
            Addressbook_Model_ContactProperties_Definition::FLD_VCARD_MAP => ['TYPE' => 'HOME'],
        ]));

        $ctrl->create(new Addressbook_Model_ContactProperties_Definition([
            Addressbook_Model_ContactProperties_Definition::FLD_IS_SYSTEM => true,
            Addressbook_Model_ContactProperties_Definition::FLD_NAME => 'tel_cell',
            Addressbook_Model_ContactProperties_Definition::FLD_LABEL => 'Mobile', // _('Mobile')
            Addressbook_Model_ContactProperties_Definition::FLD_GROUPING => 'Company Communication', // _('Company Communication')
            Addressbook_Model_ContactProperties_Definition::FLD_SORTING => 20,
            Addressbook_Model_ContactProperties_Definition::FLD_MODEL => Addressbook_Model_ContactProperties_Phone::class,
            Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE => Addressbook_Model_ContactProperties_Definition::LINK_TYPE_INLINE,
            Addressbook_Model_ContactProperties_Definition::FLD_VCARD_MAP => ['TYPE' => ['CELL', 'WORK']],
        ]));

        $ctrl->create(new Addressbook_Model_ContactProperties_Definition([
            Addressbook_Model_ContactProperties_Definition::FLD_IS_SYSTEM => true,
            Addressbook_Model_ContactProperties_Definition::FLD_NAME => 'tel_cell_private',
            Addressbook_Model_ContactProperties_Definition::FLD_LABEL => 'Mobile (private)', // _('Mobile (private)')
            Addressbook_Model_ContactProperties_Definition::FLD_GROUPING => 'Private Communication', // _('Private Communication')
            Addressbook_Model_ContactProperties_Definition::FLD_SORTING => 25,
            Addressbook_Model_ContactProperties_Definition::FLD_MODEL => Addressbook_Model_ContactProperties_Phone::class,
            Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE => Addressbook_Model_ContactProperties_Definition::LINK_TYPE_INLINE,
            Addressbook_Model_ContactProperties_Definition::FLD_GRANT_MATRIX => [Addressbook_Model_ContactGrants::GRANT_PRIVATE_DATA],
            Addressbook_Model_ContactProperties_Definition::FLD_VCARD_MAP => ['TYPE' => ['CELL', 'HOME']],
        ]));

        $ctrl->create(new Addressbook_Model_ContactProperties_Definition([
            Addressbook_Model_ContactProperties_Definition::FLD_IS_SYSTEM => true,
            Addressbook_Model_ContactProperties_Definition::FLD_NAME => 'tel_fax',
            Addressbook_Model_ContactProperties_Definition::FLD_LABEL => 'Fax', // _('Fax')
            Addressbook_Model_ContactProperties_Definition::FLD_GROUPING => 'Company Communication', // _('Company Communication')
            Addressbook_Model_ContactProperties_Definition::FLD_SORTING => 40,
            Addressbook_Model_ContactProperties_Definition::FLD_MODEL => Addressbook_Model_ContactProperties_Phone::class,
            Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE => Addressbook_Model_ContactProperties_Definition::LINK_TYPE_INLINE,
            Addressbook_Model_ContactProperties_Definition::FLD_VCARD_MAP => ['TYPE' => ['FAX', 'WORK']],
        ]));

        $ctrl->create(new Addressbook_Model_ContactProperties_Definition([
            Addressbook_Model_ContactProperties_Definition::FLD_IS_SYSTEM => true,
            Addressbook_Model_ContactProperties_Definition::FLD_NAME => 'tel_fax_home',
            Addressbook_Model_ContactProperties_Definition::FLD_LABEL => 'Fax (private)', // _('Fax (private)')
            Addressbook_Model_ContactProperties_Definition::FLD_GROUPING => 'Private Communication', // _('Private Communication')
            Addressbook_Model_ContactProperties_Definition::FLD_SORTING => 45,
            Addressbook_Model_ContactProperties_Definition::FLD_MODEL => Addressbook_Model_ContactProperties_Phone::class,
            Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE => Addressbook_Model_ContactProperties_Definition::LINK_TYPE_INLINE,
            Addressbook_Model_ContactProperties_Definition::FLD_GRANT_MATRIX => [Addressbook_Model_ContactGrants::GRANT_PRIVATE_DATA],
            Addressbook_Model_ContactProperties_Definition::FLD_VCARD_MAP => ['TYPE' => ['FAX', 'HOME']],
        ]));
        
        $ctrl->create(new Addressbook_Model_ContactProperties_Definition([
            Addressbook_Model_ContactProperties_Definition::FLD_IS_SYSTEM => true,
            Addressbook_Model_ContactProperties_Definition::FLD_NAME => 'url',
            Addressbook_Model_ContactProperties_Definition::FLD_LABEL => 'Web', // _('Web')
            Addressbook_Model_ContactProperties_Definition::FLD_GROUPING => 'Company Communication', // _('Company Communication')
            Addressbook_Model_ContactProperties_Definition::FLD_SORTING => 50,
            Addressbook_Model_ContactProperties_Definition::FLD_MODEL => Addressbook_Model_ContactProperties_Url::class,
            Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE => Addressbook_Model_ContactProperties_Definition::LINK_TYPE_INLINE,
            Addressbook_Model_ContactProperties_Definition::FLD_VCARD_MAP => ['TYPE' => 'WORK'],
        ]));

        $ctrl->create(new Addressbook_Model_ContactProperties_Definition([
            Addressbook_Model_ContactProperties_Definition::FLD_IS_SYSTEM => true,
            Addressbook_Model_ContactProperties_Definition::FLD_NAME => 'url_home',
            Addressbook_Model_ContactProperties_Definition::FLD_LABEL => 'URL (private)', // _('URL (private)')
            Addressbook_Model_ContactProperties_Definition::FLD_GROUPING => 'Private Communication', // _('Private Communication')
            Addressbook_Model_ContactProperties_Definition::FLD_SORTING => 55,
            Addressbook_Model_ContactProperties_Definition::FLD_MODEL => Addressbook_Model_ContactProperties_Url::class,
            Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE => Addressbook_Model_ContactProperties_Definition::LINK_TYPE_INLINE,
            Addressbook_Model_ContactProperties_Definition::FLD_GRANT_MATRIX => [Addressbook_Model_ContactGrants::GRANT_PRIVATE_DATA],
            Addressbook_Model_ContactProperties_Definition::FLD_VCARD_MAP => ['TYPE' => 'HOME'],
        ]));

        $ctrl->create(new Addressbook_Model_ContactProperties_Definition([
            Addressbook_Model_ContactProperties_Definition::FLD_IS_SYSTEM => true,
            Addressbook_Model_ContactProperties_Definition::FLD_NAME => 'matrix_id',
            Addressbook_Model_ContactProperties_Definition::FLD_LABEL => 'Matrix-ID', // _('Matrix-ID')
            Addressbook_Model_ContactProperties_Definition::FLD_GROUPING => 'Contact Information', // _('Contact Information')
            Addressbook_Model_ContactProperties_Definition::FLD_SORTING => 29,
            Addressbook_Model_ContactProperties_Definition::FLD_MODEL => Addressbook_Model_ContactProperties_InstantMessenger::class,
            Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE => Addressbook_Model_ContactProperties_Definition::LINK_TYPE_INLINE,
        ]));

        $ctrl->create(new Addressbook_Model_ContactProperties_Definition([
            Addressbook_Model_ContactProperties_Definition::FLD_IS_SYSTEM => true,
            Addressbook_Model_ContactProperties_Definition::FLD_NAME => 'tel_assistent',
            Addressbook_Model_ContactProperties_Definition::FLD_LABEL => 'Assistant', // _('Assistant')
            Addressbook_Model_ContactProperties_Definition::FLD_GROUPING => 'Contact Information', // _('Contact Information')
            Addressbook_Model_ContactProperties_Definition::FLD_SORTING => 60,
            Addressbook_Model_ContactProperties_Definition::FLD_MODEL => Addressbook_Model_ContactProperties_Phone::class,
            Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE => Addressbook_Model_ContactProperties_Definition::LINK_TYPE_INLINE,
            Addressbook_Model_ContactProperties_Definition::FLD_VCARD_MAP => ['TYPE' => ['X-EVOLUTION-ASSISTANT']],
        ]));
        
        $ctrl->create(new Addressbook_Model_ContactProperties_Definition([
            Addressbook_Model_ContactProperties_Definition::FLD_IS_SYSTEM => true,
            Addressbook_Model_ContactProperties_Definition::FLD_NAME => 'tel_car',
            Addressbook_Model_ContactProperties_Definition::FLD_LABEL => 'Car', // _('Car')
            Addressbook_Model_ContactProperties_Definition::FLD_GROUPING => 'Contact Information', // _('Contact Information')
            Addressbook_Model_ContactProperties_Definition::FLD_SORTING => 65,
            Addressbook_Model_ContactProperties_Definition::FLD_MODEL => Addressbook_Model_ContactProperties_Phone::class,
            Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE => Addressbook_Model_ContactProperties_Definition::LINK_TYPE_INLINE,
            Addressbook_Model_ContactProperties_Definition::FLD_VCARD_MAP => ['TYPE' => ['CAR']],
        ]));
        
        $ctrl->create(new Addressbook_Model_ContactProperties_Definition([
            Addressbook_Model_ContactProperties_Definition::FLD_IS_SYSTEM => true,
            Addressbook_Model_ContactProperties_Definition::FLD_NAME => 'tel_pager',
            Addressbook_Model_ContactProperties_Definition::FLD_LABEL => 'Pager', // _('Pager')
            Addressbook_Model_ContactProperties_Definition::FLD_GROUPING => 'Contact Information', // _('Contact Information')
            Addressbook_Model_ContactProperties_Definition::FLD_SORTING => 70,
            Addressbook_Model_ContactProperties_Definition::FLD_MODEL => Addressbook_Model_ContactProperties_Phone::class,
            Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE => Addressbook_Model_ContactProperties_Definition::LINK_TYPE_INLINE,
            Addressbook_Model_ContactProperties_Definition::FLD_VCARD_MAP => ['TYPE' => ['PAGER']],
        ]));
        
        $ctrl->create(new Addressbook_Model_ContactProperties_Definition([
            Addressbook_Model_ContactProperties_Definition::FLD_IS_SYSTEM => true,
            Addressbook_Model_ContactProperties_Definition::FLD_NAME => 'tel_other',
            Addressbook_Model_ContactProperties_Definition::FLD_LABEL => 'Other', // _('Other')
            Addressbook_Model_ContactProperties_Definition::FLD_GROUPING => 'Contact Information', // _('Contact Information')
            Addressbook_Model_ContactProperties_Definition::FLD_SORTING => 75,
            Addressbook_Model_ContactProperties_Definition::FLD_MODEL => Addressbook_Model_ContactProperties_Phone::class,
            Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE => Addressbook_Model_ContactProperties_Definition::LINK_TYPE_INLINE,
            Addressbook_Model_ContactProperties_Definition::FLD_VCARD_MAP => ['TYPE' => ['X-EVOLUTION-CALLBACK']],
        ]));
        
        $ctrl->create(new Addressbook_Model_ContactProperties_Definition([
            Addressbook_Model_ContactProperties_Definition::FLD_IS_SYSTEM => true,
            Addressbook_Model_ContactProperties_Definition::FLD_NAME => 'tel_prefer',
            Addressbook_Model_ContactProperties_Definition::FLD_LABEL => 'Prefer', // _('Prefer')
            Addressbook_Model_ContactProperties_Definition::FLD_GROUPING => 'Contact Information', // _('Contact Information')
            Addressbook_Model_ContactProperties_Definition::FLD_SORTING => 80,
            Addressbook_Model_ContactProperties_Definition::FLD_MODEL => Addressbook_Model_ContactProperties_Phone::class,
            Addressbook_Model_ContactProperties_Definition::FLD_LINK_TYPE => Addressbook_Model_ContactProperties_Definition::LINK_TYPE_INLINE,
            Addressbook_Model_ContactProperties_Definition::FLD_VCARD_MAP => ['TYPE' => ['PREF']],
        ]));

        unset($raii);
    }
    
    /**
     * set default internal addressbook
     * 
     * @param Tinebase_Model_Container $internalAddressbook
     * @return Tinebase_Model_Container
     *
     * @todo translate 'Internal Contacts'
     */
    public static function setDefaultInternalAddressbook($internalAddressbook = NULL)
    {
        if ($internalAddressbook === NULL) {
            try {
                $internalAddressbook = Tinebase_Container::getInstance()->getContainerByName(
                    Addressbook_Model_Contact::class,
                    'Internal Contacts',
                    Tinebase_Model_Container::TYPE_SHARED);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // create new internal adb
                $internalAddressbook = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
                    'name'              =>'Internal Contacts',
                    'type'              => Tinebase_Model_Container::TYPE_SHARED,
                    'backend'           => 'Sql',
                    'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),
                    'model'             => 'Addressbook_Model_Contact'
                )), null, true);
                self::setGrantsForInternalAddressbook($internalAddressbook);
            }
        }
        
        Admin_Config::getInstance()->set(
            Tinebase_Config::APPDEFAULTS,
            array(
                Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK => $internalAddressbook->getId()
            )
        );
        
        return $internalAddressbook;
    }

    /**
     * @param array $_options
     */
    protected function _setLicense($_options)
    {
        if (isset($_options['license']) && ! empty($_options['license'])) {
            if (file_exists($_options['license'])) {
                Tinebase_License::getInstance()->storeLicense(file_get_contents($_options['license']));
            } else {
                Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not find license file: '
                    . $_options['license']);
            }
        }
    }
}

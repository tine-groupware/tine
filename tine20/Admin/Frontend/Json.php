<?php
/**
 * Tine 2.0
 *
 * This class handles all Json requests for the admin application
 *
 * @package     Admin
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        try to split this into smaller parts (record proxy should support 'nested' json frontends first)
 * @todo        use functions from Tinebase_Frontend_Json_Abstract
 */
class Admin_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * the application name
     *
     * @var string
     */
    protected $_applicationName = 'Admin';
    
    /**
     * @var bool
     */
    protected $_manageSAM = false;
    
    /**
     * @var ?bool
     */
    protected $_hasMasterSieveAccess = null;

    /**
     * the models handled by this frontend
     * @var array
     */
    protected $_configuredModels = [
        Admin_Model_SchedulerTask::MODEL_NAME_PART,
        Admin_Model_SchedulerTask_Import::MODEL_NAME_PART,
    ];

    /**
     * constructs Admin_Frontend_Json
     */
    public function __construct()
    {
        if (isset(Tinebase_Core::getConfig()->samba)) {
            $this->_manageSAM = Tinebase_Core::getConfig()->samba->get('manageSAM', false);
        }
    }

    protected function _getHasMasterSieveAccess(): bool
    {
        if ($this->_hasMasterSieveAccess === null) {
            try {
                $this->_hasMasterSieveAccess = Tinebase_EmailUser::backendSupportsMasterPassword();
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
                $this->_hasMasterSieveAccess = false;
            }
        }
        return $this->_hasMasterSieveAccess;
    }

    /**
     * Returns registry data of admin.
     * @see Tinebase_Application_Json_Abstract
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        $appConfigDefaults = Admin_Controller::getInstance()->getConfigSettings();

        $registryData = array(
            'manageSAM'                     => $this->_manageSAM,
                'masterSieveAccess'             => $this->_getHasMasterSieveAccess(),
            'defaultPrimaryGroup'           => Tinebase_Group::getInstance()->getDefaultGroup()->toArray(),
            'defaultInternalAddressbook'    => (
                    isset($appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK])
                    && $appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK] !== NULL)
                ? Tinebase_Container::getInstance()->get($appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK])->toArray() 
                : NULL,
        );

        return $registryData;
    }
    
    /******************************* Access Log *******************************/
    
    /**
     * delete access log entries
     *
     * @param array $ids list of logIds to delete
     * @return array with success flag
     */
    public function deleteAccessLogs($ids)
    {
        return $this->_delete($ids, Admin_Controller_AccessLog::getInstance());
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param array $filter 
     * @param array $paging 
     * @return array
     */
    public function searchAccessLogs($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Admin_Controller_AccessLog::getInstance(), 'Tinebase_Model_AccessLogFilter');
        
        return $result;
    }

    /****************************** Applications ******************************/
    
    /**
     * get application
     *
     * @param   int $applicationId application id to get
     * @return  array with application data
     * 
     */
    public function getApplication($applicationId)
    {
        $application = Admin_Controller_Application::getInstance()->get($applicationId);
        
        return $application->toArray();
    }
    
    /**
     * get list of applications
     *
     * @param string $filter
     * @param string $sort
     * @param string $dir
     * @param int $start
     * @param int $limit
     * @return array with results array & totalcount (int)
     * 
     * @todo switch to new api with only filter and paging params
     */
    public function getApplications($filter, $sort, $dir, $start, $limit)
    {
        if (empty($filter)) {
            $filter = NULL;
        }
        
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $applicationSet = Admin_Controller_Application::getInstance()->search($filter, $sort, $dir, $start, $limit);

        $result['results']    = $applicationSet->toArray();
        if ($start == 0 && count($result['results']) < $limit) {
            $result['totalcount'] = count($result['results']);
        } else {
            $result['totalcount'] = Admin_Controller_Application::getInstance()->getTotalApplicationCount($filter);
        }
        
        return $result;
    }

    /**
     * set application state
     *
     * @param   array  $applicationIds  array of application ids
     * @param   string $state           state to set
     * @return  array with success flag
     */
    public function setApplicationState($applicationIds, $state)
    {
        $result = Admin_Controller_Application::getInstance()->setApplicationState($applicationIds, $state);

        return array(
            'success' => $result
        );
    }
            
    /********************************** Users *********************************/
    
    /**
     * returns a fullUser
     *
     * @param string $id
     * @return array
     */
    public function getUser($id)
    {
        if (!empty($id)) {
            $user = Admin_Controller_User::getInstance()->get($id);
            $userArray = $this->_recordToJson($user);
            
            // don't send some infos to the client: unset email uid+gid
            if ((isset($userArray['emailUser']) || array_key_exists('emailUser', $userArray))) {
                $unsetFields = array('emailUID', 'emailGID');
                foreach ($unsetFields as $field) {
                    unset($userArray['emailUser'][$field]);
                }

                if(isset($userArray['imapUser']['emailMailSize']) && ($userArray['imapUser']['emailMailQuota'] !== null)) {
                    $userArray['emailUser']['emailMailSize'] = $userArray['imapUser']['emailMailSize'];
                    $userArray['emailUser']['emailMailQuota'] = $userArray['imapUser']['emailMailQuota'];
                }
            }
            
            // add primary group to account for the group selection combo box
            $group = Tinebase_Group::getInstance()->getGroupById($user->accountPrimaryGroup);
            
            $userGroups = Tinebase_Group::getInstance()->getMultiple(Tinebase_Group::getInstance()->getGroupMemberships($user->accountId))->toArray();
            
            try {
                $roleMemberships = Tinebase_Acl_Roles::getInstance()->getRoleMemberships($user->accountId);
                $userRoles = Tinebase_Acl_Roles::getInstance()->getMultiple($roleMemberships)->toArray();
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . 
                    ' Failed to fetch role memberships for user ' . $user->accountFullName . ': ' . $tenf->getMessage()
                );
                $userRoles = array();
            }
            
            
        } else {
            $userArray = array('accountStatus' => 'enabled', 'visibility' => 'displayed');
            
            // get default primary group for the group selection combo box
            $group = Tinebase_Group::getInstance()->getDefaultGroup();
            
            // no user groups by default
            $userGroups = array();
            
            // no user roles by default
            $userRoles = array();
        }
        
        // encode the account array
        $userArray['accountPrimaryGroup'] = $group->toArray();
        
        // encode the groups array
        $userArray['groups'] = array(
            'results'         => $userGroups,
            'totalcount'     => count($userGroups)
        );
        
        // encode the roles array
        $userArray['accountRoles'] = array(
            'results'         => $userRoles,
            'totalcount'     => count($userRoles)
        );
        
        return $userArray;
    }
    
    /**
     * get list of accounts
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return array with results array & totalcount (int)
     * 
     * @todo switch to new api with only filter and paging params
     */
    public function getUsers($filter, $sort, $dir, $start, $limit)
    {
        $accounts = Admin_Controller_User::getInstance()->searchFullUsers($filter, $sort, $dir, $start, $limit);
        $results = array();
        foreach ($this->_multipleRecordsToJson($accounts) as $val) {
            $val['filesystemSize'] = null;
            $val['filesystemRevisionSize'] = null;
            $results[$val['accountId']] = $val;
        }

        if (Tinebase_Application::getInstance()->isInstalled('Filemanager')) {
            $this->_appendPersonalFolderSize($accounts->getId(),$results);
        }

        return array(
            'results'     => array_values($results),
            'totalcount'  => Admin_Controller_User::getInstance()->searchCount($filter)
        );
    }

    protected function _appendPersonalFolderSize(array $accountIds, array &$results)
    {
        try {
            /** @var Tinebase_Model_Tree_Node $node */
            foreach (Tinebase_FileSystem::getInstance()->searchNodes(new Tinebase_Model_Tree_Node_Filter(array(
                array('field' => 'path', 'operator' => 'equals', 'value' => '/Filemanager/folders/personal'),
                array('field' => 'name', 'operator' => 'in', 'value' => $accountIds)
            ), '', array('ignoreAcl' => true))) as $node) {
                if (isset($results[$node->name])) {
                    $results[$node->name]['filesystemSize'] = $node->size;
                    $results[$node->name]['filesystemRevisionSize'] = $node->revision_size;
                }
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $tenf->getMessage());
        }
    }

    /**
     * search for users/accounts
     * 
     * @param array $filter
     * @param array $paging
     * @return array with results array & totalcount (int)
     */
    public function searchUsers($filter, $paging)
    {
        $result = $this->getUsers(
            $filter[0]['value'] ?? null,
            $paging['sort'] ?? 'accountDisplayName',
            $paging['dir'] ?? 'ASC',
            $paging['start'] ?? 0,
            $paging['limit'] ?? null
        );
        $result['filter'] = $filter[0] ?? [];
        
        return $result;
    }
    
    /**
     * Search for groups matching given arguments
     *
     * @param  array $_filter
     * @param  array $_paging
     * @return array
     * 
     * @todo replace this by Admin.searchGroups / getGroups (without acl check)? or add getGroupCount to Tinebase_Group
     */
    public function searchGroups($filter, $paging)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        // old fn style yet
        $sort = (isset($paging['sort']))    ? $paging['sort']   : 'name';
        $dir  = (isset($paging['dir']))     ? $paging['dir']    : 'ASC';
        $groups = Tinebase_Group::getInstance()->getGroups($filter[0]['value'], $sort, $dir, isset($paging['start']) ?
            $paging['start'] : 0, isset($paging['limit']) ? $paging['limit'] : null);

        $result['results'] = $groups->toArray();
        $result['totalcount'] = Admin_Controller_Group::getInstance()->searchCount($filter[0]['value']);
        
        return $result;
    }
    
    /**
     * save user
     *
     * @param  array $recordData data of Tinebase_Model_FullUser
     * @return array  
     */
    public function saveUser($recordData)
    {
        parent::_setRequestContext(Admin_Controller_User::getInstance());
        
        $password = (isset($recordData['accountPassword'])) ? $recordData['accountPassword'] : '';
        if (! empty($password)) {
            Tinebase_Core::getLogger()->addReplacement($password);
        }

        // dehydrate primary group id + groups
        if (isset($recordData['accountPrimaryGroup'])
            && is_array($recordData['accountPrimaryGroup'])
            && isset($recordData['accountPrimaryGroup']['id'])
        ) {
            $recordData['accountPrimaryGroup'] = $recordData['accountPrimaryGroup']['id'];
        }

        if (isset($recordData['groups']['results'])) {
            $recordData['groups'] = array_map(function($group) {
                return $group['id'];
            }, $recordData['groups']['results']);
        }

        $account = new Tinebase_Model_FullUser();
        try {
            $account->setFromJsonInUsersTimezone($recordData);
            if (isset($recordData['sambaSAM'])) {
                $account->sambaSAM = new Tinebase_Model_SAMUser($recordData['sambaSAM']);
            }
            
            if (isset($recordData['emailUser']) && ! empty($recordData['accountEmailAddress'])) {
                $account->emailUser = new Tinebase_Model_EmailUser($recordData['emailUser']);
                $account->imapUser  = new Tinebase_Model_EmailUser($recordData['emailUser']);
                $account->smtpUser  = new Tinebase_Model_EmailUser($recordData['emailUser']);
            }
        } catch (Tinebase_Exception_Record_Validation $e) {
            // invalid data in some fields sent from client
            $result = array(
                'errors'            => $account->getValidationErrors(),
                'errorMessage'      => 'invalid data for some fields',
                'status'            => 'failure'
            );
            
            return $result;
        }
        
        // this needs long 3execution time because cache invalidation may take long
        // @todo remove this when "0007266: make groups / group memberships cache cleaning more efficient" is resolved 
        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime(300); // 5 minutes
        
        if ($account->getId() == NULL) {
            $account = Admin_Controller_User::getInstance()->create($account, $password, $password);
        } else {
            $account = Admin_Controller_User::getInstance()->update($account, $password, $password);
        }
        
        $result = $this->_recordToJson($account);
        
        // add primary group to account for the group selection combo box
        $group = Tinebase_Group::getInstance()->getGroupById($account->accountPrimaryGroup);
        
        // add user groups
        $userGroups = Tinebase_Group::getInstance()->getMultiple(Tinebase_Group::getInstance()->getGroupMemberships($account->accountId))->toArray();
        
        // add user roles
        $userRoles = Tinebase_Acl_Roles::getInstance()->getMultiple(Tinebase_Acl_Roles::getInstance()->getRoleMemberships($account->accountId))->toArray();
        
        // encode the account array
        $result['accountPrimaryGroup'] = $group->toArray();
        
        // encode the groups array
        $result['groups'] = array(
            'results'         => $userGroups,
            'totalcount'     => count($userGroups)
        );
        
        // encode the roles array
        $result['accountRoles'] = array(
            'results'         => $userRoles,
            'totalcount'     => count($userRoles)
        );
        
        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);
        
        return $result;
    }
    
    /**
     * delete users
     *
     * @param   array $ids array of account ids
     * @return  array with success flag
     */
    public function deleteUsers($ids)
    {
        parent::_setRequestContext(Admin_Controller_User::getInstance());
        Admin_Controller_User::getInstance()->delete($ids);
        
        $result = array(
            'success' => TRUE
        );
        return $result;
    }

    /**
     * set account state
     *
     * @param   array  $accountIds  array of account ids
     * @param   string $state      state to set
     * @return  array with success flag
     */
    public function setAccountState($accountIds, $status)
    {
        $controller = Admin_Controller_User::getInstance();
        foreach ($accountIds as $accountId) {
            $controller->setAccountStatus($accountId, $status);
        }

        $result = array(
            'success' => TRUE
        );
        
        return $result;
    }
    
    /**
     * reset password for given account
     *
     * @param array|string $account Tinebase_Model_FullUser data or account id
     * @param string $password the new password
     * @param bool $mustChange
     * @return array
     */
    public function resetPassword($account, $password, $mustChange)
    {
        if (is_array($account)) {
            if (isset($account['accountPrimaryGroup']) && is_array($account['accountPrimaryGroup']) && isset($account['accountPrimaryGroup']['id'])) {
                $account['accountPrimaryGroup'] = $account['accountPrimaryGroup']['id'];
            }
            $account = new Tinebase_Model_FullUser($account);
        } else {
            $account = Tinebase_User::factory(Tinebase_User::getConfiguredBackend())->getFullUserById($account);
        }

        Tinebase_Core::getLogger()->addReplacement($password);
        
        $controller = Admin_Controller_User::getInstance();
        $controller->setAccountPassword($account, $password, $password, (bool)$mustChange);
        
        $result = array(
            'success' => TRUE
        );
        return $result;
    }

    /**
     * returns possible mfa adapter for given user
     * 
     * @param array|string $account Tinebase_Model_FullUser data or account id
     * @retujrn array id => user_config_class
     */
    public function getPossibleMFAs($account)
    {
        $result = [];

        $mfas = Tinebase_Config::getInstance()->{Tinebase_Config::MFA};
        if (is_iterable($mfas->records)) {
            foreach ($mfas->records as $mfaConfig) {
                $result[] = [
                    'mfa_config_id' => $mfaConfig->id,
                    'config_class' => $mfaConfig->user_config_class,
                ];
            }
        }
        
        return $result;
    }

    /**
     * adds the name of the account to each item in the name property
     * 
     * @param  array  &$_items array of arrays which contain a type and id property
     * @param  bool   $_hasAccountPrefix
     * @param  bool   $_removePrefix
     * @return array  items with appended name 
     * @throws UnexpectedValueException
     * 
     * @todo    remove all this prefix stuff? why did we add this?
     * @todo    use a resolveMultiple function here
     */
    public static function resolveAccountName(array $_items, $_hasAccountPrefix = FALSE, $_removePrefix = FALSE)
    {
        $prefix = $_hasAccountPrefix ? 'account_' : '';
        
        $return = array();
        foreach ($_items as $num => $item) {
            switch ($item[$prefix . 'type']) {
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_USER:
                    try {
                        $item[$prefix . 'name'] = Tinebase_User::getInstance()->getUserById($item[$prefix . 'id'])->accountDisplayName;
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        $item[$prefix . 'name'] = 'Unknown user';
                    }
                    break;
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP:
                    try {
                        $item[$prefix . 'name'] = Tinebase_Group::getInstance()->getGroupById($item[$prefix . 'id'])->name;
                    } catch (Tinebase_Exception_Record_NotDefined $ternd) {
                        $item[$prefix . 'name'] = 'Unknown group';
                    }
                    break;
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_ROLE:
                    try {
                        $item[$prefix . 'name'] = Tinebase_Acl_Roles::getInstance()->getRoleById($item[$prefix . 'id'])->name;
                    } catch(Tinebase_Exception_NotFound $tenf) {
                        $item[$prefix . 'name'] = 'Unknown role';
                    }
                    break;
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE:
                    $item[$prefix . 'name'] = 'Anyone';
                    break;
                default:
                    throw new UnexpectedValueException('Unsupported accountType: ' . $item[$prefix . 'type']);
                    break;
            }
            if ($_removePrefix) {
                $return[$num] = array(
                    'id'    => $item[$prefix . 'id'],
                    'name'  => $item[$prefix . 'name'], 
                    'type'  => $item[$prefix . 'type'],
                );
            } else {
                $return[$num] = $item;
            }
        }
        return $return;
    }
    
    /**
     * search for shared addressbook containers
     * 
     * @param array $filter unused atm
     * @param array $paging unused atm
     * @return array
     * 
     * @todo add test
     */
    public function searchSharedAddressbooks($filter, $paging)
    {
        $sharedAddressbooks = Admin_Controller_User::getInstance()->searchSharedAddressbooks();
        $result = $this->_multipleRecordsToJson($sharedAddressbooks);
        
        return array(
            'results'       => $result,
            'totalcount'    => count($result),
        );
    }
    
    /********************************* Groups *********************************/
    
    /**
     * gets a single group
     *
     * @param string $id
     * @return array
     *
     * @todo use abstract _get
     */
    public function getGroup($id)
    {
        $groupArray = array();
        
        if ($id) {
            $group = Admin_Controller_Group::getInstance()->get($id);
            
            $groupArray = $group->toArray();
            
            if (!empty($group->container_id)) {
                $groupArray['container_id'] = Tinebase_Container::getInstance()->getContainerById($group->container_id)->toArray();
            }
            
        }
        
        $groupArray['members'] = $this->getGroupMembers($id);
        $groupArray['xprops'] = Tinebase_Helper::jsonDecode($groupArray['xprops']);
        
        return $groupArray;
    }
    
    /**
     * get list of groups
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return array with results array & totalcount (int)
     * 
     * @todo switch to new api with only filter and paging params
     */
    public function getGroups($filter, $sort, $dir, $start, $limit)
    {
        $groups = Admin_Controller_Group::getInstance()->search($filter, $sort, $dir, $start, $limit);
        
        $result = array(
            'results'     => $this->_multipleRecordsToJson($groups),
            'totalcount'  => Admin_Controller_Group::getInstance()->searchCount($filter)
        );
        
        return $result;
    }

    /**
     * get list of group members
     *
     * @param int $groupId
     * @return array with results / total count
     * 
     * @todo use Account Model?
     */
    public function getGroupMembers($groupId)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if ($groupId) {
            $accountIds = Admin_Controller_Group::getInstance()->getGroupMembers($groupId);
            $users = Tinebase_User::getInstance()->getMultiple($accountIds);
            $result['results'] = array();
            foreach ($users as $user) {
                $result['results'][] = array(
                    'id'        => $user->getId(),
                    'type'      => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                    'name'      => $user->accountDisplayName,
                );
            }
            
            $result['totalcount'] = count($result['results']);
        }
        
        return $result;
    }
        
    /**
     * save group data from edit form
     *
     * @param   array $recordData        group data
     * @return  array
     * @todo use _save
     */
    public function saveGroup($recordData)
    {
        // unset if empty
        if (empty($recordData['id'])) {
            unset($recordData['id']);
        }

        $group = new Tinebase_Model_Group($recordData);

        // this needs long execution time because cache invalidation may take long
        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime(60); // 1 minute

        if ( empty($group->id) ) {
            $group = Admin_Controller_Group::getInstance()->create($group);
        } else {
            $group = Admin_Controller_Group::getInstance()->update($group);
        }

        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);

        return $this->getGroup($group->getId());
    }
   
    /**
     * delete multiple groups
     *
     * @param array $groupIds list of contactId's to delete
     * @return array with success flag
     */
    public function deleteGroups($groupIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        Admin_Controller_Group::getInstance()->delete($groupIds);

        return $result;
    }
    
    /********************************** Samba Machines **********************************/
    
    /**
     * Search for records matching given arguments
     *
     * @param array $filter 
     * @param array $paging 
     * @return array
     */
    public function searchSambaMachines($filter, $paging)
    {
        try {
            $result = $this->_search($filter, $paging, Admin_Controller_SambaMachine::getInstance(), 'Admin_Model_SambaMachineFilter');
        } catch (Admin_Exception $ae) {
            // no samba settings defined
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $ae->getMessage());
            $result = array(
                'results'       => array(),
                'totalcount'    => 0
            );
        }
        
        return $result;
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getSambaMachine($id)
    {
        return $this->_get($id, Admin_Controller_SambaMachine::getInstance());
    }
    
    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveSambaMachine($recordData)
    {
        try {
            $result = $this->_save($recordData, Admin_Controller_SambaMachine::getInstance(), 'SambaMachine', 'accountId');
        } catch (Admin_Exception $ae) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Error while saving samba machine: ' . $ae->getMessage());
            $result = array('success' => FALSE);
        }
        
        return $result;
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids 
     * @return string
     */
    public function deleteSambaMachines($ids)
    {
        return $this->_delete($ids, Admin_Controller_SambaMachine::getInstance());
    }
    

    /********************************** Tags **********************************/
    
    /**
     * gets a single tag
     *
     * @param int $tagId
     * @return array
     */
    public function getTag($tagId)
    {
        $tag = array();
        
        if ($tagId) {
            $tag = Admin_Controller_Tags::getInstance()->get($tagId)->toArray();
            $tag['rights'] = self::resolveAccountName($tag['rights'] , true);
        }
        $tag['appList'] = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED)->toArray();
        
        return $tag;
    }
    
    /**
     * get list of tags
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return array with results array & totalcount (int)
     */
    public function getTags($query, $sort, $dir, $start, $limit)
    {
        $filter = new Tinebase_Model_TagFilter(array(
            'name'        => '%' . $query . '%',
            'description' => '%' . $query . '%',
            'type'        => Tinebase_Model_Tag::TYPE_SHARED
        ));
        $paging = new Tinebase_Model_Pagination(array(
            'start' => $start,
            'limit' => $limit,
            'sort'  => $sort,
            'dir'   => $dir
        ));
        
        $tags = Admin_Controller_Tags::getInstance()->search_($filter, $paging);
        
        $result = array(
            'results'     => $this->_multipleRecordsToJson($tags),
            'totalcount'  => Admin_Controller_Tags::getInstance()->searchCount_($filter)
        );
        
        return $result;
    }
        
    /**
     * save tag data from edit form
     *
     * @param   array $tagData
     * 
     * @return  array with success, message, tag data and tag members
     */
    public function saveTag($tagData)
    {
        // unset if empty
        if (empty($tagData['id'])) {
            unset($tagData['id']);
        }
        
        $tag = new Tinebase_Model_Tag($tagData);
        $tag->rights = new Tinebase_Record_RecordSet('Tinebase_Model_TagRight', $tagData['rights']);
        
        if ( empty($tag->id) ) {
            $tag = Admin_Controller_Tags::getInstance()->create($tag);
        } else {
            $tag = Admin_Controller_Tags::getInstance()->update($tag);
        }
        
        return $this->getTag($tag->getId());
        
    }    
        
    /**
     * delete multiple tags
     *
     * @param array $tagIds list of contactId's to delete
     * @return array with success flag
     */
    public function deleteTags($tagIds)
    {
        return $this->_delete($tagIds, Admin_Controller_Tags::getInstance());
    }
    
    /********************************* Roles **********************************/
    
    /**
     * get a single role with all related data
     *
     * @param int $roleId
     * @return array
     */
    public function getRole($roleId)
    {
        $role = array();
        if ($roleId) {
            $role = Admin_Controller_Role::getInstance()->get($roleId)->toArray();
        }

        $role['roleMembers'] = $this->getRoleMembers($roleId);
        $role['roleRights'] = $this->getRoleRights($roleId);
        $role['allRights'] = $this->getAllRoleRights();
        return $role;
    }
    
    /**
     * get list of roles
     *
     * @param string $query
     * @param string $sort
     * @param string $dir
     * @param int $start
     * @param int $limit
     * @return array with results array & totalcount (int)
     */
    public function getRoles($query, $sort, $dir, $start, $limit)
    {
        $filter = new Tinebase_Model_RoleFilter(array(
            array('field' => 'query', 'operator' => 'contains', 'value' => $query),
        ));
        $paging = new Tinebase_Model_Pagination(array(
            'start' => $start,
            'limit' => $limit,
            'sort'  => $sort,
            'dir'   => $dir
        ));
        
        $roles = Admin_Controller_Role::getInstance()->search($filter, $paging);
        
        $result = array(
            'results'     => $this->_multipleRecordsToJson($roles),
            'totalcount'  => Admin_Controller_Role::getInstance()->searchCount($filter)
        );
        
        return $result;
    }

    /**
     * save role data from edit form
     *
     * @param   array $roleData        role data
     * @param   array $roleMembers     role members
     * @param   array $roleRights      role rights
     * @return  array
     */
    public function saveRole($roleData, $roleMembers, $roleRights)
    {
        // unset if empty
        if (empty($roleData['id'])) {
            unset($roleData['id']);
        }
        
        $role = new Tinebase_Model_Role($roleData);
        
        if (empty($role->id) ) {
            $role = Admin_Controller_Role::getInstance()->create($role, $roleMembers, $roleRights);
        } else {
            $role = Admin_Controller_Role::getInstance()->update($role, $roleMembers, $roleRights);
        }
        
        return $this->getRole($role->getId());
    }    

    /**
     * delete multiple roles
     *
     * @param array $roleIds list of roleId's to delete
     * @return array with success flag
     */
    public function deleteRoles($roleIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        Admin_Controller_Role::getInstance()->delete($roleIds);

        return $result;
    }

    /**
     * get list of role members
     *
     * @param int $roleId
     * @return array with results / totalcount
     * 
     * @todo    move group/user resolution to new accounts class
     */
    public function getRoleMembers($roleId)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if (!empty($roleId)) {
            $members = Admin_Controller_Role::getInstance()->getRoleMembers($roleId);
    
            $result['results'] = self::resolveAccountName($members, TRUE, TRUE);
            $result['totalcount'] = count($result['results']);
        }
        return $result;
    }

    /**
     * get list of role rights
     *
     * @param int $roleId
     * @return array with results / totalcount
     */
    public function getRoleRights($roleId)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if (!empty($roleId)) {
            $rights = Admin_Controller_Role::getInstance()->getRoleRights($roleId);
        
            $result['results'] = $rights;
            $result['totalcount'] = count($rights);
        }    
        return $result;
    }
    
    /**
     * get list of all role rights for all applications
     *
     * @return array with all rights for applications
     * 
     * @todo    get only rights of active applications?
     */
    public function getAllRoleRights()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Get all rights of all apps.');
        
        $result = array();
        
        $applications = Admin_Controller_Application::getInstance()->search(NULL, 'name', 'ASC', NULL, NULL);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($applications->toArray(), TRUE));
        
        foreach ($applications as $application) {
            $appId = $application->getId();
            $rightsForApplication = array(
                "application_id"    => $appId,
                "text"              => $application->name,
                "children"          => array()
            );
            
            $allAplicationRights = Tinebase_Application::getInstance()->getAllRightDescriptions($appId);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($allAplicationRights, TRUE));
            
            foreach ($allAplicationRights as $right => $description) {
                $rightsForApplication["children"][] = array(
                    "text"      => $description['text'],
                    "qtip"      => $description['description'],
                    "right"     => $right,
                );
            }

            $result[] = $rightsForApplication;
        }
        
        return $result;
    }
    
    /****************************** Container ******************************/

    /**
     * Search for records matching given arguments
     *
     * @param array $filter 
     * @param array $paging 
     * @return array
     */
    public function searchContainers($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Admin_Controller_Container::getInstance(), 'Tinebase_Model_ContainerFilter');
        
        // remove acl (app) filter
        foreach ($result['filter'] as $id => $filter) {
            if ($filter['field'] === 'application_id' && $filter['operator'] === 'in') {
                unset($result['filter'][$id]);
            }
        }
        
        return $result;
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getContainer($id)
    {
        return $this->_get($id, Admin_Controller_Container::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveContainer($recordData)
    {
        $application = Tinebase_Application::getInstance()->getApplicationById($recordData['application_id'])->name;
        if (!isset($recordData['model']) || empty($recordData['model']) ||
                !($applicationModelParts = \explode('.', $recordData['model'])) ||
                    (1 === \count($applicationModelParts) && false === \strpos($recordData['model'], '_Model_'))) {
            throw new \InvalidArgumentException('Invalid model specified.');
        }
        // Handling if a model is either in php format like Application_Model_Foobar or Application.Model.Foobar
        $recordData['model'] = \strpos($recordData['model'], '_Model_') !== false ? $recordData['model'] :
            $application . '_Model_' . \end($applicationModelParts);
        \reset($applicationModelParts);

        $additionalArguments = ((isset($recordData['note']) || array_key_exists('note', $recordData))) ? array(array('note' => $recordData['note'])) : array();
        return $this->_save($recordData, Admin_Controller_Container::getInstance(), 'Tinebase_Model_Container', 'id', $additionalArguments);
    }

    /**
     * deletes existing records
     *
     * @param  array  $ids
     * @return array
     */
    public function deleteContainers($ids)
    {
        return $this->_delete($ids, Admin_Controller_Container::getInstance());
    }

    /****************************** Customfield ******************************/

    /**
     * Search for records matching given arguments
     *
     * @param array $filter 
     * @param array $paging 
     * @return array
     */
    public function searchCustomfields($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Admin_Controller_Customfield::getInstance(), 'Tinebase_Model_CustomField_ConfigFilter');
        
        return $result;
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getCustomfield($id)
    {
        $customField = Admin_Controller_Customfield::getInstance()->get($id)->toArray();
        $customField['grants'] = self::resolveAccountName($customField['grants'] , true);
        return $customField;
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveCustomfield($recordData)
    {
        return $this->_save($recordData, Admin_Controller_Customfield::getInstance(), 'Tinebase_Model_CustomField_Config', 'id');
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids
     * @param  array $context
     * @return array
     */
    public function deleteCustomfields($ids, array $context = array())
    {
        $controller = Admin_Controller_Customfield::getInstance();
        $controller->setRequestContext($context);

        return $this->_delete($ids, $controller);
    }

    /****************************** Config *********************************/

    /**
     * Search for records matching given arguments
     *
     * @param array $filter
     * @param array $paging
     * @return array
     */
    public function searchConfigs($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Admin_Controller_Config::getInstance(), 'Tinebase_Model_ConfigFilter', false, self::TOTALCOUNT_COUNTRESULT);

        return $result;
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getConfig($id)
    {
        return $this->_get($id, Admin_Controller_Config::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveConfig($recordData)
    {
        return $this->_save($recordData, Admin_Controller_Config::getInstance(), 'Tinebase_Model_Config', 'id');
    }

    /**
     * deletes existing records
     *
     * @param  array  $ids
     * @return array
     */
    public function deleteConfigs($ids)
    {
        return $this->_delete($ids, Admin_Controller_Config::getInstance());
    }

    /****************************** ImportExportDefinition ******************************/

    /**
     * Search for records matching given arguments
     *
     * @param array $filter
     * @param array $paging
     * @return array
     */
    public function searchImportExportDefinitions($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Admin_Controller_ImportExportDefinition::getInstance(), 'Tinebase_Model_ImportExportDefinitionFilter');

        return $result;
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getImportExportDefinition($id)
    {
        return $this->_get($id, Admin_Controller_ImportExportDefinition::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveImportExportDefinition($recordData)
    {
        return $this->_save($recordData, Admin_Controller_ImportExportDefinition::getInstance(), 'Tinebase_Model_ImportExportDefinition');
    }

    /**
     * deletes existing records
     *
     * @param  array  $ids
     * @return string
     */
    public function deleteImportExportDefinitions($ids)
    {
        return $this->_delete($ids, Admin_Controller_ImportExportDefinition::getInstance());
    }


    /****************************** EmailAccount ******************************/

    /**
     * Search for records matching given arguments
     *
     * @param array $filter
     * @param array $paging
     * @return array
     */
    public function searchEmailAccounts($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Admin_Controller_EmailAccount::getInstance(), 'Felamimail_Model_AccountFilter');

        return $result;
    }

    /**
     * Return a single record
     *
     * @param string $id
     * @return array
     * @throws Tinebase_Exception_NotFound
     */
    public function getEmailAccount(string $id): array
    {
        if (Tinebase_EmailUser::backendSupportsMasterPassword()) {
            try {
                $raii = Tinebase_EmailUser::prepareAccountForSieveAdminAccess($id);
                $sieve = true;
            } catch (Tinebase_Exception_Backend $teb) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                        . ' ' . $teb->getMessage());
                }
                $sieve = false;
            }
        } else {
            $sieve = false;
        }

        $result = $this->_get($id, Admin_Controller_EmailAccount::getInstance());

        if ($sieve && isset($result['type']) && $result['type'] !== Felamimail_Model_Account::TYPE_USER) {
            try {
                $sieveRecord = Felamimail_Controller_Sieve::getInstance()->getVacation($id);
                $result['sieve_vacation'] = $this->_recordToJson($sieveRecord);

                $records = Felamimail_Controller_Sieve::getInstance()->getRules($id);
                $result['sieve_rules'] = $this->_multipleRecordsToJson($records);
            } catch (Felamimail_Exception_SieveInvalidCredentials $fesic) {
                Tinebase_Exception::log($fesic);
            } catch (Zend_Mail_Protocol_Exception $zmpe) {
                Tinebase_Exception::log($zmpe);
            } finally {
                Tinebase_EmailUser::removeAdminAccess();
                if (isset($raii)) {
                    unset($raii);
                }
            }
        }

        if ($result['type'] === Felamimail_Model_Account::TYPE_ADB_LIST) {
            $listRecord = Addressbook_Controller_List::getInstance()->search(new Addressbook_Model_ListFilter([
                ['field' => 'id', 'operator' => 'equals', 'value' => $result['user_id']]
            ]))->getFirstRecord();
            if ($listRecord) {
                $result['adb_list'] = $listRecord->toArray();
            }
        }

        return $result;
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveEmailAccount($recordData)
    {
        return $this->_save($recordData, Admin_Controller_EmailAccount::getInstance(), 'Felamimail_Model_Account');
    }

    /**
     * deletes existing records
     *
     * @param  array  $ids
     * @return string
     */
    public function deleteEmailAccounts($ids)
    {
        return $this->_delete($ids, Admin_Controller_EmailAccount::getInstance());
    }

    /****************************** LogEntry ******************************/

    /**
     * Search for records matching given arguments
     *
     * @param array $filter
     * @param array $paging
     * @return array
     */
    public function searchLogEntrys($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Admin_Controller_LogEntry::getInstance(), 'Tinebase_Model_LogEntryFilter');

        return $result;
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getLogEntry($id)
    {
        return $this->_get($id, Admin_Controller_LogEntry::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveLogEntry($recordData)
    {
        return $this->_save($recordData, Admin_Controller_LogEntry::getInstance(), 'Tinebase_Model_LogEntry');
    }

    /**
     * deletes existing records
     *
     * @param  array  $ids
     * @return string
     */
    public function deleteLogEntrys($ids)
    {
        return $this->_delete($ids, Admin_Controller_LogEntry::getInstance());
    }


    /****************************** other *******************************/
    
    /**
     * returns phpinfo() output
     * 
     * @return array
     */
    public function getServerInfo()
    {
        if (! Tinebase_Core::getUser()->hasRight('Admin', Admin_Acl_Rights::RUN)) {
            return [];
        }
        
        ob_start();
        phpinfo();
        $out = ob_get_clean();
        
        // only return body
        $dom = new DOMDocument('1.0', 'UTF-8');
        try {
            $dom->loadHTML($out);
            $body = $dom->getElementsByTagName('body');
            $phpinfo = $dom->saveXml($body->item(0));
        } catch (Exception $e) {
            // no html (CLI)
            $phpinfo = $out;
        }
        
        return array(
            'html' => $phpinfo
        );
    }

    /**
     * save preferences for application
     *
     * @param array $data json encoded preferences data
     * @return array with the changed prefs
     *
     * @throws Tinebase_Exception_AccessDenied
     */
    public function savePreferences($data, $accountId = null)
    {
        $decodedData = $this->_prepareParameter($data);

        $result = array();
        // save default preference
        if (!$accountId) {
            foreach ($decodedData as $applicationName => $data) {
                $backend = Tinebase_Core::getPreference($applicationName);
                if ($backend !== NULL) {
                    $backend->saveAdminPreferences($data);
                }
            }
        } else {
            // save other user preference
            if (!Tinebase_Core::getUser()->hasRight('Admin', Admin_Acl_Rights::MANAGE_ACCOUNTS)) {
                throw new Tinebase_Exception_AccessDenied('user has not right to manage preference for other users');
            }
            $json = new Tinebase_Frontend_Json();
            return $json->savePreferences($data, $accountId);
        }
        
        return array(
            'status'    => 'success',
            'results'   => $result
        );
    }

    /**
     * save quotas
     * @param string $application
     * @param array $additionalData
     * @return false[]|mixed|Tinebase_Config_Struct|Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_Backend_Database_LockTimeout
     * @throws Tinebase_Exception_NotFound
     */
    public function saveQuota(string $application, $recordData = null, array $additionalData = [])
    {
        parent::_setRequestContext(Admin_Controller_Quota::getInstance());
        $result = Admin_Controller_Quota::getInstance()->updateQuota($application, $recordData, $additionalData);
        // FIXME updateQuota should return a defined type ... this is very ugly
        return (is_object($result) && method_exists($result, 'toArray')) ? $result->toArray() : $result;
    }

    /**
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_Validation
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    public function searchQuotaNodes($filter = null)
    {
        if (! Tinebase_Core::getUser()->hasRight('Admin', Admin_Acl_Rights::VIEW_QUOTA_USAGE)) {
            return FALSE;
        }

        if ($isFelamimailInstalled = Tinebase_Application::getInstance()->isInstalled('Felamimail')) {
            $emailPath = Tinebase_FileSystem::getInstance()->getApplicationBasePath('Felamimail');
        } else {
            $emailPath = '';
        }
        $virtualPath = $emailPath . '/Emails';
        $path = '';
        $pathArray = null;
        if (null !== $filter) {
            $key = null;
            array_walk($filter, function ($val, $k) use (&$path, &$pathArray, &$key) {
                if ('path' === $val['field']) {
                    $path = $val['value'];
                    $pathArray = $val;
                    $key = $k;
                }
            });
            if (null !== $key) {
                unset($filter[$key]);
            }
        }
        
        if ($isFelamimailInstalled && strpos($path, $virtualPath) === 0) {
            $records = $this->_getVirtualEmailQuotaNodes(str_replace($virtualPath, '', $path));
            $filterArray = $filter;
            if (null !== $pathArray) {
                $filterArray[] = $pathArray;
            }
            $result = $this->_multipleRecordsToJson($records);
        } else {
            $filter = $this->_decodeFilter($filter, 'Tinebase_Model_Tree_Node_Filter');
            // ATTENTION sadly the pathfilter to Array does path magic, returns the flatpath and not the statpath
            // etc. this is Filemanager path magic. We don't want that here!
            $filterArray = $filter->toArray();
            if (null !== $pathArray) {
                $filterArray[] = $pathArray;
            }
            $filter = new Tinebase_Model_Tree_Node_Filter($filterArray, '', array('ignoreAcl' => true));

            $pathFilters = $filter->getFilter('path', true);
            if (count($pathFilters) !== 1) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                        . ' Exactly one path filter required.');
                }
                $pathFilter = $filter->createFilter(array(
                        'field' => 'path',
                        'operator' => 'equals',
                        'value' => '/',
                    )
                );
                $filter->removeFilter('path');
                $filter->addFilter($pathFilter);
                $path = '/';
            }

            if ($path === '') {
                $records = new Tinebase_Record_RecordSet(Tinebase_Model_Tree_Node::class, []);
                
                if (Admin_Config::getInstance()->{Admin_Config::QUOTA_ALLOW_TOTALINMB_MANAGEMNET}) {
                    $totalQuotaInMB = Tinebase_Config::getInstance()->{Tinebase_Config::QUOTA}->{Tinebase_Config::QUOTA_TOTALINMB};
                    try {
                        $imapBackend = Tinebase_EmailUser::getInstance();
                        $imapUsageQuota = $imapBackend instanceof Tinebase_EmailUser_Imap_Dovecot ? $imapBackend->getTotalUsageQuota() : null;
                        $totalEmailQuotaUsage = $imapUsageQuota['mailSize'];
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        $totalEmailQuotaUsage = 0;
                    }

                    $node = new Tinebase_Model_Tree_Node([], true);
                    $node->name = 'Total Quota';
                    $node->quota = $totalQuotaInMB * 1024 * 1024;
                    $node->size = Tinebase_FileSystem_Quota::getRootUsedBytes() + $totalEmailQuotaUsage;
                    $node->revision_size = $node->size;
                    $node->setId(md5($node->name));
                    $node->type = Tinebase_Model_Tree_FileObject::TYPE_FOLDER;
                    $records->addRecord($node);
                }
            } else {
                $filter->removeFilter('type');
                $filter->addFilter($filter->createFilter(array(
                    'field' => 'type',
                    'operator' => 'equals',
                    'value' => Tinebase_Model_Tree_FileObject::TYPE_FOLDER,
                )));

                $records = Tinebase_FileSystem::getInstance()->search($filter);
                if ($isFelamimailInstalled && $path === $emailPath) {
                    $imapBackend = null;
                    try {
                        $imapBackend = Tinebase_EmailUser::getInstance();
                    } catch (Tinebase_Exception_NotFound $tenf) {
                    }
                    if ($imapBackend instanceof Tinebase_EmailUser_Imap_Dovecot) {
                        /** @var Tinebase_Model_Tree_Node $emailNode */
                        $emailNode = clone $records->getFirstRecord();
                        $emailNode->setId(trim($emailPath, '/'));
                        $emailNode->name = 'Emails';
                        $emailNode->path = $virtualPath;
                        $imapUsageQuota = $imapBackend->getTotalUsageQuota();
                        $emailNode->quota = $imapUsageQuota['mailQuota'];
                        $emailNode->size = $imapUsageQuota['mailSize'];
                        $emailNode->revision_size = $emailNode->size;
                        $emailNode->xprops('customfields')['emailQuotas'] = $imapUsageQuota;
                        $records->addRecord($emailNode);
                    }
                } elseif ($isFelamimailInstalled && '/' === $path) {
                    $imapBackend = null;
                    try {
                        $imapBackend = Tinebase_EmailUser::getInstance();
                    } catch (Tinebase_Exception_NotFound $tenf) {
                    }
                    if ($imapBackend instanceof Tinebase_EmailUser_Imap_Dovecot) {
                        $imapUsageQuota = $imapBackend->getTotalUsageQuota();
                        $node = $records->filter('name',
                            Tinebase_Application::getInstance()->getApplicationByName('Felamimail')->getId())->getFirstRecord();
                        $node->quota += $imapUsageQuota['mailQuota'];
                        $node->size += $imapUsageQuota['mailSize'];
                        $node->xprops('customfields')['emailQuotas'] = $imapUsageQuota;
                    }
                }

                $filterArray = $filter->toArray();
                array_walk($filterArray, function (&$val) use ($path) {
                    if ('path' === $val['field']) {
                        $val['value'] = $path;
                    }
                });
            }
            
            $result = $this->_multipleRecordsToJson($records, $filter);
        }

        return array(
            'results'       => array_values($result),
            'totalcount'    => count($result),
            'filter'        => $filterArray
        );
    }

    /**
     * @param string $path
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    protected function _getVirtualEmailQuotaNodes($path)
    {
        /** @var Tinebase_EmailUser_Imap_Dovecot $imapBackend */
        $imapBackend = Tinebase_EmailUser::getInstance();
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array());

        if (!$imapBackend instanceof Tinebase_EmailUser_Imap_Dovecot) {
            return $result;
        }

        $path = trim($path, '/');
        if (empty($path)) {
            $parent_id = Tinebase_Application::getInstance()->getApplicationByName('Felamimail')->getId();

            $domains = array_unique(array_merge(
                Tinebase_EmailUser::getAllowedDomains(Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP)),
                $imapBackend->getAllDomains()
            ));

            foreach ($domains as $domain) {
                $usageQuota = $imapBackend->getTotalUsageQuota($domain);

                $node = new Tinebase_Model_Tree_Node(array(), true);
                $node->parent_id = $parent_id;
                $node->name = $domain;
                $node->quota = $usageQuota['mailQuota'];
                $node->size = $usageQuota['mailSize'];
                $node->revision_size = $usageQuota['mailSize'];
                $node->setId(md5($domain));
                $node->type = Tinebase_Model_Tree_FileObject::TYPE_FOLDER;
                $node->xprops('customfields')['emailQuotas'] = $usageQuota;
                $node->xprops('customfields')['domain'] = $domain;
                $result->addRecord($node);
            }
        } elseif (count($pathParts = explode('/', $path)) === 1) {
            $parent_id = md5($pathParts[0]);
            $accountIds = [];
            $nodeIds = [];
            $accounts = Admin_Controller_EmailAccount::getInstance()->search();
            foreach ($accounts as $account) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . ' account node data: '
                    . print_r($account, true));
            }
            
            $emailUsers = $imapBackend->getAllEmailUsers($pathParts[0]);
            /** @var Tinebase_Model_EmailUser $emailUser */
            foreach ($emailUsers as $emailUser) {
                $node = new Tinebase_Model_Tree_Node(array(), true);
                $node->parent_id = $parent_id;
                $node->name = $emailUser->emailUsername;
                $node->quota = $emailUser->emailMailQuota;
                $node->size = $emailUser->emailMailSize;
                $node->revision_size = $emailUser->emailMailSize;
                $node->setId($emailUser->emailUserId);
                $node->type = Tinebase_Model_Tree_FileObject::TYPE_FOLDER;
                $node->xprops('customfields')['emailUser'] = $emailUser->toArray();
                $node->xprops('customfields')['isPersonalNode'] = true;
                $result->addRecord($node);
                list($accountId) = explode('@', $emailUser->emailUsername);
                $nodeIds[$accountId] = $emailUser->emailUserId;
                $accountIds[] = $accountId;

                foreach ($accounts as $account) {
                    if ($account instanceof Felamimail_Model_Account && !empty($account->xprops)) {
                        if (isset($account->xprops['emailUserIdImap']) && $emailUser->emailUserId === $account->xprops['emailUserIdImap']) {
                            $node->name = $account->email;
                            $node->xprops('customfields')['emailAccountId'] = $account['id'];
                        }
                    }
                }
            }
            
            /** @var Tinebase_Model_User $user */
            foreach (Tinebase_User::getInstance()->getMultiple($accountIds) as $user) {
                if (isset($nodeIds[$user->accountId])) {
                    $result->getById($nodeIds[$user->accountId])->name = $user->accountDisplayName;
                }
            }
        }

        return $result;
    }
    
    /****************************** common ******************************/
    
    /**
     * returns record prepared for json transport
     *
     * @param Tinebase_Record_Interface $_record
     * @return array record data
     */
    protected function _recordToJson($_record)
    {
        if ($_record instanceof Felamimail_Model_Account) {
            $converter = new Admin_Convert_EmailAccount_Json(get_class($_record));
            $result = $converter->fromTine20Model($_record);
        } else {
            $result = parent::_recordToJson($_record);
        }

        if ($_record instanceof Tinebase_Model_Container) {
            $result['account_grants'] = Tinebase_Frontend_Json_Container::resolveAccounts($_record['account_grants']->toArray());
        }

        return $result;
    }
    
    /**
     * returns multiple records prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_records Tinebase_Record_Interface
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @return array data
     */
    protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records, $_filter = NULL, $_pagination = NULL)
    {
        switch ($_records->getRecordClassName()) {
            case 'Tinebase_Model_AccessLog':
                // TODO use _resolveUserFields and remove this
                foreach ($_records as $record) {
                    if (! empty($record->account_id)) {
                        try {
                            $record->account_id = Admin_Controller_User::getInstance()->get($record->account_id, true)->toArray();
                        } catch (Tinebase_Exception_NotFound $e) {
                            $record->account_id = Tinebase_User::getInstance()->getNonExistentUser('Tinebase_Model_FullUser')->toArray();
                        }
                    }
                }
                break;
            case 'Tinebase_Model_Container':
            case 'Tinebase_Model_ImportExportDefinition':
            case 'Tinebase_Model_CustomField_Config':
                $applications = Tinebase_Application::getInstance()->getApplications();
                foreach ($_records as $record) {
                    $idx = $applications->getIndexById($record->application_id);
                    if ($idx !== FALSE) {
                        $record->application_id = $applications[$idx];
                    }
                }
                break;
            case Tinebase_Model_Tree_Node::class:
                // check if we filtered for /personal and expand accountid's
                if ($_filter) {
                    $filter = $_filter->getFilter('path', true);
                    $path = $filter[0]->getValue();
                    $filemanager_id = Tinebase_Application::getInstance()->getApplicationByName('Filemanager')->getId();
             
                    foreach ($_records as $record) {
                        if (empty($record->name) || !$record instanceof Tinebase_Model_Tree_Node) {
                            $userData = Tinebase_User::getInstance()->getNonExistentUser('Tinebase_Model_FullUser')->toArray();
                            $record->name = $userData['accountLoginName'];
                            
                            continue;
                        }
                        
                        try {
                            // generic effective quota infos
                            if (strpos($path, $filemanager_id )) {
                                if ($quotas = Tinebase_FileSystem::getInstance()->getEffectiveAndLocalQuota($record)) {
                                    $record->xprops('customfields')['effectiveAndLocalQuota'] = $quotas;
                                }
                            }
                            
                            // user personalFSQuota quota under /Filemanager/folders/personal
                            if ($path === "/$filemanager_id/folders/personal") {
                                $userData = Admin_Controller_User::getInstance()->get($record->name)->toArray();
                                $record->quota = $userData['xprops']['personalFSQuota'] ?? 0;
                                $record->xprops('customfields')['isPersonalNode'] = true;
                                $record->xprops('customfields')['accountLoginName'] = $userData['accountLoginName'];
                                $record->xprops('customfields')['accountId'] = $record->name;
                                $record->name = $userData['accountDisplayName'];
                            }
    
                            // total quota for /Filemanager
                            if ($record->name === $filemanager_id || $path === "/$filemanager_id") {
                                $record->quota = Tinebase_FileSystem_Quota::getRootQuotaBytes();
                                $record->size = Tinebase_FileSystem_Quota::getRootUsedBytes();
                            } 
                        } catch (Tinebase_Exception_NotFound $e) {
                            $userData = Tinebase_User::getInstance()->getNonExistentUser('Tinebase_Model_FullUser')->toArray();
                            $record->name = $userData['accountLoginName'];
                        }
                    }

                    // only return exist users
                    $_records = $_records->filter(function($record) {
                        return $record->name !== 'unknown';
                    });
                    // check if there is helper function
                }
                break;
        }
        
        return parent::_multipleRecordsToJson($_records, $_filter, $_pagination);
    }

    /***************************** sieve funcs *******************************/

    /**
     * get sieve vacation for account
     *
     * @param  string $id account id
     * @return array
     */
    public function getSieveVacation($id)
    {
        $raii = Tinebase_EmailUser::prepareAccountForSieveAdminAccess($id);

        try {
            Admin_Controller_EmailAccount::getInstance()->checkRight(Admin_Acl_Rights::VIEW_EMAILACCOUNTS);
            $result = (new Felamimail_Frontend_Json())->getVacation($id);
        } finally {
            Tinebase_EmailUser::removeAdminAccess();
        }
        //for unused variable check
        unset($raii);
        return $result;
    }

    /**
     * set sieve vacation for account
     *
     * @param array $recordData
     * @return array
     */
    public function saveSieveVacation($recordData)
    {
        $raii = Tinebase_EmailUser::prepareAccountForSieveAdminAccess($recordData['id']);
        try {
            Admin_Controller_EmailAccount::getInstance()->checkRight(Admin_Acl_Rights::MANAGE_EMAILACCOUNTS);
            $result = (new Felamimail_Frontend_Json())->saveVacation($recordData);
        } finally {
            Tinebase_EmailUser::removeAdminAccess();
        }
        //for unused variable check
        unset($raii);
        return $result;
    }

    /**
     * set sieve custom script for account
     *
     * @param $scriptData
     * @return string
     */
    public function saveSieveCustomScript($accountId, $scriptData)
    {
        $raii = Tinebase_EmailUser::prepareAccountForSieveAdminAccess($accountId);
        try {
            Admin_Controller_EmailAccount::getInstance()->checkRight(Admin_Acl_Rights::MANAGE_EMAILACCOUNTS);
            $sieveScript = Felamimail_Controller_Sieve::getInstance()->setCustomScript($accountId, $scriptData, false);
        } finally {
            Tinebase_EmailUser::removeAdminAccess();
        }
        //for unused variable check
        unset($raii);
        return $sieveScript;
    }

    /**
     * get sieve rules for account
     *
     * @param  string $accountId
     * @return array
     */
    public function getSieveRules($accountId)
    {
        $raii = Tinebase_EmailUser::prepareAccountForSieveAdminAccess($accountId);
        try {
            Admin_Controller_EmailAccount::getInstance()->checkRight(Admin_Acl_Rights::VIEW_EMAILACCOUNTS);
            $result = (new Felamimail_Frontend_Json())->getRules($accountId);
        } finally {
            Tinebase_EmailUser::removeAdminAccess();
        }
        unset($raii);
        return $result;
    }

    /**
     * get sieve script for account
     *
     * @param  string $accountId
     * @return string
     */
    public function getSieveScript($accountId)
    {
        $raii = Tinebase_EmailUser::prepareAccountForSieveAdminAccess($accountId);
        try {
            Admin_Controller_EmailAccount::getInstance()->checkRight(Admin_Acl_Rights::VIEW_EMAILACCOUNTS);
            $result = Felamimail_Controller_Sieve::getInstance()->getSieveScript($accountId);
        } finally {
            Tinebase_EmailUser::removeAdminAccess();
        }
        // for unused variable check
        unset($raii);
        return $result->getSieve();
    }

    /**
     * get sieve custom script for account
     *
     * @param  string $accountId
     * @return array
     */
    public function getSieveCustomScript($accountId)
    {
        $raii = Tinebase_EmailUser::prepareAccountForSieveAdminAccess($accountId);
        try {
            Admin_Controller_EmailAccount::getInstance()->checkRight(Admin_Acl_Rights::VIEW_EMAILACCOUNTS);
            $result = Felamimail_Controller_Sieve::getInstance()->getSieveCustomScript($accountId);
        } finally {
            Tinebase_EmailUser::removeAdminAccess();
        }
        // for unused variable check
        unset($raii);
        return  $this->_recordToJson($result);;
    }

    /**
     * set sieve rules for account
     *
     * @param   array $accountId
     * @param   array $rulesData
     * @return  array
     */
    public function saveRules($accountId, $rulesData)
    {
        $raii = Tinebase_EmailUser::prepareAccountForSieveAdminAccess($accountId);
        try {
            Admin_Controller_EmailAccount::getInstance()->checkRight(Admin_Acl_Rights::MANAGE_EMAILACCOUNTS);
            $result = (new Felamimail_Frontend_Json())->saveRules($accountId, $rulesData);
        } finally {
            Tinebase_EmailUser::removeAdminAccess();
        }
        // for unused variable check
        unset($raii);
        return $result;
    }

    /**
     * reveal email account password
     *
     * @param string $accountId
     * @return array
     * @throws Felamimail_Exception
     * @throws Tinebase_Exception
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     */
    public function revealEmailAccountPassword(string $accountId): array
    {
        if (! $accountId) {
            return [];
        }

        Admin_Controller_EmailAccount::getInstance()->checkRight(Admin_Acl_Rights::MANAGE_EMAILACCOUNTS);
        $fmailaccount = Felamimail_Controller_Account::getInstance()->get($accountId);
        $imapConfig = $fmailaccount->getImapConfig();
        
        Tinebase_Notes::getInstance()->addSystemNote($fmailaccount, Tinebase_Core::getUser(),
            Tinebase_Model_Note::SYSTEM_NOTE_REVEAL_PASSWORD, $fmailaccount, 'Sql', 'Felamimail_Model_Account');
        
        return [
            'password'=> $imapConfig['password']
        ];
    }
}

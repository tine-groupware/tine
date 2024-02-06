<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * principal backend class
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 */
class Tinebase_WebDav_PrincipalBackend implements \Tine20\DAVACL\PrincipalBackend\BackendInterface
{
    const PREFIX_USERS  = 'principals/users';
    const PREFIX_GROUPS = 'principals/groups';
    const PREFIX_INTELLIGROUPS = 'principals/intelligroups';
    const SHARED        = 'shared';

    protected static $_showHiddenGroups = false;

    /**
     * @param null|bool $bool
     * @return bool
     */
    public static function showHiddenGroups($bool = null)
    {
        $oldValue = static::$_showHiddenGroups;
        if (null !== $bool) {
            static::$_showHiddenGroups = (bool)$bool;
        }
        return $oldValue;
    }
    
    /**
     * (non-PHPdoc)
     * @see Tine20\DAVACL\IPrincipalBackend::getPrincipalsByPrefix()
     */
    public function getPrincipalsByPrefix($prefixPath) 
    {
        $principals = array();
        
        switch ($prefixPath) {
            case self::PREFIX_GROUPS:
            case self::PREFIX_INTELLIGROUPS:
                $filter = new Addressbook_Model_ListFilter(array(
                    array(
                        'field'     => 'type',
                        'operator'  => 'equals',
                        'value'     => Addressbook_Model_List::LISTTYPE_GROUP
                    )
                ));
                
                $lists = Addressbook_Controller_List::getInstance()->search($filter);
                
                foreach ($lists as $list) {
                    $principals[] = $this->_listToPrincipal($list, $prefixPath);
                }

                foreach (Tinebase_Acl_Roles::getInstance()->getAll() as $role) {
                    $principals[] = $this->_roleToPrincipal($role, $prefixPath);
                }
                
                break;
                
            case self::PREFIX_USERS:
                $filter = $this->_getContactFilterForUserContact();
                
                $contacts = Addressbook_Controller_Contact::getInstance()->search($filter);
                
                foreach ($contacts as $contact) {
                    $principals[] = $this->_contactToPrincipal($contact);
                }
                
                $principals[] = $this->_contactForSharedPrincipal();
                
                break;
        }
        
        return $principals;
    }
    
    /**
     * (non-PHPdoc)
     * @see Tine20\DAVACL\IPrincipalBackend::getPrincipalByPath()
     * @todo resolve real $path
     */
    public function getPrincipalByPath($path) 
    {
        // any user has to lookup the data at least once
        $cacheId = Tinebase_Helper::convertCacheId('getPrincipalByPath' . Tinebase_Core::getUser()->getId() . $path);
        
        $principal = Tinebase_Core::getCache()->load($cacheId);
        if ($principal !== false) {
            return $principal;
        }
        
        $principal = null;
        
        list($prefix, $id) = \Tine20\DAV\URLUtil::splitPath($path);
        
        // special handling for calendar proxy principals
        // they are groups in the user namespace
        if (in_array($id, array('calendar-proxy-read', 'calendar-proxy-write'))) {
            $path = $prefix;
            
            // set prefix to calendar-proxy-read or calendar-proxy-write
            $prefix = $id;
            
            list(, $id) = \Tine20\DAV\URLUtil::splitPath($path);
        }
        
        switch ($prefix) {
            case 'calendar-proxy-read':
                return null;
                
                break;
                
            case 'calendar-proxy-write':
                // does the account exist
                $contactPrincipal = $this->getPrincipalByPath(self::PREFIX_USERS . '/' . $id);
                
                if (! $contactPrincipal) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                            __METHOD__ . '::' . __LINE__ . ' Account principal does not exist: ' . $id);
                    return null;
                }
                
                $principal = array(
                    'uri'                     => $contactPrincipal['uri'] . '/' . $prefix,
                    '{' . \Tine20\CalDAV\Plugin::NS_CALDAV . '}calendar-user-type'  => 'GROUP',
                    '{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}record-type' => 'groups'
                );
                
                break;
                
            case self::PREFIX_GROUPS:
            case self::PREFIX_INTELLIGROUPS:
                if (0 === strpos($id, 'role-')) {
                    try {
                        $role = Tinebase_Acl_Roles::getInstance()->getRoleById(substr($id, 5));
                    } catch(Tinebase_Exception_NotFound $tenf) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                            Tinebase_Core::getLogger()->notice(
                                __METHOD__ . '::' . __LINE__ . ' Role(group) principal does not exist: ' . $id);
                        }
                        return null;
                    }

                    $principal = $this->_roleToPrincipal($role, $prefix);

                } else {
                    $filter = new Addressbook_Model_ListFilter(array(
                        array(
                            'field' => 'type',
                            'operator' => 'equals',
                            'value' => Addressbook_Model_List::LISTTYPE_GROUP
                        ),
                        array(
                            'field' => 'id',
                            'operator' => 'equals',
                            'value' => $id
                        ),
                    ));

                    $list = Addressbook_Controller_List::getInstance()->search($filter)->getFirstRecord();

                    if (!$list) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                            Tinebase_Core::getLogger()->notice(
                                __METHOD__ . '::' . __LINE__ . ' Group/list principal does not exist: ' . $id);
                        }
                        return null;
                    }

                    $principal = $this->_listToPrincipal($list, $prefix);
                }
                
                break;
                
            case self::PREFIX_USERS:
                if ($id === self::SHARED) {
                    $principal = $this->_contactForSharedPrincipal();
                    
                } else {
                    $filter = $this->_getContactFilterForUserContact($id);
                    
                    $contact = Addressbook_Controller_Contact::getInstance()->search($filter)->getFirstRecord();
                    
                    if (! $contact) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                            __METHOD__ . '::' . __LINE__ . ' Contact principal does not exist: ' . $id);
                        return null;
                    }
                    
                    $principal = $this->_contactToPrincipal($contact);
                }
                
                break;
        }
        
        Tinebase_Core::getCache()->save($principal, $cacheId, array(), /* 1 minute */ 60);
        
        return $principal;
    }
    
    /**
     * get contact filter
     * 
     * @param string $id
     * @return Addressbook_Model_ContactFilter
     */
    protected function _getContactFilterForUserContact($id = null)
    {
        $filterData = array(
            array('field'=> 'type', 'operator'=> 'equals', 'value' => Addressbook_Model_Contact::CONTACTTYPE_USER),
            array('field'=> 'showDisabled', 'operator'=> 'equals', 'value' =>
                Addressbook_Model_ContactHiddenFilter::SHOW_HIDDEN_ACTIVE),
        );
        
        if ($id !== null) {
            $filterData[] = array(
                'field'     => 'id',
                'operator'  => 'equals',
                'value'     => $id
            );
        }
        
        return new Addressbook_Model_ContactFilter($filterData);
    }
    
    /**
     * (non-PHPdoc)
     * @see Tine20\DAVACL\IPrincipalBackend::getGroupMemberSet()
     */
    public function getGroupMemberSet($principal) 
    {
        $result = array();
        
        list($prefix, $id) = \Tine20\DAV\URLUtil::splitPath($principal);
        
        // special handling for calendar proxy principals
        // they are groups in the user namespace
        if (in_array($id, array('calendar-proxy-read', 'calendar-proxy-write'))) {
            $path = $prefix;
            
            // set prefix to calendar-proxy-read or calendar-proxy-write
            $prefix = $id;
            
            list(, $id) = \Tine20\DAV\URLUtil::splitPath($path);
        }
        
        switch ($prefix) {
            case 'calendar-proxy-read':
                return array();
                
            case 'calendar-proxy-write':
                $applications = array(
                    'Calendar' => 'Calendar_Model_Event',
                    'Tasks'    => 'Tasks_Model_Task'
                );
                
                foreach ($applications as $application => $model) {
                    if ($id === self::SHARED) {
                        // check if account has the right to run the calendar at all
                        if (!Tinebase_Acl_Roles::getInstance()->hasRight($application, Tinebase_Core::getUser()->getId(), Tinebase_Acl_Rights::RUN)) {
                            continue;
                        }
                        
                        // collect all users which have access to any of the calendars of this user
                        $sharedContainerSync = Tinebase_Container::getInstance()->getSharedContainer(Tinebase_Core::getUser(), $model, [Tinebase_Model_Grants::GRANT_SYNC, Tinebase_Model_Grants::GRANT_READ], false, true);
                        
                        if ($sharedContainerSync->count() > 0) {
                            $result = array_merge(
                                $result,
                                $this->_containerGrantsToPrincipals($sharedContainerSync));
                        }
                    } else {
                        $filter = $this->_getContactFilterForUserContact($id);
                        
                        $contact = Addressbook_Controller_Contact::getInstance()->search($filter)->getFirstRecord();
                        
                        if (!$contact instanceof Addressbook_Model_Contact || !$contact->account_id) {
                            continue;
                        }
                        
                        // check if account has the right to run the calendar at all
                        if (!Tinebase_Acl_Roles::getInstance()->hasRight($application, $contact->account_id, Tinebase_Acl_Rights::RUN)) {
                            continue;
                        }
                        
                        // collect all users which have access to any of the calendars of this user
                        $personalContainerSync = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), $model, $contact->account_id, Tinebase_Model_Grants::GRANT_SYNC);
                        
                        if ($personalContainerSync->count() > 0) {
                            $personalContainerRead = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), $model, $contact->account_id, Tinebase_Model_Grants::GRANT_READ);
                            
                            $personalContainerIds = array_intersect($personalContainerSync->getArrayOfIds(), $personalContainerRead->getArrayOfIds());
                            
                            $result = array_merge(
                                $result,
                                $this->_containerGrantsToPrincipals($personalContainerSync->filter('id', $personalContainerIds))
                            );
                        }
                    }
                }
                
                break;
                
            case self::PREFIX_GROUPS:
            case self::PREFIX_INTELLIGROUPS:
                if (0 === strpos($id, 'role-')) {
                    $accounts = array();
                    $groups = array();
                    foreach (Tinebase_Acl_Roles::getInstance()->getRoleMembers(substr($id, 5)) as $roleMember) {
                        if (Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP === $roleMember['account_type']) {
                            $groups[] = $roleMember['account_id'];
                        } elseif (Tinebase_Acl_Rights::ACCOUNT_TYPE_USER === $roleMember['account_type']) {
                            $accounts[] = $roleMember['account_id'];
                        } else {
                            Tinebase_Core::getLogger()->err($id .
                                ' has a member of unknown type: ' . print_r($roleMember));
                            continue;
                        }
                    }

                    if (count($groups) > 0) {
                        /** @var Tinebase_Model_Group $group */
                        foreach (Tinebase_Group::getInstance()->getMultiple($groups) as $group) {
                            if (empty($group->list_id) ||
                                    $group->visibility !== Tinebase_Model_Group::VISIBILITY_DISPLAYED) {
                                continue;
                            }
                            $result =
                                array_merge($result, $this->_resolveListToUserPrincipals($group->list_id));
                        }
                    }


                    if (count($accounts) > 0) {
                        /** @var Tinebase_Model_FullUser $user */
                        foreach (Tinebase_User::getInstance()->getMultiple($accounts, 'Tinebase_Model_FullUser') as $user) {
                            if (Tinebase_Model_User::VISIBILITY_DISPLAYED !== $user->visibility ||
                                    Tinebase_Model_User::ACCOUNT_STATUS_DISABLED === $user->accountStatus) {
                                continue;
                            }
                            $result[] = self::PREFIX_USERS . '/' . $user->contact_id;
                        }
                    }

                    $result = array_unique($result);

                } else {
                    $result = $this->_resolveListToUserPrincipals($id);
                }
                
                break;
        }
        
        return $result;
    }

    /**
     * @param string $id
     * @return array
     */
    protected function _resolveListToUserPrincipals($id)
    {
        $filter = new Addressbook_Model_ListFilter(array(
            array(
                'field' => 'type',
                'operator' => 'equals',
                'value' => Addressbook_Model_List::LISTTYPE_GROUP
            ),
            array(
                'field' => 'id',
                'operator' => 'equals',
                'value' => $id
            ),
        ));

        $list = Addressbook_Controller_List::getInstance()->search($filter)->getFirstRecord();

        if (!$list) {
            return array();
        }

        $result = array();
        foreach ($list->members as $member) {
            $result[] = self::PREFIX_USERS . '/' . $member;
        }
        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see Tine20\DAVACL\IPrincipalBackend::getGroupMembership()
     */
    public function getGroupMembership($principal)
    {
        $result = array();
        
        list($prefix, $contactId) = \Tine20\DAV\URLUtil::splitPath($principal);
        
        switch ($prefix) {
            case self::PREFIX_GROUPS:
                // @TODO implement?
                break;
        
            case self::PREFIX_USERS:
                if ($contactId !== self::SHARED) {
                    $classCacheId = $principal . '::' . static::$_showHiddenGroups;
                    
                    try {
                        return Tinebase_Cache_PerRequest::getInstance()->load(__CLASS__, __FUNCTION__, $classCacheId);
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        // continue...
                    }
                    
                    $cacheId = __FUNCTION__ . sha1($classCacheId);
                    
                    // try to load from cache
                    $cache  = Tinebase_Core::getCache();
                    $result = $cache->load($cacheId);
                    
                    if ($result !== FALSE) {
                        Tinebase_Cache_PerRequest::getInstance()->save(__CLASS__, __FUNCTION__, $classCacheId, $result);
                        
                        return $result;
                    }
                    $result = array();
                    
                    $user = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('contactId', $contactId);
                    
                    $groupIds = Tinebase_Group::getInstance()->getGroupMemberships($user);
                    $groups   = Tinebase_Group::getInstance()->getMultiple($groupIds);
                    $oldListAclCheck = Addressbook_Controller_List::getInstance()->doContainerACLChecks(false);
                    try {
                        $lists = Addressbook_Controller_List::getInstance()->getMultiple(array_filter($groups->list_id), true);
                    } finally {
                        Addressbook_Controller_List::getInstance()->doContainerACLChecks($oldListAclCheck);
                    }

                    /** @var Tinebase_Model_Group $group */
                    foreach ($groups as $group) {
                        if ($group->list_id && (static::$_showHiddenGroups || $group->visibility == Tinebase_Model_Group::VISIBILITY_DISPLAYED) &&
                                false !== $lists->getIndexById($group->list_id)) {
                            $result[] = self::PREFIX_GROUPS . '/' . $group->list_id;
                        }
                    }

                    $roleIds = Tinebase_Acl_Roles::getInstance()->getRoleMemberships($user->getId());
                    //$roles   = Tinebase_Acl_Roles::getInstance()->getMultiple($roleIds);

                    foreach ($roleIds as $role) {
                        $result[] = self::PREFIX_GROUPS . '/role-' . $role;
                    }
                    
                    if (Tinebase_Core::getUser()->hasRight('Calendar', Tinebase_Acl_Rights::RUN)) {
                        // return users only, if they have the sync AND read grant set
                        $otherUsers = Tinebase_Container::getInstance()->getOtherUsers($user, 'Calendar', array(Tinebase_Model_Grants::GRANT_SYNC, Tinebase_Model_Grants::GRANT_READ), false, true);
                        /** @var Tinebase_Model_FullUser $u */
                        foreach ($otherUsers as $u) {
                            if ($u->contact_id) {
                                $result[] = self::PREFIX_USERS . '/' . $u->contact_id . '/calendar-proxy-write';
                            }
                        }
                        
                        // return containers only, if the user has the sync AND read grant set
                        $sharedContainers = Tinebase_Container::getInstance()->getSharedContainer($user, Calendar_Model_Event::class, array(Tinebase_Model_Grants::GRANT_SYNC, Tinebase_Model_Grants::GRANT_READ), false, true);
                        
                        if ($sharedContainers->count() > 0) {
                            $result[] = self::PREFIX_USERS . '/' . self::SHARED . '/calendar-proxy-write';
                        }
                    }
                    Tinebase_Cache_PerRequest::getInstance()->save(__CLASS__, __FUNCTION__, $classCacheId, $result);
                    $cache->save($result, $cacheId, array(), 60 * 3);
                }
                
                break;
        }
        
        return $result;
    }
    
    public function setGroupMemberSet($principal, array $members) 
    {
        // do nothing
    }
    
    public function updatePrincipal($path, $mutations)
    {
        return false;
    }
    
    /**
     * This method is used to search for principals matching a set of
     * properties.
     *
     * This search is specifically used by RFC3744's principal-property-search
     * REPORT. You should at least allow searching on
     * http://sabredav.org/ns}email-address.
     *
     * The actual search should be a unicode-non-case-sensitive search. The
     * keys in searchProperties are the WebDAV property names, while the values
     * are the property values to search on.
     *
     * If multiple properties are being searched on, the search should be
     * AND'ed.
     *
     * This method should simply return an array with full principal uri's.
     *
     * If somebody attempted to search on a property the backend does not
     * support, you should simply return 0 results.
     *
     * You can also just return 0 results if you choose to not support
     * searching at all, but keep in mind that this may stop certain features
     * from working.
     *
     * @param string $prefixPath
     * @param array $searchProperties
     * @todo implement handling for shared pseudo user
     * @return array
     */
    public function searchPrincipals($prefixPath, array $searchProperties)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' path: ' . $prefixPath . ' properties: ' . print_r($searchProperties, true));
        
        $principalUris = array();
        
        switch ($prefixPath) {
            case self::PREFIX_GROUPS:
            case self::PREFIX_INTELLIGROUPS:
                $filter = new Addressbook_Model_ListFilter(array(
                    array(
                        'field'     => 'type',
                        'operator'  => 'equals',
                        'value'     => Addressbook_Model_List::LISTTYPE_GROUP
                    )
                ));
                
                if (!empty($searchProperties['{http://calendarserver.org/ns/}search-token'])) {
                    $filter->addFilterGroup($filter->createFilter(array(
                        'field'     => 'query',
                        'operator'  => 'contains',
                        'value'     => $searchProperties['{http://calendarserver.org/ns/}search-token']
                    )));
                }
                
                if (!empty($searchProperties['{DAV:}displayname'])) {
                    $filter->addFilter($filter->createFilter(array(
                        'field'     => 'name',
                        'operator'  => 'contains',
                        'value'     => $searchProperties['{DAV:}displayname']
                    )));
                }
                
                $result = Addressbook_Controller_List::getInstance()->search($filter, null, false, true);
                
                foreach ($result as $listId) {
                    $principalUris[] = $prefixPath . '/' . $listId;
                }

                $filter = null;
                if (!empty($searchProperties['{http://calendarserver.org/ns/}search-token'])) {
                    $filter = new Tinebase_Model_RoleFilter(array(array(
                        'field'     => 'query',
                        'operator'  => 'contains',
                        'value'     => $searchProperties['{http://calendarserver.org/ns/}search-token']
                    )));
                }

                if (!empty($searchProperties['{DAV:}displayname'])) {
                    if (null === $filter) {
                        $filter = new Tinebase_Model_RoleFilter(array());
                    }
                    $filter->addFilter($filter->createFilter(array(
                        'field'     => 'name',
                        'operator'  => 'contains',
                        'value'     => $searchProperties['{DAV:}displayname']
                    )));
                }

                if (null !== $filter) {
                    foreach (Tinebase_Acl_Roles::getInstance()->search($filter, null, false, true) as $roleId) {
                        $principalUris[] = $prefixPath . '/role-' . $roleId;
                    }
                }

                break;
                
            case self::PREFIX_USERS:
                $filter = $this->_getContactFilterForUserContact();
                
                if (!empty($searchProperties['{http://calendarserver.org/ns/}search-token'])) {
                    $filter->addFilterGroup($filter->createFilter(array(
                        'field'     => 'query',
                        'operator'  => 'contains',
                        'value'     => $searchProperties['{http://calendarserver.org/ns/}search-token']
                    )));
                }
                
                if (!empty($searchProperties['{http://sabredav.org/ns}email-address'])) {
                    $filter->addFilterGroup($filter->createFilter(array(
                        'field'     => 'email_query',
                        'operator'  => 'contains',
                        'value'     => $searchProperties['{http://sabredav.org/ns}email-address']
                    )));
                }
                
                if (!empty($searchProperties['{DAV:}displayname'])) {
                    $filter->addFilterGroup($filter->createFilter(array(
                        'field'     => 'query',
                        'operator'  => 'contains',
                        'value'     => $searchProperties['{DAV:}displayname']
                    )));
                }
                
                if (!empty($searchProperties['{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name'])) {
                    $filter->addFilter($filter->createFilter(array(
                        'field'     => 'n_given',
                        'operator'  => 'contains',
                        'value'     => $searchProperties['{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name']
                    )));
                }
                
                if (!empty($searchProperties['{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name'])) {
                    $filter->addFilter($filter->createFilter(array(
                        'field'     => 'n_family',
                        'operator'  => 'contains',
                        'value'     => $searchProperties['{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name']
                    )));
                }
                
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
                    ' path: ' . $prefixPath . ' properties: ' . print_r($filter->toArray(), true));
                
                $result = Addressbook_Controller_Contact::getInstance()->search($filter, null, false, true);
                
                foreach ($result as $contactId) {
                    $principalUris[] = $prefixPath . '/' . $contactId;
                }
                
                break;
        }
        
        return $principalUris;
    }
    
    /**
     * return shared pseudo principal (principal for the shared containers) 
     */
    protected function _contactForSharedPrincipal()
    {
        $translate = Tinebase_Translation::getTranslation('Tinebase');
        
        $principal = array(
            'uri'                     => self::PREFIX_USERS . '/' . self::SHARED,
            '{DAV:}displayname'       => $translate->_('Shared folders'),
            
            '{' . \Tine20\CalDAV\Plugin::NS_CALDAV . '}calendar-user-type'  => 'INDIVIDUAL',
            
            '{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}record-type' => 'users',
            '{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name'  => 'Folders',
            '{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name'   => 'Shared'
        );
        
        return $principal;
        
    }
    
    /**
     * convert contact model to principal array
     * 
     * @param Addressbook_Model_Contact $contact
     * @return array
     */
    protected function _contactToPrincipal(Addressbook_Model_Contact $contact)
    {
        $principal = array(
            'uri'                     => self::PREFIX_USERS . '/' . $contact->getId(),
            '{DAV:}displayname'       => $contact->n_fileas,
            '{DAV:}alternate-URI-set' => array('urn:uuid:' . $contact->getId()),
            
            '{' . \Tine20\CalDAV\Plugin::NS_CALDAV . '}calendar-user-type'  => 'INDIVIDUAL',
            
            '{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}record-type' => 'users',
            '{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name'  => $contact->n_given,
            '{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name'   => $contact->n_family
        );
        
        if (!empty(Tinebase_Core::getUser()->accountEmailAddress)) {
            $principal['{http://sabredav.org/ns}email-address'] = $contact->email;
        }
        
        return $principal;
    }
    
    /**
     * convert container grants to principals 
     * 
     * @param Tinebase_Record_RecordSet $containers
     * @return array
     * 
     * @todo improve algorithm to fetch all contact/list_ids at once
     */
    protected function _containerGrantsToPrincipals(Tinebase_Record_RecordSet $containers)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Converting grants to principals for ' . count($containers) . ' containers.');
        
        $result = array();

        /** @var Tinebase_Model_Container $container */
        foreach ($containers as $container) {
            $cacheId = Tinebase_Helper::convertCacheId('_containerGrantsToPrincipals' . $container->getId() . $container->seq);
            
            $containerPrincipals = Tinebase_Core::getCache()->load($cacheId);
            
            if ($containerPrincipals === false) {
                $containerPrincipals = array();
                
                $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($container);
                
                foreach ($grants as $grant) {
                    switch ($grant->account_type) {
                        case Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP:
                            try {
                                $group = Tinebase_Group::getInstance()->getGroupById($grant->account_id);
                                if ($group->list_id) {
                                    $containerPrincipals[] = self::PREFIX_GROUPS . '/' . $group->list_id;
                                }
                            } catch (Tinebase_Exception_Record_NotDefined $ternd) {
                                // skip group
                                continue 2;
                            } catch (Tinebase_Exception_NotFound $tenf) {
                                // skip group
                                continue 2;
                            }
                            break;

                        case Tinebase_Acl_Rights::ACCOUNT_TYPE_USER:
                            // skip if grant belongs to the owner of the calendar
                            if ($container->owner_id == $grant->account_id) {
                                continue 2;
                            }
                            try {
                                $user = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $grant->account_id);
                                if ($user->contact_id) {
                                    $containerPrincipals[] = self::PREFIX_USERS . '/' . $user->contact_id;
                                }
                            } catch (Tinebase_Exception_Record_NotDefined $ternd) {
                                // skip group
                                continue 2;
                            } catch (Tinebase_Exception_NotFound $tenf) {
                                // skip user
                                continue 2;
                            }
                            
                            break;

                        case Tinebase_Acl_Rights::ACCOUNT_TYPE_ROLE:
                            try {
                                $role = Tinebase_Acl_Roles::getInstance()->getRoleById($grant->account_id);
                                $containerPrincipals[] = self::PREFIX_GROUPS . '/role-' . $role->id;
                            } catch (Tinebase_Exception_NotFound $tenf) {
                                // skip role
                                continue 2;
                            }
                            break;
                    }
                }
                
                Tinebase_Core::getCache()->save($containerPrincipals, $cacheId, array(), /* 1 day */ 24 * 60 * 60);
            }
            
            $result = array_merge($result, $containerPrincipals);
        }
        
        // users and groups can be duplicate
        $result = array_unique($result);
        
        return $result;
    }
    
    /**
     * convert list model to principal array
     * 
     * @param Addressbook_Model_List $list
     * @param string $prefix
     * @return array
     */
    protected function _listToPrincipal(Addressbook_Model_List $list, $prefix)
    {
        $calUserType = $prefix == self::PREFIX_INTELLIGROUPS ? 'INTELLIGROUP' : 'GROUP';

        $principal = array(
            'uri'                     => $prefix . '/' . $list->getId(),
            '{DAV:}displayname'       => $list->name . ' (' . $translation = Tinebase_Translation::getTranslation('Calendar')->_('Group') . ')',
            '{DAV:}alternate-URI-set' => array('urn:uuid:' . $prefix . '/' . $list->getId()),
            
            '{' . \Tine20\CalDAV\Plugin::NS_CALDAV . '}calendar-user-type'  => $calUserType,
            
            '{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}record-type' => 'groups',
            '{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name'  => Tinebase_Translation::getTranslation('Calendar')->_('Group'),
            '{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name'   => $list->name,
        );

        if ($calUserType == 'INTELLIGROUP') {
            // OSX needs an email adress to send the attendee
            $principal['{http://sabredav.org/ns}email-address'] = 'urn:uuid:' . $prefix . '/' . $list->getId();
        }

        return $principal;
    }

    /**
     * convert role model to principal array
     *
     * @param Tinebase_Model_Role $role
     * @param string $prefix
     * @return array
     */
    protected function _roleToPrincipal(Tinebase_Model_Role $role, $prefix)
    {
        $calUserType = $prefix == self::PREFIX_INTELLIGROUPS ? 'INTELLIGROUP' : 'GROUP';

        $principal = array(
            'uri'                     => $prefix . '/role-' . $role->getId(),
            '{DAV:}displayname'       => $role->name . ' (' . $translation = Tinebase_Translation::getTranslation('Calendar')->_('Group') . ')',
            '{DAV:}alternate-URI-set' => array('urn:uuid:' . $prefix . '/role-' . $role->getId()),

            '{' . \Tine20\CalDAV\Plugin::NS_CALDAV . '}calendar-user-type'  => $calUserType,

            '{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}record-type' => 'groups',
            '{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name'  => Tinebase_Translation::getTranslation('Calendar')->_('Group'),
            '{' . \Tine20\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name'   => $role->name,
        );

        if ($calUserType == 'INTELLIGROUP') {
            // OSX needs an email adress to send the attendee
            $principal['{http://sabredav.org/ns}email-address'] = 'urn:uuid:' . $prefix . '/role-' . $role->getId();
        }

        return $principal;
    }
}

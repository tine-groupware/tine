<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * contact controller for Addressbook
 *
 * @package     Addressbook
 * @subpackage  Controller
 */
class Addressbook_Controller_List extends Tinebase_Controller_Record_Abstract
{
    /**
     * @var null|Tinebase_Backend_Sql
     */
    protected $_memberRolesBackend = null;

    /**
     * application backend class
     *
     * @var Addressbook_Backend_List
     */
    protected $_backend;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_resolveCustomFields = true;
        $this->_backend = new Addressbook_Backend_List();
        $this->_modelName = Addressbook_Model_List::class;
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
     * holds the instance of the singleton
     *
     * @var Addressbook_Controller_List
     */
    private static $_instance = NULL;

    public function getMemberRolesBackend()
    {
        if ($this->_memberRolesBackend === null) {
            $this->_memberRolesBackend = new Tinebase_Backend_Sql(array(
                'tableName' => 'adb_list_m_role',
                'modelName' => 'Addressbook_Model_ListMemberRole',
            ));
        }

        return $this->_memberRolesBackend;
    }

    /**
     * the singleton pattern
     *
     * @return Addressbook_Controller_List
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Addressbook_Controller_List();
        }

        return self::$_instance;
    }

    public static function destroyInstance()
    {
        self::$_instance = null;
    }

    /**
     * get by id
     *
     * @param string $_id
     * @param int $_containerId
     * @param bool         $_getRelatedData
     * @param bool $_getDeleted
     * @return Addressbook_Model_List
     * @throws Tinebase_Exception_AccessDenied
     */
    public function get($_id, $_containerId = NULL, $_getRelatedData = TRUE, $_getDeleted = FALSE, $_aclProtect = true)
    {
        $result = new Tinebase_Record_RecordSet('Addressbook_Model_List',
            array(parent::get($_id, $_containerId, $_getRelatedData, $_getDeleted, $_aclProtect)));
        $this->_removeHiddenListMembers($result);
        $this->_setAccountOnly($result);
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $result->getFirstRecord();
    }

    /**
     * use contact search to remove hidden list members
     *
     * @param Tinebase_Record_RecordSet $lists
     */
    protected function _removeHiddenListMembers($lists)
    {
        if (count($lists) === 0 || Addressbook_Config::getInstance()
                ->featureEnabled(Addressbook_Config::FEATURE_MAILINGLIST)) {
            return;
        }

        $allMemberIds = array();
        foreach ($lists as $list) {
            if (is_array($list->members)) {
                $allMemberIds = array_merge($list->members, $allMemberIds);
            }
        }
        $allMemberIds = array_unique($allMemberIds);

        if (empty($allMemberIds)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' No members found.');
            return;
        }

        $allVisibleMemberIds = Addressbook_Controller_Contact::getInstance()->search(new Addressbook_Model_ContactFilter(array(array(
            'field' => 'id',
            'operator' => 'in',
            'value' => $allMemberIds
        ))), NULL, FALSE, TRUE);

        $hiddenMemberids = array_diff($allMemberIds, $allVisibleMemberIds);

        if (count($hiddenMemberids) > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Found ' . count($hiddenMemberids) . ' hidden members, removing them');
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . print_r($hiddenMemberids, TRUE));

            foreach ($lists as $list) {
                // use array_values to make sure we have numeric index starting with 0 again
                $list->members = array_values(array_diff($list->members, $hiddenMemberids));
            }
        }
    }

    /**
     * set account_only property of list
     *
     * @param Tinebase_Record_RecordSet $lists
     *
     * TODO maybe we should fetch this via sql join
     */
    protected function _setAccountOnly($lists)
    {
        foreach ($lists as $list) {
            if ($list->type === Addressbook_Model_List::LISTTYPE_GROUP && $list->group_id) {
                try {
                    $group = Tinebase_Group::getInstance()->getGroupById($list->group_id);
                    $list->account_only = $group->account_only;
                } catch (Tinebase_Exception_Record_NotDefined $ternd) {
                    Tinebase_Exception::log($ternd);
                }
            } else {
                $list->account_only = false;
            }
        }
    }

    /**
     * @see Tinebase_Controller_Record_Abstract::search()
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param bool $_getRelations
     * @param bool $_onlyIds
     * @param string $_action
     * @return array|Tinebase_Record_RecordSet
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $result = parent::search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action);

        if ($_onlyIds !== true) {
            $this->_removeHiddenListMembers($result);
        }

        return $result;
    }

    /**
     * @see Tinebase_Controller_Record_Abstract::getMultiple()
     *
     * @param array $_ids
     * @param bool $_ignoreACL
     * @param null|Tinebase_Record_Expander $_expander
     * @param bool $_getDeleted
     * @return Tinebase_Record_RecordSet
     */
    public function getMultiple($_ids, $_ignoreACL = false, Tinebase_Record_Expander $_expander = null, $_getDeleted = false)
    {
        $result = parent::getMultiple($_ids, $_ignoreACL, $_expander, $_getDeleted);
        if (true !== $_ignoreACL) {
            $this->_removeHiddenListMembers($result);
        }
        return $result;
    }

    /**
     * add new members to list
     *
     * @param  mixed $_listId
     * @param  mixed $_newMembers
     * @param  boolean $_addToGroup
     * @return Addressbook_Model_List
     */
    public function addListMember($_listId, $_newMembers, $_addToGroup = true)
    {
        try {
            $list = $this->get($_listId);
        } catch (Tinebase_Exception_AccessDenied $tead) {
            $this->_fixEmptyContainerId($_listId);
            $list = $this->get($_listId);
        }

        $this->_checkGrant($list, 'update', TRUE, 'No permission to add list member.');
        $this->_checkGroupGrant($list, TRUE, 'No permission to add list member.');

        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            $list = $this->_backend->addListMember($_listId, $_newMembers);

            if (true === $_addToGroup && ! empty($list->group_id)) {
                foreach (Tinebase_Record_RecordSet::getIdsFromMixed($_newMembers) as $userId) {
                    Admin_Controller_Group::getInstance()->addGroupMember($list->group_id, $userId, false);
                }
            }

            if (isset($list->xprops()[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST]) &&
                    $list->xprops()[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST]) {
                Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback(function($list) {
                    try {
                        Felamimail_Sieve_AdbList::setScriptForList($list);
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' .
                            __LINE__ . ' ' . $tenf->getMessage());
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' .
                            __LINE__ . ' List: ' . print_r($list->toArray(), true));
                    }
                }, [$list]);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }

        return $this->get($list->getId());
    }

    protected function _checkGroupGrant($_list, $_throw = false, $_msg = '')
    {
        if (! empty($_list->group_id) && Admin_Controller_Group::getInstance()->doRightChecks()) {
            if (!Tinebase_Core::getUser()->hasRight('Admin', Admin_Acl_Rights::MANAGE_ACCOUNTS)) {
                if ($_throw) {
                    throw new Tinebase_Exception_AccessDenied($_msg);
                } else {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * fixes empty container ids / perhaps this can be removed later as all lists should have a container id!
     *
     * @param  mixed $_listId
     * @return Addressbook_Model_List
     */
    protected function _fixEmptyContainerId($_listId)
    {
        /** @var Addressbook_Model_List $list */
        $list = $this->_backend->get($_listId);

        if (empty($list->container_id)) {
            $list->container_id = Addressbook_Controller::getDefaultInternalAddressbook();
            $list = $this->_backend->update($list);
        }

        return $list;
    }

    /**
     * remove members from list
     *
     * @param  mixed $_listId
     * @param  mixed $_removeMembers
     * @param  boolean $_removeFromGroup
     * @return Addressbook_Model_List
     */
    public function removeListMember($_listId, $_removeMembers, $_removeFromGroup = true)
    {
        $list = $this->get($_listId);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' Removing members '
            . ' from list ' . $list->getId());

        $this->_checkGrant($list, 'update', TRUE, 'No permission to remove list member.');
        $this->_checkGroupGrant($list, TRUE, 'No permission to remove list member.');

        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            $list = $this->_backend->removeListMember($_listId, $_removeMembers);

            if (true === $_removeFromGroup && ! empty($list->group_id)) {
                foreach (Tinebase_Record_RecordSet::getIdsFromMixed($_removeMembers) as $userId) {
                    Admin_Controller_Group::getInstance()->removeGroupMember($list->group_id, $userId, false);
                }
            }

            if (isset($list->xprops()[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST]) &&
                $list->xprops()[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST]) {
                Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback(function($list) {
                    Felamimail_Sieve_AdbList::setScriptForList($list);
                }, [$list]);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }

        return $this->get($list->getId());
    }

    /**
     * flatten members array to contact ids
     *
     * @param Addressbook_Model_List $list
     */
    protected function _flattenMembers(Addressbook_Model_List $list)
    {
        if (empty($list->members)) return;
        $members = $list->members;
        foreach ($members as &$member) {
            if (is_array($member)) {
                $member = $member['id'];
            }
        }
        $list->members = $members;
    }
    /**
     * inspect creation of one record
     *
     * @param  Tinebase_Record_Interface $_record
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $this->_flattenMembers($_record);

        if (isset($_record->type) && $_record->type == Addressbook_Model_List::LISTTYPE_GROUP) {
            if (empty($_record->group_id)) {
                throw new Tinebase_Exception_UnexpectedValue('group_id is empty, must not happen for list type group');
            }

            // check rights
            $this->_checkGroupGrant($_record, TRUE, 'can not add list of type ' . Addressbook_Model_List::LISTTYPE_GROUP);

            // check if group is there, if not => not found exception
            Admin_Controller_Group::getInstance()->get($_record->group_id);
        }

        if (! empty($_record->email)) {
            $this->_checkEmailAddress($_record->email);
        }
    }

    /**
     * inspect creation of one record (after create)
     *
     * @param   Tinebase_Record_Interface $_createdRecord
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        /** @var Addressbook_Model_List $_createdRecord */
        $this->_fireChangeListeEvent($_createdRecord, $_record);

        if (isset($_createdRecord->xprops()[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST]) &&
                $_createdRecord->xprops()[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST] &&
                preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $_createdRecord->email)) {

            $this->_createMailAccount($_createdRecord);
        }
    }

    /**
     * inspect update of one record
     *
     * @param   Tinebase_Record_Interface $_record the update record
     * @param   Tinebase_Record_Interface $_oldRecord the current persistent record
     * @return  void
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        Tinebase_Record_PersistentObserver::getInstance()->fireEvent(new Addressbook_Event_InspectListBeforeUpdate([
            'observable' => $_record,
            'oldList' => $_oldRecord,
        ]));

        $this->_flattenMembers($_record);

        if (! empty($_record->email) && $_record->email !== $_oldRecord->email) {
            $this->_checkEmailAddress($_record->email);
        }

        if (! empty($_record->group_id)) {
            $group = Tinebase_Group::getInstance()->getGroupById($_record->group_id);

            if ($group->account_only) {
                $this->_checkNonAccountMembers($_record);
            }
            $this->_updateGroup($group, $_record, $_oldRecord);
            $this->_updateGroupMembers($group, $_record, $_oldRecord);
        }
    }

    /**
     * add / remove system group members
     *
     * @param Tinebase_Model_Group $group
     * @param Addressbook_Model_List $list
     * @param Addressbook_Model_List $oldList
     */
    protected function _updateGroupMembers(Tinebase_Model_Group $group, Addressbook_Model_List $list, Addressbook_Model_List $oldList)
    {
        // get all system contacts
        $checks = Addressbook_Controller_Contact::getInstance()->doContainerACLChecks(false);
        $systemcontacts = Addressbook_Controller_Contact::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
                ['field' => 'id', 'operator' => 'in', 'value' => $list->members],
                ['field' => 'type', 'operator' => 'equals', 'value' => Addressbook_Model_Contact::CONTACTTYPE_USER],
            ]
        ));
        Addressbook_Controller_Contact::getInstance()->doContainerACLChecks($checks);

        $groupMembers = Tinebase_Group::getInstance()->getGroupMembers($group->getId());

        $removeListMembers = [];
        foreach ($systemcontacts as $contact) {
            if (! Tinebase_Core::getUser()->hasRight('Admin', Admin_Acl_Rights::MANAGE_ACCOUNTS) &&
                ! in_array($contact->getId(), $oldList->members)
            ) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                    __METHOD__ . '::' . __LINE__ . ' no right to update group members - remove contact (id: '
                    . $contact->getId() . ') from list members');
                $removeListMembers[] = $contact->getId();
            } else if (! in_array($contact->account_id, $groupMembers)) {
                $groupMembers[] = $contact->account_id;
                Admin_Controller_Group::getInstance()->addGroupMember($group->getId(), $contact->account_id, false);
            }
        }

        $list->members = array_diff($list->members, $removeListMembers);

        if (Tinebase_Core::getUser()->hasRight('Admin', Admin_Acl_Rights::MANAGE_ACCOUNTS)) {
            $removeGroupMembers = array_diff($groupMembers, $systemcontacts->account_id);
            foreach ($removeGroupMembers as $account_id) {
                Admin_Controller_Group::getInstance()->removeGroupMember($group->getId(), $account_id, false);
            }
        }
    }

    protected function _checkNonAccountMembers($_record)
    {
        $contacts = Addressbook_Controller_Contact::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
                ['field' => 'id', 'operator' => 'in', 'value' => $_record->members]
            ]
        ));
        $nonUserContacts = $contacts->filter('type', Addressbook_Model_Contact::CONTACTTYPE_CONTACT);
        if (count($nonUserContacts) > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Found non-account members: '
                . print_r($nonUserContacts->toArray(), true));
            $translate = Tinebase_Translation::getTranslation('Addressbook');
            throw new Tinebase_Exception_SystemGeneric($translate->_(
                'It is not allowed to add non-account contacts to this list'));
        }
    }

    protected function _updateGroup($group, $_record, $_oldRecord)
    {
        // check if something changed that requires special rights
        $changeGroup = false;
        foreach (Addressbook_Model_List::getManageAccountFields() as $field) {
            if ($_record->{$field} != $_oldRecord->{$field}) {
                $changeGroup = true;
                break;
            }
        }

        // then do the update, the group controller will check manage accounts right
        if ($changeGroup) {
            $groupController = Admin_Controller_Group::getInstance();

            foreach (Addressbook_Model_List::getManageAccountFields() as $field) {
                $group->{$field} = $_record->{$field};
            }
            try {
                $groupController->update($group, false);
            }catch (Tinebase_Exception_AccessDenied $e) {
                    throw new Addressbook_Exception_AccessDenied('This is a system group. To edit this group you need the Admin.ManageAccounts right.');
            }
        }
    }

    /**
     * check list email address
     *
     * - throw exception if another system user or group is found with this address
     *   or the domain mismatches
     *
     * @param string $email
     * @throws Tinebase_Exception_Backend_Database
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _checkEmailAddress($email)
    {
        // already used (check contacts & lists)
        $backendAndFilter = [
            'contact' => [
                'backend' => new Addressbook_Backend_Sql(),
                'filter' => Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                    Addressbook_Model_Contact::class, [
                    ['field' => 'email_query', 'operator' => 'equals', 'value' => $email],
                    ['field' => 'type', 'operator' => 'equals', 'value' => Addressbook_Model_Contact::CONTACTTYPE_USER],
                ])
            ],
            'list' => [
                'backend' => new Addressbook_Backend_List(),
                'filter' => Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                    Addressbook_Model_List::class, [
                    ['field' => 'email', 'operator' => 'equals', 'value' => $email],
                    ['field' => 'type', 'operator' => 'equals', 'value' => Addressbook_Model_List::LISTTYPE_GROUP],
                ])
            ],
        ];
        foreach ($backendAndFilter as $toCheck) {
            $result = $toCheck['backend']->search($toCheck['filter'], null, true);
            if (count($result) > 0) {
                $translation = Tinebase_Translation::getTranslation($this->_applicationName);
                throw new Tinebase_Exception_SystemGeneric($translation->_('E-Mail address is already given. Please choose another one.'));
            }
        }

        Tinebase_EmailUser::checkDomain($email);
    }

    protected function _createMailAccount(Addressbook_Model_List $_list)
    {
        $mailAccount = new Felamimail_Model_Account([
            'user_id'       => $_list->getId(),
            'email'         => $_list->email,
            'name'          => $_list->email,
            'from'          => $_list->email,
            'type'          => Felamimail_Model_Account::TYPE_ADB_LIST,
            'password'      => Tinebase_Record_Abstract::generateUID(),
        ]);

        Felamimail_Controller_Account::getInstance()->create($mailAccount);
    }

    /**
     * @param Addressbook_Model_List $_list
     * @return NULL|Felamimail_Model_Account
     */
    protected function _getMailAccount($_list)
    {
        // if user has right for list, he/she is also allowed to see/change the mail account
        $backend = new Felamimail_Backend_Account();
        return $backend->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Felamimail_Model_Account::class, [
            ['field' => 'user_id', 'operator' => 'equals', 'value' => $_list->getId()],
            ['field' => 'type',    'operator' => 'equals', 'value' => Felamimail_Model_Account::TYPE_ADB_LIST],
        ]))->getFirstRecord();
    }

    /**
     * inspect update of one record (after update)
     *
     * @param   Addressbook_Model_List $updatedRecord   the just updated record
     * @param   Addressbook_Model_List $record          the update record
     * @param   Addressbook_Model_List $currentRecord   the current record (before update)
     * @return  void
     */
    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        $this->_fireChangeListeEvent($updatedRecord, $record, $currentRecord);

        if (isset($updatedRecord->xprops()[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST]) &&
                $updatedRecord->xprops()[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST] &&
                preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $updatedRecord->email)) {

            if (isset($currentRecord->xprops()[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST]) &&
                    $currentRecord->xprops()[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST] &&
                    preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $currentRecord->email)) {

                // do diff, check password etc ?
                if ($updatedRecord->email !== $currentRecord->email) {
                    if (null === ($mailAccount = $this->_getMailAccount($updatedRecord))) {
                        $this->_createMailAccount($updatedRecord);
                    } else {
                        $mailAccount->email = $updatedRecord->email;
                        $mailAccount->name = $updatedRecord->email;
                        $mailAccount->from = $updatedRecord->email;
                        Felamimail_Controller_Account::getInstance()->update($mailAccount);
                    }
                }
            } else {
                $this->_createMailAccount($updatedRecord);
            }

            Tinebase_TransactionManager::getInstance()->registerAfterCommitCallback(function($list) {
                try {
                    Felamimail_Sieve_AdbList::setScriptForList($list);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                        __METHOD__ . '::' . __LINE__ . ' ' . $tenf->getMessage());
                }
            }, [$updatedRecord]);

        } elseif (isset($currentRecord->xprops()[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST]) &&
                $currentRecord->xprops()[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST] &&
                preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $currentRecord->email)) {

            $account = $this->_getMailAccount($currentRecord);
            if ($account !== null) {
                Felamimail_Controller_Account::getInstance()->delete($account->getId());
            }
        }
    }

    /**
     * fireChangeListeEvent
     *
     * @param Addressbook_Model_List $list
     * @param Addressbook_Model_List|null $currentList
     */
    protected function _fireChangeListeEvent(Addressbook_Model_List $list, Addressbook_Model_List $record = null, Addressbook_Model_List $currentList = null)
    {
        $event = new Addressbook_Event_ChangeList();
        $event->list = $list;
        $event->listRecord = $record;
        $event->currentList = $currentList;
        Tinebase_Event::fireEvent($event);
    }

    /**
     * inspects delete action
     *
     * @param array $_ids
     * @return array of ids to actually delete
     */
    protected function _inspectDelete(array $_ids)
    {
        $lists = $this->getMultiple($_ids);
        foreach ($lists as $list) {
            if (! $this->_disabledEvents && ! $this->fireDeleteEvent($list)) {
                $key = array_search($list->getId(), $_ids);
                unset($_ids[$key]);
                continue;
            }

            $this->_deleteMailingListAccount($list);
        }

        return $_ids;
    }

    public function fireDeleteEvent(Addressbook_Model_List $list): bool
    {
        $event = new Addressbook_Event_DeleteList();
        $event->list = $list;
        return Tinebase_Event::fireEvent($event);
    }

    protected function _deleteMailingListAccount(Addressbook_Model_List $list)
    {
        if (isset($list->xprops()[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST])
            && $list->xprops()[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST]
            && preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $list->email)) {
            $mailAccount = $this->_getMailAccount($list);
            if ($mailAccount) {
                Felamimail_Controller_Account::getInstance()->delete($mailAccount->getId());
            }
        }
    }

    /**
     * create or update list in addressbook sql backend
     *
     * @param  Tinebase_Model_Group $group
     * @return Addressbook_Model_List
     */
    public function createOrUpdateByGroup(Tinebase_Model_Group $group)
    {
        try {
            if (empty($group->list_id)) {
                $list = $this->_backend->getByGroupName($group->name, $group->container_id);
                if (!$list) {
                    // jump to catch block => no list_id provided and no existing list for group found
                    throw new Tinebase_Exception_NotFound('list_id is empty');
                }
                $group->list_id = $list->getId();
            } else {
                try {
                    $list = $this->_backend->get($group->list_id);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    $list = $this->_backend->getByGroupName($group->name, $group->container_id);
                    if (!$list) {
                        // jump to catch block => bad list_id provided and no existing list for group found
                        throw new Tinebase_Exception_NotFound('list_id is empty');
                    }
                }
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Update list ' . $group->name);

            $upList = clone $list;
            $upList->name = $group->name;
            $upList->description = $group->description;
            $upList->email = $group->email;
            $upList->type = Addressbook_Model_List::LISTTYPE_GROUP;
            $upList->container_id = (empty($group->container_id)) ?
                Addressbook_Controller::getDefaultInternalAddressbook() : $group->container_id;
            $this->_updateListMembersFromGroup($group, $upList);
            $upList->xprops = $group->xprops;

            // add modlog info
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($upList, 'update', $upList);

            if (! empty($upList->email) && $list->email !== $upList->email) {
                $this->_checkEmailAddress($upList->email);
            }

            if (is_array($upList->container_id) && isset($upList->container_id['id'])) {
                $upList->container_id = $upList->container_id['id'];
            }
            $upList = $this->_backend->update($upList);
            $this->_inspectAfterUpdate($upList, $upList, $list);
            $this->_writeModLog($upList, $list);
            $list = $this->get($upList->getId());

        } catch (Tinebase_Exception_NotFound $tenf) {
            $list = $this->createByGroup($group);
            $group->list_id = $list->getId();

            $this->_inspectAfterCreate($list, $list);
            $this->_writeModLog($list, null);
        }

        return $list;
    }

    /**
     * add / remove system group members
     *
     * @param Tinebase_Model_Group $group
     * @param Addressbook_Model_List $list
     */
    protected function _updateListMembersFromGroup(Tinebase_Model_Group $group, Addressbook_Model_List $list)
    {
        // get all non-system contacts
        $checks = Addressbook_Controller_Contact::getInstance()->doContainerACLChecks(false);
        $nonsystemcontactIds = Addressbook_Controller_Contact::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Addressbook_Model_Contact::class, [
                ['field' => 'id', 'operator' => 'in', 'value' => $list->members],
                ['field' => 'type', 'operator' => 'equals', 'value' => Addressbook_Model_Contact::CONTACTTYPE_CONTACT],
            ]
        ))->getArrayOfIds();
        Addressbook_Controller_Contact::getInstance()->doContainerACLChecks($checks);

        $systemcontacts = (isset($group->members)) ? $this->_getContactIds($group->members) : array();
        $list->members = array_merge($nonsystemcontactIds, $systemcontacts);
    }

    /**
     * create new list by group
     *
     * @param Tinebase_Model_Group $group
     * @return Addressbook_Model_List
     */
    protected function createByGroup($group)
    {
        $list = new Addressbook_Model_List(array(
            'name' => $group->name,
            'description' => $group->description,
            'email' => $group->email,
            'type' => Addressbook_Model_List::LISTTYPE_GROUP,
            'container_id' => (empty($group->container_id)) ? Addressbook_Controller::getDefaultInternalAddressbook()
                : $group->container_id,
            'members' => (isset($group->members)) ? $this->_getContactIds($group->members) : array(),
            'xprops' => $group->xprops,
        ));

        if (! empty($list->email)) {
            $this->_checkEmailAddress($list->email);
        }

        // add modlog info
        Tinebase_Timemachine_ModificationLog::setRecordMetaData($list, 'create');

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Add new list ' . $group->name);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' ' . print_r($list->toArray(), TRUE));

        /** @var Addressbook_Model_List $list */
        $list = $this->_backend->create($list);

        $group->list_id = $list->getId();

        return $list;
    }

    /**
     * get contact_ids of users
     *
     * @param  array $_userIds
     * @return array
     */
    protected function _getContactIds($_userIds)
    {
        $contactIds = array();

        if (empty($_userIds)) {
            return $contactIds;
        }

        $contactBackend = new Addressbook_Backend_Sql();

        foreach ($_userIds as $userId) {
            try {
                $user = Tinebase_User::getInstance()->getUserByPropertyFromBackend('accountId', $userId);
                if (!empty($user->contact_id)) {
                    try {
                        $contact = $contactBackend->get($user->contact_id);
                        $contactIds[] = $contact->getId();
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                            __METHOD__ . '::' . __LINE__ . ' Removing member from list: ' . $tenf->getMessage());
                    }
                }
            } catch (Tinebase_Exception_NotFound $tenf) {}
        }

        return $contactIds;
    }

    /**
     * you can define default filters here
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     */
    protected function _addDefaultFilter(Tinebase_Model_Filter_FilterGroup $_filter = NULL)
    {
        if (!$_filter->isFilterSet('showHidden')) {
            $hiddenFilter = $_filter->createFilter('showHidden', 'equals', FALSE);
            /** @noinspection PhpDeprecationInspection */
            $hiddenFilter->setIsImplicit(TRUE);
            $_filter->addFilter($hiddenFilter);
        }
    }

    /**
     * set relations / tags / alarms
     *
     * @param   Tinebase_Record_Interface $updatedRecord the just updated record
     * @param   Tinebase_Record_Interface $record the update record
     * @param   Tinebase_Record_Interface $currentRecord   the original record if one exists
     * @param   boolean                   $returnUpdatedRelatedData
     * @param   boolean $isCreate
     * @return  Tinebase_Record_Interface
     */
    protected function _setRelatedData(Tinebase_Record_Interface $updatedRecord, Tinebase_Record_Interface $record, Tinebase_Record_Interface $currentRecord = null, $returnUpdatedRelatedData = false, $isCreate = false)
    {
        /** @var Addressbook_Model_List $record */
        if (isset($record->memberroles)) {
            $this->setMemberRoles($record, $updatedRecord->getId());
        }

        return parent::_setRelatedData($updatedRecord, $record, $currentRecord, $returnUpdatedRelatedData, $isCreate);
    }

    /**
     * set member roles for list
     *
     * @param Addressbook_Model_List $list
     * @param string $listId
     * @throws Tinebase_Exception_InvalidArgument
     *
     * TODO add generic helper fn for migration handling?
     */
    public function setMemberRoles(Addressbook_Model_List $list, $listId = null)
    {
        $memberrolesToSet = (!$list->memberroles instanceof Tinebase_Record_RecordSet)
            ? new Tinebase_Record_RecordSet(
                'Addressbook_Model_ListMemberRole',
                is_array($list->memberroles) ? $list->memberroles : [],
                /* $_bypassFilters */ true
            ) : $list->memberroles;

        // sanitize ids and check constraints
        $listConstraints = [];
        foreach ($memberrolesToSet as $memberrole) {
            foreach (array('contact_id', 'list_role_id', 'list_id') as $field) {
                if (isset($memberrole[$field]['id'])) {
                    $memberrole[$field] = $memberrole[$field]['id'];
                }
            }
            $this->_checkMaxMemberConstraint($listConstraints, $memberrole['list_role_id']);
        }

        $currentMemberroles = $this->getMemberRoles($list);
        $diff = $currentMemberroles->diff($memberrolesToSet);
        if (count($diff['added']) > 0) {
            $diff['added']->list_id = $listId ?? $list->getId();
            foreach ($diff['added'] as $memberrole) {
                $this->getMemberRolesBackend()->create($memberrole);
            }
        }
        if (count($diff['removed']) > 0) {
            $this->getMemberRolesBackend()->delete($diff['removed']->getArrayOfIds());
        }
    }

    /**
     * @param array $listConstraints
     * @param string $listRoleId
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _checkMaxMemberConstraint(&$listConstraints, $listRoleId)
    {
        if (! isset($listConstraints[$listRoleId])) {
            $listConstraints[$listRoleId] = [
                'listRole' => Addressbook_Controller_ListRole::getInstance()->get($listRoleId),
                'count' => 1,
            ];
        } else {
            $listConstraints[$listRoleId]['count']++;
        }

        /** var Addressbook_Model_ListRole $listConstraints[$listRoleId]['listRole'] */
        $maxMembers = $listConstraints[$listRoleId]['listRole']->{Addressbook_Model_ListRole::FLD_MAX_MEMBERS};
        if ($maxMembers && $listConstraints[$listRoleId]['count'] > $maxMembers) {
            $translate = Tinebase_Translation::getTranslation($this->_applicationName);
            throw new Tinebase_Exception_SystemGeneric($translate->_('Maximum number of role members reached'));
        }
    }

    /**
     * add related data to record
     *
     * @param Addressbook_Model_List $record
     */
    protected function _getRelatedData($record)
    {
        $memberRoles = $this->getMemberRoles($record);
        if (count($memberRoles) > 0) {
            $record->memberroles = $memberRoles;
        }
        parent::_getRelatedData($record);
    }

    /**
     * @param Addressbook_Model_List $record
     * @return Tinebase_Record_RecordSet|Addressbook_Model_ListMemberRole
     */
    public function getMemberRoles($record)
    {
        $result = $this->getMemberRolesBackend()->getMultipleByProperty($record->getId(), 'list_id');
        return $result;
    }

    /**
     * get all lists given contact is member of
     *
     * @param Addressbook_Model_Contact|string $contact
     * @return array
     */
    public function getMemberships($contact)
    {
        return $this->_backend->getMemberships($contact);
    }

    /**
     * set system notes
     *
     * @param   Tinebase_Record_Interface $_updatedRecord   the just updated record
     * @param   string $_systemNoteType
     * @param   Tinebase_Record_RecordSet $_currentMods
     */
    protected function _setSystemNotes($_updatedRecord, $_systemNoteType = Tinebase_Model_Note::SYSTEM_NOTE_NAME_CREATED, $_currentMods = NULL)
    {
        $resolvedMods = $_currentMods;
        if (null !== $_currentMods && Tinebase_Model_Note::SYSTEM_NOTE_NAME_CHANGED === $_systemNoteType) {
            $resolvedMods = new Tinebase_Record_RecordSet(Tinebase_Model_ModificationLog::class, array());
            /** @var Tinebase_Model_ModificationLog $mod */
            foreach ($_currentMods as $mod) {
                $diff = new Tinebase_Record_Diff(json_decode($mod->new_value, true));
                foreach ($diff->xprops('diff') as $attribute => &$value) {
                    if ('members' === $attribute) {
                        $this->_resolveMembersForNotes($value, $diff->xprops('oldData')['members']);
                    }
                }
                $newMod = clone $mod;
                $newMod->new_value = json_encode($diff->toArray());
                $resolvedMods->addRecord($newMod);
            }
        }
        parent::_setSystemNotes($_updatedRecord, $_systemNoteType, $resolvedMods);
    }

    protected function _resolveMembersForNotes(&$currentMembers, &$oldMembers)
    {
        $contactIds = array();
        if (!empty($currentMembers)) {
            $contactIds = array_merge($contactIds, $currentMembers);
        }
        if (!empty($oldMembers)) {
            $contactIds = array_merge($contactIds, $oldMembers);
        }
        $contactIds = array_unique($contactIds);
        $contacts = Addressbook_Controller_Contact::getInstance()->getMultiple($contactIds);

        if (is_array($currentMembers)) {
            foreach ($currentMembers as &$val) {
                /** @var Addressbook_Model_Contact $contact */
                if (false !== ($contact = $contacts->getById($val))) {
                    $val = $contact->getTitle();
                }
            }
        }

        if (is_array($oldMembers)) {
            foreach ($oldMembers as &$val) {
                /** @var Addressbook_Model_Contact $contact */
                if (false !== ($contact = $contacts->getById($val))) {
                    $val = $contact->getTitle();
                }
            }
        }
    }
}

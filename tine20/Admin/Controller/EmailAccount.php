<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2019-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * EmailAccount Controller for Admin application
 *
 * just a wrapper for Felamimail_Controller_Account with additional admin acl
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller_EmailAccount extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * application backend class
     *
     * @var Felamimail_Controller_Account
     */
    protected $_backend;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    protected function __construct()
    {
        $this->_applicationName       = 'Admin';
        $this->_modelName             = Admin_Model_EmailAccount::class;
        $this->_purgeRecords          = false;

        // we need to avoid that anybody else gets this instance ... as it has acl turned off!
        Felamimail_Controller_Account::destroyInstance();
        $this->_backend = Felamimail_Controller_Account::getInstance();
        $this->_backend->doContainerACLChecks(false);
        // unset internal reference to prevent others to get instance without acl
        Felamimail_Controller_Account::destroyInstance();
    }

    public static function destroyInstance()
    {
        // also destroy backend instance if set
        Felamimail_Controller_Account::destroyInstance();
        self::$_instance = null;
    }

    /**
     * get by id
     *
     * @param string $_id
     * @param int $_EmailAccountId
     * @param bool         $_getRelatedData
     * @param bool $_getDeleted
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_AccessDenied
     */
    public function get($_id, $_EmailAccountId = NULL, $_getRelatedData = TRUE, $_getDeleted = FALSE, $_aclProtect = true)
    {
        $this->_checkRight('get');
//        $record = new Admin_Model_EmailAccount(
//            parent::get($_id, $_EmailAccountId, $_getRelatedData, $_getDeleted, $_aclProtect)->toArray()
//        );
        $record = $this->_backend->get($_id);
        $this->resolveAccountEmailUsers($record);
        return $record;
    }

    public function getMultiple($_ids, $_ignoreACL = false, ?\Tinebase_Record_Expander $_expander = null, $_getDeleted = false)
    {
        $this->_checkRight('get');
        $records = new Tinebase_Record_RecordSet(Admin_Model_EmailAccount::class,
            parent::getMultiple($_ids, $_ignoreACL, $_expander, $_getDeleted)->toArray()
        );
        $this->resolveAccountEmailUsers($records);
        return $records;
    }

    /**
     * get list of records
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @param boolean $_getRelations
     * @param boolean $_onlyIds
     * @param string $_action for right/acl check
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(?\Tinebase_Model_Filter_FilterGroup $_filter = NULL, ?\Tinebase_Model_Pagination $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $this->_checkRight('get');

        $result = null;
        while (Tinebase_EmailUser::manages(Tinebase_Config::IMAP) && ('email_imap_user' === $_pagination?->sort  || ['email_imap_user'] === $_pagination?->sort)) {
            $emailUserBackend = null;
            try {
                $emailUserBackend = Tinebase_EmailUser::getInstance();
            } catch (Tinebase_Exception_NotFound) {}
            if (!$emailUserBackend instanceof Tinebase_EmailUser_Sql) {
                break;
            }

            $extIdToFelamiAccount = [];

            $systemFilter = clone $_filter;
            $systemFilter->andWrapItself();
            $systemFilter->addFilter(new Tinebase_Model_Filter_Text('type', Tinebase_Model_Filter_Abstract::OP_EQUALS, Felamimail_Model_Account::TYPE_SYSTEM));
            $systemIds = $this->_backend->search($systemFilter, _onlyIds: ['id', 'user_id']);
            $userToFelamiAccount = array_flip($systemIds);
            foreach (Tinebase_User::getInstance()->getUsersXprops(array_values($systemIds)) as $userId => $xprops) {
                $emailUserId = Tinebase_EmailUser_XpropsFacade::getEmailUserId(new Tinebase_Model_FullUser([
                    'id' => $userId,
                    'xprops' => $xprops,
                ], true));
                $extIdToFelamiAccount[$emailUserId] = $userToFelamiAccount[$userId];
            }
            unset($userToFelamiAccount);

            $userFilter = clone $_filter;
            $userFilter->andWrapItself();
            $userFilter->addFilter(new Tinebase_Model_Filter_Text('type', 'notin', [Felamimail_Model_Account::TYPE_SYSTEM, Felamimail_Model_Account::TYPE_SHARED_EXTERNAL, Felamimail_Model_Account::TYPE_USER_EXTERNAL]));
            foreach ($this->_backend->search($userFilter, _onlyIds: ['id', 'xprops']) as $id => $xprops) {
                $emailUserId = Tinebase_EmailUser_XpropsFacade::getEmailUserId(new Felamimail_Model_Account([
                    'id' => $id,
                    'xprops' => $xprops,
                ], true));
                $extIdToFelamiAccount[$emailUserId] = $id;
            }


            if (is_array($dir = $_pagination->dir)) {
                $dir = $dir[0];
            }

            $sortedIds = [];
            foreach(array_keys($emailUserBackend->getUserIdsMailSize(array_keys($extIdToFelamiAccount), $dir ?: 'ASC')) as $extId) {
                $sortedIds[] = $extIdToFelamiAccount[$extId];
            }

            $allIds = $this->_backend->search($_filter, _onlyIds: true);

            if (count($sortedIds) < count($allIds)) {
                $toProcessIds = array_diff($allIds, $sortedIds);
                if ('DESC' === $dir) {
                    $sortedIds = array_merge($sortedIds, $toProcessIds);
                } else {
                    $sortedIds = array_merge($toProcessIds, $sortedIds);
                }
            }
            $sortedIds = array_slice($sortedIds, $_pagination->start ?: 0, $_pagination->limit ?: null);

            $unsortedResult = $this->_backend->getMultiple($sortedIds);
            $result = new Tinebase_Record_RecordSet(Felamimail_Model_Account::class);
            foreach ($sortedIds as $uid) {
                if ($record = $unsortedResult->getById($uid)) {
                    $result->addRecord($record);
                }
            }

            break;
        }

        if (null === $result) {
            $result = $this->_backend->search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action);
        }

        if (! $_onlyIds) {
            // we need to unset the accounts grants to make the admin grid actions work for all accounts
            $result->account_grants = null;
            $this->resolveAccountEmailUsers($result);
        }

        return $result;
    }

    /**
     * Gets total count of search with $_filter
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action for right/acl check
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get'): int
    {
        $this->_checkRight('get');

        return $this->_backend->searchCount($_filter, $_action);
    }

    /**
     * Return array with total count of search with $_filter and additional sum / search count columns
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action for right/acl check
     * @return array
     */
    public function searchCountSum(Tinebase_Model_Filter_FilterGroup $_filter,
                                   string $_action = Tinebase_Controller_Record_Abstract::ACTION_GET): array
    {
        return ['totalcount' => $this->searchCount($_filter, $_action)];
    }

    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   boolean $_duplicateCheck
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function create(Tinebase_Record_Interface $_record, $_duplicateCheck = true)
    {
        $this->_checkRight('create');

        try {
            $account = $this->_backend->create($_record);
        } catch (Zend_Db_Statement_Exception $zdse) {
            if (Tinebase_Exception::isDbDuplicate($zdse)) {
                $translation = Tinebase_Translation::getTranslation($this->_applicationName);
                throw new Tinebase_Exception_SystemGeneric($translation->_('Email address is already in use'));
            } else {
                throw $zdse;
            }
        }
        $this->_inspectAfterCreate($account, $_record);
        
        return $account;
    }
    
    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   array $_additionalArguments
     * @return  Tinebase_Record_Interface
     */
    public function update(Tinebase_Record_Interface $_record, $_additionalArguments = array(), $_updateDeleted = false)
    {
        $this->_checkRight('update');

        $currentAccount = $this->get($_record->getId(), null, true, $_updateDeleted);

        $raii = false;
        if (Tinebase_EmailUser::backendSupportsMasterPassword($_record)) {
            $raii = Tinebase_EmailUser::prepareAccountForSieveAdminAccess($_record->getId());
        }

        try {
            $this->_inspectBeforeUpdate($_record, $currentAccount);
            $account = $this->_backend->update($_record);
            $this->_inspectAfterUpdate($account, $_record, $currentAccount);
        } finally {
            if ($raii && Tinebase_EmailUser::backendSupportsMasterPassword($_record)) {
                Tinebase_EmailUser::removeAdminAccess();
                unset($raii);
            }
        }

        return $account;
    }

    /**
     * inspect creation of one record (after create)
     *
     * @param   Felamimail_Model_Account $_createdRecord
     * @param   Felamimail_Model_Account $_record
     * @return  void
     */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        if ($_createdRecord->type !== Felamimail_Model_Account::TYPE_USER_EXTERNAL) {
            $this->updateAccountEmailUsers($_record);
            $this->resolveAccountEmailUsers($_createdRecord);
        }
        Felamimail_Controller_Account::getInstance()->checkEmailAccountContact($_createdRecord);
    }

    /**
     * inspect update of one record
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        // if user of email account changes and if migration checkbox is checked, it needs to be unchecked
        if ($_record->user_id !== $_oldRecord->user_id && $_record->type === Felamimail_Model_Account::TYPE_USER_EXTERNAL) {
            $_record->migration_approved = false;
        }
        
        if ($_record->email !== $_oldRecord->email && $_record->type === Felamimail_Model_Account::TYPE_SYSTEM) {
            // change user email address
            $user = Admin_Controller_User::getInstance()->get($_record->user_id);
            $user->accountEmailAddress = $_record->email;
            Admin_Controller_User::getInstance()->update($user);
        }
    }

    /**
     * inspect update of one record (after update)
     *
     * @param   Felamimail_Model_Account $updatedRecord   the just updated record
     * @param   Felamimail_Model_Account $record          the update record
     * @param   Felamimail_Model_Account $currentRecord   the current record (before update)
     * @return  void
     */
    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        if ($record->type !== Felamimail_Model_Account::TYPE_USER_EXTERNAL) {
            $this->updateAccountEmailUsers($record);
        }
        
        if ($currentRecord->type === Felamimail_Model_Account::TYPE_SYSTEM
            && (   $this->_backend->doConvertToShared($updatedRecord, $currentRecord, false)
                || $this->_backend->doConvertToUserInternal($updatedRecord, $currentRecord, false)
            )
        ) {
            // update user (don't delete email account!)
            $userId = is_array($currentRecord->user_id) ? $currentRecord->user_id['accountId'] :  $currentRecord->user_id;
            $user = Admin_Controller_User::getInstance()->get($userId);
            $user->accountEmailAddress = '';
            // remove xprops from user
            Tinebase_EmailUser_XpropsFacade::setXprops($user, null, false);
            Admin_Controller_User::getInstance()->updateUserWithoutEmailPluginUpdate($user);
        } else {
            $this->resolveAccountEmailUsers($updatedRecord);
        }
        if ($record->type === Felamimail_Model_Account::TYPE_ADB_LIST
            && Tinebase_Core::getUser()->hasRight(Addressbook_Config::APP_NAME, Addressbook_Acl_Rights::MANAGE_LIST_EMAIL_OPTIONS)
        ) {
            $updatedRecord['adb_list'] = $this->updateAdbList($record);
        }
    }

    /**
     * Deletes a set of records.
     * 
     * If one of the records could not be deleted, no record is deleted
     * 
     * @param   array array of record identifiers
     * @return void
     */
    public function delete($_ids)
    {
        $this->_checkRight('delete');

        Felamimail_Controller_Account::getInstance()->deleteEmailAccountContact($_ids, true);
        $records = $this->_backend->delete($_ids);
        foreach ($records as $record) {
            if ($record->type === Tinebase_EmailUser_Model_Account::TYPE_ADB_LIST) {
                $event = new Admin_Event_DeleteMailingList();
                $event->listId = $record->user_id;
                Tinebase_Event::fireEvent($event);
            }
        }
    }

    /**
     * check if user has the right to manage EmailAccounts
     * 
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight($_action)
    {
        switch ($_action) {
            case 'get':
                $this->checkRight(Admin_Acl_Rights::VIEW_EMAILACCOUNTS);
                break;
            case 'create':
            case 'update':
            case 'delete':
                $this->checkRight(Admin_Acl_Rights::MANAGE_EMAILACCOUNTS);
                break;
            default;
               break;
        }

        parent::_checkRight($_action);
    }

    /**
     * @param Tinebase_Model_User|string|null $user
     * @return Felamimail_Model_Account|null
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function getSystemAccount($user)
    {
        return $this->_backend->getSystemAccount($user);
    }

    /**
     * @param Felamimail_Model_Account $account
     */
    public function updateAccountEmailUsers(Felamimail_Model_Account $account): void
    {
        $this->checkRight('MANAGE_ACCOUNTS');

        // set emailUserId im xprops if not set
        if (! Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS}) {
            return;
        }

        if ($account->isExternalAccount()) {
            return;
        }

        if (isset($account['email_imap_user'])) {
            $emailUserBackend = Tinebase_EmailUser::getInstance();
            if (method_exists($emailUserBackend, 'updateUser')) {
                $fullUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($account);
                $newFullUser = clone($fullUser);
                $emailUserBackend->updateUser($fullUser, $newFullUser);
            }
        }

        if (isset($account['email_smtp_user'])) {
            $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP);
            if (method_exists($emailUserBackend, 'updateUser')) {
                $fullUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($account);
                $newFullUser = clone($fullUser);

                $emailUserBackend->updateUser($fullUser, $newFullUser);
            }
        }
    }

    /**
     * set emailUserId im xprops if not set
     *
     * @param Tinebase_Record_RecordSet|Felamimail_Model_Account $_records
     */
    public function resolveAccountEmailUsers($_records)
    {
        if (! Tinebase_Config::getInstance()->{Tinebase_Config::EMAIL_USER_ID_IN_XPROPS}) {
            return;
        }

        $_records = $_records instanceof Tinebase_Record_RecordSet ? $_records : [$_records];

        foreach ($_records as $_record) {
            if ($_record->isExternalAccount()) {
                continue;
            }

            if (!isset($_record->xprops()[Felamimail_Model_Account::XPROP_EMAIL_USERID_IMAP])) {
                try {
                    $user = Tinebase_User::getInstance()->getFullUserById($_record->user_id);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                        __METHOD__ . '::' . __LINE__ . ' ' . $tenf);
                    continue;
                }
                if (!isset($user->xprops()[Tinebase_Model_FullUser::XPROP_EMAIL_USERID_IMAP])) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                        __METHOD__ . '::' . __LINE__ . ' User has no XPROP_EMAIL_USERID_IMAP ...');
                    continue;
                }
                Tinebase_EmailUser_XpropsFacade::setXprops($_record,
                    $user->xprops()[Tinebase_Model_FullUser::XPROP_EMAIL_USERID_IMAP], false);
            }

            $fullUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($_record);

            if (Tinebase_EmailUser::manages(Tinebase_Config::IMAP)) {
                $emailUserBackend = Tinebase_EmailUser::getInstance();
                if (method_exists($emailUserBackend, 'getEmailuser')) {
                    $_record->email_imap_user = $emailUserBackend->getEmailuser($fullUser)->toArray();
                }
            }

            if (Tinebase_EmailUser::manages(Tinebase_Config::SMTP)) {
                $smtpUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP);
                if (method_exists($smtpUserBackend, 'getEmailuser')) {
                    $_record->email_smtp_user = $smtpUserBackend->getEmailuser($fullUser)->toArray();
                }
            }
        }
    }

    /**
     * @param ?mixed $mailAccounts
     * @param bool $dryRun
     * @param bool $allowToFail
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    public function updateNotificationScripts($mailAccounts = null, bool $dryRun = false, bool $allowToFail = true): Tinebase_Record_RecordSet
    {
        if (!$mailAccounts) {
            $backend = Admin_Controller_EmailAccount::getInstance();
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Account::class, [
                ['field' => 'sieve_notification_email', 'operator' => 'not', 'value' => NULL],
                ['field' => 'type', 'operator' => 'equals', 'value' => Tinebase_EmailUser_Model_Account::TYPE_SYSTEM]
            ]);
            $mailAccounts = $backend->search($filter);
        }

        if ($dryRun) {
            return $mailAccounts;
        }
        
        $updatedAccounts = new Tinebase_Record_RecordSet(Felamimail_Model_Account::class);
        foreach ($mailAccounts as $record) {
            if (Tinebase_EmailUser::backendSupportsMasterPassword($record)) {
                $raii = Tinebase_EmailUser::prepareAccountForSieveAdminAccess($record->getId());
                try {
                    Felamimail_Controller_Sieve::getInstance()->setNotificationEmail($record->getId(),
                        $record->sieve_notification_email);
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::'
                        . __LINE__ . 'Sieve script updated from record: ' . $record->getId());
                    $updatedAccounts->addRecord($record);
                } catch (Exception $e) {
                    if ($allowToFail) {
                        Tinebase_Exception::log($e);
                    } else {
                        throw $e;
                    }
                } finally {
                    Tinebase_EmailUser::removeAdminAccess();
                    unset($raii);
                }
            }
        }
        return $updatedAccounts;
    }

    /**
     * @param ?mixed $mailAccounts
     * @param bool $dryRun
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    public function updateSieveScript($mailAccounts = null, bool $dryRun = false): Tinebase_Record_RecordSet
    {
        if (!$mailAccounts) {
            $backend = Admin_Controller_EmailAccount::getInstance();
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Account::class, [
                ['field' => 'type', 'operator' => 'equals', 'value' => Tinebase_EmailUser_Model_Account::TYPE_ADB_LIST]
            ]);
            $mailAccounts = $backend->search($filter);
        }

        if ($dryRun) {
            return $mailAccounts;
        }

        $updatedAccounts = new Tinebase_Record_RecordSet(Felamimail_Model_Account::class);
        foreach ($mailAccounts as $record) {
            $list = $this->_getListFromAccount($record);
            if (! $list) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::'
                        . __LINE__ . ' No list found for account ' . $record->getId());
                }
            } else {
                Felamimail_Sieve_AdbList::setScriptForList($list);
                $updatedAccounts->addRecord($record);
            }
        }
        return $updatedAccounts;
    }
    

    /**
     * @param Felamimail_Model_Account $account
     */
    public function updateAdbList(Felamimail_Model_Account $account): ?Tinebase_Record_Interface
    {
        $list = $account['adb_list'];
        if (!$list) {
            $list = $this->_getListFromAccount($account);
        }
        if (is_array($list)) {
            $list = new Addressbook_Model_List($list, true);
        }
        if (!$list) return null;

        if ($account->grants) {
            foreach ($account->grants as $grant) {
                if ($grant['account_id'] !== Tinebase_Core::getUser()->getId() && $grant['readGrant']) {
                    $list->xprops()[Addressbook_Model_List::XPROP_SIEVE_KEEP_COPY] = true;
                }
            }
        }
        return Addressbook_Controller_List::getInstance()->update($list);
    }

    protected function _getListFromAccount(Felamimail_Model_Account $account): ?Addressbook_Model_List
    {
        /* @var Addressbook_Model_List $list */
        $acl = Addressbook_Controller_List::getInstance()->doContainerACLChecks(false);
        $list = Addressbook_Controller_List::getInstance()->search(new Addressbook_Model_ListFilter([
            ['field' => 'id', 'operator' => 'equals', 'value' => $account->user_id]
        ]))->getFirstRecord();
        Addressbook_Controller_List::getInstance()->doContainerACLChecks($acl);
        return $list;
    }
}

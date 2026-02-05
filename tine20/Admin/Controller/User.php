<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        extend Tinebase_Controller_Record_Abstract
 */

/**
 * User Controller for Admin application
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller_User extends Tinebase_Controller_Abstract
{
    /**
     * @var Tinebase_User_Sql
     */
    protected $_userBackend = NULL;

    protected $_doRightChecks = true;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_applicationName = 'Admin';
        
        $this->_userBackend = Tinebase_User::getInstance();
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
     * @var Admin_Controller_User
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Admin_Controller_User
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller_User;
        }
        
        return self::$_instance;
    }

    public static function destroyInstance()
    {
        self::$_instance = null;
    }

    /**
     * get list of full accounts -> renamed to search full users
     *
     * @param string $_filter string to search accounts for
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_FullUser
     */
    public function searchFullUsers($_filter, $_sort = null, $_dir = 'ASC', $_start = null, $_limit = null)
    {
        $this->checkRight('VIEW_ACCOUNTS');

        $allIds = null;
        $unsortedUserIds = [];

        if ('filesystemSize' === $_sort && Tinebase_Application::getInstance()->isInstalled('Filemanager')) {
            $unsortedUserIds = $this->_userBackend->getUsersIds($_filter);
            $sortProperty = Tinebase_Config::getInstance()->{Tinebase_Config::QUOTA}
                ->{Tinebase_Config::QUOTA_INCLUDE_REVISION} ? 'revision_size' : 'size';
            $allIds = Tinebase_FileSystem::getInstance()->searchNodes(new Tinebase_Model_Tree_Node_Filter([
                ['field' => 'path', 'operator' => 'equals', 'value' => '/Filemanager/folders/personal'],
                ['field' => 'name', 'operator' => 'in', 'value' => $unsortedUserIds]
            ], _options: ['ignoreAcl' => true]), new Tinebase_Model_Pagination([
                Tinebase_Model_Pagination::FLD_SORT => $sortProperty,
                Tinebase_Model_Pagination::FLD_DIR => $_dir !== 'ASC' ? 'DESC' : $_dir,
            ]))->name;

        } elseif ('emailUser' === $_sort && Tinebase_EmailUser::manages(Tinebase_Config::IMAP)) {
            $allIds = $this->_sortByEmailUsage($_filter, $_dir);
        }

        $_start ??= 0;
        if (null !== $allIds) {
            if (count($allIds) < count($unsortedUserIds)) {
                $toProcessIds = array_diff($unsortedUserIds, $allIds);
                if ('ASC' === $_dir) {
                    $allIds = array_merge($toProcessIds, $allIds);
                } else {
                    $allIds = array_merge($allIds, $toProcessIds);
                }
            }
            $allIds = array_slice($allIds, $_start, $_limit);

            $unsortedResult = $this->_userBackend->getMultiple($allIds, Tinebase_Model_FullUser::class);
            $result = new Tinebase_Record_RecordSet(Tinebase_Model_FullUser::class);
            foreach ($allIds as $uid) {
                if ($record = $unsortedResult->getById($uid)) {
                    $result->addRecord($record);
                }
            }
        } else {
            $result = $this->_userBackend->getUsers($_filter, $_sort, $_dir, $_start, $_limit, Tinebase_Model_FullUser::class);
        }

        if (Tinebase_EmailUser::manages(Tinebase_Config::IMAP)) {
            $emailUserBackend = null;
            try {
                $emailUserBackend = Tinebase_EmailUser::getInstance();
            } catch (Tinebase_Exception_NotFound) {}

            // FIXME LDAP email backends not supported! in Tinebase_User_Plugin_LdapInterface, inspectGetUserByProperty has a second param!
            if (null !== $emailUserBackend && !$emailUserBackend instanceof Tinebase_User_Plugin_LdapInterface) {
                foreach ($result as $idx => $user) {
                    $result[$idx] = $this->get($user->getId());
                }
            }
        }
        
        return $result;
    }

    protected function _sortByEmailUsage($_filter, $_dir): ?array
    {
        $emailUserBackend = null;
        try {
            $emailUserBackend = Tinebase_EmailUser::getInstance();
        } catch (Tinebase_Exception_NotFound) {}

        if (null !== $emailUserBackend && $emailUserBackend instanceof Tinebase_EmailUser_Sql) {
            $unsortedUserIds = $this->_userBackend->getUsersIds($_filter);
            if (empty($unsortedUserIds)) {
                return null;
            }
            $userXprops = $this->_userBackend->getUsersXprops($unsortedUserIds);
            $emailUserIdAccountId = [];

            $fAccountTblName = SQL_TABLE_PREFIX . Felamimail_Controller_Account::getInstance()->getBackend()->getTableName();
            $db = Tinebase_Core::getDb();
            foreach ($db->query('SELECT id, user_id, `type`, xprops FROM ' . $fAccountTblName
                . $db->quoteInto(' WHERE `type` IN (?)', [
                    // only show / sort by system accounts
                    Felamimail_Model_Account::TYPE_SYSTEM,
                ]) . $db->quoteInto(' AND user_id IN (?)', $unsortedUserIds))->fetchAll(Zend_Db::FETCH_ASSOC) as $row) {
                if (Felamimail_Model_Account::TYPE_SYSTEM === $row['type']) {
                    $emailUserId = Tinebase_EmailUser_XpropsFacade::getEmailUserId(new Tinebase_Model_FullUser([
                        'id' => $row['user_id'],
                        'xprops' => $userXprops[$row['user_id']],
                    ], true));
                } else {
                    $emailUserId = Tinebase_EmailUser_XpropsFacade::getEmailUserId(new Felamimail_Model_Account([
                        'id' => $row['id'],
                        'xprops' => $row['xprops'],
                    ], true));
                }
                $emailUserIdAccountId[$emailUserId] = $row['user_id'];
            }
            unset($userXprops);

            $mailSizes = [];
            foreach ($emailUserBackend->getUserIdsMailSize(array_keys($emailUserIdAccountId)) as $emailUserId => $mailSize) {
                $userId = $emailUserIdAccountId[$emailUserId];
                    $mailSizes[$userId] ?? $mailSizes[$userId] = 0;
                $mailSizes[$userId] += (int)$mailSize;
            }
            unset($emailUserIdAccountId);

            $allIds = array_keys($mailSizes);
            if ('ASC' === $_dir) {
                usort($allIds, fn($a, $b)
                => $mailSizes[$a] < $mailSizes[$b] ? -1 : ($mailSizes[$a] === $mailSizes[$b] ? 0 : 1));
            } else {
                usort($allIds, fn($a, $b)
                => $mailSizes[$a] < $mailSizes[$b] ? 1 : ($mailSizes[$a] === $mailSizes[$b] ? 0 : -1));
            }
            unset($mailSizes);
            return $allIds;
        }

        return null;
    }
    
    /**
     * count users
     *
     * @param string $_filter string to search user accounts for
     * @return int total user count
     */
    public function searchCount($_filter)
    {
        $this->checkRight('VIEW_ACCOUNTS');
        
        return $this->_userBackend->getUsersCount($_filter);
    }
    
    /**
     * get account
     *
     * @param   string  $_accountId  account id to get
     * @param   bool    $_getDeleted
     * @return  Tinebase_Model_FullUser
     */
    public function get($_userId, bool $_getDeleted = false)
    {
        $this->checkRight('VIEW_ACCOUNTS');
        
        return $this->_userBackend->getUserById($_userId, 'Tinebase_Model_FullUser', $_getDeleted);
    }
    
    /**
     * set account status
     *
     * @param   string $_accountId  account id
     * @param   string $_status     status to set
     * @return  integer (0 for no change, 1 for changed status)
     */
    public function setAccountStatus($_accountId, $_status)
    {
        $this->checkRight('MANAGE_ACCOUNTS');

        $user = $this->get($_accountId);
        if ($user->accountStatus !== $_status) {
            $user->accountStatus = $_status;
            $this->update($user);
            return 1;
        }

        return 0;
    }
    
    /**
     * set the password for a given account
     *
     * @param  Tinebase_Model_FullUser  $_account the account
     * @param  string                   $_password the new password
     *
     * @param  string                   $_passwordRepeat the new password again
     * @param  bool                     $_mustChange
     * @return void
     * 
     * @todo add must change pwd info to normal tine user accounts
     */
    public function setAccountPassword(Tinebase_Model_FullUser $_account, $_password, $_passwordRepeat, $_mustChange = null)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        if ($_password != $_passwordRepeat) {
            throw new Admin_Exception("Passwords don't match.");
        }

        if ($_mustChange === null) {
            $_mustChange = $_account->password_must_change ? true : false;
        }

        $this->_userBackend->setPassword($_account, $_password, true, $_mustChange);
        if ($_account->xprops()[Tinebase_Model_FullUser::XPROP_HAS_RANDOM_PWD] ?? false) {
            unset($_account->xprops()[Tinebase_Model_FullUser::XPROP_HAS_RANDOM_PWD]);
            Tinebase_User::getInstance()->updateUserInSqlBackend($_account);
        }

        Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . 
            ' Set new password for user ' . $_account->accountLoginName . '. Must change:' . $_mustChange
        );

        // send SMS message if client data is set
        $context = Admin_Controller_User::getInstance()->getRequestContext();
        $smsPhoneNumber = $context['sms-phone-number'] ?? $context['clientData']['sms-phone-number'] ?? null;
        $customTemplate = $context['sms-new-password-template'] ?? $context['clientData']['sms-new-password-template'] ?? null;

        if (!empty($_password) && !empty($smsPhoneNumber)) {
            $smsPhoneNumber = rawurldecode($smsPhoneNumber);
            $mobilePhoneNumber = Addressbook_Model_Contact::normalizeTelephoneNum($smsPhoneNumber);

            $template = $customTemplate ? rawurldecode($customTemplate) : Tinebase_Config::getInstance()->{Tinebase_Config::SMS}
                        ->{Tinebase_Config::SMS_MESSAGE_TEMPLATES}->get(Tinebase_Config::SMS_NEW_PASSWORD_TEMPLATE);

            $twig = new Tinebase_Twig(Tinebase_Core::getLocale(), Tinebase_Translation::getTranslation(), [
                Tinebase_Twig::TWIG_LOADER =>
                    new Tinebase_Twig_CallBackLoader(__METHOD__ . 'password', time() - 1, function () use ($template) {
                        return $template;
                    })
            ]);

            $message = $twig->load(__METHOD__ . 'password')->render([
                'password'  => $_password,
                'user'      => $_account->accountDisplayName,
                'contact'   => $_account->contact_id
            ]);

            $message = str_replace("\n", '\n', $message);

            $smsSendConfig = new Tinebase_Model_Sms_SendConfig([
                Tinebase_Model_Sms_SendConfig::FLD_MESSAGE => $message,
                Tinebase_Model_Sms_SendConfig::FLD_RECIPIENT_NUMBER => $mobilePhoneNumber,
            ]);

            try {
                Tinebase_Sms::send($smsSendConfig);
                Tinebase_Core::getLogger()->info(
                    __METHOD__ . '::' . __LINE__ .
                    ' Send SMS message successfully : ' . print_r($context, true)
                );
            } catch (Zend_Session_Exception $zse) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $zse->getMessage()) ;
            }
        }
    }

    /**
     * update user
     *
     * @param  Tinebase_Model_FullUser    $_user            the user
     * @param  string                     $_password        the new password
     * @param  string                     $_passwordRepeat  the new password again
     * @throws Tinebase_Exception_Backend_Database_LockTimeout
     * @throws Exception
     *
     * @return Tinebase_Model_FullUser
     */
    public function update(Tinebase_Model_FullUser $_user, $_password = null, $_passwordRepeat = null)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        $oldUser = $this->_userBackend->getUserByProperty('accountId', $_user, 'Tinebase_Model_FullUser');
        
        if ($oldUser->accountLoginName !== $_user->accountLoginName) {
            $this->_checkLoginNameExistance($_user);
        }
        $this->_checkLoginNameLength($_user);
        $this->_checkPrimaryGroupExistance($_user);

        $this->_checkSystemEmailAccountDuplicate($_user, $oldUser);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' Update user ' . $_user->accountLoginName);

        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

            Tinebase_Timemachine_ModificationLog::setRecordMetaData($_user, 'update', $oldUser);

            $deactivated = $this->_checkAccountStatus($_user, $oldUser);
            $this->_inspectBeforeUpdateOrCreate($_user, $oldUser);
            $this->_checkExtendExpiration($_user, $oldUser);
            $user = $this->_userBackend->updateUser($_user);

            // make sure primary groups is in the list of group memberships
            $currentGroups = ! isset($_user->groups)
                ? Admin_Controller_Group::getInstance()->getGroupMemberships($user->getId())
                : $_user->groups;
            $currentGroups = $currentGroups === '' ? [] : $currentGroups;
            $groups = array_unique(array_merge(array($user->accountPrimaryGroup), $currentGroups));
            Admin_Controller_Group::getInstance()->setGroupMemberships($user, $groups);

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $e);
            
            if ($e instanceof Zend_Db_Statement_Exception && preg_match('/Lock wait timeout exceeded/', $e->getMessage())) {
                throw new Tinebase_Exception_Backend_Database_LockTimeout($e->getMessage());
            }
            
            throw $e;
        }

        if ($deactivated) {
            // TODO send this for blocked/expired, too? allow to configure this?
            Tinebase_User::getInstance()->sendDeactivationNotification($user);
        }

        // do this before throwing event because we might have to create email systemaccount/folders for mail accounts
        if (!empty($_password) && !empty($_passwordRepeat)) {
            $this->setAccountPassword($user, $_password, $_passwordRepeat, (bool)$_user->password_must_change);
        }

        // fire needed events
        $event = new Admin_Event_UpdateAccount;
        $event->account = $user;
        $event->oldAccount = $oldUser;
        $event->pwd = $_password;
        Tinebase_Event::fireEvent($event);

        $this->_updateCurrentUser($user);

        return $user;
    }

    /**
     * @param $user
     * @param $oldUser
     * @param $password
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _checkSystemEmailAccountCreation($user, $oldUser, $password)
    {
        if (! Tinebase_EmailUser::isEmailSystemAccountConfigured()) {
            return;
        }

        if ((! $oldUser || empty($oldUser->accountEmailAddress))
            && ! empty($user->accountEmailAddress)
            && empty($password)
        ) {
            $translate = Tinebase_Translation::getTranslation('Admin');
            throw new Tinebase_Exception_SystemGeneric($translate->_('Password is needed for system account creation'));
        }
    }

    /**
     * @param $user
     * @param $oldUser
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _checkSystemEmailAccountDuplicate($user, $oldUser = null)
    {
        if (! Tinebase_EmailUser::isEmailSystemAccountConfigured()) {
            return;
        }

        if ((! $oldUser || empty($oldUser->accountEmailAddress))
            && ! empty($user->accountEmailAddress) && Tinebase_EmailUser::manages(Tinebase_Config::SMTP)
        ) {
            if (! $oldUser || $oldUser->accountEmailAddress !== $user->accountEmailAddress) {
                Tinebase_EmailUser::checkIfEmailUserExists($user, Tinebase_Config::SMTP);
            }
        }
    }

    /**
     * @param Tinebase_Model_FullUser $_user
     * @return Tinebase_Model_FullUser
     * @throws Tinebase_Exception_Backend_Database_LockTimeout
     */
    public function updateUserWithoutEmailPluginUpdate(Tinebase_Model_FullUser $_user)
    {
        // remove email user plugins (add again afterwards)
        $userPlugins = $this->_userBackend->getSqlPluginNames();
        $emailPlugins = [];
        foreach ($userPlugins as $pluginName) {
            if (Tinebase_EmailUser::isEmailUserPlugin($pluginName)) {
                $emailPlugins[] = $this->_userBackend->removePlugin($pluginName);
            }
        }
        $result = $this->update($_user);
        foreach ($emailPlugins as $plugin) {
            $this->_userBackend->registerPlugin($plugin);
        }
        return $result;
    }

    /**
     * update account status if changed to enabled/disabled
     *
     * @param $_user
     * @param $_oldUser
     * @return boolean true if user is deactivated
     */
    protected function _checkAccountStatus($_user, $_oldUser)
    {
        if ($_oldUser->accountStatus !== $_user->accountStatus && in_array($_user->accountStatus, array(
                Tinebase_Model_FullUser::ACCOUNT_STATUS_ENABLED,
                Tinebase_Model_FullUser::ACCOUNT_STATUS_DISABLED,
            ))) {

            if ($_user->accountStatus === Tinebase_Model_FullUser::ACCOUNT_STATUS_ENABLED) {
                $this->_checkMaxUsers();
            }

            if ($_user->accountStatus === Tinebase_Model_FullUser::ACCOUNT_STATUS_DISABLED) {
                return true;
            }

            // check if max users are reached when a user is activated
            if ($_user->accountStatus === Tinebase_Model_FullUser::ACCOUNT_STATUS_ENABLED) {
                $this->_checkMaxUsers();
            }
        }

        return false;
    }

    /**
     * update current user in session if changed
     *
     * @param $updatedUser
     */
    protected function _updateCurrentUser($updatedUser)
    {
        $currentUser = Tinebase_Core::getUser();
        if ($currentUser->getId() === $updatedUser->getId() && Tinebase_Session::isStarted()) {
            // update current user in session!
            Tinebase_Core::set(Tinebase_Core::USER, $updatedUser);
            Tinebase_Session::getSessionNamespace()->currentAccount = $updatedUser;
        }
    }

    public function doRightChecks($setTo = NULL)
    {
        $oldValue = $this->_doRightChecks;
        if (null !== $setTo) {
            $this->_doRightChecks= $setTo;
        }
        return $oldValue;
    }

    public function checkRight($_right, $_throwException = true, $_includeTinebaseAdmin = true)
    {
        if (!$this->_doRightChecks) {
            return true;
        }
        return parent::checkRight($_right, $_throwException, $_includeTinebaseAdmin);
    }

    /**
     * create user
     *
     * @param  Tinebase_Model_FullUser  $_account           the account
     * @param  string                     $_password           the new password
     * @param  string                     $_passwordRepeat  the new password again
     * @param  boolean                    $_ignorePwdPolicy
     * @return Tinebase_Model_FullUser
     */
    public function create(Tinebase_Model_FullUser $_user, $_password, $_passwordRepeat, $_ignorePwdPolicy = false)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        // avoid forging accountId, gets created in backend
        unset($_user->accountId);
        
        $event = new Admin_Event_BeforeAddAccount([
            'account' => $_user,
        ]);
        Tinebase_Event::fireEvent($event);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' Create new user ' . $_user->accountLoginName);
        }

        if ($_password != $_passwordRepeat) {
            throw new Admin_Exception("Passwords don't match.");
        } else if (empty($_password)) {
            $_password = '';
            $_passwordRepeat = '';
        }
        if (!$_ignorePwdPolicy) {
            Tinebase_User_PasswordPolicy::checkPasswordPolicy($_password, $_user);
        }

        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

            if (!in_array($_user->accountLoginName, Tinebase_User::getSystemUsernames())) {
                $this->_checkMaxUsers();
            }
            $this->_checkLoginNameExistance($_user);
            $this->_checkLoginNameLength($_user);
            $this->_checkPrimaryGroupExistance($_user);
            $this->_checkSystemEmailAccountCreation($_user, null, $_password);
            $this->_checkSystemEmailAccountDuplicate($_user);
            $this->_inspectBeforeUpdateOrCreate($_user);

        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

            throw $tesg;
        }  catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Exception::log($e);
            throw $e;
        }

        try {
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($_user, 'create');
            
            $user = $this->_userBackend->addUser($_user);
            $user->imapUser = $_user->imapUser;

            // make sure primary groups is in the list of groupmemberships
            $groups = array_unique(array_merge(array($user->accountPrimaryGroup), (array) $_user->groups));
            Admin_Controller_Group::getInstance()->setGroupMemberships($user, $groups);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            if (! $e instanceof Tinebase_Exception_SystemGeneric) {
                Tinebase_Exception::log($e);
            }
            throw $e;
        }

        if (!empty($_password)) {
            $this->setAccountPassword($user, $_password, $_passwordRepeat);
        }

        $event = new Admin_Event_AddAccount(array(
            'account' => $user,
            'pwd'     => $_password,
        ));
        Tinebase_Event::fireEvent($event);

        return $user;
    }
    
    /**
     * checks number of allowed users
     *
     * @throws Admin_Exception
     */
    protected function _checkMaxUsers()
    {
        $translation = Tinebase_Translation::getTranslation('Admin');
        $license = Tinebase_License::getInstance();

        $licenseType = $license->getLicenseType();

        if ($licenseType === Tinebase_License::LICENSE_TYPE_LIMITED_TIME || $licenseType === Tinebase_License::LICENSE_TYPE_ON_DEMAND) {
            // no user limit
            return;
        }

        $maxUsers = $license->getMaxUsers();
        $currentUserCount = $this->_userBackend->countNonSystemUsers();
        if ($currentUserCount >= $maxUsers) {
            $message = $translation->_('Maximum number of users reached.') . ' '
                . $translation->_('Please contact Metaways Infosystems GmbH to buy a license that supports a higher number of users.');
            throw new Tinebase_Exception_SystemGeneric($message);
        }
    }

    /**
     * look for user with the same login name
     * 
     * @param Tinebase_Model_FullUser $user
     * @return boolean
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _checkLoginNameExistance(Tinebase_Model_FullUser $user)
    {
        try {
            $existing = Tinebase_User::getInstance()->getUserByLoginName($user->accountLoginName);
            if ($existing->is_deleted) {
                return true;
            }
            if ($user->getId() === NULL || $existing->getId() !== $user->getId()) {
                throw new Tinebase_Exception_SystemGeneric('Login name already exists. Please choose another one.');
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
        }
        
        return true;
    }
    
    /**
     * Check login name length
     *
     * @param Tinebase_Model_FullUser $user
     * @return boolean
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _checkLoginNameLength(Tinebase_Model_FullUser $user)
    {
        $maxLoginNameLength = Tinebase_Config::getInstance()->get(Tinebase_Config::MAX_USERNAME_LENGTH);
        if (!empty($maxLoginNameLength) && strlen((string)$user->accountLoginName) > $maxLoginNameLength) {
            throw new Tinebase_Exception_SystemGeneric('The login name exceeds the maximum length of  ' . $maxLoginNameLength . ' characters. Please choose another one.');
        }
        return TRUE;
    }
    
    /**
     * look for primary group, if it does not exist, fallback to default user group
     * 
     * @param Tinebase_Model_FullUser $user
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _checkPrimaryGroupExistance(Tinebase_Model_FullUser $user)
    {
        try {
            $group = Tinebase_Group::getInstance()->getGroupById($user->accountPrimaryGroup);
        } catch (Tinebase_Exception_Record_NotDefined $ternd) {
            $defaultUserGroup = Tinebase_Group::getInstance()->getDefaultGroup();
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Group with id ' . $user->accountPrimaryGroup . ' not found. Use default group (' . $defaultUserGroup->name
                . ') as primary group for ' . $user->accountLoginName);
            
            $user->accountPrimaryGroup = $defaultUserGroup->getId();
        }
    }

    public function undelete(Tinebase_Model_FullUser $account): Tinebase_Model_FullUser
    {
        $transaction = Tinebase_RAII::getTransactionManagerRAII();
        $this->_userBackend->undelete($account->accountLoginName);
        $account = $this->get($account->getId());
        $account->visibility = Tinebase_Model_FullUser::VISIBILITY_DISPLAYED;
        $account->accountStatus = Tinebase_Model_FullUser::ACCOUNT_STATUS_ENABLED;
        Tinebase_Timemachine_ModificationLog::setRecordMetaData($account, 'undelete', $account);
        $account = $this->update($account);
        $transaction->release();

        return $account;
    }

    /**
     * delete accounts
     *
     * @param mixed $_accountIds array of account ids
     * @return  array with success flag
     * @throws  Tinebase_Exception_Record_NotAllowed
     * @throws Tinebase_Exception_Confirmation
     */
    public function delete($_accountIds)
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        $groupsController = Admin_Controller_Group::getInstance();

        foreach ((array)$_accountIds as $accountId) {
            if ($accountId === Tinebase_Core::getUser()->getId()) {
                $message = 'You are not allowed to delete yourself!';
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $message);
                throw new Tinebase_Exception_AccessDenied($message);
            }
        }
        
        $this->_checkAccountDeletionConfig($_accountIds);
        
        foreach ((array)$_accountIds as $accountId) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " about to remove user with id: {$accountId}");

            $oldUser = $this->get($accountId);

            $memberships = $groupsController->getGroupMemberships($accountId);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " removing user from groups: " . print_r($memberships, true));

            foreach ((array)$memberships as $groupId) {
                $groupsController->removeGroupMember($groupId, $accountId);
            }

            $this->_userBackend->deleteUser($accountId);
        }
    }
    
    /**
     * returns all shared addressbooks
     * 
     * @return Tinebase_Record_RecordSet of shared addressbooks
     * 
     * @todo do we need to fetch ALL shared containers here (even if admin user has NO grants for them)?
     */
    public function searchSharedAddressbooks()
    {
        $this->checkRight('MANAGE_ACCOUNTS');
        
        return Tinebase_Container::getInstance()->getSharedContainer(Tinebase_Core::getUser(), Addressbook_Model_Contact::class, Tinebase_Model_Grants::GRANT_READ, TRUE);
    }
    
    /**
     * returns default internal addressbook container
     * 
     * @return string|int ID
     */
    public static function getDefaultInternalAddressbook()
    {
        $appConfigDefaults = Admin_Controller::getInstance()->getConfigSettings();
        if (empty($appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK])) {
            $internalAdb = Addressbook_Setup_Initialize::setDefaultInternalAddressbook();
            $internalAdbId = $internalAdb->getId();
        } else {
            $internalAdbId = $appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK];
        }
        return $internalAdbId;
    }

    /**
     *  add email userid xprops to user accounts
     */
    public function convertAccountsToSaveUserIdInXprops()
    {
        if (! Tinebase_EmailUser::isEmailSystemAccountConfigured()) {
            return;
        }

        $this->checkRight('MANAGE_ACCOUNTS');

        foreach (Tinebase_User::getInstance()->getFullUsers() as $user) {
            if (empty($user->accountEmailAddress) || ! Tinebase_EmailUser::checkAllowedDomain($user->accountEmailAddress)) {
                continue;
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' .
                __LINE__ . ' Set xprops of user ' . $user->accountLoginName);

            Tinebase_EmailUser_XpropsFacade::setXprops($user, $user->getId());
            Tinebase_User::getInstance()->updateUserInSqlBackend($user);
        }
    }

    /**
     * @param array|string $_accountIds
     * @throws Tinebase_Exception_Confirmation
     */
    protected function _checkAccountDeletionConfig($_accountIds)
    {
        $context = $this->getRequestContext();
        $confirmHeader = $context['confirm'] ?? $context['clientData']['confirm'] ?? null;

        if (!$confirmHeader) {
            $userData = '<br />';

            foreach ((array)$_accountIds as $accountId) {
                $oldUser = $this->get($accountId)->getTitle();
                $userData .= "$oldUser <br />";
            }

            $translation = Tinebase_Translation::getTranslation($this->_applicationName);
            $configs = Tinebase_Config::getInstance()->getDefinition(Tinebase_Config::ACCOUNT_DELETION_EVENTCONFIGURATION);

            $exception = new Tinebase_Exception_Confirmation(
                $translation->_('Delete user will trigger the [V] events, do you still want to execute this action?'));

            foreach ($configs['content'] as $key => $content) {
                switch ($key) {
                    case Tinebase_Config::ACCOUNT_DELETION_DELETE_PERSONAL_CONTAINER:
                    case Tinebase_Config::ACCOUNT_DELETION_KEEP_AS_CONTACT:
                    case Tinebase_Config::ACCOUNT_DELETION_KEEP_ORGANIZER_EVENTS:
                    case Tinebase_Config::ACCOUNT_DELETION_KEEP_AS_EXTERNAL_ATTENDER:
                    case Tinebase_Config::ACCOUNT_DELETION_DELETE_PERSONAL_FOLDERS:
                    case Tinebase_Config::ACCOUNT_DELETION_DELETE_EMAIL_ACCOUNTS:
                        $label = $translation->_($content['label']);
                        $enable = Tinebase_Config::getInstance()->get(Tinebase_Config::ACCOUNT_DELETION_EVENTCONFIGURATION)->{$key};
                        $enable =  $enable === true ? '[V]' : '[ ]';
                        $userData .= "<br /> $enable $label";
                        break;
                    case Tinebase_Config::ACCOUNT_DELETION_ADDITIONAL_TEXT:
                        $text = Tinebase_Config::getInstance()->get(Tinebase_Config::ACCOUNT_DELETION_EVENTCONFIGURATION)->{$key};
                        $userData .= "<br /> $text <br />";
                        break;
                    default;
                        break;
                }
            }

            $exception->setInfo($userData);
            throw $exception;
        } else {
            $this->_handleUserDeleteConfirmation($_accountIds);
        }

        return true;
    }

    /**
     * @param array|string $_accountIds
     */
    protected function _handleUserDeleteConfirmation($_accountIds)
    {
        Tinebase_Controller_ActionLog::getInstance()->addActionLogUserDelete($_accountIds);
    }

    protected function _inspectBeforeUpdateOrCreate(Tinebase_Model_FullUser $user, ?Tinebase_Model_FullUser $oldUser = null)
    {
        if (! Admin_Config::getInstance()->featureEnabled(
            Admin_Config::FEATURE_CHANGE_USER_TYPE)
        ) {
            if ($oldUser === null || $user->type !== $oldUser->type) {
                unset($user->type);
            }
        }
        $event = new Admin_Event_BeforeUpdateAccount([
            'oldAccount' => $oldUser,
            'newAccount' => $user,
        ]);
        Tinebase_Event::fireEvent($event);
    }

    private function _checkExtendExpiration(Tinebase_Model_FullUser $user, Tinebase_Model_FullUser|Tinebase_Model_User $oldUser): void
    {
        if ($oldUser->accountStatus === Tinebase_Model_FullUser::ACCOUNT_STATUS_EXPIRED &&
            $user->accountExpires !== null &&
            !$oldUser->accountExpires->equals($user->accountExpires))
        {
            if ($user->accountExpires->isLater(new Tinebase_DateTime())) {
                $user->accountStatus = Tinebase_Model_FullUser::ACCOUNT_STATUS_ENABLED;
            }
        }
    }
}

<?php
/**
 * Tine 2.0
 * 
 * MAIN controller for Felamimail, does event handling
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * main controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller extends Tinebase_Controller_Event
{
    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'Felamimail_Model_Message';

    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller
     */
    private static $_instance = NULL;

    /**
     * constructor
     */
    private function __construct()
    {
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
     * @return Felamimail_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Felamimail_Controller;
        }
        
        return self::$_instance;
    }

    /**
     * event handler function
     * 
     * all events get routed through this function
     *
     * @param Tinebase_Event_Abstract $_eventObject the eventObject
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        if (in_array(get_class($_eventObject), [
                Tinebase_Event_User_CreatedAccount::class,
                Admin_Event_UpdateAccount::class,
                Tinebase_Event_User_ChangePassword::class,
            ]) && ! Tinebase_Config::getInstance()->{Tinebase_Config::IMAP}
                ->{Tinebase_Config::IMAP_USE_SYSTEM_ACCOUNT}) {
            // no need to go further - this tine does not care about system accounts
            return;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()
            ->debug(__METHOD__ . '::' . __LINE__ . ' Handle event of type ' . get_class($_eventObject));

        switch (get_class($_eventObject)) {
            case Tinebase_Event_User_ChangeCredentialCache::class:
                /** @var Tinebase_Event_User_ChangeCredentialCache $_eventObject */
                if ($_eventObject->oldCredentialCache) {
                    Felamimail_Controller_Account::getInstance()
                        ->updateCredentialsOfAllUserAccounts($_eventObject->oldCredentialCache);
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()
                        ->warn(__METHOD__ . '::' . __LINE__ . ' Did not get a valid oldCredentialCache');
                }
                break;
            case Admin_Event_AddAccount::class:
                /** @var Admin_Event_AddAccount $_eventObject */
                Felamimail_Controller_Account::getInstance()->createSystemAccount($_eventObject->account,
                    $_eventObject->pwd);
                break;
            case Admin_Event_UpdateAccount::class:
                /** @var Admin_Event_UpdateAccount $_eventObject */
                Felamimail_Controller_Account::getInstance()->updateSystemAccount(
                    $_eventObject->account, $_eventObject->oldAccount, $_eventObject->pwd);
                
                $listToUpdate = [];
                if ($_eventObject->account->accountStatus !== $_eventObject->oldAccount->accountStatus) {
                    $lists = Addressbook_Controller_List::getInstance()->search(
                        Tinebase_Model_Filter_FilterGroup::getFilterForModel(Addressbook_Model_List::class, [
                            ['field' => 'showHidden', 'operator' => 'equals', 'value' => TRUE],
                            ['field' => 'xprops', 'operator' => 'contains', 'value' => Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST],
                        ]));
                    
                    foreach ($lists as $list) {
                        $members = $list['members'];
                        $listToUpdate[] = [$list['email'] => $list->toArray()];
                        if (in_array($_eventObject->account->contact_id, $members)) {
                            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
                            try {
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

                                Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                                $transactionId = null;
                            } catch (Exception $e) {
                                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                                    . ' Could not pdate sieve script for adb lists: ' . $e);
                                throw new Tinebase_Exception_Backend($e->getMessage());
                            } finally {
                                if (null !== $transactionId) {
                                    Tinebase_TransactionManager::getInstance()->rollBack();
                                }
                            }
                        }
                    }
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()
                        ->debug(__METHOD__ . '::' . __LINE__ . ' Update sieve script for adb lists: ' . print_r($listToUpdate, true));
                }

                break;
            case Tinebase_Event_User_ChangePassword::class:
                /** @var Tinebase_Event_User_ChangePassword $_eventObject */
                if (! Tinebase_EmailUser::manages(Tinebase_Config::IMAP)) {
                    return;
                }
                try {
                    $filter =
                        Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Account::class, [
                            ['field' => 'type', 'operator' => 'equals', 'value' => Felamimail_Model_Account::TYPE_USER_INTERNAL],
                            ['field' => 'user_id', 'operator' => 'equals', 'value' => $_eventObject->userId]
                    ]);
                    if (Tinebase_Core::getUser()->hasRight('Admin', Admin_Acl_Rights::MANAGE_EMAILACCOUNTS)) {
                        $filter->doIgnoreAcl(true);
                    }
                    $internalAccounts = Felamimail_Controller_Account::getInstance()->getBackend()->search($filter);
                    $emailUserBackend = Tinebase_EmailUser::getInstance();
                    $emailUserSMTPBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP);
                    
                    foreach ($internalAccounts as $internalAccount) {
                        /** @var Tinebase_EmailUser_Sql $emailUserBackend */
                        $emailUserId = Tinebase_EmailUser_XpropsFacade::getEmailUserId($internalAccount);
                        $emailUserBackend->inspectSetPassword($emailUserId, $_eventObject->password, );
                        $emailUserSMTPBackend->inspectSetPassword($emailUserId, $_eventObject->password);
                    }
                } catch (Exception $e) {
                    Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                        . ' Could not change internal email accounts password: ' . $e);
                    throw new Tinebase_Exception_Backend($e->getMessage());
                }
                break;
            case Tinebase_Event_User_DeleteAccount::class:
                /** @var Tinebase_Event_User_DeleteAccount $_eventObject */
                if ($_eventObject->deleteEmailAccounts()) {
                    try {
                        $accountTypes = [  
                            Felamimail_Model_Account::TYPE_USER_EXTERNAL,
                            Felamimail_Model_Account::TYPE_USER_INTERNAL
                        ];

                        if (Tinebase_Config::getInstance()->{Tinebase_Config::IMAP}
                            ->{Tinebase_Config::IMAP_USE_SYSTEM_ACCOUNT}) {
                            array_push($accountTypes, Felamimail_Model_Account::TYPE_SYSTEM);
                        }
                        
                        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Account::class, [
                            ['field' => 'user_id', 'operator' => 'equals', 'value' => $_eventObject->account['accountId']],
                            ['field' => 'type', 'operator' => 'in', 'value' => $accountTypes]
                        ]);

                        $emailAccountIds = Admin_Controller_EmailAccount::getInstance()->search($filter)->getArrayOfIds();
                        
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()
                            ->debug(__METHOD__ . '::' . __LINE__ . ' User accounts to delete: ' . print_r($emailAccountIds, true));

                        Admin_Controller_EmailAccount::getInstance()->delete($emailAccountIds);
                    } catch (Tinebase_Exception_AccessDenied $tead) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()
                            ->warn(__METHOD__ . '::' . __LINE__ . ' Could not delete accounts: ' . $tead->getMessage());
                    }
                    break;
                }
                break;
            case Tinebase_Event_User_Login::class:
                if (null !== $_eventObject->password) {
                    $this->handleAccountLogin($_eventObject->user, $_eventObject->password);
                }
                break;
        }
    }

    public function handleAccountLogin(Tinebase_Model_FullUser $_account, $pwd)
    {
        try {
            // this is sort of a weird flag to make addSystemAccount do its actual work
            $_account->imapUser = new Tinebase_Model_EmailUser(null, true);
            Felamimail_Controller_Account::getInstance()->createSystemAccount($_account, $pwd);
        } catch (Zend_Db_Adapter_Exception $zdae) {
            Tinebase_Exception::log($zdae);
        } catch (Tinebase_Exception_Backend $teb) {
            Tinebase_Exception::log($teb);
        }
    }

    public function truncateEmailCache()
    {
        $db = Tinebase_Core::getDb();

        // disable fk checks
        $db->query("SET FOREIGN_KEY_CHECKS=0");

        $cacheTables = array(
            'felamimail_cache_message',
            'felamimail_cache_msg_flag',
            'felamimail_cache_message_to',
            'felamimail_cache_message_cc',
            'felamimail_cache_message_bcc'
        );

        // truncate tables
        foreach ($cacheTables as $table) {
            $db->query("TRUNCATE TABLE " . $db->table_prefix . $table);
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()
                ->info(__METHOD__ . '::' . __LINE__ . ' Truncated ' . $table . ' table');
        }

        $db->query("SET FOREIGN_KEY_CHECKS=1");
    }

    /**
     * get application metrics
     *
     * @return array
     */
    public function metrics(): array
    {
        $data = [];
        try {
            if (Tinebase_EmailUser::isEmailSystemAccountConfigured()) {
                $backend = new Felamimail_Backend_Account();
                $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Felamimail_Model_Account::class, [
                    ['field' => 'type', 'operator' => 'in', 'value' => [
                        Tinebase_EmailUser_Model_Account::TYPE_SYSTEM,
                        Tinebase_EmailUser_Model_Account::TYPE_ADB_LIST,
                        Tinebase_EmailUser_Model_Account::TYPE_SHARED_INTERNAL,
                    ]]
                ], '', [
                    'ignoreAcl' => true,
                ]);
                $totalSystemAccounts = 0;
                $totalSharedAccounts = 0;
                $totalMailingLists = 0;
                foreach ($backend->search($filter) as $account) {
                    switch ($account->type) {
                        case Tinebase_EmailUser_Model_Account::TYPE_SYSTEM:
                            $totalSystemAccounts++;
                            break;
                        case Tinebase_EmailUser_Model_Account::TYPE_SHARED_INTERNAL:
                            $totalSharedAccounts++;
                            break;
                        case Tinebase_EmailUser_Model_Account::TYPE_ADB_LIST:
                            $totalMailingLists++;
                            break;
                    }
                }
                $data = [
                    'totalEmailSystemAccounts' => $totalSystemAccounts,
                    'totalEmailSharedAccounts' => $totalSharedAccounts,
                    'totalEmailMailingList' => $totalMailingLists,
                ];
            }
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
        }
        
        return $data;
    }

    public function createEmailSSORelyingParty(): SSO_Model_RelyingParty
    {
        return SSO_Controller_RelyingParty::getInstance()->create(new SSO_Model_RelyingParty([
            SSO_Model_RelyingParty::FLD_NAME => 'email.' . Tinebase_Core::getUrl(Tinebase_Core::GET_URL_HOST),
            SSO_Model_RelyingParty::FLD_CONFIG_CLASS => SSO_Model_OAuthOIdRPConfig::class,
            SSO_Model_RelyingParty::FLD_CONFIG => new SSO_Model_OAuthOIdRPConfig([
                SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS => ['/'],
                SSO_Model_OAuthOIdRPConfig::FLD_SECRET => Tinebase_Record_Abstract::generateUID(),
                SSO_Model_OAuthOIdRPConfig::FLD_OAUTH2_GRANTS => new Tinebase_Record_RecordSet(SSO_Model_OAuthGrant::class, [
                    new SSO_Model_OAuthGrant([
                        SSO_Model_OAuthGrant::FLD_GRANT => \SSO_Config::OAUTH2_GRANTS_AUTHORIZATION_CODE,
                    ], true),
                ]),
            ], true),
        ], true));
    }

    public function deleteEmailSSORelyingParty(): void
    {
        SSO_Controller_RelyingParty::getInstance()->deleteByFilter(Tinebase_Model_Filter_FilterGroup::getFilterForModel(SSO_Model_RelyingParty::class, [
            [TMFA::FIELD => SSO_Model_RelyingParty::FLD_NAME, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => 'email.' . Tinebase_Core::getUrl(Tinebase_Core::GET_URL_HOST)],
        ]));
    }
}

<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Sieve
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to manage addressbook list shared email accounts sieve rules
 *
 * @package     Felamimail
 * @subpackage  Sieve
 */
class Felamimail_Sieve_AdbList
{
    protected $_allowExternal = false;
    protected $_allowOnlyGroupMembers = false;
    protected $_keepCopy = false;
    protected $_forwardOnlySystem = false;
    protected $_receiverList = [];
    protected $_replyTo = null;
    protected $_listEmail = null;
    public static $adbListSieveAuthFailure = false;

    public function __toString()
    {
        $result = 'require ["envelope", "copy", "reject", "editheader", "variables"];' . PHP_EOL;
        $rejectMsg = Felamimail_Config::getInstance()->{Felamimail_Config::SIEVE_MAILINGLIST_REJECT_REASON};
        $translation = Tinebase_Translation::getTranslation('Felamimail');
        $rejectMsg = $translation->_($rejectMsg);
        $this->_addReplyTo($result);

        if ($this->_allowExternal) {
            $this->_addReceiverList($result);
            if (!$this->_keepCopy) {
                $result .= 'discard;' . PHP_EOL;
            }
        } else {
            if ($this->_allowOnlyGroupMembers && !empty($this->_receiverList)) {
                $result .= 'if address :is :all "from" ["' . join('","', $this->_receiverList) . '"] {' . PHP_EOL;
            } else {
                // only internal email addresses are allowed to mail!
                $internalDomains = Tinebase_EmailUser::getAllowedDomains();
                if (! empty($internalDomains)) {
                    $result .= 'if address :is :domain "from" ["' . join('","', $internalDomains) . '"] {' . PHP_EOL;
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' .
                        __LINE__ . ' Allowed domains list is empty ... skipping domain check in sieve script.');
                }
            }

            $this->_addReceiverList($result);

            // we keep msg by default, only if the condition was not met we discard?
            // always discard non-allowed msgs?!?
            $result .= '} else { reject "' . $rejectMsg . '"; }' . PHP_EOL;
        }

        return $result;
    }

    /**
     * @param string $result
     * @return void
     */
    protected function _addReceiverList(string &$result): void
    {
        $internalDomains = [];
        if ($this->_forwardOnlySystem && empty($internalDomains = Tinebase_EmailUser::getAllowedDomains())) {
            throw new Tinebase_Exception_UnexpectedValue('allowed domains list is empty');
        }

        foreach ($this->_receiverList as $email) {
            if (! preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $email)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__ . ' Email address invalid: ' . $email);
                continue;
            }

            if ($this->_forwardOnlySystem) {
                $match = false;
                foreach ($internalDomains as $domain) {
                    if (preg_match('/@' . preg_quote($domain, '/') . '$/', $email)) {
                        $match = true;
                        break;
                    }
                }
                if (!$match) {
                    continue;
                }
            }
            $result .= 'redirect :copy "' . $email . '";' . PHP_EOL;
        }
    }

    /**
     * @param string $result
     * @return void
     */
    protected function _addReplyTo(string &$result): void
    {
        if (empty($this->_replyTo)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' .
                __LINE__ . 'reply to option is null , skip resoling reply-to header');
            return;
        }

        if ($this->_replyTo === 'mailingList' && !empty($this->_listEmail)) {
            $result .= 'deleteheader "Reply-To";
    addheader "Reply-To" "' . $this->_listEmail . '";';
        } elseif ($this->_replyTo === 'sender') {
            $result .= 'if address :matches "from" "*" {
    deleteheader "Reply-To";
    addheader "Reply-To" "${1}";
}';
        } elseif ($this->_replyTo === 'both') {
            $mailingList = $this->_listEmail ? ', ' . $this->_listEmail : '';
            $result .= 'if address :matches "from" "*" {
    deleteheader "Reply-To";
    addheader "Reply-To" "${1}' . $mailingList. '";
}';
        } else {
            return;
        }
        $result .= PHP_EOL . PHP_EOL;
    }

    /**
     * @param Addressbook_Model_List $_list
     * @return Felamimail_Sieve_AdbList
     */
    static public function createFromList(Addressbook_Model_List $_list)
    {
        $sieveRule = new self();

        $oldAcl = Addressbook_Controller_Contact::getInstance()->doContainerACLChecks(false);
        $raii = new Tinebase_RAII(function() use($oldAcl) {
            Addressbook_Controller_Contact::getInstance()->doContainerACLChecks($oldAcl);
        });

        $receivers = Addressbook_Controller_Contact::getInstance()->search(
            new Addressbook_Model_ContactFilter([
                ['field' => 'id', 'operator' => 'in', 'value' => $_list->members],
                ['field' => 'showDisabled', 'operator' => 'equals', 'value' => false],
            ]));
        $sieveRule->_receiverList = [];
        $sieveRule->_listEmail = $_list->email;
        foreach ($receivers as $receiver) {
            /** @var Addressbook_Model_Contact $receiver */
            $email = $receiver->getPreferredEmailAddress();

            if ($email) {
                if ($receiver->type === Addressbook_Model_Contact::CONTACTTYPE_USER) {
                    $accountStatus = Tinebase_User::getInstance()->getFullUserById($receiver->account_id)->accountStatus;
                    if ($accountStatus === Tinebase_Model_User::ACCOUNT_STATUS_ENABLED) {
                        $sieveRule->_receiverList[] = $email;
                    }
                } else {
                    $sieveRule->_receiverList[] = $email;
                }
            }
        }

        // for unused variable check
        unset($raii);

        if (isset($_list->xprops()[Addressbook_Model_List::XPROP_SIEVE_KEEP_COPY]) && $_list
                ->xprops()[Addressbook_Model_List::XPROP_SIEVE_KEEP_COPY]) {
            $sieveRule->_keepCopy = true;
        }

        if (isset($_list->xprops()[Addressbook_Model_List::XPROP_SIEVE_ALLOW_EXTERNAL]) && $_list
                ->xprops()[Addressbook_Model_List::XPROP_SIEVE_ALLOW_EXTERNAL]) {
            $sieveRule->_allowExternal = true;
        }

        if (isset($_list->xprops()[Addressbook_Model_List::XPROP_SIEVE_ALLOW_ONLY_MEMBERS]) && $_list
                ->xprops()[Addressbook_Model_List::XPROP_SIEVE_ALLOW_ONLY_MEMBERS]) {
            if ($sieveRule->_allowExternal) {
                $translation = Tinebase_Translation::getTranslation('Felamimail');
                throw new Tinebase_Exception_SystemGeneric($translation->_('Can not combine "allow external" and "allow only members"'));
            }
            $sieveRule->_allowOnlyGroupMembers = true;
        }

        if (isset($_list->xprops()[Addressbook_Model_List::XPROP_SIEVE_FORWARD_ONLY_SYSTEM]) && $_list
                ->xprops()[Addressbook_Model_List::XPROP_SIEVE_FORWARD_ONLY_SYSTEM]) {
            $sieveRule->_forwardOnlySystem = true;
        }

        if (isset($_list->xprops()[Addressbook_Model_List::XPROP_SIEVE_REPLY_TO]) && $_list
                ->xprops()[Addressbook_Model_List::XPROP_SIEVE_REPLY_TO]) {
            $sieveRule->_replyTo = $_list->xprops()[Addressbook_Model_List::XPROP_SIEVE_REPLY_TO];
        }
        return $sieveRule;
    }

    static public function setScriptForList(Addressbook_Model_List $list)
    {
        $oldValue = Felamimail_Controller_Account::getInstance()->doContainerACLChecks(false);
        $raii = new Tinebase_RAII(function() use ($oldValue) {
            Felamimail_Controller_Account::getInstance()->doContainerACLChecks($oldValue);});

        $account = Felamimail_Controller_Account::getInstance()->getAccountForList($list);

        if (! $account) {
            throw new Tinebase_Exception_NotFound('account of list ' . $list->getId() . ' not found');
        }

        if ($account instanceof Felamimail_Model_Account && Tinebase_EmailUser::backendSupportsMasterPassword($account)) {
            $raii = Tinebase_EmailUser::prepareAccountForSieveAdminAccess($account->getId());
            $sieveAdminAccessActivated = true;
        } else {
            $sieveAdminAccessActivated = false;
        }

        $sieveRule = static::createFromList($list)->__toString();

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' .
            __LINE__ . ' add sieve script: ' . $sieveRule);

        try {
            Felamimail_Controller_Sieve::getInstance()->setAdbListScript($account,
                Felamimail_Model_Sieve_ScriptPart::createFromString(
                    Felamimail_Model_Sieve_ScriptPart::TYPE_ADB_LIST, $list->getId(), $sieveRule));
        } catch (Felamimail_Exception_SieveInvalidCredentials $fesic) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $fesic);
            }
            self::$adbListSieveAuthFailure = true;
            throw $fesic;
        } finally {
            if ($sieveAdminAccessActivated) {
                Tinebase_EmailUser::removeAdminAccess();
            }
            unset($raii);
        }

        return true;
    }

    static public function getSieveScriptForAdbList(Addressbook_Model_List $list)
    {
        $account = Felamimail_Controller_Account::getInstance()->getAccountForList($list);
        
        if ($account) {
            $script = Felamimail_Controller_Sieve::getInstance()->getSieveScript($account);
       
            return $script;
        } else {
            return null;
        }
    }
}

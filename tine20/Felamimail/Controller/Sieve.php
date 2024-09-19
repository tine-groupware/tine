<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        add __destruct with $_backend->logout()?
 */

/**
 * Sieve controller for Felamimail
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Sieve extends Tinebase_Controller_Abstract
{
    /**
     * script name
     *
     * @var string
     */
    protected $_scriptName = 'felamimail2.0';

    /**
     * old script name (this is used to read filter settings from from egw for example and save them with the new name)
     * 
     * @var string
     */
    protected $_oldScriptName = 'felamimail';
    
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * Sieve server backend
     *
     * @var Felamimail_Backend_Sieve
     */
    protected $_backend = NULL;

    protected $_doAclCheck = true;

    /**
     * Sieve script data backend
     *
     * @var string
     * 
     * @todo create factory class?
     */
    protected $_scriptDataBackend = 'Sql';
    //protected $_scriptDataBackend = 'Script';
    
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Sieve
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
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
     * @return Felamimail_Controller_Sieve
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Felamimail_Controller_Sieve();
        }
        
        return self::$_instance;
    }

    public function doAclCheck($_value = null)
    {
        $oldValue = $this->_doAclCheck;
        if (null !== $_value) {
            $this->_doAclCheck = (bool)$_value;
        }
        return $oldValue;
    }
    /**
     * get vacation for account
     * 
     * @param string|Felamimail_Model_Account $_accountId
     * @return Felamimail_Model_Sieve_Vacation
     */
    public function getVacation($_accountId)
    {
        $script = $this->getSieveScript($_accountId);
        $vacation = ($script !== NULL) ? $script->getVacation() : NULL;

        $result = new Felamimail_Model_Sieve_Vacation(array(
            'id'    => ($_accountId instanceOf Felamimail_Model_Account) ? $_accountId->getId() : $_accountId
        ));
        
        if ($vacation !== NULL) {
            $result->setFromFSV($vacation);
        }
        return $result;
    }
    
    /**
     * get sieve script for account
     * 
     * @param string|Felamimail_Model_Account $_accountId
     * @return ?Felamimail_Sieve_Backend_Abstract
     */
    public function getSieveScript($_accountId): ?Felamimail_Sieve_Backend_Abstract
    {
        $script = NULL;
        if ($this->_scriptDataBackend === 'Sql') {
            try {
                $script = new Felamimail_Sieve_Backend_Sql($_accountId);
            } catch (Tinebase_Exception_NotFound $tenf) {
            }
        } else {
            $script = $this->_getServerSieveScript($_accountId);
        }

        if (! $script) {
            $serverScript = $this->_getServerSieveScript($_accountId);
            $script = $this->_createNewSieveScript($_accountId, $serverScript);
        }
        
        return $script;
    }

    /**
     * get sieve custom script  for account
     *
     * @param string $_accountId
     * @return Felamimail_Model_Sieve_ScriptPart of Felamimail_Model_Sieve_Rule
     */
    public function getSieveCustomScript($_accountId): ?Felamimail_Model_Sieve_ScriptPart
    {
        $script = $this->getSieveScript($_accountId);
        $result = null;
        if ($script !== NULL) {
            foreach ($script->getScriptParts() as $scriptPart) {
                if ($scriptPart->type === 'custom') {
                    $partRequires = is_array($scriptPart->xprops(Felamimail_Model_Sieve_ScriptPart::XPROPS_REQUIRES))
                        ? $scriptPart->xprops(Felamimail_Model_Sieve_ScriptPart::XPROPS_REQUIRES)
                        : (is_array($scriptPart->requires) ? $scriptPart->requires : []);
                    if (!empty($partRequires)) {
                        $header = 'require [' . implode(',', $partRequires) .'];';
                        $scriptPart->script = $header . $scriptPart->script;
                    }
                    $result = $scriptPart;
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($result->toArray(), TRUE));
                }
            }
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Sieve script empty or could not parse it.');
        }
        
        if (!$result) {
            $this->setCustomScript($_accountId, '');
            $result = $this->getSieveCustomScript($_accountId);
        }
        return $result;
    }
    
    
    
    /**
     * get sieve script from sieve server
     * 
     * @param string|Felamimail_Model_Account $_accountId
     * @return NULL|Felamimail_Sieve_Backend_Script
     */
    protected function _getServerSieveScript($_accountId)
    {
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        $result = NULL;
        try {
            $scripts = $this->_backend->listScripts();
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            if (preg_match('/Authentication failed/', $zmpe->getMessage())) {
                throw new Felamimail_Exception_SieveInvalidCredentials($zmpe->getMessage());
            } else {
                throw $zmpe;
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' Getting list of SIEVE scripts: ' . print_r($scripts, TRUE));
   
        foreach (array($this->_scriptName, $this->_oldScriptName) as $scriptNameToFetch) {
            if (count($scripts) > 0 && (isset($scripts[$scriptNameToFetch]) || array_key_exists($scriptNameToFetch, $scripts))) {
                $scriptName = $scripts[$scriptNameToFetch]['name'];
                
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                    __METHOD__ . '::' . __LINE__ . ' Get SIEVE script: ' . $scriptName);
                
                $script = $this->_backend->getScript($scriptName);
                if ($script) {
                    if ($scriptNameToFetch == $this->_oldScriptName) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                            __METHOD__ . '::' . __LINE__ . ' Got old SIEVE script for migration.');
                    }
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
                        __METHOD__ . '::' . __LINE__ . ' Got SIEVE script: ' . $script);
                    return new Felamimail_Sieve_Backend_Script($script);
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                        __METHOD__ . '::' . __LINE__ . ' Could not get SIEVE script: ' . $scriptName);
                }
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                    __METHOD__ . '::' . __LINE__ . ' No relevant SIEVE scripts found.');
            }
        }
        
        return $result;
    }
    
    /**
     * init and connect to sieve backend + authenticate with imap user of account
     * 
     * @param string|Felamimail_Model_Account $_accountId
     * @throws Felamimail_Exception
     */
    protected function _setSieveBackendAndAuthenticate($_accountId)
    {
        if (empty($_accountId)) {
            throw new Felamimail_Exception('No account id given.');
        }
        
        $this->_backend = Felamimail_Backend_SieveFactory::factory($_accountId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' sieve server capabilities: ' . print_r($this->_backend->capability(), TRUE));
    }

    /**
     * @param $account
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkAccountEditGrant($account)
    {
        if ($this->_doAclCheck) {
            if ($account->type === Felamimail_Model_Account::TYPE_SHARED) {
                Felamimail_Controller_Account::getInstance()->checkGrantForSharedAccount($account,
                    Felamimail_Model_AccountGrants::GRANT_EDIT);
            } else if ($account->user_id !== Tinebase_Core::getUser()->getId()) {
                throw new Tinebase_Exception_AccessDenied('It is not allowed to edit the account of another user.');
            }
        }
    }

    /**
     * add addresses and from to vacation
     * 
     * @param Felamimail_Model_Sieve_Vacation $_vacation
     * @param Felamimail_Model_Account $_account
     */
    protected function _addVacationUserData(Felamimail_Model_Sieve_Vacation $_vacation, Felamimail_Model_Account $_account)
    {
        if ($_account->type == Felamimail_Model_Account::TYPE_SYSTEM) {
            $addresses = $this->_getSystemAccountVacationAddresses($_account);
        } else {
            $addresses = array($_account->email);
        }
        $_vacation->addresses = $addresses;
        
        if (! $this->_isDbmailSieve()) {
            // and: no from for dbmail vacations
            $from = $_account->from;
            if (strpos((string)$from, '@') === FALSE) {
                $from .= ' <' . $_account->email . '>';
            }
            $_vacation->from = $from;
        }
    }
    
    /**
     * get vacation addresses from system account
     * 
     * @param Felamimail_Model_Account $account
     * @return array
     */
    protected function _getSystemAccountVacationAddresses(Felamimail_Model_Account $account)
    {
        $addresses = array();
        try {
            $fullUser = Tinebase_User::getInstance()->getFullUserById($account->user_id);
        } catch (Tinebase_Exception_NotFound $e) {
            $fullUser = Tinebase_User::getInstance()->getFullUserById(Tinebase_Core::getUser()->getId());
        }
        
        $addresses[] = (! empty($fullUser->accountEmailAddress)) ? $fullUser->accountEmailAddress : $account->email;
        if ($fullUser->smtpUser && ! empty($fullUser->smtpUser->emailAliases)) {
            $addresses = array_merge($addresses, $fullUser->smtpUser->getAliasesAsEmails());
        }
        
        // append all valid domains if nessesary
        $systemAccountConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        foreach ($addresses as $idx => $address) {
            if (is_array($address)) {
                // might be an array with dispatch_address + email keys
                $address = $address['email'];
            }
            if (! strpos($address, '@')) {
                $addresses[$idx] = $address . '@' . $systemAccountConfig['primarydomain'];
                if ($systemAccountConfig['secondarydomains']) {
                    foreach (explode(',', $systemAccountConfig['secondarydomains']) as $secondarydomain) {
                        if ($secondarydomain) {
                            $addresses[] = $address . '@' . trim($secondarydomain);
                        }
                    }
                }
            }
        }
        
        if ($this->_isDbmailSieve() && $fullUser->imapUser && ! empty($fullUser->imapUser->emailUID)) {
            // dbmail sieve needs dbmail uid (envelope recipient) in addresses
            // see https://bugs.launchpad.net/ubuntu/+source/libsieve/+bug/883627
            $addresses[] = $fullUser->imapUser->emailUID;
        }
        
        return $addresses;
    }
    
    /**
     * check sieve backend capabilities
     * 
     * @param Felamimail_Model_Sieve_Vacation $_vacation
     */
    protected function _checkCapabilities(Felamimail_Model_Sieve_Vacation $_vacation)
    {
        $capabilities = $this->_backend->capability();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Got capabilitites: ' . print_r($capabilities, TRUE));
        
        if (! isset($capabilities['SIEVE']) || ! in_array('mime', $capabilities['SIEVE'])) {
            unset($_vacation->mime);
            $_vacation->reason = Felamimail_Model_Message::convertHTMLToPlainTextWithQuotes($_vacation->reason);
        }
        
        if (isset($capabilities['IMPLEMENTATION']) && preg_match('/cyrus/i', $capabilities['IMPLEMENTATION'])) {
            // cyrus does not support :from
            unset($_vacation->from);
        }
        
        if (isset($capabilities['SIEVE']) && in_array('date', $capabilities['SIEVE']) && in_array('relational', $capabilities['SIEVE'])) {
            $_vacation->date_enabled = TRUE;
        }
    }
    
    /**
     * add vacation subject
     * 
     * @param Felamimail_Model_Sieve_Vacation $_vacation
     */
    protected function _addVacationSubject(Felamimail_Model_Sieve_Vacation $_vacation)
    {
        if ($this->_isDbmailSieve()) {
            // dbmail seems to have problems with different subjects and sends vacation responses to the same recipients again and again
            $account = $_vacation->account_id instanceof Felamimail_Model_Account ? $_vacation->account_id :
                Felamimail_Controller_Account::getInstance()->get($_vacation->account_id);
            try {
                $fullUser = Tinebase_User::getInstance()->getFullUserById($account->user_id);
            } catch (Tinebase_Exception_NotFound $e) {
                $fullUser = Tinebase_User::getInstance()->getFullUserById(Tinebase_Core::getUser()->getId());
            }
            $translate = Tinebase_Translation::getTranslation('Felamimail');
            $_vacation->subject = sprintf($translate->_('Out of Office reply from %1$s'), $fullUser->accountFullName);
        }
    }
    
    /**
     * checks if sieve implementation is dbmail
     * 
     * @return boolean
     */
    protected function _isDbmailSieve()
    {
        return (preg_match('/dbmail/i', $this->_backend->getImplementation()));
    }

    /**
     * put updated sieve script
     *
     * @param string|Felamimail_Model_Account $_accountId
     * @param Felamimail_Sieve_Backend_Abstract $_script
     * @throws Felamimail_Exception_SievePutScriptFail
     */
    protected function _putScript($_accountId, $_script)
    {
        $scriptToPut = $_script->getSieve();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $scriptToPut);
        
        try {
            $this->_setSieveBackendAndAuthenticate($_accountId);
            $this->_backend->putScript($this->_scriptName, $scriptToPut);
            $this->activateScript($_accountId);
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getTraceAsString());
            throw new Felamimail_Exception_SievePutScriptFail($zmpe->getMessage());
        }
    }

    /**
     * set sieve script name
     * 
     * @param string $_name
     */
    public function setScriptName($_name)
    {
        $this->_scriptName = $_name;
    }
    
    /**
     * delete sieve script
     * 
     * @param string|Felamimail_Model_Account $_accountId
     */
    public function deleteScript($_accountId)
    {
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Delete SIEVE script ' . $this->_scriptName);
        
        $this->_backend->deleteScript($this->_scriptName);
        
        if ($this->_scriptDataBackend === 'Sql') {
            $script = new Felamimail_Sieve_Backend_Sql($_accountId, FALSE);
            $script->delete();
        }
    }

    /**
     * activate sieve script
     * 
     * @param string|Felamimail_Model_Account $_accountId
     */
    public function activateScript($_accountId)
    {
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' Activate SIEVE script ' . $this->_scriptName);
        $this->_backend->setActive($this->_scriptName);
    }
    
    /**
     * get name of active script for account
     * 
     * @param string|Felamimail_Model_Account $_accountId
     * @return string|NULL
     */
    public function getActiveScriptName($_accountId)
    {
        $this->_setSieveBackendAndAuthenticate($_accountId);
        
        $scripts = $this->_backend->listScripts();
        
        $result = NULL;
        foreach ($scripts as $scriptname => $values) {
            if ($values['active'] == 1) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Found active script: ' . $scriptname);
                $result = $scriptname;
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Inactive script: ' . $scriptname);
            }
        }
        
        return $result;
    }
    
    /**
     * get rules for account
     * 
     * @param string $_accountId
     * @return Tinebase_Record_RecordSet of Felamimail_Model_Sieve_Rule
     */
    public function getRules($_accountId)
    {
        $result = new Tinebase_Record_RecordSet('Felamimail_Model_Sieve_Rule');
        
        $script = $this->getSieveScript($_accountId);
        if ($script !== NULL) {
            foreach ($script->getRules() as $fsr) {
                $rule = new Felamimail_Model_Sieve_Rule();
                $rule->setFromFSR($fsr);
                $result->addRecord($rule);
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($result->toArray(), TRUE));
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Sieve script empty or could not parse it.');
        }
        
        return $result;
    }

    /**
     * set sieve rules + vacation
     *
     * @param string|Felamimail_Model_Account $_accountId $_accountId
     * @param Felamimail_Model_Sieve_Vacation|null $_vacation
     * @param Tinebase_Record_RecordSet|null $_rules (Felamimail_Model_Sieve_Rule)
     * @return string
     * @throws Felamimail_Exception
     * @throws Felamimail_Exception_Sieve
     * @throws Felamimail_Exception_SievePutScriptFail
     * @throws Tinebase_Exception_AccessDenied
     */
    public function setSieveScript($_accountId, Felamimail_Model_Sieve_Vacation $_vacation = null, Tinebase_Record_RecordSet $_rules = null)
    {
        $account = $_accountId instanceof Felamimail_Model_Account
            ? $_accountId
            : Felamimail_Controller_Account::getInstance()->get($_accountId);
        
        $this->_checkAccountEditGrant($account);
        $this->_setSieveBackendAndAuthenticate($account);
        
        $script = $this->getSieveScript($account);
        
        if ($_vacation) {
            $this->_addVacationUserData($_vacation, $account);
            $this->_checkCapabilities($_vacation);
            $this->_addVacationSubject($_vacation);

            $fsv = $_vacation->getFSV();
            $script->setVacation($fsv);

            Felamimail_Controller_Account::getInstance()->setVacationActive($account, $_vacation->enabled);
        }

        if ($script === NULL) {
            $script = $this->_createNewSieveScript($_accountId);
        } else {
            if ($_rules) {
                $script->clearRules();
            }
        }

        if ($_rules) {
            foreach ($_rules as $rule) {
                $this->_checkRule($rule, $_accountId);
                $fsr = $rule->getFSR();
                $script->addRule($fsr);
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' Put updated SIEVE script ' . $this->_scriptName);

        $this->_putScript($account, $script);
        $this->_addSystemNotes($account, $_vacation, $_rules);
        $script = $this->getSieveScript($account);
        
        return $script->getSieve();
    }

    /**
     * @param Felamimail_Model_Account $account
     * @param Felamimail_Model_Sieve_Vacation|null $vacation
     * @param Tinebase_Record_RecordSet|null $rules (Felamimail_Model_Sieve_Rule)
     * @return void
     * @throws Tinebase_Exception_NotFound
     * @todo add modlog later
     */
    protected function _addSystemNotes($account, $vacation, $rules)
    {
        $translation = Tinebase_Translation::getTranslation('Felamimail');
        if ($vacation) {
            $text = $translation->_('Sieve vacation has been updated:');
            if ($vacation->enabled) {
                $text .= ' ' . $translation->_('Vacation message is now active.');
            } else {
                $text .= ' ' . $translation->_('Vacation message is now inactive.');
            }
            Tinebase_Notes::getInstance()->addSystemNote($account, Tinebase_Core::getUser(),
                Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE, $text);
        }
        if ($rules) {
            $text = $translation->_('Sieve rules have been updated.');
            Tinebase_Notes::getInstance()->addSystemNote($account, Tinebase_Core::getUser(),
                Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE, $text);
        }
    }
    
    /**
     * check the rules
     * 
     * @param Felamimail_Model_Sieve_Rule $_rule
     * @param string|Felamimail_Model_Account $_accountId
     * @throws Felamimail_Exception_Sieve
     */
    protected function _checkRule($_rule, $_accountId)
    {
        $account = ($_accountId instanceof Felamimail_Model_Account)
            ? $_accountId
            : Felamimail_Controller_Account::getInstance()->get($_accountId);

        if ($_rule->action_type === Felamimail_Sieve_Rule_Action::REDIRECT && $_rule->enabled) {
            $redirectEmails = is_array($_rule->action_argument) ? (array) $_rule->action_argument['emails'] : [$_rule->action_argument];

            $translate = Tinebase_Translation::getTranslation('Felamimail');
            if (in_array($account->email, $redirectEmails)) {
                throw new Felamimail_Exception_Sieve($translate->_('It is not allowed to redirect emails to self! Please change the recipient.'));
            }
            if (Felamimail_Config::getInstance()->{Felamimail_Config::SIEVE_REDIRECT_ONLY_INTERNAL}) {
                foreach ($redirectEmails as $email) {
                    if (false === Tinebase_EmailUser::checkDomain($email)) {
                        throw new Felamimail_Exception_Sieve($translate->_('Redirects to external email domains are not allowed.'));
                    }
                }
            }
        }
    }
    
    /**
     * create new sieve script for the configured backend
     * 
     * @param string|Felamimail_Model_Account $_accountId
     * @param Felamimail_Sieve_Backend_Abstract $_copyScript
     * @return Felamimail_Sieve_Backend_Abstract
     */
    protected function _createNewSieveScript($_accountId, $_copyScript = NULL)
    {
        if ($this->_scriptDataBackend === 'Sql') {
            $script = new Felamimail_Sieve_Backend_Sql($_accountId, FALSE);
        } else {
            $script = new Felamimail_Sieve_Backend_Script();
        }
        
        if ($_copyScript !== NULL) {
            $script->getDataFromScript($_copyScript);
        }
        
        return $script;
    }
    
    /**
    * get vacation message defined by template / do substitutions for dates and representative
    *
    * @param Felamimail_Model_Sieve_Vacation $vacation
    * @return string
    */
    public function getVacationMessage(Felamimail_Model_Sieve_Vacation $vacation): string
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($vacation->toArray(), TRUE));

        if (empty($vacation->account_id)) {
            $vacation->account_id = Felamimail_Controller_Account::getInstance()->getSystemAccount();
        }
        if ($vacation->template_id) {
            $message = $this->_getMessageFromTemplateFile($vacation->template_id);
            $message = $this->_doMessageSubstitutions($vacation, $message);
        } else {
            $message = '';
        }
        
        return $message;
    }
    
    /**
     * get vacation message from template file
     * 
     * @param string $templateId
     * @return string
     */
    protected function _getMessageFromTemplateFile($templateId)
    {
        $message = Tinebase_FileSystem::getInstance()->getNodeContents($templateId);
        
        return $message;
    }
    
    /**
     * do substitutions in vacation message
     * 
     * @param Felamimail_Model_Sieve_Vacation $vacation
     * @param string $message
     * @return string
     * 
     * @todo get locale from placeholder (i.e. endDate-_LOCALESTRING_)
     * @todo get field from placeholder (i.e. representation-_FIELDNAME_)
     * @todo use twig
     */
    protected function _doMessageSubstitutions(Felamimail_Model_Sieve_Vacation $vacation, $message)
    {
        $timezone = Tinebase_Core::getUserTimezone();
        $representatives = ($vacation->contact_ids) ? Addressbook_Controller_Contact::getInstance()->getMultiple($vacation->contact_ids) : array();
        if ($vacation->contact_ids && count($representatives) > 0) {
            // sort and expand representatives
            $representativesArray = array();
            foreach ($vacation->contact_ids as $id) {
                if ($contact = $representatives->getById($id)) {
                    $representativesArray[] = $contact;
                }
            }
        }
        try {
            $account = $vacation->account_id instanceof Felamimail_Model_Account ? $vacation->account_id :
                Felamimail_Controller_Account::getInstance()->get($vacation->account_id);
            $ownContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($account->user_id);
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $e);
            $ownContact = NULL;
        }

        $search = array(
            '{startDate-en_US}',
            '{endDate-en_US}',
            '{startDate-de_DE}',
            '{endDate-de_DE}',
            '{representation-n_fn-1}',
            '{representation-n_fn-2}',
            '{representation-email-1}',
            '{representation-email-2}',
            '{representation-tel_work-1}',
            '{representation-tel_work-2}',
            '{owncontact-n_fn}',
            '{signature}',
        );

        if (!isset($representativesArray[1])) {
            $message = str_replace(" or {representation-n_fn-2} ({representation-email-2}) {representation-tel_work-2} instead.", '', $message);
            $message = str_replace(" oder {representation-n_fn-2} ({representation-email-2}) {representation-tel_work-2}.", '', $message);
        }
        
        if (!isset($representativesArray[0])) {
            $message = str_replace(" {representation-n_fn-1} ({representation-email-1}) {representation-tel_work-1}", '', $message);
        }

        if (!isset($representativesArray[0]) && !isset($representativesArray[1])) {
            $message = str_replace(" Bitte kontaktieren Sie", " Bitte kontaktieren Sie andere Kollegen.", $message);
            $message = str_replace(" Please contact", " Please contact other colleagues.", $message);
        }
        
        $replace = array(
            Tinebase_Translation::dateToStringInTzAndLocaleFormat($vacation->start_date, $timezone, new Zend_Locale('en_US'), 'date'),
            Tinebase_Translation::dateToStringInTzAndLocaleFormat($vacation->end_date, $timezone, new Zend_Locale('en_US'), 'date'),
            Tinebase_Translation::dateToStringInTzAndLocaleFormat($vacation->start_date, $timezone, new Zend_Locale('de_DE'), 'date'),
            Tinebase_Translation::dateToStringInTzAndLocaleFormat($vacation->end_date, $timezone, new Zend_Locale('de_DE'), 'date'),
            (isset($representativesArray[0])) ? $representativesArray[0]->n_fn : 'unknown person',
            (isset($representativesArray[1])) ? $representativesArray[1]->n_fn : 'unknown person',
            (isset($representativesArray[0])) ? $representativesArray[0]->email : 'unknown email',
            (isset($representativesArray[1])) ? $representativesArray[1]->email : 'unknown email',
            (isset($representativesArray[0])) ? $representativesArray[0]->tel_work : '',
            (isset($representativesArray[1])) ? $representativesArray[1]->tel_work : '',
            ($ownContact) ? $ownContact->n_fn : '',
            ($vacation->signature) ? Felamimail_Model_Message::convertHTMLToPlainTextWithQuotes(
                preg_replace("/\\r|\\n/", '', $vacation->signature)) : '',
        );
        
        $result = str_replace($search, $replace, $message);
  
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $result);
        
        return $result;
    }

    /**
     * @param string $_accountId
     * @param string $_email
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public function setNotificationEmail($_accountId, $_email)
    {
        // acl check
        if (!$_accountId instanceof Felamimail_Model_Account) {
            Felamimail_Controller_Account::getInstance()->get($_accountId);
        }

        $scriptParts = new Tinebase_Record_RecordSet('Felamimail_Model_Sieve_ScriptPart', array());
        $_email = trim($_email);
        if (empty($_email)) {
            $this->setNotificationScripts($_accountId, $scriptParts);
            return;
        }
        
        $fileSystem = Tinebase_FileSystem::getInstance();
        $translate = Tinebase_Translation::getTranslation('Felamimail');
        $locale = Tinebase_Core::getLocale();
        $subject = $translate->_('You have new mail from ', $locale);
        if (empty($adminBounceEmail = Felamimail_Config::getInstance()->
                {Felamimail_Config::SIEVE_ADMIN_BOUNCE_NOTIFICATION_EMAIL}) ||
                !preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $adminBounceEmail)) {
            $defaultAdminGroup = Tinebase_Group::getInstance()->getDefaultAdminGroup();
            $members = Tinebase_Group::getInstance()->getGroupMembers($defaultAdminGroup->getId());
            foreach ($members as $memberId) {
                $adminUser = Tinebase_User::getInstance()->getFullUserById($memberId);
                if (! empty($adminUser->accountEmailAddress)) {
                    $adminBounceEmail = $adminUser->accountEmailAddress;
                    break;
                }
            }
        }

        if (empty($adminBounceEmail)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' '
                    . ' No valid $adminBounceEmail found - could not set notification script');
            }
            $this->setNotificationScripts($_accountId, $scriptParts);
            return;
        }

        $notificationEmailAddress = Tinebase_Notification_Backend_Smtp::getNotificationAddress();
        if(!($notificationEmailAddress)) {
            $notificationEmailAddress = '';
        }
        if($notificationEmailAddress !== '' && preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $notificationEmailAddress)) {
            $notificationEmailAddress = ':from "' . $notificationEmailAddress . '"' . "\n\t\t";
        }

        /** @var Tinebase_Model_Tree_Node $sieveNode */
        foreach ($fileSystem->getTreeNodeChildren(Felamimail_Config::getInstance()->
                get(Felamimail_Config::EMAIL_NOTIFICATION_TEMPLATES_CONTAINER_ID)) as $sieveNode) {
            if (Tinebase_Model_Tree_FileObject::TYPE_FILE !== $sieveNode->type) {
                continue;
            }
            $path = Tinebase_Model_Tree_Node_Path::createFromStatPath($fileSystem->getPathOfNode($sieveNode, true));
            if (strpos($path->name, '.') === 0) {
                // do nothing with dotfiles
                continue;
            }
            $sieveScript = file_get_contents($path->streamwrapperpath);
            $notifyScript = '';
            $redirectScript = '';
            $emails = explode(',', $_email);
            
            foreach ($emails as $email) {
                if (!preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $email)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                        Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' '
                            . $email . ' is not a valid email address - skipping');
                    }
                    continue;
                }
                $notifyScript .= "\n\t" . 'notify ' . $notificationEmailAddress . ':message "' . $subject. '${from}: ${subject}"' . "\n\t\t" . '"mailto:' . $email .'?body=${message}";' . "\n";
                $redirectScript .= "\n\t" . 'redirect :copy "' . $email . '";';
            }

            $sieveScript = str_replace('TRANSLATE_SUBJECT', $subject, $sieveScript);
            $sieveScript = str_replace('ADMIN_BOUNCE_EMAIL', $adminBounceEmail, $sieveScript);
            $sieveScript = str_replace('REDIRECT_EMAILS_SCRIPT', $redirectScript, $sieveScript);
            $sieveScript = str_replace('NOTIFY_EMAILS_SCRIPT', $notifyScript, $sieveScript);
            $scriptParts->addRecord(Felamimail_Model_Sieve_ScriptPart::createFromString(
                Felamimail_Model_Sieve_ScriptPart::TYPE_NOTIFICATION, $sieveNode->name, $sieveScript));
        }

        $this->setNotificationScripts($_accountId, $scriptParts);
    }

    /**
     * set notification scripts for account
     *
     * @param string|Felamimail_Model_Account $_accountId
     * @param Tinebase_Record_RecordSet $_scriptParts (Felamimail_Model_Sieve_ScriptPart)
     */
    public function setNotificationScripts($_accountId, Tinebase_Record_RecordSet $_scriptParts)
    {
        // acl check
        if (!$_accountId instanceof Felamimail_Model_Account) {
            Felamimail_Controller_Account::getInstance()->get($_accountId);
        }

        $this->_updateScriptParts($_accountId, $_scriptParts, Felamimail_Model_Sieve_ScriptPart::TYPE_NOTIFICATION);
    }

    protected function _updateScriptParts($_accountId, Tinebase_Record_RecordSet $_scriptParts, $type)
    {
        if ($_scriptParts->count() !==
            $_scriptParts->filter('type', $type)->count()) {
            throw new Tinebase_Exception_UnexpectedValue('all script parts need to be of type '
                . $type);
        }
        $_scriptParts->account_id = $_accountId;
        if ($type === Felamimail_Model_Sieve_ScriptPart::TYPE_CUSTOM) {
            Felamimail_Controller_Sieve::getInstance()->setScriptName($this->_scriptName . '-custom');
        }
        $script = $this->getSieveScript($_accountId);
        $cloneOldScript = clone $script;

        try {
            $script->readScriptData();
        } catch(Tinebase_Exception_NotFound $tenf) {}
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Put updated rules SIEVE script ' .
                $this->_scriptName);
        }

        $oldScripParts = $script->getScriptParts();
        $oldScripParts->removeRecords($oldScripParts->filter('type', $type));
        $oldScripParts->merge($_scriptParts);
        
        try {
            $this->_putScript($_accountId, $script);
        } catch (Felamimail_Exception_SievePutScriptFail $fespsf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $fespsf->getTraceAsString());
            $this->_putScript($_accountId, $cloneOldScript);
            throw new Felamimail_Exception_SievePutScriptFail($fespsf->getMessage());
        }

    }

    /**
     * set auto move notification script for account
     *
     * @param string|Felamimail_Model_Account $_account
     */
    public function updateAutoMoveNotificationScript($_account)
    {
        // get account user and check forward_only - disable sieve if forward_only address
        if ($_account->user_id) {
            try {
                $user = Tinebase_User::getInstance()->getFullUserById($_account->user_id);
                if (isset($user->smtpUser) && $user->emailForwardOnly) {
                    $_account->sieve_notification_move = Felamimail_Model_Account::SIEVE_NOTIFICATION_MOVE_INACTIVE;
                }
            } catch (Tinebase_Exception_NotFound $tenf) {
            }
        }
        
        if (! isset($_account->sieve_notification_move)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                    'Skip update sieve script: ' . $this->_scriptName . 
                    ', Invalid sieve notification move setting : ' . print_r($_account, true)
                    );
            }
            return;
        }

        $scriptParts = new Tinebase_Record_RecordSet('Felamimail_Model_Sieve_ScriptPart');

        if (! empty($_account->sieve_notification_move_folder) 
            && $_account->sieve_notification_move !== Felamimail_Model_Account::SIEVE_NOTIFICATION_MOVE_INACTIVE) {
            $scriptParts->addRecord(new Felamimail_Model_Sieve_ScriptPart([
                'account_id' => $_account,
                'type' => Felamimail_Model_Sieve_ScriptPart::TYPE_AUTO_MOVE_NOTIFICATION,
                'script' => 'if header :contains "X-Tine20-Type" "Notification" {fileinto :create "' 
                    . $_account->sieve_notification_move_folder . '";}',
                'name' => 'auto_move_notification',
                'requires' => ['"fileinto"', '"mailbox"'],
            ]));
        }
        
        $this->_updateScriptParts($_account, $scriptParts, Felamimail_Model_Sieve_ScriptPart::TYPE_AUTO_MOVE_NOTIFICATION);
        
        if ($_account->sieve_notification_move === Felamimail_Model_Account::SIEVE_NOTIFICATION_MOVE_AUTO) {
            $_account->sieve_notification_move = Felamimail_Model_Account::SIEVE_NOTIFICATION_MOVE_ACTIVE;
            Felamimail_Controller_Account::getInstance()->update($_account);
        }
    }

    /**
     * set adb list script for account
     *
     * @param Felamimail_Model_Account $_account
     * @param Felamimail_Model_Sieve_ScriptPart $_scriptPart
     */
    public function setAdbListScript(Felamimail_Model_Account $_account, Felamimail_Model_Sieve_ScriptPart $_scriptPart)
    {
        $scriptParts = new Tinebase_Record_RecordSet('Felamimail_Model_Sieve_ScriptPart');
        $scriptParts->addRecord($_scriptPart);
        $this->_updateScriptParts($_account, $scriptParts, Felamimail_Model_Sieve_ScriptPart::TYPE_ADB_LIST);
    }
    public function addDefaultNotificationTemplate() {
        try {
            $basepath = Tinebase_FileSystem::getInstance()->getApplicationBasePath(
                'Felamimail',
                Tinebase_FileSystem::FOLDER_TYPE_SHARED
            );
            $node = Tinebase_FileSystem::getInstance()->createAclNode($basepath . '/Email Notification Templates');
            Felamimail_Config::getInstance()->set(Felamimail_Config::EMAIL_NOTIFICATION_TEMPLATES_CONTAINER_ID, $node->getId());

            if (false === ($fh = Tinebase_FileSystem::getInstance()->fopen($basepath . '/Email Notification Templates/defaultForwarding.sieve', 'w'))) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                    . ' Could not create defaultForwarding.sieve file');
                return;
            }

            fwrite($fh, <<<'sieveFile'
require ["enotify", "variables", "copy", "body"];

if header :contains "Return-Path" "<>" {
    if body :raw :contains "X-Tine20-Type: Notification" {
        notify :message "there was a notification bounce"
              "mailto:ADMIN_BOUNCE_EMAIL";
    }
} elsif header :contains "X-Tine20-Type" "Notification" {
    REDIRECT_EMAILS_SCRIPT
} else {
    if header :matches "Subject" "*" {
        set "subject" "${1}";
    }
    if header :matches "From" "*" {
        set "from" "${1}";
    }
    set :encodeurl "message" "TRANSLATE_SUBJECT${from}: ${subject}";

    NOTIFY_EMAILS_SCRIPT
}
sieveFile
            );

            if (true !== Tinebase_FileSystem::getInstance()->fclose($fh)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                    . ' Could not create defaultForwarding.sieve file');
                return;
            }

        } catch (Tinebase_Exception_Backend $teb) {
            Tinebase_Exception::log($teb);
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                . ' Could not create email notification template folder: ' . $teb);
        }
    }
    
    /**
     * set custom script for account
     *
     * @param $_accountId
     * @param $script
     * @throws Felamimail_Exception_SievePutScriptFail
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    public function setCustomScript($_accountId, $script, $checkGrant = true)
    {
        $account = Felamimail_Controller_Account::getInstance()->get($_accountId);
        if ($checkGrant) {
            $this->_checkAccountEditGrant($account);
        }
        $scriptParts = new Tinebase_Record_RecordSet('Felamimail_Model_Sieve_ScriptPart');
        $record = Felamimail_Model_Sieve_ScriptPart::createFromString(
            Felamimail_Model_Sieve_ScriptPart::TYPE_CUSTOM, $account->getId(), $script);
        $record->account_id = $account;
        $scriptParts->addRecord($record);
        $this->_updateScriptParts($account, $scriptParts, Felamimail_Model_Sieve_ScriptPart::TYPE_CUSTOM);

        $script = Felamimail_Controller_Sieve::getInstance()->getSieveScript($_accountId);
        return $script->getSieve();
    }
}

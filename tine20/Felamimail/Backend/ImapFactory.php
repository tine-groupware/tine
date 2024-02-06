<?php
/**
 * factory class for imap backends
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * An instance of the imap backend class should be created using this class
 * 
 * @package     Felamimail
 */
class Felamimail_Backend_ImapFactory
{
    /**
     * backend object instances
     */
    private static $_backends = array();
    
    /**
     * factory function to return a selected account/imap backend class
     *
     * @param   string|Felamimail_Model_Account $_accountId
     * @return  Felamimail_Backend_ImapProxy
     * @throws  Felamimail_Exception_IMAPInvalidCredentials
     */
    static public function factory($_accountId)
    {
        $accountId = $_accountId instanceof Felamimail_Model_Account ? $_accountId->getId() : $_accountId;
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
            __METHOD__ . '::' . __LINE__ . ' Getting IMAP backend for account id ' . $accountId);
        
        if (!isset(self::$_backends[$accountId])) {
            $account = $_accountId instanceof Felamimail_Model_Account
                ? $_accountId
                : Felamimail_Controller_Account::getInstance()->get($_accountId);
            self::$_backends[$accountId] = self::_createImapProxy($account);
        }
        
        return self::$_backends[$accountId];
    }

    /**
     * @param Felamimail_Model_Account $account
     * @return Felamimail_Backend_ImapProxy
     * @throws Felamimail_Exception
     * @throws Felamimail_Exception_IMAPInvalidCredentials
     * @throws Zend_Session_Exception
     */
    protected static function _createImapProxy(Felamimail_Model_Account $account): Felamimail_Backend_ImapProxy
    {
        // get imap config from account
        $imapConfig = $account->getImapConfig();

        // we need to instantiate a new imap backend
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__
            . ' Connecting to server ' . $imapConfig['host'] . ':' . $imapConfig['port']
            . ' (' . (((isset($imapConfig['ssl']) || array_key_exists('ssl', $imapConfig)))
                ? $imapConfig['ssl'] : 'none') . ')'
            . ' with username ' . $imapConfig['user']);

        try {
             return new Felamimail_Backend_ImapProxy($imapConfig, $account);
        } catch (Felamimail_Exception_IMAPInvalidCredentials $feiic) {
            // add account and username to Felamimail_Exception_IMAPInvalidCredentials
            $feiic->setAccount($account)
                ->setUsername($imapConfig['user']);
            throw $feiic;
        }
    }

    /**
     * reset the factory backends
     */
    static public function reset()
    {
        foreach (self::$_backends as $backend) {
            /* @var Felamimail_Backend_Imap $backend */
            $backend->close();
            unset($backend);
        }
        self::$_backends = [];
    }
}    

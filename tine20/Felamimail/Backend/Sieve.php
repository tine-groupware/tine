<?php
/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * Felamimail Sieve backend
 *
 * @package     Felamimail
 * @subpackage  Backend
 */
class Felamimail_Backend_Sieve extends Zend_Mail_Protocol_Sieve
{
    /**
     * Public constructor
     *
     * @param  array $_config sieve config (host/port/ssl/username/password)
     * @throws Felamimail_Exception_Sieve
     */
    public function __construct($_config)
    {
        $_config['port'] = ((isset($_config['port']) || array_key_exists('port', $_config))) ? $_config['port'] : NULL;
        $_config['ssl'] = ((isset($_config['ssl']) || array_key_exists('ssl', $_config))) ? $_config['ssl'] : FALSE;
        
        try {
            parent::__construct($_config['host'], $_config['port'], $_config['ssl']);
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            throw new Felamimail_Exception_Sieve('Could not connect to host ' . $_config['host'] . ' (' . $zmpe->getMessage() . ').');
        }
        
        try {
            if (($_config['sasl'] ?? false) && ($_config['sasl_params'] ?? false)) {
                $this->saslAuthenticate($_config['sasl_params'], $_config['sasl'], false);
            } else {
                $this->authenticate($_config['username'], $_config['password']);
            }
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            throw new Felamimail_Exception_SieveInvalidCredentials('Could not authenticate with user '
                . $_config['username'] . ' (' . $zmpe->getMessage() . ').');
        }
    }

    public function saslAuthenticate(array $_params, string $_method, bool $dontParse = true): void
    {
        switch ($_method)
        {
            case 'XOAUTH2':
                $token = base64_encode('user=' . ($_params['email'] ?? '') . chr(1) . chr(1)  . 'auth=Bearer ' . ($_params['token'] ?? '') . chr(1) . chr(1));
                $this->requestAndResponse('AUTHENTICATE', $this->escapeString('XOAUTH2', $token), dontParse: $dontParse);
                break;

            default :
                throw new Exception("Sasl method $_method not implemented!");
        }
    }
}

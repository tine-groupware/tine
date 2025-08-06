<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Protocol
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
class Zend_Mail_Protocol_Smtp_Auth_XOauth2 extends Zend_Mail_Protocol_Smtp
{
    protected string $_username;
    protected string $_password;


    /**
     * Constructor.
     *
     * @param string $host (Default: 127.0.0.1)
     * @param int $port (Default: null)
     * @param array $config Auth-specific parameters
     * @return void
     */
    public function __construct($host = '127.0.0.1', $port = null, $config = null)
    {
        if (is_array($config)) {
            if (isset($config['username'])) {
                $this->_username = $config['username'];
            }
            if (isset($config['password'])) {
                $this->_password = $config['password'];
            }
        }

        parent::__construct($host, $port, $config);
    }


    /**
     * Perform PLAIN authentication with supplied credentials
     *
     * @return void
     */
    public function auth()
    {
        // Ensure AUTH has not already been initiated.
        parent::auth();

        $this->_send('AUTH XOAUTH2');
        $this->_expect(334);
        $this->_send(base64_encode('user=' . $this->_username . chr(1) . chr(1)  . 'auth=Bearer ' . $this->_password . chr(1) . chr(1)));
        $this->_expect(235);
        $this->_auth = true;
    }
}


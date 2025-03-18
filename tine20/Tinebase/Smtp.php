<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Smtp
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

/**
 * class Tinebase_Smtp
 * 
 * send emails using smtp
 * 
 * @package Tinebase
 * @subpackage Smtp
 */
class Tinebase_Smtp
{
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Smtp
     */
    private static $_instance = NULL;
    
    /**
     * the default smtp transport
     *
     * @var Zend_Mail_Transport_Abstract
     */
    protected static $_defaultTransport = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        self::createDefaultTransport();
    }
    
    /**
     * create default transport
     */
    public static function createDefaultTransport()
    {
        $config = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct(array(
            'hostname' => 'localhost', 
            'port' => 25
        )))->toArray();
        
        // set default transport none is set yet
        if (! self::getDefaultTransport()) {
            if (empty($config['hostname'])) {
                $config['hostname'] = 'localhost';
            }
            
            // don't try to login if no username is given or if auth set to 'none'
            if (! isset($config['auth']) || $config['auth'] == 'none' || empty($config['username'])) {
                unset($config['username']);
                unset($config['password']);
                unset($config['auth']);
            }
            
            if (isset($config['ssl']) && $config['ssl'] == 'none') {
                unset($config['ssl']);
            }

            $config['connectionOptions'] = Tinebase_Mail::getConnectionOptions();

            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Setting default SMTP transport. Hostname: ' . $config['hostname']);

            $transport = new Zend_Mail_Transport_Smtp($config['hostname'], $config);
            self::setDefaultTransport($transport);
        }
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
     * @return Tinebase_Smtp
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_Smtp();
        }
        
        return self::$_instance;
    }

    /**
     * sets default transport
     * @param  Zend_Mail_Transport_Abstract|NULL $_transport
     * @return void
     */
    public static function setDefaultTransport($_transport)
    {
        if ($_transport) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' Setting SMTP transport: ' . $_transport::class);
            self::$_defaultTransport = $_transport;
        } else {
            self::$_defaultTransport = NULL;
            self::createDefaultTransport();
        }
    }
    
    /**
     * returns default transport
     * 
     * @return null|Zend_Mail_Transport_Abstract
     */
    public static function getDefaultTransport()
    {
        return self::$_defaultTransport;
    }
    
    /**
     * send message using default transport or an instance of Zend_Mail_Transport_Abstract
     *
     * @param Zend_Mail $_mail
     * @param Zend_Mail_Transport_Abstract $_transport
     * @return void
     */
    public function sendMessage(Zend_Mail $_mail, $_transport = NULL)
    {
        $transport = $_transport instanceof Zend_Mail_Transport_Abstract ? $_transport : self::getDefaultTransport();

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG) && $transport) {
            Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Send Message using SMTP transport: '
                . $transport::class);
        }
        
        if (! $_mail->getMessageId()) {
            $_mail->setMessageId();
        }
        $_mail->addHeader('X-MailGenerator', 'Tine 2.0');
        
        $_mail->send($transport);
    }
}

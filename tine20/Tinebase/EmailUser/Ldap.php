<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 */

/**
 * class Tinebase_EmailUser
 * 
 * Email User Settings Managing for dbmail (+ ...) attributes in ldap backend
 * 
 * @package    Tinebase
 * @subpackage EmailLdap
 */
class Tinebase_EmailUser_Ldap extends Tinebase_User_Plugin_LdapAbstract
{
    /**
     * user properties mapping 
     * -> we need to use lowercase for ldap fields because ldap_fetch returns lowercase keys
     *
     * @var array
     */
    protected $_propertyMapping = array(
        'emailAddress'  => 'mail',
    );
    
    /**
     * objectclasses required for users
     *
     * @var array
     */
    protected $_requiredObjectClass = array(
        'inetOrgPerson'
    );
    
    protected $_defaults = array();
    
    /******************* protected functions *********************/
    
    /**
     * Returns a user object with raw data from ldap
     *
     * @param array $_userData
     * @param string $_accountClass
     * @return Tinebase_Model_EmailUser
     * 
     * @todo add generic function for this in Tinebase_User_Ldap or Tinebase_Ldap?
     */
    protected function _ldap2User(Tinebase_Model_User $_user, array &$_ldapEntry)
    {
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_ldapEntry, true));
        $accountArray = $this->_defaults;

        if ($this instanceof Tinebase_EmailUser_Smtp_Interface) {
            $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP);
            $accountArray = array_merge($accountArray, array(
                'emailHost'        => $smtpConfig['hostname'],
                'emailPort'        => $smtpConfig['port'],
                'emailSecure'      => $smtpConfig['ssl'],
                'emailAuth'        => $smtpConfig['auth'],
            ));
        } else {
            $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP);

        }

        if ($this instanceof Tinebase_EmailUser_Smtp_Interface) {
            $accountArray = array_merge($accountArray, array(
                'emailForwardOnly' => false,
                'emailAliases'     => array(),
                'emailForwards'    => array()
            ));
        }
        
        foreach ($_ldapEntry as $key => $value) {
            if (is_int($key)) {
                continue;
            }
            
            $keyMapping = array_search($key, $this->_propertyMapping);
            
            if ($keyMapping !== FALSE) {
                switch($keyMapping) {
                    case 'emailMailQuota':
                        // convert to megabytes
                        $accountArray[$keyMapping] = Tinebase_Helper::convertToMegabytes($value[0]);
                        break;
                        
                    case 'emailAliases':
                    case 'emailForwards':
                        $accountArray[$keyMapping] = $value;
                        break;
                
                    case 'emailForwardOnly':
                        $accountArray[$keyMapping] = (strtolower((string) $value[0]) == 'forwardonly') ? true : false;
                        break;
                        
                    default: 
                        $accountArray[$keyMapping] = $value[0];
                        break;
                }
            }
        }
        
        return new Tinebase_Model_EmailUser($accountArray, true);
    }
    
    /**
     * convert object with user data to ldap data array
     * 
     * @param  Tinebase_Model_FullUser  $_user
     * @param  array                    $_ldapData   the data to be written to ldap
     * @param  array                    $_ldapEntry  the data currently stored in ldap 
     */
    protected function _user2Ldap(Tinebase_Model_FullUser $_user, array &$_ldapData, array &$_ldapEntry = array())
    {
        if ($this instanceof Tinebase_EmailUser_Smtp_Interface) {
            if (empty($_user->smtpUser)) {
                return;
            }
            $mailSettings = $_user->smtpUser;
        } else {
            if (empty($_user->imapUser)) {
                return;
            }
            $mailSettings = $_user->imapUser;
        }
        
        foreach ($this->_propertyMapping as $objectProperty => $ldapAttribute) {
            $value = empty($mailSettings->{$objectProperty}) ? array() : $mailSettings->{$objectProperty};
            
            switch($objectProperty) {
                case 'emailMailQuota':
                    // convert to bytes
                    $_ldapData[$ldapAttribute] = !empty($mailSettings->{$objectProperty}) ? Tinebase_Helper::convertToBytes($mailSettings->{$objectProperty} . 'M') : array();
                    break;
                    
                case 'emailUID':
                    $_ldapData[$ldapAttribute] = $this->_appendDomain($_user->accountLoginName);
                    break;
                    
                case 'emailGID':
                    $_ldapData[$ldapAttribute] = $this->_config['emailGID'];
                    break;
                    
                case 'emailForwardOnly':
                    $_ldapData[$ldapAttribute] = ($mailSettings->{$objectProperty} == true) ? 'forwardonly' : array();
                    break;
                    
                case 'emailAddress';
                    $_ldapData[$ldapAttribute] = $_user->accountEmailAddress;
                    break;

                case 'emailAliases':
                case 'emailForwards':
                    $_ldapData[$ldapAttribute] = $_user->{$objectProperty} instanceof Tinebase_Record_RecordSet
                        ? $_user->{$objectProperty}->email
                        : $_user->{$objectProperty};
                    break;

                default:
                    $_ldapData[$ldapAttribute] = $mailSettings->{$objectProperty};
                    break;
            }
        }
        
        if ((isset($this->_propertyMapping['emailForwards']) || array_key_exists('emailForwards', $this->_propertyMapping)) && empty($_ldapData[$this->_propertyMapping['emailForwards']])) {
            $_ldapData[$this->_propertyMapping['emailForwardOnly']] = array();
        }
        
        // check if user has all required object classes. This is needed
        // when updating users which where created using different requirements
        foreach ($this->_requiredObjectClass as $className) {
            if (! in_array($className, $_ldapData['objectclass'])) {
                // merge all required classes at once
                $_ldapData['objectclass'] = array_unique(array_merge($_ldapData['objectclass'], $this->_requiredObjectClass));
                break;
            }
        }
    }
}  

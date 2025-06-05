<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

use Tine\SDDL_Parser\ACE;
use Tine\SDDL_Parser\GUID;
use Tine\SDDL_Parser\SDDL;
use Tine\SDDL_Parser\SID;

/**
 * User Samba4 ldap backend
 *
 * @package     Tinebase
 * @subpackage  User
 */
class Tinebase_User_ActiveDirectory extends Tinebase_User_Ldap
{
    // TODO move more duplicated (in User/Group AD controllers) code into traits
    use Tinebase_ActiveDirectory_DomainConfigurationTrait;

    // see http://www.selfadsi.de/ads-attributes/user-userAccountControl.htm for more constants
    public const ACCOUNTDISABLE = 2;
    public const PASSWD_CANT_CHANGE = 64;
    public const NORMAL_ACCOUNT = 512;

    /**
     * mapping of ldap attributes to class properties
     *
     * @var array
     */
    protected $_rowNameMapping = array(
        'accountDisplayName'        => 'displayname',
        'accountFullName'           => 'cn',
        'accountFirstName'          => 'givenname',
        'accountLastName'           => 'sn',
        'accountLoginName'          => 'samaccountname',
        'accountLastPasswordChange' => 'pwdlastset',
        'accountExpires'            => 'accountexpires',
        'accountPrimaryGroup'       => 'primarygroupid',
        'accountEmailAddress'       => 'mail',
            
        'profilePath'               => 'profilepath',
        'logonScript'               => 'scriptpath',
        'homeDrive'                 => 'homedrive',
        'homePath'                  => 'homedirectory',
        
        #'accountStatus'             => 'shadowinactive'
    );

    /**
     * objectclasses required by this backend
     *
     * @var array
     */
    protected $_requiredObjectClass = array(
        'top',
        'user',
        'person',
        'organizationalPerson'
    );

    /**
     * the basic group ldap filter (for example the objectclass)
     *
     * @var string
     */
    protected $_groupBaseFilter = 'objectclass=group';

    /**
     * the basic user ldap filter (for example the objectclass)
     *
     * @var string
     */
    protected $_userBaseFilter = 'objectclass=user';

    /**
     * the constructor
     *
     * @param  array  $_options  Options used in connecting, binding, etc.
     * @throws Tinebase_Exception_Backend_Ldap
     */
    public function __construct(array $_options = array())
    {
        if(empty($_options['userUUIDAttribute'])) {
            $_options['userUUIDAttribute']  = 'objectGUID';
        }
        if(empty($_options['groupUUIDAttribute'])) {
            $_options['groupUUIDAttribute'] = 'objectGUID';
        }
        if(empty($_options['baseDn'])) {
            $_options['baseDn']             = $_options['userDn'];
        }
        if(empty($_options['userFilter'])) {
            $_options['userFilter']         = 'objectclass=user';
        }
        if(empty($_options['userSearchScope'])) {
            $_options['userSearchScope']    = Zend_Ldap::SEARCH_SCOPE_SUB;
        }
        if(empty($_options['groupFilter'])) {
            $_options['groupFilter']        = 'objectclass=group';
        }
        
        parent::__construct($_options);
        
        if ($this->_options['useRfc2307']) {
            $this->_requiredObjectClass[] = 'posixAccount';
            $this->_requiredObjectClass[] = 'shadowAccount';
            
            $this->_rowNameMapping['accountHomeDirectory'] = 'unixhomedirectory';
            $this->_rowNameMapping['accountLoginShell']    = 'loginshell';
        }

        $this->_additionalLdapAttributesToFetch[] = 'ntsecuritydescriptor';
    }
    
    /**
     * add an user
     * 
     * @param   Tinebase_Model_FullUser  $_user
     * @return  Tinebase_Model_FullUser|NULL
     */
    public function addUserToSyncBackend(Tinebase_Model_FullUser $_user)
    {
        if ($this->isReadOnlyUser($_user->getId())) {
            return null;
        }
        
        $ldapData = $this->_user2ldap($_user);
        if ($this->_writeGroupsIds) {
            unset($ldapData[$this->_userUUIDAttribute]);
        }

        $ldapData = array_merge($ldapData, $this->getLdapPasswordData(
            Tinebase_User_PasswordPolicy::generatePolicyConformPassword())
        );

        // will be added later
        $primaryGroupId = $ldapData['primarygroupid'];
        unset($ldapData['primarygroupid']);
        
        $ldapData['objectclass'] = $this->_requiredObjectClass;

        foreach ($this->_ldapPlugins as $plugin) {
            /** @var Tinebase_User_Plugin_LdapInterface $plugin */
            $plugin->inspectAddUser($_user, $ldapData);
        }

        $dn = $this->generateDn($_user);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  ldapData: ' . print_r($ldapData, true));

        $this->_ldap->add($dn, $ldapData);
                
        $userId = $this->_ldap->getEntry($dn, array($this->_userUUIDAttribute));
        $userId = $this->_decodeAccountId($userId[$this->_userUUIDAttribute][0]);
        if ($this->_writeGroupsIds) {
            $_user->xprops()[static::class]['syncId'] = $userId;
        }

        // add user to primary group and set primary group
        /** @noinspection PhpUndefinedMethodInspection */
        Tinebase_Group::getInstance()->addGroupMemberInSyncBackend(Tinebase_Config::getInstance()->
            {Tinebase_Config::USERBACKEND}->{Tinebase_Config::SYNCOPTIONS}->{Tinebase_Config::SYNC_DEVIATED_PRIMARY_GROUP_UUID}
            ?: $_user->accountPrimaryGroup, $userId, false);
        
        // set primary group id
        $this->_ldap->updateProperty($dn, array('primarygroupid' => $primaryGroupId));

        $user = $this->getUserByPropertyFromSyncBackend('accountId', $userId, 'Tinebase_Model_FullUser');

        if ($_user->accountId && Tinebase_Config::getInstance()->
            {Tinebase_Config::USERBACKEND}->{Tinebase_Config::SYNCOPTIONS}->{Tinebase_Config::PWD_CANT_CHANGE}
        ) {
            $user->accountId = $_user->accountId;
            $user->xprops()[static::class]['syncId'] = $_user->xprops()[static::class]['syncId'];
            $this->updateUserInSyncBackend($user);
            $user = $this->getUserByPropertyFromSyncBackend('accountId', $user->accountId,
                'Tinebase_Model_FullUser');
        }

        return $user;
    }
    
    /**
     * sets/unsets expiry date in ldap backend
     *
     * @param   mixed              $_accountId
     * @param   Tinebase_DateTime  $_expiryDate
     */
    public function setExpiryDateInSyncBackend($_accountId, $_expiryDate)
    {
        if ($this->isReadOnlyUser(Tinebase_Model_User::convertId($_accountId))) {
            return;
        }
        
        $metaData = $this->_getMetaData($_accountId);

        if ($_expiryDate instanceof DateTime) {
            $ldapData['accountexpires'] = bcmul(bcadd($_expiryDate->getTimestamp(), '11644473600'), '10000000');
            
            if ($this->_options['useRfc2307']) {
                // days since Jan 1, 1970
                $ldapData = array_merge($ldapData, array(
                    'shadowexpire' => floor($_expiryDate->getTimestamp() / 86400)
                ));
            }
        } else {
            $ldapData = array(
                'accountexpires' => '9223372036854775807'
            );
            
            if ($this->_options['useRfc2307']) {
                $ldapData = array_merge($ldapData, array(
                    'shadowexpire' => array()
                ));
            }
        }

        foreach ($this->_ldapPlugins as $plugin) {
            /** @var Tinebase_User_LdapPlugin_Interface $plugin */
            $plugin->inspectExpiryDate($_expiryDate, $ldapData);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " {$metaData['dn']}  $ldapData: " . print_r($ldapData, true));

        $this->_ldap->update($metaData['dn'], $ldapData);
    }

    protected function getLdapPasswordData(string $password): array
    {
        $ldapData = [
            'unicodePwd' => $this->_encodePassword($password),
        ];
        if ($this->_options['useRfc2307'] ?? false) {
            $ldapData['shadowlastchange'] = floor(Tinebase_DateTime::now()->getTimestamp() / 86400);
        }
        return $ldapData;
    }

    public function setPasswordInSyncBackend(Tinebase_Model_FullUser $user, string $_password, bool $_encrypt = true, bool $_mustChange = false): void
    {
        $metaData = $this->_getMetaData($user);

        $ldapData = $this->getLdapPasswordData($_password);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $metaData['dn']);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE))
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));

        $this->_ldap->updateProperty($metaData['dn'], $ldapData);
    }

    /**
     * set the password for given account
     *
     * @param   string  $_userId
     * @param   string  $_password
     * @param   bool    $_encrypt encrypt password
     * @param   bool    $_mustChange
     * @param   bool $ignorePwPolicy
     * @return  void
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public function setPassword($_userId, $_password, $_encrypt = TRUE, $_mustChange = null, $ignorePwPolicy = false): void
    {
        if ($this->isReadOnlyUser(Tinebase_Model_User::convertId($_userId))) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' Read only LDAP - let sql parent handle it');
            parent::setPassword($_userId, $_password, $_encrypt, $_mustChange, $ignorePwPolicy);
            return;
        }
        
        $user = $_userId instanceof Tinebase_Model_FullUser ? $_userId : $this->getFullUserById($_userId);

        if (! $ignorePwPolicy) {
            Tinebase_User_PasswordPolicy::checkPasswordPolicy($_password, $user);
        }

        $this->setPasswordInSyncBackend($user, $_password, $_encrypt, (bool)$_mustChange);

        if ($this->_options[Tinebase_Config::USERBACKEND_WRITE_PW_TO_SQL]) {
            $this->_updatePasswordProperties($user->getId(), $_password, $_encrypt, $_mustChange);
        } else {
            try {
                // update last modify timestamp in sql backend too
                $this->_setAccountPasswordProperties($user->getId());
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__ . ' ' . $tenf);
            }
        }
        $this->_setPluginsPassword($user, $_password, $_encrypt);

        $this->firePasswordEvent($user, $_password);

        $accountData['id'] = $user->getId();
        $oldPassword = new Tinebase_Model_UserPassword(array('id' => $user->getId()), true);
        $newPassword = new Tinebase_Model_UserPassword($accountData, true);
        $this->_writeModLog($newPassword, $oldPassword);
    }
    
    /**
     * update user status (enabled or disabled)
     *
     * @param   mixed   $_accountId
     * @param   string  $_status
     */
    public function setStatusInSyncBackend($_accountId, $_status)
    {
        if ($this->isReadOnlyUser(Tinebase_Model_User::convertId($_accountId))) {
            return;
        }
        
        $metaData = $this->_getMetaData($_accountId);
        
        if ($_status == 'enabled') {
            $ldapData = array(
                'useraccountcontrol' => $metaData['useraccountcontrol'][0] &= ~self::ACCOUNTDISABLE
            );
            if ($this->_options['useRfc2307']) {
                $ldapData = array_merge($ldapData, array(
                    'shadowMax'      => 999999,
                    'shadowInactive' => array()
                ));
            }
        } else {
            $ldapData = array(
                'useraccountcontrol' => $metaData['useraccountcontrol'][0] |=  self::ACCOUNTDISABLE
            );
            if ($this->_options['useRfc2307']) {
                $ldapData = array_merge($ldapData, array(
                    'shadowMax'      => 1,
                    'shadowInactive' => 1
                ));
            }
        }

        foreach ($this->_ldapPlugins as $plugin) {
            /** @var Tinebase_User_LdapPlugin_Interface $plugin */
            $plugin->inspectStatus($_status, $ldapData);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " {$metaData['dn']}  ldapData: " . print_r($ldapData, true));

        $this->_ldap->update($metaData['dn'], $ldapData);
    }
    
    /**
     * updates an existing user
     *
     * @todo check required objectclasses?
     *
     * @param  Tinebase_Model_FullUser  $_account
     * @return Tinebase_Model_FullUser|null
     */
    public function updateUserInSyncBackend(Tinebase_Model_FullUser $_account)
    {
        if ($this->isReadOnlyUser($_account->getId())) {
            return null;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        Tinebase_Group::getInstance()->addGroupMemberInSyncBackend($_account->accountPrimaryGroup, $_account->getId(), false);
        
        $ldapEntry = $this->_getLdapEntry('accountId', $_account);

        $ldapData = $this->_user2ldap($_account, $ldapEntry);
        
        foreach ($this->_ldapPlugins as $plugin) {
            /** @var Tinebase_User_Plugin_LdapInterface $plugin */
            $plugin->inspectUpdateUser($_account, $ldapData, $ldapEntry);
        }

        // do we need to rename the entry?
        // TODO move to rename()
        $dn = Zend_Ldap_Dn::factory($ldapEntry['dn'], null);
        $rdn = $dn->getRdn();
        if ($rdn['CN'] != $ldapData['cn']) {
            $newDN = $this->generateDn($_account);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  rename ldap entry to: ' . $newDN);
            $this->_ldap->rename($dn, $newDN);
            $ldapEntry['dn'] = $newDN;
        }

        // no need to update this attribute, it's not allowed to change and even might not be updateable
        unset($ldapData[$this->_userUUIDAttribute]);

        // remove cn as samba forbids updating the CN (even if it does not change...
        // 0x43 (Operation not allowed on RDN; 00002016: Modify of RDN 'CN' on CN=...,CN=Users,DC=example,DC=org
        // not permitted, must use 'rename' operation instead
        unset($ldapData['cn']);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . '  $dn: ' . $ldapEntry['dn']);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE))
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . '  $ldapData: ' . print_r($ldapData, true));

        $this->_ldap->update($ldapEntry['dn'], $ldapData);

        // refetch user from ldap backend
        $user = $this->getUserByPropertyFromSyncBackend('accountId', $_account, 'Tinebase_Model_FullUser');

        return $user;
    }
    
    /**
     * convert binary id to plain text id
     * 
     * @param  string  $accountId
     * @return string
     */
    protected function _decodeAccountId($accountId)
    {
        return match ($this->_userUUIDAttribute) {
            'objectguid' => Tinebase_Ldap::decodeGuid($accountId),
            'objectsid' => Tinebase_Ldap::decodeSid($accountId),
            default => $accountId,
        };
    }
    
    /**
     * convert plain text id to binary id
     * 
     * @param  string  $accountId
     * @return string
     */
    protected function _encodeAccountId($accountId)
    {
        return match ($this->_userUUIDAttribute) {
            'objectguid' => Tinebase_Ldap::encodeGuid($accountId),
            default => $accountId,
        };
        
    }
    
    /**
     * generates dn for new user
     *
     * @param  Tinebase_Model_FullUser $_account
     * @return string
     */
    public function generateDn(Tinebase_Model_FullUser $_account)
    {
        $newDn = 'cn=' . Zend_Ldap_Filter_Abstract::escapeValue($_account->accountFullName) . ",{$this->_baseDn}";

        return $newDn;
    }
    
    /**
     * Returns a user obj with raw data from ldap
     *
     * @param array $_userData
     * @param string $_accountClass
     * @return Tinebase_Record_Interface
     */
    protected function _ldap2User(array $_userData, $_accountClass = 'Tinebase_Model_FullUser')
    {
        $errors = false;
        
        foreach ($_userData as $key => $value) {
            if (is_int($key)) {
                continue;
            }
            $keyMapping = array_search($key, $this->_rowNameMapping);
            if ($keyMapping !== FALSE) {
                switch($keyMapping) {
                    case 'accountExpires':
                        if ($value === '0' || $value[0] === '0' || $value[0] === '9223372036854775807') {
                            $accountArray[$keyMapping] = null;
                        } else {
                            $accountArray[$keyMapping] = self::convertADTimestamp($value[0]);
                        }
                        break;
                        
                    case 'accountLastPasswordChange':
                        $accountArray[$keyMapping] = self::convertADTimestamp($value[0]);
                        break;
                        
                    case 'accountId':
                        $accountArray[$keyMapping] = $this->_decodeAccountId($value[0]);
                        
                        break;
                        
                    default:
                        $accountArray[$keyMapping] = $value[0];
                        break;
                }
            }
        }

        $accountArray['accountStatus'] = (isset($_userData['useraccountcontrol']) && ($_userData['useraccountcontrol'][0] & self::ACCOUNTDISABLE)) ? 'disabled' : 'enabled';
        if ($accountArray['accountExpires'] instanceof Tinebase_DateTime && Tinebase_DateTime::now()->compare($accountArray['accountExpires']) === 1) {
            $accountArray['accountStatus'] = Tinebase_Model_User::ACCOUNT_STATUS_EXPIRED;
        }
        
        /*
        $domainConfig = $this->getDomainConfiguration();
        $maxPasswordAge = abs(bcdiv($domainConfig['maxpwdage'][0], '10000000'));
        if ($maxPasswordAge > 0 && isset($accountArray['accountLastPasswordChange'])) {
            $accountArray['accountExpires'] = clone $accountArray['accountLastPasswordChange'];
            $accountArray['accountExpires']->addSecond($maxPasswordAge);
            
            if (Tinebase_DateTime::now()->compare($accountArray['accountExpires']) === 1) {
                $accountArray['accountStatus'] = 'disabled';
            }
        }*/

        if (empty($accountArray['accountLastName']) && !empty($accountArray['accountFullName'])) {
            $accountArray['accountLastName'] = $accountArray['accountFullName'];
        }
        
        if ($errors) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not instantiate account object for ldap user ' . print_r($_userData, 1));
            $accountObject = null;
        } else {
            $accountObject = new $_accountClass($accountArray, TRUE);
            if (isset($_userData['uidnumber'])) {
                $accountObject->xprops()['uidnumber'] = $_userData['uidnumber'][0];
            }
        }
        
        if ($accountObject instanceof Tinebase_Model_FullUser) {
            $accountObject->sambaSAM = new Tinebase_Model_SAMUser($accountArray);
        }
        
        return $accountObject;
    }

    /**
     * convert windows nt timestamp
     *
     * The timestamp is the number of 100-nanoseconds intervals (1 nanosecond = one billionth of a second)
     *  since Jan 1, 1601 UTC.
     *
     * @param $timestamp
     * @return Tinebase_DateTime
     *
     * @see http://www.epochconverter.com/epoch/ldap-timestamp.php
     */
    public static function convertADTimestamp($timestamp)
    {
        return new Tinebase_DateTime(bcsub(bcdiv((string) $timestamp, '10000000'), '11644473600'));
    }

    /**
     * returns array of ldap data
     *
     * @param  Tinebase_Model_FullUser $_user
     * @param array $_ldapEntry
     * @return array
     */
    protected function _user2ldap(Tinebase_Model_FullUser $_user, array $_ldapEntry = array())
    {
        $ldapData = array(
            'useraccountcontrol' => isset($_ldapEntry['useraccountcontrol']) ? $_ldapEntry['useraccountcontrol'][0] : self::NORMAL_ACCOUNT
        );
        if (Tinebase_Config::getInstance()->{Tinebase_Config::USERBACKEND}->{Tinebase_Config::SYNCOPTIONS}->{Tinebase_Config::PWD_CANT_CHANGE}
                && ($_ldapEntry['ntsecuritydescriptor'][0] ?? false)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' start parsing sddl');
            try {
                $sddl = SDDL::fromBytes($_ldapEntry['ntsecuritydescriptor'][0]);
                $foundSelf = false;
                $foundEveryone = false;
                $setAceData = function(ACE\ObjectAccess $ace): void {
                    $ace->setType(ACE::ACETYPE_ACCESS_DENIED_OBJECT);
                    $ace->setAccessMask(ACE::ACCESS_MASK_ADS_RIGHT_DS_CONTROL_ACCESS);
                    $ace->setInheritedObject(null);
                    $ace->setFlags(0);
                    $ace->setUniqueFlags(ACE::ACE_OBJECT_TYPE_PRESENT);
                };

                foreach ($sddl->getDACL()->getACEs() as $offset => $ace) {
                    if ($ace instanceof ACE\ObjectAccess && $ace->getObject()?->getStringForm() === GUID::CHANGE_PASSWORD_GUID) {
                        if ($ace->getSID()->getStringForm() === SID::SID_EVERYONE) {
                            if ($foundEveryone) {
                                $sddl->getDACL()->removeACE($offset);
                            } else {
                                $foundEveryone = true;
                                $setAceData($ace);
                            }
                        } elseif ($ace->getSID()->getStringForm() === SID::SID_NT_AUTHORITY_SELF) {
                            if ($foundSelf) {
                                $sddl->getDACL()->removeACE($offset);
                            } else {
                                $foundSelf = true;
                                $setAceData($ace);
                            }
                        }
                    }
                }

                if (!$foundSelf) {
                    $sddl->getDACL()->addACE(new ACE\ObjectAccess(
                        binaryForm: '',
                        flags: 0,
                        type: ACE::ACETYPE_ACCESS_DENIED_OBJECT,
                        accessMask: ACE::ACCESS_MASK_ADS_RIGHT_DS_CONTROL_ACCESS,
                        uniqueFlags: ACE::ACE_OBJECT_TYPE_PRESENT,
                        object: GUID::fromString(GUID::CHANGE_PASSWORD_GUID),
                        inheritedObject: null,
                        sid: SID::fromString(SID::SID_NT_AUTHORITY_SELF)
                    ));
                }
                if (!$foundEveryone) {
                    $sddl->getDACL()->addACE(new ACE\ObjectAccess(
                        binaryForm: '',
                        flags: 0,
                        type: ACE::ACETYPE_ACCESS_DENIED_OBJECT,
                        accessMask: ACE::ACCESS_MASK_ADS_RIGHT_DS_CONTROL_ACCESS,
                        uniqueFlags:ACE::ACE_OBJECT_TYPE_PRESENT,
                        object: GUID::fromString(GUID::CHANGE_PASSWORD_GUID),
                        inheritedObject: null,
                        sid: SID::fromString(SID::SID_EVERYONE)
                    ));
                }

                $ldapData['ntsecuritydescriptor'] = $sddl->toBytes();
                if ($_ldapEntry['ntsecuritydescriptor'][0] !== $ldapData['ntsecuritydescriptor'] && Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' changing ntsecuritydescriptor');
            } catch (\Tine\SDDL_Parser\ParserException $e) {
                Tinebase_Exception::log($e);
            }
        }

        if (isset($_user->xprops()['uidnumber'])) {
            $_ldapEntry['uidnumber'] = $_user->xprops()['uidnumber'];
        }

        foreach ($_user as $key => $value) {
            $ldapProperty = (isset($this->_rowNameMapping[$key]) || array_key_exists($key, $this->_rowNameMapping)) ? $this->_rowNameMapping[$key] : false;

            if ($ldapProperty === false) {
                continue;
            }
            
            switch ($key) {
                case 'accountLastPasswordChange':
                    // field is readOnly
                    break;
                    
                case 'accountExpires':
                    if ($value instanceof DateTime) {
                        $ldapData[$ldapProperty] = bcmul(bcadd($value->getTimestamp(), '11644473600'), '10000000');
                    } else {
                        $ldapData[$ldapProperty] = '9223372036854775807';
                    }
                    break;
                    
                case 'accountStatus':
                    if ($value === Tinebase_Model_User::ACCOUNT_STATUS_ENABLED) {
                        // unset account disable flag
                        $ldapData['useraccountcontrol'] &= ~self::ACCOUNTDISABLE;
                    } elseif ($value === Tinebase_Model_User::ACCOUNT_STATUS_DISABLED) {
                        // set account disable flag
                        $ldapData['useraccountcontrol'] |=  self::ACCOUNTDISABLE;
                    }
                    break;
                    
                case 'accountPrimaryGroup':
                    if ($deviateGroupId = Tinebase_Config::getInstance()->{Tinebase_Config::USERBACKEND}->{Tinebase_Config::SYNCOPTIONS}->{Tinebase_Config::SYNC_DEVIATED_PRIMARY_GROUP_UUID}) {
                        $value = $deviateGroupId;
                    }
                    /** @noinspection PhpUndefinedMethodInspection */
                    $ldapData[$ldapProperty] = Tinebase_Group::getInstance()->resolveUUIdToGIdNumber($value);
                    if ($this->_options['useRfc2307']) {
                        /** @noinspection PhpUndefinedMethodInspection */
                        $ldapData['gidNumber'] = Tinebase_Group::getInstance()->resolveGidNumber($value);
                    }
                    break;
                    
                default:
                    $ldapData[$ldapProperty] = $value;
                    break;
            }
        }

        $domainConfig = $this->getDomainConfiguration();
        $ldapData['name'] = $ldapData['cn'];
        $ldapData['userPrincipalName'] =  $_user->accountLoginName . '@' . $domainConfig['domainName'];
        
        if ($this->_options['useRfc2307']) {
            // homedir is an required attribute
            if (empty($ldapData['unixhomedirectory'])) {
                $ldapData['unixhomedirectory'] = '/dev/null';
            }
            
            // set uidNumber only when not set in AD already
            if (empty($_ldapEntry['uidnumber'])) {
                $ldapData['uidnumber'] = $this->_generateUidNumber();
            }
            /** @noinspection PhpUndefinedMethodInspection */
            $ldapData['gidnumber'] = Tinebase_Group::getInstance()->resolveGidNumber($_user->accountPrimaryGroup);
            
            $ldapData['msSFU30NisDomain'] = Tinebase_Helper::array_value(0, explode('.', (string) $domainConfig['domainName']));
        }
        
        if (isset($_user->sambaSAM) && $_user->sambaSAM instanceof Tinebase_Model_SAMUser) {
            $ldapData['profilepath']   = $_user->sambaSAM->profilePath;
            $ldapData['scriptpath']    = $_user->sambaSAM->logonScript;
            $ldapData['homedrive']     = $_user->sambaSAM->homeDrive;
            $ldapData['homedirectory'] = $_user->sambaSAM->homePath;
            
        }
        
        $ldapData['objectclass'] = $_ldapEntry['objectclass'] ?? array();
        
        // check if user has all required object classes. This is needed
        // when updating users which where created using different requirements
        foreach ($this->_requiredObjectClass as $className) {
            if (! in_array($className, $ldapData['objectclass'])) {
                // merge all required classes at once
                $ldapData['objectclass'] = array_unique(array_merge($ldapData['objectclass'], $this->_requiredObjectClass));
                break;
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE))
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' LDAP data ' . print_r($ldapData, true));
        
        return $ldapData;
    }
    
    /**
    * Encode a password to UTF-16LE
    *
    * @param string $password the plain password
    * 
    * @return string
    */
    protected function _encodePassword($password)
    {
        $password        = '"' . $password . '"';
        $passwordLength  = strlen($password);
        
        $encodedPassword = null;

        for ($pos = 0; $pos < $passwordLength; $pos++) {
            $encodedPassword .= "{$password[$pos]}\000";
        }
        
        return $encodedPassword;
    }
}

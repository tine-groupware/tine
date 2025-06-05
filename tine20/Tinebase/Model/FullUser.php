<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * defines the datatype for a full users
 * 
 * this datatype contains all information about an user
 * the usage of this datatype should be restricted to administrative tasks only
 * 
 * @package     Tinebase
 * @subpackage  User
 *
 * @property    string                      $accountStatus
 * @property    Tinebase_Model_SAMUser      $sambaSAM            object holding samba settings
 * @property    string                      $accountEmailAddress email address of user
 * @property    Tinebase_DateTime           $accountExpires      date when account expires
 * @property    string                      $accountFullName     fullname of the account
 * @property    string                      $accountDisplayName  displayname of the account
 * @property    string                      $accountLoginName    account login name
 * @property    string                      $accountLoginShell   account login shell
 * @property    string                      $accountPrimaryGroup primary group id
 * @property    string                      $container_id
 * @property    string                      $contact_id
 * @property    string                      $configuration
 * @property    array                       $groups              list of group memberships
 * @property    Tinebase_DateTime           $lastLoginFailure    time of last login failure
 * @property    string                      $loginFailures       login failures by client type
 * @property    string                      $visibility          displayed/hidden in/from addressbook
 * @property    string                      $type
 * @property    Tinebase_Model_EmailUser    $emailUser
 * @property    Tinebase_Model_EmailUser    $imapUser
 * @property    Tinebase_Model_EmailUser    $smtpUser
 * @property    Tinebase_DateTime           $accountLastPasswordChange      date when password was last changed
 * @property    bool                        $password_must_change
 * @property    Tinebase_Record_RecordSet   $mfa_configs
 */
class Tinebase_Model_FullUser extends Tinebase_Model_User
{
    public const XPROP_PERSONAL_FS_QUOTA = 'personalFSQuota';

    /**
     * external email user ids (for example in dovecot/postfix sql)
     */
    public const XPROP_EMAIL_USERID_IMAP = 'emailUserIdImap';
    public const XPROP_EMAIL_USERID_SMTP = 'emailUserIdSmtp';

    // don't create and show users system mail account
    public const XPROP_FMAIL_SKIP_MAILACCOUNT = 'emailSkipFmailAccount';
    public const XPROP_HAS_RANDOM_PWD = 'hasRandomPwd';

    public const USER_TYPE_SYSTEM = 'system';
    public const USER_TYPE_USER = 'user';
    public const USER_TYPE_VOLUNTEER = 'volunteer';

    public const PASSWORD_EXPIRED = 'expired';
    public const PASSWORD_FLAGGED = 'flagged';
    public const PASSWORD_NEVER_CHANGED = 'neverChanged';


    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        'recordName'        => 'User',
        'recordsName'       => 'Users', // ngettext('User', 'Users', n)
        'hasRelations'      => false,
        'hasCustomFields'   => false,
        'hasNotes'          => false,
        'hasTags'           => false,
        'hasXProps'         => true,
        'modlogActive'      => true,
        self::HAS_DELETED_TIME_UNIQUE => true,
        'hasAttachments'    => false,
        'createModule'      => false,
        'exposeHttpApi'     => false,
        'exposeJsonApi'     => false,

        // container_id is used by admin module to create an account's contact directly in the proper adb container
        'containerProperty' => 'container_id',
        // ????
        'containerUsesFilter' => false,

        'titleProperty'     => 'accountDisplayName',
        'appName'           => 'Tinebase',
        'modelName'         => 'FullUser',
        'idProperty'        => 'accountId',

        'filterModel'       => [],

        self::TABLE         => [
            self::NAME          => 'accounts'
        ],

        'fields'            => [
            'accountLoginName'              => [
                'type'                          => 'string',
                'validators'                    => ['presence' => 'required'],
                'inputFilters'                  => [
                    Zend_Filter_StringTrim::class => null,
                    Zend_Filter_StringToLower::class => null,
                ],
            ],
            'accountDisplayName'            => [
                'type'                          => 'string',
                'validators'                    => ['presence' => 'required'],
                'inputFilters'                  => [Zend_Filter_StringTrim::class => null],
            ],
            'accountLastName'               => [
                'type'                          => 'string',
                'validators'                    => ['presence' => 'required'],
                'inputFilters'                  => [Zend_Filter_StringTrim::class => null],
            ],
            'accountFirstName'              => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'                  => [Zend_Filter_StringTrim::class => null],
            ],
            'accountEmailAddress'           => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'accountFullName'               => [
                'type'                          => 'string',
                'validators'                    => ['presence' => 'required'],
                'inputFilters'                  => [Zend_Filter_StringTrim::class => null],
            ],
            'contact_id'                    => [
                self::TYPE                      => self::TYPE_RECORD,
                self::CONFIG                    => [
                    self::APP_NAME                  => Addressbook_Config::APP_NAME,
                    self::MODEL_NAME                => Addressbook_Model_Contact::MODEL_NAME_PART,
                ],
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::UI_CONFIG             => [
                    'recordEditPluginConfig'    => [
                        'allowCreateNew'            => true,
                    ],
                ],
            ],
            'accountLastLogin'              => [
                'type'                          => 'datetime',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'accountLastLoginfrom'          => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'accountLastPasswordChange'     => [
                'type'                          => 'datetime',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'accountStatus'                 => [
                'type'                          => 'string',
                'validators'                    => ['inArray' => [
                    self::ACCOUNT_STATUS_ENABLED,
                    self::ACCOUNT_STATUS_DISABLED,
                    self::ACCOUNT_STATUS_BLOCKED,
                    self::ACCOUNT_STATUS_EXPIRED
                ], Zend_Filter_Input::DEFAULT_VALUE => self::ACCOUNT_STATUS_ENABLED],
            ],
            'accountExpires'                => [
                'type'                          => 'datetime',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'accountPrimaryGroup'           => [
                //'type'                          => 'record',
                'validators'                    => ['presence' => 'required'],
            ],
            'accountHomeDirectory'          => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'accountLoginShell'             => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'lastLoginFailure'              => [
                'type'                          => 'datetime',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'loginFailures'                 => [
                self::TYPE                      => self::TYPE_JSON,
                self::VALIDATORS        => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
            ],
            'sambaSAM'                      => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'openid'                        => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'                  => [Zend_Filter_Empty::class => null],
            ],
            'emailUser'                     => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'groups'                        => [
                // ??? array? 'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'imapUser'                      => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'smtpUser'                      => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'type' => [
                self::LABEL => 'User type', // _('User type')
                self::TYPE => self::TYPE_KEY_FIELD,
                self::NAME => Tinebase_Config::USER_TYPES,
            ],
            'visibility'                    => [
                'type'                          => 'string',
                'validators'                    => ['inArray' => [
                    self::VISIBILITY_HIDDEN,
                    self::VISIBILITY_DISPLAYED,
                ], Zend_Filter_Input::DEFAULT_VALUE => self::VISIBILITY_DISPLAYED],
            ],
            'password_must_change'          => [
                'type'                          => 'boolean',
                'default'                       => false,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'mfa_configs'             => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::OMIT_MOD_LOG              => true,
                self::CONFIG                    => [
                    self::STORAGE                   => self::TYPE_JSON,
                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                => Tinebase_Model_MFA_UserConfig::MODEL_NAME_PART,
                ],
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                     => 'Area lock configurations', // _('Area lock configurations')
            ],
            'roles'        => array(
                self::TYPE          => self::TYPE_VIRTUAL,
                self::VALIDATORS    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ),
            'must_change_password'  => [
                self::TYPE              => self::TYPE_STRING,
                self::NULLABLE          => true,
                self::IS_VIRTUAL        => true,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ]
        ],
    ];

    
    /**
     * adds email and samba users, generates username + user password and 
     *   applies multiple options (like accountLoginNamePrefix, accountHomeDirectoryPrefix, ...)
     * 
     * @param array $options
     * @param string $password
     * @return string
     */
    public function applyOptionsAndGeneratePassword($options, $password = NULL)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' ' . print_r($options, TRUE));
        
        if (empty($this->accountPrimaryGroup)) {
            if (! empty($options['group_id'])) {
                $groupId = $options['group_id'];
            } else {
                // use default user group
                $defaultUserGroup = Tinebase_Group::getInstance()->getDefaultGroup();
                $groupId = $defaultUserGroup->getId();
            }
            $this->accountPrimaryGroup = $groupId;
        }
        
        // add prefix to login name if given
        if (! empty($options['accountLoginNamePrefix'])) {
            $this->accountLoginName = $options['accountLoginNamePrefix'] . $this->accountLoginName;
        }
        
        // short username if needed
        $this->accountLoginName = $this->shortenUsername();

        if (isset($options['afterAccountLoginName'])) {
            $options['afterAccountLoginName']($this);
        }
        
        // add home dir if empty and prefix is given (append login name)
        if (empty($this->accountHomeDirectory) && ! empty($options['accountHomeDirectoryPrefix'])) {
            $this->accountHomeDirectory = $options['accountHomeDirectoryPrefix'] . $this->accountLoginName;
        }
        
        // create email address if accountEmailDomain if given
        if (empty($this->accountEmailAddress) && ! empty($options['accountEmailDomain'])) {
            $this->accountEmailAddress = $this->accountLoginName . '@' . $options['accountEmailDomain'];
        }
        
        if (! empty($options['samba'])) {
            $this->_addSambaSettings($options['samba']);
        }
        
        if (empty($this->accountLoginShell) && ! empty($options['accountLoginShell'])) {
            $this->accountLoginShell = $options['accountLoginShell'];
        }
        
        // generate passwd (use accountLoginName or password from options or password from csv in this order)
        $userPassword = $this->accountLoginName;
        
        if (! empty($password)) {
            $userPassword = $password;
        } elseif (! empty($options['password'])) {
            $userPassword = $options['password'];
        } elseif (! empty($options['passwordGenerator']) && is_callable($options['passwordGenerator'])) {
            if (is_string($options['passwordGenerator']) && str_contains($options['passwordGenerator'],
                    'Tinebase_User_PasswordPolicy::generatePolicyConformPassword')
            ) {
                // TODO maybe the default should be a function call without param? how can we detect this?
                $userPassword = $options['passwordGenerator']();
            } else {
                // passwordGenerator function expects user as its param
                $userPassword = $options['passwordGenerator']($this);
            }
        }
        
        $this->_addEmailUser($userPassword);
        
        return $userPassword;
    }
    
    /**
     * add samba settings to user
     *
     * @param array $options
     */
    protected function _addSambaSettings($options)
    {
        $samUser = new Tinebase_Model_SAMUser(array(
            'homePath'      => (isset($options['homePath'])) ? $options['homePath'] . $this->accountLoginName : '',
            'homeDrive'     => $options['homeDrive'] ?? '',
            'logonScript'   => $options['logonScript'] ?? '',
            'profilePath'   => (isset($options['profilePath'])) ? $options['profilePath'] . $this->accountLoginName : '',
            'pwdCanChange'  => $options['pwdCanChange'] ?? new Tinebase_DateTime('@1'),
            'pwdMustChange' => $options['pwdMustChange'] ?? new Tinebase_DateTime('@2147483647')
        ));
    
        $this->sambaSAM = $samUser;
    }
    
    /**
     * add email users to record (if email set + config exists)
     *
     * @param string $_password
     */
    protected function _addEmailUser($password)
    {
        if (! empty($this->accountEmailAddress)) {
            
            if (isset($this->imapUser)) {
                $this->imapUser->emailPassword = $password;
            } else {
                $this->imapUser = new Tinebase_Model_EmailUser(array(
                    'emailPassword' => $password
                ));
            }
            
            if (isset($this->smtpUser)) {
                $this->smtpUser->emailPassword = $password;
            } else {
                $this->smtpUser = new Tinebase_Model_EmailUser(array(
                    'emailPassword' => $password
                ));
            }
        }
    }
    
    /**
     * check if windows password needs to b changed
     *  
     * @return boolean
     */
    protected function _sambaSamPasswordChangeNeeded()
    {
        if ($this->password_must_change) {
            return true;
        }
        if ($this->sambaSAM instanceof Tinebase_Model_SAMUser
            && isset($this->sambaSAM->pwdMustChange) 
            && $this->sambaSAM->pwdMustChange instanceof DateTime) 
        {
            if ($this->sambaSAM->pwdMustChange->compare(Tinebase_DateTime::now()) < 0) {
                if (!isset($this->sambaSAM->pwdLastSet)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                        . ' User ' . $this->accountLoginName . ' has to change his pw: it got never set by user');
                        
                    return true;
                    
                } else if (isset($this->sambaSAM->pwdLastSet) && $this->sambaSAM->pwdLastSet instanceof DateTime) {
                    $dateToCompare = $this->sambaSAM->pwdLastSet;
                    
                    if ($this->sambaSAM->pwdMustChange->compare($dateToCompare) > 0) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                            . ' User ' . $this->accountLoginName . ' has to change his pw: ' . $this->sambaSAM->pwdMustChange . ' > ' . $dateToCompare);
                            
                        return true;
                    }
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Password is up to date.');
                }
            }
        }

        return false;
    }
    
    /**
     * check if sql password needs to be changed
     * 
     * @return boolean
     */
    protected function _sqlPasswordChangeNeeded()
    {
        if (empty($this->accountLastPasswordChange) || $this->password_must_change) {
            return true;
        }

        $passwordChangeDays = $this->getPasswordChangeDays();

        if ($passwordChangeDays > 0) {
            $now = Tinebase_DateTime::now();
            return $this->accountLastPasswordChange->isEarlier($now->subDay($passwordChangeDays));
        }
        return false;
    }

    /**
     * return the public information of this user only
     *
     * @return Tinebase_Model_User
     */
    public function getPublicUser()
    {
        $result = new Tinebase_Model_User($this->toArray(), true);
        
        return $result;
    }
    
    /**
     * returns user login name
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->accountLoginName;
    }
    
    /**
     * returns TRUE if user has to change his/her password (compare sambaSAM->pwdMustChange with Tinebase_DateTime::now())
     *
     * @return ?string
     */
    public function mustChangePassword(): ?string
    {
        switch (Tinebase_User::getConfiguredBackend()) {
            case Tinebase_User::ACTIVEDIRECTORY:
            case Tinebase_User::LDAP:
                if($this->_sambaSamPasswordChangeNeeded()) {
                    return Tinebase_Translation::getTranslation()->translate('Your password expired.');
                }
                break;
            default:
                if (Tinebase_Auth::getConfiguredBackend() === Tinebase_Auth::SQL && $this->_sqlPasswordChangeNeeded()) {
                    return Tinebase_Translation::getTranslation()->translate('Your password expired.');
                }
                break;
        }

        // password complexity check
        if (Tinebase_Core::get(Tinebase_Core::SESSION)->mustChangePassword) {
            return Tinebase_Core::get(Tinebase_Core::SESSION)->mustChangePassword;
        }

        return null;
    }
    
    /**
     * Short username to a configured length
     */
    public function shortenUsername(int $reserveRight = 0)
    {
        $username = (string)$this->accountLoginName;
        $maxLoginNameLength = Tinebase_Config::getInstance()->get(Tinebase_Config::MAX_USERNAME_LENGTH);
        if (!empty($maxLoginNameLength) && strlen($username) > $maxLoginNameLength - $reserveRight) {
            $username = substr($username, 0, $maxLoginNameLength - $reserveRight);
        }
        
        return $username;
    }

    public function runConvertToData()
    {
        if (isset($this->_properties['configuration']) && is_array($this->_properties['configuration'])) {
            if (count($this->_properties['configuration']) > 0) {
                $this->_properties['configuration'] = json_encode($this->_properties['configuration']);
            } else {
                $this->_properties['configuration'] = null;
            }
        }

        parent::runConvertToData();
    }

    public function getEmailUserId($type = self::XPROP_EMAIL_USERID_IMAP)
    {
        return Tinebase_EmailUser_XpropsFacade::getEmailUserId($this, $type);
    }

    public function getMustChangePassword(): ?string
    {
        if (empty($this->accountLastPasswordChange)) {
            return self::PASSWORD_NEVER_CHANGED;
        }

        $mustChange = null;
        if ($this->password_must_change) {
            $mustChange = self::PASSWORD_FLAGGED;
        }

        $passwordChangeDays = $this->getPasswordChangeDays();
        if ($passwordChangeDays > 0) {
            $now = Tinebase_DateTime::now();
            $mustChange = $this->accountLastPasswordChange->isEarlier($now->subDay($passwordChangeDays)) ? self::PASSWORD_EXPIRED : $mustChange;
        }
        return $mustChange;
    }

    public function getPasswordChangeDays(): ?int
    {
        $passwordPolicy = Tinebase_Config::getInstance()->get(Tinebase_Config::USER_PASSWORD_POLICY);

        $passwordChangeDays = 0;
        if ($passwordPolicy) {
            $passwordChangeDays = $passwordPolicy->get(Tinebase_Config::PASSWORD_POLICY_CHANGE_AFTER);
        }
        return $passwordChangeDays;
    }
}

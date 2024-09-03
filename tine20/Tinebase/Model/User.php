<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * defines the datatype for simple user object
 * 
 * this user object contains only public informations
 * its primary usecase are user selection interfaces
 * 
 * @package     Tinebase
 * @subpackage  User
 * 
 * @property    string  accountId
 * @property    string  contact_id
 * @property    string  accountEmailAddress  email address of user
 * @property    string  accountDisplayName
 * @property    string  accountLastName
 * @property    string  accountFirstName
 * @property    string  $accountLoginName
 */
class Tinebase_Model_User extends Tinebase_Record_Abstract
{
    public const MODEL_NAME_PART = 'User';

    /**
     * const to describe current account accountId independent
     * 
     * @var string
     */
    const CURRENTACCOUNT = 'currentAccount';
    
    /**
     * hidden from addressbook
     * 
     * @var string
     */
    const VISIBILITY_HIDDEN    = 'hidden';
    
    /**
     * visible in addressbook
     * 
     * @var string
     */
    const VISIBILITY_DISPLAYED = 'displayed';
    
    /**
     * account is enabled
     * 
     * @var string
     */
    const ACCOUNT_STATUS_ENABLED = 'enabled';
    
    /**
     * account is disabled
     * 
     * @var string
     */
    const ACCOUNT_STATUS_DISABLED = 'disabled';
    
    /**
     * account is expired
     * 
     * @var string
     */
    const ACCOUNT_STATUS_EXPIRED = 'expired';
    
    /**
     * account is blocked
     * 
     * @var string
     */
    const ACCOUNT_STATUS_BLOCKED  = 'blocked';

    /**
     * key in $_validators/$_properties array for the filed which
     * represents the identifier
     *
     * @var string
     */
    protected $_identifier = 'accountId';

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

        'titleProperty'     => 'accountDisplayName',
        'appName'           => 'Tinebase',
        'modelName'         => 'User',
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
            ],
        ],
    ];

    /**
     * if foreign Id fields should be resolved on search and get from json
     * should have this format:
     *     array('Calendar_Model_Contact' => 'contact_id', ...)
     * or for more fields:
     *     array('Calendar_Model_Contact' => array('contact_id', 'customer_id), ...)
     * (e.g. resolves contact_id with the corresponding Model)
     *
     * @var array
     */
    protected static $_resolveForeignIdFields = array(
        'Tinebase_Model_User'        => array('created_by', 'last_modified_by')
    );

    protected static $_replicable = true;

    protected static $_forceSuperUser = false;
    
    /**
     * (non-PHPdoc)
     * @see Tinebase/Record/Tinebase_Record_Abstract#setFromArray($_data)
     * 
     * @todo need to discuss if this is the right place to do this. perhaps the client should send the fullname (and displayname), too.
     */
    public function setFromArray(array &$_data)
    {
        // make sure we run through the setFromArray
        $bypassFilter = $this->bypassFilters;
        $this->bypassFilters = true;

        if (isset($_data['accountEmailAddress'])) {
            $_data['accountEmailAddress'] = Tinebase_Helper::convertDomainToPunycode(mb_strtolower(trim($_data['accountEmailAddress'])));
        }

        parent::setFromArray($_data);

        $twigConfig = Tinebase_Config::getInstance()->{Tinebase_Config::ACCOUNT_TWIG};

        // only set accountDisplayName and accountFullName if they are not set already
        if (!isset($_data['accountDisplayName']) || '' === trim($_data['accountDisplayName'])) {
            $this->accountDisplayName = $this->applyAccountTwig('accountDisplayName',
                $twigConfig->{Tinebase_Config::ACCOUNT_TWIG_DISPLAYNAME});
        }
        if (!isset($_data['accountFullName']) || '' === trim($_data['accountFullName'])) {
            $this->accountFullName = $this->applyAccountTwig('accountFullName',
                $twigConfig->{Tinebase_Config::ACCOUNT_TWIG_FULLNAME});
        }

        if (!$bypassFilter) {
            $this->bypassFilters = false;
            $this->isValid(true);
        }
    }

    public function applyTwigTemplates()
    {
        $twigConfig = Tinebase_Config::getInstance()->{Tinebase_Config::ACCOUNT_TWIG};

        // only set properties if they are not set already
        if (!isset($this->_properties['accountDisplayName']) || '' === trim($this->_properties['accountDisplayName'])) {
            $this->accountDisplayName = $this->applyAccountTwig('accountDisplayName',
                $twigConfig->{Tinebase_Config::ACCOUNT_TWIG_DISPLAYNAME});
        }
        if (!isset($this->_properties['accountFullName']) || '' === trim($this->_properties['accountFullName'])) {
            $this->accountFullName = $this->applyAccountTwig('accountFullName',
                $twigConfig->{Tinebase_Config::ACCOUNT_TWIG_FULLNAME});
        }
        // maybe set accountLoginName before setting accountEmailAddress, eventually the latter is based on first one
        if (!isset($this->_properties['accountLoginName']) || '' === trim($this->_properties['accountLoginName'])) {
            $this->accountLoginName = $this->applyAccountTwig('accountLoginName',
                $twigConfig->{Tinebase_Config::ACCOUNT_TWIG_LOGIN});
        }

        // key does not exist => applyTwig
        // value !== null && trim(value) evaluates to '' => applyTwig
        if (!array_key_exists('accountEmailAddress', $this->_properties) ||
                (null !== $this->_properties['accountEmailAddress'] &&
                    '' === trim($this->_properties['accountEmailAddress']))) {
            $this->accountEmailAddress = Tinebase_Helper::convertDomainToPunycode(
                $this->applyAccountTwig('accountEmailAddress', $twigConfig->{Tinebase_Config::ACCOUNT_TWIG_EMAIL}));
        }
    }

    /**
     * @param bool $_recursive
     * @return array
     */
    public function toArray($_recursive = TRUE)
    {
        $result = parent::toArray($_recursive);

        if ($this->accountEmailAddress) {
            $result['accountEmailAddress'] = Tinebase_Helper::convertDomainToUnicode($this->accountEmailAddress);
        }

        return $result;
    }

    public function applyAccountTwig($name, $twig)
    {
        $locale = Tinebase_Core::getLocale();
        if (! $locale) {
            $locale = Tinebase_Translation::getLocale();
        }
        $twig = new Tinebase_Twig($locale, Tinebase_Translation::getTranslation(), [
            Tinebase_Twig::TWIG_LOADER =>
                new Tinebase_Twig_CallBackLoader(__METHOD__ . $name, time() - 1, function() use($twig) { return $twig; }),
            Tinebase_Twig::TWIG_AUTOESCAPE => false,
        ]);
        return $twig->load(__METHOD__ . $name)->render(array_merge(['email' => Tinebase_EmailUser::getConfig(Tinebase_Config::SMTP, true)], static::$twigContext, ['account' => $this]));
    }

    protected static $twigContext = [];
    public static function setTwigContext(array $data)
    {
        static::$twigContext = $data;
    }

    /**
     * check if current user has a given right for a given application
     *
     * @param string|Tinebase_Model_Application $_application the application (one of: app name, id or record)
     * @param int $_right the right to check for
     * @return bool
     */
    public function hasRight($_application, $_right)
    {
        if (true === static::$_forceSuperUser) {
            return true;
        }

        $roles = Tinebase_Acl_Roles::getInstance();
        
        return $roles->hasRight($_application, $this->accountId, $_right);
    }
    
    /**
     * returns a bitmask of rights for current user and given application
     *
     * @param string $_application the name of the application
     * @return int bitmask of rights
     */
    public function getRights($_application)
    {
        $roles = Tinebase_Acl_Roles::getInstance();
        
        return $roles->getApplicationRights($_application, $this->accountId);
    }
    
    /**
     * return the group ids current user is member of
     *
     * @return array list of group ids
     */
    public function getGroupMemberships()
    {
        $backend = Tinebase_Group::getInstance();
        
        return $backend->getGroupMemberships($this->accountId);
    }
    
    /**
     * update the lastlogin time of current user
     *
     * @param string $_ipAddress
     * @return void
     * @todo write test for that
    */
    public function setLoginTime($_ipAddress)
    {
        $backend = Tinebase_User::getInstance();
        
        return $backend->setLoginTime($this->accountId, $_ipAddress);
    }
    
    /**
     * set the password for current user
     *
     * @param string $_password
     * @return void
     */
    public function setPassword($_password)
    {
        $backend = Tinebase_User::getInstance();
        $backend->setPassword($this->accountId, $_password);
    }
    
    /**
     * returns list of applications the current user is able to use
     *
     * this function takes group memberships into user. Applications the user is able to use
     * must have the 'run' right set 
     * 
     * @param boolean $_anyRight is any right enough to geht app?
     * @return Tinebase_Record_RecordSet list of enabled applications for this user
     */
    public function getApplications($_anyRight = FALSE)
    {
        $roles = Tinebase_Acl_Roles::getInstance();
        return $roles->getApplications($this->accountId, $_anyRight);
    }
    
    /**
     * get containers of other users
     * 
     * @param string|Tinebase_Model_Application $_application
     * @param array|string $_grant
     * @return  Tinebase_Record_RecordSet set of Tinebase_Model_Container
     */
    public function getOtherUsersContainer($_application, $_grant)
    {
        $container = Tinebase_Container::getInstance();
        
        $result = $container->getOtherUsersContainer($this, $_application, $_grant);
        
        return $result;
    }
    
    /**
     * check if the current user has a given grant
     *
     * @param mixed $_containerId
     * @param string $_grant
     * @param string $_aclModel
     * @return boolean
     * @throws Tinebase_Exception_InvalidArgument
     *
     * TODO improve handling of different acl models
     */
    public function hasGrant($_containerId, $_grant, $_aclModel = 'Tinebase_Model_Container')
    {
        if (true === static::$_forceSuperUser) {
            return true;
        }

        if ($_containerId instanceof Tinebase_Record_Interface) {
            $aclModel = get_class($_containerId);
            if (! in_array($aclModel, array('Tinebase_Model_Container', 'Tinebase_Model_Tree_Node'))) {
                // fall back to param
                $aclModel = $_aclModel;
            }
        } else {
            $aclModel = $_aclModel;
        }

        switch ($aclModel) {
            case 'Tinebase_Model_Container':
                $result = Tinebase_Container::getInstance()->hasGrant($this->accountId, $_containerId, $_grant);
                break;
            case 'Tinebase_Model_Tree_Node':
                $result = Tinebase_FileSystem::getInstance()->hasGrant($this->accountId, $_containerId, $_grant);
                break;
            default:
                throw new Tinebase_Exception_InvalidArgument('ACL model not supported ');
        }

        if (!$result && Tinebase_Model_Grants::GRANT_ADMIN !== $_grant) {
            return $this->hasGrant($_containerId, Tinebase_Model_Grants::GRANT_ADMIN, $_aclModel);
        }

        return $result;
    }
    
    /**
     * converts a int, string or Tinebase_Model_User to an accountid
     *
     * @param int|string|Tinebase_Model_User $_accountId the accountid to convert
     * @return string
     * @throws Tinebase_Exception_InvalidArgument
     * 
     * TODO completely replace with TRA::convertId
     */
    static public function convertUserIdToInt($_accountId)
    {
        return (string) self::convertId($_accountId, 'Tinebase_Model_User');
    }
    
    /**
     * sanitizes account primary group and returns primary group id
     * 
     * @return string
     */
    public function sanitizeAccountPrimaryGroup()
    {
        try {
            Tinebase_Group::getInstance()->getGroupById($this->accountPrimaryGroup);
        } catch (Tinebase_Exception_Record_NotDefined $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' Could not resolve accountPrimaryGroupgroup (' . $this->accountPrimaryGroup . '): ' . $e->getMessage() . ' => set default user group id as accountPrimaryGroup for account ' . $this->getId());
            $this->accountPrimaryGroup = Tinebase_Group::getInstance()->getDefaultGroup()->getId();
        }
        
        return $this->accountPrimaryGroup;
    }

    /**
     * returns true if this record should be replicated
     *
     * @return boolean
     */
    public function isReplicable()
    {
        return static::$_replicable;
    }

    /**
     * @param boolean $isReplicable
     */
    public static function setReplicable($isReplicable)
    {
        static::$_replicable = (bool)$isReplicable;
    }

    /**
     * @param bool $bool
     */
    public static function forceSuperUser($bool = true)
    {
        static::$_forceSuperUser = (bool)$bool;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->accountDisplayName;
    }
}

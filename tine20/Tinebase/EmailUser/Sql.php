<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle
 * */

/**
 * plugin to handle sql email accounts
 * 
 * @package    Tinebase
 * @subpackage EmailUser
 */
abstract class Tinebase_EmailUser_Sql extends Tinebase_User_Plugin_SqlAbstract
{
    /**
     * user table name with prefix
     *
     * @var string
     */
    protected $_userTable = NULL;

    /**
     * schema of the table
     *
     * @var array
     */
    protected $_schema = NULL;

    /**
     * email user config
     * 
     * @var array 
     */
     protected $_config = array();
    
    /**
     * user properties mapping
     *
     * @var array
     */
    protected $_propertyMapping = array();

    /**
     * @var array
     */
    protected $_tableMapping = array();

    /**
     * config key (IMAP/SMTP)
     * 
     * @var string
     */
    protected $_configKey = NULL;
    
    /**
     * subconfig for user email backend (for example: dovecot)
     * 
     * @var string
     */
    protected $_subconfigKey =  NULL;
    
    /**
    * client id
    *
    * @var string
    */
    protected $_clientId = NULL;
    
    /**
     * the constructor
     * 
     * @param array $_options
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public function __construct(array $_options = array())
    {
        if ($this instanceof Tinebase_EmailUser_Smtp_Interface) {
            $this->_configKey = Tinebase_Config::SMTP;
        } else if ($this instanceof Tinebase_EmailUser_Imap_Interface) {
            $this->_configKey = Tinebase_Config::IMAP;
        } else {
            throw new Tinebase_Exception_UnexpectedValue('Plugin must be instance of Tinebase_EmailUser_Smtp_Interface or Tinebase_EmailUser_Imap_Interface');
        }
        
        // get email user backend config options (host, dbname, username, password, port)
        $emailConfig = Tinebase_Config::getInstance()->get($this->_configKey, new Tinebase_Config_Struct())->toArray();
        
        // merge _config and email backend config
        if ($this->_subconfigKey) {
            if (! isset($emailConfig[$this->_subconfigKey])) {
                throw new Tinebase_Exception_UnexpectedValue(
                    'Email user config is broken - subconfig key "' . $this->_subconfigKey . '" missing');
            }
            // flatten array
            $emailConfig = array_merge($emailConfig[$this->_subconfigKey], $emailConfig);
        }
        // merge _config and email backend config
        $this->_config = array_merge($this->_config, $emailConfig);
        
        // _tablename (for example "dovecot_users")
        $this->_userTable = $this->_config['prefix'] . $this->_config['userTable'];
        
        // connect to DB
        $this->_getDB();
        $this->_dbCommand = Tinebase_Backend_Sql_Command::factory($this->_db);

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
            __METHOD__ . '::' . __LINE__ . ' ' . print_r($this->_config, TRUE));
    }

    /**
    * delete user by id
    *
    * @param  Tinebase_Model_FullUser  $_user
    */
    public function inspectDeleteUser(Tinebase_Model_FullUser $_user)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . ' Delete ' . $this->_configKey . ' email settings for user ' . $_user->accountLoginName
            . ' emailUserId: ' . $_user->getId());
        
        $this->deleteUserById($_user->getId());
    }
    
    /**
     * delete user by id
     * 
     * @param string $id
     */
    public function deleteUserById($id)
    {
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?', $id) . ' OR ' .
            $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' LIKE ?',
                substr((string)$id, 0,32) . '#~#%')
        );
        $this->_appendClientIdOrDomain($where);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' ' . print_r($where, TRUE));
        
        $this->_db->delete($this->_userTable, $where);
    }


    /**
     * delete all email users
     *
     */
    public function deleteAllEmailUsers()
    {
        $where = $this->_appendClientIdOrDomain();

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' ' . print_r($where, TRUE));

        $rowCount = $this->_db->delete($this->_userTable, $where);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' delete : ' . $rowCount . ' rows');
    }
    
    /**
     * append domain if set or domain IS NULL
     * 
     * @param array $where
     * @return string
     * 
     * @todo check if user table has domain or client_idnr field and use mapping for the field identifier
     */
    protected function _appendClientIdOrDomain(&$where = NULL)
    {
        if ($this->_clientId !== NULL) {
            $cond = $this->_db->quoteInto($this->_db->quoteIdentifier($this->_userTable . '.' . 'client_idnr') . ' = ?', $this->_clientId);
        } else {
            if ((isset($this->_config['domain']) || array_key_exists('domain', $this->_config)) && ! empty($this->_config['domain'])) {
                $cond = $this->_db->quoteInto($this->_db->quoteIdentifier($this->_userTable . '.' . 'domain') . ' = ?',   $this->_config['domain']);
            } else {
                $cond = $this->_db->quoteIdentifier($this->_userTable . '.' . 'domain') . " =''";
            }
        }
        
        if ($where !== NULL) {
            $where[] = $cond;
        }
        
        return $cond;
    }

    /**
     * @param Tinebase_Model_User $_user
     * @return mixed
     */
    public function getRawUserById(Tinebase_Model_User $_user)
    {
        return $this->getRawUserByProperty($_user, 'emailUserId');
    }

    /**
     * @param Tinebase_Model_User $_user
     * @param $property
     * @param $userProperty
     * @return mixed
     * @throws Zend_Db_Statement_Exception
     */
    public function getRawUserByProperty(Tinebase_Model_User $_user, $property, $userProperty = null)
    {
        $value = $property === 'emailUserId'
            ? $_user->getId()
            : ($userProperty ? $_user->{$userProperty} : $_user->{$this->_propertyMapping[$property]});

        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier($this->_userTable
                    . '.' . $this->_propertyMapping[$property]) . ' = ?', $value)
        );
        $this->_appendClientIdOrDomain($where);

        $select = $this->_getSelect();
        foreach ($where as $w) {
            $select->where($w);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
            __METHOD__ . '::' . __LINE__ . ' user select: ' . $select);

        // Perform query - retrieve user from database
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();

        if (!$queryResult) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__. ' ' . $this->_subconfigKey . ' config for user with '
                . $this->_propertyMapping[$property] . ' = ' . $value . ' not found. '
                . '(' . $select . ')');
        }

        return $queryResult;
    }

    /**
     * inspect get user by property
     * 
     * @param Tinebase_Model_User  $_user  the user object
     */
    public function inspectGetUserByProperty(Tinebase_Model_User $_user)
    {
        if (! $_user instanceof Tinebase_Model_FullUser) {
            return;
        }
        
        $rawUser = (array)$this->getRawUserById($_user);
        
        // convert data to Tinebase_Model_EmailUser
        $emailUser = $this->_rawDataToRecord($rawUser);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' ' . print_r($emailUser->toArray(), TRUE));
        
        // modify/correct username
        if (empty($emailUser->emailUsername)) {
            $emailUser->emailUsername = $this->getEmailUserName($_user);
        }
        
        if ($this instanceof Tinebase_EmailUser_Smtp_Interface) {
            $_user->smtpUser  = $emailUser;
            $_user->emailUser = Tinebase_EmailUser::merge($_user->emailUser, clone $_user->smtpUser);
        } else {
            $_user->imapUser  = $emailUser;
            $_user->emailUser = Tinebase_EmailUser::merge(clone $_user->imapUser, $_user->emailUser);
        }
    }

    /**
     * update/set email user password
     *
     * @param string $_userId
     * @param string $_password
     * @param bool $_encrypt
     * @param bool $_mustChange
     * @param array $_additionalData
     * @return void
     */
    public function inspectSetPassword($_userId, string $_password, bool $_encrypt = true, bool $_mustChange = false, array &$_additionalData = [])
    {
        if (!isset($this->_propertyMapping['emailPassword'])) {
            return;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
            ' Setting email user password (encrypt: ' . (int) $_encrypt . ') for user id ' . $_userId);
        
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
        if ((isset($imapConfig['pwsuffix']) || array_key_exists('pwsuffix', $imapConfig))) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                ' Appending configured pwsuffix to new email account password.');
            $password = $_password . $imapConfig['pwsuffix'];
        } else {
            $password = $_password;
        }
        
        $values = array(
            $this->_propertyMapping['emailPassword'] => ($_encrypt) ? Hash_Password::generate($this->_config['emailScheme'], $password) : $password
        );
        
        $where = array(
            '(' . $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?',
            $_userId) . ' OR ' .
            $this->_db->quoteInto($this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' LIKE ?',
                substr((string)$_userId, 0,32) . '#~#%') . ')'
        );
        $this->_appendClientIdOrDomain($where);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' where: ' . print_r($where, true));
        
        $this->_db->update($this->_userTable, $values, $where);
    }
    
    /*********  protected functions  *********/
    
    /**
     * get the basic select object to fetch records from the database
     *  
     * @param  array|string|Zend_Db_Expr  $_cols        columns to get, * per default
     * @param  boolean                    $_getDeleted  get deleted records (if modlog is active)
     * @return Zend_Db_Select
     */
    abstract protected function _getSelect($_cols = '*', $_getDeleted = FALSE);
    
    /**
     * adds email properties for a new user
     * 
     * @param  Tinebase_Model_FullUser  $_addedUser
     * @param  Tinebase_Model_FullUser  $_newUserProperties
     */
    protected function _addUser(Tinebase_Model_FullUser $_addedUser, Tinebase_Model_FullUser $_newUserProperties)
    {
        if (! $_addedUser->accountEmailAddress) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
            . ' User ' . $_addedUser->accountDisplayName . ' has no email address defined. Skipping email user creation.');
            return;
        }
        
        $emailUserData = $this->_recordToRawData($_addedUser, $_newUserProperties);

        $emailUsername = $emailUserData[$this->_propertyMapping['emailUsername']];
        
        $this->_checkEmailExistance($emailUsername);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Adding new ' . $this->_configKey . ' email user ' . $emailUsername);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
            . print_r($emailUserData, TRUE));
        
        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);
            
            // generate random password if not set
            if (isset($this->_propertyMapping['emailPassword']) && empty($emailUserData[$this->_propertyMapping['emailPassword']])) {
                $emailUserData[$this->_propertyMapping['emailPassword']] = Hash_Password::generate($this->_config['emailScheme'], Tinebase_Record_Abstract::generateUID());
            }
            
            $insertData = $emailUserData;
            $this->_beforeAdd($insertData);

            $insertData = array_intersect_key($insertData, $this->getSchema());
            $this->_db->insert($this->_userTable, $insertData);
            
            $this->_afterAddOrUpdate($emailUserData);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
            $this->inspectGetUserByProperty($_addedUser);
            
        } catch (Zend_Db_Statement_Exception $zdse) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Error while creating email user: ' . $zdse);
            throw $zdse;
        }
    }

    /**
     * interceptor before add
     *
     * @param array{loginname:string, domain:string, userid:string} $emailUserData
     */
    protected function _beforeAdd(&$emailUserData)
    {
    }

    /**
     * interceptor before update
     *
     * @param array{loginname:string, domain:string, userid:string} $emailUserData
     */
    protected function _beforeUpdate(&$emailUserData)
    {
    }

    /**
     * interceptor after add
     * 
     * @param array $emailUserData
     */
    protected function _afterAddOrUpdate(&$emailUserData)
    {
        
    }
    
    /**
     * check if user email already exists in table
     * 
     * @param  string  $email
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _checkEmailExistance($email)
    {
        $select = $this->_getSelect()
            ->where($this->_db->quoteIdentifier($this->_userTable . '.' . $this->_propertyMapping['emailUsername']) . ' = ?',   $email)
            ->where($this->_appendClientIdOrDomain());
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $select);
        
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
        
        if (! $queryResult) {
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($queryResult, TRUE));
        
        $userId = $queryResult[$this->_propertyMapping['emailUserId']];
        
        try {
            Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $userId);
            throw new Tinebase_Exception_SystemGeneric('Could not overwrite existing email user.');
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Delete obsolete email user ' .$userId);
            $this->deleteUserById($userId);
        }
    }
    
    /**
     * updates email properties for an existing user
     * 
     * @param  Tinebase_Model_FullUser  $_updatedUser
     * @param  Tinebase_Model_FullUser  $_newUserProperties
     */
    protected function _updateUser(Tinebase_Model_FullUser $_updatedUser, Tinebase_Model_FullUser $_newUserProperties)
    {
        $emailUserData = $this->_recordToRawData($_updatedUser, $_newUserProperties);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Updating ' . $this->_userTable . ' user ' . $emailUserData[$this->_propertyMapping['emailUsername']]);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' emailUserData :  ' . print_r($emailUserData, true));

        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);

        $updateData = $emailUserData;

        $this->_beforeUpdate($updateData);

        $id = $updateData['oldUserId'] ?? $emailUserData[$this->_propertyMapping['emailUserId']];
        $where = [
            $this->_db->quoteInto(
                $this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ' = ?', $id
            )
        ];
        $this->_appendClientIdOrDomain($where);

        $updateData = array_intersect_key($updateData, $this->getSchema());
        try {
            $this->_db->update($this->_userTable, $updateData, $where);
        } catch (Zend_Db_Statement_Exception $zdse) {
            if (! Tinebase_Exception::isDbDuplicate($zdse)) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Error while updating email user');
                Tinebase_TransactionManager::getInstance()->rollBack();

                throw $zdse;
            } else {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Duplicate: '
                    . $zdse);
                $translate = Tinebase_Translation::getTranslation('Tinebase');
                throw new Tinebase_Exception_SystemGeneric($translate->_('Email account already exists'));
            }
        }

        $this->_afterAddOrUpdate($emailUserData);

        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

        $this->inspectGetUserByProperty($_updatedUser);
    }
    
    /**
     * check if user exists already in email backend user table
     * 
     * @param  Tinebase_Model_FullUser  $_user
     * @throws Tinebase_Exception_Backend_Database
     * @return boolean
     */
    protected function _userExists(Tinebase_Model_FullUser $_user)
    {
        $data = $this->_recordToRawData($_user, $_user);

        $select = $this->_getSelect();
        
        if (! empty($data[$this->_propertyMapping['emailUserId']])) {
            $select->where($this->_db->quoteIdentifier($this->_userTable . '.' . $this->_propertyMapping['emailUserId']) .
                ' = ?', $data[$this->_propertyMapping['emailUserId']]);
        }
        $select->orwhere($this->_db->quoteIdentifier($this->_userTable . '.' . $this->_propertyMapping['emailUsername']) .
            ' = ?', $data[$this->_propertyMapping['emailUsername']]);

        if ($this->_propertyMapping['emailUsername'] !== 'loginname' && isset($data['loginname'])) {
            $select->orwhere($this->_db->quoteIdentifier($this->_userTable . '.loginname') .
                ' = ?',   $data['loginname']);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
        
        // Perform query - retrieve user from database
        try {
            $stmt = $this->_db->query($select);
        } catch (Zend_Db_Statement_Exception $zdse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . $zdse);
            throw new Tinebase_Exception_Backend_Database($zdse->getMessage());
        }
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
        
        if (!$queryResult) {
            return false;
        }

        return true;
    }

    /**
     * returns the db schema
     * @return array
     * @throws Tinebase_Exception_Backend_Database
     *
     * @refactor use trait (see \Tinebase_Backend_Sql_Abstract::getSchema)
     */
    public function getSchema()
    {
        if (!$this->_schema) {
            try {
                $this->_schema = Tinebase_Db_Table::getTableDescriptionFromCache($this->_userTable, $this->_db);
            } catch (Zend_Db_Adapter_Exception $zdae) {
                throw new Tinebase_Exception_Backend_Database('Connection failed: ' . $zdae->getMessage());
            }
        }

        return $this->_schema;
    }

    /**
     * converts raw data from adapter into a single record / do mapping
     *
     * @param  array                    $_data
     * @return Tinebase_Model_EmailUser
     */
    abstract protected function _rawDataToRecord(array &$_rawdata);
     
    /**
     * returns array of raw user data
     *
     * @param  Tinebase_Model_FullUser  $_user
     * @param  Tinebase_Model_FullUser  $_newUserProperties
     * @return array
     */
    abstract protected function _recordToRawData(Tinebase_Model_FullUser $_user, Tinebase_Model_FullUser $_newUserProperties);

    /**
     * @param $domain optional domain to limit to
     * @return Tinebase_Record_RecordSet of Tinebase_Model_EmailUser
     */
    public function getAllEmailUsers($domain = null)
    {
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_EmailUser', array());
        $select = $this->_getSelect()->limit(0);
        if (null !== $domain) {
            $select->where($this->_db->quoteIdentifier($this->_userTable . '.' . 'domain') . ' = ?', $domain);
        }
        foreach ($select->query()->fetchAll() as $row) {
            $result->addRecord($this->_rawDataToRecord($row));
        }
        return $result;
    }

    /**
     * @param Tinebase_Model_FullUser $_user
     * @return Tinebase_Model_EmailUser of Tinebase_Model_EmailUser
     */
    public function getEmailUser(Tinebase_Model_FullUser $_user): ?Tinebase_Model_EmailUser
    {
        $rawUser = (array)$this->getRawUserById($_user);

        // convert data to Tinebase_Model_EmailUser
        return $this->_rawDataToRecord($rawUser);
    }
    
    /**
     * returns array with keys mailQuota and mailSize
     *
     * @param string $domain optional domain to limit to
     * @return array
     */
    public function getTotalUsageQuota($domain = null)
    {
        $select = $this->_getSelect(
            array(
                new Zend_Db_Expr('SUM(' . $this->_db->quoteIdentifier($this->_tableMapping['emailMailQuota'] . '.' .
                        $this->_propertyMapping['emailMailQuota']) . ') as mailQuota'),
                new Zend_Db_Expr('SUM(' . $this->_db->quoteIdentifier($this->_tableMapping['emailMailSize']  . '.' .
                        $this->_propertyMapping['emailMailSize'])  . ') as mailSize'),
                //new Zend_Db_Expr('SUM(' . $this->_propertyMapping['emailSieveSize'] . ') as sieveSize'),
            ));

        if (null !== $domain) {
            $select->where($this->_db->quoteIdentifier($this->_userTable . '.' . 'domain') . ' = ?', $domain);
        }

        $data = $select->query()->fetchAll();
        return $data[0];
    }

    protected function _replaceValue(array &$array, array $replacements)
    {
        foreach($array as &$value) {
            if (isset($replacements[$value])) {
                $value = $replacements[$value];
            }
        }
    }

    /**
     * copy email user records from another instance / usable with master/slave setup after install_dump from master
     *
     * @param $fromInstance
     * @todo make it work for smtp?
     */
    public function copyFromInstance($fromInstance)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Copy email users from instance ' . $fromInstance);

        $select = $this->_db->select()
            ->from(array($this->_userTable))
            ->where('instancename = ?', $fromInstance);
        $data = $select->query()->fetchAll();
        $count = $update = 0;
        foreach ($data as $recordData) {
            // adjust instancename + domain and save record
            $recordData['instancename'] = $this->_config['instanceName'];
            $recordData['domain'] = empty($this->_config['domain']) ? $recordData['instancename'] : $this->_config['domain'];
            $recordData['username'] = str_replace($fromInstance, $recordData['instancename'], $recordData['username']);
            $recordData['loginname'] = str_replace($fromInstance, $recordData['instancename'], $recordData['loginname']);
            $recordData['home'] = str_replace($fromInstance, $recordData['instancename'], $recordData['home']);
            try {
                $this->_db->insert($this->_userTable, $recordData);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . ' Copied email record ' . print_r($recordData, true));
                $count++;

            } catch (Zend_Db_Statement_Exception $zdse) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                    __METHOD__ . '::' . __LINE__ . $zdse->getMessage());
                $where = array(
                    $this->_db->quoteInto($this->_db->quoteIdentifier('username') . ' = ?', $recordData['username'])
                );
                $this->_appendClientIdOrDomain($where);

                $this->_db->update($this->_userTable, $recordData, $where);
                $update++;
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' Copied ' . $count
            . ' email records from instance' . $fromInstance . ' to ' .  $this->_config['instanceName']);
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' Updated ' . $update
            . ' email records from instance' . $fromInstance);
    }

    /**
     * copy email user
     *
     * @param Tinebase_Model_FullUser $_user
     * @param string $newId
     * @throws Tinebase_Exception
     * @throws Zend_Db_Statement_Exception
     * @return array
     */
    public function copyUser(Tinebase_Model_FullUser $_user, $newId)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' Copy email user for account ' . $_user->getId() . ' - new id: '
            . $newId);

        $columns = $this->_db->query('show columns from ' . $this->_db->quoteIdentifier($this->_userTable))
            ->fetchAll(Zend_Db::FETCH_COLUMN, 0);

        foreach (['emailUserId', 'emailUsername', 'emailAddress', 'emailLoginname', 'emailHome'] as $colToRemove) {
            if (isset($this->_propertyMapping[$colToRemove])) {
                if (false === ($offset = array_search($this->_propertyMapping[$colToRemove], $columns))) {
                    throw new Tinebase_Exception('did not find ' . $this->_propertyMapping[$colToRemove] . ' in ' .
                        join(', ', $columns));
                }
                unset($columns[$offset]);
            }
        }

        // always unset id
        if (false !== ($offset = array_search('id', $columns))) {
            unset($columns[$offset]);
        }

        $escapedColumns = $columns;
        array_walk($escapedColumns, function(&$val) { $val = $this->_db->quoteIdentifier($val); });

        $where = '';
        if (isset($this->_config['instanceName']) && $this->_config['instanceName'] &&
            in_array('instancename', $columns)) {
            $where = ' AND ' . $this->_db->quoteIdentifier('instancename') . $this->_db->quoteInto(' = ?',
                    $this->_config['instanceName']);
        } else {
            if (in_array('client_idnr', $columns) && $this->_clientId) {
                $where = ' AND ' . $this->_db->quoteIdentifier('client_idnr') . $this->_db->quoteInto(' = ?',
                        $this->_clientId);
            }
        }

        $query = 'INSERT INTO ' . $this->_db->quoteIdentifier($this->_userTable) . ' (' .
            $this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . ', ' .
            $this->_db->quoteIdentifier($this->_propertyMapping['emailUsername']) . ', ';

        foreach (['emailAddress', 'emailLoginname', 'emailHome'] as $column) {
            if (isset($this->_propertyMapping[$column])) {
                $query .= $this->_db->quoteIdentifier($this->_propertyMapping[$column]) . ', ';
            }
        }


        $query .= join(', ', $escapedColumns) . ') SELECT '
            /* ID */ . $this->_db->quote($newId) . ', '
            /* USERNAME */ . $this->_db->quote($_user->accountLoginName) . ', ';

        foreach (['emailAddress', 'emailLoginname'] as $column) {
            if (isset($this->_propertyMapping[$column])) {
                $query .= $this->_db->quote($_user->accountEmailAddress) . ', ';
            }
        }

        if (isset($this->_propertyMapping['emailHome'])) {
            $emailhome = $this->_getEmailHome($newId);
            $query .= $this->_db->quote($emailhome) . ', ';
        }

        $query .= join(', ', $escapedColumns) .
            ' FROM ' . $this->_db->quoteIdentifier($this->_userTable) . ' WHERE ' .
            $this->_db->quoteIdentifier($this->_propertyMapping['emailUserId']) . $this->_db->quoteInto(' = ?',
                $_user->getId()) . $where;

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' ' . $query);

        $this->_db->query($query);

        $newUser = clone($_user);
        $newUser->setId($newId);
        return $this->getRawUserById($newUser);
    }

    /**
     * copy email password from another email user account
     *
     * @param $fromUser
     * @param $toUser
     */
    public function copyPassword($fromUser, $toUser)
    {
        $rawUser = $this->getRawUserById($fromUser);
        $this->inspectSetPassword($toUser->getId(), $rawUser[$this->_propertyMapping['emailPassword']], false);
    }

    /**
     * replace home wildcards when storing to db
     *  %d = domain
     *  %n = user
     *  %u == user@domain
     *
     * @param $localPart
     * @param $domain
     * @param $emailUsername
     * @return string|string[]
     */
    protected function _getEmailHome($emailUsername, $localPart = null, $domain = null)
    {
        if ($localPart === null || $domain === null) {
            if (strpos($emailUsername, '@') !== false) {
                list($localPart, $usernamedomain) = explode('@', $emailUsername, 2);
                $domain = empty($this->_config['domain']) ? $usernamedomain : $this->_config['domain'];
            } else {
                $localPart = $emailUsername;
                $domain = $this->_config['domain'];
            }
        }

        $search = array('%n', '%d', '%u');
        $replace = array(
            $localPart,
            $domain,
            $emailUsername
        );

        return str_replace($search, $replace, $this->_config['emailHome']);
    }


    /**
     * backup user to a dump file
     *
     * @param array $option
     */
    public function backup($option)
    {
        $backupDir = $option['backupDir'];

        // hide password from shell via my.cnf
        $domain = $this->_config['domain'];
        $mycnf = $backupDir . '/my.cnf';

        $dbConfig = $this->_config['dovecot'];
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' dbconfig : ' . print_r($dbConfig, true));
        
        $mysqlBackEnd = new Setup_Backend_Mysql();
        $mysqlBackEnd->createMyConf($mycnf, new Zend_Config($dbConfig));

        //create the dump via mysqldump with --where to select the data we want to export
        $cmd = "mysqldump --defaults-extra-file=$mycnf "
            ."--no-create-info "
            ."--single-transaction --max_allowed_packet=512M "
            ."--opt --no-tablespaces "
            . escapeshellarg($this->_config['dbname']) . ' '
            . escapeshellarg($this->_userTable)
            .' --where="' . "domain='$domain'" . '"'
            ." | bzip2 > $backupDir/tine20_dovecot.sql.bz2";

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 'exec commend ' . print_r($cmd, true));
        exec($cmd, $output);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 'backexecoutput ' . print_r($output, true));
        unlink($mycnf);
    }
}

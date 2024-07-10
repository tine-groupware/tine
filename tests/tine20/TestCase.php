<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tests
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . 'TestHelper.php';

use OTPHP\TOTP;
use OTPHP\TOTPInterface;
use ParagonIE\ConstantTime\Base32;

/**
 * Abstract test class
 * 
 * @package     Tests
 *
 * TODO separation of concerns: split into multiple classes/traits with cleanup / fixture / ... functionality
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * transaction id if test is wrapped in an transaction
     * 
     * @var string
     */
    protected $_transactionId = null;
    
    /**
     * usernames to be deleted (in sync backend)
     * 
     * @var array
     */
    protected $_usernamesToDelete = array();
    
    /**
     * groups (ID) to be deleted (in sync backend)
     * 
     * @var array
     */
    protected $_groupIdsToDelete = array();
    
    /**
     * remove group members, too when deleting groups
     * 
     * @var boolean
     */
    protected $_removeGroupMembers = true;
    
    /**
     * invalidate roles cache
     * 
     * @var boolean
     */
    protected $_invalidateRolesCache = false;
    
    /**
     * test personas
     * 
     * @var array
     */
    protected $_personas = [];
    
    /**
     * unit in test
     *
     * @var Object
     */
    protected $_uit = null;
    
    /**
     * the test user
     *
     * @var Tinebase_Model_FullUser
     */
    protected $_originalTestUser;
    
    /**
     * the mailer
     * 
     * @var Zend_Mail_Transport_Abstract
     */
    protected static $_mailer = null;

    /**
     * db lock ids to be released
     *
     * @var array
     */
    protected $_releaseDBLockIds = array();

    /**
     * customfields that should be deleted later
     *
     * @var array
     */
    protected $_customfieldIdsToDelete = array();

    protected $_areaLocksToInvalidate = [];
    protected $_oldAreaLockCfg;

    protected $_originalSmtpConfig = null;

    protected $_originalGrants = [];

    /**
     * @var array lists to delete in tearDown
     */
    protected $_listsToDelete = [];

    protected $_pin = '1234';

    protected $_containerToDelete = [];

    /**
     * set up tests
     */
    protected function setUp(): void
    {
        Tinebase_Record_Expander_DataRequest::clearCache();

        foreach ($this->_customfieldIdsToDelete as $cfd) {
            Tinebase_CustomField::getInstance()->deleteCustomField($cfd);
        }

        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        $refProp = new ReflectionProperty(Felamimail_Controller_Account::class, '_instance');
        $refProp->setAccessible(true);
        $refProp->setValue(Felamimail_Controller_AccountMock::getInstance());
        
        Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(false);

        if (Zend_Registry::isRegistered('personas')) {
            $this->_personas = Zend_Registry::get('personas');
        }
        
        $this->_originalTestUser = Tinebase_Core::getUser();
        $this->_oldAreaLockCfg = null;
    }
    
    /**
     * tear down tests
     */
    protected function tearDown(): void
    {
        if (($u = Tinebase_Core::getUser()) instanceof Tinebase_Model_FullUser && $u->mfa_configs) {
            $u->mfa_configs->removeById('userpin');
        }
        if ($this->_originalTestUser instanceof Tinebase_Model_User) {
            if ($this->_originalTestUser instanceof Tinebase_Model_FullUser) {
                Tinebase_Core::unsetUser();
            }
            Tinebase_Core::setUser($this->_originalTestUser);
        }

        $this->_deleteUsers();
        $this->_deleteGroups();

        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(false);
        if (Tinebase_TransactionManager::getInstance()->hasOpenTransactions()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Rolling back test transaction');
            Tinebase_TransactionManager::getInstance()->rollBack();
        }
        
        Addressbook_Controller_Contact::getInstance()->setGeoDataForContacts(true);

        if ($this->_invalidateRolesCache) {
            Tinebase_Acl_Roles::getInstance()->resetClassCache();
        }

        Tinebase_Cache_PerRequest::getInstance()->reset();

        $this->_releaseDBLocks();

        foreach ($this->_areaLocksToInvalidate as $area) {
            Tinebase_AreaLock::getInstance()->resetValidAuth($area);
        }
        if (null !== $this->_oldAreaLockCfg) {
            Tinebase_Config::getInstance()->{Tinebase_Config::AREA_LOCKS} = $this->_oldAreaLockCfg;
            Tinebase_AreaLock::destroyInstance();
        }

        if ($this->_originalSmtpConfig) {
            Tinebase_Config::getInstance()->set(Tinebase_Config::SMTP, $this->_originalSmtpConfig);
        }

        foreach ($this->_originalGrants as $container => $grants) {
            Tinebase_Container::getInstance()->setGrants($container, $grants, true);
        }

        foreach ($this->_listsToDelete as $list) {
            $listId = $list instanceof Addressbook_Model_List ? $list->getId() : $list;
            Addressbook_Controller_List::getInstance()->delete($listId);
        }

        if (!empty($this->_containerToDelete)) {
            try {
                Tinebase_Container::getInstance()->delete($this->_containerToDelete);
            } catch (Tinebase_Exception $e) {}
        }

        Tinebase_Lock::clearLocks();

        Felamimail_Controller_AccountMock::getInstance()->cleanUp();
    }

    /**
     * release db locks
     */
    protected function _releaseDBLocks()
    {
        foreach ($this->_releaseDBLockIds as $lockId) {
            try {
                Tinebase_Core::releaseMultiServerLock($lockId);
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
            }
        }

        $this->_releaseDBLockIds = array();
    }

    /**
     * tear down after test class
     */
    public static function tearDownAfterClass(): void
    {
        try {
            /** @var Tinebase_EmailUser_Imap_Dovecot $emaiUserImap */
            $emaiUserImap = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);
            $emaiUserImap->getDb()->query('select now()');
        } catch (Tinebase_Exception $e) {}

        try {
            /** @var Tinebase_EmailUser_Smtp_Postfix $emaiUserSmtp */
            $emaiUserSmtp = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP);
            $emaiUserSmtp->getDb()->query('select now()');
        } catch (Tinebase_Exception $e) {}

        Tinebase_Core::getDbProfiling();
    }

    /**
     * set primary domain
     */
    protected function _setMailDomainIfEmpty($domain = 'example.org')
    {
        if (empty($this->_smtpConfig)) {
            $this->_smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP,
                new Tinebase_Config_Struct(array()));
        }
        // if mailing is not installed, as with pgsql
        if (empty($this->_smtpConfig->primarydomain)) {
            if (! $this->_originalSmtpConfig) {
                $this->_originalSmtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP,
                    new Tinebase_Config_Struct(array()));
            }
            $this->_smtpConfig = clone($this->_originalSmtpConfig);
            $this->_smtpConfig->primarydomain = $domain;
            Tinebase_Config::getInstance()->set(Tinebase_Config::SMTP, $this->_smtpConfig);
        }
    }

    /**
     * test needs transaction
     */
    protected function _testNeedsTransaction()
    {
        if ($this->_transactionId) {
            Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
            $this->_transactionId = null;
        }
    }
    
    /**
     * get tag
     *
     * @param string $tagType
     * @param string $tagName
     * @param array $contexts
     * @return Tinebase_Model_Tag
     */
    protected function _getTag($tagType = Tinebase_Model_Tag::TYPE_SHARED, $tagName = NULL, $contexts = NULL)
    {
        if ($tagName) {
            try {
                $tag = Tinebase_Tags::getInstance()->getTagByName($tagName);
                return $tag;
            } catch (Tinebase_Exception_NotFound $tenf) {
            }
        } else {
            $tagName = Tinebase_Record_Abstract::generateUID();
        }
    
        $targ = array(
            'type'          => $tagType,
            'name'          => $tagName,
            'description'   => 'testTagDescription',
            'color'         => '#009B31',
        );
    
        if ($contexts) {
            $targ['contexts'] = $contexts;
        }
    
        return new Tinebase_Model_Tag($targ);
    }
    
    /**
     * delete groups and their members
     * 
     * - also deletes groups and users in sync backends
     */
    protected function _deleteGroups()
    {
        if (! is_array($this->_groupIdsToDelete)) {
            return;
        }

        foreach ($this->_groupIdsToDelete as $groupId) {
            if ($this->_removeGroupMembers) {
                foreach (Tinebase_Group::getInstance()->getGroupMembers($groupId) as $userId) {
                    try {
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                            . ' Deleting group member ' . $userId);
                        Tinebase_User::getInstance()->deleteUser($userId);
                    } catch (Exception $e) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' error while deleting user: ' . $e->getMessage());
                    }
                }
            }
            try {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Deleting group ' . $groupId);
                Tinebase_Group::getInstance()->deleteGroups($groupId);
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' error while deleting group: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * delete users
     */
    protected function _deleteUsers()
    {
        foreach ($this->_usernamesToDelete as $username) {
            try {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Trying to delete user: ' . $username);

                Tinebase_User::getInstance()->deleteUser($user = Tinebase_User::getInstance()->getUserByLoginName($username));
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Error while deleting user: ' . $e->getMessage());
            }
            Tinebase_Core::getDb()->delete(SQL_TABLE_PREFIX . 'accounts', 'login_name = "' . $username . '"');
        }
    }

    /**
     * removes records and their relations
     *
     * @param Tinebase_Record_RecordSet $records
     * @param array $modelsToDelete
     * @param array $typesToDelete
     */
    protected function _deleteRecordRelations($records, $modelsToDelete = array(), $typesToDelete = array())
    {
        $controller = Tinebase_Core::getApplicationInstance($records->getRecordClassName());

        if (! method_exists($controller, 'deleteLinkedRelations')) {
            return;
        }

        foreach ($records as $record) {
            $controller->deleteLinkedRelations($record, $modelsToDelete, $typesToDelete);
        }
    }

    /**
     * get personal container
     * 
     * @param string $modelName
     * @param Tinebase_Model_User $user
     * @return Tinebase_Model_Container
     */
    protected function _getPersonalContainer($modelName, $user = null)
    {
        if ($user === null) {
            $user = Tinebase_Core::getUser();
        }

        /** @var Tinebase_Model_Container $personalContainer */
        $personalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
            $user,
            $modelName,
            $user,
            Tinebase_Model_Grants::GRANT_EDIT
        )->getFirstRecord();

        if (! $personalContainer) {
            throw new Tinebase_Exception_UnexpectedValue('no personal container found!');
        }

        return $personalContainer;
    }
    
    /**
     * get test container
     * 
     * @param string $applicationName
     * @param string $model
     * @param boolean $shared
     * @param string $name
     * @return Tinebase_Model_Container
     *
     * TODO use array as param (with array_merge)
     */
    protected function _getTestContainer($applicationName, $model, $shared = false, $name = null): Tinebase_Model_Container
    {
        $name = $name ?: 'PHPUnit ' . $model . ($shared ? ' shared' : '') . ' container';
        $container = new Tinebase_Model_Container(array(
            'name' => $name,
            'type'           => $shared ? Tinebase_Model_Container::TYPE_SHARED :
                Tinebase_Model_Container::TYPE_PERSONAL,
            'owner_id'       => $shared ? null : Tinebase_Core::getUser(),
            'backend'        => 'Sql',
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName($applicationName)->getId(),
            'model'          => $model,
        ), true);
        return Tinebase_Container::getInstance()->addContainer($container, null, false, true);
    }
    
    /**
     * get test user email address
     * 
     * @return string test user email address
     */
    protected function _getEmailAddress()
    {
        $testConfig = TestServer::getInstance()->getConfig();
        return ($testConfig->email) ? $testConfig->email : Tinebase_Core::getUser()->accountEmailAddress;
    }
    
    /**
     * lazy init of uit
     * 
     * @return Object
     * @throws Exception
     *
     * @todo fix ide object class detection for completions
     */
    protected function _getUit()
    {
        if ($this->_uit === null) {
            $uitClass = preg_replace('/Tests{0,1}$/', '', get_class($this));
            if (@method_exists($uitClass, 'getInstance')) {
                $this->_uit = call_user_func($uitClass . '::getInstance');
            } else if (@class_exists($uitClass)) {
                $this->_uit = new $uitClass();
            } else {
                // use generic json frontend
                if ($pos = strpos($uitClass, '_')) {
                    $appName = substr($uitClass, 0, $pos);
                    $this->_uit = new Tinebase_Frontend_Json_Generic($appName);
                } else {
                    throw new Exception('could not find class ' . $uitClass);
                }
            }
        }
        
        return $this->_uit;
    }
    
    /**
     * get messages
     * 
     * @return array
     */
    public static function getMessages()
    {
        // make sure messages are sent if queue is activated
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue();
        }

        /** @noinspection PhpUndefinedMethodInspection */
        return self::getMailer()->getMessages();
    }
    
    /**
     * get mailer
     * 
     * @return Zend_Mail_Transport_Abstract
     */
    public static function getMailer()
    {
        if (! self::$_mailer) {
            self::$_mailer = Tinebase_Smtp::getDefaultTransport();
        }
        
        return self::$_mailer;
    }

    /**
     * reset test mailer
     */
    public static function resetMailer()
    {
        self::$_mailer = null;
    }

    /**
     * flush mailer (send all remaining mails first)
     */
    public static function flushMailer()
    {
        // make sure all messages are sent if queue is activated
        if (isset(Tinebase_Core::getConfig()->actionqueue)) {
            Tinebase_ActionQueue::getInstance()->processQueue();
        }

        /** @noinspection PhpUndefinedMethodInspection */
        self::getMailer()->flush();
    }
    
    /**
     * returns the content.xml of an ods document
     * 
     * @param string $filename
     * @return SimpleXMLElement
     */
    protected function _getContentXML($filename)
    {
        $zipHandler = new ZipArchive();
        $zipHandler->open($filename);
        
        // read entry
        $entryContent = $zipHandler->getFromName('content.xml');
        $zipHandler->close();
        
        $xml = simplexml_load_string($entryContent);
        
        return $xml;
    }
    
    /**
     * get test temp file
     *
     * @param string|null $path
     * @param string $filename
     * @param string $type
     *
     * @return Tinebase_Model_TempFile
     */
    protected function _getTempFile($path = null, $filename = 'test.txt', $type = 'text/plain')
    {
        $tempFileBackend = new Tinebase_TempFile();
        $handle = fopen($path ? $path : dirname(__FILE__) . '/Filemanager/files/test.txt', 'r');
        $tempfile = $tempFileBackend->createTempFileFromStream($handle, $filename, $type);
        fclose($handle);
        return $tempfile;
    }
    
    /**
     * remove right in all users roles
     * 
     * @param string $applicationName
     * @param string $rightToRemove
     * @param boolean $removeAdminRight
     * @return array original role rights by role id
     */
    protected function _removeRoleRight($applicationName, $rightToRemove, $removeAdminRight = true)
    {
        $app = Tinebase_Application::getInstance()->getApplicationByName($applicationName);
        $rolesOfUser = Tinebase_Acl_Roles::getInstance()->getRoleMemberships(Tinebase_Core::getUser()->getId());
        $this->_invalidateRolesCache = true;

        $roleRights = array();
        foreach ($rolesOfUser as $roleId) {
            $roleRights[$roleId] = $rights = Tinebase_Acl_Roles::getInstance()->getRoleRights($roleId);
            foreach ($rights as $idx => $right) {
                if ($right['application_id'] === $app->getId() && ($right['right'] === $rightToRemove || (
                    true === $removeAdminRight && $right['right'] === Tinebase_Acl_Rights_Abstract::ADMIN))) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                        . ' Removing right ' . $right['right'] . ' from app ' . $applicationName . ' in role (id) ' . $roleId);
                    unset($rights[$idx]);
                }
            }
            Tinebase_Acl_Roles::getInstance()->setRoleRights($roleId, $rights);
        }

        Tinebase_Acl_Roles::getInstance()->resetClassCache();

        return $roleRights;
    }
    
    /**
     * set grants for a persona and the current user
     * 
     * @param Tinebase_Model_Container|string $container
     * @param string $persona
     * @param boolean $personaAdminGrant
     * @param boolean $userAdminGrant
     * @param array $additionalGrants
     * @param boolean $restoreOldGrants
     */
    protected function _setPersonaGrantsForTestContainer(
        $container,
        $persona,
        $personaAdminGrant = false,
        $userAdminGrant = true,
        $additionalGrants = [],
        $restoreOldGrants = false)
    {
        $container = $container instanceof Tinebase_Model_Container ? $container : Tinebase_Container::getInstance()
            ->getContainerById($container);
        if ($restoreOldGrants) {
            $this->_originalGrants[$container->getId()] = Tinebase_Container::getInstance()->getGrantsOfContainer($container, true);
        }
        $grantClass = $container->getGrantClass();
        $grants = new Tinebase_Record_RecordSet($grantClass, array(array(
            'account_id'    => $this->_personas[$persona]->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => $personaAdminGrant,
        ), array(
            'account_id'    => Tinebase_Core::getUser()->getId(),
            'account_type'  => 'user',
            Tinebase_Model_Grants::GRANT_READ     => true,
            Tinebase_Model_Grants::GRANT_ADD      => true,
            Tinebase_Model_Grants::GRANT_EDIT     => true,
            Tinebase_Model_Grants::GRANT_DELETE   => true,
            Tinebase_Model_Grants::GRANT_ADMIN    => $userAdminGrant,
        )));

        foreach ($additionalGrants as $grant) {
            if (is_array($grant)) {
                $grant = new $grantClass($grant);
            }
            $grants->addRecord($grant);
        }

        Tinebase_Container::getInstance()->setGrants($container, $grants, TRUE);
    }

    /**
     * set current user
     *
     * @param $user
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _setUser($user)
    {
        Tinebase_Core::set(Tinebase_Core::USER, $user);
    }

    /**
     * call handle cli function (Setup) with params
     *  example usage:
     *      $result = $this->_cliHelper('getconfig', array('--getconfig','--','configkey=allowedJsonOrigins'));
     *
     * @param string $command
     * @param array $params
     * @return string
     * @todo should be renamed to _setupCliHelper
     */
    protected function _cliHelper($command, $params)
    {
        $opts = new Zend_Console_Getopt(array($command => $command));
        $opts->setArguments($params);
        ob_start();
        $this->_cli->handle($opts, false);
        $out = ob_get_clean();
        return $out;
    }

    /**
     * example usage:
     *           $out = $this->_appCliHelper('Addressbook', 'createDemoData', []);
     *
     * @param $appName
     * @param $command
     * @param $params
     * @return string
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _appCliHelper($appName, $command, $params)
    {
        $classname = $appName . '_Frontend_Cli';
        if (! class_exists($classname)) {
            throw new Tinebase_Exception_InvalidArgument('CLI class ' . $classname . ' not found');
        }

        $cli = new $classname();
        $opts = new Zend_Console_Getopt('abp:');
        $opts->setArguments($params);

        ob_start();
        call_user_func_array([$cli, $command], [$opts]);
        $out = ob_get_clean();

        return $out;
    }

    /**
     * add an attachment to a record
     *
     * @param $record
     */
    protected function _addRecordAttachment($record)
    {
        $path = Tinebase_TempFile::getTempPath();
        file_put_contents($path, 'testAttachmentData');
        $record->attachments = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array(
            array(
                'name'      => 'testAttachmentData.txt',
                'tempFile'  => Tinebase_TempFile::getInstance()->createTempFile($path)
            )
        ), true);
    }

    /**
     * test record json api
     *
     * @param string $modelName
     * @param string $nameField
     * @param string $descriptionField
     * @param bool $delete
     * @param array $recordData
     * @param bool $description
     * @return array
     * @throws Exception
     */
    protected function _testSimpleRecordApi(
        $modelName,
        $nameField = 'name',
        $descriptionField = 'description',
        $delete = true,
        $recordData = [],
        $description = true
    ) {
        $uit = $this->_getUit();
        if (!$uit instanceof Tinebase_Frontend_Json_Abstract) {
            throw new Exception('only allowed for json frontend tests suites');
        }

        $newRecord = array();

        if ($nameField && ! isset($recordData[$nameField])) {
            $newRecord[$nameField] = 'my test ' . $modelName;
        }

        if ($description) {
            $newRecord[$descriptionField] = 'my test description';
        }

        $classParts = explode('_', get_called_class());
        $realModelName = $classParts[0] . '_Model_' . $modelName;
        /** @var Tinebase_Record_Abstract $realModelName */
        if (class_exists($realModelName)) {
            $configuration = $realModelName::getConfiguration();
        } else {
            $configuration = null;
        }

        $savedRecord = call_user_func(array($uit, 'save' . $modelName), array_merge($newRecord, $recordData));
        if ($nameField) {
            self::assertTrue(isset($savedRecord[$nameField]), 'name field missing: ' . print_r($savedRecord, true));
            $nameValue = isset($recordData[$nameField]) ? $recordData[$nameField] : 'my test ' . $modelName;
            self::assertEquals($nameValue, $savedRecord[$nameField], print_r($savedRecord, true));
            if (null !== $configuration && $configuration->modlogActive) {
                self::assertTrue(isset($savedRecord['created_by']['accountId']), 'created_by not present: ' .
                    print_r($savedRecord, true));
                self::assertEquals(Tinebase_Core::getUser()->getId(), $savedRecord['created_by']['accountId'],
                    'created_by has wrong value: ' . print_r($savedRecord, true));
            }
        }

        $recordWasUpdated = false;
        // Update description if record has
        if ($description) {
            $savedRecord[$descriptionField] = 'my updated description';
            $updatedRecord = call_user_func(array($uit, 'save' . $modelName), $savedRecord);
            self::assertEquals('my updated description', $updatedRecord[$descriptionField]);
            $savedRecord = $updatedRecord;
            $recordWasUpdated = true;
        }

        if ($nameField) {
            // update name as well!
            $savedRecord[$nameField] = 'my updated namefield';
            $updatedRecord = call_user_func(array($uit, 'save' . $modelName), $savedRecord);
            self::assertEquals('my updated namefield', $updatedRecord[$nameField]);
            $savedRecord = $updatedRecord;
            $recordWasUpdated = true;
        }

        $filter = array(array('field' => 'id', 'operator' => 'equals', 'value' => $savedRecord['id']));
        $result = call_user_func(array($uit, 'search' . $modelName . 's'), $filter, array());
        self::assertEquals(1, $result['totalcount'], print_r($result['results'], true));

        if (null !== $configuration && $configuration->modlogActive && $recordWasUpdated) {
            self::assertTrue(isset($result['results'][0]['last_modified_by']['accountId']),
                'last_modified_by not present: ' . print_r($result, true));
            self::assertEquals(Tinebase_Core::getUser()->getId(), $result['results'][0]['last_modified_by']['accountId'],
                'last_modified_by has wrong value: ' . print_r($result, true));
        }

        if ($delete) {
            call_user_func(array($uit, 'delete' . $modelName . 's'), array($savedRecord['id']));
            try {
                call_user_func(array($uit, 'get' . $modelName), $savedRecord['id']);
                self::fail('should delete Record');
            } catch (Tinebase_Exception_NotFound $tenf) {
                self::assertTrue($tenf instanceof Tinebase_Exception_NotFound);
            }
        }

        return $savedRecord;
    }

    /**
     * returns true if main db adapter is postgresql
     *
     * @return bool
     */
    protected function _dbIsPgsql()
    {
        $db = Tinebase_Core::getDb();
        return ($db instanceof Zend_Db_Adapter_Pdo_Pgsql);
    }

    /**
     * get custom field record
     *
     * @param string|array $nameOrValues
     * @param string $model
     * @param string $type
     * @param array|null $definition
     * @return Tinebase_Model_CustomField_Config
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    protected function _createCustomField($nameOrValues = 'YomiName',
                                          string $model = 'Addressbook_Model_Contact',
                                          string $type = 'string',
                                          ?array $definition = null): Tinebase_Model_CustomField_Config
    {
        $name = is_array($nameOrValues)
            ? $nameOrValues['name'] ?? 'cf' . Tinebase_Record_Abstract::generateUID(8)
            : $nameOrValues;
        $application = substr($model, 0, strpos($model, '_'));
        $configData = [
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName($application)->getId(),
            'name'              => $name,
            'model'             => $model,
        ];
        if (is_array($nameOrValues)) {
            $configData = array_merge($configData, $nameOrValues);
        }

        if (! isset($configData['definition'])) {
            $configData['definition'] = $definition ?? [
                'label' => Tinebase_Record_Abstract::generateUID(),
                'type'  => $type,
                'recordConfig' => $type === 'record'
                    ? array('value' => array('records' => 'Tine.Addressbook.Model.Contact'))
                    : null,
                'uiconfig' => [
                    'xtype'  => Tinebase_Record_Abstract::generateUID(),
                    'length' => 10,
                    'group'  => 'unittest',
                    'order'  => 100,
                ]
            ];
        }

        $cfData = new Tinebase_Model_CustomField_Config($configData);

        try {
            $result = Tinebase_CustomField::getInstance()->addCustomField($cfData);
            $this->_customfieldIdsToDelete[] = $result->getId();
        } catch (Zend_Db_Statement_Exception $zdse) {
            // custom field already exists
            $cfs = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication($application);
            $result = $cfs->filter('name', $name)->getFirstRecord();
        }

        /** @var Tinebase_Model_CustomField_Config $result */
        return $result;
    }

    /**
     * returns a test user object
     *
     * @param array $userdata
     * @return Tinebase_Model_FullUser
     */
    public static function getTestUser($userdata = [])
    {
        $emailDomain = TestServer::getPrimaryMailDomain();

        return new Tinebase_Model_FullUser(array_merge([
            'accountLoginName'      => 'tine20phpunituser',
            'accountStatus'         => 'enabled',
            'accountExpires'        => NULL,
            'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
            'accountLastName'       => 'Tine 2.0',
            'accountFirstName'      => 'PHPUnit User',
            'accountEmailAddress'   => 'phpunit@' . $emailDomain,
        ], $userdata));
    }

    /**
     * return persona/testuser (stat)path
     *
     * @param string|Tinebase_Model_User $persona
     * @param string $appName
     * @return string
     */
    protected function _getPersonalPath($persona = 'sclever', $appName = 'Filemanager')
    {
        if ($persona instanceof Tinebase_Model_User) {
            $userId = $persona->getId();
        } elseif (is_string($persona)) {
            $userId = $this->_personas[$persona]->getId();
        } else {
            $userId = '';
        }
        return Tinebase_FileSystem::getInstance()->getApplicationBasePath(
            $appName,
            Tinebase_FileSystem::FOLDER_TYPE_PERSONAL
        ) . '/' . $userId;
    }

    protected function _setPin()
    {
        if (!Tinebase_Core::getUser()->mfa_configs) {
            Tinebase_Core::getUser()->mfa_configs =
                new Tinebase_Record_RecordSet(Tinebase_Model_MFA_UserConfig::class);
        }
        if (!($pinCfg = Tinebase_Core::getUser()->mfa_configs->find(
            Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID, 'pin'))) {
            $pinCfg = new Tinebase_Model_MFA_UserConfig([
                Tinebase_Model_MFA_UserConfig::FLD_ID => 'userpin',
                Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => 'pin',
                Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS =>
                    Tinebase_Model_MFA_PinUserConfig::class,
                Tinebase_Model_MFA_UserConfig::FLD_CONFIG => new Tinebase_Model_MFA_PinUserConfig()
            ]);
            Tinebase_Core::getUser()->mfa_configs->addRecord($pinCfg);
        }

        $pinCfg->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG} = new Tinebase_Model_MFA_PinUserConfig([
            Tinebase_Model_MFA_PinUserConfig::FLD_HASHED_PIN => Hash_Password::generate('SSHA256', $this->_pin)
        ]);
    }

    protected function _prepTOTP(): TOTPInterface
    {
        $secret = Base32::encodeUpperUnpadded(random_bytes(64));

        $this->_originalTestUser->mfa_configs = new Tinebase_Record_RecordSet(
            Tinebase_Model_MFA_UserConfig::class, [[
            Tinebase_Model_MFA_UserConfig::FLD_ID => 'TOTPunittest',
            Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => 'unittest',
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS =>
                Tinebase_Model_MFA_TOTPUserConfig::class,
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG =>
                new Tinebase_Model_MFA_TOTPUserConfig([
                    Tinebase_Model_MFA_TOTPUserConfig::FLD_SECRET => $secret,
                ]),
        ]]);

        $this->_createAreaLockConfig([
            Tinebase_Model_AreaLockConfig::FLD_MFAS => ['unittest'],
        ], [
            Tinebase_Model_MFA_Config::FLD_ID => 'unittest',
            Tinebase_Model_MFA_Config::FLD_USER_CONFIG_CLASS =>
                Tinebase_Model_MFA_TOTPUserConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG_CLASS =>
                Tinebase_Model_MFA_TOTPConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CLASS =>
                Tinebase_Auth_MFA_HTOTPAdapter::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG => []
        ]);

        $this->_originalTestUser = Tinebase_User::getInstance()->updateUser($this->_originalTestUser);
        return TOTP::create($secret);
    }

    /**
     * @param array $config
     */
    protected function _createAreaLockConfig($config = [], $mfaConfig = [])
    {
        $config = array_merge([
            Tinebase_Model_AreaLockConfig::FLD_AREA_NAME => 'login',
            Tinebase_Model_AreaLockConfig::FLD_AREAS => [Tinebase_Model_AreaLockConfig::AREA_LOGIN],
            Tinebase_Model_AreaLockConfig::FLD_MFAS => ['pin'],
            Tinebase_Model_AreaLockConfig::FLD_VALIDITY => Tinebase_Model_AreaLockConfig::VALIDITY_SESSION,
        ], $config);
        $this->_oldAreaLockCfg = Tinebase_Config::getInstance()->{Tinebase_Config::AREA_LOCKS};
        $locks = new Tinebase_Config_KeyField([
            'records' => new Tinebase_Record_RecordSet(Tinebase_Model_AreaLockConfig::class, [$config])
        ]);
        Tinebase_Config::getInstance()->set(Tinebase_Config::AREA_LOCKS, $locks);

        $mfaConfig = array_merge([
            Tinebase_Model_MFA_Config::FLD_ID => 'pin',
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG_CLASS =>
                Tinebase_Model_MFA_PinConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG =>
                new Tinebase_Model_MFA_PinConfig(),
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CLASS =>
                Tinebase_Auth_MFA_PinAdapter::class,
            Tinebase_Model_MFA_Config::FLD_USER_CONFIG_CLASS =>
                Tinebase_Model_MFA_PinUserConfig::class,
        ], $mfaConfig);
        $mfas = new Tinebase_Config_KeyField([
            'records' => new Tinebase_Record_RecordSet(Tinebase_Model_MFA_Config::class, [$mfaConfig])
        ]);
        Tinebase_Config::getInstance()->set(Tinebase_Config::MFA, $mfas);

        $this->_areaLocksToInvalidate[] = $config[Tinebase_Model_AreaLockConfig::FLD_AREAS][0];
        Tinebase_AreaLock::destroyInstance();
        Tinebase_AreaLock::getInstance()->activatedByFE();
    }

    /**
     * @param $docx
     * @return string
     * @throws Tinebase_Exception
     */
    protected function getPlainTextFromDocx($docx) {
        $zip = new ZipArchive();

        if ($zip->open($docx, ZipArchive::CREATE) !== true) {
            throw new Tinebase_Exception('Cannot open docx file.');
        }

        return strip_tags($zip->getFromName('word/document.xml'));
    }
    
    /**
     * @param $app
     * @param $model
     *
     * @todo coding style (-> _clear)
     */
    protected function clear($app,$model)
    {
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($app . '_Model_' . $model , [
            ['field' => 'creation_time', 'operator' => 'within', 'value' => 'dayThis']
        ]);
        $controller =  Tinebase_Core::getApplicationInstance($app,$model); // @TODO seem not good...
        $controller::getInstance()->deleteByFilter($filter);
    }

    /**
     * skips this test if LDAP or AD user backend is configured
     *
     * @param string $message
     */
    protected function _skipIfLDAPBackend($message = 'Does not work with LDAP/AD backend')
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::LDAP ||
            Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY
        ) {
            self::markTestSkipped($message);
        }
    }

    protected function _skipWithoutEmailSystemAccountConfig()
    {
        if (!Tinebase_EmailUser::isEmailSystemAccountConfigured()) {
            self::markTestSkipped('imap systemaccount config required');
        }
    }

    protected function _skipSundayNight()
    {
        if (Tinebase_DateTime::now()->get('N') == 7 // Sunday
            && (Tinebase_DateTime::now()->get('H') == 22 || Tinebase_DateTime::now()->get('H') == 23)
        ) {
            self::markTestSkipped('FIXME: this fails around Sunday -> Monday midnight ' .
                'as inweek filter uses user tz, but creation_time contains utc');
        }
    }

    /**
     * create node in personal container of test user
     *
     * @param $nodeName
     * @param $filePath
     * @return array
     */
    protected function _createTestNode($nodeName, $filePath)
    {
        $user = Tinebase_Core::getUser();
        $container = Tinebase_FileSystem::getInstance()->getPersonalContainer(
            $user,
            Filemanager_Model_Node::class, $user
        )->getFirstRecord();
        $filepaths = ['/' . Tinebase_FileSystem::FOLDER_TYPE_PERSONAL
            . '/' . $user->accountLoginName
            . '/' . $container->name
            . '/' . $nodeName
        ];
        $tempPath = Tinebase_TempFile::getTempPath();
        $tempFileIds = [Tinebase_TempFile::getInstance()->createTempFile($tempPath)];
        self::assertTrue(is_int($strLen = file_put_contents(
            $tempPath,
            file_get_contents($filePath)
        )));
        $ffj = new Filemanager_Frontend_Json();
        $result = $ffj->createNodes(
            $filepaths,
            Tinebase_Model_Tree_FileObject::TYPE_FILE, $tempFileIds, true
        );
        self::assertEquals(1, count($result));
        return $result;
    }

    /**
     * @param array $xpropsToSet
     * @return Addressbook_Model_List
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _createMailinglist($xpropsToSet = [])
    {
        // create list with unittest user as member
        $name = 'testsievelist' . Tinebase_Record_Abstract::generateUID(5);
        $list = new Addressbook_Model_List([
            'name' => $name,
            'email' => $name . '@' . TestServer::getPrimaryMailDomain(),
            'container_id' => $this->_getTestContainer('Addressbook', 'Addressbook_Model_List'),
            'members'      => [Tinebase_Core::getUser()->contact_id],
        ]);
        $list->xprops()[Addressbook_Model_List::XPROP_USE_AS_MAILINGLIST] = 1;
        foreach ($xpropsToSet as $xprop) {
            $list->xprops()[$xprop] = 1;
        }
        $mailinglist = Addressbook_Controller_List::getInstance()->create($list);
        $this->_listsToDelete[] = $mailinglist;
        $this->_containerToDelete[] = $mailinglist->container_id;
        return $mailinglist;
    }

    /**
     * @return Tinebase_Model_Group|unknown
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     *
     * TODO add more data?
     */
    protected function _createGroup()
    {
        $group = Tinebase_Group::getInstance()->addGroup(new Tinebase_Model_Group([
            'name'      => 'unittestgroup' . Tinebase_Record_Abstract::generateUID(5)
        ]));
        $this->_listsToDelete[] = $group->list_id;

        return $group;
    }

    /**
     * create user account
     *
     * @param array $data
     * @return Tinebase_Model_FullUser
     */
    protected function _createUserWithEmailAccount($data = [])
    {
        $data = array_merge($data, $this->_getUserData(true));
        return $this->_createTestUser($data);
    }

    /**
     * @param bool $withEmail
     * @return array
     */
    protected function _getUserData($withEmail = false)
    {
        $username = 'phpunit_' . Tinebase_Record_Abstract::generateUID(6);
        $data = [
            'accountLoginName'      => $username,
            'accountEmailAddress'   => $username . '@' . TestServer::getPrimaryMailDomain(),
        ];
        if ($withEmail) {
            $data['imapUser'] = new Tinebase_Model_EmailUser([
                'emailAddress' => $username . '@' . TestServer::getPrimaryMailDomain()
            ]);
        }
        return $data;
    }

    /**
     * @param null|array $data
     * @return Tinebase_Model_FullUser
     * @throws Admin_Exception
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _createTestUser($data = null)
    {
        if ($data === null) {
            $data = $this->_getUserData();
        }
        $pw = $data['password'] ?? Tinebase_Record_Abstract::generateUID(16);
        $account = Admin_Controller_User::getInstance()->create(self::getTestUser($data), $pw, $pw);
        $this->_usernamesToDelete[] = $account->accountLoginName;

        return $account;
    }

    /**
     * @param $_modelName
     * @param Tinebase_Model_User $_user
     * @return NULL|Tinebase_Record_Interface
     */
    protected function _getPersonalContainerNode($_modelName = 'Filemanager', $_user = null)
    {
        $user = ($_user) ? $_user : Tinebase_Core::getUser();
        return Tinebase_FileSystem::getInstance()->getPersonalContainer(
            $user,
            $_modelName,
            $user
        )->getFirstRecord();
    }

    /**
     * @param null|Tinebase_Model_Container $personalFilemanagerContainer
     * @return string
     */
    protected function _getPersonalFilemanagerPath($personalFilemanagerContainer = null)
    {
        if (!$personalFilemanagerContainer) {
            $personalFilemanagerContainer = $this->_getPersonalContainerNode(
                'Filemanager',
                Tinebase_Core::getUser()
            );
        }

        $path = '/' . Tinebase_Model_Container::TYPE_PERSONAL
            . '/' . Tinebase_Core::getUser()->accountLoginName
            . '/' . $personalFilemanagerContainer->name;
        return $path;
    }

    protected function _getTestNodes($path, $name = 'test')
    {
        $filter = new Tinebase_Model_Tree_Node_Filter();
        $filter->setFromArrayInUsersTimezone(array(array(
            'field' => 'path',
            'operator' => 'equals',
            'value' => $path
        ), array(
            'field' => 'name',
            'operator' => 'contains',
            'value' => $name
        )));
        return Filemanager_Controller_Node::getInstance()->search($filter, new Tinebase_Model_Pagination([
            'sort' => 'name',
            'dir'  => 'DESC',
        ]));
    }

    /**
     * @param $app
     * @param $model
     * @param $options
     * @param $container
     * @return Tinebase_Model_Container
     * @throws Tinebase_Exception_NotFound
     */
    protected function _importDemoData($app, $model, $options, $container = null)
    {
        $container = $container ? $container : $this->_getTestContainer($app, $model);
        $options['container_id'] = $container->getId();
        $importer = new Tinebase_Setup_DemoData_Import($model, $options);
        $importer->importDemodata();
        return $container;
    }

    /**
     * @param Tinebase_Config_Abstract $config
     * @param string $feature
     * @param boolean $enable
     */
    protected function _setFeatureForTest(Tinebase_Config_Abstract $config, $feature, $enable = true)
    {
        $enabledFeatures = $config->get(Addressbook_Config::ENABLED_FEATURES);
        $enabledFeatures[$feature] = $enable;
        $config->set(Tinebase_Config::ENABLED_FEATURES, $enabledFeatures);

        // TODO reset in tear down? use raii?
    }

    protected function _sendMessageWithAccount($account = null, $to = null)
    {
        if (! $account) {
            $account = Admin_Controller_EmailAccount::getInstance()->getSystemAccount(Tinebase_Core::getUser());
            // remove instance to prevent acl pollution
            Admin_Controller_EmailAccount::destroyInstance();
        }

        Felamimail_Backend_ImapFactory::reset();
        // check if email account exists and mail sending works
        $subject = 'test message ' . Tinebase_Record_Abstract::generateUID(10);
        $message = new Felamimail_Model_Message(array(
            'account_id'    => $account['id'],
            'subject'       => $subject,
            'to'            => $to ? $to : Tinebase_Core::getUser()->accountEmailAddress,
            'body'          => 'aaaaaä <br>',
        ));
        $sendMessage = Felamimail_Controller_Message_Send::getInstance()->sendMessage($message);
        self::assertEquals($message->subject, $sendMessage->subject);
    }

    protected function _unzipContent($zippedContent, $filenameInZip = 'test1.txt')
    {
        $zipfilename = Tinebase_TempFile::getTempPath();
        file_put_contents($zipfilename, $zippedContent);

        // create zip file, unzip, check content
        $zip = new ZipArchive();
        $opened = $zip->open($zipfilename);
        self::assertTrue($opened, 'could not open zip file');
        $zip->extractTo(Tinebase_Core::getTempDir());
        $extractedFile = Tinebase_Core::getTempDir() . DIRECTORY_SEPARATOR . $filenameInZip;
        self::assertTrue(file_exists($extractedFile), 'did not find extracted '
            . $extractedFile . ' file in dir');
        $content = file_get_contents($extractedFile);
        unlink($extractedFile);
        $zip->close();

        return $content;
    }

    protected function _genericCsvExport(array $config, Tinebase_Model_Filter_FilterGroup $filter = null)
    {
        if (isset($config['definitionName'])) {
            $definition = Tinebase_ImportExportDefinition::getInstance()->getByName($config['definitionName']);
        } else {
            $app = Tinebase_Application::getInstance()->getApplicationByName($config['app']);
            $definition = Tinebase_ImportExportDefinition::getInstance()
                ->updateOrCreateFromFilename($config['definition'], $app);
        }

        if (isset($config['exportClass'])) {
            $class = $config['exportClass'];
        } else if ($definition->plugin) {
            $class = $definition->plugin;
        } else {
            $class = Tinebase_Export_CsvNew::class;
        }
        /** @var Tinebase_Export_CsvNew $csv */
        $csv = new $class($filter, null, array(
            'definitionId' => $definition->getId()
        ));
        $csv->generate();

        $fh = fopen('php://memory', 'r+');
        $csv->write($fh);

        return $fh;
    }

    protected function _assertHistoryNote($record, $translatedString, $model, $expectedNumber = 1)
    {
        $history = $this->_getRecordHistory($record['id'], $model);
        self::assertGreaterThan($expectedNumber, $history['totalcount'], 'no update note created');

        $notes = array_filter($history['results'], function($note) {
            return $note['note_type_id'] === Tinebase_Model_Note::SYSTEM_NOTE_NAME_NOTE;
        });
        self::assertCount($expectedNumber, $notes, 'no update note found:' . print_r($history['results'], true));
        $notesMatching = array_filter($notes, function($note) use ($translatedString) {
            return $note['note'] === $translatedString;
        });
        self::assertCount(1, $notesMatching, print_r($notes, true));
    }

    protected function _getRecordHistory($id, $model)
    {
        $tinebaseJson = new Tinebase_Frontend_Json();
        return $tinebaseJson->searchNotes(array(array(
            'field' => 'record_id',
            'operator' => 'equals',
            'value' => $id
        ), array(
            'field' => "record_model",
            'operator' => "equals",
            'value' => $model
        )), array(
            'sort' => array('note_type_id', 'creation_time')
        ));
    }
}

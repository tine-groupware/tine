<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_DovecotTest
 */
class Tinebase_User_EmailUser_Imap_DovecotTest extends TestCase
{
    /**
     * email user backend
     *
     * @var Tinebase_User_Plugin_Abstract
     */
    protected $_backend = null;

    /**
     * @var array test objects
     */
    protected $_objects = array();

    /**
     * @var array config
     */
    protected $_config;

    protected $_oldImapConf = null;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY) {
            // error: Zend_Ldap_Exception: 0x44 (Already exists; 00002071: samldb: Account name (sAMAccountName)
            // 'tine20phpunituser' already in use!): adding: cn=PHPUnit User Tine 2.0,cn=Users,dc=example,dc=org
            $this->markTestSkipped('skipped for ad backends as it does not allow duplicate CNs');
        }

        parent::setUp();

        $this->_config = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP,
            new Tinebase_Config_Struct())->toArray();
        if (!isset($this->_config['backend']) || !('Imap_' . ucfirst($this->_config['backend']) == Tinebase_EmailUser::IMAP_DOVECOT) || $this->_config['active'] != true) {
            $this->markTestSkipped('Dovecot MySQL backend not configured or not enabled');
        }

        $this->_backend = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP);

        $this->_objects['addedUsers'] = array();
        $this->_objects['fullUsers'] = array();
        $this->_objects['emailUserIds'] = array();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
    {
        // delete email account
        foreach ($this->_objects['addedUsers'] as $user) {
            $this->_backend->inspectDeleteUser($user);
        }

        foreach ($this->_objects['fullUsers'] as $user) {
            Tinebase_User::getInstance()->deleteUser($user);
        }

        // also remove remaining stuff from dovecot table - mail accounts no longer linked to a tine user account
        foreach ($this->_objects['emailUserIds'] as $userId) {
            try {
                $this->_backend->deleteUserById($userId);
            } catch (Zend_Db_Statement_Exception $zdse) {
                Tinebase_Exception::log($zdse);
            }
        }

        // also delete from smtp
        $smtpBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP);
        foreach ($this->_objects['emailUserIds'] as $userId) {
            $smtpBackend->deleteUserById($userId);
        }

        if ($this->_oldImapConf) {
            Tinebase_Config::getInstance()->set(Tinebase_Config::IMAP, $this->_oldImapConf);
            Tinebase_User::destroyInstance();
            Tinebase_EmailUser::clearCaches();
            Tinebase_EmailUser::destroyInstance();
            $this->_oldImapConf = null;
        }

        parent::tearDown();
    }

    /**
     * try to add an email account
     */
    public function testAddEmailAccount()
    {
        $user = $this->_createTestUser();
        $emailUser = clone $user;
        $emailUser->imapUser = new Tinebase_Model_EmailUser(array(
            'emailPassword' => Tinebase_Record_Abstract::generateUID(),
            'emailUID' => '1000',
            'emailGID' => '1000',
            'emailAddress' => $user->accountLoginName . '@' . TestServer::getPrimaryMailDomain(),
            'emailLoginname' => $user->accountEmailAddress
        ));

        try {
            $this->_backend->inspectAddUser($user, $emailUser);
        } catch (Zend_Db_Statement_Exception $zdse) {
            Tinebase_Exception::log($zdse);
            self::markTestSkipped('FIXME sometimes, we have random deadlocks ... :(');
        }
        $this->_objects['addedUsers']['emailUser'] = $user;

        $this->_assertImapUser($user);
        return $user;
    }

    /**
     * try to update an email account
     */
    public function testUpdateAccount()
    {
        // add smtp user
        $user = $this->testAddEmailAccount();

        // update user
        $emailUser = clone $user;
        $emailUser->imapUser->emailMailQuota = 600 * 1024 * 1024;

        $this->_backend->inspectUpdateUser($user, $emailUser);
        $this->_assertImapUser($user, array('emailMailQuota' => 600  * 1024 * 1024));
    }

    /**
     * asserts that imapUser object contains the correct data
     *
     * @param $user
     * @param array $additionalExpectations
     */
    protected function _assertImapUser($user, $additionalExpectations = array())
    {
        $this->assertEquals(array_merge(array(
            'emailUserId' => $user->getId(),
            'emailUsername' => $user->imapUser->emailUsername,
            'emailMailQuota' => 2097152000,
            'emailSieveQuota'=> null,
            'emailUID' => !empty($this->_config['dovecot']['uid']) ? $this->_config['dovecot']['uid'] : '1000',
            'emailGID' => !empty($this->_config['dovecot']['gid']) ? $this->_config['dovecot']['gid'] : '1000',
            'emailLastLogin' => null,
            'emailMailSize' => 0,
            'emailSieveSize' => null,
            'emailPort' => $this->_config['port'],
            'emailSecure' => $this->_config['ssl'],
            'emailHost' => $this->_config['host'],
            'emailLoginname' => $user->accountEmailAddress
        ), $additionalExpectations), $user->imapUser->toArray());
    }

    /**
     * testSavingDuplicateAccount
     *
     * @see 0006546: saving user with duplicate imap/smtp user entry fails
     *
     */
    public function testSavingDuplicateAccount()
    {
        $this->_skipIfLDAPBackend();

        $user = $this->_addUser();
        $userId = $user->getId();
        $this->_objects['emailUserIds'][] = $userId;

        // delete user
        Tinebase_User::getInstance()->deleteUser($userId);

        // create user again - should not throw an exception as old email user data gets deleted
        unset($user->accountId);
        $newUser = Tinebase_User::getInstance()->addUser($user);
        $newUser = Tinebase_User::getInstance()->getFullUserById($newUser->getId());
        $this->_objects['fullUsers'] = array($newUser);
        $this->assertNotEquals($userId, $newUser->getId());
        $this->assertTrue(isset($newUser->imapUser), 'imapUser data not found: ' . print_r($newUser->toArray(), true));
        // teardown will delete user -> we need to make sure deleted_times are not equal -> sleep(1)
        sleep(1);
    }

    /**
     * add user with email data
     *
     * @param string $username
     * @param array $userdata
     * @return Tinebase_Model_FullUser
     */
    protected function _addUser($username = null, $userdata = [])
    {
        $user = TestCase::getTestUser($userdata);
        if ($username) {
            $user->accountLoginName = $username;
        }
        $user->imapUser = new Tinebase_Model_EmailUser(array(
            'emailPassword' => Tinebase_Record_Abstract::generateUID(),
            'emailUID' => '1000',
            'emailGID' => '1000'
        ));
        $user = Tinebase_User::getInstance()->addUser($user);
        $this->_objects['fullUsers'] = array($user);

        return $user;
    }

    /**
     * try to set password
     */
    public function testSetPassword()
    {
        $user = $this->testAddEmailAccount();

        $newPassword = Tinebase_Record_Abstract::generateUID();
        $this->_backend->inspectSetPassword($user->getId(), $newPassword);

        // fetch email pw from db
        $dovecot = Tinebase_User::getInstance()->getSqlPlugin(Tinebase_EmailUser_Imap_Dovecot::class);
        $rawDovecotUser = $dovecot->getRawUserById($user);
        if ($rawDovecotUser) {
            $hashPw = new Hash_Password();
            self::assertTrue($hashPw->validate($rawDovecotUser['password'], $newPassword), 'password mismatch');
        } else {
            // FIXME: somehow we can't find the email user
        }
    }

    public function testSetLoginName()
    {
        // fetch dovecot user from db
        $dovecot = Tinebase_User::getInstance()->getSqlPlugin(Tinebase_EmailUser_Imap_Dovecot::class);
        $emailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord(Tinebase_Core::getUser());
        $rawDovecotUser = $dovecot->getRawUserById($emailUser);
        self::assertTrue(isset($rawDovecotUser['loginname']), 'loginname property not found ' . print_r($rawDovecotUser, true));
        self::assertEquals(Tinebase_Core::getUser()->accountEmailAddress, $rawDovecotUser['loginname']);
    }

    /**
     * testDuplicateUserId
     *
     * @see 0007218: Duplicate userid in dovecot_users
     */
    public function testDuplicateUserId()
    {
        $this->_skipIfLDAPBackend();

        $emailDomain = TestServer::getPrimaryMailDomain();
        $user = $this->_addUser('testuser@' . $emailDomain);

        // update user login name
        $user->accountLoginName = 'testuser';
        $user = Tinebase_User::getInstance()->updateUser($user);

        $dovecot = Tinebase_User::getInstance()->getSqlPlugin(Tinebase_EmailUser_Imap_Dovecot::class);
        $emailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($user);
        $rawDovecotUser = $dovecot->getRawUserById($emailUser);
        self::assertNotNull($rawDovecotUser['username'], 'username missing: ' . print_r($rawDovecotUser, true));
        self::assertEquals($user->xprops()[Tinebase_EmailUser_XpropsFacade::XPROP_EMAIL_USERID_IMAP]
            . '@' . $this->_config['instanceName'], $rawDovecotUser['username'],
            'username has not been updated in dovecot user table ' . print_r($rawDovecotUser, true));
    }

    /**
     * testInstanceName
     *
     * @see 0013326: use userid@instancename and for email account name
     *
     * ALTER TABLE `dovecot_users` ADD `instancename` VARCHAR(80) NULL AFTER `username`, ADD INDEX `instancename` (`instancename`);
     */
    public function testInstanceName()
    {
        $this->_skipIfLDAPBackend();

        // check if is instanceName in config
        if (empty($this->_config['instanceName'])) {
            self::markTestSkipped('no instanceName set in config');
        }

        $user = $this->_addUser();

        // check email tables (username + instancename)
        $emailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($user);
        $rawDovecotUser = $this->_getRawDovecotUser($user, $emailUser);
        self::assertTrue(is_array($rawDovecotUser), 'did not fetch dovecotuser: ' . print_r($rawDovecotUser, true));
        self::assertEquals($emailUser->getId() . '@' . $this->_config['instanceName'], $rawDovecotUser['username']);
        self::assertTrue(isset($rawDovecotUser['instancename']), 'instancename missing: ' . print_r($rawDovecotUser, true));
        self::assertEquals($this->_config['instanceName'], $rawDovecotUser['instancename']);
    }

    protected function _getRawDovecotUser($user, $emailUser = null)
    {
        $dovecot = Tinebase_User::getInstance()->getSqlPlugin(Tinebase_EmailUser_Imap_Dovecot::class);
        if ($emailUser === null) {
            $emailUser = Tinebase_EmailUser_XpropsFacade::getEmailUserFromRecord($user);
        }
        return $dovecot->getRawUserById($emailUser);
    }

    public function testAddUserWithSecondaryDomain()
    {
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        if (! isset($smtpConfig['secondarydomains']) || empty($smtpConfig['secondarydomains'])) {
            self::markTestIncomplete('secondarydomains config needed for this test');
        }
        $domains = explode(',', $smtpConfig['secondarydomains']);
        $secEmailDomain = array_shift($domains);
        $username = 'phpunit' . Tinebase_Record_Abstract::generateUID(6);
        $user = $this->_addUser($username, [
            'accountEmailAddress'   => $username . '@' . $secEmailDomain,
        ]);
        $rawDovecotUser = $this->_getRawDovecotUser($user);
        self::assertNotNull($rawDovecotUser, 'could not find dovecot user');
        self::assertEquals(TestServer::getPrimaryMailDomain(), $rawDovecotUser['domain'],
            'primary domain expected: ' . print_r($rawDovecotUser, true));

        return $rawDovecotUser;
    }

    public function testAddUserWithSecondaryDomainWithoutInstanceName()
    {
        $this->_configRemoveInstanceName();

        // username needs to be: phpunit-secondary-domain@DOMAIN!
        $rawDovecotUser = $this->testAddUserWithSecondaryDomain();
        $expectedUsername = preg_replace('/[@\.]+/', '-', $rawDovecotUser['loginname']) . '@' . $rawDovecotUser['domain'];
        self::assertEquals($expectedUsername, $rawDovecotUser['username']);
    }

    public function testUpdateUserWithSecondaryDomainWithoutInstanceName()
    {
        $this->_testNeedsTransaction();

        $rawDovecotUser = $this->testAddUserWithSecondaryDomain();

        $this->_configRemoveInstanceName();

        $testuser = Tinebase_User::getInstance()->getUserByProperty('accountEmailAddress', $rawDovecotUser['loginname']);
        $testuser = Tinebase_User::getInstance()->getFullUserById($testuser->getId());

        // update user -> username must not change!

        Admin_Controller_User::getInstance()->update($testuser);
        $updatedRawDovecotUser = $this->_getRawDovecotUser($testuser);
        self::assertEquals($rawDovecotUser['username'], $updatedRawDovecotUser['username']);

        // get loginname -> username/loginname must not change!

        $mailaccount = Admin_Controller_EmailAccount::getInstance()->getSystemAccount($testuser);
        self::assertNotNull($mailaccount, 'could not find mail account');
        $username = Tinebase_EmailUser::getAccountUsername($mailaccount);
        self::assertEquals($rawDovecotUser['username'], $username);
    }

    /**
     * remove instanceName from imap config
     *
     * @return void
     */
    protected function _configRemoveInstanceName(): void
    {
        Tinebase_User::destroyInstance();
        $this->_oldImapConf = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP);
        $conf = clone $this->_oldImapConf;
        $conf->instanceName = null;
        Tinebase_Config::getInstance()->set(Tinebase_Config::IMAP, $conf);
    }
}

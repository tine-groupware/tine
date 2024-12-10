<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * Test class for Admin_Controller_User
 * 
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Admin_Controller_UserTest extends TestCase
{
    protected $_imapConf = null;
    protected $_smtpConf = null;
    protected $_emailAddressToRemove = null;

    /**
     * set up tests
     */
    protected function setUp(): void
    {
        Tinebase_TransactionManager::getInstance()->unitTestForceSkipRollBack(true);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Tinebase_Config::getInstance()->set(Tinebase_Config::EVENT_HOOK_CLASS, null);
        if ($this->_imapConf !== null && $this->_smtpConf !== null ) {
            Tinebase_Config::getInstance()->set(Tinebase_Config::IMAP, $this->_imapConf);
            Tinebase_Config::getInstance()->set(Tinebase_Config::SMTP, $this->_smtpConf);
            Tinebase_EmailUser::clearCaches();
            Tinebase_EmailUser::destroyInstance();
            // we also need to save the current user again to restore their email user
            // TODO see testUpdateUserWithAlreadyExistingEmailAddressOverwrite
            //Admin_Controller_User::getInstance()->update(Tinebase_Core::getUser());

            // remove mailaccounts after test
            if ($this->_emailAddressToRemove) {
                foreach ([
                             Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP),
                             Tinebase_EmailUser::getInstance(),
                         ] as $emailUserBackend) {
                    $emailUserBackend->deleteOldUserDataIfExists(['email' => $this->_emailAddressToRemove]);
                }
            }
        }
    }

    /**
     * @param string $mode
     * @param bool $overwrite try to overwrite existing user
     * @return void
     * @throws Admin_Exception
     * @throws Tinebase_Exception_Backend_Database_LockTimeout
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function testAddUserWithAlreadyExistingEmailData(string $mode = 'create', bool $overwrite = false)
    {
        $this->_skipWithoutEmailSystemAccountConfig();
        $this->_skipIfLDAPBackend();
        $this->_testNeedsTransaction();

        if ($overwrite) {
            $this->_imapConf = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP);
            $this->_smtpConf = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP);
            $imapConf = $this->_imapConf->toArray();
            $smtpConf = $this->_smtpConf->toArray();
            $imapConf['allowOverwrite'] = $smtpConf['allowOverwrite'] = true;
            Tinebase_Config::getInstance()->set(Tinebase_Config::IMAP, $imapConf);
            Tinebase_Config::getInstance()->set(Tinebase_Config::SMTP, $smtpConf);
            Tinebase_EmailUser::clearCaches();
            Tinebase_EmailUser::destroyInstance();
        }

        $username = 'phpunit' . Tinebase_Record_Abstract::generateUID(10);
        $userToCreate = TestCase::getTestUser([
            'accountLoginName'      => $username,
            'accountEmailAddress'   => $username . '@' . TestServer::getPrimaryMailDomain(),
        ]);
        $this->_emailAddressToRemove = $userToCreate->accountEmailAddress;
        $userToCreate->smtpUser = new Tinebase_Model_EmailUser(array(
            'emailAddress'     => $userToCreate->accountEmailAddress,
        ));
        $pw = Tinebase_Record_Abstract::generateUID(12);
        $user = Admin_Controller_User::getInstance()->create($userToCreate, $pw, $pw);
        $this->_usernamesToDelete[] = $user->accountLoginName;

        try {
            if ($mode === 'create') {
                // remove user from tine20 table and add again
                $backend = new Tinebase_User_Sql();
                $backend->deleteUserInSqlBackend($user);
                // sleep is needed because of deleted_time constraint
                sleep(1);
                $user = Admin_Controller_User::getInstance()->create($userToCreate, $pw, $pw);
            } else if ($mode === 'update') {
                // try update with existing email account with the same address
                $user->accountEmailAddress = Tinebase_Core::getUser()->accountEmailAddress;
                $user->smtpUser = new Tinebase_Model_EmailUser(array(
                    'emailAddress'     => $user->accountEmailAddress,
                ));
                $user = Admin_Controller_User::getInstance()->update($user);
            }
            if ($overwrite) {
                $expected = $mode === 'create' ? $userToCreate->accountEmailAddress : Tinebase_Core::getUser()->accountEmailAddress;
                self::assertEquals($expected, $user->accountEmailAddress);
            } else {
                self::fail('should throw an exception: "email address already exists". user: ' . print_r($user->toArray(), true));
            }
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            if ($overwrite) {
                self::fail('should not throw an exception - overwrite allowed -> ' . $tesg);
            } else {
                $translate = Tinebase_Translation::getTranslation('Tinebase');
                self::assertEquals($translate->_('Email account already exists'), $tesg->getMessage());
            }
        }
    }

    public function testAddUserWithAlreadyExistingEmailDataOverwrite()
    {
        $this->testAddUserWithAlreadyExistingEmailData('create', true);
    }

    public function testUpdateUserWithAlreadyExistingEmailAddress()
    {
        $this->testAddUserWithAlreadyExistingEmailData('update');
    }

    public function testUpdateUserWithAlreadyExistingEmailAddressOverwrite()
    {
        $this->markTestSkipped('TODO make this work - we might need another test account for this');
        $this->testAddUserWithAlreadyExistingEmailData('update', true);
    }

    public function testAddAccountWithMFAConfigSMS()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::MFA, new Tinebase_Config_KeyField([
            'records' => new Tinebase_Record_RecordSet(Tinebase_Model_MFA_Config::class, [[
            Tinebase_Model_MFA_Config::FLD_ID => 'unittest',
            Tinebase_Model_MFA_Config::FLD_USER_CONFIG_CLASS =>
                Tinebase_Model_MFA_SmsUserConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG_CLASS =>
                Tinebase_Model_MFA_GenericSmsConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CLASS =>
                Tinebase_Auth_MFA_GenericSmsAdapter::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG => [
                Tinebase_Model_MFA_GenericSmsConfig::FLD_URL => 'https://shoo.tld/restapi/message',
                Tinebase_Model_MFA_GenericSmsConfig::FLD_BODY => '{"encoding":"auto","body":"{{ message }}","originator":"{{ app.branding.title }}","recipients":["{{ cellphonenumber }}"],"route":"2345"}',
                Tinebase_Model_MFA_GenericSmsConfig::FLD_METHOD => 'POST',
                Tinebase_Model_MFA_GenericSmsConfig::FLD_HEADERS => [
                    'Auth-Bearer' => 'unittesttokenshaaaaalalala'
                ],
                Tinebase_Model_MFA_GenericSmsConfig::FLD_PIN_TTL => 600,
                Tinebase_Model_MFA_GenericSmsConfig::FLD_PIN_LENGTH => 6,
            ]
        ]])
        ]));

        $user = $this->_createUserWithEmailAccount([
            'mfa_configs' => [[
                Tinebase_Model_MFA_UserConfig::FLD_ID => 'userunittest',
                Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => 'unittest',
                Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS => Tinebase_Model_MFA_SmsUserConfig::class,
                Tinebase_Model_MFA_UserConfig::FLD_CONFIG => [
                    Tinebase_Model_MFA_SmsUserConfig::FLD_CELLPHONENUMBER => '01234567890',
                ]
            ]]
        ]);

        $this->assertInstanceOf(Tinebase_Record_RecordSet::class, $user->mfa_configs);
        $this->assertInstanceOf(Tinebase_Model_MFA_UserConfig::class, $user->mfa_configs->getFirstRecord());
        $this->assertSame(Addressbook_Model_Contact::normalizeTelephoneNum('01234567890'), $user->mfa_configs->getFirstRecord()
            ->{Tinebase_Model_MFA_UserConfig::FLD_CONFIG}->{Tinebase_Model_MFA_SmsUserConfig::FLD_CELLPHONENUMBER});
    }

    public function testAddAccountWithMFAConfigs()
    {
        $user = $this->_createUserWithEmailAccount([
            'mfa_configs' => [[
                Tinebase_Model_MFA_UserConfig::FLD_ID => 'test',
                Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => 'test',
                Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS => Tinebase_Model_MFA_PinUserConfig::class,
                Tinebase_Model_MFA_UserConfig::FLD_CONFIG => [
                    Tinebase_Model_MFA_PinUserConfig::FLD_PIN => ''
                ]
            ]]
        ]);

        $this->assertInstanceOf(Tinebase_Record_RecordSet::class, $user->mfa_configs);
        $this->assertInstanceOf(Tinebase_Model_MFA_UserConfig::class, $user->mfa_configs->getFirstRecord());
    }

    /** this test makes Admin_Frontend_Json_EmailAccountTest::testGetSetSieveRuleForSclever fail
     * something doesnt properly get cleaned up in the email account area
    public function testDeleteRenameLogin()
    {
        Admin_Controller_User::getInstance()->delete([$this->_personas['sclever']->getId()]);
        /** @var Tinebase_Model_FullUser $jmcblack *
        $jmcblack = clone $this->_personas['jmcblack'];
        $jmcblack->accountLoginName = 'sclever';
        Admin_Controller_User::getInstance()->update($jmcblack);
        Admin_Controller_User::getInstance()->setAccountPassword($jmcblack, '1234qweRT!', '1234qweRT!');
        $authResult = Tinebase_Auth::getInstance()->authenticate('sclever', '1234qweRT!');
        $this->assertSame(Tinebase_Auth::SUCCESS, $authResult->getCode(), print_r($authResult->getMessages(), true));
    }*/

    public function testAddAccountWithEmailUserXprops()
    {
        $this->_skipWithoutEmailSystemAccountConfig();
        $this->_testNeedsTransaction();

        // create user + check if email user is created
        $user = $this->_createUserWithEmailAccount();
        self::assertTrue(isset($user->xprops()[Tinebase_Model_FullUser::XPROP_EMAIL_USERID_SMTP]),
            'email userid xprop missing: ' . print_r($user->toArray(), true));
        $emailUserBackend = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP);
        $xpropsUser = clone($user);
        Tinebase_EmailUser_XpropsFacade::setIdFromXprops($user, $xpropsUser);
        $userInBackend = $emailUserBackend->getRawUserById($xpropsUser);
        self::assertIsArray($userInBackend);
        self::assertEquals($user->accountEmailAddress, $userInBackend['email'], 'email was not added: '
            . print_r($userInBackend, true));
        self::assertEquals($user->xprops()[Tinebase_Model_FullUser::XPROP_EMAIL_USERID_SMTP], $userInBackend['userid']);

        // add alias
        $emailUser = array (
            'emailMailQuota' => 0,
            'emailMailSize' => 0,
            'emailSieveQuota' => 0,
            'emailSieveSize' => 0,
            'emailLastLogin' => '',
            'emailForwardOnly' => false,
            'emailAliases' =>
                array (
                    0 =>
                        array (
                            'email' => 'aliasxprops@' . TestServer::getPrimaryMailDomain(),
                            'dispatch_address' => 1,
                        ),
                ),
            'emailForwards' =>
                array (
                ),
        );
        $user->emailUser = new Tinebase_Model_EmailUser($emailUser);
        $user->imapUser  = new Tinebase_Model_EmailUser($emailUser);
        $user->smtpUser  = new Tinebase_Model_EmailUser($emailUser);
        $user = Admin_Controller_User::getInstance()->update($user);
        // check aliases
        $user = Admin_Controller_User::getInstance()->get($user->getId());
        self::assertTrue(isset($user->smtpUser->emailAliases) && count($user->smtpUser->emailAliases) === 1,
            print_r($user->toArray(), true));
        self::assertEquals('aliasxprops@' . TestServer::getPrimaryMailDomain(),
            $user->smtpUser->emailAliases[0]['email'], print_r($user->smtpUser->toArray(), true));

        // update user (email address) + check if email user is updated
        $newEmail = 'newaddress' . Tinebase_Record_Abstract::generateUID(6)
            . '@' . TestServer::getPrimaryMailDomain();
        $user->accountEmailAddress = $newEmail;
        Admin_Controller_User::getInstance()->update($user);
        $userInBackend = $emailUserBackend->getRawUserById($xpropsUser);
        self::assertEquals($newEmail, $userInBackend['email'], 'email was not updated: '
            . print_r($userInBackend, true));

        // delete user + check if email user is deleted
        
        // user deletion need the confirmation header
        Admin_Controller_User::getInstance()->setRequestContext(['confirm' => true]);
        Admin_Controller_User::getInstance()->delete([$user->getId()]);
        $userInBackend = $emailUserBackend->getRawUserById($xpropsUser);
        self::assertFalse($userInBackend, 'email user should be deleted: '
            . print_r($userInBackend, true));
    }

    public function testAddUserAdbContainer()
    {
        $container = $this->_getTestContainer(Addressbook_Config::APP_NAME, Addressbook_Model_Contact::class, true);

        $userToCreate = TestCase::getTestUser();
        $userToCreate->container_id = $container->getId();
        $pw = Tinebase_Record_Abstract::generateUID(12);

        $this->_usernamesToDelete[] = $userToCreate->accountLoginName;
        $user = Admin_Controller_User::getInstance()->create($userToCreate, $pw, $pw);

        static::assertSame($container->getId(), $user->container_id);
        static::assertSame($container->getId(),
            Addressbook_Controller_Contact::getInstance()->get($user->contact_id)->getIdFromProperty('container_id'));
    }

    public function testUpdateUserAdbContainer()
    {
        $container = $this->_getTestContainer(Addressbook_Config::APP_NAME, Addressbook_Model_Contact::class, true);

        $userToCreate = TestCase::getTestUser();
        $userToCreate->container_id = $container->getId();
        $pw = Tinebase_Record_Abstract::generateUID(12);

        $this->_usernamesToDelete[] = $userToCreate->accountLoginName;
        $user = Admin_Controller_User::getInstance()->create($userToCreate, $pw, $pw);

        static::assertSame($container->getId(), $user->container_id);
        static::assertSame($container->getId(),
            Addressbook_Controller_Contact::getInstance()->get($user->contact_id)->getIdFromProperty('container_id'));

        $updateContainer = $this->_getTestContainer(Addressbook_Config::APP_NAME, Addressbook_Model_Contact::class,
            true, __METHOD__);

        $user->container_id = $updateContainer->getId();

        $user = Admin_Controller_User::getInstance()->update($user);

        static::assertSame($updateContainer->getId(), $user->container_id);
        static::assertSame($updateContainer->getId(),
            Addressbook_Controller_Contact::getInstance()->get($user->contact_id)->getIdFromProperty('container_id'));
    }

    public function testUpdateUserWithEmailButNoPassword()
    {
        $this->_skipWithoutEmailSystemAccountConfig();
        $this->_skipIfLDAPBackend();

        // $this->_testNeedsTransaction();
        $pw = 'aw%6N64ZR2Pev';

        $userToCreate = TestCase::getTestUser();
        $email = $userToCreate->accountEmailAddress;
        $user = Admin_Controller_User::getInstance()->create($userToCreate, $pw, $pw);
        unset($user->accountEmailAddress);
        $user = Admin_Controller_User::getInstance()->update($user);
        $this->assertEmpty($user->accountEmailAddress);
        $this->_usernamesToDelete[] = $userToCreate->accountLoginName;

        $user->accountEmailAddress = $email;
        $updatedUser = Admin_Controller_User::getInstance()->update($user);

        Admin_Frontend_Json_EmailAccountTest::validateImapUserPw($updatedUser->getId(), $pw);
    }

    public function testAddUserWithExistingMail()
    {
        $this->_skipWithoutEmailSystemAccountConfig();

        $pw = Tinebase_Record_Abstract::generateUID(10);
        $userToCreate = TestCase::getTestUser();
        $userToCreate->accountEmailAddress = Tinebase_Core::getUser()->accountEmailAddress;
        try {
            $user = Admin_Controller_User::getInstance()->create($userToCreate, $pw, $pw);
            self::fail('should throw an exception: "email address already exists". user: ' . print_r($user->toArray(), true));
        } catch (Tinebase_Exception_SystemGeneric $tesg) {
            $translate = Tinebase_Translation::getTranslation('Tinebase');
            self::assertEquals($translate->_('Email account already exists'), $tesg->getMessage());
        }
    }

    public function testUpdateUserRemoveMail()
    {
        $this->_skipWithoutEmailSystemAccountConfig();

        $user = $this->_createUserWithEmailAccount();
        $user->accountEmailAddress = '';
        $updatedUser = Admin_Controller_User::getInstance()->update($user);

        self::assertEmpty($updatedUser->xprops()[Tinebase_Model_FullUser::XPROP_EMAIL_USERID_SMTP],
            'smtp user id still set: ' . print_r($updatedUser->toArray(), true));
        self::assertEmpty($updatedUser->xprops()[Tinebase_Model_FullUser::XPROP_EMAIL_USERID_IMAP],
            'imap user id still set ' . print_r($updatedUser->toArray(), true));
        $oldUser = Tinebase_Core::getUser();
        Tinebase_Core::setUser($user);
        $config = Tinebase_Core::getPreference('Felamimail')->getValue(Felamimail_Preference::DEFAULTACCOUNT);
        self::assertEmpty($config);
        Tinebase_Core::setUser($oldUser);
        $config = Tinebase_Core::getPreference('Felamimail')->getValueForUser(Felamimail_Preference::DEFAULTACCOUNT,$user->getId());
        self::assertEmpty($config);
    }

    public function testCustomEventHookUserAdd()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::EVENT_HOOK_CLASS, 'Admin_Controller_CustomEventHook');
        $pw = Tinebase_Record_Abstract::generateUID(10);
        $userToCreate = TestCase::getTestUser();
        $this->_usernamesToDelete[] = $userToCreate->accountLoginName;
        ob_start();
        Admin_Controller_User::getInstance()->create($userToCreate, $pw, $pw);
        $out = ob_get_clean();
        self::assertEquals('Handled event Admin_Event_AddAccount', $out);
    }

    public function testChangeUserType()
    {
        // try to set type for new user (without feature)
        $user = $this->_createTestUser([
            'type' => 'somethin',
        ]);
        self::assertNull($user->type);

        // try to set type for user (with feature)
        $features = Admin_Config::getInstance()->{Admin_Config::ENABLED_FEATURES};
        $features[Admin_Config::FEATURE_CHANGE_USER_TYPE] = true;
        Admin_Config::getInstance()->set(Admin_Config::ENABLED_FEATURES, $features);

        $user->type = 'somethin';
        $updatedUser = Admin_Controller_User::getInstance()->update($user);
        self::assertEquals($user->type, $updatedUser->type);
    }
}

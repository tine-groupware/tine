<?php

/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     SaasInstance
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching-En, Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Test class for Tinebase_Admin
 */
class SaasInstance_ControllerTest extends TestCase
{
    /**
     * Backend
     *
     * @var Admin_Frontend_Json
     */
    protected $_json;

    /**
     * @var array test $_emailAccounts
     */
    protected $_emailAccounts = array();

    protected $_originalRoleRights = null;
    protected $_oldFileSystemConfig = null;
    protected $_oldQuota = null;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        if (!Tinebase_Application::getInstance()->isInstalled('SaasInstance')) {
            self::markTestSkipped('SaasInstance is not installed.');
        }

        parent::setUp();

        $this->_json = new Admin_Frontend_Json();
        
        if (count($result = Tinebase_Controller_ActionLog::getInstance()->search()) > 0) {
            Tinebase_Controller_ActionLog::getInstance()->delete($result->getArrayOfIds());
        }

        $this->_oldFileSystemConfig = clone Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM};
        $this->_oldQuota = Tinebase_Config::getInstance()->{Tinebase_Config::QUOTA};

    }

    protected function tearDown(): void
    {
        foreach ($this->_emailAccounts as $account) {
            try {
                $this->_json->deleteEmailAccounts([is_array($account) ? $account['id'] : $account->getId()]);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // already removed
            }
        }

        $this->_resetOriginalRoleRights();
        Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM} = $this->_oldFileSystemConfig;
        Tinebase_Config::getInstance()->{Tinebase_Config::QUOTA} = $this->_oldQuota;

        parent::tearDown();
    }

    protected function _resetOriginalRoleRights()
    {
        if (!empty($this->_originalRoleRights)) {
            foreach ($this->_originalRoleRights as $roleId => $rights) {
                Tinebase_Acl_Roles::getInstance()->setRoleRights($roleId, $rights);
            }

            $this->_originalRoleRights = null;
        }
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function testSaveQuotaWithConfirmation()
    {
        // save total quota
        $app = 'Tinebase';
        $additionalData['totalInByte'] = 1234 * 1024 * 1024;

        try {
            Admin_Controller_Quota::getInstance()->setRequestContext([]);
            $this->_json->saveQuota($app, null, $additionalData);
            self::fail('should throw Tinebase_Exception_Confirmation');
        } catch (Tinebase_Exception_Confirmation $e) {
            $translate = Tinebase_Translation::getTranslation('SaasInstance');
            $translation = str_replace('{0}', $app,
                $translate->_("Do you want to change your {0} Quota?"));

            self::assertEquals($translation, $e->getMessage());
        }

        Admin_Controller_Quota::getInstance()->setRequestContext(['confirm' => true]);

        $result = $this->_json->saveQuota($app, null, $additionalData);

        self::assertIsArray($result);
        self::assertEquals(1234, $result[Tinebase_Config::QUOTA_TOTALINMB]);

        Tinebase_ControllerTest::assertActionLogEntry();
    }

    /**
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function testAddUserWithConfirmation()
    {
        $userLimit = SaasInstance_Config::getInstance()->get(SaasInstance_Config::NUMBER_OF_INCLUDED_USERS);
        $noneSystemUserCount = Tinebase_User::getInstance()->countNonSystemUsers();
        SaasInstance_Config::getInstance()->set(SaasInstance_Config::NUMBER_OF_INCLUDED_USERS, $noneSystemUserCount);
        
        try {
            Admin_Controller_User::getInstance()->setRequestContext([]);
            $accountData = $this->_createTestUser();
            self::fail('should throw Tinebase_Exception_Confirmation');
        } catch (Tinebase_Exception_Confirmation $e) {
            $translate = Tinebase_Translation::getTranslation('SaasInstance');
            $translation = $translate->_("Do you want to upgrade your user limit?");

            self::assertEquals($translation, $e->getMessage());
        } finally {
            SaasInstance_Config::getInstance()->set(SaasInstance_Config::NUMBER_OF_INCLUDED_USERS, $userLimit);
        }

        Admin_Controller_User::getInstance()->setRequestContext(['confirm' => true]);
        $accountData = $this->_createTestUser();
        self::assertInstanceOf(Tinebase_Model_FullUser::class, $accountData);

        Tinebase_ControllerTest::assertActionLogEntry();
        return $accountData;
    }

    /**
     * hard quota notification should send mails to 
     * 
     * - configured roles
     * - all users
     * - configures additional emails
     * 
     */
    public function testSendHardQuotaNotification()
    {
        /** @var Tinebase_Model_Tree_Node $node */
        $node = Tinebase_FileSystem::getInstance()->_getTreeNodeBackend()->search(new Tinebase_Model_Tree_Node_Filter(array(
            array('field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Tree_FileObject::TYPE_FOLDER),
            array('field' => 'size', 'operator' => 'greater', 'value' => 2)
        )), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();

        // test hard quota , should send mail to role, additional emails, all users
        $node->quota = 1;
        Tinebase_FileSystem::getInstance()->update($node);
        $this->_testNotifyQuotaHelper($node, false);
    }

    /**
     * sof quota notification should send mails to
     *
     * - configured roles
     * - configures additional emails
     */
    public function testSendSoftQuotaNotification()
    {
        /** @var Tinebase_Model_Tree_Node $node */
        $node = Tinebase_FileSystem::getInstance()->_getTreeNodeBackend()->search(new Tinebase_Model_Tree_Node_Filter(array(
            array('field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Tree_FileObject::TYPE_FOLDER),
            array('field' => 'size', 'operator' => 'greater', 'value' => 2)
        )), new Tinebase_Model_Pagination(['limit' => 1]))->getFirstRecord();
        
        Tinebase_Core::getConfig()->{Tinebase_Config::QUOTA}->{Tinebase_Config::QUOTA_SOFT_QUOTA} = 40;
        $node->quota = $node->size * 2;
        Tinebase_FileSystem::getInstance()->update($node);
        $this->_testNotifyQuotaHelper($node);
    }

    protected function _testNotifyQuotaHelper($node, $softQuota = true)
    {
        $imapConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP, new Tinebase_Config_Struct())->toArray();
        
        if (empty($imapConfig)) {
            static::markTestSkipped('no mail configuration');
        }

        try {
            $notificationRole = Tinebase_Core::getConfig()->{Tinebase_Config::QUOTA}->{Tinebase_Config::QUOTA_SQ_NOTIFICATION_ROLE};
            $role = Tinebase_Role::getInstance()->getRoleByName($notificationRole);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $role = new Tinebase_Model_Role(array(
                'name'                  => $notificationRole,
                'description'           => 'soft quota notification role.',
            ));
            $role = Tinebase_Acl_Roles::getInstance()->createRole($role);

            $user = new Tinebase_Model_FullUser(array(
                'accountLoginName'      => 'saastine20phpunit',
                'accountDisplayName'    => 'saastine20phpunit',
                'accountStatus'         => 'enabled',
                'accountExpires'        => NULL,
                'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
                'accountLastName'       => 'saas',
                'accountFirstName'      => 'tine20phpunit',
                'accountEmailAddress'   => 'saastine20phpunit@' . TestServer::getPrimaryMailDomain(),
            ));
            $user = Admin_Controller_User::getInstance()->create($user, 'pw5823H132', 'pw5823H132');
            $this->_usernamesToDelete[] = $user->accountLoginName;
            
            Tinebase_Acl_Roles::getInstance()->addRoleMember($role->getId(), array(
                'type'     => 'user',
                'id'    => $user->getId()
            ));
        }

        $addresses = [
            'test1@mail.test',
            'test2@mail.test'
        ];

        SaasInstance_Config::getInstance()->set(Tinebase_Config::QUOTA_NOTIFICATION_ADDRESSES, $addresses);
        
        $this->flushMailer();
        Tinebase_FileSystem::getInstance()->notifyQuota();
        
        $messages = $this->getMessages();
        $senders = Tinebase_FileSystem::getInstance()->getNotificationSenders($node);
        $totalCount = 0;

        foreach ($senders as $sender) {
            $recipients = Tinebase_FileSystem::getInstance()->getQuotaNotificationRecipients($sender, $softQuota);
            $totalCount = $totalCount + count($recipients);
        }
        
        static::assertEquals($totalCount, count($messages));

        $actionLogs = Tinebase_ControllerTest::assertActionLogEntry(Tinebase_Model_ActionLog::TYPE_EMAIL_NOTIFICATION, count($senders));

        foreach ($actionLogs as $actionLog) {
            $recipients = Tinebase_FileSystem::getInstance()->getQuotaNotificationRecipients(null, $softQuota);
            foreach ($recipients as $recipient) {
                static::assertStringContainsString($recipient->email, $actionLog->data, 'recipients in action log should include : ' . $recipient->email);
            }
        }
    }

    /**
     * change user type confirmation
     */
    public function testChangeUserTypeConfirmation()
    {
        // enable feature
        $features = Admin_Config::getInstance()->{Admin_Config::ENABLED_FEATURES};
        $features[Admin_Config::FEATURE_CHANGE_USER_TYPE] = true;
        Admin_Config::getInstance()->set(Admin_Config::ENABLED_FEATURES, $features);

        $accountData = $this->_createTestUser();
        $accountData['type'] = Tinebase_Model_FullUser::USER_TYPE_USER;
        Admin_Controller_User::getInstance()->update($accountData);
        try {
            Admin_Controller_User::getInstance()->setRequestContext([]);
            $accountData['type'] = Tinebase_Model_FullUser::USER_TYPE_VOLUNTEER;
            Admin_Controller_User::getInstance()->update($accountData);
            self::fail('should throw Tinebase_Exception_Confirmation');
        } catch (Tinebase_Exception_Confirmation $e) {
            $translate = Tinebase_Translation::getTranslation('SaasInstance');
            $translation = $translate->_("Do you want to change the user type?");

            self::assertEquals($translation, $e->getMessage());
        }
        //test confoirmed request
        $accountData = $this->_createTestUser();
        $accountData['type'] = Tinebase_Model_FullUser::USER_TYPE_USER;
        Admin_Controller_User::getInstance()->update($accountData);
        
        Admin_Controller_User::getInstance()->setRequestContext(['confirm' => true]);
        $accountData['type'] = Tinebase_Model_FullUser::USER_TYPE_VOLUNTEER;
        $account = Admin_Controller_User::getInstance()->update($accountData);
        self::assertEquals($account['type'], Tinebase_Model_FullUser::USER_TYPE_VOLUNTEER);

        $metrics = SaasInstance_Controller::getInstance()->metrics();
        self::assertEquals(1, $metrics[SaasInstance_Config::APP_NAME]['numberOfReducedPriceUsers']);
        
        $features[Admin_Config::FEATURE_CHANGE_USER_TYPE] = false;
        Admin_Config::getInstance()->set(Admin_Config::ENABLED_FEATURES, $features);
    }
}

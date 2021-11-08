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

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->_json = new Admin_Frontend_Json();
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
        $additionalData['totalInMB'] = 1234 * 1024 * 1024;

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
    }
}

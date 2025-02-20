<?php

/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Test
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class MatrixSynapseIntegrator_Controller_UserTests extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        MatrixSynapseIntegrator_Controller_User::getInstance()->setBackend(new MatrixSynapseIntegrator_Backend_CorporalMock());
    }

    public function tearDown(): void
    {
        parent::tearDown();

        MatrixSynapseIntegrator_Controller_User::destroyInstance();
    }

    public function testCreateUser(): Tinebase_Model_FullUser
    {
        $user = $this->_createTestUser([
            'xprops' => [
                MatrixSynapseIntegrator_Config::USER_XPROP_MATRIX_ID => '@{user.id}:matrix.domain',
                MatrixSynapseIntegrator_Config::USER_XPROP_MATRIX_ACTIVE => true,
            ]
        ]);

        self::assertTrue($user->xprops()[MatrixSynapseIntegrator_Config::USER_XPROP_MATRIX_ACTIVE]);
        self::assertEquals('@' . $user->getId() . ':matrix.domain',
            $user->xprops()[MatrixSynapseIntegrator_Config::USER_XPROP_MATRIX_ID]);

        // assert corporal policy json
        $backend = MatrixSynapseIntegrator_Controller_User::getInstance()->getBackend();
        $policy = $backend->getPushedPolicy();
        self::assertArrayHasKey('users', $policy);
        self::assertCount(1, $policy['users']);
        self::assertArrayHasKey('authType', $policy['users'][0]);
        self::assertEquals('sha1', $policy['users'][0]['authType']);
        $userData = $policy['users'][0];
        self::assertEquals($user->xprops()[MatrixSynapseIntegrator_Config::USER_XPROP_MATRIX_ID], $userData['id']);
        self::assertEquals($user->accountDisplayName, $userData['displayName']);
        return $user;
    }

    public function testUpdateUser()
    {
        $user = $this->testCreateUser();
        $user->xprops()[MatrixSynapseIntegrator_Config::USER_XPROP_MATRIX_ACTIVE] = false;
        $updatedUser = Admin_Controller_User::getInstance()->update($user);
        self::assertFalse($updatedUser->xprops()[MatrixSynapseIntegrator_Config::USER_XPROP_MATRIX_ACTIVE]);
        $this->_assertInactiveUserInPolicy();
    }

    protected function _assertInactiveUserInPolicy()
    {
        $backend = MatrixSynapseIntegrator_Controller_User::getInstance()->getBackend();
        $policy = $backend->getPushedPolicy();
        self::assertArrayHasKey('users', $policy);
        self::assertCount(1, $policy['users']);
        self::assertArrayHasKey('active', $policy['users'][0]);
        self::assertFalse($policy['users'][0]['active']);
    }

    public function testDeleteUser()
    {
        $user = $this->testCreateUser();
        // user deletion need the confirmation header
        Admin_Controller_User::getInstance()->setRequestContext(['confirm' => true]);
        Admin_Controller_User::getInstance()->delete([$user->getId()]);
        $this->_assertInactiveUserInPolicy();
    }
}

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

class MatrixSynapseIntegrator_Controller_MatrixAccountTests extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        MatrixSynapseIntegrator_Config::getInstance()->set(
            MatrixSynapseIntegrator_Config::CORPORAL_SHARED_AUTH_TOKEN,
            'SynapseSharedSecretAuthenticatorTineSharedSecret');
        MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->setCorporalBackend(
            new MatrixSynapseIntegrator_Backend_CorporalMock()
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();

        MatrixSynapseIntegrator_Controller_MatrixAccount::destroyInstance();
    }

    public function testCreateUser(): Tinebase_Model_FullUser
    {
        $user = $this->_createTestUser();
        $matrixAccount = MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->create(
            new MatrixSynapseIntegrator_Model_MatrixAccount(
                MatrixSynapseIntegrator_ControllerTests::getMatrixAccountData($user)
            )
        );

        self::assertNotNull($matrixAccount->getPasswordFromProperty(
            MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_SESSION_KEY
        ));
        self::assertEquals(
            32,
            strlen(base64_decode($matrixAccount->getPasswordFromProperty(
                MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_SESSION_KEY
            )))
        );

        // assert corporal policy json
        $backend = MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->getCorporalBackend();
        $policy = $backend->getPushedPolicy();
        self::assertArrayHasKey('users', $policy);
        self::assertCount(1, $policy['users']);
        self::assertArrayHasKey('authType', $policy['users'][0]);
        self::assertEquals('sha1', $policy['users'][0]['authType']);
        $userData = $policy['users'][0];
        self::assertEquals($matrixAccount->{MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID}, $userData['id']);
        self::assertEquals($user->accountDisplayName, $userData['displayName']);
        return $user;
    }

    public function testCreateUserDefaults() {
        $userWithRecoveryKey = $this->_createTestUser();
        $matrixAccountWithRecoveryKey = MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->create(
            new MatrixSynapseIntegrator_Model_MatrixAccount([
                MatrixSynapseIntegrator_Model_MatrixAccount::FLD_ACCOUNT_ID => $userWithRecoveryKey->getId(),
                MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID => '@' . $userWithRecoveryKey->getId() . ':matrix.domain',
                MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_RECOVERY_KEY => 'somekey',
            ])
        );

        self::assertNotNull($matrixAccountWithRecoveryKey->getPasswordFromProperty(
            MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_RECOVERY_KEY
        ));

        self::assertEquals('somekey', $matrixAccountWithRecoveryKey->getPasswordFromProperty(
            MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_RECOVERY_KEY
        ));

        self::assertNull($matrixAccountWithRecoveryKey->getPasswordFromProperty(
            MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_RECOVERY_PASSWORD
        ));

        $userWithoutRecoveryData  = $this->_createTestUser();
        $matrixAccountWithoutRecoveryData = MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->create(
            new MatrixSynapseIntegrator_Model_MatrixAccount([
                MatrixSynapseIntegrator_Model_MatrixAccount::FLD_ACCOUNT_ID => $userWithoutRecoveryData->getId(),
                MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID => '@' . $userWithoutRecoveryData->getId() . ':matrix.domain',
            ])
        );

        self::assertNull($matrixAccountWithoutRecoveryData->getPasswordFromProperty(
            MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_RECOVERY_KEY
        ));

        self::assertNotNull($matrixAccountWithoutRecoveryData->getPasswordFromProperty(
            MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_RECOVERY_PASSWORD
        ));

        // Generated password format itself is not important. It should be secure.
        self::assertGreaterThan(
            31,
            strlen($matrixAccountWithoutRecoveryData->getPasswordFromProperty(
                MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_RECOVERY_PASSWORD
            ))
        );
    }

    public function testUpdateUser()
    {
        $user = $this->testCreateUser();
        $matrixAccount = MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->getMatrixAccountForUser($user);
        MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->delete([$matrixAccount->getId()]);
        $this->_assertInactiveUserInPolicy();
    }

    protected function _assertInactiveUserInPolicy()
    {
        $backend = MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->getCorporalBackend();
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

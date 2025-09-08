<?php
/**
 * @package     MatrixSynapseIntegrator
 * @subpackage  Test
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Json Frontend
 */
class MatrixSynapseIntegrator_Frontend_JsonTest extends TestCase
{
    /**
     * unit in test
     *
     * @var MatrixSynapseIntegrator_Frontend_Json
     */
    protected $_uit = null;

    public function setUp(): void
    {
        parent::setUp();

        MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->setCorporalBackend(
            new MatrixSynapseIntegrator_Backend_CorporalMock()
        );
    }

    public function testMatrixAccountApi($delete = true): array
    {
        $user = Tinebase_Core::getUser();
        return $this->_testSimpleRecordApi(
            MatrixSynapseIntegrator_Model_MatrixAccount::MODEL_NAME_PART,
            null,
            null, // no description because _testSimpleRecordApi does not support TYPE_USER fields atm
            $delete,
            [
                MatrixSynapseIntegrator_Model_MatrixAccount::FLD_ACCOUNT_ID => $user->getId(),
                MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID => '@' . $user->getId() . ':matrix.domain',
                MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_RECOVERY_PASSWORD => 'somepw',
            ],
            false // no update (see above - descriptionField)
        );
    }

    public function testGetBootstrapdata()
    {
        $this->testMatrixAccountApi(false);
        $accountData = $this->_getUit()->getBootstrapdata();
        self::assertIsArray($accountData);
        self::assertEquals('somepw', $accountData['recovery_password']);
    }

    public function testMissingGetBootstrapdata()
    {
        Tinebase_Core::setUser($this->_personas['sclever']);
        try {
            $this->_getUit()->getBootstrapdata();
            self::fail('should throw 404 exception');
        } catch (Tinebase_Exception_NotFound $tenf) {
            self::assertEquals('No Matrix Account found', $tenf->getMessage());
        }
    }

    public function testCreateUpdateMatrixAccountViaAdmin()
    {
        $user = $this->_createTestUser();
        $matrixId = '@' . $user->getId() . ':matrix.domain';
        $user->{Tinebase_Model_FullUser::FLD_MATRIX_ACCOUNT_ID} = [
            MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID => $matrixId,
            MatrixSynapseIntegrator_Model_MatrixAccount::ID => Tinebase_Record_Abstract::generateUID(),
        ];

        $adminFE = new Admin_Frontend_Json();
        $savedUser = $adminFE->saveUser($user->toArray());
        $getUser = $adminFE->getUser($user->getId());

        foreach ([$savedUser, $getUser] as $userToCheck) {
            self::assertArrayHasKey(Tinebase_Model_FullUser::FLD_MATRIX_ACCOUNT_ID, $userToCheck);
            $matrixAccount = $userToCheck[Tinebase_Model_FullUser::FLD_MATRIX_ACCOUNT_ID];
            self::assertEquals($matrixId, $matrixAccount[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID]);
            self::assertEquals($user->getId(), $matrixAccount[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_ACCOUNT_ID]);
            self::assertNotEmpty($matrixAccount[MatrixSynapseIntegrator_Model_MatrixAccount::ID]);
        }
    }

    public function testGetLogindata(): void
    {
        $testSynapse = new MatrixSynapseIntegrator_Backend_SynapseMock();
        MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->setSynapseBackend($testSynapse);

        $this->testMatrixAccountApi(false);
        $result = $this->_getUit()->getLogindata();
        self::assertEquals('@monkey83:matrix.local.tine-dev.de', $result['mx_user_id']);
        self::assertEquals(MatrixSynapseIntegrator_Config::getInstance()
            ->{MatrixSynapseIntegrator_Config::HOME_SERVER_URL}, $result['mx_hs_url']);
    }

    public function testSetRecoveryPassword()
    {
        $this->testMatrixAccountApi(false);
        $pw = 'abcde';
        $updatedMatrixAccount = $this->_getUit()->setRecoveryPassword($pw);
        self::assertEquals($pw, $updatedMatrixAccount['recovery_password']);
    }

    public function testSetRecoveryKey()
    {
        $this->testMatrixAccountApi(false);
        $key = 'abcdefghi';
        $updatedMatrixAccount = $this->_getUit()->setRecoveryKey($key);
        self::assertEquals($key, $updatedMatrixAccount['recovery_key']);
    }
}

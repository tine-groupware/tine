<?php
/**
 * tine Groupware - https://www.tine-groupware.de/
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Test
 * @license     https://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2025-2026 Metaways Infosystems GmbH (https://www.metaways.de)
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

    public function testMatrixAccountApi(bool $delete = true): array
    {
        return $this->_testSimpleRecordApi(
            MatrixSynapseIntegrator_Model_MatrixAccount::MODEL_NAME_PART,
            null,
            null, // no description because _testSimpleRecordApi does not support TYPE_USER fields atm
            $delete,
            MatrixSynapseIntegrator_ControllerTests::getMatrixAccountData(),
            false // no update (see above - descriptionField)
        );
    }

    public function testRoomCreateDelete(): void
    {
        $testSynapse = new MatrixSynapseIntegrator_Backend_SynapseMock();
        MatrixSynapseIntegrator_Controller_Room::getInstance()->setSynapseBackend($testSynapse);

        $list = Addressbook_Controller_List::getInstance()->create(new Addressbook_Model_List([
            'name' => 'test list',
            'container_id' => $this->_getTestContainer(
                Addressbook_Config::APP_NAME, Addressbook_Model_List::class)->getId(),
        ]));
        $roomData = [
            MatrixSynapseIntegrator_Model_Room::FLD_NAME => 'test room',
            MatrixSynapseIntegrator_Model_Room::FLD_TOPIC => 'topic',
            MatrixSynapseIntegrator_Model_Room::FLD_SYSTEM_USER_ONLY => true,
            MatrixSynapseIntegrator_Model_Room::FLD_ACTIVE => true,
        ];

        $adbJson = new Addressbook_Frontend_Json();
        $listData = $list->toArray();
        $listData[MatrixSynapseIntegrator_Config::ADDRESSBOOK_CF_NAME_ROOM] = $roomData;
        $listArray = $adbJson->saveList($listData);

        self::assertArrayHasKey(MatrixSynapseIntegrator_Config::ADDRESSBOOK_CF_NAME_ROOM, $listArray,
            print_r($listArray, true));
        self::assertNotNull($listArray[MatrixSynapseIntegrator_Config::ADDRESSBOOK_CF_NAME_ROOM]);
        $roomData = $listArray[MatrixSynapseIntegrator_Config::ADDRESSBOOK_CF_NAME_ROOM];
        self::assertEquals('test room', $roomData[MatrixSynapseIntegrator_Model_Room::FLD_NAME]);
        self::assertEquals(\MatrixSynapseIntegrator_Backend_SynapseMock::ROOM_ID,
            $roomData[MatrixSynapseIntegrator_Model_Room::FLD_ROOM_ID]);

        // check delete list -> room should be deleted, too
        $adbJson->deleteLists([$listArray['id']]);
        try {
            MatrixSynapseIntegrator_Controller_Room::getInstance()->get($roomData['id']);
            self::fail('room should be deleted');
        } catch (Tinebase_Exception_NotFound $tenf) {
            self::assertStringContainsString('MatrixSynapseIntegrator_Model_Room record with id', $tenf->getMessage());
        }
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
        $matrixIdFromClient = '@@{user.id}:matrix.domain';
        $matrixIdExpected = '@' . $user->getId() . ':matrix.domain';
        $user->{Tinebase_Model_FullUser::FLD_MATRIX_ACCOUNT_ID} = [
            MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID => $matrixIdFromClient,
            MatrixSynapseIntegrator_Model_MatrixAccount::ID => Tinebase_Record_Abstract::generateUID(),
        ];

        $adminFE = new Admin_Frontend_Json();
        $savedUser = $adminFE->saveUser($user->toArray());
        $getUser = $adminFE->getUser($user->getId());

        foreach ([$savedUser, $getUser] as $userToCheck) {
            self::assertArrayHasKey(Tinebase_Model_FullUser::FLD_MATRIX_ACCOUNT_ID, $userToCheck);
            $matrixAccount = $userToCheck[Tinebase_Model_FullUser::FLD_MATRIX_ACCOUNT_ID];
            self::assertEquals($matrixIdExpected, $matrixAccount[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID]);
            self::assertEquals($user->getId(), $matrixAccount[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_ACCOUNT_ID]);
            self::assertNotEmpty($matrixAccount[MatrixSynapseIntegrator_Model_MatrixAccount::ID]);
        }

        $updatedMatrixId = '@somethingelse:matrix.domain';
        $getUser[Tinebase_Model_FullUser::FLD_MATRIX_ACCOUNT_ID]
            [MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID] = $updatedMatrixId;
        $savedUser = $adminFE->saveUser($getUser);
        self::assertEquals($updatedMatrixId, $savedUser[Tinebase_Model_FullUser::FLD_MATRIX_ACCOUNT_ID]
            [MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID]);
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

    public function testChangeMatrixId()
    {
        $account = $this->testMatrixAccountApi(false);
        $account[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID] = '@somethingelse:matrix.domain';
        $account[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_ACCOUNT_ID]
            = $account[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_ACCOUNT_ID]['accountId'];
        $updatedAccount = $this->_getUit()->saveMatrixAccount($account);
        self::assertEquals(
            $account[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID],
            $updatedAccount[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID]
        );
    }

    public function testSaveOwnMatrixAccount()
    {
        MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->setCorporalBackend(
            new MatrixSynapseIntegrator_Backend_CorporalMock()
        );

        $user = $this->_createTestUser();
        $account = MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->create(
            new MatrixSynapseIntegrator_Model_MatrixAccount(
                MatrixSynapseIntegrator_ControllerTests::getMatrixAccountData($user)
            )
        )->toArray();
        $this->_setUser($user);
        $account[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID] = '@somethingelse:matrix.domain';
        $account[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_DESCRIPTION] = 'my account';
        $updatedAccount = $this->_getUit()->saveOwnMatrixAccount($account);
        self::assertNotEquals(
            $account[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID],
            $updatedAccount[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID]
        );
        self::assertEquals(
            $account[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_DESCRIPTION],
            $updatedAccount[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_DESCRIPTION]
        );
    }

    public function testRevealPassword()
    {
        $account = $this->testMatrixAccountApi(false);
        $knownPassword = 'testRecoveryPw123';
        $this->_getUit()->setRecoveryPassword($knownPassword);
        $tbJson = new Tinebase_Frontend_Json();

        $result = $tbJson->revealPassword(MatrixSynapseIntegrator_Model_MatrixAccount::class,
            $account['id'], MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_RECOVERY_PASSWORD);

        self::assertIsArray($result);
        self::assertArrayHasKey('password', $result);
        self::assertEquals($knownPassword, $result['password']);

        $notes = Tinebase_Notes::getInstance()->searchNotes(new Tinebase_Model_NoteFilter([
            ['field' => 'record_id', 'operator' => 'equals', 'value' => $account['id']],
            ['field' => 'record_model', 'operator' => 'equals', 'value' => MatrixSynapseIntegrator_Model_MatrixAccount::class],
            ['field' => 'note_type_id', 'operator' => 'equals', 'value' => Tinebase_Model_Note::SYSTEM_NOTE_REVEAL_PASSWORD],
        ]));
        self::assertCount(1, $notes, 'reveal password note should be logged');
    }

    public function testRevealPasswordInvalidField()
    {
        $account = $this->testMatrixAccountApi(false);
        $tbJson = new Tinebase_Frontend_Json();

        try {
            $tbJson->revealPassword(MatrixSynapseIntegrator_Model_MatrixAccount::class,
                $account['id'], MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID);
            self::fail('Expected Tinebase_Exception_InvalidArgument for non-password field');
        } catch (Tinebase_Exception_InvalidArgument $e) {
            self::assertStringContainsString('not a password field', $e->getMessage());
        }

        try {
            $tbJson->revealPassword(MatrixSynapseIntegrator_Model_MatrixAccount::class,
                $account['id'], 'nonexistent_field');
            self::fail('Expected Tinebase_Exception_InvalidArgument for nonexistent field');
        } catch (Tinebase_Exception_InvalidArgument $e) {
            self::assertStringContainsString('does not exist', $e->getMessage());
        }
    }
}

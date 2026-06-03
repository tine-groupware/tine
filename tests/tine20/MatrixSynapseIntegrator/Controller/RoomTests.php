<?php

/**
 * tine Groupware
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Test
 * @license     https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class MatrixSynapseIntegrator_Controller_RoomTests extends TestCase
{
    public function setUp(): void
    {
        self::_skipIfLDAPBackend();

        parent::setUp();

        MatrixSynapseIntegrator_Config::getInstance()->set(
            MatrixSynapseIntegrator_Config::CORPORAL_SHARED_AUTH_TOKEN,
            'SynapseSharedSecretAuthenticatorTineSharedSecret');
        $corporal = new MatrixSynapseIntegrator_Backend_CorporalMock();
        MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->setCorporalBackend($corporal);
        MatrixSynapseIntegrator_Controller_Room::getInstance()->setCorporalBackend($corporal);

        $synapse = new MatrixSynapseIntegrator_Backend_SynapseMock();
        MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->setSynapseBackend($synapse);
        MatrixSynapseIntegrator_Controller_Room::getInstance()->setSynapseBackend($synapse);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        MatrixSynapseIntegrator_Controller_Room::destroyInstance();
    }

    protected function _createTestRoomWithAccount(string $listName, string $roomName, bool $isActive): MatrixSynapseIntegrator_Model_Room
    {
        $user = $this->_createTestUser();
        MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->create(
            new MatrixSynapseIntegrator_Model_MatrixAccount(
                MatrixSynapseIntegrator_ControllerTests::getMatrixAccountData($user)
            )
        );

        $list = Addressbook_Controller_List::getInstance()->create(new Addressbook_Model_List([
            'name' => $listName,
        ]));
        $list->{MatrixSynapseIntegrator_Config::ADDRESSBOOK_CF_NAME_ROOM} = new MatrixSynapseIntegrator_Model_Room([
            MatrixSynapseIntegrator_Model_Room::FLD_NAME => $roomName,
            MatrixSynapseIntegrator_Model_Room::FLD_TOPIC => 'topic',
            MatrixSynapseIntegrator_Model_Room::FLD_ACTIVE => $isActive,
            MatrixSynapseIntegrator_Model_Room::FLD_SYSTEM_USER_ONLY => true,
        ], true);
        $list->members = [$user->contact_id];
        $updatedList = Addressbook_Controller_List::getInstance()->update($list);

        // does expanding etc.
        $json = new Addressbook_Frontend_Json();
        $listArray = $json->getList($updatedList->getId());

        self::assertNotNull($listArray[MatrixSynapseIntegrator_Config::ADDRESSBOOK_CF_NAME_ROOM],
            print_r($listArray, true));
        $room = new MatrixSynapseIntegrator_Model_Room($listArray[MatrixSynapseIntegrator_Config::ADDRESSBOOK_CF_NAME_ROOM]);
        self::assertNotNull($room->{MatrixSynapseIntegrator_Model_Room::FLD_ROOM_ID});
        return $room;
    }

    protected function _pushPolicy(): void
    {
        $corporal = new MatrixSynapseIntegrator_Backend_CorporalMock();
        MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->setCorporalBackend($corporal);
        MatrixSynapseIntegrator_Controller_Room::getInstance()->setCorporalBackend($corporal);
        $corporal->push();
    }

    public function testCreateRoom(): MatrixSynapseIntegrator_Model_Room
    {
        return $this->_createTestRoomWithAccount(
            'my nice matrix group with a room',
            'test room',
            true
        );
    }

    public function _assertRoomInPolicy(MatrixSynapseIntegrator_Model_Room $room): void
    {
        $backend = MatrixSynapseIntegrator_Controller_Room::getInstance()->getCorporalBackend();
        $policy = $backend->getPushedPolicy();

        // "managedRoomIds": [
        //"!1:a.test"
        //],
        //
        //"users": [
        //{
        //"id":"@a:a.test",
        //"authType": "plain",
        //"joinedRooms": [{"roomId":"!1:a.test", "powerLevel": 10}]
        //}
        //],

        self::assertArrayHasKey('managedRoomIds', $policy);
        self::assertContains(MatrixSynapseIntegrator_Backend_SynapseMock::ROOM_ID, $policy['managedRoomIds']);

        self::assertArrayHasKey('users', $policy);
        foreach ($policy['users'] as $policyUser) {
            self::assertArrayHasKey('joinedRooms', $policyUser);
            self::assertContains([
                'roomId' => MatrixSynapseIntegrator_Backend_SynapseMock::ROOM_ID,
                'powerLevel' => 10,
            ],
            $policyUser['joinedRooms']);
        }
    }
    public function testCorporalPolicy(): void
    {
        $room = $this->testCreateRoom();
        $this->_assertRoomInPolicy($room);
    }

    public function testInactiveRoomNotInPolicy(): void
    {
        $this->_createTestRoomWithAccount(
            'my nice matrix group with an inactive room',
            'inactive test room',
            false
        );
        $this->_pushPolicy();

        $policy = MatrixSynapseIntegrator_Controller_Room::getInstance()->getCorporalBackend()->getPushedPolicy();

        // The inactive room should NOT be in managedRoomIds
        self::assertArrayHasKey('managedRoomIds', $policy);
        self::assertNotContains(MatrixSynapseIntegrator_Backend_SynapseMock::ROOM_ID, $policy['managedRoomIds']);
    }
}

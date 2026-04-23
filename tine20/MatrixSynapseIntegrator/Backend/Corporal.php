<?php

/**
 * MatrixSynapseIntegrator Backend
 *
 * @package      MatrixSynapseIntegrator
 * @subpackage   Backend
 * @license      https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025-2026 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * MatrixSynapseIntegrator Backend
 *
 * @package      MatrixSynapseIntegrator
 * @subpackage   Backend
 */
class MatrixSynapseIntegrator_Backend_Corporal
{
    protected array $_policy = [];

    protected const CORPORAL_ENDPOINT = '_matrix/corporal/policy';

    public function push(): bool
    {
        $this->_policy = $this->_getPolicy();
        $this->pushPolicyToCorporal($this->_policy);

        return true;
    }

    protected function _getHttpClient(): Zend_Http_Client
    {
        $matrixHomeServer = MatrixSynapseIntegrator_Config::getInstance()->get(
            MatrixSynapseIntegrator_Config::HOME_SERVER_URL);
        $corporalUrl = $matrixHomeServer . '/' . self::CORPORAL_ENDPOINT;

        return Tinebase_Core::getHttpClient($corporalUrl);
    }

    /**
     * @see https://github.com/devture/matrix-corporal/blob/master/docs/http-api.md#policy-submission-endpoint
     *
     * @param array $policy
     * @return bool
     * @throws Zend_Http_Client_Exception
     */
    protected function pushPolicyToCorporal(array $policy): bool
    {
        $client = $this->_getHttpClient();
        $client->setHeaders([
            'Authorization' => 'Bearer ' . MatrixSynapseIntegrator_Config::getInstance()->get(
                    MatrixSynapseIntegrator_Config::CORPORAL_SHARED_AUTH_TOKEN),
            'Content-Type' =>  'application/json',
        ]);
        $client->setRawData(json_encode($policy));

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Pushing policy to ' . $client->getUri());
        }

        $client->request(Zend_Http_Client::PUT);

        $response = $client->getLastResponse();
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Response status: ' . $response->getStatus());
        }

        return $response->isSuccessful();
    }

    protected function _getPolicy(): array
    {
        // prevent acls from being checked
        $roomAcl = MatrixSynapseIntegrator_Controller_Room::getInstance()->doRightChecks(false);
        $users = $this->_getUserPolicy();
        $rooms = $this->_getManagedRooms();
        MatrixSynapseIntegrator_Controller_Room::getInstance()->doRightChecks($roomAcl);

        return [
            "schemaVersion" => 2,
            // TODO allow to configure flags
            "flags" => [
                "allowCustomUserDisplayNames" => true,
                "allowCustomUserAvatars" => true,
                "allowCustomPassthroughUserPasswords" => true,
                "forbidRoomCreation" => false,
                "forbidEncryptedRoomCreation" => false,
                "forbidUnencryptedRoomCreation" => false
            ],
            "users" => $users,
            "managedRoomIds" => $rooms,
        ];
    }

    protected function _getUserPolicy(): array
    {
        $policy = [];

        // TODO add paging / use iterator?

        foreach (MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->getBackend()->search() as $active) {
            $policy[] = $this->_getPolicyForAccount($active);
        }

        foreach (MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->getBackend()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                MatrixSynapseIntegrator_Model_MatrixAccount::class, [
                    ['field' => 'is_deleted', 'operator' => 'equals', 'value' => 1]
            ])) as $inactive) {
            $policy[] = $this->_getPolicyForAccount($inactive);
        }

        return $policy;
    }

    protected function _getPolicyForAccount(MatrixSynapseIntegrator_Model_MatrixAccount $matrixAccount): array
    {
        try {
            $user = Tinebase_User::getInstance()->getFullUserById(
                $matrixAccount->{MatrixSynapseIntegrator_Model_MatrixAccount::FLD_ACCOUNT_ID}
            );
        } catch (Tinebase_Exception_NotFound) {
            $user = null;
        }

        $joinedRooms = [];
        if ($user) {
            $rooms = MatrixSynapseIntegrator_Controller_Room::getInstance()->getRoomsForAccount($user);
            foreach ($rooms as $room) {
                $joinedRooms[] = [
                    'roomId' => $room->{MatrixSynapseIntegrator_Model_Room::FLD_ROOM_ID},
                    // TODO where do we set the power level? maybe depending on list membership role?
                    'powerLevel' => 10,
                ];
            }
        }

        return [
            "id" => $matrixAccount->{MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID},
            "active" => $matrixAccount->is_deleted == 0,
            "displayName" => $user ? $user->accountDisplayName : 'unknown',
            "forbidRoomCreation" => false,
            "joinedRooms" => $joinedRooms,
			"authType" => "sha1",
			"authCredential" => $user
                ? Tinebase_User::getInstance()->getPasswordHashByLoginname($user->accountLoginName)
                : '',
//			"authType" => "plain",
//			"avatarUri" => "https://example.com/john.jpg",
        ];
    }

    protected function _getManagedRooms(): array
    {
        // TODO add paging / use iterator / direct backend call?

        $rooms = MatrixSynapseIntegrator_Controller_Room::getInstance()->getAll();
        return $rooms->{MatrixSynapseIntegrator_Model_Room::FLD_ROOM_ID};
    }

    public function getPushedPolicy(): array
    {
        return $this->_policy;
    }
}

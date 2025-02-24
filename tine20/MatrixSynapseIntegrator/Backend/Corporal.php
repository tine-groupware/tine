<?php

/**
 * MatrixSynapseIntegrator Backend
 *
 * @package      MatrixSynapseIntegrator
 * @subpackage   Backend
 * @license      http =>//www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
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

    public function push(Tinebase_Model_FullUser $user): bool
    {
        $this->_policy = $this->_getPolicy($user);
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

    protected function _getPolicy(Tinebase_Model_FullUser $user): array
    {
        // TODO allow to configure defaults/flags

        return [
            "schemaVersion" => 2,
            "flags" => [
                "allowCustomUserDisplayNames" => true,
                "allowCustomUserAvatars" => true,
                "allowCustomPassthroughUserPasswords" => true,
                "forbidRoomCreation" => false,
                "forbidEncryptedRoomCreation" => false,
                "forbidUnencryptedRoomCreation" => false
            ],
            "users" => [
                $this->_getUserPolicy($user)
            ],
        ];
    }

    protected function _getUserPolicy(Tinebase_Model_FullUser $user): array
    {

        return [
            "id" => $user->xprops()[MatrixSynapseIntegrator_Config::USER_XPROP_MATRIX_ID],
            "active" => $user->is_deleted === 0 && $user->xprops()[MatrixSynapseIntegrator_Config::USER_XPROP_MATRIX_ACTIVE],
            "displayName" => $user->accountDisplayName,
            "forbidRoomCreation" => false,
			"authType" => "sha1",
			"authCredential" => Tinebase_User::getInstance()->getPasswordHashByLoginname($user->accountLoginName),
//			"authType" => "plain",
//			"avatarUri" => "https://example.com/john.jpg",
//            "joinedRooms" => [
//				{"roomId": "!roomA:example.com", "powerLevel": 0},
//				{"roomId": "!roomB:example.com", "powerLevel": 50}
//            ],
        ];
    }

    public function getPushedPolicy(): array
    {
        return $this->_policy;
    }
}

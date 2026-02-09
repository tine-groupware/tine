<?php

/**
 * MatrixSynapseIntegrator Backend
 *
 * @package      MatrixSynapseIntegrator
 * @subpackage   Backend
 * @license      https://www.gnu.org/licenses/agpl.html AGPL Version 3
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
            "users" => $this->_getUserPolicy()
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
            $user = Tinebase_User::getInstance()->getUserById(
                $matrixAccount->{MatrixSynapseIntegrator_Model_MatrixAccount::FLD_ACCOUNT_ID}
            );
        } catch (Tinebase_Exception_NotFound) {
            $user = null;
        }
        return [
            "id" => $matrixAccount->{MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID},
            "active" => $matrixAccount->is_deleted == 0,
            "displayName" => $user ? $user->accountDisplayName : 'unknown',
            "forbidRoomCreation" => false,
			"authType" => "sha1",
			"authCredential" => $user
                ? Tinebase_User::getInstance()->getPasswordHashByLoginname($user->accountLoginName)
                : '',
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

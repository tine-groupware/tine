<?php

/**
 * MatrixSynapseIntegrator Backend
 *
 * @package      MatrixSynapseIntegrator
 * @subpackage   Backend
 * @license      https://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright    Copyright (c) 2025 Metaways Infosystems GmbH (https://www.metaways.de)
 * @author       Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * MatrixSynapseIntegrator Backend
 *
 * @package      MatrixSynapseIntegrator
 * @subpackage   Backend
 */
class MatrixSynapseIntegrator_Backend_Synapse
{
    protected const SYNAPSE_ENDPOINT = '_matrix/client/v3';
    protected const LOGIN_ENDPOINT = 'login';
    protected const ROOM_ENDPOINT = 'createRoom';
    protected $managementUserToken = null;

    // Only works for matrix account belonging to current user, if MATRIX_SYNAPSE_SHARED_SECRET_AUTH is disabled.
    // todo: maybe: rename to userLogin AND remove requirement for matrix account, to prevent it being used with
    // a wrong user matrix account
    public function login(MatrixSynapseIntegrator_Model_MatrixAccount $account): array
    {
        $matrix_id = $account->{MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID};
        if (
            MatrixSynapseIntegrator_Config::getInstance()->get(
                MatrixSynapseIntegrator_Config::MATRIX_SYNAPSE_SHARED_SECRET_AUTH
            )
        ) {
            $loginData = $this->_getSharedSecretLoginParams($matrix_id);
        } else {
            $loginData = $this->_getPasswordLoginParams($matrix_id);
        }
        return $this->_synapseRequest(self::LOGIN_ENDPOINT, $loginData);
    }

    protected function _synapseRequest(string $endpoint, array $requestData, ?array $headers = null): array
    {
        $client = $this->_getHttpClient($endpoint);

        $client->setHeaders('Content-Type', 'application/json');

        if ($headers) {
            $client->setHeaders($headers);
        }

        $client->setRawData(json_encode($requestData));

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' [' . $endpoint . '] Synapse call to ' . $client->getUri());
        }

        $client->request(Zend_Http_Client::POST);

        $response = $client->getLastResponse();
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Response status: ' . $response->getStatus());
        }

        if ($response->isSuccessful()) {
            return (array) json_decode($response->getBody());
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                    . ' response: ' . $response->getBody());
            }
            throw new Tinebase_Exception_Backend('synapse request failed');
        }
    }

    protected function _synapseRequestAuthenticated(string $endpoint, array $requestData)
    {
        if (! $this->managementUserToken) {
            $config = MatrixSynapseIntegrator_Config::getInstance();

            $corporalUserId = $config->get(MatrixSynapseIntegrator_Config::CORPORAL_USER_ID);
            if (!$corporalUserId || !$config->get(MatrixSynapseIntegrator_Config::CORPORAL_SHARED_AUTH_TOKEN)) {
                throw new Tinebase_Exception_Backend(
                    'backend not configured correctly: missing CORPORAL_SHARED_AUTH_TOKEN or CORPORAL_USER_ID'
                );
            }

            $loginData = $this->_getSharedSecretLoginParams($corporalUserId);
            $login_response = $this->_synapseRequest(self::LOGIN_ENDPOINT, $loginData);

            $this->managementUserToken = $login_response['access_token'];
        }

        return $this->_synapseRequest($endpoint, $requestData, [
            'Authorization' => 'Bearer ' . $this->managementUserToken
        ]);
    }

    protected function _getPasswordLoginParams(string $matrix_id): array
    {
        $credentials = Tinebase_Core::getUserCredentialCache();
        $credentialsBackend = Tinebase_Auth_CredentialCache::getInstance();
        $credentialsBackend->getCachedCredentials($credentials);

        return [
            'type' => 'm.login.password',
            'password' => $credentials->password,
            'identifier' => [
                'type' => 'm.id.user',
                'user' => $matrix_id,
            ],
        ];
    }

    protected function _getSharedSecretLoginParams(string $matrix_id): array
    {
        $token = hash_hmac('sha512', $matrix_id, MatrixSynapseIntegrator_Config::getInstance()->get(
            MatrixSynapseIntegrator_Config::CORPORAL_SHARED_AUTH_TOKEN
        ));

        return [
            'type' => 'com.devture.shared_secret_auth',
            'token' => $token,
            'identifier' => [
                'type' => 'm.id.user',
                'user' => $matrix_id,
            ],
        ];
    }

    protected function _getHttpClient(string $endpoint): Zend_Http_Client
    {
        $matrixHomeServer = MatrixSynapseIntegrator_Config::getInstance()->get(
            MatrixSynapseIntegrator_Config::HOME_SERVER_URL
        );
        $synapseUrl = $matrixHomeServer . DIRECTORY_SEPARATOR . self::SYNAPSE_ENDPOINT . DIRECTORY_SEPARATOR . $endpoint;

        return Tinebase_Core::getHttpClient($synapseUrl);
    }

    public function createRoom(MatrixSynapseIntegrator_Model_Room $room): string
    {
        // curl --header "Authorization: Bearer ***" -XPOST https://URL/_matrix/client/v3/createRoom
        //      --data '{"name": "test", "preset": "_private_chat_"}'
        // => {"room_id":"ROOM_ID"}

        $roomData = [
            'name' => $room->{MatrixSynapseIntegrator_Model_Room::FLD_NAME},
            'topic' => $room->{MatrixSynapseIntegrator_Model_Room::FLD_TOPIC},
            'initial_state' => [
                [
                    "content" => [
                        "algorithm" => "m.megolm.v1.aes-sha2"
                    ],
                    "type" => "m.room.encryption",
                ],
            ],
        ];
        $responseData = $this->_synapseRequestAuthenticated(self::ROOM_ENDPOINT, $roomData);

        if (! isset($responseData['room_id'])) {
            throw new Tinebase_Exception_Backend('synapse room creation failed');
        }

        return $responseData['room_id'];
    }
}

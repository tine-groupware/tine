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
    protected const LOGIN_ENDPOINT = '_matrix/client/v3/login';

    public function login(MatrixSynapseIntegrator_Model_MatrixAccount $account): array
    {
        $client = $this->_getHttpClient();

        // TODO set some headers?
//        $client->setHeaders([
//            'Authorization' => 'Bearer ' . MatrixSynapseIntegrator_Config::getInstance()->get(
//                    MatrixSynapseIntegrator_Config::CORPORAL_SHARED_AUTH_TOKEN),
//            'Content-Type' =>  'application/json',
//        ]);

        if (MatrixSynapseIntegrator_Config::getInstance()->get(
            MatrixSynapseIntegrator_Config::MATRIX_SYNAPSE_SHARED_SECRET_AUTH
        )) {
            $loginData = $this->_getSharedSecretLoginParams($account);
        } else {
            $loginData = $this->_getPasswordLoginParams($account);
        }
        $client->setRawData(json_encode($loginData));

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Synapse login to ' . $client->getUri());
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
            throw new Tinebase_Exception_Backend('synapse login failed');
        }
    }

    protected function _getPasswordLoginParams(MatrixSynapseIntegrator_Model_MatrixAccount $account): array
    {
        $credentials = Tinebase_Core::getUserCredentialCache();
        $credentialsBackend = Tinebase_Auth_CredentialCache::getInstance();
        $credentialsBackend->getCachedCredentials($credentials);

        return [
            'type' => 'm.login.password',
            'password' => $credentials->password,
            'identifier' => [
                'type' => 'm.id.user',
                'user' => $account->{MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID},
            ],
        ];
    }

    protected function _getSharedSecretLoginParams(MatrixSynapseIntegrator_Model_MatrixAccount $account): array
    {
        $full_user_id = $account->{MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID};
        $token = hash_hmac('sha512', $full_user_id, MatrixSynapseIntegrator_Config::getInstance()->get(
            MatrixSynapseIntegrator_Config::CORPORAL_SHARED_AUTH_TOKEN
        ));

        return [
            'type' => 'com.devture.shared_secret_auth',
            'token' => $token,
            'identifier' => [
                'type' => 'm.id.user',
                'user' => $full_user_id,
            ],
        ];
    }

    protected function _getHttpClient(): Zend_Http_Client
    {
        $matrixHomeServer = MatrixSynapseIntegrator_Config::getInstance()->get(
            MatrixSynapseIntegrator_Config::HOME_SERVER_URL);
        $synapseUrl = $matrixHomeServer . '/' . self::LOGIN_ENDPOINT;

        return Tinebase_Core::getHttpClient($synapseUrl);
    }
}

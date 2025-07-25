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
class MatrixSynapseIntegrator_Backend_Synapse
{
    protected const LOGIN_ENDPOINT = '_matrix/client/v3/login';

    public function login(MatrixSynapseIntegrator_Model_MatrixAccount $account): array
    {
        // curl -X POST https://MATRIXURL/_matrix/client/v3/login -d '{"type": "m.login.password",
        // "identifier": {"type": "m.id.user", "user": "@matrixid:DOMAIN"}, "password": "'"$MATRIX_PASSWORD"'"}'

        $client = $this->_getHttpClient();

        // TODO set some headers?
//        $client->setHeaders([
//            'Authorization' => 'Bearer ' . MatrixSynapseIntegrator_Config::getInstance()->get(
//                    MatrixSynapseIntegrator_Config::CORPORAL_SHARED_AUTH_TOKEN),
//            'Content-Type' =>  'application/json',
//        ]);

        $credentials = Tinebase_Core::getUserCredentialCache();
        $credentialsBackend = Tinebase_Auth_CredentialCache::getInstance();
        $credentialsBackend->getCachedCredentials($credentials);

        $data = [
            'type' => 'm.login.password',
            'password' => $credentials->password,
            'identifier' => [
                'type' => 'm.id.user',
                'user' => $account->{MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID},
            ],
        ];
        $client->setRawData(json_encode($data));

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

    protected function _getHttpClient(): Zend_Http_Client
    {
        $matrixHomeServer = MatrixSynapseIntegrator_Config::getInstance()->get(
            MatrixSynapseIntegrator_Config::HOME_SERVER_URL);
        $synapseUrl = $matrixHomeServer . '/' . self::LOGIN_ENDPOINT;

        return Tinebase_Core::getHttpClient($synapseUrl);
    }
}

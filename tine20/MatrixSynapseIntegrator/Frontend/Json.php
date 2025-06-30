<?php
/**
 * tine Groupware
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * MatrixSynapseIntegrator json fronend
 *
 * @package     MatrixSynapseIntegrator
 * @subpackage  Frontend
 *
 */
class MatrixSynapseIntegrator_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    protected $_applicationName = MatrixSynapseIntegrator_Config::APP_NAME;

    /**
     * the models handled by this frontend
     * @var array
     */
    protected $_configuredModels = [
        MatrixSynapseIntegrator_Model_MatrixAccount::MODEL_NAME_PART,
    ];

    public function setRecoveryPassword()
    {
        // TODO implement
    }

    public function setRecoveryKey()
    {
        // TODO implement
    }
 
    public function regenerateAccessToken()
    {
        // TODO implement
        // one access token per tine account
        // device id are a one to one mapping with access_token
        // device id and access token need to be store until this function is called again
        // only needs to return success
    }

    public function getAccountData()
    {
        $matrixAccount = MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->getMatrixAccountForUser(
            Tinebase_Core::getUser()
        );

        $conf = MatrixSynapseIntegrator_Config::getInstance();

        // @TODO do the master login for user to get accessToken
//        $conf->{MatrixSynapseIntegrator_Config::HOME_SERVER_URL};
//        $conf->{MatrixSynapseIntegrator_Config::COPORAL_SHARED_AUTH_TOKEN};
        // TODO if access token is empty generate one (and device id)
        // TODO if recovery_password empty generate one

        $userData = $this->_recordToJson($matrixAccount);
        return [
            'mx_access_token' => $userData[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ACCESS_TOKEN],
            'mx_hs_url' => $conf->{MatrixSynapseIntegrator_Config::HOME_SERVER_URL},
            'mx_is_url' => $conf->{MatrixSynapseIntegrator_Config::IDENTITY_SERVER_URL},
            'mx_user_id' => $userData[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID],
            'recovery_key' => $userData[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_RECOVERY_KEY],
            'recovery_password' => $matrixAccount->getPasswordFromProperty(
                MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_RECOVERY_PASSWORD
            ),
            'mx_device_id' => $userData[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_DEVICE_ID],
            'session_key' => $userData[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_SESSION_KEY],
        ];
    }
}

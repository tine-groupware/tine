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

    public function setRecoveryPassword(string $password): array
    {
        $matrixAccount = MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->getMatrixAccountForUser(
            Tinebase_Core::getUser()
        );
        $matrixAccount->{MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_RECOVERY_PASSWORD} = $password;
        MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->update($matrixAccount);
        return $this->getBootstrapdata();
    }

    public function setRecoveryKey(string $key): array
    {
        $matrixAccount = MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->getMatrixAccountForUser(
            Tinebase_Core::getUser()
        );
        $matrixAccount->{MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_RECOVERY_KEY} = $key;
        MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->update($matrixAccount);
        return $this->getBootstrapdata();
    }
 
    /**
     * Request new access_token and mx_device_id for user
     * this can be done using the corporal shared auth key or
     * using the users password. Both options will be needed. User password requires synapse to authenticate via
     * ldap or rest against tine. Corporal shared auth key requires shared secret authenticate
     * plugin to be setup in synapse. This is needed if matrix does not authenticate against tine or sso is used.
     * Optional: Request an access token with custom device_id branded with tine e.g tine-ERER2423d. Device ids
     * need to be uniqid
     *
     * @return array
     */
    public function getLogindata()
    {
        $login_response = MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->synapseLogin();

        $conf = MatrixSynapseIntegrator_Config::getInstance();
        return [
            'mx_hs_url' => $conf->{MatrixSynapseIntegrator_Config::HOME_SERVER_URL},
            'mx_is_url' => $conf->{MatrixSynapseIntegrator_Config::IDENTITY_SERVER_URL},
            'mx_user_id' => $login_response['user_id'],
            'mx_device_id' => $login_response['device_id'],
            'mx_access_token' => $login_response['access_token'],
        ];
    }

    /**
     * @return array
     * @throws Tinebase_Exception
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     *
     * TODO if recovery_password empty generate one
     */
    public function getBootstrapdata(): array
    {
        $matrixAccount = MatrixSynapseIntegrator_Controller_MatrixAccount::getInstance()->getMatrixAccountForUser(
            Tinebase_Core::getUser()
        );

        $userData = $this->_recordToJson($matrixAccount);
        return [
            'mx_user_id' => $userData[MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_ID],
            'recovery_key' => $matrixAccount->getPasswordFromProperty(
                MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_RECOVERY_KEY
            ),
            'recovery_password' => $matrixAccount->getPasswordFromProperty(
                MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_RECOVERY_PASSWORD
            ),
            'session_key' => $matrixAccount->getPasswordFromProperty(
                MatrixSynapseIntegrator_Model_MatrixAccount::FLD_MATRIX_SESSION_KEY
            ),
        ];
    }
}

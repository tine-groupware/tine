<?php declare(strict_types=1);

use Jumbojett\OpenIDConnectClient;

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * @todo allow to create user if it does not exist
 */
class Tinebase_Auth_OpenIdConnect extends Tinebase_Auth_Adapter_Abstract
{
    public const TYPE = 'OpenIdConnect';
    public const IDP_CONFIG = 'idpConfig';

    protected ?OpenIDConnectClient $_client = null;
    protected ?SSO_Model_ExIdp_OIdConfig $_idpConfig = null;
    protected ?string $_oidcResponse = null;
    protected $_userInfo = null;
    protected $_user = null;

    public function __construct($options, $username = null, $password = null)
    {
        parent::__construct($options, $username, $password);
        $this->_idpConfig = $options[self::IDP_CONFIG] ?? null;
    }

    public function setOICDResponse(string $oidcResponse): void
    {
        $this->_oidcResponse = $oidcResponse;
    }
    
    /**
     * Performs an authentication attempt
     *
     * @return Zend_Auth_Result
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function authenticate()
    {
        // TODO validate state / auth?

        parse_str((string) $this->_oidcResponse, $responseArray);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($responseArray, true));

        if (! isset($responseArray['access_token'])) {
            throw new Tinebase_Exception_InvalidArgument('no access token in response string');
        }

        $oidc = $this->_getClient();
        $oidc->setAccessToken($responseArray['access_token']);
        $this->_userInfo = $oidc->requestUserInfo();

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->_userInfo, true));

        $this->_user = $this->getLoginUser();

        if ($this->_user) {
            return new Zend_Auth_Result(
                Zend_Auth_Result::SUCCESS,
                $this->_user->accountLoginName
            );
        } else {
            return new Zend_Auth_Result(
                Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID,
                $this->_userInfo->email
            );
        }
    }

    /**
     * send auth request to provider - request gets redirected to tine20 login page
     */
    public function providerAuthRequest(): bool
    {
        $oidc = $this->_getClient();

        $redirectUrl = rtrim(Tinebase_Core::getUrl(), '/') . '/sso/oid/auth/response';
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Set provider redirect url: ' . $redirectUrl);
        $oidc->setRedirectURL($redirectUrl);

        $oidc->addScope(['openid','email']);
        $oidc->setResponseTypes(array('code'));
        $oidc->setAllowImplicitFlow(true);

        // TODO add this (if configured)
        //$oidc->setCertPath('/path/to/my.cert');

        try {
            $oidc->resetClient();
            if (false === $oidc->authenticate() && null !== ($getRedirectUrl = $oidc->didRedirectOccur())) {
                $e = (new Tinebase_Exception_Auth_Redirect())->setUrl($getRedirectUrl);
            } else {
                Tinebase_Exception::log(new Tinebase_Exception('should not happen'));
                return false;
            }
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            return false;
        }

        throw $e;
    }

    /**
     * @return null|Tinebase_Model_FullUser
     */
    public function getLoginUser()
    {
        if ($this->_user) {
            return $this->_user;
        }

        // depends on provider?
        if (! $this->_userInfo->email_verified) {
            return null;
        }
        try {
            // fetch user from DB (need to put user email address in user table)
            // always use email as ID?
            $user = Tinebase_User::getInstance()->getUserByProperty('openid', $this->_userInfo->email, Tinebase_Model_FullUser::class);
        } catch (Tinebase_Exception_NotFound) {
            $user = null;
        }

        return $user;
    }

    public function _getClient(): SSO_Facade_OpenIdConnect_Client
    {
        if ($this->_client === null) {
            if (null === $this->_idpConfig) {
                // legacy
                throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' legacy path, should not be reachable anymore');
            } else {
                $provider_url = $this->_idpConfig->{SSO_Model_ExIdp_OIdConfig::FLD_PROVIDER_URL};
                $client_id = $this->_idpConfig->{SSO_Model_ExIdp_OIdConfig::FLD_CLIENT_ID};
                $client_secret = $this->_idpConfig->getClientSecret();
                $issuer = $this->_idpConfig->{SSO_Model_ExIdp_OIdConfig::FLD_ISSUER};
            }

            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Set provider url: ' . $provider_url);

            $this->_client = new SSO_Facade_OpenIdConnect_Client($provider_url,
                $client_id,
                $client_secret,
                $issuer);
        }

        return $this->_client;
    }
}

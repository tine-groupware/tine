<?php declare(strict_types=1);

use League\OAuth2\Server\Grant\GrantTypeInterface;
use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * facade for the Authorization Server
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class SSO_Facade_OpenIdConnect_AuthorizationServer extends \League\OAuth2\Server\AuthorizationServer
{
    protected ?SSO_Facade_OAuth2_CryptKey $activePrivateKey = null;
    protected ?string $activePublicKey = null;

    protected function getActivePrivateKey(): SSO_Facade_OAuth2_CryptKey
    {
        if ($this->activePrivateKey !== null) {
            return $this->activePrivateKey;
        }

        $keyConfig = SSO_Controller::getActiveOAuth2Key();
        $this->activePrivateKey = new SSO_Facade_OAuth2_CryptKey($keyConfig['privatekey'], $keyConfig['kid']);
        $this->activePublicKey = $keyConfig['publickey'];
        return $this->activePrivateKey;
    }

    public function __construct()
    {
        $activeKey = $this->getActivePrivateKey();

        parent::__construct(
            new SSO_Facade_OAuth2_ClientRepository(),
            new SSO_Facade_OAuth2_AccessTokenRepository(),
            new SSO_Facade_OAuth2_ScopeRepository(),
            $activeKey,
            $this->activePublicKey,
            new \Idaas\OpenID\ResponseTypes\BearerTokenResponse
        );

        $grant = new SSO_Facade_OpenIdConnect_AuthCodeGrant(
            new SSO_Facade_OAuth2_AuthCodeRepository(),
            new SSO_Facade_OAuth2_RefreshTokenRepository(),
            new SSO_Facade_OpenIdConnect_ClaimRepository(),
            new \Idaas\OpenID\Session(),
            new \DateInterval('PT10M'), // authorization codes will expire after 10 minutes
            new \DateInterval('PT1H') // id tokens will expire after 1 hour
        );

        $grant->setUserRepository(new SSO_Facade_OpenIdConnect_UserRepository());
        $grant->setIssuer(SSO_Controller::getOAuthIssuer());
        $grant->setRefreshTokenTTL(new \DateInterval('P1D')); // refresh tokens will expire after 1 day

        // Enable the authentication code grant on the server
        $this->enableGrantType(
            $grant,
            new \DateInterval('PT1H') // access tokens will expire after 1 hour
        );

        $grant = new SSO_Facade_OpenIdConnect_DeviceCodeGrant(
            new SSO_Facade_OAuth2_AuthCodeRepository(),
            new SSO_Facade_OAuth2_RefreshTokenRepository(),
            new SSO_Facade_OpenIdConnect_ClaimRepository(),
            new \Idaas\OpenID\Session(),
            new \DateInterval('PT10M'), // authorization codes will expire after 10 minutes
            new \DateInterval('PT1H') // id tokens will expire after 1 hour
        );

        $grant->setUserRepository(new SSO_Facade_OpenIdConnect_UserRepository());
        $grant->setIssuer(SSO_Controller::getOAuthIssuer());
        $grant->setRefreshTokenTTL(new \DateInterval('P1D')); // refresh tokens will expire after 1 day

        // Enable the authentication code grant on the server
        $this->enableGrantType(
            $grant,
            new \DateInterval('PT1H') // access tokens will expire after 1 hour
        );

        /*
        $grant = new \League\OAuth2\Server\Grant\PasswordGrant(
            $userRepository,
            $refreshTokenRepository
        );

        $grant->setRefreshTokenTTL(new \DateInterval('P1M')); // refresh tokens will expire after 1 month

        // Enable the password grant on the server
        $this->enableGrantType(
            $grant,
            new \DateInterval('PT1H') // access tokens will expire after 1 hour
        );*/
    }

    public function getIdToken(Tinebase_Model_FullUser $account, string $azp, ?array $claims = null, ?DateInterval $ttl = null): string
    {
        if (null === ($rp = SSO_Controller_RelyingParty::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(SSO_Model_RelyingParty::class, [
                    [TMFA::FIELD => SSO_Model_RelyingParty::FLD_NAME, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $azp],
                ]))->getFirstRecord())) {
            $rp = new SSO_Model_RelyingParty([
                SSO_Model_RelyingParty::ID => Tinebase_Record_Abstract::generateUID(),
                SSO_Model_RelyingParty::FLD_NAME => $azp,
                SSO_Model_RelyingParty::FLD_CONFIG_CLASS => SSO_Model_OAuthOIdRPConfig::class,
                SSO_Model_RelyingParty::FLD_CONFIG => new SSO_Model_OAuthOIdRPConfig([
                    SSO_Model_OAuthOIdRPConfig::FLD_OAUTH2_GRANTS => new Tinebase_Record_RecordSet(SSO_Model_OAuthGrant::class, [
                        new SSO_Model_OAuthGrant([
                            SSO_Model_OAuthGrant::FLD_GRANT => \SSO_Config::OAUTH2_GRANTS_AUTHORIZATION_CODE,
                        ], true),
                    ]),
                ], true),
            ], true);
        }

        /** @var SSO_Facade_OpenIdConnect_DeviceCodeGrant $grant */
        $grant = $this->enabledGrantTypes[SSO_Facade_OpenIdConnect_DeviceCodeGrant::IDENTIFIER];
        /** @var \Idaas\OpenID\ResponseTypes\BearerTokenResponse $response */
        $response = $grant->getAccessTokenReponse($account, $rp, $this->getResponseType(),
            $ttl ?: $this->grantTypeAccessTokenTTL[SSO_Facade_OpenIdConnect_DeviceCodeGrant::IDENTIFIER], $claims);

        /** @var \Idaas\OpenID\Entities\IdToken $idToken */
        $idToken = $response->getIdToken();
        return $idToken->convertToJWT($this->getActivePrivateKey())->toString();
    }
}

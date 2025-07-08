<?php declare(strict_types=1);
/**
 * facade for AuthCodeGrant
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Idaas\OpenID\Entities\IdToken;
use Idaas\OpenID\Grant\AuthCodeGrant;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @property SSO_Facade_OpenIdConnect_UserRepository $userRepository
 */
class SSO_Facade_OpenIdConnect_AuthCodeGrant extends AuthCodeGrant
{
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        \DateInterval $accessTokenTTL
    ): ResponseTypeInterface {

        list($clientId) = $this->getClientCredentials($request);
        /** @var SSO_Facade_OAuth2_ClientEntity $client */
        $client = $this->getClientEntityOrFail($clientId, $request);
        if ($ttl = $client->getRelyingPart()->{SSO_Model_RelyingParty::FLD_ACCESS_TOKEN_TTL}) {
            $this->idTokenTTL = $accessTokenTTL = new DateInterval('PT' . $ttl . 'M');
        }

        /** @var \Idaas\OpenID\ResponseTypes\BearerTokenResponse $result */
        $result = parent::respondToAccessTokenRequest($request, $responseType, $accessTokenTTL);

        /** @var SSO_Facade_OAuth2_AccessTokenEntity $accessToken */
        $accessToken = $result->getAccessToken();
        /** @var SSO_Facade_OpenIdConnect_IdToken $idToken */
        $idToken = $result->getIdToken();
        $idToken->setIdentifier($accessToken->getIdentifier());

        $userEntity = $this->userRepository->getUserByIdentifier($accessToken->getUserIdentifier());
        foreach ($this->userRepository->getAttributes($userEntity, $accessToken->getClaims(), $accessToken->getScopes()) as $claim => $value) {
            $idToken->addExtra($claim, $value);
        }

        return $result;
    }

    protected function makeIdTokenInstance()
    {
        return new SSO_Facade_OpenIdConnect_IdToken();
    }

    protected function addMoreClaimsToIdToken(IdToken $idToken)
    {
        //if (in_array('groups', $this->accessTokenRepository->getStoredClaims())) {
            $userEntity = $this->userRepository->getUserByIdentifier($idToken->getSubject());
            $result = $this->userRepository->getAttributes($userEntity, ['groups'], []);
            if ($result['groups'] ?? false) {
                $idToken->addExtra('groups', $result['groups']);
            }
        //}

        return $idToken;
    }
}
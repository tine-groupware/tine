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

        /** @var \League\OAuth2\Server\Entities\TokenInterface $accessToken */
        $accessToken = $result->getAccessToken();
        $result->getIdToken()->setIdentifier($accessToken->getIdentifier());

        return $result;
    }

    protected function makeIdTokenInstance()
    {
        return new SSO_Facade_OpenIdConnect_IdToken();
    }

    protected function addMoreClaimsToIdToken(IdToken $idToken)
    {
        //if (in_array('groups', $this->accessTokenRepository->getStoredClaims())) {
            $userRepo = new SSO_Facade_OpenIdConnect_UserRepository();
            $userEntity = $userRepo->getUserByIdentifier($idToken->getSubject());
            $result = $userRepo->getAttributes($userEntity, ['groups'], []);
            if ($result['groups'] ?? false) {
                $idToken->addExtra('groups', $result['groups']);
            }
        //}

        return $idToken;
    }
}
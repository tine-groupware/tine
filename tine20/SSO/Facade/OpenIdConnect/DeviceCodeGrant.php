<?php declare(strict_types=1);
/**
 * facade for DeviceCodeGrant
 *
 * @package     SSO
 * @subpackage  Facade
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

class SSO_Facade_OpenIdConnect_DeviceCodeGrant extends SSO_Facade_OpenIdConnect_AuthCodeGrant
{
    public function getIdentifier(): string
    {
        return 'urn:ietf:params:oauth:grant-type:device_code';
    }

    public function canRespondToAuthorizationRequest(ServerRequestInterface $request): bool
    {
        return false;
    }

    public function canRespondToAccessTokenRequest(ServerRequestInterface $request): bool
    {
        $requestParameters = (array)$request->getParsedBody();

        if ('urn:ietf:params:oauth:grant-type:device_code' !== ($requestParameters['grant_type'] ?? null)) {
            return false;
        }

        if (!$requestParameters['device_code'] ?? false) {
            throw OAuthServerException::invalidRequest('device_code');
        }

        if (!$request instanceof \Laminas\Diactoros\ServerRequest) {
            throw new Tinebase_Exception_Backend('unsupported server request implemenation');
        }

        return true;
    }

    public function respondToAccessTokenRequest(
            ServerRequestInterface $request,
            ResponseTypeInterface $responseType,
            \DateInterval $accessTokenTTL
        ): ResponseTypeInterface
    {
        $requestParameters = (array)$request->getParsedBody();

        try {
            $deviceCode = SSO_Controller_OAuthDeviceCode::getInstance()->get($requestParameters['device_code']);
            if ($deviceCode->{SSO_Model_OAuthDeviceCode::FLD_VALID_UNTIL}->isEarlier(Tinebase_DateTime::now())) {
                throw new Tinebase_Exception_NotFound('');
            }
            if (null === $deviceCode->{SSO_Model_OAuthDeviceCode::FLD_APPROVED_BY}) {
                throw new OAuthServerException('device_code is not yet authroized, try again', 0, 'authorization_pending');
            }
        } catch (Tinebase_Exception_NotFound) {
            throw new OAuthServerException('device_code is not valid', 0, 'access_denied');
        }

        /** @var SSO_Model_RelyingParty $rp */
        $rp = SSO_Controller_RelyingParty::getInstance()->get(
            $deviceCode->getIdFromProperty(SSO_Model_OAuthDeviceCode::FLD_RELYING_PARTY_ID)
        );
        $rp->{SSO_Model_RelyingParty::FLD_CONFIG}->{SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS} = ['/'];
        $clientEntity = new SSO_Facade_OAuth2_ClientEntity($rp);

        $authRequest = new \Idaas\OpenID\RequestTypes\AuthenticationRequest();
        $authRequest->setAuthorizationApproved(true);
        $authRequest->setClient($clientEntity);
        $authRequest->setUser(new SSO_Facade_OAuth2_UserEntity(Tinebase_User::getInstance()->getFullUserById($deviceCode->getIdFromProperty(SSO_Model_OAuthDeviceCode::FLD_APPROVED_BY))));
        $authRequest->setRedirectUri('/');
        $authRequest->setResponseType('code');

        $response = $this->completeAuthorizationRequest($authRequest)->generateHttpResponse(new \Laminas\Diactoros\Response());
        $token = substr(current($response->getHeader('location')), 7);

        $request = $request->withParsedBody([
            'code' => $token,
            'client_id' => $clientEntity->getIdentifier(),
            'client_secret' => $rp->{SSO_Model_RelyingParty::FLD_CONFIG}->{SSO_Model_OAuthOIdRPConfig::FLD_SECRET},
            'redirect_uri' => '/',
        ]);

        $this->setClientRepository(new SSO_Facade_OAuth2_ClientRepositoryStatic($clientEntity));

        return parent::respondToAccessTokenRequest($request, $responseType, $accessTokenTTL);
    }
}
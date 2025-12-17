<?php declare(strict_types=1);
/**
 * MAIN controller for SSO application
 *
 * @package     SSO
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Jumbojett\OpenIDConnectClientException;
use League\OAuth2\Server\CryptKey;
use Tinebase_Model_Filter_Abstract as TMFA;

use SAML2\AuthnRequest;
use SAML2\Binding;
use SAML2\Constants;
use SAML2\XML\saml\Issuer;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use Strobotti\JWK\KeyFactory;


/**
 * 
 * @package     SSO
 * @subpackage  Controller
 */
class SSO_Controller extends Tinebase_Controller_Event
{
    use Tinebase_Controller_SingletonTrait;

    public const WEBFINGER_REL = 'http://openid.net/specs/connect/1.0/issuer';
    public const OIDC_AUTH_REQUEST_TYPE = 'oidc_auth';
    public const OIDC_AUTH_CLIENT_REQUEST_TYPE = 'oidc_auth_client';

    protected $_applicationName = SSO_Config::APP_NAME;

    protected static $_logoutHandlerRecursion = false;

    public static function addFastRoutes(FastRoute\RouteCollector $r): void {
        $r->get('/.well-known/openid-configuration', (new Tinebase_Expressive_RouteHandler(
            self::class, 'publicGetWellKnownOpenIdConfiguration', [
            Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
        ]))->toArray());

        $r->addGroup('/sso', function (\FastRoute\RouteCollector $routeCollector) {
            $routeCollector->addRoute(['GET'], '/oid/auth/response', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicOidAuthResponse', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->addRoute(['GET', 'POST'], '/oauth2/authorize', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicAuthorize', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->addRoute(['GET', 'POST'], '/oauth2/token', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicToken', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->addRoute(['POST'], '/oauth2/device/auth', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicOAuthDeviceAuth', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->addRoute(['GET', 'POST'], '/oauth2/device/user[/{userCode}]', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicOAuthDeviceUser', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->addRoute(['POST'], '/oauth2/device/userlogin[/{userCode}]', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicOAuthDeviceUserLogin', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->post('/oauth2/register', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicRegister', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->get('/oauth2/certs', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicCerts', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->get('/openidconnect/userinfo', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicOIUserInfo', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->get('/saml2/idpmetadata', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicSaml2IdPMetaData', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->addRoute(['GET', 'POST'], '/saml2/redirect/signon', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicSaml2RedirectRequest', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
            $routeCollector->addRoute(['GET', 'POST'], '/saml2/redirect/logout', (new Tinebase_Expressive_RouteHandler(
                self::class, 'publicSaml2RedirectLogout', [
                Tinebase_Expressive_RouteHandler::IS_PUBLIC => true
            ]))->toArray());
        });
    }

    public static function serviceNotEnabled(): \Psr\Http\Message\ResponseInterface
    {
        return new \Laminas\Diactoros\Response('php://memory', 403);
    }

    public static function publicCerts(): \Psr\Http\Message\ResponseInterface
    {
        if (! SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::ENABLED}) {
            return self::serviceNotEnabled();
        }

        $keys = [];

        foreach (SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::OAUTH2_KEYS} as $key) {
            if (!isset($key['use']) || !isset($key['kty']) || !isset($key['alg']) || !isset($key['kid']) || !isset($key['e']) || !isset($key['n'])) {
                continue;
            }
            $keys[] = [
                'use' => $key['use'],
                'kty' => $key['kty'],
                'alg' => $key['alg'],
                'kid' => $key['kid'],
                'e'   => $key['e'],
                'n'   => $key['n'],
            ];
        }

        $response = (new \Laminas\Diactoros\Response())
            // the jwks_uri SHOULD include a Cache-Control header in the response that contains a max-age directive
            ->withHeader('cache-control', 'public, max-age=20683, must-revalidate, no-transform');
        $response->getBody()->write(json_encode(['keys' => $keys]));

        return $response;
    }

    public static function publicRegister(): \Psr\Http\Message\ResponseInterface
    {
        //TODO FIXME: The OpenID Provider MAY require an Initial Access Token that is provisioned out-of-band
        //if (! SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::ENABLED}) {
            return self::serviceNotEnabled();
        //}
    }

    public static function publicOIUserInfo(): \Psr\Http\Message\ResponseInterface
    {
        if (!SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::ENABLED}) {
            return self::serviceNotEnabled();
        }

        /** @var \Psr\Http\Message\ServerRequestInterface $request */
        $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);
        $userInfo = new Idaas\OpenID\UserInfo(
            $userRep = new SSO_Facade_OpenIdConnect_UserRepository(),
            $tokenRepo = new SSO_Facade_OAuth2_AccessTokenRepository(),
            new \League\OAuth2\Server\ResourceServer(
                $tokenRepo,
                $cryptKey = new CryptKey(SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::OAUTH2_KEYS}[0]['publickey']),
                $bearerTokenValidator = new SSO_Facade_OAuth2_BearerTokenValidator($tokenRepo, $userRep)
            ),
            new SSO_Facade_OpenIdConnect_ClaimRepository()
        );
        $bearerTokenValidator->setPublicKey($cryptKey);
        try {
            $userInfo->respondToUserInfoRequest($request, $response = new \Laminas\Diactoros\Response(headers: ['Cache-Control' => 'no-store']));
        } catch (Tinebase_Exception_NotFound) {
            return new \Laminas\Diactoros\Response(status: 401);
        }
        return $response;
    }

    protected static function getLoginFakeRequest(string $url): Tinebase_Http_Request
    {
        /** @var \Psr\Http\Message\ServerRequestInterface $request */
        $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);

        return Tinebase_Http_Request::fromString(
            "POST $url HTTP/1.1\r\n"
            . (($line = $request->getHeaderLine('USER-AGENT')) ? 'USER-AGENT: ' . $line . "\r\n" : '')
            . "\r\n\r\n")->setServer(new \Laminas\Stdlib\Parameters([$request->getServerParams()]));
    }

    protected static function processJsLoginRequest(\Psr\Http\Message\ServerRequestInterface $request): ?\Psr\Http\Message\ResponseInterface
    {
        if (isset($request->getParsedBody()['username'])) {
            try {
                if (!empty($request->getParsedBody()['password'] ?? null)) {
                    Tinebase_Controller::getInstance()->forceUnlockLoginArea();
                    try {
                        Tinebase_Controller::getInstance()->setRequestContext(array(
                            'MFAPassword' => isset($request->getParsedBody()['MFAPassword']) ? $request->getParsedBody()['MFAPassword'] : null,
                            'MFAId'       => isset($request->getParsedBody()['MFAUserConfigId']) ? $request->getParsedBody()['MFAUserConfigId'] : null,
                        ));
                        Tinebase_Controller::getInstance()->login($request->getParsedBody()['username'],
                            $request->getParsedBody()['password'],
                            static::getLoginFakeRequest($request->getUri()->getPath()),
                            self::OIDC_AUTH_REQUEST_TYPE);
                    } finally {
                        Tinebase_Controller::getInstance()->forceUnlockLoginArea(false);
                    }
                } else {
                    static::passwordLessLogin($request->getParsedBody()['username']);
                }
                if (!Tinebase_Core::getUser()) {
                    throw new Tinebase_Exception_Auth_PwdRequired('Wrong username or password!');
                }
            } catch (Tinebase_Exception_AreaUnlockFailed | Tinebase_Exception_AreaLocked
                | Tinebase_Exception_Auth_PwdRequired | Tinebase_Exception_Auth_Redirect $tea)
            {
                // 630 + 631 + 650 + 651
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    Tinebase_Core::getLogger()->info(
                        __METHOD__ . '::' . __LINE__ . ' ' . $tea->getMessage());
                }

                return static::getJsonLoginFailureResponse($tea);
            }
        }
        return null;
    }

    protected static function getJsonLoginFailureResponse(Tinebase_Exception_AreaUnlockFailed | Tinebase_Exception_AreaLocked
                                                          | Tinebase_Exception_Auth_PwdRequired | Tinebase_Exception_Auth_Redirect $e):  \Psr\Http\Message\ResponseInterface
    {
        $response = new \Laminas\Diactoros\Response(headers: [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-store',
        ]);
        $response->getBody()->write(json_encode([
            'jsonrpc' => '2.0',
            'id' => 'fakeid',
            'error' => [
                'code' => -32000,
                'message' => $e->getMessage(),
                'data' => $e->toArray(),
            ],
        ]));
        return $response;
    }

    // TODO FIX ME
    // 3.1.2.6.  Authentication Error Response
    public static function publicAuthorize(): \Psr\Http\Message\ResponseInterface
    {
        if (! SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::ENABLED}) {
            return self::serviceNotEnabled();
        }

        $server = static::getOpenIdConnectServer();

        try {
            // \League\OAuth2\Server\Grant\AuthCodeGrant::canRespondToAuthorizationRequest
            // expects ['response_type'] === 'code' && isset($request->getQueryParams()['client_id'])
            $authRequest = $server->validateAuthorizationRequest(
            /** @var \Psr\Http\Message\ServerRequestInterface $request */
                $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class)
            );
        } catch (League\OAuth2\Server\Exception\OAuthServerException $oauthException) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                Tinebase_Core::getLogger()->notice(
                    __METHOD__ . '::' . __LINE__ . ' ' . $oauthException->getMessage());
            }
            return new \Laminas\Diactoros\Response('php://memory', 401);
        }

        Tinebase_Session::getSessionNamespace()->sso_oid_authRequest = $authRequest;
        if (null !== ($response = static::processJsLoginRequest($request))) {
            return $response;
        }
        unset(Tinebase_Session::getSessionNamespace()->sso_oid_authRequest);

        // TODO FIXME
        // 3.1.2.3.  Authorization Server Authenticates End-User
        // The Authentication Request contains the prompt parameter with the value login. In this case, the Authorization Server MUST reauthenticate the End-User even if the End-User is already authenticated.

        if ($user = Tinebase_Core::getUser()) {
            return static::_answerOIdAuthRequest($authRequest, $user, $server);
        }

        return static::renderLoginPage($authRequest->getClient()->getRelyingPart(), ['url' => $request->getUri()]);
    }

    protected static function _answerOIdAuthRequest(\League\OAuth2\Server\RequestTypes\AuthorizationRequest $authRequest, Tinebase_Model_FullUser $user, ?\League\OAuth2\Server\AuthorizationServer $server = null): \Psr\Http\Message\ResponseInterface
    {
        if (null === $server) {
            $server = static::getOpenIdConnectServer();
        }

        $authRequest->setUser(new SSO_Facade_OAuth2_UserEntity($user));
        $authRequest->setAuthorizationApproved(true);
        $response = $server->completeAuthorizationRequest($authRequest, new \Laminas\Diactoros\Response());

        if (Tinebase_Core::isRegistered(Tinebase_Core::USERCREDENTIALCACHE)) {
            $response = Tinebase_Auth_CredentialCache::getInstance()->getCacheAdapter()
                ->setCache(Tinebase_Core::getUserCredentialCache(), $response);
        }

        Tinebase_Session::getSessionNamespace()->sso_oid_authRequest = null;

        /** @var \Psr\Http\Message\ServerRequestInterface $request */
        $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);
        if ($request->hasHeader('x-requested-with') && $request->getHeader('x-requested-With')[0] === 'XMLHttpRequest') {
            // our login client
            $response->getBody()->write('{ "method":"GET", "url": "' . $response->getHeader('Location')[0] . '" }');
            return $response->withoutHeader('Location');
        } else {
            return $response;
        }
    }

    protected static function getOAuthErrorResponse(string $error, ?string $errorDescription = null): \Psr\Http\Message\ResponseInterface
    {
        $response = new \Laminas\Diactoros\Response(
            status: 400,
            headers: [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-store',
            ]
        );
        $response->getBody()->write(json_encode(array_merge([
            'error' => $error,
        ], $errorDescription === null ? [] : [
            'error_description' => $errorDescription,
        ])));
        return $response;
    }

    public static function publicOAuthDeviceUserLogin(?string $userCode = null): \Psr\Http\Message\ResponseInterface
    {
        if (! SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::ENABLED}) {
            return self::serviceNotEnabled();
        }

        /** @var \Psr\Http\Message\ServerRequestInterface $request */
        $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);
        if (null !== ($response = static::processJsLoginRequest($request))) {
            return $response;
        }

        if (Tinebase_Core::getUser()) {
            $e = (new Tinebase_Exception_Auth_Redirect())->setUrl(str_replace('/sso/oauth2/device/userlogin', '/sso/oauth2/device/user', (string)$request->getUri()));
        } else {
            $e = new Tinebase_Exception_Auth_PwdRequired('Wrong username or password!');
        }

        return static::getJsonLoginFailureResponse($e);
    }

    public static function publicOAuthDeviceUser(?string $userCode = null): \Psr\Http\Message\ResponseInterface
    {
        if (! SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::ENABLED}) {
            return self::serviceNotEnabled();
        }

        /** @var \Psr\Http\Message\ServerRequestInterface $request */
        $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);
        $parsedBody = $request->getParsedBody();

        $deviceCode = null;
        $relyingParty = null;
        if ($userCode) {
            $deviceCode = SSO_Controller_OAuthDeviceCode::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(SSO_Model_OAuthDeviceCode::class, [
                    [TMFA::FIELD => SSO_Model_OAuthDeviceCode::FLD_USER_CODE, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $userCode],
                    [TMFA::FIELD => SSO_Model_OAuthDeviceCode::FLD_VALID_UNTIL, TMFA::OPERATOR => 'after', TMFA::VALUE => Tinebase_DateTime::now()],
                ]))->getFirstRecord();

            if ($deviceCode) {
                $relyingParty = SSO_Controller_RelyingParty::getInstance()->get($deviceCode->{SSO_Model_OAuthDeviceCode::FLD_RELYING_PARTY_ID});
            }
        }

        if ($user = Tinebase_Core::getUser()) {
            $confirmed = $parsedBody['confirmed'] ?? false;
            if (! $confirmed || null === $deviceCode) {
                return static::renderLoginPage(
                    rp: $relyingParty,
                    data: [
                        'isDeviceAuth' => true,
                        'user' => $user->accountDisplayName,
                        'userCode' => $userCode,
                    ] + ($confirmed && !$deviceCode ? ['deviceError' => true] : [])
                );
            }

            $deviceCode->{SSO_Model_OAuthDeviceCode::FLD_APPROVED_BY} = $user->getId();
            // we give the device 3 minutes to poll the authorization
            $deviceCode->{SSO_Model_OAuthDeviceCode::FLD_VALID_UNTIL} = Tinebase_DateTime::now()->addMinute(3);
            SSO_Controller_OAuthDeviceCode::getInstance()->update($deviceCode);

            return static::renderLoginPage(
                rp: $relyingParty,
                data: [
                    'isDeviceAuth' => true,
                    'user' => $user->accountDisplayName,
                    'success' => true,
                ]
            );
        }

        return static::renderLoginPage(
            rp: $relyingParty,
            data: [
                'isDeviceAuth' => true,
                'userCode' => $userCode,
            ],
            url: str_replace('/sso/oauth2/device/user', '/sso/oauth2/device/userlogin', (string)$request->getUri()),
        );
    }

    public static function publicOAuthDeviceAuth(): \Psr\Http\Message\ResponseInterface
    {
        /** @var \Psr\Http\Message\ServerRequestInterface $request */
        $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);
        $requestBody = $request->getParsedBody();

        if (null === ($clientId = ($requestBody['client_id'] ?? null))) {
            return static::getOAuthErrorResponse('invalid_request');
        }

        if (null === ($rp = (new SSO_Facade_OAuth2_ClientRepository(SSO_Config::OAUTH2_GRANTS_DEVICE_CODE))->getClientEntity($clientId)?->getRelyingPart())) {
            return static::getOAuthErrorResponse('invalid_client');
        }

        $deviceCodeCreateFun = fn() => SSO_Controller_OAuthDeviceCode::getInstance()->create(new SSO_Model_OAuthDeviceCode([
            SSO_Model_OAuthDeviceCode::FLD_RELYING_PARTY_ID => $rp->getId(),
            SSO_Model_OAuthDeviceCode::FLD_VALID_UNTIL => Tinebase_DateTime::now()->addMinute(16), // add one more minute than we tell the client
            SSO_Model_OAuthDeviceCode::FLD_USER_CODE => strtoupper(Tinebase_Record_Abstract::generateUID(5) . '-' . Tinebase_Record_Abstract::generateUID(5)),
        ]));

        try {
            $deviceCode = $deviceCodeCreateFun();
        } catch (Zend_Db_Exception) {
            // retry in case of user_code collision
            $deviceCode = $deviceCodeCreateFun();
        }

        $uri = Tinebase_Core::getUrl() . '/sso/oauth2/device/user';
        $response = new \Laminas\Diactoros\Response(
            headers: [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-store',
            ]
        );
        $response->getBody()->write(json_encode([
            'device_code' => $deviceCode->getId(),
            'user_code' => $deviceCode->{SSO_Model_OAuthDeviceCode::FLD_USER_CODE},
            'verification_uri' => $uri,
            'verification_uri_complete' => $uri . '/' . urlencode($deviceCode->{SSO_Model_OAuthDeviceCode::FLD_USER_CODE}),
            'expires_in' => 1800,
            'interval' => 5,
        ]));
        return $response;
    }

    public static function publicToken(): \Psr\Http\Message\ResponseInterface
    {
        if (! SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::ENABLED}) {
            return self::serviceNotEnabled();
        }

        // workaround for quay ... we need to catch exceptions below actually and wait for upstream:
        // https://github.com/thephpleague/oauth2-server/pull/1431
        /** @var \Psr\Http\Message\ServerRequestInterface $request */
        $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);
        if ('badcode' === ($request->getParsedBody()['code'] ?? null)) {
            $response = (new \Laminas\Diactoros\Response())->withStatus(400);
            $response->getBody()->write('{"error":"invalid_grant"}');
            return $response;
        }

        Tinebase_Core::setUser(Tinebase_User::createSystemUser(Tinebase_User::SYSTEM_USER_ANONYMOUS));
        $server = static::getOpenIdConnectServer();

        try {
            $response = $server->respondToAccessTokenRequest(
                Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class),
                new \Laminas\Diactoros\Response()
            );
        } catch (\League\OAuth2\Server\Exception\OAuthServerException $e) {
            $response = (new \Laminas\Diactoros\Response())->withStatus($e->getHttpStatusCode());
            if ($e->getPayload()) {
                $response->getBody()->write(json_encode($e->getPayload()));
            }
            return $response;
        }

        return $response;
    }

    public static function getOAuthIssuer(): string
    {
        return Tinebase_Core::getUrl(Tinebase_Core::GET_URL_NOPATH);
    }

    public static function publicGetWellKnownOpenIdConfiguration(): \Psr\Http\Message\ResponseInterface
    {
        if (! SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::ENABLED}) {
            return self::serviceNotEnabled();
        }

        $response = new \Laminas\Diactoros\Response('php://memory', 200, ['Content-Type' => 'application/json']);
        $serverUrl = rtrim(Tinebase_Core::getUrl(), '/');
        $config = [
            'issuer'                                            => static::getOAuthIssuer(),
            'authorization_endpoint'                            => $serverUrl . '/sso/oauth2/authorize',
            'token_endpoint'                                    => $serverUrl . '/sso/oauth2/token',
            'registration_endpoint'                             => $serverUrl . '/sso/oauth2/register',
            'userinfo_endpoint'                                 => $serverUrl . '/sso/openidconnect/userinfo',
            //'revocation_endpoint'                             => $serverUrl . '/sso/oauth2/revocation',
            'jwks_uri'                                          => $serverUrl . '/sso/oauth2/certs',
            "device_authorization_endpoint"                     => $serverUrl . '/sso/oauth2/device/auth',
            'response_types_supported'                          => [
                'code',
            ],
            'grant_types_supported'                             => [
                'authorization_code',
                'urn:ietf:params:oauth:grant-type:device_code',
            ],
            'token_endpoint_auth_methods_supported'             => ['client_secret_basic', 'private_key_jwt'],
            'token_endpoint_auth_signing_alg_values_supported'  => ['RS256'],
            'subject_types_supported' => [
                'public',
            ],
            'id_token_signing_alg_values_supported' => [
                'RS256',
            ],
        ];

        $response->getBody()->write(json_encode($config));
        return $response;
    }

    public static function webfingerHandler(&$result)
    {
        $result['links'][] = [
            'rel' => SSO_Controller::WEBFINGER_REL,
            'href' => rtrim(Tinebase_Core::getUrl(), '/') . '/sso',
        ];
    }

    /*
     * https://docs.oasis-open.org/security/saml/v2.0/saml-metadata-2.0-os.pdf
     */
    public static function publicSaml2IdPMetaData(): \Psr\Http\Message\ResponseInterface
    {
        if (! SSO_Config::getInstance()->{SSO_Config::SAML2}->{SSO_Config::ENABLED}) {
            return self::serviceNotEnabled();
        }

        $serverUrl = rtrim(Tinebase_Core::getUrl(), '/');

        static::initSAMLServer();
        $idpentityid = SSO_Config::getInstance()->{SSO_Config::SAML2}->{SSO_Config::SAML2_ENTITYID};

        $certInfo = (new \SimpleSAML\Utils\Crypto)->loadPublicKey(\SimpleSAML\Configuration::getInstance(), true);

        $metaArray = [
            'metadata-set'          => 'saml20-idp-remote',
            'entityid'              => $idpentityid,
            'NameIDFormat'          => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
            'SingleSignOnService'   => [[
                'Binding'  => Constants::BINDING_HTTP_REDIRECT,
                'Location' => $serverUrl . '/sso/saml2/redirect/signon',
            ]],
            'SingleLogoutService'   => [[
                'Binding'  => Constants::BINDING_HTTP_REDIRECT,
                'Location' => $serverUrl . '/sso/saml2/redirect/logout',
            ]],
            'certData'              => $certInfo['certData'],
        ];

        $metaBuilder = new \SimpleSAML\Metadata\SAMLBuilder($idpentityid);
        $metaBuilder->addMetadataIdP20($metaArray);
        $metaBuilder->addOrganizationInfo($metaArray);

        $metaxml = $metaBuilder->getEntityDescriptorText();

        // sign the metadata if enabled
        $metaxml = \SimpleSAML\Metadata\Signer::sign($metaxml, [], 'SAML 2 IdP');

        $response = new \Laminas\Diactoros\Response('php://memory', 200,
            ['Content-Type' => 'application/samlmetadata+xml']);
        $response->getBody()->write($metaxml);

        return $response;
    }

    public static function logoutHandler(): array
    {
        $result = [];

        if (static::$_logoutHandlerRecursion) {
            return $result;
        }

        if (SSO_Config::getInstance()->{SSO_Config::SAML2}->{SSO_Config::ENABLED}) {
            static::initSAMLServer();
            $idp = \SimpleSAML\IdP::getById(\SimpleSAML\Configuration::getConfig(), 'saml2:' . SSO_Config::getInstance()->{SSO_Config::SAML2}->{SSO_Config::SAML2_ENTITYID});

            \SimpleSAML\Session::getSessionFromRequest()->doLogout(substr($idp->getId(), 6));
            // @phpstan-ignore-next-line
            if ($logoutMessages = \SimpleSAML\Session::getSessionFromRequest()->getLastLogoutMessages()) {
                $urls = [];
                foreach ($logoutMessages as $binding => $messages) {
                    switch ($binding) {
                        case SSO_Config::SAML2_BINDINGS_POST:
                            $redirect = new \SAML2\HTTPPost();
                            break;
                        case SSO_Config::SAML2_BINDINGS_REDIRECT:
                            $redirect = new \SAML2\HTTPRedirect();
                            break;
                        default:
                            throw new Tinebase_Exception_NotImplemented($binding);
                    }
                    foreach ($messages as $message) {
                        try {
                            $redirect->send($message);
                        } catch (SSO_Facade_SAML_RedirectException $e) {
                            if (!isset($urls[$e->binding])) {
                                $urls[$e->binding] = [];
                            }
                            $urls[$e->binding][] = [
                                'url' => $e->redirectUrl,
                                'data' => $e->data,
                            ];
                        }
                    }
                }

                $result['logoutUrls'] = $urls;
                $result['finalLocation'] = Tinebase_Config::getInstance()->{Tinebase_Config::REDIRECTURL};
            }
        }

        return $result;
    }

    public static function publicSaml2RedirectLogout(): \Psr\Http\Message\ResponseInterface
    {
        if (! SSO_Config::getInstance()->{SSO_Config::SAML2}->{SSO_Config::ENABLED}) {
            return self::serviceNotEnabled();
        }

        try {
            Tinebase_Core::startCoreSession();
        } catch (Zend_Session_Exception $zse) {
            // expire session cookie for client
            Tinebase_Session::expireSessionCookie();
            return new \Laminas\Diactoros\Response($body = 'php://memory', $status = 500);
        }

        static::initSAMLServer();
        $idp = \SimpleSAML\IdP::getById(\SimpleSAML\Configuration::getConfig(), 'saml2:' . SSO_Config::getInstance()->{SSO_Config::SAML2}->{SSO_Config::SAML2_ENTITYID});

        $binding = Binding::getCurrentBinding();
        $message = $binding->receive();

        $issuer = $message->getIssuer();
        if ($issuer === null) {
            /* Without an issuer we have no way to respond to the message. */
            throw new \SimpleSAML\Error\BadRequest('Received message on logout endpoint without issuer.');
        } elseif ($issuer instanceof Issuer) {
            $spEntityId = $issuer->getValue();
            if ($spEntityId === null) {
                /* Without an issuer we have no way to respond to the message. */
                throw new \SimpleSAML\Error\BadRequest('Received message on logout endpoint without issuer.');
            }
        } else {
            $spEntityId = $issuer;
        }
        // @phpstan-ignore-next-line
        \SimpleSAML\Session::getSessionFromRequest()->setSPEntityId($spEntityId);

        $metadata = MetaDataStorageHandler::getMetadataHandler(\SimpleSAML\Configuration::getConfig());
        $idpMetadata = $idp->getConfig();
        $spMetadata = $metadata->getMetaDataConfig($spEntityId, 'saml20-sp-remote');

        \SimpleSAML\Module\saml\Message::validateMessage($spMetadata, $idpMetadata, $message);

        if ($message instanceof \SAML2\LogoutRequest) {
            \SimpleSAML\Session::getSessionFromRequest()->doLogout(substr($idp->getId(), 6));
            // @phpstan-ignore-next-line
            $logoutRequests = \SimpleSAML\Session::getSessionFromRequest()->getLastLogoutMessages();

            if (SSO_Config::getInstance()->{SSO_Config::SAML2}->{SSO_Config::SAML2_TINELOGOUT}) {
                try {
                    static::$_logoutHandlerRecursion = true;
                    (new Tinebase_Frontend_Json)->logout();
                } finally {
                    static::$_logoutHandlerRecursion = false;
                }
            }

            if (is_array($logoutRequests) && !empty($logoutRequests)) {
                // render logout redirect page
                $redirect = new \SAML2\HTTPRedirect();
                $urls = [];
                foreach ($logoutRequests as $requests) {
                    foreach ($requests as $request) {
                        try {
                            $redirect->send($request);
                        } catch (SSO_Facade_SAML_RedirectException $e) {
                            $urls[] = $e->redirectUrl;
                        }
                    }
                }

                $locale = Tinebase_Core::getLocale();

                $jsFiles = ['SSO/js/logoutClient.js'];
                $jsFiles[] = "index.php?method=Tinebase.getJsTranslations&locale={$locale}&app=all";

                return Tinebase_Frontend_Http_SinglePageApplication::getClientHTML($jsFiles, SSO_Config::APP_NAME, context: [
                    'base' => Tinebase_Core::getUrl(Tinebase_Core::GET_URL_PATH),
                    'lang' => $locale,
                    'initialData' => [
                        'logoutUrls' => $urls,
                        'finalLocation' => $spMetadata->getValue('SingleLogoutService')['Location']
                    ]
                ]);
            }
            $response = new \Laminas\Diactoros\Response('php://memory', 302, [
                // @TODO: when logging out from tine the final redirect should be our login-page
                //        but with the test-sp we created a redirect loop here (but saml-test-sp might be borke)
                //'Location' => $spMetadata->getValue('SingleLogoutService')['Location']
                'Location' => Tinebase_Core::getUrl()
            ]);
            return $response;

        } elseif ($message instanceof \SAML2\LogoutResponse) {
            $rp = SSO_Controller_RelyingParty::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                SSO_Model_RelyingParty::class, [
                ['field' => 'name', 'operator' => 'equals', 'value' => $spEntityId]
            ]))->getFirstRecord();
            $locale = Tinebase_Core::getLocale();

            $jsFiles = ['SSO/js/logoutClient.js'];
            $jsFiles[] = "index.php?method=Tinebase.getJsTranslations&locale={$locale}&app=all";

            return Tinebase_Frontend_Http_SinglePageApplication::getClientHTML($jsFiles, SSO_Config::APP_NAME, context: [
                'base' => Tinebase_Core::getUrl(Tinebase_Core::GET_URL_PATH),
                'lang' => $locale,
                'initialData' => [
                    'logoutStatus' => $message->getStatus(),
                    'relyingParty' => [
                        SSO_Model_RelyingParty::FLD_LABEL => $rp ? $rp->{SSO_Model_RelyingParty::FLD_LABEL} : null,
                        SSO_Model_RelyingParty::FLD_DESCRIPTION => $rp ? $rp->{SSO_Model_RelyingParty::FLD_DESCRIPTION} : null,
                        SSO_Model_RelyingParty::FLD_LOGO_LIGHT => $rp ? $rp->{SSO_Model_RelyingParty::FLD_LOGO_LIGHT} : null,
                        SSO_Model_RelyingParty::FLD_LOGO_DARK => $rp?->{SSO_Model_RelyingParty::FLD_LOGO_DARK},
                    ],
                ]
            ]);
        } else {
            throw new \SimpleSAML\Error\BadRequest('Unknown message received on logout endpoint: ' . get_class($message));
        }
    }

    /**
     * http://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-tech-overview-2.0-cd-02.html#5.1.2.SP-Initiated%20SSO:%20%20Redirect/POST%20Bindings|outline
     */
    public static function publicSaml2RedirectRequest(): \Psr\Http\Message\ResponseInterface
    {
        if (! SSO_Config::getInstance()->{SSO_Config::SAML2}->{SSO_Config::ENABLED}) {
            return self::serviceNotEnabled();
        }

        try {
            Tinebase_Core::startCoreSession();
        } catch (Zend_Session_Exception $zse) {
            // expire session cookie for client
            Tinebase_Session::expireSessionCookie();
            return new \Laminas\Diactoros\Response($body = 'php://memory', $status = 500);
        }
        $request = Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class);

        static::initSAMLServer();
        $idp = \SimpleSAML\IdP::getById(\SimpleSAML\Configuration::getConfig(), 'saml2:' . SSO_Config::getInstance()->{SSO_Config::SAML2}->{SSO_Config::SAML2_ENTITYID});
        $simpleSampleIsReallyGreat = new ReflectionProperty(\SimpleSAML\IdP::class, 'authSource');
        $simpleSampleIsReallyGreat->setAccessible(true);
        $newSimple = new SSO_Facade_SAML_AuthSimple('tine20');
        $simpleSampleIsReallyGreat->setValue($idp, $newSimple);

        try {
            $binding = Binding::getCurrentBinding();
        } catch (\SAML2\Exception\Protocol\UnsupportedBindingException $e) {
            return (new \Laminas\Diactoros\Response())->withStatus(405); // Method not allowed
        }
        $authnRequest = $binding->receive();

        if ($authnRequest instanceof AuthnRequest && ($issuer = $authnRequest->getIssuer()) instanceof Issuer &&
                null !== ($spEntityId = $issuer->getValue())) {
            // @phpstan-ignore-next-line
            \SimpleSAML\Session::getSessionFromRequest()->setSPEntityId($spEntityId);
        } else {
            throw new Tinebase_Exception('can\'t resolve request issuer');
        }

        try {
            \SimpleSAML\Module\saml\IdP\SAML2::receiveAuthnRequest(\Symfony\Component\HttpFoundation\Request::createFromGlobals(), $idp);

            throw new Tinebase_Exception('expect simplesaml to throw a resolution');
        } catch (SSO_Facade_SAML_MFAMaskException $e) {
            if ($request->getParsedBody() && array_key_exists('username', $request->getParsedBody())) {
                // render MFA mask
                $response = static::getJsonLoginFailureResponse($e->mfaException);
            } else {
                // reload while in mfa required state
                $response = self::getLoginPage($request);
            }
        } catch (SSO_Facade_SAML_LoginMaskException $e) {
            if ($request->getParsedBody() && array_key_exists('username', $request->getParsedBody())) {
                // this is our js client trying to login
                $response = (new \Laminas\Diactoros\Response())->withHeader('content-type', 'application/json');
                $response->getBody()->write(json_encode([
                    'jsonrpc' => '2.0',
                    'id' => 'fakeid',
                    'result' => (new Tinebase_Frontend_Json())->_getLoginFailedResponse(),
                ]));
            } else {
                $response = self::getLoginPage($request);
            }
        } catch (SSO_Facade_SAML_RedirectException $e) {
            $response = new \Laminas\Diactoros\Response();
            $response->getBody()->write(\Tinebase_Helper::createFormHTML($e->redirectUrl, $e->data));
        }

        if (Tinebase_Core::isRegistered(Tinebase_Core::USERCREDENTIALCACHE)) {
            $response = Tinebase_Auth_CredentialCache::getInstance()->getCacheAdapter()
                ->setCache(Tinebase_Core::getUserCredentialCache(), $response);
        }

        return $response;
    }
    
    protected static function getLoginPage($request)
    {
        $binding = Binding::getCurrentBinding();
        $samlRequest = $binding->receive();
        /** @var SSO_Model_RelyingParty $rp */
        $rp = SSO_Controller_RelyingParty::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            SSO_Model_RelyingParty::class, [
            ['field' => 'name', 'operator' => 'equals', 'value' => $samlRequest->getIssuer()->getValue()]
        ]))->getFirstRecord();

        /** @var \Psr\Http\Message\ServerRequestInterface $request */
        if ($request->getQueryParams()['SAMLRequest'] ?? false) {
            $data = $request->getQueryParams();
        } else {
            $data = $request->getParsedBody();
        }
        $data['SAMLRequest'] = base64_encode(gzinflate($decode = base64_decode($data['SAMLRequest'])) ?: $decode);

        return static::renderLoginPage($rp, $data);
    }

    protected static function renderLoginPage(?SSO_Model_RelyingParty $rp = null, array $data = [], ?string $url = null, ?Tinebase_Exception_SystemGeneric $mfaEx = null)
    {
        $locale = Tinebase_Core::getLocale();

        $jsFiles = ['SSO/js/login.js'];
        $jsFiles[] = "index.php?method=Tinebase.getJsTranslations&locale={$locale}&app=all";

        $initialData = [
            'sso' => $data,
            'relyingParty' => $rp ? [
                SSO_Model_RelyingParty::FLD_LABEL => $rp->{SSO_Model_RelyingParty::FLD_LABEL},
                SSO_Model_RelyingParty::FLD_DESCRIPTION => $rp->{SSO_Model_RelyingParty::FLD_DESCRIPTION},
                SSO_Model_RelyingParty::FLD_LOGO_LIGHT => $rp->{SSO_Model_RelyingParty::FLD_LOGO_LIGHT},
                SSO_Model_RelyingParty::FLD_LOGO_DARK => $rp->{SSO_Model_RelyingParty::FLD_LOGO_DARK},
            ] : [],
        ];
        if ($mfaEx) {
            $initialData['mfa'] = [
                'message' => $mfaEx->getMessage(),
                'data' => $mfaEx->toArray(),
            ];
        }
        if ($url) {
            $initialData['url'] = $url;
        }
        $data = [
            'base' => Tinebase_Core::getUrl(Tinebase_Core::GET_URL_PATH),
            'lang' => $locale,
            'initialData' => $initialData,
        ];

        return Tinebase_Frontend_Http_SinglePageApplication::getClientHTML($jsFiles, SSO_Config::APP_NAME, context: $data);
    }
    
    public static function getOpenIdConnectServer(): SSO_Facade_OpenIdConnect_AuthorizationServer
    {
        if (! SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::ENABLED}) {
            throw new Tinebase_Exception('OIDC not enabled');
        }

        return new SSO_Facade_OpenIdConnect_AuthorizationServer();
    }

    protected static function initSAMLServer()
    {
        (new ReflectionProperty(\SimpleSAML\Session::class, 'instance'))->setAccessible(true);
        (new ReflectionClass(\SimpleSAML\Session::class))->setStaticPropertyValue('instance', new SSO_Facade_SAML_Session());

        $saml2Config = SSO_Config::getInstance()->{SSO_Config::SAML2};

        \SAML2\Compat\ContainerSingleton::setContainer(new SSO_Facade_SAML_Container);
        \SimpleSAML\SAML2\Compat\ContainerSingleton::setContainer(new SSO_Facade_SAML_SimpleSamlContainer);
        \SimpleSAML\Configuration::setPreLoadedConfig(new \SimpleSAML\Configuration([
            'metadata.sources' => [['type' => SSO_Facade_SAML_MetaDataStorage::class]],
            'metadata.sign.enable' => true,
            'metadata.sign.privatekey' => $saml2Config->{SSO_Config::SAML2_KEYS}[0]['privatekey'],
            'metadata.sign.certificate' => $saml2Config->{SSO_Config::SAML2_KEYS}[0]['certificate'],
            'certificate' => $saml2Config->{SSO_Config::SAML2_KEYS}[0]['certificate'],
            'enable.saml20-idp' => true,
            'logging.level' => -1,
        ], 'tine20'));
        \SimpleSAML\Configuration::setPreLoadedConfig(new \SimpleSAML\Configuration([
            'tine20' => [SSO_Facade_SAML_AuthSourceFactory::class]
        ], 'authsources.php'), 'authsources.php');
    }

    public static function passwordLessLogin(string $username): bool
    {
        try {
            if (Tinebase_Application::ENABLED !== Tinebase_Application::getInstance()
                    ->getApplicationByName(SSO_Config::APP_NAME)->status) {
                return false;
            }
        } catch (Tinebase_Exception_NotFound) {
            return false;
        }

        $idp = null;
        switch (SSO_Config::getInstance()->{SSO_Config::PWD_LESS_LOGIN}) {
            case SSO_Config::PWD_LESS_LOGIN_BOTH:
            case SSO_Config::PWD_LESS_LOGIN_ONLY_LOCAL:
                $account = null;
                try {
                    $account = Tinebase_User::getInstance()->getFullUserByLoginName($username);
                } catch (Tinebase_Exception_NotFound) {}
                if ($account?->openid && ($pos = strpos($account->openid, ':'))) {
                    try {
                        $idp = SSO_Controller_ExternalIdp::getInstance()->get(substr($account->openid, 0, $pos));
                        break;
                    } catch(Tinebase_Exception_NotFound $tenf) {
                        $tenf->setLogLevelMethod('notice');
                        $tenf->setLogToSentry(false);
                        Tinebase_Exception::log($tenf);
                    }
                }

                if (SSO_Config::PWD_LESS_LOGIN_ONLY_LOCAL === SSO_Config::getInstance()->{SSO_Config::PWD_LESS_LOGIN}) {
                    break;
                }
            case SSO_Config::PWD_LESS_LOGIN_ONLY_PROXY:
                // search for idp....
                // eventually we only have one, fix idp?
                // for now @ notation
                if (false === ($pos = strrpos($username, '@')) || '' === ($idpDomain = substr($username, $pos + 1))) {
                    return false;
                }

                $idp = SSO_Controller_ExternalIdp::getInstance()->search(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(SSO_Model_ExternalIdp::class, [
                        [TMFA::FIELD => SSO_Model_ExternalIdp::FLD_DOMAINS, TMFA::OPERATOR => 'definedBy', TMFA::VALUE => [
                            // this filter is case INsensitive, so no worries here
                            [TMFA::FIELD => SSO_Model_ExIdpDomain::FLD_DOMAIN, TMFA::OPERATOR => TMFA::OP_EQUALS, TMFA::VALUE => $idpDomain],
                        ]],
                ]))->getFirstRecord();
                break;
        }

        return $idp ? static::startExternalIdpAuthProcess($idp): false;
    }

    public static function startExternalIdpAuthProcess(SSO_Model_ExternalIdp $idp): bool
    {
        Tinebase_Session::getSessionNamespace()->sso_idp = $idp->getId();
        return $idp->{SSO_Model_ExternalIdp::FLD_CONFIG}->initAuthProcess();
    }

    public static function publicOidAuthResponseErrorRedirect(?\League\OAuth2\Server\RequestTypes\AuthorizationRequest $authRequest): \Psr\Http\Message\ResponseInterface
    {
        Tinebase_Session::destroyAndRemoveCookie();
        if ($authRequest) {
            return new \Laminas\Diactoros\Response('php://memory', 302, ['Location' => $authRequest->getRedirectUri()]);
        }
        return (new Tinebase_Frontend_Http())->mainScreen();
    }

    public static function publicOidAuthResponse(): \Psr\Http\Message\ResponseInterface
    {
        /** @var \League\OAuth2\Server\RequestTypes\AuthorizationRequest $authRequest */
        $authRequest = Tinebase_Session::getSessionNamespace()->sso_oid_authRequest;

        if (empty($ssoIdp = Tinebase_Session::getSessionNamespace()->sso_idp)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()
                ->notice(__METHOD__ . '::' . __LINE__ . ' session does not have a sso_idp');
            return static::publicOidAuthResponseErrorRedirect($authRequest);
        }
        try {
            $ssoIdp = SSO_Controller_ExternalIdp::getInstance()->get($ssoIdp);
            /** @var SSO_Model_ExternalIdp $ssoIdp */
        } catch (Tinebase_Exception) {
            return static::publicOidAuthResponseErrorRedirect($authRequest);
        }

        /** @var Tinebase_Auth_OpenIdConnect $oidc */
        $oidc = Tinebase_Auth_Factory::factory(Tinebase_Auth_OpenIdConnect::TYPE, [
            Tinebase_Auth_OpenIdConnect::IDP_CONFIG => $ssoIdp->{SSO_Model_ExternalIdp::FLD_CONFIG},
        ]);
        $client = $oidc->_getClient();
        $redirectUrl = rtrim(Tinebase_Core::getUrl(), '/') . '/sso/oid/auth/response';
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Set provider redirect url: ' . $redirectUrl);
        $client->setRedirectURL($redirectUrl);
        $client->addScope(array_unique(array_merge(['openid', 'email', 'profile'],
            $ssoIdp->{SSO_Model_ExternalIdp::FLD_REQUIRED_GROUP_CLAIMS} || $ssoIdp->{SSO_Model_ExternalIdp::FLD_CREATE_GROUPS}
                || $ssoIdp->{SSO_Model_ExternalIdp::FLD_ASSIGN_GROUPS} ? [$ssoIdp->{SSO_Model_ExternalIdp::FLD_GROUPS_CLAIM_NAME} ?: 'groups'] : []
        )));

        try {
            $oidcAuthResult = $client->authenticate();
            $groupsClaim = $client->getVerifiedClaims($ssoIdp->{SSO_Model_ExternalIdp::FLD_GROUPS_CLAIM_NAME} ?: 'groups');
            if ($ssoIdp->{SSO_Model_ExternalIdp::FLD_REQUIRED_GROUP_CLAIMS}) {
                if (null === $groupsClaim || !array_intersect($ssoIdp->{SSO_Model_ExternalIdp::FLD_REQUIRED_GROUP_CLAIMS}, (array)$groupsClaim)) {
                    throw new OpenIDConnectClientException(($ssoIdp->{SSO_Model_ExternalIdp::FLD_GROUPS_CLAIM_NAME} ?: 'groups') . ' doesn\'t contain one of ' . print_r($ssoIdp->{SSO_Model_ExternalIdp::FLD_REQUIRED_GROUP_CLAIMS}, true));
                }
            }
        } catch (OpenIDConnectClientException $e) {
            $e = new Tinebase_Exception($e->getMessage(), previous: $e);
            $e->setLogToSentry(false);
            $e->setLogLevelMethod('info');
            Tinebase_Exception::log($e);
            return static::publicOidAuthResponseErrorRedirect($authRequest);
        }
        if ($oidcAuthResult) {
            $data = $client->requestUserInfo();

            if (!isset($data->sub)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()
                    ->notice(__METHOD__ . '::' . __LINE__ . ' external idp did not send us an sub to work with');
                return static::publicOidAuthResponseErrorRedirect($authRequest);
            }
            $openid = $ssoIdp->getId() . ':' . $data->sub;
            $account = null;
            try {
                try {
                    $account = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('openid', $openid, Tinebase_Model_FullUser::class);
                } catch (Tinebase_Exception_NotFound) {
                    $account = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('openid', $openid, Tinebase_Model_FullUser::class, true);
                    $oldValue = Admin_Controller_User::getInstance()->doRightChecks(false);
                    $oldGroupValue = Admin_Controller_Group::getInstance()->doRightChecks(false);
                    try {
                        Tinebase_Core::setUser(Tinebase_User::createSystemUser(Tinebase_User::SYSTEM_USER_ANONYMOUS));
                        $account = Admin_Controller_User::getInstance()->undelete($account);
                    } finally {
                        Admin_Controller_User::getInstance()->doRightChecks($oldValue);
                        Admin_Controller_Group::getInstance()->doRightChecks($oldGroupValue);
                    }
                }
            } catch (Tinebase_Exception_NotFound) {

                $loginNameClaim = $ssoIdp->{SSO_Model_ExternalIdp::FLD_USERNAME_CLAIM};
                if (!isset($data->email) || !($pos = strpos($data->email, '@'))) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                        Tinebase_Core::getLogger()
                            ->notice(__METHOD__ . '::' . __LINE__ . ' external idp did not send us an email address to work with' . print_r($data, true));
                    }
                    return static::publicOidAuthResponseErrorRedirect($authRequest);
                }
                if ('email:local_part' === $loginNameClaim) {
                    $loginName = substr($data->email, 0, $pos);
                } else {
                    if (!isset($data->{$loginNameClaim}) || strlen((string)$data->{$loginNameClaim}) === 0) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                            Tinebase_Core::getLogger()
                                ->notice(__METHOD__ . '::' . __LINE__ . ' external idp did not send us an ' . $loginNameClaim . ' to work with' . print_r($data, true));
                        }
                        return static::publicOidAuthResponseErrorRedirect($authRequest);
                    }
                    $loginName = $data->{$loginNameClaim};
                }

                if ($ssoIdp->{SSO_Model_ExternalIdp::FLD_ALLOW_EXISTING_LOCAL_ACCOUNT}) {
                    try {
                        $account = Tinebase_User::getInstance()->getFullUserByLoginName($loginName);
                        if (empty($account->openid) || $ssoIdp->{SSO_Model_ExternalIdp::FLD_ALLOW_REASSIGN_LOCAL_ACCOUNT}) {
                            $account->openid = $openid;
                            Tinebase_Core::setUser(Tinebase_User::createSystemUser(Tinebase_User::SYSTEM_USER_ANONYMOUS));
                            Tinebase_User::getInstance()->updateUserInSqlBackend($account);
                        } else {
                            $account = null;
                        }
                    } catch (Tinebase_Exception_NotFound) {}
                }

                if (null === $account && $ssoIdp->{SSO_Model_ExternalIdp::FLD_ALLOW_CREATE_LOCAL_ACCOUNT}) {
                    $oldValue = Admin_Controller_User::getInstance()->doRightChecks(false);
                    $oldGroupValue = Admin_Controller_Group::getInstance()->doRightChecks(false);
                    try {
                        Tinebase_Core::setUser(Tinebase_User::createSystemUser(Tinebase_User::SYSTEM_USER_ANONYMOUS));

                        $pw = Tinebase_User_PasswordPolicy::generatePolicyConformPassword();
                        $account = new Tinebase_Model_FullUser([
                            'accountLoginName' => $ssoIdp->{SSO_Model_ExternalIdp::FLD_ACCOUNT_PREFIX} . $loginName,
                            'accountEmailAddress' => $data->email,
                            'accountStatus' => 'enabled',
                            'accountExpires' => NULL,
                            'openid' => $ssoIdp->getId() . ':' . $data->sub,
                            'accountLastName' => $data->name ?? $loginName,
                            'accountPrimaryGroup' => $ssoIdp->{SSO_Model_ExternalIdp::FLD_PRIMARY_GROUP_NEW_ACCOUNT} ?
                                $ssoIdp->getIdFromProperty(SSO_Model_ExternalIdp::FLD_PRIMARY_GROUP_NEW_ACCOUNT) :
                                Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
                            'groups' => is_array($ssoIdp->{SSO_Model_ExternalIdp::FLD_GROUPS_NEW_ACCOUNT}) ? $ssoIdp->{SSO_Model_ExternalIdp::FLD_GROUPS_NEW_ACCOUNT} : [],
                            'xprops' => [Tinebase_Model_FullUser::XPROP_HAS_RANDOM_PWD => true],
                        ]);
                        $account->applyTwigTemplates();
                        if ($ssoIdp->{SSO_Model_ExternalIdp::FLD_ACCOUNT_DISPLAY_NAME_PREFIX}) {
                            $account->accountDisplayName = $ssoIdp->{SSO_Model_ExternalIdp::FLD_ACCOUNT_DISPLAY_NAME_PREFIX} . $account->accountDisplayName;
                        }
                        if ($ssoIdp->{SSO_Model_ExternalIdp::FLD_ADDRESSBOOK}) {
                            $account->container_id = $ssoIdp->{SSO_Model_ExternalIdp::FLD_ADDRESSBOOK};
                        }
                        $account = Admin_Controller_User::getInstance()->create($account, $pw, $pw);
                    } catch (Tinebase_Exception $e) {
                        $e->setLogLevelMethod('notice');
                        $e->setLogToSentry(false);
                        Tinebase_Exception::log($e);
                        return static::publicOidAuthResponseErrorRedirect($authRequest);
                    } finally {
                        Admin_Controller_User::getInstance()->doRightChecks($oldValue);
                        Admin_Controller_Group::getInstance()->doRightChecks($oldGroupValue);
                        Tinebase_Core::unsetUser();
                    }
                }
            }

            if (null === $account) {
                return static::publicOidAuthResponseErrorRedirect($authRequest);
            }

            if (is_array($ssoIdp->{SSO_Model_ExternalIdp::FLD_DENY_GROUPS})) {
                $memberships = Tinebase_Group::getInstance()->getGroupMemberships($account->getId());
                if (array_intersect($memberships, $ssoIdp->{SSO_Model_ExternalIdp::FLD_DENY_GROUPS})) {
                    return static::publicOidAuthResponseErrorRedirect($authRequest);
                }
            }

            if (is_array($ssoIdp->{SSO_Model_ExternalIdp::FLD_DENY_ROLES})) {
                $memberships = Tinebase_Role::getInstance()->getRoleMemberships($account->getId());
                if (array_intersect($memberships, $ssoIdp->{SSO_Model_ExternalIdp::FLD_DENY_ROLES})) {
                    return static::publicOidAuthResponseErrorRedirect($authRequest);
                }
            }

            /** @var Tinebase_Group_Sql $groupCtrl */
            $groupCtrl = Tinebase_Group::getInstance();
            $admGroupCtrl = Admin_Controller_Group::getInstance();
            $oldRightsCheck = $admGroupCtrl->doRightChecks(false);
            $raii = new Tinebase_RAII(fn() => $admGroupCtrl->doRightChecks($oldRightsCheck));
            if ($ssoIdp->{SSO_Model_ExternalIdp::FLD_CREATE_GROUPS} && $groupsClaim) {
                foreach ((array)$groupsClaim as $groupName) {
                    $groupName = $ssoIdp->{SSO_Model_ExternalIdp::FLD_GROUP_PREFIX} . $groupName;
                    try {
                        $groupCtrl->getGroupByName($groupName);
                    } catch (Tinebase_Exception_Record_NotDefined) {
                        $groupToBeCreated = new Tinebase_Model_Group([
                            'name' => $groupName,
                        ], true);
                        if ($ssoIdp->{SSO_Model_ExternalIdp::FLD_ADDRESSBOOK}) {
                            $groupToBeCreated->container_id = $ssoIdp->{SSO_Model_ExternalIdp::FLD_ADDRESSBOOK};
                        }
                        $admGroupCtrl->create($groupToBeCreated);
                    }
                }
            }
            unset($raii);

            if ($ssoIdp->{SSO_Model_ExternalIdp::FLD_ASSIGN_GROUPS} && $groupsClaim) {
                $groupMemberships = $groupCtrl->getGroupMemberships($account->getId());
                foreach ((array)$groupsClaim as $groupName) {
                    $groupName = $ssoIdp->{SSO_Model_ExternalIdp::FLD_GROUP_PREFIX} . $groupName;
                    try {
                        $group = $groupCtrl->getGroupByName($groupName);

                        if (!in_array($group->getId(), $groupMemberships)) {
                            $groupCtrl->addGroupMember($group->getId(), $account->getId());
                        }
                    } catch (Tinebase_Exception_Record_NotDefined) {}
                }
            }

            Tinebase_Auth::destroyInstance();
            Tinebase_Auth::setBackendType('OidcMock');

            try {
                Tinebase_Controller::getInstance()->forceUnlockLoginArea();
                Tinebase_Core::unsetUser();
                if (!Tinebase_Controller::getInstance()->login($account->accountLoginName, '',
                        static::getLoginFakeRequest('/sso/oid/auth/response'),
                        self::OIDC_AUTH_CLIENT_REQUEST_TYPE)) {
                    Tinebase_Exception::log(new Tinebase_Exception('login did not work unexpectedly'));
                    return static::publicOidAuthResponseErrorRedirect($authRequest);
                }
            } finally {
                Tinebase_Auth::destroyInstance();
                Tinebase_Auth::setBackendType(null);
            }

            Tinebase_Session::getSessionNamespace()->{SSO_Model_ExternalIdp::SESSION_KEY} = $ssoIdp->toUserArray();

            if (Tinebase_Session::getSessionNamespace()->sso_oid_authRequest instanceof \League\OAuth2\Server\RequestTypes\AuthorizationRequest) {
                return static::_answerOIdAuthRequest(Tinebase_Session::getSessionNamespace()->sso_oid_authRequest, $account);
            }
            return new \Laminas\Diactoros\Response('php://memory', 302, ['Location' => Tinebase_Core::getUrl()]);
        } else if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()
                ->info(__METHOD__ . '::' . __LINE__ . ' OIDC auth failure');
        }

        return static::publicOidAuthResponseErrorRedirect($authRequest);
    }

    public function generateKey(string $path, string $name): void
    {
        $path = rtrim($path, '/') . '/';
        if (!is_dir($path)) {
            throw new Tinebase_Exception($path . ' does not exist');
        }
        $fileNames = [
            'keyFile' => $path . $name . '_key.pem',
            'pemCertFile' => $path . $name . '_cert.pem',
            'crtCertFile' => $path . $name . '_cert.crt',
            'jwkCertFile' => $path . $name . '_cert.jwk',
        ];
        foreach ($fileNames as $fileName) {
            if (is_file($fileName)) {
                throw new Tinebase_Exception($fileName . ' already exists');
            }
        }

        $cmd = 'openssl req -x509 -newkey rsa:4096 -keyout ' . escapeshellarg($fileNames['keyFile']) . ' -out ' . escapeshellarg($fileNames['pemCertFile']) . ' -days 730 -nodes -subj \'/CN=tine-sso\' 2>&1';
        exec($cmd, $output, $returnCode);
        if (0 !== $returnCode) {
            @unlink($fileNames['keyFile']);
            @unlink($fileNames['pemCertFile']);
            throw new Tinebase_Exception($cmd . PHP_EOL . 'failed with code ' . $returnCode . ' and ' . join(PHP_EOL, $output));
        }

        $cmd = 'openssl pkey -in ' . escapeshellarg($fileNames['keyFile']) . ' -out ' . escapeshellarg($fileNames['crtCertFile']) . ' -pubout 2>&1';
        exec($cmd, $output, $returnCode);
        if (0 !== $returnCode) {
            @unlink($fileNames['keyFile']);
            @unlink($fileNames['pemCertFile']);
            @unlink($fileNames['crtCertFile']);
            throw new Tinebase_Exception($cmd . PHP_EOL . 'failed with code ' . $returnCode . ' and ' . join(PHP_EOL, $output));
        }

        $options = [
            'use' => 'sig',
            'alg' => 'RS256',
            'kid' => Tinebase_Record_Abstract::generateUID(),
        ];

        $keyFactory = new KeyFactory();
        if (false === file_put_contents($fileNames['jwkCertFile'], json_encode($keyFactory->createFromPem(file_get_contents($fileNames['crtCertFile']), $options)))) {
            @unlink($fileNames['keyFile']);
            @unlink($fileNames['pemCertFile']);
            @unlink($fileNames['crtCertFile']);
            @unlink($fileNames['jwkCertFile']);
            throw new Tinebase_Exception('failed to write file '.  $fileNames['jwkCertFile']);
        }

        foreach ($fileNames as $fileName) {
            $cmd = 'chmod 640 ' . escapeshellarg($fileName) . ' 2>&1';
            exec($cmd, $output, $returnCode);
            if (0 !== $returnCode) {
                @unlink($fileNames['keyFile']);
                @unlink($fileNames['pemCertFile']);
                @unlink($fileNames['crtCertFile']);
                @unlink($fileNames['jwkCertFile']);
                throw new Tinebase_Exception($cmd . PHP_EOL . 'failed with code ' . $returnCode . ' and ' . join(PHP_EOL, $output));
            }
        }
    }

    public function keyRotate(): bool
    {
        if (!SSO_Config::getInstance()->{SSO_Config::KEY_ROTATION_ENABLED}) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()
                    ->info(__METHOD__ . '::' . __LINE__ . ' key rotation disabled.');
            return true;
        }

        if (isset(SSO_Config::getInstance()->getConfigFileData()[SSO_Config::APP_NAME][SSO_Config::OAUTH2][SSO_Config::OAUTH2_KEYS]) ||
                isset(SSO_Config::getInstance()->getConfigFileData()[SSO_Config::APP_NAME][SSO_Config::SAML2][SSO_Config::SAML2_KEYS])) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' keys set in config file, can\'t rotate. Please remove them from file config.');
            return false;
        }

        if (null === ($basePath = $this->getKeyBaseDir())) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' can\'t find key basedir.');
            return false;
        }

        if ($existingKey = (SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::OAUTH2_KEYS}[0]['privatekey'] ?? null)) {
            if (preg_match('/^\d\d\d\d-\d\d-\d\d$/', ($date = basename(dirname($existingKey))))) {
                $date = new Tinebase_DateTime($date);
                if ($date->isLater(Tinebase_DateTime::today()->subMonth(6))) {
                    // key still valid, nothing to do
                    $this->retireKeys();
                    return true;
                }
            }
        }

        if (null === ($basePath = $this->_makeDatedDir($basePath))) {
            return false;
        }
        $cleanDatedDir = fn() => @exec('rm -rf ' . escapeshellarg($basePath) . ' 2>&1');
        try {
            $this->generateKey($basePath, $keyName = 'tine-sso-' . basename($basePath));
        } catch (Tinebase_Exception $e) {
            $e->setLogToSentry(false);
            Tinebase_Exception::log($e);
            $cleanDatedDir();
            return false;
        }

        if (!$this->addKeysToConfig($basePath, $keyName)) {
            $cleanDatedDir();
            return false;
        }

        $this->retireKeys();
        return true;
    }

    public function addKeysToConfig(string $path, string $keyName): bool
    {
        if (!str_ends_with($path, '/')) {
            $path .= '/';
        }

        if (!($cert = file_get_contents($path . $keyName . '_cert.jwk'))) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' can\'t read cert file ' . $path . $keyName . '_cert.jwk');
            return false;
        }
        if (!is_array($certData = json_decode($cert, true))) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' can\'t corrupted cert file ' . $path . $keyName . '_cert.jwk');
            return false;
        }
        $oauth2Keys = SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::OAUTH2_KEYS};
        array_unshift($oauth2Keys, array_merge($certData, [
            'use' => 'sig',
            'alg' => 'RS256',
            'kid' => $keyName,
            'publickey' => $path . $keyName . '_cert.crt',
            'privatekey' => $path . $keyName . '_key.pem',
        ]));
        SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::OAUTH2_KEYS} = $oauth2Keys;

        $saml2Keys = SSO_Config::getInstance()->{SSO_Config::SAML2}->{SSO_Config::SAML2_KEYS};
        array_unshift($saml2Keys, [
            'privatekey' => $path . $keyName . '_key.pem',
            'certificate' => $path . $keyName . '_cert.pem',
        ]);
        SSO_Config::getInstance()->{SSO_Config::SAML2}->{SSO_Config::SAML2_KEYS} = $saml2Keys;

        return true;
    }

    protected function retireKeys(): void
    {
        $filterKeys = function(array $keys) {
            $validKey = false;
            $changed = false;
            foreach($keys as $index => $key) {
                if (!isset($key['privatekey'])) continue;
                $date = basename(dirname($key['privatekey']));
                if (!preg_match('/^\d\d\d\d-\d\d-\d\d$/', $date)) {
                    $changed = true;
                    unset($keys[$index]);
                } else {
                    if ((new Tinebase_DateTime($date))->isLater(Tinebase_DateTime::today()->subMonth(6)->subDay(2))) {
                        $validKey = true;
                    } else {
                        $changed = true;
                        unset($keys[$index]);
                    }
                }
            }
            if ($validKey && $changed) {
                return $keys;
            }
            return null;
        };

        $saml2Keys = SSO_Config::getInstance()->{SSO_Config::SAML2}->{SSO_Config::SAML2_KEYS};
        if (null !== ($saml2Keys = $filterKeys($saml2Keys))) {
            SSO_Config::getInstance()->{SSO_Config::SAML2}->{SSO_Config::SAML2_KEYS} = $saml2Keys;
        }

        $oauth2Keys = SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::OAUTH2_KEYS};
        if (null !== ($saml2Keys = $filterKeys($oauth2Keys))) {
            SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::OAUTH2_KEYS} = $oauth2Keys;
        }
    }

    protected function _makeDatedDir($path): ?string
    {
        $path .= '/' . Tinebase_DateTime::today()->format('Y-m-d');
        if (!mkdir($path)) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' can\'t mkdir ' . $path);
            return null;
        }
        return $path . '/';
    }

    public function getKeyBaseDir(): ?string
    {
        $path = rtrim(Tinebase_Config::getInstance()->{Tinebase_Config::FILESDIR}, '/') . '/.ssokeys';
        if (!is_dir($path)) {
            if (!mkdir($path)) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' can\'t mkdir ' . $path);
                return null;
            }
        }
        return $path;
    }
}

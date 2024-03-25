<?php declare(strict_types=1);
/**
 * MAIN controller for SSO application
 *
 * @package     SSO
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_Model_Filter_Abstract as TMFA;

use League\OAuth2\Server\AuthorizationValidators\BearerTokenValidator;
use SAML2\AuthnRequest;
use SAML2\Binding;
use SAML2\Constants;
use SAML2\XML\saml\Issuer;
use SimpleSAML\Logger;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Stats;

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

    public static function addFastRoutes(
        /** @noinspection PhpUnusedParameterInspection */
        \FastRoute\RouteCollector $r
    ) {
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

        /*$keys = [
            'keys' => [
                [
                    'use' => 'sig',
                    'kty' => 'RSA',
                    'alg' => 'RS256',
                    'kid' => 'tempkid',
                    'e'   => 'AQAB',
                    'n'   => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArXkViV0Cz0cwmGAcnP1U9z2K5utziToHUBHnWanV1HvLym8xsvlpjXVtqPXdnBQuHIXxDcDUfL7SWlKrrdkZTDZn21YJQvar3nS0Hwl1fpKd/CK1uWukmkfiOnuew6cwgskAbr4Oc3QVREEGBNTnpqiB0rLwlUqB4Pey/nGXCe2h8bm9NwNp/T9IlZhrwhfzMDhUSLo7FA6v9ShWVSLBDwvwXodLbq9DVX9OomZCPAapFjljxveCcSoKy1oQNUMDKdE7t1MEh5V4FAP2Ezhvexrq3cyLZtImypL15wgWujY2CXlDi9NkKAL7LyeevrQ2SbRAmKzTmCiZ7OKH4OpZWwIDAQAB',
                ]
            ]
        ];*/
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
        (new Idaas\OpenID\UserInfo(
            new SSO_Facade_OpenIdConnect_UserRepository(),
            $tokenRepo = new SSO_Facade_OAuth2_AccessTokenRepository(),
            new \League\OAuth2\Server\ResourceServer(
                $tokenRepo,
                SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::OAUTH2_KEYS}[0]['publickey'],
                new BearerTokenValidator($tokenRepo)
            ),
            new SSO_Facade_OpenIdConnect_ClaimRepository()
        ))->respondToUserInfoRequest($request, $response = new \Laminas\Diactoros\Response());

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
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' ' . $oauthException->getMessage());
            return new \Laminas\Diactoros\Response('php://memory', 401);
        }

        if (isset($request->getParsedBody()['username'])) {
            try {
                if (!empty($request->getParsedBody()['password'] ?? null)) {
                    Tinebase_Controller::getInstance()->forceUnlockLoginArea();
                    try {
                        Tinebase_Controller::getInstance()->login($request->getParsedBody()['username'],
                            $request->getParsedBody()['password'],
                            static::getLoginFakeRequest('/sso/oauth2/authorize'),
                            self::OIDC_AUTH_REQUEST_TYPE);
                    } finally {
                        Tinebase_Controller::getInstance()->forceUnlockLoginArea(false);
                    }
                } else {
                    Tinebase_Session::getSessionNamespace()->sso_oid_authRequest = $authRequest;
                    static::passwordLessLogin($request->getParsedBody()['username']);
                }
            } catch (Tinebase_Exception_AreaUnlockFailed | Tinebase_Exception_AreaLocked
                    | Tinebase_Exception_Auth_PwdRequired | Tinebase_Exception_Auth_Redirect $tea) { // 630 + 631 + 650 + 651
                $response = (new \Laminas\Diactoros\Response())->withHeader('content-type', 'application/json');
                $response->getBody()->write(json_encode([
                    'jsonrpc' => '2.0',
                    'id' => 'fakeid',
                    'error' => [
                        'code' => -32000,
                        'message' => $tea->getMessage(),
                        'data' => $tea->toArray(),
                    ],
                ]));
                return $response;
            }
        }

        // TODO FIXME
        // 3.1.2.3.  Authorization Server Authenticates End-User
        // The Authentication Request contains the prompt parameter with the value login. In this case, the Authorization Server MUST reauthenticate the End-User even if the End-User is already authenticated.

        if ($user = Tinebase_Core::getUser()) {
            $authRequest->setUser(new SSO_Facade_OAuth2_UserEntity($user));
            $authRequest->setAuthorizationApproved(true);
            $response = $server->completeAuthorizationRequest($authRequest, new \Laminas\Diactoros\Response());

            if (Tinebase_Core::isRegistered(Tinebase_Core::USERCREDENTIALCACHE)) {
                $response = Tinebase_Auth_CredentialCache::getInstance()->getCacheAdapter()
                    ->setCache(Tinebase_Core::getUserCredentialCache(), $response);
            }
            
            if ($request->hasHeader('x-requested-with') && $request->getHeader('x-requested-With')[0] === 'XMLHttpRequest') {
                // our login client
                $response->getBody()->write('{ "method":"GET", "url": "' . $response->getHeader('Location')[0] . '" }');
                return $response->withoutHeader('Location');
            } else {
                return $response;
            }

        }

        return static::renderLoginPage($authRequest->getClient()->getRelyingPart(), ['url' => $request->getUri()]);
    }

    public static function publicToken(): \Psr\Http\Message\ResponseInterface
    {
        if (! SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::ENABLED}) {
            return self::serviceNotEnabled();
        }

        Tinebase_Core::set(Tinebase_Core::USER, Tinebase_User::getInstance()
            ->getFullUserByLoginName(Tinebase_User::SYSTEM_USER_ANONYMOUS));
        $server = static::getOpenIdConnectServer();

        $response = $server->respondToAccessTokenRequest(
            Tinebase_Core::getContainer()->get(\Psr\Http\Message\RequestInterface::class),
            new \Laminas\Diactoros\Response()
        );

        return $response;
    }

    protected static function getOAuthIssuer(): string
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
            //"device_authorization_endpoint": "https://oauth2.googleapis.com/device/code",
            'response_types_supported'                          => [
                'code',
            ],
            'grant_types_supported'                             => [
                'authorization_code',
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
        $idpentityid = 'tine20';

        $certInfo = \SimpleSAML\Utils\Crypto::loadPublicKey(\SimpleSAML\Configuration::getInstance(), true);

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
            $idp = \SimpleSAML\IdP::getById('saml2:tine20');

            // @phpstan-ignore-next-line
            if ($logoutMessages = \SimpleSAML\Session::getSessionFromRequest()->doLogout(substr($idp->getId(), 6))) {
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
        $idp = \SimpleSAML\IdP::getById('saml2:tine20');

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

        $metadata = MetaDataStorageHandler::getMetadataHandler();
        $idpMetadata = $idp->getConfig();
        $spMetadata = $metadata->getMetaDataConfig($spEntityId, 'saml20-sp-remote');

        \SimpleSAML\Module\saml\Message::validateMessage($spMetadata, $idpMetadata, $message);

        if ($message instanceof \SAML2\LogoutRequest) {
            // @phpstan-ignore-next-line
            $logoutRequests = \SimpleSAML\Session::getSessionFromRequest()->doLogout(substr($idp->getId(), 6));

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

                return Tinebase_Frontend_Http_SinglePageApplication::getClientHTML($jsFiles, 'Tinebase/views/singlePageApplication.html.twig', [
                    'base' => Tinebase_Core::getUrl(Tinebase_Core::GET_URL_PATH),
                    'lang' => $locale,
                    'initialData' => json_encode([
                        'logoutUrls' => $urls,
                        'finalLocation' => $spMetadata->getValue('SingleLogoutService')['Location']
                    ])
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

            return Tinebase_Frontend_Http_SinglePageApplication::getClientHTML($jsFiles, 'Tinebase/views/singlePageApplication.html.twig', [
                'base' => Tinebase_Core::getUrl(Tinebase_Core::GET_URL_PATH),
                'lang' => $locale,
                'initialData' => json_encode([
                    'logoutStatus' => $message->getStatus(),
                    'relyingParty' => [
                        SSO_Model_RelyingParty::FLD_LABEL => $rp ? $rp->{SSO_Model_RelyingParty::FLD_LABEL} : null,
                        SSO_Model_RelyingParty::FLD_DESCRIPTION => $rp ? $rp->{SSO_Model_RelyingParty::FLD_DESCRIPTION} : null,
                        SSO_Model_RelyingParty::FLD_LOGO => $rp ? $rp->{SSO_Model_RelyingParty::FLD_LOGO} : null,
                    ],
                ])
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
        $idp = \SimpleSAML\IdP::getById('saml2:tine20');
        $simpleSampleIsReallyGreat = new ReflectionProperty(\SimpleSAML\IdP::class, 'authSource');
        $simpleSampleIsReallyGreat->setAccessible(true);
        if ($simpleSampleIsReallyGreat->getValue($idp) instanceof \SimpleSAML\Auth\Simple) {
            $simpleSampleIsReallyGreat2 = new ReflectionProperty(\SimpleSAML\Auth\Simple::class, 'authSource');
            $simpleSampleIsReallyGreat2->setAccessible(true);
            $newSimple = new SSO_Facade_SAML_AuthSimple($simpleSampleIsReallyGreat2->getValue($simpleSampleIsReallyGreat
                ->getValue($idp)));
            $simpleSampleIsReallyGreat->setValue($idp, $newSimple);
        } elseif (! $simpleSampleIsReallyGreat->getValue($idp) instanceof SSO_Facade_SAML_AuthSimple) {
            throw new Tinebase_Exception('simple samle auth source config failure ');
        }

        $binding = Binding::getCurrentBinding();
        $authnRequest = $binding->receive();

        if ($authnRequest instanceof AuthnRequest && ($issuer = $authnRequest->getIssuer()) instanceof Issuer &&
                null !== ($spEntityId = $issuer->getValue())) {
            // @phpstan-ignore-next-line
            \SimpleSAML\Session::getSessionFromRequest()->setSPEntityId($spEntityId);
        } else {
            throw new Tinebase_Exception('can\'t resolve request issuer');
        }


        try {
            \SimpleSAML\Module\saml\IdP\SAML2::receiveAuthnRequest($idp);

            throw new Tinebase_Exception('expect simplesaml to throw a resolution');
        } catch (SSO_Facade_SAML_MFAMaskException $e) {
            if ($request->getParsedBody() && array_key_exists('username', $request->getParsedBody())) {
                // render MFA mask
                $response = (new \Laminas\Diactoros\Response())->withHeader('content-type', 'application/json');
                $response->getBody()->write(json_encode([
                    'jsonrpc' => '2.0',
                    'id' => 'fakeid',
                    'error' => [
                        'code' => -32000,
                        'message' => $e->mfaException->getMessage(),
                        'data' => $e->mfaException->toArray(),
                    ],
                ]));
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

    protected static function renderLoginPage(SSO_Model_RelyingParty $rp, array $data, ?string $url = null, ?Tinebase_Exception_SystemGeneric $mfaEx = null)
    {
        $locale = Tinebase_Core::getLocale();

        $jsFiles = ['SSO/js/login.js'];
        $jsFiles[] = "index.php?method=Tinebase.getJsTranslations&locale={$locale}&app=all";

        $initialData = [
            'sso' => $data,
            'relyingParty' => [
                SSO_Model_RelyingParty::FLD_LABEL => $rp->{SSO_Model_RelyingParty::FLD_LABEL},
                SSO_Model_RelyingParty::FLD_DESCRIPTION => $rp->{SSO_Model_RelyingParty::FLD_DESCRIPTION},
                SSO_Model_RelyingParty::FLD_LOGO => $rp->{SSO_Model_RelyingParty::FLD_LOGO},
            ],
        ];
        if ($mfaEx) {
            $initialData['mfa'] = [
                'message' => $mfaEx->getMessage(),
                'data' => $mfaEx->toArray(),
            ];
        }

        $data = [
            'base' => Tinebase_Core::getUrl(Tinebase_Core::GET_URL_PATH),
            'lang' => $locale,
            'initialData' => json_encode($initialData),
        ];
        if ($url) {
            $data['url'] = $url;
        }

        return Tinebase_Frontend_Http_SinglePageApplication::getClientHTML($jsFiles, 'Tinebase/views/singlePageApplication.html.twig', $data);
    }
    
    protected static function getOpenIdConnectServer(): \League\OAuth2\Server\AuthorizationServer
    {
        // Setup the authorization server
        $server = new \League\OAuth2\Server\AuthorizationServer(
            new SSO_Facade_OAuth2_ClientRepository(),
            new SSO_Facade_OAuth2_AccessTokenRepository(),
            new SSO_Facade_OAuth2_ScopeRepository(),
            new SSO_Facade_OAuth2_CryptKey(SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::OAUTH2_KEYS}[0]['privatekey'],
                SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::OAUTH2_KEYS}[0]['kid']),
            SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::OAUTH2_KEYS}[0]['publickey'],
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

        $grant->setIssuer(static::getOAuthIssuer());
        $grant->setRefreshTokenTTL(new \DateInterval('P1M')); // refresh tokens will expire after 1 month

        // Enable the authentication code grant on the server
        $server->enableGrantType(
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
        $server->enableGrantType(
            $grant,
            new \DateInterval('PT1H') // access tokens will expire after 1 hour
        );*/

        return $server;
    }

    protected static function initSAMLServer()
    {
        $sessionReflection = new ReflectionProperty(\SimpleSAML\Session::class, 'instance');
        $sessionReflection->setAccessible(true);
        $sessionReflection->setValue(new SSO_Facade_SAML_Session());

        $saml2Config = SSO_Config::getInstance()->{SSO_Config::SAML2};

        \SAML2\Compat\ContainerSingleton::setContainer(new SSO_Facade_SAML_Container());
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
        switch(SSO_Config::getInstance()->{SSO_Config::PWD_LESS_LOGIN}) {
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

        /** @var ?SSO_Model_ExternalIdp $idp */
        Tinebase_Session::getSessionNamespace()->sso_idp = $idp?->getId();
        return $idp?->{SSO_Model_ExternalIdp::FLD_CONFIG}->initAuthProcess() ?? false;
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
        $client->addScope(['openid', 'email', 'profile']);

        if ($client->authenticate()) {
            $data = $client->requestUserInfo();

            if (!isset($data->sub)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()
                    ->notice(__METHOD__ . '::' . __LINE__ . ' external idp did not send us an sub to work with');
                return static::publicOidAuthResponseErrorRedirect($authRequest);
            }
            try {
                $account = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('openid', $ssoIdp->getId() . ':' . $data->sub, Tinebase_Model_FullUser::class);
            } catch (Tinebase_Exception_NotFound) {
                // TODO FIXME check if we should create!

                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()
                    ->notice(__METHOD__ . '::' . __LINE__ . ' create from extern idp not yet supported');
                return static::publicOidAuthResponseErrorRedirect($authRequest);
                /*
                if (!isset($data->email) || !($pos = strpos($data->email, '@'))) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()
                        ->notice(__METHOD__ . '::' . __LINE__ . ' external idp did not send us an email address to work with');
                    return static::publicOidAuthResponseErrorRedirect($authRequest);
                }
                $loginName = substr($data->email, 0, $pos);

                $oldValue = Admin_Controller_User::getInstance()->doRightChecks(false);
                try {
                    $user = Tinebase_User::createSystemUser(Tinebase_User::SYSTEM_USER_ANONYMOUS);
                    Tinebase_Core::setUser($user);

                    // TODO FIXME without password email user cant be created, needs to be disabled
                    $account = Admin_Controller_User::getInstance()->create(new Tinebase_Model_FullUser(array_merge([
                        'accountLoginName'      => $loginName,
                        'accountEmailAddress'   => $data->email,
                        'openid'                => $ssoIdp->getId() . ':' . $data->sub,
                        'accountLastName'       => $data->name ?? $loginName,
                        'accountPrimaryGroup'   => Tinebase_Group::getInstance()->getDefaultGroup()->getId(),
                    ])), '', '');
                } catch (Tinebase_Exception $e) {
                    $e->setLogLevelMethod('notice');
                    $e->setLogToSentry(false);
                    Tinebase_Exception::log($e);
                    return static::publicOidAuthResponseErrorRedirect($authRequest);
                } finally {
                    Admin_Controller_User::getInstance()->doRightChecks($oldValue);
                    Tinebase_Core::unsetUser();
                }*/
            }

            Tinebase_Auth::destroyInstance();
            Tinebase_Auth::setBackendType('OidcMock');
            try {

                /* TODO FIXME!!! Tinebase_Controller::getInstance()->setRequestContext(array(
                    'MFAPassword' => $MFAPassword,
                    'MFAId'       => $MFAUserConfigId
                ));*/
                Tinebase_Controller::getInstance()->forceUnlockLoginArea();
                if (!Tinebase_Controller::getInstance()->login($account->accountLoginName, '',
                        static::getLoginFakeRequest('/sso/oid/auth/response'),
                        self::OIDC_AUTH_CLIENT_REQUEST_TYPE)) {
                    Tinebase_Exception::log(new Tinebase_Exception('login did not work unexpectedly'));
                    return static::publicOidAuthResponseErrorRedirect($authRequest);
                }

            } catch (Tinebase_Exception_AreaLocked | Tinebase_Exception_AreaUnlockFailed $teal) {
                /** @var SSO_Facade_OAuth2_ClientEntity $client */
                $client = $authRequest?->getClient();
                if (!($relyingParty = $client?->getRelyingPart())) {
                    $relyingParty = new SSO_Model_RelyingParty([], true);
                }
                return static::renderLoginPage($relyingParty, [], $redirectUrl, $teal);
            } finally {
                Tinebase_Auth::destroyInstance();
                Tinebase_Auth::setBackendType(null);
            }

            if ($authRequest) {
                $authRequest->setUser(new SSO_Facade_OAuth2_UserEntity($account));
                $authRequest->setAuthorizationApproved(true);
                return static::getOpenIdConnectServer()->completeAuthorizationRequest($authRequest, new \Laminas\Diactoros\Response());
            }
            return new \Laminas\Diactoros\Response('php://memory', 302, ['Location' => Tinebase_Core::getUrl()]);
        }

        return static::publicOidAuthResponseErrorRedirect($authRequest);
    }
}

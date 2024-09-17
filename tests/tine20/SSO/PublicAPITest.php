<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     SSO
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use SAML2\Utils;

/**
 * SSO public API tests
 *
 * @package     SSO
 */
class SSO_PublicAPITest extends TestCase
{
    protected $_oldSaml2KeyCfg = null;
    protected $_oldOauth2KeyCfg = null;

    public function setUp(): void
    {
        parent::setUp();

        $config = SSO_Config::getInstance();
        $config->{SSO_Config::OAUTH2}->{SSO_Config::ENABLED} = true;
        $config->{SSO_Config::SAML2}->{SSO_Config::ENABLED} = true;

        $keys = $config->{SSO_Config::SAML2}->{SSO_Config::SAML2_KEYS};
        $this->_oldSaml2KeyCfg = is_object($keys) ? $keys->toArray() : $keys;
        if (!isset($keys[0]['privatekey']) || !is_file($keys[0]['privatekey'])) {
            $path = Tinebase_TempFile::getTempPath();
            copy(__DIR__ . '/keys/saml2.pem', $path);
            chmod($path, 0600);
            $keys[0]['privatekey'] = $path;
        }
        if (!isset($keys[0]['certificate']) || !is_file($keys[0]['certificate'])) {
            $path = Tinebase_TempFile::getTempPath();
            copy(__DIR__ . '/keys/saml2.crt', $path);
            chmod($path, 0600);
            $keys[0]['certificate'] = $path;
        }
        $config->{SSO_Config::SAML2}->{SSO_Config::SAML2_KEYS} = $keys;

        $keys = $config->{SSO_Config::OAUTH2}->{SSO_Config::OAUTH2_KEYS};
        $this->_oldOauth2KeyCfg = is_object($keys) ? $keys->toArray() : $keys;
        if (!isset($keys[0]['privatekey']) || !is_file($keys[0]['privatekey'])) {
            $path = Tinebase_TempFile::getTempPath();
            copy(__DIR__ . '/keys/private.key', $path);
            chmod($path, 0600);
            $keys[0]['privatekey'] = $path;
            $keys[0]['kid'] = 'unittestkey';
        }
        if (!isset($keys[0]['publickey']) || !is_file($keys[0]['publickey'])) {
            $keys[0]['publickey'] = __DIR__ . '/keys/public.key';
        }
        $config->{SSO_Config::OAUTH2}->{SSO_Config::OAUTH2_KEYS} = $keys;
    }

    protected function tearDown(): void
    {
        SSO_Config::getInstance()->{SSO_Config::SAML2}->{SSO_Config::SAML2_KEYS} = $this->_oldSaml2KeyCfg;
        SSO_Config::getInstance()->{SSO_Config::OAUTH2}->{SSO_Config::OAUTH2_KEYS} = $this->_oldOauth2KeyCfg;

        parent::tearDown();
    }

    protected function _createSAML2Config()
    {
        SSO_Controller_RelyingParty::getInstance()->create(new SSO_Model_RelyingParty([
            SSO_Model_RelyingParty::FLD_NAME => 'https://localhost:8443/auth/saml2/sp/metadata.php',
            SSO_Model_RelyingParty::FLD_LABEL => 'moodle',
            SSO_Model_RelyingParty::FLD_DESCRIPTION => 'desc',
            SSO_Model_RelyingParty::FLD_LOGO => 'logo',
            SSO_Model_RelyingParty::FLD_CONFIG_CLASS => SSO_Model_Saml2RPConfig::class,
            SSO_Model_RelyingParty::FLD_CONFIG => new SSO_Model_Saml2RPConfig([
                SSO_Model_Saml2RPConfig::FLD_NAME => 'moodle',
                SSO_Model_Saml2RPConfig::FLD_ENTITYID => 'https://localhost:8443/auth/saml2/sp/metadata.php',
                SSO_Model_Saml2RPConfig::FLD_ASSERTION_CONSUMER_SERVICE_BINDING => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                SSO_Model_Saml2RPConfig::FLD_ASSERTION_CONSUMER_SERVICE_LOCATION => 'https://localhost:8443/auth/saml2/sp/saml2-acs.php/localhost',
                SSO_Model_Saml2RPConfig::FLD_SINGLE_LOGOUT_SERVICE_LOCATION => 'https://localhost:8443/auth/saml2/sp/saml2-logout.php/localhost',
                SSO_Model_Saml2RPConfig::FLD_ATTRIBUTE_MAPPING => ['uid' => 'accountEmailAddress'],
                SSO_Model_Saml2RPConfig::FLD_CUSTOM_HOOKS => ['postAuthenticate' => __DIR__ . '/samlPostAuthenticateHook.php'],
            ]),
        ]));

    }

    /**
     * @throws Tinebase_Exception
     * @throws Zend_Session_Exception
     *
     * @group needsbuild
     */
    public function testSaml2LoginPage()
    {
        $this->_createSAML2Config();

        $this->createSAMLRequest();
        Tinebase_Core::unsetUser();
        Tinebase_Session::getSessionNamespace()->unsetAll();

        $response = SSO_Controller::publicSaml2RedirectRequest();
        $response->getBody()->rewind();

        $this->assertSame(200, $response->getStatusCode());
        $response = $response->getBody()->getContents();
        $this->assertStringContainsString('window.initialData={"sso":{"SAMLRequest', $response);
        $this->assertStringContainsString('},"relyingParty":{', $response);
        $this->assertStringContainsString('"label":"moodle"', $response);
        $this->assertStringContainsString('"description":"desc"', $response);
        $this->assertStringContainsString('"logo":"logo"', $response);
    }

    protected function createSAMLRequest()
    {
        $authNRequest = new \SAML2\AuthnRequest();
        ($issuer = new \SAML2\XML\saml\Issuer())->setValue('https://localhost:8443/auth/saml2/sp/metadata.php');
        $authNRequest->setIssuer($issuer);
        $msgStr = $authNRequest->toUnsignedXML();
        $msgStr = $msgStr->ownerDocument->saveXML($msgStr);
        $msgStr = gzdeflate($msgStr);
        $msgStr = base64_encode($msgStr);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = 'SAMLRequest='.urlencode($msgStr);
        $_GET['SAMLRequest'] = $msgStr;

        Tinebase_Core::getContainer()->set(\Psr\Http\Message\RequestInterface::class,
            (new \Laminas\Diactoros\ServerRequest([], [], 'https://unittest/shalala?SAMLRequest=' .
                urlencode($msgStr), 'GET'))
                ->withQueryParams([
                    'SAMLRequest' => $msgStr,
                ])
        );
    }

    /**
     * @group nogitlabciad
     */
    public function testSaml2RedirectRequestAlreadyLoggedIn()
    {
        $this->_createSAML2Config();

        $this->createSAMLRequest();

        $response = SSO_Controller::publicSaml2RedirectRequest();
        $response->getBody()->rewind();

        $this->assertSame(200, $response->getStatusCode());
        $response = $response->getBody()->getContents();
        $this->assertSame(1, preg_match('/\<input\s+type="hidden"\s+name="SAMLResponse"\s+value="([^"]+)"/', $response, $matches));
        $this->assertNotFalse($xml = base64_decode($matches[1]));
        $this->assertStringContainsString('Format="urn:oasis:names:tc:SAML:2.0:nameid-format:persistent">' .
            Tinebase_Core::getUser()->accountEmailAddress . '</saml:NameID>', $xml);
        $this->assertStringContainsString(
            '<saml:Attribute Name="Klasse" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:basic"><saml:AttributeValue xsi:type="xs:string">Users</saml:AttributeValue></saml:Attribute>',
            $xml);
    }

    /**
     * testOAuthGetLoginMask
     *
     * @group needsbuild
     */
    public function testOAuthGetLoginMask()
    {
        $relyingParty = SSO_Controller_RelyingParty::getInstance()->create(new SSO_Model_RelyingParty([
            SSO_Model_RelyingParty::FLD_NAME => 'unittest',
            SSO_Model_RelyingParty::FLD_LABEL => 'unittest label',
            SSO_Model_RelyingParty::FLD_CONFIG_CLASS => SSO_Model_OAuthOIdRPConfig::class,
            SSO_Model_RelyingParty::FLD_CONFIG => new SSO_Model_OAuthOIdRPConfig([
                SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS   => ['https://unittest.test/uri'],
                SSO_Model_OAuthOIdRPConfig::FLD_SECRET          => 'unittest',
                SSO_Model_OAuthOIdRPConfig::FLD_IS_CONFIDENTIAL => true,
            ]),
        ]));

        Tinebase_Core::getContainer()->set(\Psr\Http\Message\RequestInterface::class,
            (new \Laminas\Diactoros\ServerRequest([], [], 'https://unittest/shalala?response_type=code' .
                '&scope=openid%20profile%20email' .
                '&client_id=' . urlencode($relyingParty->{SSO_Model_RelyingParty::FLD_NAME}) .
                '&state=af0ifjsldkj' .
                '&nonce=nonce' .
                '&redirect_uri=' . urlencode($relyingParty->{SSO_Model_RelyingParty::FLD_CONFIG}->{SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS}[0]), 'GET'))
            ->withQueryParams([
                'response_type' => 'code',
                'scope' => 'openid profile email',
                'client_id' => $relyingParty->{SSO_Model_RelyingParty::FLD_NAME},
                'state' => 'af0ifjsldkj',
                'nonce' => 'nonce',
                'redirect_uri' => $relyingParty->{SSO_Model_RelyingParty::FLD_CONFIG}->{SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS}[0]
            ])
        );

        Tinebase_Core::unsetUser();
        $coreSession = Tinebase_Session::getSessionNamespace();
        if (isset($coreSession->currentAccount)) {
            unset($coreSession->currentAccount);
        }

        $response = SSO_Controller::publicAuthorize();
        $response->getBody()->rewind();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('"label":"'. $relyingParty->{SSO_Model_RelyingParty::FLD_LABEL}, $response->getBody()->getContents());
    }

    public function testOAuthPostLoginMask()
    {
        $relyingParty = SSO_Controller_RelyingParty::getInstance()->create(new SSO_Model_RelyingParty([
            SSO_Model_RelyingParty::FLD_NAME => 'unittest',
            SSO_Model_RelyingParty::FLD_CONFIG_CLASS => SSO_Model_OAuthOIdRPConfig::class,
            SSO_Model_RelyingParty::FLD_CONFIG => new SSO_Model_OAuthOIdRPConfig([
                SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS   => ['https://unittest.test/uri'],
                SSO_Model_OAuthOIdRPConfig::FLD_SECRET          => 'unittest',
                SSO_Model_OAuthOIdRPConfig::FLD_IS_CONFIDENTIAL => true,
            ]),
        ]));

        Tinebase_Core::getContainer()->set(\Psr\Http\Message\RequestInterface::class,
            (new \Laminas\Diactoros\ServerRequest([], [], 'https://unittest/shalala?response_type=code' .
                '&scope=openid%20profile%20email' .
                '&client_id=' . urlencode($relyingParty->{SSO_Model_RelyingParty::FLD_NAME}) .
                '&state=af0ifjsldkj' .
                '&nonce=nonce' .
                '&redirect_uri=' . urlencode($relyingParty->{SSO_Model_RelyingParty::FLD_CONFIG}->{SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS}[0]), 'POST'))
                ->withQueryParams([
                    'response_type' => 'code',
                    'scope' => 'openid profile email',
                    'client_id' => $relyingParty->{SSO_Model_RelyingParty::FLD_NAME},
                    'state' => 'af0ifjsldkj',
                    'nonce' => 'nonce',
                    'redirect_uri' => $relyingParty->{SSO_Model_RelyingParty::FLD_CONFIG}->{SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS}[0],
                ])->withParsedBody([
                    'username' => TestServer::getInstance()->getTestCredentials()['username'],
                    'password' => TestServer::getInstance()->getTestCredentials()['password'],
                ])
        );

        Tinebase_Auth::getInstance()->setBackend();
        Tinebase_Core::unsetUser();
        $coreSession = Tinebase_Session::getSessionNamespace();
        if (isset($coreSession->currentAccount)) {
            unset($coreSession->currentAccount);
        }

        $response = SSO_Controller::publicAuthorize();
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testOAuthAutoAuth()
    {
        $relyingParty = SSO_Controller_RelyingParty::getInstance()->create(new SSO_Model_RelyingParty([
            SSO_Model_RelyingParty::FLD_NAME => 'unittest',
            SSO_Model_RelyingParty::FLD_CONFIG_CLASS => SSO_Model_OAuthOIdRPConfig::class,
            SSO_Model_RelyingParty::FLD_CONFIG => new SSO_Model_OAuthOIdRPConfig([
                SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS   => ['https://unittest.test/uri'],
                SSO_Model_OAuthOIdRPConfig::FLD_SECRET          => 'unittest',
                SSO_Model_OAuthOIdRPConfig::FLD_IS_CONFIDENTIAL => true,
            ]),
        ]));

        Tinebase_Core::getContainer()->set(\Psr\Http\Message\RequestInterface::class,
            (new \Laminas\Diactoros\ServerRequest([], [], 'https://unittest/shalala?response_type=code' .
                '&scope=openid%20profile%20email' .
                '&client_id=' . urlencode($relyingParty->{SSO_Model_RelyingParty::FLD_NAME}) .
                '&state=af0ifjsldkj' .
                '&nonce=nonce' .
                '&redirect_uri=' . urlencode($relyingParty->{SSO_Model_RelyingParty::FLD_CONFIG}->{SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS}[0]), 'GET'))
                ->withQueryParams([
                    'response_type' => 'code',
                    'scope' => 'openid profile email',
                    'client_id' => $relyingParty->{SSO_Model_RelyingParty::FLD_NAME},
                    'state' => 'af0ifjsldkj',
                    'nonce' => 'nonce',
                    'redirect_uri' => $relyingParty->{SSO_Model_RelyingParty::FLD_CONFIG}->{SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS}[0]
                ])
        );

        $response = SSO_Controller::publicAuthorize();
        $this->assertSame(302, $response->getStatusCode());
        $header = $response->getHeader('Location');
        $this->assertIsArray($header);
        $this->assertCount(1, $header);
        $this->assertStringContainsString('&state=af0ifjsldkj', $header[0]);
        $this->assertStringStartsWith($relyingParty->{SSO_Model_RelyingParty::FLD_CONFIG}->{SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS}[0] . '?', $header[0]);
        $this->assertTrue((bool)preg_match('/code=([^&]+)/', $header[0], $m), 'can not pregmatch code in redirect url');

        Tinebase_Core::getContainer()->set(\Psr\Http\Message\RequestInterface::class,
            (new \Laminas\Diactoros\ServerRequest([], [], 'https://unittest/shalala', 'GET'))
                ->withParsedBody([
                    'grant_type' => 'authorization_code',
                    'scope' => 'openid profile email',
                    'code' => $m[1],
                    'client_id' => $relyingParty->{SSO_Model_RelyingParty::FLD_NAME},
                    'client_secret' => 'unittest',
                    'redirect_uri' => $relyingParty->{SSO_Model_RelyingParty::FLD_CONFIG}->{SSO_Model_OAuthOIdRPConfig::FLD_REDIRECT_URLS}[0],
                ])
        );
/*
        $response = SSO_Controller::publicToken();
        $this->assertSame(200, $response->getStatusCode());
        $stream = $response->getBody();
        $stream->rewind();
        $this->assertIsArray($response = json_decode($stream->getContents(), true));
        $this->assertArrayHasKey('id_token', $response);
        //$components = explode('.', $response['id_token']);
        //$claims = json_decode(base64_decode($components[1]), true);

        Tinebase_Core::getContainer()->set(\Psr\Http\Message\RequestInterface::class,
            (new \Laminas\Diactoros\ServerRequest([], [], 'https://unittest/shalala', 'GET'))
                ->withHeader('Authorization', 'Bearer ' . $response['id_token'])
        );

        $response = SSO_Controller::publicOIUserInfo();
        $this->assertSame(200, $response->getStatusCode());*/
        /*$stream = $response->getBody();
        $stream->rewind();
        $this->assertSame('foo', $stream->getContents());*/
    }
}

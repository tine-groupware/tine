<?php

/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

use Psr\Http\Message\RequestInterface;

/**
 * Test class for Tinebase_Server_Json
 * 
 * @package     Tinebase
 */
class Tinebase_Server_JsonTests extends TestCase
{
    protected $_imapConf = null;

    protected function setUp(): void
    {
        if (! Tinebase_Application::getInstance()->isInstalled('Inventory')) {
            self::markTestSkipped('Tests need Inventory app');
        }
        parent::setUp();
    }

    /**
     * tear down tests
     */
    protected function tearDown(): void
    {
        if ($this->_imapConf !== null) {
            Tinebase_Config::getInstance()->set(Tinebase_Config::IMAP, $this->_imapConf);
            Tinebase_EmailUser::clearCaches();
            Tinebase_EmailUser::destroyInstance();
        }

        parent::tearDown();
    }

    /**
     * @param bool $success
     * @return void
     * @throws Tinebase_Exception_SystemGeneric
     * @throws Zend_Json_Exception
     * @throws Zend_Session_Exception
     *
     * @group ServerTests
     */
    public function testLoginMFA(bool $success = true)
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::LDAP) {
            $this->markTestSkipped('LDAP backend enabled');
        }

        $totp = $this->_prepTOTP();

        $user = Tinebase_Core::getUser();
        $mfaId = $user->mfa_configs->getFirstRecord()->getId();
        $credentials = TestServer::getInstance()->getTestCredentials();
        $loginData = [
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'MFAUserConfigId' => $mfaId,
            'MFAPassword' => $success ? $totp->now() : '000000',
        ];
        $resultString = $this->_handleRequest('Tinebase.login', $loginData, !$success);

        $result = Tinebase_Helper::jsonDecode($resultString);
        if ($success) {
            self::assertArrayHasKey('success', $result['result']);
            self::assertEquals($success, $result['result']['success'], print_r($result, true));
        } else {
            $accessLog = Tinebase_AccessLog::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
                Tinebase_Model_AccessLog::class, [[
                    'field' => 'user_agent', 'operator' => 'equals', 'value' => 'Unit Test Client',
                    'field' => 'clienttype', 'operator' => 'equals', 'value' => 'JSON-RPC',
                ]]
            ), new Tinebase_Model_Pagination(['sort' => 'li', 'dir' => 'desc']))->getFirstRecord();
            self::assertNotNull($accessLog);
            self::assertNotEquals(Tinebase_Auth::SUCCESS, $accessLog->result,
                print_r($accessLog->toArray(), true));
        }
    }

    /**
     * @throws Tinebase_Exception_SystemGeneric
     * @throws Zend_Json_Exception
     * @throws Zend_Session_Exception
     *
     * @group ServerTests
     */
    public function testLoginMFAFail()
    {
        // wait 1 sec to make sure we get the correct access log
        sleep(1);
        $this->testLoginMFA(false);
    }

    /**
     * @group ServerTests
     */
    public function testGetServiceMap()
    {
        $smd = Tinebase_Server_Json::getServiceMap();
        $smdArray = $smd->toArray();

        $expectedFunctions = array(
            'Inventory.searchInventoryItems',
            'Inventory.saveInventoryItem',
            'Inventory.deleteInventoryItems',
            'Inventory.getInventoryItem',
            'Inventory.importInventoryItems',
            'Tasks.importTasks',
        );

        foreach ($expectedFunctions as $function) {
            $this->assertTrue(in_array($function, array_keys($smdArray['methods'])), 'fn not in methods: ' . $function);
            $this->assertTrue(in_array($function, array_keys($smdArray['services'])), 'fn not in services: ' . $function);
        }

        $this->assertEquals(array
        (
            'envelope' => 'JSON-RPC-2.0',
            'transport' => 'POST',
            'parameters' => array (
                array (
                    'type' => 'array',
                    'optional' => false,
                    'name' => 'recordData'
                ),
                array (
                    'type' => 'boolean',
                    'optional' => true,
                    'name' => 'duplicateCheck'
                )
            ),
            'returns' => 'array',
            'apiTimeout' => null,
        ), $smdArray['services']['Inventory.saveInventoryItem'], 'saveInventoryItem smd mismatch');
        $this->assertEquals(array
        (
            'envelope' => 'JSON-RPC-2.0',
            'transport' => 'POST',
            'parameters' => array (
                array (
                    'type' => 'array',
                    'optional' => false,
                    'name' => 'ids'
                )
            ),
            'returns' => 'array',
            'apiTimeout' => null,
        ), $smdArray['services']['Inventory.deleteInventoryItems']);

        $this->assertEquals(array
        (
            'envelope' => 'JSON-RPC-2.0',
            'transport' => 'POST',
            'parameters' => array
            (
                array
                (
                    'type' => 'array',
                    'optional' => false,
                    'name' => 'filter'
                ),
                array
                (
                    'type' => 'array',
                    'optional' => false,
                    'name' => 'paging'
                )
            ),
            'returns' => 'array',
            'apiTimeout' => null,
        ), $smdArray['services']['Inventory.searchInventoryItems']);

        self::assertEquals(array
        (
            'envelope' => 'JSON-RPC-2.0',
            'transport' => 'POST',
            'parameters' => array
            (
                array
                (
                    'type' => 'string',
                    'optional' => false,
                    'name' => 'tempFileId'
                ),
                array
                (
                    'type' => 'string',
                    'optional' => false,
                    'name' => 'definitionId'
                ),
                array
                (
                    'type' => 'array',
                    'optional' => true,
                    'name' => 'importOptions'
                ),
                array
                (
                    'type' => 'array',
                    'optional' => true,
                    'name' => 'clientRecordData'
                ),
            ),
            'returns' => 'array',
            'apiTimeout' => null,
        ), $smdArray['services']['Inventory.importInventoryItems']);
    }

    public function testMFALoginSms()
    {
        $this->_originalTestUser->mfa_configs = new Tinebase_Record_RecordSet(
            Tinebase_Model_MFA_UserConfig::class, [[
            Tinebase_Model_MFA_UserConfig::FLD_ID => 'userunittest',
            Tinebase_Model_MFA_UserConfig::FLD_MFA_CONFIG_ID => 'sms',
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG_CLASS =>
                Tinebase_Model_MFA_SmsUserConfig::class,
            Tinebase_Model_MFA_UserConfig::FLD_CONFIG =>
                new Tinebase_Model_MFA_SmsUserConfig([
                    Tinebase_Model_MFA_SmsUserConfig::FLD_CELLPHONENUMBER => '1234567890',
                ]),
        ]]);

        $this->_createAreaLockConfig([
            Tinebase_Model_AreaLockConfig::FLD_MFAS => ['sms'],
        ], [
            Tinebase_Model_MFA_Config::FLD_ID => 'sms',
            Tinebase_Model_MFA_Config::FLD_USER_CONFIG_CLASS =>
                Tinebase_Model_MFA_SmsUserConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG_CLASS =>
                Tinebase_Model_MFA_GenericSmsConfig::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CLASS =>
                Tinebase_Auth_MFA_GenericSmsAdapter::class,
            Tinebase_Model_MFA_Config::FLD_PROVIDER_CONFIG => [
                Tinebase_Model_MFA_GenericSmsConfig::FLD_URL => 'https://shoo.tld/restapi/message',
                Tinebase_Model_MFA_GenericSmsConfig::FLD_BODY => '{"encoding":"auto","body":"{{ message }}","originator":"{{ app.branding.title }}","recipients":["{{ cellphonenumber }}"],"route":"2345"}',
                Tinebase_Model_MFA_GenericSmsConfig::FLD_METHOD => 'POST',
                Tinebase_Model_MFA_GenericSmsConfig::FLD_HEADERS => [
                    'Auth-Bearer' => 'unittesttokenshaaaaalalala'
                ],
                Tinebase_Model_MFA_GenericSmsConfig::FLD_PIN_TTL => 600,
                Tinebase_Model_MFA_GenericSmsConfig::FLD_PIN_LENGTH => 6,
            ]
        ]);
        $this->_originalTestUser = Tinebase_User::getInstance()->updateUser($this->_originalTestUser);

        $mfa = Tinebase_Auth_MFA::getInstance('sms');
        $mfa->getAdapter()->setHttpClientConfig([
            'adapter' => ($httpClientTestAdapter = new Tinebase_ZendHttpClientAdapter())
        ]);
        $httpClientTestAdapter->setResponse(new Zend_Http_Response(200, []));

        $mfaId = $this->_originalTestUser->mfa_configs->getFirstRecord()->getId();
        $credentials = TestServer::getInstance()->getTestCredentials();
        $loginData = [
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'MFAUserConfigId' => $mfaId,
            'MFAPassword' => '',
        ];
        // we don't have core user before logged in
        $this->_originalTestUser = Tinebase_Core::getUser();

        $session = Tinebase_Session::getSessionNamespace();
        try {
            $session->currentAccount = null;
            Tinebase_Core::unsetUser();
            $resultString = $this->_handleRequest('Tinebase.login', $loginData, true);
            $result = Tinebase_Helper::jsonDecode($resultString);
            self::assertEquals('mfa required', $result['error']['message'] ?? '', print_r($result, true));
        } finally {
            $session->currentAccount = $this->_originalTestUser;
            Tinebase_Core::setUser($this->_originalTestUser);
        }
    }

    /**
     * @group ServerTests
     */
    public function testGetAppPwdServiceMap()
    {
        $appPwd = Tinebase_Controller_AppPassword::getInstance()->create(new Tinebase_Model_AppPassword([
            Tinebase_Model_AppPassword::FLD_ACCOUNT_ID => $this->_originalTestUser->getId(),
            Tinebase_Model_AppPassword::FLD_AUTH_TOKEN => Tinebase_Record_Abstract::generateUID(),
            Tinebase_Model_AppPassword::FLD_VALID_UNTIL => Tinebase_DateTime::now()->addYear(10),
            Tinebase_Model_AppPassword::FLD_CHANNELS => [
                'Addressbook.saveContact' => true,
                'Crm.saveLead' => true,
                'HumanResources.getAttendanceRecorderDeviceStates' => true,
            ],
        ]));

        $session = Tinebase_Session::getSessionNamespace();
        try {
            $session->{Tinebase_Model_AppPassword::class} = $appPwd;

            $smd = Tinebase_Server_Json::getServiceMap();
            $smdArray = $smd->toArray();
            $msg = print_r($smdArray, true);
            $this->assertCount(3, $smdArray['services'], $msg);
            $this->assertArrayHasKey('Addressbook.saveContact', $smdArray['services'], $msg);
            $this->assertArrayHasKey('Crm.saveLead', $smdArray['services'], $msg);
            $this->assertArrayHasKey('HumanResources.getAttendanceRecorderDeviceStates', $smdArray['services'], $msg);

        } finally {
            $session->{Tinebase_Model_AppPassword::class} = null;
        }
    }

    /**
     * @group ServerTests
     */
    public function testAppPwdApiCall()
    {
        $pwd = join('', array_fill(0, Tinebase_Controller_AppPassword::PWD_LENGTH - Tinebase_Controller_AppPassword::PWD_SUFFIX_LENGTH, 'a')) . Tinebase_Controller_AppPassword::PWD_SUFFIX;
        $appPwd = Tinebase_Controller_AppPassword::getInstance()->create(new Tinebase_Model_AppPassword([
            Tinebase_Model_AppPassword::FLD_ACCOUNT_ID => $this->_originalTestUser->getId(),
            Tinebase_Model_AppPassword::FLD_AUTH_TOKEN => $pwd,
            Tinebase_Model_AppPassword::FLD_VALID_UNTIL => Tinebase_DateTime::now()->addYear(10),
            Tinebase_Model_AppPassword::FLD_CHANNELS => [
                'Addressbook.searchContacts' => true,
            ],
        ]));

        $session = Tinebase_Session::getSessionNamespace();
        try {
            $session->currentAccount = null;
            $session->{Tinebase_Model_AppPassword::class} = $appPwd;
            Tinebase_Core::unsetUser();

            $resultString = $this->_handleRequest('Addressbook.searchContacts', [[], []], false,
                'Authorization: Basic ' . base64_encode($this->_originalTestUser->accountLoginName . ':' . $pwd) . "\r\n");
            $result = Tinebase_Helper::jsonDecode($resultString);
            $this->assertArrayHasKey('results', $result['result']);
            $this->assertGreaterThan(0, count($result['result']['results']));

            Tinebase_Core::unsetUser();
            $session->currentAccount = null;
            $session->{Tinebase_Model_AppPassword::class} = null;
            $pwd = join('', array_fill(0, Tinebase_Controller_AppPassword::PWD_LENGTH - Tinebase_Controller_AppPassword::PWD_SUFFIX_LENGTH, 'b')) . Tinebase_Controller_AppPassword::PWD_SUFFIX;
            $resultString = $this->_handleRequest('Addressbook.searchContacts', [[], []], true,
                'Authorization: Basic ' . base64_encode($this->_originalTestUser->accountLoginName . ':' . $pwd) . "\r\n", true);
            $result = Tinebase_Helper::jsonDecode($resultString);
            $this->assertArrayHasKey('error', $result);
            $this->assertSame(401, ($result['error']['data']['code']));

        } finally {
            $session->{Tinebase_Model_AppPassword::class} = null;
            $session->currentAccount = $this->_originalTestUser;
            Tinebase_Core::setUser($this->_originalTestUser);
        }
    }

    /**
     * @group ServerTests
     */
    public function testAppPwdApiCall1()
    {
        $this->_createAreaLockConfig();
        $pwd = join('', array_fill(0, Tinebase_Controller_AppPassword::PWD_LENGTH - Tinebase_Controller_AppPassword::PWD_SUFFIX_LENGTH, 'a')) . Tinebase_Controller_AppPassword::PWD_SUFFIX;
        $appPwd = Tinebase_Controller_AppPassword::getInstance()->create(new Tinebase_Model_AppPassword([
            Tinebase_Model_AppPassword::FLD_ACCOUNT_ID => $this->_originalTestUser->getId(),
            Tinebase_Model_AppPassword::FLD_AUTH_TOKEN => $pwd,
            Tinebase_Model_AppPassword::FLD_VALID_UNTIL => Tinebase_DateTime::now()->addYear(10),
            Tinebase_Model_AppPassword::FLD_CHANNELS => [
                'HumanResources.getAttendanceRecorderDeviceStates' => true,
            ],
        ]));

        $session = Tinebase_Session::getSessionNamespace();
        try {
            $session->currentAccount = null;
            $session->{Tinebase_Model_AppPassword::class} = $appPwd;
            Tinebase_Core::unsetUser();

            $resultString = $this->_handleRequest('HumanResources.getAttendanceRecorderDeviceStates', [], false,
                'Authorization: Basic ' . base64_encode($this->_originalTestUser->accountLoginName . ':' . $pwd) . "\r\n");
            $result = Tinebase_Helper::jsonDecode($resultString);
            $this->assertArrayHasKey('results', $result['result']);
            $this->assertCount(0, $result['result']['results']);

        } finally {
            $session->{Tinebase_Model_AppPassword::class} = null;
            $session->currentAccount = $this->_originalTestUser;
            Tinebase_Core::setUser($this->_originalTestUser);
        }
    }

    /**
     * @group ServerTests
     */
    public function testGetAnonServiceMap()
    {
        // unset registry (and the user object)
        Zend_Registry::_unsetInstance();

        $smd = Tinebase_Server_Json::getServiceMap();
        $smdArray = $smd->toArray();
        $this->assertTrue(isset($smdArray['services']['Tinebase.ping']), 'Tinebase.ping missing from service map: '
            . print_r($smdArray, true));
    }

    /**
     * @group ServerTests
     *
     * @see  0011760: create smd from model definition
     */
    public function testHandleRequestForDynamicAPI()
    {
        $this->_handleRequest('Inventory.searchInventoryItems', [
            'filter' => [],
            'paging' => [],
        ]);

        // TODO add get/delete/save
        $resultString = $this->_handleRequest('Inventory.saveInventoryItem', [
            'recordData' => [
                'name' => Tinebase_Record_Abstract::generateUID()
            ],
        ]);
        $result = Tinebase_Helper::jsonDecode($resultString);
        self::assertArrayHasKey('id', $result['result']);
        $id = $result['result']['id'];

        $resultString = $this->_handleRequest('Inventory.getInventoryItem', [
            'id' => $id
        ]);
        $result = Tinebase_Helper::jsonDecode($resultString);
        self::assertArrayHasKey('id', $result['result']);

        $resultString = $this->_handleRequest('Inventory.deleteInventoryItems', [
            'ids' => [$id]
        ]);
        $result = Tinebase_Helper::jsonDecode($resultString);
        self::assertArrayHasKey('status', $result['result']);
        self::assertEquals('success', $result['result']['status']);
    }

    /**
     * @group ServerTests
     */
    public function testRateLimit()
    {
        $oldConfigs = Tinebase_Config::getInstance()->get(Tinebase_Config::RATE_LIMITS)->toArray();
        $configs = $oldConfigs;
        $configs[Tinebase_Config::RATE_LIMITS_FRONTENDS][Tinebase_Server_Json::class] = [
            [
                Tinebase_Model_RateLimit::FLD_METHOD => 'Inventory.searchInventoryItems',
                Tinebase_Model_RateLimit::FLD_MAX_REQUESTS => 1,
                Tinebase_Model_RateLimit::FLD_PERIOD => 3600, // per hour
            ]
        ];
        Tinebase_Config::getInstance()->set(Tinebase_Config::RATE_LIMITS, $configs);

        $params = [
            'filter' => [],
            'paging' => [],
        ];
        $response = $this->_handleRequest('Inventory.searchInventoryItems', $params, true);
        $response = $this->_handleRequest('Inventory.searchInventoryItems', $params, true);
        self::assertStringContainsString('Method is rate-limited: Inventory.searchInventoryItems', $response);
        Tinebase_Config::getInstance()->set(Tinebase_Config::RATE_LIMITS, $oldConfigs);
    }

    /**
     * @group ServerTests
     */
    public function testRateLimitByUser()
    {
        $oldConfigs = Tinebase_Config::getInstance()->get(Tinebase_Config::RATE_LIMITS)->toArray();
        $configs = $oldConfigs;
        $configs[Tinebase_Config::RATE_LIMITS_USER]['*'] = [
            [
                Tinebase_Model_RateLimit::FLD_METHOD => 'Inventory.search*',
                Tinebase_Model_RateLimit::FLD_MAX_REQUESTS => 1,
                Tinebase_Model_RateLimit::FLD_PERIOD => 3600, // per hour
            ]
        ];
        Tinebase_Config::getInstance()->set(Tinebase_Config::RATE_LIMITS, $configs);

        $params = [
            'filter' => [],
            'paging' => [],
        ];
        $response = $this->_handleRequest('Inventory.searchInventoryItems', $params, true);
        $response = $this->_handleRequest('Inventory.searchInventoryItems', $params, true);
        self::assertStringContainsString('Method is rate-limited: Inventory.searchInventoryItems', $response);
        Tinebase_Config::getInstance()->set(Tinebase_Config::RATE_LIMITS, $oldConfigs);
    }

    /**
     * @param string $method
     * @param array $params
     * @throws Tinebase_Exception_SystemGeneric
     * @throws Zend_Session_Exception
     * @return string
     */
    protected function _handleRequest(string $method, array $params, bool $allowError = false, string $additionalHeaders = '', bool $allow401 = false)
    {
        // handle jsonkey check
        $jsonkey = 'myawsomejsonkey';
        $_SERVER['HTTP_X_TINE20_JSONKEY'] = $jsonkey;
        $coreSession = Tinebase_Session::getSessionNamespace();
        $coreSession->jsonKey = $jsonkey;

        $server = new Tinebase_Server_Json();
        $request = Tinebase_Http_Request::fromString(
            'POST /index.php?requestType=JSON HTTP/1.1' . "\r\n"
            . 'Host: localhost' . "\r\n"
            . 'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7' . "\r\n"
            . 'Content-Type: application/json' . "\r\n"
            . 'X-Tine20-Transactionid: 18da265bc0eb66a36081bfd42689c1675ed68bab' . "\r\n"
            . 'X-Requested-With: XMLHttpRequest' . "\r\n"
            . 'Accept: */*' . "\r\n"
            . 'Referer: http://tine20.vagrant/' . "\r\n"
            . 'Accept-Encoding: gzip, deflate' . "\r\n"
            . 'Accept-Language: en-US,en;q=0.8,de-DE;q=0.6,de;q=0.4' . "\r\n"
            . $additionalHeaders
            . "\r\n"
            . '{"jsonrpc":"2.0","method":"' . $method . '","params":' . json_encode($params) . ',"id":6}' . "\r\n"
        );
        ob_start();
        $server->handle($request);
        $out = ob_get_clean();
        //echo $out;
        $this->assertTrue(! empty($out), 'request should not be empty');
        if (!$allow401) {
            $this->assertStringNotContainsString('Not Authorised', $out);
        }
        $this->assertStringNotContainsString('Method not found', $out);
        $this->assertStringNotContainsString('No Application Controller found', $out);
        if (! $allowError) {
            $this->assertStringNotContainsString('"error"', $out);
            $this->assertStringContainsString('"result"', $out);
        }
        $this->assertStringNotContainsString('PHP Fatal error', $out);

        return $out;
    }

    /**
     * @throws Tinebase_Exception_SystemGeneric
     * @throws Zend_Json_Exception
     * @throws Zend_Session_Exception
     *
     * @group ServerTests
     */
    public function testLogin()
    {
        if (Tinebase_User::getInstance() instanceof Tinebase_User_Ldap) {
            // disconnect ldap auth backend first to make new ldap->bind work
            Tinebase_Auth::getInstance()->getBackend()->getLdap()->disconnect();
        }

        $credentials = TestServer::getInstance()->getTestCredentials();
        $resultString = $this->_handleRequest('Tinebase.login', [
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ]);

        $result = Tinebase_Helper::jsonDecode($resultString);
        self::assertArrayHasKey('success', $result['result']);
        self::assertEquals(true, $result['result']['success'], print_r($result, true));
    }

    /**
     * @throws Tinebase_Exception_SystemGeneric
     * @throws Zend_Json_Exception
     * @throws Zend_Session_Exception
     *
     * @group ServerTests
     */
    public function testLoginWithoutConnectionToMailDb()
    {
        $this->_skipWithoutEmailSystemAccountConfig();

        $this->_imapConf = Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP);
        $newConf = $this->_imapConf->toArray();
        $newConf['dovecot']['host'] = 'www.tine-groupware.de';
        Tinebase_Config::getInstance()->set(Tinebase_Config::IMAP, $newConf);
        Tinebase_EmailUser::clearCaches();
        Tinebase_EmailUser::destroyInstance();

        $before = Tinebase_DateTime::now();
        $this->testLogin();
        self::assertTrue($before->isLater(Tinebase_DateTime::now()->subSecond(5))
            , 'login took longer than 4-5 secs!');
    }
}

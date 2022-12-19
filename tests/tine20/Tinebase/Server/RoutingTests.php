<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for Tinebase_Server_Routing
 *
 * @package     Tinebase
 *
 * TODO rename all this stuff once we decided on a name!
 *
 * TODO routing Routing Expressive expressive in case we search for it, remove this comment after renaming stuff
 */
class Tinebase_Server_RoutingTests extends TestCase
{
    /**
     * @group ServerTests
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_NotImplemented
     */
    public function testExampleApplicationPublicTestRoute()
    {
        if (! Tinebase_Application::getInstance()->isInstalled('ExampleApplication')) {
            self::markTestSkipped('Test needs ExampleApplication');
        }

        Tinebase_Application::getInstance()->setApplicationStatus(Tinebase_Application::getInstance()
            ->getApplicationByName('ExampleApplication'), Tinebase_Application::ENABLED);

        $emitter = new Tinebase_Server_UnittestEmitter();
        $server = new Tinebase_Server_Expressive($emitter);

        $request = \Zend\Psr7Bridge\Psr7ServerRequest::fromZend(Tinebase_Http_Request::fromString(
            'GET /ExampleApplication/public/testRoute HTTP/1.1' . "\r\n"
            . 'Host: localhost' . "\r\n"
            . 'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7' . "\r\n"
            . 'Content-Type: multipart/form-data; boundary=----WebKitFormBoundaryZQRf6nhpOLbSRcoe' . "\r\n"
            . 'Accept: */*' . "\r\n"
            . 'Referer: http://tine20.vagrant/' . "\r\n"
            . 'Accept-Encoding: gzip, deflate' . "\r\n"
            . 'Accept-Language: en-US,en;q=0.8,de-DE;q=0.6,de;q=0.4' . "\r\n"
            . "\r\n"
        ));

        /** @var \Symfony\Component\DependencyInjection\Container $container */
        $container = Tinebase_Core::getPreCompiledContainer();
        $container->set(\Psr\Http\Message\RequestInterface::class, $request);
        Tinebase_Core::setContainer($container);

        $server->handle();

        $emitter->response->getBody()->rewind();
        static::assertEquals(ExampleApplication_Controller::publicTestRouteOutput, $emitter->response->getBody()
            ->getContents());
    }

    /**
     * @group ServerTests
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_NotImplemented
     */
    public function testExampleApplicationAuthTestRoute()
    {
        if (! Tinebase_Application::getInstance()->isInstalled('ExampleApplication')) {
            self::markTestSkipped('Test needs ExampleApplication');
        }

        Tinebase_Application::getInstance()->setApplicationStatus(Tinebase_Application::getInstance()
            ->getApplicationByName('ExampleApplication'), Tinebase_Application::ENABLED);

        $request = \Zend\Psr7Bridge\Psr7ServerRequest::fromZend(Tinebase_Http_Request::fromString(
            'GET /ExampleApplication/testRoute HTTP/1.1' . "\r\n"
            . 'Host: localhost' . "\r\n"
            . 'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:15.0) Gecko/20120824 Thunderbird/15.0 Lightning/1.7' . "\r\n"
            . 'Content-Type: multipart/form-data; boundary=----WebKitFormBoundaryZQRf6nhpOLbSRcoe' . "\r\n"
            . 'Accept: */*' . "\r\n"
            . 'Referer: http://tine20.vagrant/' . "\r\n"
            . 'Accept-Encoding: gzip, deflate' . "\r\n"
            . 'Accept-Language: en-US,en;q=0.8,de-DE;q=0.6,de;q=0.4' . "\r\n"
            . "\r\n"
        ));

        $content = $this->_emitRequest($request);
        static::assertEquals(ExampleApplication_Controller::authTestRouteOutput, $content);
    }

    protected function _emitRequest($request)
    {
        $emitter = new Tinebase_Server_UnittestEmitter();
        $server = new Tinebase_Server_Expressive($emitter);
        /** @var \Symfony\Component\DependencyInjection\Container $container */
        $container = Tinebase_Core::getPreCompiledContainer();
        $container->set(\Psr\Http\Message\RequestInterface::class, $request);
        Tinebase_Core::setContainer($container);

        $server->handle();

        $emitter->response->getBody()->rewind();
        return $emitter->response->getBody()->getContents();
    }

    /**
     * @group ServerTests
     */
    public function testHealthCheck()
    {
        $request = \Zend\Psr7Bridge\Psr7ServerRequest::fromZend(Tinebase_Http_Request::fromString(
            'GET /health HTTP/1.1' . "\r\n"
            . 'Host: localhost' . "\r\n"
            . 'User-Agent: Tine 2.0 UNITTEST' . "\r\n"
            . 'Accept: */*' . "\r\n"
            . "\r\n"
        ));

        $content = $this->_emitRequest($request);
        self::assertNotEmpty($content);
        self::assertEquals('{"status":"pass","problems":[]}', $content);
    }

    /**
     * @group ServerTests
     */
    public function testMetric()
    {
        Tinebase_Config::getInstance()->set(Tinebase_Config::METRICS_API_KEY, 'testmetrics123');
        
        $jsonResponse = Tinebase_Controller::getInstance()->getStatusMetrics('testmetrics123');
        $status = Tinebase_Helper::jsonDecode($jsonResponse->getBody()->getContents());

        $userId = Tinebase_Core::getUser()->getId();
        $adminJson = new Admin_Frontend_Json();
        $user = $adminJson->getUser($userId);

        $imapBackend = Tinebase_EmailUser::getInstance();
        $imapUsageQuota = $imapBackend->getTotalUsageQuota();
        
        $data = [
            'activeUsers' => 1,
            'fileStorage' => $user['effectiveAndLocalQuota']['effectiveUsage'],
            'emailStorage' => $imapUsageQuota['mailQuota'] * 1024 * 1024,
            'quotas' => Tinebase_Config::getInstance()->{Tinebase_Config::QUOTA}->toArray(),
        ];
        
        self::assertNotEmpty($status);
        self::assertEquals($data, $status, print_r($user));
    }
}
